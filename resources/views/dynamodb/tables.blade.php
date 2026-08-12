@extends('layouts.app')

@section('title', 'DynamoDB Tables')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="border-b border-slate-800 pb-5">
        <div class="flex items-center gap-2 text-xs font-semibold text-[#ff9900] uppercase tracking-wider mb-1">
            <i class="fa-solid fa-database"></i>
            <span>Amazon DynamoDB</span>
        </div>
        <h1 class="text-3xl font-extrabold tracking-tight text-white">DynamoDB Tables</h1>
    </div>

    @if(isset($error))
        <div class="bg-rose-950/40 border border-rose-800 text-rose-300 p-4 rounded-lg flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-lg text-rose-400"></i>
            <span class="text-sm font-medium">{{ $error }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Tables list -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm lg:col-span-2">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-slate-950/40 border-b border-slate-800 text-slate-400 text-xs font-bold uppercase">
                            <th class="px-5 py-3">Table Name</th>
                            <th class="px-5 py-3">Primary Key Schema</th>
                            <th class="px-5 py-3">Items Count</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-mono text-xs">
                        @if(empty($tables))
                            <tr class="font-sans">
                                <td colspan="4" class="px-5 py-12 text-center text-slate-500">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <i class="fa-solid fa-table-cells text-3xl text-slate-700"></i>
                                        <span>No DynamoDB tables found. Create one using the form.</span>
                                    </div>
                                </td>
                            </tr>
                        @else
                            @foreach($tables as $table)
                                <tr class="hover:bg-slate-800/10 text-slate-300">
                                    <td class="px-5 py-4 font-semibold text-white">
                                        <a href="{{ route('dynamodb.items.index', $table['name']) }}" class="flex items-center gap-2 hover:text-[#ff9900] transition">
                                            <i class="fa-solid fa-table text-amber-500 text-sm"></i>
                                            <span>{{ $table['name'] }}</span>
                                        </a>
                                        <span class="text-[9px] px-1.5 py-0.5 ml-2 font-bold uppercase rounded bg-emerald-950/60 border border-emerald-900 text-emerald-400 font-sans">{{ $table['status'] }}</span>
                                    </td>
                                    <td class="px-5 py-4 font-sans text-xs space-y-1.5">
                                        <div class="flex items-center gap-2 text-slate-300">
                                            <span class="text-slate-500 font-bold font-mono text-[10px]">Partition Key:</span>
                                            <span class="font-semibold text-white font-mono">{{ $table['partition_key_name'] }}</span>
                                            <span class="bg-slate-950 border border-slate-800 px-1 py-0.5 rounded text-[10px] text-slate-400 font-mono">{{ $table['partition_key_type'] }}</span>
                                        </div>
                                        @if($table['sort_key_name'])
                                            <div class="flex items-center gap-2 text-slate-300">
                                                <span class="text-slate-500 font-bold font-mono text-[10px]">Sort Key:</span>
                                                <span class="font-semibold text-indigo-300 font-mono">{{ $table['sort_key_name'] }}</span>
                                                <span class="bg-slate-950 border border-slate-800 px-1 py-0.5 rounded text-[10px] text-slate-400 font-mono">{{ $table['sort_key_type'] }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 font-mono text-xs">
                                        {{ $table['item_count'] }}
                                    </td>
                                    <td class="px-5 py-4 text-right font-sans">
                                        <div class="flex items-center justify-end gap-2.5">
                                            <a href="{{ route('dynamodb.items.index', $table['name']) }}" class="text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 text-xs px-2.5 py-1.5 rounded transition flex items-center gap-1">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                                <span>Items</span>
                                            </a>
                                            <form action="{{ route('dynamodb.tables.destroy', $table['name']) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete table \'{{ $table['name'] }}\'?')" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-400 hover:text-white bg-rose-950/20 hover:bg-rose-900 border border-rose-900/50 text-[10px] px-2.5 py-1.5 rounded transition inline-flex items-center gap-1 cursor-pointer">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                    <span>Delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create Table Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm h-fit">
            <h3 class="font-bold text-md text-white mb-4 flex items-center gap-2 border-b border-slate-800 pb-2">
                <i class="fa-solid fa-table text-[#ff9900]"></i>
                <span>Create Table</span>
            </h3>
            
            <form action="{{ route('dynamodb.tables.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label for="table_name" class="block text-slate-400 font-semibold mb-2">Table Name</label>
                    <input type="text" id="table_name" name="table_name" placeholder="e.g. users-table" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-[#ff9900] font-mono">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="partition_key" class="block text-slate-400 font-semibold mb-2">Partition Key</label>
                        <input type="text" id="partition_key" name="partition_key" placeholder="e.g. id" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-[#ff9900] font-mono">
                    </div>
                    <div>
                        <label for="partition_type" class="block text-slate-400 font-semibold mb-2">Key Type</label>
                        <select id="partition_type" name="partition_type" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-[#ff9900]">
                            <option value="S">String (S)</option>
                            <option value="N">Number (N)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="sort_key" class="block text-slate-400 font-semibold mb-2">Sort Key (Optional)</label>
                        <input type="text" id="sort_key" name="sort_key" placeholder="e.g. email" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-[#ff9900] font-mono">
                    </div>
                    <div>
                        <label for="sort_type" class="block text-slate-400 font-semibold mb-2">Sort Key Type</label>
                        <select id="sort_type" name="sort_type" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-[#ff9900]">
                            <option value="S">String (S)</option>
                            <option value="N">Number (N)</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-[#ff9900] hover:bg-[#e68a00] text-slate-950 font-bold text-xs py-2 rounded-lg transition shadow-md cursor-pointer">
                    Create Table
                </button>
            </form>
        </div>
    </div>

    <!-- AWS CLI Commands Box -->
    <div class="bg-slate-950 border border-slate-800 rounded-xl p-5 shadow-sm space-y-3">
        <span class="text-xs font-bold text-[#ff9900] uppercase tracking-wider block">AWS CLI Terminal Commands</span>
        <p class="text-xs text-slate-400 font-sans">Run the following commands using your CLI terminal to manage tables directly in LocalStack DynamoDB:</p>
        
        <div class="space-y-3 font-mono text-[11px]">
            <div>
                <span class="text-slate-500 block"># List all active DynamoDB Tables</span>
                <div class="bg-slate-900 border border-slate-800 rounded px-3.5 py-2 text-slate-300">
                    <code>lstk aws dynamodb list-tables</code>
                </div>
            </div>
            <div>
                <span class="text-slate-500 block"># Create a new table with Partition Key (id - Number)</span>
                <div class="bg-slate-900 border border-slate-800 rounded px-3.5 py-2 text-slate-300 leading-normal">
                    <code>lstk aws dynamodb create-table \
  --table-name users-table \
  --attribute-definitions AttributeName=id,AttributeType=N \
  --key-schema AttributeName=id,KeyType=HASH \
  --billing-mode PAY_PER_REQUEST</code>
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
        <h4 class="text-lg font-bold text-white mb-2">Partition Keys and Sort Keys</h4>
        <p class="text-slate-300 text-sm leading-relaxed max-w-4xl font-sans">
            DynamoDB table items are partitioned physically using the **Partition Key** (also called the **Hash Key**). This key must be unique if no Sort Key is configured. 
        </p>
        <p class="text-slate-300 text-sm leading-relaxed max-w-4xl font-sans mt-2">
            If you define a **Sort Key** (also called a **Range Key**), the primary index key is a combination of both Partition and Sort Key. This allows storing multiple items with the same Partition Key, sorted by the Sort Key values.
        </p>
    </div>
</div>
@endsection
