<?php

namespace App\Console\Commands\DiscountPlan;

use App\Console\Commands\Command;
use App\Mail\DiscountPlanTrialReminder;
use App\Models\V2\DiscountPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendReminderEmails extends Command
{
    protected $signature = 'discount-plan:send-reminder-emails {--T|test-run} {--F|force}';
    protected $description = 'Send discount plan trial reminder emails';

    public Carbon $now;

    public function __construct()
    {
        parent::__construct();
        $this->now = Carbon::now();
    }

    public function handle()
    {
        DiscountPlan::where('status', 'approved')
            ->where('term_start_date', '<=', $this->now)
            ->where('term_end_date', '>=', $this->now)
            ->where('is_trial', true)
            ->each(function ($discountPlan) {

                // Send reminder when 50% through trial and no resources exist
                $days = $discountPlan->term_start_date->diffInDays($discountPlan->term_end_date);

                $midpoint = ($days/2);

                $midpoint = $discountPlan->term_start_date->addDays($midpoint);

                dd($midpoint->isSameDay($this->now));

            });


//        $discountPlanTrialReminder = new DiscountPlanTrialReminder($availabilityZoneCapacity);
//        Mail::send($discountPlanTrialReminder);
//        Log::info();


    }


}
