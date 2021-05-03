<?php

namespace Tests\Traits;

use Laravel\Lumen\Testing\DatabaseMigrations;

trait ResellerDatabaseMigrations
{
    use DatabaseMigrations { runDatabaseMigrations as parentRunDatabaseMigrations; }

    public function runDatabaseMigrations()
    {
        $this->parentRunDatabaseMigrations();

        $path = 'database/migrations/reseller';

        $result = $this->artisan('migrate:fresh', ['--path' => $path]);

        $this->beforeApplicationDestroyed(function () use ($path) {
            $this->artisan('migrate:rollback', ['--path' => $path]);
        });
    }
}