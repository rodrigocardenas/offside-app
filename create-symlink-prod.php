#!/usr/bin/env php
<?php
/**
 * Script para crear symlink de storage en producción
 * Ejecutar: ssh usuario@servidor "php /tmp/create-symlink.php"
 */

$appPath = '/var/www/html/offside-app';
$publicPath = $appPath . '/public';
$storagePath = $appPath . '/storage/app/public';
$linkPath = $publicPath . '/storage';

echo "🔍 Verificando symlink de storage en producción\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "📁 Rutas:\n";
echo "   App:     {$appPath}\n";
echo "   Link:    {$linkPath}\n";
echo "   Target:  {$storagePath}\n\n";

// Verificar que existen las rutas
if (!is_dir($appPath)) {
    echo "❌ ERROR: Ruta de aplicación no encontrada: {$appPath}\n";
    exit(1);
}

if (!is_dir($storagePath)) {
    echo "❌ ERROR: Ruta de storage no encontrada: {$storagePath}\n";
    exit(1);
}

echo "✅ Rutas existen\n\n";

// Verificar symlink actual
echo "🔗 Estado del symlink:\n";
if (is_link($linkPath)) {
    $target = readlink($linkPath);
    if (is_dir($linkPath)) {
        echo "   ✅ Symlink válido\n";
        echo "   Apunta a: {$target}\n";
    } else {
        echo "   ⚠️  Symlink roto\n";
        echo "   Apunta a: {$target} (NO EXISTE)\n";
        echo "\n🔧 Reparando symlink...\n";
        unlink($linkPath);
        symlink('../storage/app/public', $linkPath);
        echo "✅ Symlink reparado\n";
    }
} elseif (is_dir($linkPath)) {
    echo "   ⚠️  Directorio común (no symlink)\n";
    echo "   Moviendo a backup...\n";
    rename($linkPath, $linkPath . '.bak');
    symlink('../storage/app/public', $linkPath);
    echo "✅ Symlink creado\n";
} else {
    echo "   ❌ No existe symlink\n";
    echo "   Creando...\n";
    symlink('../storage/app/public', $linkPath);
    echo "✅ Symlink creado\n";
}

// Verificación final
echo "\n🔍 Verificación final:\n";
if (is_link($linkPath) && is_dir($linkPath)) {
    $target = readlink($linkPath);
    echo "✅ Symlink está OK\n";
    echo "   Target: {$target}\n\n";
    
    // Mostrar algunos logos
    $logos = glob($linkPath . '/logos/*.png');
    if (!empty($logos)) {
        echo "📸 Logos encontrados: " . count($logos) . "\n";
        echo "   Ejemplos:\n";
        for ($i = 0; $i < min(3, count($logos)); $i++) {
            $fileName = basename($logos[$i]);
            echo "     - {$fileName}\n";
        }
    }
    echo "\n✨ ¡Configuración exitosa!\n";
} else {
    echo "❌ ERROR: Symlink no se pudo crear\n";
    exit(1);
}
?>
