<?php
$xml = simplexml_load_file('coverage/php-coverage.xml');
$metrics = $xml->xpath('//metrics')[0];
$statements = (int)$metrics['statements'];
$covered = (int)$metrics['coveredstatements'];
echo "Total statements: $statements\n";
echo "Covered: $covered\n";
echo "Coverage: " . ($statements > 0 ? ($covered/$statements)*100 : 0) . "%\n";
