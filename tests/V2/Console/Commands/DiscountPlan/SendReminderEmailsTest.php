<?php
namespace Tests\V2\Console\Commands\DiscountPlan;

use App\Console\Commands\Command;
use App\Console\Commands\DiscountPlan\SendReminderEmails;
use App\Console\Commands\Orchestrator\ScheduledDeploy;
use App\Mail\AvailabilityZoneCapacityAlert;
use App\Mail\DiscountPlanTrialReminder;
use App\Models\V2\DiscountPlan;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendReminderEmailsTest extends TestCase
{
    protected $command;

    public function setUp(): void
    {
        parent::setUp();
        $this->command = \Mockery::mock(SendReminderEmails::class)->makePartial();
        $this->command->shouldReceive('info')->andReturnTrue();
        $this->command->shouldReceive('option')->with('test-run')->andReturnTrue();
        $this->command->shouldReceive('option')->with('force')->andReturnTrue();
    }

    /**
     * WHEN 50% of trial length is remaining AND more than a week till trial end AND no vpc resources exist
     * @return void
     */
    public function testSendsHalfWayThroughTrialUsageReminder()
    {
        Mail::fake();

        $discountPlan = DiscountPlan::factory()->create([
            'is_trial' => true,
            'status' => 'approved',
            'term_start_date' => Carbon::parse('January 1st 2022'),
            'term_end_date' =>  Carbon::parse('January 31st 2022'),
        ]);

        $midpoint = Carbon::parse('January 16th 2022');

        $this->command->now = $midpoint;

        $this->command->handle();

//        Mail::assertSent(DiscountPlanTrialReminder::class, function ($alert) {
//            return $alert->alertLevel = AvailabilityZoneCapacityAlert::ALERT_LEVEL_WARNING;
//        });
    }

    public function test7DaysToGoSendsReminder()
    {
        
    }

    public function testEndsTodaySendsReminder()
    {
        
    }

    public function testNoEmailIsSentIfDoesNotMatchCriteria()
    {

    }


}