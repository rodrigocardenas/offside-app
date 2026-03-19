import { existsSync } from 'node:fs';
import { resolve } from 'node:path';
import { CapacitorConfig } from '@capacitor/cli';
import { config as loadEnv } from 'dotenv';

const explicitEnv = process.env.APP_ENV?.trim() || process.env.NODE_ENV?.trim();
const envFiles = ['.env', '.env.local'];

if (explicitEnv) {
    envFiles.push(`.env.${explicitEnv}`, `.env.${explicitEnv}.local`);
}

for (const envFile of envFiles) {
    const envPath = resolve(envFile);

    if (existsSync(envPath)) {
        loadEnv({ path: envPath, override: true });
    }
}

const appId = process.env.CAPACITOR_APP_ID ?? 'com.offsideclub.app';
const appName = process.env.CAPACITOR_APP_NAME ?? 'Offside Club';
const webDir = process.env.CAPACITOR_WEB_DIR ?? 'mobile-shell';
const appEnv = process.env.APP_ENV?.trim().toLowerCase();
const appUrl = process.env.APP_URL?.trim();

function isRemoteAppUrl(url?: string): url is string {
    if (!url) {
        return false;
    }

    try {
        const parsedUrl = new URL(url);
        const hostname = parsedUrl.hostname.toLowerCase();
        const isLocalHost = ['localhost', '127.0.0.1', '0.0.0.0'].includes(hostname);

        return !isLocalHost && !hostname.endsWith('.local') && !hostname.endsWith('.test');
    } catch {
        return false;
    }
}

// In production/staging, fall back to APP_URL so mobile builds do not ship the placeholder shell by mistake.
const serverUrl =
    process.env.CAPACITOR_SERVER_URL?.trim() ??
    ((appEnv === 'production' || appEnv === 'staging') && isRemoteAppUrl(appUrl) ? appUrl : undefined);

const capacitorConfig: CapacitorConfig = {
    appId,
    appName,
    webDir,
    plugins: {
        SplashScreen: {
            launchShowDuration: 0
        },
        FirebaseMessaging: {
            presentationOptions: ['badge', 'sound', 'alert']
        }
    },
    android: {
        allowMixedContent: true
    }
};

if (serverUrl) {
    capacitorConfig.server = {
        url: serverUrl,
        cleartext: serverUrl.startsWith('http://')
    };
}

export default capacitorConfig;
