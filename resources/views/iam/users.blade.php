@extends('layouts.app')

@section('title', 'IAM Users')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="border-b border-slate-800 pb-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-[#ff9900] uppercase tracking-wider mb-1">
                <i class="fa-solid fa-user-shield"></i>
                <span>Identity and Access Management</span>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">IAM Users</h1>
        </div>
    </div>

    @if(isset($error))
        <div class="bg-rose-950/40 border border-rose-800 text-rose-300 p-4 rounded-lg flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-lg text-rose-400"></i>
            <span class="text-sm font-medium">{{ $error }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Users List Table -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm lg:col-span-2">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-slate-950/40 border-b border-slate-800 text-slate-400 text-xs font-bold uppercase">
                            <th class="px-5 py-3">User Name</th>
                            <th class="px-5 py-3">Attached Policies</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-mono text-xs">
                        @if(empty($users))
                            <tr class="font-sans">
                                <td colspan="3" class="px-5 py-12 text-center text-slate-500">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <i class="fa-solid fa-user-slash text-3xl text-slate-700"></i>
                                        <span>No IAM users found. Create one to begin.</span>
                                    </div>
                                </td>
                            </tr>
                        @else
                            @foreach($users as $user)
                                <tr class="hover:bg-slate-800/10 text-slate-300">
                                    <td class="px-5 py-4 font-semibold text-white">
                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-user text-slate-500 text-sm"></i>
                                            <span>{{ $user['name'] }}</span>
                                        </div>
                                        <div class="text-[10px] text-slate-500 mt-1 font-normal break-all select-all">{{ $user['arn'] }}</div>
                                    </td>
                                    <td class="px-5 py-4 font-sans text-xs space-y-2">
                                        <!-- Attached Policies List -->
                                        @if(empty($user['policies']))
                                            <span class="text-slate-500 italic block text-[11px]">No policies attached</span>
                                        @else
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach($user['policies'] as $policy)
                                                    <span class="inline-flex items-center gap-1 bg-slate-950 border border-slate-800 text-slate-300 px-2 py-0.5 rounded text-[11px] font-medium">
                                                        <i class="fa-solid fa-file-contract text-teal-500 text-[9px]"></i>
                                                        <span>{{ $policy['name'] }}</span>
                                                        <form action="{{ route('iam.users.policy.detach', $user['name']) }}" method="POST" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="policy_arn" value="{{ $policy['arn'] }}">
                                                            <button type="submit" class="text-slate-500 hover:text-rose-400 font-bold ml-1 cursor-pointer" title="Detach Policy">&times;</button>
                                                        </form>
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif

                                        <!-- Attach Policy Inline Form -->
                                        @if(!empty($policies))
                                            <form action="{{ route('iam.users.policy.attach', $user['name']) }}" method="POST" class="flex gap-1.5 mt-2 max-w-xs">
                                                @csrf
                                                <select name="policy_arn" required class="bg-slate-950 border border-slate-800 rounded px-2 py-1 text-[11px] text-white focus:outline-none focus:border-[#ff9900] flex-1">
                                                    <option value="">-- Attach Policy --</option>
                                                    @foreach($policies as $p)
                                                        @php
                                                            $alreadyAttached = collect($user['policies'])->contains('arn', $p['arn']);
                                                        @endphp
                                                        @if(!$alreadyAttached)
                                                            <option value="{{ $p['arn'] }}">{{ $p['name'] }}</option>
                                                        @endif
                                                    @endoption
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-[#ff9900] font-bold border border-slate-700 px-2 py-1 rounded text-xs cursor-pointer">+</button>
                                            </form>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right font-sans">
                                        <form action="{{ route('iam.users.destroy', $user['name']) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete user \'{{ $user['name'] }}\'? (Attached policies will be detached first)')" class="inline">
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

        <!-- Create User Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm h-fit">
            <h3 class="font-bold text-md text-white mb-4 flex items-center gap-2 border-b border-slate-800 pb-2">
                <i class="fa-solid fa-user-plus text-[#ff9900]"></i>
                <span>Create IAM User</span>
            </h3>
            
            <form action="{{ route('iam.users.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="username" class="block text-xs font-bold text-slate-400 uppercase mb-2">IAM User Name</label>
                    <input type="text" id="username" name="username" placeholder="e.g. cloud-developer" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-[#ff9900] font-mono">
                    <p class="text-[10px] text-slate-500 mt-1.5">Alphanumeric characters and +=,.@_- are permitted.</p>
                </div>
                
                <button type="submit" class="w-full bg-[#ff9900] hover:bg-[#e68a00] text-slate-950 font-bold text-xs py-2 rounded-lg transition shadow-md cursor-pointer">
                    Create User
                </button>
            </form>
        </div>
    </div>

    <!-- AWS CLI Commands Box -->
    <div class="bg-slate-950 border border-slate-800 rounded-xl p-5 shadow-sm space-y-3">
        <span class="text-xs font-bold text-[#ff9900] uppercase tracking-wider block">AWS CLI Terminal Commands</span>
        <p class="text-xs text-slate-400 font-sans">Run the following commands using your CLI terminal to manage users directly in LocalStack IAM:</p>
        
        <div class="space-y-3 font-mono text-[11px]">
            <div>
                <span class="text-slate-500 block"># Create a new IAM User</span>
                <div class="bg-slate-900 border border-slate-800 rounded px-3.5 py-2 text-slate-300">
                    <code>lstk aws iam create-user --user-name your-user-name</code>
                </div>
            </div>
            <div>
                <span class="text-slate-500 block"># Attach a policy to a user</span>
                <div class="bg-slate-900 border border-slate-800 rounded px-3.5 py-2 text-slate-300">
                    <code>lstk aws iam attach-user-policy --user-name your-user-name --policy-arn arn:aws:iam::000000000000:policy/your-policy</code>
                </div>
            </div>
            <div>
                <span class="text-slate-500 block"># List all IAM Users</span>
                <div class="bg-slate-900 border border-slate-800 rounded px-3.5 py-2 text-slate-300">
                    <code>lstk aws iam list-users</code>
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
        <h4 class="text-lg font-bold text-white mb-2">What is an IAM User?</h4>
        <p class="text-slate-300 text-sm leading-relaxed max-w-4xl font-sans">
            An **IAM User** is an identity you create in AWS to represent a person or application that needs to interact with your resources. On their own, a newly created IAM User has **zero permissions** (known as the **Default Deny** rule). To grant permissions, you must explicitly attach one or more IAM Policies to that user.
        </p>
    </div>
</div>
@endsection
