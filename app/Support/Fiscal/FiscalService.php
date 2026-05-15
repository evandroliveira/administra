<?php

namespace App\Support\Fiscal;

use App\Models\Venda;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class FiscalService
{
    public function enabled(): bool
    {
        return (bool) config('services.fiscal.enabled', false);
    }

    public function provider(): string
    {
        return strtolower(trim((string) config('services.fiscal.provider', '')));
    }

    public function providerLabel(): string
    {
        if (! $this->enabled()) {
            return 'Desabilitado';
        }

        return match ($this->provider()) {
            'mock' => 'Simulação local',
            'custom_api' => trim((string) config('services.fiscal.provider_name', '')) ?: 'API fiscal externa',
            '' => 'Não configurado',
            default => $this->provider(),
        };
    }

    public function configured(): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        return match ($this->provider()) {
            'mock' => true,
            'custom_api' => trim((string) config('services.fiscal.api_url', '')) !== '',
            default => false,
        };
    }

    public function requestEmission(Venda $venda): Venda
    {
        $venda->loadMissing(['empresa', 'cliente', 'itens.produto']);

        if (! $this->enabled()) {
            return $this->updateStatus(
                $venda,
                'nao_emitir',
                'Integração fiscal desabilitada no sistema.',
                ['emitir_nota_fiscal' => false]
            );
        }

        $provider = $this->provider();

        if ($provider === '') {
            return $this->updateStatus(
                $venda,
                'pendente',
                'Integração fiscal não configurada. A venda foi salva e a nota fiscal pode ser emitida depois.',
                ['emitir_nota_fiscal' => true]
            );
        }

        $resultado = match ($provider) {
            'mock' => $this->emitMock($venda),
            'custom_api' => $this->emitCustomApi($venda),
            default => throw new FiscalConfigurationException('FISCAL_PROVIDER inválido: '.$provider.'.'),
        };

        return $this->updateStatus(
            $venda,
            (string) ($resultado['status'] ?? 'emitida'),
            (string) ($resultado['mensagem'] ?? ''),
            [
                'emitir_nota_fiscal' => true,
                'numero' => $resultado['numero'] ?? null,
                'serie' => $resultado['serie'] ?? null,
                'chave' => $resultado['chave'] ?? null,
                'protocolo' => $resultado['protocolo'] ?? null,
                'url_pdf' => $resultado['url_pdf'] ?? null,
                'url_xml' => $resultado['url_xml'] ?? null,
                'payload' => $resultado['payload'] ?? null,
                'emitida_em' => $resultado['emitida_em'] ?? now(),
            ]
        );
    }

    public function markPending(Venda $venda, string $mensagem): Venda
    {
        return $this->updateStatus($venda, 'pendente', $mensagem, ['emitir_nota_fiscal' => true]);
    }

    public function markFailure(Venda $venda, string $mensagem): Venda
    {
        return $this->updateStatus($venda, 'erro', $mensagem, ['emitir_nota_fiscal' => true]);
    }

    public function buildPayload(Venda $venda): array
    {
        $venda->loadMissing(['empresa', 'cliente', 'itens.produto']);

        return [
            'provider' => $this->provider(),
            'sale' => [
                'id' => $venda->numero,
                'status' => $venda->status,
                'issuedAt' => optional($venda->data_venda)->toIso8601String(),
                'subtotal' => (float) $venda->subtotal,
                'discount' => (float) $venda->desconto,
                'shipping' => (float) $venda->frete,
                'total' => (float) $venda->total,
                'notes' => $venda->observacoes,
            ],
            'issuer' => [
                'id' => $venda->empresa_id,
                'name' => $venda->empresa?->nome,
                'document' => $venda->empresa?->documento,
                'email' => $venda->empresa?->email,
                'phone' => $venda->empresa?->telefone,
            ],
            'customer' => [
                'id' => $venda->cliente_id,
                'name' => $venda->cliente?->nome,
                'type' => $venda->cliente?->tipo,
                'document' => $venda->cliente?->cpf_cnpj,
                'email' => $venda->cliente?->email,
                'phone' => $venda->cliente?->telefone,
                'mobile' => $venda->cliente?->celular,
                'address' => [
                    'street' => $venda->cliente?->endereco,
                    'number' => $venda->cliente?->numero,
                    'complement' => $venda->cliente?->complemento,
                    'district' => $venda->cliente?->bairro,
                    'city' => $venda->cliente?->cidade,
                    'state' => $venda->cliente?->estado,
                    'zipcode' => $venda->cliente?->cep,
                ],
            ],
            'items' => $venda->itens->map(fn ($item) => [
                'productId' => $item->produto_id,
                'code' => $item->produto?->codigo,
                'name' => $item->produto?->nome,
                'quantity' => (int) $item->quantidade,
                'unitPrice' => (float) $item->preco_unitario,
                'totalPrice' => (float) $item->valor_total,
            ])->values()->all(),
        ];
    }

    private function emitMock(Venda $venda): array
    {
        $emitidaEm = now();
        $serie = trim((string) config('services.fiscal.series', '1')) ?: '1';
        $numero = sprintf('%06d', (int) $venda->numero);
        $chave = 'MOCKNFE'.$emitidaEm->format('YmdHis').sprintf('%08d', (int) $venda->numero);
        $payload = $this->buildPayload($venda);
        $payload['mockResponse'] = [
            'numero' => $numero,
            'serie' => $serie,
            'chave' => $chave,
        ];

        return [
            'status' => 'emitida',
            'numero' => $numero,
            'serie' => $serie,
            'chave' => $chave,
            'protocolo' => 'MOCK-'.sprintf('%06d', (int) $venda->numero),
            'mensagem' => 'Nota fiscal emitida em modo de simulação local.',
            'payload' => $payload,
            'emitida_em' => $emitidaEm,
        ];
    }

    private function emitCustomApi(Venda $venda): array
    {
        $apiUrl = trim((string) config('services.fiscal.api_url', ''));

        if ($apiUrl === '') {
            throw new FiscalConfigurationException('FISCAL_API_URL não configurada.');
        }

        $headers = [
            'Accept' => 'application/json',
        ];

        $token = trim((string) config('services.fiscal.token', ''));
        $authHeader = trim((string) config('services.fiscal.auth_header', 'Authorization'));
        $authPrefix = trim((string) config('services.fiscal.auth_prefix', 'Bearer'));
        if ($token !== '' && $authHeader !== '') {
            $headers[$authHeader] = $authPrefix !== '' ? trim($authPrefix.' '.$token) : $token;
        }

        $timeout = max((int) config('services.fiscal.timeout', 15), 1);

        try {
            $response = Http::withHeaders($headers)
                ->timeout($timeout)
                ->post($apiUrl, $this->buildPayload($venda));

            $response->throw();
        } catch (ConnectionException $exception) {
            throw new FiscalEmissionException('Não foi possível acessar a API fiscal: '.$exception->getMessage(), 0, $exception);
        } catch (RequestException $exception) {
            $status = $exception->response?->status();
            $detalhe = trim((string) $exception->response?->body());
            throw new FiscalEmissionException('API fiscal retornou erro HTTP '.$status.': '.($detalhe !== '' ? $detalhe : $exception->getMessage()), 0, $exception);
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new FiscalEmissionException('A API fiscal retornou uma resposta JSON inválida.');
        }

        return $this->normalizeResponse($data);
    }

    private function normalizeResponse(array $data): array
    {
        $status = strtolower(trim((string) ($data['status'] ?? $data['invoiceStatus'] ?? 'emitida')));

        if (! in_array($status, ['pendente', 'emitida', 'erro'], true)) {
            $status = 'emitida';
        }

        return [
            'status' => $status,
            'numero' => $data['numero'] ?? $data['invoiceNumber'] ?? $data['number'] ?? '',
            'serie' => $data['serie'] ?? $data['series'] ?? '',
            'chave' => $data['chave'] ?? $data['chaveAcesso'] ?? $data['accessKey'] ?? '',
            'protocolo' => $data['protocolo'] ?? $data['protocol'] ?? $data['reference'] ?? '',
            'url_pdf' => $data['url_pdf'] ?? $data['pdfUrl'] ?? '',
            'url_xml' => $data['url_xml'] ?? $data['xmlUrl'] ?? '',
            'mensagem' => $data['mensagem'] ?? $data['message'] ?? '',
            'payload' => $data,
            'emitida_em' => $status === 'emitida' ? now() : null,
        ];
    }

    private function updateStatus(Venda $venda, string $status, string $mensagem = '', array $campos = []): Venda
    {
        $venda->emitir_nota_fiscal = (bool) ($campos['emitir_nota_fiscal'] ?? ($status !== 'nao_emitir'));
        $venda->status_nota_fiscal = $status;
        $venda->nota_fiscal_mensagem = $mensagem;
        $venda->nota_fiscal_numero = array_key_exists('numero', $campos) ? (string) ($campos['numero'] ?? '') : (string) $venda->nota_fiscal_numero;
        $venda->nota_fiscal_serie = array_key_exists('serie', $campos) ? (string) ($campos['serie'] ?? '') : (string) $venda->nota_fiscal_serie;
        $venda->nota_fiscal_chave = array_key_exists('chave', $campos) ? (string) ($campos['chave'] ?? '') : (string) $venda->nota_fiscal_chave;
        $venda->nota_fiscal_protocolo = array_key_exists('protocolo', $campos) ? (string) ($campos['protocolo'] ?? '') : (string) $venda->nota_fiscal_protocolo;
        $venda->nota_fiscal_url_pdf = array_key_exists('url_pdf', $campos) ? (string) ($campos['url_pdf'] ?? '') : (string) $venda->nota_fiscal_url_pdf;
        $venda->nota_fiscal_url_xml = array_key_exists('url_xml', $campos) ? (string) ($campos['url_xml'] ?? '') : (string) $venda->nota_fiscal_url_xml;
        $venda->nota_fiscal_payload = array_key_exists('payload', $campos) ? ($campos['payload'] ?? []) : ($venda->nota_fiscal_payload ?? []);

        if ($status === 'emitida') {
            $venda->nota_fiscal_emitida_em = $campos['emitida_em'] instanceof Carbon
                ? $campos['emitida_em']
                : ($campos['emitida_em'] ?? now());
        }

        $venda->save();

        return $venda->refresh();
    }
}