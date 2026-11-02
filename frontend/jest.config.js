module.exports = {
  preset: 'jest-preset-svelte',
  testEnvironment: 'jsdom',
  setupFilesAfterEnv: ['<rootDir>/src/setupTests.js'],
  moduleFileExtensions: ['js', 'svelte'],
  transform: {
    '^.+\\.js$': 'babel-jest',
    '^.+\\.svelte$': ['jest-preset-svelte', {
      preprocess: true
    }]
  },
  moduleNameMapping: {
    '^\\$lib(.*)$': '<rootDir>/src/lib$1',
    '^\\$app(.*)$': [
      '<rootDir>/__mocks__/app.js',
      '<rootDir>/node_modules/@sveltejs/kit/src/runtime/app$1'
    ]
  },
  testPathIgnorePatterns: ['node_modules'],
  collectCoverageFrom: [
    'src/**/*.{js,svelte}',
    '!src/**/*.test.{js,svelte}',
    '!src/**/*.spec.{js,svelte}'
  ]
};
