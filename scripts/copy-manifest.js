#!/usr/bin/env node

/**
 * Post-build script to copy Vite manifest to the expected location
 * 
 * Vite 5.x generates manifest.json in public/build/.vite/manifest.json
 * but Laravel expects it at public/build/manifest.json
 */

const fs = require('fs');
const path = require('path');

const source = path.join(process.cwd(), 'public', 'build', '.vite', 'manifest.json');
const dest = path.join(process.cwd(), 'public', 'build', 'manifest.json');

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
