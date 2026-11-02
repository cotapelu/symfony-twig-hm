<?php

declare(strict_types=1);

namespace App\Tests\Controller;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;

#[AllowMockObjectsWithoutExpectations]
class FoodDeliveryControllerTest extends DatabaseIntegrationTestCase
{
    public function testListRestaurants(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/food/restaurants');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertCount(2, $responseData['data']);
    }

    public function testGetRestaurant(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/food/restaurants/1');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals(1, $responseData['id']);
    }

    public function testCreateOrder(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/food/orders', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'restaurantId' => 1,
            'items' => [
                ['id' => 1, 'quantity' => 2],
                ['id' => 2, 'quantity' => 1]
            ],
            'total' => 140000,
            'address' => '123 Đường ABC, Quận XYZ'
        ]));

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals('pending', $responseData['status']);
    }

    public function testGetOrder(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/food/orders/1234');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(1234, $responseData['id']);
    }
}
