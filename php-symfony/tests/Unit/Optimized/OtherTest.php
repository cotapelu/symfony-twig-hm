<?php

declare(strict_types=1);

namespace App\Tests\Unit\Optimized;

use PHPUnit\Framework\TestCase;
use App\Core\Entity\LoyaltyProgram;
use App\Core\Entity\LoyaltyAccount;
use App\Core\Entity\Achievement;
use App\Core\Entity\Notification;
use App\Core\Entity\NotificationChannel;
use App\Core\Entity\MediaFolder;
use App\Core\Entity\MediaFile;
use App\Core\Entity\Event;
use App\Core\Entity\PollSurvey;

// Load the file containing all entity classes (non-PSR-4)
require_once __DIR__.'/../../../src/Entity/Optimized/Other.php';

class OtherTest extends TestCase
{
    public function testLoyaltyProgram(): void
    {
        $id = 'prog-'.uniqid();
        $name = 'Test Program';
        $type = LoyaltyProgram::TYPE_POINTS;

        $program = new LoyaltyProgram($id, $name, $type);

        $this->assertSame($id, $program->getId());
        $this->assertSame($name, $program->getName());
        $this->assertSame($type, $program->getType());
        $this->assertNull($program->getRules());
        $this->assertSame(LoyaltyProgram::STATUS_ACTIVE, $program->getStatus());
    }

    public function testLoyaltyAccount(): void
    {
        $id = 'acc-'.uniqid();
        $userId = 'user-'.uniqid();
        $programId = 'prog-'.uniqid();

        $account = new LoyaltyAccount($id, $userId, $programId);

        $this->assertSame($id, $account->getId());
        $this->assertSame($userId, $account->getUserId());
        $this->assertSame($programId, $account->getProgramId());
        $this->assertSame(0, $account->getPoints());
        $this->assertSame(LoyaltyAccount::TIER_BRONZE, $account->getTier());
        $this->assertNull($account->getMetadata());
    }

    public function testLoyaltyAccountAddPoints(): void
    {
        $account = new LoyaltyAccount('id', 'uid', 'pid');
        $account->addPoints(100);
        $this->assertSame(100, $account->getPoints());
    }

    public function testLoyaltyAccountDeductPoints(): void
    {
        $account = new LoyaltyAccount('id', 'uid', 'pid');
        $account->addPoints(100);
        $account->deductPoints(30);
        $this->assertSame(70, $account->getPoints());
    }

    public function testAchievement(): void
    {
        $id = 'ach-'.uniqid();
        $userId = 'user-'.uniqid();
        $programId = 'prog-'.uniqid();
        $type = 'BADGE';
        $name = 'Test Achievement';

        $ach = new Achievement($id, $userId, $programId, $type, $name);

        $this->assertSame($id, $ach->getId());
        $this->assertSame($userId, $ach->getUserId());
        $this->assertSame($programId, $ach->getProgramId());
        $this->assertSame($type, $ach->getType());
        $this->assertSame($name, $ach->getName());
        $this->assertNull($ach->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $ach->getEarnedAt());
    }

    public function testNotification(): void
    {
        $id = 'notif-'.uniqid();
        $userId = 'user-'.uniqid();
        $type = 'INFO';
        $title = 'Test Title';
        $body = 'Test Body';

        $notif = new Notification($id, $userId, $type, $title, $body);

        $this->assertSame($id, $notif->getId());
        $this->assertSame($userId, $notif->getUserId());
        $this->assertSame($type, $notif->getType());
        $this->assertSame($title, $notif->getTitle());
        $this->assertSame($body, $notif->getBody());
        $this->assertNull($notif->getData());
        $this->assertNull($notif->getReadAt());
        $this->assertInstanceOf(\DateTime::class, $notif->getCreatedAt());
    }

    public function testNotificationMarkAsRead(): void
    {
        $notif = new Notification('id', 'uid', 'type', 'title', 'body');
        $this->assertFalse($notif->isRead());
        $result = $notif->markAsRead();
        $this->assertInstanceOf(\DateTime::class, $notif->getReadAt());
        $this->assertTrue($notif->isRead());
        $this->assertSame($notif, $result); // fluent
    }

    public function testNotificationChannel(): void
    {
        $id = 'chan-'.uniqid();
        $userId = 'user-'.uniqid();
        $channel = NotificationChannel::CHANNEL_EMAIL;
        $endpoint = 'user@example.com';

        $chan = new NotificationChannel($id, $userId, $channel, $endpoint);

        $this->assertSame($id, $chan->getId());
        $this->assertSame($userId, $chan->getUserId());
        $this->assertSame($channel, $chan->getChannel());
        $this->assertSame($endpoint, $chan->getEndpoint());
        $this->assertNull($chan->getPreferences());
        $this->assertSame('ACTIVE', $chan->getStatus());
    }

    public function testMediaFolder(): void
    {
        $id = 'folder-'.uniqid();
        $userId = 'user-'.uniqid();
        $name = 'My Folder';

        $folder = new MediaFolder($id, $userId, $name);

        $this->assertSame($id, $folder->getId());
        $this->assertSame($userId, $folder->getUserId());
        $this->assertNull($folder->getParentId());
        $this->assertSame($name, $folder->getName());
        $this->assertInstanceOf(\DateTime::class, $folder->getCreatedAt());
    }

    public function testMediaFolderSetParentId(): void
    {
        $folder = new MediaFolder('id', 'uid', 'name');
        $parentId = 'parent-'.uniqid();
        $result = $folder->setParentId($parentId);
        $this->assertSame($parentId, $folder->getParentId());
        $this->assertSame($folder, $result); // fluent
    }

    public function testMediaFile(): void
    {
        $id = 'file-'.uniqid();
        $userId = 'user-'.uniqid();
        $filename = 'test.jpg';
        $mime = 'image/jpeg';
        $url = '/uploads/test.jpg';

        $file = new MediaFile($id, $userId, $filename, $mime, $url);

        $this->assertSame($id, $file->getId());
        $this->assertNull($file->getFolderId());
        $this->assertSame($userId, $file->getUserId());
        $this->assertSame($filename, $file->getFilename());
        $this->assertSame($mime, $file->getMimeType());
        $this->assertSame(0, $file->getSize());
        $this->assertSame($url, $file->getUrl());
        $this->assertNull($file->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $file->getCreatedAt());
    }

    public function testEvent(): void
    {
        $id = 'evt-'.uniqid();
        $entityType = 'USER';
        $entityId = 'user-'.uniqid();
        $eventType = 'REGISTERED';

        $event = new Event($id, $entityType, $entityId, $eventType);

        $this->assertSame($id, $event->getId());
        $this->assertSame($entityType, $event->getEntityType());
        $this->assertSame($entityId, $event->getEntityId());
        $this->assertSame($eventType, $event->getEventType());
        $this->assertNull($event->getData());
        $this->assertInstanceOf(\DateTime::class, $event->getCreatedAt());
    }

    public function testEventSetData(): void
    {
        $event = new Event('id', 'type', 'eid', 'evt');
        $data = ['key' => 'value'];
        $result = $event->setData($data);
        $this->assertSame($data, $event->getData());
        $this->assertSame($event, $result); // fluent
    }

    public function testPollSurvey(): void
    {
        $id = 'poll-'.uniqid();
        $userId = 'user-'.uniqid();
        $type = PollSurvey::TYPE_POLL;
        $question = 'Favorite color?';
        $options = ['Red', 'Blue'];

        $poll = new PollSurvey($id, $userId, $type, $question, $options);

        $this->assertSame($id, $poll->getId());
        $this->assertSame($userId, $poll->getUserId());
        $this->assertSame($type, $poll->getType());
        $this->assertSame($question, $poll->getQuestion());
        $this->assertSame($options, $poll->getOptions());
        $this->assertNull($poll->getData());
        $this->assertSame(PollSurvey::STATUS_DRAFT, $poll->getStatus());
        $this->assertNull($poll->getExpiresAt());
        $this->assertInstanceOf(\DateTime::class, $poll->getCreatedAt());
    }

    public function testPollSurveySetStatus(): void
    {
        $poll = new PollSurvey('id', 'uid', PollSurvey::TYPE_SURVEY, 'Q', []);
        $poll->setStatus(PollSurvey::STATUS_ACTIVE);
        $this->assertSame(PollSurvey::STATUS_ACTIVE, $poll->getStatus());
        $poll->setStatus(PollSurvey::STATUS_CLOSED);
        $this->assertSame(PollSurvey::STATUS_CLOSED, $poll->getStatus());
    }
}
