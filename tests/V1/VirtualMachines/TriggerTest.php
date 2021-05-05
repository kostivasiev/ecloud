<?php

namespace Tests\V1\VirtualMachines;

use App\Models\V1\Trigger;
use App\Models\V1\VirtualMachine;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\V1\TestCase;

class TriggerTest extends TestCase
{
    public function testPublicContractTriggerLoading()
    {
        $serverId = 123;
        $resellerId = 321;

        $server = factory(VirtualMachine::class, 1)->create([
            'servers_id' => $serverId,
            'servers_reseller_id' => $resellerId,
        ])->first();

        // trigger wording can be in different formats depending on where its created from
        $descriptionFormats = [
            'eCloud VM #' . $serverId . ': RAM: 4GB',
            'eCloud VM #' . $serverId . ': RAM: 4GB - PG12345',
            '1 X RAM: 4GB - PG12345'
        ];

        foreach ($descriptionFormats as $description) {
            factory(Trigger::class, 1)->create([
                'trigger_reference_id' => $serverId,
                'trigger_reseller_id' => $resellerId,
                'trigger_description' => $description,
            ]);

            $trigger = $server->trigger('RAM');
            $this->assertEquals($description, $trigger->trigger_description);

            // remove for next run
            $trigger->delete();
        }
    }
}
