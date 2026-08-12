<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\S3Controller;

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

