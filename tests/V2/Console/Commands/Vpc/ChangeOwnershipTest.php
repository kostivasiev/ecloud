<?php
namespace Tests\V2\Console\Commands\Vpc;

use App\Models\V2\BillingMetric;
use App\Models\V2\Vpc;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Tests\TestCase;

class ChangeOwnershipTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testCanChangeOwnershipOfVPC()
    {
        $currentResellerId = 1374;
        $newResellerId = 1337;
        $vpc = Vpc::factory()->create(['reseller_id' => $currentResellerId]);

        BillingMetric::factory(10)->create([
            'vpc_id' => $vpc->id,
            'start' => Carbon::now()->endOfDay()->subMonth(),
            'end' => null,
            'reseller_id' => $currentResellerId
        ]);

        //check old metrics finished, new metrics started and vpc ownership changed
        $this->assertEquals(BillingMetric::where('vpc_id', $vpc->id)->count(), 10);

        $this->artisan(sprintf('vpc:change-ownership --vpc=%s --reseller=%s', $vpc->id, $newResellerId))
            ->assertExitCode(Command::SUCCESS);

        $this->assertEquals(BillingMetric::where('vpc_id', $vpc->id)->count(), 20);
        $this->assertEquals(BillingMetric::where('vpc_id', $vpc->id)->whereNull('end')->count(), 10);
        $this->assertEquals(Vpc::where('id', $vpc->id)->first()->reseller_id, $newResellerId);
    }

    public function testFailsChangeOwnershipOfVPC()
    {
        $currentResellerId = 1374;
        $newResellerId = 1337;
        $vpc = Vpc::factory()->create(['reseller_id' => $currentResellerId]);

        BillingMetric::factory(10)->create([
            'vpc_id' => $vpc->id,
            'start' => Carbon::now()->endOfDay()->subMonth(),
            'end' => null,
            'reseller_id' => $currentResellerId
        ]);

        //check old metrics finished, new metrics started and vpc ownership changed
        $this->assertEquals(BillingMetric::where('vpc_id', $vpc->id)->count(), 10);

        $this->artisan(sprintf('vpc:change-ownership --vpc=%s --reseller=%s --date=%s', $vpc->id, $newResellerId, 'INVALID_DATE'))
            ->assertExitCode(Command::FAILURE);

        //check old metrics finished, new metrics started and vpc ownership changed
        $this->assertEquals(BillingMetric::where('vpc_id', $vpc->id)->count(), 10);
    }
}
