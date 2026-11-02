<?php
$dirs = [
    'modules/PharmacyDelivery/Entity',
    'modules/GroceryDelivery/Entity',
    'modules/FoodDelivery/Entity',
];
foreach ($dirs as $dir) {
    foreach (glob("$dir/*.php") as $file) {
        $code = file_get_contents($file);
        // Remove return type after ) in function/method declarations, keep following whitespace
        $new = preg_replace('/\)\s*:\s*([a-zA-Z0-9\\\|?]+)(\s+)/', ')$2', $code);
        if ($new !== $code) {
            file_put_contents($file, $new);
            echo "Relaxed $file\n";
        }
    }
}
echo "Done.\n";