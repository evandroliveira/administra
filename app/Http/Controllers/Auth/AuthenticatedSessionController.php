<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\Billing\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, BillingService $billingService): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user()?->loadMissing('usuarioVendas.empresa', 'usuarioVendas.empresa.assinaturaAtual.plano');

        if (! $user?->usuarioVendas?->ativo || ! $user->usuarioVendas?->empresa) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'email' => 'Seu acesso está inativo ou sem empresa vinculada.',
            ]);
        }

        $request->session()->regenerate();

        $empresa = $user->usuarioVendas->empresa;

        if (! $empresa->ativa) {
            return redirect()
                ->route('assinatura.show')
                ->with('warning', 'A empresa está inativa. Regularize o cadastro para continuar.');
        }

        $assinatura = $empresa->assinaturaAtual ?? $billingService->ensureCurrentSubscription($empresa);
        if ($assinatura->precisaRegularizar()) {
            $message = $assinatura->status === 'suspensa'
                ? 'A assinatura da empresa foi suspensa automaticamente por atraso acima da carência configurada.'
                : 'A assinatura da empresa precisa de regularização para liberar o uso do sistema.';

            return redirect()
                ->route('assinatura.show')
                ->with('warning', $message);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
