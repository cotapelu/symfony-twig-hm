<?php declare(strict_types=1);

namespace App\Tests\Unit\BusinessAccount\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\BusinessAccount\Entity\ApiKey;
use App\BusinessAccount\Entity\Business;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ApiKeyTest extends TestCase
{
    private function createApiKey(array $overrides = []): ApiKey
    {
        $key = new ApiKey();
        $key->setBusiness($this->createMock(Business::class));
        $key->setKey('key_' . bin2hex(random_bytes(8)));
        $key->setName('Test API Key');
        foreach ($overrides as $prop => $value) {
            $setter = 'set' . $prop;
            if (method_exists($key, $setter)) {
                $key->$setter($value);
            }
        }
        return $key;
    }

    public function testConstructorInitializesTimestamps(): void
    {
        $key = $this->createApiKey([]);
        $this->assertInstanceOf(\DateTime::class, $key->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $key->getLastUsedAt());
    }

    public function testDefaultValues(): void
    {
        $key = $this->createApiKey([]);
        $this->assertSame('active', $key->getStatus());
        $this->assertSame(1000, $key->getRequestLimit());
        $this->assertSame(0, $key->getRequestCount());
        $this->assertNull($key->getPermissions());
        $this->assertNull($key->getExpiresAt());
    }

    public function testGettersAndSetters(): void
    {
        $key = $this->createApiKey([]);

        $key->setStatus('revoked');
        $this->assertSame('revoked', $key->getStatus());

        $key->setPermissions('read,write');
        $this->assertSame('read,write', $key->getPermissions());

        $key->setRequestLimit(5000);
        $this->assertSame(5000, $key->getRequestLimit());

        $key->setRequestCount(3);
        $this->assertSame(3, $key->getRequestCount());

        $lastUsed = new \DateTime('-1 hour');
        $key->setLastUsedAt($lastUsed);
        $this->assertSame($lastUsed, $key->getLastUsedAt());

        $expires = new \DateTime('+1 year');
        $key->setExpiresAt($expires);
        $this->assertSame($expires, $key->getExpiresAt());
    }
}
