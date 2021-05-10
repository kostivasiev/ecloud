<?php

namespace Tests\unit\Jobs\Instance\Deploy;

use App\Events\V2\Task\Created;
use App\Jobs\Instance\Deploy\PrepareOsDisk;
use App\Jobs\Network\Deploy;
use App\Jobs\Network\DeployDiscoveryProfile;
use App\Models\V2\Router;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class PrepareOSDiskTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSetsVolumeName()
    {
        $this->kingpinServiceMock()->expects('get')
            ->withArgs(['/api/v2/vpc/' . $this->instance()->vpc->id . '/instance/' . $this->instance()->id])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'volumes' => [
                        [
                            'uuid' => 'd64169c6-4c40-4008-916c-8be822d8cc2d',
                        ],
                    ]
                ]));
            });

        $this->kingpinServiceMock()->expects('put')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->instance()->vpc->id . '/volume/d64169c6-4c40-4008-916c-8be822d8cc2d/resourceid')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'volumes' => [
                        [
                            'uuid' => 'd64169c6-4c40-4008-916c-8be822d8cc2d',
                        ],
                    ]
                ]));
            });

        $this->kingpinServiceMock()->expects('put')
            ->withArgs(['/api/v2/vpc/' . $this->instance()->vpc->id . '/instance/' . $this->instance()->id . '/volume/d64169c6-4c40-4008-916c-8be822d8cc2d/iops',
                [
                    'json' => [
                        'limit' => 300,
                    ],
                ]
            ]);

        $this->kingpinServiceMock()->expects('put')
            ->withArgs(['/api/v2/vpc/' . $this->instance()->vpc->id . '/instance/' . $this->instance()->id . '/volume/d64169c6-4c40-4008-916c-8be822d8cc2d/size',
                [
                    'json' => [
                        'sizeGiB' => 20,
                    ],
                ]
            ]);

        Event::fake([Created::class]);

        dispatch(new PrepareOsDisk($this->instance()));

        $this->assertEquals(1, $this->vpc()->volumes()->count());
        $this->assertEquals("i-test - Test Appliance", $this->vpc()->volumes[0]->name);
    }
}
