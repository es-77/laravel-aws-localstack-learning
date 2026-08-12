@extends('layouts.app')

@section('title', 'S3 Overview')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-800 pb-5">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-[#ff9900] uppercase tracking-wider mb-1">
                <i class="fa-solid fa-box-open"></i>
                <span>Amazon Simple Storage Service</span>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">S3 Overview</h1>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('s3.buckets.index') }}" class="bg-aws-slate hover:bg-slate-700 text-white font-semibold text-sm px-4 py-2 rounded-lg border border-slate-700 transition flex items-center gap-2">
                <i class="fa-solid fa-circle-nodes"></i>
                <span>View Buckets</span>
            </a>
            @if($status['connected'])
                <a href="{{ route('s3.upload.index') }}" class="bg-[#ff9900] hover:bg-[#e68a00] text-slate-950 font-bold text-sm px-4 py-2 rounded-lg transition flex items-center gap-2 shadow-lg">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <span>Upload File</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Connection Status Banner -->
    @if(!$status['connected'])
        <div class="bg-rose-950/40 border border-rose-800 text-rose-200 p-5 rounded-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-start gap-4">
                <div class="bg-rose-900/50 p-3 rounded-lg text-rose-400">
                    <i class="fa-solid fa-circle-exclamation text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white">LocalStack S3 Connection Down</h3>
                    <p class="text-sm text-rose-300/80 mt-1">Unable to connect to LocalStack S3 at <code>{{ $status['endpoint'] }}</code>. Make sure Docker is running and the LocalStack container is healthy.</p>
                </div>
            </div>
            <div class="shrink-0">
                <span class="bg-rose-900/60 border border-rose-800 text-rose-300 text-xs px-3 py-1.5 rounded-lg font-semibold uppercase tracking-wider">
                    S3 Offline
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
                    <h3 class="text-lg font-bold text-white text-emerald-400">LocalStack Connected & Healthy</h3>
                    <p class="text-sm text-emerald-300/80 mt-1">Connected successfully to LocalStack mock S3 engine running locally on port 4566.</p>
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
        <!-- Bucket Count Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Total Buckets</span>
                <span class="text-3xl font-extrabold text-white">{{ $status['connected'] ? $totalBuckets : '--' }}</span>
            </div>
            <div class="bg-slate-800 text-[#ff9900] p-4 rounded-xl">
                <i class="fa-solid fa-folder-tree text-2xl"></i>
            </div>
        </div>

        <!-- Object Count Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Total Objects</span>
                <span class="text-3xl font-extrabold text-white">{{ $status['connected'] ? $totalObjects : '--' }}</span>
            </div>
            <div class="bg-slate-800 text-indigo-400 p-4 rounded-xl">
                <i class="fa-solid fa-file-invoice text-2xl"></i>
            </div>
        </div>

        <!-- Storage Size Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-1">Estimated Storage</span>
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

    <!-- Details and Recent Files split -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Connection Config Detail Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm flex flex-col h-full lg:col-span-1">
            <h3 class="font-bold text-lg text-white mb-4 flex items-center gap-2">
                <i class="fa-solid fa-circle-nodes text-[#ff9900]"></i>
                <span>S3 Configuration</span>
            </h3>
            <div class="space-y-4 text-sm flex-1">
                <div>
                    <span class="text-xs text-slate-500 block">S3 Endpoint URL</span>
                    <code class="text-slate-300 text-xs font-mono break-all">{{ $status['endpoint'] }}</code>
                </div>
                <div>
                    <span class="text-xs text-slate-500 block">AWS Region</span>
                    <span class="text-slate-300 font-mono font-medium">{{ $status['region'] }}</span>
                </div>
                <div>
                    <span class="text-xs text-slate-500 block">Default Configured Bucket</span>
                    <span class="text-slate-300 font-mono font-medium">{{ $status['bucket'] }}</span>
                </div>
                <div>
                    <span class="text-xs text-slate-500 block">Path Style Endpoint</span>
                    <span class="text-xs px-2 py-0.5 rounded font-semibold inline-block {{ $status['path_style'] ? 'bg-amber-950/40 text-amber-400 border border-amber-800/40' : 'bg-slate-800 text-slate-400' }}">
                        {{ $status['path_style'] ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Recent Uploads Table -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm lg:col-span-2 flex flex-col h-full">
            <h3 class="font-bold text-lg text-white mb-4 flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-[#ff9900]"></i>
                <span>Recent Uploaded Files</span>
            </h3>
            <div class="flex-1 overflow-x-auto">
                @if(!$status['connected'])
                    <div class="text-center py-8 text-slate-500 text-sm">
                        Connect to LocalStack to view files.
                    </div>
                @elseif(empty($recentFiles))
                    <div class="text-center py-8 text-slate-500 text-sm flex flex-col items-center justify-center gap-3">
                        <i class="fa-solid fa-inbox text-3xl text-slate-600"></i>
                        <span>No files uploaded to the default bucket yet.</span>
                    </div>
                @else
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-800 text-slate-400 text-xs font-bold uppercase">
                                <th class="pb-3">File Name</th>
                                <th class="pb-3">Size</th>
                                <th class="pb-3">Uploaded At</th>
                                <th class="pb-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @foreach($recentFiles as $file)
                                <tr class="text-slate-300 hover:bg-slate-800/30">
                                    <td class="py-3 flex items-center gap-2">
                                        <i class="fa-solid {{ Str::contains($file['mime_type'], 'image') ? 'fa-file-image text-teal-400' : (Str::contains($file['mime_type'], 'pdf') ? 'fa-file-pdf text-rose-400' : 'fa-file-lines text-slate-400') }}"></i>
                                        <span class="font-medium truncate max-w-[200px]" title="{{ $file['key'] }}">{{ $file['name'] }}</span>
                                    </td>
                                    <td class="py-3 font-mono text-xs">
                                        @if($file['size'] < 1024)
                                            {{ $file['size'] }} B
                                        @elseif($file['size'] < 1024 * 1024)
                                            {{ round($file['size'] / 1024, 1) }} KB
                                        @else
                                            {{ round($file['size'] / (1024 * 1024), 1) }} MB
                                        @endif
                                    </td>
                                    <td class="py-3 text-xs text-slate-400">
                                        {{ \Carbon\Carbon::parse($file['last_modified'])->setTimezone(config('app.timezone'))->diffForHumans() }}
                                    </td>
                                    <td class="py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            @if(Str::contains($file['mime_type'], 'image') || Str::contains($file['mime_type'], 'pdf'))
                                                <a href="{{ route('s3.objects.preview', ['bucket' => config('filesystems.disks.s3.bucket'), 'key' => $file['key']]) }}" target="_blank" class="text-slate-400 hover:text-[#ff9900] text-xs p-1" title="Preview File">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            @endif
                                            <a href="{{ route('s3.objects.download', ['bucket' => config('filesystems.disks.s3.bucket'), 'key' => $file['key']]) }}" class="text-slate-400 hover:text-emerald-400 text-xs p-1" title="Download File">
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    <!-- Educational "What are you learning?" Panel -->
    <div class="bg-aws-slate border border-aws-slate rounded-xl p-6 shadow-inner relative overflow-hidden">
        <div class="absolute right-0 bottom-0 translate-x-1/4 translate-y-1/4 text-slate-700/20 text-9xl font-extrabold select-none pointer-events-none">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
        
        <h3 class="text-md font-bold text-[#ff9900] uppercase tracking-wider mb-2 flex items-center gap-2">
            <i class="fa-solid fa-graduation-cap text-base"></i>
            <span>What are you learning?</span>
        </h3>
        <h4 class="text-lg font-bold text-white mb-2">What is Amazon S3?</h4>
        <p class="text-slate-300 text-sm leading-relaxed max-w-4xl">
            Amazon S3 (Simple Storage Service) is an **object storage service** designed to store and retrieve any amount of data from anywhere on the web. Unlike traditional file systems where files are organized in nested hierarchies of directories (POSIX style), S3 stores files as **objects** inside flat namespaces called **buckets**.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 text-xs">
            <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800/80">
                <span class="font-bold text-[#ff9900] block mb-1">Buckets</span>
                Containers for objects. Bucket names must be globally unique across all AWS accounts.
            </div>
            <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800/80">
                <span class="font-bold text-[#ff9900] block mb-1">Objects</span>
                The files themselves, along with unique keys (paths) and metadata describing the content.
            </div>
            <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800/80">
                <span class="font-bold text-[#ff9900] block mb-1">Integration style</span>
                Using Laravel's <code>Storage</code> API translates native method calls into standardized AWS S3 API requests behind the scenes.
            </div>
        </div>
    </div>
</div>
@endsection
