<?php

namespace App\Traits\V1;

use Ramsey\Uuid\Uuid;

/**
 * Trait UUIDHelper
 *
 * UUIDHelper related Model functionality
 *
 */
trait UUIDHelper
{
    /**
     * Create and save UUID on saving a new record
     * @param array $options
     * @return bool
     * @throws \Exception
     */
    public function save(array $options = [])
    {
        $uuidColumnName = $this->getUuidColumnName();

        if (empty($this->{$uuidColumnName})) {
            $this->{$uuidColumnName} = Uuid::uuid4()->toString();
        }

        return parent::save($options);
    }


    /**
     * Scope a query by UUID
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $uuid
     * @return \Illuminate\Database\Eloquent\Builder $query
     *
     */
    public function scopeWithUuid($query, $uuid)
    {
        $uuidColumnName = $this->getUuidColumnName();
        // Suppress codesniffer warning as we're using using PDO prepared statements in the background anyway
        //@codingStandardsIgnoreStart
        $query->where($uuidColumnName, '=', $uuid);
        //@codingStandardsIgnoreEnd
        return $query;
    }

    /**
     * Determine the table's UUID column
     *
     * Note: Assumes table_name.table_name_uuid unless told otherwise
     * @return string
     */
    protected function getUuidColumnName()
    {
        return (isset($this->uuidColumn) ? $this->uuidColumn :  $this->table . "_uuid");
    }

    /**
     * Get the value of the UUID column
     */
    public function getUuid()
    {
        $uuidColumnName = $this->getUuidColumnName();
        return $this->{$uuidColumnName};
    }
}
