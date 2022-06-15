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

    public function __construct(
        public int $success = 0,
        public int $failed = 0,
    )
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
                    })->count() == 0) {
                        Log::info('Sending mid-point trial reminder for ' . $discountPlan->id);
                        $this->sendEmail($discountPlan, new DiscountPlanTrialReminder($discountPlan));
                        return;
                    }
                }

                if ($this->now->diffInDays($discountPlan->term_end_date) == 7) {
                    Log::info('Sending 7 day trial reminder for ' . $discountPlan->id);
                    $this->sendEmail($discountPlan, new DiscountPlanTrialReminder($discountPlan));
                    return;
                }

                if ($this->now->diffInDays($discountPlan->term_end_date) == 0) {
                    Log::info('Sending trial ends today reminder for ' . $discountPlan->id);
                    $this->sendEmail($discountPlan, new DiscountPlanTrialReminder($discountPlan));
                }
            });

        $this->info(
            'Total: ' . ($this->success + $this->failed) .
            ', Total Success: ' . $this->success .
            ', Total Failures: ' . $this->failed
        );
    }

    /**
     * @throws \Exception
     */
    private function sendEmail(DiscountPlan $discountPlan, DiscountPlanTrialReminder $discountPlanTrialReminder): void
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
                $discountPlan->id . ' : ' . print_r($exception->getErrors(), true);
            $this->error($message);
            $this->failed++;
            return;
        }

        if (!$this->option('test-run')) {
            if (config('app.env') != 'production') {
                $emailAddress = config('mail.to.dev');
            }

            Mail::to($emailAddress)->send($discountPlanTrialReminder);
        }

        // TODO: getting an error with this, but better handling would be good
//        if (!empty(Mail::failures())) {
//            $this->error('Failed to send reminder email to ' . $emailAddress . ' for discount plan ' . $discountPlan->id . ' ' . print_r(Mail::failures(), true));
//            $this->failed++;
//            return;
//        }

        Log::info('Reminder email sent for discount plan ' . $discountPlan->id,  [$discountPlanTrialReminder]);
        $this->success++;
    }
}
