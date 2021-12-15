<?php

namespace Database\Seeders\Software;

use App\Models\V2\Script;
use App\Models\V2\Software;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use function app;

class McafeeLinuxSoftwareSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            'name' => 'McAfee Antivirus',
            'visibility' => Software::VISIBILITY_PUBLIC,
            'platform' => Software::PLATFORM_LINUX
        ];

        if (app()->environment() != 'production') {
            $data['id'] = 'soft-mcafee-' . strtolower(Software::PLATFORM_LINUX);
        }

        $software = Software::factory()->create($data);

        // TODO: add real scripts
        Script::factory()
            ->count(2)
            ->state(new Sequence(
                [
                    'name' => 'Install',
                    'sequence' => 1,
                    'script' => 'exit 0'
                ],
                [
                    'name' => 'Readiness',
                    'sequence' => 2,
                    'script' => 'exit 0'
                ],
            ))
            ->create([
                'software_id' => $software->id,
            ]);
    }
}
