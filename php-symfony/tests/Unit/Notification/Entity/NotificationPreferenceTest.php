<?php declare(strict_types=1);

namespace App\Tests\Unit\Notification\Entity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Notification\Entity\NotificationPreference;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class NotificationPreferenceTest extends TestCase
{
    private function createPref(?string $userId = null, ?string $type = null): NotificationPreference
    {
        return new NotificationPreference($userId ?? '1', $type ?? 'general');
    }

    public function testConstructor(): void
    {
        $pref = $this->createPref();
        $this->assertNull($pref->getId());
        $this->assertSame('1', $pref->getUserId());
        $this->assertSame('general', $pref->getType());
        $this->assertTrue($pref->isEmailEnabled());
        $this->assertTrue($pref->isPushEnabled());
        $this->assertFalse($pref->isSmsEnabled());
        $this->assertTrue($pref->isInAppEnabled());
    }

    public function testGetters(): void
    {
        $pref = $this->createPref('5', 'alert');
        $this->assertSame('5', $pref->getUserId());
        $this->assertSame('alert', $pref->getType());
    }

    public function testSetters(): void
    {
        $pref = $this->createPref();
        $pref->setEmailEnabled(false);
        $this->assertFalse($pref->isEmailEnabled());
        $pref->setPushEnabled(false);
        $this->assertFalse($pref->isPushEnabled());
        $pref->setSmsEnabled(true);
        $this->assertTrue($pref->isSmsEnabled());
        $pref->setInAppEnabled(false);
        $this->assertFalse($pref->isInAppEnabled());
    }
}
