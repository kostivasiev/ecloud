<?php

namespace Database\Seeders\Images;

use App\Models\V2\Image;
use App\Models\V2\ImageMetadata;
use Illuminate\Database\Seeder;
use function factory;

class LoadBalancerImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $image = factory(Image::class)->create([
            'id' => 'img-loadbalancer',
            'name' => 'Ubuntu 20.04 LBv2',
            'vpc_id' => null,
            'logo_uri' => null,
            'documentation_uri' => null,
            'description' => 'Load Balancer Image',
            'script_template' => '',
            'vm_template' => 'CentOS7 x86_64',
            'platform' => 'Linux',
            'active' => true,
            'public' => false,
            'visibility' => Image::VISIBILITY_PUBLIC,
        ]);

        // Sync the pivot table
        $image->availabilityZones()->sync('az-aaaaaaaa');

        factory(ImageMetadata::class)->create([
            'image_id' => $image->id,
            'key' => 'ukfast.loadbalancer.version',
            'value' => 'Ubuntu-20.04-LBv2',
        ]);

//        factory(ImageParameter::class)->create([
//            'name' => 'Statistics page password',
//            'key' => 'stats_password',
//            'type' => 'String',
//            'description' => 'Password for accessing the haproxy stats page',
//            'required' => true,
//            'validation_rule' => '/.+/',
//        ]);


    }
}
