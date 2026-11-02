<?php

declare(strict_types=1);

namespace App\Media\Service;

/**
 * File storage service for secure file operations.
 * Handles file moving, reading, deletion with path traversal protection.
 */
class FileStorageService
{
    private string $uploadDir;
    private string $chunksBaseDir;

    public function __construct(string $uploadDir, string $projectDir)
    {
        $this->uploadDir = rtrim($uploadDir, '/');
        $this->chunksBaseDir = $projectDir.'/var/chunks';
        $this->ensureSecureDirectory();
    }

    /**
     * Ensure upload directory exists and is secure.
     */
    private function ensureSecureDirectory()
    {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }

        // Create .htaccess to prevent script execution
        $htaccessPath = $this->uploadDir.'/.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, $this->getSecureHtaccessContent());
        }
    }

    /**
     * Get secure .htaccess content.
     */
    private function getSecureHtaccessContent()
    {
        return <<<HTACCESS
# Prevent script execution in upload directory
<FilesMatch "\.(php|php3|php4|php5|phtml|pl|py|cgi|scr|exe|bat|cmd|jar)$">
    Order Deny,Allow
    Deny from all
</FilesMatch>

# Prevent access to sensitive files
<FilesMatch "(^\.|#.*#$|\.bak$|\.old$|\.orig$|\.tmp$|\.temp$)">
    Order Deny,Allow
    Deny from all
</FilesMatch>

# Allow image files only for direct access
<FilesMatch "\.(jpg|jpeg|png|gif|webp)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>

# Force download for other files
<FilesMatch "\.(pdf|txt|doc|docx)$">
    Header set Content-Disposition attachment
</FilesMatch>
HTACCESS;
    }

    /**
     * Securely move uploaded file.
     */
    public function moveUploadedFile(string $sourcePath, string $targetFilename)
    {
        // Generate full path using only filename (no directory component allowed)
        $targetPath = $this->uploadDir.'/'.$targetFilename;

        // Ensure target path is within upload directory (prevent path traversal)
        $realUploadDir = realpath($this->uploadDir);
        if (false === $realUploadDir) {
            throw new \RuntimeException('Upload directory does not exist');
        }

        // Check that target filename contains no directory separators
        if (false !== strpos($targetFilename, '/') || false !== strpos($targetFilename, '\\')) {
            throw new \InvalidArgumentException('Target filename cannot contain directory separators');
        }

        // Move file securely
        if (!move_uploaded_file($sourcePath, $targetPath)) {
            throw new \RuntimeException('Failed to move uploaded file');
        }

        // Verify file was moved and has correct permissions
        if (!file_exists($targetPath)) {
            throw new \RuntimeException('File move verification failed');
        }

        // Verify file is actually within upload directory after move
        $realTargetPath = realpath($targetPath);
        if (false === $realTargetPath || 0 !== strpos($realTargetPath, $realUploadDir)) {
            unlink($targetPath);
            throw new \RuntimeException('File was not placed in secure location');
        }

        // Set secure permissions (readable by owner and web server)
        chmod($targetPath, 0644);

        return $targetPath;
    }

    /**
     * Securely delete file.
     */
    public function deleteFile(string $filename)
    {
        $filePath = $this->uploadDir.'/'.$filename;

        // Ensure upload directory exists
        $realUploadDir = realpath($this->uploadDir);
        if (false === $realUploadDir) {
            throw new \RuntimeException('Upload directory does not exist');
        }

        // Verify file is within upload directory
        $realFilePath = realpath($filePath);
        if (false === $realFilePath || 0 !== strpos($realFilePath, $realUploadDir)) {
            throw new \InvalidArgumentException('Invalid file path');
        }

        // Delete file
        return unlink($filePath);
    }

    /**
     * Securely read file.
     */
    public function readFile(string $filename)
    {
        $filePath = $this->uploadDir.'/'.$filename;

        // Ensure upload directory exists
        $realUploadDir = realpath($this->uploadDir);
        if (false === $realUploadDir) {
            throw new \RuntimeException('Upload directory does not exist');
        }

        // Verify file is within upload directory
        $realFilePath = realpath($filePath);
        if (false === $realFilePath || 0 !== strpos($realFilePath, $realUploadDir)) {
            throw new \InvalidArgumentException('Invalid file path');
        }

        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException('File not found');
        }

        return file_get_contents($filePath);
    }

    /**
     * Get file information securely.
     */
    public function getFileInfo(string $filename)
    {
        $filePath = $this->uploadDir.'/'.$filename;

        // Ensure upload directory exists
        $realUploadDir = realpath($this->uploadDir);
        if (false === $realUploadDir) {
            throw new \RuntimeException('Upload directory does not exist');
        }

        // Verify file is within upload directory
        $realFilePath = realpath($filePath);
        if (false === $realFilePath || 0 !== strpos($realFilePath, $realUploadDir)) {
            throw new \InvalidArgumentException('Invalid file path');
        }

        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException('File not found');
        }

        $stat = stat($filePath);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        return [
            'filename' => $filename,
            'path' => $filePath,
            'size' => $stat['size'],
            'mime_type' => $mimeType,
            'modified' => $stat['mtime'],
            'extension' => strtolower(pathinfo($filename, PATHINFO_EXTENSION)),
        ];
    }

    /**
     * Assemble file from chunks.
     */
    public function assembleFileFromChunks(string $uploadId, string $targetFilename, int $totalChunks)
    {
        $chunkDir = $this->chunksBaseDir.'/'.$uploadId;

        $targetPath = $this->uploadDir.'/'.$targetFilename;

        $finalFile = fopen($targetPath, 'wb');
        if ($finalFile === false) {
            throw new \RuntimeException('Failed to create final file');
        }

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $chunkDir.'/chunk_'.$i;
            if (!file_exists($chunkPath)) {
                fclose($finalFile);
                if (file_exists($targetPath)) {
                    unlink($targetPath);
                }
                throw new \RuntimeException("Chunk {$i} not found");
            }

            $chunkFileHandle = fopen($chunkPath, 'rb');
            if ($chunkFileHandle === false) {
                fclose($finalFile);
                if (file_exists($targetPath)) {
                    unlink($targetPath);
                }
                throw new \RuntimeException("Failed to open chunk {$i}");
            }

            stream_copy_to_stream($chunkFileHandle, $finalFile);
            fclose($chunkFileHandle);
        }

        fclose($finalFile);

        $realUploadDir = realpath($this->uploadDir);
        $realTargetPath = realpath($targetPath);
        if (false === $realTargetPath || 0 !== strpos($realTargetPath, $realUploadDir)) {
            unlink($targetPath);
            throw new \RuntimeException('File was not placed in secure location');
        }

        chmod($targetPath, 0644);

        return $targetPath;
    }

    /**
     * List files securely.
     */
    public function listFiles(?string $prefix = null, ?int $limit = null, int $offset = 0)
    {
        $realUploadDir = realpath($this->uploadDir);
        if (false === $realUploadDir) {
            throw new \RuntimeException('Upload directory does not exist');
        }

        $files = [];
        $directory = new \DirectoryIterator($realUploadDir);

        foreach ($directory as $fileInfo) {
            if ($fileInfo->isDot() || $fileInfo->isDir()) {
                continue;
            }

            $filename = $fileInfo->getFilename();
            if (!$this->shouldIncludeFile($filename, $prefix)) {
                continue;
            }

            try {
                $files[] = $this->getFileInfo($filename);
            } catch (\Exception $e) {
                continue;
            }
        }

        usort($files, fn ($a, $b) => $b['modified'] <=> $a['modified']);
        return $files;
    }

    private function shouldIncludeFile(string $filename, ?string $prefix): bool
    {
        if (0 === strpos($filename, '.')) {
            return false;
        }
        if (false !== strpos($filename, '/') || false !== strpos($filename, '\\')) {
            return false;
        }
        if (null !== $prefix && 0 !== strpos($filename, $prefix)) {
            return false;
        }
        return true;
    }

    public function storeFileInfo(array $fileInfo): void
    {
        // Placeholder: could persist file metadata to database or cache.
        // Currently no-op to satisfy interface.
    }
}
