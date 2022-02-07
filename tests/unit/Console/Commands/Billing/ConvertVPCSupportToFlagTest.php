<?php

namespace Tests\unit\Console\Commands\Billing;

use App\Console\Commands\VPC\ConvertVpcSupportToFlag;
use App\Listeners\V2\Vpc\UpdateSupportEnabledBilling;
use App\Models\V2\BillingMetric;
use App\Models\V2\VpcSupport;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Console\Concerns\InteractsWithIO;
use Tests\TestCase;

class ConvertVPCSupportToFlagTest extends TestCase
{
    public BillingMetric $metric;
    public $job;
    public $dates;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vpc()->setAttribute('support_enabled', false)->saveQuietly();
        $this->dates = [
            'start_date' => Carbon::now()->subMonth(),
            'end_date' => Carbon::now()->addMonth(),
        ];

        VpcSupport::create([
            'vpc_id' => $this->vpc()->id,
            'start_date' => $this->dates['start_date'],
            'end_date' => $this->dates['end_date'],
        ]);
    }

    public function testCommand()
    {
        $job = new ConvertVpcSupportToFlag;
        $job->handle(true);

        $this->vpc()->refresh();

        $this->assertTrue($this->vpc()->support_enabled);
        $bm = BillingMetric::getActiveByKey($this->vpc(), UpdateSupportEnabledBilling::getKeyName());
        $this->assertEquals($bm->start, $this->dates['start_date']);
        $this->assertEquals($bm->end, $this->dates['end_date']);
    }
}
