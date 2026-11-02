module.exports = {
  parser: '@typescript-eslint/parser',
  plugins: ['import'],
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
    '@typescript-eslint/no-unused-expressions': 'off',
    '@typescript-eslint/no-unused-vars': 'off',
    'svelte/valid-compile': 'off'
  }
};
