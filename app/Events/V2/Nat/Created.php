<?php

namespace App\Events\V2\Nat;

use App\Models\V2\Nat;
use Illuminate\Queue\SerializesModels;

class Created
{
    use SerializesModels;

    /**
     * @var Nat
     */
    public $nat;

    /**
     * @param Nat $nat
     */
    public function __construct(Nat $nat)
    {
        $this->nat = $nat;
    }
}
