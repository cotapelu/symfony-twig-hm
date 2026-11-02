<?php

declare(strict_types=1);

namespace App\Conversation\Service;

use App\Conversation\Entity\Conversation;
use App\Conversation\Repository\ConversationRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class CreateGroupUseCase
{
    public function __construct(
        private ConversationRepositoryInterface $conversationRepository,
        
        private HubInterface $hub,
    ) {
    }

    public function execute(CreateGroupRequest $request)
    {
        // Validate title
        if (empty(trim($request->title))) {
            throw new \InvalidArgumentException('Group title cannot be empty');
        }

        // Create conversation entity for group
        $conversationId = uniqid('conv_', true);
        $conversation = new Conversation(
            $conversationId,
            trim($request->title),
            'group',
            $request->userId
        );
        $conversation->setIsActive(true);
        $conversation->setIsArchived(false);
        $conversation->setIsPublic(false);

        // Add creator as first participant
        $conversation->addParticipant($request->userId, 'admin');

        // Save
        $this->conversationRepository->save($conversation);

        // Publish real-time group creation update
        $update = new Update(
            'groups',
            json_encode([
                'type' => 'group_created',
                'group' => [
                    'id' => $conversation->getId(),
                    'title' => $conversation->getTitle(),
                    'participants' => $conversation->getParticipantIds(),
                    'created_at' => $conversation->getCreatedAt()->format('Y-m-d H:i:s'),
                ],
                'timestamp' => time(),
            ])
        );
        $this->hub->publish($update);

        return new CreateGroupResponse($conversation);
    }
}

class CreateGroupRequest
{
    public function __construct(
        public string $title,
        public string $userId,
        public ?string $description = null,
    ) {
    }
}

class CreateGroupResponse
{
    public function __construct(
        public Conversation $conversation,
    ) {
    }
}
