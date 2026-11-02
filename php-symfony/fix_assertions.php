<?php
$file = "tests/Integration/PollIntegrationTest.php";
$code = file_get_contents($file);
$code = str_replace(
    ["\$this->assertEquals('vote-1', \$vote->getId());", "\$this->assertEquals('poll-1', \$vote->getPollId());"],
    ["\$this->assertEquals(1, \$vote->getId());", "\$this->assertEquals(1, \$vote->getPollId());"],
    $code
);
file_put_contents($file, $code);
echo "Done.\n";
