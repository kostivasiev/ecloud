<?php

namespace Database\Seeders\Images\Magento;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Image;
use App\Models\V2\ImageMetadata;
use Illuminate\Database\Seeder;

class Ubuntu2004MagentoDb extends Seeder
{
    public function run()
    {
        $imageData = [
            'name' => 'Magento - Ubuntu 20.04 - DB',
            'vpc_id' => null,
            'logo_uri' => 'https://www.ukfast.co.uk/images/structure/logos/magento-logo.png',
            'documentation_uri' => 'https://docs.ukfast.co.uk/ecommercestacks/magento/magento2/index.html',
            'description' => <<<'EOM'
Magento is a feature-rich eCommerce platform built on open-source technology that provides online merchants with unprecedented flexibility and control over the look, content and functionality of their eCommerce store.

<h3>Getting started</h3>

<p>You can import your site’s files and database once the instance has been deployed, so everything should be good to go following this. However, should you have any further questions or queries, please refer to our Magento 2 documentation</p>

<p>If our documentation does not answer your questions, please do not hesitate to get in touch with our specialist Magento support team.</p>

<p><b>This image will require the assignment of a floating IP during launch. This is to allow guest customisations to complete during the launch process.</b></p>
EOM,
            'script_template' => null,
            'readiness_script' => null,
            'vm_template' => 'ubuntu2004-magento-db',
            'platform' => 'Linux',
            'active' => true,
            'public' => false,
            'visibility' => Image::VISIBILITY_PUBLIC,
        ];

        if (app()->environment() != 'production') {
            $imageData['id'] = 'img-ubuntu2004-magento-db';
        }

        $image = Image::factory()->create($imageData);

        // Sync the pivot table
        $image->availabilityZones()->sync(AvailabilityZone::all()->pluck('id')->toArray());

        ImageMetadata::factory()->create([
            'image_id' => $image->id,
            'key' => 'ukfast.license.id',
            'value' => 366,
        ]);

        ImageMetadata::factory()->create([
            'image_id' => $image->id,
            'key' => 'ukfast.fip.required',
            'value' => 'true',
        ]);
    }
}
