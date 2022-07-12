<?php

return [
    /*
     * List of commands that can be run using the Artisan Runner
     * BE CAREFUL what Laravel commands you leave in here.
     * For instance Tinker can be used to echo out config values!
     */
    'allowed-commands' => [
        App\Console\Commands\DiscountPlan\SendReminderEmails::class,
        App\Console\Commands\FastDesk\BackfillVpn::class,
        UKFast\ArtisanRunner\Commands\HotFuzzQuote::class,
    ],
];
