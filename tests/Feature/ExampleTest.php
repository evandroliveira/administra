<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guest_sees_login_screen_from_root(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Se sua empresa já existe', false)
            ->assertSee('Criar empresa e iniciar assinatura', false);
    }
}
