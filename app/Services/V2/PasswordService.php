<?php

namespace App\Services\V2;

final class PasswordService
{
    public function generate()
    {
        $alphabet = 'abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789';
        $alphaLength = strlen($alphabet) - 1;
        do {
            $password = '';
            for ($i = 0; $i < 12; $i++) {
                $password .= $alphabet[mt_rand(0, $alphaLength)];
            }
        } while (!preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password)
        );
        return $password;
    }
}
