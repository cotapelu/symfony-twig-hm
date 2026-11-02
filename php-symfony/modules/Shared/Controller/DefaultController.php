<?php

declare(strict_types=1);

namespace App\Shared\Controller;

use App\Shared\Service\DashboardService;
use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends BaseController
{
    public function __construct(
        private DashboardService $dashboardService,
    ) {
    }

    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): Response
    {
        $indexPath = $this->getParameter('kernel.project_dir').'/public/index.html';
        if (file_exists($indexPath)) {
            return new Response(file_get_contents($indexPath), 200, ['Content-Type' => 'text/html']);
        }

        return $this->json([
            'message' => 'CommUnity API',
            'version' => '1.0.0',
            'endpoints' => [
                'messages' => '/messages',
                'groups' => '/groups',
                'calls' => '/calls',
                'files' => '/files',
            ],
        ]);
    }

    #[Route('/messages', name: 'app_messages', methods: ['GET'])]
    public function messages(): Response
    {
        return $this->redirectToRoute('app_home');
    }

    #[Route('/groups', name: 'app_groups', methods: ['GET'])]
    public function groups(): Response
    {
        return $this->redirectToRoute('app_home');
    }

    #[Route('/calls', name: 'app_calls', methods: ['GET'])]
    public function calls(): Response
    {
        return $this->redirectToRoute('app_home');
    }

    #[Route('/files', name: 'app_files', methods: ['GET'])]
    public function files(): Response
    {
        return $this->redirectToRoute('app_home');
    }

    #[Route('/api/dashboard/stats', name: 'api_dashboard_stats', methods: ['GET'])]
    public function dashboardStats(): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['error' => 'Unauthorized'], 401);
            }

            $stats = $this->dashboardService->getDashboardStats($user);

            return $this->json($stats);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to fetch dashboard stats'], 500);
        }
    }
}
