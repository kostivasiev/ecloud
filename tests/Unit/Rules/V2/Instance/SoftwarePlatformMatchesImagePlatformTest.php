<?php

namespace Tests\Unit\Rules\V2\Instance;

use App\Models\V2\Image;
use App\Rules\V2\Instance\SoftwarePlatformMatchesImagePlatform;
use Database\Seeders\SoftwareSeeder;
use Tests\TestCase;

class SoftwarePlatformMatchesImagePlatformTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        (new SoftwareSeeder())->run();
    }

    public function testSamePlatformPasses()
    {
        $rule = new SoftwarePlatformMatchesImagePlatform($this->image()->id);

        $this->assertTrue($rule->passes('software_ids', 'soft-aaaaaaaa'));

    }

    public function testDifferentPlatformFails()
    {
        $this->image()->setAttribute('platform', Image::PLATFORM_WINDOWS)->save();

        $rule = new SoftwarePlatformMatchesImagePlatform($this->image()->id);

        $this->assertFalse($rule->passes('software_ids', 'soft-aaaaaaaa'));
    }
}
