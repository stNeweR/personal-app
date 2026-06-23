import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  build: {
    lib: {
      entry: './frontend/index.ts',
      name: 'MailNotifierPlugin',
      formats: ['umd'],
      fileName: 'mail_notifier',
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
