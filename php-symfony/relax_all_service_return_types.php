<?php
$dirs = [
    'src/Service',
    'modules/*/Service',
];
$files = [];
foreach ($dirs as $pattern) {
    foreach (glob($pattern, GLOB_BRACE) as $dir) {
        if (is_dir($dir)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
                if ($file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }
    }
}
$count = 0;
foreach ($files as $file) {
    $code = file_get_contents($file);
    $new = preg_replace('/\)\s*:\s*([a-zA-Z0-9\\\|?]+)(\s|{)/', ')$2', $code);
    if ($new !== $code) {
        file_put_contents($file, $new);
        $count++;
        echo "Relaxed $file\n";
    }
}
echo "Total service files updated: $count\n";