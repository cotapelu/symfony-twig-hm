<?php

declare(strict_types=1);

namespace App\GDPR\Controller;

use App\GDPR\Service\GdprService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class GdprController extends BaseController
{
    public function __construct(private GdprService $gdprService)
    {
    }

    #[Route('/api/gdpr/export', name: 'gdpr_export', methods: ['GET'])]
    public function export(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], 401);
        }
        $userId = $user->getUserIdentifier();

        $data = $this->gdprService->exportUserData($userId);

        return $this->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="data-export.json"',
        ]);
    }

    #[Route('/api/gdpr/account', name: 'gdpr_delete_account', methods: ['DELETE'])]
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], 401);
        }
        $userId = $user->getUserIdentifier();

        $this->gdprService->deleteUserData($userId);

        return $this->json(['status' => 'account_deleted']);
    }
}
