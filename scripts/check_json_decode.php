#!/usr/bin/env php
<?php

$modulesDir = __DIR__ . '/../php-symfony/modules';
$excludedPatterns = [
    '/BaseController\.php$/',
    // CustomerServiceController uses json_decode in a private helper (allowed)
    '/CustomerServiceController\.php$/'
];

$violations = [];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($modulesDir));
foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    if (!preg_match('/Controller\.php$/', $file->getFilename())) {
        continue;
    }
    $path = $file->getPathname();
    $skip = false;
    foreach ($excludedPatterns as $pattern) {
        if (preg_match($pattern, $path)) {
            $skip = true;
            break;
        }
    }
    if ($skip) {
        continue;
    }
    $content = file_get_contents($path);
    // Match json_decode($request->getContent() with optional whitespace and optional second parameter true
    if (preg_match('/json_decode\s*\(\s*\$request\s*->\s*getContent\s*\([^)]*\)\s*(?:,\s*true)?\s*\)/', $content)) {
        $violations[] = $path;
    }
}

if ($violations) {
    echo "ERROR: Found raw json_decode on request body in controllers:\n";
    foreach ($violations as $v) {
        echo " - $v\n";
    }
    exit(1);
}
echo "OK: All controllers properly use decodeJsonOr400.\n";
exit(0);
