<?php

namespace Database\Seeders\Images;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Image;
use App\Models\V2\Software;
use Illuminate\Database\Seeder;
use function factory;

class WindowsServer2019Seeder extends Seeder
{
    /**
     * Create a Centos image with associated software to be installed on deploy
     *
     * @return void
     */
    public function run()
    {
        $imageData = [
            'id' => 'img-windows',
            'name' => 'Windows Server 2019',
            'vpc_id' => null,
            'logo_uri' => null,
            'documentation_uri' => null,
            'description' => 'A test Windows Server 2019 image.',
            'script_template' => null,
            'vm_template' => 'Windows 2019 Datacenter (2-core pack)',
            'platform' => 'Windows',
            'active' => true,
            'public' => true,
            'visibility' => Image::VISIBILITY_PUBLIC,
        ];

        $image = factory(Image::class)->create($imageData);

        // Sync the pivot table
        $image->availabilityZones()->sync(AvailabilityZone::all()->pluck('id')->toArray());

        $image->software()->sync(['soft-mcafee-' . strtolower(Software::PLATFORM_WINDOWS)]);
    }
}
