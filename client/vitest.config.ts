/**
 * vitest.config.ts
 *
 * Configuration for the Vitest test runner.
 * Uses happy-dom as the browser-like environment so Vue components and
 * composables can reference DOM APIs without a real browser.
 *
 * The `@` alias mirrors the one in vite.config.ts so that imports like
 * `@/stores/schedule` resolve correctly during tests.
 */

import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  test: {
    environment: 'happy-dom',
    include: ['src/**/*.{test,spec}.{ts,js}'],
  },
})
