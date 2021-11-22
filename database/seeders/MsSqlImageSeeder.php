<?php

namespace Database\Seeders;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Image;
use App\Models\V2\ImageMetadata;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MsSqlImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /**
         * Microsoft SQL Server 2017 Standard
         */
        $imageData = [
            [
                'name' => 'Microsoft SQL Server 2019 Standard',
                'vpc_id' => null,
                'logo_uri' => null,
                'documentation_uri' => null,
                'description' => null,
                'script_template' => null,
                'vm_template' => 'windows 2019 datacenter-mssql2019-standard',
                'platform' => 'Windows',
                'active' => true,
                'public' => true,
                'visibility' => Image::VISIBILITY_PUBLIC,
            ],
            [
                'name' => 'Microsoft SQL Server 2019 Web',
                'vpc_id' => null,
                'logo_uri' => null,
                'documentation_uri' => null,
                'description' => null,
                'script_template' => null,
                'vm_template' => 'windows 2019 datacenter-mssql2019-web',
                'platform' => 'Windows',
                'active' => true,
                'public' => true,
                'visibility' => Image::VISIBILITY_PUBLIC,
            ],
            [
                'name' => 'Microsoft SQL Server 2019 Enterprise',
                'vpc_id' => null,
                'logo_uri' => null,
                'documentation_uri' => null,
                'description' => null,
                'script_template' => null,
                'vm_template' => 'windows 2019 datacenter-mssql2019-enterprise',
                'platform' => 'Windows',
                'active' => true,
                'public' => true,
                'visibility' => Image::VISIBILITY_PUBLIC,
            ]
        ];

        if (app()->environment() != 'production') {
            $imageData[0]['id'] = 'img-mssql-std';
            $imageData[1]['id'] = 'img-mssql-web';
            $imageData[2]['id'] = 'img-mssql-ent';
        }

        $imageCount = 0;
        $images = factory(Image::class, 3)
            ->make()
            ->each(function ($image) use ($imageData, &$imageCount) {
                foreach ($imageData[$imageCount] as $key => $value) {
                    $image->setAttribute($key, $value);
                }
                $image->save();
                $imageCount++;
                return $image;
            });

        $images->each(function ($image) {
            // Sync the pivot table
            $image->availabilityZones()->sync(AvailabilityZone::all()->pluck('id')->toArray());
            factory(ImageMetadata::class)->create([
                'image_id' => $image->id,
                'key' => 'ukfast.license.identifier',
                'value' => Str::upper(Str::replace(' ', '-', $image->vm_template)),
            ]);

            factory(ImageMetadata::class)->create([
                'image_id' => $image->id,
                'key' => 'ukfast.license.type',
                'value' => 'mssql',
            ]);

            factory(ImageMetadata::class)->create([
                'image_id' => $image->id,
                'key' => 'ukfast.license.mssql.edition',
                'value' => Str::replace('windows 2019 ', '', $image->vm_template),
            ]);
        });
    }
}
