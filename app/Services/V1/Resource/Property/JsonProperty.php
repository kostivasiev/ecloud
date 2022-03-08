<?php
namespace App\Services\V1\Resource\Property;

class JsonProperty extends AbstractProperty
{
    /**
     * IntProperty constructor.
     * @param $databaseName
     * @param $friendlyName
     * @param $value
     */
    public function __construct($databaseName, $friendlyName, $value = null)
    {
        parent::__construct($databaseName, $friendlyName, $value);
    }

    /**
     * Returns a friendly formatted property
     * @return null|int
     */
    public function serialize()
    {
        return json_decode($this->value);
    }

    /**
     * Returns a database formatted property
     * @return null|int
     */
    public function deserialize()
    {
        return json_encode($this->value);
    }

    /**
     * Statically create an instance of itself
     * @param $databaseName
     * @param $friendlyName
     * @param $value
     * @return static
     */
    public static function create($databaseName, $friendlyName, $value = null)
    {
        return new static($databaseName, $friendlyName, $value);
    }
}
