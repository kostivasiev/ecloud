<?php

namespace App\Console\Commands\Credentials;

use App\Models\V2\Credential;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\Table;

class Show extends Command
{
    protected $signature = 'credentials:show {resource_id}';

    protected $description = 'Show credentials for a resource';

    public function handle()
    {
        $rows = collect();
        Credential::where('resource_id', '=', $this->argument('resource_id'))->each(function ($credential) use ($rows) {
            $rows[] = $credential->toArray();
        });

        if (count($rows)) {
            (new Table($this->output))->setHeaders(array_keys($rows[0]))
                ->setRows($rows->toArray())
                ->render();
        } else {
            $this->info('No credentials found');
        }

        return Command::SUCCESS;
    }
}
