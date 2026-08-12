<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\S3Service;
use Exception;
use Illuminate\Support\Facades\Log;

class S3Controller extends Controller
{
    protected S3Service $s3Service;

    public function __construct(S3Service $s3Service)
    {
        $this->s3Service = $s3Service;
    }

    /**
     * S3 Overview Dashboard.
     */
    public function overview()
    {
        $status = $this->s3Service->getConnectionStatus();
        $buckets = [];
        $totalBuckets = 0;
        $totalObjects = 0;
        $totalSize = 0;
        $recentFiles = [];

        if ($status['connected']) {
            try {
                $buckets = $this->s3Service->listBuckets();
                $totalBuckets = count($buckets);

                foreach ($buckets as $b) {
                    $totalObjects += $b['object_count'];
                    $totalSize += $b['total_size'];
                }

                // Get recent files from the default bucket if it exists
                $defaultBucket = config('filesystems.disks.s3.bucket');
                $defaultBucketExists = collect($buckets)->contains('name', $defaultBucket);

                if ($defaultBucketExists) {
                    $objects = $this->s3Service->listObjects($defaultBucket);
                    $recentFiles = collect($objects['files'])
                        ->sortByDesc('last_modified')
                        ->take(5)
                        ->values()
                        ->all();
                } else if ($totalBuckets > 0) {
                    // Fallback to first available bucket
                    $objects = $this->s3Service->listObjects($buckets[0]['name']);
                    $recentFiles = collect($objects['files'])
                        ->sortByDesc('last_modified')
                        ->take(5)
                        ->values()
                        ->all();
                }
            } catch (Exception $e) {
                Log::warning('Failed to load dashboard S3 metadata: ' . $e->getMessage());
            }
        }

        return view('s3.overview', compact(
            'status',
            'totalBuckets',
            'totalObjects',
            'totalSize',
            'recentFiles',
            'buckets'
        ));
    }

    /**
     * List all Buckets.
     */
    public function listBuckets()
    {
        $status = $this->s3Service->getConnectionStatus();
        $buckets = [];
        $error = null;

        if ($status['connected']) {
            try {
                $buckets = $this->s3Service->listBuckets();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = "Unable to connect to LocalStack S3. Please ensure Docker is running.";
        }

        return view('s3.buckets', compact('buckets', 'error', 'status'));
    }

    /**
     * Create Bucket.
     */
    public function createBucket(Request $request)
    {
        $request->validate([
            'bucket_name' => 'required|string',
            'region' => 'nullable|string',
        ]);

        $name = strtolower(trim($request->input('bucket_name')));
        $region = $request->input('region');

        try {
            $this->s3Service->createBucket($name, $region);
            return redirect()->route('s3.buckets.index')->with('success', "Bucket '{$name}' created successfully!");
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete Bucket.
     */
    public function deleteBucket(Request $request, string $bucket)
    {
        $force = $request->has('force');

        try {
            $this->s3Service->deleteBucket($bucket, $force);
            return redirect()->route('s3.buckets.index')->with('success', "Bucket '{$bucket}' deleted successfully.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * List objects in a bucket.
     */
    public function listObjects(Request $request, string $bucket)
    {
        $status = $this->s3Service->getConnectionStatus();
        $prefix = $request->query('prefix', '');
        $data = [];
        $error = null;

        if (!$status['connected']) {
            return redirect()->route('s3.overview')->with('error', 'LocalStack S3 connection offline.');
        }

        try {
            $data = $this->s3Service->listObjects($bucket, $prefix);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        return view('s3.objects', compact('data', 'bucket', 'prefix', 'error', 'status'));
    }

    /**
     * Delete object from a bucket.
     */
    public function deleteObject(Request $request, string $bucket)
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        $key = $request->input('key');

        try {
            $this->s3Service->deleteObject($bucket, $key);
            return redirect()->back()->with('success', "File '" . basename($key) . "' deleted successfully.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show Upload Page.
     */
    public function uploadPage()
    {
        $status = $this->s3Service->getConnectionStatus();
        $buckets = [];
        $selectedBucket = config('filesystems.disks.s3.bucket');

        if ($status['connected']) {
            try {
                $buckets = $this->s3Service->listBuckets();
            } catch (Exception $e) {
                Log::warning('Failed to list buckets for upload: ' . $e->getMessage());
            }
        }

        return view('s3.upload', compact('buckets', 'selectedBucket', 'status'));
    }

    /**
     * Handle S3 File Upload.
     */
    public function uploadFile(Request $request)
    {
        $request->validate([
            'bucket' => 'required|string',
            'prefix' => 'nullable|string',
            'files' => 'required|array',
            'files.*' => 'required|file|max:20480', // Max 20MB per file
        ]);

        $bucket = $request->input('bucket');
        $prefix = $request->input('prefix', 'uploads/');
        $files = $request->file('files');
        
        $successCount = 0;
        $errors = [];

        foreach ($files as $file) {
            try {
                $this->s3Service->uploadFile($bucket, $file, $prefix);
                $successCount++;
            } catch (Exception $e) {
                $errors[] = "Failed to upload {$file->getClientOriginalName()}: " . $e->getMessage();
            }
        }

        if (count($errors) > 0) {
            $errMsg = implode('; ', $errors);
            if ($successCount > 0) {
                return redirect()->route('s3.buckets.show', ['bucket' => $bucket, 'prefix' => $prefix])
                    ->with('success', "Uploaded {$successCount} files.")
                    ->with('error', "Failed uploads: " . $errMsg);
            }
            return redirect()->back()->with('error', $errMsg);
        }

        return redirect()->route('s3.buckets.show', ['bucket' => $bucket, 'prefix' => $prefix])
            ->with('success', "All {$successCount} files uploaded successfully under prefix '{$prefix}'!");
    }

    /**
     * Download Object.
     */
    public function downloadObject(string $bucket, Request $request)
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        $key = $request->query('key');

        try {
            $object = $this->s3Service->getObject($bucket, $key);
            
            return response()->stream(function () use ($object) {
                fpassthru($object['body']);
            }, 200, [
                'Content-Type' => $object['content_type'],
                'Content-Length' => $object['size'],
                'Content-Disposition' => 'attachment; filename="' . rawurlencode(basename($key)) . '"',
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with('error', "Download failed: " . $e->getMessage());
        }
    }

    /**
     * Preview Object.
     */
    public function previewObject(string $bucket, Request $request)
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        $key = $request->query('key');

        try {
            $object = $this->s3Service->getObject($bucket, $key);
            
            return response()->stream(function () use ($object) {
                fpassthru($object['body']);
            }, 200, [
                'Content-Type' => $object['content_type'],
                'Content-Length' => $object['size'],
                'Content-Disposition' => 'inline',
            ]);
        } catch (Exception $e) {
            return response("Preview failed: " . $e->getMessage(), 404);
        }
    }

    /**
     * S3 Connection & Permissions Page.
     */
    public function permissions()
    {
        $status = $this->s3Service->getConnectionStatus();
        return view('s3.permissions', compact('status'));
    }
}
