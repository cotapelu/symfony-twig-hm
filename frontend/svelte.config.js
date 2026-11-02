import adapter from "@sveltejs/adapter-static";
import { vitePreprocess } from "@sveltejs/vite-plugin-svelte";

/** @type {import('@sveltejs/kit').Config} */
const config = {
	preprocess: vitePreprocess(),
	kit: {
		adapter: adapter({
			fallback: "index.html",
			precompress: true,
		}),
		serviceWorker: {
			register: true,
		},
		appDir: "_app",
	},
	onwarn: (warning, handler) => {
		// Ignore a11y warnings for faster builds
		if (warning.code?.startsWith('a11y-')) return;
		handler(warning);
	},
};

export default config;
