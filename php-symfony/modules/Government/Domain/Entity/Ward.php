<?php

declare(strict_types=1);

namespace App\Government\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'gov_wards')]
class Ward
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 20, unique: true)]
    private string $code;

    #[ORM\Column(type: 'string', length: 100)]
    private string $district;

    #[ORM\Column(type: 'string', length: 100)]
    private string $province;

    #[ORM\Column(name: 'assigned_officer_id', type: 'string', length: 36, nullable: true)]
    private ?string $assignedOfficerId = null;

    #[ORM\Column(type: 'integer')]
    private int $population;

    #[ORM\Column(name: 'area_square_km', type: 'float')]
    private float $areaSquareKm;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct(
        string $name,
        string $code,
        string $district,
        string $province,
        int $population = 0,
        float $areaSquareKm = 0.0
    ) {
        $this->id = Uuid::uuid4()->toString();
        $this->name = $name;
        $this->code = $code;
        $this->district = $district;
        $this->province = $province;
        $this->population = $population;
        $this->areaSquareKm = $areaSquareKm;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getDistrict(): string
    {
        return $this->district;
    }

    public function getProvince(): string
    {
        return $this->province;
    }

    public function getAssignedOfficerId(): ?string
    {
        return $this->assignedOfficerId;
    }

    public function setAssignedOfficerId(?string $officerId): void
    {
        $this->assignedOfficerId = $officerId;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getPopulation(): int
    {
        return $this->population;
    }

    public function setPopulation(int $population): void
    {
        $this->population = $population;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getAreaSquareKm(): float
    {
        return $this->areaSquareKm;
    }

    public function setAreaSquareKm(float $area): void
    {
        $this->areaSquareKm = $area;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'district' => $this->district,
            'province' => $this->province,
            'assignedOfficerId' => $this->assignedOfficerId,
            'population' => $this->population,
            'areaSquareKm' => $this->areaSquareKm,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
