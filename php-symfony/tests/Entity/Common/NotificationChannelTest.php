<?php declare(strict_types=1);

namespace App\Tests\Entity\Common;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Entity\Common\NotificationChannel;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class NotificationChannelTest extends TestCase
{
    private function createChannel(
        string $id = 'channel-id',
        string $userId = 'user-1',
        string $channel = 'email'
    ): NotificationChannel {
        return new NotificationChannel($id, $userId, $channel);
    }

    public function testConstructor(): void
    {
        $c = $this->createChannel();
        $this->assertSame('channel-id', $c->getId());
        $this->assertSame('user-1', $c->getUserId());
        $this->assertSame('email', $c->getChannel());
        $this->assertNull($c->getEndpoint());
        $this->assertNull($c->getPreferences());
        $this->assertSame('ACTIVE', $c->getStatus());
    }

    public function testSetEndpoint(): void
    {
        $c = $this->createChannel();
        $c->setEndpoint('https://example.com/webhook');
        $this->assertSame('https://example.com/webhook', $c->getEndpoint());
        $c->setEndpoint(null);
        $this->assertNull($c->getEndpoint());
    }

    public function testSetPreferences(): void
    {
        $c = $this->createChannel();
        $prefs = ['lang' => 'vi', 'timezone' => 'UTC+7'];
        $c->setPreferences($prefs);
        $this->assertSame($prefs, $c->getPreferences());
        $c->setPreferences(null);
        $this->assertNull($c->getPreferences());
    }

    public function testSetStatus(): void
    {
        $c = $this->createChannel();
        $c->setStatus('INACTIVE');
        $this->assertSame('INACTIVE', $c->getStatus());
        $this->assertFalse($c->isActive());
        $this->assertFalse($c->isEnabled());
        $this->assertFalse($c->isDisabled());
        $c->setStatus('DISABLED');
        $this->assertTrue($c->isDisabled());
        $c->setStatus('ACTIVE');
        $this->assertTrue($c->isActive());
        $this->assertTrue($c->isEnabled());
    }
}
