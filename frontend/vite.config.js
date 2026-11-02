// SPDX-License-Identifier: MIT
import { sveltekit } from "@sveltejs/kit/vite";
import { defineConfig } from "vite";

/**
 * @param {any} warning
 * @param {(warning: any) => void} warn
 */
const onwarn = (warning, warn) => {
  if (warning.code === "css_unused_selector") return;
  if (warning.code === "a11y_click_events_have_key_events") return;
  if (warning.code === "a11y_no_static_element_interactions") return;
  if (warning.code === "a11y_label_has_associated_control") return;
  if (warning.code === "a11y_consider_explicit_label") return;
  if (warning.code === "attribute_duplicate") return;
  if (warning.code === "slot_element_invalid_name") return;
  if (warning.code === "export_let_unused") return;
  warn(warning);
};

/** @type {import('vite').UserConfig} */
export default defineConfig({
  plugins: [sveltekit()],
  build: {
    sourcemap: true,
    minify: "esbuild",
  },
  optimizeDeps: {
    include: ["svelte", "@sveltejs/kit"],
  },
  // Alias configuration removed; rely on SvelteKit defaults ($lib, $app) and relative imports

  server: {
    fs: {
      strict: true,
    },
  },
});
