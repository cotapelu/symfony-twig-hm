<?php

declare(strict_types=1);

namespace App\RideHailing\Service;

use App\RideHailing\Entity\Driver;

class DriverService
{
    private \App\RideHailing\Repository\DriverRepository $driverRepository;
    private \App\RideHailing\Repository\VehicleRepository $vehicleRepository;

    public function __construct(
        \App\RideHailing\Repository\DriverRepository $driverRepository,
        \App\RideHailing\Repository\VehicleRepository $vehicleRepository
    ) {
        $this->driverRepository = $driverRepository;
        $this->vehicleRepository = $vehicleRepository;
    }

    public function registerDriver(array $data)
    {
        $driver = new Driver();
        $driver->setUserId($data['userId']);
        $driver->setLicensePlate($data['licensePlate']);
        $driver->setVehicleType($data['vehicleType']);
        $driver->setVehicleBrand($data['vehicleBrand']);
        $driver->setVehicleModel($data['vehicleModel']);
        $driver->setVehicleColor($data['vehicleColor']);
        $driver->setVehicleYear($data['vehicleYear'] ?? null);
        $driver->setStatus(Driver::STATUS_OFFLINE);
        $driver->setCurrentLatitude(0);
        $driver->setCurrentLongitude(0);

        $this->driverRepository->save($driver);

        if (isset($data['vehicle'])) {
            $this->addVehicle($driver, $data['vehicle']);
        }

        return $driver;
    }

    public function addVehicle(Driver $driver, array $data)
    {
        $vehicle = new \App\RideHailing\Entity\Vehicle();
        $vehicle->setDriver($driver);
        $vehicle->setLicensePlate($data['licensePlate']);
        $vehicle->setType($data['type']);
        $vehicle->setBrand($data['brand']);
        $vehicle->setModel($data['model']);
        $vehicle->setColor($data['color']);
        $vehicle->setYear($data['year'] ?? null);
        $vehicle->setImageUrl($data['imageUrl'] ?? null);
        $vehicle->setDefault(true);

        $this->vehicleRepository->save($vehicle);

        return $vehicle;
    }

    public function setOnline(int $driverId, float $latitude, float $longitude)
    {
        $driver = $this->driverRepository->find($driverId);
        if (!$driver) {
            return null;
        }

        $driver->setStatus(Driver::STATUS_ONLINE);
        $driver->setCurrentLatitude($latitude);
        $driver->setCurrentLongitude($longitude);
        $driver->setLastActiveAt(new \DateTime());

        $this->driverRepository->save($driver);

        return $driver;
    }

    public function setOffline(int $driverId)
    {
        $driver = $this->driverRepository->find($driverId);
        if (!$driver) {
            return null;
        }

        $driver->setStatus(Driver::STATUS_OFFLINE);
        $this->driverRepository->save($driver);

        return $driver;
    }

    public function getDriverByUserId(string $userId)
    {
        return $this->driverRepository->findByUserId($userId);
    }

    public function getDriver(int $driverId)
    {
        return $this->driverRepository->find($driverId);
    }

    public function updateBankInfo(int $driverId, array $bankInfo)
    {
        $driver = $this->driverRepository->find($driverId);
        if (!$driver) {
            return null;
        }

        $driver->setBankAccountNumber($bankInfo['accountNumber'] ?? null);
        $driver->setBankName($bankInfo['bankName'] ?? null);
        $driver->setBankAccountName($bankInfo['accountName'] ?? null);

        $this->driverRepository->save($driver);

        return $driver;
    }

    public function verifyDriver(int $driverId)
    {
        $driver = $this->driverRepository->find($driverId);
        if (!$driver) {
            return null;
        }

        $driver->setVerified(true);
        $this->driverRepository->save($driver);

        return $driver;
    }

    public function getDriverStats(int $driverId)
    {
        $driver = $this->driverRepository->find($driverId);
        if (!$driver) {
            return [];
        }

        return [
            'totalRides' => $driver->getTotalRides(),
            'completedRides' => $driver->getCompletedRides(),
            'cancelledRides' => $driver->getCancelledRides(),
            'rating' => $driver->getRating(),
            'status' => $driver->getStatus(),
        ];
    }
}
