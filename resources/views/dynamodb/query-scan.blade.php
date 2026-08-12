@extends('layouts.app')

@section('title', 'DynamoDB Query / Scan Playground')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="border-b border-slate-800 pb-5">
        <div class="flex items-center gap-2 text-xs font-semibold text-[#ff9900] uppercase tracking-wider mb-1">
            <i class="fa-solid fa-database"></i>
            <span>Amazon DynamoDB</span>
        </div>
        <h1 class="text-3xl font-extrabold tracking-tight text-white">Query / Scan Playground</h1>
    </div>

    @if(isset($error))
        <div class="bg-rose-950/40 border border-rose-800 text-rose-300 p-4 rounded-lg flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-lg text-rose-400"></i>
            <span class="text-sm font-medium">{{ $error }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Input parameters -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm">
            <h3 class="font-bold text-md text-white mb-4 flex items-center gap-2 border-b border-slate-800 pb-2">
                <i class="fa-solid fa-sliders text-[#ff9900]"></i>
                <span>Query Parameters</span>
            </h3>

            <form action="{{ route('dynamodb.query-scan') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                
                <!-- Table Selection -->
                <div>
                    <label for="tableSelect" class="block text-slate-400 font-semibold mb-2">Select DynamoDB Table</label>
                    <select id="tableSelect" name="table" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-[#ff9900]">
                        @if(empty($tables))
                            <option value="">-- No Tables Available --</option>
                        @else
                            @foreach($tables as $t)
                                <option value="{{ $t['name'] }}" {{ $selectedTable === $t['name'] ? 'selected' : '' }}>
                                    {{ $t['name'] }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Operation Type Selection -->
                <div>
                    <label class="block text-slate-400 font-semibold mb-2">Operation Type</label>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="operation" value="scan" {{ $operation === 'scan' ? 'checked' : '' }} onclick="toggleQueryFields(false)" class="rounded-full border-slate-850 text-[#ff9900] focus:ring-[#ff9900] bg-slate-950">
                            <span class="text-slate-300 font-semibold">Scan (Reads all items)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="operation" value="query" {{ $operation === 'query' ? 'checked' : '' }} onclick="toggleQueryFields(true)" class="rounded-full border-slate-850 text-[#ff9900] focus:ring-[#ff9900] bg-slate-950">
                            <span class="text-slate-300 font-semibold">Query (Uses keys)</span>
                        </label>
                    </div>
                </div>

                <!-- Query Key Parameters (Conditional) -->
                <div id="queryFields" class="{{ $operation === 'query' ? '' : 'hidden' }} space-y-4 bg-slate-950 p-4 rounded-lg border border-slate-850">
                    <span class="font-bold text-[10px] text-[#ff9900] uppercase tracking-wider block">Key Condition Expressions</span>
                    
                    <div>
                        <label for="partition_value" class="block text-slate-500 font-semibold mb-2">Partition Key Value (Required)</label>
                        <input type="text" id="partition_value" name="partition_value" value="{{ $partitionValue }}" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-[#ff9900] font-mono">
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <label for="sort_value" class="block text-slate-500 font-semibold mb-2">Sort Key Value (Optional)</label>
                            <input type="text" id="sort_value" name="sort_value" value="{{ $sortValue }}" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-[#ff9900] font-mono">
                        </div>
                        <div>
                            <label for="sort_operator" class="block text-slate-500 font-semibold mb-2">Operator</label>
                            <select id="sort_operator" name="sort_operator" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-[#ff9900]">
                                <option value="=" {{ $sortOperator === '=' ? 'selected' : '' }}>=</option>
                                <option value="<" {{ $sortOperator === '<' ? 'selected' : '' }}>&lt;</option>
                                <option value=">" {{ $sortOperator === '>' ? 'selected' : '' }}>&gt;</option>
                                <option value="<=" {{ $sortOperator === '<=' ? 'selected' : '' }}>&lt;=</option>
                                <option value=">=" {{ $sortOperator === '>=' ? 'selected' : '' }}>&gt;=</option>
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" {{ empty($tables) ? 'disabled' : '' }} class="w-full bg-[#ff9900] hover:bg-[#e68a00] text-slate-950 font-bold text-xs py-2.5 rounded-lg transition shadow-md flex items-center justify-center gap-2 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Execute Search</span>
                </button>
            </form>
        </div>

        <!-- Result Box -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm flex flex-col h-full">
            <h3 class="font-bold text-md text-white mb-4 flex items-center gap-2 border-b border-slate-800 pb-2">
                <i class="fa-solid fa-square-poll-vertical text-[#ff9900]"></i>
                <span>Search Results</span>
            </h3>

            @if($results !== null)
                <div class="flex-grow flex flex-col min-h-0 space-y-3">
                    <div class="flex justify-between items-center text-xs text-slate-400 font-semibold bg-slate-950/60 p-2.5 rounded border border-slate-850">
                        <span>Items Returned: <span class="text-white font-mono">{{ count($results) }}</span></span>
                        <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded {{ $operation === 'query' ? 'bg-emerald-950 border border-emerald-900 text-emerald-400' : 'bg-amber-950 border border-amber-900 text-amber-400' }}">
                            {{ $operation }}
                        </span>
                    </div>

                    <pre class="flex-grow bg-slate-950 border border-slate-850 p-4 rounded-lg text-[10px] font-mono text-emerald-400 overflow-y-auto whitespace-pre select-all max-h-[50vh]">{{ json_encode($results, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @else
                <div class="flex-grow flex flex-col items-center justify-center text-slate-500 text-center py-12 space-y-3 font-sans">
                    <i class="fa-solid fa-code text-5xl text-slate-700"></i>
                    <div>
                        <h4 class="font-bold text-sm text-slate-400 font-sans">Playground Idle</h4>
                        <p class="text-xs text-slate-500/80 mt-1 max-w-xs font-sans">Select search parameters and execute search to display records.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- AWS CLI Commands Box -->
    <div class="bg-slate-950 border border-slate-800 rounded-xl p-5 shadow-sm space-y-3">
        <span class="text-xs font-bold text-[#ff9900] uppercase tracking-wider block">AWS CLI Terminal Commands</span>
        <p class="text-xs text-slate-400 font-sans">Run these commands using your CLI terminal to execute queries and scans directly in LocalStack:</p>
        
        <div class="space-y-3 font-mono text-[11px]">
            <div>
                <span class="text-slate-500 block"># Scan operation (Reads entire table)</span>
                <div class="bg-slate-900 border border-slate-800 rounded px-3.5 py-2 text-slate-300">
                    <code>lstk aws dynamodb scan --table-name {{ $selectedTable ?: 'your-table-name' }}</code>
                </div>
            </div>
            <div>
                <span class="text-slate-500 block"># Query operation (Reads only keys matching condition)</span>
                <div class="bg-slate-900 border border-slate-800 rounded px-3.5 py-2 text-slate-300 leading-normal">
                    <code>lstk aws dynamodb query \
  --table-name {{ $selectedTable ?: 'your-table-name' }} \
  --key-condition-expression "#pk = :pk" \
  --expression-attribute-names '{"#pk": "id"}' \
  --expression-attribute-values '{":pk": {"N": "1"}}'</code>
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
        <h4 class="text-lg font-bold text-white mb-2">Query vs Scan</h4>
        <p class="text-slate-300 text-sm leading-relaxed max-w-4xl font-sans">
            In DynamoDB, there are two primary operations for retrieving data:
        </p>
        <ul class="list-disc list-inside text-xs text-slate-300 space-y-1.5 mt-2 max-w-3xl font-sans">
            <li><strong>Query:</strong> Searches for items based only on the primary key values. Because DynamoDB stores keys in hash tables, a Query is highly efficient and operates at O(1) lookup speeds.</li>
            <li><strong>Scan:</strong> Evaluates every single item in the entire table. As a table grows, a Scan operation becomes increasingly slow and expensive, consuming significant Read Capacity Units (RCUs). Scans should generally be avoided in production.</li>
        </ul>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function toggleQueryFields(show) {
        const fields = document.getElementById('queryFields');
        if (show) {
            fields.classList.remove('hidden');
        } else {
            fields.classList.add('hidden');
        }
    }
</script>
@endsection
