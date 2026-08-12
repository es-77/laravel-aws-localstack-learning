@extends('layouts.app')

@section('title', 'S3 Objects - ' . $bucket)

@section('content')
<div class="space-y-6">
    <!-- Page Header & Breadcrumbs -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-800 pb-5">
        <div class="min-w-0">
            <div class="flex items-center gap-2 text-xs font-semibold text-[#ff9900] uppercase tracking-wider mb-1">
                <i class="fa-solid fa-box-open"></i>
                <span>Amazon Simple Storage Service</span>
            </div>
            
            <!-- Breadcrumbs -->
            <div class="flex flex-wrap items-center gap-2 text-slate-300 font-mono text-sm">
                <a href="{{ route('s3.buckets.index') }}" class="hover:text-white transition flex items-center gap-1">
                    <i class="fa-solid fa-folder-tree text-amber-500"></i>
                    <span>Buckets</span>
                </a>
                <span class="text-slate-600"><i class="fa-solid fa-chevron-right text-xs"></i></span>
                <a href="{{ route('s3.buckets.show', $bucket) }}" class="hover:text-white transition font-semibold text-white">
                    {{ $bucket }}
                </a>
                
                @if($prefix !== '')
                    @php
                        $parts = array_filter(explode('/', $prefix));
                        $currentPrefix = '';
                    @endphp
                    @foreach($parts as $part)
                        @php $currentPrefix .= $part . '/'; @endphp
                        <span class="text-slate-600"><i class="fa-solid fa-chevron-right text-xs"></i></span>
                        <a href="{{ route('s3.buckets.show', ['bucket' => $bucket, 'prefix' => $currentPrefix]) }}" class="hover:text-white transition">
                            {{ $part }}
                        </a>
                    @endforeach
                @endif
            </div>
        </div>
        
        <div class="flex gap-3 shrink-0">
            <a href="{{ route('s3.upload.index') }}?bucket={{ $bucket }}&prefix={{ $prefix }}" class="bg-[#ff9900] hover:bg-[#e68a00] text-slate-950 font-bold text-sm px-4 py-2 rounded-lg transition flex items-center gap-2 shadow-lg">
                <i class="fa-solid fa-cloud-arrow-up"></i>
                <span>Upload to this folder</span>
            </a>
        </div>
    </div>

    @if($error)
        <div class="bg-rose-950/40 border border-rose-800 text-rose-300 p-4 rounded-lg flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-lg text-rose-400"></i>
            <span class="text-sm font-medium">{{ $error }}</span>
        </div>
    @endif

    <!-- Main Browser Card -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm">
        <!-- Explorer Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-950/40 border-b border-slate-800 text-slate-400 text-xs font-bold uppercase">
                        <th class="px-6 py-4">Name</th>
                        <th class="px-6 py-4">Key / Path</th>
                        <th class="px-6 py-4">Size</th>
                        <th class="px-6 py-4">Type</th>
                        <th class="px-6 py-4">Last Modified</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-mono text-xs">
                    <!-- Parent Directory Link -->
                    @if($prefix !== '')
                        @php
                            $parts = explode('/', rtrim($prefix, '/'));
                            array_pop($parts);
                            $parentPrefix = count($parts) > 0 ? implode('/', $parts) . '/' : '';
                        @endphp
                        <tr class="hover:bg-slate-800/20 text-slate-400 transition">
                            <td colspan="6" class="px-6 py-3">
                                <a href="{{ route('s3.buckets.show', ['bucket' => $bucket, 'prefix' => $parentPrefix]) }}" class="flex items-center gap-2 text-slate-400 hover:text-white transition">
                                    <i class="fa-solid fa-arrow-turn-up text-indigo-400"></i>
                                    <span class="font-sans font-bold">.. (Go Up)</span>
                                </a>
                            </td>
                        </tr>
                    @endif

                    @if(empty($data['folders']) && empty($data['files']))
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center gap-3">
                                    <i class="fa-regular fa-file text-4xl text-slate-700"></i>
                                    <span class="font-sans">This directory prefix is empty. Upload some files!</span>
                                </div>
                            </td>
                        </tr>
                    @else
                        <!-- Virtual Folders -->
                        @foreach($data['folders'] as $folder)
                            <tr class="hover:bg-slate-800/20 text-slate-300 transition">
                                <td class="px-6 py-4 font-sans font-semibold text-white">
                                    <a href="{{ route('s3.buckets.show', ['bucket' => $bucket, 'prefix' => $folder['prefix']]) }}" class="flex items-center gap-2 hover:text-[#ff9900] transition">
                                        <i class="fa-solid fa-folder text-amber-500 text-base"></i>
                                        <span>{{ $folder['name'] }}/</span>
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-slate-500">
                                    {{ $folder['prefix'] }}
                                </td>
                                <td class="px-6 py-4 text-slate-500">--</td>
                                <td class="px-6 py-4 text-slate-500">Folder</td>
                                <td class="px-6 py-4 text-slate-500">--</td>
                                <td class="px-6 py-4 text-right font-sans">
                                    <a href="{{ route('s3.buckets.show', ['bucket' => $bucket, 'prefix' => $folder['prefix']]) }}" class="text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 text-xs px-2 py-1 rounded transition">
                                        Open
                                    </a>
                                </td>
                            </tr>
                        @endforeach

                        <!-- Files -->
                        @foreach($data['files'] as $file)
                            @php
                                $isPreviewable = Str::contains($file['mime_type'], 'image') || Str::contains($file['mime_type'], 'pdf');
                            @endphp
                            <tr class="hover:bg-slate-800/20 text-slate-300 transition">
                                <td class="px-6 py-4 font-sans text-slate-200">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid {{ Str::contains($file['mime_type'], 'image') ? 'fa-file-image text-teal-400' : (Str::contains($file['mime_type'], 'pdf') ? 'fa-file-pdf text-rose-400' : 'fa-file-lines text-slate-400') }} text-sm"></i>
                                        <span class="font-medium truncate max-w-[200px]" title="{{ $file['name'] }}">{{ $file['name'] }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-400 truncate max-w-[150px]" title="{{ $file['key'] }}">
                                    {{ $file['key'] }}
                                </td>
                                <td class="px-6 py-4 text-slate-300 font-semibold">
                                    @if($file['size'] < 1024)
                                        {{ $file['size'] }} B
                                    @elseif($file['size'] < 1024 * 1024)
                                        {{ round($file['size'] / 1024, 1) }} KB
                                    @else
                                        {{ round($file['size'] / (1024 * 1024), 1) }} MB
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-400 text-[10px]">
                                    {{ $file['mime_type'] }}
                                </td>
                                <td class="px-6 py-4 text-slate-400">
                                    {{ \Carbon\Carbon::parse($file['last_modified'])->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-6 py-4 text-right font-sans">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($isPreviewable)
                                            <button onclick="openPreviewModal('{{ $file['name'] }}', '{{ route('s3.objects.preview', ['bucket' => $bucket, 'key' => $file['key']]) }}', '{{ $file['mime_type'] }}')" class="text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 text-xs p-1.5 rounded transition cursor-pointer" title="Preview File">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                        @else
                                            <button onclick="openPreviewModal('{{ $file['name'] }}', '', '')" class="text-slate-600 hover:text-slate-500 bg-slate-800/40 text-xs p-1.5 rounded transition cursor-pointer" title="Preview Not Available">
                                                <i class="fa-solid fa-eye-slash"></i>
                                            </button>
                                        @endif

                                        <a href="{{ route('s3.objects.download', ['bucket' => $bucket, 'key' => $file['key']]) }}" class="text-emerald-400 hover:text-white bg-emerald-950/20 hover:bg-emerald-900 border border-emerald-900/40 text-xs p-1.5 rounded transition" title="Download File">
                                            <i class="fa-solid fa-download"></i>
                                        </a>

                                        <button onclick="confirmDeleteObject('{{ $file['key'] }}')" class="text-rose-400 hover:text-white bg-rose-950/20 hover:bg-rose-900 border border-rose-900/50 text-xs p-1.5 rounded transition cursor-pointer" title="Delete File">
                                            <i class="fa-solid fa-trash-can"></i>
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

    <!-- Preview Modal -->
    <div id="previewModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Overlay -->
            <div class="fixed inset-0 transition-opacity bg-slate-950/80 backdrop-blur-sm" onclick="closePreviewModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <!-- Modal Body -->
            <div class="inline-block align-middle bg-slate-900 border border-slate-800 rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-aws-slate border-b border-slate-800 px-6 py-4 flex items-center justify-between text-white">
                    <h3 class="text-md font-bold flex items-center gap-2">
                        <i class="fa-solid fa-eye text-[#ff9900]"></i>
                        <span id="previewFilenameDisplay">File Preview</span>
                    </h3>
                    <button onclick="closePreviewModal()" class="text-slate-400 hover:text-white cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
                </div>
                
                <div class="p-6 flex flex-col items-center justify-center min-h-[300px] max-h-[70vh] overflow-y-auto bg-slate-950">
                    <div id="previewContentArea" class="w-full flex justify-center">
                        <!-- Loaded dynamic preview contents (image or pdf iframe) go here -->
                    </div>
                    
                    <div id="noPreviewArea" class="hidden text-center space-y-4">
                        <i class="fa-solid fa-eye-slash text-5xl text-slate-700"></i>
                        <p class="text-slate-400 text-sm font-sans">No inline preview available for this file type.</p>
                    </div>
                </div>

                <div class="bg-slate-900 border-t border-slate-800 px-6 py-4 flex justify-end gap-3">
                    <button onclick="closePreviewModal()" class="bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-semibold text-xs px-4 py-2.5 rounded-lg transition cursor-pointer">Close</button>
                    <a id="previewDownloadButton" href="#" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-5 py-2.5 rounded-lg transition shadow-lg flex items-center gap-2">
                        <i class="fa-solid fa-download"></i>
                        <span>Download</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Object Modal -->
    <div id="deleteObjectModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Overlay -->
            <div class="fixed inset-0 transition-opacity bg-slate-950/80 backdrop-blur-sm" onclick="toggleDeleteObjectModal(false)"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <!-- Modal Body -->
            <div class="inline-block align-bottom bg-slate-900 border border-slate-800 rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <div class="bg-rose-950/80 border-b border-rose-900/60 px-6 py-4 flex items-center justify-between text-white">
                    <h3 class="text-md font-bold flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-rose-400"></i>
                        <span>Delete Object Confirmation</span>
                    </h3>
                    <button onclick="toggleDeleteObjectModal(false)" class="text-slate-400 hover:text-white cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
                </div>
                
                <form action="{{ route('s3.objects.destroy', $bucket) }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" id="deleteObjectKey" name="key" value="">

                    <p class="text-sm text-slate-300">
                        Are you sure you want to permanently delete the file <span id="deleteObjectNameDisplay" class="font-bold text-white break-all"></span> from the S3 bucket?
                    </p>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="toggleDeleteObjectModal(false)" class="bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-semibold text-xs px-4 py-2.5 rounded-lg transition cursor-pointer">Cancel</button>
                        <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs px-5 py-2.5 rounded-lg transition shadow-lg cursor-pointer">Delete File</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- AWS CLI Commands Box -->
    <div class="bg-slate-950 border border-slate-800 rounded-xl p-5 shadow-sm space-y-3">
        <span class="text-xs font-bold text-[#ff9900] uppercase tracking-wider block">AWS CLI Terminal Commands</span>
        <p class="text-xs text-slate-400">Run the following commands using your CLI terminal to manage objects in this prefix:</p>
        
        <div class="space-y-3 font-mono text-[11px]">
            <div>
                <span class="text-slate-500 block"># List all objects recursively in this bucket</span>
                <div class="flex items-center justify-between bg-slate-900 border border-slate-800 rounded px-3.5 py-2 text-slate-300">
                    <code>lstk aws s3 ls s3://{{ $bucket }} --recursive</code>
                </div>
            </div>
            <div>
                <span class="text-slate-500 block"># List objects only under the current prefix (directory path)</span>
                <div class="flex items-center justify-between bg-slate-900 border border-slate-800 rounded px-3.5 py-2 text-slate-300">
                    <code>lstk aws s3 ls s3://{{ $bucket }}/{{ $prefix }}</code>
                </div>
            </div>
            @if($prefix !== '')
                <div>
                    <span class="text-slate-500 block"># Delete folder prefix recursively</span>
                    <div class="flex items-center justify-between bg-slate-900 border border-slate-800 rounded px-3.5 py-2 text-slate-300">
                        <code>lstk aws s3 rm s3://{{ $bucket }}/{{ $prefix }} --recursive</code>
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
        <h4 class="text-lg font-bold text-white mb-2">What is an S3 Object?</h4>
        <p class="text-slate-300 text-sm leading-relaxed max-w-4xl">
            An **object** is the primary unit of storage in S3. It consists of the file data (the byte stream), a unique key (identifier name), and key-value metadata properties (such as Content-Type).
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 text-xs">
            <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800/80">
                <span class="font-bold text-[#ff9900] block mb-1">Virtual Prefixes (Folders)</span>
                S3 doesn't actually have folders. When you create a path like <code>uploads/document.pdf</code>, S3 creates a single object named exactly <code>uploads/document.pdf</code>. S3 tools use the slash <code>/</code> character delimiter to dynamically construct folder views.
            </div>
            <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800/80">
                <span class="font-bold text-[#ff9900] block mb-1">MIME Detection</span>
                To preview files or allow browsers to render them correctly, we read the object's <code>ContentType</code> metadata from S3 and stream it with corresponding headers.
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openPreviewModal(filename, url, mimeType) {
        document.getElementById('previewFilenameDisplay').innerText = 'Preview: ' + filename;
        const downloadButton = document.getElementById('previewDownloadButton');
        
        // Setup download button route
        downloadButton.href = `{{ route('s3.objects.download', $bucket) }}?key=${encodeURIComponent('{{ $prefix }}' + filename)}`;

        const previewArea = document.getElementById('previewContentArea');
        const noPreviewArea = document.getElementById('noPreviewArea');

        previewArea.innerHTML = '';
        noPreviewArea.classList.add('hidden');

        if (url === '') {
            noPreviewArea.classList.remove('hidden');
        } else if (mimeType.includes('image')) {
            const img = document.createElement('img');
            img.src = url;
            img.className = 'max-w-full max-h-[60vh] object-contain rounded border border-slate-800 shadow-md';
            previewArea.appendChild(img);
        } else if (mimeType.includes('pdf')) {
            const iframe = document.createElement('iframe');
            iframe.src = url;
            iframe.className = 'w-full h-[60vh] rounded border border-slate-800';
            previewArea.appendChild(iframe);
        } else {
            noPreviewArea.classList.remove('hidden');
        }

        document.getElementById('previewModal').classList.remove('hidden');
    }

    function closePreviewModal() {
        document.getElementById('previewModal').classList.add('hidden');
    }

    function toggleDeleteObjectModal(show) {
        const modal = document.getElementById('deleteObjectModal');
        if (show) {
            modal.classList.remove('hidden');
        } else {
            modal.classList.add('hidden');
        }
    }

    function confirmDeleteObject(key) {
        const parts = key.split('/');
        const filename = parts[parts.length - 1];
        
        document.getElementById('deleteObjectNameDisplay').innerText = filename + ' (' + key + ')';
        document.getElementById('deleteObjectKey').value = key;
        toggleDeleteObjectModal(true);
    }
</script>
@endsection
