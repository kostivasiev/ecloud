<?php

namespace App\Template;

use App\Models\V1\GpuProfile;
use App\Models\V1\Pod;
use App\Models\V1\PodTemplate as PodTemplateModel;
use App\Models\V1\ServerLicense;

class PodTemplate extends AbstractTemplate
{
    public function __construct($template, Pod $pod)
    {
        parent::__construct($template, $pod);

        $this->type = 'Pod';

        if (($this->name == $this->serverLicense->name)) {
            $this->subType = 'Base';
        }

        // GPU Base template
        $substringResult = substr(
            $this->name,
            0,
            strpos($this->name, '-gpu-')
        );
        if (($this->isGpuTemplate() && $substringResult == $this->serverLicense->name)) {
            $this->subType = 'Base';
        }
    }

    /**
     * Determined whether the template is ukfast base template
     * @return bool
     */
    public function isUKFastBaseTemplate()
    {
        // If the template has already been identified as a UKFast managed base template in this pod, return true
        if ($this->subType == 'Base') {
            return true;
        }

        // Check against other available server licenses in all Pods?
        $availableEcloudLicenses = ServerLicense::availableToInstall('ecloud vm', true, 'OS', null); // sans Datacentre

        foreach ($availableEcloudLicenses as $UKFastTemplates) {
            if ($UKFastTemplates->server_license_name == $this->name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert the template to public format
     * @return \stdClass
     */
    public function convertToPublicTemplate()
    {
        $tmp_template = parent::convertToPublicTemplate();

        // For UKFast managed Pod templates return the server license friendly name as the template name
        if ($this->isUKFastBaseTemplate()) {
            $tmp_template->name = $this->serverLicense->friendly_name;
        }

        return $tmp_template;
    }

    /**
     * Return the template's friendly name based off the server license
     * @return mixed
     */
    public function getFriendlyName(): string
    {
        return ($this->isUKFastBaseTemplate()) ? $this->serverLicense->friendly_name : $this->name;
    }

    /**
     * Search for and return the GPU version of this template
     * @param GpuProfile $gpuProfile
     * @return PodTemplate
     * @throws \Exception
     */
    public function getGpuVersion(GpuProfile $gpuProfile): PodTemplate
    {
        if (empty($gpuProfile->card_type)) {
            throw new \Exception('Unable to determine GPU card type');
        }

        $gpuTemplateName = $this->name . '-gpu-' . $gpuProfile->card_type;

        return PodTemplateModel::withName($this->pod, $gpuTemplateName);
    }
}
