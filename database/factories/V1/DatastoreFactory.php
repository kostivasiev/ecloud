<?php

$factory->define(App\Models\V1\Datastore::class, function (Faker\Generator $faker) {
    return [
        'reseller_lun_reseller_id' => 1,
        'reseller_lun_ucs_reseller_id' => 1,
        'reseller_lun_ucs_site_id' => 1,
        'reseller_lun_status' => 'Completed',
        'reseller_lun_type' => 'Hybrid',
        'reseller_lun_size_gb' => $faker->numberBetween(10, 100),
        'reseller_lun_name' => 'MCS_PX_VV_1_DATA',
        'reseller_lun_friendly_name' => '',
        'reseller_lun_wwn' => '',
        'reseller_lun_lun_type' => 'Data',
        'reseller_lun_lun_sub_type' => 'DATA_1',
    ];
});
