<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (class_exists(Dotenv::class)) {
    $dotenv = new Dotenv();
    $dotenv->bootenv(dirname(__DIR__).'/.env');
}
