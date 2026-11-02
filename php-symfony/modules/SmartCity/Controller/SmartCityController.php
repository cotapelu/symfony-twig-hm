<?php

declare(strict_types=1);

namespace App\SmartCity\Controller;

use App\SmartCity\Domain\Service\SmartCityService;
use App\SmartCity\Domain\Entity\IoTSensor;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use InvalidArgumentException;
use DateTimeImmutable;

#[Route('/api/smartcity')]
class SmartCityController extends BaseController
{
    private const MAX_WARD_CODE_LENGTH = 20;
    private const MAX_WARD_NAME_LENGTH = 255;
    private const MAX_LOCATION_LENGTH = 500;
    private const MAX_POPULATION = 10000000;
    private const MAX_SENSOR_ID_LENGTH = 50;
    private const MAX_SENSOR_TYPE_LENGTH = 100;
    private const ALLOWED_SENSOR_TYPES = ['traffic','energy','weather','air_quality','noise','water','security','other'];
    private const ALLOWED_STATUSES = ['active','inactive','maintenance','error'];

    public function __construct(
        private SmartCityService $smartCityService,
        private ?TokenStorageInterface $tokenStorage = null,
        ?AuthorizationCheckerInterface $authChecker = null
    ) {
        parent::__construct();
        $this->authChecker = $authChecker;
    }

    private function isAuthenticated(): bool
    {
        if ($this->tokenStorage === null || $this->authChecker === null) return false;
        return $this->tokenStorage->getToken() !== null && $this->authChecker->isGranted('IS_AUTHENTICATED_REMEMBERED');
    }

    private function isAdmin(): bool
    {
        if (!$this->isAuthenticated()) return false;
        return $this->authChecker->isGranted('ROLE_ADMIN');
    }

    private function requireAuthOr401(): JsonResponse|true
    {
        if (!$this->isAuthenticated()) return new JsonResponse(['error'=>'Authentication required'], 401);
        return true;
    }

    private function handle(callable $action): JsonResponse
    {
        try { return $action(); }
        catch (InvalidArgumentException $e) { return $this->json(['error'=>$e->getMessage()], 400); }
        catch (\Exception $e) { return $this->handleException('controller', $e); }
    }

    private function handleException(string $context, \Exception $e): JsonResponse
    {
        error_log("SmartCityController::{$context} error: " . $e->getMessage());
        $this->logAction("{$context}_error", ['error'=>$e->getMessage()]);
        $this->incrementMetric("{$context}_errors");
        return $this->json(['error'=>'Internal server error'], 500);
    }

    private function logAction(string $action, array $context = []): void
    {
        $logEntry = ['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'action'=>$action, 'context'=>$context];
        error_log(json_encode($logEntry, JSON_UNESCAPED_UNICODE));
    }

    private function incrementMetric(string $metric, array $tags = []): void
    {
        error_log(json_encode(['timestamp'=>(new DateTimeImmutable())->format('Y-m-d H:i:s'), 'type'=>'metric', 'name'=>$metric, 'tags'=>$tags, 'value'=>1]));
    }

    private function sanitizeString(?string $value): string
    {
        return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    // ==================== Helpers ====================

    private function buildWardListResponse(array $wards): JsonResponse
    {
        $result = [];
        foreach ($wards as $ward) {
            $result[] = [
                'wardCode' => $ward->getWardCode(),
                'name' => $ward->getName(),
                'population' => $ward->getPopulation(),
                'location' => $ward->getLocation(),
                'status' => $ward->getStatus(),
                'sensorCount' => count($ward->getSensors()),
            ];
        }
        return $this->json(['wards' => $result]);
    }

    private function buildWardDetailResponse($ward): JsonResponse
    {
        return $this->json([
            'wardCode' => $ward->getWardCode(),
            'name' => $ward->getName(),
            'population' => $ward->getPopulation(),
            'location' => $ward->getLocation(),
            'status' => $ward->getStatus(),
            'sensorCount' => count($ward->getSensors()),
        ]);
    }

    private function buildSensorListResponse(array $sensors): JsonResponse
    {
        $result = [];
        foreach ($sensors as $sensor) {
            $loc = $sensor->getLocation();
            $result[] = [
                'id' => $sensor->getId(),
                'type' => $sensor->getType(),
                'location' => $loc['location'] ?? '',
                'latitude' => $loc['latitude'],
                'longitude' => $loc['longitude'],
                'status' => $sensor->getStatus(),
                'lastReading' => $sensor->getLastReading(),
            ];
        }
        return $this->json(['sensors' => $result]);
    }

    // ==================== Validation Helpers ====================

    private function validateWardCode(array $data): ?string
    {
        if (empty($data['ward_code']) || !is_string($data['ward_code'])) return 'Ward code is required';
        if (strlen($data['ward_code']) > self::MAX_WARD_CODE_LENGTH) return 'Ward code too long (max '.self::MAX_WARD_CODE_LENGTH.' chars)';
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['ward_code'])) return 'Invalid ward code format (alphanumeric, underscore, hyphen only)';
        return null;
    }

    private function validateWardName(array $data): ?string
    {
        if (empty($data['name']) || !is_string($data['name'])) return 'Ward name is required';
        if (strlen($data['name']) > self::MAX_WARD_NAME_LENGTH) return 'Ward name too long (max '.self::MAX_WARD_NAME_LENGTH.' chars)';
        return null;
    }

    private function validateLatitude(array $data, string $key): ?string
    {
        if (!isset($data[$key]) || !is_numeric($data[$key])) return ucfirst($key).' is required and must be numeric';
        $val = (float)$data[$key];
        if ($key === 'latitude' && ($val < -90 || $val > 90)) return 'Latitude must be between -90 and 90';
        return null;
    }

    private function validateLongitude(array $data, string $key): ?string
    {
        if (!isset($data[$key]) || !is_numeric($data[$key])) return ucfirst($key).' is required and must be numeric';
        $val = (float)$data[$key];
        if ($key === 'longitude' && ($val < -180 || $val > 180)) return 'Longitude must be between -180 and 180';
        return null;
    }

    private function validatePopulation(array $data): ?string
    {
        if (isset($data['population'])) {
            if (!is_int($data['population']) || $data['population'] < 0) return 'Population must be a non-negative integer';
            if ($data['population'] > self::MAX_POPULATION) return 'Population exceeds maximum allowed';
        }
        return null;
    }

    private function validateCreateWardInput(array $data): ?string
    {
        if ($err = $this->validateWardCode($data)) return $err;
        if ($err = $this->validateWardName($data)) return $err;
        if ($err = $this->validateLatitude($data, 'latitude')) return $err;
        if ($err = $this->validateLongitude($data, 'longitude')) return $err;
        if ($err = $this->validatePopulation($data)) return $err;
        return null;
    }

    private function validateSensorId(array $data): ?string
    {
        if (empty($data['sensor_id']) || !is_string($data['sensor_id'])) return 'Sensor ID is required';
        if (strlen($data['sensor_id']) > self::MAX_SENSOR_ID_LENGTH) return 'Sensor ID too long (max '.self::MAX_SENSOR_ID_LENGTH.' chars)';
        return null;
    }

    private function validateSensorType(array $data): ?string
    {
        if (empty($data['sensor_type']) || !is_string($data['sensor_type'])) return 'Sensor type is required';
        if (!in_array($data['sensor_type'], self::ALLOWED_SENSOR_TYPES)) return 'Invalid sensor type. Allowed: '.implode(', ', self::ALLOWED_SENSOR_TYPES);
        return null;
    }

    private function validateSensorLocation(array $data): ?string
    {
        if (empty($data['location']) || !is_string($data['location'])) return 'Location is required';
        if (strlen($data['location']) > self::MAX_LOCATION_LENGTH) return 'Location too long (max '.self::MAX_LOCATION_LENGTH.' chars)';
        return null;
    }

    private function validateSensorLatitude(array $data): ?string
    {
        if (!isset($data['latitude']) || !is_numeric($data['latitude'])) return 'Latitude is required and must be numeric';
        $lat = (float)$data['latitude'];
        if ($lat < -90 || $lat > 90) return 'Latitude must be between -90 and 90';
        return null;
    }

    private function validateSensorLongitude(array $data): ?string
    {
        if (!isset($data['longitude']) || !is_numeric($data['longitude'])) return 'Longitude is required and must be numeric';
        $lng = (float)$data['longitude'];
        if ($lng < -180 || $lng > 180) return 'Longitude must be between -180 and 180';
        return null;
    }

    private function validateAddSensorInput(array $data): ?string
    {
        if ($err = $this->validateSensorId($data)) return $err;
        if ($err = $this->validateSensorType($data)) return $err;
        if ($err = $this->validateSensorLocation($data)) return $err;
        if ($err = $this->validateSensorLatitude($data)) return $err;
        if ($err = $this->validateSensorLongitude($data)) return $err;
        return null;
    }

    // ==================== Endpoints ====================

    #[Route('/wards', methods: ['GET'])]
    public function getAllWards(): JsonResponse
    {
        return $this->handle(fn() => $this->getAllWardsAction());
    }

    private function getAllWardsAction(): JsonResponse
    {
        $authCheck = $this->requireAuthOr401();
        if ($authCheck instanceof JsonResponse) return $authCheck;
        $wards = $this->smartCityService->getAllWards();
        return $this->buildWardListResponse($wards);
    }

    #[Route('/wards/{wardCode}', methods: ['GET'])]
    public function getWard(string $wardCode): JsonResponse
    {
        return $this->handle(fn() => $this->getWardAction($wardCode));
    }

    private function getWardAction(string $wardCode): JsonResponse
    {
        $authCheck = $this->requireAuthOr401();
        if ($authCheck instanceof JsonResponse) return $authCheck;
        $ward = $this->smartCityService->getWard($wardCode);
        if (!$ward) return $this->json(['error'=>'Ward not found'], 404);
        return $this->buildWardDetailResponse($ward);
    }

    #[Route('/wards', methods: ['POST'])]
    public function createWard(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createWardAction($request));
    }

    private function createWardAction(Request $request): JsonResponse
    {
        if (!$this->isAdmin()) return $this->json(['error'=>'Admin access required'], 403);
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400;
        if ($err = $this->validateCreateWardInput($data)) return $this->json(['error'=>$err], 400);
        try {
            $ward = $this->smartCityService->createWard($data['ward_code'], $data['name'], (float)$data['latitude'], (float)$data['longitude'], (int)($data['population']??0));
            return $this->json(['wardCode'=>$ward->getWardCode(),'name'=>$ward->getName(),'location'=>$ward->getLocation()], 201);
        } catch (\InvalidArgumentException $e) { return $this->json(['error'=>$e->getMessage()], 400); }
    }

    #[Route('/wards/{wardCode}/sensors', methods: ['GET'])]
    public function getWardSensors(string $wardCode): JsonResponse
    {
        return $this->handle(fn() => $this->getWardSensorsAction($wardCode));
    }

    private function getWardSensorsAction(string $wardCode): JsonResponse
    {
        $authCheck = $this->requireAuthOr401();
        if ($authCheck instanceof JsonResponse) return $authCheck;
        $sensors = $this->smartCityService->getWardSensors($wardCode);
        return $this->buildSensorListResponse($sensors);
    }

    #[Route('/wards/{wardCode}/sensors', methods: ['POST'])]
    public function addSensorToWard(Request $request, string $wardCode): JsonResponse
    {
        return $this->handle(fn() => $this->addSensorToWardAction($request, $wardCode));
    }

    private function addSensorToWardAction(Request $request, string $wardCode): JsonResponse
    {
        if (!$this->isAdmin()) return $this->json(['error'=>'Admin access required'], 403);
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400;
        if ($err = $this->validateAddSensorInput($data)) return $this->json(['error'=>$err], 400);
        $sensor = new IoTSensor($data['sensor_id'], $data['sensor_type'], $data['location'], (float)$data['latitude'], (float)$data['longitude']);
        $success = $this->smartCityService->addSensorToWard($wardCode, $sensor);
        if (!$success) return $this->json(['error'=>'Ward not found'], 404);
        return $this->json(['message'=>'Sensor added successfully'], 201);
    }

    #[Route('/wards/{wardCode}/sensors/{sensorId}', methods: ['GET'])]
    public function getSensorData(string $wardCode, string $sensorId): JsonResponse
    {
        return $this->handle(fn() => $this->getSensorDataAction($wardCode, $sensorId));
    }

    private function getSensorDataAction(string $wardCode, string $sensorId): JsonResponse
    {
        $authCheck = $this->requireAuthOr401();
        if ($authCheck instanceof JsonResponse) return $authCheck;
        $sensorData = $this->smartCityService->getSensorData($wardCode, $sensorId);
        if (!$sensorData) return $this->json(['error'=>'Sensor not found'], 404);
        return $this->json($sensorData);
    }

    #[Route('/wards/{wardCode}/sensors/{sensorId}/readings', methods: ['POST'])]
    public function updateSensorReading(Request $request, string $wardCode, string $sensorId): JsonResponse
    {
        return $this->handle(fn() => $this->updateSensorReadingAction($request, $wardCode, $sensorId));
    }

    private function updateSensorReadingAction(Request $request, string $wardCode, string $sensorId): JsonResponse
    {
        if (!$this->isAdmin()) return $this->json(['error'=>'Admin access required'], 403);
        $dataOr400 = $this->decodeJsonOr400($request);
        if ($dataOr400 instanceof JsonResponse) return $dataOr400;
        $data = $dataOr400;
        if (empty($data) || !isset($data['value'])) return $this->json(['error'=>'Reading value required'], 400);
        if (!is_numeric($data['value'])) return $this->json(['error'=>'Reading value must be numeric'], 400);
        $success = $this->smartCityService->updateSensorReading($wardCode, $sensorId, (float)$data['value']);
        if (!$success) return $this->json(['error'=>'Sensor not found'], 404);
        return $this->json(['message'=>'Reading updated successfully']);
    }

    #[Route('/wards/{wardCode}/analytics', methods: ['GET'])]
    public function getWardAnalytics(string $wardCode): JsonResponse
    {
        return $this->handle(fn() => $this->getWardAnalyticsAction($wardCode));
    }

    private function getWardAnalyticsAction(string $wardCode): JsonResponse
    {
        $authCheck = $this->requireAuthOr401();
        if ($authCheck instanceof JsonResponse) return $authCheck;
        $analytics = $this->smartCityService->getWardAnalytics($wardCode);
        if (empty($analytics)) return $this->json(['error'=>'Ward not found'], 404);
        return $this->json($analytics);
    }

    #[Route('/wards/{wardCode}/analytics/predictive', methods: ['GET'])]
    public function getPredictiveAnalytics(string $wardCode): JsonResponse
    {
        return $this->handle(fn() => $this->getPredictiveAnalyticsAction($wardCode));
    }

    private function getPredictiveAnalyticsAction(string $wardCode): JsonResponse
    {
        $authCheck = $this->requireAuthOr401();
        if ($authCheck instanceof JsonResponse) return $authCheck;
        $predictions = $this->smartCityService->getPredictiveAnalytics($wardCode);
        $ward = $this->smartCityService->getWard($wardCode);
        if (!$ward) return $this->json(['error'=>'Ward not found'], 404);
        return $this->json([
            'wardCode' => $wardCode,
            'wardName' => $ward->getName(),
            'predictions' => $predictions,
            'note' => 'Predictions are based on historical sensor data and may not reflect actual future conditions',
        ]);
    }

    #[Route('/wards/{wardCode}/map', methods: ['GET'])]
    public function getMapData(string $wardCode): JsonResponse
    {
        return $this->handle(fn() => $this->getMapDataAction($wardCode));
    }

    private function getMapDataAction(string $wardCode): JsonResponse
    {
        $authCheck = $this->requireAuthOr401();
        if ($authCheck instanceof JsonResponse) return $authCheck;
        $mapData = $this->smartCityService->getMapData($wardCode);
        if (empty($mapData)) return $this->json(['error'=>'Ward not found'], 404);
        return $this->json($mapData);
    }

    #[Route('/metrics', name: 'api_smartcity_metrics', methods: ['GET'])]
    public function getMetrics(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $metrics = $this->smartCityService->getMetrics();
        return $this->json(['success'=>true, 'data'=>$metrics]);
    }

    #[Route('/incidents', name: 'api_smartcity_incidents', methods: ['GET'])]
    public function getIncidents(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $incidents = $this->smartCityService->getIncidents();
        return $this->json(['success'=>true, 'incidents'=>$incidents]);
    }

    #[Route('/sensors', name: 'api_smartcity_sensors', methods: ['GET'])] 
    public function getSensors(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $sensors = $this->smartCityService->getSensors();
        return $this->json(['success'=>true, 'sensors'=>$sensors]);
    }
}
