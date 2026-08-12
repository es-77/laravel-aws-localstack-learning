@extends('layouts.app')

@section('title', 'S3 File Upload')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-800 pb-5">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-[#ff9900] uppercase tracking-wider mb-1">
                <i class="fa-solid fa-box-open"></i>
                <span>Amazon Simple Storage Service</span>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">Upload Files</h1>
        </div>
        <div>
            <a href="{{ route('s3.buckets.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-semibold text-sm px-4 py-2 rounded-lg border border-slate-700 transition flex items-center gap-2">
                <i class="fa-solid fa-folder-tree"></i>
                <span>View Buckets</span>
            </a>
        </div>
    </div>

    <!-- Upload Form Card -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm">
        <form id="uploadForm" action="{{ route('s3.upload.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Select Bucket -->
                <div>
                    <label for="bucketSelect" class="block text-xs font-bold text-slate-400 uppercase mb-2">Destination Bucket</label>
                    <select id="bucketSelect" name="bucket" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-[#ff9900]">
                        @if(empty($buckets))
                            <option value="">-- No Buckets Available (Create one first) --</option>
                        @else
                            @foreach($buckets as $b)
                                <option value="{{ $b['name'] }}" {{ request('bucket') === $b['name'] || $selectedBucket === $b['name'] ? 'selected' : '' }}>
                                    {{ $b['name'] }} ({{ $b['region'] }})
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Folder Prefix -->
                <div>
                    <label for="prefixInput" class="block text-xs font-bold text-slate-400 uppercase mb-2">Folder Path / Prefix</label>
                    <input type="text" id="prefixInput" name="prefix" value="{{ request('prefix', 'uploads/') }}" placeholder="e.g. reports/2026/" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3.5 py-2 text-sm text-white focus:outline-none focus:border-[#ff9900] font-mono">
                    <p class="text-[10px] text-slate-500 mt-1">Leave empty to upload to the bucket root directory.</p>
                </div>
            </div>

            <!-- Drag & Drop Zone -->
            <div id="dropZone" class="border-2 border-dashed border-slate-800 hover:border-[#ff9900]/60 bg-slate-950/40 hover:bg-slate-950/70 rounded-xl p-10 text-center transition cursor-pointer relative group">
                <input type="file" id="fileInput" name="files[]" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" {{ empty($buckets) ? 'disabled' : '' }}>
                
                <div class="space-y-3 pointer-events-none">
                    <div class="text-[#ff9900] group-hover:scale-110 transition duration-300">
                        <i class="fa-solid fa-cloud-arrow-up text-5xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-200">Drag and drop your files here</p>
                        <p class="text-xs text-slate-500 mt-1">or click to browse from your device</p>
                    </div>
                    <div class="text-[10px] text-slate-600 font-mono">
                        Max file size: 20MB
                    </div>
                </div>
            </div>

            <!-- Selected Files List -->
            <div id="fileListContainer" class="hidden space-y-3">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Selected Files</span>
                <div id="fileList" class="divide-y divide-slate-850 bg-slate-950 rounded-lg border border-slate-850 max-h-60 overflow-y-auto">
                    <!-- Dynamic Rows Go Here -->
                </div>
            </div>

            <!-- Global Upload Progress Bar -->
            <div id="progressContainer" class="hidden space-y-2 bg-slate-950 p-4 rounded-lg border border-slate-850">
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-400 font-medium flex items-center gap-1.5">
                        <i class="fa-solid fa-spinner animate-spin text-amber-500"></i>
                        <span id="progressStatusText">Uploading files to LocalStack S3...</span>
                    </span>
                    <span id="progressPercentage" class="font-bold text-[#ff9900]">0%</span>
                </div>
                <div class="w-full bg-slate-800 rounded-full h-2">
                    <div id="progressBar" class="bg-aws-orange h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-3 border-t border-slate-800 pt-5">
                <a href="{{ request('bucket') ? route('s3.buckets.show', request('bucket')) : route('s3.buckets.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-semibold text-xs px-4 py-2.5 rounded-lg transition">Cancel</a>
                <button type="submit" id="submitBtn" {{ empty($buckets) ? 'disabled' : '' }} class="bg-[#ff9900] hover:bg-[#e68a00] text-slate-950 font-bold text-xs px-5 py-2.5 rounded-lg transition shadow-lg flex items-center gap-2 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <span>Start Upload</span>
                </button>
            </div>
        </form>
    </div>

    <!-- AWS CLI Commands Box -->
    <div class="bg-slate-950 border border-slate-800 rounded-xl p-5 shadow-sm space-y-3">
        <span class="text-xs font-bold text-[#ff9900] uppercase tracking-wider block">AWS CLI Terminal Commands</span>
        <p class="text-xs text-slate-400">Run the following command in your terminal to copy/upload a file into your LocalStack bucket:</p>
        
        <div class="space-y-3 font-mono text-[11px]">
            <div>
                <span class="text-slate-500 block"># Copy a local file to S3 bucket</span>
                <div class="flex items-center justify-between bg-slate-900 border border-slate-800 rounded px-3.5 py-2 text-slate-300">
                    <code>lstk aws s3 cp /path/to/local/file.txt s3://{{ request('bucket', 'your-bucket-name') }}/{{ request('prefix', 'uploads/') }}file.txt</code>
                </div>
            </div>
            <div>
                <span class="text-slate-500 block"># Sync a local directory containing multiple files to S3</span>
                <div class="flex items-center justify-between bg-slate-900 border border-slate-800 rounded px-3.5 py-2 text-slate-300">
                    <code>lstk aws s3 sync /path/to/local/dir s3://{{ request('bucket', 'your-bucket-name') }}/{{ request('prefix', 'uploads/') }}</code>
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
        <h4 class="text-lg font-bold text-white mb-2">How do file uploads to S3 work?</h4>
        <p class="text-slate-300 text-sm leading-relaxed max-w-4xl">
            When files are uploaded to AWS S3, a standard HTTP `PUT` request is dispatched containing the body streams. In large applications, uploading files from the browser directly to S3 (referred to as **S3 Presigned Post Uploads**) is a common pattern to bypass backend server bandwidth limits. However, in this learning environment, files are routed through the Laravel backend. Laravel's S3 Flysystem driver handles the connection parameters and securely pipes the files to S3/LocalStack.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 text-xs">
            <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800/80">
                <span class="font-bold text-[#ff9900] block mb-1">Piped Stream Upload</span>
                Laravel streams uploads directly to the S3 adapter instead of loading entire files into PHP application memory, which prevents memory limits from triggering when handling large files.
            </div>
            <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800/80">
                <span class="font-bold text-[#ff9900] block mb-1">Prefix Namespaces</span>
                We pass an optional folder prefix. S3 appends this string directly to the filename to construct the object key (e.g., <code>[prefix] + [filename]</code>).
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const fileInput = document.getElementById('fileInput');
    const fileList = document.getElementById('fileList');
    const fileListContainer = document.getElementById('fileListContainer');
    const uploadForm = document.getElementById('uploadForm');
    const progressContainer = document.getElementById('progressContainer');
    const progressStatusText = document.getElementById('progressStatusText');
    const progressPercentage = document.getElementById('progressPercentage');
    const progressBar = document.getElementById('progressBar');
    const submitBtn = document.getElementById('submitBtn');

    // Handle Drag & Drop styling
    const dropZone = document.getElementById('dropZone');
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, e => {
            e.preventDefault();
            dropZone.classList.add('border-[#ff9900]');
            dropZone.classList.add('bg-slate-950/70');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, e => {
            e.preventDefault();
            dropZone.classList.remove('border-[#ff9900]');
            dropZone.classList.remove('bg-slate-950/70');
        }, false);
    });

    fileInput.addEventListener('change', handleFileSelect);
    dropZone.addEventListener('drop', e => {
        if(e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            handleFileSelect();
        }
    });

    function handleFileSelect() {
        const files = fileInput.files;
        fileList.innerHTML = '';

        if (files.length === 0) {
            fileListContainer.classList.add('hidden');
            return;
        }

        fileListContainer.classList.remove('hidden');

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const sizeStr = formatBytes(file.size);
            const row = document.createElement('div');
            row.className = 'px-4 py-3 text-xs flex justify-between items-center text-slate-300';
            row.innerHTML = `
                <div class="flex items-center gap-2 truncate max-w-[70%]">
                    <i class="fa-solid fa-file text-slate-500"></i>
                    <span class="font-medium text-white truncate">${file.name}</span>
                </div>
                <span class="font-mono text-slate-400 font-semibold">${sizeStr}</span>
            `;
            fileList.appendChild(row);
        }
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    // Ajax Form Submission for Real-Time Progress Bar
    uploadForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const files = fileInput.files;
        if (files.length === 0) {
            alert('Please select files to upload.');
            return;
        }

        submitBtn.disabled = true;
        progressContainer.classList.remove('hidden');
        progressBar.style.width = '0%';
        progressPercentage.innerText = '0%';
        progressStatusText.innerText = 'Initializing LocalStack S3 upload...';

        const formData = new FormData(uploadForm);
        
        // Remove standard multiple file mapping and recreate it to ensure proper serialization
        formData.delete('files[]');
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', uploadForm.action, true);
        
        // Setup headers
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        // Track Progress
        xhr.upload.addEventListener('progress', function (e) {
            if (e.lengthComputable) {
                const percentComplete = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percentComplete + '%';
                progressPercentage.innerText = percentComplete + '%';

                if (percentComplete < 100) {
                    progressStatusText.innerText = `Uploading files... (${formatBytes(e.loaded)} / ${formatBytes(e.total)})`;
                } else {
                    progressStatusText.innerText = 'Processing and finalizing S3 metadata in LocalStack...';
                }
            }
        });

        // Track Response
        xhr.onreadystatechange = function () {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status >= 200 && xhr.status < 400) {
                    // Success, redirect to bucket view
                    const bucket = document.getElementById('bucketSelect').value;
                    const prefix = document.getElementById('prefixInput').value;
                    
                    // Show full completion
                    progressBar.style.width = '100%';
                    progressPercentage.innerText = '100%';
                    progressStatusText.innerText = 'Upload successful!';
                    
                    setTimeout(() => {
                        window.location.href = `{{ route('s3.buckets.show', ':bucket') }}`.replace(':bucket', bucket) + `?prefix=${encodeURIComponent(prefix)}`;
                    }, 500);
                } else {
                    // Fail
                    alert('Upload failed. Please check the logs or LocalStack status.');
                    submitBtn.disabled = false;
                    progressContainer.classList.add('hidden');
                }
            }
        };

        xhr.send(formData);
    });
</script>
@endsection
