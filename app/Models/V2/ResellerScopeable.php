<?php

namespace App\Models\V2;

interface ResellerScopeable
{
    public function getResellerId(): int;
}