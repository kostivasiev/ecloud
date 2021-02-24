<?php

namespace App\Template;

use App\Models\V1\Pod;
use App\Models\V1\ServerLicense;

abstract class AbstractTemplate
{
    public $name;
    public $size;
    public $guest_os;
    public $actual_os;
    public $cpu;
    public $ram;
    public $encrypted;
    public $hard_drives;

    public $type;
    public $subType;

    public $pod;
    public $serverLicense;

    public function __construct($template, Pod $pod)
    {
        $this->name = $template->name;
        $this->size_gb = (string)$template->capacityGB;
        $this->guest_os = (string)$template->guestOS;
        $this->actual_os = trim((string)$template->actualOS);
        $this->cpu = intval($template->numCPU);
        $this->ram = intval($template->ramGB);
        $this->encrypted = $template->encrypted ?? false;

        $hard_drives = array();
        foreach ($template->disks as $hard_drive) {
            $hdd = new \stdClass();
            $hdd->name = (string)$hard_drive->name;
            $hdd->capacitygb = intval($hard_drive->capacityGB);
            $hard_drives[] = $hdd;
        }

        $this->hard_drives = $hard_drives;

        $this->pod = $pod;
        $this->serverLicense = $this->getServerLicense();
    }

    /**
     * Get the server license associated with the template
     * @return \stdClass
     * @throws \Exception
     */
    public function getServerLicense()
    {
        if (empty($this->pod)) {
            throw new \Exception('Can not determine template\'s server license. No Pod defined.');
        }

        $templateName = $this->name;
        if ($this->isGpuTemplate()) {
            $templateName = substr($this->name, 0, strpos($this->name, '-gpu-'));
        }

        $ecloudLicenses = ServerLicense::availableToInstall('ecloud vm', true, 'OS', $this->pod->getKey());

        // exact name match (aka base templates)
        $baseTemplate = $ecloudLicenses->filter(function ($license) use ($templateName) {
            return $license->server_license_name == $templateName;
        });

        if ($baseTemplate->count() > 0) {
            return $baseTemplate->first();
        }

        // partial match (aka customer templates)

        // Because PHP's similar_text doesn't always match the correct result
        // let's try and make a more direct comparison by removing known flaws
        $templateFriendlyName = trim(
            str_replace(array('(', ')', 'Microsoft'), '', $this->guest_os)
        );

        $serverLicense = ServerLicense::withFriendlyName($templateFriendlyName);
        if ($serverLicense->count() > 0) {
            return $serverLicense->first();
        }

        foreach ($ecloudLicenses as $availableLicence) {
            if ($availableLicence->name == $this->actual_os) {
                $serverLicense = ServerLicense::withName($availableLicence->name);
                if ($serverLicense->count() > 0) {
                    return $serverLicense->first();
                }
            }

            if ($availableLicence->friendly_name == $this->guest_os) {
                $serverLicense = ServerLicense::withFriendlyName($availableLicence->friendly_name);
                if ($serverLicense->count() > 0) {
                    return $serverLicense->first();
                }
            }
        }

        //If still no match found
        $serverLicenses = ServerLicense::withType('OS')->get();
        foreach ($serverLicenses as $availableLicence) {
            if ($availableLicence->name == $this->actual_os) {
                $serverLicense = ServerLicense::withName($availableLicence->name);
                if ($serverLicense->count() > 0) {
                    return $serverLicense->first();
                }
            }

            if ($availableLicence->friendly_name == $this->guest_os) {
                $serverLicense = ServerLicense::withFriendlyName($availableLicence->friendly_name);
                if ($serverLicense->count() > 0) {
                    return $serverLicense->first();
                }
            }
        }

        // no matching license, try to create one
        $serverLicence = new \stdClass();
        $serverLicence->id = 0;
        $serverLicence->name = (string)$this->actual_os;
        $serverLicence->friendly_name = (string)$this->guest_os;

        if (strpos($this->guest_os, 'Windows') !== false) {
            $serverLicence->category = 'Windows';
        } else {
            $serverLicence->category = 'Linux';
        }

        return $serverLicence;
    }

    /**
     * Determined whether the template is a GPU template
     * @return bool
     */
    public function isGpuTemplate()
    {
        return (strpos($this->name, '-gpu-') !== false);
    }

    /**
     * Get the GPU card version for GPU templates
     * @return bool|string
     * @throws \Exception
     */
    public function getGpuCard()
    {
        if (!$this->isGpuTemplate()) {
            throw new \Exception('Template is not a GPU template');
        }
        return substr($this->name, strrpos($this->name, '-gpu-') + 5);
    }

    /**
     * Formats the Solution and Pod templates for return by the API
     * @return \stdClass
     */
    public function convertToPublicTemplate()
    {
        $tmp_template = new \stdClass;
        $tmp_template->type = $this->type;
        $tmp_template->name = $this->name;

        $tmp_template->cpu = (int)$this->cpu;
        $tmp_template->ram = (int)$this->ram;
        $tmp_template->hdd = (int)$this->size_gb;

        $tmp_template->license = 'Unknown';
        $tmp_template->encrypted = $this->encrypted ?? false;

        foreach ($this->hard_drives as $hard_drive) {
            $tmp_template->hdd_disks[] = (object)array(
                'name' => $hard_drive->name,
                'capacity' => $hard_drive->capacitygb,
            );
        }

        if (!empty($this->serverLicense)) {
            $tmp_template->platform = $this->serverLicense->category;
            $tmp_template->license = $this->serverLicense->name;
        }

        return $tmp_template;
    }

    /**
     * Helper function to get server license name
     * @return mixed
     */
    public function license()
    {
        return $this->serverLicense->name;
    }

    /**
     * Helper function to get server license platform
     * @return mixed
     */
    public function platform()
    {
        return $this->serverLicense->category;
    }
}
