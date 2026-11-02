<?php

declare(strict_types=1);

namespace App\PharmacyDelivery\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: App\PharmacyDelivery\Repository\OrderRepository::class)]
#[ORM\Table(name: "pharmacy_delivery_orders")]
class Order
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_READY = 'ready';
    public const STATUS_PICKED_UP = 'picked_up';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_WAITING_PRESCRIPTION = 'waiting_prescription';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 36)]
    private string $orderNumber;

    #[ORM\ManyToOne(targetEntity: Pharmacy::class)]
    #[ORM\JoinColumn(name: "pharmacy_id", referencedColumnName: "id")]
    private Pharmacy $pharmacy;

    #[ORM\Column(type: "string", length: 36)]
    private string $customerId;

    #[ORM\ManyToOne(targetEntity: DeliveryPerson::class)]
    #[ORM\JoinColumn(name: "delivery_person_id", referencedColumnName: "id", nullable: true)]
    private ?DeliveryPerson $deliveryPerson = null;

    #[ORM\Column(type: "string", length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $subtotal;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $deliveryFee;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $serviceFee;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $tax;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $discount;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $total;

    #[ORM\OneToMany(mappedBy: "order", targetEntity: OrderItem::class, cascade: ["persist", "remove"])]
    private Collection $items;

    #[ORM\OneToOne(targetEntity: Payment::class, cascade: ["persist", "remove"])]
    private ?Payment $payment = null;

    #[ORM\Column(type: "string", length: 255)]
    private string $deliveryAddress;

    #[ORM\Column(type: "decimal", precision: 10, scale: 6)]
    private float $deliveryLatitude;

    #[ORM\Column(type: "decimal", precision: 10, scale: 6)]
    private float $deliveryLongitude;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    private ?string $deliveryInstructions = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $customerPhone = null;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    private ?string $customerName = null;

    #[ORM\Column(type: "string", length: 500, nullable: true)]
    private ?string $prescriptionImageUrl = null;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    private ?string $prescriptionNumber = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $patientName = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $patientAge = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $estimatedDeliveryTime = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTime $confirmedAt = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTime $readyAt = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTime $pickedUpAt = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTime $deliveredAt = null;

    #[ORM\Column(type: "datetime")]
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->orderNumber = 'PHM-' . strtoupper(bin2hex(random_bytes(6)));
        $this->createdAt = new \DateTime();
        $this->items = new ArrayCollection();
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

    public function getOrderNumber() { return $this->orderNumber; }
    public function getPharmacy() { return $this->pharmacy; }
    public function setPharmacy(Pharmacy $pharmacy) { $this->pharmacy = $pharmacy; return $this; }
    public function getCustomerId() { return $this->customerId; }
    public function setCustomerId(string $customerId) { $this->customerId = $customerId; return $this; }
    public function getDeliveryPerson() { return $this->deliveryPerson; }
    public function setDeliveryPerson(?DeliveryPerson $deliveryPerson) { $this->deliveryPerson = $deliveryPerson; return $this; }
    public function getStatus() { return $this->status; }
    public function setStatus(string $status) { $this->status = $status; return $this; }
    public function getSubtotal() { return $this->subtotal; }
    public function setSubtotal(float $subtotal) { $this->subtotal = $subtotal; return $this; }
    public function getDeliveryFee() { return $this->deliveryFee; }
    public function setDeliveryFee(float $deliveryFee) { $this->deliveryFee = $deliveryFee; return $this; }
    public function getServiceFee() { return $this->serviceFee; }
    public function setServiceFee(float $serviceFee) { $this->serviceFee = $serviceFee; return $this; }
    public function getTax() { return $this->tax; }
    public function setTax(float $tax) { $this->tax = $tax; return $this; }
    public function getDiscount() { return $this->discount; }
    public function setDiscount(float $discount) { $this->discount = $discount; return $this; }
    public function getTotal() { return $this->total; }
    public function setTotal(float $total) { $this->total = $total; return $this; }
    public function getItems() { return $this->items; }
    public function addItem(OrderItem $item) { $this->items->add($item); return $this; }
    public function removeItem(OrderItem $item) { $this->items->removeElement($item); return $this; }
    public function getPayment() { return $this->payment; }
    public function setPayment(?Payment $payment) { $this->payment = $payment; return $this; }
    public function getDeliveryAddress() { return $this->deliveryAddress; }
    public function setDeliveryAddress(string $deliveryAddress) { $this->deliveryAddress = $deliveryAddress; return $this; }
    public function getDeliveryLatitude() { return $this->deliveryLatitude; }
    public function setDeliveryLatitude(float $deliveryLatitude) { $this->deliveryLatitude = $deliveryLatitude; return $this; }
    public function getDeliveryLongitude() { return $this->deliveryLongitude; }
    public function setDeliveryLongitude(float $deliveryLongitude) { $this->deliveryLongitude = $deliveryLongitude; return $this; }
    public function getDeliveryInstructions() { return $this->deliveryInstructions; }
    public function setDeliveryInstructions(?string $deliveryInstructions) { $this->deliveryInstructions = $deliveryInstructions; return $this; }
    public function getCustomerPhone() { return $this->customerPhone; }
    public function setCustomerPhone(?string $customerPhone) { $this->customerPhone = $customerPhone; return $this; }
    public function getCustomerName() { return $this->customerName; }
    public function setCustomerName(?string $customerName) { $this->customerName = $customerName; return $this; }
    public function getPrescriptionImageUrl() { return $this->prescriptionImageUrl; }
    public function setPrescriptionImageUrl(?string $prescriptionImageUrl) { $this->prescriptionImageUrl = $prescriptionImageUrl; return $this; }
    public function getPrescriptionNumber() { return $this->prescriptionNumber; }
    public function setPrescriptionNumber(?string $prescriptionNumber) { $this->prescriptionNumber = $prescriptionNumber; return $this; }
    public function getPatientName() { return $this->patientName; }
    public function setPatientName(?string $patientName) { $this->patientName = $patientName; return $this; }
    public function getPatientAge() { return $this->patientAge; }
    public function setPatientAge(?int $patientAge) { $this->patientAge = $patientAge; return $this; }
    public function getEstimatedDeliveryTime() { return $this->estimatedDeliveryTime; }
    public function setEstimatedDeliveryTime(?int $estimatedDeliveryTime) { $this->estimatedDeliveryTime = $estimatedDeliveryTime; return $this; }
    public function getConfirmedAt() { return $this->confirmedAt; }
    public function setConfirmedAt(?\DateTime $confirmedAt) { $this->confirmedAt = $confirmedAt; return $this; }
    public function getReadyAt() { return $this->readyAt; }
    public function setReadyAt(?\DateTime $readyAt) { $this->readyAt = $readyAt; return $this; }
    public function getPickedUpAt() { return $this->pickedUpAt; }
    public function setPickedUpAt(?\DateTime $pickedUpAt) { $this->pickedUpAt = $pickedUpAt; return $this; }
    public function getDeliveredAt() { return $this->deliveredAt; }
    public function setDeliveredAt(?\DateTime $deliveredAt) { $this->deliveredAt = $deliveredAt; return $this; }
    public function getCreatedAt() { return $this->createdAt; }
    public function requiresPrescription()
    {
        foreach ($this->items as $item) {
            if ($item->getMedicine()->requiresPrescription()) return true;
        }
        return false;
    }
    public function isActive() { return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_PREPARING, self::STATUS_READY, self::STATUS_PICKED_UP]); }
    public function isCompleted() { return $this->status === self::STATUS_DELIVERED; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->orderNumber,
            'pharmacy_id' => $this->pharmacy->getId(),
            'customer_id' => $this->customerId,
            'delivery_person_id' => $this->deliveryPerson?->getId(),
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'delivery_fee' => $this->deliveryFee,
            'service_fee' => $this->serviceFee,
            'tax' => $this->tax,
            'discount' => $this->discount,
            'total' => $this->total,
            'delivery_address' => $this->deliveryAddress,
            'delivery_latitude' => $this->deliveryLatitude,
            'delivery_longitude' => $this->deliveryLongitude,
            'delivery_instructions' => $this->deliveryInstructions,
            'customer_phone' => $this->customerPhone,
            'customer_name' => $this->customerName,
            'prescription_image_url' => $this->prescriptionImageUrl,
            'prescription_number' => $this->prescriptionNumber,
            'patient_name' => $this->patientName,
            'patient_age' => $this->patientAge,
            'estimated_delivery_time' => $this->estimatedDeliveryTime,
            'confirmed_at' => $this->confirmedAt?->format('c'),
            'ready_at' => $this->readyAt?->format('c'),
            'picked_up_at' => $this->pickedUpAt?->format('c'),
            'delivered_at' => $this->deliveredAt?->format('c'),
            'created_at' => $this->createdAt->format('c'),
        ];
    }
}
