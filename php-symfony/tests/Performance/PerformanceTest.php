<?php

declare(strict_types=1);

namespace App\Tests\Performance;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Message\Service\MessageService;
use App\Media\Service\FileStorageService;
use App\Conversation\Service\ConversationService;
use App\Shared\Service\CacheWarmupService;
use App\Message\Repository\MessageRepositoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[AllowMockObjectsWithoutExpectations]
class PerformanceTest extends WebTestCase
{
    private const PERFORMANCE_THRESHOLD_MS = 1000;
    private const PAGINATION_PAGE_SIZE = 50;

    private MessageService $messageService;
    private FileStorageService $fileStorageService;
    private CacheWarmupService $cacheWarmupService;
    private ConversationService $conversationService;
    private MessageRepositoryInterface $messageRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->messageService = $container->get(MessageService::class);
        $this->fileStorageService = $container->get(FileStorageService::class);
        $this->cacheWarmupService = $container->get(CacheWarmupService::class);
        $this->conversationService = $container->get(ConversationService::class);
        $this->messageRepository = $container->get(MessageRepositoryInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testMessagePaginationPerformance(): void
    {
        $conversationId = 'test-conversation-1';
        $userId = 'test-user-1';

        $startTime = microtime(true);
        $page1 = $this->messageService->getConversationMessages($conversationId, $userId, 1, self::PAGINATION_PAGE_SIZE);
        $durationMs = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(self::PERFORMANCE_THRESHOLD_MS, $durationMs, 'Pagination should complete in less than 1s');
    }

    public function testCursorPaginationPerformance(): void
    {
        $conversationId = 'test-conversation-2';
        $userId = 'test-user-2';

        $startTime = microtime(true);
        $result1 = $this->messageService->getConversationMessagesCursor($conversationId, $userId, null, 50);
        $duration1Ms = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(self::PERFORMANCE_THRESHOLD_MS, $duration1Ms, 'Cursor pagination should complete in less than 1s');
        $this->assertArrayHasKey('data', $result1);
        $this->assertArrayHasKey('has_more', $result1);
        $this->assertArrayHasKey('next_cursor', $result1);
    }

    public function testCacheWarmupPerformance(): void
    {
        $userId = 'test-user-warmup';

        $startTime = microtime(true);
        $this->cacheWarmupService->warmupUserCache($userId);
        $durationMs = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(2000, $durationMs, 'Cache warmup should complete in less than 2s');
    }

    public function testFileUploadPerformance(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'perf_test_');
        file_put_contents($tempFile, str_repeat('test data', 1024 * 100));

        $uploadedFile = new UploadedFile(
            $tempFile,
            'test-file.txt',
            'text/plain',
            null,
            true
        );

        $startTime = microtime(true);
        $result = $this->fileStorageService->moveUploadedFile($uploadedFile->getPathname(), 'test-upload.txt');
        $durationMs = (microtime(true) - $startTime) * 1000;

        unlink($tempFile);
        if (file_exists($result)) {
            unlink($result);
        }

        $this->assertLessThan(500, $durationMs, 'File upload should complete in less than 500ms');
    }

    public function testBatchQueryPerformance(): void
    {
        $conversationIds = ['conv-1', 'conv-2', 'conv-3', 'conv-4', 'conv-5'];

        $startTime = microtime(true);
        $results = $this->messageRepository->findLatestMessagesByConversationIds($conversationIds);
        $durationMs = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(self::PERFORMANCE_THRESHOLD_MS, $durationMs, 'Batch query should complete in less than 1s');
        $this->assertIsArray($results);
    }
}
