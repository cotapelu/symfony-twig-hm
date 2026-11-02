<?php

declare(strict_types=1);

namespace App\Tests\Controller\Discover;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Discover\Controller\DiscoverController;
use App\Discover\Entity\Action;
use App\Discover\Service\DiscoverService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\User\Entity\User;

#[AllowMockObjectsWithoutExpectations]
class DiscoverControllerTest extends TestCase
{
    private DiscoverController $controller;
    private DiscoverService|MockObject $discoverService;
    private User|MockObject $currentUser;

    protected function setUp(): void
    {
        $this->discoverService = $this->createMock(DiscoverService::class);
        $this->currentUser = $this->createMock(User::class);
        $this->currentUser->method('getId')->willReturn('user123');

        $this->controller = new DiscoverController($this->discoverService);
        $this->setTestUser();
    }

    private function setTestUser(): void
    {
        $this->controller->setTestUser($this->currentUser);
    }

    public function testGetTrendingDefault(): void
    {
        $request = new Request();
        $trendingContent = [
            ['id' => 'content1', 'title' => 'Trending 1'],
            ['id' => 'content2', 'title' => 'Trending 2'],
        ];

        $this->discoverService
            ->expects($this->once())
            ->method('getTrendingContent')
            ->with(20)
            ->willReturn($trendingContent);

        $result = $this->controller->getTrending($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertCount(2, $data);
    }

    public function testGetTrendingWithLimit(): void
    {
        $request = new Request(['limit' => '10']);
        $trendingContent = [['id' => 'content1', 'title' => 'Trending 1']];

        $this->discoverService
            ->expects($this->once())
            ->method('getTrendingContent')
            ->with(10)
            ->willReturn($trendingContent);

        $result = $this->controller->getTrending($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testGetTrendingByType(): void
    {
        $request = new Request(['type' => 'video', 'limit' => '20']);
        $trendingContent = [
            ['id' => 'video1', 'title' => 'Video 1', 'type' => 'video'],
        ];

        $this->discoverService
            ->expects($this->once())
            ->method('getTrendingByType')
            ->with('video', 20)
            ->willReturn($trendingContent);

        $result = $this->controller->getTrending($request);

        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('video', $data[0]['type']);
    }

    public function testGetRecommended(): void
    {
        $request = new Request(['limit' => '15']);
        $recommendedContent = [
            ['id' => 'content1', 'title' => 'Recommended 1'],
            ['id' => 'content2', 'title' => 'Recommended 2'],
        ];

        $this->discoverService
            ->expects($this->once())
            ->method('getRecommendedContent')
            ->with('user123', 15)
            ->willReturn($recommendedContent);

        $result = $this->controller->getRecommended($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertCount(2, $data);
    }

    public function testGetRecommendedDefaultLimit(): void
    {
        $request = new Request();
        $recommendedContent = [];

        $this->discoverService
            ->expects($this->once())
            ->method('getRecommendedContent')
            ->with('user123', 20)
            ->willReturn($recommendedContent);

        $result = $this->controller->getRecommended($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testTrackActionSuccess(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'action_type' => 'like',
                'content_type' => 'post',
                'content_id' => 'post123',
            ])
        );

        $action = $this->createMock(Action::class);
        $action->method('toArray')->willReturn([
            'id' => 'action1',
            'userId' => 'user123',
            'actionType' => 'like',
            'contentType' => 'post',
            'contentId' => 'post123',
        ]);

        $this->discoverService
            ->expects($this->once())
            ->method('trackAction')
            ->with('user123', 'like', 'post', 'post123')
            ->willReturn($action);

        $result = $this->controller->trackAction($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(201, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('user123', $data['userId']);
        $this->assertEquals('like', $data['actionType']);
    }

    public function testTrackActionInvalidJson(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $result = $this->controller->trackAction($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Invalid JSON', $data['error']);
    }

    public function testTrackActionMissingFields(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['action_type' => 'like'])
        );

        $result = $this->controller->trackAction($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('action_type, content_type, and content_id are required', $data['error']);
    }

    public function testTrackViewSuccess(): void
    {
        $this->discoverService
            ->expects($this->once())
            ->method('incrementViewCount')
            ->with('item', 123);

        $result = $this->controller->trackView(123, new Request());

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('View tracked successfully', $data['message']);
    }

    public function testGetUserHistory(): void
    {
        $request = new Request(['limit' => '10']);
        $history = [
            ['contentId' => 'content1', 'viewedAt' => '2024-01-01 10:00:00'],
            ['contentId' => 'content2', 'viewedAt' => '2024-01-01 11:00:00'],
        ];

        $this->discoverService
            ->expects($this->once())
            ->method('getUserDiscoverHistory')
            ->with('user123', 10)
            ->willReturn($history);

        $result = $this->controller->getUserHistory($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertCount(2, $data);
    }

    public function testGetUserHistoryDefaultLimit(): void
    {
        $request = new Request();
        $history = [];

        $this->discoverService
            ->expects($this->once())
            ->method('getUserDiscoverHistory')
            ->with('user123', 20)
            ->willReturn($history);

        $result = $this->controller->getUserHistory($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testRefreshRecommendations(): void
    {
        $this->discoverService
            ->expects($this->once())
            ->method('refreshRecommendations');

        $result = $this->controller->refreshRecommendations(new Request());

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Recommendations refreshed successfully', $data['message']);
    }

    public function testSearchContentSuccess(): void
    {
        $request = new Request(['q' => 'test query', 'type' => 'post', 'limit' => '10']);
        $results = [
            ['id' => 'content1', 'title' => 'Test Result 1'],
            ['id' => 'content2', 'title' => 'Test Result 2'],
        ];

        $this->discoverService
            ->expects($this->once())
            ->method('searchContent')
            ->with('test query', 'post', 10)
            ->willReturn($results);

        $result = $this->controller->searchContent($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertCount(2, $data);
    }

    public function testSearchContentMissingQuery(): void
    {
        $request = new Request();

        $result = $this->controller->searchContent($request);

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(400, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        $this->assertEquals('Search query is required', $data['error']);
    }

    public function testSearchContentDefaultParameters(): void
    {
        $request = new Request(['q' => 'test']);
        $results = [];

        $this->discoverService
            ->expects($this->once())
            ->method('searchContent')
            ->with('test', null, 20)
            ->willReturn($results);

        $result = $this->controller->searchContent($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testGetTrendingWithoutType(): void
    {
        $request = new Request(['limit' => '30']);
        $trendingContent = [];

        $this->discoverService
            ->expects($this->once())
            ->method('getTrendingContent')
            ->with(30)
            ->willReturn($trendingContent);

        $result = $this->controller->getTrending($request);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testTrackActionEmptyFields(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'action_type' => '',
                'content_type' => 'post',
                'content_id' => 'content123',
            ])
        );

        $result = $this->controller->trackAction($request);

        $this->assertEquals(400, $result->getStatusCode());
        $data = json_decode($result->getContent(), true);
        $this->assertEquals('action_type, content_type, and content_id are required', $data['error']);
    }
}
