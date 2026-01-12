<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class TestCompleteCycle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:cycle-complete {--advanced : Use the advanced version} {--users=1 : Number of test users} {--matches=2 : Number of matches} {--competitions=laliga : Competitions to use} {--templates=3 : Number of question templates} {--verbose : Show verbose output} {--dry-run : Simulate without making changes} {--clean : Clean previous test data}';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $description = 'Execute the complete test cycle of the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Complete Test Cycle...');

        // Determine which script to use
        $script = $this->option('advanced')
            ? 'scripts/test-complete-cycle-advanced.php'
            : 'scripts/test-complete-cycle.php';

        if ($this->option('advanced')) {
            $this->info('📋 Using advanced version with options');

            // Build command arguments
            $args = [];
            $args[] = '--users=' . $this->option('users');
            $args[] = '--matches=' . $this->option('matches');
            $args[] = '--competitions=' . $this->option('competitions');
            $args[] = '--templates=' . $this->option('templates');

            if ($this->option('verbose')) {
                $args[] = '--verbose';
            }
            if ($this->option('dry-run')) {
                $args[] = '--dry-run';
            }
            if ($this->option('clean')) {
                $args[] = '--clean';
            }

            $this->executeAdvanced($script, $args);
        } else {
            $this->executeBasic($script);
        }

        $this->info('✅ Test cycle completed successfully!');
        $this->info('📄 Check storage/logs/ for detailed reports');
    }

    protected function executeBasic($script)
    {
        $process = new Process(['php', $script]);
        $process->setWorkingDirectory(base_path());
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error('❌ Test cycle failed!');
            $this->error($process->getErrorOutput());
            exit(1);
        }
    }

    protected function executeAdvanced($script, $args)
    {
        $command = ['php', $script, ...$args];
        $process = new Process($command);
        $process->setWorkingDirectory(base_path());
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error('❌ Test cycle failed!');
            $this->error($process->getErrorOutput());
            exit(1);
        }
    }
}
