<?php

namespace Database\Seeders;

use App\Models\MatchAction;
use Illuminate\Database\Seeder;

class MatchActionSeeder extends Seeder
{
    /**
     * Acciones de partido predefinidas para sugerir
     */
    public function run(): void
    {
        $actions = [
            // GOLES Y ANOTACIONES
            ['title' => 'Gol de cabeza', 'description' => 'Anotación con la cabeza', 'category' => 'goal', 'icon' => '⚽', 'popularity' => 0],
            ['title' => 'Gol de pie izquierdo', 'description' => 'Gol anotado con la pierna izquierda', 'category' => 'goal', 'icon' => '⚽', 'popularity' => 0],
            ['title' => 'Gol de pie derecho', 'description' => 'Gol anotado con la pierna derecha', 'category' => 'goal', 'icon' => '⚽', 'popularity' => 0],
            ['title' => 'Gol de chilena', 'description' => 'Gol acrobático de espaldas', 'category' => 'goal', 'icon' => '🎪', 'popularity' => 0],
            ['title' => 'Gol de rebote', 'description' => 'Gol tras rebote del guardameta', 'category' => 'goal', 'icon' => '⚽', 'popularity' => 0],
            ['title' => 'Gol de penalti', 'description' => 'Anotación desde el punto de penalti', 'category' => 'goal', 'icon' => '🎯', 'popularity' => 0],
            ['title' => 'Gol en propia puerta', 'description' => 'Gol anotado en contra', 'category' => 'goal', 'icon' => '❌', 'popularity' => 0],
            ['title' => 'Gol de tiro libre', 'description' => 'Gol directo desde tiro libre', 'category' => 'goal', 'icon' => '⚽', 'popularity' => 0],
            ['title' => 'Gol de volea', 'description' => 'Gol de media volea sin dejar rebotar', 'category' => 'goal', 'icon' => '⚽', 'popularity' => 0],
            
            // DIFERENCIAS DE GOLES
            ['title' => 'Local gana por 1 gol', 'description' => 'Equipo local gana con diferencia de 1', 'category' => 'condition', 'icon' => '🏠', 'popularity' => 0],
            ['title' => 'Local gana por 2 goles', 'description' => 'Equipo local gana con diferencia de 2', 'category' => 'condition', 'icon' => '🏠', 'popularity' => 0],
            ['title' => 'Local gana por 3 goles', 'description' => 'Equipo local gana con diferencia de 3', 'category' => 'condition', 'icon' => '🏠', 'popularity' => 0],
            ['title' => 'Visitante gana por 1 gol', 'description' => 'Equipo visitante gana con diferencia de 1', 'category' => 'condition', 'icon' => '🚌', 'popularity' => 0],
            ['title' => 'Visitante gana por 2 goles', 'description' => 'Equipo visitante gana con diferencia de 2', 'category' => 'condition', 'icon' => '🚌', 'popularity' => 0],
            ['title' => 'Visitante gana por 3 goles', 'description' => 'Equipo visitante gana con diferencia de 3', 'category' => 'condition', 'icon' => '🚌', 'popularity' => 0],
            ['title' => 'Empate 0-0', 'description' => 'Partido termina sin goles', 'category' => 'condition', 'icon' => '🤝', 'popularity' => 0],
            ['title' => 'Empate 1-1', 'description' => 'Ambos equipos anotan 1 gol cada uno', 'category' => 'condition', 'icon' => '🤝', 'popularity' => 0],
            ['title' => 'Empate 2-2', 'description' => 'Ambos equipos anotan 2 goles cada uno', 'category' => 'condition', 'icon' => '🤝', 'popularity' => 0],
            
            // TARJETAS
            ['title' => 'Tarjeta roja a local', 'description' => 'Expulsión de jugador del equipo local', 'category' => 'event', 'icon' => '🔴', 'popularity' => 0],
            ['title' => 'Tarjeta roja a visitante', 'description' => 'Expulsión de jugador del equipo visitante', 'category' => 'event', 'icon' => '🔴', 'popularity' => 0],
            ['title' => 'Tarjeta amarilla a local', 'description' => 'Amonestación de jugador local', 'category' => 'event', 'icon' => '🟡', 'popularity' => 0],
            ['title' => 'Tarjeta amarilla a visitante', 'description' => 'Amonestación de jugador visitante', 'category' => 'event', 'icon' => '🟡', 'popularity' => 0],
            ['title' => 'Dos tarjetas amarillas', 'description' => 'Un jugador recibe dos amarillas y ve roja', 'category' => 'event', 'icon' => '🔴', 'popularity' => 0],
            
            // HITOS DEL PARTIDO
            ['title' => 'Primer gol antes del minuto 15', 'description' => 'Gol en los primeros minutos', 'category' => 'timing', 'icon' => '⚡', 'popularity' => 0],
            ['title' => 'Gol en segundo tiempo', 'description' => 'Primer gol después del minuto 45', 'category' => 'timing', 'icon' => '⌚', 'popularity' => 0],
            ['title' => 'Gol en los últimos 10 minutos', 'description' => 'Gol muy al final del partido', 'category' => 'timing', 'icon' => '⏰', 'popularity' => 0],
            ['title' => 'Más de 3 goles totales', 'description' => 'El partido tiene 4 o más goles', 'category' => 'condition', 'icon' => '🎊', 'popularity' => 0],
            ['title' => 'Menos de 3 goles totales', 'description' => 'El partido tiene menos de 3 goles', 'category' => 'condition', 'icon' => '😴', 'popularity' => 0],
            ['title' => 'Más de 5 goles totales', 'description' => 'Partidazo con muchos goles', 'category' => 'condition', 'icon' => '🎆', 'popularity' => 0],
            
            // JUGADORES Y ASISTENCIAS
            ['title' => 'Gol del máximo anotador', 'description' => 'Anotación del delantero estrella', 'category' => 'event', 'icon' => '⭐', 'popularity' => 0],
            ['title' => 'Asistencia de lateral', 'description' => 'Pase gol desde la banda', 'category' => 'event', 'icon' => '🎯', 'popularity' => 0],
            ['title' => 'Gol de defensa', 'description' => 'Un defensa anota', 'category' => 'event', 'icon' => '🛡️', 'popularity' => 0],
            ['title' => 'Gol de portero en tiempo añadido', 'description' => 'Portero asciende para anotar', 'category' => 'event', 'icon' => '😱', 'popularity' => 0],
            
            // ÁRBITRO Y DECISIONES
            ['title' => 'Penalti pitado', 'description' => 'Al menos un penalti en el partido', 'category' => 'event', 'icon' => '🎯', 'popularity' => 0],
            ['title' => 'Penalti no convertido', 'description' => 'Un penalti falla', 'category' => 'event', 'icon' => '😩', 'popularity' => 0],
            ['title' => 'Gol anulado por fuera de juego', 'description' => 'Gol no validado por offside', 'category' => 'event', 'icon' => '📍', 'popularity' => 0],
            ['title' => 'VAR revierte decisión', 'description' => 'El árbitro cambia su decisión tras revisar', 'category' => 'event', 'icon' => '📺', 'popularity' => 0],
            
            // GOLPES Y LESIONES
            ['title' => 'Lesión importante', 'description' => 'Un jugador clave se lesiona', 'category' => 'event', 'icon' => '🤕', 'popularity' => 0],
            ['title' => 'Cambio en los últimos minutos', 'description' => 'Sustitución en tiempo de descuento', 'category' => 'event', 'icon' => '🔄', 'popularity' => 0],
            
            // JUEGO Y POSESIÓN
            ['title' => 'Más del 60% de posesión local', 'description' => 'Equipo local domina la posesión', 'category' => 'condition', 'icon' => '🎮', 'popularity' => 0],
            ['title' => 'Contraataque exitoso', 'description' => 'Gol en transición rápida', 'category' => 'event', 'icon' => '⚡', 'popularity' => 0],
            ['title' => 'Dominio total de local', 'description' => 'Local controla claramente el juego', 'category' => 'condition', 'icon' => '👑', 'popularity' => 0],
            
            // REMONTADAS Y GIROS
            ['title' => 'Remontada en segundo tiempo', 'description' => 'Un equipo va perdiendo y empareja o gana', 'category' => 'event', 'icon' => '🔄', 'popularity' => 0],
            ['title' => 'Gol del empate en último minuto', 'description' => 'Gol de la igualdad muy al final', 'category' => 'timing', 'icon' => '⏱️', 'popularity' => 0],
            ['title' => 'Gol de la victoria en tiempo añadido', 'description' => 'Gol ganador en los últimos segundos', 'category' => 'timing', 'icon' => '🏁', 'popularity' => 0],
        ];

        foreach ($actions as $action) {
            MatchAction::create($action);
        }
    }
}

