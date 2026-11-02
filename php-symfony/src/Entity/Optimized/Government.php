<?php

declare(strict_types=1);

namespace App\Core\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;

#[ORM\Entity]
#[ORM\Table(name: 'php.citizens')]
#[ORM\Index(name: 'idx_citizens_identity', columns: ['identity_number'], unique: true)]
#[ORM\Index(name: 'idx_citizens_household', columns: ['household_id'])]
#[HasLifecycleCallbacks]
class Citizen
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'identity_number', type: 'string', length: 20, unique: true)]
    private string $identityNumber;

    #[ORM\Column(name: 'household_id', type: 'string', length: 36, nullable: true)]
    private ?string $householdId = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $name, string $identityNumber)
    {
        $this->id = $id;
        $this->name = $name;
        $this->identityNumber = $identityNumber;
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getIdentityNumber() { return $this->identityNumber; }
    public function getHouseholdId() { return $this->householdId; }
    public function getStatus() { return $this->status; }
    public function getMetadata() { return $this->metadata; }
    public function getCreatedAt() { return $this->createdAt; }

    public function setHouseholdId(?string $id) { $this->householdId = $id; return $this; }
    public function setStatus(string $status) { $this->status = $status; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.households')]
#[ORM\Index(name: 'idx_households_ward', columns: ['ward_id'])]
class Household
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'ward_id', type: 'string', length: 36)]
    private string $wardId;

    #[ORM\Column(name: 'head_id', type: 'string', length: 36, nullable: true)]
    private ?string $headId = null;

    #[ORM\Column(type: 'text')]
    private string $address;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    public function __construct(string $id, string $wardId, string $address)
    {
        $this->id = $id;
        $this->wardId = $wardId;
        $this->address = $address;
    }

    public function getId() { return $this->id; }
    public function getWardId() { return $this->wardId; }
    public function getHeadId() { return $this->headId; }
    public function getAddress() { return $this->address; }
    public function getMetadata() { return $this->metadata; }

    public function setHeadId(?string $id) { $this->headId = $id; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.gov_officers')]
#[ORM\Index(name: 'idx_gov_officers_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_gov_officers_dept', columns: ['department'])]
class GovOfficer
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 100)]
    private string $department;

    #[ORM\Column(type: 'string', length: 100)]
    private string $position;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'ACTIVE';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    public function __construct(string $id, string $userId, string $department, string $position)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->department = $department;
        $this->position = $position;
    }

    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getDepartment() { return $this->department; }
    public function getPosition() { return $this->position; }
    public function getStatus() { return $this->status; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.administrative_procedures')]
#[ORM\Index(name: 'idx_procedures_code', columns: ['code'], unique: true)]
class AdministrativeProcedure
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $code;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $requirements = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'ACTIVE';

    public function __construct(string $id, string $code, string $name)
    {
        $this->id = $id;
        $this->code = $code;
        $this->name = $name;
    }

    public function getId() { return $this->id; }
    public function getCode() { return $this->code; }
    public function getName() { return $this->name; }
    public function getDescription() { return $this->description; }
    public function getRequirements() { return $this->requirements; }
    public function getStatus() { return $this->status; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.procedure_applications')]
#[ORM\Index(name: 'idx_procedure_applications_citizen', columns: ['citizen_id'])]
#[ORM\Index(name: 'idx_procedure_applications_procedure', columns: ['procedure_id'])]
#[ORM\Index(name: 'idx_procedure_applications_status', columns: ['status'])]
class ProcedureApplication
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PROCESSING = 'PROCESSING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'citizen_id', type: 'string', length: 36)]
    private string $citizenId;

    #[ORM\Column(name: 'procedure_id', type: 'string', length: 36)]
    private string $procedureId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $id, string $citizenId, string $procedureId)
    {
        $this->id = $id;
        $this->citizenId = $citizenId;
        $this->procedureId = $procedureId;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getCitizenId() { return $this->citizenId; }
    public function getProcedureId() { return $this->procedureId; }
    public function getStatus() { return $this->status; }
    public function getData() { return $this->data; }
    public function getCreatedAt() { return $this->createdAt; }

    public function setStatus(string $status) { $this->status = $status; $this->updatedAt = new \DateTime(); return $this; }
    public function setData(?array $data) { $this->data = $data; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.citizen_incidents')]
#[ORM\Index(name: 'idx_citizen_incidents_citizen', columns: ['citizen_id'])]
#[ORM\Index(name: 'idx_citizen_incidents_type', columns: ['type'])]
#[ORM\Index(name: 'idx_citizen_incidents_status', columns: ['status'])]
class CitizenIncident
{
    public const TYPE_ANTT = 'ANTT';
    public const TYPE_SECURITY = 'SECURITY';
    public const TYPE_MEDICAL = 'MEDICAL';
    public const TYPE_OTHER = 'OTHER';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_RESOLVED = 'RESOLVED';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'citizen_id', type: 'string', length: 36)]
    private string $citizenId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $citizenId, string $type)
    {
        $this->id = $id;
        $this->citizenId = $citizenId;
        $this->type = $type;
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getCitizenId() { return $this->citizenId; }
    public function getType() { return $this->type; }
    public function getStatus() { return $this->status; }
    public function getData() { return $this->data; }
    public function getCreatedAt() { return $this->createdAt; }

    public function setStatus(string $status) { $this->status = $status; return $this; }
}

#[ORM\Entity]
#[ORM\Table(name: 'php.procedure_approvals')]
class ProcedureApproval
{
    public const DECISION_APPROVE = 'APPROVE';
    public const DECISION_REJECT = 'REJECT';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(name: 'application_id', type: 'string', length: 36)]
    private string $applicationId;

    #[ORM\Column(name: 'officer_id', type: 'string', length: 36)]
    private string $officerId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $decision;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct(string $id, string $applicationId, string $officerId, string $decision)
    {
        $this->id = $id;
        $this->applicationId = $applicationId;
        $this->officerId = $officerId;
        $this->decision = $decision;
        $this->createdAt = new \DateTime();
    }

    public function getId() { return $this->id; }
    public function getApplicationId() { return $this->applicationId; }
    public function getOfficerId() { return $this->officerId; }
    public function getDecision() { return $this->decision; }
    public function getNotes() { return $this->notes; }
    public function getCreatedAt() { return $this->createdAt; }

    public function setNotes(?string $notes) { $this->notes = $notes; return $this; }
}