<?php declare(strict_types=1);

namespace App\Shared\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\JsonResponse;

class CsrfProtectionMiddleware
{
    private array $trustedOrigins;

    public function __construct(array|string $trustedOrigins = [])
    {
        if (is_string($trustedOrigins)) {
            $this->trustedOrigins = array_map('trim', explode(',', $trustedOrigins));
        } else {
            $this->trustedOrigins = $trustedOrigins;
        }
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Disable CSRF validation in test environment
        if (($_SERVER['APP_ENV'] ?? getenv('APP_ENV') ?? 'prod') === 'test') {
            return;
        }

        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        $requestUri = $request->getRequestUri();
        $isApiRequest = str_starts_with($requestUri, '/api/');

        if (!$isApiRequest) {
            return;
        }

        // Skip CSRF for requests using Authorization header (JWT, API token)
        if ($request->headers->has('Authorization')) {
            return;
        }

        $method = $request->getMethod();
        $stateChangingMethods = ['POST', 'PUT', 'DELETE', 'PATCH'];

        if (!in_array($method, $stateChangingMethods, true)) {
            return;
        }

        $exemptRoutes = [
            '/api/auth/login',
            '/api/auth/register',
            '/api/auth/social',
            '/api/auth/forgot-password',
            '/api/auth/reset-password',
            '/api/auth/verify-email',
            '/api/login',
            '/api/register',
        ];

        foreach ($exemptRoutes as $route) {
            if (str_starts_with($requestUri, $route)) {
                return;
            }
        }

        $this->validateCsrfRequest($request, $event);
    }

    private function validateCsrfRequest(Request $request, RequestEvent $event): void
    {
        $origin = $request->headers->get('Origin');
        $referer = $request->headers->get('Referer');
        $isValid = false;

        if (!empty($origin)) {
            $isValid = $this->isOriginTrusted($origin);
        } elseif (!empty($referer)) {
            $isValid = $this->isOriginTrusted($referer);
        }

        if (!$isValid) {
            $event->setResponse(new JsonResponse([
                'error' => 'CSRF validation failed',
                'message' => 'Invalid origin or referer header'
            ], 403));
        }
    }

    private function isOriginTrusted(string $url): bool
    {
        if (empty($this->trustedOrigins)) {
            return true;
        }

        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';

        if (empty($host)) {
            return false;
        }

        foreach ($this->trustedOrigins as $trustedOrigin) {
            $parsedTrusted = parse_url($trustedOrigin);
            $trustedHost = $parsedTrusted['host'] ?? '';

            if ($host === $trustedHost || $this->isSubdomain($host, $trustedHost)) {
                return true;
            }
        }

        return false;
    }

    private function isSubdomain(string $host, string $parentDomain): bool
    {
        // Exact host is not a subdomain
        if ($host === $parentDomain) {
            return false;
        }
        return str_ends_with('.' . $host, '.' . $parentDomain);
    }
}
