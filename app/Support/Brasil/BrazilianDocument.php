<?php

namespace App\Support\Brasil;

class BrazilianDocument
{
    public static function digits(?string $value): string
    {
        return preg_replace('/\D+/', '', $value ?? '') ?: '';
    }

    public static function isValid(?string $document): bool
    {
        $document = self::digits($document);

        return match (strlen($document)) {
            11 => self::isValidCpf($document),
            14 => self::isValidCnpj($document),
            default => false,
        };
    }

    private static function isValidCpf(string $document): bool
    {
        if (strlen($document) !== 11 || count(array_unique(str_split($document))) === 1) {
            return false;
        }

        $firstDigit = self::calculateVerifierDigit(substr($document, 0, 9), range(10, 2));
        $secondDigit = self::calculateVerifierDigit(substr($document, 0, 9).$firstDigit, range(11, 2));

        return substr($document, -2) === $firstDigit.$secondDigit;
    }

    private static function isValidCnpj(string $document): bool
    {
        if (strlen($document) !== 14 || count(array_unique(str_split($document))) === 1) {
            return false;
        }

        $firstDigit = self::calculateVerifierDigit(substr($document, 0, 12), [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        $secondDigit = self::calculateVerifierDigit(substr($document, 0, 12).$firstDigit, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

        return substr($document, -2) === $firstDigit.$secondDigit;
    }

    private static function calculateVerifierDigit(string $document, array $weights): string
    {
        $total = 0;

        foreach (str_split($document) as $index => $digit) {
            $total += ((int) $digit) * ((int) $weights[$index]);
        }

        $remainder = $total % 11;

        return (string) ($remainder < 2 ? 0 : 11 - $remainder);
    }
}