<?php declare(strict_types=1);

namespace Tests\Unit\Poll\Controller;

use App\Poll\Controller\PollController;
use App\Poll\Entity\Poll;
use App\Poll\Entity\PollVote;
use App\Poll\Service\PollService;
use App\User\Entity\User;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use PHPUnit\Framework\TestCase;

class PollControllerTest extends TestCase
{
    private PollService $serviceMock;
    private PollController $controller;
    private TokenStorageInterface $tokenStorageMock;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(PollService::class);
        $this->tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $this->controller = new PollController($this->serviceMock, $this->tokenStorageMock);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function setAuthenticatedUser(int $userId = 1): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $this->tokenStorageMock->method('getToken')->willReturn($token);
    }

    private function createPollMock(
        int $id,
        int $creatorId,
        string $question,
        array $options = [],
        ?string $description = null,
        bool $allowMultiple = false,
        ?\DateTimeImmutable $expiresAt = null,
        string $status = 'active'
    ): Poll {
        $poll = $this->createMock(Poll::class);
        $poll->method('getId')->willReturn($id);
        $poll->method('getCreatorId')->willReturn($creatorId);
        $poll->method('getQuestion')->willReturn($question);
        $poll->method('getOptions')->willReturn($options);
        $poll->method('getDescription')->willReturn($description);
        $poll->method('getAllowMultiple')->willReturn($allowMultiple);
        $poll->method('getExpiresAt')->willReturn($expiresAt);
        $poll->method('getStatus')->willReturn($status);
        return $poll;
    }

    private function createPollVoteMock(int $id, int $pollId, int $userId, array $selectedOptions): PollVote
    {
        $vote = $this->createMock(PollVote::class);
        $vote->method('getId')->willReturn($id);
        $vote->method('getPollId')->willReturn($pollId);
        $vote->method('getUserId')->willReturn($userId);
        $vote->method('getSelectedOptions')->willReturn($selectedOptions);
        return $vote;
    }

    private function buildRequest(array $params = [], array $query = [], array $body = []): array
    {
        return [
            'params' => $params,
            'query' => $query,
            'body' => $body,
        ];
    }

    // ==================== createPoll ====================

    public function testCreatePoll_RequiresAuth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Authentication required');

        $request = $this->buildRequest([], [], ['question' => 'Q', 'options' => ['O']]);
        $this->controller->createPoll($request);
    }

    public function testCreatePoll_Success(): void
    {
        $this->setAuthenticatedUser(1);
        $poll = $this->createPollMock(1, 1, 'Best framework?', [['text' => 'Laravel', 'votes' => 0], ['text' => 'Symfony', 'votes' => 0]], null, false, null, 'active');
        $this->serviceMock->expects($this->once())
            ->method('createPoll')
            ->with(
                $this->equalTo(1),
                $this->equalTo('Best framework?'),
                $this->equalTo(['Laravel', 'Symfony']),
                $this->equalTo(null),
                $this->equalTo(false),
                $this->equalTo(null)
            )
            ->willReturn($poll);

        $request = $this->buildRequest([], [], [
            'question' => 'Best framework?',
            'options' => ['Laravel', 'Symfony'],
        ]);
        $response = $this->controller->createPoll($request);

        $this->assertSame(201, $response['status']);
        $this->assertInstanceOf(Poll::class, $response['data']);
        $this->assertSame(1, $response['data']->getId());
    }

    public function testCreatePoll_Validation_MissingQuestion(): void
    {
        $this->setAuthenticatedUser(1);
        $request = $this->buildRequest([], [], ['options' => ['O']]);
        $response = $this->controller->createPoll($request);
        $this->assertSame(400, $response['status']);
        $this->assertSame('Question is required', $response['error']);
    }

    public function testCreatePoll_Validation_MissingOptions(): void
    {
        $this->setAuthenticatedUser(1);
        $request = $this->buildRequest([], [], ['question' => 'Q']);
        $response = $this->controller->createPoll($request);
        $this->assertSame(400, $response['status']);
        $this->assertSame('At least one option is required', $response['error']);
    }

    public function testCreatePoll_Validation_DescriptionTooLong(): void
    {
        $this->setAuthenticatedUser(1);
        $long = str_repeat('a', 1001);
        $request = $this->buildRequest([], [], [
            'question' => 'Q',
            'options' => ['O'],
            'description' => $long
        ]);
        $response = $this->controller->createPoll($request);
        $this->assertSame(400, $response['status']);
        $this->assertSame('Description too long (max 1000 chars)', $response['error']);
    }

    public function testCreatePoll_Validation_InvalidExpiresAt(): void
    {
        $this->setAuthenticatedUser(1);
        $request = $this->buildRequest([], [], [
            'question' => 'Q',
            'options' => ['O'],
            'expires_at' => 'invalid-date'
        ]);
        $response = $this->controller->createPoll($request);
        $this->assertSame(400, $response['status']);
        $this->assertSame('Invalid expiration date format', $response['error']);
    }

    public function testCreatePoll_Validation_ExpiresAtInPast(): void
    {
        $this->setAuthenticatedUser(1);
        $past = (new \DateTimeImmutable('-1 day'))->format(\DateTimeInterface::ATOM);
        $request = $this->buildRequest([], [], [
            'question' => 'Q',
            'options' => ['O'],
            'expires_at' => $past
        ]);
        $response = $this->controller->createPoll($request);
        $this->assertSame(400, $response['status']);
        $this->assertSame('Expiration date must be in the future', $response['error']);
    }

    // ==================== getPolls ====================

    public function testGetPolls_AllActive(): void
    {
        $poll1 = $this->createPollMock(1, 1, 'Q1', [['text' => 'A', 'votes' => 0]], null, false, null, 'active');
        $poll2 = $this->createPollMock(2, 2, 'Q2', [['text' => 'B', 'votes' => 0]], null, false, null, 'active');
        $this->serviceMock->expects($this->once())
            ->method('getActivePolls')
            ->willReturn([$poll1, $poll2]);

        $request = $this->buildRequest();
        $response = $this->controller->getPolls($request);
        $this->assertSame(200, $response['status']);
        $data = $response['data'];
        $this->assertCount(2, $data);
        $this->assertInstanceOf(Poll::class, $data[0]);
    }

    public function testGetPolls_CreatorFilter_Unauthorized(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Authentication required');

        $request = $this->buildRequest([], ['creator_id' => '999']);
        $this->controller->getPolls($request);
    }

    public function testGetPolls_CreatorFilter_AccessDenied(): void
    {
        $this->setAuthenticatedUser(1);
        $request = $this->buildRequest([], ['creator_id' => '999']);
        $response = $this->controller->getPolls($request);
        $this->assertSame(403, $response['status']);
        $this->assertSame('Access denied', $response['error']);
    }

    public function testGetPolls_CreatorFilter_Success(): void
    {
        $this->setAuthenticatedUser(1);
        $poll = $this->createPollMock(1, 1, 'Q', [['text' => 'A', 'votes' => 0]], null, false, null, 'active');
        $this->serviceMock->expects($this->once())
            ->method('getCreatorPolls')
            ->with($this->equalTo(1))
            ->willReturn([$poll]);

        $request = $this->buildRequest([], ['creator_id' => '1']);
        $response = $this->controller->getPolls($request);
        $this->assertSame(200, $response['status']);
        $this->assertCount(1, $response['data']);
    }

    // ==================== getActivePolls ====================

    public function testGetActivePolls_Success(): void
    {
        $poll = $this->createPollMock(1, 1, 'Q', [['text' => 'A', 'votes' => 0]], null, false, null, 'active');
        $this->serviceMock->expects($this->once())
            ->method('getActivePolls')
            ->willReturn([$poll]);

        $request = $this->buildRequest(['action' => 'getActivePolls']);
        $response = $this->controller->getActivePolls($request);
        $this->assertSame(200, $response['status']);
        $this->assertCount(1, $response['data']);
    }

    // ==================== getPoll ====================

    public function testGetPoll_InvalidId(): void
    {
        $request = $this->buildRequest(['id' => 0]);
        $response = $this->controller->getPoll($request);
        $this->assertSame(400, $response['status']);
        $this->assertSame('Invalid poll ID', $response['error']);
    }

    public function testGetPoll_NotFound(): void
    {
        $this->serviceMock->expects($this->once())
            ->method('getPoll')
            ->with($this->equalTo(123))
            ->willReturn(null);

        $request = $this->buildRequest(['id' => 123]);
        $response = $this->controller->getPoll($request);
        $this->assertSame(404, $response['status']);
        $this->assertSame('Poll not found', $response['error']);
    }

    public function testGetPoll_Success(): void
    {
        $poll = $this->createPollMock(1, 1, 'Q', [['text' => 'A', 'votes' => 0]], 'Desc', false, null, 'active');
        $this->serviceMock->expects($this->once())
            ->method('getPoll')
            ->with($this->equalTo(1))
            ->willReturn($poll);

        $request = $this->buildRequest(['id' => 1]);
        $response = $this->controller->getPoll($request);
        $this->assertSame(200, $response['status']);
        $data = $response['data'];
        $this->assertInstanceOf(Poll::class, $data);
        $this->assertSame(1, $data->getId());
        $this->assertSame('Q', $data->getQuestion());
    }

    // ==================== getResults ====================

    public function testGetResults_InvalidId(): void
    {
        $request = $this->buildRequest(['id' => 0]);
        $response = $this->controller->getResults($request);
        $this->assertSame(400, $response['status']);
        $this->assertSame('Invalid poll ID', $response['error']);
    }

    public function testGetResults_NotFound(): void
    {
        $this->serviceMock->expects($this->once())
            ->method('getPollResults')
            ->with($this->equalTo(123))
            ->willReturn(null);

        $request = $this->buildRequest(['id' => 123]);
        $response = $this->controller->getResults($request);
        $this->assertSame(404, $response['status']);
        $this->assertSame('Poll not found', $response['error']);
    }

    public function testGetResults_Success(): void
    {
        $results = [
            'question' => 'Q?',
            'options' => [
                ['text' => 'A', 'votes' => 5],
                ['text' => 'B', 'votes' => 3],
            ],
            'total_votes' => 8,
        ];
        $this->serviceMock->expects($this->once())
            ->method('getPollResults')
            ->with($this->equalTo(1))
            ->willReturn($results);

        $request = $this->buildRequest(['id' => 1]);
        $response = $this->controller->getResults($request);
        $this->assertSame(200, $response['status']);
        $data = $response['data'];
        $this->assertSame('Q?', $data['question']);
        $this->assertCount(2, $data['options']);
        $this->assertSame(8, $data['total_votes']);
    }

    // ==================== hasVoted ====================

    public function testHasVoted_RequiresAuth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Authentication required');

        $request = $this->buildRequest(['id' => 1]);
        $this->controller->hasVoted($request);
    }

    public function testHasVoted_SuccessFalse(): void
    {
        $this->setAuthenticatedUser(1);
        $this->serviceMock->expects($this->once())
            ->method('hasVoted')
            ->with($this->equalTo(1), $this->equalTo(1))
            ->willReturn(false);

        $request = $this->buildRequest(['id' => 1]);
        $response = $this->controller->hasVoted($request);
        $this->assertSame(200, $response['status']);
        $data = $response['data'];
        $this->assertFalse($data['has_voted']);
    }

    public function testHasVoted_SuccessTrue(): void
    {
        $this->setAuthenticatedUser(1);
        $this->serviceMock->expects($this->once())
            ->method('hasVoted')
            ->with($this->equalTo(1), $this->equalTo(1))
            ->willReturn(true);

        $request = $this->buildRequest(['id' => 1]);
        $response = $this->controller->hasVoted($request);
        $this->assertSame(200, $response['status']);
        $data = $response['data'];
        $this->assertTrue($data['has_voted']);
    }

    // ==================== updatePoll ====================

    public function testUpdatePoll_RequiresAuth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Authentication required');

        $request = $this->buildRequest(['id' => 1], [], ['question' => 'New Q']);
        $this->controller->updatePoll($request);
    }

    public function testUpdatePoll_InvalidId(): void
    {
        $this->setAuthenticatedUser(1);
        $request = $this->buildRequest(['id' => 0], [], ['question' => 'New Q']);
        $response = $this->controller->updatePoll($request);
        $this->assertSame(400, $response['status']);
        $this->assertSame('Invalid poll ID', $response['error']);
    }

    public function testUpdatePoll_NotFound(): void
    {
        $this->setAuthenticatedUser(1);
        $this->serviceMock->expects($this->once())
            ->method('updatePoll')
            ->with($this->equalTo(1), $this->anything(), $this->equalTo(1))
            ->willReturn(null);

        $request = $this->buildRequest(['id' => 1], [], ['question' => 'New Q']);
        $response = $this->controller->updatePoll($request);
        $this->assertSame(404, $response['status']);
        $this->assertSame('Poll not found', $response['error']);
    }

    public function testUpdatePoll_Validation_DescriptionTooLong(): void
    {
        $this->setAuthenticatedUser(1);
        $long = str_repeat('a', 1001);
        $request = $this->buildRequest(['id' => 1], [], [
            'description' => $long
        ]);
        $response = $this->controller->updatePoll($request);
        $this->assertSame(400, $response['status']);
        $this->assertSame('Description too long (max 1000 chars)', $response['error']);
    }

    public function testUpdatePoll_Validation_TooManyOptions(): void
    {
        $this->setAuthenticatedUser(1);
        $many = range(1, 21); // 21 options
        $request = $this->buildRequest(['id' => 1], [], [
            'options' => $many
        ]);
        $response = $this->controller->updatePoll($request);
        $this->assertSame(400, $response['status']);
        $this->assertSame('Too many options (max 20)', $response['error']);
    }

    public function testUpdatePoll_Success(): void
    {
        $this->setAuthenticatedUser(1);
        $poll = $this->createPollMock(1, 1, 'Updated Q', [['text' => 'A', 'votes' => 0]], 'New desc', false, null, 'active');
        $this->serviceMock->expects($this->once())
            ->method('updatePoll')
            ->with(
                $this->equalTo(1),
                $this->arrayHasKey('question'),
                $this->equalTo(1)
            )
            ->willReturn($poll);

        $request = $this->buildRequest(['id' => 1], [], [
            'question' => 'Updated Q',
            'description' => 'New desc'
        ]);
        $response = $this->controller->updatePoll($request);
        $this->assertSame(200, $response['status']);
        $this->assertInstanceOf(Poll::class, $response['data']);
    }

    // ==================== deletePoll ====================

    public function testDeletePoll_RequiresAuth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Authentication required');

        $request = $this->buildRequest(['id' => 1]);
        $this->controller->deletePoll($request);
    }

    public function testDeletePoll_InvalidId(): void
    {
        $this->setAuthenticatedUser(1);
        $request = $this->buildRequest(['id' => 0]);
        $response = $this->controller->deletePoll($request);
        $this->assertSame(400, $response['status']);
        $this->assertSame('Invalid poll ID', $response['error']);
    }

    public function testDeletePoll_NotFound(): void
    {
        $this->setAuthenticatedUser(1);
        $this->serviceMock->expects($this->once())
            ->method('deletePoll')
            ->with($this->equalTo(1), $this->equalTo(1))
            ->willReturn(false);

        $request = $this->buildRequest(['id' => 1]);
        $response = $this->controller->deletePoll($request);
        $this->assertSame(404, $response['status']);
        $this->assertSame('Poll not found', $response['error']);
    }

    public function testDeletePoll_Success(): void
    {
        $this->setAuthenticatedUser(1);
        $this->serviceMock->expects($this->once())
            ->method('deletePoll')
            ->with($this->equalTo(1), $this->equalTo(1))
            ->willReturn(true);

        $request = $this->buildRequest(['id' => 1]);
        $response = $this->controller->deletePoll($request);
        $this->assertSame(204, $response['status']);
        $this->assertNull($response['data']);
    }

    // ==================== vote ====================

    public function testVote_RequiresAuth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Authentication required');

        $request = $this->buildRequest(['id' => 1], [], ['options' => [0]]);
        $this->controller->vote($request);
    }

    public function testVote_InvalidPollId(): void
    {
        $this->setAuthenticatedUser(1);
        $request = $this->buildRequest(['id' => 0], [], ['options' => [0]]);
        $response = $this->controller->vote($request);
        $this->assertSame(400, $response['status']);
        $this->assertSame('Invalid poll ID', $response['error']);
    }

    public function testVote_PollNotFound(): void
    {
        $this->setAuthenticatedUser(1);
        $this->serviceMock->expects($this->once())
            ->method('getPoll')
            ->with($this->equalTo(1))
            ->willReturn(null);

        $request = $this->buildRequest(['id' => 1], [], ['options' => [0]]);
        $response = $this->controller->vote($request);
        $this->assertSame(404, $response['status']);
        $this->assertSame('Poll not found', $response['error']);
    }

    public function testVote_Validation_MissingOptions(): void
    {
        $this->setAuthenticatedUser(1);
        $poll = $this->createPollMock(1, 1, 'Q', [['text' => 'A', 'votes' => 0]]);
        $this->serviceMock->expects($this->once())
            ->method('getPoll')
            ->with($this->equalTo(1))
            ->willReturn($poll);

        $request = $this->buildRequest(['id' => 1], [], []);
        $response = $this->controller->vote($request);
        $this->assertSame(400, $response['status']);
        $this->assertSame('At least one option is required', $response['error']);
    }

    public function testVote_Validation_OptionsNotArray(): void
    {
        $this->setAuthenticatedUser(1);
        $poll = $this->createPollMock(1, 1, 'Q', [['text' => 'A', 'votes' => 0]]);
        $this->serviceMock->method('getPoll')->willReturn($poll);

        $request = $this->buildRequest(['id' => 1], [], ['options' => '0']);
        $response = $this->controller->vote($request);
        $this->assertSame(400, $response['status']);
        $this->assertSame('At least one option is required', $response['error']);
    }

    public function testVote_Validation_OptionOutOfRange(): void
    {
        $this->setAuthenticatedUser(1);
        $poll = $this->createPollMock(1, 1, 'Q', [['text' => 'A', 'votes' => 0], ['text' => 'B', 'votes' => 0]]);
        $this->serviceMock->method('getPoll')->willReturn($poll);

        $request = $this->buildRequest(['id' => 1], [], ['options' => [2]]);
        $response = $this->controller->vote($request);
        $this->assertSame(400, $response['status']);
        $this->assertSame('Invalid option index', $response['error']);
    }

    public function testVote_Success(): void
    {
        $this->setAuthenticatedUser(1);
        $poll = $this->createPollMock(1, 1, 'Q', [['text' => 'A', 'votes' => 0]], null, true);
        $vote = $this->createPollVoteMock(1, 1, 1, [0]);
        $this->serviceMock->expects($this->once())
            ->method('getPoll')
            ->with($this->equalTo(1))
            ->willReturn($poll);
        $this->serviceMock->expects($this->once())
            ->method('vote')
            ->with($this->equalTo(1), $this->equalTo(1), $this->equalTo([0]))
            ->willReturn($vote);

        $request = $this->buildRequest(['id' => 1], [], ['options' => [0]]);
        $response = $this->controller->vote($request);
        $this->assertSame(201, $response['status']);
        $data = $response['data'];
        $this->assertInstanceOf(PollVote::class, $data);
        $this->assertSame(1, $data->getId());
    }

    public function testVote_Failure(): void
    {
        $this->setAuthenticatedUser(1);
        $poll = $this->createPollMock(1, 1, 'Q', [['text' => 'A', 'votes' => 0]], null, true);
        $this->serviceMock->method('getPoll')->willReturn($poll);
        $this->serviceMock->expects($this->once())
            ->method('vote')
            ->with($this->equalTo(1), $this->equalTo(1), $this->equalTo([0]))
            ->willReturn(false);

        $request = $this->buildRequest(['id' => 1], [], ['options' => [0]]);
        $response = $this->controller->vote($request);
        $this->assertSame(400, $response['status']);
        $this->assertSame('Cannot vote on this poll', $response['error']);
    }
}
