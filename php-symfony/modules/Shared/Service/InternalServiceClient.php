<?php

declare(strict_types=1);

namespace App\Shared\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use App\Shared\Exception\ServiceUnavailableException;

class InternalServiceClient
{
    private array $serviceUrls;

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%env(string:GO_SERVICE_URL)%')]
        string $goServiceUrl,
        #[Autowire('%env(string:PYTHON_SERVICE_URL)%')]
        string $pythonServiceUrl,
        #[Autowire('%env(string:RUST_SERVICE_URL)%')]
        string $rustServiceUrl,
        #[Autowire('%env(string:ZIG_SERVICE_URL)%')]
        string $zigServiceUrl,
        #[Autowire('%env(string:ELIXIR_SERVICE_URL)%')]
        string $elixirServiceUrl,
        private LoggerInterface $logger,
    ) {
        $this->serviceUrls = [
            'go' => $goServiceUrl,
            'python' => $pythonServiceUrl,
            'rust' => $rustServiceUrl,
            'zig' => $zigServiceUrl,
            'elixir' => $elixirServiceUrl,
        ];
    }

    public function goGet(string $path, array $query = [])
    {
        return $this->requestService('go', 'GET', $path, ['query' => $query]);
    }

    public function goPost(string $path, array $data = [])
    {
        return $this->requestService('go', 'POST', $path, ['json' => $data]);
    }

    public function goPut(string $path, array $data = [])
    {
        return $this->requestService('go', 'PUT', $path, ['json' => $data]);
    }

    public function goDelete(string $path)
    {
        return $this->requestService('go', 'DELETE', $path);
    }

    public function pythonGet(string $path, array $query = [])
    {
        return $this->requestService('python', 'GET', $path, ['query' => $query]);
    }

    public function pythonPost(string $path, array $data = [])
    {
        return $this->requestService('python', 'POST', $path, ['json' => $data]);
    }

    public function pythonPut(string $path, array $data = [])
    {
        return $this->requestService('python', 'PUT', $path, ['json' => $data]);
    }

    public function pythonDelete(string $path)
    {
        return $this->requestService('python', 'DELETE', $path);
    }

    public function rustGet(string $path, array $query = [])
    {
        return $this->requestService('rust', 'GET', $path, ['query' => $query]);
    }

    public function rustPost(string $path, array $data = [])
    {
        return $this->requestService('rust', 'POST', $path, ['json' => $data]);
    }

    public function rustPut(string $path, array $data = [])
    {
        return $this->requestService('rust', 'PUT', $path, ['json' => $data]);
    }

    public function rustDelete(string $path)
    {
        return $this->requestService('rust', 'DELETE', $path);
    }

    public function zigGet(string $path, array $query = [])
    {
        return $this->requestService('zig', 'GET', $path, ['query' => $query]);
    }

    public function zigPost(string $path, array $data = [])
    {
        return $this->requestService('zig', 'POST', $path, ['json' => $data]);
    }

    public function zigPut(string $path, array $data = [])
    {
        return $this->requestService('zig', 'PUT', $path, ['json' => $data]);
    }

    public function zigDelete(string $path)
    {
        return $this->requestService('zig', 'DELETE', $path);
    }

    public function elixirGet(string $path, array $query = [])
    {
        return $this->requestService('elixir', 'GET', $path, ['query' => $query]);
    }

    public function elixirPost(string $path, array $data = [])
    {
        return $this->requestService('elixir', 'POST', $path, ['json' => $data]);
    }

    public function elixirPut(string $path, array $data = [])
    {
        return $this->requestService('elixir', 'PUT', $path, ['json' => $data]);
    }

    public function elixirDelete(string $path)
    {
        return $this->requestService('elixir', 'DELETE', $path);
    }

    private function requestService(string $service, string $method, string $path, array $options = [])
    {
        if (!isset($this->serviceUrls[$service])) {
            throw new \InvalidArgumentException("Unknown service: $service");
        }

        $url = $this->serviceUrls[$service] . $path;

        return $this->request($method, $url, $options);
    }

    private function request(string $method, string $url, array $options = [])
    {
        try {
            $response = $this->httpClient->request($method, $url, $options);

            $statusCode = $response->getStatusCode();
            $content = $response->toArray(false);

            if ($statusCode < 200 || $statusCode >= 300) {
                throw new \RuntimeException("HTTP error: $statusCode - " . json_encode($content));
            }

            return $content;

        } catch (TransportException $e) {
            $this->logger->error('Transport error calling internal service', [
                'url' => $url,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            throw new ServiceUnavailableException('Internal service unavailable: ' . $e->getMessage());

        } catch (ClientException $e) {
            $this->logger->error('Client error calling internal service', [
                'url' => $url,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Internal service error: ' . $e->getMessage(), 0, $e);
        }
    }

    public function checkGoService()
    {
        return $this->checkServiceHealth('go');
    }

    public function checkPythonService()
    {
        return $this->checkServiceHealth('python');
    }

    public function checkRustService()
    {
        return $this->checkServiceHealth('rust');
    }

    public function checkZigService()
    {
        return $this->checkServiceHealth('zig');
    }

    public function checkElixirService()
    {
        return $this->checkServiceHealth('elixir');
    }

    private function checkServiceHealth(string $service)
    {
        if (!isset($this->serviceUrls[$service])) {
            return false;
        }

        try {
            $response = $this->httpClient->request('GET', $this->serviceUrls[$service] . '/health', ['timeout' => 2.0]);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getHealthStatus()
    {
        return [
            'go' => $this->checkGoService(),
            'python' => $this->checkPythonService(),
            'rust' => $this->checkRustService(),
            'zig' => $this->checkZigService(),
            'elixir' => $this->checkElixirService(),
            'go_url' => $this->serviceUrls['go'],
            'python_url' => $this->serviceUrls['python'],
            'rust_url' => $this->serviceUrls['rust'],
            'zig_url' => $this->serviceUrls['zig'],
            'elixir_url' => $this->serviceUrls['elixir'],
        ];
    }
}
