package com.offsideclub.app;

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.net.Uri;
import android.util.Log;

/**
 * BroadcastReceiver global para capturar TODOS los intents VIEW hacia app.offsideclub.es
 * Este actúa como interceptor antes de que Android decida qué app abrir
 */
public class DeepLinkReceiver extends BroadcastReceiver {
    private static final String TAG = "DeepLinkReceiver";

    @Override
    public void onReceive(Context context, Intent intent) {
        Log.d(TAG, "onReceive called");
        
        if (intent.getAction() == null) {
            return;
        }

        String action = intent.getAction();
        String dataString = intent.getDataString();
        Uri data = intent.getData();

        Log.d(TAG, "Action: " + action);
        Log.d(TAG, "Data: " + dataString);
        Log.d(TAG, "Intent: " + intent.toString());

        // Si es un VIEW intent hacia app.offsideclub.es
        if (Intent.ACTION_VIEW.equals(action) && data != null) {
            String host = data.getHost();
            if (host != null && host.equals("app.offsideclub.es")) {
                Log.d(TAG, "Deep link válido detectado: " + dataString);
                
                // Crear un intent explícito hacia MainActivity
                Intent mainIntent = new Intent(context, MainActivity.class);
                mainIntent.setData(data);
                mainIntent.setAction(Intent.ACTION_VIEW);
                mainIntent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TOP);
                
                try {
                    context.startActivity(mainIntent);
                    Log.d(TAG, "MainActivity iniciada con deep link");
                    
                    // Abortar broadcast para que otros receivers no lo procesen
                    abortBroadcast();
                } catch (Exception e) {
                    Log.e(TAG, "Error al iniciar MainActivity", e);
                }
            }
        }
    }
}
