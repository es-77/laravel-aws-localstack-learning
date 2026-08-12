# Stage 3: DynamoDB Integration (Amazon DynamoDB NoSQL Database)

## Design Decisions: Local NoSQL Learning Dashboard

DynamoDB is a fully managed NoSQL database service that provides fast and predictable performance with seamless scalability. In local development environments, we connect to LocalStack's DynamoDB service.

Unlike relational databases (like MySQL) where you define strict tables and columns in advance (schemas), NoSQL databases like DynamoDB are **schema-less** at the item level. Each item in a table can have completely different attributes, except for the **Primary Key attributes**, which must be defined during table creation and must be supplied for every item.

To teach DynamoDB design patterns, our module focuses on:
1. **Dynamic Table Schemes**: Creating tables with custom Hash/Partition keys (S or N) and optional Range/Sort keys.
2. **Flexible Item Insertion**: Providing a simple JSON-attribute builder to insert NoSQL items with custom attributes.
3. **Query vs Scan Workspaces**: An interactive query tool to help developers understand why a targeted `Query` (O(1) lookup on key indices) is superior to a broad `Scan` (O(N) search reading every record).

---

## DynamoDB Architecture Overview

```
Request ──> DynamoDbController ──> DynamoDbService ──> AWS DynamoDbClient SDK ──> LocalStack DynamoDB
```

---

## Service Functions Summary (`app/Services/DynamoDbService.php`)

### 1. `getClient(): \Aws\DynamoDb\DynamoDbClient`
Initializes the AWS PHP SDK `DynamoDbClient` targeting the local unified gateway `http://localhost:4566`.

### 2. `getConnectionStatus(): array`
Performs a lightweight `listTables(['Limit' => 1])` call to check if DynamoDB is reachable.

### 3. `listTables(): array`
Queries `listTables()` to list all active tables. For each table, runs `describeTable()` to fetch:
- Table name and status.
- Partition key name and type.
- Sort key name and type (if configured).
- Total item count and size in bytes.

### 4. `createTable(...)`:
Creates a table using `createTable()`.
- Declares `AttributeDefinitions` (name and type: `S` for string, `N` for number).
- Declares `KeySchema` (Hash for Partition key, Range for Sort key).
- Uses `PAY_PER_REQUEST` billing mode (On-Demand) to avoid specifying read/write capacity units in local testing.

### 5. `deleteTable(string $tableName): void`
Deletes a table from DynamoDB by name.

### 6. `scanItems(string $tableName, int $limit = 50): array`
Executes a raw `Scan` on the table to retrieve all items (up to `$limit`). Unpacks DynamoDB typed attributes (e.g. `{ "name": { "S": "Shayan" } }` -> `{ "name": "Shayan" }`) into simple PHP arrays for clean web presentation.

### 7. `queryItems(string $tableName, string $partitionValue, ?string $sortValue = null, string $sortOperator = '='): array`
Executes a targeted `Query`.
- Inspects the table schema to identify the key names.
- Configures `KeyConditionExpression` and `ExpressionAttributeValues`.
- Evaluates search parameters on index attributes.

### 8. `putItem(string $tableName, array $item): void`
Formats a simple PHP array into a DynamoDB typed attribute object (e.g., matching string, number, or boolean formats) and saves it using `putItem()`.

### 9. `deleteItem(string $tableName, array $key): void`
Deletes an item from the table using its exact primary key mapping.
