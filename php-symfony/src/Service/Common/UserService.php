<?php declare(strict_types=1);

namespace App\Service\Common;

use App\Entity\Common\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Psr\Log\LoggerInterface;

/**
 * Service for managing users
 */
class UserService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

    /**
     * Generate unique UUIDv4
     */
    private function generateId()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x%04x%04x',
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

    /**
     * Validate email format
     */
    private function validateEmail(string $email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email format: $email");
        }
    }

    /**
     * Create a new user
     */
    public function create(
        string $email,
        string $passwordHash,
        array $roles = ['ROLE_USER'],
        ?string $phone = null,
        ?array $profile = null
    ) {
        $this->validateCreateInput($email, $passwordHash, $roles);
        $user = $this->buildUserEntity($email, $passwordHash, $roles, $phone, $profile);
        try {
            $this->em->persist($user);
            $this->em->flush();
        } catch (ORMException $e) {
            $this->logger->error('Failed to create user', ['email' => $email, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Unable to create user', 0, $e);
        }
        $this->logger->info('User created', ['id' => $user->getId(), 'email' => $email]);
        return $user;
    }

    /**
     * Find user by ID
     */
    public function get(string $id)
    {
        return $this->em->getRepository(User::class)->find($id);
    }

    /**
     * Find user by email
     */
    public function getByEmail(string $email)
    {
        $this->validateEmail($email);
        return $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
    }

    /**
     * Update user
     */
    public function update(
        string $userId,
        ?string $email = null,
        ?string $passwordHash = null,
        ?array $roles = null,
        ?string $phone = null,
        ?array $profile = null
    ) {
        $user = $this->get($userId) ?? throw new \InvalidArgumentException('User not found');

        if ($email !== null) {
            $this->applyEmailUpdate($user, $email);
        }
        if ($passwordHash !== null) {
            $this->applyPasswordHashUpdate($user, $passwordHash);
        }
        if ($roles !== null) {
            $this->applyRolesUpdate($user, $roles);
        }
        if ($phone !== null) {
            $this->applyPhoneUpdate($user, $phone);
        }
        if ($profile !== null) {
            $this->applyProfileUpdate($user, $profile);
        }

        $this->flushAndLog($userId);

        return $user;
    }

    private function flushAndLog(string $userId): void
    {
        try {
            $this->em->flush();
            $this->logger->info('User updated', ['id' => $userId]);
        } catch (ORMException $e) {
            $this->logger->error('Failed to update user', ['id' => $userId, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Unable to update user', 0, $e);
        }
    }

    /**
     * Delete user
     */
    public function delete(string $userId)
    {
        $user = $this->get($userId);
        if ($user === null) {
            return false;
        }

        $this->em->remove($user);
        try {
            $this->em->flush();
        } catch (ORMException $e) {
            $this->logger->error('Failed to delete user', ['id' => $userId, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Unable to delete user', 0, $e);
        }

        $this->logger->info('User deleted', ['id' => $userId]);

        return true;
    }

    /**
     * Find users by criteria
     */
    public function search(?string $email = null, ?array $roles = null, ?int $limit = null, ?int $offset = null)
    {
        $qb = $this->em->getRepository(User::class)->createQueryBuilder('u');
        $this->applyEmailFilter($qb, $email);
        $this->applyRolesFilter($qb, $roles);
        $qb->orderBy('u.createdAt', 'DESC');
        $this->applyPagination($qb, $limit, $offset);
        return $qb->getQuery()->getResult();
    }

    /**
     * Count users
     */
    public function count(?string $email = null, ?array $roles = null)
    {
        $qb = $this->em->getRepository(User::class)->createQueryBuilder('u');
        $qb->select('COUNT(u.id)');

        if ($email !== null) {
            $qb->where('u.email LIKE :email')
                ->setParameter('email', '%' . $email . '%');
        }

        if ($roles !== null) {
            $qb->andWhere('u.roles LIKE :roles')
                ->setParameter('roles', '%' . implode(',', $roles) . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Validate email format (public wrapper)
     */
    public function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Build user entity (helper)
     */
    private function buildUserEntity(
        string $email,
        string $passwordHash,
        array $roles,
        ?string $phone,
        ?array $profile
    ): User {
        $user = new User($this->generateId(), $email, $passwordHash);
        $user->setPhone($phone);
        $user->setRoles($roles);
        $user->setProfile($profile);
        return $user;
    }

    /**
     * Validate create input (helper)
     */
    private function validateCreateInput(string $email, string $passwordHash, array $roles): void
    {
        $this->validateEmail($email);
        if (empty($passwordHash)) {
            throw new \InvalidArgumentException('Password hash cannot be empty');
        }
        if (!is_array($roles) || empty($roles)) {
            throw new \InvalidArgumentException('At least one role is required');
        }
    }

    /** Apply email filter to query builder */
    private function applyEmailFilter($qb, ?string $email): void
    {
        if ($email !== null) {
            $qb->where('u.email LIKE :email')
                ->setParameter('email', '%' . $email . '%');
        }
    }

    /** Apply roles filter to query builder */
    private function applyRolesFilter($qb, ?array $roles): void
    {
        if ($roles !== null) {
            $qb->andWhere('u.roles LIKE :roles')
                ->setParameter('roles', '%' . implode(',', $roles) . '%');
        }
    }

    /** Apply pagination to query builder */
    private function applyPagination($qb, ?int $limit, ?int $offset): void
    {
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }
    }

    /** Apply email update to user */
    private function applyEmailUpdate(User $user, string $email): void
    {
        $this->validateEmail($email);
        $user->setEmail($email);
    }

    /** Apply password hash update to user */
    private function applyPasswordHashUpdate(User $user, string $passwordHash): void
    {
        if (empty($passwordHash)) {
            throw new \InvalidArgumentException('Password hash cannot be empty');
        }
        $user->setPasswordHash($passwordHash);
    }

    /** Apply roles update to user */
    private function applyRolesUpdate(User $user, array $roles): void
    {
        if (!is_array($roles) || empty($roles)) {
            throw new \InvalidArgumentException('At least one role is required');
        }
        $user->setRoles($roles);
    }

    /** Apply phone update to user */
    private function applyPhoneUpdate(User $user, string $phone): void
    {
        $user->setPhone($phone);
    }

    /** Apply profile update to user */
    private function applyProfileUpdate(User $user, array $profile): void
    {
        $user->setProfile($profile);
    }
}
