/// <reference types="vitest" />
import { defineConfig } from "vitest/config";
import { svelte } from "@sveltejs/vite-plugin-svelte";
import sveltePreprocess from "svelte-preprocess";
import path from "node:path";

const projectRoot = process.cwd();

export default defineConfig({
  // mode: 'test',  // Removed - may cause SSR issues
  define: {
    'process.env.NODE_ENV': '"test"',
    'process.env.VITEST': '"true"',
    'process.env.SSR': '"false"'
  },
  resolve: {
    conditions: ['browser', 'import', 'module'],
    alias: {
      $lib: path.resolve(projectRoot, 'src/lib'),
      '$service-worker': path.resolve(projectRoot, 'src/__mocks__/service-worker.ts'),
      // Use svelte5 compatibility layer - testing-library/svelte v5 uses src/index.js
      '@testing-library/svelte': path.resolve(projectRoot, 'node_modules/@testing-library/svelte/src/index.js'),
      '$app/environment': path.resolve(projectRoot, 'src/lib/environment-stub.ts'),
      '$app/navigation': path.resolve(projectRoot, 'src/lib/navigation-stub.ts'),
      '$app/forms': path.resolve(projectRoot, 'src/lib/forms-stub.ts'),
      // Fix broken imports in components
      './atoms/Textarea.svelte': path.resolve(projectRoot, 'src/lib/components/Textarea.svelte'),
      './atoms/Select.svelte': path.resolve(projectRoot, 'src/lib/components/atoms/Select.svelte'),
      './atoms/Radio.svelte': path.resolve(projectRoot, 'src/lib/components/atoms/Radio.svelte'),
      './atoms/Button.svelte': path.resolve(projectRoot, 'src/lib/components/atoms/Button.svelte'),
      './atoms/Input.svelte': path.resolve(projectRoot, 'src/lib/components/atoms/Input.svelte'),
      './atoms/Checkbox.svelte': path.resolve(projectRoot, 'src/lib/components/atoms/Checkbox.svelte'),
      './molecules/Card.svelte': path.resolve(projectRoot, 'src/lib/components/molecules/Card.svelte'),
      // Fix common broken relative imports
      './Icon.svelte': path.resolve(projectRoot, 'src/lib/components/atoms/Icon.svelte'),
      './Typography.svelte': path.resolve(projectRoot, 'src/lib/components/atoms/Typography.svelte'),
      './Badge.svelte': path.resolve(projectRoot, 'src/lib/components/atoms/Badge.svelte'),
      './Alert.svelte': path.resolve(projectRoot, 'src/lib/components/molecules/Alert.svelte'),
      './Select.svelte': path.resolve(projectRoot, 'src/lib/components/atoms/Select.svelte'),
      './Button.svelte': path.resolve(projectRoot, 'src/lib/components/atoms/Button.svelte'),
      './Input.svelte': path.resolve(projectRoot, 'src/lib/components/atoms/Input.svelte'),
      './Checkbox.svelte': path.resolve(projectRoot, 'src/lib/components/atoms/Checkbox.svelte'),
      './Radio.svelte': path.resolve(projectRoot, 'src/lib/components/atoms/Radio.svelte'),
      './Card.svelte': path.resolve(projectRoot, 'src/lib/components/molecules/Card.svelte'),
      // Fix $lib/api import
      '$lib/api': path.resolve(projectRoot, 'src/lib/api.ts'),
    }
  },

  plugins: [
    svelte({
      preprocess: sveltePreprocess(),
      // Force client-side compilation for tests
      compilerOptions: {
        generate: 'client',
        hydratable: false
      }
    })
  ],

  test: {
    // Parallel execution for speed - use multiple forks
    pool: 'forks',
    poolOptions: {
      forks: {
        maxForks: 4,
        minForks: 2,
        isolate: true
      }
    },
    // Prevent tests from hanging
    testTimeout: 10000,
    hookTimeout: 10000,
    include: [
      // Unit tests
      "tests/unit/**/*.{test,spec}.{js,ts}",
      "src/modules/**/services/*.{test,spec}.{js,ts}",
      "src/modules/**/events/*.{test,spec}.{js,ts}",
      "src/modules/**/state/*.{test,spec}.{js,ts}",
      "src/stores/**/*.{test,spec}.{js,ts}",
      "src/lib/api-client.test.ts",
      "src/lib/**/*.{test,spec}.{ts,js}",
      "src/appcore/**/*.{test,spec}.{ts,js}",
      // Module-level tests (e.g., onboarding)
      "src/modules/**/*.{test,spec}.{js,ts,svelte}",
      // Component tests
      "src/lib/components/**/*.{test,spec}.{js,ts,svelte}",
      "src/modules/**/ui/*.{test,spec}.{js,ts,svelte}"
    ],
    exclude: [
      "**/frontend-module-template.test.ts",
      "**/playwright/**",
      "**/e2e/**"
    ],
    globals: true,
    environment: "jsdom",
    setupFiles: ["src/setupTests.js"],
    coverage: {
      provider: "v8",
      reporter: ["lcov", "html"],
      reportsDirectory: "../coverage/frontend",
      include: ["src/**/*.ts", "src/**/*.svelte"],
      exclude: ["node_modules", "tests", "**/*.test.ts", "**/*.spec.ts", "**/*.d.ts", "src/setupTests.js"],
    },
  },
});