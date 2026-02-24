package com.offsideclub.app;

import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.content.Context;
import android.os.Build;
import androidx.core.app.NotificationCompat;

/**
 * NotificationChannelManager - Gestor de Canales de Notificación de Android
 * 
 * Responsabilidades:
 * - Crear canales de notificación requeridos por Android 8.0+ (API 26+)
 * - Definir configuración de notificaciones (sonido, vibración, importancia)
 * - Ser llamado una sola vez en el inicio de la aplicación (MainActivity.onCreate)
 * 
 * PASO 9: Configuración de Firebase Cloud Messaging
 * 
 * @author Offside Club Development Team
 * @version 1.0
 */
public class NotificationChannelManager {

    /**
     * Channel ID para notificaciones de alta importancia
     * Debe coincidir con el valor en AndroidManifest.xml:
     * com.google.firebase.messaging.default_notification_channel_id
     */
    public static final String HIGH_IMPORTANCE_CHANNEL_ID = "high_importance_channel";

    /**
     * Crea los canales de notificación requeridos por Android 8.0+
     * 
     * Nota: En Android 7.1 e inferiores, este método no hace nada (sin efecto)
     * Android 8.0+ (API 26+) requiere canales de notificación
     * 
     * @param context Contexto de la aplicación (MainActivity)
     */
    public static void createNotificationChannels(Context context) {
        // Android 8.0+ (API 26+) requiere canales de notificación
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            
            // === High Importance Channel ===
            // Usado por: Firebase Cloud Messaging para notificaciones push
            NotificationChannel highImportanceChannel = new NotificationChannel(
                    HIGH_IMPORTANCE_CHANNEL_ID,
                    "Notificaciones de Ofside Club",           // Nombre visible al usuario
                    NotificationManager.IMPORTANCE_HIGH         // Importancia: ALTA
            );
            
            // Descripción mostrada en Settings > Apps > Notificaciones
            highImportanceChannel.setDescription(
                    "Notificaciones importantes de partidos, resultados y eventos especiales"
            );
            
            // === Configuración de Audio ===
            // Sonido por defecto del sistema
            highImportanceChannel.setShowBadge(true);          // Mostrar punto rojo en ícono
            highImportanceChannel.enableLights(true);          // Activar LED de notificación
            
            // === Obtener NotificationManager y Registrar Canal ===
            NotificationManager notificationManager = 
                    context.getSystemService(NotificationManager.class);
            
            if (notificationManager != null) {
                notificationManager.createNotificationChannel(highImportanceChannel);
                android.util.Log.i(
                        "NotificationChannelManager",
                        "✅ Canal de notificación 'high_importance_channel' creado exitosamente"
                );
            }
        } else {
            // Android 7.1 e inferiores no necesitan canales
            android.util.Log.d(
                    "NotificationChannelManager",
                    "Android version < O, canales no necesarios"
            );
        }
    }

    /**
     * Elimina un canal de notificación (menos común, por compatibilidad)
     * 
     * Nota: Android no permite eliminar canales de las versiones recientes,
     * pero sí permite "disable" (deshabilitar) desde Settings del usuario
     * 
     * @param context Contexto de la aplicación
     * @param channelId ID del canal a eliminar
     */
    public static void deleteNotificationChannel(Context context, String channelId) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            NotificationManager notificationManager = 
                    context.getSystemService(NotificationManager.class);
            
            if (notificationManager != null) {
                notificationManager.deleteNotificationChannel(channelId);
                android.util.Log.i(
                        "NotificationChannelManager",
                        "Canal de notificación eliminado: " + channelId
                );
            }
        }
    }
}
