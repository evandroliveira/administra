<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicSolutionPagesTest extends TestCase
{
    public function test_solution_pages_are_public_and_have_unique_content(): void
    {
        $pages = [
            'gestao-financeira' => 'Organize as finanças da empresa em um só lugar',
            'controle-de-estoque' => 'Saiba o que tem no estoque e acompanhe a movimentação',
            'vendas-e-clientes' => 'Organize seus clientes e acompanhe cada venda',
            'boletos-e-promissorias' => 'Acompanhe boletos, promissórias e valores a receber',
        ];

        foreach ($pages as $slug => $heading) {
            $this->get(route('solutions.show', ['slug' => $slug]))
                ->assertOk()
                ->assertSee($heading)
                ->assertSee('name="description"', false)
                ->assertSee(route('register'), false);
        }
    }

    public function test_unknown_solution_page_returns_not_found(): void
    {
        $this->get(route('solutions.show', ['slug' => 'solucao-inexistente']))
            ->assertNotFound();
    }

    public function test_homepage_links_to_each_solution_page(): void
    {
        $response = $this->get(route('home'))->assertOk();

        foreach (['gestao-financeira', 'controle-de-estoque', 'vendas-e-clientes', 'boletos-e-promissorias'] as $slug) {
            $response->assertSee(route('solutions.show', ['slug' => $slug]), false);
        }
    }
}
