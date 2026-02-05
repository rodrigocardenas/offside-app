#!/usr/bin/env node

/**
 * Post-build script to copy Vite manifest to the expected location
 * 
 * Vite 5.x generates manifest.json in public/build/.vite/manifest.json
 * but Laravel expects it at public/build/manifest.json
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const source = path.join(path.dirname(__dirname), 'public', 'build', '.vite', 'manifest.json');
const dest = path.join(path.dirname(__dirname), 'public', 'build', 'manifest.json');

try {
    if (fs.existsSync(source)) {
        fs.copyFileSync(source, dest);
        console.log(`✓ Manifest copied: ${source} → ${dest}`);
    } else {
        console.warn(`⚠ Manifest source not found: ${source}`);
        process.exit(1);
    }
} catch (error) {
    console.error(`✗ Error copying manifest: ${error.message}`);
    process.exit(1);
}
