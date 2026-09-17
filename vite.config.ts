import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'node:path';

// VueForge is built as a library of independent entry points (the hydrator
// plus every example/generated component) so October's ViteResolver can
// import each one by its own manifest key instead of shipping one giant bundle.
export default defineConfig({
    plugins: [vue()],
    base: '/plugins/amjad/vueforge/assets/dist/',
    build: {
        manifest: true,
        outDir: 'assets/dist',
        emptyOutDir: true,
        rollupOptions: {
            input: {
                vueforge: resolve(__dirname, 'assets/js/vueforge.ts'),
                TagInput: resolve(__dirname, 'assets/vue/components/TagInput.vue'),
                JsonEditor: resolve(__dirname, 'assets/vue/components/JsonEditor.vue'),
            },
        },
    },
    server: {
        origin: 'http://localhost:5173',
    },
});
