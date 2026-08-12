<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DynamoDbService;
use Exception;
use Illuminate\Support\Facades\Log;

class DynamoDbController extends Controller
{
    protected DynamoDbService $ddbService;

    public function __construct(DynamoDbService $ddbService)
    {
        $this->ddbService = $ddbService;
    }

    /**
     * DynamoDB Overview Page.
     */
    public function overview()
    {
        $status = $this->ddbService->getConnectionStatus();
        $totalTables = 0;
        $totalItems = 0;
        $totalSize = 0;

        if ($status['connected']) {
            try {
                $tables = $this->ddbService->listTables();
                $totalTables = count($tables);

                foreach ($tables as $t) {
                    $totalItems += $t['item_count'];
                    $totalSize += $t['size'];
                }
            } catch (Exception $e) {
                Log::warning('Failed to load DynamoDB overview stats: ' . $e->getMessage());
            }
        }

        return view('dynamodb.overview', compact('status', 'totalTables', 'totalItems', 'totalSize'));
    }

    /**
     * List DynamoDB Tables.
     */
    public function tables()
    {
        $status = $this->ddbService->getConnectionStatus();
        $tables = [];
        $error = null;

        if ($status['connected']) {
            try {
                $tables = $this->ddbService->listTables();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = "Unable to connect to LocalStack DynamoDB. Please ensure Docker is running.";
        }

        return view('dynamodb.tables', compact('tables', 'error', 'status'));
    }

    /**
     * Create DynamoDB Table.
     */
    public function createTable(Request $request)
    {
        $request->validate([
            'table_name' => 'required|string',
            'partition_key' => 'required|string',
            'partition_type' => 'required|string|in:S,N',
            'sort_key' => 'nullable|string',
            'sort_type' => 'required_with:sort_key|string|in:S,N',
        ]);

        $tableName = trim($request->input('table_name'));
        $partitionKey = trim($request->input('partition_key'));
        $partitionType = $request->input('partition_type');
        $sortKey = trim($request->input('sort_key'));
        $sortType = $request->input('sort_type');

        try {
            $this->ddbService->createTable($tableName, $partitionKey, $partitionType, $sortKey, $sortType);
            return redirect()->route('dynamodb.tables.index')->with('success', "Table '{$tableName}' created successfully.");
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete DynamoDB Table.
     */
    public function deleteTable(string $table)
    {
        try {
            $this->ddbService->deleteTable($table);
            return redirect()->route('dynamodb.tables.index')->with('success', "Table '{$table}' deleted successfully.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Browse Items in a Table.
     */
    public function items(Request $request, string $table)
    {
        $status = $this->ddbService->getConnectionStatus();
        $items = [];
        $schema = [];
        $error = null;

        if (!$status['connected']) {
            return redirect()->route('dynamodb.overview')->with('error', 'DynamoDB connection offline.');
        }

        try {
            $schema = $this->ddbService->describeTable($table);
            $items = $this->ddbService->scanItems($table);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        return view('dynamodb.items', compact('items', 'table', 'schema', 'error', 'status'));
    }

    /**
     * Put (Insert/Replace) Item in a table.
     */
    public function putItem(Request $request, string $table)
    {
        $request->validate([
            'pk_value' => 'required|string',
            'sk_value' => 'nullable|string',
            'attributes_json' => 'nullable|string',
        ]);

        try {
            $schema = $this->ddbService->describeTable($table);
            $pkName = $schema['partition_key_name'];
            $skName = $schema['sort_key_name'];

            $itemData = [
                $pkName => $request->input('pk_value')
            ];

            if ($skName) {
                if ($request->input('sk_value') === null || trim($request->input('sk_value')) === '') {
                    throw new Exception("Sort key '{$skName}' value is required for this table.");
                }
                $itemData[$skName] = $request->input('sk_value');
            }

            // Merge optional JSON attributes
            $attrJson = $request->input('attributes_json');
            if ($attrJson && trim($attrJson) !== '') {
                $attrs = json_decode($attrJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid JSON format in custom attributes.');
                }
                
                // Merge, preventing overwriting primary keys
                foreach ($attrs as $key => $val) {
                    if ($key !== $pkName && $key !== $skName) {
                        $itemData[$key] = $val;
                    }
                }
            }

            $this->ddbService->putItem($table, $itemData);
            return redirect()->route('dynamodb.items.index', $table)->with('success', 'Item saved successfully.');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete Item from table.
     */
    public function deleteItem(Request $request, string $table)
    {
        $request->validate([
            'pk_val' => 'required|string',
            'sk_val' => 'nullable|string',
        ]);

        try {
            $schema = $this->ddbService->describeTable($table);
            $pkName = $schema['partition_key_name'];
            $skName = $schema['sort_key_name'];

            $keyData = [
                $pkName => $request->input('pk_val')
            ];

            if ($skName) {
                $keyData[$skName] = $request->input('sk_val');
            }

            $this->ddbService->deleteItem($table, $keyData);
            return redirect()->route('dynamodb.items.index', $table)->with('success', 'Item deleted successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Query / Scan playground page.
     */
    public function queryScan(Request $request)
    {
        $status = $this->ddbService->getConnectionStatus();
        $tables = [];
        $results = null;
        $error = null;

        $selectedTable = $request->input('table');
        $operation = $request->input('operation', 'scan');
        $partitionValue = $request->input('partition_value');
        $sortValue = $request->input('sort_value');
        $sortOperator = $request->input('sort_operator', '=');

        if ($status['connected']) {
            try {
                $tables = $this->ddbService->listTables();

                if ($request->isMethod('POST')) {
                    $request->validate([
                        'table' => 'required|string',
                        'operation' => 'required|in:query,scan',
                    ]);

                    if ($operation === 'scan') {
                        $results = $this->ddbService->scanItems($selectedTable);
                    } else {
                        $results = $this->ddbService->queryItems($selectedTable, $partitionValue, $sortValue, $sortOperator);
                    }
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        return view('dynamodb.query-scan', compact(
            'status',
            'tables',
            'selectedTable',
            'operation',
            'partitionValue',
            'sortValue',
            'sortOperator',
            'results',
            'error'
        ));
    }
}
