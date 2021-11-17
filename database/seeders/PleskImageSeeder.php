<?php

namespace Database\Seeders;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Image;
use App\Models\V2\ImageMetadata;
use App\Models\V2\ImageParameter;
use Illuminate\Database\Seeder;

class PleskImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /**
         * Ubuntu 20.04 x86_64 - Plesk
         */
        $imageData = [
            'name' => 'Ubuntu 20.04 x86_64 - Plesk',
            'vpc_id' => null,
            'logo_uri' => 'https://images.ukfast.co.uk/logos/plesk/300x300_white.jpg',
            'documentation_uri' => 'https://docs.plesk.com/',
            'description' => 'We have partnered with Plesk to provide their leading tool for managing your Ubuntu 20.04 server.

Plesk Web Host Edition is for Web Hosters to customize, provision, and manage hosting businesses. Get maximum flexibility, access to the domain management tools, and the full WordPress Toolkit to support your multi-tenant, “install anything” business model.

Plesk provides an intuitive user interface to simplify your server access, so whatever your level of expertise, you can easily manage, configure and update your server. With multiple levels of administration access, Plesk is a market-leading control panel, ensuring security and flexibility for every organisation.

This appliance is installed with Plesk Web Host Edition*, allowing you to customize, provision, and manage hosting businesses. Get maximum flexibility, access to the domain management tools, and the full WordPress Toolkit to support your multi-tenant, “install anything” business model.

* Additional license fees apply',
            'script_template' => <<<EOM
plesk bin init_conf --init -email '{{{ plesk_admin_email_address }}}' -passwd '{{{ plesk_admin_password }}}'
if  [ $? -gt 0 ]; then
    echo "failed to initialise Plesk"
    exit 1
fi

{{#plesk_key}}
cat > /tmp/plesk.key << EOF
{{{ plesk_key }}}
EOF

plesk bin license --install /tmp/plesk.key
if  [ $? -gt 0 ]; then
    echo "failed to install Plesk license key"
    exit 1
fi

rm -f /tmp/plesk.key
{{/plesk_key}}
EOM,
            'vm_template' => 'ubuntu2004-plesk-v1.0.0',
            'platform' => 'Linux',
            'active' => true,
            'public' => true,
            'visibility' => Image::VISIBILITY_PUBLIC,
        ];

        if (app()->environment() != 'production') {
            $imageData['id'] = 'img-plesk';
        }

        $image = factory(Image::class)->create($imageData);

        // Sync the pivot table
        $image->availabilityZones()->sync(AvailabilityZone::all()->pluck('id')->toArray());

        factory(ImageMetadata::class)->create([
            'image_id' => $image->id,
            'key' => 'ukfast.license.identifier',
            'value' => 'PLESK-12-VPS-WEB-HOST-1M',
        ]);

        factory(ImageMetadata::class)->create([
            'image_id' => $image->id,
            'key' => 'ukfast.license.type',
            'value' => 'plesk',
        ]);

        factory(ImageMetadata::class)->create([
            'image_id' => $image->id,
            'key' => 'ukfast.fip.required',
            'value' => 'true',
        ]);

        factory(ImageParameter::class)->create([
            'image_id' => $image->id,
            'name' => 'Plesk Admin Email Address',
            'key' => 'plesk_admin_email_address',
            'type' => 'String',
            'description' => 'Plesk Admin Email Address',
            'required' => true,
            'validation_rule' => '/\w+/',
        ]);

        factory(ImageParameter::class)->create([
            'image_id' => $image->id,
            'name' => 'Plesk Admin Password',
            'key' => 'plesk_admin_password',
            'type' => 'Password',
            'description' => 'Plesk Admin Password',
            'required' => true,
            'validation_rule' => '/\w+/',
        ]);
    }
}
