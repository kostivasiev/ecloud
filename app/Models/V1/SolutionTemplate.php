<?php

namespace App\Models\V1;

use App\Exceptions\V1\TemplateNotFoundException;
use Log;

class SolutionTemplate
{
    /**
     * Retrieve a Solution template from a Solution by name
     * @param Solution $solution
     * @param $templateName
     * @return bool|object
     * @throws TemplateNotFoundException
     */
    public static function withName(Solution $solution, $templateName)
    {
        try {
            $kingpin = app()->makeWith(
                'App\Services\Kingpin\V1\KingpinService',
                [
                    $solution->pod,
                    $solution->ucs_reseller_type
                ]
            );
        } catch (\Exception $exception) {
            //Failed to connect to Kingpin
            Log::error(
                'Searching for solution template by name - Failed to connect to Kingpin: ' . $exception->getMessage(),
                [
                    'solution_id' => $solution->getKey(),
                    'template_name' => $templateName,
                    'pod' => $solution->pod
                ]
            );
            return false;
        }

        $template = $kingpin->getSolutionTemplate($solution, $templateName);


        if (empty($template)) {
            Log::info(
                'Search for solution template by name did not return any results.',
                [
                    'solution_id' => $solution->getKey(),
                    'template_name' => $templateName,
                    'pod' => $solution->pod
                ]
            );

            throw new TemplateNotFoundException("A template matching the requested name '$templateName' was not found");
        }

        return $template;
    }

    /**
     * Get the templates for a specific Solution
     *
     * @param Solution $solution
     * @return array|bool
     */
    public static function withSolution(Solution $solution)
    {
        $templates = [];
        try {
            $kingpin = app()->makeWith(
                'App\Services\Kingpin\V1\KingpinService',
                [
                    $solution->pod,
                    $solution->ucs_reseller_type
                ]
            );
        } catch (\Exception $exception) {
            //Failed to connect to Kingpin
            return $templates;
        }

        $result = $kingpin->getSolutionTemplates($solution);
        if (empty($result) || !is_array($result)) {
            //Failed to retrieve templates or empty array
            return $templates;
        }

        //Add the solution_id to the template data
        foreach ($result as $template) {
            $templates[] = $template;
        }

        return $templates;
    }
}
