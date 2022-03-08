<?php
namespace App\Services\V1\Resource\Property;

class BooleanProperty extends AbstractProperty
{
    /**
     * The database representation of a true value
     * @var string
     */
    protected $truthy;

    /**
     * The database representation of a false value
     * @var string
     */
    protected $falsey;

    /**
     * BooleanProperty constructor.
    * @param string $databaseName
     * @param string $friendlyName
     * @param mixed $value
     * @param null|string $truthy
     * @param null|string $falsey
     */
    public function __construct($databaseName, $friendlyName, $value = null, $truthy = null, $falsey = null)
    {
        parent::__construct($databaseName, $friendlyName, $value);

        if (!is_null($truthy)) {
            $this->truthy = $truthy;
        }

        if (!is_null($falsey)) {
            $this->falsey = $falsey;
        }
    }

    /**
     * Returns a friendly formatted property
     * @return mixed
     */
    public function serialize()
    {
        switch (true) {
            case (is_null($this->value) || ($this->value === '')):
                break;

            case ($this->value === $this->truthy):
                $this->value = true;
                break;

            case ($this->value === $this->falsey):
                $this->value = false;
                break;

            default:
                $boolean = filter_var($this->value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if (is_null($boolean)) {
                    break;
                }
                $this->value = $boolean;
                break;
        }
        return $this->value;
    }

    /**
     * Returns a friendly formatted property
     * @return mixed
     */
    public function deserialize()
    {
        $boolean = filter_var($this->value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($this->value !== null && $this->value !== '' && ($boolean === true || $boolean === false)) {
            $this->value =  $boolean;
        }

        switch (true) {
            case (is_null($this->value) || ($this->value === '')):
                break;

            case ($this->value === false):
                $this->value = $this->falsey;
                break;

            case ($this->value === true):
                $this->value = $this->truthy;
                break;

            default:
                break;
        }
        return $this->value;
    }

    /**
     * Statically create an instance of itself
     * @param string $databaseName
     * @param string $friendlyName
     * @param mixed $value
     * @param null|string $truthy
     * @param null|string $falsey
     * @return static
     */
    public static function create($databaseName, $friendlyName, $value = null, $truthy = null, $falsey = null)
    {
        return new static($databaseName, $friendlyName, $value, $truthy, $falsey);
    }
}
