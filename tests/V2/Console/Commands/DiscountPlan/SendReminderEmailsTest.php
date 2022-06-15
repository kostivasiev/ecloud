<?php
namespace Tests\V2\Console\Commands\DiscountPlan;

use App\Console\Commands\DiscountPlan\SendReminderEmails;
use App\Mail\DiscountPlanTrialReminder;
use App\Models\V2\BillingMetric;
use App\Models\V2\DiscountPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendReminderEmailsTest extends TestCase
{
    protected $command;

    public function setUp(): void
    {
        parent::setUp();
        $mockAccountAdminClient = \Mockery::mock(\UKFast\Admin\Account\AdminClient::class);

        $mockAccountAdminClient->allows('customers->getById')
            ->with(1)
            ->andReturnUsing(function () {
                return new \UKFast\Admin\Account\Entities\Customer([
                    'primaryContactId' => 111,
                ]);
            });

        $mockAccountAdminClient->allows('contacts->getById')
            ->with(111)
            ->andReturnUsing(function () {
                return new \UKFast\Admin\Account\Entities\Contact([
                    'emailAddress' => 'captain.kirk@example.com',
                ]);
            });

        app()->bind(\UKFast\Admin\Account\AdminClient::class, function () use ($mockAccountAdminClient) {
            return $mockAccountAdminClient;
        });

        $this->command = \Mockery::mock(SendReminderEmails::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        $this->command->success = 0;
        $this->command->failed = 0;
        $this->command->shouldReceive('info')->andReturnTrue();
        $this->command->shouldReceive('option')->with('test-run')->andReturnFalse();
        $this->command->shouldReceive('option')->with('force')->andReturnTrue();
    }

    /**
     * WHEN 50% of trial length is remaining AND more than a week till trial end AND no vpc resources exist
     * @return void
     */
    public function testSendsHalfWayThroughTrialUsageReminder()
    {
        Mail::fake();

        DiscountPlan::factory()->create([
            'is_trial' => true,
            'status' => 'approved',
            'term_start_date' => Carbon::parse('January 1st 2022'),
            'term_end_date' =>  Carbon::parse('January 31st 2022'),
        ]);

        $midpoint = Carbon::parse('January 16th 2022');

        // Set the current day to the midpoint through the discount plan trial that would trigger the email.
        Carbon::setTestNow($midpoint);
        $this->command->now = $midpoint;

        $this->command->handle();

        Mail::assertSent(DiscountPlanTrialReminder::class, function ($discountPlanTrialReminder) {
            return (
                $discountPlanTrialReminder->priority == 3 &&
                $discountPlanTrialReminder->subject = 'Your eCloud VPC trial will end in 15 days!' &&
                $discountPlanTrialReminder->to = 'captain.kirk@example.com'
            );
        });
    }

    /**
     * WHEN 50% of trial length is remaining AND more than a week till trial end AND vpc resources exist
     * @return void
     */
    public function testHalfWayThroughTrialUsageReminderNotSentIfTrialInUse()
    {
        Mail::fake();

        DiscountPlan::factory()->create([
            'is_trial' => true,
            'status' => 'approved',
            'term_start_date' => Carbon::parse('January 1st 2022'),
            'term_end_date' =>  Carbon::parse('January 31st 2022'),
        ]);

        // The customer is using the trial, don't send reminder
        BillingMetric::factory()->create([
            'id' => 'bm-test',
            'resource_id' => $this->instanceModel()->id,
            'vpc_id' => $this->vpc()->id,
            'key' => 'ram.capacity',
            'value' => 1024,
            'start' => Carbon::parse('January 5th 2022'),
        ]);

        $midpoint = Carbon::parse('January 16th 2022');

        // Set the current day to the midpoint through the discount plan trial that would trigger the email.
        Carbon::setTestNow($midpoint);
        $this->command->now = $midpoint;

        $this->command->handle();

        Mail::assertNothingSent();
    }

    public function test7DaysToGoSendsReminder()
    {
        Mail::fake();

        $discountPlan = DiscountPlan::factory()->create([
            'is_trial' => true,
            'status' => 'approved',
            'term_start_date' => Carbon::parse('January 1st 2022'),
            'term_end_date' =>  Carbon::parse('January 31st 2022'),
        ]);

        $weekBeforeEnd = $discountPlan->term_end_date->subDays(7);

        // Set the current day to a week before the discount plan trial end.
        Carbon::setTestNow($weekBeforeEnd);
        $this->command->now = $weekBeforeEnd;

        $this->command->handle();

        Mail::assertSent(DiscountPlanTrialReminder::class, function ($discountPlanTrialReminder) {
            return (
                $discountPlanTrialReminder->priority == 2 &&
                $discountPlanTrialReminder->subject = 'Your eCloud VPC trial will end in 7 days!' &&
                $discountPlanTrialReminder->to = 'captain.kirk@example.com'
            );
        });
    }

    public function testEndsTodaySendsReminder()
    {
        Mail::fake();

        $discountPlan = DiscountPlan::factory()->create([
            'is_trial' => true,
            'status' => 'approved',
            'term_start_date' => Carbon::parse('January 1st 2022'),
            'term_end_date' =>  Carbon::parse('January 31st 2022'),
        ]);


        // Set the current day to a week before the discount plan trial end.
        Carbon::setTestNow($discountPlan->term_end_date);
        $this->command->now = $discountPlan->term_end_date;

        $this->command->handle();

        Mail::assertSent(DiscountPlanTrialReminder::class, function ($discountPlanTrialReminder) {
            return (
                $discountPlanTrialReminder->priority == 1 &&
                $discountPlanTrialReminder->subject = 'Your eCloud VPC trial ands at midnight!' &&
                $discountPlanTrialReminder->to = 'captain.kirk@example.com'
            );
        });
    }

    public function testNoEmailIsSentIfDoesNotMatchCriteria()
    {
        Mail::fake();

        $discountPlan = DiscountPlan::factory()->create([
            'is_trial' => true,
            'status' => 'approved',
            'term_start_date' => Carbon::parse('January 1st 2022'),
            'term_end_date' =>  Carbon::parse('January 31st 2022'),
        ]);


        // Set the current day to a week before the discount plan trial end.
        Carbon::setTestNow($discountPlan->term_start_date);
        $this->command->now = $discountPlan->term_start_date;

        $this->command->handle();

        Mail::assertNotSent(DiscountPlanTrialReminder::class);
    }


}