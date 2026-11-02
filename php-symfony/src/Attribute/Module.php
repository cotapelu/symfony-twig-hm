<?php

namespace App\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Module
{
    public string $name;
    public array $dependencies;
    public bool $autoLoad;

    public function __construct(
        string $name,
        array $dependencies = [],
        bool $autoLoad = true
    ) {
        $this->name = $name;
        $this->dependencies = $dependencies;
        $this->autoLoad = $autoLoad;
    }
}

#[\Attribute(\Attribute::TARGET_CLASS)]
class Service
{
    public string $id;
    public array $tags;

    public function __construct(
        ?string $id = null,
        array $tags = []
    ) {
        $this->id = $id ?? '';
        $this->tags = $tags;
    }
}