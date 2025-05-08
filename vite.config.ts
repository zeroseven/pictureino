import {fileURLToPath, URL} from 'url'
import {defineConfig} from 'vite'

const entries = {
  'JavaScript/frontend/main': '@/main',
  'JavaScript/backend/element/aspectratio': '@backend/element/aspectratio',
}

export default defineConfig({
  publicDir: false,
  plugins: [],
  resolve: {
    alias: [
      {find: '@', replacement: fileURLToPath(new URL('./Resources/Private/JavaScript/frontend', import.meta.url))},
      {find: '@backend', replacement: fileURLToPath(new URL('./Resources/Private/JavaScript/backend', import.meta.url))}
    ]
  },
  build: {
    lib: {
      entry: entries,
      formats: ['es'],
    },
    cssCodeSplit: true,
    outDir: 'Resources/Public',
    emptyOutDir: false,
    rollupOptions: {
      input: entries,
      external: [/@typo3\/backend\/.*/],
      output: {
        entryFileNames: `[name].js`,
        chunkFileNames: `[name].js`,
        assetFileNames: `[name].[ext]`
      }
    },
    minify: 'terser'
  },
  css: {
    preprocessorOptions: {
      scss: {
        api: 'modern-compiler'
      }
    }
  }
})
