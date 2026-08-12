# Laravel AWS LocalStack Learning Lab

A practical Laravel project for learning AWS services locally with LocalStack.

## AWS Learning Roadmap

| Stage | AWS Service | What You Will Learn | Status |
|---:|---|---|---|
| 1 | **S3** | Files, buckets, uploads, permissions | ⬜ |
| 2 | **IAM** | Users, roles, policies, permissions | ⬜ |
| 3 | **DynamoDB** | NoSQL database | ⬜ |
| 4 | **SQS** | Queues and background jobs | ⬜ |
| 5 | **SNS** | Notifications and pub/sub | ⬜ |
| 6 | **Lambda** | Serverless functions | ⬜ |
| 7 | **API Gateway** | APIs → Lambda | ⬜ |
| 8 | **EventBridge** | Event-driven architecture | ⬜ |
| 9 | **CloudWatch** | Logs and monitoring | ⬜ |
| 10 | **RDS** | Managed MySQL/PostgreSQL | ⬜ |
| 11 | **ECS** | Docker containers on AWS | ⬜ |
| 12 | **EC2** | Virtual servers | ⬜ |
| 13 | **VPC** | AWS networking | ⬜ |
| 14 | **CloudFront** | CDN | ⬜ |
| 15 | **Route 53** | DNS | ⬜ |

## Goal

This repository teaches AWS through practical Laravel + LocalStack examples. Each stage should cover:

1. AWS concept
2. AWS CLI
3. LocalStack
4. Laravel integration where appropriate
5. Testing
6. Permissions/security
7. LocalStack → real AWS migration

## Technology Stack

- Laravel
- PHP
- Composer
- Docker
- LocalStack
- AWS CLI
- AWS SDK for PHP
- Flysystem
- Git/GitHub

# Stage 1 — S3

## What to Learn

- Buckets
- Objects
- Object keys
- Uploads
- Downloads
- Delete operations
- Laravel integration
- Permissions
- Bucket policies
- Presigned URLs
- S3 security

## Start LocalStack

```bash
lstk start --type aws
```

Check:

```bash
lstk status
```

Health:

```bash
curl http://localhost:4566/_localstack/health
```

## Create S3 Bucket

```bash
lstk aws s3 mb s3://aws-learning-bucket
```

List buckets:

```bash
lstk aws s3 ls
```

## Laravel S3 Adapter

```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

## `.env`

```env
FILESYSTEM_DISK=s3

AWS_ACCESS_KEY_ID=test
AWS_SECRET_ACCESS_KEY=test
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=aws-learning-bucket
AWS_USE_PATH_STYLE_ENDPOINT=true
AWS_ENDPOINT=http://s3.localhost.localstack.cloud:4566
```

Then:

```bash
php artisan config:clear
```

## Upload

```php
use Illuminate\Support\Facades\Storage;

Storage::disk('s3')->put(
    'hello.txt',
    'Hello from Laravel + LocalStack S3!'
);
```

Verify:

```bash
lstk aws s3 ls s3://aws-learning-bucket
```

## Upload User File

```php
$path = Storage::disk('s3')->putFile(
    'uploads',
    $request->file('file')
);
```

## Download

```php
return Storage::disk('s3')->download('hello.txt');
```

## Read

```php
$content = Storage::disk('s3')->get('hello.txt');
```

## Check Exists

```php
Storage::disk('s3')->exists('hello.txt');
```

## Delete

```php
Storage::disk('s3')->delete('hello.txt');
```

## S3 Checklist

- [ ] Create bucket
- [ ] List buckets
- [ ] Upload object
- [ ] Download object
- [ ] Read object
- [ ] Delete object
- [ ] Upload from Laravel
- [ ] Download from Laravel
- [ ] Delete from Laravel
- [ ] Understand bucket vs object
- [ ] Understand object keys
- [ ] Learn S3 permissions
- [ ] Learn bucket policies
- [ ] Learn IAM integration
- [ ] Learn presigned URLs
- [ ] Understand S3 security

# Stage 2 — IAM

Learn:

- Users
- Groups
- Roles
- Policies
- Permissions
- Identity-based policies
- Resource-based policies
- Least privilege
- Access keys
- Temporary credentials

Status: ⬜ Not started

# Stage 3 — DynamoDB

Learn:

- Tables
- Items
- Attributes
- Partition keys
- Sort keys
- Query
- Scan
- Indexes
- Conditional writes
- TTL
- Streams

Status: ⬜ Not started

# Stage 4 — SQS

Learn:

- Queues
- Producers
- Consumers
- Messages
- Visibility timeout
- Long polling
- Dead-letter queues
- Retries
- Background jobs

Example:

```text
Laravel → SQS → Worker → Process Job
```

Status: ⬜ Not started

# Stage 5 — SNS

Learn:

- Topics
- Publishers
- Subscribers
- Fan-out
- SQS subscriptions
- Notifications

Status: ⬜ Not started

# Stage 6 — Lambda

Learn:

- Functions
- Runtime
- Handler
- Events
- Environment variables
- IAM execution roles
- Cold starts
- Logs
- Lambda + S3
- Lambda + SQS

Status: ⬜ Not started

# Stage 7 — API Gateway

Learn:

- HTTP APIs
- REST APIs
- Routes
- Methods
- Lambda integration
- Authentication
- Deployment
- Request/response handling

Status: ⬜ Not started

# Stage 8 — EventBridge

Learn:

- Events
- Event buses
- Rules
- Event patterns
- Targets
- Scheduled events
- Service integration

Status: ⬜ Not started

# Stage 9 — CloudWatch

Learn:

- Logs
- Log groups
- Log streams
- Metrics
- Alarms
- Monitoring
- Troubleshooting

Status: ⬜ Not started

# Stage 10 — RDS

Learn:

- MySQL
- PostgreSQL
- Database instances
- Backups
- Security
- Connections
- High availability
- Read replicas

Status: ⬜ Not started

# Stage 11 — ECS

Learn:

- Docker
- Images
- Containers
- Task definitions
- Services
- Clusters
- Networking
- Load balancing
- Fargate

Status: ⬜ Not started

# Stage 12 — EC2

Learn:

- Instances
- AMIs
- SSH
- Instance types
- Storage
- Security groups
- Elastic IP
- User data
- Auto Scaling

Status: ⬜ Not started

# Stage 13 — VPC

Learn:

- VPC
- CIDR
- Subnets
- Public/private subnets
- Route tables
- Internet Gateway
- NAT Gateway
- Security Groups
- Network ACLs

Status: ⬜ Not started

# Stage 14 — CloudFront

Learn:

- Distributions
- Origins
- Caching
- Cache policies
- HTTPS
- Static assets
- S3 + CloudFront

Status: ⬜ Not started

# Stage 15 — Route 53

Learn:

- Hosted zones
- DNS records
- A records
- CNAME
- Alias records
- Health checks
- Domain routing

Status: ⬜ Not started

# Recommended Learning Flow

```text
AWS concept
    ↓
AWS CLI
    ↓
LocalStack
    ↓
Laravel integration
    ↓
Testing
    ↓
Permissions/security
    ↓
Terraform
    ↓
Real AWS
```

The same Laravel learning project should be extended through all 15 stages instead of creating unrelated examples.

## Final Goal

Build an architecture where Laravel works with AWS services such as:

```text
Route 53
   ↓
CloudFront
   ↓
API / Load Balancer
   ↓
ECS / Lambda
   ↓
S3 / SQS / SNS / DynamoDB / RDS
   ↓
CloudWatch
```

LocalStack is the local learning and testing environment. The final goal is to understand the same AWS concepts well enough to deploy appropriate parts to real AWS.
