<?php

namespace App\Mail;

use App\Models\V2\DiscountPlan;
use Illuminate\Mail\Mailable;

class DiscountPlanTrialReminder extends Mailable
{
    public int $priority = 3; // Normal

    public function __construct(public DiscountPlan $discountPlan)
    {
//        $this->priority(2); // 7 days

//        $this->priority(1); // 0 days
    }

    /**
     * @return DiscountPlanTrialReminder
     */
    public function build()
    {
        $this->from(config('mail.from.address'));

        if (config('app.env') != 'production') {
            $this->to(config('mail.to.dev'));
        } else {
            // Get the reseller email


        }

        $this->subject('Your eCloud VPC trial will end in ' . $daysRemaining . ' days!');

        $this->priority($this->priority);

        return $this->view('mail.discount_plan_trial_reminder')
            ->with([
                'availability_zone_id' => $this->availabilityZoneCapacity->availability_zone_id,
            ]);
    }
}
