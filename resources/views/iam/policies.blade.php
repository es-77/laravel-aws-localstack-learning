@extends('layouts.app')

@section('title', 'IAM Policies')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="border-b border-slate-800 pb-5">
        <div class="flex items-center gap-2 text-xs font-semibold text-[#ff9900] uppercase tracking-wider mb-1">
            <i class="fa-solid fa-user-shield"></i>
            <span>Identity and Access Management</span>
        </div>
        <h1 class="text-3xl font-extrabold tracking-tight text-white">IAM Custom Policies</h1>
    </div>

    @if(isset($error))
        <div class="bg-rose-950/40 border border-rose-800 text-rose-300 p-4 rounded-lg flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-lg text-rose-400"></i>
            <span class="text-sm font-medium">{{ $error }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Policies List Table -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm lg:col-span-2">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-slate-950/40 border-b border-slate-800 text-slate-400 text-xs font-bold uppercase">
                            <th class="px-5 py-3">Policy Name</th>
                            <th class="px-5 py-3">Attachments</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-mono text-xs">
                        @if(empty($policies))
                            <tr class="font-sans">
                                <td colspan="3" class="px-5 py-12 text-center text-slate-500">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <i class="fa-solid fa-file-invoice text-3xl text-slate-700"></i>
                                        <span>No custom policies found. Create one using the form.</span>
                                    </div>
                                </td>
                            </tr>
                        @else
                            @foreach($policies as $policy)
                                <tr class="hover:bg-slate-800/10 text-slate-300">
                                    <td class="px-5 py-4 font-semibold text-white">
                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-file-contract text-teal-400 text-sm"></i>
                                            <span>{{ $policy['name'] }}</span>
                                        </div>
                                        <div class="text-[10px] text-slate-500 mt-1 font-normal break-all select-all">{{ $policy['arn'] }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-slate-400 font-sans text-xs">
                                        <span class="bg-slate-950 border border-slate-850 px-2 py-0.5 rounded text-[11px] font-semibold text-slate-300">
                                            {{ $policy['attachment_count'] }} attachment(s)
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right font-sans">
                                        <form action="{{ route('iam.policies.destroy') }}" method="POST" onsubmit="return confirm('Are you sure you want to delete policy \'{{ $policy['name'] }}\'?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="arn" value="{{ $policy['arn'] }}">
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

        <!-- Create Policy Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm h-fit">
            <h3 class="font-bold text-md text-white mb-4 flex items-center gap-2 border-b border-slate-800 pb-2">
                <i class="fa-solid fa-file-circle-plus text-[#ff9900]"></i>
                <span>Create IAM Policy</span>
            </h3>
            
            <form action="{{ route('iam.policies.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="policy_name" class="block text-xs font-bold text-slate-400 uppercase mb-2">Policy Name</label>
                    <input type="text" id="policy_name" name="policy_name" placeholder="e.g. MyS3ReadWritePolicy" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-[#ff9900] font-mono">
                </div>

                <div>
                    <label for="policy_document" class="block text-xs font-bold text-slate-400 uppercase mb-2">Policy JSON Document</label>
                    <textarea id="policy_document" name="policy_document" rows="12" required class="w-full bg-slate-950 border border-slate-800 rounded-lg p-3 text-[10px] font-mono text-slate-300 focus:outline-none focus:border-[#ff9900]">{{ $defaultPolicyDoc }}</textarea>
                </div>
                
                <button type="submit" class="w-full bg-[#ff9900] hover:bg-[#e68a00] text-slate-950 font-bold text-xs py-2 rounded-lg transition shadow-md cursor-pointer">
                    Create Policy
                </button>
            </form>
        </div>
    </div>

    <!-- AWS CLI Commands Box -->
    <div class="bg-slate-950 border border-slate-800 rounded-xl p-5 shadow-sm space-y-3">
        <span class="text-xs font-bold text-[#ff9900] uppercase tracking-wider block">AWS CLI Terminal Commands</span>
        <p class="text-xs text-slate-400 font-sans">Run the following commands using your CLI terminal to manage custom policies in LocalStack IAM:</p>
        
        <div class="space-y-3 font-mono text-[11px]">
            <div>
                <span class="text-slate-500 block"># Create a new IAM Customer Managed Policy</span>
                <div class="bg-slate-900 border border-slate-800 rounded px-3.5 py-2 text-slate-300">
                    <code>lstk aws iam create-policy --policy-name your-policy-name --policy-document file://policy.json</code>
                </div>
            </div>
            <div>
                <span class="text-slate-500 block"># Delete an IAM Policy</span>
                <div class="bg-slate-900 border border-slate-800 rounded px-3.5 py-2 text-slate-300">
                    <code>lstk aws iam delete-policy --policy-arn arn:aws:iam::000000000000:policy/your-policy</code>
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
        <h4 class="text-lg font-bold text-white mb-2">What is an IAM Policy?</h4>
        <p class="text-slate-300 text-sm leading-relaxed max-w-4xl font-sans">
            An **IAM Policy** is a JSON document that declares formal permissions. It consists of:
        </p>
        <ul class="list-disc list-inside text-xs text-slate-300 space-y-1.5 mt-2 max-w-3xl">
            <li><strong>Effect:</strong> Specifying either <code>Allow</code> or <code>Deny</code>.</li>
            <li><strong>Action:</strong> The list of operations permitted/blocked (e.g., <code>s3:GetObject</code>).</li>
            <li><strong>Resource:</strong> The ARN identifying target resources (e.g., <code>arn:aws:s3:::my-bucket/*</code>).</li>
        </ul>
    </div>
</div>
@endsection
