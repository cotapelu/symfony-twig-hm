<?php
require __DIR__ . '/../vendor/autoload.php';
$kernel = new App\Kernel('test', true);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');
$metadata = $em->getMetadataFactory()->getAllMetadata();
echo "Metadata count: " . count($metadata) . "\n";
foreach ($metadata as $m) {
    echo " - " . $m->getName() . "\n";
}
$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
$schemaTool->updateSchema($metadata, true);
$conn = $em->getConnection();
$tables = $conn->createSchemaManager()->listTableNames();
echo "Tables after updateSchema: " . implode(', ', $tables) . "\n";
