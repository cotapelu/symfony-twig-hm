<?php

declare(strict_types=1);

namespace App\Shared\Service;

use App\Call\Repository\CallRepository;
use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class DashboardService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CallRepository $callRepository,
    ) {
    }

    public function getDashboardStats(User $user)
    {
        // Single query with aggregations - FIXED N+1!
        $conn = $this->entityManager->getConnection();
        $sql = "
            SELECT
                (SELECT COUNT(*) FROM communication_messages) as messages_count,
                (SELECT COUNT(*) FROM communication_conversations WHERE type = 'group') as groups_count,
                (SELECT COUNT(*) FROM communication_calls) as calls_count,
                (SELECT COUNT(*) FROM communication_notifications WHERE user_id = ? AND read_at IS NULL) as notifications_unread,
                (SELECT COUNT(DISTINCT conversation_id) FROM communication_conversation_participants WHERE user_id = ?) as conversations_count,
                (SELECT COUNT(*) FROM communication_calls WHERE caller_id = ? AND status = 'missed') as missed_calls_count
        ";
        $stmt = $conn->prepare($sql);
        $userId = $user->getId();
        $stmt->bindValue(1, $userId);
        $stmt->bindValue(2, $userId);
        $stmt->bindValue(3, $userId);
        $result = $stmt->executeQuery()->fetchAssociative();

        return [
            'stats' => [
                [
                    'title' => 'Total Messages',
                    'value' => (int) $result['messages_count'],
                    'change' => '+12%',
                    'changeType' => 'increase',
                ],
                [
                    'title' => 'Active Conversations',
                    'value' => (int) $result['conversations_count'],
                    'change' => '+5%',
                    'changeType' => 'increase',
                ],
                [
                    'title' => 'Groups',
                    'value' => (int) $result['groups_count'],
                    'change' => '+2%',
                    'changeType' => 'increase',
                ],
                [
                    'title' => 'Total Calls',
                    'value' => (int) $result['calls_count'],
                    'change' => '+8%',
                    'changeType' => 'increase',
                ],
                [
                    'title' => 'Missed Calls',
                    'value' => (int) $result['missed_calls_count'],
                    'change' => '-15%',
                    'changeType' => 'decrease',
                ],
            ],
            'recentActivity' => $this->getRecentActivity($user),
            'charts' => [
                'callsPerDay' => $this->getCallsPerDayChart($user),
                'activityBreakdown' => $this->getActivityBreakdownChart($user),
            ],
        ];
    }

    private function getRecentActivity(User $user)
    {
        // Get recent calls and group activities
        $recentCalls = $this->callRepository->findUserCalls($user, 1, 5);

        $activities = [];

        foreach ($recentCalls as $call) {
            $activities[] = [
                'type' => 'call',
                'description' => 'audio' === $call->getType() ? 'Made an audio call' : 'Made a video call',
                'timestamp' => $call->getStartedAt()->format('c'),
                'duration' => $call->getDuration() ? gmdate('i:s', $call->getDuration()) : null,
            ];
        }

        // Sort by timestamp descending
        usort($activities, fn ($a, $b) => strtotime($b['timestamp']) - strtotime($a['timestamp']));

        return array_slice($activities, 0, 10);
    }

    private function getCallsPerDayChart(User $user)
    {
        // Get call count for last 7 days
        $data = [];
        for ($i = 6; $i >= 0; --$i) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $count = $this->callRepository->countCallsByDate($user, $date);
            $data[] = [
                'date' => $date,
                'count' => $count,
            ];
        }

        return $data;
    }

    private function getActivityBreakdownChart(User $user)
    {
        $conn = $this->entityManager->getConnection();
        $result = $conn->executeQuery("
            SELECT
                (SELECT COUNT(*) FROM communication_messages) as messages,
                (SELECT COUNT(*) FROM communication_calls) as calls,
                (SELECT COUNT(*) FROM communication_conversations WHERE type = 'group') as groups
        ")->fetchAssociative();

        return [
            ['name' => 'Messages', 'value' => (int) $result['messages'], 'color' => '#3b82f6'],
            ['name' => 'Calls', 'value' => (int) $result['calls'], 'color' => '#10b981'],
            ['name' => 'Groups', 'value' => (int) $result['groups'], 'color' => '#f59e0b'],
        ];
    }
}
