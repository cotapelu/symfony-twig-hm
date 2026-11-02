<?php

declare(strict_types=1);

namespace App\Realtime\Service;

class LiveCaptionService
{
    public function generate(string $audioData, string $language = 'en')
    {
        return [
            'captions' => [
                [
                    'text' => 'Hello, welcome to the meeting.',
                    'start_time' => 0.0,
                    'end_time' => 2.5,
                    'confidence' => 0.95,
                ],
                [
                    'text' => 'Today we will discuss the new project requirements.',
                    'start_time' => 2.5,
                    'end_time' => 5.0,
                    'confidence' => 0.92,
                ],
            ],
            'language' => $language,
            'status' => 'active',
        ];
    }
}
