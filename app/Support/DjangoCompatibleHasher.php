<?php

namespace App\Support;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Hashing\AbstractHasher;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Support\Str;

class DjangoCompatibleHasher extends AbstractHasher implements Hasher
{
    public function __construct(private readonly BcryptHasher $bcrypt)
    {
    }

    public function info($hashedValue): array
    {
        if ($this->isDjangoHash($hashedValue)) {
            return [
                'algo' => null,
                'algoName' => 'django-pbkdf2-sha256',
                'options' => [],
            ];
        }

        if ($this->isLegacySha1Hash($hashedValue)) {
            return [
                'algo' => null,
                'algoName' => 'legacy-sha1',
                'options' => [],
            ];
        }

        if ($this->isNativePasswordHash($hashedValue)) {
            return password_get_info($hashedValue);
        }

        return [
            'algo' => null,
            'algoName' => 'legacy-plain-text',
            'options' => [],
        ];
    }

    public function make($value, array $options = []): string
    {
        return $this->bcrypt->make($value, $options);
    }

    public function check($value, $hashedValue, array $options = []): bool
    {
        if (! is_string($hashedValue) || $hashedValue === '') {
            return false;
        }

        $plainValue = (string) $value;

        if ($this->isDjangoHash($hashedValue)) {
            return $this->checkDjangoPbkdf2($plainValue, $hashedValue);
        }

        if ($this->isNativePasswordHash($hashedValue)) {
            return password_verify($plainValue, $hashedValue);
        }

        if ($this->isLegacySha1Hash($hashedValue)) {
            return hash_equals(strtolower($hashedValue), sha1($plainValue));
        }

        return hash_equals($hashedValue, $plainValue);
    }

    public function needsRehash($hashedValue, array $options = []): bool
    {
        if (! is_string($hashedValue) || $hashedValue === '') {
            return true;
        }

        if ($this->isDjangoHash($hashedValue) || $this->isLegacySha1Hash($hashedValue)) {
            return true;
        }

        if ($this->isBcryptHash($hashedValue)) {
            return $this->bcrypt->needsRehash($hashedValue, $options);
        }

        return ! $this->isNativePasswordHash($hashedValue);
    }

    private function isDjangoHash(mixed $hashedValue): bool
    {
        return is_string($hashedValue) && Str::startsWith($hashedValue, 'pbkdf2_sha256$');
    }

    private function isNativePasswordHash(mixed $hashedValue): bool
    {
        return is_string($hashedValue)
            && (password_get_info($hashedValue)['algoName'] ?? 'unknown') !== 'unknown';
    }

    private function isBcryptHash(mixed $hashedValue): bool
    {
        return is_string($hashedValue)
            && (password_get_info($hashedValue)['algoName'] ?? 'unknown') === 'bcrypt';
    }

    private function isLegacySha1Hash(mixed $hashedValue): bool
    {
        return is_string($hashedValue) && preg_match('/^[a-f0-9]{40}$/i', $hashedValue) === 1;
    }

    private function checkDjangoPbkdf2(string $value, string $hashedValue): bool
    {
        $parts = explode('$', $hashedValue, 4);

        if (count($parts) !== 4) {
            return false;
        }

        [$algorithm, $iterations, $salt, $hash] = $parts;

        if ($algorithm !== 'pbkdf2_sha256' || ! ctype_digit($iterations) || $salt === '' || $hash === '') {
            return false;
        }

        $derived = base64_encode(hash_pbkdf2('sha256', $value, $salt, (int) $iterations, 32, true));

        return hash_equals($hash, $derived);
    }
}