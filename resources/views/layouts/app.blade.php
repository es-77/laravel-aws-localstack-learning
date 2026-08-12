<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AWS Learning Lab - @yield('title', 'S3 Console')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Font Awesome Icons for premium look -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
        /* Custom AWS Colors */
        .aws-orange {
            color: #ff9900;
        }
        .bg-aws-orange {
            background-color: #ff9900;
        }
        .bg-aws-dark {
            background-color: #161b22;
        }
        .bg-aws-slate {
            background-color: #232f3e;
        }
        .border-aws-slate {
            border-color: #3b4b5e;
        }
        .hover-aws-orange:hover {
            color: #ff9900;
        }
        .hover-bg-aws-slate:hover {
            background-color: #232f3e;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col">

    <!-- Top Navigation Header -->
    <header class="bg-aws-dark border-b border-slate-800 text-white flex items-center justify-between px-6 py-3 sticky top-0 z-40">
        <div class="flex items-center gap-3">
            <div class="bg-aws-orange text-slate-950 p-1.5 rounded font-extrabold text-sm tracking-wider flex items-center justify-center">
                <i class="fa-solid fa-cubes-stacked text-base"></i>
            </div>
            <div>
                <span class="font-bold tracking-tight text-lg">AWS Learning Lab</span>
                <span class="text-xs text-slate-400 ml-2 hidden md:inline">LocalStack Sandbox</span>
            </div>
        </div>

        <div class="flex items-center gap-6">
            <!-- Connection Status -->
            <div class="flex items-center gap-2 bg-slate-900 border border-slate-800 rounded-full px-3 py-1 text-xs">
                @if(isset($status) && $status['connected'])
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-slate-300 font-medium">LocalStack Connected</span>
                @else
                    <span class="h-2 w-2 rounded-full bg-rose-500 animate-pulse"></span>
                    <span class="text-slate-400 font-medium">LocalStack Offline</span>
                @endif
            </div>

            <!-- Version Info -->
            <span class="text-xs text-slate-400 bg-aws-slate px-2.5 py-1 rounded-md border border-slate-800">
                Stage 1: S3 Active
            </span>
        </div>
    </header>

    <div class="flex-1 flex flex-col md:flex-row">
        <!-- Sidebar Navigation -->
        <aside class="w-full md:w-64 bg-aws-dark border-r border-slate-800 shrink-0 md:sticky md:top-[57px] md:h-[calc(100vh-57px)] overflow-y-auto custom-scrollbar">
            <div class="p-4">
                <!-- Dashboard Section -->
                <div class="mb-6">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover-bg-aws-slate transition {{ request()->routeIs('dashboard') ? 'bg-aws-slate text-white border-l-4 border-[#ff9900]' : '' }}">
                        <i class="fa-solid fa-chart-line text-aws-orange"></i>
                        <span class="font-semibold text-sm">Dashboard Overview</span>
                    </a>
                </div>

                <!-- AWS Services Title -->
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest px-3 mb-3">AWS LEARNING ROADMAP</h3>
                
                <nav class="space-y-1">
                    <!-- 1. S3 Section -->
                    <div class="space-y-0.5">
                        <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-slate-200 bg-aws-slate font-medium text-sm transition">
                            <span class="flex items-center gap-2.5">
                                <span class="bg-[#ff9900] text-slate-950 font-bold px-1.5 py-0.5 rounded text-[10px]">1</span>
                                <i class="fa-solid fa-box-open text-[#ff9900]"></i>
                                <span>Amazon S3</span>
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>
                        </button>
                        
                        <!-- S3 Active Submenu -->
                        <div class="pl-8 pr-2 py-1.5 space-y-1 bg-slate-900/40 rounded-lg border-l border-slate-800 ml-3">
                            <a href="{{ route('s3.overview') }}" class="block px-3 py-1.5 text-xs rounded transition {{ request()->routeIs('s3.overview') ? 'text-[#ff9900] font-semibold bg-aws-slate/40' : 'text-slate-400 hover:text-slate-200' }}">
                                <i class="fa-solid fa-gauge-high mr-2 text-[10px]"></i>Overview
                            </a>
                            <a href="{{ route('s3.buckets.index') }}" class="block px-3 py-1.5 text-xs rounded transition {{ request()->routeIs('s3.buckets.index') || request()->routeIs('s3.buckets.show') && !request()->routeIs('s3.upload.index') ? 'text-[#ff9900] font-semibold bg-aws-slate/40' : 'text-slate-400 hover:text-slate-200' }}">
                                <i class="fa-solid fa-folder-open mr-2 text-[10px]"></i>Buckets
                            </a>
                            <a href="{{ request()->routeIs('s3.buckets.show') ? request()->fullUrl() : (isset($status) && $status['connected'] && isset($buckets) && count($buckets) > 0 ? route('s3.buckets.show', $buckets[0]['name']) : route('s3.buckets.index')) }}" class="block px-3 py-1.5 text-xs rounded transition {{ request()->routeIs('s3.buckets.show') ? 'text-[#ff9900] font-semibold bg-aws-slate/40' : 'text-slate-400 hover:text-slate-200' }}">
                                <i class="fa-solid fa-file-image mr-2 text-[10px]"></i>Files / Objects
                            </a>
                            <a href="{{ route('s3.upload.index') }}" class="block px-3 py-1.5 text-xs rounded transition {{ request()->routeIs('s3.upload.index') ? 'text-[#ff9900] font-semibold bg-aws-slate/40' : 'text-slate-400 hover:text-slate-200' }}">
                                <i class="fa-solid fa-cloud-arrow-up mr-2 text-[10px]"></i>Uploads
                            </a>
                            <a href="{{ route('s3.permissions') }}" class="block px-3 py-1.5 text-xs rounded transition {{ request()->routeIs('s3.permissions') ? 'text-[#ff9900] font-semibold bg-aws-slate/40' : 'text-slate-400 hover:text-slate-200' }}">
                                <i class="fa-solid fa-shield-halved mr-2 text-[10px]"></i>Permissions
                            </a>
                        </div>
                    </div>

                    <!-- Future Roadmaps (2 - 15) -->
                    @php
                        $futureStages = [
                            ['num' => '2', 'name' => 'AWS IAM', 'icon' => 'fa-user-shield'],
                            ['num' => '3', 'name' => 'DynamoDB', 'icon' => 'fa-database'],
                            ['num' => '4', 'name' => 'SQS', 'icon' => 'fa-list-ol'],
                            ['num' => '5', 'name' => 'SNS', 'icon' => 'fa-bullhorn'],
                            ['num' => '6', 'name' => 'Lambda', 'icon' => 'fa-bolt'],
                            ['num' => '7', 'name' => 'API Gateway', 'icon' => 'fa-route'],
                            ['num' => '8', 'name' => 'EventBridge', 'icon' => 'fa-network-wired'],
                            ['num' => '9', 'name' => 'CloudWatch', 'icon' => 'fa-chart-line'],
                            ['num' => '10', 'name' => 'RDS', 'icon' => 'fa-server'],
                            ['num' => '11', 'name' => 'ECS', 'icon' => 'fa-dharmachakra'],
                            ['num' => '12', 'name' => 'EC2', 'icon' => 'fa-network-wired'],
                            ['num' => '13', 'name' => 'VPC', 'icon' => 'fa-network-wired'],
                            ['num' => '14', 'name' => 'CloudFront', 'icon' => 'fa-globe'],
                            ['num' => '15', 'name' => 'Route 53', 'icon' => 'fa-network-wired'],
                        ];
                    @endphp

                    @foreach($futureStages as $stage)
                        <div class="opacity-45 select-none relative group cursor-not-allowed">
                            <div class="flex items-center justify-between px-3 py-2 rounded-lg text-slate-400 text-sm transition font-normal hover:bg-slate-900/20">
                                <span class="flex items-center gap-2.5">
                                    <span class="bg-slate-800 text-slate-500 font-bold px-1.5 py-0.5 rounded text-[10px]">{{ $stage['num'] }}</span>
                                    <i class="fa-solid {{ $stage['icon'] }} text-slate-500"></i>
                                    <span>{{ $stage['name'] }}</span>
                                </span>
                                <span class="flex items-center gap-1 text-[9px] font-semibold tracking-wider text-slate-500 bg-slate-900 border border-slate-800 px-1.5 py-0.5 rounded">
                                    <i class="fa-solid fa-lock text-[8px]"></i>
                                    <span>SOON</span>
                                </span>
                            </div>
                        </div>
                    @endforeach
                </nav>
            </div>
        </aside>

        <!-- Main Body Wrapper -->
        <main class="flex-1 flex flex-col bg-slate-900 overflow-x-hidden min-w-0">
            <!-- Alert Center (Flash Messages) -->
            @if(session('success'))
                <div class="mx-6 mt-6 bg-emerald-950/70 border border-emerald-800/80 text-emerald-300 px-4 py-3 rounded-lg flex items-start gap-3 shadow-lg animate-fade-in relative z-20">
                    <i class="fa-solid fa-circle-check text-emerald-400 mt-0.5 text-lg"></i>
                    <div>
                        <h4 class="font-bold text-sm">Success</h4>
                        <p class="text-xs text-emerald-400/90 mt-0.5">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="mx-6 mt-6 bg-rose-950/70 border border-rose-800/80 text-rose-300 px-4 py-3 rounded-lg flex items-start gap-3 shadow-lg animate-fade-in relative z-20">
                    <i class="fa-solid fa-triangle-exclamation text-rose-400 mt-0.5 text-lg"></i>
                    <div>
                        <h4 class="font-bold text-sm">Action Failed</h4>
                        <p class="text-xs text-rose-400/90 mt-0.5">{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            <!-- Main Page Content -->
            <div class="flex-1 p-6 md:p-8">
                @yield('content')
            </div>

            <!-- Global AWS Console Footer -->
            <footer class="bg-aws-dark border-t border-slate-800 px-6 py-4 text-center text-xs text-slate-500 flex flex-col md:flex-row items-center justify-between gap-2 mt-auto">
                <div>
                    AWS LocalStack Learning Sandbox &copy; {{ date('Y') }}
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('s3.permissions') }}" class="hover:text-slate-300">S3 Configuration</a>
                    <span>&bull;</span>
                    <a href="https://docs.localstack.cloud" target="_blank" class="hover:text-[#ff9900] transition">LocalStack Docs</a>
                </div>
            </footer>
        </main>
    </div>

    <!-- Additional Yield Scripts -->
    @yield('scripts')
</body>
</html>
