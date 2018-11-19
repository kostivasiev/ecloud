<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ServerLicense
 * @package App\Models\V1
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ServerLicense extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'server_license';

    /**
     * The primary key associated with the model.
     *
     * @var string
     */
    protected $primaryKey = 'server_license_id';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = false;


    /**
     * Map a server_license to the Virtual Machine
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function serverLicenceAvailable()
    {
        return $this->hasMany(
            'App\Models\V1\ServerLicenseAvailable',
            'server_license_id',
            'server_license_available_license_id'
        );
    }

    /**
     * Got available Server Licenses based on the passed in information
     * @param $query
     * @param $serverType
     * @param bool $includeWindows
     * @param null $licenseType
     * @param null $datacenterId
     * @return mixed
     */
    public function scopeAvailableToInstall(
        $query,
        $serverType,
        $includeWindows = true,
        $licenseType = null,
        $datacenterId = null
    ) {
        $query->join('server_license_available', 'server_license_id', '=', 'server_license_available_license_id');
        $query->where('server_license_available_server_type', '=', $serverType);
        if (!$includeWindows) {
            $query->where('server_license_category', '!=', 'Windows');
        }

        if (!is_null($licenseType)) {
            $query->where('server_license_type', '=', $licenseType);
        }

        $query->where('server_license_available_ucs_datacentre_id', '=', $datacenterId);

        return $query->get();
    }

    /**
     * Return a collection of licenses by type
     * @param $query
     * @param $licenseType
     * @return mixed
     */
    public function scopeWithType($query, $licenseType)
    {
        $query->where('server_license_type', '=', $licenseType);
        return $query;
    }

    public function scopeWithFriendlyName($query, $friendlyName)
    {
        $query->where('server_license_friendly_name', '=', $friendlyName);
        return $query;
    }

    /**
     * Check that a template has a correct os licence and return the licence if true
     * @param $datacentreId
     * @param $template
     * @return \stdClass
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public static function checkTemplateLicense($datacentreId, $template)
    {
        $ecloudLicenses = ServerLicense::availableToInstall('ecloud vm', true, 'OS', $datacentreId);

        $serverLicenses = ServerLicense::withType('OS')->get();

        // Because PHP's similar_text doesn't always match the correct result
        // let's try and make a more direct comparison by removing known flaws
        $templateFriendlyName = trim(
            str_replace(array('(', ')', 'Microsoft'), '', $template->guest_os)
        );

        $serverLicense = ServerLicense::withFriendlyName($templateFriendlyName);

        if ($serverLicense->count() < 1) {
            $similarText = [];
            //If no match found, try similar_text
            foreach ($ecloudLicenses as $availableLicence) {
                similar_text($availableLicence->friendly_name, $template->guest_os, $percent);

                // Increase the confidence required. We need it.
                if ($percent > 50) {
                    $similarText[$availableLicence->friendly_name] = $percent;
                }
                if (!empty($similarText)) {
                    $mostLikelyLicence = array_keys($similarText, max($similarText));
                    $serverLicense = ServerLicense::withFriendlyName($mostLikelyLicence[0]);
                }
            }
        }

        //If still no match found
        if ($serverLicense->count() < 1) {
            $similarText = [];
            foreach ($serverLicenses as $availableLicence) {
                similar_text($availableLicence->friendly_name, $template->guest_os, $percent);
                // Increase the confidence required. We need it.
                if ($percent > 50) {
                    $similarText[$availableLicence->id] = $percent;
                }
            }
            if (!empty($similarText)) {
                $mostLikelyLicence = array_keys($similarText, max($similarText));
                $serverLicense = new server_licence($mostLikelyLicence[0]);
            }
        }

        if ($serverLicense->count() < 1) {
            // Try and load up from all server licences
            $serverLicence = new \stdClass();
            $serverLicence->id = 0;
            $serverLicence->friendly_name = (string)$template->guest_os;
            $serverLicence->category = 'Unknown';
        }

        return $serverLicence;
    }
}
