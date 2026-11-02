<?php
$dirs = [
    'modules/PharmacyDelivery/Entity',
    'modules/GroceryDelivery/Entity',
    'modules/FoodDelivery/Entity',
];
$methodPatterns = [
    'getId' => ['int', '?int'],
    'getTotal' => ['float', '?float'],
    'getQuantity' => ['int', '?int'],
    'getPrice' => ['float', '?float'],
    'getAmount' => ['float', '?float'],
    'getSubtotal' => ['float', '?float', 'string', '?string'],
    'getDeliveryFee' => ['float', '?float'],
    'getServiceFee' => ['float', '?float'],
    'getTax' => ['float', '?float'],
    'getDiscount' => ['float', '?float'],
    'getWeight' => ['float', '?float'],
    'getDistance' => ['float', '?float'],
    'getRating' => ['float', '?float'],
];
foreach ($dirs as $dir) {
    foreach (glob("$dir/*.php") as $file) {
        $code = file_get_contents($file);
        $original = $code;
        foreach ($methodPatterns as $method => $types) {
            foreach ($types as $type) {
                // Pattern: any whitespace/declaration before method name, then params, then optional spaces, colon, type
                $pattern = '/(' . preg_quote("public function $method", '/') . '\([^)]*\))\s*:\s*' . preg_quote($type, '/') . '/';
                $replacement = '$1';
                $code = preg_replace($pattern, $replacement, $code);
            }
        }
        if ($code !== $original) {
            file_put_contents($file, $code);
            echo "Relaxed $file\n";
        }
    }
}
echo "Done.\n";