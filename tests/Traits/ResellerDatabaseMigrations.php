<?php

namespace Tests\Traits;

use Laravel\Lumen\Testing\DatabaseMigrations;

trait ResellerDatabaseMigrations
{
    use DatabaseMigrations { runDatabaseMigrations as parentRunDatabaseMigrations; }

    public function runDatabaseMigrations()
    {
        // We need to run parent migrations first, as doing this afterwards will drop tables from default connection/database (reseller),
        // undoing the migrations reseller migrations below. #871 raised to address default connection. Once this has been addressed, the parent migrations
        // will need to be ran after the reseller migrations (move `parentRunDatabaseMigrations()` function call after below `artisan('migrate:fresh')` call)
        $this->parentRunDatabaseMigrations();

        $path = 'database/migrations/reseller';

        $this->artisan('migrate:fresh', ['--path' => $path]);

        $this->beforeApplicationDestroyed(function () use ($path) {
            $this->artisan('migrate:rollback', ['--path' => $path]);
        });
    }
}