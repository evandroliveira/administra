<?php

namespace App\Support\Billing;

use App\Models\Assinatura;
use App\Models\EventoWebhook;
use App\Models\Fatura;
use App\Support\Brasil\BrazilianDocument;
use App\Support\Brasil\BrazilianPhone;
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

        $fatura = $this->syncActionableInvoice($assinatura);

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

    public function regenerateCharge(Assinatura $assinatura, ?string $billingType = null, $dueDate = null): array
    {
        $this->ensureConfigured();

        $assinatura->loadMissing('empresa', 'empresa.usuariosVendas.user', 'plano');

        if ((float) $assinatura->plano->valor_mensal <= 0 || ! $assinatura->gateway_subscription_id) {
            return $this->syncSubscription($assinatura, $billingType, $dueDate);
        }

        $this->createOrUpdateCustomer($assinatura);

        $dataVencimento = $dueDate ? Carbon::parse($dueDate) : now();
        $faturas = $this->syncSubscriptionInvoices($assinatura);
        $faturaAcionavel = $this->selectBestActionableOpenInvoice($faturas);

        if ($faturaAcionavel) {
            return $this->buildInvoiceResponse($faturaAcionavel, 'existing-open-invoice');
        }

        $faturaEmAberto = $this->selectBestOpenInvoice($faturas);
        if ($faturaEmAberto && $faturaEmAberto->external_id) {
            $faturaAtualizada = $this->updatePaymentCharge($assinatura, $faturaEmAberto, $billingType, $dataVencimento);

            return $this->buildInvoiceResponse($faturaAtualizada, 'updated-payment');
        }

        $novaFatura = $this->createRecoveryPayment($assinatura, $billingType, $dataVencimento);

        return $this->buildInvoiceResponse($novaFatura, 'new-payment');
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
        $proximoVencimento = $nextDueDate
            ?: $assinatura->trial_ends_at
            ?: $assinatura->ativa_ate
            ?: now();

        if ((float) $assinatura->plano->valor_mensal <= 0) {
            if ($assinatura->gateway_subscription_id) {
                $this->request('PUT', '/subscriptions/'.$assinatura->gateway_subscription_id, [
                    'status' => 'INACTIVE',
                    'description' => 'Assinatura '.$assinatura->plano->nome.' - '.$assinatura->empresa->nome,
                    'externalReference' => 'assinatura:'.$assinatura->id.':empresa:'.$assinatura->empresa_id,
                ]);
            }

            $assinatura->forceFill([
                'status' => 'ativa',
                'gateway' => $assinatura->gateway ?: ($assinatura->gateway_subscription_id ? 'asaas' : null),
                'fim_periodo_atual' => $assinatura->ativa_ate ?: $assinatura->fim_periodo_atual,
            ])->save();

            return ['mode' => 'free-plan'];
        }

        if ($assinatura->gateway_subscription_id) {
            $response = $this->request('PUT', '/subscriptions/'.$assinatura->gateway_subscription_id, [
                'billingType' => $billingType ?: config('billing.asaas.billing_type', 'UNDEFINED'),
                'status' => 'ACTIVE',
                'value' => (float) $assinatura->plano->valor_mensal,
                'nextDueDate' => Carbon::parse($proximoVencimento)->toDateString(),
                'cycle' => config('billing.asaas.subscription_cycle', 'MONTHLY'),
                'description' => 'Assinatura '.$assinatura->plano->nome.' - '.$assinatura->empresa->nome,
                'externalReference' => 'assinatura:'.$assinatura->id.':empresa:'.$assinatura->empresa_id,
                'updatePendingPayments' => true,
            ]);

            $assinatura->forceFill([
                'gateway' => 'asaas',
            ])->save();

            return [
                ...$response,
                'id' => (string) ($response['id'] ?? $assinatura->gateway_subscription_id),
                'mode' => 'updated-subscription',
            ];
        }

        if (! $assinatura->gateway_customer_id) {
            $this->createOrUpdateCustomer($assinatura);
            $assinatura->refresh();
        }

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

    private function syncActionableInvoice(Assinatura $assinatura): ?Fatura
    {
        $faturas = $this->syncSubscriptionInvoices($assinatura);

        if ($faturas === []) {
            return null;
        }

        return $this->selectBestInvoice($faturas);
    }

    /**
     * @return array<int, Fatura>
     */
    private function syncSubscriptionInvoices(Assinatura $assinatura): array
    {
        if ((float) $assinatura->plano->valor_mensal <= 0 || ! $assinatura->gateway_subscription_id) {
            return [];
        }

        $response = $this->request('GET', '/subscriptions/'.$assinatura->gateway_subscription_id.'/payments');
        $pagamentos = $response['data'] ?? [];
        $faturas = [];

        foreach ($pagamentos as $pagamento) {
            if (! empty($pagamento['id'])) {
                $fatura = $this->saveInvoice($assinatura, $pagamento);

                if ($fatura) {
                    $faturas[] = $fatura;
                }
            }
        }

        return $faturas;
    }

    private function saveInvoice(Assinatura $assinatura, array $paymentData): ?Fatura
    {
        $paymentId = trim((string) ($paymentData['id'] ?? ''));
        if ($paymentId === '') {
            return null;
        }

        $faturaExistente = Fatura::query()
            ->where('assinatura_id', $assinatura->id)
            ->where('external_id', $paymentId)
            ->first();

        if ($faturaExistente && $this->shouldPreserveArchivedInvoice($faturaExistente)) {
            return $faturaExistente;
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

    private function shouldPreserveArchivedInvoice(Fatura $fatura): bool
    {
        return $fatura->status === 'cancelada'
            && (($fatura->payload['local_cancellation']['reason'] ?? null) === 'migrated_to_free_plan');
    }

    private function updatePaymentCharge(Assinatura $assinatura, Fatura $fatura, ?string $billingType, Carbon $dueDate): Fatura
    {
        $response = $this->request('PUT', '/payments/'.$fatura->external_id, [
            'billingType' => $billingType ?: config('billing.asaas.billing_type', 'UNDEFINED'),
            'dueDate' => $dueDate->toDateString(),
            'value' => (float) $assinatura->plano->valor_mensal,
            'description' => $fatura->descricao ?: 'Assinatura '.$assinatura->plano->nome.' - '.$assinatura->empresa->nome,
        ]);

        return $this->saveInvoice($assinatura, $response) ?? $fatura;
    }

    private function createRecoveryPayment(Assinatura $assinatura, ?string $billingType, Carbon $dueDate): Fatura
    {
        if (! $assinatura->gateway_customer_id) {
            $this->createOrUpdateCustomer($assinatura);
            $assinatura->refresh();
        }

        $response = $this->request('POST', '/payments', [
            'customer' => $assinatura->gateway_customer_id,
            'billingType' => $billingType ?: config('billing.asaas.billing_type', 'UNDEFINED'),
            'value' => (float) $assinatura->plano->valor_mensal,
            'dueDate' => $dueDate->toDateString(),
            'description' => 'Recuperação da assinatura '.$assinatura->plano->nome.' - '.$assinatura->empresa->nome,
            'externalReference' => 'assinatura:'.$assinatura->id.':empresa:'.$assinatura->empresa_id.':recuperacao',
        ]);

        return $this->saveInvoice($assinatura, $response)
            ?? throw new RuntimeException('O Asaas não retornou os dados da nova cobrança gerada para a assinatura.');
    }

    private function buildInvoiceResponse(Fatura $fatura, string $mode): array
    {
        return [
            'mode' => $mode,
            'payment_id' => $fatura->external_id ?: '',
            'invoice_url' => $fatura->invoice_url,
            'checkout_url' => $fatura->checkout_url,
        ];
    }

    /**
     * @param  array<int, Fatura>  $faturas
     */
    private function selectBestInvoice(array $faturas): ?Fatura
    {
        usort($faturas, function (Fatura $left, Fatura $right): int {
            $scoreComparison = $this->invoicePriority($left) <=> $this->invoicePriority($right);
            if ($scoreComparison !== 0) {
                return $scoreComparison;
            }

            $leftDue = $left->vencimento?->getTimestamp() ?? 0;
            $rightDue = $right->vencimento?->getTimestamp() ?? 0;
            if ($leftDue !== $rightDue) {
                return $rightDue <=> $leftDue;
            }

            return $right->id <=> $left->id;
        });

        return $faturas[0] ?? null;
    }

    private function invoicePriority(Fatura $fatura): int
    {
        $hasActionableUrl = trim((string) ($fatura->checkout_url ?: $fatura->invoice_url)) !== '';
        $isOpen = in_array($fatura->status, ['pendente', 'atrasada'], true);

        return match (true) {
            $isOpen && $hasActionableUrl => 0,
            $isOpen => 1,
            $hasActionableUrl => 2,
            default => 3,
        };
    }

    /**
     * @param  array<int, Fatura>  $faturas
     */
    private function selectBestOpenInvoice(array $faturas): ?Fatura
    {
        return $this->selectBestInvoice(array_values(array_filter(
            $faturas,
            static fn (Fatura $fatura): bool => in_array($fatura->status, ['pendente', 'atrasada'], true)
        )));
    }

    /**
     * @param  array<int, Fatura>  $faturas
     */
    private function selectBestActionableOpenInvoice(array $faturas): ?Fatura
    {
        return $this->selectBestInvoice(array_values(array_filter(
            $faturas,
            fn (Fatura $fatura): bool => in_array($fatura->status, ['pendente', 'atrasada'], true)
                && $this->invoiceActionUrl($fatura) !== ''
        )));
    }

    private function invoiceActionUrl(?Fatura $fatura): string
    {
        return trim((string) (($fatura?->checkout_url ?: $fatura?->invoice_url) ?? ''));
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
        $telefone = BrazilianPhone::digits((string) ($empresa->telefone ?: $adminUser?->telefone ?: ''));
        $documento = BrazilianDocument::digits((string) ($empresa->documento ?: ''));

        $payload = [
            'name' => $empresa->nome,
            'email' => $email,
            'externalReference' => 'empresa:'.$empresa->id,
            'notificationDisabled' => false,
        ];

        if ($telefone !== '') {
            if (! BrazilianPhone::isValid($telefone)) {
                throw new BillingConfigurationException('Informe um telefone válido com DDD na empresa antes de sincronizar a assinatura com o Asaas.');
            }

            $payload['mobilePhone'] = $telefone;
        }

        if ($nomeContato !== '') {
            $payload['company'] = $nomeContato;
        }

        if ($documento !== '') {
            if (! BrazilianDocument::isValid($documento)) {
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