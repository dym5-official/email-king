import path from 'node:path';
import fs from 'node:fs';
import esbuild from 'esbuild';
import uniqcss from 'uniqcss';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const entryFile = path.join(__dirname, '..', 'dev', 'src', 'index.jsx');
const outDir = path.join(__dirname, '..', 'assets');

(async () => {

    if ( fs.existsSync( outDir ) ) {
        fs.rmSync( outDir, { recursive: true } );
    }

    await esbuild.build({
        entryPoints: [entryFile],
        bundle: true,
        minify: process.env.NODE_ENV === "production",
        sourcemap: process.env.NODE_ENV !== "production",
        format: 'iife',
        outfile: path.join(outDir, 'bundle.js'),
        jsx: "automatic",

        loader: {
            '.woff2': 'file',
            '.ttf': 'file',
            '.svg': 'file',
            '.png': 'file',
        },

        plugins: [
            uniqcss()
        ]
    });
})();