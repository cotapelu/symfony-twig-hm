<?php
$file = "tests/Service/PollServiceTest.php";
$code = file_get_contents($file);
// Replace integer user IDs with string versions in various places
$replacements = [
    // updatePoll calls: add third argument (userId)
    "\$this->service->updatePoll(\$poll->getId(), [" => "\$this->service->updatePoll(\$poll->getId(), [, 1)",
    "\$this->assertNull(\$this->service->updatePoll(999, ['question' => 'x']));" => "\$this->assertNull(\$this->service->updatePoll(999, ['question' => 'x'], 1));",
    // deletePoll calls: add userId
    "\$this->assertTrue(\$this->service->deletePoll(\$poll->getId()));" => "\$this->assertTrue(\$this->service->deletePoll(\$poll->getId(), 1));",
    "\$this->assertFalse(\$this->service->deletePoll(999));" => "\$this->assertFalse(\$this->service->deletePoll(999, 1));",
    // vote calls: change userId to string
    "\$this->service->vote(\$poll->getId(), 2, [0])" => "\$this->service->vote(\$poll->getId(), '2', [0])",
    "\$this->service->vote(\$poll->getId(), 2, [1])" => "\$this->service->vote(\$poll->getId(), '2', [1])",
    // hasVoted calls
    "\$this->assertFalse(\$this->service->hasVoted(\$poll->getId(), 2));" => "\$this->assertFalse(\$this->service->hasVoted(\$poll->getId(), '2'));",
    "\$this->assertTrue(\$this->service->hasVoted(\$poll->getId(), 2));" => "\$this->assertTrue(\$this->service->hasVoted(\$poll->getId(), '2'));",
    // getUserVote calls
    "\$this->assertNull(\$this->service->getUserVote(\$poll->getId(), 2));" => "\$this->assertNull(\$this->service->getUserVote(\$poll->getId(), '2'));",
    "\$retrieved = \$this->service->getUserVote(\$poll->getId(), 2);" => "\$retrieved = \$this->service->getUserVote(\$poll->getId(), '2');",
    // getPollResults votes
    "\$this->service->vote(\$poll->getId(), 2, [0]);" => "\$this->service->vote(\$poll->getId(), '2', [0]);",
    "\$this->service->vote(\$poll->getId(), 3, [1]);" => "\$this->service->vote(\$poll->getId(), '3', [1]);",
    "\$this->service->vote(\$poll->getId(), 4, [0]);" => "\$this->service->vote(\$poll->getId(), '4', [0]);",
    // For testGetPollResults assertions, change expected userId from integer
    "\$this->assertEquals(2, \$vote->getUserId());" => "\$this->assertEquals('2', \$vote->getUserId());",
];
foreach ($replacements as $search => $replace) {
    $code = str_replace($search, $replace, $code);
}
// Also need to handle the case where the updatePoll call includes 2nd argument array with multiple lines? Our simple replace may break.
// We'll also adjust the line where updatePoll is called in testUpdatePoll with the array - the array lines may have newline. Use regex? But maybe pattern okay.
file_put_contents($file, $code);
echo "PollServiceTest patched.\n";
