<?php

namespace App\Console\Commands\Image;

use App\Models\V2\ApplianceVersion;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Image;
use App\Models\V2\ImageMetadata;
use App\Models\V2\ImageParameter;
use Illuminate\Console\Command;
use UKFast\Admin\Devices\AdminClient;

class Populate extends Command
{
    protected $signature = 'image:populate';

    protected $description = 'Populate image tables with appliance data';

    public function handle()
    {
        Image::all()->each(function ($image) {
            if (!empty($image->appliance_version_id)) {
                $this->info("Updating Image $image->id \"$image->name\"");

                $applianceVersion = ApplianceVersion::findOrFail($image->appliance_version_id);

                $appliance = $applianceVersion->appliance;

                // Update the image record
                $image->name = $appliance->appliance_name;
                $image->logo_uri = $appliance->appliance_logo_uri;
                $image->documentation_uri = $appliance->appliance_documentation_uri;
                $image->description = $appliance->appliance_description;
                $image->script_template = $applianceVersion->appliance_version_script_template;
                $image->vm_template = $applianceVersion->appliance_version_vm_template;

                $mockAdminDevices = \Mockery::mock(AdminClient::class)
                    ->shouldAllowMockingProtectedMethods();
                app()->bind(AdminClient::class, function () use ($mockAdminDevices) {
                    $mockedResponse = new \stdClass();
                    $mockedResponse->category = "Linux";
                    $mockAdminDevices->shouldReceive('licenses->getById')->andReturn($mockedResponse);
                    return $mockAdminDevices;
                });

                $devicesAdminClient = app()->make(AdminClient::class);
                try {
                    $image->platform = $devicesAdminClient->licenses()->getById($applianceVersion->appliance_version_server_license_id)->category;
                } catch (\Exception $exception) {
                    $this->error('Failed to set platform for appliance version ' . $applianceVersion->id);
                }

                $image->active = ($applianceVersion->appliance_version_active == 'Yes');
                $image->public = true;
                $image->publisher = $appliance->appliance_publisher;

                $image->save();

                /**
                 * Insert image parameters
                 */

                // Prevent duplicates if we run the script multiple times
                $image->imageParameters->each(function ($imageParameter) {
                    $imageParameter->delete();
                });

                $applianceScriptParameters = $applianceVersion->applianceScriptParameters;

                $applianceScriptParameters->each(function ($applianceScriptParameter) use ($image) {
                    $imageParameter = app()->make(ImageParameter::class);
                    $imageParameter->fill([
                        'image_id' => $image->id,
                        'name' => $applianceScriptParameter->appliance_script_parameters_name,
                        'key' => $applianceScriptParameter->appliance_script_parameters_key,
                        'type' => $applianceScriptParameter->appliance_script_parameters_type,
                        'description' => $applianceScriptParameter->appliance_script_parameters_description,
                        'required' => ($applianceScriptParameter->appliance_script_parameters_required == 'Yes'),
                        'validation_rule' => $applianceScriptParameter->appliance_script_parameters_validation_rule,
                    ]);
                    $imageParameter->save();
                });
                $this->info('Added ' . $applianceScriptParameters->count() . ' image parameters');

                /**
                 * Update image metadata
                 */

                // Prevent duplicates if we run the script multiple times
                $image->imageMetadata->each(function ($imageMetadata) {
                    $imageMetadata->delete();
                });

                $applianceVersionData = $applianceVersion->applianceVersionData;

                $applianceVersionData->each(function ($data) use ($image) {
                    $imageMetadata = app()->make(ImageMetadata::class);
                    $imageMetadata->fill([
                        'image_id' => $image->id,
                        'key' => $data->key,
                        'value' => $data->value
                    ]);
                    $imageMetadata->save();
                });
                $this->info('Added ' . $applianceVersionData->count() . ' image metadata records');

                $this->info("Updated Image $image->id \"$image->name\"");

                // Update availability zones
                // WARNING: ADDS THE IMAGES TO ALL AVAILABILITY ZONES
                $image->availabilityZones()->sync(AvailabilityZone::all()->pluck('id'));

                $this->info("Image $image->id \"$image->name\" was added to all availability zones");
            }
        });

        return Command::SUCCESS;
    }
}
