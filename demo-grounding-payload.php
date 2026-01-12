<?php
/**
 * DEMOSTRACIÓN: Cómo INTENTAR habilitar Grounding en Gemini
 * (Aunque probablemente no funcione sin acceso especial de Google)
 */

// Esto es lo que DEBERÍA estar en GeminiService::callGemini()
// pero NO está implementado actualmente

$payload_CON_grounding = [
    'contents' => [
        [
            'parts' => [
                [
                    'text' => 'Busca en internet: ¿Cuáles son los partidos de La Liga para el 10 de enero de 2026?'
                ]
            ]
        ]
    ],

    // ← ESTO FALTA en el código actual
    'tools' => [
        [
            'googleSearch' => (object)[]
        ]
    ],

    'generationConfig' => [
        'temperature' => 0.5,
        'maxOutputTokens' => 4096,
        // Opcional en Gemini 2+
        // 'groundingConfig' => [
        //     'googleSearch' => [
        //         'searchQueries' => [
        //             'La Liga fixtures 10 enero 2026'
        //         ]
        //     ]
        // ]
    ]
];

// La respuesta CON grounding incluiría:
$respuesta_con_grounding = [
    'candidates' => [
        [
            'content' => [
                'parts' => [
                    [
                        'text' => 'Los partidos son...'
                    ]
                ]
            ],
            // ← ESTO es lo diferente: citaciones/referencias
            'groundingMetadata' => [
                'groundingSearches' => [
                    [
                        'webSearches' => [
                            [
                                'uri' => 'https://www.laliga.es/...',
                                'title' => 'La Liga - Partidos'
                            ]
                        ]
                    ]
                ],
                'groundingAttributions' => [
                    [
                        'segment' => [
                            'startIndex' => 0,
                            'endIndex' => 50
                        ],
                        'confidenceScore' => 0.95,
                        'web' => [
                            'uri' => 'https://www.laliga.es/...'
                        ]
                    ]
                ]
            ]
        ]
    ]
];

echo "═══════════════════════════════════════════════════════════════\n";
echo "📊 COMPARACIÓN: Sin Grounding vs Con Grounding\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "❌ SIN GROUNDING (estado actual):\n";
echo "───────────────────────────────────────────────────────────────\n";
echo "• Usa solo conocimiento del modelo (training cutoff)\n";
echo "• Puede devolver información desactualizada\n";
echo "• No tiene acceso a web en tiempo real\n";
echo "• No incluye citas/referencias\n";
echo "• Respuestas más genéricas\n";
echo "• Velocidad: RÁPIDA\n";
echo "• Confiabilidad para datos actuales: BAJA\n\n";

echo "✅ CON GROUNDING (lo que necesitarías):\n";
echo "───────────────────────────────────────────────────────────────\n";
echo "• Busca en Google en tiempo real\n";
echo "• Acceso a información actual\n";
echo "• Respuestas con citas/referencias\n";
echo "• 'groundingMetadata' en la respuesta\n";
echo "• Respuestas verificables\n";
echo "• Velocidad: MÁS LENTA (2-5s extra)\n";
echo "• Confiabilidad para datos actuales: MUY ALTA\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "⚠️  PROBLEMAS CON GROUNDING HOY:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "1. NO DISPONIBLE en Gemini 3 Flash Preview\n";
echo "   → Solo en Gemini 2 Pro (pero requiere acceso especial)\n\n";

echo "2. REQUIERE AUTENTICACIÓN ESPECIAL\n";
echo "   → Google limita acceso a grounding\n";
echo "   → Debe estar en lista blanca de Google\n\n";

echo "3. RATE LIMITING MÁS AGRESIVO\n";
echo "   → Cada búsqueda web = más tokens\n";
echo "   → Límites mucho más bajos\n\n";

echo "4. COSTO ADICIONAL\n";
echo "   → Búsquedas web consumen más\n";
echo "   → Generalmente requiere plan pagado\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ CONCLUSIÓN:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Para hoy: Football-Data.org (gratuito, confiable)\n";
echo "No intentes forzar grounding en Gemini 3 Flash\n";
echo "Cuando Google lo habilite: actualiza a Gemini 2 Pro\n\n";
