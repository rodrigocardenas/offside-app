<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Obtener información de qué equipo pertenece a qué liga basado en competiciones
// Vamos a asumir que podemos identificar la liga por los equipos conocidos

$laLigaTeams = [
    'Elche', 'Sevilla FC', 'Levante UD', 'Rayo Vallecano de Madrid',
    'CA Osasuna', 'Valencia CF', 'RCD Espanyol de Barcelona', 'Villarreal CF',
    'Real Madrid', 'Club Atlético de Madrid', 'RCD Mallorca', 'FC Barcelona',
    'Real Oviedo', 'Real Sociedad de Fútbol', 'RC Celta de Vigo', 'Deportivo Alavés',
    'Real Betis Balompié', 'Girona FC', 'Getafe CF'
];

$premierLeagueTeams = [
    'Brighton & Hove Albion FC', 'AFC Bournemouth', 'West Ham United FC', 'Sunderland AFC',
    'Burnley FC', 'Tottenham Hotspur FC', 'Fulham', 'Manchester City', 'Wolverhampton Wanderers FC',
    'Liverpool', 'Crystal Palace', 'Chelsea', 'Brentford', 'Nottingham Forest FC',
    'Newcastle United FC', 'Aston Villa', 'Arsenal', 'Manchester United FC', 'Everton',
    'Leeds United FC'
];

$serieATeams = [
    'SS Lazio', 'Como 1907', 'AC Pisa 1909', 'Torino FC', 'ACF Fiorentina', 'Cagliari Calcio',
    'US Lecce', 'US Sassuolo Calcio', 'US Cremonese', 'Parma Calcio 1913', 'Genoa CFC',
    'Bologna FC 1909', 'AS Roma', 'AC Milan', 'Hellas Verona FC', 'Udinese Calcio'
];

$championsLeagueTeams = [
    'FK Kairat', 'Club Brugge KV', 'FK Bodø/Glimt', 'Borussia Dortmund', 'Sporting Clube de Portugal',
    'Paris Saint-Germain FC', 'PAE Olympiakos SFP', 'Bayer 04 Leverkusen', 'AFC Ajax', 'FC København',
    'SSC Napoli', 'AS Monaco FC', 'FC Internazionale Milano', 'Qarabağ Ağdam FK', 'Eintracht Frankfurt',
    'Galatasaray SK', 'Olympique de Marseille', 'SK Slavia Praha', 'Atalanta', 'Juventus',
    'Sport Lisboa e Benfica', 'PSV', 'FC Bayern München', 'Royale Union Saint-Gilloise', 'Paphos FC'
];

// Obtener api_names de la BD
$teams = DB::table('teams')
    ->whereNotNull('api_name')
    ->get(['name', 'api_name'])
    ->keyBy('name');

echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║              EQUIPOS POR LIGA - FOOTBALL-DATA API                         ║\n";
echo "║                    (Nombres sincronizados de api_name)                    ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

// LA LIGA
echo "════════════════════════════════════════════════════════════════════════════\n";
echo "LA LIGA (España) - PD\n";
echo "════════════════════════════════════════════════════════════════════════════\n\n";

$laLigaNames = [];
foreach ($laLigaTeams as $dbName) {
    if (isset($teams[$dbName])) {
        $laLigaNames[] = $teams[$dbName]->api_name;
    }
}
sort($laLigaNames);
foreach ($laLigaNames as $name) {
    echo $name . "\n";
}
echo "\nTotal: " . count($laLigaNames) . " equipos\n\n\n";

// PREMIER LEAGUE
echo "════════════════════════════════════════════════════════════════════════════\n";
echo "PREMIER LEAGUE (Inglaterra) - PL\n";
echo "════════════════════════════════════════════════════════════════════════════\n\n";

$plNames = [];
foreach ($premierLeagueTeams as $dbName) {
    if (isset($teams[$dbName])) {
        $plNames[] = $teams[$dbName]->api_name;
    }
}
sort($plNames);
foreach ($plNames as $name) {
    echo $name . "\n";
}
echo "\nTotal: " . count($plNames) . " equipos\n\n\n";

// SERIE A
echo "════════════════════════════════════════════════════════════════════════════\n";
echo "SERIE A (Italia) - SA\n";
echo "════════════════════════════════════════════════════════════════════════════\n\n";

$saNames = [];
foreach ($serieATeams as $dbName) {
    if (isset($teams[$dbName])) {
        $saNames[] = $teams[$dbName]->api_name;
    }
}
sort($saNames);
foreach ($saNames as $name) {
    echo $name . "\n";
}
echo "\nTotal: " . count($saNames) . " equipos\n\n\n";

// CHAMPIONS LEAGUE
echo "════════════════════════════════════════════════════════════════════════════\n";
echo "CHAMPIONS LEAGUE (Europa) - CL\n";
echo "════════════════════════════════════════════════════════════════════════════\n\n";

$clNames = [];
foreach ($championsLeagueTeams as $dbName) {
    if (isset($teams[$dbName])) {
        $clNames[] = $teams[$dbName]->api_name;
    }
}
sort($clNames);
foreach ($clNames as $name) {
    echo $name . "\n";
}
echo "\nTotal: " . count($clNames) . " equipos\n\n\n";

echo "════════════════════════════════════════════════════════════════════════════\n";
$total = count($laLigaNames) + count($plNames) + count($saNames) + count($clNames);
echo "TOTAL GENERAL: $total equipos\n";
echo "════════════════════════════════════════════════════════════════════════════\n";
