<?php

namespace Database\Seeders;

use App\Models\V2\Image;
use Database\Seeders\Images\CpanelImageSeeder;
use Database\Seeders\Images\CentosWithMcafeeSeeder;
use Database\Seeders\Images\MsSqlImageSeeder;
use Database\Seeders\Images\PleskImageSeeder;
use Database\Seeders\Images\WindowsServer2019Seeder;
use Illuminate\Database\Seeder;

class ImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $image = Image::factory()->create([
            'id' => 'img-aaaaaaaa',
            'name' => 'Dev Image (Centos 7)',
            'vpc_id' => null,
            'logo_uri' => 'https://images.ukfast.co.uk/logos/centos/300x300_white.png',
            'documentation_uri' => 'https://docs.centos.org/en-US/docs/',
            'description' => 'CentOS (Community enterprise Operating System)',
            'script_template' => '',
            'vm_template' => 'CentOS7 x86_64',
            'platform' => 'Linux',
            'active' => true,
            'public' => true,
            'visibility' => Image::VISIBILITY_PUBLIC,
        ]);

        // Sync the pivot table
        $image->availabilityZones()->sync('az-aaaaaaaa');

        /**
         * Other Images
         */
        $this->call(PleskImageSeeder::class);
        $this->call(MsSqlImageSeeder::class);
        $this->call(CpanelImageSeeder::class);
        $this->call(CentosWithMcafeeSeeder::class);
        $this->call(WindowsServer2019Seeder::class);
    }
}
