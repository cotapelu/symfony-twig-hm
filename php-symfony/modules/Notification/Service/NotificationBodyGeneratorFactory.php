<?php

declare(strict_types=1);

namespace App\Notification\Service;

class NotificationBodyGeneratorFactory
{
    /** @var NotificationBodyGeneratorInterface[] */
    private array $generators = [];

    public function registerGenerator(NotificationBodyGeneratorInterface $generator)
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
