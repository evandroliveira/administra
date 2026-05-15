<?php

namespace App\Support\Billing;

use App\Models\Assinatura;
use App\Models\EventoWebhook;
use App\Models\Fatura;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AsaasGateway
{
    public function syncSubscription(Assinatura $assinatura, ?string $billingType = null, $nextDueDate = null): array
    {
        $this->ensureConfigured();

        $assinatura->loadMissing('empresa', 'empresa.usuariosVendas.user', 'plano');

        $this->createOrUpdateCustomer($assinatura);

        $subscriptionResponse = $this->createSubscription(
            $assinatura,
            $billingType,
            $nextDueDate ? Carbon::parse($nextDueDate) : null,
        );

        $fatura = $this->syncFirstInvoice($assinatura);

        if (! $fatura) {
            return $subscriptionResponse;
        }

        return [
            ...$subscriptionResponse,
            'payment_id' => $fatura->external_id ?: '',
            'invoice_url' => $fatura->invoice_url,
            'checkout_url' => $fatura->checkout_url,
        ];
    }

    public function checkoutUrl(array $response): string
    {
        return trim((string) ($response['checkout_url'] ?? $response['invoice_url'] ?? ''));
    }

    public function processWebhook(array $payload, ?string $token = null): array
    {
        $configuredToken = trim((string) config('billing.asaas.webhook_token', ''));
        if ($configuredToken !== '' && ! hash_equals($configuredToken, (string) $token)) {
            throw new WebhookAuthorizationException('Token de webhook inválido para Asaas.');
        }

        $eventType = trim((string) ($payload['event'] ?? 'UNKNOWN')) ?: 'UNKNOWN';
        $paymentData = is_array($payload['payment'] ?? null) ? $payload['payment'] : [];
        $paymentId = trim((string) ($paymentData['id'] ?? $payload['id'] ?? ''));
        $externalId = $paymentId !== '' ? $eventType.':'.$paymentId : $eventType;

        $evento = EventoWebhook::query()->firstOrCreate(
            [
                'provider' => 'asaas',
                'event_type' => $eventType,
                'external_id' => $externalId,
            ],
            [
                'payload' => $payload,
                'status' => 'recebido',
            ]
        );

        $evento->payload = $payload;

        $assinatura = $this->findSubscriptionForWebhook($paymentData, $payload);
        $fatura = null;

        try {
            if ($assinatura && $paymentData !== []) {
                $fatura = $this->saveInvoice($assinatura, $paymentData);

                $evento->fill([
                    'empresa_id' => $assinatura->empresa_id,
                    'assinatura_id' => $assinatura->id,
                    'fatura_id' => $fatura?->id,
                    'status' => 'processado',
                    'erro' => null,
                    'processado_em' => now(),
                ])->save();

                return [
                    'evento' => $evento,
                    'fatura' => $fatura,
                    'assinatura' => $assinatura,
                ];
            }

            $evento->fill([
                'status' => 'ignorado',
                'erro' => 'Evento recebido sem assinatura ou pagamento correlacionado.',
                'processado_em' => now(),
            ])->save();

            return [
                'evento' => $evento,
                'fatura' => null,
                'assinatura' => $assinatura,
            ];
        } catch (\Throwable $exception) {
            $evento->fill([
                'status' => 'erro',
                'erro' => $exception->getMessage(),
                'processado_em' => now(),
            ])->save();

            throw $exception;
        }
    }

    private function createOrUpdateCustomer(Assinatura $assinatura): array
    {
        $payload = $this->customerPayload($assinatura);

        if ($assinatura->gateway_customer_id) {
            $response = $this->request('POST', '/customers/'.$assinatura->gateway_customer_id, $payload);
            $assinatura->forceFill([
                'gateway' => 'asaas',
            ])->save();

            return $response;
        }

        $response = $this->request('POST', '/customers', $payload);

        $assinatura->forceFill([
            'gateway' => 'asaas',
            'gateway_customer_id' => (string) ($response['id'] ?? ''),
        ])->save();

        return $response;
    }

    private function createSubscription(Assinatura $assinatura, ?string $billingType, ?Carbon $nextDueDate): array
    {
        if ((float) $assinatura->plano->valor_mensal <= 0) {
            $assinatura->forceFill([
                'status' => 'ativa',
                'fim_periodo_atual' => $assinatura->ativa_ate ?: $assinatura->fim_periodo_atual,
            ])->save();

            return ['mode' => 'free-plan'];
        }

        if ($assinatura->gateway_subscription_id) {
            $assinatura->forceFill([
                'gateway' => 'asaas',
            ])->save();

            return [
                'id' => $assinatura->gateway_subscription_id,
                'mode' => 'existing-subscription',
            ];
        }

        if (! $assinatura->gateway_customer_id) {
            $this->createOrUpdateCustomer($assinatura);
            $assinatura->refresh();
        }

        $proximoVencimento = $nextDueDate
            ?: $assinatura->trial_ends_at
            ?: $assinatura->ativa_ate
            ?: now();

        $payload = [
            'customer' => $assinatura->gateway_customer_id,
            'billingType' => $billingType ?: config('billing.asaas.billing_type', 'UNDEFINED'),
            'value' => (float) $assinatura->plano->valor_mensal,
            'nextDueDate' => Carbon::parse($proximoVencimento)->toDateString(),
            'cycle' => config('billing.asaas.subscription_cycle', 'MONTHLY'),
            'description' => 'Assinatura '.$assinatura->plano->nome.' - '.$assinatura->empresa->nome,
            'externalReference' => 'assinatura:'.$assinatura->id.':empresa:'.$assinatura->empresa_id,
        ];

        $response = $this->request('POST', '/subscriptions', $payload);

        $assinatura->forceFill([
            'gateway' => 'asaas',
            'gateway_subscription_id' => (string) ($response['id'] ?? ''),
        ])->save();

        return $response;
    }

    private function syncFirstInvoice(Assinatura $assinatura): ?Fatura
    {
        if ((float) $assinatura->plano->valor_mensal <= 0 || ! $assinatura->gateway_subscription_id) {
            return null;
        }

        $response = $this->request('GET', '/subscriptions/'.$assinatura->gateway_subscription_id.'/payments');
        $pagamentos = $response['data'] ?? [];

        foreach ($pagamentos as $pagamento) {
            if (! empty($pagamento['id'])) {
                return $this->saveInvoice($assinatura, $pagamento);
            }
        }

        return null;
    }

    private function saveInvoice(Assinatura $assinatura, array $paymentData): ?Fatura
    {
        $paymentId = trim((string) ($paymentData['id'] ?? ''));
        if ($paymentId === '') {
            return null;
        }

        $fatura = Fatura::query()->updateOrCreate(
            [
                'assinatura_id' => $assinatura->id,
                'external_id' => $paymentId,
            ],
            [
                'empresa_id' => $assinatura->empresa_id,
                'descricao' => (string) ($paymentData['description'] ?? ''),
                'valor' => $this->normalizeDecimal($paymentData['value'] ?? 0),
                'vencimento' => $this->normalizeDate($paymentData['dueDate'] ?? null),
                'pago_em' => $this->normalizeDateTime($paymentData['paymentDate'] ?? null),
                'status' => $this->mapInvoiceStatus($paymentData['status'] ?? null),
                'invoice_url' => (string) ($paymentData['invoiceUrl'] ?? ''),
                'checkout_url' => (string) (($paymentData['bankSlipUrl'] ?? '') ?: ($paymentData['invoiceUrl'] ?? '')),
                'payload' => $paymentData,
            ]
        );

        $this->updateSubscriptionStatusByInvoice($assinatura, $fatura);

        return $fatura;
    }

    private function updateSubscriptionStatusByInvoice(Assinatura $assinatura, Fatura $fatura): void
    {
        if ($fatura->status === 'paga') {
            $assinatura->status = 'ativa';
            $assinatura->fim_periodo_atual = $fatura->vencimento ?: $assinatura->fim_periodo_atual;
            $assinatura->ativa_ate = $fatura->vencimento ?: $assinatura->ativa_ate;
        } elseif ($fatura->status === 'atrasada') {
            $assinatura->status = 'inadimplente';
        } elseif (in_array($fatura->status, ['cancelada', 'estornada'], true) && $assinatura->status !== 'teste') {
            $assinatura->status = 'suspensa';
        }

        $assinatura->save();
    }

    private function customerPayload(Assinatura $assinatura): array
    {
        $empresa = $assinatura->empresa;
        $adminUser = $empresa->usuariosVendas()->with('user')->orderBy('id')->first();
        $usuario = $adminUser?->user;
        $nomeContato = trim((string) ($usuario?->name ?: $empresa->nome));
        $email = trim((string) ($empresa->email ?: $usuario?->email ?: ''));
        $telefone = $this->normalizeDigits($empresa->telefone ?: $adminUser?->telefone ?: '');
        $documento = $this->normalizeDigits($empresa->documento ?: '');

        $payload = [
            'name' => $empresa->nome,
            'email' => $email,
            'externalReference' => 'empresa:'.$empresa->id,
            'notificationDisabled' => false,
        ];

        if ($telefone !== '') {
            $payload['mobilePhone'] = $telefone;
        }

        if ($nomeContato !== '') {
            $payload['company'] = $nomeContato;
        }

        if ($documento !== '') {
            if (! $this->isValidBrazilianDocument($documento)) {
                throw new BillingConfigurationException('O documento da empresa precisa ser um CPF ou CNPJ válido para sincronizar com o Asaas.');
            }

            $payload['cpfCnpj'] = $documento;
        } elseif ((float) $assinatura->plano->valor_mensal > 0) {
            throw new BillingConfigurationException('Informe um CPF ou CNPJ válido na empresa antes de sincronizar a assinatura com o Asaas.');
        }

        return $payload;
    }

    private function findSubscriptionForWebhook(array $paymentData, array $payload): ?Assinatura
    {
        $subscriptionId = trim((string) ($paymentData['subscription'] ?? $payload['subscription'] ?? ''));
        $customerId = trim((string) ($paymentData['customer'] ?? $payload['customer'] ?? ''));

        if ($subscriptionId !== '') {
            $assinatura = Assinatura::query()
                ->with(['empresa', 'plano'])
                ->where('gateway_subscription_id', $subscriptionId)
                ->first();

            if ($assinatura) {
                return $assinatura;
            }
        }

        if ($customerId !== '') {
            return Assinatura::query()
                ->with(['empresa', 'plano'])
                ->where('gateway_customer_id', $customerId)
                ->latest('id')
                ->first();
        }

        return null;
    }

    private function request(string $method, string $endpoint, ?array $payload = null): array
    {
        $this->ensureConfigured();

        $url = rtrim((string) config('billing.asaas.base_url', 'https://api.asaas.com/v3'), '/').'/'.ltrim($endpoint, '/');

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'access_token' => (string) config('billing.asaas.api_key', ''),
            ])
                ->timeout((int) config('billing.asaas.timeout', 30))
                ->acceptJson()
                ->send($method, $url, $payload === null ? [] : ['json' => $payload]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Falha de comunicação com Asaas: '.$exception->getMessage(), previous: $exception);
        }

        if ($response->failed()) {
            throw $this->mapHttpError($response->status(), $response->body(), $response->json());
        }

        return $response->json() ?? [];
    }

    private function mapHttpError(int $statusCode, string $details, ?array $payload = null): RuntimeException
    {
        if ($statusCode === 401) {
            return new BillingConfigurationException('A chave de API do Asaas configurada no ambiente está inválida ou expirada. Atualize ASAAS_API_KEY antes de tentar abrir a página de pagamento.');
        }

        $payload ??= [];
        $errors = $payload['errors'] ?? [];
        $descricao = trim($details);

        if (is_array($errors) && $errors !== []) {
            $mensagens = array_values(array_filter(array_map(
                static fn (array $error): string => trim((string) ($error['description'] ?? '')),
                array_filter($errors, 'is_array')
            )));

            if ($mensagens !== []) {
                $descricao = implode('; ', $mensagens);
            }

            foreach ($errors as $error) {
                if (! is_array($error)) {
                    continue;
                }

                if (strtolower(trim((string) ($error['code'] ?? ''))) === 'not_allowed_ip') {
                    return new BillingConfigurationException('O Asaas recusou a conexão porque este IP não está autorizado. Libere o IP no painel do Asaas antes de sincronizar a cobrança.');
                }
            }
        }

        return new RuntimeException('Asaas retornou erro HTTP '.$statusCode.': '.$descricao);
    }

    private function normalizeDigits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?: '';
    }

    private function isValidBrazilianDocument(string $document): bool
    {
        return match (strlen($document)) {
            11 => $this->isValidCpf($document),
            14 => $this->isValidCnpj($document),
            default => false,
        };
    }

    private function isValidCpf(string $document): bool
    {
        if (strlen($document) !== 11 || count(array_unique(str_split($document))) === 1) {
            return false;
        }

        $firstDigit = $this->calculateVerifierDigit(substr($document, 0, 9), range(10, 2));
        $secondDigit = $this->calculateVerifierDigit(substr($document, 0, 9).$firstDigit, range(11, 2));

        return substr($document, -2) === $firstDigit.$secondDigit;
    }

    private function isValidCnpj(string $document): bool
    {
        if (strlen($document) !== 14 || count(array_unique(str_split($document))) === 1) {
            return false;
        }

        $firstDigit = $this->calculateVerifierDigit(substr($document, 0, 12), [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        $secondDigit = $this->calculateVerifierDigit(substr($document, 0, 12).$firstDigit, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

        return substr($document, -2) === $firstDigit.$secondDigit;
    }

    private function calculateVerifierDigit(string $document, array $weights): string
    {
        $total = 0;

        foreach (str_split($document) as $index => $digit) {
            $total += ((int) $digit) * ((int) $weights[$index]);
        }

        $remainder = $total % 11;
        return (string) ($remainder < 2 ? 0 : 11 - $remainder);
    }

    private function normalizeDecimal($value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function normalizeDate(?string $value): ?string
    {
        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        try {
            return Carbon::parse($text)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeDateTime(?string $value): ?Carbon
    {
        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        try {
            return Carbon::parse($text);
        } catch (\Throwable) {
            return null;
        }
    }

    private function mapInvoiceStatus(?string $gatewayStatus): string
    {
        return match (strtoupper(trim((string) $gatewayStatus))) {
            'RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH' => 'paga',
            'OVERDUE' => 'atrasada',
            'REFUNDED', 'REFUND_REQUESTED' => 'estornada',
            'DELETED', 'CANCELED' => 'cancelada',
            default => 'pendente',
        };
    }

    private function configured(): bool
    {
        return strtolower(trim((string) config('billing.provider', ''))) === 'asaas'
            && trim((string) config('billing.asaas.api_key', '')) !== '';
    }

    private function ensureConfigured(): void
    {
        if (! $this->configured()) {
            throw new BillingConfigurationException('Integração Asaas não configurada no ambiente.');
        }
    }
}