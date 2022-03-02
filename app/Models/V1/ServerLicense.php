<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ServerLicense
 * @package App\Models\V1
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ServerLicense extends Model
{
    use HasFactory;

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

        if (!is_null($datacenterId)) {
            $query->where('server_license_available_ucs_datacentre_id', '=', $datacenterId);
        }

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

    public function scopeWithName($query, $name)
    {
        $query->where('server_license_name', '=', $name);
        return $query;
    }

    public function scopeWithFriendlyName($query, $friendlyName)
    {
        $query->where('server_license_friendly_name', '=', $friendlyName);
        return $query;
    }

    public function getNameAttribute()
    {
        return $this->server_license_name;
    }

    public function getCategoryAttribute()
    {
        return $this->server_license_category;
    }

    public function getFriendlyNameAttribute()
    {
        return $this->server_license_friendly_name;
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

        // exact name match (aka base templates)
        $baseTemplate = $ecloudLicenses->filter(function ($license) use ($template) {
            return $license->server_license_name == $template->name;
        });

        if ($baseTemplate->count() > 0) {
            return $baseTemplate->first();
        }


        // partial match (aka customer templates)

        // Because PHP's similar_text doesn't always match the correct result
        // let's try and make a more direct comparison by removing known flaws
        $templateFriendlyName = trim(
            str_replace(array('(', ')', 'Microsoft'), '', $template->guest_os)
        );

        $serverLicense = ServerLicense::withFriendlyName($templateFriendlyName);
        if ($serverLicense->count() > 0) {
            return $serverLicense->first();
        }

        foreach ($ecloudLicenses as $availableLicence) {
            if ($availableLicence->friendly_name == $template->guest_os) {
                $serverLicense = ServerLicense::withFriendlyName($availableLicence->friendly_name);

                if ($serverLicense->count() > 0) {
                    return $serverLicense->first();
                }
            }
        }


        //If still no match found
        $serverLicenses = ServerLicense::withType('OS')->get();
        foreach ($serverLicenses as $availableLicence) {
            if ($availableLicence->friendly_name == $template->guest_os) {
                $serverLicense = ServerLicense::withFriendlyName($availableLicence->friendly_name);

                if ($serverLicense->count() > 0) {
                    return $serverLicense->first();
                }
            }
        }

        // no matching license, try to create one
        $serverLicence = new \stdClass();
        $serverLicence->id = 0;
        $serverLicence->name = '';
        $serverLicence->friendly_name = (string)$template->guest_os;

        if (strpos($template->guest_os, 'Windows') !== false) {
            $serverLicence->category = 'Windows';
        } else {
            $serverLicence->category = 'Linux';
        }

        return $serverLicence;
    }
}
