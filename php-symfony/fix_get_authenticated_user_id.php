<?php
$controllers = [
    'modules/Ecommerce/Controller/EcommerceController.php',
    'modules/FoodDelivery/Controller/FoodDeliveryController.php',
    'modules/PharmacyDelivery/Controller/PharmacyDeliveryController.php',
    'modules/GroceryDelivery/Controller/GroceryDeliveryController.php',
    'modules/Groups/Controller/GroupController.php',
    'modules/Notifications/Api/Controller/NotificationsController.php',
    'modules/LiveSupport/Controller/LiveSupportController.php',
    'modules/Notification/Controller/NotificationController.php',
    'modules/Media/Controller/MediaController.php',
    'modules/News/Controller/NewsController.php',
    'modules/DigitalWallet/Controller/DigitalWalletController.php',
    'modules/JobFinding/Controller/JobFindingController.php',
    // add others as needed
];
foreach ($controllers as $file) {
    if (!file_exists($file)) {
        echo "Missing $file\n";
        continue;
    }
    $code = file_get_contents($file);
    $original = $code;
    // Pattern: return ... ->getUserIdentifier();
    $code = preg_replace(
        '/return\s+\$user->getUserIdentifier\(\);/',
        'return $user->getId();',
        $code
    );
    // Also handle cases with cast: return \(string\) $user->getUserIdentifier();
    $code = preg_replace(
        '/return\s+\(string\)\s+\$user->getUserIdentifier\(\);/',
        'return (string) $user->getId();',
        $code
    );
    if ($code !== $original) {
        file_put_contents($file, $code);
        echo "Updated $file\n";
    } else {
        echo "No change in $file\n";
    }
}
echo "Done.\n";