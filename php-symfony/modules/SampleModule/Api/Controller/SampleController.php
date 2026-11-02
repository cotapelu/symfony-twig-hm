<?php

namespace App\Module\Sample\Api\Controller;

use App\Shared\Controller\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SampleController extends BaseController
{
    #[Route('/api/sample', name: 'api_sample')]
    public function index(): Response
    {
        return $this->json([
            'message' => 'Welcome to Sample module!',
        ]);
    }
}