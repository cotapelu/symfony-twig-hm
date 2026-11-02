<?php

declare(strict_types=1);

namespace App\Platform\ApiGateway\Controller;

use Platform\ApiGateway\Service\GatewayService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GatewayController extends AbstractController
{
    private GatewayService $gateway;

    public function __construct(GatewayService $gateway)
    {
        $this->gateway = $gateway;
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            GatewayService::class,
        ]);
    }

    #[Route('/gateway/health', name: 'gateway_health', methods: ['GET'])]
    public function health(): Response
    {
        return $this->json([
            'status' => 'healthy',
            'service' => 'api-gateway',
            'timestamp' => date('c'),
        ]);
    }

    #[Route('/gateway/routes', name: 'gateway_routes', methods: ['GET'])]
    public function routes(): Response
    {
        $routeList = [];
        foreach ($this->gateway->getRouteConfig() as $prefix => $target) {
            $routeList[] = [
                'prefix' => $prefix,
                'target' => $target,
            ];
        }

        return $this->json([
            'routes' => $routeList,
        ]);
    }

    #[Route('/api/{prefix}/{path}', name: 'gateway_proxy', requirements: ['prefix' => 'auth|wallet|banking|qrpayment|bills|location|sync|events|marketplace|coreservices|delivery|tracking|notifications|courier|credit|fraud|vc|key|ca|telemedicine|chatbot|ai|flights|hotels|booking|calls|voicemail|webrtc|moderation|streaming|storage|secure'], methods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'])]
    #[Route('/api/{prefix}', name: 'gateway_proxy_root', requirements: ['prefix' => 'auth|wallet|banking|qrpayment|bills|location|sync|events|marketplace|coreservices|delivery|tracking|notifications|courier|credit|fraud|vc|key|ca|telemedicine|chatbot|ai|flights|hotels|booking|calls|voicemail|webrtc|moderation|streaming|storage|secure'], methods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'])]
    public function proxy(Request $request, string $prefix, ?string $path = null): Response
    {
        $fullPath = '/api/' . $prefix . ($path ? '/' . $path : '');
        
        $result = $this->gateway->proxy(
            $request->getMethod(),
            $fullPath,
            $request->headers->all(),
            $request->getContent() ?: null
        );

        return new Response(
            $result['body'],
            $result['status'],
            ['Content-Type' => 'application/json']
        );
    }
}
