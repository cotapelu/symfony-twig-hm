<?php

namespace App\Media\Service;

use App\Media\Entity\MediaFile;
use App\Media\Entity\MediaFolder;
use App\Media\Repository\MediaFileRepository;
use App\Media\Repository\MediaFolderRepository;

class MediaService
{
    public function __construct(
        private MediaFileRepository $fileRepository,
        private MediaFolderRepository $folderRepository
    ) {
    }

    public function uploadFile(
        int $userId,
        string $filename,
        string $originalName,
        string $mimeType,
        int $size,
        string $storageType,
        string $path
    ) {
        $file = new MediaFile();
        $file->setUserId($userId);
        $file->setFilename($filename);
        $file->setOriginalName($originalName);
        $file->setMimeType($mimeType);
        $file->setSize($size);
        $file->setStorageType($storageType);
        $file->setPath($path);
        $file->setCreatedAt(new \DateTime());
        $file->setUpdatedAt(new \DateTime());

        $this->fileRepository->save($file);

        return $file;
    }

    public function getFile(int $id)
    {
        return $this->fileRepository->find($id);
    }

    public function getUserFiles(int $userId, ?int $folderId = null, int $limit = 50, int $offset = 0)
    {
        return $this->fileRepository->findByUser($userId, $folderId, $limit, $offset);
    }

    public function deleteFile(int $id)
    {
        $file = $this->fileRepository->find($id);
        if (!$file) {
            return false;
        }
        $this->fileRepository->delete($file);
        return true;
    }

    public function createFolder(int $userId, string $name, ?int $parentId = null)
    {
        $folder = new MediaFolder();
        $folder->setUserId($userId);
        $folder->setName($name);
        $folder->setParentId($parentId);
        $folder->setCreatedAt(new \DateTime());
        $this->folderRepository->save($folder);
        return $folder;
    }

    public function getFolder(int $id)
    {
        return $this->folderRepository->find($id);
    }

    public function getUserFolders(int $userId, ?int $parentId = null)
    {
        return $this->folderRepository->findByUser($userId, $parentId);
    }

    public function deleteFolder(int $id)
    {
        $folder = $this->folderRepository->find($id);
        if (!$folder) {
            return false;
        }
        $this->folderRepository->delete($folder);
        return true;
    }

    public function moveFile(int $fileId, ?int $folderId)
    {
        $file = $this->fileRepository->find($fileId);
        if (!$file) {
            return null;
        }
        $file->setFolderId($folderId);
        $file->setUpdatedAt(new \DateTime());
        $this->fileRepository->save($file);
        return $file;
    }
}
