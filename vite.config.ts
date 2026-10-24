import { defineConfig } from 'vite';
import liveReload from 'vite-plugin-live-reload';
import path from 'path';

export default defineConfig(({ mode }) => {
  const isDev = mode === 'development';
  const rootDir = import.meta.dirname ?? path.resolve();

  return {
    plugins: [
      // Автоматическая перезагрузка страницы при изменении PHP-шаблонов
      liveReload([
        path.resolve(rootDir, '**/*.php'),
      ]),
    ],

    // В Dev используем '/', в Build — относительные пути './', чтобы исключить любые проблемы с URL
    base: isDev ? '/' : './',

    resolve: {
      alias: {
        '@': path.resolve(rootDir, 'src'),
      },
    },

    server: {
      cors: true,
      strictPort: true,
      port: 5173,
      host: 'localhost',
      origin: 'http://localhost:5173',
      hmr: {
        host: 'localhost',
      },
    },

    build: {
      outDir: 'dist',
      emptyOutDir: true,
      manifest: true, // Создает dist/.vite/manifest.json
      sourcemap: isDev,
      rollupOptions: {
        input: {
          main: path.resolve(rootDir, 'src/main.ts'),
          editor: path.resolve(rootDir, 'src/editor.ts'),
        },
        output: {
          entryFileNames: 'assets/[name]-[hash].js',
          chunkFileNames: 'assets/[name]-[hash].js',
          assetFileNames: 'assets/[name]-[hash].[ext]',
        },
      },
    },

    css: {
      devSourcemap: true,
    },
  };
});
