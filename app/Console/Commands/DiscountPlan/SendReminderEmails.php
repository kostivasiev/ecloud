<?php

namespace App\Console\Commands\DiscountPlan;

use App\Console\Commands\Command;
use App\Mail\DiscountPlanTrialReminder;
use App\Models\V2\BillingMetric;
use App\Models\V2\DiscountPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use UKFast\Admin\Account\AdminClient;
use UKFast\SDK\Exception\ApiException;

class SendReminderEmails extends Command
{
    protected $signature = 'discount-plan:send-reminder-emails {--T|test-run} {--F|force}';
    protected $description = 'Send discount plan trial reminder emails';

    public Carbon $now;

    private $adminClient;

    public function __construct()
    {
        parent::__construct();
        $this->now = Carbon::now();
    }

    public function handle()
    {
        $this->adminClient = app()->make(AdminClient::class);
        DiscountPlan::where('status', 'approved')
            ->where('term_start_date', '<=', $this->now)
            ->where('term_end_date', '>=', $this->now)
            ->where('is_trial', true)
            ->each(function ($discountPlan) {
                // Send reminder when 50% through trial and no resources exist & more than 7 days remaining
                $days = $discountPlan->term_start_date->diffInDays($discountPlan->term_end_date);
                $midpoint = ($days/2);
                $midpoint = $discountPlan->term_start_date->addDays($midpoint);

                if ($midpoint->isSameDay($this->now) && $this->now->diffInDays($discountPlan->term_end_date) > 7) {
                    // The customer has created resources and is using the trial, don't send reminder
                    if (BillingMetric::whereHas('vpc', function ($query) use ($discountPlan) {
                        $query->where('reseller_id', $discountPlan->reseller_id);
                    })->count() > 0) {
                        return;
                    }
                    return $this->sendEmail($discountPlan, new DiscountPlanTrialReminder($discountPlan));
                }

                if ($this->now->diffInDays($discountPlan->term_end_date) <= 7) {
                    return $this->sendEmail($discountPlan, new DiscountPlanTrialReminder($discountPlan));
                }

                if ($this->now->diffInDays($discountPlan->term_end_date) == 0) {
                    return $this->sendEmail($discountPlan, new DiscountPlanTrialReminder($discountPlan));
                }
                return;
            });
    }

    private function sendEmail(DiscountPlan $discountPlan, DiscountPlanTrialReminder $discountPlanTrialReminder)
    {
        try {
            if (empty($discountPlan->contact_id)) {
                $this->info('Discount plan ' . $discountPlan->id . ' has no contact_id, retrieving from accounts API');
                $discountPlan->contact_id =
                    ($this->adminClient->customers()->getById($discountPlan->reseller_id))
                        ->primaryContactId;
            }

            $emailAddress = $this->adminClient->contacts()->getById($discountPlan->contact_id)->emailAddress;
        } catch (ApiException $exception) {
            $message = 'Failed to retrieve contact email address from accounts API for discount plan ' .
                $discountPlan->id . ' : ' .
                print_r($exception->getErrors(), true);
            $this->error($message);
            return;
        }

        if (!$this->option('test-run')) {
            Mail::to($emailAddress)->send($discountPlanTrialReminder);
        }

        Log::info('Trial reminder email sent for discount plan ' . $discountPlan->id);
    }
}
