<?php
$paths = [
    'modules/PharmacyDelivery/Entity',
    'modules/GroceryDelivery/Entity',
    'modules/FoodDelivery/Entity',
];
foreach ($paths as $dir) {
    foreach (glob($dir . '/*.php') as $file) {
        $code = file_get_contents($file);
        $code = str_replace(
            ['function getId(): ?int', 'function getId(): int', 'function getTotal(): ?float', 'function getTotal(): float'],
            ['function getId()', 'function getId()', 'function getTotal()', 'function getTotal()'],
            $code
        );
        file_put_contents($file, $code);
        echo "Relaxed $file\n";
    }
}
?>