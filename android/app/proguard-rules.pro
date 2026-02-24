# Add project specific ProGuard rules here.
# You can control the set of applied configuration files using the
# proguardFiles setting in build.gradle.
#
# For more details, see
#   http://developer.android.com/guide/developing/tools/proguard.html

# Preserve line numbers for debugging (necesario para Play Store)
-keepattributes SourceFile,LineNumberTable
-renamesourcefileattribute SourceFile

# ========== Capacitor ==========
-keep class com.getcapacitor.** { *; }
-keep public class com.getcapacitor.Bridge { *; }
-keep class com.getcapacitor.android.** { *; }

# ========== Firebase ==========
-keep class com.google.firebase.** { *; }
-keep class com.google.android.gms.** { *; }
-keepclassmembers class com.google.firebase.** { *; }
-keepclassmembers class com.google.android.gms.** { *; }

# ========== Plugins Capacitor ==========
-keep class io.capawesome.capacitorjs.plugins.firebase.messaging.** { *; }
-keep class com.capacitorjs.plugins.app.** { *; }
-keep class com.capacitorjs.plugins.device.** { *; }
-keep class com.capacitorjs.plugins.applauncherapp.** { *; }

# ========== WebView ==========
-keep class android.webkit.** { *; }
-keepclassmembers class android.webkit.** { *; }

# ========== App-specific classes ==========
-keep class com.offsideclub.app.** { *; }
-keepclassmembers class com.offsideclub.app.** { *; }

# ========== AndroidX ==========
-keep class androidx.** { *; }
-keepclassmembers class androidx.** { *; }

# ========== JSON/Serialization ==========
-keepclassmembers class * {
    public <init>(android.content.Context);
}

# ========== Native methods ==========
-keepclasseswithmembernames class * {
    native <methods>;
}
