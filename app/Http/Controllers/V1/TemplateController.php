<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\V1\SolutionNotFoundException;
use App\Exceptions\V1\TemplateNotFoundException;

use App\Models\V1\ServerLicense;
use App\Models\V1\Solution;
use App\Models\V1\Pod;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

use App\Services\IntapiService;
use App\Exceptions\V1\IntapiServiceException;

use UKFast\Api\Exceptions\ForbiddenException;
use App\Exceptions\V1\TemplateUpdateException;
use UKFast\Api\Exceptions\NotFoundException;
use UKFast\Api\Exceptions\UnauthorisedException;

/**
 * Class TemplateController
 * @package App\Http\Controllers\V1
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class TemplateController extends BaseController
{
    /**
     * List all Solution and Pod Templates
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $solutionTemplates = $this->getResellerSolutionTemplates();

        // List all Pod templates
        $podTemplates = $this->getResellerPodTemplates();

        $allTemplates = array_merge($solutionTemplates, $podTemplates);

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
     * Returns a template Item
     * @param Request $request
     * @param $templateName
     * @return \Illuminate\Http\Response
     * @throws TemplateNotFoundException
     */
    public function show(Request $request, $templateName)
    {
        $templateName = urldecode($templateName);

        // Check Solution Templates First
        $templates = $this->getResellerSolutionTemplates();

        $template = $this->findTemplateByName($templateName, $templates);

        if (!$template) {
            // Not found in Solution templates, check Pod templates
            $templates = $this->getResellerPodTemplates();
            $template = $this->findTemplateByName($templateName, $templates);
        }

        if (!$template) {
            throw new TemplateNotFoundException("A template matching the requested name '$templateName' was not found");
        }

        return $this->respondItem(
            $request,
            $template
        );
    }

    /**
     * @param Request $request
     * @param IntapiService $intapiService
     * @param $templateName
     * @return \Illuminate\Http\Response
     * @throws IntapiServiceException
     * @throws SolutionNotFoundException
     * @throws TemplateNotFoundException
     * @throws ForbiddenException
     * @throws TemplateUpdateException
     */
    public function renameTemplate(Request $request, IntapiService $intapiService, $templateName)
    {
        $templateName = urldecode($templateName);

        $this->validate($request, [
            'new_template_name' => [
                'required',
                'regex:/[A-Za-z0-9-_\ ]+/'
            ],
            'solution_id' => 'nullable|integer'
        ]);

        $newTemplateName = $request->input('new_template_name');

        // Check Solution Templates First
        $templates = $this->getResellerSolutionTemplates($request->input('solution_id', null));
        $template = $this->findTemplateByName($templateName, $templates);
        if (!$template) {
            //Check for system/pod templates
            if ($this->isAdmin) {
                $allPodtemplates = $this->getResellerPodTemplates(true);
                foreach ($allPodtemplates as $datacentreId => $templates) {
                    $template = $this->findTemplateByName($templateName, $templates);
                    if ($template !== false) {
                        //Add the datacentre id to the template
                        $template->datacentreId = $datacentreId;
                        break;
                    }
                }
            }
        }

        if (!$template) {
            throw new TemplateNotFoundException("A template matching the requested name '$templateName' was not found");
        }

        // Solution template?
        if (isset($template->solution_id)) {
            $solution = Solution::find($template->solution_id);
            if (!$solution) {
                throw new SolutionNotFoundException('Failed to load Solution information for template');
            }

            try {
                $intapiService->automationRequest(
                    'rename_template',
                    'ucs_reseller',
                    $template->solution_id,
                    [
                        'template_type' => 'solution',
                        'template_name' => $templateName,
                        'new_template_name' => $newTemplateName
                    ],
                    'ecloud_ucs_' . $solution->pod->getKey()
                );

                return $this->respondEmpty(202);
            } catch (IntapiServiceException $exception) {
                throw new TemplateUpdateException('Failed to schedule template rename');
            }
        }

        //Template is a System / Pod template
        // Check it isn't a UKFast base template
        if ($this->isUKFastBaseTemplate($template)) {
            throw new ForbiddenException('UKFast Base Templates can not be edited.');
        }
        try {
            $intapiService->automationRequest(
                'rename_template',
                'ucs_reseller',
                0,
                [
                    'template_type' => 'system',
                    'template_name' => $templateName,
                    'new_template_name' => $newTemplateName,
                    'datacentre_id' => $template->datacentreId
                ]
            );

            return $this->respondEmpty(202);
        } catch (IntapiServiceException $exception) {
            throw new TemplateUpdateException('Failed to schedule template rename');
        }
    }

    /**
     * Returns the templates for a Solution
     * @param Request $request
     * @param $solutionId
     * @return \Illuminate\Http\Response
     * @throws SolutionNotFoundException
     */
    public function solutionTemplates(Request $request, $solutionId)
    {
        $solutionQuery = Solution::withReseller($this->resellerId);
        if (!$this->isAdmin) {
            $solutionQuery->where('ucs_reseller_active', 'Yes');
        }
        $solution = $solutionQuery->find($solutionId);

        if (!$solution) {
            throw new SolutionNotFoundException('Solution ID #' . $solutionId . ' not found', 'solution_id');
        }

        $templates = $this->getTemplatesForSolution($solution, false);

        $collection = new Collection($templates);

        $paginator = new LengthAwarePaginator(
            $collection->slice(
                LengthAwarePaginator::resolveCurrentPage('page') - 1 * $this->perPage,
                $this->perPage
            )->all(),
            count($collection),
            $this->perPage
        );

        return $this->respondCollection(
            $request,
            $paginator
        );
    }

    /**
     * Delete a template
     * @param Request $request
     * @param IntapiService $intapiService
     * @param $templateName
     * @return \Illuminate\Http\Response
     * @throws ForbiddenException
     * @throws SolutionNotFoundException
     * @throws TemplateNotFoundException
     * @throws TemplateUpdateException
     */
    public function deleteTemplate(Request $request, IntapiService $intapiService, $templateName)
    {
        $templateName = urldecode($templateName);

        // Check Solution Templates First
        $templates = $this->getResellerSolutionTemplates();

        $template = $this->findTemplateByName($templateName, $templates);

        if (!$template) {
            // Not found in Solution templates, check Pod templates
            $templates = $this->getResellerPodTemplates(true);
            $template = $this->findTemplateByName($templateName, $templates);
        }

        if (!$template) {
            throw new TemplateNotFoundException("A template matching the requested name '$templateName' was not found");
        }

        // Are we deleting a Solution template?
        if (isset($template->solution_id)) {
            $solution = Solution::find($template->solution_id);
            if (!$solution) {
                throw new SolutionNotFoundException('Failed to load Solution information for template');
            }
            //delete the solution template
            try {
                $intapiService->automationRequest(
                    'delete_template',
                    'ucs_reseller',
                    $template->solution_id,
                    [
                        'template_type' => 'solution',
                        'template_name' => $templateName,
                    ],
                    'ecloud_ucs_' . $solution->pod->getKey()
                );

                return $this->respondEmpty(202);
            } catch (IntapiServiceException $exception) {
                throw new TemplateUpdateException('Failed to schedule template deletion');
            }
        }


        //If it's a system template, check it's not a ukfast base template
        if ($this->isUKFastBaseTemplate($template)) {
            throw new ForbiddenException('UKFast Base Templates can not be deleted.');
        }

        //delete the pod template
        try {
            $intapiService->automationRequest(
                'delete_template',
                'ucs_reseller',
                0,
                [
                    'template_type' => 'system',
                    'template_name' => $templateName,
                    'datacentre_id' => $template->datacentreId
                ]
            );

            return $this->respondEmpty(202);
        } catch (IntapiServiceException $exception) {
            throw new TemplateUpdateException('Failed to schedule template deletion');
        }
    }


    //-------------------------------------------------


    /**
     * Formats the Solution and Pod templates
     * @param $template
     * @param null $serverLicense
     * @return \stdClass
     */
    protected function convertToPublicTemplate($template, $serverLicense = null)
    {
        $tmp_template = new \stdClass;
        $tmp_template->type = $template->type;
        $tmp_template->name = $template->name;

        $tmp_template->cpu = $template->cpu;
        $tmp_template->ram = $template->ram;
        $tmp_template->hdd = $template->size_gb;

        foreach ($template->hard_drives as $hard_drive) {
            $tmp_template->hdd_disks[] = (object)array(
                'name' => $hard_drive->name,
                'capacity' => $hard_drive->capacitygb,
            );
        }

        if (!empty($serverLicense)) {
            $tmp_template->platform = $serverLicense->category;
            $tmp_template->license = $serverLicense->name;
            $tmp_template->operating_system = $serverLicense->friendly_name;
        } else {
            $tmp_template->license = 'Unknown';
            $tmp_template->operating_system = $template->guest_os;
        }

        //Add the solution_id for Solution templates
        if (isset($template->solution_id)) {
            $tmp_template->solution_id = $template->solution_id;
        }

        return $tmp_template;
    }


    /**
     * Loop through and get the templates for ALL the reseller's solutions.
     * @param null $solutionId
     * @return array
     */
    protected function getResellerSolutionTemplates($solutionId = null)
    {
        $templates = [];
        $solutionQuery = Solution::query();
        if (!$this->isAdmin) {
            // get the resellers solutions
            $solutionQuery = $solutionQuery->withReseller($this->resellerId);
            $solutionQuery->where('ucs_reseller_active', 'Yes');
        }

        if (!empty($solutionId)) {
            $solutionQuery->where('ucs_reseller_id', '=', $solutionId);
        }

        // Get the resellers's Solution's templates
        if ($solutionQuery->count() > 0) {
            foreach ($solutionQuery->get() as $solution) {
                $result = $this->getTemplatesForSolution($solution);
                if ($result !== false) {
                    $templates = array_merge($templates, $result);
                }
            }
        }

        return $templates;
    }


    /**
     * Get Pod templates - Loops through the pods for each of the resellers solutions and
     * gets the default templates available in that pod.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @return array
     */
    protected function getResellerPodTemplates($withDatacentre = false)
    {
        $allTemplates = [];

        //Loop through all the Datacentres and get all templates from each pod
        $solutionsQuery = Solution::query();
        if (!$this->isAdmin) {
            $solutionsQuery->withReseller($this->resellerId);
            $solutionsQuery->where('ucs_reseller_active', 'Yes');
        }

        if ($solutionsQuery->count() < 1) {
            return $allTemplates;
        }

        $datacentres = [];

        //Collate a list of unique datacentres
        foreach ($solutionsQuery->get() as $solution) {
            if (!empty($solution->pod)) {
                $datacentres[$solution->pod->getKey()] = $solution->pod;
            }
        }

        // Get the templates for each pod
        foreach ($datacentres as $pod) {
            $templates = $this->getTemplatesForPod($pod);
            if (!empty($templates)) {
                if ($withDatacentre) {
                    $allTemplates[$pod->getKey()] = $templates;
                    continue;
                }
                $allTemplates = array_merge($allTemplates, $templates);
            }
        }

        return $allTemplates;
    }

    /**
     * Get the templates for a specific Solution
     * @param Solution $solution
     * @param bool $appendSolutionId
     * @return array|bool
     */
    protected function getTemplatesForSolution(Solution $solution, $appendSolutionId = true)
    {
        $templates = [];
        try {
            $kingpin = app()->makeWith('App\Kingpin\V1\KingpinService', [$solution->pod]);
        } catch (\Exception $exception) {
            //Failed to connect to Kingpin
            return $templates;
        }

        $result = $kingpin->getSolutionTemplates($solution->getKey());
        if (empty($result) || !is_array($result)) {
            //Failed to retrieve templates or empty array
            return $templates;
        }

        //Add the solution_id to the template data
        foreach ($result as &$template) {
            $template->type = 'Solution';

            if ($appendSolutionId) {
                $template->solution_id = $solution->getKey();
            }
            // Check the template license
            $serverLicense = ServerLicense::checkTemplateLicense($solution->pod->getKey(), $template);
            //Convert to public format
            $templates[] = $this->convertToPublicTemplate($template, $serverLicense);
        }

        return $templates;
    }


    /**
     * Get the default templates for this pod/datacentre
     * @param Pod $pod
     * @return array
     */
    protected function getTemplatesForPod(Pod $pod)
    {
        $templates = [];
        try {
            $kingpin = app()->makeWith('App\Kingpin\V1\KingpinService', [$pod]);
        } catch (\Exception $exception) {
            //Failed to connect to Kingpin
            return $templates;
        }

        $result = $kingpin->getPodTemplates();

        if (!$result || !is_array($result)) {
            return $templates;
        }

        foreach ($result as &$template) {
            $serverLicense = ServerLicense::checkTemplateLicense($pod->getKey(), $template);

            if ($template->name == $serverLicense->name) {
                $template->type = 'Base';
            } else {
                $template->type = 'Pod';
            }

            $template = $this->convertToPublicTemplate($template, $serverLicense);
        }

        return $result;
    }

    public static function getTemplateByName(string $name, Pod $pod, Solution $solution = null)
    {
        $request = app('request');
        $TemplateController = new static($request);

        if (!is_null($solution)) {
            $templates = $TemplateController->getTemplatesForSolution($solution, false);
            if (is_array($templates) and count($templates) > 0) {
                $template = $TemplateController->findTemplateByName($name, $templates);
                if ($template) {
                    return $template;
                }
            }
        }

        // load pod templates
        $templates = $TemplateController->getResellerPodTemplates(true)[$pod->getKey()];
        if (is_array($templates) and count($templates) > 0) {
            $template = $TemplateController->findTemplateByName($name, $templates);
            if ($template) {
                return $template;
            }
        }

        throw new TemplateNotFoundException("A template matching the requested name was not found");
    }


    /**
     * Find a template by it's name
     * @param $name
     * @param $objects
     * @return bool
     */
    protected function findTemplateByName($name, $objects)
    {
        foreach ($objects as $object) {
            if ($object->name == $name) {
                return $object;
            }
        }

        return false;
    }


    /**
     * Is the template a UKFast template?
     * @param $template
     * @return bool
     */
    protected function isUKFastBaseTemplate($template)
    {
        // Check against available server licenses
        $availableEcloudLicenses = ServerLicense::availableToInstall(
            'ecloud vm',
            true,
            'OS',
            null // Datacentre
        );

        foreach ($availableEcloudLicenses as $UKFastTemplates) {
            if ($UKFastTemplates->server_license_name == $template->name) {
                return true;
            }
        }

        return false;
    }
}
