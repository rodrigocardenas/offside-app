const fs = require('fs');
const path = require('path');
const { createCanvas } = require('canvas');

// Crear directorio de imágenes si no existe
const imagesDir = path.join(__dirname, 'public', 'images');
if (!fs.existsSync(imagesDir)) {
    fs.mkdirSync(imagesDir, { recursive: true });
}

// Tamaños de íconos necesarios
const sizes = [192, 512];

// Función para crear un ícono
function createIcon(size) {
    const canvas = createCanvas(size, size);
    const ctx = canvas.getContext('2d');
    
    // Fondo del ícono
    ctx.fillStyle = '#2d3748';
    ctx.fillRect(0, 0, size, size);
    
    // Texto
    ctx.fillStyle = '#ffffff';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    
    // Tamaño de fuente proporcional
    const fontSize = size * 0.4;
    ctx.font = `bold ${fontSize}px Arial`;
    
    // Texto centrado
    ctx.fillText('OC', size / 2, size / 2);
    
    // Guardar el archivo
    const buffer = canvas.toBuffer('image/png');
    const filename = path.join(imagesDir, `logo-offside-${size}x${size}.png`);
    fs.writeFileSync(filename, buffer);
    
    console.log(`Ícono creado: ${filename}`);
}

// Crear todos los íconos
sizes.forEach(size => {
    createIcon(size);
});

console.log('Todos los íconos han sido creados exitosamente');
