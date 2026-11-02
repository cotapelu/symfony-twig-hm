module.exports = {
  parser: '@typescript-eslint/parser',
  parserOptions: {
    ecmaVersion: 2020,
    sourceType: 'module',
    extraFileExtensions: ['.js']
  },
  plugins: ['@typescript-eslint', 'import'],
  rules: {
    'import/no-restricted-paths': [
      'error',
      {
        zones: [
          {
            from: '*/state/*',
            target: '*/ui/*',
            message: 'State layer should not import from UI layer.'
          }
        ]
      }
    ],
    // Turn off rules that cause errors in our test
    '@typescript-eslint/no-unused-vars': 'off',
    '@typescript-eslint/no-unused-expressions': 'off',
    'svelte/valid-compile': 'off',
    'no-undef': 'off',
    'import/no-unresolved': 'off',
    'import/extensions': 'off',
    '@typescript-eslint/no-explicit-any': 'off',
    '@typescript-eslint/no-implicit-any-catch': 'off'
  }
};
