import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  build: {
    lib: {
      entry: './frontend/index.ts',
      name: 'TodoistPlugin',
      formats: ['umd'],
      fileName: 'todoist',
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
