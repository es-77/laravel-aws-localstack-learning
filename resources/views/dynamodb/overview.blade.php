@extends('layouts.app')

@section('title', 'DynamoDB Overview')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="border-b border-slate-800 pb-5">
        <div class="flex items-center gap-2 text-xs font-semibold text-[#ff9900] uppercase tracking-wider mb-1">
            <i class="fa-solid fa-database"></i>
            <span>Amazon DynamoDB</span>
        </div>
        <h1 class="text-3xl font-extrabold tracking-tight text-white">DynamoDB Overview</h1>
    </div>

    <!-- Connection Status Banner -->
    @if(!$status['connected'])
        <div class="bg-rose-950/40 border border-rose-800 text-rose-200 p-5 rounded-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-start gap-4">
                <div class="bg-rose-900/50 p-3 rounded-lg text-rose-400">
                    <i class="fa-solid fa-circle-exclamation text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white">LocalStack DynamoDB Connection Down</h3>
                    <p class="text-sm text-rose-300/80 mt-1">Unable to connect to LocalStack DynamoDB at <code>{{ $status['endpoint'] }}</code>. Ensure Docker and LocalStack are running.</p>
                </div>
            </div>
            <div class="shrink-0">
                <span class="bg-rose-900/60 border border-rose-800 text-rose-300 text-xs px-3 py-1.5 rounded-lg font-semibold uppercase tracking-wider">
                    DynamoDB Offline
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
                    <h3 class="text-lg font-bold text-white text-emerald-400">LocalStack DynamoDB Connected</h3>
                    <p class="text-sm text-emerald-300/80 mt-1">Connected successfully to LocalStack mock DynamoDB database running locally.</p>
                </div>
            </div>
            <div class="shrink-0 flex items-center gap-2 bg-emerald-950/65 border border-emerald-800 text-emerald-400 text-xs px-3.5 py-1.5 rounded-lg font-bold">
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-ping"></span>
                <span>Active</span>
            </div>
        </div>
    @endif

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Tables Count -->
        <a href="{{ route('dynamodb.tables.index') }}" class="bg-slate-900 border border-slate-800 hover:border-slate-700 rounded-xl p-6 shadow-sm flex items-center justify-between transition">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">DynamoDB Tables</span>
                <span class="text-3xl font-extrabold text-white">{{ $status['connected'] ? $totalTables : '--' }}</span>
            </div>
            <div class="bg-slate-800 text-[#ff9900] p-4 rounded-xl">
                <i class="fa-solid fa-table text-2xl"></i>
            </div>
        </a>

        <!-- Total Items Count -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Total Table Items</span>
                <span class="text-3xl font-extrabold text-white">{{ $status['connected'] ? $totalItems : '--' }}</span>
            </div>
            <div class="bg-slate-800 text-indigo-400 p-4 rounded-xl">
                <i class="fa-solid fa-list text-2xl"></i>
            </div>
        </div>

        <!-- Total Storage Size -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Total Table Storage</span>
                <span class="text-3xl font-extrabold text-white">
                    @if(!$status['connected'])
                        --
                    @elseif($totalSize < 1024)
                        {{ $totalSize }} B
                    @elseif($totalSize < 1024 * 1024)
                        {{ round($totalSize / 1024, 2) }} KB
                    @else
                        {{ round($totalSize / (1024 * 1024), 2) }} MB
                    @endif
                </span>
            </div>
            <div class="bg-slate-800 text-teal-400 p-4 rounded-xl">
                <i class="fa-solid fa-database text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Educational Description -->
    <div class="bg-aws-slate border border-aws-slate rounded-xl p-6 shadow-inner relative overflow-hidden">
        <h3 class="text-md font-bold text-[#ff9900] uppercase tracking-wider mb-2 flex items-center gap-2">
            <i class="fa-solid fa-graduation-cap text-base"></i>
            <span>What are you learning?</span>
        </h3>
        <h4 class="text-lg font-bold text-white mb-2">What is Amazon DynamoDB?</h4>
        <p class="text-slate-300 text-sm leading-relaxed max-w-4xl">
            Amazon DynamoDB is a **NoSQL database** service that supports key-value and document data structures. Relational databases store data in columns and rows, but DynamoDB stores unstructured key-value records called **Items**.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 text-xs">
            <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800/80">
                <span class="font-bold text-[#ff9900] block mb-1">No Schemas (Schema-less)</span>
                You don't need to define any table column names when creating a DynamoDB table. The only attributes you must specify are the primary key attributes (Partition Key and optional Sort Key). Other item properties are added dynamically.
            </div>
            <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800/80">
                <span class="font-bold text-[#ff9900] block mb-1">On-Demand Scaling</span>
                By setting the billing mode to <code>PAY_PER_REQUEST</code>, you only pay for the exact read/write requests you execute, making it perfect for rapid local sandbox testing.
            </div>
        </div>
    </div>
</div>
@endsection
