<?php

namespace App\Mail;

use App\Models\V2\DiscountPlan;
use Carbon\Carbon;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Str;

class DiscountPlanTrialReminder extends Mailable
{
    public int $priority = 3; // Normal

    public int $daysRemaining;

    public function __construct(public DiscountPlan $discountPlan)
    {
        $this->daysRemaining = $discountPlan->term_end_date->diffInDays(Carbon::now());

        $this->priority = match (true) {
            $this->daysRemaining <= 1 => 1,
            $this->daysRemaining <= 7 => 2,
            $this->daysRemaining > 7 => 3,
        };
    }

    /**
     * @return DiscountPlanTrialReminder
     */
    public function build()
    {
        if ($this->daysRemaining == 0) {
            $this->subject('Your eCloud VPC trial ends at midnight!');
        } else {
            $this->subject('Your eCloud VPC trial will end in ' . $this->daysRemaining . ' '.
                Str::plural('day', $this->daysRemaining)  . '!');
        }

        return ($this->daysRemaining == 0) ?
            $this->view('mail.discount_plan_trial_ending'):
            $this->view('mail.discount_plan_trial_reminder');
    }
}
