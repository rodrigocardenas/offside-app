import { CapacitorConfig } from '@capacitor/cli';
import { config as loadEnv } from 'dotenv';

loadEnv();

const appId = process.env.CAPACITOR_APP_ID ?? 'com.offsideclub.app';
const appName = process.env.CAPACITOR_APP_NAME ?? 'Offside Club';
const webDir = process.env.CAPACITOR_WEB_DIR ?? 'mobile-shell';
const serverUrl = process.env.CAPACITOR_SERVER_URL;

const capacitorConfig: CapacitorConfig = {
    appId,
    appName,
    webDir,
    plugins: {
        SplashScreen: {
            launchShowDuration: 0
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
