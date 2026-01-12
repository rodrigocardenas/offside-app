# Módulo de Marketplace - Implementación

## Resumen
Se ha implementado un nuevo módulo de **Marketplace** (Tienda) en la aplicación Offside Club, que permite mostrar productos deportivos de sponsors de forma coherente con el diseño actual de la aplicación.

## Componentes Creados

### 1. **Controlador: MarketController**
**Ubicación**: `app/Http/Controllers/MarketController.php`

- **Método `index()`**: Muestra el marketplace con productos patrocinados
  - Incluye datos mock de 8 productos iniciales
  - Agrupa productos por sponsor
  - Pasa datos a la vista

- **Datos Mock Incluidos**:
  - Botines Nike Phantom
  - Jersey Manchester United 2024
  - Balón Adidas Champions League
  - Shorts Puma Training
  - Guantes Portero Nike
  - Calcetines Compresión Adidas
  - Mochila Arsenal FC
  - Botella Hidratación Puma

- **Estructura de Producto**:
  ```php
  [
    'id' => int,
    'name' => string,
    'sponsor' => string,
    'logo' => string (URL),
    'image' => string (URL),
    'price' => string,
    'description' => string,
    'rating' => float,
    'category' => string
  ]
  ```

### 2. **Vista: market/index.blade.php**
**Ubicación**: `resources/views/market/index.blade.php`

#### Secciones Incluidas:

**a) Header**
- Título "Marketplace"
- Descripción de productos deportivos

**b) Featured Banner**
- Banner destacado con gradiente de colores del acento
- Botón "Explorar Ahora"
- Animaciones hover

**c) Sección de Sponsors**
- Grid responsive con logos de sponsors
- Muestra cantidad de items por sponsor
- Efectos hover interactivos

**d) Grid de Productos**
- Layout responsivo (auto-fill, mínimo 220px)
- Tarjetas de producto con:
  - Imagen del producto
  - Badge del sponsor
  - Calificación (★ rating)
  - Nombre del producto
  - Descripción
  - Categoría
  - Precio destacado en color acentuado
  - Botón "Ver"
  - Efectos hover (zoom de imagen, elevación de tarjeta)

**e) Sección CTA (Call-to-Action)**
- Invitación para que marcas se asocien al programa
- Botón "Contáctanos"

**f) Menú Inferior Personalizado**
- Enlaces a: Grupos, Rankings, Market (nuevo), Resultados, Perfil
- Market está destacado con color acentuado

### 3. **Rutas Agregadas**
**Ubicación**: `routes/web.php`

```php
Route::middleware(['auth'])->group(function () {
    // Market
    Route::get('market', [MarketController::class, 'index'])->name('market.index');
    Route::get('market/{id}', [MarketController::class, 'show'])->name('market.show');
});
```

### 4. **Actualización de Menú Inferior**
**Ubicación**: `resources/views/components/groups/group-bottom-menu.blade.php`

- Se agregó nuevo elemento de navegación al market
- Icono: carrito de compras (SVG)
- La navegación ahora incluye 5 opciones:
  1. Grupos
  2. Ranking
  3. **Market** (nuevo)
  4. Resultados
  5. Perfil

## Diseño y Coherencia

### Colores y Tema
- Se respetan los colores dinámicos del sistema:
  - Color primario: `#0a2e2c` (dark mode)
  - Color acentuado: `#00deb0` (verde agua)
  - Textos: adaptados al tema del usuario
  
### Componentes Visuales
- Grid responsivo que se adapta a cualquier pantalla
- Tarjetas interactivas con efectos hover
- Animaciones suaves de transición
- Iconos SVG consistentes con el diseño

### Estructura de Layout
- Utiliza `x-app-layout` como base (consistente con otras vistas)
- Padding y márgenes coherentes
- Menú inferior fijo en todas las vistas
- Espaciado adecuado para dispositivos móviles

## Características Futuras

### Base de Datos
Actualmente utiliza datos mock. Para implementación final:

**Tabla `products`**:
```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug');
    $table->text('description');
    $table->decimal('price', 10, 2);
    $table->string('image_url');
    $table->foreignId('sponsor_id')->constrained();
    $table->string('category');
    $table->float('rating')->default(0);
    $table->integer('review_count')->default(0);
    $table->string('external_link')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

**Tabla `sponsors`**:
```php
Schema::create('sponsors', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('logo_url');
    $table->text('description')->nullable();
    $table->string('website')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

### Funcionalidades Adicionales
- [ ] Filtrado por categoría
- [ ] Búsqueda de productos
- [ ] Sistema de favoritos/wishlist
- [ ] Ordenamiento (precio, rating, nuevos)
- [ ] Página de detalle de producto
- [ ] Sistema de reseñas
- [ ] Carrito de compras
- [ ] Integración con pasarelas de pago
- [ ] Notificaciones de nuevos productos
- [ ] Cupones y códigos promocionales

## Instalación/Uso

La funcionalidad está lista para usar. Para acceder:

1. Inicia sesión en la aplicación
2. Navega al menú inferior
3. Haz clic en el icono de **Market** (carrito)
4. Visualiza los productos disponibles

## Notas Técnicas

- ✅ No requiere migración de base de datos
- ✅ Datos hardcodeados para demostración
- ✅ Compatible con el sistema de temas existente
- ✅ Responsive design (móvil/tablet/desktop)
- ✅ Animaciones y efectos coherentes con el resto de la UI
- ✅ Build de Vite compilado exitosamente
