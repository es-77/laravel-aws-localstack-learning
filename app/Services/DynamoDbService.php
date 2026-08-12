<?php

namespace App\Services;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Illuminate\Support\Facades\Log;
use Exception;

class DynamoDbService
{
    protected ?DynamoDbClient $client = null;
    protected ?Marshaler $marshaler = null;

    /**
     * Get DynamoDB client instance.
     *
     * @return DynamoDbClient
     */
    public function getClient(): DynamoDbClient
    {
        if (!$this->client) {
            $endpoint = config('filesystems.disks.s3.endpoint', 'http://s3.localhost.localstack.cloud:4566');
            $ddbEndpoint = str_replace('s3.localhost.localstack.cloud', 'localhost', $endpoint);

            $this->client = new DynamoDbClient([
                'version' => 'latest',
                'region' => config('filesystems.disks.s3.region', 'us-east-1'),
                'endpoint' => $ddbEndpoint,
                'credentials' => [
                    'key' => config('filesystems.disks.s3.key', 'test'),
                    'secret' => config('filesystems.disks.s3.secret', 'test'),
                ],
            ]);
        }
        return $this->client;
    }

    /**
     * Get Marshaler instance.
     *
     * @return Marshaler
     */
    public function getMarshaler(): Marshaler
    {
        if (!$this->marshaler) {
            $this->marshaler = new Marshaler();
        }
        return $this->marshaler;
    }

    /**
     * Get DynamoDB Connection Status.
     *
     * @return array
     */
    public function getConnectionStatus(): array
    {
        try {
            $client = $this->getClient();
            $client->listTables(['Limit' => 1]);
            
            return [
                'connected' => true,
                'error' => null,
                'endpoint' => $client->getEndpoint(),
            ];
        } catch (Exception $e) {
            Log::error('LocalStack DynamoDB connection failed: ' . $e->getMessage());
            
            return [
                'connected' => false,
                'error' => $e->getMessage(),
                'endpoint' => 'http://localhost:4566',
            ];
        }
    }

    /**
     * List all tables with schema details.
     *
     * @return array
     * @throws Exception
     */
    public function listTables(): array
    {
        try {
            $client = $this->getClient();
            $result = $client->listTables();
            $tables = [];

            if (isset($result['TableNames'])) {
                foreach ($result['TableNames'] as $tableName) {
                    $tables[] = $this->describeTable($tableName);
                }
            }

            return $tables;
        } catch (Exception $e) {
            Log::error('Failed to list DynamoDB tables: ' . $e->getMessage());
            throw new Exception('Unable to list tables: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Describe a DynamoDB Table structure.
     *
     * @param string $tableName
     * @return array
     * @throws Exception
     */
    public function describeTable(string $tableName): array
    {
        try {
            $result = $this->getClient()->describeTable(['TableName' => $tableName]);
            $table = $result['Table'];
            
            $partitionKeyName = '';
            $partitionKeyType = '';
            $sortKeyName = null;
            $sortKeyType = null;
            
            $attrs = [];
            if (isset($table['AttributeDefinitions'])) {
                foreach ($table['AttributeDefinitions'] as $def) {
                    $attrs[$def['AttributeName']] = $def['AttributeType'];
                }
            }
            
            if (isset($table['KeySchema'])) {
                foreach ($table['KeySchema'] as $key) {
                    if ($key['KeyType'] === 'HASH') {
                        $partitionKeyName = $key['AttributeName'];
                        $partitionKeyType = $attrs[$partitionKeyName] ?? 'S';
                    } elseif ($key['KeyType'] === 'RANGE') {
                        $sortKeyName = $key['AttributeName'];
                        $sortKeyType = $attrs[$sortKeyName] ?? 'S';
                    }
                }
            }
            
            return [
                'name' => $table['TableName'],
                'partition_key_name' => $partitionKeyName,
                'partition_key_type' => $partitionKeyType,
                'sort_key_name' => $sortKeyName,
                'sort_key_type' => $sortKeyType,
                'status' => $table['TableStatus'],
                'item_count' => $table['ItemCount'] ?? 0,
                'size' => $table['TableSizeBytes'] ?? 0,
                'created_at' => $table['CreationDateTime'],
            ];
        } catch (Exception $e) {
            Log::error("Failed to describe table {$tableName}: " . $e->getMessage());
            throw new Exception("Unable to describe table: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create DynamoDB Table.
     *
     * @param string $tableName
     * @param string $partitionKey
     * @param string $partitionType
     * @param string|null $sortKey
     * @param string|null $sortType
     * @return void
     * @throws Exception
     */
    public function createTable(string $tableName, string $partitionKey, string $partitionType, ?string $sortKey = null, ?string $sortType = null): void
    {
        $tableName = trim($tableName);
        $partitionKey = trim($partitionKey);

        if (!preg_match('/^[a-zA-Z0-9_.-]{3,255}$/', $tableName)) {
            throw new Exception('Invalid table name. Must be 3-255 characters, containing only letters, numbers, underscores, periods, and hyphens.');
        }

        $attrDefs = [
            ['AttributeName' => $partitionKey, 'AttributeType' => $partitionType]
        ];
        $keySchema = [
            ['AttributeName' => $partitionKey, 'KeyType' => 'HASH']
        ];
        
        if ($sortKey && trim($sortKey) !== '') {
            $sortKey = trim($sortKey);
            $attrDefs[] = ['AttributeName' => $sortKey, 'AttributeType' => $sortType];
            $keySchema[] = ['AttributeName' => $sortKey, 'KeyType' => 'RANGE'];
        }
        
        $params = [
            'TableName' => $tableName,
            'AttributeDefinitions' => $attrDefs,
            'KeySchema' => $keySchema,
            'BillingMode' => 'PAY_PER_REQUEST', // Pay-per-request is recommended for local sandbox testing
        ];

        try {
            $this->getClient()->createTable($params);
        } catch (Exception $e) {
            Log::error("Failed to create DynamoDB table {$tableName}: " . $e->getMessage());
            throw new Exception('Unable to create table: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete DynamoDB Table.
     *
     * @param string $tableName
     * @return void
     * @throws Exception
     */
    public function deleteTable(string $tableName): void
    {
        try {
            $this->getClient()->deleteTable(['TableName' => $tableName]);
        } catch (Exception $e) {
            Log::error("Failed to delete DynamoDB table {$tableName}: " . $e->getMessage());
            throw new Exception('Unable to delete table: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Scan items in the table.
     *
     * @param string $tableName
     * @param int $limit
     * @return array
     * @throws Exception
     */
    public function scanItems(string $tableName, int $limit = 50): array
    {
        try {
            $params = [
                'TableName' => $tableName,
                'Limit' => $limit,
            ];
            
            $result = $this->getClient()->scan($params);
            
            $items = [];
            $marshaler = $this->getMarshaler();

            if (isset($result['Items'])) {
                foreach ($result['Items'] as $item) {
                    $items[] = $marshaler->unmarshalItem($item);
                }
            }

            return $items;
        } catch (Exception $e) {
            Log::error("Failed to scan table {$tableName}: " . $e->getMessage());
            throw new Exception('Unable to retrieve items: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Query items in the table using partition and optional sort key values.
     *
     * @param string $tableName
     * @param string $partitionValue
     * @param string|null $sortValue
     * @param string $sortOperator
     * @return array
     * @throws Exception
     */
    public function queryItems(string $tableName, string $partitionValue, ?string $sortValue = null, string $sortOperator = '='): array
    {
        try {
            $marshaler = $this->getMarshaler();
            $tableDesc = $this->describeTable($tableName);
            
            $pkName = $tableDesc['partition_key_name'];
            $skName = $tableDesc['sort_key_name'];
            $pkType = $tableDesc['partition_key_type'];
            $skType = $tableDesc['sort_key_type'];
            
            $keyConditions = '#pk = :pk';
            $exprNames = ['#pk' => $pkName];
            
            // Format partition key value based on attribute type definition
            $typedPk = $pkType === 'N' ? (float)$partitionValue : (string)$partitionValue;
            $exprValues = [':pk' => $marshaler->marshalValue($typedPk)];
            
            if ($skName && $sortValue !== null && trim($sortValue) !== '') {
                $sortValue = trim($sortValue);
                $typedSk = $skType === 'N' ? (float)$sortValue : (string)$sortValue;
                $keyConditions .= " AND #sk {$sortOperator} :sk";
                $exprNames['#sk'] = $skName;
                $exprValues[':sk'] = $marshaler->marshalValue($typedSk);
            }
            
            $params = [
                'TableName' => $tableName,
                'KeyConditionExpression' => $keyConditions,
                'ExpressionAttributeNames' => $exprNames,
                'ExpressionAttributeValues' => $exprValues,
            ];
            
            $result = $this->getClient()->query($params);
            
            $items = [];
            if (isset($result['Items'])) {
                foreach ($result['Items'] as $item) {
                    $items[] = $marshaler->unmarshalItem($item);
                }
            }
            
            return $items;
        } catch (Exception $e) {
            Log::error("Failed to query table {$tableName}: " . $e->getMessage());
            throw new Exception('Query operation failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Put (Insert/Replace) NoSQL Item in a table.
     *
     * @param string $tableName
     * @param array $itemData
     * @return void
     * @throws Exception
     */
    public function putItem(string $tableName, array $itemData): void
    {
        try {
            $marshaler = $this->getMarshaler();
            
            // Ensure primary key attributes match types
            $tableDesc = $this->describeTable($tableName);
            $pkName = $tableDesc['partition_key_name'];
            $skName = $tableDesc['sort_key_name'];
            $pkType = $tableDesc['partition_key_type'];
            $skType = $tableDesc['sort_key_type'];

            if (!isset($itemData[$pkName]) || trim($itemData[$pkName]) === '') {
                throw new Exception("Missing partition key value for attribute: {$pkName}");
            }

            // Unmarshal / clean type casting for primary keys
            $itemData[$pkName] = $pkType === 'N' ? (float)$itemData[$pkName] : (string)$itemData[$pkName];
            
            if ($skName) {
                if (!isset($itemData[$skName]) || trim($itemData[$skName]) === '') {
                    throw new Exception("Missing sort key value for attribute: {$skName}");
                }
                $itemData[$skName] = $skType === 'N' ? (float)$itemData[$skName] : (string)$itemData[$skName];
            }

            // Convert other attributes if numeric strings to float/int to store as N type
            foreach ($itemData as $key => $val) {
                if ($key !== $pkName && $key !== $skName && is_string($val) && is_numeric($val)) {
                    $itemData[$key] = strpos($val, '.') !== false ? (float)$val : (int)$val;
                }
            }

            $marshaledItem = $marshaler->marshalItem($itemData);
            
            $this->getClient()->putItem([
                'TableName' => $tableName,
                'Item' => $marshaledItem,
            ]);
        } catch (Exception $e) {
            Log::error("Failed to put item in table {$tableName}: " . $e->getMessage());
            throw new Exception($e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete Item from Table.
     *
     * @param string $tableName
     * @param array $keyData (Primary keys array)
     * @return void
     * @throws Exception
     */
    public function deleteItem(string $tableName, array $keyData): void
    {
        try {
            $marshaler = $this->getMarshaler();
            
            // Typecast keys based on describe table schema
            $tableDesc = $this->describeTable($tableName);
            $pkName = $tableDesc['partition_key_name'];
            $skName = $tableDesc['sort_key_name'];
            $pkType = $tableDesc['partition_key_type'];
            $skType = $tableDesc['sort_key_type'];

            $key = [
                $pkName => $pkType === 'N' ? (float)$keyData[$pkName] : (string)$keyData[$pkName]
            ];

            if ($skName) {
                $key[$skName] = $skType === 'N' ? (float)$keyData[$skName] : (string)$keyData[$skName];
            }

            $marshaledKey = $marshaler->marshalItem($key);

            $this->getClient()->deleteItem([
                'TableName' => $tableName,
                'Key' => $marshaledKey,
            ]);
        } catch (Exception $e) {
            Log::error("Failed to delete item from table {$tableName}: " . $e->getMessage());
            throw new Exception('Unable to delete item: ' . $e->getMessage(), 0, $e);
        }
    }
}
