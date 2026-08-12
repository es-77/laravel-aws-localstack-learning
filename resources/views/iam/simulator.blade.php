@extends('layouts.app')

@section('title', 'IAM Policy Simulator')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="border-b border-slate-800 pb-5">
        <div class="flex items-center gap-2 text-xs font-semibold text-[#ff9900] uppercase tracking-wider mb-1">
            <i class="fa-solid fa-user-shield"></i>
            <span>Identity and Access Management</span>
        </div>
        <h1 class="text-3xl font-extrabold tracking-tight text-white">IAM Policy Simulator</h1>
    </div>

    <!-- Simulator Workspace -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Input Form -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm">
            <h3 class="font-bold text-md text-white mb-4 flex items-center gap-2 border-b border-slate-800 pb-2">
                <i class="fa-solid fa-sliders text-[#ff9900]"></i>
                <span>Simulation Parameters</span>
            </h3>
            
            <form action="{{ route('iam.simulator') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="policy_document" class="block text-xs font-bold text-slate-400 uppercase mb-2">Policy Document (JSON)</label>
                    <textarea id="policy_document" name="policy_document" rows="12" required class="w-full bg-slate-950 border border-slate-800 rounded-lg p-3 text-[10px] font-mono text-slate-300 focus:outline-none focus:border-[#ff9900]">{{ $policyDocument }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="action" class="block text-xs font-bold text-slate-400 uppercase mb-2">Action to Test</label>
                        <input type="text" id="action" name="action" value="{{ $action }}" placeholder="e.g. s3:GetObject" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-[#ff9900] font-mono">
                    </div>
                    <div>
                        <label for="resource" class="block text-xs font-bold text-slate-400 uppercase mb-2">Resource ARN to Test</label>
                        <input type="text" id="resource" name="resource" value="{{ $resource }}" placeholder="e.g. arn:aws:s3:::my-bucket/file.txt" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-[#ff9900] font-mono">
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-[#ff9900] hover:bg-[#e68a00] text-slate-950 font-bold text-xs py-2.5 rounded-lg transition shadow-md flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-vial"></i>
                    <span>Run Simulation</span>
                </button>
            </form>
        </div>

        <!-- Simulation Output -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm flex flex-col h-full">
            <h3 class="font-bold text-md text-white mb-4 flex items-center gap-2 border-b border-slate-800 pb-2">
                <i class="fa-solid fa-square-poll-vertical text-[#ff9900]"></i>
                <span>Evaluation Results</span>
            </h3>

            @if(isset($error))
                <div class="bg-rose-950/40 border border-rose-800 text-rose-300 p-4 rounded-lg flex items-center gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-lg text-rose-400"></i>
                    <span class="text-xs font-medium">{{ $error }}</span>
                </div>
            @elseif($result)
                <div class="flex-1 flex flex-col justify-between space-y-4">
                    <!-- Outcome Badge -->
                    <div class="flex items-center gap-4">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Decision:</span>
                        @if($result['allowed'])
                            <span class="bg-emerald-950/80 border border-emerald-800 text-emerald-400 text-xs px-3.5 py-1.5 rounded-lg font-extrabold uppercase tracking-widest inline-flex items-center gap-1.5 shadow-md">
                                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                <span>Allowed</span>
                            </span>
                        @else
                            <span class="bg-rose-950/80 border border-rose-850 text-rose-400 text-xs px-3.5 py-1.5 rounded-lg font-extrabold uppercase tracking-widest inline-flex items-center gap-1.5 shadow-md">
                                <span class="h-2 w-2 rounded-full bg-rose-400"></span>
                                <span>Denied</span>
                            </span>
                        @endif
                    </div>

                    <!-- Description -->
                    <div class="bg-slate-950 p-4 rounded-lg border border-slate-850 space-y-2">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Evaluation Reason</span>
                        <p class="text-xs text-slate-300 leading-relaxed font-sans">{{ $result['reason'] }}</p>
                    </div>

                    <!-- Matched statement if exists -->
                    @if($result['matched_statement'])
                        <div class="flex-1 flex flex-col min-h-0">
                            <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block mb-2">Matching Statement Details</span>
                            <pre class="bg-slate-950 border border-slate-850 p-4 rounded text-[9px] font-mono text-slate-300 max-h-48 overflow-y-auto whitespace-pre-wrap select-all">{{ $result['matched_statement'] }}</pre>
                        </div>
                    @endif
                </div>
            @else
                <div class="flex-1 flex flex-col items-center justify-center text-slate-500 text-center py-12 space-y-3 font-sans">
                    <i class="fa-solid fa-vial text-5xl text-slate-700"></i>
                    <div>
                        <h4 class="font-bold text-sm text-slate-400">Simulator Idle</h4>
                        <p class="text-xs text-slate-500/80 mt-1 max-w-xs">Enter your JSON policy document and click 'Run Simulation' to test access outcomes.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Learning Section -->
    <div class="bg-aws-slate border border-aws-slate rounded-xl p-6 shadow-inner relative overflow-hidden">
        <h3 class="text-md font-bold text-[#ff9900] uppercase tracking-wider mb-2 flex items-center gap-2">
            <i class="fa-solid fa-graduation-cap text-base"></i>
            <span>What are you learning?</span>
        </h3>
        <h4 class="text-lg font-bold text-white mb-2">IAM Policy Evaluation Logic</h4>
        <p class="text-slate-300 text-sm leading-relaxed max-w-4xl font-sans">
            AWS IAM uses a highly specific execution sequence to determine access:
        </p>
        <ul class="list-disc list-inside text-xs text-slate-300 space-y-1.5 mt-2 max-w-3xl font-sans">
            <li><strong>Default Denied:</strong> By default, all requests are denied.</li>
            <li><strong>Explicit Deny Overrides Allow:</strong> If any single statement has <code>"Effect": "Deny"</code> matching the action/resource, access is immediately Denied, even if another statement explicitly allows it.</li>
            <li><strong>Allow Matches:</strong> If there is an explicit <code>Allow</code> statement, and no matching <code>Deny</code> statements exist, access is allowed.</li>
        </ul>
    </div>
</div>
@endsection
