#!/usr/bin/env php
<?php
/**
 * Check PHP coverage threshold from Clover XML.
 * Usage: check-php-coverage.php <coverage.xml> [threshold_percent]
 */

if ($argc < 2) {
    fwrite(STDERR, "Usage: check-php-coverage.php <coverage.xml> [threshold]\n");
    exit(1);
}

$coverageFile = $argv[1];
$threshold = isset($argv[2]) ? (float)$argv[2] : 80.0;

if (!file_exists($coverageFile)) {
    fwrite(STDERR, "Coverage file not found: $coverageFile\n");
    exit(1);
}

$xml = simplexml_load_file($coverageFile);
if ($xml === false) {
    fwrite(STDERR, "Error parsing coverage XML\n");
    exit(1);
}

// Get project-level metrics (last <metrics> under <project>)
$projectMetrics = $xml->xpath('//project/metrics')[0] ?? null;
if ($projectMetrics === null) {
    // Fallback: try any top-level metrics
    $allMetrics = $xml->xpath('//metrics');
    $projectMetrics = end($allMetrics) ?: null;
}
if ($projectMetrics === null) {
    fwrite(STDERR, "Could not find project metrics in coverage XML\n");
    exit(1);
}

$statements = (int)($projectMetrics['statements'] ?? 0);
$coveredStatements = (int)($projectMetrics['coveredstatements'] ?? 0);

if ($statements === 0) {
    fwrite(STDERR, "No statements found in coverage report\n");
    exit(1);
}

$coverage = ($coveredStatements / $statements) * 100;

printf("PHP coverage: %.2f%% (threshold: %.2f%%)\n", $coverage, $threshold);

if ($coverage >= $threshold) {
    echo "Coverage OK\n";
    exit(0);
} else {
    echo "Coverage below threshold!\n";
    exit(1);
}
