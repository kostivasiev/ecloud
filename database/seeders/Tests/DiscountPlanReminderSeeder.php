<?php

namespace Database\Seeders\Tests;

use App\Models\V2\DiscountPlan;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DiscountPlanReminderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Trial halfway
        DiscountPlan::factory()
            ->create([
                'reseller_id' => 7052,
                'contact_id' => 1,
                'is_trial' => true,
                'status' => 'approved',
                'term_start_date' => Carbon::now()->subDays(15),
                'term_end_date' => Carbon::now()->addDays(15)
            ]);

        // 7 days to go
        DiscountPlan::factory()
            ->create([
                'reseller_id' => 7052,
                'contact_id' => 1,
                'is_trial' => true,
                'status' => 'approved',
                'term_start_date' => Carbon::now()->subDays(23),
                'term_end_date' => Carbon::now()->addDays(7),
            ]);

        // 0 days to go
        DiscountPlan::factory()
            ->create([
                'reseller_id' => 7052,
                'contact_id' => 1,
                'is_trial' => true,
                'status' => 'approved',
                'term_start_date' => Carbon::now()->subDays(29),
                'term_end_date' => Carbon::now()->addHours(12),
            ]);

        // Nothing to do
        DiscountPlan::factory()
            ->create([
                'reseller_id' => 7052,
                'contact_id' => 1,
                'is_trial' => true,
                'status' => 'approved',
                'term_start_date' => Carbon::now(),
                'term_end_date' => Carbon::now()->addDays(30),
            ]);
    }
}
