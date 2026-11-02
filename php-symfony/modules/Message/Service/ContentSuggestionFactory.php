<?php

declare(strict_types=1);

namespace App\Message\Service;

class ContentSuggestionFactory
{
    /** @var ContentSuggestionGeneratorInterface[] */
    private array $generators = [];

    public function registerGenerator(ContentSuggestionGeneratorInterface $generator)
    {
        $this->generators[] = $generator;
    }

    public function getGenerator(string $type)
    {
        foreach ($this->generators as $generator) {
            if ($generator->canHandle($type)) {
                return $generator;
            }
        }

        return null;
    }
}
