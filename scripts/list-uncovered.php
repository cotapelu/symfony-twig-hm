<?php
$xml = simplexml_load_file('coverage/php-coverage.xml');
$packages = $xml->xpath('//package');
$files = [];
foreach ($packages as $package) {
    foreach ($package->file as $file) {
        $metrics = $file->metrics;
        $stmts = (int)$metrics['statements'];
        if ($stmts == 0) continue;
        $covered = (int)$metrics['coveredstatements'];
        if ($covered == 0) {
            $files[] = [
                'path' => (string)$file['name'],
                'stmts' => $stmts,
                'basename' => basename((string)$file['name'])
            ];
        }
    }
}
usort($files, fn($a,$b) => $b['stmts'] <=> $a['stmts']);
$top = array_slice($files, 0, 100);
$fh = fopen('docs/top-uncovered-files.txt', 'w');
fprintf($fh, "Top 100 uncovered files by statement count\n");
fprintf($fh, "Generated: %s\n\n", date('c'));
foreach ($top as $f) {
    fprintf($fh, "%d\t%s\t%s\n", $f['stmts'], $f['basename'], $f['path']);
}
fclose($fh);
echo "Wrote " . count($top) . " entries to docs/top-uncovered-files.txt\n";
