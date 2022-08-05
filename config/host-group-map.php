<?php
/**
 * Map proposed host groups to existing ones
 * See: https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/1605
 */


return [
    // G0
    'az-aaaaaaaa' => [
        'hg-99f9b758' => '1001', // Standard CPU
    ],

    // MAN5 Manchester West
    'az-c3fa6878' => [
        'hg-9d7e6b43' => 'StandardCPU-01',
        'hg-cf1bae59' => 'HighCPU-01',
    ],

    'az-4fcc2a10' => [
        // LON1 London Central
        'hg-89e95d15' => 'StandardCPU-01',
        'hg-add46c9a' => 'HighCPU-01',
    ],

    'az-b3cbcbe7' => [
        // LON2 London West
        'hg-89ca4ae8' => 'StandardCPU-01',
        'hg-593e3885' => 'HighCPU-01',
    ],

    'az-4c31a488' => [
        // MAN4 Manchester South
        'hg-56271b1e' => 'StandardCPU-01',
        'hg-a670426f' => 'HighCPU-01',
    ],

    'az-a75a74fa' => [
        // AMS Amsterdam East
        'hg-ed7a097e' => 'StandardCPU-01',
        'hg-b2aeea8a' => 'HighCPU-01',
//    '' => 'StandardCPU-Windows',
    ],
];