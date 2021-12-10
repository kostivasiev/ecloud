<?php

namespace App\Rules\V2\Instance;

use App\Models\V2\Image;
use App\Models\V2\Software;
use Illuminate\Contracts\Validation\Rule;

class SoftwarePlatformMatchesImagePlatform implements Rule
{
    public string $imageId;

    public function __construct(string $imageId)
    {
        $this->imageId = $imageId;
    }

    public function passes($attribute, $value)
    {
        $image = Image::find($this->imageId);
        $software = Software::find($value);

        return $image->platform == $software->platform;
    }

    public function message()
    {
        return 'Software platform does not match image platform';
    }
}
