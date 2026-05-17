<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Tests\TestCase;

class ProductionUrlConfigurationTest extends TestCase
{
    public function test_it_forces_the_configured_domain_and_https_in_production(): void
    {
        config()->set('app.env', 'production');
        config()->set('app.url', 'https://www.lojagerencia.com.br');
        config()->set('app.force_https', true);
        config()->set('app.force_root_url', true);

        (new AppServiceProvider($this->app))->boot();

        $this->assertSame('https://www.lojagerencia.com.br/login', route('login'));
        $this->assertSame('https://www.lojagerencia.com.br/dashboard', route('dashboard'));
        $this->assertSame('https://www.lojagerencia.com.br/webhooks/asaas', route('billing.webhooks.asaas'));
    }
}