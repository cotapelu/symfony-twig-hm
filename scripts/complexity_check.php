<?php
$baseDir = 'php-symfony/src';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));
$max = 0; $worst = [];
foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') continue;
    $code = file_get_contents($file->getPathname());
    $tokens = token_get_all($code);
    $inFunction = false; $name = ''; $depth = 0; $func = '';
    foreach ($tokens as $i => $token) {
        if (is_array($token) && $token[0] === T_FUNCTION) {
            $inFunction = true; $func = ''; $depth = 0;
            // capture function name
            $name = '';
            for ($j=$i+1; $j<count($tokens); $j++) {
                if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_STRING,T_NAME_QUALIFIED])) {
                    $name = $tokens[$j][1]; break;
                }
            }
            continue;
        }
        if ($inFunction) {
            if (is_array($token) && $token[0] === '{') $depth++;
            if (is_array($token) && $token[0] === '}') $depth--;
            if ($depth < 0) {
                $inFunction = false;
                // compute complexity on $func
                $comp = 1;
                $funcTokens = token_get_all($func);
                foreach ($funcTokens as $t) {
                    if (is_array($t)) {
                        switch ($t[0]) {
                            case T_IF: case T_ELSEIF: case T_FOR: case T_FOREACH:
                            case T_WHILE: case T_DO: case T_SWITCH: case T_CASE: case T_CATCH:
                                $comp++; break;
                            case T_LOGICAL_AND: case T_LOGICAL_OR: case T_LOGICAL_XOR:
                                $comp++; break;
                            case T_COALESCE: case T_TERNARY:
                                $comp++; break;
                        }
                    }
                }
                if ($comp > 10) {
                    $rel = substr($file->getPathname(), strlen($baseDir)+1);
                    $worst["$rel::$name"] = $comp;
                }
                if ($comp > $max) $max = $comp;
                continue;
            }
            if ($inFunction) $func .= is_array($token) ? $token[1] : $token;
        }
    }
}
arsort($worst);
echo "Max complexity: $max\n";
if ($max > 10) {
    echo "Complexity violations (>10):\n";
    foreach ($worst as $k=>$c) echo " - $k : $c\n";
    echo "Total: ".count($worst)." violations\n";
} else {
    echo "All functions have complexity ≤10.\n";
}
