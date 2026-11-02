<?php
$file = "tests/Integration/PollIntegrationTest.php";
$code = file_get_contents($file);
$code = preg_replace_callback("/id:\s*'poll-[^']*'/", fn() => "id: 1", $code);
$code = preg_replace_callback("/id:\s*'vote-[^']*'/", fn() => "id: 1", $code);
$code = preg_replace_callback("/pollId:\s*'poll-[^']*'/", fn() => "pollId: 1", $code);
file_put_contents($file, $code);
echo "PollIntegrationTest fixed.\n";
