<?php

declare(strict_types=1);
// modules/Kernel.php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import('../config/{packages}/*.yaml');
        $container->import('../config/{packages}/'.$this->environment.'/*.yaml');

        if (is_file(\dirname(__DIR__).'/config/services.yaml')) {
            $container->import('../config/services.yaml');
            $container->import('../config/{services}_'.$this->environment.'.yaml');
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        // Load explicit YAML routes first
        $routes->import('../config/{routes}/'.$this->environment.'/*.yaml');
        $routes->import('../config/{routes}/*.yaml');

        if (is_file(\dirname(__DIR__).'/config/routes.yaml')) {
            $routes->import(\dirname(__DIR__).'/config/routes.yaml');
        }

        // Auto-load attribute routes from all module controllers
        $modulesDir = \dirname(__DIR__).'/modules';
        foreach (glob($modulesDir.'/*/Controller', GLOB_ONLYDIR) as $controllerDir) {
            $routes->import($controllerDir, 'attribute');
        }
        // Note: Do not import entire modules root to avoid loading non-controller files.
    }
}
