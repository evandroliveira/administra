<?php

namespace App\Support;

use App\Models\Perfil;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DjangoDataImporter
{
    private const ROLE_DEFINITIONS = [
        Perfil::ADMIN => [
            'descricao' => 'Administrador do sistema',
            'pode_vender' => true,
            'pode_gerar_relatorios' => true,
            'pode_gerenciar_usuarios' => true,
            'pode_gerenciar_financeiro' => true,
            'pode_editar_produtos' => true,
            'pode_editar_clientes' => true,
            'permissions' => [
                'vendas.realizar',
                'relatorios.visualizar',
                'usuarios.gerenciar',
                'financeiro.gerenciar',
                'produtos.editar',
                'clientes.editar',
            ],
        ],
        Perfil::GERENTE => [
            'descricao' => 'Gerente/Supervisor',
            'pode_vender' => true,
            'pode_gerar_relatorios' => true,
            'pode_gerenciar_usuarios' => false,
            'pode_gerenciar_financeiro' => true,
            'pode_editar_produtos' => true,
            'pode_editar_clientes' => true,
            'permissions' => [
                'vendas.realizar',
                'relatorios.visualizar',
                'financeiro.gerenciar',
                'produtos.editar',
                'clientes.editar',
            ],
        ],
        Perfil::VENDEDOR => [
            'descricao' => 'Vendedor',
            'pode_vender' => true,
            'pode_gerar_relatorios' => false,
            'pode_gerenciar_usuarios' => false,
            'pode_gerenciar_financeiro' => false,
            'pode_editar_produtos' => false,
            'pode_editar_clientes' => true,
            'permissions' => [
                'vendas.realizar',
                'clientes.editar',
            ],
        ],
        Perfil::RECEPCAO => [
            'descricao' => 'Recepção/Atendimento',
            'pode_vender' => false,
            'pode_gerar_relatorios' => false,
            'pode_gerenciar_usuarios' => false,
            'pode_gerenciar_financeiro' => false,
            'pode_editar_produtos' => false,
            'pode_editar_clientes' => true,
            'permissions' => [
                'clientes.editar',
            ],
        ],
    ];

    public function import(): array
    {
        $this->ensureRequiredTablesExist();

        $summary = [];

        DB::transaction(function () use (&$summary): void {
            $profileMap = $this->importPerfisAndRoles();

            $summary['empresas'] = $this->importEmpresas();
            $summary['planos'] = $this->importPlanos();
            $summary['assinaturas'] = $this->importAssinaturas();
            $summary['faturas'] = $this->importFaturas();
            $summary['evento_webhooks'] = $this->importEventoWebhooks();
            $summary['perfis'] = count(self::ROLE_DEFINITIONS);
            $summary['users'] = $this->importUsers();
            $summary['usuario_vendas'] = $this->importUsuarioVendas($profileMap);
            $summary['categorias'] = $this->importCategorias();
            $summary['clientes'] = $this->importClientes();
            $summary['produtos'] = $this->importProdutos();
            $summary['produto_imagens'] = $this->importProdutoImagens();
            $summary['movimentacao_estoques'] = $this->importMovimentacoesEstoque();
            $summary['vendas'] = $this->importVendas();
            $summary['item_vendas'] = $this->importItensVenda();
            $summary['contas_receber'] = $this->importContasReceber();
            $summary['promissorias'] = $this->importPromissorias();
            $summary['promissoria_parcelas'] = $this->importPromissoriaParcelas();
            $summary['pagamentos_receber'] = $this->importPagamentosReceber();
            $summary['contas_pagar'] = $this->importContasPagar();
            $summary['pagamentos_pagar'] = $this->importPagamentosPagar();

            $this->syncUserRoles($profileMap);

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });

        return $summary;
    }

    private function ensureRequiredTablesExist(): void
    {
        $tables = [
            'users',
            'roles',
            'permissions',
            'perfis',
            'empresas',
            'planos',
            'assinaturas',
            'faturas',
            'evento_webhooks',
            'usuario_vendas',
            'categorias',
            'clientes',
            'produtos',
            'produto_imagens',
            'movimentacao_estoques',
            'vendas',
            'item_vendas',
            'contas_receber',
            'pagamentos_receber',
            'contas_pagar',
            'pagamentos_pagar',
            'promissorias',
            'promissoria_parcelas',
            'auth_user',
            'vendas_app_empresa',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                throw new \RuntimeException("Tabela obrigatoria ausente para a importacao: {$table}.");
            }
        }
    }

    private function importPerfisAndRoles(): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->allPermissions() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $profileIdsByName = [];
        $now = now()->toDateTimeString();

        foreach (self::ROLE_DEFINITIONS as $name => $definition) {
            $existingId = DB::table('perfis')->where('nome', $name)->value('id');
            $data = [
                'nome' => $name,
                'descricao' => $definition['descricao'],
                'pode_vender' => $definition['pode_vender'],
                'pode_gerar_relatorios' => $definition['pode_gerar_relatorios'],
                'pode_gerenciar_usuarios' => $definition['pode_gerenciar_usuarios'],
                'pode_gerenciar_financeiro' => $definition['pode_gerenciar_financeiro'],
                'pode_editar_produtos' => $definition['pode_editar_produtos'],
                'pode_editar_clientes' => $definition['pode_editar_clientes'],
                'updated_at' => $now,
            ];

            if ($existingId) {
                DB::table('perfis')->where('id', $existingId)->update($data);
                $profileIdsByName[$name] = (int) $existingId;
            } else {
                $profileIdsByName[$name] = (int) DB::table('perfis')->insertGetId($data + [
                    'created_at' => $now,
                ]);
            }

            $role = Role::findOrCreate($name, 'web');
            $role->syncPermissions($definition['permissions']);
        }

        $profileMap = [];
        $legacyProfiles = DB::table('vendas_app_perfil')->orderBy('id')->get();

        foreach ($legacyProfiles as $legacyProfile) {
            $canonicalName = $this->canonicalProfileName($legacyProfile);
            $profileMap[(int) $legacyProfile->id] = [
                'id' => $profileIdsByName[$canonicalName],
                'name' => $canonicalName,
            ];
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $profileMap;
    }

    private function importEmpresas(): int
    {
        $rows = DB::table('vendas_app_empresa')
            ->orderBy('id')
            ->get()
            ->map(function ($empresa) {
                return [
                    'id' => (int) $empresa->id,
                    'nome' => $this->sanitizeString($empresa->nome) ?? 'Empresa '.$empresa->id,
                    'slug' => $this->sanitizeSlug($empresa->slug, $empresa->nome, $empresa->id),
                    'documento' => $this->nullableString($empresa->documento),
                    'email' => $this->nullableEmail($empresa->email),
                    'telefone' => $this->nullableString($empresa->telefone),
                    'logo' => $this->nullableString($empresa->logo),
                    'ativa' => (bool) $empresa->ativa,
                    'created_at' => $this->dateTimeOrNow($empresa->criado_em),
                    'updated_at' => $this->dateTimeOrNow($empresa->atualizado_em ?? $empresa->criado_em),
                ];
            })
            ->all();

        return $this->upsertRows('empresas', $rows, ['id']);
    }

    private function importPlanos(): int
    {
        $rows = DB::table('vendas_app_plano')
            ->orderBy('id')
            ->get()
            ->map(function ($plano) {
                return [
                    'id' => (int) $plano->id,
                    'nome' => $this->sanitizeString($plano->nome) ?? 'Plano '.$plano->id,
                    'descricao' => $this->nullableString($plano->descricao),
                    'valor_mensal' => $plano->valor_mensal ?? 0,
                    'limite_usuarios' => (int) ($plano->limite_usuarios ?? 0),
                    'limite_produtos' => (int) ($plano->limite_produtos ?? 0),
                    'permite_promissoria' => (bool) $plano->permite_promissoria,
                    'permite_relatorios_pdf' => (bool) $plano->permite_relatorios_pdf,
                    'permite_exportacao_xlsx' => (bool) $plano->permite_exportacao_xlsx,
                    'ativo' => (bool) $plano->ativo,
                    'created_at' => $this->dateTimeOrNow($plano->criado_em),
                    'updated_at' => $this->dateTimeOrNow($plano->atualizado_em ?? $plano->criado_em),
                ];
            })
            ->all();

        return $this->upsertRows('planos', $rows, ['id']);
    }

    private function importAssinaturas(): int
    {
        $rows = DB::table('vendas_app_assinatura')
            ->orderBy('id')
            ->get()
            ->map(function ($assinatura) {
                return [
                    'id' => (int) $assinatura->id,
                    'empresa_id' => (int) $assinatura->empresa_id,
                    'plano_id' => (int) $assinatura->plano_id,
                    'status' => $assinatura->status ?? 'ativa',
                    'gateway' => $this->nullableString($assinatura->gateway),
                    'gateway_customer_id' => $this->nullableString($assinatura->gateway_customer_id),
                    'gateway_subscription_id' => $this->nullableString($assinatura->gateway_subscription_id),
                    'inicio_vigencia' => $this->dateOrNow($assinatura->inicio_vigencia),
                    'trial_ends_at' => $this->nullableDate($assinatura->trial_ends_at),
                    'fim_periodo_atual' => $this->nullableDate($assinatura->fim_periodo_atual),
                    'cancelar_no_fim_periodo' => (bool) $assinatura->cancelar_no_fim_periodo,
                    'ativa_ate' => $this->nullableDate($assinatura->ativa_ate),
                    'created_at' => $this->dateTimeOrNow($assinatura->criado_em),
                    'updated_at' => $this->dateTimeOrNow($assinatura->atualizado_em ?? $assinatura->criado_em),
                ];
            })
            ->all();

        return $this->upsertRows('assinaturas', $rows, ['id']);
    }

    private function importFaturas(): int
    {
        $rows = DB::table('vendas_app_fatura')
            ->orderBy('id')
            ->get()
            ->map(function ($fatura) {
                return [
                    'id' => (int) $fatura->id,
                    'empresa_id' => (int) $fatura->empresa_id,
                    'assinatura_id' => (int) $fatura->assinatura_id,
                    'external_id' => $this->nullableString($fatura->external_id),
                    'descricao' => $this->nullableString($fatura->descricao),
                    'valor' => $fatura->valor ?? 0,
                    'vencimento' => $this->nullableDate($fatura->vencimento),
                    'pago_em' => $this->nullableDateTime($fatura->pago_em),
                    'status' => $fatura->status ?? 'pendente',
                    'invoice_url' => $this->nullableString($fatura->invoice_url),
                    'checkout_url' => $this->nullableString($fatura->checkout_url),
                    'payload' => $this->jsonOrNull($fatura->payload),
                    'created_at' => $this->dateTimeOrNow($fatura->criado_em),
                    'updated_at' => $this->dateTimeOrNow($fatura->atualizado_em ?? $fatura->criado_em),
                ];
            })
            ->all();

        return $this->upsertRows('faturas', $rows, ['id']);
    }

    private function importEventoWebhooks(): int
    {
        $rows = DB::table('vendas_app_eventowebhook')
            ->orderBy('id')
            ->get()
            ->map(function ($evento) {
                return [
                    'id' => (int) $evento->id,
                    'provider' => $evento->provider,
                    'event_type' => $evento->event_type,
                    'external_id' => $this->nullableString($evento->external_id),
                    'empresa_id' => $evento->empresa_id ? (int) $evento->empresa_id : null,
                    'assinatura_id' => $evento->assinatura_id ? (int) $evento->assinatura_id : null,
                    'fatura_id' => $evento->fatura_id ? (int) $evento->fatura_id : null,
                    'payload' => $this->jsonOrNull($evento->payload),
                    'status' => $evento->status ?? 'recebido',
                    'erro' => $this->nullableString($evento->erro),
                    'processado_em' => $this->nullableDateTime($evento->processado_em),
                    'created_at' => $this->dateTimeOrNow($evento->recebido_em ?? $evento->processado_em),
                    'updated_at' => $this->dateTimeOrNow($evento->processado_em ?? $evento->recebido_em),
                ];
            })
            ->all();

        return $this->upsertRows('evento_webhooks', $rows, ['id']);
    }

    private function importUsers(): int
    {
        $usedEmails = [];
        $rows = [];

        foreach (DB::table('auth_user')->orderBy('id')->get() as $user) {
            $email = $this->uniqueEmail(
                $user->email,
                'legacy-user',
                (int) $user->id,
                $usedEmails
            );

            $name = trim(implode(' ', array_filter([
                $this->sanitizeString($user->first_name),
                $this->sanitizeString($user->last_name),
            ])));

            if ($name === '') {
                $name = $this->sanitizeString($user->username) ?? 'Usuario '.$user->id;
            }

            $createdAt = $this->dateTimeOrNow($user->date_joined);
            $updatedAt = $this->dateTimeOrNow($user->last_login ?? $user->date_joined);

            $rows[] = [
                'id' => (int) $user->id,
                'username' => $this->sanitizeString($user->username) ?? 'legacy_user_'.$user->id,
                'name' => $name,
                'email' => $email,
                'email_verified_at' => $createdAt,
                'password' => (string) $user->password,
                'remember_token' => null,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ];
        }

        return $this->upsertRows('users', $rows, ['id']);
    }

    private function importUsuarioVendas(array $profileMap): int
    {
        $fallbackProfileId = $this->fallbackProfileId($profileMap);

        $rows = DB::table('vendas_app_usuariovendas')
            ->orderBy('id')
            ->get()
            ->map(function ($usuarioVendas) use ($profileMap, $fallbackProfileId) {
                $profile = $profileMap[(int) $usuarioVendas->perfil_id] ?? null;

                return [
                    'id' => (int) $usuarioVendas->id,
                    'user_id' => (int) $usuarioVendas->usuario_id,
                    'empresa_id' => (int) $usuarioVendas->empresa_id,
                    'perfil_id' => $profile['id'] ?? $fallbackProfileId,
                    'telefone' => $this->nullableString($usuarioVendas->telefone),
                    'endereco' => $this->nullableString($usuarioVendas->endereco),
                    'cidade' => $this->nullableString($usuarioVendas->cidade),
                    'estado' => $this->nullableString($usuarioVendas->estado),
                    'cep' => $this->nullableString($usuarioVendas->cep),
                    'ativo' => (bool) $usuarioVendas->ativo,
                    'data_contratacao' => $this->nullableDate($usuarioVendas->data_contratacao),
                    'created_at' => $this->dateTimeOrNow($usuarioVendas->criado_em),
                    'updated_at' => $this->dateTimeOrNow($usuarioVendas->atualizado_em ?? $usuarioVendas->criado_em),
                ];
            })
            ->all();

        return $this->upsertRows('usuario_vendas', $rows, ['id']);
    }

    private function importCategorias(): int
    {
        $timestamp = now()->toDateTimeString();

        $rows = DB::table('vendas_app_categoria')
            ->orderBy('id')
            ->get()
            ->map(function ($categoria) use ($timestamp) {
                return [
                    'id' => (int) $categoria->id,
                    'empresa_id' => (int) $categoria->empresa_id,
                    'nome' => $this->sanitizeString($categoria->nome) ?? 'Categoria '.$categoria->id,
                    'descricao' => $this->nullableString($categoria->descricao),
                    'ativo' => (bool) $categoria->ativo,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })
            ->all();

        return $this->upsertRows('categorias', $rows, ['id']);
    }

    private function importClientes(): int
    {
        $usedEmails = [];

        $rows = DB::table('vendas_app_cliente')
            ->orderBy('id')
            ->get()
            ->map(function ($cliente) use (&$usedEmails) {
                $empresaId = (int) $cliente->empresa_id;

                return [
                    'id' => (int) $cliente->id,
                    'empresa_id' => $empresaId,
                    'tipo' => $this->sanitizeTipoCliente($cliente->tipo),
                    'nome' => $this->sanitizeString($cliente->nome) ?? 'Cliente '.$cliente->id,
                    'email' => $this->uniqueScopedEmail(
                        $cliente->email,
                        'legacy-cliente',
                        (int) $cliente->id,
                        $empresaId,
                        $usedEmails
                    ),
                    'telefone' => $this->sanitizeString($cliente->telefone) ?? '',
                    'celular' => $this->nullableString($cliente->celular),
                    'cpf_cnpj' => $this->sanitizeDocumentoCliente($cliente->cpf_cnpj, $empresaId, (int) $cliente->id),
                    'rg_ie' => $this->nullableString($cliente->rg_ie),
                    'endereco' => $this->sanitizeString($cliente->endereco) ?? 'Nao informado',
                    'numero' => $this->sanitizeString($cliente->numero) ?? 'S/N',
                    'complemento' => $this->nullableString($cliente->complemento),
                    'bairro' => $this->sanitizeString($cliente->bairro) ?? 'Nao informado',
                    'cidade' => $this->sanitizeString($cliente->cidade) ?? 'Nao informado',
                    'estado' => $this->sanitizeEstado($cliente->estado),
                    'cep' => $this->sanitizeString($cliente->cep) ?? '00000-000',
                    'limite_credito' => $cliente->limite_credito ?? 0,
                    'credito_disponivel' => $cliente->credito_disponivel ?? $cliente->limite_credito ?? 0,
                    'percentual_multa_atraso_padrao' => $cliente->percentual_multa_atraso_padrao ?? 2,
                    'percentual_juros_dia_padrao' => $cliente->percentual_juros_dia_padrao ?? 0.0333,
                    'ativo' => (bool) $cliente->ativo,
                    'created_at' => $this->dateTimeOrNow($cliente->data_cadastro),
                    'updated_at' => $this->dateTimeOrNow($cliente->atualizado_em ?? $cliente->data_cadastro),
                ];
            })
            ->all();

        return $this->upsertRows('clientes', $rows, ['id']);
    }

    private function importProdutos(): int
    {
        $rows = DB::table('vendas_app_produto')
            ->orderBy('id')
            ->get()
            ->map(function ($produto) {
                return [
                    'id' => (int) $produto->id,
                    'empresa_id' => (int) $produto->empresa_id,
                    'codigo' => $this->sanitizeString($produto->codigo) ?? 'LEGACY-PROD-'.$produto->id,
                    'nome' => $this->sanitizeString($produto->nome) ?? 'Produto '.$produto->id,
                    'descricao' => $this->nullableString($produto->descricao),
                    'categoria_id' => $produto->categoria_id ? (int) $produto->categoria_id : null,
                    'preco_custo' => $produto->preco_custo ?? 0,
                    'preco_venda' => $produto->preco_venda ?? 0,
                    'margem_lucro' => $produto->margem_lucro ?? 0,
                    'custo_medio' => $produto->custo_medio ?? 0,
                    'estoque_atual' => (int) ($produto->estoque_atual ?? 0),
                    'estoque_minimo' => (int) ($produto->estoque_minimo ?? 0),
                    'ativo' => (bool) $produto->ativo,
                    'imagem' => $this->nullableString($produto->imagem),
                    'created_at' => $this->dateTimeOrNow($produto->criado_em),
                    'updated_at' => $this->dateTimeOrNow($produto->atualizado_em ?? $produto->criado_em),
                ];
            })
            ->all();

        return $this->upsertRows('produtos', $rows, ['id']);
    }

    private function importProdutoImagens(): int
    {
        $rows = DB::table('vendas_app_produtoimagem')
            ->orderBy('id')
            ->get()
            ->map(function ($imagem) {
                $timestamp = $this->dateTimeOrNow($imagem->criado_em);

                return [
                    'id' => (int) $imagem->id,
                    'empresa_id' => (int) $imagem->empresa_id,
                    'produto_id' => (int) $imagem->produto_id,
                    'imagem' => $this->sanitizeString($imagem->imagem) ?? 'sem-imagem',
                    'ordem' => (int) ($imagem->ordem ?? 0),
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })
            ->all();

        return $this->upsertRows('produto_imagens', $rows, ['id']);
    }

    private function importMovimentacoesEstoque(): int
    {
        $rows = DB::table('vendas_app_movimentacaoestoque')
            ->orderBy('id')
            ->get()
            ->map(function ($movimentacao) {
                $timestamp = $this->dateTimeOrNow($movimentacao->criado_em);

                return [
                    'id' => (int) $movimentacao->id,
                    'empresa_id' => (int) $movimentacao->empresa_id,
                    'produto_id' => (int) $movimentacao->produto_id,
                    'tipo' => $this->sanitizeString($movimentacao->tipo) ?? 'ajuste',
                    'quantidade' => (int) ($movimentacao->quantidade ?? 0),
                    'estoque_anterior' => (int) ($movimentacao->estoque_anterior ?? 0),
                    'estoque_posterior' => (int) ($movimentacao->estoque_posterior ?? 0),
                    'custo_unitario' => $movimentacao->custo_unitario ?? 0,
                    'custo_medio_anterior' => $movimentacao->custo_medio_anterior ?? 0,
                    'custo_medio_posterior' => $movimentacao->custo_medio_posterior ?? 0,
                    'origem_tipo' => $this->nullableString($movimentacao->origem_tipo),
                    'origem_id' => $movimentacao->origem_id ? (int) $movimentacao->origem_id : null,
                    'observacao' => $this->nullableString($movimentacao->observacao),
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })
            ->all();

        return $this->upsertRows('movimentacao_estoques', $rows, ['id']);
    }

    private function importVendas(): int
    {
        $rows = DB::table('vendas_app_venda')
            ->orderBy('numero')
            ->get()
            ->map(function ($venda) {
                $createdAt = $this->dateTimeOrNow($venda->data_venda);

                return [
                    'numero' => (int) $venda->numero,
                    'empresa_id' => (int) $venda->empresa_id,
                    'cliente_id' => (int) $venda->cliente_id,
                    'vendedor_id' => $venda->vendedor_id ? (int) $venda->vendedor_id : null,
                    'data_venda' => $createdAt,
                    'data_entrega' => $this->nullableDate($venda->data_entrega),
                    'status' => $venda->status ?? 'pendente',
                    'subtotal' => $venda->subtotal ?? 0,
                    'desconto' => $venda->desconto ?? 0,
                    'frete' => $venda->frete ?? 0,
                    'total' => $venda->total ?? 0,
                    'percentual_multa_atraso_promissoria' => $venda->percentual_multa_atraso_promissoria ?? 2,
                    'percentual_juros_dia_promissoria' => $venda->percentual_juros_dia_promissoria ?? 0.0333,
                    'emitir_nota_fiscal' => (bool) $venda->emitir_nota_fiscal,
                    'status_nota_fiscal' => $venda->status_nota_fiscal ?? 'nao_emitir',
                    'nota_fiscal_numero' => $this->nullableString($venda->nota_fiscal_numero),
                    'nota_fiscal_serie' => $this->nullableString($venda->nota_fiscal_serie),
                    'nota_fiscal_chave' => $this->nullableString($venda->nota_fiscal_chave),
                    'nota_fiscal_protocolo' => $this->nullableString($venda->nota_fiscal_protocolo),
                    'nota_fiscal_url_pdf' => $this->nullableString($venda->nota_fiscal_url_pdf),
                    'nota_fiscal_url_xml' => $this->nullableString($venda->nota_fiscal_url_xml),
                    'nota_fiscal_mensagem' => $this->nullableString($venda->nota_fiscal_mensagem),
                    'nota_fiscal_payload' => $this->jsonOrNull($venda->nota_fiscal_payload),
                    'nota_fiscal_emitida_em' => $this->nullableDateTime($venda->nota_fiscal_emitida_em),
                    'lucro_total' => $venda->lucro_total ?? 0,
                    'observacoes' => $this->nullableString($venda->observacoes),
                    'cancelamento_motivo' => $this->nullableString($venda->cancelamento_motivo),
                    'created_at' => $createdAt,
                    'updated_at' => $this->dateTimeOrNow($venda->atualizado_em ?? $venda->data_venda),
                ];
            })
            ->all();

        return $this->upsertRows('vendas', $rows, ['numero']);
    }

    private function importItensVenda(): int
    {
        $timestamp = now()->toDateTimeString();

        $rows = DB::table('vendas_app_itemvenda')
            ->orderBy('id')
            ->get()
            ->map(function ($item) use ($timestamp) {
                return [
                    'id' => (int) $item->id,
                    'empresa_id' => (int) $item->empresa_id,
                    'venda_numero' => (int) $item->venda_id,
                    'produto_id' => (int) $item->produto_id,
                    'quantidade' => (int) ($item->quantidade ?? 0),
                    'preco_unitario' => $item->preco_unitario ?? 0,
                    'custo_unitario' => $item->custo_unitario ?? 0,
                    'valor_total' => $item->valor_total ?? 0,
                    'lucro' => $item->lucro ?? 0,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })
            ->all();

        return $this->upsertRows('item_vendas', $rows, ['id']);
    }

    private function importContasReceber(): int
    {
        $rows = DB::table('vendas_app_contareceber')
            ->orderBy('id')
            ->get()
            ->map(function ($conta) {
                return [
                    'id' => (int) $conta->id,
                    'empresa_id' => (int) $conta->empresa_id,
                    'venda_numero' => (int) $conta->venda_id,
                    'cliente_id' => (int) $conta->cliente_id,
                    'valor_original' => $conta->valor_original ?? 0,
                    'valor_pago' => $conta->valor_pago ?? 0,
                    'valor_juros' => $conta->valor_juros ?? 0,
                    'data_vencimento' => $this->dateOrNow($conta->data_vencimento),
                    'data_criacao' => $this->dateTimeOrNow($conta->data_criacao),
                    'status' => $conta->status ?? 'aberta',
                    'observacoes' => $this->nullableString($conta->observacoes),
                    'created_at' => $this->dateTimeOrNow($conta->data_criacao),
                    'updated_at' => $this->dateTimeOrNow($conta->atualizado_em ?? $conta->data_criacao),
                ];
            })
            ->all();

        return $this->upsertRows('contas_receber', $rows, ['id']);
    }

    private function importPagamentosReceber(): int
    {
        $rows = DB::table('vendas_app_pagamentoreceber')
            ->orderBy('id')
            ->get()
            ->map(function ($pagamento) {
                $timestamp = $this->dateTimeOrNow($pagamento->data_pagamento);

                return [
                    'id' => (int) $pagamento->id,
                    'empresa_id' => (int) $pagamento->empresa_id,
                    'conta_id' => (int) $pagamento->conta_id,
                    'promissoria_parcela_id' => $pagamento->promissoria_parcela_id ? (int) $pagamento->promissoria_parcela_id : null,
                    'data_pagamento' => $timestamp,
                    'valor' => $pagamento->valor ?? 0,
                    'valor_abatimento' => $pagamento->valor_abatimento ?? 0,
                    'valor_multa' => $pagamento->valor_multa ?? 0,
                    'valor_juros' => $pagamento->valor_juros ?? 0,
                    'metodo' => $pagamento->metodo ?? 'dinheiro',
                    'observacoes' => $this->nullableString($pagamento->observacoes),
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })
            ->all();

        return $this->upsertRows('pagamentos_receber', $rows, ['id']);
    }

    private function importContasPagar(): int
    {
        $rows = DB::table('vendas_app_contapagar')
            ->orderBy('id')
            ->get()
            ->map(function ($conta) {
                return [
                    'id' => (int) $conta->id,
                    'empresa_id' => (int) $conta->empresa_id,
                    'descricao' => $this->sanitizeString($conta->descricao) ?? 'Conta a pagar '.$conta->id,
                    'fornecedor' => $this->sanitizeString($conta->fornecedor) ?? 'Nao informado',
                    'valor_original' => $conta->valor_original ?? 0,
                    'valor_pago' => $conta->valor_pago ?? 0,
                    'valor_juros' => $conta->valor_juros ?? 0,
                    'data_vencimento' => $this->dateOrNow($conta->data_vencimento),
                    'data_criacao' => $this->dateTimeOrNow($conta->data_criacao),
                    'status' => $conta->status ?? 'aberta',
                    'observacoes' => $this->nullableString($conta->observacoes),
                    'created_at' => $this->dateTimeOrNow($conta->data_criacao),
                    'updated_at' => $this->dateTimeOrNow($conta->atualizado_em ?? $conta->data_criacao),
                ];
            })
            ->all();

        return $this->upsertRows('contas_pagar', $rows, ['id']);
    }

    private function importPagamentosPagar(): int
    {
        $rows = DB::table('vendas_app_pagamentopagar')
            ->orderBy('id')
            ->get()
            ->map(function ($pagamento) {
                $timestamp = $this->dateTimeOrNow($pagamento->data_pagamento);

                return [
                    'id' => (int) $pagamento->id,
                    'empresa_id' => (int) $pagamento->empresa_id,
                    'conta_id' => (int) $pagamento->conta_id,
                    'data_pagamento' => $timestamp,
                    'valor' => $pagamento->valor ?? 0,
                    'metodo' => $pagamento->metodo ?? 'transferencia',
                    'observacoes' => $this->nullableString($pagamento->observacoes),
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })
            ->all();

        return $this->upsertRows('pagamentos_pagar', $rows, ['id']);
    }

    private function importPromissorias(): int
    {
        $rows = DB::table('vendas_app_promissoria')
            ->orderBy('id')
            ->get()
            ->map(function ($promissoria) {
                return [
                    'id' => (int) $promissoria->id,
                    'empresa_id' => (int) $promissoria->empresa_id,
                    'conta_id' => (int) $promissoria->conta_id,
                    'venda_numero' => (int) $promissoria->venda_id,
                    'cliente_id' => (int) $promissoria->cliente_id,
                    'valor_entrada' => $promissoria->valor_entrada ?? 0,
                    'valor_financiado' => $promissoria->valor_financiado ?? 0,
                    'quantidade_parcelas' => (int) ($promissoria->quantidade_parcelas ?? 1),
                    'intervalo_dias' => (int) ($promissoria->intervalo_dias ?? 30),
                    'primeira_parcela_vencimento' => $this->dateOrNow($promissoria->primeira_parcela_vencimento),
                    'percentual_multa_atraso' => $promissoria->percentual_multa_atraso ?? 2,
                    'percentual_juros_dia' => $promissoria->percentual_juros_dia ?? 0.0333,
                    'status' => $promissoria->status ?? 'aberta',
                    'observacoes' => $this->nullableString($promissoria->observacoes),
                    'data_emissao' => $this->dateTimeOrNow($promissoria->data_emissao),
                    'created_at' => $this->dateTimeOrNow($promissoria->data_emissao),
                    'updated_at' => $this->dateTimeOrNow($promissoria->atualizado_em ?? $promissoria->data_emissao),
                ];
            })
            ->all();

        return $this->upsertRows('promissorias', $rows, ['id']);
    }

    private function importPromissoriaParcelas(): int
    {
        $rows = DB::table('vendas_app_promissoriaparcela')
            ->orderBy('id')
            ->get()
            ->map(function ($parcela) {
                return [
                    'id' => (int) $parcela->id,
                    'empresa_id' => (int) $parcela->empresa_id,
                    'promissoria_id' => (int) $parcela->promissoria_id,
                    'numero' => (int) ($parcela->numero ?? 1),
                    'valor_original' => $parcela->valor_original ?? 0,
                    'valor_pago' => $parcela->valor_pago ?? 0,
                    'valor_abatimento' => $parcela->valor_abatimento ?? 0,
                    'data_vencimento' => $this->dateOrNow($parcela->data_vencimento),
                    'status' => $parcela->status ?? 'aberta',
                    'observacoes' => $this->nullableString($parcela->observacoes),
                    'created_at' => $this->dateTimeOrNow($parcela->atualizado_em ?? $parcela->data_vencimento),
                    'updated_at' => $this->dateTimeOrNow($parcela->atualizado_em ?? $parcela->data_vencimento),
                ];
            })
            ->all();

        return $this->upsertRows('promissoria_parcelas', $rows, ['id']);
    }

    private function syncUserRoles(array $profileMap): void
    {
        $fallbackRoleName = Perfil::RECEPCAO;

        foreach (DB::table('vendas_app_usuariovendas')->orderBy('id')->get() as $usuarioVendas) {
            $roleName = $profileMap[(int) $usuarioVendas->perfil_id]['name'] ?? $fallbackRoleName;
            $user = User::query()->find((int) $usuarioVendas->usuario_id);

            if (! $user) {
                continue;
            }

            $user->syncRoles([$roleName]);
        }
    }

    private function upsertRows(string $table, array $rows, array $uniqueBy): int
    {
        if ($rows === []) {
            return 0;
        }

        $updateColumns = array_values(array_diff(array_keys($rows[0]), $uniqueBy));

        DB::table($table)->upsert($rows, $uniqueBy, $updateColumns);

        return count($rows);
    }

    private function canonicalProfileName(object $legacyProfile): string
    {
        $name = Str::lower((string) $legacyProfile->nome);

        if (Str::contains($name, Perfil::ADMIN) || (bool) $legacyProfile->pode_gerenciar_usuarios) {
            return Perfil::ADMIN;
        }

        if (Str::contains($name, Perfil::GERENTE)
            || (bool) $legacyProfile->pode_gerenciar_financeiro
            || (bool) $legacyProfile->pode_gerar_relatorios) {
            return Perfil::GERENTE;
        }

        if (Str::contains($name, Perfil::VENDEDOR) || (bool) $legacyProfile->pode_vender) {
            return Perfil::VENDEDOR;
        }

        return Perfil::RECEPCAO;
    }

    private function allPermissions(): array
    {
        $permissions = [];

        foreach (self::ROLE_DEFINITIONS as $definition) {
            $permissions = array_merge($permissions, $definition['permissions']);
        }

        return array_values(array_unique($permissions));
    }

    private function fallbackProfileId(array $profileMap): int
    {
        $firstProfile = reset($profileMap);

        if (is_array($firstProfile) && isset($firstProfile['id'])) {
            return (int) $firstProfile['id'];
        }

        return (int) DB::table('perfis')->where('nome', Perfil::RECEPCAO)->value('id');
    }

    private function sanitizeTipoCliente(mixed $tipo): string
    {
        $value = Str::upper((string) $tipo);

        return in_array($value, ['PF', 'PJ'], true) ? $value : 'PF';
    }

    private function sanitizeEstado(mixed $estado): string
    {
        $value = Str::upper(substr(trim((string) $estado), 0, 2));

        return $value !== '' ? $value : 'NI';
    }

    private function sanitizeDocumentoCliente(mixed $documento, int $empresaId, int $clienteId): string
    {
        $value = $this->sanitizeString($documento);

        if ($value !== null) {
            return $value;
        }

        return sprintf('LEGACY-%d-%d', $empresaId, $clienteId);
    }

    private function uniqueEmail(mixed $email, string $prefix, int $id, array &$usedEmails): string
    {
        $candidate = $this->nullableEmail($email);

        if ($candidate === null || isset($usedEmails[$candidate])) {
            $candidate = sprintf('%s-%d@local.invalid', $prefix, $id);
        }

        $usedEmails[$candidate] = true;

        return $candidate;
    }

    private function uniqueScopedEmail(mixed $email, string $prefix, int $id, int $scope, array &$usedEmails): string
    {
        $candidate = $this->nullableEmail($email);
        $scopeKey = $scope.':'.$candidate;

        if ($candidate === null || isset($usedEmails[$scopeKey])) {
            $candidate = sprintf('%s-%d-%d@local.invalid', $prefix, $scope, $id);
            $scopeKey = $scope.':'.$candidate;
        }

        $usedEmails[$scopeKey] = true;

        return $candidate;
    }

    private function nullableEmail(mixed $value): ?string
    {
        $email = $this->sanitizeString($value);

        if ($email === null) {
            return null;
        }

        return Str::lower($email);
    }

    private function sanitizeSlug(mixed $slug, mixed $fallback, int $id): string
    {
        $value = $this->sanitizeString($slug);

        if ($value !== null) {
            return $value;
        }

        $fallbackValue = $this->sanitizeString($fallback) ?? 'empresa-'.$id;

        return Str::slug($fallbackValue).'-'.$id;
    }

    private function jsonOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            json_decode($value);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $value;
            }

            return json_encode(['legacy' => $value], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function sanitizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function nullableString(mixed $value): ?string
    {
        return $this->sanitizeString($value);
    }

    private function nullableDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->toDateTimeString();
    }

    private function dateTimeOrNow(mixed $value): string
    {
        return $this->nullableDateTime($value) ?? now()->toDateTimeString();
    }

    private function nullableDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->toDateString();
    }

    private function dateOrNow(mixed $value): string
    {
        return $this->nullableDate($value) ?? now()->toDateString();
    }
}