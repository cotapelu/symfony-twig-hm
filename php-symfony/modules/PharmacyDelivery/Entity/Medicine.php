<?php

declare(strict_types=1);

namespace App\PharmacyDelivery\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: App\PharmacyDelivery\Repository\MedicineRepository::class)]
#[ORM\Table(name: "pharmacy_delivery_medicines")]
class Medicine
{
    public const CATEGORY_TABLET = 'tablet';
    public const CATEGORY_CAPSULE = 'capsule';
    public const CATEGORY_SYRUP = 'syrup';
    public const CATEGORY_INJECTION = 'injection';
    public const CATEGORY_CREAM = 'cream';
    public const CATEGORY_DROPS = 'drops';
    public const CATEGORY_INHALER = 'inhaler';
    public const CATEGORY_OTHER = 'other';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Pharmacy::class)]
    #[ORM\JoinColumn(name: "pharmacy_id", referencedColumnName: "id")]
    private Pharmacy $pharmacy;

    #[ORM\Column(type: "string", length: 100)]
    private string $name;

    #[ORM\Column(type: "string", length: 50)]
    private string $sku;

    #[ORM\Column(type: "string", length: 50)]
    private string $genericName;

    #[ORM\Column(type: "string", length: 20)]
    private string $category;

    #[ORM\Column(type: "string", length: 100)]
    private string $manufacturer;

    #[ORM\Column(type: "string", length: 50)]
    private string $dosage;

    #[ORM\Column(type: "string", length: 100)]
    private string $form;

    #[ORM\Column(type: "integer")]
    private int $quantityInStock;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $price;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $originalPrice;

    #[ORM\Column(type: "string", length: 500, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "string", length: 500, nullable: true)]
    private ?string $usageInstructions = null;

    #[ORM\Column(type: "string", length: 500, nullable: true)]
    private ?string $sideEffects = null;

    #[ORM\Column(type: "string", length: 500, nullable: true)]
    private ?string $contraindications = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: "boolean")]
    private bool $requiresPrescription;

    #[ORM\Column(type: "boolean")]
    private bool $isAvailable = true;

    #[ORM\Column(type: "datetime")]
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }

    /**
     * @ORM\PrePersist
     */
    public function setId(int $id)
    {
        $this->id = $id;
        return $this;
    }

    public function getPharmacy() { return $this->pharmacy; }
    public function setPharmacy(Pharmacy $pharmacy) { $this->pharmacy = $pharmacy; return $this; }
    public function getName() { return $this->name; }
    public function setName(string $name) { $this->name = $name; return $this; }
    public function getSku() { return $this->sku; }
    public function setSku(string $sku) { $this->sku = $sku; return $this; }
    public function getGenericName() { return $this->genericName; }
    public function setGenericName(string $genericName) { $this->genericName = $genericName; return $this; }
    public function getCategory() { return $this->category; }
    public function setCategory(string $category) { $this->category = $category; return $this; }
    public function getManufacturer() { return $this->manufacturer; }
    public function setManufacturer(string $manufacturer) { $this->manufacturer = $manufacturer; return $this; }
    public function getDosage() { return $this->dosage; }
    public function setDosage(string $dosage) { $this->dosage = $dosage; return $this; }
    public function getForm() { return $this->form; }
    public function setForm(string $form) { $this->form = $form; return $this; }
    public function getQuantityInStock() { return $this->quantityInStock; }
    public function setQuantityInStock(int $quantityInStock) { $this->quantityInStock = $quantityInStock; return $this; }
    public function getPrice() { return $this->price; }
    public function setPrice(float $price) { $this->price = $price; return $this; }
    public function getOriginalPrice() { return $this->originalPrice; }
    public function setOriginalPrice(float $originalPrice) { $this->originalPrice = $originalPrice; return $this; }
    public function getDescription() { return $this->description; }
    public function setDescription(?string $description) { $this->description = $description; return $this; }
    public function getUsageInstructions() { return $this->usageInstructions; }
    public function setUsageInstructions(?string $usageInstructions) { $this->usageInstructions = $usageInstructions; return $this; }
    public function getSideEffects() { return $this->sideEffects; }
    public function setSideEffects(?string $sideEffects) { $this->sideEffects = $sideEffects; return $this; }
    public function getContraindications() { return $this->contraindications; }
    public function setContraindications(?string $contraindications) { $this->contraindications = $contraindications; return $this; }
    public function getImageUrl() { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl) { $this->imageUrl = $imageUrl; return $this; }
    public function requiresPrescription() { return $this->requiresPrescription; }
    public function setRequiresPrescription(bool $requiresPrescription) { $this->requiresPrescription = $requiresPrescription; return $this; }
    public function isAvailable() { return $this->isAvailable && $this->quantityInStock > 0; }
    public function setIsAvailable(bool $isAvailable) { $this->isAvailable = $isAvailable; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getDiscountPercentage()
    {
        if ($this->originalPrice <= 0) return 0;
        return (int)(($this->originalPrice - $this->price) / $this->originalPrice * 100);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'pharmacy_id' => $this->pharmacy->getId(),
            'name' => $this->name,
            'sku' => $this->sku,
            'generic_name' => $this->genericName,
            'category' => $this->category,
            'manufacturer' => $this->manufacturer,
            'dosage' => $this->dosage,
            'form' => $this->form,
            'quantity_in_stock' => $this->quantityInStock,
            'price' => $this->price,
            'original_price' => $this->originalPrice,
            'description' => $this->description,
            'usage_instructions' => $this->usageInstructions,
            'side_effects' => $this->sideEffects,
            'contraindications' => $this->contraindications,
            'image_url' => $this->imageUrl,
            'requires_prescription' => $this->requiresPrescription,
            'is_available' => $this->isAvailable(),
            'discount_percentage' => $this->getDiscountPercentage(),
            'created_at' => $this->createdAt->format('c'),
        ];
    }
}
