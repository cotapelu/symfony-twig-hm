<?php

declare(strict_types=1);

namespace App\Realtime\Service;

/**
 * Use case for managing typing indicators.
 */
class TypingIndicatorUseCase
{
    private TypingIndicatorService $typingIndicatorService;

    public function __construct(TypingIndicatorService $typingIndicatorService)
    {
        $this->typingIndicatorService = $typingIndicatorService;
    }

    /**
     * Execute the typing indicator use case.
     */
    public function execute(TypingIndicatorRequest $request)
    {
        if ($request->isTyping) {
            $this->typingIndicatorService->startTyping($request->conversationId, $request->userId);
        } else {
            $this->typingIndicatorService->stopTyping($request->conversationId, $request->userId);
        }

        $typingUsers = $this->typingIndicatorService->getTypingUsers($request->conversationId);

        return new TypingIndicatorResponse($typingUsers);
    }
}

/**
 * Response DTO for TypingIndicatorUseCase.
 */
class TypingIndicatorResponse
{
    public array $typingUsers;

    public function __construct(array $typingUsers)
    {
        $this->typingUsers = $typingUsers;
    }
}
