<?php

declare(strict_types=1);

namespace App\Tests\Integration\Utility;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

use App\Tests\Integration\DatabaseIntegrationTestCase;
use App\Utility\Entity\PollSurvey;

#[AllowMockObjectsWithoutExpectations]
class PollSurveyIntegrationDbTest extends DatabaseIntegrationTestCase
{
    public function testCreateAndRetrievePollSurvey(): void
    {
        $poll = new PollSurvey(
            type: PollSurvey::TYPE_POLL,
            question: 'What is your favorite color?'
        );
        $poll->setOptions(['Red', 'Blue', 'Green'])
            ->setStatus(PollSurvey::STATUS_ACTIVE)
            ->setExpiresAt(new \DateTime('+1 week'));

        $em = self::getEntityManager();
        $em->persist($poll);
        $em->flush();

        $this->assertNotNull($poll->getId());

        $found = $em->getRepository(PollSurvey::class)->find($poll->getId());

        $this->assertNotNull($found);
        $this->assertSame(PollSurvey::TYPE_POLL, $found->getType());
        $this->assertSame('What is your favorite color?', $found->getQuestion());
        $this->assertSame(['Red', 'Blue', 'Green'], $found->getOptions());
        $this->assertSame(PollSurvey::STATUS_ACTIVE, $found->getStatus());
        $this->assertInstanceOf(\DateTime::class, $found->getExpiresAt());
    }

    public function testUpdatePollSurvey(): void
    {
        $poll = new PollSurvey(
            type: PollSurvey::TYPE_SURVEY,
            question: 'Feedback on service?'
        );

        $em = self::getEntityManager();
        $em->persist($poll);
        $em->flush();

        $poll->setStatus(PollSurvey::STATUS_CLOSED);
        $em->flush();

        $found = $em->getRepository(PollSurvey::class)->find($poll->getId());
        $this->assertSame(PollSurvey::STATUS_CLOSED, $found->getStatus());
    }

    public function testDeletePollSurvey(): void
    {
        $poll = new PollSurvey(
            type: PollSurvey::TYPE_QUIZ,
            question: 'Quiz question?'
        );

        $em = self::getEntityManager();
        $em->persist($poll);
        $em->flush();

        $id = $poll->getId();

        $em->remove($poll);
        $em->flush();

        $found = $em->getRepository(PollSurvey::class)->find($id);
        $this->assertNull($found);
    }

    public function testPollSurveyWithNullUser(): void
    {
        $poll = new PollSurvey(
            type: PollSurvey::TYPE_POLL,
            question: 'Should we add feature X?'
        );

        $em = self::getEntityManager();
        $em->persist($poll);
        $em->flush();

        $this->assertNull($poll->getUser());
    }
}
