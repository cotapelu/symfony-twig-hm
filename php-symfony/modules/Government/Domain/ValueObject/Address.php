<?php

declare(strict_types=1);

namespace App\Government\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Embeddable]
class Address
{
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $province = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $district = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $ward = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $street = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $apartmentNumber = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $fullAddress = null;

    public function __construct(
        string $province,
        string $district,
        string $ward,
        string $street,
        ?string $apartmentNumber = null
    ) {
        if (empty(trim($province))) {
            throw new InvalidArgumentException('Province cannot be empty');
        }
        if (empty(trim($district))) {
            throw new InvalidArgumentException('District cannot be empty');
        }
        if (empty(trim($ward))) {
            throw new InvalidArgumentException('Ward cannot be empty');
        }
        if (empty(trim($street))) {
            throw new InvalidArgumentException('Street cannot be empty');
        }

        $this->province = $province;
        $this->district = $district;
        $this->ward = $ward;
        $this->street = $street;
        $this->apartmentNumber = $apartmentNumber;
        $this->fullAddress = $this->buildFullAddress();
    }

    private function buildFullAddress(): string
    {
        $parts = [];

        if ($this->apartmentNumber) {
            $parts[] = $this->apartmentNumber;
        }
        $parts[] = $this->street;
        $parts[] = 'Phường/Xã ' . $this->ward;
        $parts[] = 'Quận/Huyện ' . $this->district;
        $parts[] = 'Tỉnh/TP ' . $this->province;

        return implode(', ', $parts);
    }

    public function getProvince(): string
    {
        return $this->province;
    }

    public function getDistrict(): string
    {
        return $this->district;
    }

    public function getWard(): string
    {
        return $this->ward;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getApartmentNumber(): ?string
    {
        return $this->apartmentNumber;
    }

    public function getFullAddress(): string
    {
        return $this->fullAddress;
    }

    public function equals(Address $other): bool
    {
        return $this->province === $other->province
            && $this->district === $other->district
            && $this->ward === $other->ward
            && $this->street === $other->street
            && $this->apartmentNumber === $other->apartmentNumber;
    }

    public function toArray(): array
    {
        return [
            'province' => $this->province,
            'district' => $this->district,
            'ward' => $this->ward,
            'street' => $this->street,
            'apartmentNumber' => $this->apartmentNumber,
            'fullAddress' => $this->fullAddress,
        ];
    }

    public function __toString(): string
    {
        return $this->fullAddress;
    }
}
