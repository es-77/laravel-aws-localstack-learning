@extends('layouts.app')

@section('title', 'IAM Overview')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="border-b border-slate-800 pb-5">
        <div class="flex items-center gap-2 text-xs font-semibold text-[#ff9900] uppercase tracking-wider mb-1">
            <i class="fa-solid fa-user-shield"></i>
            <span>Identity and Access Management</span>
        </div>
        <h1 class="text-3xl font-extrabold tracking-tight text-white">IAM Overview</h1>
    </div>

    <!-- Connection Status Banner -->
    @if(!$status['connected'])
        <div class="bg-rose-950/40 border border-rose-800 text-rose-200 p-5 rounded-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-start gap-4">
                <div class="bg-rose-900/50 p-3 rounded-lg text-rose-400">
                    <i class="fa-solid fa-circle-exclamation text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white">LocalStack IAM Connection Down</h3>
                    <p class="text-sm text-rose-300/80 mt-1">Unable to connect to LocalStack IAM at <code>{{ $status['endpoint'] }}</code>. Make sure Docker and LocalStack are running.</p>
                </div>
            </div>
            <div class="shrink-0">
                <span class="bg-rose-900/60 border border-rose-800 text-rose-300 text-xs px-3 py-1.5 rounded-lg font-semibold uppercase tracking-wider">
                    IAM Offline
                </span>
            </div>
        </div>
    @else
        <div class="bg-emerald-950/20 border border-emerald-800/60 text-emerald-200 p-5 rounded-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-start gap-4">
                <div class="bg-emerald-900/20 p-3 rounded-lg text-emerald-400">
                    <i class="fa-solid fa-circle-check text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white text-emerald-400">LocalStack IAM Connected</h3>
                    <p class="text-sm text-emerald-300/80 mt-1">Connected successfully to LocalStack mock IAM engine running locally on port 4566.</p>
                </div>
            </div>
            <div class="shrink-0 flex items-center gap-2 bg-emerald-950/65 border border-emerald-800 text-emerald-400 text-xs px-3.5 py-1.5 rounded-lg font-bold">
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-ping"></span>
                <span>Active</span>
            </div>
        </div>
    @endif

    <!-- Statistics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Users Count -->
        <a href="{{ route('iam.users.index') }}" class="bg-slate-900 border border-slate-800 hover:border-slate-700 rounded-xl p-6 shadow-sm flex items-center justify-between transition">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">IAM Users</span>
                <span class="text-3xl font-extrabold text-white">{{ $status['connected'] ? $totalUsers : '--' }}</span>
            </div>
            <div class="bg-slate-800 text-[#ff9900] p-4 rounded-xl">
                <i class="fa-solid fa-users text-2xl"></i>
            </div>
        </a>

        <!-- Roles Count -->
        <a href="{{ route('iam.roles.index') }}" class="bg-slate-900 border border-slate-800 hover:border-slate-700 rounded-xl p-6 shadow-sm flex items-center justify-between transition">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">IAM Roles</span>
                <span class="text-3xl font-extrabold text-white">{{ $status['connected'] ? $totalRoles : '--' }}</span>
            </div>
            <div class="bg-slate-800 text-indigo-400 p-4 rounded-xl">
                <i class="fa-solid fa-user-gear text-2xl"></i>
            </div>
        </a>

        <!-- Policies Count -->
        <a href="{{ route('iam.policies.index') }}" class="bg-slate-900 border border-slate-800 hover:border-slate-700 rounded-xl p-6 shadow-sm flex items-center justify-between transition">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">IAM Policies</span>
                <span class="text-3xl font-extrabold text-white">{{ $status['connected'] ? $totalPolicies : '--' }}</span>
            </div>
            <div class="bg-slate-800 text-teal-400 p-4 rounded-xl">
                <i class="fa-solid fa-file-shield text-2xl"></i>
            </div>
        </a>
    </div>

    <!-- Educational Description -->
    <div class="bg-aws-slate border border-aws-slate rounded-xl p-6 shadow-inner relative overflow-hidden">
        <h3 class="text-md font-bold text-[#ff9900] uppercase tracking-wider mb-2 flex items-center gap-2">
            <i class="fa-solid fa-graduation-cap text-base"></i>
            <span>What are you learning?</span>
        </h3>
        <h4 class="text-lg font-bold text-white mb-2">What is AWS IAM?</h4>
        <p class="text-slate-300 text-sm leading-relaxed max-w-4xl">
            AWS **IAM (Identity and Access Management)** is a service that helps you control access to AWS resources. It controls both **authentication** (who you are, verified by credentials) and **authorization** (what you are allowed to do, defined by policies).
        </p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 text-xs">
            <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800/80">
                <span class="font-bold text-[#ff9900] block mb-1">Users</span>
                Represent individuals or services that interact with AWS. Users have permanent credentials like access keys or passwords.
            </div>
            <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800/80">
                <span class="font-bold text-[#ff9900] block mb-1">Roles</span>
                Identities assumed by trusted entities (like Lambda functions or EC2 instances) to obtain temporary security credentials.
            </div>
            <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800/80">
                <span class="font-bold text-[#ff9900] block mb-1">Policies</span>
                JSON documents that define permissions (Allow or Deny actions on specific AWS resource scopes).
            </div>
        </div>
    </div>
</div>
@endsection
