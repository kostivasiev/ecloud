<?php

namespace Database\Seeders;

use App\Models\V2\Network;
use Illuminate\Database\Seeder;

class NetworkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Network::class)->create([
            'id' => 'net-aaaaaaaa',
            'name' => 'Dev Network',
            'subnet' => '10.0.0.0/24',
            'router_id' => 'rtr-aaaaaaaa'
        ]);
    }
}
