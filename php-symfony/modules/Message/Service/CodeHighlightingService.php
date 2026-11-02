<?php

declare(strict_types=1);

namespace App\Message\Service;

/**
 * Code Highlighting Service
 * Generates HTML for syntax highlighted code blocks.
 */
class CodeHighlightingService
{
    private LanguageDetectionService $languageDetection;
    private PrismAssetService $prismAssets;

    public function __construct(
        LanguageDetectionService $languageDetection,
        PrismAssetService $prismAssets
    ) {
        $this->languageDetection = $languageDetection;
        $this->prismAssets = $prismAssets;
    }

    public function highlight(string $code, ?string $language = null, array $options = [])
    {
        $language = $language ? $this->languageDetection->normalize($language) : $this->languageDetection->detect($code);

        if (!$this->languageDetection->isSupported($language)) {
            $language = 'plaintext';
        }

        $showLineNumbers = $options['lineNumbers'] ?? true;
        $showCopyButton = $options['copyButton'] ?? true;

        $classes = ['code-block', "language-{$language}"];
        if ($showLineNumbers) {
            $classes[] = 'line-numbers';
        }

        $codeHtml = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');

        $html = '<div class="syntax-highlighting-container">';

        if ($showCopyButton) {
            $html .= '<button class="copy-code-btn" onclick="copyCodeBlock(this)" title="Copy code">';
            $html .= '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>';
            $html .= '</svg>';
            $html .= '</button>';
        }

        $html .= '<pre class="'.implode(' ', $classes).'" data-language="'.$language.'">';
        $html .= '<code class="language-'.$language.'">'.$codeHtml.'</code>';
        $html .= '</pre>';
        $html .= '</div>';

        return $html;
    }

    public function parseCodeBlocks(string $content)
    {
        // Parse ```language\ncode\n``` blocks
        $content = preg_replace_callback('/```(\w+)?\n(.*?)```/s', function ($matches) {
            $language = $matches[1] ?? '';
            $code = $matches[2];

            return $this->highlight($code, $language, ['lineNumbers' => true, 'copyButton' => true]);
        }, $content);

        // Parse `inline code` blocks
        $content = preg_replace_callback('/`([^`]+)`/', function ($matches) {
            $code = $matches[1];
            return '<code class="inline-code">'.htmlspecialchars($code).'</code>';
        }, $content);

        return $content;
    }

    public function getAssets(array $languages = [], ?string $theme = 'default')
    {
        return [
            'scripts' => $this->prismAssets->getScripts($languages),
            'styles' => $this->prismAssets->getStyles($theme),
        ];
    }
}
