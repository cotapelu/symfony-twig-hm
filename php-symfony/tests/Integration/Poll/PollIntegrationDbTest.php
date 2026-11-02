<?php

declare(strict_types=1);

namespace App\Tests\Integration\Poll;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Poll\Entity\Poll;

#[AllowMockObjectsWithoutExpectations]
class PollIntegrationDbTest extends DatabaseIntegrationTestCase
{
    public function testCreateAndRetrievePoll(): void
    {
        $poll = new Poll(
            creatorId: 123,
            question: 'What is your favorite color?',
            options: ['Red', 'Blue', 'Green'],
            description: 'Pick one',
            allowMultiple: false,
            expiresAt: null
        );

        $em = self::getEntityManager();
        $em->persist($poll);
        $em->flush();

        $this->assertNotNull($poll->getId());

        $found = $em->getRepository(Poll::class)->find($poll->getId());
        $this->assertNotNull($found);
        $this->assertSame(123, $found->getCreatorId());
        $this->assertSame('What is your favorite color?', $found->getQuestion());
        $this->assertSame(['Red', 'Blue', 'Green'], array_column($found->getOptions(), 'text'));
        $this->assertSame([0, 0, 0], array_column($found->getOptions(), 'votes'));
        $this->assertSame('Pick one', $found->getDescription());
        $this->assertFalse($found->getAllowMultiple());
        $this->assertNull($found->getExpiresAt());
        $this->assertSame('active', $found->getStatus());
    }

    public function testUpdatePoll(): void
    {
        $poll = new Poll(
            creatorId: 456,
            question: 'Initial question',
            options: ['A', 'B']
        );

        $em = self::getEntityManager();
        $em->persist($poll);
        $em->flush();

        $poll->setQuestion('Updated question')
            ->setAllowMultiple(true);
        $em->flush();

        $found = $em->getRepository(Poll::class)->find($poll->getId());
        $this->assertSame('Updated question', $found->getQuestion());
        $this->assertTrue($found->getAllowMultiple());
    }

    public function testDeletePoll(): void
    {
        $poll = new Poll(
            creatorId: 789,
            question: 'To delete',
            options: ['Yes', 'No']
        );

        $em = self::getEntityManager();
        $em->persist($poll);
        $em->flush();

        $id = $poll->getId();
        $em->remove($poll);
        $em->flush();

        $found = $em->getRepository(Poll::class)->find($id);
        $this->assertNull($found);
    }

    public function testPollIdIsAutoIncrement(): void
    {
        $poll = new Poll(
            creatorId: 1,
            question: 'ID test',
            options: []
        );

        $em = self::getEntityManager();
        $em->persist($poll);
        $em->flush();

        $this->assertIsInt($poll->getId());
        $this->assertGreaterThan(0, $poll->getId());
    }
}
