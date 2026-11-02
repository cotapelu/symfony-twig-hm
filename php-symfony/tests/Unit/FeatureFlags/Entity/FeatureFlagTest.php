<?php declare(strict_types=1);

namespace App\Tests\Unit\FeatureFlags\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\FeatureFlags\Entity\FeatureFlag;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class FeatureFlagTest extends TestCase
{
    public function testConstructor(): void
    {
        $flag = new FeatureFlag('new_feature', true, 'Test feature');
        $this->assertNull($flag->getId());
        $this->assertSame('new_feature', $flag->getName());
        $this->assertTrue($flag->isEnabled());
        $this->assertSame('Test feature', $flag->getDescription());
        $this->assertInstanceOf(\DateTimeImmutable::class, $flag->getCreatedAt());
    }

    public function testConstructorDefaults(): void
    {
        $flag = new FeatureFlag('basic');
        $this->assertFalse($flag->isEnabled());
        $this->assertNull($flag->getDescription());
    }

    public function testGettersAndSetters(): void
    {
        $flag = new FeatureFlag('flag1');
        $flag->setName('updated_flag');
        $this->assertSame('updated_flag', $flag->getName());

        $flag->setEnabled(true);
        $this->assertTrue($flag->isEnabled());

        $flag->setDescription('New desc');
        $this->assertSame('New desc', $flag->getDescription());
    }
}
