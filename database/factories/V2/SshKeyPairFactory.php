<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\VpnSession;

$factory->define(\App\Models\V2\SshKeyPair::class, function () {
    return [
        'name' => 'Test SSH Key',
        'reseller_id' => 1,
        'public_key' => 'ssh-rsa AAAAB3NzaC1yc2EAAAABJQAAAgEAhQQvHVb/HiRqTUjYSN9ndX//mRRYAvLWpZJsiypBJIbLjd/JALb0/WB23e+ADBF2fz7craA4NovbvkILaVBBuvnNofd/2WWBmLZwMGGkn+nC8i4e5mOWVYpy1hoLin90ZvQIVFEbzztZGaYKyUNpFNMpXef3XaVp4V4R22Uh3Bm4HH6Brqx5aSeaPfwSnB+3A/L9J1NIPVyhZ3Clg7U5FI+ptqG+9MW3aRPnGLqQIeFa/o+C19tS2iBQ+WbDltv7ZWfXAgE8Rbrz2+q50JIHrFvA777P/4ScBLHbW3mgnB295fZKrDTyQRoJfEuzefcuRJe8qG1z/auZEiGpNawlR/nPYAF9ljQ4poCN2gjHLzSD9ObLKOj/1JtZo07dh3LNdmBtIlE2MDM3qiSz2Crf7OGSEfuLHwbw2R6b9oe643HZkoV6uAvzZbmRROQbV799MUHysyDh2CV4EdCpvdnEkKhcf57UzysVIJre1Zh/FR1d1crvGj23FHCCYeLLtr4koj4P1uB7eWjdbW4QS3hje9Jz+jC1Xu460CGZ9ONoorWyAOmufslwhFlXxfRtesN/xlz0f2UerIBd251LWlTtDrD2+2ponb1pcuZiYrhS4FFQo0NoSGvuprlcirsCMYS9ItBDoDqph5L3cFjuFq16Ogj2zLNF1kpJY9qclDEviRE= lee-home-desktop',
    ];
});
