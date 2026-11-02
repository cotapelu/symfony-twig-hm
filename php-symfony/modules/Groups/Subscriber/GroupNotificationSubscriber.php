<?php

namespace App\Groups\Subscriber;

use App\Groups\DomainEvent\GroupCreatedEvent;
use App\Groups\DomainEvent\GroupJoinedEvent;
use App\Groups\DomainEvent\GroupLeftEvent;
use App\Groups\DomainEvent\MemberRoleChangedEvent;
use App\Notification\Service\NotificationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GroupNotificationSubscriber implements EventSubscriberInterface
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            GroupCreatedEvent::class => 'onGroupCreated',
            GroupJoinedEvent::class => 'onGroupJoined',
            GroupLeftEvent::class => 'onGroupLeft',
            MemberRoleChangedEvent::class => 'onMemberRoleChanged',
        ];
    }

    public function onGroupCreated(GroupCreatedEvent $event): void
    {
        $this->notificationService->createNotification(
            $event->getOwnerId(),
            'Group Created',
            "Your group has been created successfully",
            'groups',
            ['groupId' => $event->getGroupId()]
        );
    }

    public function onGroupJoined(GroupJoinedEvent $event): void
    {
        $this->notificationService->createNotification(
            $event->getUserId(),
            'Group Joined',
            "You have joined a new group",
            'groups',
            ['groupId' => $event->getGroupId(), 'memberId' => $event->getMemberId()]
        );
    }

    public function onGroupLeft(GroupLeftEvent $event): void
    {
        $this->notificationService->createNotification(
            $event->getUserId(),
            'Group Left',
            "You have left a group",
            'groups',
            ['groupId' => $event->getGroupId(), 'memberId' => $event->getMemberId()]
        );
    }

    public function onMemberRoleChanged(MemberRoleChangedEvent $event): void
    {
        $this->notificationService->createNotification(
            $event->getUserId(),
            'Role Changed',
            "Your role has been changed from {$event->getOldRole()} to {$event->getNewRole()}",
            'groups',
            [
                'groupId' => $event->getGroupId(),
                'memberId' => $event->getMemberId(),
                'actorId' => $event->getActorId(),
            ]
        );
    }
}