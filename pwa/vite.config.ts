import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { resolve } from 'path'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],

  // The app is served at /app/ by the PHP server
  base: '/app/',

  // Output to ../public/app so PHP can serve it directly
  build: {
    outDir: resolve(__dirname, '../public/app'),
    emptyOutDir: true,
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'index.html'),
      },
      output: {
        entryFileNames: 'assets/[name]-[hash].js',
        manualChunks: {
          'react-vendor': ['react', 'react-dom', 'react-router-dom'],
          'query-vendor': ['@tanstack/react-query'],
        },
      },
    },
    sourcemap: false,
    minify: 'esbuild',
  },

  server: {
    port: 5173,
    // Proxy API calls to the PHP backend during development
    proxy: {
      '/pwa-api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
})
