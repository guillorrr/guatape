/// <reference types="vitest/config" />
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    // Vite only answers localhost and IPs unless told otherwise. The dev
    // stack serves the SPA at DOMAIN and, with tenancy, at <slug>.DOMAIN.
    allowedHosts: process.env.DOMAIN ? [process.env.DOMAIN, `.${process.env.DOMAIN}`] : [],
    proxy: {
      '/api': {
        target: 'http://nginx',
        changeOrigin: true,
      },
      '/sanctum': {
        target: 'http://nginx',
        changeOrigin: true,
      },
    },
  },
  test: {
    environment: 'jsdom',
    include: ['src/**/*.spec.ts'],
    setupFiles: ['src/test/setup.ts'],
    // Component specs mount PrimeVue; its CSS-in-JS theme isn't needed there.
    css: false,
  },
  css: {
    preprocessorOptions: {
      scss: {
        additionalData: `@use "@/core/styles/variables" as *;\n`,
      },
    },
  },
});
