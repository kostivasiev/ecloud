<?php

namespace App\Traits\V1;

use Illuminate\Support\Str;

/**
 * Trait ColumnPrefix
 *
 * Modifies Model attribute getter / setter methods to account for table's where
 * column names are prefixed with the table name.
 *
 * e.g. foobar.foobar_id becomes $foobar->id instead of $foobar->foobar_id
 *
 * Works for both accessing and setting model attributes.
 *
 */
trait ColumnPrefixHelper
{
    /**
     * @var bool
     */
    protected $prefixOnGet = true;

    /**
     * @var bool
     */
    protected $prefixOnSet = true;

    public function getAttribute($key)
    {
        if (!Str::startsWith($key, $this->table) && $this->prefixOnGet) {
            $prefixed = "{$this->table}_$key";
            if (array_key_exists($prefixed, $this->attributes) || $this->hasGetMutator($prefixed)) {
                return $this->getAttributeValue($prefixed);
            }
        }

        return parent::getAttribute($key);
    }

    public function setAttribute($key, $value)
    {
        if (!Str::startsWith($key, $this->table) && $this->prefixOnSet) {
            $key = "{$this->table}_$key";
        }

        return parent::setAttribute($key, $value);
    }
}
