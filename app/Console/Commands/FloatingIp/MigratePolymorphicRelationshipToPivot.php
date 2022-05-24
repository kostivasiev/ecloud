<?php

namespace App\Console\Commands\FloatingIp;

use App\Console\Commands\Command;
use App\Models\V2\FloatingIp;

class MigratePolymorphicRelationshipToPivot extends Command
{
    protected $signature = 'floating-ip:migrate-polymorphic-relationship';

    protected $description = 'Migrates the polymorphic relationship from existing fips to the pivot table floating_ip_resource';

    public function handle()
    {

        FloatingIp::all()->each(function ($floatingIp) {

        });


        return 0;
    }
}
