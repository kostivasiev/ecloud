<?php

namespace App\Http\Controllers\V1;

use App\Models\V1\ServerLicense;
use Illuminate\Http\Request;
use App\Models\V1\Solution;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class TemplateController
 * @package App\Http\Controllers\V1
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class TemplateController extends BaseController
{
    /**
     * List all Solution and System Templates
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $solutionTemplates = $this->getSolutionTemplates();

        // List all system templates
        $systemTemplates = $this->getSystemTemplates();

        $allTemplates = array_merge($solutionTemplates, $systemTemplates);

        $collection = new Collection($allTemplates);

        $paginator = new LengthAwarePaginator(
            $collection->slice(
                LengthAwarePaginator::resolveCurrentPage('page') - 1 * $this->perPage,
                $this->perPage
            )->all(),
            count($collection),
            $this->perPage
        );

        $paginator->setPath($request->root() . '/' . $request->path());

        return $this->respondCollection(
            $request,
            $paginator
        );
    }


    /**
     * Formats the Solution and System templates
     * @param $template
     * @param null $serverLicense
     * @return \stdClass
     */
    protected function convertToPublicTemplate($template, $serverLicense = null)
    {
        $tmp_template = new \stdClass;
        $tmp_template->name = $template->name;
        $tmp_template->operating_system = $template->guest_os;

        if (!empty($serverLicense)) {
            $tmp_template->operating_system = $serverLicense->friendly_name;
            $tmp_template->platform = $serverLicense->category;
        }

        $tmp_template->cpu = $template->cpu;
        $tmp_template->ram_gb = $template->ram;
        $tmp_template->hdd_gb = $template->size_gb;

        foreach ($template->hard_drives as $hard_drive) {
            $tmp_template->hard_drives[] = (object)array(
                'name' => $hard_drive->name,
                'capacity' => $hard_drive->capacitygb,
            );
        }

        //Add the solution_id for Solution templates
        if (isset($template->solution_id)) {
            $tmp_template->solution_id = $template->solution_id;
        }

        return $tmp_template;
    }


    /**
     * Get Solution specific Templates
     * @return array
     */
    protected function getSolutionTemplates()
    {
        $solutionQuery = Solution::query();
        if (!$this->isAdmin) {
            // get the resellers solutions
            $solutionQuery = Solution::withReseller($this->resellerId);
            $solutionQuery->where('ucs_reseller_active', 'Yes');
        }

        $templates = [];

        // Get the resellers's Solution's templates
        if ($solutionQuery->count() > 0) {
            foreach ($solutionQuery->get() as $solution) {
                // We need to load a Kingpin service with the datacentre for the solution
                try {
                    $kingpin = app()->makeWith('App\Kingpin\V1\KingpinService', [$solution->pod]);
                } catch (\Exception $exception) {
                    //Failed to connect to Kingpin
                    continue;
                }

                $result = $kingpin->getSolutionTemplates($solution->getKey());
                if (empty($result) || !is_array($result)) {
                    //Failed to retrieve templates or empty array
                    continue;
                }

                //Add the solution_id to the template data
                foreach ($result as &$template) {
                    $template->solution_id = $solution->getKey();
                    // Check the template license
                    $serverLicense = ServerLicense::checkTemplateLicense($solution->pod->getKey(), $template);
                    //Convert to public format
                    $template = $this->convertToPublicTemplate($template, $serverLicense);
                }

                $templates = array_merge($templates, $result);
            }
        }
        return $templates;
    }


    /**
     * Get system templates - Loops through the pods for each of the resellers solutions
     * and extracts the system templates from each.
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @return array
     */
    protected function getSystemTemplates()
    {
        $allTemplates = [];

        //Loop through all the Datacentres and get all system templates from each pods
        $solutionsQuery = Solution::query();
        if (!$this->isAdmin) {
            $solutionsQuery->withReseller($this->resellerId);
            $solutionsQuery->where('ucs_reseller_active', 'Yes');
        }

        if ($solutionsQuery->count() < 1) {
            return $allTemplates;
        }

        $datacentres = [];

        foreach ($solutionsQuery->get() as $solution) {
            if (!empty($solution->pod)) {
                $datacentres[$solution->pod->getKey()] = $solution->pod;
            }
        }

        // Get the system templates for each pod
        foreach ($datacentres as $pod) {
            try {
                $kingpin = app()->makeWith('App\Kingpin\V1\KingpinService', [$pod]);
            } catch (\Exception $exception) {
                //Failed to connect to Kingpin
                continue;
            }

            $result = $kingpin->getSystemTemplates();

            if (empty($result) || !is_array($result)) {
                continue;
            }

            $templates = $result;

            //===========
            // Check against available server licenses
//            $availableEcloudLicenses = ServerLicense::availableToInstall(
//                'ecloud vm',
//                true,
//                'OS',
//                $pod->getKey()
//            );

            foreach ($templates as &$template) {
                $serverLicense = ServerLicense::checkTemplateLicense($pod->getKey(), $template);
                // TODO: Need to double check we need this bit of code, or just return all templates
                //need to filter UKFast templates
//                if ($this->findTemplateByName($template->name, $availableEcloudLicenses) !== false) {
//                    continue; //skip and dont show to customer
//                }
//
//                if (!empty($serverLicense->name)) {
//                    if ($this->findTemplateByName($serverLicense->name, $availableEcloudLicenses) === false) {
//                        continue; //skip and dont show to customer
//                    }
//                }

                $template = $this->convertToPublicTemplate($template, $serverLicense);
            }
            //===========

            $allTemplates = array_merge($allTemplates, $templates);
        }

        return $allTemplates;
    }

    /**
     * Find a template by it's name
     * @param $value
     * @param $objects
     * @return bool
     */
    protected function findTemplateByName($value, $objects)
    {
        foreach ($objects as $object) {
            if ($object->name == $value) {
                return $object;
            }
        }

        return false;
    }
}
