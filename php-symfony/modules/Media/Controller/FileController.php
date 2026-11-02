<?php

declare(strict_types=1);

namespace App\Media\Controller;

use App\Shared\Infrastructure\RateLimitService;
use App\Shared\Security\AuthenticationHelperTrait;
use App\Media\Service\FileValidationService;
use App\Media\Service\FileStorageService;
use App\Media\Service\FileSanitizationService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;

class FileController extends BaseController
{
    use AuthenticationHelperTrait;

    private string $chunksDir;

    public function __construct(
        private FileValidationService $validationService,
        private FileStorageService $storageService,
        private FileSanitizationService $sanitizationService,
        private RateLimitService $rateLimitService,
        private HubInterface $hub,
        string $projectDir,
    ) {
        $this->chunksDir = $projectDir.'/var/chunks';
        if (!is_dir($this->chunksDir)) {
            mkdir($this->chunksDir, 0755, true);
        }
    }

    #[Route('/api/files', name: 'api_files_list', methods: ['GET'])]
    public function getFiles(Request $request): Response
    {
        $userId = $this->requireAuth();
        if ($userId instanceof Response) return $userId;

        [$page, $perPage] = $this->parsePagination($request);
        $allFiles = $this->storageService->listFiles();
        $total = count($allFiles);
        $offset = ($page - 1) * $perPage;
        $files = array_slice($allFiles, $offset, $perPage);

        $formattedFiles = array_map(fn($file) => $this->formatFileForApi($file, $userId), $files);

        return $this->json([
            'files' => $formattedFiles,
            'pagination' => $this->buildPagination($page, $perPage, $total),
        ]);
    }

    #[Route('/api/files/upload', name: 'api_files_upload', methods: ['POST'])]
    public function upload(Request $request): Response
    {
        $userId = $this->requireAuth();
        if ($userId instanceof Response) return $userId;

        $result = $this->handleUpload($request, $userId);
        if ($result instanceof Response) return $result;

        return $this->json(['success' => true, 'data' => $result], Response::HTTP_CREATED);
    }

    private function handleUpload(Request $request, string $userId): array|Response
    {
        $validation = $this->validateUploadRequest($request, $userId);
        if ($validation instanceof Response) return $validation;

        $fileInfo = $this->performFileUpload($validation, $userId);
        $this->publishUploadEvent($fileInfo, $userId);

        return $fileInfo;
    }

    private function validateUploadRequest(Request $request, string $userId): array|Response
    {
        if (!$this->rateLimitService->checkRateLimit($userId, 'file_upload')) {
            return $this->json(['error' => 'Upload rate limit exceeded.'], 429);
        }

        $uploadedFile = $request->files->get('file');
        if (!$uploadedFile) {
            return $this->json(['error' => 'No file uploaded'], 400);
        }

        $fileArray = [
            'name' => $uploadedFile->getClientOriginalName(),
            'type' => $uploadedFile->getMimeType(),
            'tmp_name' => $uploadedFile->getPathname(),
            'error' => $uploadedFile->getError(),
            'size' => $uploadedFile->getSize(),
        ];

        if (!$this->validationService->validateMagicNumber($fileArray['tmp_name'], $fileArray['type'])) {
            return $this->json(['error' => 'File content mismatch'], 400);
        }

        return $fileArray;
    }

    private function performFileUpload(array $fileArray, string $userId): array
    {
        $dimensions = null;
        if (str_starts_with($fileArray['type'], 'image/')) {
            try {
                $dimensions = $this->validationService->validateImageDimensions($fileArray['tmp_name']);
            } catch (\InvalidArgumentException $e) {
                throw $e;
            }
        }

        $secureFilename = $this->validationService->generateSecureFilename($fileArray['name']);
        $filePath = $this->storageService->moveUploadedFile($fileArray['tmp_name'], $secureFilename);

        if (str_starts_with($fileArray['type'], 'image/')) {
            $this->sanitizationService->sanitizeImageExif($filePath);
        }

        return [
            'filename' => $secureFilename,
            'original_name' => $fileArray['name'],
            'size' => $fileArray['size'],
            'mime_type' => $fileArray['type'],
            'path' => $filePath,
            'url' => '/uploads/communication/'.$secureFilename,
            'dimensions' => $dimensions,
            'uploaded_by' => $userId,
        ];
    }

    private function publishUploadEvent(array $fileInfo, string $userId): void
    {
        $this->storageService->storeFileInfo($fileInfo);
        $this->logAction('file_uploaded', ['file_id' => md5($fileInfo['filename']), 'filename' => $fileInfo['filename'], 'user_id' => $userId, 'mime_type' => $fileInfo['mime_type'], 'size' => $fileInfo['size']]);
        $this->incrementMetric('files_uploaded');
        $update = new Update('files', json_encode(['type' => 'file_uploaded', 'file' => $fileInfo, 'timestamp' => (new \DateTimeImmutable())->format('c')]));
        $this->hub->publish($update);
    }

    private function logAction(string $action, array $context = []): void
    {
        $logData = [
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601),
            'action' => $action,
            'context' => $context,
        ];
        error_log(json_encode($logData, JSON_UNESCAPED_UNICODE));
    }

    private function incrementMetric(string $metric, array $tags = []): void
    {
        $metricData = [
            'metric' => $metric,
            'tags' => $tags,
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ISO8601),
        ];
        error_log(json_encode($metricData));
    }

    #[Route('/api/files/chunk', name: 'api_files_upload_chunk', methods: ['POST'])]
    public function uploadChunk(Request $request): Response
    {
        $userId = $this->requireAuth();
        if ($userId instanceof Response) return $userId;

        $headers = $this->extractChunkHeaders($request);
        if ($headers instanceof Response) return $headers;
        extract($headers);

        if (!$uploadId || !$fileName) {
            return $this->json(['error' => 'Missing required headers'], 400);
        }

        try {
            $chunkDir = $this->ensureChunkDirectory($uploadId);
            if (!$this->writeChunk($chunkDir, $chunkNumber, $chunkData = $request->getContent(true))) {
                return $this->json(['error' => 'Failed to write chunk'], 500);
            }

            if ($this->shouldFinalize($chunkNumber, $totalChunks, $chunkDir)) {
                $response = $this->executeFinalize($uploadId, $fileName, $fileMimeType, $fileSize, $userId, $chunkDir, $totalChunks);
                if ($response instanceof Response) return $response;
                return $this->json(['success' => true, 'data' => $response], Response::HTTP_CREATED);
            }

            return $this->json(['success' => true, 'chunk' => $chunkNumber, 'total_chunks' => $totalChunks], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->handleException('file_upload_chunk', $e);
        }
    }

    private function extractChunkHeaders(Request $request): array|Response
    {
        $uploadId = $request->headers->get('X-Upload-Id');
        $chunkNumber = (int) $request->headers->get('X-Chunk-Number', '0');
        $totalChunks = (int) $request->headers->get('X-Total-Chunks', '1');
        $fileSize = (int) $request->headers->get('X-File-Size', '0');
        $fileName = $request->headers->get('X-File-Name', '');
        $fileMimeType = $request->headers->get('X-File-Mime-Type', '');

        if (!$uploadId || !$fileName) {
            return new Response('Missing required headers', 400);
        }

        return compact('uploadId', 'chunkNumber', 'totalChunks', 'fileSize', 'fileName', 'fileMimeType');
    }

    private function ensureChunkDirectory(string $uploadId): string
    {
        $chunkDir = $this->getParameter('kernel.project_dir').'/var/chunks/'.$uploadId;
        if (!is_dir($chunkDir)) mkdir($chunkDir, 0755, true);
        return $chunkDir;
    }

    private function writeChunk(string $chunkDir, int $chunkNumber, $chunkData): bool
    {
        $chunkFile = $chunkDir.'/chunk_'.$chunkNumber;
        $bytesWritten = file_put_contents($chunkFile, $chunkData);
        return $bytesWritten !== false;
    }

    private function shouldFinalize(int $chunkNumber, int $totalChunks, string $chunkDir): bool
    {
        return $chunkNumber === $totalChunks - 1 && $this->allChunksExist($chunkDir, $totalChunks);
    }

    private function allChunksExist(string $chunkDir, int $totalChunks): bool
    {
        for ($i = 0; $i < $totalChunks; $i++) {
            if (!file_exists($chunkDir.'/chunk_'.$i)) return false;
        }
        return true;
    }

    private function executeFinalize(string $uploadId, string $fileName, string $fileMimeType, int $fileSize, string $userId, string $chunkDir, int $totalChunks): array|Response
    {
        try {
            $secureFilename = $this->validationService->generateSecureFilename($fileName);
            $finalPath = $this->storageService->assembleFileFromChunks($uploadId, $secureFilename, $totalChunks);
            $this->deleteDirectoryRecursive($chunkDir);

            $fileInfo = [
                'filename' => $secureFilename,
                'original_name' => $fileName,
                'size' => $fileSize,
                'mime_type' => $fileMimeType,
                'path' => $finalPath,
                'url' => '/uploads/communication/'.$secureFilename,
            ];

            $this->storageService->storeFileInfo($fileInfo);
            $this->logAction('file_uploaded', ['file_id' => md5($secureFilename), 'filename' => $secureFilename, 'user_id' => $userId, 'mime_type' => $fileMimeType, 'size' => $fileSize]);
            $this->incrementMetric('files_uploaded');
            $update = new Update('files', json_encode(['type' => 'file_uploaded', 'file' => $fileInfo, 'timestamp' => (new \DateTimeImmutable())->format('c')]));
            $this->hub->publish($update);

            return $fileInfo;
        } catch (\Exception $e) {
            return $this->handleException('file_upload_chunk_finalize', $e);
        }
    }

    #[Route('/api/files/{fileId}', name: 'api_files_delete', methods: ['DELETE'])]
    public function deleteFile(Request $request, string $fileId): Response
    {
        $userId = $this->requireAuth();
        if ($userId instanceof Response) return $userId;

        try {
            $file = $this->storageService->getFileInfo($fileId);
            if (!$file) return $this->json(['error' => 'File not found'], Response::HTTP_NOT_FOUND);
            if ($file['uploaded_by'] !== $userId && !$this->isAdmin()) return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);

            $this->storageService->deleteFile($fileId);
            $this->logAction('file_deleted', ['file_id' => $fileId, 'user_id' => $userId]);
            $this->incrementMetric('files_deleted');
            $update = new Update('files', json_encode(['type' => 'file_deleted', 'file_id' => $fileId, 'timestamp' => (new \DateTimeImmutable())->format('c')]));
            $this->hub->publish($update);

            return $this->json(['success' => true], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->handleException('file_delete', $e);
        }
    }

    // ==================== Private Helpers ====================
    private function parsePagination(Request $request): array
    {
        $page = max(1, (int)($request->query->get('page', 1)));
        $perPage = max(1, min(100, (int)($request->query->get('perPage', 20))));
        return [$page, $perPage];
    }

    private function formatFileForApi(array $file, string $userId): array
    {
        $thumbnailUrl = $this->generateThumbnailUrl($file);
        return [
            'id' => md5($file['filename']),
            'name' => $file['filename'],
            'url' => '/uploads/communication/'.$file['filename'],
            'thumbnail_url' => $thumbnailUrl,
            'size' => $file['size'],
            'mime_type' => $file['mime_type'],
            'uploaded_at' => $file['modified'],
            'uploaded_by' => $userId,
        ];
    }

    private function generateThumbnailUrl(array $file): ?string
    {
        if (!str_starts_with($file['mime_type'], 'image/')) return null;
        $thumbnailFilename = 'thumb_'.$file['filename'];
        $thumbnailPath = $this->getParameter('kernel.project_dir').'/public/uploads/communication/'.$thumbnailFilename;
        return file_exists($thumbnailPath) ? '/uploads/communication/'.$thumbnailFilename : null;
    }

    private function buildPagination(int $page, int $perPage, int $total): array
    {
        return [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => (int)ceil($total / $perPage),
        ];
    }
}
