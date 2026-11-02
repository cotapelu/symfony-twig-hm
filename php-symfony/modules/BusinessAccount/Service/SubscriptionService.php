<?php

declare(strict_types=1);

namespace App\BusinessAccount\Service;

use App\BusinessAccount\Entity\Business;
use App\BusinessAccount\Entity\Subscription;
use App\BusinessAccount\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;

class SubscriptionService
{
    private EntityManagerInterface $em;
    private SubscriptionRepository $subscriptionRepo;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        /** @var SubscriptionRepository $subscriptionRepo */
        $subscriptionRepo = $em->getRepository(Subscription::class);
        $this->subscriptionRepo = $subscriptionRepo;
    }

    public function createSubscription(Business $business, string $plan, int $durationMonths)
    {
        $subscription = new Subscription();
        $subscription->setBusiness($business);
        $subscription->setPlan($plan);
        $subscription->setStatus('active');
        $subscription->setStartedAt(new \DateTime());
        $subscription->setExpiresAt(new \DateTime('+' . $durationMonths . ' months'));

        $prices = ['basic' => 10, 'professional' => 30, 'enterprise' => 100];
        $subscription->setPrice($prices[$plan] ?? 10);

        $this->em->persist($subscription);
        $this->em->flush();

        return $subscription;
    }

    public function renewSubscription(Subscription $subscription, int $months)
    {
        if ($subscription->getExpiresAt() < new \DateTime()) {
            $subscription->setStartedAt(new \DateTime());
        }
        $subscription->setExpiresAt(clone $subscription->getExpiresAt());
        $subscription->setExpiresAt($subscription->getExpiresAt()->add(new \DateInterval('P' . $months . 'M')));
        $subscription->setStatus('active');

        $this->em->flush();

        return $subscription;
    }

    public function cancelSubscription(Subscription $subscription)
    {
        $subscription->setStatus('cancelled');
        $this->em->flush();

        return $subscription;
    }

    public function getActiveSubscription(Business $business)
    {
        return $this->subscriptionRepo->findActiveByBusiness($business->getId());
    }

    public function hasFeature(Business $business, string $feature)
    {
        $subscription = $this->getActiveSubscription($business);
        if (!$subscription) {
            return false;
        }

        $features = [
            'basic' => ['catalog', 'basic_analytics', 'api_access'],
            'professional' => ['catalog', 'basic_analytics', 'api_access', 'advanced_analytics', 'priority_support', 'custom_branding'],
            'enterprise' => ['catalog', 'basic_analytics', 'api_access', 'advanced_analytics', 'priority_support', 'custom_branding', 'multi_user', 'webhooks', 'white_label']
        ];

        $planFeatures = $features[$subscription->getPlan()] ?? [];

        return in_array($feature, $planFeatures, true);
    }
}
