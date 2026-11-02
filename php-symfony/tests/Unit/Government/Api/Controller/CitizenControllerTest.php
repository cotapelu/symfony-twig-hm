<?php

declare(strict_types=1);

namespace App\Tests\Unit\Government\Api\Controller;

use App\Government\Api\Controller\CitizenController;
use App\Government\Application\Service\CitizenService;
use App\Government\Contracts\DTO\CitizenResponse;
use App\Government\Contracts\DTO\CreateCitizenRequest;
use App\Government\Contracts\DTO\UpdateCitizenRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use InvalidArgumentException;

class CitizenControllerTest extends TestCase
{
    private CitizenService $citizenServiceMock;
    private CitizenController $controller;

    private function createCitizenResponse(array $data): CitizenResponse
    {
        return new CitizenResponse(
            $data['id'] ?? '',
            $data['fullName'] ?? '',
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['dateOfBirth'] ?? null,
            $data['identityNumber'] ?? '',
            $data['gender'] ?? '',
            $data['registeredAddress'] ?? [],
            $data['isActive'] ?? false,
            $data['occupation'] ?? null,
            $data['maritalStatus'] ?? null,
            $data['householdId'] ?? null,
            $data['age'] ?? null,
            $data['createdAt'] ?? ''
        );
    }

    private function createRequest(array $query = [], array $request = null, string $method = 'GET'): Request
    {
        $content = $request !== null ? json_encode($request) : null;
        return Request::create('/api/government/citizens', $method, $query, [], [], ['CONTENT_TYPE' => 'application/json'], $content);
    }

    protected function setUp(): void
    {
        $this->citizenServiceMock = $this->createMock(CitizenService::class);
        $this->controller = new CitizenController($this->citizenServiceMock);
        $this->controller->setContainer(new ContainerBuilder());
    }

    // ==================== create ====================

    public function testCreate_Success(): void
    {
        $data = [
            'fullName' => 'John Doe',
            'identityNumber' => 'ID123456',
            'province' => 'Hanoi',
            'district' => 'Cau Giay',
            'ward' => 'Dich Vong',
            'street' => '123 Main St',
        ];
        $request = $this->createRequest([], $data, 'POST');

        $citizenResponse = $this->createCitizenResponse(array_merge($data, ['id' => 'new-id']));
        $this->citizenServiceMock->expects($this->once())
            ->method('createCitizen')
            ->with($this->callback(function($arg) use ($data) {
                return $arg instanceof CreateCitizenRequest
                    && $arg->fullName === $data['fullName']
                    && $arg->identityNumber === $data['identityNumber']
                    && $arg->province === $data['province']
                    && $arg->district === $data['district']
                    && $arg->ward === $data['ward']
                    && $arg->street === $data['street'];
            }))
            ->willReturn($citizenResponse);

        $response = $this->controller->create($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
        $result = json_decode($response->getContent(), true);
        $this->assertSame($data['fullName'], $result['fullName']);
        $this->assertSame('new-id', $result['id']);
    }

    public function testCreate_MissingRequiredField(): void
    {
        $data = [
            'fullName' => 'John Doe',
            'identityNumber' => 'ID123',
            'province' => 'Hanoi',
            // missing district, ward, street
        ];
        $request = $this->createRequest([], $data, 'POST');

        $response = $this->controller->create($request);
        $this->assertSame(400, $response->getStatusCode());
        $error = json_decode($response->getContent(), true)['error'];
        $this->assertStringContainsString("Field 'district' is required", $error);
    }

    public function testCreate_InvalidEmail(): void
    {
        $data = [
            'fullName' => 'John',
            'identityNumber' => 'ID123',
            'province' => 'Hanoi',
            'district' => 'Cau Giay',
            'ward' => 'Dich Vong',
            'street' => '123 Main St',
            'email' => 'invalid-email',
        ];
        $request = $this->createRequest([], $data, 'POST');

        $response = $this->controller->create($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Invalid email format', json_decode($response->getContent(), true)['error']);
    }

    public function testCreate_InvalidPhone(): void
    {
        $data = [
            'fullName' => 'John',
            'identityNumber' => 'ID123',
            'province' => 'Hanoi',
            'district' => 'Cau Giay',
            'ward' => 'Dich Vong',
            'street' => '123 Main St',
            'phone' => 'abc123',
        ];
        $request = $this->createRequest([], $data, 'POST');

        $response = $this->controller->create($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Invalid phone number format', json_decode($response->getContent(), true)['error']);
    }

    public function testCreate_InvalidGender(): void
    {
        $data = [
            'fullName' => 'John',
            'identityNumber' => 'ID123',
            'province' => 'Hanoi',
            'district' => 'Cau Giay',
            'ward' => 'Dich Vong',
            'street' => '123 Main St',
            'gender' => 'unknown',
        ];
        $request = $this->createRequest([], $data, 'POST');

        $response = $this->controller->create($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid gender', json_decode($response->getContent(), true)['error']);
    }

    public function testCreate_InvalidDateOfBirth(): void
    {
        $data = [
            'fullName' => 'John',
            'identityNumber' => 'ID123',
            'province' => 'Hanoi',
            'district' => 'Cau Giay',
            'ward' => 'Dich Vong',
            'street' => '123 Main St',
            'dateOfBirth' => 'invalid-date',
        ];
        $request = $this->createRequest([], $data, 'POST');

        $response = $this->controller->create($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Invalid date of birth format', json_decode($response->getContent(), true)['error']);
    }

    public function testCreate_FullNameTooLong(): void
    {
        $data = [
            'fullName' => str_repeat('a', 101),
            'identityNumber' => 'ID123',
            'province' => 'Hanoi',
            'district' => 'Cau Giay',
            'ward' => 'Dich Vong',
            'street' => '123 Main St',
        ];
        $request = $this->createRequest([], $data, 'POST');

        $response = $this->controller->create($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Full name cannot exceed 100 characters', json_decode($response->getContent(), true)['error']);
    }

    public function testCreate_ServiceThrowsInvalidArgumentException(): void
    {
        $data = [
            'fullName' => 'John',
            'identityNumber' => 'ID123',
            'province' => 'Hanoi',
            'district' => 'Cau Giay',
            'ward' => 'Dich Vong',
            'street' => '123 Main St',
        ];
        $request = $this->createRequest([], $data, 'POST');

        $this->citizenServiceMock->expects($this->once())
            ->method('createCitizen')
            ->willThrowException(new InvalidArgumentException('Citizen with identity number ID123 already exists'));

        $response = $this->controller->create($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Citizen with identity number ID123 already exists', json_decode($response->getContent(), true)['error']);
    }

    public function testCreate_ServiceThrowsGenericException(): void
    {
        $data = [
            'fullName' => 'John',
            'identityNumber' => 'ID123',
            'province' => 'Hanoi',
            'district' => 'Cau Giay',
            'ward' => 'Dich Vong',
            'street' => '123 Main St',
        ];
        $request = $this->createRequest([], $data, 'POST');

        $this->citizenServiceMock->expects($this->once())
            ->method('createCitizen')
            ->willThrowException(new \Exception('Database error'));

        $response = $this->controller->create($request);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(['error' => 'Internal server error'], json_decode($response->getContent(), true));
    }

    // ==================== get ====================

    public function testGet_Success(): void
    {
        $id = 'citizen-uuid';
        $citizenResponse = $this->createCitizenResponse(['id' => $id, 'fullName' => 'John']);
        $this->citizenServiceMock->method('getCitizenById')->with($id)->willReturn($citizenResponse);

        $response = $this->controller->get($id);
        $this->assertSame(200, $response->getStatusCode());
        $result = json_decode($response->getContent(), true);
        $this->assertSame($id, $result['id']);
    }

    public function testGet_NotFound(): void
    {
        $id = 'unknown';
        $this->citizenServiceMock->method('getCitizenById')->with($id)->willReturn(null);

        $response = $this->controller->get($id);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(['error' => 'Citizen not found'], json_decode($response->getContent(), true));
    }

    public function testGet_InvalidId(): void
    {
        $id = '';
        $response = $this->controller->get($id);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'ID cannot be empty'], json_decode($response->getContent(), true));
    }

    public function testGet_IdTooLong(): void
    {
        $id = str_repeat('a', 37);
        $response = $this->controller->get($id);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'ID cannot exceed 36 characters'], json_decode($response->getContent(), true));
    }

    // ==================== list ====================

    public function testList_Success(): void
    {
        $citizens = [
            $this->createCitizenResponse(['id' => '1', 'fullName' => 'Alice']),
            $this->createCitizenResponse(['id' => '2', 'fullName' => 'Bob']),
        ];
        $this->citizenServiceMock->method('listCitizens')->with([], 1, 20)->willReturn($citizens);

        $request = $this->createRequest([], null, 'GET');
        $response = $this->controller->list($request);
        $this->assertSame(200, $response->getStatusCode());
        $result = json_decode($response->getContent(), true);
        $this->assertCount(2, $result['data']);
        $this->assertSame(1, $result['page']);
        $this->assertSame(20, $result['perPage']);
    }

    public function testList_WithFilters(): void
    {
        $query = ['province' => 'Hanoi', 'active' => 'true'];
        $this->citizenServiceMock->method('listCitizens')
            ->with($this->callback(function($filters) {
                return isset($filters['province']) && $filters['province'] === 'Hanoi' && array_key_exists('isActive', $filters);
            }), 1, 20)
            ->willReturn([]);

        $request = $this->createRequest($query, null, 'GET');
        $response = $this->controller->list($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testList_InvalidPage(): void
    {
        $query = ['page' => '0'];
        $request = $this->createRequest($query, null, 'GET');

        $response = $this->controller->list($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'Page must be at least 1'], json_decode($response->getContent(), true));
    }

    public function testList_InvalidPerPage(): void
    {
        $query = ['perPage' => '200'];
        $request = $this->createRequest($query, null, 'GET');

        $response = $this->controller->list($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Per page must be between', json_decode($response->getContent(), true)['error']);
    }

    // ==================== search ====================

    public function testSearch_Success(): void
    {
        $query = 'john';
        $citizens = [$this->createCitizenResponse(['id' => '1', 'fullName' => 'John'])];
        $this->citizenServiceMock->method('searchCitizens')->with($query)->willReturn($citizens);

        $request = $this->createRequest(['q' => $query], null, 'GET');
        $response = $this->controller->search($request);
        $this->assertSame(200, $response->getStatusCode());
        $result = json_decode($response->getContent(), true);
        $this->assertCount(1, $result['data']);
    }

    public function testSearch_MissingQuery(): void
    {
        $request = $this->createRequest([], null, 'GET');
        $response = $this->controller->search($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'Search query is required'], json_decode($response->getContent(), true));
    }

    public function testSearch_QueryTooLong(): void
    {
        $query = str_repeat('a', 101);
        $request = $this->createRequest(['q' => $query], null, 'GET');

        $response = $this->controller->search($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'Search query cannot exceed 100 characters'], json_decode($response->getContent(), true));
    }

    // ==================== update ====================

    public function testUpdate_Success(): void
    {
        $id = 'citizen-id';
        $data = ['email' => 'new@example.com', 'phone' => '1234567890'];
        $request = $this->createRequest([], $data, 'PUT');

        $citizenResponse = $this->createCitizenResponse(['id' => $id, 'email' => 'new@example.com']);
        $this->citizenServiceMock->expects($this->once())
            ->method('updateCitizen')
            ->with($id, $this->callback(function($arg) use ($data) {
                return $arg instanceof UpdateCitizenRequest
                    && $arg->email === $data['email']
                    && $arg->phone === $data['phone'];
            }))
            ->willReturn($citizenResponse);

        $response = $this->controller->update($id, $request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('new@example.com', json_decode($response->getContent(), true)['email']);
    }

    public function testUpdate_NotFound(): void
    {
        $id = 'unknown';
        $data = ['email' => 'test@example.com'];
        $request = $this->createRequest([], $data, 'PUT');
        $this->citizenServiceMock->method('updateCitizen')->with($id, $this->anything())->willReturn(null);

        $response = $this->controller->update($id, $request);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(['error' => 'Citizen not found'], json_decode($response->getContent(), true));
    }

    public function testUpdate_InvalidEmail(): void
    {
        $id = 'citizen-id';
        $data = ['email' => 'invalid'];
        $request = $this->createRequest([], $data, 'PUT');

        $response = $this->controller->update($id, $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Invalid email format', json_decode($response->getContent(), true)['error']);
    }

    public function testUpdate_InvalidPhone(): void
    {
        $id = 'citizen-id';
        $data = ['phone' => 'abc'];
        $request = $this->createRequest([], $data, 'PUT');

        $response = $this->controller->update($id, $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Invalid phone number format', json_decode($response->getContent(), true)['error']);
    }

    public function testUpdate_InvalidDateOfBirth(): void
    {
        $id = 'citizen-id';
        $data = ['dateOfBirth' => 'invalid-date'];
        $request = $this->createRequest([], $data, 'PUT');

        $response = $this->controller->update($id, $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Invalid date of birth format', json_decode($response->getContent(), true)['error']);
    }

    public function testUpdate_InvalidGender(): void
    {
        $id = 'citizen-id';
        $data = ['gender' => 'unknown'];
        $request = $this->createRequest([], $data, 'PUT');

        $response = $this->controller->update($id, $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid gender', json_decode($response->getContent(), true)['error']);
    }

    // ==================== deactivate ====================

    public function testDeactivate_Success(): void
    {
        $id = 'citizen-id';
        $this->citizenServiceMock->method('deactivateCitizen')->with($id)->willReturn(true);
        $response = $this->controller->deactivate($id);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['message' => 'Citizen deactivated successfully'], json_decode($response->getContent(), true));
    }

    public function testDeactivate_NotFound(): void
    {
        $id = 'unknown';
        $this->citizenServiceMock->method('deactivateCitizen')->with($id)->willReturn(false);
        $response = $this->controller->deactivate($id);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(['error' => 'Citizen not found'], json_decode($response->getContent(), true));
    }

    // ==================== activate ====================

    public function testActivate_Success(): void
    {
        $id = 'citizen-id';
        $this->citizenServiceMock->method('activateCitizen')->with($id)->willReturn(true);
        $response = $this->controller->activate($id);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['message' => 'Citizen activated successfully'], json_decode($response->getContent(), true));
    }

    public function testActivate_NotFound(): void
    {
        $id = 'unknown';
        $this->citizenServiceMock->method('activateCitizen')->with($id)->willReturn(false);
        $response = $this->controller->activate($id);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(['error' => 'Citizen not found'], json_decode($response->getContent(), true));
    }

    // ==================== getByHousehold ====================

    public function testGetByHousehold_Success(): void
    {
        $householdId = 'hh-1';
        $citizens = [$this->createCitizenResponse(['id' => 'c1'])];
        $this->citizenServiceMock->method('getCitizensByHousehold')->with($householdId)->willReturn($citizens);

        $response = $this->controller->getByHousehold($householdId);
        $this->assertSame(200, $response->getStatusCode());
        $result = json_decode($response->getContent(), true);
        $this->assertCount(1, $result);
    }

    public function testGetByHousehold_InvalidId(): void
    {
        $householdId = '';
        $response = $this->controller->getByHousehold($householdId);
        $this->assertSame(400, $response->getStatusCode());
    }

    // ==================== statistics ====================

    public function testGetStatistics_Success(): void
    {
        $stats = ['total' => 100, 'active' => 80, 'deceased' => 5, 'inactive' => 20];
        $this->citizenServiceMock->method('getStatistics')->with([])->willReturn($stats);

        $request = $this->createRequest([], null, 'GET');
        $response = $this->controller->statistics($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($stats, json_decode($response->getContent(), true));
    }

    public function testGetStatistics_WithFilters(): void
    {
        $filters = ['province' => 'Hanoi'];
        $this->citizenServiceMock->expects($this->once())
            ->method('getStatistics')
            ->with($this->equalTo(['province' => 'Hanoi']))
            ->willReturn(['total' => 10]);

        $request = $this->createRequest(['province' => 'Hanoi'], null, 'GET');
        $response = $this->controller->statistics($request);
        $this->assertSame(200, $response->getStatusCode());
    }
}
