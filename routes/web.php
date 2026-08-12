<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\S3Controller;
use App\Http\Controllers\IamController;
use App\Http\Controllers\DynamoDbController;

Route::get('/', [S3Controller::class, 'overview'])->name('dashboard');

Route::prefix('s3')->group(function () {
    Route::get('/', [S3Controller::class, 'overview'])->name('s3.overview');
    
    Route::get('/buckets', [S3Controller::class, 'listBuckets'])->name('s3.buckets.index');
    Route::post('/buckets', [S3Controller::class, 'createBucket'])->name('s3.buckets.store');
    Route::delete('/buckets/{bucket}', [S3Controller::class, 'deleteBucket'])->name('s3.buckets.destroy');
    
    Route::get('/buckets/{bucket}/objects', [S3Controller::class, 'listObjects'])->name('s3.buckets.show');
    Route::delete('/buckets/{bucket}/objects', [S3Controller::class, 'deleteObject'])->name('s3.objects.destroy');
    
    Route::get('/upload', [S3Controller::class, 'uploadPage'])->name('s3.upload.index');
    Route::post('/upload', [S3Controller::class, 'uploadFile'])->name('s3.upload.store');
    
    Route::get('/buckets/{bucket}/download', [S3Controller::class, 'downloadObject'])->name('s3.objects.download');
    Route::get('/buckets/{bucket}/preview', [S3Controller::class, 'previewObject'])->name('s3.objects.preview');
    
    Route::get('/permissions', [S3Controller::class, 'permissions'])->name('s3.permissions');
});

Route::prefix('iam')->group(function () {
    Route::get('/', [IamController::class, 'overview'])->name('iam.overview');
    
    Route::get('/users', [IamController::class, 'users'])->name('iam.users.index');
    Route::post('/users', [IamController::class, 'createUser'])->name('iam.users.store');
    Route::delete('/users/{username}', [IamController::class, 'deleteUser'])->name('iam.users.destroy');
    Route::post('/users/{username}/policies', [IamController::class, 'attachPolicy'])->name('iam.users.policy.attach');
    Route::delete('/users/{username}/policies', [IamController::class, 'detachPolicy'])->name('iam.users.policy.detach');
    
    Route::get('/roles', [IamController::class, 'roles'])->name('iam.roles.index');
    Route::post('/roles', [IamController::class, 'createRole'])->name('iam.roles.store');
    Route::delete('/roles/{role}', [IamController::class, 'deleteRole'])->name('iam.roles.destroy');
    
    Route::get('/policies', [IamController::class, 'policies'])->name('iam.policies.index');
    Route::post('/policies', [IamController::class, 'createPolicy'])->name('iam.policies.store');
    Route::delete('/policies', [IamController::class, 'deletePolicy'])->name('iam.policies.destroy');
    
    Route::match(['get', 'post'], '/simulator', [IamController::class, 'simulator'])->name('iam.simulator');
});

Route::prefix('dynamodb')->group(function () {
    Route::get('/', [DynamoDbController::class, 'overview'])->name('dynamodb.overview');
    Route::get('/tables', [DynamoDbController::class, 'tables'])->name('dynamodb.tables.index');
    Route::post('/tables', [DynamoDbController::class, 'createTable'])->name('dynamodb.tables.store');
    Route::delete('/tables/{table}', [DynamoDbController::class, 'deleteTable'])->name('dynamodb.tables.destroy');
    
    Route::get('/tables/{table}/items', [DynamoDbController::class, 'items'])->name('dynamodb.items.index');
    Route::post('/tables/{table}/items', [DynamoDbController::class, 'putItem'])->name('dynamodb.items.store');
    Route::delete('/tables/{table}/items', [DynamoDbController::class, 'deleteItem'])->name('dynamodb.items.destroy');
    
    Route::match(['get', 'post'], '/query-scan', [DynamoDbController::class, 'queryScan'])->name('dynamodb.query-scan');
});

