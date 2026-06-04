import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

export default defineConfig({
  plugins: [
    vue(),
    vueDevTools(),
  ],
  server: {
    host: '0.0.0.0',
    port: 5173,
    allowedHosts: ['my-bot.loca.lt', '.loca.lt'],
    proxy: {
      '/api': {
        target: 'http://nginx:80',
        changeOrigin: true,
      },
      '/sanctum': {
        target: 'http://nginx:80',
        changeOrigin: true,
      },
    },
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
})
