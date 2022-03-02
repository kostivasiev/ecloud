<?php

namespace Tests\unit\Console\Commands\Billing;

use App\Console\Commands\Billing\CleanupAdvancedNetworking;
use App\Models\V2\BillingMetric;
use Tests\TestCase;

class CleanupAdvancedNetworkingTest extends TestCase
{
    public BillingMetric $metric;
    public $job;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vpc()->setAttribute('advanced_networking', false)->saveQuietly();
        $this->metric = BillingMetric::factory()->create([
            'id' => 'bm-test',
            'resource_id' => $this->vpc()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'networking.advanced',
            'value' => 1024,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);
        $this->job = \Mockery::mock(CleanupAdvancedNetworking::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $this->job->allows('info')->withAnyArgs()->andReturnTrue();
        $this->job->allows('option')->withAnyArgs()->andReturnFalse();
    }

    public function testCommand()
    {
        $this->job->handle();
        $this->metric->refresh();
        $this->assertNotNull($this->metric->deleted_at);
    }
}
