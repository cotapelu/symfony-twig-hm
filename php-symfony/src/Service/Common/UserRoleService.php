<?php declare(strict_types=1);

namespace App\Service\Common;

use App\Entity\Common\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for managing user roles
 */
class UserRoleService
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_OFFICER = 'officer';
    public const ROLE_CITIZEN = 'citizen';
    public const ROLE_DRIVER = 'driver';
    public const ROLE_MERCHANT = 'merchant';
    public const ROLE_USER = 'user';

    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

    public function grantRole(
        string $userId,
        string $role,
        ?string $grantedBy = null,
        ?\DateTime $expiresAt = null,
        ?array $metadata = null
    ) {
        $existing = $this->findExistingRole($userId, $role);
        if ($existing !== null) {
            return $this->handleExistingRole($existing, $grantedBy, $expiresAt, $metadata);
        }
        return $this->createNewRole($userId, $role, $grantedBy, $expiresAt, $metadata);
    }

    public function revokeRole(string $userId, string $role)
    {
        $userRole = $this->em->getRepository(UserRole::class)->findOneBy(['userId' => $userId, 'role' => $role]);
        if ($userRole === null) return false;
        $this->em->remove($userRole);
        $this->em->flush();
        $this->logger->info('User role revoked', ['user_id' => $userId, 'role' => $role]);
        return true;
    }

    public function getRole(string $id)
    {
        return $this->em->getRepository(UserRole::class)->find($id);
    }

    public function getUserRoles(string $userId)
    {
        return $this->em->getRepository(UserRole::class)->findBy(['userId' => $userId]);
    }

    public function getActiveUserRoles(string $userId)
    {
        return array_filter($this->getUserRoles($userId), fn($role) => $role->isActive());
    }

    public function hasRole(string $userId, string $role)
    {
        $userRole = $this->em->getRepository(UserRole::class)->findOneBy(['userId' => $userId, 'role' => $role]);
        return $userRole !== null && $userRole->isActive();
    }

    public function hasAnyRole(string $userId, array $roles)
    {
        foreach ($roles as $role) {
            if ($this->hasRole($userId, $role)) return true;
        }
        return false;
    }

    public function revokeAllRoles(string $userId)
    {
        $roles = $this->getUserRoles($userId);
        $count = count($roles);
        foreach ($roles as $role) $this->em->remove($role);
        $this->em->flush();
        $this->logger->info('All user roles revoked', ['user_id' => $userId, 'count' => $count]);
        return $count;
    }

    public function getUsersByRole(string $role)
    {
        return $this->em->getRepository(UserRole::class)->findBy(['role' => $role]);
    }

    public function cleanupExpiredRoles()
    {
        $expiredRoles = $this->getExpiredRoles();
        $count = count($expiredRoles);
        $this->removeRoles($expiredRoles);
        $this->logger->info('Expired roles cleaned up', ['count' => $count]);
        return $count;
    }

    public function getAvailableRoles()
    {
        return [self::ROLE_ADMIN, self::ROLE_OFFICER, self::ROLE_CITIZEN, self::ROLE_DRIVER, self::ROLE_MERCHANT, self::ROLE_USER];
    }

    public function isValidRole(string $role)
    {
        return in_array($role, $this->getAvailableRoles(), true);
    }

    private function generateId()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }

    private function findExistingRole(string $userId, string $role): ?UserRole
    {
        return $this->em->getRepository(UserRole::class)->findOneBy(['userId' => $userId, 'role' => $role]);
    }

    private function handleExistingRole(
        UserRole $existing,
        ?string $grantedBy,
        ?\DateTime $expiresAt,
        ?array $metadata
    ): UserRole {
        if ($existing->isExpired()) {
            $existing->setGrantedBy($grantedBy);
            $existing->setGrantedAt(new \DateTime());
            $existing->setExpiresAt($expiresAt);
            $existing->setMetadata($metadata);
            $this->em->flush();
            $this->logger->info('User role re-granted', ['user_id' => $existing->getUserId(), 'role' => $existing->getRole(), 'granted_by' => $grantedBy]);
        }
        return $existing;
    }

    private function createNewRole(
        string $userId,
        string $role,
        ?string $grantedBy,
        ?\DateTime $expiresAt,
        ?array $metadata
    ): UserRole {
        $userRole = new UserRole($this->generateId(), $userId, $role);
        $userRole->setGrantedBy($grantedBy);
        $userRole->setExpiresAt($expiresAt);
        $userRole->setMetadata($metadata);
        $this->em->persist($userRole);
        $this->em->flush();
        $this->logger->info('User role granted', ['user_id' => $userId, 'role' => $role, 'granted_by' => $grantedBy, 'expires_at' => $expiresAt?->format(\DateTime::ATOM)]);
        return $userRole;
    }

    private function getExpiredRoles(): array
    {
        $now = new \DateTime();
        return $this->em->getRepository(UserRole::class)
            ->createQueryBuilder('r')
            ->where('r.expiresAt < :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    private function removeRoles(array $roles): void
    {
        foreach ($roles as $role) $this->em->remove($role);
        $this->em->flush();
    }
}
