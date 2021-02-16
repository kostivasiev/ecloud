<?php

namespace Tests\V2\Volume;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Credential;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use App\Providers\EncryptionServiceProvider;
use App\Services\V2\KingpinService;
use App\Services\V2\NsxService;
use Faker\Factory as Faker;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    protected $region;
    protected $vpc;
    protected $volume;
    protected $availability_zone;
    protected $instance;

    public function setUp(): void
    {
        parent::setUp();

        $mockEncryptionServiceProvider = \Mockery::mock(EncryptionServiceProvider::class)
            ->shouldAllowMockingProtectedMethods();
        app()->bind('encrypter', function () use ($mockEncryptionServiceProvider) {
            $mockEncryptionServiceProvider->shouldReceive('encrypt')->andReturn('EnCrYpTeD-pAsSwOrD');
            $mockEncryptionServiceProvider->shouldReceive('decrypt')->andReturn('somepassword');
            return $mockEncryptionServiceProvider;
        });

        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id,
        ]);
        factory(Credential::class)->create([
            'username' => 'kingpinapi',
            'resource_id' => $this->availability_zone->id,
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->id,
        ]);
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->id,
        ]);
        $this->volume = factory(Volume::class)->create([
            'vpc_id' => $this->vpc->id,
            'availability_zone_id' => $this->availability_zone->id,
        ]);
        $this->volume->setSyncCompleted();

        $kingpinService = app()->makeWith(KingpinService::class, [$this->availability_zone]);
        $mockKingpinService = \Mockery::mock($kingpinService)->makePartial();
        app()->bind(KingpinService::class, function () use ($mockKingpinService) {
            $mockKingpinService->shouldReceive('delete')
                ->andReturnUsing(function () {
                    return new Response(204, [], '');
                });
            return $mockKingpinService;
        });
    }

    public function testFailedDeleteDueToAssignedInstance()
    {
        $this->instance->volumes()->attach($this->volume);
        $this->delete('/v2/volumes/' . $this->volume->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ]);
        $this->assertNull($this->volume->deleted_at);
    }

    public function testSuccessfulDelete()
    {
        $this->assertNull($this->volume->deleted_at);
        $this->delete('/v2/volumes/' . $this->volume->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(204);
        $this->volume->refresh();
        $this->assertNotNull($this->volume->deleted_at);
    }
}
