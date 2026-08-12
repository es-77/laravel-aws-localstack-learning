@extends('layouts.app')

@section('title', 'S3 Buckets')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-800 pb-5">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-[#ff9900] uppercase tracking-wider mb-1">
                <i class="fa-solid fa-box-open"></i>
                <span>Amazon Simple Storage Service</span>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">S3 Buckets</h1>
        </div>
        <div>
            @if($status['connected'])
                <button onclick="toggleCreateModal(true)" class="bg-[#ff9900] hover:bg-[#e68a00] text-slate-950 font-bold text-sm px-4 py-2 rounded-lg transition flex items-center gap-2 shadow-lg cursor-pointer">
                    <i class="fa-solid fa-folder-plus"></i>
                    <span>Create Bucket</span>
                </button>
            @endif
        </div>
    </div>

    @if(isset($error))
        <div class="bg-rose-950/40 border border-rose-800 text-rose-300 p-4 rounded-lg flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-lg text-rose-400"></i>
            <span class="text-sm font-medium">{{ $error }}</span>
        </div>
    @endif

    <!-- Buckets Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-950/40 border-b border-slate-800 text-slate-400 text-xs font-bold uppercase">
                        <th class="px-6 py-4">Bucket Name</th>
                        <th class="px-6 py-4">Region</th>
                        <th class="px-6 py-4">Created At</th>
                        <th class="px-6 py-4">Objects count</th>
                        <th class="px-6 py-4">Storage Used</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @if(empty($buckets))
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center gap-3">
                                    <i class="fa-solid fa-folder-open text-4xl text-slate-700"></i>
                                    <span>No buckets found in this LocalStack S3 environment.</span>
                                </div>
                            </td>
                        </tr>
                    @else
                        @foreach($buckets as $bucket)
                            <tr class="hover:bg-slate-800/20 text-slate-300 transition">
                                <td class="px-6 py-4 font-semibold text-white">
                                    <a href="{{ route('s3.buckets.show', $bucket['name']) }}" class="flex items-center gap-2 hover:text-[#ff9900] transition">
                                        <i class="fa-regular fa-folder text-amber-500"></i>
                                        <span>{{ $bucket['name'] }}</span>
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-xs font-mono text-slate-400">
                                    {{ $bucket['region'] }}
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-400">
                                    {{ \Carbon\Carbon::parse($bucket['created_at'])->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-6 py-4 font-mono text-xs">
                                    {{ $bucket['object_count'] }}
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-slate-400">
                                    @if($bucket['total_size'] < 1024)
                                        {{ $bucket['total_size'] }} B
                                    @elseif($bucket['total_size'] < 1024 * 1024)
                                        {{ round($bucket['total_size'] / 1024, 1) }} KB
                                    @else
                                        {{ round($bucket['total_size'] / (1024 * 1024), 1) }} MB
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('s3.buckets.show', $bucket['name']) }}" class="text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 text-xs px-2.5 py-1.5 rounded transition flex items-center gap-1">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            <span>Open</span>
                                        </a>
                                        <button onclick="confirmDeleteBucket('{{ $bucket['name'] }}', {{ $bucket['object_count'] }})" class="text-rose-400 hover:text-white bg-rose-950/20 hover:bg-rose-900 border border-rose-900/50 text-xs px-2.5 py-1.5 rounded transition flex items-center gap-1 cursor-pointer">
                                            <i class="fa-solid fa-trash-can"></i>
                                            <span>Delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Bucket Modal Dialog -->
    <div id="createModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Overlay -->
            <div class="fixed inset-0 transition-opacity bg-slate-950/80 backdrop-blur-sm" onclick="toggleCreateModal(false)"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <!-- Modal Body -->
            <div class="inline-block align-bottom bg-slate-900 border border-slate-800 rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-aws-slate border-b border-slate-800 px-6 py-4 flex items-center justify-between text-white">
                    <h3 class="text-md font-bold flex items-center gap-2">
                        <i class="fa-solid fa-folder-plus text-[#ff9900]"></i>
                        <span>Create S3 Bucket</span>
                    </h3>
                    <button onclick="toggleCreateModal(false)" class="text-slate-400 hover:text-white cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
                </div>
                
                <form action="{{ route('s3.buckets.store') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label for="bucket_name" class="block text-xs font-bold text-slate-400 uppercase mb-2">Bucket Name</label>
                        <input type="text" id="bucket_name" name="bucket_name" placeholder="e.g. my-learning-bucket-123" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3.5 py-2 text-sm text-white focus:outline-none focus:border-[#ff9900] font-mono">
                        <p class="text-[11px] text-slate-500 mt-2">Must be 3-63 characters, contain only lowercase letters, numbers, periods, and hyphens, and start/end with a letter or number.</p>
                    </div>

                    <div>
                        <label for="region" class="block text-xs font-bold text-slate-400 uppercase mb-2">AWS Region</label>
                        <select id="region" name="region" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-[#ff9900]">
                            <option value="us-east-1">US East (N. Virginia) [us-east-1]</option>
                            <option value="us-east-2">US East (Ohio) [us-east-2]</option>
                            <option value="us-west-1">US West (N. California) [us-west-1]</option>
                            <option value="us-west-2">US West (Oregon) [us-west-2]</option>
                            <option value="eu-west-1">Europe (Ireland) [eu-west-1]</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="toggleCreateModal(false)" class="bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-semibold text-xs px-4 py-2.5 rounded-lg transition cursor-pointer">Cancel</button>
                        <button type="submit" class="bg-[#ff9900] hover:bg-[#e68a00] text-slate-950 font-bold text-xs px-5 py-2.5 rounded-lg transition shadow-lg cursor-pointer">Create Bucket</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Bucket Modal -->
    <div id="deleteModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Overlay -->
            <div class="fixed inset-0 transition-opacity bg-slate-950/80 backdrop-blur-sm" onclick="toggleDeleteModal(false)"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <!-- Modal Body -->
            <div class="inline-block align-bottom bg-slate-900 border border-slate-800 rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-rose-950/80 border-b border-rose-900/60 px-6 py-4 flex items-center justify-between text-white">
                    <h3 class="text-md font-bold flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-rose-400"></i>
                        <span>Delete Bucket Confirmation</span>
                    </h3>
                    <button onclick="toggleDeleteModal(false)" class="text-slate-400 hover:text-white cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
                </div>
                
                <form id="deleteBucketForm" method="POST" class="p-6 space-y-4">
                    @csrf
                    @method('DELETE')
                    
                    <p class="text-sm text-slate-300">
                        Are you sure you want to permanently delete the S3 bucket <span id="deleteBucketNameDisplay" class="font-bold text-white font-mono bg-slate-950 px-2 py-0.5 rounded border border-slate-800"></span>?
                    </p>

                    <!-- Warning for non-empty bucket -->
                    <div id="nonEmptyWarning" class="hidden bg-amber-950/40 border border-amber-900 text-amber-300 p-4 rounded-lg space-y-3">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-exclamation text-amber-400 mt-0.5"></i>
                            <div>
                                <span class="font-bold text-xs uppercase block">Bucket Is Not Empty</span>
                                <span class="text-xs text-amber-400/90 leading-relaxed block mt-1">This bucket contains <span id="deleteBucketCountDisplay" class="font-bold text-white font-mono"></span> file(s). Standard AWS S3 rules prevent deleting non-empty buckets.</span>
                            </div>
                        </div>
                        <label class="flex items-center gap-2.5 bg-slate-950/60 p-2.5 rounded border border-slate-900 cursor-pointer">
                            <input type="checkbox" name="force" value="1" class="rounded border-slate-800 text-[#ff9900] focus:ring-[#ff9900] bg-slate-950">
                            <span class="text-xs font-semibold text-rose-300">Confirm recursive delete: Delete bucket and all its files</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="toggleDeleteModal(false)" class="bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-semibold text-xs px-4 py-2.5 rounded-lg transition cursor-pointer">Cancel</button>
                        <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs px-5 py-2.5 rounded-lg transition shadow-lg cursor-pointer">Delete Bucket</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- AWS CLI Commands Box -->
    <div class="bg-slate-950 border border-slate-800 rounded-xl p-5 shadow-sm space-y-3">
        <span class="text-xs font-bold text-[#ff9900] uppercase tracking-wider block">AWS CLI Terminal Commands</span>
        <p class="text-xs text-slate-400">Run the following commands using your CLI terminal to manage buckets directly in LocalStack S3:</p>
        
        <div class="space-y-3 font-mono text-[11px]">
            <div>
                <span class="text-slate-500 block"># List all active S3 buckets</span>
                <div class="flex items-center justify-between bg-slate-900 border border-slate-800 rounded px-3.5 py-2 text-slate-300">
                    <code>lstk aws s3 ls</code>
                </div>
            </div>
            <div>
                <span class="text-slate-500 block"># Create a new bucket (make bucket)</span>
                <div class="flex items-center justify-between bg-slate-900 border border-slate-800 rounded px-3.5 py-2 text-slate-300">
                    <code>lstk aws s3 mb s3://your-bucket-name</code>
                </div>
            </div>
            <div>
                <span class="text-slate-500 block"># Delete an empty bucket (remove bucket)</span>
                <div class="flex items-center justify-between bg-slate-900 border border-slate-800 rounded px-3.5 py-2 text-slate-300">
                    <code>lstk aws s3 rb s3://your-bucket-name</code>
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
        <h4 class="text-lg font-bold text-white mb-2">What is an S3 Bucket?</h4>
        <p class="text-slate-300 text-sm leading-relaxed max-w-4xl">
            A **bucket** is a fundamental container for storage in Amazon S3. In some ways, it behaves like a root-level folder, but there is a major difference: S3 uses a **flat namespace**. This means that although you can display file pathways containing slashes (e.g. <code>uploads/images/photo.jpg</code>), S3 interprets that path as a single long unique string (referred to as the **object key**).
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 text-xs">
            <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800/80">
                <span class="font-bold text-[#ff9900] block mb-1">Global Uniqueness</span>
                Bucket names are shared globally. Even in LocalStack, bucket name rules ensure your naming structure matches real AWS guidelines to prevent deployment conflicts later.
            </div>
            <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800/80">
                <span class="font-bold text-[#ff9900] block mb-1">Regional Scope</span>
                Every bucket resides inside a specific AWS Region. Any objects stored in the bucket are physically stored in that region's datacenter coordinates.
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function toggleCreateModal(show) {
        const modal = document.getElementById('createModal');
        if (show) {
            modal.classList.remove('hidden');
        } else {
            modal.classList.add('hidden');
        }
    }

    function toggleDeleteModal(show) {
        const modal = document.getElementById('deleteModal');
        if (show) {
            modal.classList.remove('hidden');
        } else {
            modal.classList.add('hidden');
        }
    }

    function confirmDeleteBucket(name, objectCount) {
        document.getElementById('deleteBucketNameDisplay').innerText = name;
        
        const form = document.getElementById('deleteBucketForm');
        form.action = `/s3/buckets/${name}`;

        const warning = document.getElementById('nonEmptyWarning');
        const countDisplay = document.getElementById('deleteBucketCountDisplay');
        
        if (objectCount > 0) {
            warning.classList.remove('hidden');
            countDisplay.innerText = objectCount;
        } else {
            warning.classList.add('hidden');
        }

        toggleDeleteModal(true);
    }
</script>
@endsection
