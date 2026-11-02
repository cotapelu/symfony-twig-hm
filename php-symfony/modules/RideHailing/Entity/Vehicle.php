<?php

declare(strict_types=1);

namespace App\RideHailing\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: App\RideHailing\Repository\VehicleRepository::class)]
#[ORM\Table(name: "ride_hailing_vehicles")]
class Vehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Driver::class)]
    #[ORM\JoinColumn(name: "driver_id", referencedColumnName: "id")]
    private Driver $driver;

    #[ORM\Column(type: "string", length: 50)]
    private string $licensePlate;

    #[ORM\Column(type: "string", length: 50)]
    private string $type;

    #[ORM\Column(type: "string", length: 100)]
    private string $brand;

    #[ORM\Column(type: "string", length: 100)]
    private string $model;

    #[ORM\Column(type: "string", length: 50)]
    private string $color;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $year;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $imageUrl;

    #[ORM\Column(type: "boolean")]
    private bool $isDefault = true;

    #[ORM\Column(type: "boolean")]
    private bool $isVerified = false;

    #[ORM\Column(type: "datetime")]
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }
    /**
     * @ORM\PrePersist
     */
    public function setId(int $id)
    {
        $this->id = $id;
        return $this;
    }


    public function getDriver()
    {
        return $this->driver;
    }

    public function setDriver(Driver $driver)
    {
        $this->driver = $driver;
        return $this;
    }

    public function getLicensePlate()
    {
        return $this->licensePlate;
    }

    public function setLicensePlate(string $licensePlate)
    {
        $this->licensePlate = $licensePlate;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType(string $type)
    {
        $this->type = $type;
        return $this;
    }

    public function getBrand()
    {
        return $this->brand;
    }

    public function setBrand(string $brand)
    {
        $this->brand = $brand;
        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function setModel(string $model)
    {
        $this->model = $model;
        return $this;
    }

    public function getColor()
    {
        return $this->color;
    }

    public function setColor(string $color)
    {
        $this->color = $color;
        return $this;
    }

    public function getYear()
    {
        return $this->year;
    }

    public function setYear(?int $year)
    {
        $this->year = $year;
        return $this;
    }

    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl)
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function isDefault()
    {
        return $this->isDefault;
    }

    public function setDefault(bool $isDefault)
    {
        $this->isDefault = $isDefault;
        return $this;
    }

    public function isVerified()
    {
        return $this->isVerified;
    }

    public function setVerified(bool $isVerified)
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
