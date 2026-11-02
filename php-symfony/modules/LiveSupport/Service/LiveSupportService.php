<?php

namespace App\LiveSupport\Service;

use App\LiveSupport\Entity\Conversation;
use App\LiveSupport\Entity\Message;
use App\LiveSupport\Entity\Agent;
use App\LiveSupport\Repository\ConversationRepository;
use App\LiveSupport\Repository\MessageRepository;
use App\LiveSupport\Repository\AgentRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class LiveSupportService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ConversationRepository $conversationRepo,
        private MessageRepository $messageRepo,
        private AgentRepository $agentRepo
    ) {}

    // Conversations

    public function createConversation(string $creatorId, string $topic, array $participants = [])
    {
        if (empty(trim($topic))) {
            throw new InvalidArgumentException('Topic cannot be empty');
        }
        $conversation = new Conversation($topic, $creatorId, $participants);
        $this->em->persist($conversation);
        $this->em->flush();
        return $conversation;
    }

    public function getConversation(int $conversationId)
    {
        return $this->conversationRepo->find($conversationId);
    }

    public function getActiveConversations(?string $agentId = null)
    {
        if ($agentId !== null) {
            return $this->conversationRepo->findBy(['assignedAgentId' => $agentId, 'status' => 'active']);
        }
        return $this->conversationRepo->findActive();
    }

    public function getConversationsForParticipant(string $userId)
    {
        return $this->conversationRepo->findByParticipant($userId);
    }

    public function endConversation(int $conversationId, string $actingUserId)
    {
        $conversation = $this->conversationRepo->find($conversationId);
        if (!$conversation) {
            throw new InvalidArgumentException('Conversation not found');
        }
        // Authorization: only creator or assigned agent can end
        if ($conversation->getCreatorId() !== $actingUserId && $conversation->getAssignedAgentId() != $actingUserId) {
            throw new InvalidArgumentException('Unauthorized to end conversation');
        }
        $conversation->setStatus('ended');
        // Also de-assign agent? Not necessary.
        $this->conversationRepo->save($conversation);
        return $conversation;
    }

    public function assignAgent(int $conversationId, string $actingUserId, int $agentId)
    {
        $conversation = $this->conversationRepo->find($conversationId);
        if (!$conversation) {
            throw new InvalidArgumentException('Conversation not found');
        }
        // Authorization: only creator can assign agent (or maybe system)
        if ($conversation->getCreatorId() !== $actingUserId) {
            throw new InvalidArgumentException('Unauthorized to assign agent');
        }
        // Verify agent exists
        $agent = $this->agentRepo->find($agentId);
        if (!$agent) {
            throw new InvalidArgumentException('Agent not found');
        }
        $conversation->setAssignedAgentId($agentId);
        // Possibly add agent to participants automatically?
        $conversation->addParticipant($agent->getAgentUserId());
        $this->conversationRepo->save($conversation);
        return $conversation;
    }

    public function joinConversation(int $conversationId, string $userId)
    {
        $conversation = $this->conversationRepo->find($conversationId);
        if (!$conversation) {
            throw new InvalidArgumentException('Conversation not found');
        }
        if ($conversation->getStatus() !== 'active') {
            throw new InvalidArgumentException('Cannot join ended conversation');
        }
        // If already participant, ok
        if ($conversation->hasParticipant($userId)) {
            return $conversation;
        }
        // Check if $userId is a registered online agent; if so, allow join.
        $agent = $this->agentRepo->findByAgentUserId($userId);
        if ($agent && $agent->getStatus() === 'online') {
            $conversation->addParticipant($userId);
        } else {
            // For regular users, they can only join if they are the creator? But they are not in participants yet; we could allow creator to re-join but they are already participant. So reject.
            throw new InvalidArgumentException('Unauthorized to join conversation');
        }
        $this->conversationRepo->save($conversation);
        return $conversation;
    }

    public function leaveConversation(int $conversationId, string $userId)
    {
        $conversation = $this->conversationRepo->find($conversationId);
        if (!$conversation) {
            throw new InvalidArgumentException('Conversation not found');
        }
        if (!$conversation->hasParticipant($userId)) {
            throw new InvalidArgumentException('User is not a participant');
        }
        $conversation->removeParticipant($userId);
        $this->em->flush();
        return $conversation;
    }

    // Messages

    public function sendMessage(int $conversationId, string $senderId, string $content)
    {
        $conversation = $this->conversationRepo->find($conversationId);
        if (!$conversation) {
            throw new InvalidArgumentException('Conversation not found');
        }
        if ($conversation->getStatus() !== 'active') {
            throw new InvalidArgumentException('Cannot send message to ended conversation');
        }
        if (!$conversation->hasParticipant($senderId)) {
            throw new InvalidArgumentException('Sender is not a participant in this conversation');
        }
        $content = trim($content);
        if ($content === '') {
            throw new InvalidArgumentException('Message content cannot be empty');
        }
        $message = new Message($conversation, $senderId, $content);
        // Do NOT auto-add to conversation messages collection to avoid cascade persist issues? In Message entity we have ManyToOne with cascade persist? It's not cascade from Message to Conversation; we have cascade persist from Conversation to Message, not vice versa. Here we need to persist Message directly. Also we could add to conversation collection for consistency. Let's do:
        $conversation->addMessage($message);
        // Persist the message (the conversation will cascade if we persist through conversation but we already persist separately? Actually cascade persist is on Conversation->messages, meaning persisting conversation will also persist new messages. But we are not persisting conversation now; we could, but we'll persist message directly.
        $this->em->persist($message);
        $this->em->flush();
        return $message;
    }

    public function getMessages(int $conversationId, int $limit = 100, int $offset = 0, ?\DateTime $since = null)
    {
        $conversation = $this->conversationRepo->find($conversationId);
        if (!$conversation) {
            throw new InvalidArgumentException('Conversation not found');
        }
        // Authorization: the acting user should be participant; but that's handled in controller.
        return $this->messageRepo->findByConversation($conversationId, $limit, $offset, $since);
    }

    public function getMessageCount(int $conversationId)
    {
        return $this->messageRepo->countByConversation($conversationId);
    }

    // Agents

    public function registerAgent(string $agentUserId, string $name, ?array $skills = null)
    {
        $existing = $this->agentRepo->findByAgentUserId($agentUserId);
        if ($existing) {
            // Update name/skills if provided? Return existing
            // Optionally update
            return $existing;
        }
        $agent = new Agent($agentUserId, $name, $skills);
        $this->agentRepo->save($agent);
        return $agent;
    }

    public function getAgentByUserId(string $agentUserId)
    {
        return $this->agentRepo->findByAgentUserId($agentUserId);
    }

    public function getAgentStatus(string $agentUserId)
    {
        $agent = $this->agentRepo->findByAgentUserId($agentUserId);
        if (!$agent) {
            return null;
        }
        return [
            'id' => $agent->getId(),
            'agent_user_id' => $agent->getAgentUserId(),
            'name' => $agent->getName(),
            'skills' => $agent->getSkills(),
            'status' => $agent->getStatus(),
            'current_load' => $agent->getCurrentLoad(),
            'last_heartbeat' => $agent->getLastHeartbeat()?->format(\DateTimeImmutable::ATOM)
        ];
    }

    public function setAgentStatus(string $agentUserId, string $status, ?int $currentLoad = null)
    {
        $agent = $this->agentRepo->findByAgentUserId($agentUserId);
        if (!$agent) {
            throw new InvalidArgumentException('Agent not found');
        }
        $agent->setStatus($status);
        if ($currentLoad !== null) {
            $agent->setCurrentLoad($currentLoad);
        }
        $agent->setLastHeartbeat(new \DateTimeImmutable());
        $this->agentRepo->save($agent);
        return $agent;
    }

    public function listAgents()
    {
        return $this->agentRepo->findAll();
    }
}
