/**
 * Google OAuth Service para Capacitor
 * Maneja el flujo completo de autenticación con Google en la app móvil
 */

import { Browser } from '@capacitor/browser';
import { App } from '@capacitor/app';

declare global {
  interface Window {
    googleOAuthService?: GoogleOAuthService;
  }
}

interface GoogleAuthResponse {
  success: boolean;
  message?: string;
  url?: string;
  user?: {
    id: number;
    name: string;
    email: string;
    avatar?: string;
  };
  redirect?: string;
}

export class GoogleOAuthService {
  private static API_BASE_URL = window.location.origin;
  private static STORAGE_KEY = 'user_session';
  private static OAUTH_STATE_KEY = 'oauth_state';

  /**
   * Iniciar el flujo de Google OAuth
   */
  static async startGoogleLogin(): Promise<void> {
    try {
      console.log('📱 Iniciando Google OAuth flow...');

      // 1. Obtener URL de Google OAuth del servidor
      const response = await this.fetchGoogleAuthUrl();

      if (!response.success || !response.url) {
        throw new Error(response.message || 'Failed to get Google auth URL');
      }

      console.log('✅ URL de Google OAuth obtenida, abriendo navegador...');

      // Guardar estado
      const state = this.generateRandomState();
      localStorage.setItem(this.OAUTH_STATE_KEY, state);

      // 2. Abrir URL en navegador
      // Usa Browser API de Capacitor para abrir en navegador del sistema
      await Browser.open({
        url: response.url,
      });

      // 3. Escuchar el callback
      this.listenForCallback();

    } catch (error) {
      console.error('❌ Google OAuth error:', error);
      this.showError('Error al intentar login con Google: ' + (error instanceof Error ? error.message : String(error)));
    }
  }

  /**
   * Obtener URL de Google OAuth desde el servidor
   */
  private static async fetchGoogleAuthUrl(): Promise<GoogleAuthResponse> {
    try {
      const response = await fetch(`${this.API_BASE_URL}/api/auth/mobile/google-url`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      return await response.json();
    } catch (error) {
      console.error('Error fetching Google auth URL:', error);
      throw error;
    }
  }

  /**
   * Completar Google OAuth después del callback
   */
  static async completeGoogleLogin(googleData: any): Promise<void> {
    try {
      console.log('📱 Completando Google OAuth...');

      // 2. Enviar datos de Google al servidor
      const response = await fetch(`${this.API_BASE_URL}/api/auth/mobile/google-login`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        credentials: 'include', // Incluir cookies si es necesario
        body: JSON.stringify({
          ...googleData,
          timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        }),
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data: GoogleAuthResponse = await response.json();

      if (!data.success) {
        throw new Error(data.message || 'Authentication failed');
      }

      console.log('✅ Google OAuth completado exitosamente');

      // 3. Guardar datos de sesión
      if (data.user) {
        this.saveSession(data.user);
      }

      // 4. Mostrar éxito y navegar
      this.showSuccess('¡Login exitoso!');

      // Navegar después de 1.5 segundos
      setTimeout(() => {
        window.location.href = data.redirect || '/groups';
      }, 1500);

    } catch (error) {
      console.error('❌ Error completando Google OAuth:', error);
      this.showError('Error al completar autenticación: ' + (error instanceof Error ? error.message : String(error)));
    }
  }

  /**
   * Escuchar el callback de Google (Deep Links o URL)
   */
  private static listenForCallback(): void {
    // Método 1: Deep Links (Capacitor App)
    this.setupDeepLinkListener();

    // Método 2: Polling - Verificar si volvemos a la app
    this.setupAppResumeListener();
  }

  /**
   * Configurar listener para deep links
   */
  private static setupDeepLinkListener(): void {
    App.addListener('appUrlOpen', (data: any) => {
      console.log('🔗 Deep link recibido:', data.url);

      const url = data.url;

      // Verificar si es el callback de Google
      if (url.includes('/auth/google/callback')) {
        const params = this.parseUrlParams(url);
        console.log('📝 Parámetros de callback:', params);

        // Completar login
        this.completeGoogleLogin(params);
      }
    });
  }

  /**
   * Configurar listener para cuando la app vuelve al primer plano
   */
  private static setupAppResumeListener(): void {
    App.addListener('appStateChange', (state: any) => {
      if (state.isActive) {
        console.log('📱 App volvió al primer plano');
        // En algunos casos, verificar si hay datos en URL
      }
    });
  }

  /**
   * Parsear parámetros de URL
   */
  private static parseUrlParams(url: string): Record<string, string> {
    const params: Record<string, string> = {};

    // Extraer la parte después de ?
    const queryString = url.split('?')[1];
    if (!queryString) return params;

    // Parsear cada parámetro
    queryString.split('&').forEach((param: string) => {
      const [key, value] = param.split('=');
      params[decodeURIComponent(key)] = decodeURIComponent(value);
    });

    return params;
  }

  /**
   * Guardar datos de sesión
   */
  private static saveSession(user: any): void {
    try {
      const sessionData = {
        user,
        timestamp: new Date().toISOString(),
      };

      localStorage.setItem(this.STORAGE_KEY, JSON.stringify(sessionData));
      console.log('✅ Sesión guardada en localStorage');
    } catch (error) {
      console.error('Error guardando sesión:', error);
    }
  }

  /**
   * Generar un estado aleatorio para OAuth
   */
  private static generateRandomState(): string {
    return Math.random().toString(36).substring(7) + Date.now().toString(36);
  }

  /**
   * Mostrar error al usuario
   */
  private static showError(message: string): void {
    console.error('❌ ' + message);

    // Si es PWA/Web, usar alert
    if (typeof window !== 'undefined' && window.alert) {
      alert(message);
    }

    // Si es Capacitor, podría usar Toast
    if (window.Toastify) {
      window.Toastify({
        text: message,
        duration: 5000,
        gravity: 'bottom',
        backgroundColor: '#dc3545',
      }).showToast();
    }
  }

  /**
   * Mostrar éxito al usuario
   */
  private static showSuccess(message: string): void {
    console.log('✅ ' + message);

    if (window.Toastify) {
      window.Toastify({
        text: message,
        duration: 3000,
        gravity: 'bottom',
        backgroundColor: '#28a745',
      }).showToast();
    }
  }
}

// Exponer globalmente para uso en HTML
if (typeof window !== 'undefined') {
  window.googleOAuthService = GoogleOAuthService;
}

export default GoogleOAuthService;
