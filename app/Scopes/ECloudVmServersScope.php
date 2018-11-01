<?php
namespace App\Scopes;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Scope any queries to ecloud vm's only
 * Class ecloudServersScope
 * @package App\Scopes
 */
class ECloudVmServersScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->where('servers_type', '=', 'ecloud vm');
    }
}
