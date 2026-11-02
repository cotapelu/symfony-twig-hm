<?php declare(strict_types=1);

namespace App\Tests\Entity\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\FeatureFlag;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class FeatureFlagTest extends TestCase
{
    private function createFlag(
        string $id = 'flag-id',
        string $key = 'new-feature',
        string $environment = 'production'
    ): FeatureFlag {
        return new FeatureFlag($id, $key, $environment);
    }

    public function testConstructor(): void
    {
        $f = $this->createFlag();
        $this->assertSame('flag-id', $f->getId());
        $this->assertSame('new-feature', $f->getKey());
        $this->assertSame('production', $f->getEnvironment());
        $this->assertFalse($f->isEnabled());
        $this->assertNull($f->getRules());
        $this->assertSame('ACTIVE', $f->getStatus());
        $this->assertInstanceOf(\DateTime::class, $f->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $f->getUpdatedAt());
    }

    public function testSetKey(): void
    {
        $f = $this->createFlag();
        $originalUpdatedAt = $f->getUpdatedAt();
        usleep(1000);
        $f->setKey('updated-key');
        $this->assertSame('updated-key', $f->getKey());
        $this->assertNotEquals($originalUpdatedAt, $f->getUpdatedAt());
    }

    public function testSetEnvironment(): void
    {
        $f = $this->createFlag();
        $originalUpdatedAt = $f->getUpdatedAt();
        usleep(1000);
        $f->setEnvironment('staging');
        $this->assertSame('staging', $f->getEnvironment());
        $this->assertNotEquals($originalUpdatedAt, $f->getUpdatedAt());
    }

    public function testSetEnabled(): void
    {
        $f = $this->createFlag();
        $originalUpdatedAt = $f->getUpdatedAt();
        usleep(1000);
        $f->setEnabled(true);
        $this->assertTrue($f->isEnabled());
        $this->assertNotEquals($originalUpdatedAt, $f->getUpdatedAt());
        $f->setEnabled(false);
        $this->assertFalse($f->isEnabled());
    }

    public function testSetRules(): void
    {
        $f = $this->createFlag();
        $originalUpdatedAt = $f->getUpdatedAt();
        usleep(1000);
        $rules = ['user_ids' => [1, 2, 3]];
        $f->setRules($rules);
        $this->assertSame($rules, $f->getRules());
        $this->assertNotEquals($originalUpdatedAt, $f->getUpdatedAt());
        $f->setRules(null);
        $this->assertNull($f->getRules());
    }

    public function testSetStatus(): void
    {
        $f = $this->createFlag();
        $originalUpdatedAt = $f->getUpdatedAt();
        usleep(1000);
        $f->setStatus('INACTIVE');
        $this->assertSame('INACTIVE', $f->getStatus());
        $this->assertNotEquals($originalUpdatedAt, $f->getUpdatedAt());
    }

    public function testIsActive(): void
    {
        $f = $this->createFlag();
        // By default: status=ACTIVE, enabled=false => isActive false
        $this->assertFalse($f->isActive());
        $f->setEnabled(true);
        $this->assertTrue($f->isActive());
        $f->setStatus('INACTIVE');
        $this->assertFalse($f->isActive());
    }

    public function testIsDisabled(): void
    {
        $f = $this->createFlag();
        // default status ACTIVE, enabled false => isDisabled true (because !enabled)
        $this->assertTrue($f->isDisabled());
        $f->setEnabled(true);
        $this->assertFalse($f->isDisabled());
        $f->setStatus('DISABLED');
        $this->assertTrue($f->isDisabled());
    }

    public function testIsArchived(): void
    {
        $f = $this->createFlag();
        $this->assertFalse($f->isArchived());
        $f->setStatus('ARCHIVED');
        $this->assertTrue($f->isArchived());
    }
}
