<?php
namespace Tests\unit\Jobs\OrchestratorBuild;

use App\Events\V2\Task\Created;
use App\Jobs\OrchestratorBuild\CreateInstances;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateInstancesTest extends TestCase
{
    protected OrchestratorConfig $orchestratorConfig;

    protected OrchestratorBuild $orchestratorBuild;

    public function setUp(): void
    {
        parent::setUp();
        $this->orchestratorConfig = factory(OrchestratorConfig::class)->create([
            'data' => json_encode([
                'instances' => [
                    [
                        "name" => "Builder Test Instance",
                        "vpc_id" => "{vpc.0}",
                        "image_id" => $this->image()->id,
                        "vcpu_cores" => 1,
                        "ram_capacity" => 1024,
                        "locked" => false,
                        "backup_enabled" => false,
                        "network_id" => "{network.0}",
                        "requires_floating_ip" => true,
                        "volume_capacity" => 30,
                        "volume_iops" => 300,
                        "ssh_key_pair_ids" => [
                            "ssh-aaaaaaaa"
                        ],
                        'image_data' => [
                            "mysql_root_password" => "EnCrYpTeD-PaSsWoRd",
                            "mysql_wordpress_user_password" => "EnCrYpTeD-PaSsWoRd",
                            "wordpress_url" => "mydomain.com"
                        ]
                    ]
                ]
            ])
        ]);

        $this->orchestratorBuild = factory(OrchestratorBuild::class)->make();
        $this->orchestratorBuild->orchestratorConfig()->associate($this->orchestratorConfig);
        $this->orchestratorBuild->save();

        $this->orchestratorBuild->updateState('vpc', 0, $this->vpc()->id);
        $this->orchestratorBuild->updateState('network', 0, $this->network()->id);
    }

    public function testNoInstanceDataSkips()
    {
        $this->orchestratorConfig->data = null;
        $this->orchestratorConfig->save();

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new CreateInstances($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testResourceAlreadyExistsSkips()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->orchestratorBuild->updateState('instance', 0, $this->instance()->id);

        dispatch(new CreateInstances($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->orchestratorBuild->refresh();

        $this->assertNotNull($this->orchestratorBuild->state['instance']);

        $this->assertEquals(1, count($this->orchestratorBuild->state['instance']));
    }

    public function testSuccess()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        dispatch(new CreateInstances($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertDispatched(Created::class);

        $this->orchestratorBuild->refresh();

        $this->assertNotNull($this->orchestratorBuild->state['instance']);

        $this->assertEquals(1, count($this->orchestratorBuild->state['instance']));
    }
}