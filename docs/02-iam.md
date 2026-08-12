# Stage 2: IAM Integration (Identity and Access Management)

## Design Decisions: Simple, Educational IAM Management

IAM is the security gatekeeper of AWS. In local development environments (like the LocalStack community edition), IAM permissions are simulated at the API level (you can create users, roles, and attach policies), but LocalStack does not enforce resource blocks by default (e.g., S3 operations won't fail due to a lack of permissions unless LocalStack Pro with IAM enforcement is active).

To bridge this gap, this module focuses on two areas:
1. **LocalStack API Operations**: Integrating with IAM API endpoints using the AWS PHP SDK `IamClient` to create, read, and delete IAM Users, Roles, and Custom Policies.
2. **Interactive Local Policy Simulator**: An educational engine built directly into our application that evaluates standard JSON policy documents against specific actions and resources to teach developers policy resolution rules.

---

## IAM Architecture Overview

```
Request ──> IamController ──> IamService ──> AWS IamClient SDK ──> LocalStack IAM
                                    │
                                    └──> Custom Policy Simulator Engine (In-App)
```

### 1. Separation of Concerns
The `IamService` acts as the single interface to interact with LocalStack's IAM engine. It initializes the `IamClient` pointing to LocalStack and wraps SQS/S3 credential bindings. It also houses our local Policy Simulator evaluation parser.

### 2. Local Policy Simulator Evaluation Logic
To teach the rules of IAM policy evaluation, we implement a simple regex-matching evaluator:
- **Default Deny**: All requests are denied by default.
- **Explicit Deny**: If any statement has `"Effect": "Deny"` and matches the requested action and resource, the evaluation immediately returns `Denied (Explicit Deny)`. This overrides any Allow statement.
- **Explicit Allow**: If a statement has `"Effect": "Allow"`, and matches both the requested action and resource, it is marked as `Allowed`.
- **Wildcard Matching (`*`)**: The simulator translates AWS wildcards into standard regular expressions:
  - `s3:*` translates to `^s3:.*$`
  - `arn:aws:s3:::my-bucket/*` translates to `^arn:aws:s3:::my-bucket/.*$`

---

## Service Functions Summary (`app/Services/IamService.php`)

### 1. `getClient(): \Aws\Iam\IamClient`
Initializes the AWS PHP SDK `IamClient` using the general LocalStack edge endpoint `http://localhost:4566`.

### 2. `getConnectionStatus(): array`
Performs a lightweight `listUsers(['MaxItems' => 1])` request.
- If successful: returns `['connected' => true]`.
- If a connection error occurs: returns `['connected' => false, 'error' => $message]`.

### 3. `listUsers(): array`
Retrieves all IAM users via `listUsers()`. For each user, queries attached policy ARNs using `listAttachedUserPolicies` to show their bound policies.

### 4. `createUser(string $username): void`
Creates an IAM user in LocalStack S3. Checks that the username conforms to alphanumeric characters and symbols (`+=,.@_-`).

### 5. `deleteUser(string $username): void`
Detaches all attached policies from the user via `detachUserPolicy` before calling `deleteUser` to prevent orphan bindings.

### 6. `listRoles(): array`
Lists roles, retrieving Assume Role trust relationship policy documents (JSON format).

### 7. `createRole(string $roleName, string $assumeRolePolicyDocument): void`
Creates an IAM Role with a trust policy that defines which entity is authorized to assume the role.

### 8. `listPolicies(): array`
Lists custom created policies by calling `listPolicies(['Scope' => 'Local'])` to filter out default global AWS system policies, keeping the view clean.

### 9. `createPolicy(string $policyName, string $policyDocument): void`
Creates a reusable custom IAM policy.

### 10. `simulatePolicy(string $policyJson, string $action, string $resource): array`
Decodes the JSON policy and checks statements. Returns:
- `allowed`: Boolean indicating the final outcome.
- `reason`: Description of why the policy evaluated to Allow or Deny (e.g. "No matching Allow statement found" or "Explicit Deny statement matched").
- `matched_statement`: The JSON representation of the statement that determined the result.
