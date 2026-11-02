<?php

declare(strict_types=1);

namespace App\Search\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'search_results')]
#[ORM\Index(name: 'idx_search_result', columns: ['userId', 'query', 'createdAt'])]
class SearchResult
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 500)]
    private string $query;

    #[ORM\Column(type: 'string', length: 50)]
    private string $entityType;

    #[ORM\Column(type: 'string', length: 36)]
    private string $entityId;

    #[ORM\Column(type: 'float')]
    private float $score;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->id = (string) \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->createdAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId(string $userId)
    {
        $this->userId = $userId;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function setQuery(string $query)
    {
        $this->query = $query;
    }

    public function getEntityType()
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType)
    {
        $this->entityType = $entityType;
    }

    public function getEntityId()
    {
        return $this->entityId;
    }

    public function setEntityId(string $entityId)
    {
        $this->entityId = $entityId;
    }

    public function getScore()
    {
        return $this->score;
    }

    public function setScore(float $score)
    {
        $this->score = $score;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
