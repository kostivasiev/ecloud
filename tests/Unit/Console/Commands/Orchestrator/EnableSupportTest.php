<?php

namespace Tests\Unit\Console\Commands\Orchestrator;

use App\Console\Commands\Orchestrator\EnableSupport;
use App\Models\V2\BillingMetric;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use App\Models\V2\Vpc;
use Carbon\Carbon;
use Tests\TestCase;

class EnableSupportTest extends TestCase
{
    protected OrchestratorConfig $orchestratorConfig;

    protected OrchestratorBuild $orchestratorBuild;

    protected $command;

    public function setUp(): void
    {
        parent::setUp();
        $this->orchestratorConfig = OrchestratorConfig::factory()->create();
        $this->orchestratorConfig->data = json_encode([
            'vpcs' => [
                [
                    'name' => 'vpc-1',
                    'region_id' => $this->region()->id,
                    'console_enabled' => true,
                    'advanced_networking' => true,
                    'support_enabled' => true,
                ],
                [
                    'name' => 'vpc-2',
                    'region_id' => $this->region()->id,
                    'console_enabled' => true,
                    'support_enabled' => false,
                ],
            ]
        ]);
        $this->orchestratorConfig->save();

        $this->orchestratorBuild = OrchestratorBuild::factory()->make();
        $this->orchestratorBuild->orchestratorConfig()->associate($this->orchestratorConfig);
        $this->orchestratorBuild->save();

        $this->orchestratorBuild->updateState('vpc', 0, $this->vpc()->id);
        Vpc::factory()->create([
            'region_id' => $this->region()->id
        ])->each(function ($vpc, $index) {
            $this->orchestratorBuild->updateState('vpc', $index+1, $vpc->id);
        });

        $this->command = \Mockery::mock(EnableSupport::class)->makePartial();
        $this->command->updated = 0;
        $this->command->errors = 0;

        $this->command->allows('option')->andReturnFalse();
        $this->command->allows('info')->with(\Mockery::capture($message));
    }

    public function testSuccess()
    {
        $this->assertCount(0, BillingMetric::all());

        $this->command->handle();

        $this->assertCount(1, BillingMetric::all());

        $billingMetric = BillingMetric::first();

        $this->assertEquals($this->vpc()->id, $billingMetric->resource_id);
        $this->assertEquals('vpc.support', $billingMetric->key);
        $this->assertEquals($this->vpc()->created_at, $billingMetric->start);
    }

    public function testBillingMetricRecordAlreadyExistsSkips()
    {
        BillingMetric::factory()->create([
            'resource_id' => $this->vpc()->id,
            'reseller_id' => $this->vpc()->reseller_id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'vpc.support',
            'start' => Carbon::now()->subtract('month', 1)->toString(),
        ]);

        $this->assertCount(1, BillingMetric::all());

        $this->command->handle();

        $this->assertCount(1, BillingMetric::all());
    }
}
