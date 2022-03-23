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
            'script_template' => <<<'EOM'
sed -i '/^DOMAIN=\"\"/c\DOMAIN="'{{{magento_external_url}}}'"\' /scripts/.ubuntu.addon.sh
sed -i 's/WP=\"no\"/WP=\"yes\"/g' /scripts/.ubuntu.addon.sh
sed -i 's/VARNISH=\"no\"/VARNISH=\"yes\"/g' /scripts/.ubuntu.addon.sh

sh /scripts/.ubuntu.addon.sh

ippaddr=$(ip a|grep inet| grep -v '127.0.0.1'|grep -v 'inet6'|awk '{print $2}'|sed 's/\// /'| awk '{print $1}')
sed -i 's/[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}'/$ippaddr/g /etc/systemd/system/varnish.service
sed -i 's/[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}'/$ippaddr/g /etc/varnish/default.vcl
systemctl daemon-reload
service varnish restart

kbvariable="$(cat /proc/meminfo | grep -i "memtotal" | awk '{print $2}')"
memoryvariable="$(($kbvariable / 1024 / 4))"
sed -i "/Xms/c\-Xms$memoryvariable\m" /etc/elasticsearch/jvm.options
sed -i "/Xmx/c\-Xmx$memoryvariable\m" /etc/elasticsearch/jvm.options

php-fpm -t
service php-fpm restart

if ! systemctl restart nginx; then exit 5; fi
EOM,
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
