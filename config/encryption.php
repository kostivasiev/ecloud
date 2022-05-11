<?php

return [
    'keystore_host' => env('ENCRYPTION_KEYSTORE_HOST'),
    'keystore_host_key' => env('ENCRYPTION_KEYSTORE_HOST_KEY'),
    'aes_iv' => hex2bin(env('ENCRYPTION_AES_IV')),
];