import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  server: {
    host: true,
    port: 5174,
    strictPort: true,
    proxy: {
      '/api': 'http://localhost',
      '/auth': 'http://localhost',
      '/me': 'http://localhost',
      '/logout': 'http://localhost',
      '/calendar': 'http://localhost',
      '/swap-requests': 'http://localhost',
    }
  }
})