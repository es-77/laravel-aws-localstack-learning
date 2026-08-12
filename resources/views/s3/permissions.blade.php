@extends('layouts.app')

@section('title', 'S3 Configuration & Permissions')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-800 pb-5">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-[#ff9900] uppercase tracking-wider mb-1">
                <i class="fa-solid fa-box-open"></i>
                <span>Amazon Simple Storage Service</span>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">S3 Connection &amp; Permissions</h1>
        </div>
    </div>

    <!-- Configuration Display -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm space-y-4">
        <h3 class="font-bold text-lg text-white flex items-center gap-2 border-b border-slate-800 pb-3">
            <i class="fa-solid fa-sliders text-[#ff9900]"></i>
            <span>Environment Parameters (.env / config)</span>
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
            <div class="space-y-4">
                <div>
                    <span class="text-xs text-slate-500 block font-semibold uppercase tracking-wider">FILESYSTEM_DISK</span>
                    <code class="text-slate-300 font-mono">s3</code>
                    <span class="text-[10px] text-slate-500 ml-2">(Default application storage is S3)</span>
                </div>
                <div>
                    <span class="text-xs text-slate-500 block font-semibold uppercase tracking-wider">AWS_ENDPOINT</span>
                    <code class="text-slate-300 font-mono">{{ $status['endpoint'] }}</code>
                </div>
                <div>
                    <span class="text-xs text-slate-500 block font-semibold uppercase tracking-wider">AWS_DEFAULT_REGION</span>
                    <span class="text-slate-300 font-mono">{{ $status['region'] }}</span>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <span class="text-xs text-slate-500 block font-semibold uppercase tracking-wider">AWS_ACCESS_KEY_ID</span>
                    <code class="text-slate-300 font-mono">{{ config('filesystems.disks.s3.key') }}</code>
                </div>
                <div>
                    <span class="text-xs text-slate-500 block font-semibold uppercase tracking-wider">AWS_SECRET_ACCESS_KEY</span>
                    <span class="text-[#ff9900] font-mono text-xs select-none">•••••••••••••••• (Masked for Security)</span>
                </div>
                <div>
                    <span class="text-xs text-slate-500 block font-semibold uppercase tracking-wider">AWS_BUCKET</span>
                    <span class="text-slate-300 font-mono">{{ $status['bucket'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Educational Permissions Guide -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- IAM & Credentials Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm space-y-4">
            <h4 class="font-bold text-md text-white flex items-center gap-2 border-b border-slate-800 pb-2">
                <i class="fa-solid fa-key text-[#ff9900] text-sm"></i>
                <span>1. IAM Users &amp; Credentials</span>
            </h4>
            <p class="text-xs text-slate-300 leading-relaxed">
                AWS uses **IAM (Identity and Access Management)** to authenticate clients. An IAM user receives an **Access Key ID** (behaves like a username) and a **Secret Access Key** (behaves like a password). 
            </p>
            <p class="text-xs text-slate-300 leading-relaxed">
                In a secure Laravel configuration, you retrieve credentials using environment variables (<code>env('AWS_ACCESS_KEY_ID')</code>) instead of committing them to code repositories. In Stage 2 of this lab, we will build a custom IAM emulator to learn how users and permissions are bound together.
            </p>
            <div class="bg-slate-950 p-3 rounded-lg border border-slate-805 text-[11px] text-[#ff9900] font-mono">
                <span class="text-slate-500 block"># Test AWS credentials configuration via CLI</span>
                <code>aws sts get-caller-identity</code>
            </div>
        </div>

        <!-- Bucket Policies Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm space-y-4">
            <h4 class="font-bold text-md text-white flex items-center gap-2 border-b border-slate-800 pb-2">
                <i class="fa-solid fa-file-contract text-[#ff9900] text-sm"></i>
                <span>2. S3 Bucket Policies</span>
            </h4>
            <p class="text-xs text-slate-300 leading-relaxed">
                A **Bucket Policy** is a JSON-based configuration attached directly to an S3 bucket. It defines what actions (such as <code>s3:GetObject</code>, <code>s3:PutObject</code>) can be performed on the bucket by specific entities (referred to as **Principals**).
            </p>
            <div class="bg-slate-950 p-3 rounded-lg border border-slate-805 font-mono text-[10px] text-slate-400 overflow-x-auto">
                <span class="text-[#ff9900] font-semibold">// Example Read-Only Policy</span>
{
  "Version": "2012-10-17",
  "Statement": [{
    "Effect": "Allow",
    "Principal": "*",
    "Action": "s3:GetObject",
    "Resource": "arn:aws:s3:::my-bucket/*"
  }]
}
            </div>
        </div>

        <!-- Object Access & ACLs Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm space-y-4">
            <h4 class="font-bold text-md text-white flex items-center gap-2 border-b border-slate-800 pb-2">
                <i class="fa-solid fa-lock-open text-[#ff9900] text-sm"></i>
                <span>3. S3 Access Control Lists (ACLs)</span>
            </h4>
            <p class="text-xs text-slate-300 leading-relaxed">
                **Access Control Lists (ACLs)** are legacy security tables attached to individual buckets or object items. ACLs allow you to define read/write permissions for specific AWS accounts or predefined groups (like public access).
            </p>
            <p class="text-xs text-slate-300 leading-relaxed">
                *Note:* AWS recommends disabling ACLs in favor of modern Bucket Policies for security consistency.
            </p>
        </div>

        <!-- Block Public Access Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm space-y-4">
            <h4 class="font-bold text-md text-white flex items-center gap-2 border-b border-slate-800 pb-2">
                <i class="fa-solid fa-shield-halved text-[#ff9900] text-sm"></i>
                <span>4. Block Public Access</span>
            </h4>
            <p class="text-xs text-slate-300 leading-relaxed">
                To prevent accidental data leaks, S3 includes a master switch called **Block Public Access**. When enabled (which is the default on AWS), public policies and ACLs are overridden. This prevents objects from being made public on the web even if the bucket policy permits it.
            </p>
        </div>
    </div>

    <!-- Educational "What are you learning?" Panel -->
    <div class="bg-aws-slate border border-aws-slate rounded-xl p-6 shadow-inner relative overflow-hidden">
        <h3 class="text-md font-bold text-[#ff9900] uppercase tracking-wider mb-2 flex items-center gap-2">
            <i class="fa-solid fa-graduation-cap text-base"></i>
            <span>What are you learning?</span>
        </h3>
        <h4 class="text-lg font-bold text-white mb-2">Permissions control who can access S3</h4>
        <p class="text-slate-300 text-sm leading-relaxed max-w-4xl">
            S3 uses a three-tier security check structure: **IAM user policy** (who is requesting?), **Bucket Policy** (does this bucket allow the request?), and **Object ACL** (does the object item permit access?). If any check explicitly denies access, the request is rejected immediately.
        </p>
    </div>
</div>
@endsection
