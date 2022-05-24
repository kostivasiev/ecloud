<?php

namespace Tests\Unit\Console\Commands\FloatingIp;

use App\Events\V2\Task\Created;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MigratePolymorphicRelationshipToPivotTest extends TestCase
{
    public function testSuccess()
    {
        Event::fake([Created::class]);

        $this->floatingIp();
        $this->floatingIp()->resource()->associate($this->ip());


        $pendingCommand = $this->artisan('floating-ip:migrate-polymorphic-relationship');
        $pendingCommand->assertSuccessful();



    }


}
