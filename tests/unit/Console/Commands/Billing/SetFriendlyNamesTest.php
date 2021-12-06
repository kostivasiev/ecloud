<?php

namespace Tests\unit\Console\Commands\Billing;

use App\Console\Commands\Billing\SetFriendlyNames;
use App\Models\V2\BillingMetric;
use Database\Seeders\BillingMetricSeeder;
use Tests\TestCase;

class SetFriendlyNamesTest extends TestCase
{
    public $commandMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandMock = \Mockery::mock(SetFriendlyNames::class)->makePartial();
        $this->commandMock->allows('info')->andReturnTrue();
        $this->commandMock->allows('line')->andReturnTrue();
        (new BillingMetricSeeder())->run();
    }

    public function testCommandToSetNames()
    {
        $this->commandMock->allows()->option()->with('reset')->andReturnFalse();
        $this->commandMock->allows()->option()->with('test-run')->andReturnFalse();

        $this->commandMock->handle();
        $metrics = BillingMetric::whereNull('friendly_name')->get();
        $this->assertEquals(0, $metrics->count());
    }

    public function testCommandToResetNames()
    {
        $this->commandMock->allows()->option()->with('reset')->andReturnTrue();

        $this->commandMock->handle();
        $metrics = BillingMetric::whereNull('friendly_name')->get();
        $this->assertNotEquals(0, $metrics->count());
    }
}
