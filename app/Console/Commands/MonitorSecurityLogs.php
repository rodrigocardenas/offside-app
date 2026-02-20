<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class MonitorSecurityLogs extends Command
{
    protected $signature = 'security:monitor {--clear : Limpiar alertas previas}';
    protected $description = 'Monitor de actividad sospechosa en tiempo real desde los logs de seguridad';

    private $alertCount = 0;
    private $criticalCount = 0;
    private $lastLine = 0;

    public function handle()
    {
        $logPath = storage_path('logs/security.log');

        $this->info('🔒 Monitor de Seguridad Iniciado');
        $this->info("📁 Archivo: {$logPath}");
        $this->newLine();

        if (!File::exists($logPath)) {
            $this->warn('⚠️  Archivo de logs de seguridad no encontrado. Esperando eventos...');
        }

        // Limpiar alertas si se solicita
        if ($this->option('clear')) {
            File::put($logPath, '');
            $this->info('✓ Logs de seguridad limpiados');
            $this->newLine();
        }

        // Monitoreo continuo
        while (true) {
            if (File::exists($logPath)) {
                $this->checkNewEntries($logPath);
            }

            sleep(2); // Verificar cada 2 segundos
        }
    }

    private function checkNewEntries($logPath)
    {
        $lines = File::lines($logPath)->toArray();
        $newLines = array_slice($lines, $this->lastLine);

        foreach ($newLines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            // Parsear y mostrar entrada
            $this->processLogEntry($line);
            $this->lastLine++;
        }
    }

    private function processLogEntry($line)
    {
        // Detectar por palabras clave en el log
        if (strpos($line, 'CRITICAL') !== false) {
            $this->error('🚨 CRITICAL: ' . $this->extractMessage($line));
            $this->criticalCount++;
            $this->alert($line);
        } elseif (strpos($line, 'alert') !== false || strpos($line, 'warning') !== false) {
            $this->warn('⚠️  ALERT: ' . $this->extractMessage($line));
            $this->alertCount++;
        } else {
            $this->line('ℹ️  INFO: ' . $this->extractMessage($line));
        }
    }

    private function extractMessage($line)
    {
        // Extraer el mensaje principal del log (después del timestamp y nivel)
        if (preg_match('/\[[\d\-\s:]+\].*?\s(.*?)$/', $line, $matches)) {
            return $matches[1];
        }

        return substr($line, 0, 100);
    }

    private function alert($message)
    {
        // Aquí se puede enviar email, Slack, etc.
        // Por ahora solo lo registramos
        Log::channel('security')->critical('ALERT DETECTED: ' . $message);

        // Mostrar resumen
        $this->newLine();
        $this->line('═══════════════════════════════════════════');
        $this->error('📊 Resumen de Alertas');
        $this->line('─────────────────────────────────────────────');
        $this->line("Total de alertas: {$this->alertCount}");
        $this->error("Alertas CRÍTICAS: {$this->criticalCount}");
        $this->line('═══════════════════════════════════════════');
        $this->newLine();
    }
}
