<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Message\Entity\Message;

class SendMessageResponse
{
    public function __construct(
        public readonly Message $message,
    ) {
    }
}
