@extends('layouts.app')

@section('title', 'DynamoDB Items - ' . $table)

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="border-b border-slate-800 pb-5">
        <div class="flex items-center gap-2 text-xs font-semibold text-[#ff9900] uppercase tracking-wider mb-1">
            <i class="fa-solid fa-database"></i>
            <span>Amazon DynamoDB</span>
        </div>
        <div class="flex flex-wrap items-center gap-2 text-slate-300 font-mono text-sm">
            <a href="{{ route('dynamodb.tables.index') }}" class="hover:text-white transition flex items-center gap-1">
                <i class="fa-solid fa-table-cells text-amber-500"></i>
                <span>Tables</span>
            </a>
            <span class="text-slate-600"><i class="fa-solid fa-chevron-right text-xs"></i></span>
            <span class="font-semibold text-white">
                {{ $table }}
            </span>
        </div>
    </div>

    @if(isset($error))
        <div class="bg-rose-950/40 border border-rose-800 text-rose-300 p-4 rounded-lg flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-lg text-rose-400"></i>
            <span class="text-sm font-medium">{{ $error }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Items List -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm lg:col-span-2">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-slate-950/40 border-b border-slate-800 text-slate-400 text-xs font-bold uppercase">
                            <th class="px-5 py-3">Partition Key ({{ $schema['partition_key_name'] }})</th>
                            @if($schema['sort_key_name'])
                                <th class="px-5 py-3">Sort Key ({{ $schema['sort_key_name'] }})</th>
                            @endif
                            <th class="px-5 py-3">Attributes</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-mono text-xs">
                        @if(empty($items))
                            <tr class="font-sans">
                                <td colspan="{{ $schema['sort_key_name'] ? 4 : 3 }}" class="px-5 py-12 text-center text-slate-500">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <i class="fa-solid fa-inbox text-3xl text-slate-700"></i>
                                        <span>No items found in this table.</span>
                                    </div>
                                </td>
                            </tr>
                        @else
                            @foreach($items as $item)
                                @php
                                    $pkValue = $item[$schema['partition_key_name']];
                                    $skValue = $schema['sort_key_name'] ? $item[$schema['sort_key_name']] : null;
                                    
                                    // Filter out primary keys to isolate custom attributes
                                    $customAttributes = array_filter($item, function($key) use ($schema) {
                                        return $key !== $schema['partition_key_name'] && $key !== $schema['sort_key_name'];
                                    }, ARRAY_FILTER_USE_KEY);
                                @endphp
                                <tr class="hover:bg-slate-800/10 text-slate-300">
                                    <td class="px-5 py-4 font-semibold text-white break-all">
                                        {{ $pkValue }}
                                    </td>
                                    @if($schema['sort_key_name'])
                                        <td class="px-5 py-4 text-indigo-300 break-all">
                                            {{ $skValue }}
                                        </td>
                                    @endif
                                    <td class="px-5 py-4 font-sans text-xs">
                                        @if(empty($customAttributes))
                                            <span class="text-slate-600 italic">No custom attributes</span>
                                        @else
                                            <div class="grid grid-cols-1 gap-1 text-[11px]">
                                                @foreach($customAttributes as $key => $val)
                                                    <div>
                                                        <span class="font-mono font-bold text-slate-400">{{ $key }}:</span>
                                                        <span class="text-slate-300 font-mono">{{ is_array($val) ? json_encode($val) : $val }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right font-sans">
                                        <form action="{{ route('dynamodb.items.destroy', $table) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this item?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="pk_val" value="{{ $pkValue }}">
                                            @if($skValue !== null)
                                                <input type="hidden" name="sk_val" value="{{ $skValue }}">
                                            @endif
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

        <!-- Add Item Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm h-fit">
            <h3 class="font-bold text-md text-white mb-4 flex items-center gap-2 border-b border-slate-800 pb-2">
                <i class="fa-solid fa-circle-plus text-[#ff9900]"></i>
                <span>Add Item</span>
            </h3>
            
            <form action="{{ route('dynamodb.items.store', $table) }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label for="pk_value" class="block text-slate-400 font-semibold mb-2">Partition Key ({{ $schema['partition_key_name'] }} [{{ $schema['partition_key_type'] }}])</label>
                    <input type="text" id="pk_value" name="pk_value" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-[#ff9900] font-mono">
                </div>

                @if($schema['sort_key_name'])
                    <div>
                        <label for="sk_value" class="block text-slate-400 font-semibold mb-2">Sort Key ({{ $schema['sort_key_name'] }} [{{ $schema['sort_key_type'] }}])</label>
                        <input type="text" id="sk_value" name="sk_value" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-[#ff9900] font-mono">
                    </div>
                @endif

                <div>
                    <label for="attributes_json" class="block text-slate-400 font-semibold mb-2">Additional Attributes (JSON)</label>
                    <textarea id="attributes_json" name="attributes_json" rows="6" placeholder='{
  "name": "Jane Doe",
  "age": 28,
  "active": true
}' class="w-full bg-slate-950 border border-slate-800 rounded-lg p-3 font-mono text-slate-300 focus:outline-none focus:border-[#ff9900]"></textarea>
                </div>
                
                <button type="submit" class="w-full bg-[#ff9900] hover:bg-[#e68a00] text-slate-950 font-bold text-xs py-2 rounded-lg transition shadow-md cursor-pointer">
                    Save Item
                </button>
            </form>
        </div>
    </div>

    <!-- AWS CLI Commands Box -->
    <div class="bg-slate-950 border border-slate-800 rounded-xl p-5 shadow-sm space-y-3">
        <span class="text-xs font-bold text-[#ff9900] uppercase tracking-wider block">AWS CLI Terminal Commands</span>
        <p class="text-xs text-slate-400 font-sans">Run the following commands using your CLI terminal to manage items in this table:</p>
        
        <div class="space-y-3 font-mono text-[11px]">
            <div>
                <span class="text-slate-500 block"># Insert/Put a new item</span>
                <div class="bg-slate-900 border border-slate-800 rounded px-3.5 py-2 text-slate-300 leading-normal">
                    <code>lstk aws dynamodb put-item \
  --table-name {{ $table }} \
  --item '{"{{ $schema['partition_key_name'] }}": {"{{ $schema['partition_key_type'] }}": "value"}}'</code>
                </div>
            </div>
            <div>
                <span class="text-slate-500 block"># Delete an item</span>
                <div class="bg-slate-900 border border-slate-800 rounded px-3.5 py-2 text-slate-300 leading-normal">
                    <code>lstk aws dynamodb delete-item \
  --table-name {{ $table }} \
  --key '{"{{ $schema['partition_key_name'] }}": {"{{ $schema['partition_key_type'] }}": "value"}}'</code>
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
        <h4 class="text-lg font-bold text-white mb-2">NoSQL schema flexibility</h4>
        <p class="text-slate-300 text-sm leading-relaxed max-w-4xl font-sans">
            In DynamoDB, individual **items** can have unique layouts. While traditional RDBMS databases require a migration script to add a new column, NoSQL items can save extra attributes (like <code>name</code> or <code>active</code>) immediately on insert.
        </p>
    </div>
</div>
@endsection
