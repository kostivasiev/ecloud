<?php

namespace App\Models\V1;

class DrsRule
{
    protected $uuid;

    protected $name;

    protected $ruleType;

    protected $enabled;

    protected $hostGroup;

    protected $vmGroup;

    protected $vmsInRule;

    /**
     * DrsRule constructor.
     * @param $drsRule
     */
    public function __construct($drsRule)
    {
        $this->uuid = $drsRule->uuid;
        $this->name = $drsRule->name;
        $this->ruleType = $drsRule->type;
        $this->enabled = $drsRule->enabled;
        $this->hostGroup = $drsRule->host;
        $this->vmGroup = $drsRule->vmGroup;
        $this->vmsInRule = $drsRule->vmsInRule ?? [];
    }

    /**
     * @return mixed
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @param mixed $uuid
     */
    public function setUuid($uuid): void
    {
        $this->uuid = $uuid;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getRuleType()
    {
        return $this->ruleType;
    }

    /**
     * @param mixed $ruleType
     */
    public function setRuleType($ruleType): void
    {
        $this->ruleType = $ruleType;
    }

    /**
     * @return mixed
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param mixed $enabled
     */
    public function setEnabled($enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * @return mixed
     */
    public function getHostGroup()
    {
        return $this->hostGroup;
    }

    /**
     * @param mixed $hostGroup
     */
    public function setHostGroup($hostGroup): void
    {
        $this->hostGroup = $hostGroup;
    }

    /**
     * @return mixed
     */
    public function getVmGroup()
    {
        return $this->vmGroup;
    }

    /**
     * @param mixed $vmGroup
     */
    public function setVmGroup($vmGroup): void
    {
        $this->vmGroup = $vmGroup;
    }

    /**
     * @return mixed
     */
    public function getVmsInRule()
    {
        return $this->vmsInRule;
    }

    /**
     * @param mixed $vmsInRule
     */
    public function setVmsInRule($vmsInRule): void
    {
        $this->vmsInRule = $vmsInRule;
    }
}
