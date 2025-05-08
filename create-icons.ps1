# Crear un ícono simple usando .NET
Add-Type -AssemblyName System.Drawing

# Directorio de imágenes
$imagesDir = "public\images"

# Asegurarse de que el directorio existe
if (-not (Test-Path -Path $imagesDir)) {
    New-Item -ItemType Directory -Path $imagesDir | Out-Null
}

# Tamaños de íconos a crear
$sizes = @(192, 512)

foreach ($size in $sizes) {
    # Crear un nuevo bitmap
    $bitmap = New-Object System.Drawing.Bitmap($size, $size)
    $graphics = [System.Drawing.Graphics]::FromImage($bitmap)
    
    # Rellenar el fondo
    $graphics.Clear([System.Drawing.Color]::FromArgb(45, 55, 72))  # Color offside-dark
    
    # Configurar el texto
    $font = New-Object System.Drawing.Font("Arial", ($size * 0.4), [System.Drawing.FontStyle]::Bold)
    $brush = [System.Drawing.Brushes]::White
    
    # Calcular la posición del texto
    $text = "OC"
    $textSize = $graphics.MeasureString($text, $font)
    $x = ($size - $textSize.Width) / 2
    $y = ($size - $textSize.Height) / 2
    
    # Dibujar el texto
    $graphics.DrawString($text, $font, $brush, $x, $y)
    
    # Guardar la imagen
    $filename = "$imagesDir\logo-offside-${size}x${size}.png"
    $bitmap.Save($filename, [System.Drawing.Imaging.ImageFormat]::Png)
    
    Write-Host "Ícono creado: $filename"
    
    # Liberar recursos
    $graphics.Dispose()
    $bitmap.Dispose()
}

Write-Host "Todos los íconos han sido creados exitosamente"
