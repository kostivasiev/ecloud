<?php

namespace App\Listeners\V2\Nat;

use App\Events\V2\Nat\Saved;

class Deploy
{
    public function handle(Saved $event)
    {
        $nat = $event->model;
        if ($nat->destination_id != $nat->getOriginal('destination_id') || $nat->translated_id != $nat->getOriginal('translated_id')) {
            dispatch(new \App\Jobs\Nat\Deploy([
                'nat_id' => $nat->id
            ]));
        }
    }
}
