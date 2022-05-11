<?php

namespace Database\Seeders\Images;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Image;
use App\Models\V2\Software;
use Illuminate\Database\Seeder;
use function factory;

class CentosWithMcafeeSeeder extends Seeder
{
    /**
     * Create a Centos image with associated software to be installed on deploy
     *
     * @return void
     */
    public function run()
    {
        $imageData = [
            'id' => 'img-centos-mcafee',
            'name' => 'Centos with McAfee Antivirus',
            'vpc_id' => null,
            'logo_uri' => null,
            'documentation_uri' => null,
            'description' => 'A test Centos image with associated McAfee software.',
            'script_template' => null,
            'vm_template' => 'CentOS7 x86_64',
            'platform' => 'Linux',
            'active' => true,
            'public' => true,
            'visibility' => Image::VISIBILITY_PUBLIC,
        ];

        /** @var Image $image */
        $image = Image::factory()->create($imageData);

        // Sync the pivot table
        $image->availabilityZones()->sync(AvailabilityZone::all()->pluck('id')->toArray());

        $image->software()->sync(['soft-mcafee-' . strtolower(Software::PLATFORM_LINUX)]);
    }
}
