<?php

namespace Tests\unit\Jobs\Nsx\Dhcp;

use App\Jobs\Nsx\Dhcp\Undeploy;
use App\Jobs\Nsx\Dhcp\UndeployCheck;
use App\Models\V2\Dhcp;
use App\Models\V2\Volume;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UndeployCheckTest extends TestCase
{
    use DatabaseMigrations;

    protected $dhcp;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSucceeds()
    {
        Model::withoutEvents(function() {
            $this->dhcp = factory(Dhcp::class)->create([
                'id' => 'dhcp-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);
        });

        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/policy/api/v1/infra/dhcp-server-configs/?include_mark_for_delete_objects=true')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [],
                ]));
            });

        dispatch(new UndeployCheck($this->dhcp));

        $this->assertTrue(true);
    }
}
