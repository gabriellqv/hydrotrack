import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import tailwindcss from '@tailwindcss/vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue(), vueDevTools(), tailwindcss()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  // O host 0.0.0.0 e o polling só são necessários dentro de containers,
  // onde o file watching de bind mounts é pouco confiável. Fora disso,
  // expor a rede e usar polling só consome CPU.
  server: {
    host: process.env.VITE_DEV_HOST || 'localhost',
    port: 5173,
    watch: {
      usePolling: process.env.VITE_USE_POLLING === 'true',
    },
  },
})
