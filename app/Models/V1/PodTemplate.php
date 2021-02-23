<?php

namespace App\Models\V1;

use App\Exceptions\V1\TemplateNotFoundException;
use Illuminate\Support\Facades\Auth;

class PodTemplate
{
    /**
     * Get the Pod templates templates for this pod/datacentre
     *
     * For listing pod templates we should only list 'managed' templates for solution pods
     * which have a reseller_id of 0. Only when the reseller_id is non-0 and matches the
     * current reseller ID should we show non-managed pod templates as well as the managed
     * templates in these pods.
     *
     * @param Pod $pod
     * @param bool $forceNonManagedPodTemplates
     * @return array
     */
    public static function withPod(Pod $pod, $forceNonManagedPodTemplates = false)
    {
        $templates = [];
        try {
            $kingpin = app()->makeWith('App\Services\Kingpin\V1\KingpinService', [$pod]);
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
        if ($pod->ucs_datacentre_reseller_id != 0
            && $pod->ucs_datacentre_reseller_id == Auth::user()->resellerId()) {
            $showNonManagedPodTemplates = true;
        }

        foreach ($result as $template) {
            if ($template->isUKFastBaseTemplate() || $showNonManagedPodTemplates || $forceNonManagedPodTemplates) {
                $templates[] = $template;
            }
        }

        return $templates;
    }

    /**
     * Retrieve a Pod (Pod/Base) template from a Pod by FRIENDLY name.
     * Note: This will not return GPU templates, as we don't want to display these to customers.
     * @param Pod $pod
     * @param $templateName
     * @return bool|null
     * @throws TemplateNotFoundException
     */
    public static function withFriendlyName(Pod $pod, $templateName)
    {
        $templates = PodTemplate::withPod($pod);
        if (is_array($templates) and count($templates) > 0) {
            foreach ($templates as $template) {
                if ($template->isGpuTemplate()) {
                    continue;
                }
                if ($template->getFriendlyName() == $templateName) {
                    return $template;
                }
            }
        }

        throw new TemplateNotFoundException("A template matching the requested name '$templateName' was not found");
    }

    /**
     * Retrieve a Pod (Pod/Base) template from a Pod by name.
     * note: This will include GPU templates also
     * @param Pod $pod
     * @param $templateName
     * @return mixed
     * @throws TemplateNotFoundException
     */
    public static function withName(Pod $pod, $templateName)
    {
        $templates = PodTemplate::withPod($pod);

        if (is_array($templates) and count($templates) > 0) {
            foreach ($templates as $template) {
                if ($template->name == $templateName) {
                    return $template;
                }
            }
        }

        throw new TemplateNotFoundException("A template matching the requested name '$templateName' was not found");
    }

    // Load an appliance template
    public static function applianceTemplate(Pod $pod, $templateName)
    {
        $templates = PodTemplate::withPod($pod, true);

        if (is_array($templates) and count($templates) > 0) {
            foreach ($templates as $template) {
                if ($template->name == $templateName) {
                    // Unset the server license as we work this out from the appliance version
                    unset($template->serverLicense);
                    return $template;
                }
            }
        }

        throw new TemplateNotFoundException("A template matching the requested name '$templateName' was not found");
    }
}
