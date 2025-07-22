<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ConfigureTimeouts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:configure-timeouts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configura timeouts para evitar problemas de timeout en jobs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Configurando timeouts para Laravel...');

        // 1. Configurar Horizon (si existe)
        $this->configureHorizon();

        // 2. Configurar Queue
        $this->configureQueue();

        // 3. Crear archivo .user.ini para PHP
        $this->createUserIni();

        $this->info('Configuración de timeouts completada.');
        $this->info('Recuerda reiniciar tu servidor web y workers de cola.');
    }

    private function configureHorizon()
    {
        $horizonConfigPath = config_path('horizon.php');

        if (File::exists($horizonConfigPath)) {
            $this->info('Configurando Horizon...');

            $content = File::get($horizonConfigPath);

            // Buscar y reemplazar configuración de timeout
            $patterns = [
                '/\'timeout\' => \d+,/' => "'timeout' => 300,",
                '/\'retry_after\' => \d+,/' => "'retry_after' => 300,"
            ];

            foreach ($patterns as $pattern => $replacement) {
                if (preg_match($pattern, $content)) {
                    $content = preg_replace($pattern, $replacement, $content);
                } else {
                    // Si no existe, agregar después de 'defaults'
                    $content = str_replace(
                        "'defaults' => [",
                        "'defaults' => [\n            'timeout' => 300,\n            'retry_after' => 300,",
                        $content
                    );
                }
            }

            File::put($horizonConfigPath, $content);
            $this->info('Horizon configurado con timeout de 300 segundos.');
        } else {
            $this->warn('Archivo de configuración de Horizon no encontrado.');
        }
    }

    private function configureQueue()
    {
        $queueConfigPath = config_path('queue.php');

        if (File::exists($queueConfigPath)) {
            $this->info('Configurando Queue...');

            $content = File::get($queueConfigPath);

            // Buscar y reemplazar configuración de timeout
            $patterns = [
                '/\'retry_after\' => \d+,/' => "'retry_after' => 300,"
            ];

            foreach ($patterns as $pattern => $replacement) {
                if (preg_match($pattern, $content)) {
                    $content = preg_replace($pattern, $replacement, $content);
                }
            }

            File::put($queueConfigPath, $content);
            $this->info('Queue configurado con retry_after de 300 segundos.');
        }
    }

    private function createUserIni()
    {
        $userIniPath = base_path('.user.ini');

        $content = "; Configuración de PHP para evitar timeouts\n";
        $content .= "max_execution_time = 300\n";
        $content .= "max_input_time = 300\n";
        $content .= "memory_limit = 512M\n";
        $content .= "default_socket_timeout = 300\n";

        File::put($userIniPath, $content);

        $this->info('Archivo .user.ini creado en el directorio raíz.');
    }
}
