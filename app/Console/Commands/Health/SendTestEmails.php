<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Mail\Mailer;

class SendTestEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'health:send-test-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send\'s test emails to emails from alerts config';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Mailer $mailer)
    {
        $addresses = [
            config('alerts.billing.to'),
            config('alerts.capacity.default.to'),
            config('alerts.capacity.floating_ip.to'),
            config('alerts.capacity.floating_ip.cc'),
            config('alerts.health.to'),
            config('alerts.capacity.dev.to'),
        ];
        $text = 'Test';
        $mailer->raw($text, function ($m) use ($addresses) {
            $m->to($addresses)->subject("Test Email");
        });

        return 0;
    }
}
