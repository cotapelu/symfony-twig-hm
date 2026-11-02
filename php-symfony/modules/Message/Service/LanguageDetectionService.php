<?php

declare(strict_types=1);

namespace App\Message\Service;

/**
 * Language Detection Service
 * Detects programming language from code content using pattern matching.
 */
class LanguageDetectionService
{
    private array $languagePatterns = [
        'javascript' => '/(?:const|let|var|function|=>|import|export|class|interface)/',
        'typescript' => '/:\s|type\s/',
        'python' => '/(?:def |import |from |class |if __name__|\.format\(|f["\'])/',
        'php' => '/(?:<\?php|\$[a-zA-Z_]|namespace |use |public function|private function)/',
        'java' => '/(?:public class|public static void|System\.out|import java\.)/',
        'cpp' => '/(?:std::|cout <<)/',
        'c' => '/(?:#include|int main\(|printf\()/',
        'csharp' => '/(?:using System|Console\.Write|public class|namespace )/i',
        'ruby' => '/(?:def |end|puts|require|class |module )/i',
        'go' => '/(?:package main|func |import \(|fmt\.Print)/',
        'rust' => '/(?:fn |let |println!|use |impl |struct )/i',
        'html' => '/(?:<html|<head|<body|<div|<script|<style)/i',
        'css' => '/(?:\{[^}]*\}|\.class|#id|color:|font-size:)/',
        'sql' => '/(?:SELECT |INSERT |UPDATE |DELETE |CREATE |ALTER |DROP )/i',
        'json' => '/^\s*[\[\{]/',
        'yaml' => '/(?:^[\s]*[a-zA-Z_][a-zA-Z0-9_]*\s*:/m)',
    ];

    private array $languageAliases = [
        'js' => 'javascript',
        'ts' => 'typescript',
        'py' => 'python',
        'c++' => 'cpp',
        'c#' => 'csharp',
        'cs' => 'csharp',
        'rb' => 'ruby',
        'rs' => 'rust',
        'sh' => 'bash',
        'yml' => 'yaml',
        'htm' => 'html',
    ];

    public function detect(string $code)
    {
        $codeLower = strtolower($code);

        // Check TypeScript first (has JS patterns + type annotations)
        if (preg_match($this->languagePatterns['javascript'], $codeLower)) {
            if (preg_match($this->languagePatterns['typescript'], $codeLower)) {
                return 'typescript';
            }
            return 'javascript';
        }

        // Check other languages
        foreach ($this->languagePatterns as $language => $pattern) {
            if ($language === 'javascript' || $language === 'typescript') {
                continue;
            }
            if (preg_match($pattern, $codeLower)) {
                return $language;
            }
        }

        return 'plaintext';
    }

    public function normalize(string $language)
    {
        $language = strtolower(trim($language));
        return $this->languageAliases[$language] ?? $language;
    }

    public function isSupported(string $language)
    {
        $normalized = $this->normalize($language);
        return isset($this->languagePatterns[$normalized]) || $normalized === 'plaintext';
    }

    public function getSupportedLanguages()
    {
        return array_keys($this->languagePatterns);
    }
}
