// 🔍 SCRIPT DE DIAGNÓSTICO PARA ANDROID - Pega esto en la consola de Chrome DevTools (F12)

console.log('=== 🔍 DIAGNÓSTICO OFFSIDE CLUB ANDROID ===\n');

// 1. Verificar CSRF Token
console.log('1️⃣ CSRF TOKEN:');
const csrfMeta = document.querySelector('meta[name="csrf-token"]');
if (csrfMeta) {
    const csrfToken = csrfMeta.getAttribute('content');
    console.log('✅ Meta tag existe');
    console.log('   Token: ' + (csrfToken ? csrfToken.substring(0, 20) + '...' : 'EMPTY'));
} else {
    console.error('❌ NO SE ENCUENTRA meta[name="csrf-token"]');
}

// 2. Verificar Cookies
console.log('\n2️⃣ COOKIES:');
const cookies = document.cookie.split(';').map(c => c.trim());
if (cookies.length > 0) {
    console.log('✅ Cookies encontradas:');
    cookies.forEach(cookie => {
        const [name, value] = cookie.split('=');
        if (name.includes('SESSION') || name.includes('XSRF') || name.includes('LARAVEL')) {
            console.log(`   ${name}: ${value?.substring(0, 15)}...`);
        }
    });
} else {
    console.error('❌ NO HAY COOKIES');
}

// 3. Verificar localStorage
console.log('\n3️⃣ LOCAL STORAGE (Pre Match):');
const prematchKeys = Object.keys(localStorage).filter(k => k.includes('prematch'));
if (prematchKeys.length > 0) {
    prematchKeys.forEach(key => {
        const val = localStorage.getItem(key);
        console.log('   ' + key + ': ' + val);
    });
} else {
    console.log('⚠️  Sin localStorage de prematch (normal la primera vez)');
}

// 4. Verificar usuario actual
console.log('\n4️⃣ USUARIO ACTUAL:');
const userIdMeta = document.querySelector('meta[name="user-id"]');
const userId = userIdMeta?.getAttribute('content');
console.log(`   User ID: ${userId}`);
if (typeof currentUserId !== 'undefined') {
    console.log(`   currentUserId variable: ${currentUserId}`);
} else {
    console.warn('⚠️  currentUserId NO está definido');
}

// 5. Verificar origen
console.log('\n5️⃣ ORIGEN:');
console.log(`   window.location.origin: ${window.location.origin}`);
console.log(`   window.location.href: ${window.location.href}`);

// 6. Test de fetch (pequeño)
console.log('\n6️⃣ TEST DE FETCH (última propuesta):');
const preMatchId = window.location.pathname.match(/pre-matches\/(\d+)/)?.[1];
if (preMatchId) {
    console.log(`   Pre Match ID: ${preMatchId}`);

    fetch(`${window.location.origin}/api/pre-matches/${preMatchId}/events-poll?last_id=0`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'include'
    })
    .then(r => {
        console.log(`   Response status: ${r.status}`);
        if (r.status === 401) console.error('❌ 401: NO AUTENTICADO');
        if (r.status === 403) console.error('❌ 403: SIN PERMISO');
        if (r.status === 200) console.log('✅ 200: OK');
        return r.json();
    })
    .then(data => {
        console.log(`   Events: ${data.events?.length || 0}`);
        console.log(`   Last ID: ${data.last_id}`);
    })
    .catch(e => console.error('❌ Fetch error:', e.message));
} else {
    console.warn('⚠️  No se pudo detectar Pre Match ID');
}

console.log('\n=== FIN DIAGNÓSTICO ===\n');
console.log('📸 Captura de pantalla: screenshot en ChromeDevTools');
console.log('💾 Copia este output y comparte con el soporte');
