import { vitePreprocess } from "@sveltejs/vite-plugin-svelte";

/** @type {import('@sveltejs/kit').Config} */
const config = {
	preprocess: vitePreprocess(),
	// Disable SSR for tests
	compilerOptions: {
		generate: 'dom',
		hydratable: false
	},
	onwarn: (warning, handler) => {
		// Ignore a11y warnings for faster builds
		if (warning.code?.startsWith('a11y-')) return;
		handler(warning);
	},
};

export default config;