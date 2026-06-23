import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  build: {
    lib: {
      entry: './frontend/index.ts',
      name: 'TelegramPlugin',
      formats: ['umd'],
      fileName: 'telegram',
    },
    rollupOptions: {
      external: [],
      output: {
        globals: {
          vue: 'Vue',
        },
      },
    },
  },
})
