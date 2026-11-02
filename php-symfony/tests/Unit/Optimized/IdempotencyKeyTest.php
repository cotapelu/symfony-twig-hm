<?php

declare(strict_types=1);

namespace App\Tests\Unit\Optimized;

use PHPUnit\Framework\TestCase;
use App\Core\Entity\IdempotencyKey;

// Load the file containing all entity classes (non-PSR-4)
require_once __DIR__.'/../../../src/Entity/Optimized/EcommerceWallet.php';

class IdempotencyKeyTest extends TestCase
{
    private function createKey(?string $id = null, ?string $keyHash = null, ?array $response = null): IdempotencyKey
    {
        $id = $id ?? 'key-id';
        $keyHash = $keyHash ?? 'hash-'.hash('sha256', 'test');
        $response = $response ?? ['result' => 'ok'];
        return new IdempotencyKey($id, $keyHash, $response);
    }

    public function testConstructorInitializesProperties(): void
    {
        $key = $this->createKey();

        $this->assertSame('key-id', $key->getId());
        $this->assertSame('hash-'.hash('sha256', 'test'), $key->getKeyHash());
        $this->assertSame(['result' => 'ok'], $key->getResponse());
        $this->assertInstanceOf(\DateTime::class, $key->getCreatedAt());
    }

    public function testGetters(): void
    {
        $key = $this->createKey('id-2', 'hash-2', ['data' => 'value']);
        $this->assertSame('id-2', $key->getId());
        $this->assertSame('hash-2', $key->getKeyHash());
        $this->assertSame(['data' => 'value'], $key->getResponse());
    }
}
