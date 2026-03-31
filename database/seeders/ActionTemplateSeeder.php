<?php

namespace Database\Seeders;

use App\Models\ActionTemplate;
use Illuminate\Database\Seeder;

class ActionTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Crea 50+ plantillas de acciones para Pre Match
     * Categorías: GOALS, CARDS, SCORING, DEFENSE, RARE, FUNNY
     */
    public function run(): void
    {
        $actions = [
            // 🎯 GOALS (Goles) - HIGH probability
            ['action' => 'Gol de tiro libre directo', 'category' => 'GOALS', 'probability' => 0.25, 'description' => 'Gol anotado desde tiro libre sin intermediarios'],
            ['action' => '2+ goles de cabeza', 'category' => 'GOALS', 'probability' => 0.15, 'description' => 'Dos o más goles anotados de cabeza'],
            ['action' => 'Gol en propia puerta', 'category' => 'GOALS', 'probability' => 0.08, 'description' => 'Equipo marca en contra accidentalmente'],
            ['action' => 'Hat-trick del goleador', 'category' => 'GOALS', 'probability' => 0.10, 'description' => 'Un jugador anota 3 goles'],
            ['action' => 'Gol del arquero/defensa', 'category' => 'RARE', 'probability' => 0.02, 'description' => 'Jugador no delantero anota gol'],
            ['action' => 'Gol en los primeros 5 minutos', 'category' => 'GOALS', 'probability' => 0.18, 'description' => 'Primer gol antes del minuto 5'],
            ['action' => 'Gol en últimos 5 minutos', 'category' => 'GOALS', 'probability' => 0.20, 'description' => 'Gol en minuto 85 o posterior'],
            ['action' => '5+ goles en el partido', 'category' => 'GOALS', 'probability' => 0.35, 'description' => 'Total de goles >= 5'],
            
            // 🟨 CARDS (Tarjetas) - HIGH probability
            ['action' => '3+ tarjetas rojas', 'category' => 'CARDS', 'probability' => 0.12, 'description' => 'Tres o más tarjetas rojas en total'],
            ['action' => 'Expulsión en primer tiempo', 'category' => 'CARDS', 'probability' => 0.15, 'description' => 'Tarjeta roja antes del minuto 45'],
            ['action' => '8+ tarjetas amarillas', 'category' => 'CARDS', 'probability' => 0.25, 'description' => 'Ocho o más tarjetas amarillas totales'],
            ['action' => 'Gol anulado por VAR', 'category' => 'SCORING', 'probability' => 0.05, 'description' => 'VAR anula un gol válido (fuera de juego, falta)'],
            
            // ⚽ SCORING (Anotación) - MEDIUM probability
            ['action' => 'Penalti atajado', 'category' => 'SCORING', 'probability' => 0.10, 'description' => 'Arquero detiene penalti'],
            ['action' => 'Penalti errado (poste/afuera)', 'category' => 'SCORING', 'probability' => 0.08, 'description' => 'Falla penalti contra poste o afuera'],
            ['action' => 'Doble penalti en el partido', 'category' => 'RARE', 'probability' => 0.04, 'description' => 'Dos penaltis diferentes anotados/atajados'],
            ['action' => 'Autogol de defensor clave', 'category' => 'RARE', 'probability' => 0.02, 'description' => 'Jugador defensivo importante marca en contra'],
            ['action' => 'Gol sin rematador claro', 'category' => 'FUNNY', 'probability' => 0.05, 'description' => 'Gol por toque casualidad/rebote'],
            
            // 🛡️ DEFENSE (Defensa) - MEDIUM probability
            ['action' => 'Cero goles concedidos', 'category' => 'DEFENSE', 'probability' => 0.20, 'description' => 'Equipo no concede goles (0-x resultado)'],
            ['action' => 'Arquero con 8+ atajadas', 'category' => 'DEFENSE', 'probability' => 0.30, 'description' => 'Arquero hace 8 o más atajadas'],
            ['action' => 'Empate sin goles (0-0)', 'category' => 'DEFENSE', 'probability' => 0.08, 'description' => 'Partido termina 0-0'],
            ['action' => 'Clean sheet + gol anotado', 'category' => 'DEFENSE', 'probability' => 0.18, 'description' => 'Equipo no concede y anota al menos 1'],
            
            // 🎭 RARE (Raras) - LOW probability
            ['action' => 'Lluvia extrema detiene partido', 'category' => 'RARE', 'probability' => 0.01, 'description' => 'Condiciones climáticas interrumpen juego'],
            ['action' => 'Lesión grave de estrella', 'category' => 'RARE', 'probability' => 0.03, 'description' => 'Jugador clave se lesiona seriamente'],
            ['action' => 'Invasión de campo', 'category' => 'FUNNY', 'probability' => 0.02, 'description' => 'Aficionado invade campo de juego'],
            ['action' => 'Gol con baile provocativo', 'category' => 'FUNNY', 'probability' => 0.03, 'description' => 'Goleador baila de forma polémica'],
            ['action' => 'Falta brutal con discusión', 'category' => 'CARDS', 'probability' => 0.06, 'description' => 'Falta de rojo + discusión masiva'],
            ['action' => 'Cambio en minuto 35', 'category' => 'RARE', 'probability' => 0.04, 'description' => 'Cambio táctico tempranero por lesión/roja'],
            ['action' => 'Gol de penalti de rebote', 'category' => 'FUNNY', 'probability' => 0.02, 'description' => 'Penalti atajado, rebote y gol'],
            ['action' => 'Cortina de humo en celebración', 'category' => 'FUNNY', 'probability' => 0.03, 'description' => 'Pirotecnia/humo en celebración de gol'],
            
            // 😂 FUNNY (Divertidas) - LOWMEDIUM probability
            ['action' => 'Celebración de entrenador en cancha', 'category' => 'FUNNY', 'probability' => 0.05, 'description' => 'DT entra a celebrar gol'],
            ['action' => 'Gol anulado ridículamente', 'category' => 'FUNNY', 'probability' => 0.02, 'description' => 'VAR anula gol claro por error visual'],
            ['action' => 'Camiseta rasgada en celebración', 'category' => 'FUNNY', 'probability' => 0.04, 'description' => 'Goleador rasgada camiseta al celebrar'],
            ['action' => 'Defensor bloquea con la cara', 'category' => 'FUNNY', 'probability' => 0.03, 'description' => 'Defensor bloquea tiro con la cara'],
            ['action' => 'Saque de meta fallido (gol)', 'category' => 'FUNNY', 'probability' => 0.05, 'description' => 'Saque de meta va directo a gol'],
            ['action' => 'Caída cómica en el área', 'category' => 'FUNNY', 'probability' => 0.04, 'description' => 'Contacto mínimo, caída dramática'],
            ['action' => 'Tiro lejano inesperado (gol)', 'category' => 'RARE', 'probability' => 0.06, 'description' => 'Tiro de larga distancia entra por sorpresa'],
            
            // Adicionales MEDIUM probability
            ['action' => 'Cambio de jugador en minuto 1', 'category' => 'RARE', 'probability' => 0.01, 'description' => 'Cambio preventivo antes de minuto 1'],
            ['action' => 'Gol sin tiro previo', 'category' => 'FUNNY', 'probability' => 0.02, 'description' => 'Gol de rebote sin tiro definido'],
            ['action' => 'Árbitro pierde tarjeta/silbato', 'category' => 'FUNNY', 'probability' => 0.01, 'description' => 'Árbitro tiene problemas equipamiento'],
            ['action' => 'Comunicación VAR audible en TV', 'category' => 'FUNNY', 'probability' => 0.02, 'description' => 'Micrófono abierto revela conversación VAR'],
            ['action' => '10+ cambios totales', 'category' => 'DEFENSE', 'probability' => 0.08, 'description' => 'Ambos equipos hacen muchos cambios'],
            ['action' => 'Gol tras perder balón insano', 'category' => 'FUNNY', 'probability' => 0.04, 'description' => 'Equipo pierde balón, recupera y anota gol inmediato'],
            ['action' => 'Gol de defensor en contraataque', 'category' => 'DEFENSE', 'probability' => 0.05, 'description' => 'Defensor anota en contraataque rápido'],
            ['action' => 'Remate bloqueado 3+ veces', 'category' => 'DEFENSE', 'probability' => 0.07, 'description' => 'Un tiro es bloqueado múltiples veces'],
            ['action' => 'Disparo al poste 2+ veces', 'category' => 'RARE', 'probability' => 0.04, 'description' => 'Balon choca poste más de una vez'],
            ['action' => 'Gol decididor en prórroga', 'category' => 'RARE', 'probability' => 0.03, 'description' => 'Partido va a prórroga y alguien anota'],
        ];

        foreach ($actions as $action) {
            ActionTemplate::firstOrCreate(
                ['action' => $action['action']],
                $action
            );
        }

        $this->command->info('✅ ' . count($actions) . ' plantillas de acciones creadas exitosamente');
    }
}

