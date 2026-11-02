<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Repository\ThreadRepositoryInterface;

/**
 * Use case for retrieving thread messages.
 */
class GetThreadUseCase
{
    private ThreadRepositoryInterface $threadRepository;

    public function __construct(ThreadRepositoryInterface $threadRepository)
    {
        $this->threadRepository = $threadRepository;
    }

    /**
     * Execute the get thread use case.
     */
    public function execute(GetThreadRequest $request)
    {
        $messages = $this->threadRepository->getThreadMessages($request->threadId);
        $rootMessage = $this->threadRepository->getThreadRoot($request->threadId);

        if (!$rootMessage) {
            throw new \InvalidArgumentException('Thread not found');
        }

        return new GetThreadResponse((string) $request->threadId, $rootMessage, $messages);
    }
}

/**
 * Response DTO for GetThreadUseCase.
 */
class GetThreadResponse
{
    public string $threadId;
    public \App\Entity\Message $rootMessage;
    public array $messages;

    public function __construct(string $threadId, \App\Entity\Message $rootMessage, array $messages)
    {
        $this->threadId = $threadId;
        $this->rootMessage = $rootMessage;
        $this->messages = $messages;
    }
}
