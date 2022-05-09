<?php

namespace App\Console\Commands\Credentials;

use App\Models\V2\Credential;
use App\Console\Commands\Command;

class UpdatePleskCredentials extends Command
{
    protected $signature = 'credentials:update-plesk {--D|debug} {--T|test-run}';
    protected $description = 'Create user friendly Plesk administrator credentials for existing Plesk installations';

    public function handle()
    {
        Credential::where('username', 'plesk_admin_password')->each(function ($credential) {
            $this->info('Updating plesk administrator credentials [' . $credential->id . '] for instance ' . $credential->instance->id);

            if (!$this->option('test-run')) {
                $credential
                    ->setAttribute('name', 'Plesk Administrator')
                    ->setAttribute('username', 'admin')
                    ->setAttribute('port', config('plesk.admin.port', 8880))
                    ->setAttribute('is_hidden', false)
                    ->save();
            }
        });

        $this->info('Updating Plesk Admin Credentials - Finished');
        return Command::SUCCESS;
    }
}
