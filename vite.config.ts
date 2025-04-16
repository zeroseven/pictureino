import {fileURLToPath, URL} from 'url'
import {defineConfig} from 'vite'

const entries = {
  'JavaScript/main': '@/main'
}

export default defineConfig({
  publicDir: false,
  plugins: [],
  resolve: {
    alias: [
      {find: '@', replacement: fileURLToPath(new URL('./Resources/Private/JavaScript', import.meta.url))},
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
