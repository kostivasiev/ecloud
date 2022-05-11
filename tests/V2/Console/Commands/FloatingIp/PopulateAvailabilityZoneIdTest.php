<?php
namespace Tests\V2\Console\Commands\FloatingIp;

use App\Console\Commands\FloatingIp\PopulateAvailabilityZoneId;
use Illuminate\Console\Command;
use Tests\TestCase;

class PopulateAvailabilityZoneIdTest extends TestCase
{
    protected array $infoArgument;
    protected $infoArgumentItem;

    public function testSuccess()
    {
        $this->floatingIp()->availability_zone_id = null;
        $this->floatingIp()->save();

        $this->assertNull($this->floatingIp()->availability_zone_id);

        $this->artisan('floating-ip:populate-availability-zone-id')
            ->assertExitCode(Command::SUCCESS);

        $this->floatingIp()->refresh();
        $this->assertEquals($this->availabilityZone()->id, $this->floatingIp()->availability_zone_id);
    }

    public function testFailedToLoadAvailabilityZone()
    {
        $this->command = \Mockery::mock(PopulateAvailabilityZoneId::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        $this->command->shouldReceive('line')
            ->andReturnUsing(function () {
                return true;
            });
        $this->command->shouldReceive('error')
            ->andReturnUsing(function () {
                return true;
            });

        $this->command->shouldReceive('info')
            ->with(\Mockery::capture($this->infoArgumentItem))->andReturnUsing(function () {
                $this->infoArgument[] = $this->infoArgumentItem;
                return true;
            });

        $this->floatingIp()->availability_zone_id = null;
        $this->floatingIp()->save();
        $this->vpc()->delete();

        $this->assertNull($this->floatingIp()->availability_zone_id);

        $this->command->handle();

        $this->assertEquals('1 errors found, id\'s: ' . $this->floatingIp()->id, $this->infoArgument[0]);
    }
}