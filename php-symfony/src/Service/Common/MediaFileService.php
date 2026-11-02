<?php declare(strict_types=1);

namespace App\Service\Common;

use App\Entity\Common\MediaFile;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for managing media files
 */
class MediaFileService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

    public function createMediaFile(
        string $filename,
        string $mimeType,
        int $size,
        string $url,
        ?string $folderId = null,
        ?string $userId = null,
        ?array $metadata = null
    ) {
        $mediaFile = $this->buildMediaFileEntity($filename, $mimeType, $size, $url, $folderId, $userId, $metadata);
        $this->em->persist($mediaFile);
        $this->em->flush();
        $this->logger->info('Media file created', [
            'file_id' => $mediaFile->getId(),
            'filename' => $filename,
            'mime_type' => $mimeType,
            'size' => $size,
        ]);
        return $mediaFile;
    }

    public function getMediaFile(string $fileId)
    {
        return $this->em->getRepository(MediaFile::class)->find($fileId);
    }

    public function getFilesByFolder(?string $folderId)
    {
        return $this->em->getRepository(MediaFile::class)->findBy(['folderId' => $folderId]);
    }

    public function getUserFiles(string $userId)
    {
        return $this->em->getRepository(MediaFile::class)->findBy(['userId' => $userId]);
    }

    public function getFilesByMimeType(string $mimeType)
    {
        return $this->em->getRepository(MediaFile::class)->findBy(['mimeType' => $mimeType]);
    }

    public function getImages(?string $folderId = null, ?string $userId = null)
    {
        $qb = $this->em->getRepository(MediaFile::class)->createQueryBuilder('f');
        $qb->where('f.mimeType LIKE :imageType')->setParameter('imageType', 'image/%');
        if ($folderId !== null) $qb->andWhere('f.folderId = :folderId')->setParameter('folderId', $folderId);
        if ($userId !== null) $qb->andWhere('f.userId = :userId')->setParameter('userId', $userId);
        return $qb->getQuery()->getResult();
    }

    public function updateMediaFile(
        string $fileId,
        ?string $filename = null,
        ?array $metadata = null
    ) {
        $mediaFile = $this->getMediaFile($fileId);
        if ($mediaFile === null) return null;
        if ($filename !== null) $mediaFile->setFilename($filename);
        if ($metadata !== null) $mediaFile->setMetadata($metadata);
        $this->em->flush();
        $this->logger->info('Media file updated', ['file_id' => $fileId]);
        return $mediaFile;
    }

    public function deleteMediaFile(string $fileId)
    {
        $mediaFile = $this->getMediaFile($fileId);
        if ($mediaFile === null) return false;
        $this->em->remove($mediaFile);
        $this->em->flush();
        $this->logger->info('Media file deleted', ['file_id' => $fileId, 'filename' => $mediaFile->getFilename()]);
        return true;
    }

    public function deleteFolderFiles(string $folderId)
    {
        $files = $this->getFilesByFolder($folderId);
        $count = count($files);
        foreach ($files as $file) $this->em->remove($file);
        $this->em->flush();
        $this->logger->info('Folder files deleted', ['folder_id' => $folderId, 'count' => $count]);
        return $count;
    }

    public function getUserTotalSize(string $userId)
    {
        $qb = $this->em->getRepository(MediaFile::class)->createQueryBuilder('f');
        $qb->select('SUM(f.size)')->where('f.userId = :userId')->setParameter('userId', $userId);
        $result = $qb->getQuery()->getSingleScalarResult();
        return (int) ($result ?? 0);
    }

    public function countUserFiles(string $userId)
    {
        return $this->em->getRepository(MediaFile::class)->count(['userId' => $userId]);
    }

    private function buildMediaFileEntity(
        string $filename,
        string $mimeType,
        int $size,
        string $url,
        ?string $folderId,
        ?string $userId,
        ?array $metadata
    ): MediaFile {
        $mediaFile = new MediaFile(
            $this->generateId(),
            $filename,
            $mimeType,
            $size,
            $url
        );
        $mediaFile->setFolderId($folderId);
        $mediaFile->setUserId($userId);
        $mediaFile->setMetadata($metadata);
        return $mediaFile;
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
