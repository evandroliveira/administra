<?php

namespace App\Support\Brasil;

class BrazilianPhone
{
    public static function digits(?string $value): string
    {
        return BrazilianDocument::digits($value);
    }

    public static function isValid(?string $phone): bool
    {
        $phone = self::digits($phone);

        return in_array(strlen($phone), [10, 11], true);
    }
}