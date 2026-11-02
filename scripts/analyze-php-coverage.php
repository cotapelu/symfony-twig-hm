#!/usr/bin/env php
<?php
/**
 * Analyze PHP coverage to identify files with lowest coverage.
 * Usage: scripts/analyze-php-coverage.php [coverage.xml]
 */

$coverageFile = $argv[1] ?? 'coverage/php-coverage.xml';
if (!file_exists($coverageFile)) {
    fwrite(STDERR, "Coverage file not found: $coverageFile\n");
    exit(1);
}

$xml = simplexml_load_file($coverageFile);
if ($xml === false) {
    fwrite(STDERR, "Error parsing coverage XML\n");
    exit(1);
}

$files = [];
foreach ($xml->xpath('//file') as $file) {
    $name = (string)$file['name'];
    $metrics = $file->xpath('./metrics')[0] ?? null;
    if ($metrics) {
        $statements = (int)$metrics['statements'];
        $covered = (int)$metrics['coveredstatements'];
        $pct = $statements > 0 ? ($covered / $statements) * 100 : 0;
        $files[] = [
            'name' => $name,
            'statements' => $statements,
            'covered' => $covered,
            'pct' => $pct,
        ];
    }
}

// Sort by coverage percentage ascending (lowest first)
usort($files, fn($a, $b) => $a['pct'] <=> $b['pct']);

// Output top 20 least covered files with >0 statements
echo "PHP Coverage Analysis (Top 20 Lowest)\n";
echo str_repeat('=', 60) . "\n";
printf("%-60s %10s %10s %8s\n", 'File', 'Statements', 'Covered', 'Coverage');
echo str_repeat('-', 60) . "\n";

$count = 0;
foreach ($files as $f) {
    if ($f['statements'] > 0 && $count < 20) {
        printf("%-60s %10d %10d %7.1f%%\n",
            substr(basename($f['name']) . ' (' . dirname($f['name']) . ')', 0, 60),
            $f['statements'],
            $f['covered'],
            $f['pct']
        );
        $count++;
    }
}

echo "\nTotal files analyzed: " . count($files) . "\n";