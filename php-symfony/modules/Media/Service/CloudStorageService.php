<?php

declare(strict_types=1);

namespace App\Media\Service;

use Aws\S3\S3Client;
use Google\Cloud\Storage\StorageClient;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\GoogleCloudStorage\GoogleCloudStorageAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Spatie\FlysystemDropbox\DropboxAdapter;

/**
 * Cloud Storage Service for managing multiple cloud storage providers.
 */
class CloudStorageService
{
    private array $filesystems = [];

    public function __construct(
        private string $defaultProvider = 'local',
        private array $config = [],
    ) {
        $this->initializeFilesystems();
    }

    private function initializeFilesystems()
    {
        // Local storage (fallback)
        $this->filesystems['local'] = new Filesystem(
            new LocalFilesystemAdapter($this->config['local']['path'] ?? '/tmp')
        );

        // AWS S3
        if (isset($this->config['s3'])) {
            $s3Config = $this->config['s3'];
            $client = new S3Client([
                'credentials' => [
                    'key' => $s3Config['key'],
                    'secret' => $s3Config['secret'],
                ],
                'region' => $s3Config['region'],
                'version' => 'latest',
            ]);

            $this->filesystems['s3'] = new Filesystem(
                new AwsS3V3Adapter($client, $s3Config['bucket'])
            );
        }

        // Google Cloud Storage
        if (isset($this->config['gcs'])) {
            $gcsConfig = $this->config['gcs'];
            $storageClient = new StorageClient([
                'projectId' => $gcsConfig['project_id'],
                'keyFilePath' => $gcsConfig['key_file'],
            ]);

            $bucket = $storageClient->bucket($gcsConfig['bucket']);

            $this->filesystems['gcs'] = new Filesystem(
                new GoogleCloudStorageAdapter($bucket)
            );
        }

        // Dropbox
        if (isset($this->config['dropbox'])) {
            $dropboxConfig = $this->config['dropbox'];
            $this->filesystems['dropbox'] = new Filesystem(
                new DropboxAdapter($dropboxConfig['token'])
            );
        }
    }

    public function getFilesystem(?string $provider = null)
    {
        $provider = $provider ?? $this->defaultProvider;

        return $this->filesystems[$provider] ?? $this->filesystems['local'];
    }

    public function uploadFile(string $provider, string $path, string $content, array $config = [])
    {
        $filesystem = $this->getFilesystem($provider);
        $filesystem->write($path, $content, $config);

        return $path;
    }

    public function downloadFile(string $provider, string $path)
    {
        $filesystem = $this->getFilesystem($provider);

        return $filesystem->read($path);
    }

    public function deleteFile(string $provider, string $path)
    {
        $filesystem = $this->getFilesystem($provider);
        $filesystem->delete($path);
    }

    public function fileExists(string $provider, string $path)
    {
        $filesystem = $this->getFilesystem($provider);

        return $filesystem->fileExists($path);
    }

    public function getFileUrl(string $provider, string $path, int $expiration = 3600)
    {
        $filesystem = $this->getFilesystem($provider);

        // For S3, generate signed URL
        if ('s3' === $provider && method_exists($filesystem, 'getAdapter')) {
            $adapter = $filesystem->getAdapter();
            if (method_exists($adapter, 'getClient')) {
                $client = $adapter->getClient();
                $bucket = $this->config['s3']['bucket'];
                $command = $client->getCommand('GetObject', [
                    'Bucket' => $bucket,
                    'Key' => $path,
                ]);
                $request = $client->createPresignedRequest($command, "+{$expiration} seconds");

                return (string) $request->getUri();
            }
        }

        // For other providers, return direct path (implement as needed)
        return "/files/{$provider}/{$path}";
    }

    public function getAvailableProviders()
    {
        return array_keys($this->filesystems);
    }
}
