<?php

namespace App\Services;

use Aws\S3\S3Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Exception;

class S3Service
{
    protected ?S3Client $client = null;

    /**
     * Get S3 client instance from Laravel's filesystem config.
     *
     * @return S3Client
     */
    public function getClient(): S3Client
    {
        if (!$this->client) {
            $this->client = Storage::disk('s3')->getClient();
        }
        return $this->client;
    }

    /**
     * Get connection status for S3 / LocalStack.
     *
     * @return array
     */
    public function getConnectionStatus(): array
    {
        try {
            $client = $this->getClient();
            $client->listBuckets();
            
            return [
                'connected' => true,
                'error' => null,
                'endpoint' => config('filesystems.disks.s3.endpoint'),
                'region' => config('filesystems.disks.s3.region', 'us-east-1'),
                'bucket' => config('filesystems.disks.s3.bucket'),
                'path_style' => config('filesystems.disks.s3.use_path_style_endpoint', false),
            ];
        } catch (Exception $e) {
            Log::error('LocalStack S3 connection failed: ' . $e->getMessage());
            
            return [
                'connected' => false,
                'error' => $e->getMessage(),
                'endpoint' => config('filesystems.disks.s3.endpoint'),
                'region' => config('filesystems.disks.s3.region', 'us-east-1'),
                'bucket' => config('filesystems.disks.s3.bucket'),
                'path_style' => config('filesystems.disks.s3.use_path_style_endpoint', false),
            ];
        }
    }

    /**
     * List all S3 buckets with metadata.
     *
     * @return array
     */
    public function listBuckets(): array
    {
        try {
            $client = $this->getClient();
            $result = $client->listBuckets();
            $buckets = [];

            if (isset($result['Buckets'])) {
                foreach ($result['Buckets'] as $b) {
                    $name = $b['Name'];
                    $createdAt = $b['CreationDate'];

                    // Get bucket location/region
                    $region = 'us-east-1';
                    try {
                        $loc = $client->getBucketLocation(['Bucket' => $name]);
                        if (!empty($loc['LocationConstraint'])) {
                            $region = $loc['LocationConstraint'];
                        }
                    } catch (Exception $e) {
                        Log::warning("Could not get region for bucket {$name}: " . $e->getMessage());
                    }

                    // Get object count and size
                    $objectCount = 0;
                    $totalSize = 0;
                    try {
                        $objects = $client->listObjectsV2(['Bucket' => $name]);
                        if (isset($objects['Contents'])) {
                            $objectCount = count($objects['Contents']);
                            foreach ($objects['Contents'] as $obj) {
                                $totalSize += $obj['Size'];
                            }
                        }
                    } catch (Exception $e) {
                        Log::warning("Could not get object list for bucket {$name}: " . $e->getMessage());
                    }

                    $buckets[] = [
                        'name' => $name,
                        'region' => $region,
                        'created_at' => $createdAt,
                        'object_count' => $objectCount,
                        'total_size' => $totalSize,
                    ];
                }
            }

            return $buckets;
        } catch (Exception $e) {
            Log::error('Failed to list S3 buckets: ' . $e->getMessage());
            throw new Exception('Unable to list buckets: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create a new bucket.
     *
     * @param string $name
     * @param string|null $region
     * @return void
     * @throws Exception
     */
    public function createBucket(string $name, ?string $region = null): void
    {
        $this->validateBucketName($name);
        
        $region = $region ?: config('filesystems.disks.s3.region', 'us-east-1');
        $params = ['Bucket' => $name];

        // us-east-1 does not accept LocationConstraint in CreateBucketConfiguration
        if ($region !== 'us-east-1') {
            $params['CreateBucketConfiguration'] = [
                'LocationConstraint' => $region,
            ];
        }

        try {
            $this->getClient()->createBucket($params);
        } catch (Exception $e) {
            Log::error("Failed to create bucket {$name}: " . $e->getMessage());
            throw new Exception('Unable to create bucket: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete an S3 bucket.
     *
     * @param string $name
     * @param bool $force Delete all objects first if true
     * @return void
     * @throws Exception
     */
    public function deleteBucket(string $name, bool $force = false): void
    {
        try {
            $client = $this->getClient();

            // Check if bucket has objects
            $objects = $client->listObjectsV2(['Bucket' => $name]);
            $hasObjects = isset($objects['Contents']) && count($objects['Contents']) > 0;

            if ($hasObjects) {
                if (!$force) {
                    throw new Exception('Bucket is not empty. Clean all objects first or enable forced deletion.');
                }

                // Delete all objects
                $keys = [];
                foreach ($objects['Contents'] as $obj) {
                    $keys[] = ['Key' => $obj['Key']];
                }

                $client->deleteObjects([
                    'Bucket' => $name,
                    'Delete' => [
                        'Objects' => $keys,
                        'Quiet' => true,
                    ],
                ]);
            }

            // Delete bucket
            $client->deleteBucket(['Bucket' => $name]);
        } catch (Exception $e) {
            Log::error("Failed to delete bucket {$name}: " . $e->getMessage());
            throw new Exception($e->getMessage(), 0, $e);
        }
    }

    /**
     * List objects and virtual folders (prefixes) within a bucket.
     *
     * @param string $bucketName
     * @param string $prefix
     * @return array
     * @throws Exception
     */
    public function listObjects(string $bucketName, string $prefix = ''): array
    {
        try {
            $client = $this->getClient();
            $params = [
                'Bucket' => $bucketName,
                'Delimiter' => '/',
            ];

            if ($prefix !== '') {
                // Ensure prefix ends with '/'
                $prefix = rtrim($prefix, '/') . '/';
                $params['Prefix'] = $prefix;
            }

            $result = $client->listObjectsV2($params);
            
            $folders = [];
            $files = [];

            // Parse sub-folders (CommonPrefixes)
            if (isset($result['CommonPrefixes'])) {
                foreach ($result['CommonPrefixes'] as $cp) {
                    $fullPrefix = $cp['Prefix'];
                    // Extract folder name from prefix
                    $parts = explode('/', rtrim($fullPrefix, '/'));
                    $folderName = end($parts);
                    
                    $folders[] = [
                        'name' => $folderName,
                        'prefix' => $fullPrefix,
                    ];
                }
            }

            // Parse files (Contents)
            if (isset($result['Contents'])) {
                foreach ($result['Contents'] as $content) {
                    $key = $content['Key'];
                    
                    // Skip if the key matches the prefix itself (which indicates the directory placeholder)
                    if ($key === $prefix) {
                        continue;
                    }

                    $parts = explode('/', $key);
                    $filename = end($parts);
                    
                    $files[] = [
                        'name' => $filename,
                        'key' => $key,
                        'size' => $content['Size'],
                        'last_modified' => $content['LastModified'],
                        'mime_type' => $this->detectMimeType($filename),
                    ];
                }
            }

            return [
                'folders' => $folders,
                'files' => $files,
                'bucket' => $bucketName,
                'prefix' => $prefix,
            ];
        } catch (Exception $e) {
            Log::error("Failed to list objects in bucket {$bucketName} with prefix {$prefix}: " . $e->getMessage());
            throw new Exception("Unable to list files: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Upload file to a specific S3 bucket and prefix.
     *
     * @param string $bucketName
     * @param UploadedFile $file
     * @param string $prefix
     * @return void
     * @throws Exception
     */
    public function uploadFile(string $bucketName, UploadedFile $file, string $prefix = 'uploads/'): void
    {
        try {
            $client = $this->getClient();
            
            $filename = $file->getClientOriginalName();
            $prefix = $prefix ? rtrim($prefix, '/') . '/' : '';
            $key = $prefix . $filename;

            $client->putObject([
                'Bucket' => $bucketName,
                'Key' => $key,
                'Body' => fopen($file->getRealPath(), 'r'),
                'ContentType' => $file->getMimeType() ?: 'application/octet-stream',
            ]);
        } catch (Exception $e) {
            Log::error("Failed to upload file to bucket {$bucketName}: " . $e->getMessage());
            throw new Exception('Unable to upload file: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Download object from bucket.
     *
     * @param string $bucketName
     * @param string $key
     * @return array
     * @throws Exception
     */
    public function getObject(string $bucketName, string $key): array
    {
        try {
            $client = $this->getClient();
            $result = $client->getObject([
                'Bucket' => $bucketName,
                'Key' => $key,
            ]);

            return [
                'body' => $result['Body'],
                'content_type' => $result['ContentType'] ?: 'application/octet-stream',
                'size' => $result['ContentLength'],
            ];
        } catch (Exception $e) {
            Log::error("Failed to retrieve S3 object {$key} from bucket {$bucketName}: " . $e->getMessage());
            throw new Exception("Unable to download object: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete an S3 object.
     *
     * @param string $bucketName
     * @param string $key
     * @return void
     * @throws Exception
     */
    public function deleteObject(string $bucketName, string $key): void
    {
        try {
            $this->getClient()->deleteObject([
                'Bucket' => $bucketName,
                'Key' => $key,
            ]);
        } catch (Exception $e) {
            Log::error("Failed to delete S3 object {$key} from bucket {$bucketName}: " . $e->getMessage());
            throw new Exception("Unable to delete file: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Validate bucket name against S3 rules.
     *
     * @param string $name
     * @return void
     * @throws Exception
     */
    protected function validateBucketName(string $name): void
    {
        $length = strlen($name);
        if ($length < 3 || $length > 63) {
            throw new Exception('Bucket name must be between 3 and 63 characters long.');
        }

        if (!preg_match('/^[a-z0-9.-]+$/', $name)) {
            throw new Exception('Bucket name can only contain lowercase letters, numbers, periods (.), and hyphens (-).');
        }

        if (preg_match('/(^|[-.])[-.]/', $name)) {
            throw new Exception('Bucket name cannot contain consecutive periods or hyphens.');
        }

        if (!preg_match('/^[a-z0-9]/', $name) || !preg_match('/[a-z0-9]$/', $name)) {
            throw new Exception('Bucket name must start and end with a lowercase letter or number.');
        }

        if (preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $name)) {
            throw new Exception('Bucket name cannot be formatted as an IP address.');
        }
    }

    /**
     * Detect mime type by extension fallback.
     *
     * @param string $filename
     * @return string
     */
    protected function detectMimeType(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $types = [
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'txt' => 'text/plain',
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'zip' => 'application/zip',
        ];

        return $types[$ext] ?? 'application/octet-stream';
    }
}
