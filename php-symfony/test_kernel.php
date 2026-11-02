<?php
require __DIR__.'/vendor/autoload.php';
use App\Kernel;
$kernel = new Kernel('test', true);
$kernel->boot();
echo "Booted\n";
