import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  build: {
    lib: {
      entry: './frontend/index.ts',
      name: 'YandexCalendarPlugin',
      formats: ['umd'],
      fileName: 'yandex_calendar',
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
