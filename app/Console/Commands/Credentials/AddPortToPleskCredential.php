<?php

namespace App\Console\Commands\Credentials;

use App\Models\V2\Credential;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\Table;

class AddPortToPleskCredential extends Command
{
    protected $signature = 'credentials:update-plesk {--T|test-run}';

    protected $description = 'Updates plesk credentials to include port';

    public function handle()
    {
        $this->info('Adding port to plesk_admin_password credentials' . PHP_EOL);
        Credential::where(function ($query) {
            $query->where('username', '=', 'plesk_admin_password');
            $query->whereNull('port');
        })->each(function (Credential $credential) {
            $this->line('Adding port to ' . $credential->id);
            if (!$this->option('test-run')) {
                $credential->setAttribute('port', config('plesk.admin.port', 8880))->saveQuietly();
            }
        });

        return Command::SUCCESS;
    }
}
