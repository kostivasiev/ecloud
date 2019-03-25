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
    const TEMPLATE_NAME_FORMAT_REGEX = '^[A-Za-z0-9-_\ ]+$';

    /**
     * Returns a Solution template Item
     *
     * @param Request $request
     * @param $solutionId
     * @param $templateName
     * @return \Illuminate\Http\Response
     * @throws TemplateNotFoundException
     */

    public function show(Request $request, $solutionId, $templateName)
    {
        $templateName = urldecode($templateName);

        $templates = $this->getResellerSolutionTemplates($solutionId);

        $template = $this->findTemplateByName($templateName, $templates);

        if (!$template) {
            throw new TemplateNotFoundException("A template matching the requested name '$templateName' was not found");
        }

        return $this->respondItem(
            $request,
            $this->filterAdminProperties($request, $template)
        );
    }

    /**
     * Returns a Pod template item
     *
     * @param Request $request
     * @param $podId
     * @param $templateName
     * @return \Illuminate\Http\Response
     * @throws TemplateNotFoundException
     * @throws \App\Exceptions\V1\PodNotFoundException
     */
    public function showPodTemplate(Request $request, $podId, $templateName)
    {
        $templateName = urldecode($templateName);

        $pod = PodController::getPodById($request, $podId);

        $template = static::getPodTemplateByName($pod, $templateName);

        if (!$template) {
            throw new TemplateNotFoundException("A template matching the requested name '$templateName' was not found");
        }

        return $this->respondItem(
            $request,
            $this->filterAdminProperties($request, $template)
        );
    }

    /**
     * Get templates for a Pod
     *
     * @param Request $request
     * @param $podId
     * @return \Illuminate\Http\Response
     * @throws \App\Exceptions\V1\PodNotFoundException
     */
    public function podTemplates(Request $request, $podId)
    {
        $pod = PodController::getPodById($request, $podId);

        $templates = $this->filterAdminProperties($request, $this->getPodTemplates($pod));

        return $this->respondCollection(
            $request,
            $this->paginateTemplateData($templates)
        );
    }

    /**
     * Rename / move a Solution template
     *
     * @param Request $request
     * @param IntapiService $intapiService
     * @param $solutionId
     * @param $templateName
     * @return \Illuminate\Http\Response
     * @throws SolutionNotFoundException
     * @throws TemplateNotFoundException
     * @throws TemplateUpdateException
     */
    public function renameSolutionTemplate(Request $request, IntapiService $intapiService, $solutionId, $templateName)
    {
        $templateName = urldecode($templateName);

        $this->validate($request, ['destination' => ['required', 'regex:/[A-Za-z0-9-_\ ]+/']]);

        $newTemplateName = $request->input('destination');

        $templates = $this->getResellerSolutionTemplates($solutionId);

        $template = $this->findTemplateByName($templateName, $templates);

        if (empty($template)) {
            throw new TemplateNotFoundException("A template matching the requested name '$templateName' was not found");
        }

        $solution = SolutionController::getSolutionById($request, $solutionId);

        try {
            $intapiService->automationRequest(
                'rename_template',
                'ucs_reseller',
                $template->solution_id,
                [
                    'template_type' => 'solution',
                    'template_name' => $template->name,
                    'new_template_name' => $newTemplateName
                ],
                'ecloud_ucs_' . $solution->pod->getKey()
            );

            return $this->respondEmpty(202);
        } catch (IntapiServiceException $exception) {
            throw new TemplateUpdateException('Failed to schedule template rename');
        }
    }


    /**
     * Rename / move a Pod template
     *
     * @param Request $request
     * @param IntapiService $intapiService
     * @param $podId
     * @param $templateName
     * @return \Illuminate\Http\Response
     * @throws ForbiddenException
     * @throws TemplateNotFoundException
     * @throws TemplateUpdateException
     * @throws \App\Exceptions\V1\PodNotFoundException
     */
    public function renamePodTemplate(Request $request, IntapiService $intapiService, $podId, $templateName)
    {
        $templateName = urldecode($templateName);

        $this->validate($request, ['destination' => ['required', 'regex:/[A-Za-z0-9-_\ ]+/']]);

        $newTemplateName = $request->input('destination');

        $pod = PodController::getPodById($request, $podId);

        $templates = $this->getPodTemplates($pod);

        $template = $this->findTemplateByName($templateName, $templates);

        if (empty($template)) {
            throw new TemplateNotFoundException("A template matching the requested name '$templateName' was not found");
        }

        // Dont allow renaming of UKFast managed base templates
        if ($template->type == 'Base' || $this->isUKFastBaseTemplate($template)) {
            throw new ForbiddenException('UKFast Base templates can not be edited');
        }

        try {
            $intapiService->automationRequest(
                'rename_template',
                'ucs_reseller',
                0,
                [
                    'template_type' => 'system',
                    'template_name' => $template->name,
                    'new_template_name' => $newTemplateName,
                    'datacentre_id' => $podId
                ]
            );

            return $this->respondEmpty(202);
        } catch (IntapiServiceException $exception) {
            throw new TemplateUpdateException('Failed to schedule template rename');
        }
    }

    /**
     * Returns the templates for a Solution
     *
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

        $templates = $this->filterAdminProperties(
            $request,
            $this->getSolutionTemplates($solution, false)
        );

        return $this->respondCollection(
            $request,
            $this->paginateTemplateData($templates)
        );
    }

    /**
     * Delete a Solution template
     *
     * @param Request $request
     * @param IntapiService $intapiService
     * @param $solutionId
     * @param $templateName
     * @return \Illuminate\Http\Response
     * @throws SolutionNotFoundException
     * @throws TemplateNotFoundException
     * @throws TemplateUpdateException
     */
    public function deleteSolutionTemplate(Request $request, IntapiService $intapiService, $solutionId, $templateName)
    {
        $templateName = urldecode($templateName);

        $templates = $this->getResellerSolutionTemplates($solutionId);

        $template = $this->findTemplateByName($templateName, $templates);

        if (empty($template)) {
            throw new TemplateNotFoundException("A template matching the requested name '$templateName' was not found");
        }

        $solution = SolutionController::getSolutionById($request, $solutionId);

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

    /**
     * Delete Pod Template
     *
     * @param Request $request
     * @param IntapiService $intapiService
     * @param $podId
     * @param $templateName
     * @return \Illuminate\Http\Response
     * @throws ForbiddenException
     * @throws TemplateNotFoundException
     * @throws TemplateUpdateException
     * @throws \App\Exceptions\V1\PodNotFoundException
     */
    public function deletePodTemplate(Request $request, IntapiService $intapiService, $podId, $templateName)
    {
        $templateName = urldecode($templateName);

        $pod = PodController::getPodById($request, $podId);

        $templates = $this->getPodTemplates($pod);

        $template = $this->findTemplateByName($templateName, $templates);

        if (empty($template)) {
            throw new TemplateNotFoundException("A template matching the requested name '$templateName' was not found");
        }

        // Dont allow deletion of UKFast managed base templates
        if ($template->type == 'Base' || $this->isUKFastBaseTemplate($template)) {
            throw new ForbiddenException('UKFast Base templates can not be deleted');
        }

        try {
            $intapiService->automationRequest(
                'delete_template',
                'ucs_reseller',
                0,
                [
                    'template_type' => 'system',
                    'template_name' => $template->name,
                    'datacentre_id' => $podId
                ]
            );

            return $this->respondEmpty(202);
        } catch (IntapiServiceException $exception) {
            throw new TemplateUpdateException('Failed to schedule template deletion');
        }
    }
    //-------------------------------------------------

    /**
     * Paginate template data
     * @param $templates
     * @return LengthAwarePaginator
     */
    protected function paginateTemplateData($templates)
    {
        $collection = new Collection($templates);

        $paginator = new LengthAwarePaginator(
            $collection->slice(
                LengthAwarePaginator::resolveCurrentPage('page') - 1 * $this->perPage,
                $this->perPage
            )->all(),
            count($collection),
            $this->perPage
        );

        return $paginator;
    }

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

        $tmp_template->cpu = (int) $template->cpu;
        $tmp_template->ram = (int) $template->ram;
        $tmp_template->hdd = (int) $template->size_gb;

        $tmp_template->license = 'Unknown';

        foreach ($template->hard_drives as $hard_drive) {
            $tmp_template->hdd_disks[] = (object)array(
                'name' => $hard_drive->name,
                'capacity' => $hard_drive->capacitygb,
            );
        }

        if (!empty($serverLicense)) {
            $tmp_template->platform = $serverLicense->category;
            $tmp_template->license = $serverLicense->name;

            // For UKFast managed Pod templates return the server license friendly name as the template name
            if ($template->type == 'Base' || $this->isUKFastBaseTemplate($template)) {
                $tmp_template->name = $serverLicense->friendly_name;
            }
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
                $result = $this->getSolutionTemplates($solution);
                if ($result !== false) {
                    $templates = array_merge($templates, $result);
                }
            }
        }

        return $templates;
    }


    /**
     * Get Pod templates - Loops through the pods for each of the resellers solutions and
     * gets the default templates available in that pod
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @param bool $withDatacentre - Keep datacentre association as array key
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
            $templates = $this->getPodTemplates($pod);
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
     *
     * @param Solution $solution
     * @param bool $appendSolutionId
     * @return array|bool
     */
    public function getSolutionTemplates(Solution $solution, $appendSolutionId = true)
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
     * Get the Pod templates templates for this pod/datacentre
     *
     * For listing pod templates we should only list 'managed' templates for solution pods
     * which have a reseller_id of 0. Only when the reseller_id is non-0 and matches the
     * current reseller ID should we show non-managed pod templates as well as the managed
     * templates in these pods.
     *
     * @param Pod $pod
     * @return array
     */
    protected function getPodTemplates(Pod $pod)
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

        // Show non-managed pod templates?
        $showNonManagedPodTemplates = false;
        if ($pod->ucs_datacentre_reseller_id != 0 && $pod->ucs_datacentre_reseller_id == $this->resellerId) {
            $showNonManagedPodTemplates = true;
        }

        $podTemplates = [];

        foreach ($result as $template) {
            $serverLicense = ServerLicense::checkTemplateLicense($pod->getKey(), $template);

            if ($template->name == $serverLicense->name) {
                $template->type = 'Base';
                $podTemplates[] = $this->convertToPublicTemplate($template, $serverLicense);
            } else {
                $template->type = 'Pod';
                if ($showNonManagedPodTemplates) {
                    $podTemplates[] = $this->convertToPublicTemplate($template, $serverLicense);
                }
            }
        }

        return $podTemplates;
    }

    /**
     * Attempt to load a template by name using Solution or Pod
     * @param string $name
     * @param Pod $pod
     * @param Solution|null $solution
     * @return bool|null
     * @throws TemplateNotFoundException
     */
    public static function getTemplateByName(string $name, Pod $pod, Solution $solution = null)
    {
        if (!is_null($solution)) {
            $template = static::getSolutionTemplateByName($solution, $name);
            if (!empty($template)) {
                return $template;
            }
        }

        $template = static::getPodTemplateByName($pod, $name);
        if (!empty($template)) {
            return $template;
        }

        throw new TemplateNotFoundException("A template matching the requested name was not found");
    }


    /**
     * Retrieve a Pod (Pod/Base) template from a Pod by name
     * @param Pod $pod
     * @param $templateName
     * @return bool|null
     */
    public static function getPodTemplateByName(Pod $pod, $templateName)
    {
        $request = app('request');
        $TemplateController = new static($request);
        $templates = $TemplateController->getResellerPodTemplates(true)[$pod->getKey()];
        if (is_array($templates) and count($templates) > 0) {
            $template = $TemplateController->findTemplateBy('name', $templateName, $templates);
            if ($template) {
                return $template;
            }

            // base templates use friendly names
            $template = $TemplateController->findTemplateBy('name', $templateName, $templates);
            if ($template) {
                return $template;
            }
        }
        return false;
    }

    /**
     * Retrieve a Solution template from a Solution by name
     * @param Solution $solution
     * @param $templateName
     * @return bool|null
     */
    public static function getSolutionTemplateByName(Solution $solution, $templateName)
    {
        $request = app('request');
        $TemplateController = new static($request);
        $templates = $TemplateController->getSolutionTemplates($solution, false);
        if (is_array($templates) and count($templates) > 0) {
            $template = $TemplateController->findTemplateByName($templateName, $templates);
            if ($template) {
                return $template;
            }
        }
        return false;
    }

    /**
     * Find a template by it's name
     * @param $name
     * @param $objects
     * @return |null
     */
    protected function findTemplateByName($name, $objects)
    {
        return $this->findTemplateBy('name', $name, $objects);
    }

    protected function findTemplateBy($property, $value, $objects)
    {
        foreach ($objects as $object) {
            if ($object->$property == $value) {
                return $object;
            }
        }

        return null;
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

    /**
     * Filter admin properties, no model defined?
     * @param Request $request
     * @param $templates
     * @return array
     */
    protected function filterAdminProperties(Request $request, $templates)
    {
        if ($request->user->isAdmin) {
            return $templates;
        }

        if (!is_array($templates)) {
            $templates = [$templates];
        }

        foreach ($templates as $key => $template) {
            unset($template->type);
            unset($template->license);

            $templates[$key] = $template;
        }

        return (count($templates) > 1) ? $templates : $templates[0];
    }
}
