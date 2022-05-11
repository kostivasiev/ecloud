<?php

namespace Database\Seeders\Images;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Image;
use App\Models\V2\ImageMetadata;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use function app;
use function factory;

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
                'script_template' => <<<'EOM'
Start-Process -FilePath "C:\Program Files\Microsoft SQL Server\150\Setup Bootstrap\SQL2019\setup.exe" -ArgumentList "/QS /ACTION=CompleteImage /INSTANCEID=MSSQLSERVER /INSTANCENAME=MSSQLSERVER /PID=PMBDC-FXVM3-T777P-N4FY8-PKFF4 /IAcceptSQLServerLicenseTerms=true /SQLSYSADMINACCOUNTS=`"$env:COMPUTERNAME\ukfast.support`" `"$env:COMPUTERNAME\graphite.rack`" /BROWSERSVCSTARTUPTYPE=DISABLED /TCPENABLED=1" -Wait
EOM,
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
                'script_template' => <<<'EOM'
Start-Process -FilePath "C:\Program Files\Microsoft SQL Server\150\Setup Bootstrap\SQL2019\setup.exe" -ArgumentList "/QS /ACTION=CompleteImage /INSTANCEID=MSSQLSERVER /INSTANCENAME=MSSQLSERVER /PID=33QQK-WWQNB-G6T46-C86YB-TX2PH /IAcceptSQLServerLicenseTerms=true /SQLSYSADMINACCOUNTS=`"$env:COMPUTERNAME\ukfast.support`" `"$env:COMPUTERNAME\graphite.rack`" /BROWSERSVCSTARTUPTYPE=DISABLED /TCPENABLED=1" -Wait
EOM,
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
                'script_template' => <<<'EOM'
Start-Process -FilePath "C:\Program Files\Microsoft SQL Server\150\Setup Bootstrap\SQL2019\setup.exe" -ArgumentList "/QS /ACTION=CompleteImage /INSTANCEID=MSSQLSERVER /INSTANCENAME=MSSQLSERVER /PID=2C9JR-K3RNG-QD4M4-JQ2HR-8468J /IAcceptSQLServerLicenseTerms=true /SQLSYSADMINACCOUNTS=`"$env:COMPUTERNAME\ukfast.support`" `"$env:COMPUTERNAME\graphite.rack`" /BROWSERSVCSTARTUPTYPE=DISABLED /TCPENABLED=1" -Wait
EOM,
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
        $images = Image::factory(3)
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
            ImageMetadata::factory()->create([
                'image_id' => $image->id,
                'key' => 'ukfast.license.identifier',
                'value' => Str::upper(Str::replaceFirst(' ', '-', $image->vm_template)),
            ]);

            ImageMetadata::factory(3)->create([
                'image_id' => $image->id,
                'key' => 'ukfast.license.type',
                'value' => 'mssql',
            ]);

            ImageMetadata::factory()->create([
                'image_id' => $image->id,
                'key' => 'ukfast.license.id',
                'value' => '353',
            ]);

            ImageMetadata::factory()->create([
                'image_id' => $image->id,
                'key' => 'ukfast.license.mssql.edition',
                'value' => Str::replaceFirst('windows 2019 ', '', $image->vm_template),
            ]);
        });
    }
}
