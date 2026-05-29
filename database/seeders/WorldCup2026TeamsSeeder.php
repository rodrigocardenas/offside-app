<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * WorldCup2026TeamsSeeder
 *
 * Inserta los 48 equipos del Mundial 2026 y actualiza los que ya existen.
 *
 * ─ api_name : nombre EXACTO almacenado en football_matches.home_team / away_team.
 * ─ crest_url: descarga banderas de flagcdn.com y las guarda en
 *              storage/app/public/logos/{TLA}.png
 *              → accesibles en /storage/logos/{TLA}.png (requiere php artisan storage:link)
 *
 *   Si prefieres las URLs remotas sin descargar, cambia DOWNLOAD_LOGOS = false.
 *
 * Uso:
 *   php artisan db:seed --class=WorldCup2026TeamsSeeder
 */
class WorldCup2026TeamsSeeder extends Seeder
{
    /**
     * Si es true, descarga las 48 banderas a storage/app/public/logos/{TLA}.png
     * y usa la URL local /storage/logos/{TLA}.png como crest_url.
     *
     * Si es false, usa directamente la URL de flagcdn.com (sin descarga).
     * Requiere `php artisan storage:link` para que /storage/ sea accesible.
     */
    private const DOWNLOAD_LOGOS = true;

    /**
     * Los 48 equipos del Mundial 2026 con los api_name exactos de football_matches.
     * Fuente de banderas: flagcdn.com (CDN gratuito, sin rate-limit para lectura).
     *
     * iso2: código ISO 3166-1 alpha-2 para construir la URL de flagcdn.com.
     */
    private array $wc2026Teams = [

        // ─── CONMEBOL (6) ────────────────────────────────────────────────
        ['name' => 'Argentina',           'api_name' => 'Argentina',         'tla' => 'ARG', 'iso2' => 'ar', 'country' => 'Argentina',          'confederation' => 'CONMEBOL'],
        ['name' => 'Brasil',              'api_name' => 'Brazil',            'tla' => 'BRA', 'iso2' => 'br', 'country' => 'Brazil',              'confederation' => 'CONMEBOL'],
        ['name' => 'Colombia',            'api_name' => 'Colombia',          'tla' => 'COL', 'iso2' => 'co', 'country' => 'Colombia',            'confederation' => 'CONMEBOL'],
        ['name' => 'Uruguay',             'api_name' => 'Uruguay',           'tla' => 'URU', 'iso2' => 'uy', 'country' => 'Uruguay',             'confederation' => 'CONMEBOL'],
        ['name' => 'Ecuador',             'api_name' => 'Ecuador',           'tla' => 'ECU', 'iso2' => 'ec', 'country' => 'Ecuador',             'confederation' => 'CONMEBOL'],
        ['name' => 'Paraguay',            'api_name' => 'Paraguay',          'tla' => 'PAR', 'iso2' => 'py', 'country' => 'Paraguay',            'confederation' => 'CONMEBOL'],

        // ─── CONCACAF (6: 3 anfitriones + 3 clasificados) ────────────────
        ['name' => 'Estados Unidos',      'api_name' => 'United States',     'tla' => 'USA', 'iso2' => 'us', 'country' => 'United States',       'confederation' => 'CONCACAF'],
        ['name' => 'México',              'api_name' => 'Mexico',            'tla' => 'MEX', 'iso2' => 'mx', 'country' => 'Mexico',              'confederation' => 'CONCACAF'],
        ['name' => 'Canadá',              'api_name' => 'Canada',            'tla' => 'CAN', 'iso2' => 'ca', 'country' => 'Canada',              'confederation' => 'CONCACAF'],
        ['name' => 'Panamá',              'api_name' => 'Panama',            'tla' => 'PAN', 'iso2' => 'pa', 'country' => 'Panama',              'confederation' => 'CONCACAF'],
        ['name' => 'Curazao',             'api_name' => 'Curaçao',           'tla' => 'CUW', 'iso2' => 'cw', 'country' => 'Curaçao',             'confederation' => 'CONCACAF'],
        ['name' => 'Haití',               'api_name' => 'Haiti',             'tla' => 'HAI', 'iso2' => 'ht', 'country' => 'Haiti',               'confederation' => 'CONCACAF'],

        // ─── UEFA (16) ────────────────────────────────────────────────────
        ['name' => 'España',              'api_name' => 'Spain',             'tla' => 'ESP', 'iso2' => 'es', 'country' => 'Spain',               'confederation' => 'UEFA'],
        ['name' => 'Alemania',            'api_name' => 'Germany',           'tla' => 'GER', 'iso2' => 'de', 'country' => 'Germany',             'confederation' => 'UEFA'],
        ['name' => 'Francia',             'api_name' => 'France',            'tla' => 'FRA', 'iso2' => 'fr', 'country' => 'France',              'confederation' => 'UEFA'],
        ['name' => 'Inglaterra',          'api_name' => 'England',           'tla' => 'ENG', 'iso2' => 'gb-eng', 'country' => 'England',         'confederation' => 'UEFA'],
        ['name' => 'Portugal',            'api_name' => 'Portugal',          'tla' => 'POR', 'iso2' => 'pt', 'country' => 'Portugal',            'confederation' => 'UEFA'],
        ['name' => 'Países Bajos',        'api_name' => 'Netherlands',       'tla' => 'NED', 'iso2' => 'nl', 'country' => 'Netherlands',         'confederation' => 'UEFA'],
        ['name' => 'Bélgica',             'api_name' => 'Belgium',           'tla' => 'BEL', 'iso2' => 'be', 'country' => 'Belgium',             'confederation' => 'UEFA'],
        ['name' => 'Austria',             'api_name' => 'Austria',           'tla' => 'AUT', 'iso2' => 'at', 'country' => 'Austria',             'confederation' => 'UEFA'],
        ['name' => 'Croacia',             'api_name' => 'Croatia',           'tla' => 'CRO', 'iso2' => 'hr', 'country' => 'Croatia',             'confederation' => 'UEFA'],
        ['name' => 'Suiza',               'api_name' => 'Switzerland',       'tla' => 'SUI', 'iso2' => 'ch', 'country' => 'Switzerland',         'confederation' => 'UEFA'],
        ['name' => 'Escocia',             'api_name' => 'Scotland',          'tla' => 'SCO', 'iso2' => 'gb-sct', 'country' => 'Scotland',        'confederation' => 'UEFA'],
        ['name' => 'Turquía',             'api_name' => 'Turkey',            'tla' => 'TUR', 'iso2' => 'tr', 'country' => 'Turkey',              'confederation' => 'UEFA'],
        ['name' => 'Noruega',             'api_name' => 'Norway',            'tla' => 'NOR', 'iso2' => 'no', 'country' => 'Norway',              'confederation' => 'UEFA'],
        ['name' => 'Suecia',              'api_name' => 'Sweden',            'tla' => 'SWE', 'iso2' => 'se', 'country' => 'Sweden',              'confederation' => 'UEFA'],
        ['name' => 'Rep. Checa',          'api_name' => 'Czechia',           'tla' => 'CZE', 'iso2' => 'cz', 'country' => 'Czechia',             'confederation' => 'UEFA'],
        ['name' => 'Bosnia y Herzegovina','api_name' => 'Bosnia-Herzegovina','tla' => 'BIH', 'iso2' => 'ba', 'country' => 'Bosnia and Herzegovina','confederation' => 'UEFA'],

        // ─── CAF (9) ──────────────────────────────────────────────────────
        ['name' => 'Marruecos',           'api_name' => 'Morocco',           'tla' => 'MAR', 'iso2' => 'ma', 'country' => 'Morocco',             'confederation' => 'CAF'],
        ['name' => 'Egipto',              'api_name' => 'Egypt',             'tla' => 'EGY', 'iso2' => 'eg', 'country' => 'Egypt',               'confederation' => 'CAF'],
        ['name' => 'Senegal',             'api_name' => 'Senegal',           'tla' => 'SEN', 'iso2' => 'sn', 'country' => 'Senegal',             'confederation' => 'CAF'],
        ['name' => 'Costa de Marfil',     'api_name' => 'Ivory Coast',       'tla' => 'CIV', 'iso2' => 'ci', 'country' => "Côte d'Ivoire",       'confederation' => 'CAF'],
        ['name' => 'Sudáfrica',           'api_name' => 'South Africa',      'tla' => 'RSA', 'iso2' => 'za', 'country' => 'South Africa',        'confederation' => 'CAF'],
        ['name' => 'R.D. Congo',          'api_name' => 'Congo DR',          'tla' => 'COD', 'iso2' => 'cd', 'country' => 'DR Congo',            'confederation' => 'CAF'],
        ['name' => 'Argelia',             'api_name' => 'Algeria',           'tla' => 'ALG', 'iso2' => 'dz', 'country' => 'Algeria',             'confederation' => 'CAF'],
        ['name' => 'Túnez',               'api_name' => 'Tunisia',           'tla' => 'TUN', 'iso2' => 'tn', 'country' => 'Tunisia',             'confederation' => 'CAF'],
        ['name' => 'Cabo Verde',          'api_name' => 'Cape Verde Islands','tla' => 'CPV', 'iso2' => 'cv', 'country' => 'Cape Verde',          'confederation' => 'CAF'],

        // ─── AFC (8) ──────────────────────────────────────────────────────
        ['name' => 'Japón',               'api_name' => 'Japan',             'tla' => 'JPN', 'iso2' => 'jp', 'country' => 'Japan',               'confederation' => 'AFC'],
        ['name' => 'Corea del Sur',       'api_name' => 'South Korea',       'tla' => 'KOR', 'iso2' => 'kr', 'country' => 'Korea Republic',      'confederation' => 'AFC'],
        ['name' => 'Irán',                'api_name' => 'Iran',              'tla' => 'IRN', 'iso2' => 'ir', 'country' => 'Iran',                'confederation' => 'AFC'],
        ['name' => 'Arabia Saudita',      'api_name' => 'Saudi Arabia',      'tla' => 'KSA', 'iso2' => 'sa', 'country' => 'Saudi Arabia',        'confederation' => 'AFC'],
        ['name' => 'Australia',           'api_name' => 'Australia',         'tla' => 'AUS', 'iso2' => 'au', 'country' => 'Australia',           'confederation' => 'AFC'],
        ['name' => 'Uzbekistán',          'api_name' => 'Uzbekistan',        'tla' => 'UZB', 'iso2' => 'uz', 'country' => 'Uzbekistan',          'confederation' => 'AFC'],
        ['name' => 'Iraq',                'api_name' => 'Iraq',              'tla' => 'IRQ', 'iso2' => 'iq', 'country' => 'Iraq',                'confederation' => 'AFC'],
        ['name' => 'Jordania',            'api_name' => 'Jordan',            'tla' => 'JOR', 'iso2' => 'jo', 'country' => 'Jordan',              'confederation' => 'AFC'],

        // ─── OFC (1) ──────────────────────────────────────────────────────
        ['name' => 'Nueva Zelanda',       'api_name' => 'New Zealand',       'tla' => 'NZL', 'iso2' => 'nz', 'country' => 'New Zealand',         'confederation' => 'OFC'],

        // ─── Ghana (CAF) ─ detectado en football_matches (GROUP_L) ───────
        ['name' => 'Ghana',               'api_name' => 'Ghana',             'tla' => 'GHA', 'iso2' => 'gh', 'country' => 'Ghana',               'confederation' => 'CAF'],

        // ─── Catar (AFC) ─ en football_matches GROUP_B ───────────────────
        ['name' => 'Catar',               'api_name' => 'Qatar',             'tla' => 'QAT', 'iso2' => 'qa', 'country' => 'Qatar',               'confederation' => 'AFC'],
    ];

    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════╗');
        $this->command->info('║  WorldCup2026TeamsSeeder                     ║');
        $this->command->info('╚══════════════════════════════════════════════╝');

        // Prepara el directorio de logos si vamos a descargar
        if (self::DOWNLOAD_LOGOS) {
            Storage::disk('public')->makeDirectory('logos');
            $this->command->line('  📁 Directorio storage/app/public/logos listo');
        }

        $created = 0;
        $updated = 0;

        foreach ($this->wc2026Teams as $data) {
            $tla      = $data['tla'];
            $crestUrl = $this->resolveCrestUrl($data['tla'], $data['iso2']);

            // Busca primero por tla; si no, por external_id = TLA
            // (ej. España tiene external_id='ESP' y tla=NULL en la BD actual)
            $team = Team::where('type', 'national')
                ->where(function ($q) use ($tla) {
                    $q->where('tla', $tla)
                      ->orWhere(function ($q2) use ($tla) {
                          $q2->where('external_id', $tla)
                             ->where('external_id', '!=', '');
                      });
                })
                ->first();

            $payload = [
                'name'          => $data['name'],
                'api_name'      => $data['api_name'],
                'type'          => 'national',
                'short_name'    => $tla,
                'country'       => $data['country'],
                'confederation' => $data['confederation'],
                'crest_url'     => $crestUrl,
                'tla'           => $tla,
            ];

            if ($team) {
                $team->update($payload);
                $this->command->line("  ✏  Actualizado : {$data['name']} ({$tla})");
                $updated++;
            } else {
                $existsByExtId = Team::where('external_id', $tla)->exists();

                Team::create(array_merge($payload, [
                    'external_id' => $existsByExtId ? null : $tla,
                    'is_featured' => false,
                ]));

                $this->command->line("  ➕  Creado      : {$data['name']} ({$tla})");
                $created++;
            }
        }

        $this->command->newLine();
        $this->command->info("✅ Listo — Creados: {$created} | Actualizados: {$updated}");

        if (self::DOWNLOAD_LOGOS) {
            $this->command->newLine();
            $this->command->warn('Asegúrate de haber ejecutado: php artisan storage:link');
        }
    }

    /**
     * Devuelve la URL del escudo/bandera.
     * Si DOWNLOAD_LOGOS = true, descarga de flagcdn.com y retorna la ruta local.
     * Si DOWNLOAD_LOGOS = false, retorna la URL pública de flagcdn.com.
     *
     * flagcdn.com es un CDN gratuito respaldado por flagpedia.net.
     * Formato: https://flagcdn.com/w160/{iso2}.png  (160 px de ancho)
     */
    private function resolveCrestUrl(string $tla, string $iso2): string
    {
        $remoteUrl = "https://flagcdn.com/w160/{$iso2}.png";
        $localPath = "logos/{$tla}.png";

        if (! self::DOWNLOAD_LOGOS) {
            return $remoteUrl;
        }

        // Si ya fue descargada, reutiliza
        if (Storage::disk('public')->exists($localPath)) {
            return '/storage/' . $localPath;
        }

        try {
            $imageContent = file_get_contents($remoteUrl);

            if ($imageContent === false) {
                $this->command->warn("  ⚠  No se pudo descargar bandera para {$tla} — se usará URL remota");
                return $remoteUrl;
            }

            Storage::disk('public')->put($localPath, $imageContent);
            return '/storage/' . $localPath;
        } catch (\Throwable $e) {
            $this->command->warn("  ⚠  Error descargando {$tla}: {$e->getMessage()}");
            return $remoteUrl;
        }
    }
}
