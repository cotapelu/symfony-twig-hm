<?php

declare(strict_types=1);

namespace Tests\Unit\SmartCity\Controller;

use App\SmartCity\Controller\SmartCityController;
use App\SmartCity\Domain\Service\SmartCityService;
use App\SmartCity\Domain\Entity\SmartWard;
use App\SmartCity\Domain\Entity\IoTSensor;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

class SmartCityControllerTest extends TestCase
{
    private SmartCityService $serviceMock;
    private SmartCityController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(SmartCityService::class);
        $this->controller = new SmartCityController($this->serviceMock, null, null);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function setAuthenticatedUser(bool $isAdmin = false): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('user123');
        $roles = ['ROLE_USER'];
        if ($isAdmin) $roles = ['ROLE_ADMIN'];
        $user->method('getRoles')->willReturn($roles);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturnCallback(function ($role) use ($isAdmin) {
            if (str_starts_with($role, 'IS_AUTHENTICATED_')) return true;
            if ($role === 'ROLE_ADMIN') return $isAdmin;
            return false;
        });

        $ref = new ReflectionProperty($this->controller, 'tokenStorage');
        $ref->setAccessible(true);
        $ref->setValue($this->controller, $tokenStorage);
        $refAuth = new ReflectionProperty($this->controller, 'authChecker');
        $refAuth->setAccessible(true);
        $refAuth->setValue($this->controller, $authChecker);
    }

    private function createJsonRequest(array $data): Request
    {
        return Request::create('/api/smartcity', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
    }

    // ==================== getAllWards ====================

    public function testGetAllWards_Unauthenticated_Returns401(): void
    {
        $response = $this->controller->getAllWards();
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetAllWards_Authenticated_Success(): void
    {
        $this->setAuthenticatedUser(false);
        $this->serviceMock->expects($this->once())->method('getAllWards')->willReturn([]);
        $response = $this->controller->getAllWards();
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== getWard ====================

    public function testGetWard_Unauthenticated_Returns401(): void
    {
        $response = $this->controller->getWard('WC01');
        $this->assertSame(401, $response->getStatusCode());
    }



    public function testGetWard_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser(false);
        $this->serviceMock->expects($this->once())->method('getWard')->with('WC01')->willReturn(null);
        $response = $this->controller->getWard('WC01');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetWard_Success_Returns200(): void
    {
        $this->setAuthenticatedUser(false);
        $ward = $this->createMock(SmartWard::class);
        $ward->method('getWardCode')->willReturn('WC01');
        $ward->method('getName')->willReturn('Test');
        $ward->method('getPopulation')->willReturn(1000);
        $ward->method('getLocation')->willReturn(['latitude'=>10, 'longitude'=>106]);
        $ward->method('getStatus')->willReturn('active');
        $ward->method('getSensors')->willReturn([]);
        $this->serviceMock->expects($this->once())->method('getWard')->with('WC01')->willReturn($ward);
        $response = $this->controller->getWard('WC01');
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== createWard ====================

    public function testCreateWard_Unauthenticated_Returns403(): void
    {
        $request = $this->createJsonRequest(['ward_code' => 'WC01', 'name' => 'Test', 'latitude' => 10, 'longitude' => 106]);
        $response = $this->controller->createWard($request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testCreateWard_NotAdmin_Returns403(): void
    {
        $this->setAuthenticatedUser(false);
        $request = $this->createJsonRequest(['ward_code' => 'WC01', 'name' => 'Test', 'latitude' => 10, 'longitude' => 106]);
        $response = $this->controller->createWard($request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testCreateWard_InvalidJson_Returns400(): void
    {
        $this->setAuthenticatedUser(true);
        $request = Request::create('/api/smartcity/wards', 'POST', [], [], [], [], 'invalid');
        $response = $this->controller->createWard($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateWard_ValidationError_Returns400(): void
    {
        $this->setAuthenticatedUser(true);
        $request = $this->createJsonRequest(['ward_code' => 'WC01', 'name' => 'Test']); // missing latitude/longitude
        $response = $this->controller->createWard($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateWard_Duplicate_Returns400(): void
    {
        $this->setAuthenticatedUser(true);
        $this->serviceMock->expects($this->once())->method('createWard')->willThrowException(new \InvalidArgumentException('Ward code already exists'));
        $request = $this->createJsonRequest(['ward_code' => 'WC01', 'name' => 'Test', 'latitude' => 10, 'longitude' => 106]);
        $response = $this->controller->createWard($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateWard_Success_Returns201(): void
    {
        $this->setAuthenticatedUser(true);
        $ward = $this->createMock(SmartWard::class);
        $ward->method('getWardCode')->willReturn('WC01');
        $ward->method('getName')->willReturn('Test');
        $ward->method('getLocation')->willReturn(['latitude'=>10, 'longitude'=>106]);
        $this->serviceMock->expects($this->once())->method('createWard')->with('WC01', 'Test', 10.0, 106.0, 0)->willReturn($ward);
        $request = $this->createJsonRequest(['ward_code' => 'WC01', 'name' => 'Test', 'latitude' => 10, 'longitude' => 106]);
        $response = $this->controller->createWard($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    // ==================== getWardSensors ====================

    public function testGetWardSensors_Unauthenticated_Returns401(): void
    {
        $response = $this->controller->getWardSensors('WC01');
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetWardSensors_Empty_Returns200(): void
    {
        $this->setAuthenticatedUser(false);
        $this->serviceMock->expects($this->once())->method('getWardSensors')->with('WC01')->willReturn([]);
        $response = $this->controller->getWardSensors('WC01');
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEmpty($content['sensors']);
    }

    public function testGetWardSensors_Success_Returns200(): void
    {
        $this->setAuthenticatedUser(false);
        $sensor = $this->createMock(IoTSensor::class);
        $sensor->method('getId')->willReturn('S001');
        $sensor->method('getType')->willReturn('traffic');
        $sensor->method('getLocation')->willReturn(['latitude'=>10, 'longitude'=>106, 'location'=>'Downtown']);
        $sensor->method('getStatus')->willReturn('active');
        $sensor->method('getLastReading')->willReturn(50.0);
        $this->serviceMock->expects($this->once())->method('getWardSensors')->with('WC01')->willReturn(['S001' => $sensor]);
        $response = $this->controller->getWardSensors('WC01');
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== addSensorToWard ====================

    public function testAddSensorToWard_Unauthenticated_Returns403(): void
    {
        $request = $this->createJsonRequest(['sensor_id' => 'S001', 'sensor_type' => 'traffic', 'latitude' => 10, 'longitude' => 106, 'location' => 'Downtown']);
        $response = $this->controller->addSensorToWard($request, 'WC01');
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testAddSensorToWard_NotAdmin_Returns403(): void
    {
        $this->setAuthenticatedUser(false);
        $request = $this->createJsonRequest(['sensor_id' => 'S001', 'sensor_type' => 'traffic', 'latitude' => 10, 'longitude' => 106, 'location' => 'Downtown']);
        $response = $this->controller->addSensorToWard($request, 'WC01');
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testAddSensorToWard_InvalidInput_Returns400(): void
    {
        $this->setAuthenticatedUser(true);
        // missing sensor_id
        $request = $this->createJsonRequest(['sensor_type' => 'traffic', 'latitude' => 10, 'longitude' => 106, 'location' => 'Downtown']);
        $this->serviceMock->expects($this->never())->method('addSensorToWard');
        $response = $this->controller->addSensorToWard($request, 'WC01');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testAddSensorToWard_WardNotFound_Returns404(): void
    {
        $this->setAuthenticatedUser(true);
        $this->serviceMock->expects($this->once())->method('addSensorToWard')->with('WC01', $this->anything())->willReturn(false);
        $request = $this->createJsonRequest(['sensor_id' => 'S001', 'sensor_type' => 'traffic', 'latitude' => 10, 'longitude' => 106, 'location' => 'Downtown']);
        $response = $this->controller->addSensorToWard($request, 'WC01');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testAddSensorToWard_Success_Returns201(): void
    {
        $this->setAuthenticatedUser(true);
        $this->serviceMock->expects($this->once())->method('addSensorToWard')->with('WC01', $this->anything())->willReturn(true);
        $request = $this->createJsonRequest(['sensor_id' => 'S001', 'sensor_type' => 'traffic', 'latitude' => 10, 'longitude' => 106, 'location' => 'Downtown']);
        $response = $this->controller->addSensorToWard($request, 'WC01');
        $this->assertSame(201, $response->getStatusCode());
    }

    // ==================== getSensorData ====================

    public function testGetSensorData_Unauthenticated_Returns401(): void
    {
        $response = $this->controller->getSensorData('WC01', 'S001');
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetSensorData_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser(false);
        $this->serviceMock->expects($this->once())->method('getSensorData')->with('WC01', 'S001')->willReturn(null);
        $response = $this->controller->getSensorData('WC01', 'S001');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetSensorData_Success_Returns200(): void
    {
        $this->setAuthenticatedUser(false);
        $data = ['id'=>'S001','type'=>'traffic','status'=>'active','lastReading'=>50];
        $this->serviceMock->expects($this->once())->method('getSensorData')->with('WC01', 'S001')->willReturn($data);
        $response = $this->controller->getSensorData('WC01', 'S001');
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertSame($data, $content);
    }

    // ==================== updateSensorReading ====================

    public function testUpdateSensorReading_Unauthenticated_Returns403(): void
    {
        $request = $this->createJsonRequest(['value' => 100]);
        $response = $this->controller->updateSensorReading($request, 'WC01', 'S001');
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testUpdateSensorReading_NotAdmin_Returns403(): void
    {
        $this->setAuthenticatedUser(false);
        $request = $this->createJsonRequest(['value' => 100]);
        $response = $this->controller->updateSensorReading($request, 'WC01', 'S001');
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testUpdateSensorReading_MissingValue_Returns400(): void
    {
        $this->setAuthenticatedUser(true);
        $request = $this->createJsonRequest([]);
        $response = $this->controller->updateSensorReading($request, 'WC01', 'S001');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateSensorReading_InvalidValue_Returns400(): void
    {
        $this->setAuthenticatedUser(true);
        $request = $this->createJsonRequest(['value' => 'abc']);
        $response = $this->controller->updateSensorReading($request, 'WC01', 'S001');
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateSensorReading_Success_Returns200(): void
    {
        $this->setAuthenticatedUser(true);
        $this->serviceMock->expects($this->once())->method('updateSensorReading')->with('WC01', 'S001', 100.0)->willReturn(true);
        $request = $this->createJsonRequest(['value' => 100]);
        $response = $this->controller->updateSensorReading($request, 'WC01', 'S001');
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== getWardAnalytics ====================

    public function testGetWardAnalytics_Unauthenticated_Returns401(): void
    {
        $response = $this->controller->getWardAnalytics('WC01');
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetWardAnalytics_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser(false);
        $this->serviceMock->expects($this->once())->method('getWardAnalytics')->with('WC01')->willReturn([]);
        $response = $this->controller->getWardAnalytics('WC01');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetWardAnalytics_Success_Returns200(): void
    {
        $this->setAuthenticatedUser(false);
        $data = ['traffic_volume' => 1000];
        $this->serviceMock->expects($this->once())->method('getWardAnalytics')->with('WC01')->willReturn($data);
        $response = $this->controller->getWardAnalytics('WC01');
        $this->assertSame(200, $response->getStatusCode());
    }

    // ==================== getPredictiveAnalytics ====================

    public function testGetPredictiveAnalytics_Unauthenticated_Returns401(): void
    {
        $response = $this->controller->getPredictiveAnalytics('WC01');
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetPredictiveAnalytics_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser(false);
        $this->serviceMock->expects($this->once())->method('getWard')->with('WC01')->willReturn(null);
        $response = $this->controller->getPredictiveAnalytics('WC01');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetPredictiveAnalytics_Success_Returns200(): void
    {
        $this->setAuthenticatedUser(false);
        $ward = $this->createMock(SmartWard::class);
        $ward->method('getName')->willReturn('Test Ward');
        $this->serviceMock->expects($this->once())->method('getWard')->with('WC01')->willReturn($ward);
        $this->serviceMock->expects($this->once())->method('getPredictiveAnalytics')->with('WC01')->willReturn(['population_growth' => 0.05]);
        $response = $this->controller->getPredictiveAnalytics('WC01');
        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertSame('Test Ward', $content['wardName']);
    }

    // ==================== getMapData ====================

    public function testGetMapData_Unauthenticated_Returns401(): void
    {
        $response = $this->controller->getMapData('WC01');
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetMapData_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser(false);
        $this->serviceMock->expects($this->once())->method('getMapData')->with('WC01')->willReturn([]);
        $response = $this->controller->getMapData('WC01');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetMapData_Success_Returns200(): void
    {
        $this->setAuthenticatedUser(false);
        $mapData = ['features' => []];
        $this->serviceMock->expects($this->once())->method('getMapData')->with('WC01')->willReturn($mapData);
        $response = $this->controller->getMapData('WC01');
        $this->assertSame(200, $response->getStatusCode());
    }
}
