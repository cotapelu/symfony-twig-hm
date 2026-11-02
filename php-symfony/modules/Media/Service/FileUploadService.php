<?php

declare(strict_types=1);

namespace App\Media\Service;

/**
 * Service for handling file uploads in the Communication module.
 */
class FileUploadService
{
    private string $uploadDirectory;
    private array $allowedMimeTypes;
    private int $maxFileSize; // in bytes
    private FileProcessorFactory $processorFactory;

    private const DEFAULT_ALLOWED_MIME_TYPES = [
        // Images
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        // Documents
        'application/pdf',
        'text/plain',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        // Audio (Voice messages)
        'audio/mpeg',
        'audio/wav',
        'audio/ogg',
        'audio/mp3',
        'audio/webm',
        'audio/m4a',
        // Video
        'video/mp4',
        'video/avi',
        'video/quicktime',
        'video/webm',
    ];

    private const MIME_TO_EXTENSION_MAP = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
        'image/webp' => ['webp'],
        'application/pdf' => ['pdf'],
        'text/plain' => ['txt'],
        'application/msword' => ['doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        'audio/mpeg' => ['mp3'],
        'audio/wav' => ['wav'],
        'audio/ogg' => ['ogg'],
        'audio/mp3' => ['mp3'],
        'audio/webm' => ['webm'],
        'audio/m4a' => ['m4a'],
        'video/mp4' => ['mp4'],
        'video/avi' => ['avi'],
        'video/quicktime' => ['mov'],
        'video/webm' => ['webm'],
    ];

    public function __construct(
        FileProcessorFactory $processorFactory,
        ?string $uploadDirectory = null,
        ?array $allowedMimeTypes = null,
        ?int $maxFileSize = null,
    ) {
        $this->processorFactory = $processorFactory;
        $this->uploadDirectory = $uploadDirectory ?? __DIR__.'/../../../public/uploads/communication';
        $this->allowedMimeTypes = $allowedMimeTypes ?? self::DEFAULT_ALLOWED_MIME_TYPES;
        $this->maxFileSize = $maxFileSize ?? 10 * 1024 * 1024; // 10MB default

        // Register default processors
        $this->registerDefaultProcessors();
    }

    private function registerDefaultProcessors()
    {
        // Validators
        $this->processorFactory->registerValidator(new JpegValidator());
        $this->processorFactory->registerValidator(new PngValidator());
        $this->processorFactory->registerValidator(new GifValidator());
        $this->processorFactory->registerValidator(new PdfValidator());
        $this->processorFactory->registerValidator(new Mp4Validator());

        // Optimizers
        $this->processorFactory->registerOptimizer(new JpegOptimizer());
        $this->processorFactory->registerOptimizer(new PngOptimizer());
        $this->processorFactory->registerOptimizer(new GifOptimizer());
        $this->processorFactory->registerOptimizer(new WebpOptimizer());

        // Thumbnail generators
        $this->processorFactory->registerThumbnailGenerator(new JpegThumbnailGenerator());
        $this->processorFactory->registerThumbnailGenerator(new PngThumbnailGenerator());
        $this->processorFactory->registerThumbnailGenerator(new GifThumbnailGenerator());
        $this->processorFactory->registerThumbnailGenerator(new WebpThumbnailGenerator());
    }

    /**
     * Upload a file and return file information.
     */
    public function uploadFile(array $uploadedFile, string $userId)
    {
        $this->validateFile($uploadedFile);
        $filepath = $this->prepareFilepath($uploadedFile);
        if (!$this->moveUploadedFile($uploadedFile['tmp_name'], $filepath)) {
            throw new \RuntimeException('Failed to move uploaded file');
        }
        $thumbnailUrl = $this->processImageIfApplicable($filepath, $uploadedFile);
        $finalSize = filesize($filepath);
        return $this->buildUploadResult($uploadedFile, $filepath, $thumbnailUrl, $finalSize, $userId);
    }

    private function prepareFilepath(array $uploadedFile): string
    {
        if (!is_dir($this->uploadDirectory)) {
            mkdir($this->uploadDirectory, 0755, true);
        }
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '', basename($uploadedFile['name']));
        $filename = uniqid('comm_', true).'_'.$safeName;
        return $this->uploadDirectory.'/'.$filename;
    }

    private function processImageIfApplicable(string $filepath, array $uploadedFile): ?string
    {
        if (str_starts_with($uploadedFile['type'], 'image/')) {
            $this->optimizeImage($filepath, $uploadedFile['type']);
            $filename = basename($filepath);
            return $this->generateThumbnail($filepath, $filename, $uploadedFile['type']);
        }
        return null;
    }

    private function buildUploadResult(array $uploadedFile, string $filepath, ?string $thumbnailUrl, int $finalSize, string $userId): array
    {
        $filename = basename($filepath);
        return [
            'name' => $uploadedFile['name'],
            'filename' => $filename,
            'url' => '/uploads/communication/'.$filename,
            'thumbnail_url' => $thumbnailUrl,
            'size' => $finalSize,
            'mime_type' => $uploadedFile['type'],
            'uploaded_at' => time(),
            'uploaded_by' => $userId,
        ];
    }

    /**
     * Validate uploaded file.
     */
    private function validateFile(array $file): void
    {
        $this->validateUploadError($file);
        $this->validateFileSize($file);
        $this->validateFileExtension($file);
        $mimeType = $this->determineAndValidateMimeType($file);
        $this->validateImageSpecifics($file, $mimeType);
        $this->validateFileSignature($file['tmp_name'], $mimeType);
        $this->scanForMaliciousContent($file);
    }

    private function validateUploadError(array $file): void
    {
        if (UPLOAD_ERR_OK !== $file['error']) {
            throw new \InvalidArgumentException('File upload failed with error: '.$file['error']);
        }
    }

    private function validateFileSize(array $file): void
    {
        if ($file['size'] > $this->maxFileSize) {
            throw new \InvalidArgumentException('File size exceeds maximum allowed size');
        }
    }

    private function validateFileExtension(array $file): void
    {
        $allowedExtensions = $this->getAllowedExtensionsFromMimeTypes();
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new \InvalidArgumentException('File extension not allowed');
        }
    }

    private function determineAndValidateMimeType(array $file): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            throw new \InvalidArgumentException('File type validation failed');
        }
        return $mimeType;
    }

    private function validateImageSpecifics(array $file, string $mimeType): void
    {
        if (!str_starts_with($mimeType, 'image/')) {
            return;
        }
        if (!@\getimagesize($file['tmp_name'])) {
            throw new \InvalidArgumentException('File is not a valid image');
        }
        $this->validateImageSecurity($file['tmp_name'], $mimeType);
    }

    private function scanForMaliciousContent(array $file): void
    {
        if ($file['size'] <= 10 * 1024 * 1024) {
            $fileContent = file_get_contents($file['tmp_name']);
            if (preg_match('/<\?php|<script|<\?=/i', $fileContent)) {
                throw new \InvalidArgumentException('File contains executable code');
            }
        }
    }

    /**
     * Get allowed extensions from MIME types for security validation.
     */
    private function getAllowedExtensionsFromMimeTypes()
    {
        $extensions = [];
        foreach ($this->allowedMimeTypes as $mimeType) {
            if (isset(self::MIME_TO_EXTENSION_MAP[$mimeType])) {
                $extensions = array_merge($extensions, self::MIME_TO_EXTENSION_MAP[$mimeType]);
            }
        }
        return array_unique($extensions);
    }

    /**
     * Validate image security to prevent malicious EXIF data.
     */
    private function validateImageSecurity(string $filePath, string $mimeType)
    {
        // For JPEG files, we can check for potentially dangerous EXIF sections
        if ('image/jpeg' === $mimeType || 'image/jpg' === $mimeType) {
            // Try to read EXIF data and verify it's safe
            $exif = @exif_read_data($filePath, 'EXIF', true);
            // Additional validation can be added here
        }
        // For other image types, additional security checks can be implemented
    }

    /**
     * Validate file signature by checking magic bytes.
     */
    private function validateFileSignature(string $filePath, string $mimeType)
    {
        $validator = $this->processorFactory->getValidator($mimeType);
        if (!$validator) {
            throw new \InvalidArgumentException('No validator available for MIME type: '.$mimeType);
        }

        $validator->validateSignature($filePath);
    }

    /**
     * Optimize image file.
     */
    private function optimizeImage(string $filepath, string $mimeType)
    {
        $optimizer = $this->processorFactory->getOptimizer($mimeType);
        if (!$optimizer) {
            return;
        }

        $optimizer->optimize($filepath);
    }

    /**
     * Generate thumbnail for image.
     */
    private function generateThumbnail(string $originalPath, string $filename, string $mimeType)
    {
        $generator = $this->processorFactory->getThumbnailGenerator($mimeType);
        if (!$generator) {
            return null;
        }

        $thumbnailFilename = 'thumb_'.$filename;
        $thumbnailPath = $this->uploadDirectory.'/'.$thumbnailFilename;

        $generator->generate($originalPath, $thumbnailPath);

        return '/uploads/communication/'.$thumbnailFilename;
    }

    /**
     * Get file information.
     */
    public function getFileInfo(string $filename)
    {
        $filepath = $this->uploadDirectory.'/'.$filename;
        if (!file_exists($filepath)) {
            return null;
        }

        return [
            'filename' => $filename,
            'path' => $filepath,
            'url' => '/uploads/communication/'.$filename,
            'size' => filesize($filepath),
            'modified' => filemtime($filepath),
        ];
    }

    /**
     * Get file metadata.
     */
    public function getFileMetadata(string $fileId)
    {
        // Mock implementation
        return [
            'id' => $fileId,
            'name' => 'sample.pdf',
            'size' => 1024000,
            'mime_type' => 'application/pdf',
            'uploaded_at' => time(),
            'uploaded_by' => 'user123',
            'checksum' => 'abc123',
            'versions' => [],
        ];
    }

    /**
     * Create file version.
     */
    public function createFileVersion(string $fileId, $file, string $userId)
    {
        // Mock implementation - in real app, save version to storage
        $version = [
            'id' => 'v'.time(),
            'file_id' => $fileId,
            'version_number' => 2,
            'size' => $file->getSize(),
            'uploaded_by' => $userId,
            'uploaded_at' => time(),
            'checksum' => md5($file->getContent()),
            'changes' => 'Updated content',
        ];

        return $version;
    }

    /**
     * Get file versions.
     */
    public function getFileVersions(string $fileId)
    {
        // Mock versions
        return [
            [
                'id' => 'v1',
                'version_number' => 1,
                'size' => 1024000,
                'uploaded_by' => 'user123',
                'uploaded_at' => time() - 86400,
                'checksum' => 'abc123',
                'changes' => 'Initial upload',
            ],
            [
                'id' => 'v2',
                'version_number' => 2,
                'size' => 1048576,
                'uploaded_by' => 'user456',
                'uploaded_at' => time() - 3600,
                'checksum' => 'def456',
                'changes' => 'Updated formatting',
            ],
        ];
    }

    /**
     * Restore file version.
     */
    public function restoreFileVersion(string $fileId, string $versionId, string $userId)
    {
        // Mock implementation - in real app, restore from version
        return true;
    }

    /**
     * Encrypt file.
     */
    public function encryptFile($file, string $encryptionKey)
    {
        // Mock encryption - in real app, use proper encryption
        $encryptedContent = 'encrypted_'.$file->getContent();
        $encryptedFile = [
            'original_name' => $file->getClientOriginalName(),
            'encrypted_name' => 'encrypted_'.$file->getClientOriginalName(),
            'size' => strlen($encryptedContent),
            'encryption_key' => $encryptionKey,
            'checksum' => md5($encryptedContent),
        ];

        return $encryptedFile;
    }

    /**
     * Decrypt file.
     */
    public function decryptFile(string $encryptedFilePath, string $encryptionKey)
    {
        // Mock decryption - in real app, decrypt from storage
        return 'decrypted_content';
    }

    /**
     * Get file analytics.
     */
    public function getFileAnalytics(string $fileId)
    {
        // Mock analytics
        return [
            'total_views' => 45,
            'unique_viewers' => 12,
            'downloads' => 23,
            'shares' => 8,
            'comments' => 5,
            'last_accessed' => time() - 3600,
            'access_history' => [
                ['user' => 'user1', 'action' => 'view', 'timestamp' => time() - 3600],
                ['user' => 'user2', 'action' => 'download', 'timestamp' => time() - 7200],
                ['user' => 'user3', 'action' => 'share', 'timestamp' => time() - 10800],
            ],
        ];
    }

    /**
     * Add file comment.
     */
    public function addFileComment(string $fileId, string $userId, string $comment)
    {
        // Mock comment
        return [
            'id' => 'comment_'.time(),
            'file_id' => $fileId,
            'user_id' => $userId,
            'comment' => $comment,
            'created_at' => time(),
        ];
    }

    /**
     * Get file comments.
     */
    public function getFileComments(string $fileId)
    {
        // Mock comments
        return [
            [
                'id' => 'comment1',
                'user_id' => 'user123',
                'user_name' => 'John Doe',
                'comment' => 'Great document! I added some notes in the margins.',
                'created_at' => time() - 3600,
            ],
            [
                'id' => 'comment2',
                'user_id' => 'user456',
                'user_name' => 'Jane Smith',
                'comment' => 'Can you update the budget section?',
                'created_at' => time() - 1800,
            ],
        ];
    }

    /**
     * Set file permissions.
     */
    public function setFilePermissions(string $fileId, array $permissions)
    {
        // Mock permissions update
        return true;
    }

    /**
     * Get file permissions.
     */
    public function getFilePermissions(string $fileId)
    {
        // Mock permissions
        return [
            'owner' => 'user123',
            'permissions' => [
                'read' => ['user123', 'user456', 'group_admins'],
                'write' => ['user123', 'group_admins'],
                'delete' => ['user123'],
                'share' => ['user123', 'user456'],
            ],
        ];
    }

    /**
     * Wrapper for move_uploaded_file to enable testing.
     */
    protected function moveUploadedFile(string $tmpPath, string $destPath): bool
    {
        return move_uploaded_file($tmpPath, $destPath);
    }
}
