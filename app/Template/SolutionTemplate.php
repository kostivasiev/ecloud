<?php

namespace App\Template;

use App\Models\V1\Pod;
use App\Models\V1\Solution;

class SolutionTemplate extends AbstractTemplate
{
    public $solution;

    public function __construct($template, Pod $pod, Solution $solution)
    {
        parent::__construct($template, $pod);

        $this->type = 'Solution';
        $this->solution = $solution;
        $this->pod = $solution->pod;
        $this->serverLicense = $this->getServerLicense();
    }

    /**
     * Formats the Solution and Pod templates for return by the API
     * @return \stdClass
     */
    public function convertToPublicTemplate()
    {
        $tmp_template = parent::convertToPublicTemplate();

        //Add the solution_id for Solution templates
        $tmp_template->solution_id = $this->solution->getKey();

        return $tmp_template;
    }
}
