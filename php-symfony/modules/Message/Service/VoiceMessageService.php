<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Message\Entity\Message;
use App\Message\Repository\MessageRepositoryInterface;
use App\Shared\Service\UuidService;
use Doctrine\ORM\EntityManagerInterface;

class VoiceMessageService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageRepositoryInterface $messageRepository,
        private UuidService $uuidService
    ) {
    }

    public function sendVoiceMessage(
        string $conversationId,
        string $userId,
        string $audioFileId,
        int $duration,
        ?string $waveformData = null,
        ?string $transcript = null
    ) {
        if (!$this->messageRepository->isUserParticipant($conversationId, $userId)) {
            throw new \Exception('You are not a participant in this conversation');
        }

        $messageId = $this->uuidService->generate();

        $message = new Message(
            id: $messageId,
            conversationId: $conversationId,
            senderId: $userId,
            content: $transcript ?? '',
            type: 'audio'
        );

        $attachments = [
            'audio_file_id' => $audioFileId,
            'duration' => $duration,
            'waveform' => $waveformData,
            'transcript' => $transcript,
        ];

        $message->setAttachments($attachments);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return $message;
    }

    public function generateWaveform(string $audioFilePath, int $samples = 100)
    {
        $duration = $this->getAudioDuration($audioFilePath);

        $waveform = [];
        for ($i = 0; $i < $samples; $i++) {
            $waveform[] = mt_rand(0, 100) / 100;
        }

        return [
            'waveform' => $waveform,
            'samples' => $samples,
            'duration' => $duration,
        ];
    }

    public function transcribeAudio(string $audioFilePath)
    {
        $duration = $this->getAudioDuration($audioFilePath);

        return "Voice message ({$duration} seconds)";
    }

    private function getAudioDuration(string $filePath)
    {
        if (!file_exists($filePath)) {
            return 30;
        }

        $fileSize = filesize($filePath);
        $estimatedDuration = (int) ($fileSize / 8000);

        return max(1, min($estimatedDuration, 300));
    }

    public function canSendVoiceMessage(string $userId, int $maxDuration = 300)
    {
        $todayMessages = $this->getTodayVoiceMessageCount($userId);

        return $todayMessages < 100;
    }

    private function getTodayVoiceMessageCount(string $userId)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(m.id)')
           ->from(Message::class, 'm')
           ->where('m.senderId = :userId')
           ->andWhere('m.type = :type')
           ->andWhere('DATE(m.createdAt) = CURRENT_DATE()')
           ->setParameter('userId', $userId)
           ->setParameter('type', 'audio');

        $result = $qb->getQuery()->getSingleScalarResult();

        return (int) $result;
    }
}
