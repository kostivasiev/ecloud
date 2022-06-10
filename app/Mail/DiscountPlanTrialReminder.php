<?php

namespace App\Mail;

use App\Models\V2\DiscountPlan;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Str;

class DiscountPlanTrialReminder extends Mailable
{
    public int $priority = 3; // Normal

    public int $daysRemaining;

    public function __construct(public DiscountPlan $discountPlan)
    {
        $this->daysRemaining = $discountPlan->term_start_date->diffInDays($discountPlan->term_end_date);

        $this->priority(
            match (true) {
                $this->daysRemaining > 7 => 3,
                $this->daysRemaining <= 7 => 2,
                $this->daysRemaining <= 1 => 1,
            }
        );
    }

    /**
     * @return DiscountPlanTrialReminder
     */
    public function build()
    {
//        $this->from(config('mail.from.address')); this will default to config

        if (config('app.env') != 'production') {
            $this->to(config('mail.to.dev'));
        } else {
            // Get the reseller email


        }

        $this->subject('Your eCloud VPC trial will end in ' . $this->daysRemaining . ' '. Str::plural('day', $this->daysRemaining)  . '!');


        return $this->view('mail.discount_plan_trial_reminder');
//            ->with([
//                'availability_zone_id' => $this->availabilityZoneCapacity->availability_zone_id,
//            ]);
    }
}
