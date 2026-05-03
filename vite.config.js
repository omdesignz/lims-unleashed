import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import i18n from 'laravel-vue-i18n/vite'
import fs from 'node:fs'
import path from 'node:path'
import os from 'node:os'

const domain = "lims-unleashed.test";
const homedir = os.homedir();
const valetCertificateDirectory = path.join(homedir, '.config', 'valet', 'Certificates');
const valetKeyPath = path.join(valetCertificateDirectory, `${domain}.key`);
const valetCertPath = path.join(valetCertificateDirectory, `${domain}.crt`);

function readPemFileIfValid(filePath, beginMarker) {
    if (!fs.existsSync(filePath)) {
        return null;
    }

    const content = fs.readFileSync(filePath, 'utf8').trim();

    if (!content.startsWith(`-----BEGIN ${beginMarker}-----`)) {
        return null;
    }

    return content;
}

const valetKey = readPemFileIfValid(valetKeyPath, 'PRIVATE KEY');
const valetCert = readPemFileIfValid(valetCertPath, 'CERTIFICATE');
const httpsConfig = valetKey && valetCert ? { key: valetKey, cert: valetCert } : undefined;
const devServerHost = process.env.VITE_DEV_SERVER_HOST || '0.0.0.0';
const hmrHost = process.env.VITE_DEV_SERVER_HMR_HOST || domain;

function manualChunks(id) {
    if (!id.includes('node_modules')) {
        return;
    }

    if (id.includes('vue3-apexcharts') || id.includes('apexcharts')) {
        return 'charts-apex';
    }

    if (id.includes('@tiptap') || id.includes('prosemirror')) {
        return 'editor-collab';
    }

    if (id.includes('katex')) {
        return 'math-katex';
    }

    if (id.includes('hyperformula') || id.includes('vue-excel') || id.includes('handsontable')) {
        return 'spreadsheets';
    }
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            detectTls: domain,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        i18n(),
    ],
    server: {
        https: httpsConfig,
        host: devServerHost,
        hmr: {
            host: hmrHost,
        },
    },
    resolve: {
        alias: {
            'ziggy-js': '/vendor/tightenco/ziggy',
            // 'ziggy': '/vendor/tightenco/ziggy/src/js',
            // 'ziggy-vue': '/vendor/tightenco/ziggy/src/js/vue',
            '@': '/resources/js'
        },
    },
    build: {
		rollupOptions: {
			external: [],
            output: {
                manualChunks,
            },
		},
	},
});
