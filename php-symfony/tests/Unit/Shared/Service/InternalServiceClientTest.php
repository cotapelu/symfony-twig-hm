<?php declare(strict_types=1);

namespace App\Tests\Unit\Shared\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Shared\Service\InternalServiceClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use App\Shared\Exception\ServiceUnavailableException;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class InternalServiceClientTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;
    private LoggerInterface&MockObject $logger;
    private InternalServiceClient $client;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->client = new InternalServiceClient(
            $this->httpClient,
            'http://go-service',
            '',
            '',
            '',
            '',
            $this->logger
        );
    }

    public function testGoGetSuccess(): void
    {
        $path = '/api/v1/resource';
        $query = ['id' => 123];
        $expectedUrl = 'http://go-service/api/v1/resource';

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->with(false)->willReturn(['data' => 'ok']);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', $expectedUrl, $this->arrayHasKey('query'))
            ->willReturn($response);

        $result = $this->client->goGet($path, $query);
        $this->assertSame(['data' => 'ok'], $result);
    }

    public function testGoGetThrowsOnNon200(): void
    {
        $path = '/api/v1/resource';
        $expectedUrl = 'http://go-service/api/v1/resource';
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(500);
        $response->method('toArray')->with(false)->willReturn(['error' => 'Server error']);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', $expectedUrl, $this->arrayHasKey('query'))
            ->willReturn($response);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HTTP error: 500');
        $this->client->goGet($path, []);
    }

    public function testGoPostSendsJsonBody(): void
    {
        $path = '/api/v1/create';
        $data = ['name' => 'test', 'value' => 123];
        $expectedUrl = 'http://go-service/api/v1/create';

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(201);
        $response->method('toArray')->with(false)->willReturn(['id' => 456]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', $expectedUrl, $this->arrayHasKey('json'))
            ->willReturn($response);

        $result = $this->client->goPost($path, $data);
        $this->assertSame(['id' => 456], $result);
    }

    public function testGoPutSendsJsonBody(): void
    {
        $path = '/api/v1/update/123';
        $data = ['status' => 'active'];
        $expectedUrl = 'http://go-service/api/v1/update/123';

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->with(false)->willReturn(['updated' => true]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('PUT', $expectedUrl, $this->arrayHasKey('json'))
            ->willReturn($response);

        $result = $this->client->goPut($path, $data);
        $this->assertTrue($result['updated']);
    }

    public function testGoDeleteSuccess(): void
    {
        $path = '/api/v1/delete/123';
        $expectedUrl = 'http://go-service/api/v1/delete/123';

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(204);
        $response->method('toArray')->with(false)->willReturn([]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('DELETE', $expectedUrl, $this->identicalTo([]))
            ->willReturn($response);

        $result = $this->client->goDelete($path);
        $this->assertSame([], $result);
    }

    public function testRequestThrowsServiceUnavailableOnTransportException(): void
    {
        $transportException = new \Symfony\Component\HttpClient\Exception\TransportException('Connection failed');
        $this->httpClient->method('request')
            ->willThrowException($transportException);

        $this->expectException(ServiceUnavailableException::class);
        $this->expectExceptionMessage('Internal service unavailable');

        $ref = new \ReflectionMethod($this->client, 'request');
        $ref->setAccessible(true);
        $ref->invoke($this->client, 'GET', 'http://go-service/test', []);
    }
}
