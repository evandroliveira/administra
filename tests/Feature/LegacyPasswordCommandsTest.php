<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LegacyPasswordCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_password_audit_command_reports_detected_formats(): void
    {
        $this->inserirUsuario(1, 'bcrypt_user', 'bcrypt@example.com', Hash::make('secret123'));
        $this->inserirUsuario(2, 'plain_user', 'plain@example.com', 'secret123');
        $this->inserirUsuario(3, 'sha1_user', 'sha1@example.com', sha1('secret123'));
        $this->inserirUsuario(4, 'django_user', 'django@example.com', $this->gerarHashDjango('secret123'));

        $exitCode = Artisan::call('legacy:audit-user-passwords', ['--show-users' => 10]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('legacy-plain-text', $output);
        $this->assertStringContainsString('legacy-sha1', $output);
        $this->assertStringContainsString('django-pbkdf2-sha256', $output);
        $this->assertStringContainsString('plain_user', $output);
        $this->assertStringContainsString('rehash no login', $output);
    }

    public function test_plain_text_password_rehash_command_converts_only_plain_text_passwords(): void
    {
        $this->inserirUsuario(1, 'plain_user', 'plain@example.com', 'secret123');
        $this->inserirUsuario(2, 'sha1_user', 'sha1@example.com', sha1('secret123'));
        $djangoHash = $this->gerarHashDjango('secret123');
        $this->inserirUsuario(3, 'django_user', 'django@example.com', $djangoHash);

        $this->artisan('legacy:rehash-plain-text-passwords')
            ->expectsOutputToContain('plain_user')
            ->expectsOutputToContain('convertida(s) para bcrypt')
            ->assertExitCode(0);

        $plainPassword = (string) DB::table('users')->where('id', 1)->value('password');
        $this->assertStringStartsWith('$2y$', $plainPassword);
        $this->assertTrue(Hash::check('secret123', $plainPassword));
        $this->assertSame(sha1('secret123'), DB::table('users')->where('id', 2)->value('password'));
        $this->assertSame($djangoHash, DB::table('users')->where('id', 3)->value('password'));
    }

    private function inserirUsuario(int $id, string $username, string $email, string $password): void
    {
        DB::table('users')->insert([
            'id' => $id,
            'name' => ucfirst($username),
            'username' => $username,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => $password,
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function gerarHashDjango(string $password, string $salt = 'legacycommandsalt', int $iterations = 1000): string
    {
        $hash = base64_encode(hash_pbkdf2('sha256', $password, $salt, $iterations, 32, true));

        return sprintf('pbkdf2_sha256$%d$%s$%s', $iterations, $salt, $hash);
    }
}