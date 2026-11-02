<?php
$file = 'modules/Shared/Controller/BaseController.php';
$code = file_get_contents($file);
$insert = <<<'PHP'

    public function getParameter(string $name): mixed
    {
        return null;
    }

    public function get(string $service)
    {
        return null;
    }

    public function has(string $id): bool
    {
        return false;
    }
PHP;
// Find the position of the last '}' in the file (class closing brace)
$pos = strrpos($code, '}');
if ($pos !== false) {
    $new = substr_replace($code, $insert . "\n}", $pos, 1);
    file_put_contents($file, $new);
    echo "BaseController extended with fallback methods.\n";
} else {
    echo "Closing brace not found.\n";
}