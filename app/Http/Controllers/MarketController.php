<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MarketController extends Controller
{
    /**
     * Display the marketplace with sponsored products
     */
    public function index()
    {
        // Mock data for sponsored products
        $products = [
            [
                'id' => 1,
                'name' => 'Botines Nike Phantom',
                'sponsor' => 'Nike',
                'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a9/Nike_logo.svg/200px-Nike_logo.svg.png',
                'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=500&h=500&fit=crop',
                'price' => '$180',
                'description' => 'Botines de fútbol profesionales con tecnología Phantom Vision',
                'rating' => 4.8,
                'category' => 'Botines'
            ],
            [
                'id' => 2,
                'name' => 'Jersey Manchester United 2024',
                'sponsor' => 'Manchester United',
                'logo' => 'https://upload.wikimedia.org/wikipedia/en/7/7a/Manchester_United_FC_Badge.png',
                'image' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=500&h=500&fit=crop',
                'price' => '$89.99',
                'description' => 'Jersey oficial del Manchester United temporada 2024/25',
                'rating' => 4.6,
                'category' => 'Camisetas'
            ],
            [
                'id' => 3,
                'name' => 'Balón Adidas Champions League',
                'sponsor' => 'Adidas',
                'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1f/Adidas_logo.svg/200px-Adidas_logo.svg.png',
                'image' => 'https://images.unsplash.com/photo-1579952363873-27f3bade9e55?w=500&h=500&fit=crop',
                'price' => '$160',
                'description' => 'Balón oficial de la UEFA Champions League',
                'rating' => 4.9,
                'category' => 'Balones'
            ],
            [
                'id' => 4,
                'name' => 'Shorts Puma Training',
                'sponsor' => 'Puma',
                'logo' => 'https://upload.wikimedia.org/wikipedia/en/d/da/Puma_logo.svg',
                'image' => 'https://images.unsplash.com/photo-1591195853828-11db59a44f6b?w=500&h=500&fit=crop',
                'price' => '$49.99',
                'description' => 'Shorts de entrenamiento con tecnología de secado rápido',
                'rating' => 4.5,
                'category' => 'Shorts'
            ],
            [
                'id' => 5,
                'name' => 'Guantes Portero Nike',
                'sponsor' => 'Nike',
                'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a9/Nike_logo.svg/200px-Nike_logo.svg.png',
                'image' => 'https://images.unsplash.com/photo-1599057110005-fb5eef5c5fef?w=500&h=500&fit=crop',
                'price' => '$79.99',
                'description' => 'Guantes profesionales para porteros con latex de alta adherencia',
                'rating' => 4.7,
                'category' => 'Accesorios'
            ],
            [
                'id' => 6,
                'name' => 'Calcetines Compresión Adidas',
                'sponsor' => 'Adidas',
                'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1f/Adidas_logo.svg/200px-Adidas_logo.svg.png',
                'image' => 'https://images.unsplash.com/photo-1608068957830-1c5c2f2e1e41?w=500&h=500&fit=crop',
                'price' => '$24.99',
                'description' => 'Calcetines con compresión graduada para mejor rendimiento',
                'rating' => 4.4,
                'category' => 'Calcetines'
            ],
            [
                'id' => 7,
                'name' => 'Mochilas Arsenal FC',
                'sponsor' => 'Arsenal FC',
                'logo' => 'https://upload.wikimedia.org/wikipedia/en/5/53/Arsenal_FC.svg',
                'image' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=500&h=500&fit=crop',
                'price' => '$59.99',
                'description' => 'Mochila oficial del Arsenal con compartimentos especiales',
                'rating' => 4.3,
                'category' => 'Bolsas'
            ],
            [
                'id' => 8,
                'name' => 'Botella Hidratación Puma',
                'sponsor' => 'Puma',
                'logo' => 'https://upload.wikimedia.org/wikipedia/en/d/da/Puma_logo.svg',
                'image' => 'https://images.unsplash.com/photo-1602143407151-7e6dc1bfb94f?w=500&h=500&fit=crop',
                'price' => '$34.99',
                'description' => 'Botella térmica manteniendo temperatura por 24 horas',
                'rating' => 4.6,
                'category' => 'Accesorios'
            ]
        ];

        // Group products by sponsor
        $sponsors = collect($products)->groupBy('sponsor');

        return view('market.index', compact('products', 'sponsors'));
    }

    /**
     * Show product details
     */
    public function show($id)
    {
        // This would fetch from database in a real implementation
        return redirect()->route('market.index');
    }
}
