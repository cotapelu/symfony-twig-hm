<?php

declare(strict_types=1);

namespace App\Subscription\Service;

use App\Subscription\Entity\Subscription;
use App\Subscription\Entity\SubscriptionPlan;
use App\Subscription\Repository\SubscriptionRepository;
use App\Subscription\Repository\SubscriptionPlanRepository;
use App\Shared\EventBus\EventBus;
use App\Subscription\Event\SubscriptionCreatedEvent;
use App\Subscription\Event\SubscriptionExpiredEvent;
use App\User\Repository\UserRepository;

class SubscriptionService
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepository,
        private SubscriptionPlanRepository $planRepository,
        private EventBus $eventBus,
        private UserRepository $userRepository,
    ) {
    }

    public function createSubscription(string $userId, string $planId, array $options = [])
    {
        $plan = $this->planRepository->find($planId);

        if (!$plan) {
            throw new \InvalidArgumentException('Plan not found');
        }

        $subscription = new Subscription(
            user: $this->userRepository->find($userId),
            planId: $planId,
            amount: (float) $plan->getPrice(),
            currency: $plan->getCurrency(),
            features: $plan->getFeatures(),
            autoRenew: $options['auto_renew'] ?? true,
        );

        $this->subscriptionRepository->save($subscription);

        $event = new SubscriptionCreatedEvent(
            subscriptionId: $subscription->getId(),
            userId: $userId,
            planId: $planId,
            amount: $subscription->getAmount(),
            createdAt: $subscription->getCreatedAt(),
        );

        $this->eventBus->dispatch($event);

        return $subscription;
    }

    public function cancelSubscription(string $subscriptionId)
    {
        $subscription = $this->subscriptionRepository->find($subscriptionId);

        if (!$subscription) {
            throw new \InvalidArgumentException('Subscription not found');
        }

        $subscription->setStatus('cancelled');
        $subscription->setEndedAt(new \DateTime());
        $this->subscriptionRepository->save($subscription);

        $event = new SubscriptionExpiredEvent(
            subscriptionId: $subscriptionId,
            userId: $subscription->getUser()->getId(),
            planId: $subscription->getPlanId(),
            expiredAt: $subscription->getEndedAt(),
        );

        $this->eventBus->dispatch($event);
    }

    public function renewSubscription(string $subscriptionId)
    {
        $subscription = $this->subscriptionRepository->find($subscriptionId);

        if (!$subscription) {
            throw new \InvalidArgumentException('Subscription not found');
        }

        $subscription->setStatus('active');
        $this->subscriptionRepository->save($subscription);
    }

    public function getSubscription(string $subscriptionId)
    {
        return $this->subscriptionRepository->find($subscriptionId);
    }

    public function getUserSubscription(string $userId)
    {
        return $this->subscriptionRepository->findActiveByUser($userId);
    }

    public function getAllPlans()
    {
        return $this->planRepository->findAllActive();
    }

    public function createPlan(array $data)
    {
        // Validate price is non-negative
        if (!isset($data['price'])) {
            throw new \InvalidArgumentException('Price is required');
        }
        $price = (float) $data['price'];
        if ($price < 0) {
            throw new \InvalidArgumentException('Price must be positive');
        }

        $plan = new SubscriptionPlan(
            id: $data['id'],
            name: $data['name'],
            price: $data['price'],
            description: $data['description'] ?? null,
            durationDays: $data['duration_days'] ?? 30,
            maxMembers: $data['max_members'] ?? 100,
            maxFileSize: $data['max_file_size'] ?? 100,
            maxMessages: $data['max_messages'] ?? 10000,
            features: $data['features'] ?? [],
        );

        $this->planRepository->save($plan);

        return $plan;
    }

    public function updatePlan(string $id, array $data)
    {
        $plan = $this->planRepository->find($id);
        if (!$plan) {
            throw new \InvalidArgumentException('Plan not found');
        }
        // Update fields if provided
        if (isset($data['name'])) {
            $plan->setName($data['name']);
        }
        if (isset($data['price'])) {
            $plan->setPrice((float)$data['price']);
        }
        if (isset($data['description'])) {
            $plan->setDescription($data['description']);
        }
        if (isset($data['duration_days'])) {
            $plan->setDurationDays((int)$data['duration_days']);
        }
        if (isset($data['max_members'])) {
            $plan->setMaxMembers((int)$data['max_members']);
        }
        if (isset($data['max_file_size'])) {
            $plan->setMaxFileSize((int)$data['max_file_size']);
        }
        if (isset($data['max_messages'])) {
            $plan->setMaxMessages((int)$data['max_messages']);
        }
        if (isset($data['features'])) {
            $plan->setFeatures($data['features']);
        }
        $this->planRepository->save($plan);
        return $plan;
    }

    public function deletePlan(string $id): void
    {
        $plan = $this->planRepository->find($id);
        if (!$plan) {
            throw new \InvalidArgumentException('Plan not found');
        }
        $this->planRepository->delete($plan);
    }
}
