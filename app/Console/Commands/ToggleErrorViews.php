<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ToggleErrorViews extends Command
{
    protected $signature = 'errors:toggle {--enable : Enable custom error views} {--disable : Disable custom error views}';
    protected $description = 'Toggle custom error views for debugging';

    public function handle()
    {
        $errorFiles = ['404', '403', '419', '500', '503'];
        $viewsPath = resource_path('views/errors');

        if ($this->option('disable')) {
            foreach ($errorFiles as $error) {
                $file = "$viewsPath/$error.blade.php";
                $backup = "$viewsPath/$error.blade.php.bak";

                if (File::exists($file)) {
                    File::move($file, $backup);
                    $this->info("Disabled: $error.blade.php → $error.blade.php.bak");
                }
            }
            $this->info('✅ Custom error views disabled. You will now see detailed exceptions.');
            return 0;
        }

        if ($this->option('enable')) {
            foreach ($errorFiles as $error) {
                $backup = "$viewsPath/$error.blade.php.bak";
                $file = "$viewsPath/$error.blade.php";

                if (File::exists($backup)) {
                    File::move($backup, $file);
                    $this->info("Enabled: $error.blade.php.bak → $error.blade.php");
                }
            }
            $this->info('✅ Custom error views enabled.');
            return 0;
        }

        $this->error('Please specify --enable or --disable');
        return 1;
    }
}
