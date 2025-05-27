<?php

namespace Tests\Traits;

use Illuminate\Foundation\Testing\RefreshDatabase as BaseRefreshDatabase;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

trait RefreshDatabase
{
    use BaseRefreshDatabase;

    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function refreshTestDatabase()
    {
        $this->artisan('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
            '--env' => 'testing'
        ]);

        $this->app[Kernel::class]->setArtisan(null);
    }

    /**
     * Define a custom database connection for testing.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        $this->artisan('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
            '--env' => 'testing'
        ]);
    }
}
