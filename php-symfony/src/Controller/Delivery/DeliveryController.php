<?php

declare(strict_types=1);

namespace App\Controller\Delivery;

use App\Entity\Php\DeliveryOrder;
use App\Repository\Php\DeliveryOrderRepository;
use App\Service\Security\OwnershipChecker;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use InvalidArgumentException;

#[Route('/api/delivery', name: 'api_delivery_')]
#[IsGranted('ROLE_USER')]
final class DeliveryController extends BaseController
{
    private const ALLOWED_ROLES = ['ROLE_OFFICER', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'];

    public function __construct(
        private DeliveryOrderRepository $orderRepository,
        private OwnershipChecker $ownershipChecker
    ) {
        parent::__construct();
    }

    private function getJsonData(Request $request): array
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            throw new InvalidArgumentException('Invalid JSON payload');
        }
        return $data;
    }

    private function handle(callable $action): JsonResponse
    {
        try {
            return $action();
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (AccessDeniedHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (\Throwable $e) {
            return $this->handleException('delivery', $e);
        }
    }

    private function handleException(string $context, \Throwable $e): JsonResponse
    {
        return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function assertOrderAccess(string $orderId): void
    {
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('Authentication required');
        }

        if ($this->hasAllowedRole($user)) {
            return;
        }

        $order = $this->orderRepository->find($orderId);
        if (!$order instanceof DeliveryOrder) {
            throw new AccessDeniedHttpException('Order not found');
        }

        $currentUserId = $user->getId();
        $isCustomer = $this->ownershipChecker->isOwner($order->getCustomerId(), $currentUserId);
        $isCourier = $order->getDeliveryPersonId() !== null
            && $this->ownershipChecker->isOwner($order->getDeliveryPersonId(), $currentUserId);

        if (!$isCustomer && !$isCourier) {
            throw new AccessDeniedHttpException('Access denied: Not your order');
        }
    }

    private function hasAllowedRole(object $user): bool
    {
        foreach (self::ALLOWED_ROLES as $role) {
            if (in_array($role, $user->getRoles(), true)) {
                return true;
            }
        }
        return false;
    }

    #[Route('/orders', name: 'create_order', methods: ['POST'])]
    public function createOrder(Request $request): JsonResponse
    {
        return $this->handle(fn() => $this->createOrderAction($request));
    }

    private function createOrderAction(Request $request): JsonResponse
    {
        $data = $this->getJsonData($request);
        $orderId = $data['orderId'] ?? '';
        if (empty($orderId)) {
            throw new InvalidArgumentException('orderId is required');
        }

        return $this->json([
            'id' => uniqid(),
            'orderId' => $orderId,
            'status' => 'pending',
            'courierId' => null
        ], Response::HTTP_CREATED);
    }

    #[Route('/orders/{id}', name: 'get_order', methods: ['GET'])]
    public function getOrder(string $id): JsonResponse
    {
        return $this->handle(fn() => $this->getOrderAction($id));
    }

    private function getOrderAction(string $id): JsonResponse
    {
        $this->assertOrderAccess($id);
        return $this->json([
            'id' => $id,
            'status' => 'in_transit',
            'courier' => ['name' => 'John Doe']
        ]);
    }

    #[Route('/couriers/available', name: 'available_couriers', methods: ['GET'])]
    public function availableCouriers(): JsonResponse
    {
        return $this->json([
            'couriers' => [
                ['id' => '1', 'name' => 'John Doe', 'rating' => 4.8],
                ['id' => '2', 'name' => 'Jane Smith', 'rating' => 4.9]
            ]
        ]);
    }
}
