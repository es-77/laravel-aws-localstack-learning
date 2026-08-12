<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\IamService;
use Exception;
use Illuminate\Support\Facades\Log;

class IamController extends Controller
{
    protected IamService $iamService;

    public function __construct(IamService $iamService)
    {
        $this->iamService = $iamService;
    }

    /**
     * IAM Overview Page.
     */
    public function overview()
    {
        $status = $this->iamService->getConnectionStatus();
        $totalUsers = 0;
        $totalRoles = 0;
        $totalPolicies = 0;

        if ($status['connected']) {
            try {
                $totalUsers = count($this->iamService->listUsers());
                $totalRoles = count($this->iamService->listRoles());
                $totalPolicies = count($this->iamService->listPolicies());
            } catch (Exception $e) {
                Log::warning('Failed to load IAM overview counts: ' . $e->getMessage());
            }
        }

        return view('iam.overview', compact('status', 'totalUsers', 'totalRoles', 'totalPolicies'));
    }

    /**
     * List IAM Users.
     */
    public function users()
    {
        $status = $this->iamService->getConnectionStatus();
        $users = [];
        $policies = [];
        $error = null;

        if ($status['connected']) {
            try {
                $users = $this->iamService->listUsers();
                $policies = $this->iamService->listPolicies();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = "Unable to connect to LocalStack IAM. Please ensure Docker is running.";
        }

        return view('iam.users', compact('users', 'policies', 'error', 'status'));
    }

    /**
     * Create IAM User.
     */
    public function createUser(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
        ]);

        $username = trim($request->input('username'));

        try {
            $this->iamService->createUser($username);
            return redirect()->route('iam.users.index')->with('success', "IAM User '{$username}' created successfully!");
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete IAM User.
     */
    public function deleteUser(string $username)
    {
        try {
            $this->iamService->deleteUser($username);
            return redirect()->route('iam.users.index')->with('success', "IAM User '{$username}' deleted successfully.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Attach Policy to User.
     */
    public function attachPolicy(Request $request, string $username)
    {
        $request->validate([
            'policy_arn' => 'required|string',
        ]);

        $policyArn = $request->input('policy_arn');

        try {
            $this->iamService->attachUserPolicy($username, $policyArn);
            return redirect()->route('iam.users.index')->with('success', "Attached policy successfully.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Detach Policy from User.
     */
    public function detachPolicy(Request $request, string $username)
    {
        $request->validate([
            'policy_arn' => 'required|string',
        ]);

        $policyArn = $request->input('policy_arn');

        try {
            $this->iamService->detachUserPolicy($username, $policyArn);
            return redirect()->route('iam.users.index')->with('success', "Detached policy successfully.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * List IAM Roles.
     */
    public function roles()
    {
        $status = $this->iamService->getConnectionStatus();
        $roles = [];
        $error = null;
        
        $defaultTrustPolicy = json_encode([
            'Version' => '2012-10-17',
            'Statement' => [
                [
                    'Effect' => 'Allow',
                    'Principal' => [
                        'Service' => 'lambda.amazonaws.com',
                    ],
                    'Action' => 'sts:AssumeRole',
                ],
            ],
        ], JSON_PRETTY_PRINT);

        if ($status['connected']) {
            try {
                $roles = $this->iamService->listRoles();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = "Unable to connect to LocalStack IAM.";
        }

        return view('iam.roles', compact('roles', 'defaultTrustPolicy', 'error', 'status'));
    }

    /**
     * Create IAM Role.
     */
    public function createRole(Request $request)
    {
        $request->validate([
            'role_name' => 'required|string',
            'trust_policy' => 'required|string',
        ]);

        $roleName = trim($request->input('role_name'));
        $trustPolicy = $request->input('trust_policy');

        try {
            $this->iamService->createRole($roleName, $trustPolicy);
            return redirect()->route('iam.roles.index')->with('success', "IAM Role '{$roleName}' created successfully.");
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete IAM Role.
     */
    public function deleteRole(string $role)
    {
        try {
            $this->iamService->deleteRole($role);
            return redirect()->route('iam.roles.index')->with('success', "IAM Role '{$role}' deleted successfully.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * List Custom Policies.
     */
    public function policies()
    {
        $status = $this->iamService->getConnectionStatus();
        $policies = [];
        $error = null;
        
        $defaultPolicyDoc = json_encode([
            'Version' => '2012-10-17',
            'Statement' => [
                [
                    'Effect' => 'Allow',
                    'Action' => [
                        's3:GetObject',
                        's3:ListBucket',
                    ],
                    'Resource' => [
                        'arn:aws:s3:::aws-learning-bucket',
                        'arn:aws:s3:::aws-learning-bucket/*',
                    ],
                ],
            ],
        ], JSON_PRETTY_PRINT);

        if ($status['connected']) {
            try {
                $policies = $this->iamService->listPolicies();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = "Unable to connect to LocalStack IAM.";
        }

        return view('iam.policies', compact('policies', 'defaultPolicyDoc', 'error', 'status'));
    }

    /**
     * Create Custom Policy.
     */
    public function createPolicy(Request $request)
    {
        $request->validate([
            'policy_name' => 'required|string',
            'policy_document' => 'required|string',
        ]);

        $policyName = trim($request->input('policy_name'));
        $policyDoc = $request->input('policy_document');

        try {
            $this->iamService->createPolicy($policyName, $policyDoc);
            return redirect()->route('iam.policies.index')->with('success', "IAM Policy '{$policyName}' created successfully.");
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete Custom Policy.
     */
    public function deletePolicy(Request $request)
    {
        $request->validate([
            'arn' => 'required|string',
        ]);

        $arn = $request->input('arn');

        try {
            $this->iamService->deletePolicy($arn);
            return redirect()->route('iam.policies.index')->with('success', "IAM Policy deleted successfully.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Policy Simulator.
     */
    public function simulator(Request $request)
    {
        $status = $this->iamService->getConnectionStatus();
        
        $policyDocument = $request->input('policy_document');
        $action = $request->input('action');
        $resource = $request->input('resource');
        
        $result = null;
        $error = null;

        if ($request->isMethod('POST')) {
            $request->validate([
                'policy_document' => 'required|string',
                'action' => 'required|string',
                'resource' => 'required|string',
            ]);

            try {
                $result = $this->iamService->simulatePolicy($policyDocument, $action, $resource);
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            // Default placeholder JSON
            $policyDocument = json_encode([
                'Version' => '2012-10-17',
                'Statement' => [
                    [
                        'Effect' => 'Allow',
                        'Action' => 's3:GetObject',
                        'Resource' => 'arn:aws:s3:::my-learning-bucket/uploads/*'
                    ],
                    [
                        'Effect' => 'Deny',
                        'Action' => 's3:GetObject',
                        'Resource' => 'arn:aws:s3:::my-learning-bucket/uploads/private/*'
                    ]
                ]
            ], JSON_PRETTY_PRINT);
            
            $action = 's3:GetObject';
            $resource = 'arn:aws:s3:::my-learning-bucket/uploads/file.txt';
        }

        return view('iam.simulator', compact('status', 'policyDocument', 'action', 'resource', 'result', 'error'));
    }
}
