<?php
namespace Tests\V2\Console\Commands\LogicMonitor;

use App\Console\Commands\FloatingIp\PopulateAvailabilityZoneId;
use App\Jobs\NetworkPolicy\AllowLogicMonitor;
use App\Jobs\Router\CreateCollectorRules;
use App\Jobs\Router\CreateSystemPolicy;
use App\Models\V2\Network;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Illuminate\Console\Command;
use Queue;
use Tests\TestCase;

class RegisterExistingInstancesWithLogicMonitorTest extends TestCase
{
    public function testCommandDispatchesJobsSuccess()
    {
        //prep
        //make router with network
        Vpc::factory()->create(['id'=> 'vpc-a7d7c4e6']);
        Router::factory()->create(['id' => 'rtr-62827a58']);
        Network::factory()->create();

        /** @var Network $network */
        $network = Network::all()->first();
        NetworkPolicy::factory()->create(['network_id' => $network->id]);

        // Fake the queue
        Queue::fake();

        $this->artisan('lm:register-all-instances')
            ->assertExitCode(Command::SUCCESS);

        // Assert the job was pushed to the queue
        Queue::assertPushed(CreateSystemPolicy::class);
        Queue::assertPushed(AllowLogicMonitor::class);
        Queue::assertPushed(CreateCollectorRules::class);
    }
}