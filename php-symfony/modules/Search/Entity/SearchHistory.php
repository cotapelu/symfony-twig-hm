<?php

declare(strict_types=1);

namespace App\Search\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'search_history')]
#[ORM\Index(name: 'idx_search_history', columns: ['userId', 'query', 'createdAt'])]
class SearchHistory
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 500)]
    private string $query;

    #[ORM\Column(type: 'integer')]
    private int $resultCount;

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

    public function getResultCount()
    {
        return $this->resultCount;
    }

    public function setResultCount(int $resultCount)
    {
        $this->resultCount = $resultCount;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
