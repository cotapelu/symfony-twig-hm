<?php
putenv('KERNEL_CLASS=App\\Kernel');
putenv('APP_ENV=test');
require __DIR__.'/vendor/autoload.php';
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HealthTest extends WebTestCase
{
    public function testHealth()
    {
        $client = static::createClient([], [
            'HTTP_HOST' => 'localhost',
        ]);
        $client->request('GET', '/platform/health/iam');
        $response = $client->getResponse();
        echo $response->getContent()."\n";
    }
}
$test = new HealthTest('testHealth');
$test->setUpBeforeClass();
$test->testHealth();
