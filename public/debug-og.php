<?php
// Archivo temporal para depurar las meta tags de Open Graph
// Eliminar después de verificar que funciona

$url = $_GET['url'] ?? 'https://offside.club';
$html = file_get_contents($url);

// Extraer las meta tags de Open Graph
preg_match_all('/<meta[^>]+property="og:[^"]+"[^>]+content="([^"]+)"/', $html, $matches);

echo "<h1>Debug Open Graph Meta Tags</h1>";
echo "<p>URL analizada: $url</p>";
echo "<h2>Meta tags encontradas:</h2>";

if (!empty($matches[1])) {
    foreach ($matches[1] as $match) {
        echo "<p><strong>Content:</strong> $match</p>";
    }
} else {
    echo "<p>No se encontraron meta tags de Open Graph</p>";
}

// Mostrar el HTML completo para debugging
echo "<h2>HTML completo:</h2>";
echo "<pre>" . htmlspecialchars($html) . "</pre>";
?>
