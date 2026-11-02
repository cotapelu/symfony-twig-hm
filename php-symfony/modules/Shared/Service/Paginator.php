<?php

declare(strict_types=1);

namespace App\Shared\Service;

final class Paginator
{
    private int $page;
    private int $perPage;
    private int $maxPerPage;

    public function __construct(int $page = 1, int $perPage = 20, int $maxPerPage = 100)
    {
        $this->page = max(1, $page);
        $this->perPage = min($maxPerPage, max(1, $perPage));
        $this->maxPerPage = $maxPerPage;
    }

    public function getPage()
    {
        return $this->page;
    }

    public function getPerPage()
    {
        return $this->perPage;
    }

    public function getOffset()
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function getLimit()
    {
        return $this->perPage;
    }

    public function getMaxPerPage()
    {
        return $this->maxPerPage;
    }
}
