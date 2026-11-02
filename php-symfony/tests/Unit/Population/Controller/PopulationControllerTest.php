<?php

declare(strict_types=1);

namespace App\Tests\Unit\Population\Controller;

use PHPUnit\Framework\TestCase;
use App\Population\Controller\PopulationController;
use App\Population\Service\PopulationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PopulationControllerTest extends TestCase
{
    private PopulationService $service;
    private PopulationController $controller;

    protected function setUp(): void
    {
        $this->service = $this->createMock(PopulationService::class);
        $this->controller = new PopulationController($this->service);
        $this->setupContainer();
    }

    private function setupContainer(): void
    {
        $container = new ContainerBuilder();
        $container->set('serializer', new \Symfony\Component\Serializer\Serializer([], [new \Symfony\Component\Serializer\Encoder\JsonEncoder()]));
        $this->controller->setContainer($container);
    }

    private function jsonRequest(array $data = [], string $method = 'GET'): Request
    {
        return Request::create('/api/population', $method, [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
    }

    // ==================== Authentication Helpers ====================

    private function mockAuthenticatedUser(int $userId = 1, bool $isAdmin = false): void
    {
        $token = $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class);
        $user = new class($userId) implements \Symfony\Component\Security\Core\User\UserInterface {
            private int $id;
            public function __construct(int $id) { $this->id = $id; }
            public function getUserIdentifier(): string { return (string)$this->id; }
            public function getRoles(): array { return ['ROLE_USER']; }
            public function eraseCredentials() {}
            public function getId(): int { return $this->id; }
        };

        $token->method('getUser')->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturnCallback(function(string $attribute) use ($isAdmin) {
            if ($attribute === 'IS_AUTHENTICATED_REMEMBERED') {
                return true;
            }
            if ($attribute === 'ROLE_ADMIN') {
                return $isAdmin;
            }
            return false;
        });

        // Rebuild controller with mocks
        $this->controller = new PopulationController($this->service, $tokenStorage, $authChecker);
        $this->setupContainer(); // set container on the new controller instance
    }

    // ==================== list() tests ====================

    public function testListDefault(): void
    {
        $expected = ['items' => [], 'total' => 0, 'page' => 1, 'total_pages' => 0];
        $this->service->expects($this->once())
            ->method('getPopulationList')
            ->with(1, 20, 'all', '')
            ->willReturn($expected);

        $response = $this->controller->list(new Request());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expected, json_decode($response->getContent(), true));
    }

    public function testListWithPagination(): void
    {
        $expected = ['items' => [], 'total' => 5, 'page' => 2, 'total_pages' => 1];
        $request = Request::create('/api/population', 'GET', ['page' => '2', 'limit' => '10']);
        $this->service->expects($this->once())
            ->method('getPopulationList')
            ->with(2, 10, 'all', '')
            ->willReturn($expected);

        $response = $this->controller->list($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testListInvalidPageBecomesOne(): void
    {
        $request = Request::create('/api/population', 'GET', ['page' => '0']);
        $this->service->expects($this->once())
            ->method('getPopulationList')
            ->with(1, 20, 'all', '')
            ->willReturn(['items'=>[],'total'=>0,'page'=>1,'total_pages'=>0]);

        $this->controller->list($request);
    }

    public function testListInvalidLimitBecomesTwenty(): void
    {
        $request = Request::create('/api/population', 'GET', ['limit' => '200']);
        $this->service->expects($this->once())
            ->method('getPopulationList')
            ->with(1, 20, 'all', '')
            ->willReturn(['items'=>[],'total'=>0,'page'=>1,'total_pages'=>0]);

        $this->controller->list($request);
    }

    public function testListInvalidFilterReturnsBadRequest(): void
    {
        $request = Request::create('/api/population', 'GET', ['filter' => 'invalid']);
        $response = $this->controller->list($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid filter', $response->getContent());
    }

    // ==================== statistics() tests ====================

    public function testStatisticsSuccess(): void
    {
        $stats = ['total' => 100, 'male' => 50, 'female' => 50, 'avg_age' => 30];
        $this->service->expects($this->once())
            ->method('getStatistics')
            ->willReturn($stats);

        $response = $this->controller->statistics();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($stats, json_decode($response->getContent(), true));
    }

    // ==================== ageDistribution() tests ====================

    public function testAgeDistributionSuccess(): void
    {
        $dist = ['0-18' => 20, '19-30' => 30, '31-60' => 40, '60+' => 10];
        $this->service->expects($this->once())
            ->method('getAgeDistribution')
            ->willReturn($dist);

        $response = $this->controller->ageDistribution();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($dist, json_decode($response->getContent(), true));
    }

    // ==================== get() tests ====================

    public function testGetSuccess(): void
    {
        $id = 'rec-'.uniqid();
        $record = ['id' => $id, 'name' => 'John', 'user_id' => 1];
        $this->service->expects($this->once())
            ->method('getById')
            ->with($id)
            ->willReturn($record);

        $this->mockAuthenticatedUser(1); // authenticate as the owner
        $response = $this->controller->get($id);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($record, json_decode($response->getContent(), true));
    }

    public function testGetInvalidId(): void
    {
        $response = $this->controller->get(str_repeat('a', 51)); // too long
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetNotFound(): void
    {
        $id = 'missing';
        $this->service->expects($this->once())
            ->method('getById')
            ->with($id)
            ->willReturn(null);

        $response = $this->controller->get($id);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetForbiddenWhenNotOwner(): void
    {
        $id = 'rec-'.uniqid();
        $record = ['id' => $id, 'name' => 'Jane', 'user_id' => 2];
        $this->service->expects($this->once())
            ->method('getById')
            ->with($id)
            ->willReturn($record);

        $this->mockAuthenticatedUser(1); // logged in as user 1

        $response = $this->controller->get($id);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testGetAllowedForAdmin(): void
    {
        $id = 'rec-'.uniqid();
        $record = ['id' => $id, 'name' => 'AdminView', 'user_id' => 2];
        $this->service->expects($this->once())
            ->method('getById')
            ->with($id)
            ->willReturn($record);

        $this->mockAuthenticatedUser(1, true); // admin

        $response = $this->controller->get($id);
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== create() tests ====================

    public function testCreateSuccess(): void
    {
        $this->mockAuthenticatedUser(1);
        $data = ['name' => 'New Citizen', 'birth_date' => '2000-01-01'];
        $created = ['id' => 'new-id', 'name' => 'New Citizen', 'user_id' => 1];

        $this->service->expects($this->once())
            ->method('create')
            ->with($data, 1)
            ->willReturn($created);

        $request = $this->jsonRequest($data, 'POST');
        $response = $this->controller->create($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame($created, json_decode($response->getContent(), true));
    }

    public function testCreateUnauthenticated(): void
    {
        $data = ['name' => 'Test'];
        $request = $this->jsonRequest($data, 'POST');
        // No auth mocks; tokenStorage is null in default controller
        $response = $this->controller->create($request);
        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('Authentication required', $response->getContent());
    }

    public function testCreateInvalidJson(): void
    {
        $this->mockAuthenticatedUser(1);
        $request = Request::create('/api/population', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid');
        $response = $this->controller->create($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateServiceException(): void
    {
        $this->mockAuthenticatedUser(1);
        $data = ['name' => 'Test'];
        $request = $this->jsonRequest($data, 'POST');

        $this->service->expects($this->once())
            ->method('create')
            ->willThrowException(new \Exception('Validation failed'));

        $response = $this->controller->create($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Validation failed', $response->getContent());
    }

    // ==================== update() tests ====================

    public function testUpdateSuccess(): void
    {
        $this->mockAuthenticatedUser(1);
        $id = 'update-id';
        $existing = ['id' => $id, 'name' => 'Old', 'user_id' => 1];
        $data = ['name' => 'Updated'];
        $updated = ['id' => $id, 'name' => 'Updated', 'user_id' => 1];

        $this->service->expects($this->once())
            ->method('getById')
            ->with($id)
            ->willReturn($existing);
        $this->service->expects($this->once())
            ->method('update')
            ->with($id, $data, 1)
            ->willReturn($updated);

        $request = $this->jsonRequest($data, 'PUT');
        $response = $this->controller->update($id, $request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($updated, json_decode($response->getContent(), true));
    }

    public function testUpdateInvalidId(): void
    {
        $this->mockAuthenticatedUser(1);
        $response = $this->controller->update(str_repeat('a', 51), $this->jsonRequest([], 'PUT'));
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateUnauthenticated(): void
    {
        $id = 'update-id';
        $response = $this->controller->update($id, $this->jsonRequest([], 'PUT'));
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testUpdateForbidden(): void
    {
        $this->mockAuthenticatedUser(1);
        $id = 'update-id';
        $existing = ['id' => $id, 'user_id' => 2];
        $this->service->expects($this->once())
            ->method('getById')
            ->with($id)
            ->willReturn($existing);

        $response = $this->controller->update($id, $this->jsonRequest([], 'PUT'));
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testUpdateNotFoundAfterCall(): void
    {
        $this->mockAuthenticatedUser(1);
        $id = 'missing';
        $this->service->expects($this->once())
            ->method('getById')
            ->with($id)
            ->willReturn(null);
        // update called and returns false/not array?
        $this->service->expects($this->once())
            ->method('update')
            ->with($id, $this->anything(), 1)
            ->willReturn(null);

        $response = $this->controller->update($id, $this->jsonRequest([], 'PUT'));
        $this->assertSame(404, $response->getStatusCode());
    }

    // ==================== delete() tests ====================

    public function testDeleteSuccess(): void
    {
        $this->mockAuthenticatedUser(1);
        $id = 'delete-id';
        $existing = ['id' => $id, 'user_id' => 1];
        $this->service->expects($this->once())
            ->method('getById')
            ->with($id)
            ->willReturn($existing);
        $this->service->expects($this->once())
            ->method('delete')
            ->with($id, 1)
            ->willReturn(true);

        $response = $this->controller->delete($id);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['message' => 'Deleted successfully'], json_decode($response->getContent(), true));
    }

    public function testDeleteUnauthenticated(): void
    {
        $response = $this->controller->delete('any');
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testDeleteInvalidId(): void
    {
        $this->mockAuthenticatedUser(1);
        $response = $this->controller->delete(str_repeat('a', 51));
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testDeleteForbidden(): void
    {
        $this->mockAuthenticatedUser(1);
        $id = 'delete-id';
        $existing = ['id' => $id, 'user_id' => 2];
        $this->service->expects($this->once())
            ->method('getById')
            ->with($id)
            ->willReturn($existing);

        $response = $this->controller->delete($id);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testDeleteNotFound(): void
    {
        $this->mockAuthenticatedUser(1);
        $id = 'missing';
        $this->service->expects($this->once())
            ->method('getById')
            ->with($id)
            ->willReturn(null);
        $this->service->expects($this->once())
            ->method('delete')
            ->with($id, 1)
            ->willReturn(false);

        $response = $this->controller->delete($id);
        $this->assertSame(404, $response->getStatusCode());
    }

    // ==================== getByHousehold() tests ====================

    public function testGetByHouseholdSuccess(): void
    {
        $householdId = 'house-123';
        $records = [['id' => '1', 'household_id' => $householdId]];
        $this->service->expects($this->once())
            ->method('getByHousehold')
            ->with($householdId)
            ->willReturn($records);

        $response = $this->controller->getByHousehold($householdId);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($records, json_decode($response->getContent(), true));
    }

    public function testGetByHouseholdInvalidId(): void
    {
        $response = $this->controller->getByHousehold(str_repeat('a', 51));
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testGetByHouseholdEmpty(): void
    {
        $response = $this->controller->getByHousehold('');
        $this->assertSame(400, $response->getStatusCode());
    }

    // ==================== search() tests ====================

    public function testSearchSuccess(): void
    {
        $query = 'Nguyễn';
        $results = [['id' => '1', 'name' => 'Nguyễn Văn A']];
        $this->service->expects($this->once())
            ->method('search')
            ->with($query)
            ->willReturn($results);

        $request = Request::create('/api/population/search', 'GET', ['q' => $query]);
        $response = $this->controller->search($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($results, json_decode($response->getContent(), true));
    }

    public function testSearchEmptyDefault(): void
    {
        $this->service->expects($this->once())
            ->method('search')
            ->with('')
            ->willReturn([]);

        $request = Request::create('/api/population/search', 'GET');
        $response = $this->controller->search($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testSearchQueryTooLong(): void
    {
        $query = str_repeat('a', 101);
        $request = Request::create('/api/population/search', 'GET', ['q' => $query]);
        $response = $this->controller->search($request);
        $this->assertSame(400, $response->getStatusCode());
    }
}
