<?php

declare(strict_types=1);

namespace App\Call\Service;

use App\Call\Entity\CallRecord;
use App\Call\Repository\CallRepositoryInterface;
use Doctrine\Persistence\ManagerRegistry;

class CallService
{
    public function __construct(
        private CallRepositoryInterface $repository
    ) {}

    public function initiateCall($caller, $receiver, $group = null, string $type = 'audio')
    {
        $callerId = is_object($caller) ? $caller->getId() : $caller;
        $receiverId = $group ? null : (is_object($receiver) ? $receiver->getId() : $receiver);
        if ($group === null && $receiverId === null) {
            throw new \InvalidArgumentException('Either receiver or group must be provided');
        }
        $call = new CallRecord($callerId, $receiverId, $type);
        return $this->repository->save($call);
    }

    public function getCall($id)
    {
        return $this->repository->findById($id);
    }

    public function answerCall($callId)
    {
        $call = $this->repository->findById($callId);
        if ($call && $call->getStatus() === 'initiated') {
            $call->setAnswered();
            return $this->repository->save($call);
        }
        return $call;
    }

    public function endCall($callId)
    {
        $call = $this->repository->findById($callId);
        if ($call) {
            $call->end();
            return $this->repository->save($call);
        }
        return null;
    }

    public function joinCall($call, $user)
    {
        return $call;
    }

    public function acceptCall($call, $user)
    {
        if ($call->getStatus() === 'ringing') { // or appropriate
            $call->setStatus('connected'); // adjust based on entity
            return $this->repository->save($call);
        }
        return $call;
    }

    public function rejectCall($call, $user)
    {
        if ($call->getStatus() === 'ringing') {
            $call->setStatus('missed');
            return $this->repository->save($call);
        }
        return $call;
    }

    public function missCall($callId)
    {
        $call = $this->repository->findById($callId);
        if ($call && $call->getStatus() === 'initiated') {
            $call->markMissed();
            return $this->repository->save($call);
        }
        return $call;
    }

    public function getUserCalls($userId)
    {
        return $this->repository->findByUserId($userId);
    }

    public function getRecentCalls($userId, $limit = 10)
    {
        $userCalls = $this->repository->findByUserId($userId);
        usort($userCalls, fn($a, $b) => $b->getStartedAt() <=> $a->getStartedAt());
        return array_slice($userCalls, 0, $limit);
    }

    public function getMissedCalls($userId)
    {
        return $this->repository->findMissedByUserId($userId);
    }

    public function getUserCallsPaginated($userId, $page, $perPage)
    {
        $limit = $perPage;
        $offset = ($page - 1) * $perPage;
        $data = $this->repository->findByUserIdWithPagination($userId, $limit, $offset);
        $total = $this->repository->countByUserId($userId);
        return [
            'data' => $data,
            'total' => $total,
        ];
    }

    public function linkToConversation($callId, $conversationId)
    {
        $call = $this->repository->findById($callId);
        if ($call) {
            $call->setConversationId($conversationId);
            return $this->repository->save($call);
        }
        return null;
    }

    public function getCallStats($userId)
    {
        $userCalls = $this->repository->findByUserId($userId);
        $total = count($userCalls);
        $answered = count(array_filter($userCalls, fn($c) => $c->getStatus() === 'ended'));
        $missed = count(array_filter($userCalls, fn($c) => $c->getStatus() === 'missed'));
        $totalDuration = array_sum(array_map(fn($c) => $c->getDuration(), $userCalls));

        return [
            'total_calls' => $total,
            'answered' => $answered,
            'missed' => $missed,
            'total_duration_seconds' => $totalDuration,
            'avg_duration_seconds' => $total > 0 ? $totalDuration / $total : 0
        ];
    }

    public function getActiveCalls($user): array
    {
        $userId = is_object($user) ? $user->getId() : $user;
        $calls = $this->repository->findByUserId($userId);
        return array_filter($calls, fn($c) => $c->getStatus() === 'active');
    }

    public function getCallById($id)
    {
        return $this->getCall($id);
    }

    public function getDoctrine()
    {
        return null;
    }

}