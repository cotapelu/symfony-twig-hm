<?php
$files = [
    'tests/Controller/Rewards/RewardsControllerTest.php',
    'tests/Controller/Discover/DiscoverControllerTest.php',
    'tests/Controller/Social/SocialControllerTest.php',
    'tests/Controller/Conversation/ConversationControllerTest.php',
];
foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "Missing: $file\n";
        continue;
    }
    $code = file_get_contents($file);
    // Replace method name
    $code = str_replace('setAuthenticatedUser', 'setTestUser', $code);
    file_put_contents($file, $code);
    echo "Updated $file\n";
}
echo "Done.\n";