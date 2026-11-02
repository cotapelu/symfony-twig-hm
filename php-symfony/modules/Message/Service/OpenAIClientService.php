<?php

declare(strict_types=1);

namespace App\Message\Service;

/**
 * OpenAI Client Service
 * Handles all OpenAI API communications.
 */
class OpenAIClientService
{
    private ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = getenv('OPENAI_API_KEY') ?: null;
    }

    public function hasApiKey()
    {
        return $this->apiKey !== null && $this->apiKey !== '';
    }

    public function chatCompletion(array $messages, int $maxTokens = 150, float $temperature = 0.7, int $n = 1)
    {
        if (!$this->hasApiKey()) {
            throw new \RuntimeException('OpenAI API key not configured');
        }

        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            'n' => $n,
            'temperature' => $temperature,
        ];

        return $this->callOpenAI('https://api.openai.com/v1/chat/completions', $data);
    }

    public function moderation(string $input)
    {
        if (!$this->hasApiKey()) {
            throw new \RuntimeException('OpenAI API key not configured');
        }

        $data = ['input' => $input];
        return $this->callOpenAI('https://api.openai.com/v1/moderations', $data);
    }

    private function callOpenAI(string $url, array $data)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('Curl error: ' . $error);
        }

        if ($httpCode !== 200) {
            throw new \RuntimeException('OpenAI API error: ' . $response);
        }

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON response from OpenAI');
        }

        return $result;
    }
}
