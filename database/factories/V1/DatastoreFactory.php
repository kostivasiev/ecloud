<?php
namespace Database\Factories\V1;

use App\Models\V1\Datastore;
use Illuminate\Database\Eloquent\Factories\Factory;

class DatastoreFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Datastore::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'reseller_lun_reseller_id' => 1,
            'reseller_lun_ucs_reseller_id' => 1,
            'reseller_lun_ucs_site_id' => 1,
            'reseller_lun_status' => 'Completed',
            'reseller_lun_type' => 'Hybrid',
            'reseller_lun_size_gb' => 100,
            'reseller_lun_name' => 'MCS_PX_VV_1_DATA',
            'reseller_lun_friendly_name' => '',
            'reseller_lun_wwn' => '',
            'reseller_lun_lun_type' => 'Data',
            'reseller_lun_lun_sub_type' => 'DATA_1',
        ];
    }
}
