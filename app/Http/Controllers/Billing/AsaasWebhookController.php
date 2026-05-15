<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Support\Billing\AsaasGateway;
use App\Support\Billing\WebhookAuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AsaasWebhookController extends Controller
{
    public function __invoke(Request $request, AsaasGateway $asaasGateway): JsonResponse
    {
        $payload = json_decode($request->getContent() ?: '{}', true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($payload)) {
            return response()->json([
                'detail' => 'Payload JSON inválido.',
            ], 400);
        }

        $token = $request->header('X-Asaas-Webhook-Token')
            ?: $request->header('asaas-access-token')
            ?: $request->header('X-Webhook-Token');

        try {
            ['evento' => $evento, 'fatura' => $fatura, 'assinatura' => $assinatura] = $asaasGateway->processWebhook(
                $payload,
                $token,
            );
        } catch (WebhookAuthorizationException $exception) {
            return response()->json([
                'detail' => $exception->getMessage(),
            ], 403);
        } catch (\Throwable $exception) {
            return response()->json([
                'detail' => $exception->getMessage(),
            ], 400);
        }

        return response()->json([
            'status' => 'ok',
            'evento_id' => $evento->id,
            'fatura_id' => $fatura?->id,
            'assinatura_id' => $assinatura?->id,
        ]);
    }
}