<?php

namespace Database\Seeders\V1;

use Illuminate\Database\Seeder;

class ResellerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('ucs_reseller')
            ->insert([
                'ucs_reseller_id' => '1',
                'ucs_reseller_reseller_id' => '1',
                'ucs_reseller_active' => 'Yes',
                'ucs_reseller_solution_name' => 'Single Site Solution',
                'ucs_reseller_status' => 'Completed',
                'ucs_reseller_start_date' => '0000-00-00 00:00:00',
                'ucs_reseller_datacentre_id' => '5',
                'ucs_reseller_encryption_enabled' => 'No',
                'ucs_reseller_encryption_default' => 'Yes',
                'ucs_reseller_encryption_billing_type' => 'PAYG'
            ]);
    }
}