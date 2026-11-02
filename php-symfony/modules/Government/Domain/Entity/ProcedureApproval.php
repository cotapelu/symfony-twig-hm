<?php

declare(strict_types=1);

namespace App\Government\Domain\Entity;

use App\Government\Domain\ValueObject\ProcedureApprovalId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'gov_procedure_approvals')]
class ProcedureApproval
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ACTIVE = 'active';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $procedureId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comments = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $processedBy = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $processedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $procedureId,
        string $status,
        ?string $processedBy = null,
        ?string $comments = null
    ) {
        $this->id = (new ProcedureApprovalId())->toString();
        $this->procedureId = $procedureId;
        $this->status = $status;
        $this->processedBy = $processedBy;
        $this->comments = $comments;
        $this->processedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ProcedureApprovalId
    {
        return new ProcedureApprovalId($this->id);
    }

    public function getProcedureId(): string
    {
        return $this->procedureId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function getProcessedBy(): ?string
    {
        return $this->processedBy;
    }

    public function getProcessedAt(): \DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setComments(?string $comments): void
    {
        $this->comments = $comments;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setProcessedBy(?string $processedBy): void
    {
        $this->processedBy = $processedBy;
        $this->updatedAt = new \DateTimeImmutable();
    }
}