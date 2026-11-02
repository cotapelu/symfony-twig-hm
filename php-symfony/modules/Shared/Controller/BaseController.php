<?php
declare(strict_types=1);
namespace App\Shared\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Persistence\ManagerRegistry;
use UnitEnum;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
abstract class BaseController extends AbstractController
{
    public function __construct()
    {
        if (!isset($this->container)) {
            $this->container = new ContainerBuilder();
        }
    }
    /**
     * @deprecated Use setTestUser() for test user injection
     */
    protected ?UserInterface $user = null;
    protected ?UserInterface $testUser = null;
    protected ?AuthorizationCheckerInterface $authChecker = null;

    public function setTestUser(?UserInterface $user): void
    {
        $this->testUser = $user;
        $this->user = $user; // Backward compatibility for older tests
    }

    /**
     * Backward compatibility for older tests.
     */
    public function setAuthenticatedUser(?UserInterface $user): void
    {
        $this->testUser = $user;
    }
    protected function json(mixed $data, int $status = 200, array $headers = [], array $context = []): JsonResponse
    {
        if (!$this->container->has('serializer')) {
            return new JsonResponse($data, $status, $headers);
        }
        return parent::json($data, $status, $headers, $context);
    }
    public function getUser(): ?UserInterface
    {
        if ($this->testUser !== null) {
            return $this->testUser;
        }
        if (!is_object($this->container)) {
            return null;
        }
        if (!$this->container->has('security.token_storage')) {
            return null;
        }
        $tokenStorage = $this->container->get('security.token_storage');
        $token = $tokenStorage->getToken();
        if (null === $token) {
            return null;
        }
        $user = $token->getUser();
        return $user instanceof UserInterface ? $user : null;
    }
    public function getDoctrine(): ?ManagerRegistry
    {
        if (!$this->container->has('doctrine')) {
            return null;
        }
        return $this->container->get('doctrine');
    }
    public function isGranted($attributes, $subject = null): bool
    {
        if (isset($this->authChecker) && $this->authChecker instanceof AuthorizationCheckerInterface) {
            return $this->authChecker->isGranted($attributes, $subject);
        }
        return true;
    }

    public function getParameter(string $name): UnitEnum|array|string|int|float|bool|null
    {
        return null;
    }

    protected function get(string $service): mixed
    {
        return null;
    }

    protected function has(string $id): bool
    {
        return false;
    }

    protected function decodeJsonOr400(Request $request): array|JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
        return $data ?? [];
    }
}