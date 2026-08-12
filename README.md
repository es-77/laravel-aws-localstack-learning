# Laravel AWS LocalStack Learning Lab

A practical Laravel application for learning AWS services locally using LocalStack.

## Project Progress

### Stage 1 — S3 ✅

**Completed S3 Learning Module Features:**
- **S3 Dashboard / Overview:** Displays connection health status, active credentials/endpoints, bucket metrics (count, total size, object count), and recent files.
- **Bucket Management:** Supports listing active buckets, creating buckets (with name validation according to AWS S3 rules), and deleting buckets (supporting safety checks and forced recursive deletion).
- **Object/File Explorer:** Browses files and virtual folders under prefixes with folder icons and navigation breadcrumbs.
- **Interactive File Upload:** Drag-and-drop multi-file upload with active real-time upload progress bars (implemented via JS XHR).
- **Secure File Download:** Streams objects directly through a Laravel controller, protecting AWS keys.
- **Inline File Preview:** Renders images (JPG, PNG, GIF, WebP) and PDFs inline in a modal, falling back to download action for unsupported types.
- **Delete Object:** Safe object deletion with confirmation prompts.
- **S3 Configuration & Permissions:** Visual summary of AWS environments and guides on IAM Users, Credentials, Bucket Policies, and ACLs.
- **AWS CLI Command Integration:** Displays equivalent `lstk aws s3` CLI commands on all active pages.

**Next Up:**
- **Stage 2 — IAM ⬜** *(Not started - Locked)*

---

## AWS Learning Roadmap

| Stage | AWS Service | What You Will Learn | Status |
|---:|---|---|---|
| 1 | **S3** | Files, buckets, uploads, permissions | ✅ Completed |
| 2 | **IAM** | Users, roles, policies, permissions | ⬜ Locked / Coming Soon |
| 3 | **DynamoDB** | NoSQL database | ⬜ Locked / Coming Soon |
| 4 | **SQS** | Queues and background jobs | ⬜ Locked / Coming Soon |
| 5 | **SNS** | Notifications and pub/sub | ⬜ Locked / Coming Soon |
| 6 | **Lambda** | Serverless functions | ⬜ Locked / Coming Soon |
| 7 | **API Gateway** | APIs → Lambda | ⬜ Locked / Coming Soon |
| 8 | **EventBridge** | Event-driven architecture | ⬜ Locked / Coming Soon |
| 9 | **CloudWatch** | Logs and monitoring | ⬜ Locked / Coming Soon |
| 10 | **RDS** | Managed MySQL/PostgreSQL | ⬜ Locked / Coming Soon |
| 11 | **ECS** | Docker containers on AWS | ⬜ Locked / Coming Soon |
| 12 | **EC2** | Virtual servers | ⬜ Locked / Coming Soon |
| 13 | **VPC** | AWS networking | ⬜ Locked / Coming Soon |
| 14 | **CloudFront** | CDN | ⬜ Locked / Coming Soon |
| 15 | **Route 53** | DNS | ⬜ Locked / Coming Soon |

---

## How to Run Locally

### 1. Requirements
- PHP 8.3+
- Composer
- Docker
- Node.js & NPM
- AWS CLI & LocalStack

### 2. Startup Commands
Start LocalStack:
```bash
docker compose up -d   # or your local localstack startup command
```

Start Laravel Development Server:
```bash
php artisan serve
```

Compile Assets:
```bash
npm run dev
```

Open `http://localhost:8000` in your browser.
