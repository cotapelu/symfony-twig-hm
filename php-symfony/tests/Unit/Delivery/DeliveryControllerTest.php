<?php

declare(strict_types=1);

namespace App\Tests\Unit\Delivery;

use App\Controller\Delivery\DeliveryController;
use App\Entity\Php\DeliveryOrder;
use App\Repository\Php\DeliveryOrderRepository;
use App\Service\Security\OwnershipChecker;
use App\Tests\Unit\TestUser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class DeliveryControllerTest extends TestCase
{
    private DeliveryOrderRepository $orderRepositoryMock;
    private OwnershipChecker $ownershipCheckerMock;
    private DeliveryController $controller;

    protected function setUp(): void
    {
        $this->orderRepositoryMock = $this->createMock(DeliveryOrderRepository::class);
        $this->ownershipCheckerMock = $this->createMock(OwnershipChecker::class);
        $this->controller = new DeliveryController(
            $this->orderRepositoryMock,
            $this->ownershipCheckerMock
        );
    }

    #[Test]
    public function it_allows_admin_role_to_access_order(): void
    {
        $user = new TestUser('admin-1', ['ROLE_ADMIN']);
        $order = $this->createOrder('order-1', 'customer-1', 'courier-1');

        $this->orderRepositoryMock->method('find')->with('order-1')->willReturn($order);
        $this->ownershipCheckerMock->method('isOwner')->willReturnCallback(
            fn(string $ownerId, string $currentUserId) => $ownerId === $currentUserId
        );

        $request = Request::create('/api/delivery/orders', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['orderId' => 'order-1']));

        $this->setAuthenticatedUser($user);
        $response = $this->controller->createOrder($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    #[Test]
    public function it_allows_customer_to_access_own_order(): void
    {
        $userId = 'user-123';
        $orderId = 'order-1';
        $order = $this->createOrder($orderId, $userId, 'courier-1');

        $this->orderRepositoryMock->method('find')->with($orderId)->willReturn($order);
        $this->ownershipCheckerMock->method('isOwner')->willReturnCallback(
            fn(string $ownerId, string $currentUserId) => $ownerId === $currentUserId
        );

        $user = new TestUser($userId, ['ROLE_USER']);
        $this->setAuthenticatedUser($user);
        $response = $this->controller->getOrder($orderId);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($orderId, $data['id']);
    }

    #[Test]
    public function it_denies_access_for_other_user_order(): void
    {
        $orderId = 'order-1';
        $order = $this->createOrder($orderId, 'customer-1', 'courier-1');

        $this->orderRepositoryMock->method('find')->with($orderId)->willReturn($order);
        $this->ownershipCheckerMock->method('isOwner')->willReturn(false);

        $user = new TestUser('user-999', ['ROLE_USER']);
        $this->setAuthenticatedUser($user);
        $response = $this->controller->getOrder($orderId);

        $this->assertSame(403, $response->getStatusCode());
    }

    #[Test]
    public function it_denies_access_when_order_not_found(): void
    {
        $orderId = 'non-existent';

        $this->orderRepositoryMock->method('find')->with($orderId)->willReturn(null);

        $user = new TestUser('user-1', ['ROLE_USER']);
        $this->setAuthenticatedUser($user);

        $response = $this->controller->getOrder($orderId);
        $this->assertSame(403, $response->getStatusCode());
    }

    #[Test]
    public function it_returns_bad_request_for_invalid_json(): void
    {
        $user = new TestUser('user-1', ['ROLE_USER']);
        $this->setAuthenticatedUser($user);

        $request = Request::create('/api/delivery/orders', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], 'invalid-json{');

        $response = $this->controller->createOrder($request);
        $this->assertSame(400, $response->getStatusCode());
    }

    private function createOrder(string $id, string $customerId, ?string $deliveryPersonId): DeliveryOrder
    {
        $order = $this->createMock(DeliveryOrder::class);
        $order->method('getId')->willReturn($id);
        $order->method('getCustomerId')->willReturn($customerId);
        $order->method('getDeliveryPersonId')->willReturn($deliveryPersonId);
        return $order;
    }

    private function setAuthenticatedUser(TestUser $user): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $property = $reflection->getProperty('testUser');
        $property->setAccessible(true);
        $property->setValue($this->controller, $user);
    }
}
