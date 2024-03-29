<?php

namespace App\Services\V2;

final class PasswordService
{
    public bool $lowerCase = true;
    public bool $upperCase = true;
    public bool $numeric = true;
    public bool $special = false;

    public $lowerCaseChars = 'abcdefghijklmnopqrstuwxyz';
    public $upperCaseChars = 'ABCDEFGHIJKLMNOPQRSTUWXYZ';
    public $numericChars = '0123456789';
    public $specialChars = '!%^&*().@#~[]{}';

    private function contains($characters, $password)
    {
        return count(array_intersect(str_split($characters), str_split($password))) > 0;
    }

    public function generate($length = 12)
    {
        if ($length < 6) {
            throw new \Exception('Length must be at least 6 characters');
        }

        $charList = '';
        if ($this->lowerCase) {
            $charList = $charList . $this->lowerCaseChars;
        }
        if ($this->upperCase) {
            $charList = $charList . $this->upperCaseChars;
        }
        if ($this->numeric) {
            $charList = $charList . $this->numericChars;
        }
        if ($this->special) {
            $charList = $charList . $this->specialChars;
        }

        $charListLength = strlen($charList) - 1;
        do {
            $password = '';
            for ($i = 0; $i < $length; $i++) {
                $password .= $charList[mt_rand(0, $charListLength)];
            }
        } while ($this->lowerCase && !$this->contains($this->lowerCaseChars, $password) ||
            $this->upperCase && !$this->contains($this->upperCaseChars, $password) ||
            $this->numeric && !$this->contains($this->numericChars, $password) ||
            $this->special && !$this->contains($this->specialChars, $password)
        );
        return $password;
    }
}
