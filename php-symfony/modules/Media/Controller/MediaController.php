<?php

declare(strict_types=1);

namespace App\Media\Controller;

use App\Media\Service\MediaService;
use App\Media\Entity\MediaFile;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use InvalidArgumentException;
use DateTimeImmutable;

#[Route('/api/media')]
class MediaController extends BaseController
{
    private MediaService $service;

    public function __construct(MediaService $service)
    {
        $this->service = $service;
    }

    private function requireAuth(): string
    {
        $user = $this->getUser();
        if (!$user) throw new InvalidArgumentException('Authentication required');
        return (string)$user->getUserIdentifier();
    }

    private function sanitizeString(string $value): string
    {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }

    private function validateOwnership(int $fileId, string $userId): \App\Media\Entity\MediaFile
    {
        $uid = (int)$userId;
        $file = $this->service->getFile($fileId);
        if (!$file) throw new NotFoundHttpException('File not found');
        if ($file->getUserId() !== $uid) throw new AccessDeniedHttpException('Access denied to this file');
        return $file;
    }

    private function getJsonData(Request $request): array
    {
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) throw new InvalidArgumentException('Invalid JSON');
        return $dataOr400 ?? [];
    }

    private function validateUploadData(array $data): void
    {
        if (empty($data['filename']) || empty($data['original_name']) || empty($data['mime_type'])) {
            throw new InvalidArgumentException('Filename, original_name, and mime_type are required');
        }
        if (($data['size'] ?? 0) <= 0) throw new InvalidArgumentException('File size must be positive');
        if (strlen($data['filename']) > 255) throw new InvalidArgumentException('Filename too long (max 255)');
    }

    private function handle(callable $action): JsonResponse
    {
        try { return $action(); }
        catch (InvalidArgumentException $e) { return $this->json(['status'=>400, 'error'=>$e->getMessage()], 400); }
        catch (NotFoundHttpException $e) { return $this->json(['status'=>404, 'error'=>$e->getMessage()], 404); }
        catch (AccessDeniedHttpException $e) { return $this->json(['status'=>403, 'error'=>$e->getMessage()], 403); }
        catch (\Exception $e) { return $this->handleException('controller', $e); }
    }

    private function handleException(string $ctx, \Exception $e): JsonResponse
    {
        error_log("MediaController::{$ctx} error: ".$e->getMessage());
        $this->logAction("{$ctx}_error", ['error'=>$e->getMessage()]);
        $this->incrementMetric("{$ctx}_errors");
        return $this->json(['status'=>500, 'error'=>'Internal server error'], 500);
    }

    private function logAction(string $action, array $context = []): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'action'=>$action, 'userId'=>$this->getUser()?->getUserIdentifier() ?? 'anonymous', 'module'=>'Media', 'context'=>$context]));
    }

    private function incrementMetric(string $metric, array $tags = []): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'type'=>'metric', 'name'=>$metric, 'tags'=>$tags, 'value'=>1]));
    }

    // ==================== Endpoints ====================

    #[Route('/upload', name: 'media_upload_file', methods: ['POST'])]
    public function uploadFile(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->uploadFileAction($request));
    }

    private function uploadFileAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuth();
        $data = $this->getJsonData($request);
        $this->validateUploadData($data);

        $filename = $this->sanitizeString($data['filename']);
        $originalName = $this->sanitizeString($data['original_name']);
        $mimeType = $data['mime_type'];
        $size = (int)$data['size'];
        $storageType = $data['storage_type'] ?? 'local';
        $path = $data['path'] ?? '';

        $file = $this->service->uploadFile((int)$userId, $filename, $originalName, $mimeType, $size, $storageType, $path);

        $this->logAction('file_uploaded', ['file_id'=>$file->getId(), 'user_id'=>$userId, 'size'=>$size]);
        $this->incrementMetric('media_uploads_total');

        return $this->json(['status'=>201, 'data'=>$file->toArray()], 201);
    }

    #[Route('/files', name: 'media_get_user_files', methods: ['GET'])]
    public function getUserFiles(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getUserFilesAction($request));
    }

    private function getUserFilesAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuth();
        $folderId = $request->query->get('folder_id') !== null ? (int)$request->query->get('folder_id') : null;
        $files = $this->service->getUserFiles((int)$userId, $folderId);
        $this->logAction('user_files_retrieved', ['user_id'=>$userId, 'folder_id'=>$folderId, 'count'=>count($files)]);
        $this->incrementMetric('media_files_listed_total');
        return $this->json(['status'=>200, 'data'=>array_map(fn($f)=>$f->toArray(), $files)]);
    }

    #[Route('/files/{id}', name: 'media_get_file', methods: ['GET'])]
    public function getFile(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->getFileAction($id));
    }

    private function getFileAction(int $id): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('File ID must be positive');
        $userId = $this->requireAuth();
        $file = $this->validateOwnership($id, $userId);
        $this->logAction('file_viewed', ['file_id'=>$id, 'user_id'=>$userId]);
        $this->incrementMetric('media_file_viewed_total');
        return $this->json(['status'=>200, 'data'=>$file->toArray()]);
    }

    #[Route('/files/{id}', name: 'media_delete_file', methods: ['DELETE'])]
    public function deleteFile(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->deleteFileAction($id));
    }

    private function deleteFileAction(int $id): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('File ID must be positive');
        $userId = $this->requireAuth();
        $file = $this->validateOwnership($id, $userId);
        $deleted = $this->service->deleteFile($id);
        if (!$deleted) throw new NotFoundHttpException('File not found');
        $this->logAction('file_deleted', ['file_id'=>$id, 'user_id'=>$userId]);
        $this->incrementMetric('media_file_deleted_total');
        return $this->json(['status'=>204, 'data'=>null], 204);
    }

    #[Route('/files/{id}/move', name: 'media_move_file', methods: ['PUT'])]
    public function moveFile(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->moveFileAction($id, $request));
    }

    private function moveFileAction(int $id, Request $request): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('File ID must be positive');
        $userId = $this->requireAuth();
        $file = $this->validateOwnership($id, $userId);
        $data = $this->getJsonData($request);
        $folderId = isset($data['folder_id']) ? (int)$data['folder_id'] : null;
        $movedFile = $this->service->moveFile($id, $folderId);
        if (!$movedFile) throw new NotFoundHttpException('File not found');
        $this->logAction('file_moved', ['file_id'=>$id, 'user_id'=>$userId, 'folder_id'=>$folderId]);
        $this->incrementMetric('media_file_moved_total');
        return $this->json(['status'=>200, 'data'=>$movedFile->toArray()]);
    }

    #[Route('/folders', name: 'media_create_folder', methods: ['POST'])]
    public function createFolder(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createFolderAction($request));
    }

    private function createFolderAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuth();
        $data = $this->getJsonData($request);
        $name = trim($data['name'] ?? '');
        if (empty($name)) throw new InvalidArgumentException('Folder name is required');
        $name = $this->sanitizeString($name);
        $parentId = isset($data['parent_id']) ? (int)$data['parent_id'] : null;
        $folder = $this->service->createFolder((int)$userId, $name, $parentId);
        $this->logAction('folder_created', ['folder_id'=>$folder->getId(), 'user_id'=>$userId, 'parent_id'=>$parentId]);
        $this->incrementMetric('media_folders_created_total');
        return $this->json(['status'=>201, 'data'=>$folder->toArray()], 201);
    }

    #[Route('/folders', name: 'media_get_user_folders', methods: ['GET'])]
    public function getUserFolders(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->getUserFoldersAction($request));
    }

    private function getUserFoldersAction(Request $request): JsonResponse
    {
        $userId = $this->requireAuth();
        $parentId = $request->query->get('parent_id') !== null ? (int)$request->query->get('parent_id') : null;
        $folders = $this->service->getUserFolders((int)$userId, $parentId);
        $this->logAction('user_folders_retrieved', ['user_id'=>$userId, 'parent_id'=>$parentId, 'count'=>count($folders)]);
        $this->incrementMetric('media_folders_listed_total');
        return $this->json(['status'=>200, 'data'=>array_map(fn($f)=>$f->toArray(), $folders)]);
    }

    #[Route('/folders/{id}', name: 'media_get_folder', methods: ['GET'])]
    public function getFolder(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->getFolderAction($id));
    }

    private function getFolderAction(int $id): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Folder ID must be positive');
        $userId = $this->requireAuth();
        $folder = $this->service->getFolder($id);
        if (!$folder) throw new NotFoundHttpException('Folder not found');
        if ($folder->getUserId() !== (int)$userId) throw new AccessDeniedHttpException('Access denied to this folder');
        $this->logAction('folder_viewed', ['folder_id'=>$id, 'user_id'=>$userId]);
        return $this->json(['status'=>200, 'data'=>$folder->toArray()]);
    }

    #[Route('/folders/{id}', name: 'media_delete_folder', methods: ['DELETE'])]
    public function deleteFolder(Request $request, int $id): JsonResponse
    {
        return $this->handle(fn() => $this->deleteFolderAction($id));
    }

    private function deleteFolderAction(int $id): JsonResponse
    {
        if ($id <= 0) throw new InvalidArgumentException('Folder ID must be positive');
        $userId = $this->requireAuth();
        $folder = $this->service->getFolder($id);
        if (!$folder) throw new NotFoundHttpException('Folder not found');
        if ($folder->getUserId() !== (int)$userId) throw new AccessDeniedHttpException('Access denied to this folder');
        $deleted = $this->service->deleteFolder($id);
        if (!$deleted) throw new NotFoundHttpException('Folder not found');
        $this->logAction('folder_deleted', ['folder_id'=>$id, 'user_id'=>$userId]);
        $this->incrementMetric('media_folders_deleted_total');
        return $this->json(['status'=>204, 'data'=>null], 204);
    }
}
