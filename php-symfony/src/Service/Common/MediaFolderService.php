<?php declare(strict_types=1);

namespace App\Service\Common;

use App\Entity\Common\MediaFolder;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for managing media folders
 */
class MediaFolderService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

    public function createFolder(
        string $name,
        ?string $userId = null,
        ?string $parentId = null,
        ?array $metadata = null
    ) {
        if ($parentId !== null && $this->getFolder($parentId) === null) {
            throw new \InvalidArgumentException('Parent folder not found');
        }
        $folder = $this->buildFolderEntity($name, $userId, $parentId, $metadata);
        $this->em->persist($folder);
        $this->em->flush();
        $this->logger->info('Folder created', [
            'folder_id' => $folder->getId(),
            'name' => $name,
            'parent_id' => $parentId,
        ]);
        return $folder;
    }

    public function getFolder(string $folderId)
    {
        return $this->em->getRepository(MediaFolder::class)->find($folderId);
    }

    public function getRootFolders(?string $userId = null)
    {
        $qb = $this->em->getRepository(MediaFolder::class)->createQueryBuilder('f');
        $qb->where('f.parentId IS NULL');
        if ($userId !== null) {
            $qb->andWhere('f.userId = :userId')->setParameter('userId', $userId);
        }
        return $qb->getQuery()->getResult();
    }

    public function getSubfolders(string $parentId)
    {
        return $this->em->getRepository(MediaFolder::class)->findBy(['parentId' => $parentId]);
    }

    public function getUserFolders(string $userId)
    {
        return $this->em->getRepository(MediaFolder::class)->findBy(['userId' => $userId]);
    }

    public function getFolderPath(string $folderId)
    {
        $path = [];
        $currentId = $folderId;
        while ($currentId !== null) {
            $folder = $this->getFolder($currentId);
            if ($folder === null) break;
            array_unshift($path, $folder);
            $currentId = $folder->getParentId();
        }
        return $path;
    }

    public function updateFolder(
        string $folderId,
        ?string $name = null,
        ?string $parentId = null,
        ?array $metadata = null
    ) {
        $folder = $this->getFolder($folderId);
        if ($folder === null) return null;

        if ($name !== null) $folder->setName($name);
        if ($parentId !== null) $this->applyParentIdUpdate($folder, $folderId, $parentId);
        if ($metadata !== null) $folder->setMetadata($metadata);

        $this->em->flush();
        $this->logger->info('Folder updated', ['folder_id' => $folderId]);
        return $folder;
    }

    public function deleteFolder(string $folderId, bool $deleteContents = true)
    {
        $folder = $this->getFolder($folderId);
        if (!$folder) return false;
        if ($deleteContents) {
            $this->deleteSubfolders($folderId);
            $this->deleteFilesInFolder($folderId);
        } else {
            $parentId = $folder->getParentId();
            $this->moveFilesToParent($folderId, $parentId);
            $this->moveSubfoldersToParent($folderId, $parentId);
        }
        $this->em->remove($folder);
        $this->em->flush();
        $this->logger->info('Folder deleted', ['folder_id' => $folderId, 'delete_contents' => $deleteContents]);
        return true;
    }

    public function getFolderDepth(string $folderId)
    {
        return count($this->getFolderPath($folderId)) - 1;
    }

    public function isFolderEmpty(string $folderId)
    {
        $fileCount = $this->em->getRepository(\App\Entity\Common\MediaFile::class)
            ->count(['folderId' => $folderId]);
        $folderCount = $this->em->getRepository(MediaFolder::class)
            ->count(['parentId' => $folderId]);
        return $fileCount === 0 && $folderCount === 0;
    }

    private function buildPath(?string $parentId)
    {
        if ($parentId === null) return '/';
        $parent = $this->getFolder($parentId);
        if ($parent === null) return '/';
        $parentPath = $parent->getPath() ?? '/';
        return rtrim($parentPath, '/') . '/' . $parent->getName();
    }

    private function wouldCreateCycle(string $folderId, string $newParentId)
    {
        $currentId = $newParentId;
        while ($currentId !== null) {
            if ($currentId === $folderId) return true;
            $parent = $this->getFolder($currentId);
            $currentId = $parent?->getParentId();
        }
        return false;
    }

    private function buildFolderEntity(
        string $name,
        ?string $userId,
        ?string $parentId,
        ?array $metadata
    ): MediaFolder {
        $folder = new MediaFolder($this->generateId(), $name);
        $folder->setUserId($userId);
        $folder->setParentId($parentId);
        $folder->setMetadata($metadata);
        $folder->setPath($this->buildPath($parentId));
        return $folder;
    }

    private function deleteSubfolders(string $parentId): void
    {
        foreach ($this->getSubfolders($parentId) as $subfolder) {
            $this->em->getRepository(\App\Entity\Common\MediaFile::class)
                ->createQueryBuilder('f')
                ->delete()
                ->where('f.folderId = :folderId')
                ->setParameter('folderId', $subfolder->getId())
                ->getQuery()
                ->execute();
            $this->deleteSubfolders($subfolder->getId());
            $this->em->remove($subfolder);
        }
    }

    private function deleteFilesInFolder(string $folderId): void
    {
        $this->em->getRepository(\App\Entity\Common\MediaFile::class)
            ->createQueryBuilder('f')
            ->delete()
            ->where('f.folderId = :folderId')
            ->setParameter('folderId', $folderId)
            ->getQuery()
            ->execute();
    }

    private function moveFilesToParent(string $folderId, ?string $parentId): void
    {
        $this->em->getRepository(\App\Entity\Common\MediaFile::class)
            ->createQueryBuilder('f')
            ->update()
            ->set('f.folderId', ':parentId')
            ->where('f.folderId = :folderId')
            ->setParameter('folderId', $folderId)
            ->setParameter('parentId', $parentId)
            ->getQuery()
            ->execute();
    }

    private function moveSubfoldersToParent(string $folderId, ?string $parentId): void
    {
        $this->em->getRepository(MediaFolder::class)
            ->createQueryBuilder('f')
            ->update()
            ->set('f.parentId', ':parentId')
            ->where('f.parentId = :folderId')
            ->setParameter('folderId', $folderId)
            ->setParameter('parentId', $parentId)
            ->getQuery()
            ->execute();
    }

    private function applyParentIdUpdate(MediaFolder $folder, string $folderId, string $newParentId): void
    {
        if ($this->wouldCreateCycle($folderId, $newParentId)) {
            throw new \InvalidArgumentException('Cannot move folder: would create circular reference');
        }
        $folder->setParentId($newParentId);
        $folder->setPath($this->buildPath($newParentId));
    }

    private function generateId()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }
}
