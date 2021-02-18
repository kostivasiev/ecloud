<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\V1\IntapiServiceException;
use App\Exceptions\V1\SolutionNotFoundException;
use App\Exceptions\V1\TemplateNotFoundException;
use App\Exceptions\V1\TemplateUpdateException;
use App\Models\V1\Pod;
use App\Models\V1\PodTemplate;
use App\Models\V1\Solution;
use App\Models\V1\SolutionTemplate;
use App\Services\IntapiService;
use App\Solution\CanModifyResource;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;
use UKFast\Api\Exceptions\ForbiddenException;

/**
 * Class TemplateController
 * @package App\Http\Controllers\V1
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class TemplateController extends BaseController
{
    const TEMPLATE_NAME_FORMAT_REGEX = '^[A-Za-z0-9-_\ ]+$';

    /**
     * Returns the templates for a Solution
     *
     * @param Request $request
     * @param $solutionId
     * @return \Illuminate\Http\Response
     * @throws SolutionNotFoundException
     */
    public function indexSolutionTemplate(Request $request, $solutionId)
    {
        $solutionQuery = Solution::withReseller($this->resellerId);
        if (!$this->isAdmin) {
            $solutionQuery->where('ucs_reseller_active', 'Yes');
        }
        $solution = $solutionQuery->find($solutionId);

        if (!$solution) {
            throw new SolutionNotFoundException('Solution ID #' . $solutionId . ' not found', 'solution_id');
        }

        $solutionTemplates = SolutionTemplate::withSolution($solution);

        $templates = [];
        foreach ($solutionTemplates as $template) {
            $templates[] = $template->convertToPublicTemplate();
        }

        $templates = $this->filterAdminProperties(
            $request,
            $templates
        );

        return $this->respondCollection(
            $request,
            $this->paginateTemplateData($templates)
        );
    }

    /**
     * Returns a Solution template Item
     *
     * @param Request $request
     * @param $solutionId
     * @param $templateName
     * @return \Illuminate\Http\Response
     * @throws SolutionNotFoundException
     * @throws TemplateNotFoundException
     */
    public function showSolutionTemplate(Request $request, $solutionId, $templateName)
    {
        $solution = SolutionController::getSolutionById($request, $solutionId);

        $templateName = urldecode($templateName);

        $solutionTemplate = SolutionTemplate::withName($solution, $templateName);

        return $this->respondItem(
            $request,
            $this->filterAdminProperties($request, $solutionTemplate->convertToPublicTemplate())
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
    public function indexPodTemplate(Request $request, $podId)
    {
        $pod = PodController::getPodById($request, $podId);

        $podTemplates = PodTemplate::withPod($pod);

        $templates = [];

        foreach ($podTemplates as $template) {
            // Don't display GPU templates
            if (!$template->isGpuTemplate()) {
                $templates[] = $template->convertToPublicTemplate();
            }
        }

        $templates = $this->filterAdminProperties($request, $templates);

        return $this->respondCollection(
            $request,
            $this->paginateTemplateData($templates)
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

        $template = PodTemplate::withFriendlyName($pod, $templateName);

        return $this->respondItem(
            $request,
            $this->filterAdminProperties($request, $template->convertToPublicTemplate())
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
     * @throws \App\Solution\Exceptions\InvalidSolutionStateException
     */
    public function renameSolutionTemplate(Request $request, IntapiService $intapiService, $solutionId, $templateName)
    {
        $solution = SolutionController::getSolutionById($request, $solutionId);

        (new CanModifyResource($solution))->validate();

        $templateName = urldecode($templateName);

        $this->validate($request, ['destination' => ['required', 'regex:/[A-Za-z0-9-_\ ]+/']]);

        $newTemplateName = $request->input('destination');

        $solutionTemplate = SolutionTemplate::withName($solution, $templateName);

        try {
            $intapiService->automationRequest(
                'rename_template',
                'ucs_reseller',
                $solutionTemplate->solution_id,
                [
                    'template_type' => 'solution',
                    'template_name' => $solutionTemplate->name,
                    'new_template_name' => $newTemplateName
                ],
                'ecloud_ucs_' . $solution->pod->getKey(),
                $request->user()->userId(),
                $request->user()->type()
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

        $template = PodTemplate::withFriendlyName($pod, $templateName);

        // Dont allow renaming of UKFast managed base templates
        if ($template->isUKFastBaseTemplate($template)) {
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
                ],
                'ecloud_ucs_' . $podId,
                $request->user()->userId(),
                $request->user()->type()
            );

            return $this->respondEmpty(202);
        } catch (IntapiServiceException $exception) {
            throw new TemplateUpdateException('Failed to schedule template rename');
        }
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
     * @throws \App\Solution\Exceptions\InvalidSolutionStateException
     */
    public function deleteSolutionTemplate(Request $request, IntapiService $intapiService, $solutionId, $templateName)
    {
        $solution = SolutionController::getSolutionById($request, $solutionId);

        (new CanModifyResource($solution))->validate();

        $templateName = urldecode($templateName);

        $solutionTemplate = SolutionTemplate::withName($solution, $templateName);

        try {
            $intapiService->automationRequest(
                'delete_template',
                'ucs_reseller',
                $solutionTemplate->solution->getKey(),
                [
                    'template_type' => 'solution',
                    'template_name' => $templateName,
                ],
                'ecloud_ucs_' . $solution->pod->getKey(),
                $request->user()->userId(),
                $request->user()->type()
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

        $template = PodTemplate::withFriendlyName($pod, $templateName);

        // Dont allow deletion of UKFast managed base templates
        if ($template->isUKFastBaseTemplate($template)) {
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
                ],
                'ecloud_ucs_' . $podId,
                $request->user()->userId(),
                $request->user()->type()
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
        if (!is_array($templates)) {
            $templates = [$templates];
        }
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
            try {
                return SolutionTemplate::withName($solution, $name);
            } catch (TemplateNotFoundException $exception) {
                return PodTemplate::withFriendlyName($pod, $name);
            }
        }

        return PodTemplate::withFriendlyName($pod, $name);
    }

    /**
     * Filter admin properties, no model defined?
     * @param Request $request
     * @param $templates
     * @return array
     */
    protected function filterAdminProperties(Request $request, $templates)
    {
        if ($request->user()->isAdmin()) {
            return $templates;
        }

        if (empty($templates)) {
            return $templates;
        }

        if (!is_array($templates)) {
            $templates = [$templates];
        }

        foreach ($templates as &$template) {
            unset($template->type);
            unset($template->license);
            unset($template->solution_id);
        }

        return (count($templates) > 1) ? $templates : $templates[0];
    }
}
