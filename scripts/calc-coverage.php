<?php
$xml = simplexml_load_file('coverage/php-coverage.xml');
$allMetrics = $xml->xpath('//project/metrics');
$metrics = $allMetrics[0] ?? null;
if (!$metrics) {
    die("No project metrics found\n");
}
$statements = (int)$metrics['statements'];
$covered = (int)$metrics['coveredstatements'];
echo "Statements: $statements, Covered: $covered\n";
echo "Coverage: " . ($statements > 0 ? ($covered/$statements)*100 : 0) . "%\n";
