/**
 * Deep Links Permission Handler
 * Solicita al usuario configurar OffsideClub como handler preferido para links
 */

import { App } from '@capacitor/app';
import { AppLauncher } from '@capacitor/app-launcher';
import { Platform } from '@ionic/vue';

export class DeepLinksPermissionHandler {
  private static FIRST_LAUNCH_KEY = 'offsideclub_deep_links_permission_requested';

  static async requestDeepLinksPermission() {
    // Solo en Android
    if (!Platform.is('android')) {
      return false;
    }

    // Verificar si ya hemos pedido permiso
    const alreadyRequested = localStorage.getItem(this.FIRST_LAUNCH_KEY);
    if (alreadyRequested === 'true') {
      return false;
    }

    // Marcar que ya hemos pedido
    localStorage.setItem(this.FIRST_LAUNCH_KEY, 'true');

    // Mostrar dialog
    const confirmed = await this.showDeepLinksDialog();
    
    if (confirmed) {
      await this.openDeepLinksSettings();
    }

    return true;
  }

  private static async showDeepLinksDialog(): Promise<boolean> {
    return new Promise((resolve) => {
      const dialog = document.createElement('ion-alert');
      dialog.header = '⚙️ Configuración Recomendada';
      dialog.subHeader = 'Deep Links para Invitaciones';
      dialog.message = `
        Para que los links de invitación a grupos se abran correctamente en OffsideClub,
        necesitas permitir que esta app abra links de nuestro dominio.
        
        Haremos clic en Continuar para abrirte la configuración.
      `;
      dialog.buttons = [
        {
          text: 'Más Tarde',
          role: 'cancel',
          handler: () => {
            resolve(false);
          }
        },
        {
          text: 'Continuar',
          role: 'confirm',
          handler: () => {
            resolve(true);
          }
        }
      ];

      document.body.appendChild(dialog);
      dialog.present();
    });
  }

  private static async openDeepLinksSettings() {
    try {
      // Android 12+ (API 31+): ACTION_APP_OPEN_BY_DEFAULT_SETTINGS
      const canOpenDefault = await AppLauncher.canOpenUrl({
        url: 'android-app://com.android.settings/action/app_open_by_default_settings'
      });

      if (canOpenDefault.canOpen) {
        await AppLauncher.openUrl({
          url: 'android-app://com.android.settings/action/app_open_by_default_settings'
        });
      } else {
        // Fallback: Abrir Settings general
        await AppLauncher.openUrl({
          url: 'android-app://com.android.settings'
        });
      }
    } catch (error) {
      console.error('Error abriendo settings:', error);
      
      // Alternativa: mostrar instrucciones manuales
      const instructions = document.createElement('ion-alert');
      instructions.header = 'Configuración Manual';
      instructions.message = `
        Abre Settings > Apps > Default Apps > Opening links
        
        Busca "app.offsideclub.es" y selecciona "OffsideClub"
      `;
      instructions.buttons = [{ text: 'OK' }];
      
      document.body.appendChild(instructions);
      instructions.present();
    }
  }

  /**
   * Resetear el estado (útil para testing)
   */
  static resetPermissionRequest() {
    localStorage.removeItem(this.FIRST_LAUNCH_KEY);
  }

  /**
   * Marcar como completado (para usuarios que ya lo hicieron)
   */
  static markPermissionGranted() {
    localStorage.setItem(this.FIRST_LAUNCH_KEY, 'true');
  }
}
