const fs = require('fs');
const path = require('path');

const dirs = [
    'resources/views/components/layout',
    'resources/views/components/predictions',
    'resources/views/components/common',
    'resources/views/components/matches',
    'resources/views/components/chat',
    'public/js/groups',
    'public/js/predictions',
    'public/js/chat',
    'public/js/rankings',
    'public/js/common'
];

console.log('Creando directorios...\n');

dirs.forEach(dir => {
    const fullPath = path.join(__dirname, dir);
    fs.mkdirSync(fullPath, { recursive: true });
    console.log(`✅ ${dir}`);
});

console.log('\n✅ Todos los directorios creados exitosamente!');
