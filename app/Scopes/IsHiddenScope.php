<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class IsHiddenScope implements Scope
{
    public function apply(Builder $query, Model $model)
    {
        if (!Auth::user()->isScoped()) {
            return $query;
        }

        return $query->whereHas($model::$hiddenBy, function ($query) {
            $query->where('is_hidden', false);
        });
    }
}
