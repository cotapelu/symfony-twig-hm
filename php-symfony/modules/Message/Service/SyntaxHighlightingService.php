<?php

declare(strict_types=1);

namespace App\Message\Service;

class SyntaxHighlightingService
{
    private array $supportedLanguages = [
        'javascript', 'typescript', 'python', 'java', 'cpp', 'csharp', 'php', 'ruby',
        'go', 'rust', 'swift', 'kotlin', 'scala', 'dart', 'lua', 'perl', 'r',
        'matlab', 'sql', 'bash', 'powershell', 'dockerfile', 'yaml', 'json',
        'xml', 'html', 'css', 'scss', 'sass', 'less', 'stylus', 'coffeescript',
        'jsx', 'tsx', 'vue', 'svelte', 'elm', 'haskell', 'clojure', 'scheme',
        'erlang', 'elixir', 'crystal', 'nim', 'd', 'fortran', 'cobol', 'pascal',
        'delphi', 'ada', 'vhdl', 'verilog', 'systemverilog', 'assembly',
        'nasm', 'masm', 'arm', 'llvm', 'webassembly', 'brainfuck', 'whitespace',
        'esoteric', 'regex', 'diff', 'patch', 'log', 'ini', 'toml', 'properties',
        'nginx', 'apache', 'htaccess', 'makefile', 'cmake', 'qmake', 'gradle',
        'maven', 'ant', 'groovy', 'kotlin-script', 'scala-script', 'clojure-script',
        'fsharp', 'vbnet', 'smalltalk', 'objectivec', 'objectivec++', 'vala',
        'genie', 'boo', 'cobra', 'nemerle', 'pike', 'icon', 'unicon', 'rebol',
        'red', 'newlisp', 'autoit', 'autohotkey', 'applescript', 'jscript',
        'vbs', 'vbscript', 'awk', 'sed', 'tcl', 'expect', 'fish', 'xonsh',
        'nu', 'oil', 'elvish', 'm4', 'mumps', 'cache', 'gtm', 'tal', 'cobol85',
        'jcl', 'jes2', 'jes3', 'tso', 'ispf', 'sdsf', 'iof', 'smp', 'zos',
        'zvm', 'vm', 'cms', 'tpf', 'gml', 'sgml', 'tex', 'latex', 'context',
        'metapost', 'metafont', 'asymptote', 'pstricks', 'tikz', 'pgf',
        'gnuplot', 'octave', 'scilab', 'maxima', 'reduce', 'axiom', 'fricas',
        'gap', 'sage', 'pari', 'magma', 'kash', 'singular', 'macaulay2',
        'coq', 'agda', 'idris', 'lean', 'twelf', 'abella', 'beluga', 'twilight',
        'plastic', 'maude', 'cafeobj', 'bigloo', 'chicken', 'gambit', 'stklos',
        'kawa', 'sisc', 'jscheme', 'jscheme', 'loko', 'ypsilon', 'ironjs',
        'njs', 'v8cgi', 'spidermonkey', 'narwhal', 'ringo', 'rhino', 'ejs',
        'ejsvm', 'ejsplus', 'ejsplusvm', 'ejsvmplus', 'ejsplusvmplus',
    ];

    private array $languageAliases = [
        'js' => 'javascript', 'ts' => 'typescript', 'py' => 'python', 'c++' => 'cpp',
        'c#' => 'csharp', 'cs' => 'csharp', 'rb' => 'ruby', 'rs' => 'rust',
        'sh' => 'bash', 'yml' => 'yaml', 'htm' => 'html', 'md' => 'markdown',
        'ps1' => 'powershell', 'docker' => 'dockerfile', 'make' => 'makefile',
        'gradle' => 'gradle', 'maven' => 'maven', 'ant' => 'ant', 'f#' => 'fsharp',
        'fs' => 'fsharp', 'vb' => 'vbnet', 'objc' => 'objectivec',
        'objcpp' => 'objectivec++', 'vala' => 'vala', 'genie' => 'genie',
        'boo' => 'boo', 'cobra' => 'cobra', 'nemerle' => 'nemerle', 'pike' => 'pike',
        'icon' => 'icon', 'unicon' => 'unicon', 'rebol' => 'rebol', 'red' => 'red',
        'newlisp' => 'newlisp', 'autoit' => 'autoit', 'ahk' => 'autohotkey',
        'applescript' => 'applescript', 'jscript' => 'jscript', 'vbs' => 'vbscript',
        'awk' => 'awk', 'sed' => 'sed', 'tcl' => 'tcl', 'expect' => 'expect',
        'fish' => 'fish', 'xonsh' => 'xonsh', 'nu' => 'nu', 'oil' => 'oil',
        'elvish' => 'elvish', 'm4' => 'm4', 'mumps' => 'mumps', 'cache' => 'cache',
        'gtm' => 'gtm', 'tal' => 'tal', 'cobol85' => 'cobol85', 'jcl' => 'jcl',
        'jes2' => 'jes2', 'jes3' => 'jes3', 'tso' => 'tso', 'ispf' => 'ispf',
        'sdsf' => 'sdsf', 'iof' => 'iof', 'smp' => 'smp', 'zos' => 'zos',
        'zvm' => 'zvm', 'vm' => 'vm', 'cms' => 'cms', 'tpf' => 'tpf',
        'gml' => 'gml', 'sgml' => 'sgml', 'tex' => 'tex', 'latex' => 'latex',
        'context' => 'context', 'metapost' => 'metapost', 'metafont' => 'metafont',
        'asymptote' => 'asymptote', 'pstricks' => 'pstricks', 'tikz' => 'tikz',
        'pgf' => 'pgf', 'gnuplot' => 'gnuplot', 'octave' => 'octave',
        'scilab' => 'scilab', 'maxima' => 'maxima', 'reduce' => 'reduce',
        'axiom' => 'axiom', 'fricas' => 'fricas', 'gap' => 'gap', 'sage' => 'sage',
        'pari' => 'pari', 'magma' => 'magma', 'kash' => 'kash', 'singular' => 'singular',
        'macaulay2' => 'macaulay2', 'coq' => 'coq', 'agda' => 'agda', 'idris' => 'idris',
        'lean' => 'lean', 'twelf' => 'twelf', 'abella' => 'abella', 'beluga' => 'beluga',
        'twilight' => 'twilight', 'plastic' => 'plastic', 'maude' => 'maude',
        'cafeobj' => 'cafeobj', 'bigloo' => 'bigloo', 'chicken' => 'chicken',
        'gambit' => 'gambit', 'stklos' => 'stklos', 'kawa' => 'kawa', 'sisc' => 'sisc',
        'jscheme' => 'jscheme', 'loko' => 'loko', 'ypsilon' => 'ypsilon',
        'ironjs' => 'ironjs', 'njs' => 'njs', 'v8cgi' => 'v8cgi',
        'spidermonkey' => 'spidermonkey', 'narwhal' => 'narwhal', 'ringo' => 'ringo',
        'rhino' => 'rhino', 'ejs' => 'ejs', 'ejsvm' => 'ejsvm', 'ejsplus' => 'ejsplus',
        'ejsplusvm' => 'ejsplusvm', 'ejsvmplus' => 'ejsvmplus', 'ejsplusvmplus' => 'ejsplusvmplus',
    ];

    // Existing public methods (isLanguageSupported, getSupportedLanguages, normalizeLanguage, detectLanguage, etc.) omitted for brevity, remain unchanged...

    public function highlightCode(string $code, ?string $language = null, array $options = []): string
    {
        $language = $this->determineEffectiveLanguage($code, $language);
        $classes = $this->buildCodeClasses($language, $options);
        $codeHtml = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
        $html = $this->renderContainer(
            $this->renderCopyButton($options),
            $this->renderCodeBlock($codeHtml, $language, $classes)
        );

        return $html;
    }

    private function determineEffectiveLanguage(string $code, ?string $language): string
    {
        if ($language) {
            $normalized = $this->normalizeLanguage($language);
            if ($this->isLanguageSupported($normalized)) {
                return $normalized;
            }
        }

        $detected = $this->detectLanguage($code);
        if ($this->isLanguageSupported($detected)) {
            return $detected;
        }

        return 'plaintext';
    }

    private function buildCodeClasses(string $language, array $options): array
    {
        $classes = ['code-block', "language-{$language}"];
        if ($options['lineNumbers'] ?? true) {
            $classes[] = 'line-numbers';
        }

        return $classes;
    }

    private function normalizeLanguage(string $language): string
    {
        $lang = strtolower($language);
        return $this->languageAliases[$lang] ?? $lang;
    }

    private function isLanguageSupported(string $language): bool
    {
        return in_array($language, $this->supportedLanguages, true);
    }

    private function detectLanguage(string $code): string
    {
        // Simple fallback: always return plaintext; advanced detection could be added
        return 'plaintext';
    }

    private function renderCopyButton(array $options): string
    {
        if (!($options['copyButton'] ?? true)) {
            return '';
        }

        $html = '<button class="copy-code-btn" onclick="copyCodeBlock(this)" title="Copy code">';
        $html .= '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>';
        $html .= '</svg>';
        $html .= '</button>';

        return $html;
    }

    private function renderCodeBlock(string $codeHtml, string $language, array $classes): string
    {
        $classAttr = implode(' ', $classes);

        return '<pre class="'.$classAttr.'" data-language="'.$language.'">'.
               '<code class="language-'.$language.'">'.$codeHtml.'</code>'.
               '</pre>';
    }

    private function renderContainer(string $copyButton, string $codeBlock): string
    {
        $html = '<div class="syntax-highlighting-container">';
        $html .= $copyButton;
        $html .= $codeBlock;
        $html .= '</div>';

        return $html;
    }

    public function getPrismScripts(array $languages = []): string
    {
        $scripts = array_merge(
            $this->getCoreScripts(),
            $this->getComponentScripts($languages),
            $this->getPluginScripts()
        );

        return implode("\n", $scripts);
    }

    private function getCoreScripts(): array
    {
        return [
            '<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>',
        ];
    }

    private function getComponentScripts(array $languages): array
    {
        $scripts = [];
        $loadedComponents = [];
        $languageComponents = $this->getLanguageComponentMap();

        foreach ($languages as $lang) {
            $normalized = $this->normalizeLanguage($lang);
            if (isset($languageComponents[$normalized]) && !in_array($languageComponents[$normalized], $loadedComponents)) {
                $component = $languageComponents[$normalized];
                $scripts[] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-'.$component.'.min.js"></script>';
                $loadedComponents[] = $component;
            }
        }

        return $scripts;
    }

    private function getLanguageComponentMap(): array
    {
        return [
            'javascript' => 'javascript', 'typescript' => 'typescript', 'python' => 'python',
            'java' => 'java', 'cpp' => 'cpp', 'csharp' => 'csharp', 'php' => 'php',
            'ruby' => 'ruby', 'go' => 'go', 'rust' => 'rust', 'swift' => 'swift',
            'kotlin' => 'kotlin', 'scala' => 'scala', 'dart' => 'dart', 'lua' => 'lua',
            'perl' => 'perl', 'r' => 'r', 'matlab' => 'matlab', 'sql' => 'sql',
            'bash' => 'bash', 'powershell' => 'powershell', 'dockerfile' => 'docker',
            'yaml' => 'yaml', 'json' => 'json', 'xml' => 'xml', 'html' => 'markup',
            'css' => 'css', 'scss' => 'scss', 'sass' => 'sass', 'less' => 'less',
            'coffeescript' => 'coffeescript', 'jsx' => 'jsx', 'tsx' => 'tsx',
            'vue' => 'vue', 'diff' => 'diff', 'log' => 'log', 'ini' => 'ini',
            'nginx' => 'nginx', 'apache' => 'apache', 'makefile' => 'makefile',
            'cmake' => 'cmake', 'gradle' => 'gradle', 'maven' => 'maven',
            'groovy' => 'groovy', 'kotlin-script' => 'kotlin', 'scala-script' => 'scala',
            'fsharp' => 'fsharp', 'vbnet' => 'vbnet', 'objectivec' => 'objective-c',
            'vala' => 'vala', 'genie' => 'vala', 'pike' => 'clike', 'awk' => 'awk',
            'sed' => 'clike', 'tcl' => 'tcl', 'fish' => 'fish', 'regex' => 'regex',
        ];
    }

    private function getPluginScripts(): array
    {
        return [
            '<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.js"></script>',
            '<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/copy-to-clipboard/prism-copy-to-clipboard.min.js"></script>',
        ];
    }

    public function getPrismStyles(?string $theme = 'default'): string
    {
        $themeFile = $this->getThemeFile($theme);

        return implode("\n", [
            '<link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/'.$themeFile.'" rel="stylesheet">',
            '<link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.css" rel="stylesheet">',
        ]);
    }

    private function getThemeFile(string $theme): string
    {
        $themes = [
            'default' => 'prism.css',
            'dark' => 'prism-dark.css',
            'funky' => 'prism-funky.css',
            'okaidia' => 'prism-okaidia.css',
            'twilight' => 'prism-twilight.css',
            'coy' => 'prism-coy.css',
            'solarizedlight' => 'prism-solarizedlight.css',
            'tomorrow' => 'prism-tomorrow.css',
        ];

        return $themes[$theme] ?? $themes['default'];
    }

    public function parseCodeBlocks(string $content)
    {
        $content = preg_replace_callback('/```(\w+)?\n(.*?)```/s', function ($matches) {
            $language = $matches[1] ?? '';
            $code = $matches[2];

            return $this->highlightCode($code, $language, ['lineNumbers' => true, 'copyButton' => true]);
        }, $content);

        $content = preg_replace_callback('/`([^`]+)`/', function ($matches) {
            $code = $matches[1];

            return '<code class="inline-code">'.htmlspecialchars($code).'</code>';
        }, $content);

        return $content;
    }
}
