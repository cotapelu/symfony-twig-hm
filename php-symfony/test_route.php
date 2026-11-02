<?php
// Test route manually
require __DIR__.'/tests/bootstrap.php';
use Symfony\Component\HttpFoundation\Request;
use App\Kernel;
try {
    $kernel = new Kernel('test', true);
    $kernel->boot();
    $request = Request::create('/api/security/incidents', 'GET');
    $response = $kernel->handle($request);
    echo "Status: ".$response->getStatusCode().PHP_EOL;
    echo $response->getContent();
} catch (\Throwable $e) {
    echo "Exception: ".$e->getMessage().PHP_EOL;
    echo $e->getTraceAsString();
}