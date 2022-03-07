<?php
namespace App\Services\V1\Resource\Property;

abstract class AbstractProperty
{
    /**
     * Database Field Name
     * @var string
     */
    protected $databaseName;

    /**
     * External Name
     * @var string
     */
    protected $friendlyName;

    /**
     * The property value
     * @var mixed
     */
    protected $value;

    /**
     * Master constructor.
     * @param $databaseName
     * @param $friendlyName
     * @param $value
     */
    public function __construct($databaseName, $friendlyName, $value)
    {
        $this->databaseName = $databaseName;
        $this->friendlyName = $friendlyName;
        $this->value = $value;
    }

    /**
     * Returns a friendly formatted property
     * @return mixed
     */
    abstract public function serialize();

    /**
     * Returns a database formatted property
     * @return mixed
     */
    abstract public function deserialize();

    /**
     * Get the friendly name
     * @return string
     */
    public function getFriendlyName()
    {
        return $this->friendlyName;
    }

    /**
     * Get the database name
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->databaseName;
    }


    /**
     * Get the value
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the value
     * @param $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Statically create a property type
     * @param $databaseName
     * @param $friendlyName
     * @param $value
     * @return mixed
     */
    abstract public static function create($databaseName, $friendlyName, $value = null);
}
