<?php

namespace App\Services;

use Aws\Iam\IamClient;
use Illuminate\Support\Facades\Log;
use Exception;

class IamService
{
    protected ?IamClient $client = null;

    /**
     * Get IAM client instance from S3 config endpoint.
     *
     * @return IamClient
     */
    public function getClient(): IamClient
    {
        if (!$this->client) {
            $endpoint = config('filesystems.disks.s3.endpoint', 'http://s3.localhost.localstack.cloud:4566');
            // Connect to localstack edge port (replaces s3 sub-domain with root gateway)
            $iamEndpoint = str_replace('s3.localhost.localstack.cloud', 'localhost', $endpoint);

            $this->client = new IamClient([
                'version' => 'latest',
                'region' => config('filesystems.disks.s3.region', 'us-east-1'),
                'endpoint' => $iamEndpoint,
                'credentials' => [
                    'key' => config('filesystems.disks.s3.key', 'test'),
                    'secret' => config('filesystems.disks.s3.secret', 'test'),
                ],
            ]);
        }
        return $this->client;
    }

    /**
     * Get IAM Connection Status.
     *
     * @return array
     */
    public function getConnectionStatus(): array
    {
        try {
            $client = $this->getClient();
            $client->listUsers(['MaxItems' => 1]);
            
            return [
                'connected' => true,
                'error' => null,
                'endpoint' => $client->getEndpoint(),
            ];
        } catch (Exception $e) {
            Log::error('LocalStack IAM connection failed: ' . $e->getMessage());
            
            return [
                'connected' => false,
                'error' => $e->getMessage(),
                'endpoint' => 'http://localhost:4566',
            ];
        }
    }

    /**
     * List all IAM Users with attached policy metadata.
     *
     * @return array
     * @throws Exception
     */
    public function listUsers(): array
    {
        try {
            $client = $this->getClient();
            $result = $client->listUsers();
            $users = [];

            if (isset($result['Users'])) {
                foreach ($result['Users'] as $user) {
                    $username = $user['UserName'];
                    
                    // Fetch attached policies
                    $policies = [];
                    try {
                        $policiesResult = $client->listAttachedUserPolicies(['UserName' => $username]);
                        if (isset($policiesResult['AttachedPolicies'])) {
                            foreach ($policiesResult['AttachedPolicies'] as $policy) {
                                $policies[] = [
                                    'name' => $policy['PolicyName'],
                                    'arn' => $policy['PolicyArn'],
                                ];
                            }
                        }
                    } catch (Exception $e) {
                        Log::warning("Could not fetch attached policies for user {$username}: " . $e->getMessage());
                    }

                    $users[] = [
                        'name' => $username,
                        'arn' => $user['Arn'],
                        'id' => $user['UserId'],
                        'created_at' => $user['CreateDate'],
                        'policies' => $policies,
                    ];
                }
            }

            return $users;
        } catch (Exception $e) {
            Log::error('Failed to list IAM Users: ' . $e->getMessage());
            throw new Exception('Unable to list IAM users: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create IAM User.
     *
     * @param string $username
     * @return void
     * @throws Exception
     */
    public function createUser(string $username): void
    {
        if (!preg_match('/^[a-zA-Z0-9+=,.@_-]+$/', $username)) {
            throw new Exception('Invalid user name. Allowed characters: alphanumeric and +=,.@_-');
        }

        try {
            $this->getClient()->createUser(['UserName' => $username]);
        } catch (Exception $e) {
            Log::error("Failed to create IAM User {$username}: " . $e->getMessage());
            throw new Exception('Unable to create user: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete IAM User.
     *
     * @param string $username
     * @return void
     * @throws Exception
     */
    public function deleteUser(string $username): void
    {
        try {
            $client = $this->getClient();

            // Detach attached policies first
            $policiesResult = $client->listAttachedUserPolicies(['UserName' => $username]);
            if (isset($policiesResult['AttachedPolicies'])) {
                foreach ($policiesResult['AttachedPolicies'] as $policy) {
                    $client->detachUserPolicy([
                        'UserName' => $username,
                        'PolicyArn' => $policy['PolicyArn'],
                    ]);
                }
            }

            $client->deleteUser(['UserName' => $username]);
        } catch (Exception $e) {
            Log::error("Failed to delete IAM User {$username}: " . $e->getMessage());
            throw new Exception('Unable to delete user: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * List all IAM Roles.
     *
     * @return array
     * @throws Exception
     */
    public function listRoles(): array
    {
        try {
            $client = $this->getClient();
            $result = $client->listRoles();
            $roles = [];

            if (isset($result['Roles'])) {
                foreach ($result['Roles'] as $role) {
                    $roles[] = [
                        'name' => $role['RoleName'],
                        'arn' => $role['Arn'],
                        'created_at' => $role['CreateDate'],
                        'trust_policy' => urldecode($role['AssumeRolePolicyDocument']),
                    ];
                }
            }

            return $roles;
        } catch (Exception $e) {
            Log::error('Failed to list IAM Roles: ' . $e->getMessage());
            throw new Exception('Unable to list IAM roles: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create IAM Role.
     *
     * @param string $roleName
     * @param string $trustPolicy
     * @return void
     * @throws Exception
     */
    public function createRole(string $roleName, string $trustPolicy): void
    {
        try {
            // Validate trust policy JSON
            json_decode($trustPolicy);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON structure in assume role trust policy.');
            }

            $this->getClient()->createRole([
                'RoleName' => $roleName,
                'AssumeRolePolicyDocument' => $trustPolicy,
            ]);
        } catch (Exception $e) {
            Log::error("Failed to create IAM Role {$roleName}: " . $e->getMessage());
            throw new Exception('Unable to create role: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete IAM Role.
     *
     * @param string $roleName
     * @return void
     * @throws Exception
     */
    public function deleteRole(string $roleName): void
    {
        try {
            $this->getClient()->deleteRole(['RoleName' => $roleName]);
        } catch (Exception $e) {
            Log::error("Failed to delete IAM Role {$roleName}: " . $e->getMessage());
            throw new Exception('Unable to delete role: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * List Custom Policies.
     *
     * @return array
     * @throws Exception
     */
    public function listPolicies(): array
    {
        try {
            $client = $this->getClient();
            // Fetch only locally created custom policies (ignoring global AWS policies)
            $result = $client->listPolicies(['Scope' => 'Local']);
            $policies = [];

            if (isset($result['Policies'])) {
                foreach ($result['Policies'] as $policy) {
                    $policies[] = [
                        'name' => $policy['PolicyName'],
                        'arn' => $policy['Arn'],
                        'attachment_count' => $policy['AttachmentCount'],
                        'created_at' => $policy['CreateDate'],
                    ];
                }
            }

            return $policies;
        } catch (Exception $e) {
            Log::error('Failed to list IAM Policies: ' . $e->getMessage());
            throw new Exception('Unable to list custom policies: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create Reusable Policy.
     *
     * @param string $policyName
     * @param string $policyDoc
     * @return void
     * @throws Exception
     */
    public function createPolicy(string $policyName, string $policyDoc): void
    {
        try {
            // Validate policy JSON
            json_decode($policyDoc);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON structure in policy document.');
            }

            $this->getClient()->createPolicy([
                'PolicyName' => $policyName,
                'PolicyDocument' => $policyDoc,
            ]);
        } catch (Exception $e) {
            Log::error("Failed to create IAM Policy {$policyName}: " . $e->getMessage());
            throw new Exception('Unable to create policy: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete Custom Policy.
     *
     * @param string $arn
     * @return void
     * @throws Exception
     */
    public function deletePolicy(string $arn): void
    {
        try {
            $this->getClient()->deletePolicy(['PolicyArn' => $arn]);
        } catch (Exception $e) {
            Log::error("Failed to delete IAM Policy {$arn}: " . $e->getMessage());
            throw new Exception('Unable to delete policy: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Attach policy to user.
     *
     * @param string $username
     * @param string $policyArn
     * @return void
     * @throws Exception
     */
    public function attachUserPolicy(string $username, string $policyArn): void
    {
        try {
            $this->getClient()->attachUserPolicy([
                'UserName' => $username,
                'PolicyArn' => $policyArn,
            ]);
        } catch (Exception $e) {
            Log::error("Failed to attach policy {$policyArn} to user {$username}: " . $e->getMessage());
            throw new Exception('Unable to attach policy: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Detach policy from user.
     *
     * @param string $username
     * @param string $policyArn
     * @return void
     * @throws Exception
     */
    public function detachUserPolicy(string $username, string $policyArn): void
    {
        try {
            $this->getClient()->detachUserPolicy([
                'UserName' => $username,
                'PolicyArn' => $policyArn,
            ]);
        } catch (Exception $e) {
            Log::error("Failed to detach policy {$policyArn} from user {$username}: " . $e->getMessage());
            throw new Exception('Unable to detach policy: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Simulate JSON Policy document evaluation.
     *
     * @param string $policyJson
     * @param string $action
     * @param string $resource
     * @return array
     * @throws Exception
     */
    public function simulatePolicy(string $policyJson, string $action, string $resource): array
    {
        $policy = json_decode($policyJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON: ' . json_last_error_msg());
        }

        if (!isset($policy['Statement'])) {
            throw new Exception("Missing required 'Statement' block in IAM Policy Document.");
        }

        $statements = $policy['Statement'];
        if (!is_array($statements)) {
            $statements = [$statements];
        }

        $allowed = false;
        $reason = "Denied (Default Deny - No matching Allow statement found)";
        $matchedStatement = null;

        foreach ($statements as $index => $statement) {
            $effect = $statement['Effect'] ?? 'Deny';
            
            // Normalize actions and resources to arrays
            $stmtActions = isset($statement['Action']) ? (is_array($statement['Action']) ? $statement['Action'] : [$statement['Action']]) : [];
            $stmtResources = isset($statement['Resource']) ? (is_array($statement['Resource']) ? $statement['Resource'] : [$statement['Resource']]) : [];

            // Check if action matches
            $actionMatches = false;
            foreach ($stmtActions as $stmtAction) {
                if ($this->wildcardMatch($stmtAction, $action)) {
                    $actionMatches = true;
                    break;
                }
            }

            // Check if resource matches
            $resourceMatches = false;
            foreach ($stmtResources as $stmtResource) {
                if ($this->wildcardMatch($stmtResource, $resource)) {
                    $resourceMatches = true;
                    break;
                }
            }

            if ($actionMatches && $resourceMatches) {
                if ($effect === 'Deny') {
                    // Explicit Deny always overrides Allow immediately
                    return [
                        'allowed' => false,
                        'reason' => "Denied (Explicit Deny found in Statement #" . ($index + 1) . ")",
                        'matched_statement' => json_encode($statement, JSON_PRETTY_PRINT),
                    ];
                } elseif ($effect === 'Allow') {
                    $allowed = true;
                    $reason = "Allowed (Allow matched in Statement #" . ($index + 1) . ")";
                    $matchedStatement = json_encode($statement, JSON_PRETTY_PRINT);
                }
            }
        }

        return [
            'allowed' => $allowed,
            'reason' => $reason,
            'matched_statement' => $matchedStatement,
        ];
    }

    /**
     * Check if a pattern matches target using wildcards (*).
     *
     * @param string $pattern
     * @param string $target
     * @return bool
     */
    protected function wildcardMatch(string $pattern, string $target): bool
    {
        if ($pattern === '*') {
            return true;
        }

        // Convert AWS wildcard string to regex pattern
        // Escape standard characters, then replace \* with .*
        $escaped = preg_quote($pattern, '/');
        $regex = '/^' . str_replace('\*', '.*', $escaped) . '$/i';

        return (bool) preg_match($regex, $target);
    }
}
