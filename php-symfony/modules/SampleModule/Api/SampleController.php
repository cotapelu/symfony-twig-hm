<?php

declare(strict_types=1);

namespace App\SampleModule\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Sample Controller demonstrating API layer responsibilities
 * Handles HTTP requests and responses, delegates to Application layer
 */
class SampleController extends AbstractController
{
    /**
     * @Route("/api/sample", name="sample_index", methods={"GET"})
     */
    public function index(): JsonResponse
    {
        // In a real implementation, this would delegate to Application layer
        return $this->json([
            'message' => 'Sample endpoint response',
            'status' => 'success'
        ]);
    }
    
    /**
     * @Route("/api/sample/create", name="sample_create", methods={"POST"})
     */
    public function create(): JsonResponse
    {
        // Would receive DTO, validate, and delegate to Application layer
        return $this->json([
            'message' => 'Sample created successfully',
            'status' => 'success'
        ]);
    }
}