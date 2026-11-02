<?php
$file = "tests/Service/PollServiceTest.php";
$code = file_get_contents($file);
// Replace allowMultiple method calls to getAllowMultiple
$code = str_replace('$poll->allowMultiple()', '$poll->getAllowMultiple()', $code);
$code = str_replace('$updated->allowMultiple()', '$updated->getAllowMultiple()', $code);
// Replace updatePoll calls: add third arg (userId = 1)
$code = preg_replace_callback(
    '/->updatePoll\(\$poll->getId\(\), \[(.*?)\]\)/s',
    function($m) {
        return '->updatePoll($poll->getId(), [$1], 1)';
    },
    $code
);
// For updatePoll(999, ...) add third arg
$code = preg_replace(
    '/->updatePoll\(999, \[(.*?)\]\)/',
    '->updatePoll(999, [$1], 1)',
    $code
);
// deletePoll: add second arg 1
$code = preg_replace(
    '/->deletePoll\(\$poll->getId\(\)\)/',
    '->deletePoll($poll->getId(), 1)',
    $code
);
$code = preg_replace(
    '/->deletePoll\(999\)/',
    '->deletePoll(999, 1)',
    $code
);
// vote: change userId to string '2' or others. We'll replace numeric 2 with '2'. But careful not to replace numeric IDs in getPoll or other contexts.
// We'll target specifically vote calls:
$code = str_replace('vote($poll->getId(), 2, [0])', "vote(\$poll->getId(), '2', [0])", $code);
$code = str_replace('vote($poll->getId(), 2, [1])', "vote(\$poll->getId(), '2', [1])", $code);
// For other votes: 3,4
$code = str_replace('vote($poll->getId(), 3, [1])', "vote(\$poll->getId(), '3', [1])", $code);
$code = str_replace('vote($poll->getId(), 4, [0])', "vote(\$poll->getId(), '4', [0])", $code);
// hasVoted calls: change 2 to '2'
$code = str_replace('hasVoted($poll->getId(), 2)', "hasVoted($poll->getId(), '2')", $code);
// getUserVote calls:
$code = str_replace('getUserVote($poll->getId(), 2)', "getUserVote($poll->getId(), '2')", $code);
// Assertions for getUserId: change 2 to '2'
$code = str_replace('$this->assertEquals(2, $vote->getUserId());', "$this->assertEquals('2', \$vote->getUserId());", $code);
file_put_contents($file, $code);
echo "PollServiceTest updated.\n";
