module.exports = {
  root: true,
  extends: [
    'eslint:recommended',
    '@typescript-eslint/recommended',
    'plugin:svelte/recommended'
  ],
  parser: '@typescript-eslint/parser',
  plugins: ['@typescript-eslint', 'import'],
  parserOptions: {
    sourceType: 'module',
    ecmaVersion: 2020,
    extraFileExtensions: ['.svelte']
  },
  env: {
    browser: true,
    es2017: true,
    node: true
  },
  globals: {
    window: 'readonly',
    document: 'readonly',
    navigator: 'readonly',
    console: 'readonly',
    process: 'readonly',
    fetch: 'readonly',
    URL: 'readonly',
    Response: 'readonly',
    Request: 'readonly',
    CustomEvent: 'readonly',
    HTMLElement: 'readonly',
    HTMLInputElement: 'readonly',
    HTMLCanvasElement: 'readonly',
    CanvasRenderingContext2D: 'readonly',
    HTMLAudioElement: 'readonly',
    MouseEvent: 'readonly',
    KeyboardEvent: 'readonly',
    TouchEvent: 'readonly',
    setTimeout: 'readonly',
    clearTimeout: 'readonly',
    setInterval: 'readonly',
    clearInterval: 'readonly',
    requestAnimationFrame: 'readonly',
    performance: 'readonly',
    FormData: 'readonly',
    File: 'readonly',
    Blob: 'readonly',
    EventSource: 'readonly',
    tick: 'readonly',
    setContext: 'readonly',
    getContext: 'readonly',
    onMount: 'readonly',
    onDestroy: 'readonly',
    createEventDispatcher: 'readonly',
    beforeUpdate: 'readonly',
    afterUpdate: 'readonly'
  },
  overrides: [
    {
      files: ['*.svelte'],
      parser: 'svelte-eslint-parser',
      parserOptions: {
        parser: '@typescript-eslint/parser'
      }
    }
  ],
  rules: {
    'no-unused-vars': 'off', // Turn off base rule
    '@typescript-eslint/no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
    'svelte/no-at-html-tags': 'warn', // Warn instead of error for now
    'svelte/valid-compile': 'warn',
    '@typescript-eslint/no-explicit-any': 'off', // Allow any for now
    '@typescript-eslint/no-implicit-any-catch': 'off',
    'import/no-restricted-paths': [ 'error', { zones: [ { from: '*/state/*', target: '*/ui/*', message: 'State layer should not import from UI layer.' }, { from: '*/services/*', target: '*/ui/*', message: 'Services layer should not import from UI layer.' }, { from: '*/services/*', target: '*/state/*', message: 'Services layer should not import from State layer.' }, { from: '*/services/*', target: '*/events/*', message: 'Services layer should not import from Events layer.' }, { from: '*/events/*', target: '*/ui/*', message: 'Events layer should not import from UI layer.' }, { from: '*/events/*', target: '*/state/*', message: 'Events layer should not import from State layer.' }, { from: '*/events/*', target: '*/services/*', message: 'Events layer should not import from Services layer.' }, { from: '*/contracts/*', target: '*/ui/*', message: 'Contracts layer should not import from UI layer.' }, { from: '*/contracts/*', target: '*/state/*', message: 'Contracts layer should not import from State layer.' }, { from: '*/contracts/*', target: '*/services/*', message: 'Contracts layer should not import from Services layer.' }, { from: '*/contracts/*', target: '*/events/*', message: 'Contracts layer should not import from Events layer.' } ] } ],
  }
};