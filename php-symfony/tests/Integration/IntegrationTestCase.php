<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class IntegrationTestCase extends WebTestCase
{
    protected static $client;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        // Boot kernel once for all tests
        static::$client = static::createClient([], [
            'HTTP_HOST' => 'localhost',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);
    }

    protected function authenticateAs(string $userId, array $roles = ['ROLE_USER']): void
    {
        // In a real test, we would obtain a JWT token from the auth service
        // For integration, we can set a session or directly call login endpoint
        // We'll simplify by assuming token is set in Authorization header
        // This method can be expanded later
    }

    protected function jsonRequest(string $method, string $url, array $data = null, array $headers = []): \Symfony\Component\BrowserKit\Response
    {
        $request = $this->buildRequest($method, $url, $data, $headers);
        return static::$client->request($method, $url, [], [], $request->server, $request->content);
    }

    private function buildRequest(string $method, string $url, array $data = null, array $headers = []): \Symfony\Component\BrowserKit\Request
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        foreach ($headers as $key => $value) {
            $server[strtoupper($key)] = $value;
        }

        $content = $data ? json_encode($data) : null;

        return new \Symfony\Component\BrowserKit\Request($method, $url, [], $content, $server);
    }
}
