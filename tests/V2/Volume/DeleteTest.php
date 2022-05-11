<?php
namespace Tests\V2\Volume;

use App\Events\V2\Task\Created;
use App\Models\V2\Volume;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VolumeGroupMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    use VolumeGroupMock;

    protected Volume $volume;

    public function setUp(): void
    {
        parent::setUp();

        Volume::withoutEvents(function () {
            $this->volume = Volume::factory()->create([
                'id' => 'vol-test',
                'name' => 'Volume',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'uuid-test-uuid-test-uuid-test',
                'volume_group_id' => $this->volumeGroup()->id,
            ]);
        });
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    /** @test */
    public function itDoesNotDeleteAVolumeGroupMember()
    {
        $this->delete('/v2/volumes/'.$this->volume->id)
            ->assertJsonFragment(
                [
                    'title' => 'Forbidden',
                    'detail' => 'Volumes that are members of a volume group cannot be deleted',
                    'status' => 403,
                ]
            )->assertStatus(403);
    }

    /** @test */
    public function itDeletesAStandaloneVolume()
    {
        Event::fake(Created::class);

        $this->volume->volume_group_id = null;
        $this->volume->saveQuietly();

        $this->delete('/v2/volumes/'.$this->volume->id)
            ->assertStatus(202);

        Event::assertDispatched(Created::class);
    }
}