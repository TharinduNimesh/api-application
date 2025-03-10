import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig(({ mode }) => {
    // Load env file based on `mode` in the current working directory.
    // Set the third parameter to '' to load all env regardless of the `VITE_` prefix.
    const env = loadEnv(mode, process.cwd(), '');
    
    return {
        plugins: [
            tailwindcss(),
            laravel({
                input: "resources/js/app.ts",
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
        ...(env.VITE_APP_ENV === 'local' ? {
            server: {
                host: '0.0.0.0',
                port: 5173,
                hmr: {
                    host: 'localhost',
                },
            }
        } : {})
    }
});
