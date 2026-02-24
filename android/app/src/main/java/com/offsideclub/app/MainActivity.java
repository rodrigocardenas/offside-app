package com.offsideclub.app;

import android.content.Intent;
import android.content.pm.ResolveInfo;
import android.os.Bundle;
import android.net.Uri;
import com.getcapacitor.BridgeActivity;
import java.util.List;

public class MainActivity extends BridgeActivity {
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        // PASO 9: Crear canales de notificación para Firebase Cloud Messaging
        // Requerido para Android 8.0+ (API 26+)
        NotificationChannelManager.createNotificationChannels(this);
        
        handleDeepLink(getIntent());
    }

    @Override
    public void onNewIntent(Intent intent) {
        super.onNewIntent(intent);
        setIntent(intent);
        handleDeepLink(intent);
    }

    private void handleDeepLink(Intent intent) {
        String data = intent.getDataString();
        String action = intent.getAction();
        Uri uri = intent.getData();

        android.util.Log.d("DeepLink", "Intent data: " + data);
        android.util.Log.d("DeepLink", "Intent action: " + action);

        if (uri != null && uri.getHost() != null && uri.getHost().equals("app.offsideclub.es")) {
            // Verificar handlers disponibles
            Intent queryIntent = new Intent(Intent.ACTION_VIEW);
            queryIntent.setData(uri);
            List<ResolveInfo> resolveInfos = getPackageManager().queryIntentActivities(queryIntent, 0);
            
            android.util.Log.d("DeepLink", "Total handlers para dominio: " + resolveInfos.size());
            for (ResolveInfo info : resolveInfos) {
                android.util.Log.d("DeepLink", "  Handler: " + info.activityInfo.packageName);
            }
        }

        if (data != null && data.startsWith("https://app.offsideclub.es")) {
            android.util.Log.d("DeepLink", "Deep link detectado: " + data);
        }
    }
}
