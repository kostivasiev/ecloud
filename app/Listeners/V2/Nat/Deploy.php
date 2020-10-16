<?php

namespace App\Listeners\V2\Nat;

use App\Events\V2\Nat\Saved;

class Deploy
{
    public function handle(Saved $event)
    {
        $nat = $event->model;
        if ($nat->destination != $nat->getOriginal('destination') || $nat->translated != $nat->getOriginal('translated')) {
            dispatch(new \App\Jobs\Nat\Deploy([
                'nat_id' => $nat->id,
                'original_destination' => $nat->getOriginal('destination'),
                'original_translated' => $nat->getOriginal('translated'),
            ]));
        }
    }
}
