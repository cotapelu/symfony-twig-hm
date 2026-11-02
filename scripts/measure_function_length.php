#!/usr/bin/env php
<?php

$baseDir = __DIR__ . '/../php-symfony';
$excludeDirs = [ 'vendor', 'tests', 'test', 'Resources', 'config', 'migrations', 'var' ];
$thresholdLines = 20;
$thresholdComplexity = 10;

$violations = [];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));
foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    $skip = false;
    foreach ($excludeDirs as $ex) {
        if (strpos($path, DIRECTORY_SEPARATOR . $ex . DIRECTORY_SEPARATOR) !== false) {
            $skip = true;
            break;
        }
    }
    if ($skip) {
        continue;
    }
    $content = file_get_contents($path);
    $tokens = token_get_all($content);
    $inFunction = false;
    $braceDepth = 0;
    $funcStartLine = 0;
    $funcName = 'anonymous';
    $complexity = 1;
    $codeLines = []; // lines with actual code tokens
    $lineNr = 0;

    foreach ($tokens as $token) {
        if (is_array($token)) {
            $tokenId = $token[0];
            $tokenLine = $token[2];
            $text = $token[1];
            $lineNr = $tokenLine;
        } else {
            $tokenId = $token;
            $text = $token;
        }

        if ($inFunction) {
            // Count lines that contain non-whitespace, non-comment tokens
            if (is_array($token) && !in_array($tokenId, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
                $codeLines[$lineNr] = true;
            }

            if ($text === '{') {
                if ($braceDepth === 0) {
                    // body start; signature line already counted
                }
                $braceDepth++;
                continue;
            }
            if ($text === '}') {
                $braceDepth--;
                if ($braceDepth === 0) {
                    // Function ends
                    $length = count($codeLines);
                    if ($length > $thresholdLines || $complexity > $thresholdComplexity) {
                        $violations[] = [
                            'file' => $path,
                            'line' => $funcStartLine,
                            'name' => $funcName,
                            'length' => $length,
                            'complexity' => $complexity
                        ];
                    }
                    $inFunction = false;
                    $complexity = 1;
                    $funcName = 'anonymous';
                    $codeLines = [];
                }
                continue;
            }
            if ($braceDepth > 0) {
                // Count decision tokens for complexity
                if (is_array($token) && in_array($tokenId, [T_IF, T_ELSEIF, T_FOR, T_FOREACH, T_WHILE, T_DO, T_SWITCH, T_CASE, T_CATCH, T_COALESCE, T_LOGICAL_OR, T_LOGICAL_AND, T_BOOLEAN_OR, T_BOOLEAN_AND])) {
                    $complexity++;
                }
            }
        } else {
            if (is_array($token) && $tokenId === T_FUNCTION) {
                $inFunction = true;
                $funcStartLine = $lineNr;
                $funcName = 'unknown';
                $braceDepth = 0;
                // Count signature line
                $codeLines = [$lineNr => true];
                continue;
            }
        }
    }
}

// Output report
if ($violations) {
    echo "Found " . count($violations) . " functions violating thresholds (lines >$thresholdLines or complexity >$thresholdComplexity):\n";
    foreach ($violations as $v) {
        printf("%s:%d: function %s - lines=%d, complexity=%d\n", $v['file'], $v['line'], $v['name'], $v['length'], $v['complexity']);
    }
    exit(1);
}
echo "All functions meet thresholds (≤$thresholdLines lines, ≤$thresholdComplexity complexity).\n";
exit(0);
