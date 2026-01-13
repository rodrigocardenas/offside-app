<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\FootballMatch;
use App\Models\Team;

class DownloadTeamLogos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:download-team-logos {--dry-run : Show what would be downloaded without actually downloading}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download team logos from teams that appear in football matches, store them locally, and update the database with local paths';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        // Obtener equipos con crest_url que sean URLs externas
        $teams = Team::whereNotNull('crest_url')
            ->where('crest_url', 'like', 'http%')
            ->get(['id', 'name', 'crest_url']);

        if ($teams->isEmpty()) {
            $this->error('No teams with crest_url found.');
            return;
        }

        $this->info("Found {$teams->count()} teams with crest URLs to process.");

        if ($dryRun) {
            $this->info('Dry run mode - showing teams and URLs that would be downloaded:');
            foreach ($teams as $team) {
                $this->line("{$team->name}: {$team->crest_url}");
            }
            return;
        }

        // Crear directorio si no existe
        $logosPath = 'logos';
        if (!Storage::disk('public')->exists($logosPath)) {
            Storage::disk('public')->makeDirectory($logosPath);
            $this->info("Created directory: storage/app/public/{$logosPath}");
        }

        $successCount = 0;
        $errorCount = 0;

        $progressBar = $this->output->createProgressBar($teams->count());
        $progressBar->start();

        foreach ($teams as $team) {
            try {
                // Descargar la imagen
                $response = Http::timeout(30)->get($team->crest_url);

                if ($response->successful()) {
                    // Generar nombre de archivo
                    $filename = $this->generateFilename($team->name, $team->crest_url);

                    // Guardar la imagen
                    Storage::disk('public')->put("{$logosPath}/{$filename}", $response->body());

                    // Actualizar el crest_url del equipo con el path local
                    $localPath = '/storage/logos/' . $filename;
                    DB::table('teams')->where('id', $team->id)->update(['crest_url' => $localPath]);

                    $successCount++;
                    Log::info("Downloaded and updated logo for {$team->name}: {$team->crest_url} -> {$localPath}");
                } else {
                    $this->error("Failed to download logo for {$team->name}: {$team->crest_url} (Status: {$response->status()})");
                    $errorCount++;
                }
            } catch (\Exception $e) {
                $this->error("Error downloading logo for {$team->name}: {$e->getMessage()}");
                $errorCount++;
                Log::error("Error downloading logo for {$team->name}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Download completed. Success: {$successCount}, Errors: {$errorCount}");

        if ($successCount > 0) {
            $this->info("Logos saved to: storage/app/public/{$logosPath}");
            $this->info("Make sure to run 'php artisan storage:link' if not already done.");
        }
    }

    /**
     * Generate a filename from the team name and URL
     */
    private function generateFilename(string $teamName, string $url): string
    {
        // Sanitizar el nombre del equipo
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $teamName);

        // Extraer extensión de la URL
        $path = parse_url($url, PHP_URL_PATH);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        // Si no tiene extensión, usar png por defecto
        if (!$extension) {
            $extension = 'png';
        }

        return $safeName . '.' . $extension;
    }
}
