<?php
namespace Tests\unit\Jobs\Vpc;

use App\Jobs\Vpc\RemoveVPCFolder;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RemoveVpcFolderTest extends TestCase
{
    public function testRemoveVPCJobIsDispatched()
    {
        $vpc = factory(Vpc::class)->create([
            'id' => 'vpc-1',
            'region_id' => $this->region()->id
        ]);

        $this->kingpinServiceMock()
            ->expects('delete')
            ->once()
            ->withSomeOfArgs('/api/v2/vpc/vpc-1');

        Event::fake([JobFailed::class, JobProcessed::class]);
        dispatch(new RemoveVPCFolder($vpc));
        Event::assertNotDispatched(JobFailed::class);
    }
}
