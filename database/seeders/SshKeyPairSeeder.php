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
        factory(SshKeyPair::class)->create([
            'id' => 'ssh-aaaaaaaa',
        ]);
    }
}
