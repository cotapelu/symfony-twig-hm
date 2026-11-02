<?php declare(strict_types=1);

namespace Tests\Unit\Investment\Controller;

use App\Investment\Controller\InvestmentController;
use App\Investment\Service\InvestmentService;
use App\Investment\Entity\Investment;
use App\User\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

class InvestmentControllerTest extends TestCase
{
    private InvestmentService $serviceMock;
    private InvestmentController $controller;

    protected function setUp(): void
    {
        $this->serviceMock = $this->createMock(InvestmentService::class);
        $this->controller = new InvestmentController($this->serviceMock);
    }

    private function setAuthenticatedUser(string $userId = '1'): void
    {
        $user = $this->createMock(User::class);
        $user->method('getUserIdentifier')->willReturn($userId);
        $this->controller->setTestUser($user);
        $this->controller->setContainer(new ContainerBuilder());
    }

    private function createInvestmentMock(
        int $id,
        string $userId,
        string $type = 'stock',
        string $name = 'Test Investment',
        float $principal = 1000.0,
        ?DateTimeImmutable $purchaseDate = null,
        ?DateTimeImmutable $maturityDate = null
    ): Investment {
        $inv = $this->createMock(Investment::class);
        $inv->method('getId')->willReturn($id);
        $inv->method('getUserId')->willReturn($userId);
        $inv->method('getInvestmentType')->willReturn($type);
        $inv->method('getName')->willReturn($name);
        $inv->method('getPrincipal')->willReturn($principal);
        $inv->method('getCurrentValue')->willReturn($principal * 1.1);
        $inv->method('getReturnRate')->willReturn(10.0);
        $inv->method('getPurchaseDate')->willReturn($purchaseDate ?? new DateTimeImmutable('-1 month'));
        $inv->method('getMaturityDate')->willReturn($maturityDate ?? new DateTimeImmutable('+1 year'));
        return $inv;
    }

    // ==================== createInvestment ====================

    public function testCreateInvestment_Success(): void
    {
        $this->setAuthenticatedUser('1');
        $investment = $this->createInvestmentMock(1, '1', 'stock', 'My Investment', 5000);
        $this->serviceMock->expects($this->once())
            ->method('createInvestment')
            ->willReturn($investment);

        $request = Request::create('/api/investments', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'investmentType' => 'stock',
            'name' => 'My Investment',
            'principal' => 5000,
            'purchaseDate' => '2025-01-01',
            'maturityDate' => '2026-01-01'
        ]));
        $response = $this->controller->createInvestment($request);
        $this->assertSame(201, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['investmentId']);
        $this->assertSame('stock', $data['type']);
    }

    public function testCreateInvestment_MissingType_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $request = Request::create('/api/investments', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'Test',
            'principal' => 1000
        ]));
        $response = $this->controller->createInvestment($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Investment type is required', $response->getContent());
    }

    public function testCreateInvestment_MissingName_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $request = Request::create('/api/investments', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'investmentType' => 'stock',
            'principal' => 1000
        ]));
        $response = $this->controller->createInvestment($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Investment name is required', $response->getContent());
    }

    public function testCreateInvestment_MissingPrincipal_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $request = Request::create('/api/investments', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'investmentType' => 'stock',
            'name' => 'Test'
        ]));
        $response = $this->controller->createInvestment($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Principal amount is required', $response->getContent());
    }

    public function testCreateInvestment_InvalidPrincipalFormat_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $request = Request::create('/api/investments', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'investmentType' => 'stock',
            'name' => 'Test',
            'principal' => 'not_a_number'
        ]));
        $response = $this->controller->createInvestment($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Principal amount is required', $response->getContent());
    }

    public function testCreateInvestment_InvalidDateFormat_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $request = Request::create('/api/investments', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'investmentType' => 'stock',
            'name' => 'Test',
            'principal' => 1000,
            'purchaseDate' => 'invalid-date',
            'maturityDate' => '2026-01-01'
        ]));
        $response = $this->controller->createInvestment($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid date format', $response->getContent());
    }

    public function testCreateInvestment_InvalidDateOrder_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $request = Request::create('/api/investments', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'investmentType' => 'stock',
            'name' => 'Test',
            'principal' => 1000,
            'purchaseDate' => '2026-01-01',
            'maturityDate' => '2025-01-01'
        ]));
        $response = $this->controller->createInvestment($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Maturity date must be after purchase date', $response->getContent());
    }

    public function testCreateInvestment_Unauthenticated_Returns403(): void
    {
        $this->controller->setContainer(new ContainerBuilder());
        $request = Request::create('/api/investments', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'investmentType' => 'stock',
            'name' => 'Test',
            'principal' => 1000
        ]));
        $response = $this->controller->createInvestment($request);
        $this->assertSame(403, $response->getStatusCode());
    }

    // ==================== getInvestment ====================

    public function testGetInvestment_Success(): void
    {
        $this->setAuthenticatedUser('1');
        $investment = $this->createInvestmentMock(1, '1');
        $this->serviceMock->expects($this->once())
            ->method('getInvestment')
            ->with(1)
            ->willReturn($investment);
        $response = $this->controller->getInvestment(1);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1, $data['id']);
    }

    public function testGetInvestment_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser('1');
        $this->serviceMock->expects($this->once())
            ->method('getInvestment')
            ->with(1)
            ->willReturn(null);
        $response = $this->controller->getInvestment(1);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(['error' => 'Investment not found'], json_decode($response->getContent(), true));
    }

    public function testGetInvestment_Unauthorized_Returns403(): void
    {
        $this->setAuthenticatedUser('1');
        $investment = $this->createInvestmentMock(1, '2'); // different user
        $this->serviceMock->expects($this->once())
            ->method('getInvestment')
            ->with(1)
            ->willReturn($investment);
        $response = $this->controller->getInvestment(1);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(['error' => 'Access denied'], json_decode($response->getContent(), true));
    }

    public function testGetInvestment_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $response = $this->controller->getInvestment(0);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Investment ID must be positive', $response->getContent());
    }

    // ==================== getInvestments ====================

    public function testGetInvestments_Success_ReturnsList(): void
    {
        $this->setAuthenticatedUser('1');
        $inv1 = $this->createInvestmentMock(1, '1', 'stock', 'Inv1');
        $inv2 = $this->createInvestmentMock(2, '1', 'bond', 'Inv2');
        $this->serviceMock->expects($this->once())
            ->method('getUserInvestments')
            ->with('1', null, null)
            ->willReturn([$inv1, $inv2]);

        $request = Request::create('/api/investments', 'GET');
        $response = $this->controller->getInvestments($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data['investments']);
        $this->assertSame(2, $data['total']);
    }

    public function testGetInvestments_WithTypeFilter_ValidatesFilter(): void
    {
        $this->setAuthenticatedUser('1');
        $this->serviceMock->expects($this->once())
            ->method('getUserInvestments')
            ->with('1', 'stock', null)
            ->willReturn([]);

        $request = Request::create('/api/investments', 'GET', ['type' => 'stock']);
        $response = $this->controller->getInvestments($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetInvestments_WithStatusFilter_ValidatesFilter(): void
    {
        $this->setAuthenticatedUser('1');
        $this->serviceMock->expects($this->once())
            ->method('getUserInvestments')
            ->with('1', null, 'active')
            ->willReturn([]);

        $request = Request::create('/api/investments', 'GET', ['status' => 'active']);
        $response = $this->controller->getInvestments($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testGetInvestments_InvalidTypeFilter_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $request = Request::create('/api/investments', 'GET', ['type' => 'invalid']);
        $response = $this->controller->getInvestments($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid investment type filter', $response->getContent());
    }

    public function testGetInvestments_InvalidStatusFilter_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $request = Request::create('/api/investments', 'GET', ['status' => 'invalid']);
        $response = $this->controller->getInvestments($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid status filter', $response->getContent());
    }

    public function testGetInvestments_Unauthenticated_Returns403(): void
    {
        $this->controller->setContainer(new ContainerBuilder());
        $request = Request::create('/api/investments', 'GET');
        $response = $this->controller->getInvestments($request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testGetInvestments_ServiceThrowsException_Returns500(): void
    {
        $this->setAuthenticatedUser('1');
        $this->serviceMock->expects($this->once())
            ->method('getUserInvestments')
            ->with('1', null, null)
            ->will($this->throwException(new \Exception('DB error')));

        $request = Request::create('/api/investments', 'GET');
        $response = $this->controller->getInvestments($request);
        $this->assertSame(500, $response->getStatusCode());
    }

    // ==================== updateInvestmentValue ====================

    public function testUpdateInvestmentValue_Success(): void
    {
        $this->setAuthenticatedUser('1');
        $investment = $this->createInvestmentMock(1, '1', 'stock', 'Inv', 1000);
        // Mock updated investment with exact currentValue
        $updatedInvestment = $this->createMock(Investment::class);
        $updatedInvestment->method('getId')->willReturn(1);
        $updatedInvestment->method('getCurrentValue')->willReturn(1500.0);
        $updatedInvestment->method('getReturnRate')->willReturn(50.0);
        $updatedInvestment->method('getProfit')->willReturn(500.0);
        $this->serviceMock->expects($this->once())
            ->method('getInvestment')
            ->with(1)
            ->willReturn($investment);
        $this->serviceMock->expects($this->once())
            ->method('updateInvestmentValue')
            ->with(1, 1500.0)
            ->willReturn($updatedInvestment);

        $request = Request::create('/api/investments/1/update-value', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['currentValue' => 1500]));
        $response = $this->controller->updateInvestmentValue(1, $request);
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(1500, $data['currentValue']);
        $this->assertSame(50, $data['returnRate']);
        $this->assertSame(500, $data['profit']);
    }

    public function testUpdateInvestmentValue_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $response = $this->controller->updateInvestmentValue(0, Request::create('/api/investments/0/update-value', 'POST'));
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Investment ID must be positive', $response->getContent());
    }

    public function testUpdateInvestmentValue_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser('1');
        $this->serviceMock->expects($this->once())
            ->method('getInvestment')
            ->with(1)
            ->willReturn(null);

        $request = Request::create('/api/investments/1/update-value', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['currentValue' => 1500]));
        $response = $this->controller->updateInvestmentValue(1, $request);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(['error' => 'Investment not found'], json_decode($response->getContent(), true));
    }

    public function testUpdateInvestmentValue_Unauthorized_Returns403(): void
    {
        $this->setAuthenticatedUser('1');
        $investment = $this->createInvestmentMock(1, '2', 'stock', 'Inv', 1000); // different user
        $this->serviceMock->expects($this->once())
            ->method('getInvestment')
            ->with(1)
            ->willReturn($investment);

        $request = Request::create('/api/investments/1/update-value', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['currentValue' => 1500]));
        $response = $this->controller->updateInvestmentValue(1, $request);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(['error' => 'Access denied'], json_decode($response->getContent(), true));
    }

    public function testUpdateInvestmentValue_NegativeValue_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $investment = $this->createInvestmentMock(1, '1');
        $this->serviceMock->expects($this->once())
            ->method('getInvestment')
            ->with(1)
            ->willReturn($investment);

        $request = Request::create('/api/investments/1/update-value', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['currentValue' => -100]));
        $response = $this->controller->updateInvestmentValue(1, $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Current value cannot be negative', $response->getContent());
    }

    public function testUpdateInvestmentValue_MissingCurrentValue_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $investment = $this->createInvestmentMock(1, '1');
        $this->serviceMock->expects($this->once())
            ->method('getInvestment')
            ->with(1)
            ->willReturn($investment);

        $request = Request::create('/api/investments/1/update-value', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([]));
        $response = $this->controller->updateInvestmentValue(1, $request);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateInvestmentValue_UpdateFails_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $investment = $this->createInvestmentMock(1, '1');
        $this->serviceMock->expects($this->once())
            ->method('getInvestment')
            ->with(1)
            ->willReturn($investment);
        $this->serviceMock->expects($this->once())
            ->method('updateInvestmentValue')
            ->with(1, 1500.0)
            ->willReturn(false);

        $request = Request::create('/api/investments/1/update-value', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['currentValue' => 1500]));
        $response = $this->controller->updateInvestmentValue(1, $request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'Failed to update investment value'], json_decode($response->getContent(), true));
    }

    public function testUpdateInvestmentValue_ServiceThrowsException_Returns500(): void
    {
        $this->setAuthenticatedUser('1');
        $investment = $this->createInvestmentMock(1, '1');
        $this->serviceMock->expects($this->once())
            ->method('getInvestment')
            ->with(1)
            ->willReturn($investment);
        $this->serviceMock->expects($this->once())
            ->method('updateInvestmentValue')
            ->with(1, 1500.0)
            ->will($this->throwException(new \Exception('DB error')));

        $request = Request::create('/api/investments/1/update-value', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['currentValue' => 1500]));
        $response = $this->controller->updateInvestmentValue(1, $request);
        $this->assertSame(500, $response->getStatusCode());
    }

    // ==================== markAsMature ====================

    public function testMarkAsMature_Success(): void
    {
        $this->setAuthenticatedUser('1');
        $investment = $this->createInvestmentMock(1, '1', 'stock', 'Inv', 1000);
        $this->serviceMock->expects($this->once())
            ->method('getInvestment')
            ->with(1)
            ->willReturn($investment);
        $this->serviceMock->expects($this->once())
            ->method('markAsMature')
            ->with(1)
            ->willReturn(true);

        $response = $this->controller->markAsMature(1);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['success' => true, 'message' => 'Investment marked as matured'], json_decode($response->getContent(), true));
    }

    public function testMarkAsMature_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $response = $this->controller->markAsMature(0);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Investment ID must be positive', $response->getContent());
    }

    public function testMarkAsMature_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser('1');
        $this->serviceMock->expects($this->once())
            ->method('getInvestment')
            ->with(1)
            ->willReturn(null);

        $response = $this->controller->markAsMature(1);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(['error' => 'Investment not found'], json_decode($response->getContent(), true));
    }

    public function testMarkAsMature_Unauthorized_Returns403(): void
    {
        $this->setAuthenticatedUser('1');
        $investment = $this->createInvestmentMock(1, '2');
        $this->serviceMock->expects($this->once())
            ->method('getInvestment')
            ->with(1)
            ->willReturn($investment);

        $response = $this->controller->markAsMature(1);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(['error' => 'Access denied'], json_decode($response->getContent(), true));
    }

    public function testMarkAsMature_Fails_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $investment = $this->createInvestmentMock(1, '1');
        $this->serviceMock->expects($this->once())
            ->method('getInvestment')
            ->with(1)
            ->willReturn($investment);
        $this->serviceMock->expects($this->once())
            ->method('markAsMature')
            ->with(1)
            ->willReturn(false);

        $response = $this->controller->markAsMature(1);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'Failed to mark investment as matured'], json_decode($response->getContent(), true));
    }

    public function testMarkAsMature_ServiceThrowsException_Returns500(): void
    {
        $this->setAuthenticatedUser('1');
        $investment = $this->createInvestmentMock(1, '1');
        $this->serviceMock->expects($this->once())
            ->method('getInvestment')
            ->with(1)
            ->willReturn($investment);
        $this->serviceMock->expects($this->once())
            ->method('markAsMature')
            ->with(1)
            ->will($this->throwException(new \Exception('DB error')));

        $response = $this->controller->markAsMature(1);
        $this->assertSame(500, $response->getStatusCode());
    }

    // ==================== deleteInvestment ====================

    public function testDeleteInvestment_Success_Returns204(): void
    {
        $this->setAuthenticatedUser('1');
        $investment = $this->createInvestmentMock(1, '1');
        $this->serviceMock->expects($this->once())
            ->method('getInvestment')
            ->with(1)
            ->willReturn($investment);
        $this->serviceMock->expects($this->once())
            ->method('deleteInvestment')
            ->with(1)
            ->willReturn(true);

        $response = $this->controller->deleteInvestment(1);
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testDeleteInvestment_InvalidId_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $response = $this->controller->deleteInvestment(0);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Investment ID must be positive', $response->getContent());
    }

    public function testDeleteInvestment_NotFound_Returns404(): void
    {
        $this->setAuthenticatedUser('1');
        $this->serviceMock->expects($this->once())
            ->method('getInvestment')
            ->with(1)
            ->willReturn(null);

        $response = $this->controller->deleteInvestment(1);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(['error' => 'Investment not found'], json_decode($response->getContent(), true));
    }

    public function testDeleteInvestment_Unauthorized_Returns403(): void
    {
        $this->setAuthenticatedUser('1');
        $investment = $this->createInvestmentMock(1, '2');
        $this->serviceMock->expects($this->once())
            ->method('getInvestment')
            ->with(1)
            ->willReturn($investment);

        $response = $this->controller->deleteInvestment(1);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(['error' => 'Access denied'], json_decode($response->getContent(), true));
    }

    public function testDeleteInvestment_Fails_Returns400(): void
    {
        $this->setAuthenticatedUser('1');
        $investment = $this->createInvestmentMock(1, '1');
        $this->serviceMock->expects($this->once())
            ->method('getInvestment')
            ->with(1)
            ->willReturn($investment);
        $this->serviceMock->expects($this->once())
            ->method('deleteInvestment')
            ->with(1)
            ->willReturn(false);

        $response = $this->controller->deleteInvestment(1);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'Failed to delete investment'], json_decode($response->getContent(), true));
    }

    public function testDeleteInvestment_ServiceThrowsException_Returns500(): void
    {
        $this->setAuthenticatedUser('1');
        $investment = $this->createInvestmentMock(1, '1');
        $this->serviceMock->expects($this->once())
            ->method('getInvestment')
            ->with(1)
            ->willReturn($investment);
        $this->serviceMock->expects($this->once())
            ->method('deleteInvestment')
            ->with(1)
            ->will($this->throwException(new \Exception('DB error')));

        $response = $this->controller->deleteInvestment(1);
        $this->assertSame(500, $response->getStatusCode());
    }

    // ==================== getInvestmentSummary ====================

    public function testGetInvestmentSummary_Success(): void
    {
        $this->setAuthenticatedUser('1');
        $summary = [
            'totalInvested' => 5000,
            'totalCurrentValue' => 5500,
            'totalReturn' => 500,
            'averageReturnRate' => 10,
        ];
        $this->serviceMock->expects($this->once())
            ->method('getUserInvestmentSummary')
            ->with('1')
            ->willReturn($summary);

        $request = Request::create('/api/investments/summary', 'GET');
        $response = $this->controller->getInvestmentSummary($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($summary, json_decode($response->getContent(), true));
    }

    public function testGetInvestmentSummary_Unauthenticated_Returns403(): void
    {
        $this->controller->setContainer(new ContainerBuilder());
        $request = Request::create('/api/investments/summary', 'GET');
        $response = $this->controller->getInvestmentSummary($request);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testGetInvestmentSummary_ServiceThrowsException_Returns500(): void
    {
        $this->setAuthenticatedUser('1');
        $this->serviceMock->expects($this->once())
            ->method('getUserInvestmentSummary')
            ->with('1')
            ->will($this->throwException(new \Exception('DB error')));

        $request = Request::create('/api/investments/summary', 'GET');
        $response = $this->controller->getInvestmentSummary($request);
        $this->assertSame(500, $response->getStatusCode());
    }

    // ==================== getInvestmentTypes ====================

    public function testGetInvestmentTypes_ReturnsAllTypes(): void
    {
        $this->controller->setContainer(new ContainerBuilder());
        $request = Request::create('/api/investments/types', 'GET');
        $response = $this->controller->getInvestmentTypes();
        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('types', $data);
        $this->assertSame(8, count($data['types']));
        $this->assertArrayHasKey('stock', $data['types']);
        $this->assertSame('Stock', $data['types']['stock']);
    }
}

