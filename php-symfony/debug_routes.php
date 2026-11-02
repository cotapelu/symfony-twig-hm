<?php
require __DIR__.'/vendor/autoload.php';
$kernel = new App\Kernel('test', true);
$kernel->boot();
$router = $kernel->getContainer()->get('router');
$routes = $router->getRouteCollection();
echo 'Routes count: '.$routes->count().PHP_EOL;
foreach ($routes as $name => $route) {
    echo $name.': '.$route->getPath().PHP_EOL;
}