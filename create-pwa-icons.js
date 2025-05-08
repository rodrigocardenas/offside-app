import { createCanvas, loadImage } from 'canvas';
import { fileURLToPath } from 'url';
import { dirname } from 'path';
import fs from 'fs';
import path from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// Crear directorio de imágenes si no existe
const imagesDir = path.join(__dirname, 'public', 'images');
if (!fs.existsSync(imagesDir)) {
    fs.mkdirSync(imagesDir, { recursive: true });
}

// Tamaños de íconos necesarios
const iconSizes = [192, 512];

// Función para crear un ícono
async function createIcon(size) {
    const canvas = createCanvas(size, size);
    const ctx = canvas.getContext('2d');
    
    // Fondo del ícono (puedes personalizar el color)
    ctx.fillStyle = '#2d3748';
    ctx.fillRect(0, 0, size, size);
    
    // Texto en el ícono (puedes personalizar el texto y el estilo)
    ctx.fillStyle = '#ffffff';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    
    // Ajustar el tamaño de la fuente según el tamaño del ícono
    const fontSize = size * 0.4;
    ctx.font = `bold ${fontSize}px Arial`;
    
    // Texto centrado
    ctx.fillText('OC', size / 2, size / 2);
    
    // Guardar el ícono
    const buffer = canvas.toBuffer('image/png');
    const filename = path.join(imagesDir, `logo-offside-${size}x${size}.png`);
    fs.writeFileSync(filename, buffer);
    
    console.log(`Ícono creado: ${filename}`);
}

// Crear todos los íconos
async function createAllIcons() {
    for (const size of iconSizes) {
        await createIcon(size);
    }
    
    // Crear también una copia para apple-touch-icon
    const appleIconPath = path.join(imagesDir, 'apple-touch-icon.png');
    const defaultIconPath = path.join(imagesDir, 'logo-offside-192x192.png');
    
    if (fs.existsSync(defaultIconPath)) {
        fs.copyFileSync(defaultIconPath, appleIconPath);
        console.log(`Ícono de Apple creado: ${appleIconPath}`);
    }
}

// Ejecutar la creación de íconos
createAllIcons()
    .then(() => console.log('Todos los íconos han sido creados exitosamente'))
    .catch(error => console.error('Error al crear los íconos:', error));
