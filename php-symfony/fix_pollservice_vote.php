<?php
$file = 'modules/Poll/Service/PollService.php';
$code = file_get_contents($file);
$old = "        \$vote = new PollVote(\n            pollId: \$pollId,\n            userId: \$userId,\n            optionIndices: \$optionIndices\n        );";
$new = "        \$vote = new PollVote(\n            null,\n            \$pollId,\n            \$userId,\n            \$optionIndices\n        );";
if (strpos($code, $old) !== false) {
    $code = str_replace($old, $new, $code);
    file_put_contents($file, $code);
    echo "PollService vote call fixed.\n";
} else {
    echo "Old block not found. Check formatting.\n";
}
?>