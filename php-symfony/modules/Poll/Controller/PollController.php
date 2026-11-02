<?php

namespace App\Poll\Controller;

use App\Poll\Service\PollService;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Shared\Controller\BaseController;
use InvalidArgumentException;
use DateTimeImmutable;

class PollController extends BaseController
{
    private const MAX_QUESTION_LENGTH = 500;
    private const MAX_OPTION_LENGTH = 255;
    private const MAX_OPTIONS = 20;
    private const MAX_DESCRIPTION_LENGTH = 1000;

    public function __construct(
        private PollService $service,
        private ?TokenStorageInterface $tokenStorage = null
    ) {}

    private function getAuthenticatedUserId(): ?int
    {
        if ($this->tokenStorage === null) return null;
        $token = $this->tokenStorage->getToken();
        if ($token === null) return null;
        $user = $token->getUser();
        if (!$user || !method_exists($user, 'getId')) return null;
        return (int) $user->getId();
    }

    private function requireAuth(): int
    {
        $userId = $this->getAuthenticatedUserId();
        if ($userId === null) throw new InvalidArgumentException('Authentication required');
        return $userId;
    }

    private function getJsonData(array $request): array
    {
        return $request['body'] ?? [];
    }

    private function validateCreatePollInput(array $data): array
    {
        $errors = [];
        $errors = array_merge($errors, $this->validateQuestion($data['question'] ?? null));
        $errors = array_merge($errors, $this->validateOptions($data['options'] ?? null));
        $errors = array_merge($errors, $this->validateDescription($data['description'] ?? null));
        $errors = array_merge($errors, $this->validateExpiresAt($data['expires_at'] ?? null));
        return $errors;
    }

    private function validateUpdatePollInput(array $data): array
    {
        $errors = [];
        if (isset($data['question'])) {
            $errors = array_merge($errors, $this->validateQuestion($data['question']));
        }
        if (isset($data['options'])) {
            $errors = array_merge($errors, $this->validateOptions($data['options']));
        }
        if (isset($data['description'])) {
            $errors = array_merge($errors, $this->validateDescription($data['description']));
        }
        if (isset($data['expires_at'])) {
            $errors = array_merge($errors, $this->validateExpiresAt($data['expires_at']));
        }
        return $errors;
    }

    private function validateVoteInput(array $data, int $optionCount): array
    {
        $errors = [];
        if (empty($data['options']) || !is_array($data['options'])) {
            $errors[] = 'At least one option is required';
            return $errors;
        }
        foreach ($data['options'] as $idx) {
            if (!is_int($idx) || $idx < 0 || $idx >= $optionCount) {
                $errors[] = 'Invalid option index';
                break;
            }
        }
        return $errors;
    }

    private function validateQuestion(?string $question): array
    {
        if ($question === null || trim($question) === '') {
            return ['Question is required'];
        }
        if (strlen($question) > self::MAX_QUESTION_LENGTH) {
            return ['Question too long (max ' . self::MAX_QUESTION_LENGTH . ' chars)'];
        }
        return [];
    }

    private function validateOptions(?array $options): array
    {
        if (empty($options) || !is_array($options)) {
            return ['At least one option is required'];
        }
        if (count($options) > self::MAX_OPTIONS) {
            return ['Too many options (max ' . self::MAX_OPTIONS . ')'];
        }
        return [];
    }

    private function validateDescription(?string $description): array
    {
        if (!empty($description) && strlen($description) > self::MAX_DESCRIPTION_LENGTH) {
            return ['Description too long (max ' . self::MAX_DESCRIPTION_LENGTH . ' chars)'];
        }
        return [];
    }

    private function validateExpiresAt(?string $expiresAt): array
    {
        if (!empty($expiresAt)) {
            try {
                $dt = new DateTimeImmutable($expiresAt);
                if ($dt <= new DateTimeImmutable()) {
                    return ['Expiration date must be in the future'];
                }
            } catch (\Exception) {
                return ['Invalid expiration date format'];
            }
        }
        return [];
    }

    public function registerRoutes($router): void
    {
        $router->post('/polls', [$this, 'createPoll']);
        $router->get('/polls', [$this, 'getPolls']);
        $router->get('/polls/active', [$this, 'getActivePolls']);
        $router->get('/polls/{id}', [$this, 'getPoll']);
        $router->get('/polls/{id}/results', [$this, 'getResults']);
        $router->get('/polls/{id}/has-voted', [$this, 'hasVoted']);
        $router->put('/polls/{id}', [$this, 'updatePoll']);
        $router->delete('/polls/{id}', [$this, 'deletePoll']);
        $router->post('/polls/{id}/vote', [$this, 'vote']);
    }

    public function createPoll($request): array
    {
        $userId = $this->requireAuth();
        $data = $this->getJsonData($request);
        $errors = $this->validateCreatePollInput($data);
        if (!empty($errors)) {
            return ['status' => 400, 'error' => implode(', ', $errors)];
        }
        try {
            $poll = $this->service->createPoll(
                $userId,
                trim($data['question']),
                array_map('trim', $data['options']),
                $data['description'] ?? null,
                $data['allow_multiple'] ?? false,
                $data['expires_at'] ?? null
            );
            return ['status' => 201, 'data' => $poll];
        } catch (\Exception $e) {
            return ['status' => 400, 'error' => $e->getMessage()];
        }
    }

    public function getPolls($request): array
    {
        $creatorId = (int)($request['query']['creator_id'] ?? 0);
        if ($creatorId > 0) {
            $userId = $this->requireAuth();
            if ($userId !== $creatorId) {
                return ['status' => 403, 'error' => 'Access denied'];
            }
            $polls = $this->service->getCreatorPolls($creatorId);
        } else {
            $polls = $this->service->getActivePolls();
        }
        return ['status' => 200, 'data' => array_values($polls)];
    }

    public function getActivePolls($request): array
    {
        $polls = $this->service->getActivePolls();
        return ['status' => 200, 'data' => array_values($polls)];
    }

    public function getPoll($request): array
    {
        $id = (int)($request['params']['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 400, 'error' => 'Invalid poll ID'];
        }
        $poll = $this->service->getPoll($id);
        if (!$poll) return ['status' => 404, 'error' => 'Poll not found'];
        return ['status' => 200, 'data' => $poll];
    }

    public function getResults($request): array
    {
        $id = (int)($request['params']['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 400, 'error' => 'Invalid poll ID'];
        }
        $results = $this->service->getPollResults($id);
        if (!$results) return ['status' => 404, 'error' => 'Poll not found'];
        return ['status' => 200, 'data' => $results];
    }

    public function hasVoted($request): array
    {
        $id = (int)($request['params']['id'] ?? 0);
        $userId = $this->requireAuth();
        $voted = $this->service->hasVoted($id, $userId);
        return ['status' => 200, 'data' => ['has_voted' => $voted]];
    }

    public function updatePoll($request): array
    {
        $userId = $this->requireAuth();
        $id = (int)($request['params']['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 400, 'error' => 'Invalid poll ID'];
        }
        $data = $this->getJsonData($request);
        $errors = $this->validateUpdatePollInput($data);
        if (!empty($errors)) {
            return ['status' => 400, 'error' => implode(', ', $errors)];
        }
        try {
            $poll = $this->service->updatePoll($id, $data, $userId);
            if (!$poll) return ['status' => 404, 'error' => 'Poll not found'];
            return ['status' => 200, 'data' => $poll];
        } catch (\Exception $e) {
            return ['status' => 400, 'error' => $e->getMessage()];
        }
    }

    public function deletePoll($request): array
    {
        $userId = $this->requireAuth();
        $id = (int)($request['params']['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 400, 'error' => 'Invalid poll ID'];
        }
        try {
            $deleted = $this->service->deletePoll($id, $userId);
            if (!$deleted) return ['status' => 404, 'error' => 'Poll not found'];
            return ['status' => 204, 'data' => null];
        } catch (\Exception $e) {
            return ['status' => 400, 'error' => $e->getMessage()];
        }
    }

    public function vote($request): array
    {
        $userId = $this->requireAuth();
        $id = (int)($request['params']['id'] ?? 0);
        if ($id <= 0) {
            return ['status' => 400, 'error' => 'Invalid poll ID'];
        }
        $data = $this->getJsonData($request);
        $poll = $this->service->getPoll($id);
        if (!$poll) {
            return ['status' => 404, 'error' => 'Poll not found'];
        }
        $errors = $this->validateVoteInput($data, count($poll->getOptions()));
        if (!empty($errors)) {
            return ['status' => 400, 'error' => implode(', ', $errors)];
        }
        $vote = $this->service->vote($id, $userId, $data['options']);
        if (!$vote) return ['status' => 400, 'error' => 'Cannot vote on this poll'];
        return ['status' => 201, 'data' => $vote];
    }
}
