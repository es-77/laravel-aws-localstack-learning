@extends('layouts.app')

@section('title', 'IAM Roles')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="border-b border-slate-800 pb-5">
        <div class="flex items-center gap-2 text-xs font-semibold text-[#ff9900] uppercase tracking-wider mb-1">
            <i class="fa-solid fa-user-shield"></i>
            <span>Identity and Access Management</span>
        </div>
        <h1 class="text-3xl font-extrabold tracking-tight text-white">IAM Roles</h1>
    </div>

    @if(isset($error))
        <div class="bg-rose-950/40 border border-rose-800 text-rose-300 p-4 rounded-lg flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-lg text-rose-400"></i>
            <span class="text-sm font-medium">{{ $error }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Roles List Table -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm lg:col-span-2">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-slate-950/40 border-b border-slate-800 text-slate-400 text-xs font-bold uppercase">
                            <th class="px-5 py-3">Role Name</th>
                            <th class="px-5 py-3">Trust Relationship Policy</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-mono text-xs">
                        @if(empty($roles))
                            <tr class="font-sans">
                                <td colspan="3" class="px-5 py-12 text-center text-slate-500">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <i class="fa-solid fa-user-tag text-3xl text-slate-700"></i>
                                        <span>No IAM roles found. Create one to begin.</span>
                                    </div>
                                </td>
                            </tr>
                        @else
                            @foreach($roles as $role)
                                <tr class="hover:bg-slate-800/10 text-slate-300">
                                    <td class="px-5 py-4 font-semibold text-white">
                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-user-tag text-indigo-400 text-sm"></i>
                                            <span>{{ $role['name'] }}</span>
                                        </div>
                                        <div class="text-[10px] text-slate-500 mt-1 font-normal break-all select-all">{{ $role['arn'] }}</div>
                                    </td>
                                    <td class="px-5 py-4 font-normal text-slate-300">
                                        <details class="group cursor-pointer">
                                            <summary class="text-[10px] font-sans text-indigo-400 hover:text-indigo-300 select-none">View Trust Policy</summary>
                                            <pre class="bg-slate-950 border border-slate-850 p-3 rounded mt-2 text-[10px] max-h-40 overflow-y-auto whitespace-pre-wrap select-all">{{ $role['trust_policy'] }}</pre>
                                        </details>
                                    </td>
                                    <td class="px-5 py-4 text-right font-sans">
                                        <form action="{{ route('iam.roles.destroy', $role['name']) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete role \'{{ $role['name'] }}\'?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-400 hover:text-white bg-rose-950/20 hover:bg-rose-900 border border-rose-900/50 text-[10px] px-2 py-1.5 rounded transition inline-flex items-center gap-1 cursor-pointer">
                                                <i class="fa-solid fa-trash-can"></i>
                                                <span>Delete</span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create Role Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm h-fit">
            <h3 class="font-bold text-md text-white mb-4 flex items-center gap-2 border-b border-slate-800 pb-2">
                <i class="fa-solid fa-folder-plus text-[#ff9900]"></i>
                <span>Create IAM Role</span>
            </h3>
            
            <form action="{{ route('iam.roles.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="role_name" class="block text-xs font-bold text-slate-400 uppercase mb-2">IAM Role Name</label>
                    <input type="text" id="role_name" name="role_name" placeholder="e.g. LambdaS3AccessRole" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-[#ff9900] font-mono">
                </div>

                <div>
                    <label for="trust_policy" class="block text-xs font-bold text-slate-400 uppercase mb-2">Assume Role Trust Policy (JSON)</label>
                    <textarea id="trust_policy" name="trust_policy" rows="8" required class="w-full bg-slate-950 border border-slate-800 rounded-lg p-3 text-[10px] font-mono text-slate-300 focus:outline-none focus:border-[#ff9900]">{{ $defaultTrustPolicy }}</textarea>
                </div>
                
                <button type="submit" class="w-full bg-[#ff9900] hover:bg-[#e68a00] text-slate-950 font-bold text-xs py-2 rounded-lg transition shadow-md cursor-pointer">
                    Create Role
                </button>
            </form>
        </div>
    </div>

    <!-- AWS CLI Commands Box -->
    <div class="bg-slate-950 border border-slate-800 rounded-xl p-5 shadow-sm space-y-3">
        <span class="text-xs font-bold text-[#ff9900] uppercase tracking-wider block">AWS CLI Terminal Commands</span>
        <p class="text-xs text-slate-400 font-sans">Run the following commands to manage roles directly in LocalStack IAM:</p>
        
        <div class="space-y-3 font-mono text-[11px]">
            <div>
                <span class="text-slate-500 block"># Create a new IAM Role with assume-role policy</span>
                <div class="bg-slate-900 border border-slate-800 rounded px-3.5 py-2 text-slate-300">
                    <code>lstk aws iam create-role --role-name your-role-name --assume-role-policy-document file://trust-policy.json</code>
                </div>
            </div>
            <div>
                <span class="text-slate-500 block"># List all IAM Roles</span>
                <div class="bg-slate-900 border border-slate-800 rounded px-3.5 py-2 text-slate-300">
                    <code>lstk aws iam list-roles</code>
                </div>
            </div>
        </div>
    </div>

    <!-- Learning Section -->
    <div class="bg-aws-slate border border-aws-slate rounded-xl p-6 shadow-inner relative overflow-hidden">
        <h3 class="text-md font-bold text-[#ff9900] uppercase tracking-wider mb-2 flex items-center gap-2">
            <i class="fa-solid fa-graduation-cap text-base"></i>
            <span>What are you learning?</span>
        </h3>
        <h4 class="text-lg font-bold text-white mb-2">What is an IAM Role?</h4>
        <p class="text-slate-300 text-sm leading-relaxed max-w-4xl font-sans">
            Unlike an IAM User which represents a specific person/system, an **IAM Role** represents a set of permissions that can be temporarily assumed by any authorized entity (such as an AWS Lambda function or a Laravel application container).
        </p>
        <p class="text-slate-300 text-sm leading-relaxed max-w-4xl font-sans mt-2">
            Every role has a **Trust Relationship Policy** (or AssumeRole Policy) attached to it. This defines which trusted services (e.g. <code>lambda.amazonaws.com</code>) are permitted to "assume" the role and retrieve short-lived session access credentials.
        </p>
    </div>
</div>
@endsection
