<?php
putenv('DATABASE_URL=sqlite:///:memory:');
require __DIR__.'/vendor/autoload.php';
use App\Kernel;
try {
    $kernel = new Kernel('test', true);
    $kernel->boot();
    $router = $kernel->getContainer()->get('router');
    $routes = $router->getRouteCollection();
    echo "Total routes: ".$routes->count().PHP_EOL;
    foreach ($routes as $name => $route) {
        if (str_contains($name, 'auth') || str_contains($route->getPath(), '/auth')) {
            echo "$name: ".$route->getPath()." (".implode(',', $route->getMethods()).")".PHP_EOL;
        }
    }
} catch (\Throwable $e) {
    echo "Exception: ".$e->getMessage().PHP_EOL;
    echo $e->getTraceAsString().PHP_EOL;
}