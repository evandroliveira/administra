<?php

use App\Support\DjangoDataImporter;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

$legacyPasswordFormat = static function (mixed $password): string {
    if (! is_string($password) || $password === '') {
        return 'invalid-empty';
    }

    if (str_starts_with($password, 'pbkdf2_sha256$')) {
        return 'django-pbkdf2-sha256';
    }

    $algoName = password_get_info($password)['algoName'] ?? 'unknown';

    if ($algoName === 'bcrypt') {
        return 'bcrypt';
    }

    if ($algoName !== 'unknown') {
        return 'native-'.$algoName;
    }

    if (preg_match('/^[a-f0-9]{40}$/i', $password) === 1) {
        return 'legacy-sha1';
    }

    return 'legacy-plain-text';
};

$legacyPasswordAction = static function (string $format): string {
    return match ($format) {
        'bcrypt' => 'nenhuma acao',
        'legacy-plain-text' => 'pode rehashar via comando',
        'legacy-sha1', 'django-pbkdf2-sha256' => 'rehash no login',
        'invalid-empty' => 'revisao manual',
        default => 'validar formato',
    };
};

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:doctor', function () {
    $rows = [];
    $failures = 0;
    $warnings = 0;

    $record = function (string $item, string $status, string $detail) use (&$rows, &$failures, &$warnings): void {
        $rows[] = [
            'Item' => $item,
            'Status' => $status,
            'Detalhe' => $detail,
        ];

        if ($status === 'FAIL') {
            $failures++;
        }

        if ($status === 'WARN') {
            $warnings++;
        }
    };

    $check = function (string $item, callable $callback, bool $warnOnly = false) use ($record): void {
        try {
            [$ok, $detail] = $callback();

            $record($item, $ok ? 'OK' : ($warnOnly ? 'WARN' : 'FAIL'), $detail);
        } catch (Throwable $exception) {
            $record($item, $warnOnly ? 'WARN' : 'FAIL', $exception->getMessage());
        }
    };

    $dbConnection = (string) config('database.default');
    $dbConfig = config("database.connections.{$dbConnection}", []);
    $dbDatabase = (string) ($dbConfig['database'] ?? '');
    $sqlitePath = $dbDatabase !== '' ? $dbDatabase : database_path('database.sqlite');
    $requiredWritablePaths = [
        storage_path(),
        storage_path('framework'),
        storage_path('framework/cache'),
        storage_path('framework/sessions'),
        storage_path('framework/views'),
        storage_path('logs'),
        base_path('bootstrap/cache'),
    ];

    $check('APP_KEY', function () {
        $key = (string) config('app.key');

        return [$key !== '', $key !== '' ? 'definida' : 'ausente no ambiente'];
    });

    $check('Rota login', function () {
        return [Route::has('login'), Route::has('login') ? 'rota login registrada' : 'rota login nao encontrada'];
    });

    $check('View auth.login', function () {
        return [view()->exists('auth.login'), view()->exists('auth.login') ? 'view encontrada' : 'view auth.login ausente'];
    });

    foreach ($requiredWritablePaths as $path) {
        $check("Permissao {$path}", function () use ($path) {
            return [is_dir($path) && is_writable($path), is_dir($path) ? ($path.' gravavel') : ($path.' ausente')];
        });
    }

    $check('Banco configurado', function () use ($dbConnection, $dbDatabase) {
        $detail = $dbConnection === 'sqlite'
            ? 'sqlite em '.$dbDatabase
            : $dbConnection.($dbDatabase !== '' ? ' em '.$dbDatabase : ' sem nome de base');

        return [$dbConnection !== '', $detail];
    });

    if ($dbConnection === 'sqlite') {
        $check('Extensao pdo_sqlite', function () {
            return [extension_loaded('pdo_sqlite'), extension_loaded('pdo_sqlite') ? 'pdo_sqlite carregada' : 'pdo_sqlite nao carregada'];
        });

        $check('Arquivo SQLite', function () use ($sqlitePath) {
            return [is_file($sqlitePath), is_file($sqlitePath) ? 'arquivo encontrado em '.$sqlitePath : 'arquivo ausente em '.$sqlitePath];
        });

        $check('Permissao SQLite', function () use ($sqlitePath) {
            $directory = dirname($sqlitePath);
            $fileWritable = is_file($sqlitePath) && is_writable($sqlitePath);
            $dirWritable = is_dir($directory) && is_writable($directory);

            return [$fileWritable && $dirWritable, 'arquivo '.($fileWritable ? 'gravavel' : 'nao gravavel').'; diretorio '.($dirWritable ? 'gravavel' : 'nao gravavel')];
        });
    }

    if (in_array($dbConnection, ['mysql', 'mariadb'], true)) {
        $check('Extensao pdo_mysql', function () {
            return [extension_loaded('pdo_mysql'), extension_loaded('pdo_mysql') ? 'pdo_mysql carregada' : 'pdo_mysql nao carregada'];
        });
    }

    $check('Conexao com banco', function () use ($dbConnection) {
        DB::connection()->getPdo();

        return [true, 'conexao '.$dbConnection.' estabelecida'];
    });

    $check('Tabela users', function () {
        return [DB::connection()->getSchemaBuilder()->hasTable('users'), DB::connection()->getSchemaBuilder()->hasTable('users') ? 'tabela users encontrada' : 'tabela users ausente'];
    });

    $check('Tabela migrations', function () {
        return [DB::connection()->getSchemaBuilder()->hasTable('migrations'), DB::connection()->getSchemaBuilder()->hasTable('migrations') ? 'tabela migrations encontrada' : 'tabela migrations ausente'];
    });

    $check('Manifest Vite', function () {
        $manifestPath = public_path('build/manifest.json');

        return [is_file($manifestPath), is_file($manifestPath) ? 'manifest encontrado em '.$manifestPath : 'manifest ausente em '.$manifestPath.'; a tela pode abrir sem CSS/JS'];
    }, true);

    $check('Link public/storage', function () {
        $publicStoragePath = public_path('storage');

        return [is_link($publicStoragePath) || is_dir($publicStoragePath), (is_link($publicStoragePath) || is_dir($publicStoragePath)) ? 'link public/storage presente' : 'link public/storage ausente'];
    }, true);

    $this->table(['Item', 'Status', 'Detalhe'], $rows);

    if ($failures > 0) {
        $this->error("Diagnostico concluido com {$failures} falha(s) e {$warnings} aviso(s).");
        $this->line('Se o site continuar em 500, envie tambem as ultimas linhas de storage/logs/laravel.log.');

        return 1;
    }

    if ($warnings > 0) {
        $this->warn("Diagnostico concluido sem falhas, mas com {$warnings} aviso(s).");

        return 0;
    }

    $this->info('Diagnostico concluido sem falhas.');

    return 0;
})->purpose('Valida runtime, banco e permissoes para diagnostico rapido de erro 500 em producao.');

Artisan::command('legacy:import-django', function () {
    $summary = app(DjangoDataImporter::class)->import();

    $rows = collect($summary)
        ->map(fn (int $importados, string $tabela) => [
            'Tabela' => str_replace('_', ' ', $tabela),
            'Importados' => $importados,
        ])
        ->values()
        ->all();

    $this->table(['Tabela', 'Importados'], $rows);
})->purpose('Importa os dados do schema Django legado para o schema Laravel atual.');

Artisan::command('legacy:audit-django-import', function () {
    $checks = [
        ['label' => 'empresas.count', 'legacy' => ['vendas_app_empresa', 'count', null], 'current' => ['empresas', 'count', null, ['table' => 'vendas_app_empresa', 'legacy_key' => 'id', 'current_key' => 'id']]],
        ['label' => 'usuarios.count', 'legacy' => ['auth_user', 'count', null], 'current' => ['users', 'count', null, ['table' => 'auth_user', 'legacy_key' => 'id', 'current_key' => 'id']]],
        ['label' => 'usuario_vendas.count', 'legacy' => ['vendas_app_usuariovendas', 'count', null], 'current' => ['usuario_vendas', 'count', null, ['table' => 'vendas_app_usuariovendas', 'legacy_key' => 'id', 'current_key' => 'id']]],
        ['label' => 'clientes.count', 'legacy' => ['vendas_app_cliente', 'count', null], 'current' => ['clientes', 'count', null]],
        ['label' => 'categorias.count', 'legacy' => ['vendas_app_categoria', 'count', null], 'current' => ['categorias', 'count', null]],
        ['label' => 'produtos.count', 'legacy' => ['vendas_app_produto', 'count', null], 'current' => ['produtos', 'count', null]],
        ['label' => 'produto_imagens.count', 'legacy' => ['vendas_app_produtoimagem', 'count', null], 'current' => ['produto_imagens', 'count', null]],
        ['label' => 'movimentacao_estoques.count', 'legacy' => ['vendas_app_movimentacaoestoque', 'count', null], 'current' => ['movimentacao_estoques', 'count', null]],
        ['label' => 'vendas.count', 'legacy' => ['vendas_app_venda', 'count', null], 'current' => ['vendas', 'count', null]],
        ['label' => 'vendas.total', 'legacy' => ['vendas_app_venda', 'sum', 'total'], 'current' => ['vendas', 'sum', 'total']],
        ['label' => 'item_vendas.count', 'legacy' => ['vendas_app_itemvenda', 'count', null], 'current' => ['item_vendas', 'count', null]],
        ['label' => 'item_vendas.total', 'legacy' => ['vendas_app_itemvenda', 'sum', 'valor_total'], 'current' => ['item_vendas', 'sum', 'valor_total']],
        ['label' => 'contas_receber.count', 'legacy' => ['vendas_app_contareceber', 'count', null], 'current' => ['contas_receber', 'count', null]],
        ['label' => 'contas_receber.valor_original', 'legacy' => ['vendas_app_contareceber', 'sum', 'valor_original'], 'current' => ['contas_receber', 'sum', 'valor_original']],
        ['label' => 'pagamentos_receber.count', 'legacy' => ['vendas_app_pagamentoreceber', 'count', null], 'current' => ['pagamentos_receber', 'count', null]],
        ['label' => 'pagamentos_receber.valor', 'legacy' => ['vendas_app_pagamentoreceber', 'sum', 'valor'], 'current' => ['pagamentos_receber', 'sum', 'valor']],
        ['label' => 'promissorias.count', 'legacy' => ['vendas_app_promissoria', 'count', null], 'current' => ['promissorias', 'count', null]],
        ['label' => 'promissoria_parcelas.count', 'legacy' => ['vendas_app_promissoriaparcela', 'count', null], 'current' => ['promissoria_parcelas', 'count', null]],
        ['label' => 'promissoria_parcelas.valor_original', 'legacy' => ['vendas_app_promissoriaparcela', 'sum', 'valor_original'], 'current' => ['promissoria_parcelas', 'sum', 'valor_original']],
        ['label' => 'planos.count', 'legacy' => ['vendas_app_plano', 'count', null], 'current' => ['planos', 'count', null]],
        ['label' => 'assinaturas.count', 'legacy' => ['vendas_app_assinatura', 'count', null], 'current' => ['assinaturas', 'count', null, ['table' => 'vendas_app_assinatura', 'legacy_key' => 'id', 'current_key' => 'id']]],
        ['label' => 'faturas.count', 'legacy' => ['vendas_app_fatura', 'count', null], 'current' => ['faturas', 'count', null]],
        ['label' => 'faturas.valor', 'legacy' => ['vendas_app_fatura', 'sum', 'valor'], 'current' => ['faturas', 'sum', 'valor']],
        ['label' => 'eventos.count', 'legacy' => ['vendas_app_eventowebhook', 'count', null], 'current' => ['evento_webhooks', 'count', null]],
    ];

    $measure = function (array $definition) {
        [$table, $operation, $column, $scope] = array_pad($definition, 4, null);
        $query = DB::table($table);

        if (is_array($scope)) {
            $query->whereIn(
                $scope['current_key'],
                DB::table($scope['table'])->select($scope['legacy_key'])
            );
        }

        return $operation === 'sum'
            ? (float) ($query->sum($column) ?? 0)
            : (int) $query->count();
    };

    $rows = collect($checks)->map(function (array $check) use ($measure) {
        $legacy = $measure($check['legacy']);
        $current = $measure($check['current']);
        $delta = round((float) $current - (float) $legacy, 4);
        $ok = abs($delta) < 0.0001;

        return [
            'Metrica' => $check['label'],
            'Legado' => is_float($legacy) ? number_format($legacy, 2, '.', '') : $legacy,
            'Laravel' => is_float($current) ? number_format($current, 2, '.', '') : $current,
            'Delta' => is_float($delta) ? number_format($delta, 2, '.', '') : $delta,
            'Status' => $ok ? 'OK' : 'DIVERGENTE',
        ];
    })->all();

    $extras = [
        'empresas' => DB::table('empresas')->whereNotIn('id', DB::table('vendas_app_empresa')->select('id'))->count(),
        'users' => DB::table('users')->whereNotIn('id', DB::table('auth_user')->select('id'))->count(),
        'usuario_vendas' => DB::table('usuario_vendas')->whereNotIn('id', DB::table('vendas_app_usuariovendas')->select('id'))->count(),
        'assinaturas' => DB::table('assinaturas')->whereNotIn('id', DB::table('vendas_app_assinatura')->select('id'))->count(),
    ];

    $this->table(['Metrica', 'Legado', 'Laravel', 'Delta', 'Status'], $rows);
    $this->line('Nota: perfis legados foram consolidados nos papéis canônicos admin, gerente, vendedor e recepcao.');

    $extrasDetectados = collect($extras)
        ->filter(fn (int $quantidade) => $quantidade > 0)
        ->map(fn (int $quantidade, string $tabela) => $tabela.'='.$quantidade)
        ->values();

    if ($extrasDetectados->isNotEmpty()) {
        $this->warn('Registros extras criados diretamente no schema Laravel, sem origem no legado: '.$extrasDetectados->implode(', ').'.');
    }
})->purpose('Compara contagens e totais do schema Django legado com as tabelas Laravel importadas.');

Artisan::command('legacy:audit-user-passwords {--show-users=25 : Quantidade maxima de usuarios legados a listar}', function () use ($legacyPasswordFormat, $legacyPasswordAction) {
    $users = DB::table('users')
        ->orderBy('id')
        ->get(['id', 'username', 'email', 'password'])
        ->map(function ($user) use ($legacyPasswordFormat) {
            return [
                'id' => (int) $user->id,
                'username' => (string) $user->username,
                'email' => (string) $user->email,
                'format' => $legacyPasswordFormat($user->password),
            ];
        });

    $summary = $users
        ->groupBy('format')
        ->map(function ($group, string $format) use ($legacyPasswordAction) {
            return [
                'Formato' => $format,
                'Usuarios' => $group->count(),
                'Acao' => $legacyPasswordAction($format),
            ];
        })
        ->sortBy('Formato')
        ->values()
        ->all();

    $this->table(['Formato', 'Usuarios', 'Acao'], $summary);

    $legacyUsers = $users
        ->filter(fn (array $user) => $user['format'] !== 'bcrypt')
        ->values();

    if ($legacyUsers->isEmpty()) {
        $this->info('Todos os usuarios ja utilizam bcrypt.');

        return;
    }

    $limit = max((int) $this->option('show-users'), 0);
    $displayedUsers = $limit === 0 ? collect() : $legacyUsers->take($limit);

    if ($displayedUsers->isNotEmpty()) {
        $rows = $displayedUsers->map(function (array $user) use ($legacyPasswordAction) {
            return [
                'ID' => $user['id'],
                'Usuario' => $user['username'],
                'Email' => $user['email'],
                'Formato' => $user['format'],
                'Acao' => $legacyPasswordAction($user['format']),
            ];
        })->all();

        $this->table(['ID', 'Usuario', 'Email', 'Formato', 'Acao'], $rows);
    }

    if ($legacyUsers->count() > $displayedUsers->count()) {
        $this->line('Usuarios legados adicionais nao exibidos: '.($legacyUsers->count() - $displayedUsers->count()).'.');
    }
})->purpose('Audita os formatos de senha presentes em users e destaca quais ainda dependem de rehash.');

Artisan::command('legacy:rehash-plain-text-passwords {--dry-run : Apenas simula a conversao sem gravar no banco}', function () use ($legacyPasswordFormat) {
    $plainTextUsers = DB::table('users')
        ->orderBy('id')
        ->get(['id', 'username', 'email', 'password'])
        ->filter(fn ($user) => $legacyPasswordFormat($user->password) === 'legacy-plain-text')
        ->values();

    if ($plainTextUsers->isEmpty()) {
        $this->info('Nenhuma senha em texto puro encontrada para conversao.');

        return;
    }

    $rows = $plainTextUsers->map(function ($user) {
        return [
            'ID' => (int) $user->id,
            'Usuario' => (string) $user->username,
            'Email' => (string) $user->email,
        ];
    })->all();

    $this->table(['ID', 'Usuario', 'Email'], $rows);

    if ($this->option('dry-run')) {
        $this->warn('Dry run: nenhuma senha foi alterada.');

        return;
    }

    $now = now();

    DB::transaction(function () use ($plainTextUsers, $now): void {
        foreach ($plainTextUsers as $user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'password' => Hash::make((string) $user->password),
                    'updated_at' => $now,
                ]);
        }
    });

    $this->info($plainTextUsers->count().' senha(s) em texto puro convertida(s) para bcrypt.');
    $this->line('Senhas em SHA-1 e Django PBKDF2 continuam dependentes do login bem-sucedido para rehash, ou de reset manual.');
})->purpose('Converte preventivamente para bcrypt apenas as senhas legadas que ainda estao em texto puro.');
