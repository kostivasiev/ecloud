<?php

namespace App\Traits\V2;

use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;

trait Dittoable
{
    /**
     * DO NOT USE
     * This is a POC to move the Ditto model methods into a trait. Do not use yet.
     *
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory): array
    {
        $columns = [];
        collect($this->attributesToArray())->keys()->each(function ($key) use (&$columns, $factory) {
            if ($key == 'id') {
                $columns[] = $factory->create($key, Filter::$enumDefaults);
                return;
            }

            if ($this->isDateAttribute($key) || $this->isDateCastable($key)) {
                $columns[] = $factory->create($key, Filter::$dateDefaults);
                return;
            }

            $type = 'string';
            if ($this->hasCast($key)) {
                $type = $this->getCastType($key);
            }

            switch ($type) {
                case 'bool':
                case 'boolean':
                    $columns[] = $factory->create($key, Filter::$enumDefaults);
                    break;
                case 'custom_datetime':
                case 'date':
                case 'datetime':
                case 'timestamp':
                    $columns[] = $factory->create($key, Filter::$dateDefaults);
                    break;
                case 'decimal':
                case 'double':
                case 'float':
                case 'int':
                case 'integer':
                case 'real':
                    $columns[] = $factory->create($key, Filter::$numericDefaults);
                    break;
                default:
                    $columns[] = $factory->create($key, Filter::$stringDefaults);
            }
        });

        return $columns;
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort[]
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns(SortFactory $factory): array
    {
        $columns = [];
        collect($this->attributesToArray())->keys()->each(function ($key) use (&$columns, $factory) {
            $columns[] = $factory->create($key);
        });
        return $columns;
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort|\UKFast\DB\Ditto\Sort[]|null
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function defaultSort(SortFactory $factory): array
    {
        return [
            $factory->create('created_at', 'desc'),
        ];
    }

    /**
     * TODO: this does not work as attributes are not set when we new up a new model in the queryTransformer
     * @return array
     */
    public function databaseNames()
    {
        $columns = [];
        collect($this->attributesToArray())->keys()->each(function ($item) use (&$columns) {
            $columns[$item] = $item;
        });
        return $columns;
    }
}
