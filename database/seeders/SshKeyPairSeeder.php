<?php

namespace Database\Seeders;

use App\Models\V2\SshKeyPair;
use Illuminate\Database\Seeder;

class SshKeyPairSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        SshKeyPair::factory()->create([
            'id' => 'ssh-aaaaaaaa',
            'reseller_id' => 7052
        ]);
    }
}
