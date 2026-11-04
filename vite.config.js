import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import Components from 'unplugin-vue-components/vite';
import { PrimeVueResolver } from '@primevue/auto-import-resolver';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
            // Dominio servido por Herd. Con detectTls el plugin usa el
            // certificado de Herd/Valet y arranca Vite por HTTPS, que es lo que
            // espera la página (https://dtw.test). Sin esto Vite escucha en
            // HTTP pero Laravel escribe https://dtw.test:5173 en public/hot y
            // el navegador no conecta: pantalla en blanco hasta recargar.
            detectTls: 'dtw.test',
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        Components({
            resolvers: [
                PrimeVueResolver()
            ]
        })
    ],
    // El host y el puerto se fijan para que el archivo public/hot y el servidor
    // real nunca se desincronicen.
    server: {
        // dtw.test resuelve a 127.0.0.1 (archivo hosts de Herd): se escucha en
        // esa misma IP para no quedarse sólo en IPv6 (::1).
        host: '127.0.0.1',
        // strictPort: si 5173 está ocupado (por ejemplo, por un `pnpm dev`
        // anterior que quedó vivo) Vite falla en claro en lugar de saltar a
        // 5174 mientras Laravel sigue apuntando al 5173.
        port: 5173,
        strictPort: true,
    },
});
