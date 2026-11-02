<?php

declare(strict_types=1);

namespace Platform\ApiGateway\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\NativeHttpClient;
use InvalidArgumentException;

/**
 * Service for routing API requests to appropriate backend services
 */
class GatewayService
{
    private HttpClientInterface $client;
    private array $routes;
    private int $timeout;

    /**
     * @param array<string, string> $routes Mapping of path prefixes to backend URLs
     * @param int $timeout Request timeout in seconds
     */
    public function __construct(
        array $routes = [],
        int $timeout = 30,
        ?HttpClientInterface $client = null
    ) {
        $this->routes = $this->validateRoutes($routes);
        $this->timeout = $this->validateTimeout($timeout);
        if ($client !== null) {
            $this->client = $client;
        } else {
            $options = ['timeout' => $this->timeout];
            // mTLS configuration for service-to-service communication
            $caCert = getenv('GATEWAY_CA_CERT');
            $clientCert = getenv('GATEWAY_CLIENT_CERT');
            $clientKey = getenv('GATEWAY_CLIENT_KEY');
            if ($caCert && $clientCert && $clientKey) {
                $options['cafile'] = $caCert;
                $options['local_cert'] = $clientCert;
                $options['local_pk'] = $clientKey;
            }
            $this->client = new NativeHttpClient($options);
        }
    }

    public function getRoutes(): array
    {
        return array_keys($this->routes);
    }

    public function getRouteConfig(): array
    {
        return $this->routes;
    }

    public function findRoute(string $path): ?string
    {
        $this->validatePath($path);
        
        foreach ($this->routes as $prefix => $target) {
            if (str_starts_with($path, $prefix)) {
                return $target;
            }
        }
        return null;
    }

    public function proxy(string $method, string $path, array $headers = [], ?string $body = null): array
    {
        $this->validateHttpMethod($method);
        $target = $this->validateAndFindRoute($path);
        
        if ($target === null) {
            return $this->createErrorResponse(404, 'No route found for path: ' . $path);
        }

        $url = $this->buildUrl($target, $path);
        $options = $this->prepareRequestOptions($headers, $body);
        
        try {
            $response = $this->client->request($method, $url, $options);
            return $this->buildSuccessResponse($response);
        } catch (\Exception $e) {
            return $this->createErrorResponse(502, 'Bad gateway: ' . $e->getMessage());
        }
    }

    private function validateAndFindRoute(string $path): ?string
    {
        $this->validatePath($path);
        return $this->findRoute($path);
    }

    private function buildUrl(string $target, string $path): string
    {
        return $target . $path;
    }

    private function prepareRequestOptions(array $headers, ?string $body): array
    {
        return [
            'headers' => $this->filterHeaders($headers),
            'body' => $body,
        ];
    }

    private function buildSuccessResponse($response): array
    {
        return [
            'status' => $response->getStatusCode(),
            'body' => $response->getContent(),
            'headers' => $response->getHeaders(),
        ];
    }

    private function validateHttpMethod(string $method): void
    {
        $validMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];
        if (!in_array(strtoupper($method), $validMethods, true)) {
            throw new InvalidArgumentException('Invalid HTTP method: ' . $method);
        }
    }

    private function validateRoutes(array $routes): array
    {
        foreach ($routes as $prefix => $target) {
            if (!is_string($prefix) || !$prefix) {
                throw new InvalidArgumentException('Route prefix must be a non-empty string');
            }
            if (!is_string($target) || !$target) {
                throw new InvalidArgumentException('Route target must be a non-empty string');
            }
            if (!preg_match('#^https?://#', $target)) {
                throw new InvalidArgumentException('Route target must be a valid URL starting with http:// or https://');
            }
        }
        return $routes;
    }

    private function validatePath(string $path): void
    {
        if (!is_string($path) || !$path) {
            throw new InvalidArgumentException('Path must be a non-empty string');
        }
        if (!preg_match('#^/#', $path)) {
            throw new InvalidArgumentException('Path must start with a forward slash');
        }
    }

    private function validateTimeout(int $timeout): int
    {
        if ($timeout <= 0) {
            throw new InvalidArgumentException('Timeout must be a positive integer');
        }
        return $timeout;
    }

    private function createErrorResponse(int $status, string $message): array
    {
        return [
            'status' => $status,
            'body' => ['error' => $message],
        ];
    }

    private function filterHeaders(array $headers): array
    {
        $filtered = [];
        static $hopByHop = [
            'connection', 'keep-alive', 'proxy-authenticate',
            'proxy-authorization', 'te', 'trailers',
            'transfer-encoding', 'upgrade',
        ];

        foreach ($headers as $key => $value) {
            if (!in_array(strtolower($key), $hopByHop)) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }
}