<?php

declare(strict_types=1);

namespace App\RideHailing\Service;

use App\RideHailing\Entity\Ride;
use App\RideHailing\Entity\Driver;
use App\RideHailing\Entity\Payment;
use App\RideHailing\Repository\RideRepository;
use App\RideHailing\Repository\DriverRepository;
use App\RideHailing\Repository\PaymentRepository;
use Doctrine\DBAL\LockMode;

class RideHailingService
{
    private RideRepository $rideRepository;
    private DriverRepository $driverRepository;
    private PaymentRepository $paymentRepository;

    private const COMMISSION_RATE = 0.20;

    public function __construct(
        RideRepository $rideRepository,
        DriverRepository $driverRepository,
        PaymentRepository $paymentRepository
    ) {
        $this->rideRepository = $rideRepository;
        $this->driverRepository = $driverRepository;
        $this->paymentRepository = $paymentRepository;
    }

    public function requestRide(array $data)
    {
        $ride = new Ride();
        $ride->setRiderId($data['riderId']);
        $ride->setRideType($data['rideType'] ?? Ride::TYPE_STANDARD);
        $ride->setPickupLatitude((float) $data['pickupLatitude']);
        $ride->setPickupLongitude((float) $data['pickupLongitude']);
        $ride->setPickupAddress($data['pickupAddress']);
        $ride->setDropoffLatitude(isset($data['dropoffLatitude']) ? (float) $data['dropoffLatitude'] : null);
        $ride->setDropoffLongitude(isset($data['dropoffLongitude']) ? (float) $data['dropoffLongitude'] : null);
        $ride->setDropoffAddress($data['dropoffAddress'] ?? null);
        $ride->setEstimatedPrice((float) $data['estimatedPrice']);
        $ride->setEstimatedDuration((int) $data['estimatedDuration']);
        $ride->setDistance((int) ($data['distance'] ?? 0));
        $ride->setRiderNotes($data['notes'] ?? null);
        $ride->setStatus(Ride::STATUS_REQUESTED);

        $this->rideRepository->save($ride);

        return $ride;
    }

    public function acceptRide(int $rideId, int $driverId): ?Ride
    {
        $em = $this->rideRepository->getEntityManager();
        $em->beginTransaction();
        try {
            $ride = $this->acquireRideLock($rideId);
            if (!$ride) {
                $em->rollback();
                return null;
            }
            $driver = $this->acquireDriverLock($driverId);
            if (!$driver) {
                $em->rollback();
                return null;
            }
            $this->applyAcceptance($ride, $driver);
            $em->flush();
            $em->commit();
            return $ride;
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
    }

    private function acquireRideLock(int $rideId): ?Ride
    {
        $em = $this->rideRepository->getEntityManager();
        $query = $em->createQueryBuilder()->select('r')->from(Ride::class, 'r')->where('r.id = :rideId')->setParameter('rideId', $rideId);
        return $query->getQuery()->setLockMode(LockMode::PESSIMISTIC_WRITE)->getOneOrNullResult();
    }

    private function acquireDriverLock(int $driverId): ?Driver
    {
        $driver = $this->driverRepository->find($driverId);
        if (!$driver || !$driver->isAvailable()) {
            return null;
        }
        $em = $this->driverRepository->getEntityManager();
        $em->lock($driver, LockMode::PESSIMISTIC_WRITE);
        return $driver;
    }

    private function applyAcceptance(Ride $ride, Driver $driver): void
    {
        $ride->setDriver($driver);
        $ride->setStatus(Ride::STATUS_ACCEPTED);
        $ride->setAcceptedAt(new \DateTime());
        $driver->setStatus(Driver::STATUS_BUSY);
    }

    public function driverArrived(int $rideId)
    {
        $ride = $this->rideRepository->find($rideId);
        if (!$ride || $ride->getStatus() !== Ride::STATUS_ACCEPTED) {
            return null;
        }

        $ride->setStatus(Ride::STATUS_ARRIVED);
        $ride->setArrivedAt(new \DateTime());

        $this->rideRepository->save($ride);

        return $ride;
    }

    public function startRide(int $rideId)
    {
        $ride = $this->rideRepository->find($rideId);
        if (!$ride || $ride->getStatus() !== Ride::STATUS_ARRIVED) {
            return null;
        }

        $ride->setStatus(Ride::STATUS_IN_PROGRESS);
        $ride->setStartedAt(new \DateTime());

        $this->rideRepository->save($ride);

        return $ride;
    }

    public function completeRide(int $rideId, float $actualPrice, int $actualDuration, int $distance): ?Ride
    {
        $ride = $this->rideRepository->find($rideId);
        if (!$ride || $ride->getStatus() !== Ride::STATUS_IN_PROGRESS) {
            return null;
        }

        $this->finalizeRide($ride, $actualPrice, $actualDuration, $distance);
        $this->updateDriverStats($ride);

        return $ride;
    }

    private function finalizeRide(Ride $ride, float $actualPrice, int $actualDuration, int $distance): void
    {
        $ride->setStatus(Ride::STATUS_COMPLETED);
        $ride->setCompletedAt(new \DateTime());
        $ride->setActualPrice($actualPrice);
        $ride->setActualDuration($actualDuration);
        $ride->setDistance($distance);

        $payment = $this->createPayment($ride, $actualPrice);
        $this->paymentRepository->save($payment);
        $ride->setPayment($payment);

        $this->rideRepository->save($ride);
    }

    private function createPayment(Ride $ride, float $actualPrice): Payment
    {
        $payment = new Payment();
        $payment->setRide($ride);
        $payment->setAmount($actualPrice);
        $payment->setCommissionAmount($actualPrice * self::COMMISSION_RATE);
        $payment->setDriverEarning($actualPrice * (1 - self::COMMISSION_RATE));
        $payment->setPaymentMethod($ride->getPayment()?->getPaymentMethod() ?? Payment::METHOD_CASH);
        return $payment;
    }

    private function updateDriverStats(Ride $ride): void
    {
        if ($ride->getDriver()) {
            $driver = $ride->getDriver();
            $driver->setStatus(Driver::STATUS_ONLINE);
            $driver->setCompletedRides($driver->getCompletedRides() + 1);
            $this->driverRepository->save($driver);
        }
    }

    public function cancelRide(int $rideId, string $reason)
    {
        $ride = $this->rideRepository->find($rideId);
        if (!$ride || !$ride->isActive()) {
            return null;
        }

        $ride->setStatus(Ride::STATUS_CANCELLED);

        $this->rideRepository->save($ride);

        if ($ride->getDriver()) {
            $driver = $ride->getDriver();
            $driver->setStatus(Driver::STATUS_ONLINE);
            $driver->setCancelledRides($driver->getCancelledRides() + 1);
            $this->driverRepository->save($driver);
        }

        return $ride;
    }

    public function findNearbyDrivers(float $latitude, float $longitude, float $radius = 5.0, ?string $vehicleType = null)
    {
        return $this->driverRepository->findAvailableDrivers($latitude, $longitude, $radius, $vehicleType);
    }

    public function rateRide(int $rideId, float $driverRating, ?string $review, ?float $riderRating = null)
    {
        $ride = $this->rideRepository->find($rideId);
        if (!$ride || !$ride->isCompleted()) {
            return null;
        }

        $ride->setDriverRating($driverRating);
        $ride->setDriverReview($review);

        if ($riderRating !== null) {
            $ride->setRiderRating($riderRating);
        }

        $this->rideRepository->save($ride);

        if ($ride->getDriver()) {
            $this->updateDriverRating($ride->getDriver());
        }

        return $ride;
    }

    private function updateDriverRating(Driver $driver)
    {
        $completedRides = $this->rideRepository->findBy(['driver' => $driver, 'status' => Ride::STATUS_COMPLETED]);

        if (empty($completedRides)) {
            return;
        }

        $totalRating = array_sum(array_map(fn(Ride $r) => (float) $r->getDriverRating(), $completedRides));
        $driver->setRating($totalRating / count($completedRides));

        $this->driverRepository->save($driver);
    }

    public function getRideHistory(string $userId)
    {
        return $this->rideRepository->findCompletedRidesByUser($userId);
    }

    public function getActiveRide(string $userId)
    {
        $rides = $this->rideRepository->findActiveRidesByRider($userId);
        return $rides[0] ?? null;
    }

    public function getDriverActiveRide(int $driverId)
    {
        $rides = $this->rideRepository->findActiveRidesByDriver((string) $driverId);
        return $rides[0] ?? null;
    }

    public function updateDriverLocation(int $driverId, float $latitude, float $longitude)
    {
        $driver = $this->driverRepository->find($driverId);
        if ($driver) {
            $driver->setCurrentLatitude($latitude);
            $driver->setCurrentLongitude($longitude);
            $driver->setLastActiveAt(new \DateTime());
            $this->driverRepository->save($driver);
        }
    }
}
