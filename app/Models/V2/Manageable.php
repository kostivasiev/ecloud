<?php

namespace App\Models\V2;

interface Manageable
{
    public function isManaged() :bool;

    public function isHidden() :bool;
}
