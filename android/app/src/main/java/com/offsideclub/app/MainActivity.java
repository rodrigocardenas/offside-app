package com.offsideclub.app;

import android.content.Intent;
import android.os.Bundle;
import com.getcapacitor.BridgeActivity;
import com.getcapacitor.JSObject;

public class MainActivity extends BridgeActivity {
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
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
        android.util.Log.d("DeepLink", "Intent data: " + data);
        android.util.Log.d("DeepLink", "Intent action: " + intent.getAction());

        if (data != null && data.startsWith("https://app.offsideclub.es")) {
            android.util.Log.d("DeepLink", "Deep link detectado: " + data);
        }
    }
}
