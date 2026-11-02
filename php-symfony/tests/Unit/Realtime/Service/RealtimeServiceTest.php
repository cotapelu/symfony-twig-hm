<?php declare(strict_types=1);

namespace App\Tests\Unit\Realtime\Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Realtime\Service\RealtimeService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[AllowMockObjectsWithoutExpectations]
class RealtimeServiceTest extends TestCase
{
    private HubInterface&MockObject $hub;
    private RealtimeService $service;

    protected function setUp(): void
    {
        $this->hub = $this->createMock(HubInterface::class);
        $this->service = new RealtimeService($this->hub);
    }

    public function testPublishUpdatePublishesUpdateWithCorrectTopicAndData(): void
    {
        $topic = 'test/topic';
        $data = ['key' => 'value', 'number' => 123];

        // Expect publish to be called once with an Update instance
        $this->hub->expects($this->once())
            ->method('publish')
            ->with(
                $this->callback(function (Update $update) use ($topic, $data) {
                    return $update->getTopics()[0] === $topic && json_decode($update->getData(), true) === $data;
                })
            );

        $this->service->publishUpdate($topic, $data);
    }

    public function testNotifyMessageSentPublishesMessageSentEvent(): void
    {
        $conversationId = 'conv-456';
        $messageData = ['id' => 789, 'text' => 'Hello'];

        $this->hub->expects($this->once())
            ->method('publish')
            ->with(
                $this->callback(function (Update $update) use ($conversationId, $messageData) {
                    $expectedTopic = "conversation/{$conversationId}";
                    $payload = json_decode($update->getData(), true);
                    return $update->getTopics()[0] === $expectedTopic
                        && $payload['type'] === 'message_sent'
                        && $payload['message'] === $messageData;
                })
            );

        $this->service->notifyMessageSent($conversationId, $messageData);
    }
}
