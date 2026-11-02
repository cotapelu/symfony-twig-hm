<?php

declare(strict_types=1);

namespace App\Message\Service;

/**
 * Prism.js Asset Service
 * Manages Prism.js CSS and JavaScript assets for syntax highlighting.
 */
class PrismAssetService
{
    private const CDN_BASE = 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0';

    private array $languageComponents = [
        'javascript' => 'javascript',
        'typescript' => 'typescript',
        'python' => 'python',
        'java' => 'java',
        'cpp' => 'cpp',
        'csharp' => 'csharp',
        'php' => 'php',
        'ruby' => 'ruby',
        'go' => 'go',
        'rust' => 'rust',
        'swift' => 'swift',
        'kotlin' => 'kotlin',
        'scala' => 'scala',
        'dart' => 'dart',
        'lua' => 'lua',
        'perl' => 'perl',
        'r' => 'r',
        'matlab' => 'matlab',
        'sql' => 'sql',
        'bash' => 'bash',
        'powershell' => 'powershell',
        'dockerfile' => 'docker',
        'yaml' => 'yaml',
        'json' => 'json',
        'xml' => 'xml',
        'html' => 'markup',
        'css' => 'css',
        'scss' => 'scss',
        'sass' => 'sass',
        'less' => 'less',
        'coffeescript' => 'coffeescript',
        'jsx' => 'jsx',
        'tsx' => 'tsx',
        'vue' => 'vue',
        'diff' => 'diff',
        'log' => 'log',
        'ini' => 'ini',
        'nginx' => 'nginx',
        'apache' => 'apache',
        'makefile' => 'makefile',
        'cmake' => 'cmake',
        'gradle' => 'gradle',
        'maven' => 'maven',
        'groovy' => 'groovy',
        'fsharp' => 'fsharp',
        'vbnet' => 'vbnet',
        'objectivec' => 'objective-c',
        'vala' => 'vala',
        'genie' => 'vala',
        'pike' => 'clike',
        'awk' => 'awk',
        'sed' => 'clike',
        'tcl' => 'tcl',
        'fish' => 'fish',
        'regex' => 'regex',
    ];

    private array $themes = [
        'default' => 'prism.css',
        'dark' => 'prism-dark.css',
        'funky' => 'prism-funky.css',
        'okaidia' => 'prism-okaidia.css',
        'twilight' => 'prism-twilight.css',
        'coy' => 'prism-coy.css',
        'solarizedlight' => 'prism-solarizedlight.css',
        'tomorrow' => 'prism-tomorrow.css',
    ];

    public function getScripts(array $languages = [])
    {
        $scripts = [];
        $scripts[] = '<script src="'.self::CDN_BASE.'/prism.min.js"></script>';

        $loadedComponents = [];
        $languageDetection = new LanguageDetectionService();

        foreach ($languages as $lang) {
            $normalized = $languageDetection->normalize($lang);
            if (isset($this->languageComponents[$normalized]) && !in_array($this->languageComponents[$normalized], $loadedComponents)) {
                $component = $this->languageComponents[$normalized];
                $scripts[] = '<script src="'.self::CDN_BASE.'/components/prism-'.$component.'.min.js"></script>';
                $loadedComponents[] = $component;
            }
        }

        $scripts[] = '<script src="'.self::CDN_BASE.'/plugins/line-numbers/prism-line-numbers.min.js"></script>';
        $scripts[] = '<script src="'.self::CDN_BASE.'/plugins/copy-to-clipboard/prism-copy-to-clipboard.min.js"></script>';

        return implode("\n", $scripts);
    }

    public function getStyles(?string $theme = 'default')
    {
        $themeFile = $this->themes[$theme] ?? $this->themes['default'];

        $styles = [];
        $styles[] = '<link href="'.self::CDN_BASE.'/themes/'.$themeFile.'" rel="stylesheet">';
        $styles[] = '<link href="'.self::CDN_BASE.'/plugins/line-numbers/prism-line-numbers.css" rel="stylesheet">';

        return implode("\n", $styles);
    }

    public function getAvailableThemes()
    {
        return array_keys($this->themes);
    }
}
