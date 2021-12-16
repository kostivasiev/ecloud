<?php

namespace App\Console\Commands\Credentials;

use App\Models\V2\Credential;
use Illuminate\Console\Command;

class UpdatePleskCredentials extends Command
{
    protected $signature = 'credentials:update-plesk {--D|debug} {--T|test-run}';
    protected $description = 'Show credentials for a resource';



    public function handle()
    {
        $this->info('Updating Plesk Admin Credentials - Started');
        Credential::where('name', '=', 'plesk_admin_password')->each(function ($credential) {
            if ($this->option('debug')) {
                $this->info(
                    vsprintf(
                        'Updating name and username for %s',
                        [
                            $credential->id,
                        ]
                    )
                );
            }
            if (!$this->option('test-run')) {
                $credential->setAttribute('name', 'Plesk Administrator')
                    ->setAttribute('username', 'admin')
                    ->saveQuietly();
            }
        });
        $this->info('Updating Plesk Admin Credentials - Finished');
    }
}
