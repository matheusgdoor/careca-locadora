<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Services\CustomerPortal\CustomerPortalAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

final class CustomerPortalAuthController extends Controller
{
    public function login(): Response|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('customer.dashboard');
        }

        return Inertia::render('customer/login');
    }

    public function firstAccess(): Response|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('customer.dashboard');
        }

        return Inertia::render('customer/first-access');
    }

    public function authenticate(
        Request $request,
        CustomerPortalAccountService $accounts,
    ): RedirectResponse {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $user = $accounts->authenticate(
            $data['identifier'],
            $data['password'],
        );

        Auth::guard('customer')->login(
            $user,
            (bool) ($data['remember'] ?? false)
        );

        $request->session()->regenerate();

        return redirect()->route('customer.dashboard');
    }

    public function registerFirstAccess(
        Request $request,
        CustomerPortalAccountService $accounts,
    ): RedirectResponse {
        $data = $request->validate([
            'document' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:190'],
            'password' => [
                'required',
                'confirmed',
                Password::defaults(),
            ],
        ]);

        $partner = $accounts->resolvePartner(
            $data['document'],
            $data['email'],
        );

        $user = $accounts->createOrLink(
            $partner,
            $data['password'],
        );

        Auth::guard('customer')->login($user);
        $request->session()->regenerate();

        return redirect()->route('customer.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();

        // Não invalida a sessão web inteira: o painel administrativo pode
        // continuar autenticado em outra guarda na mesma sessão.
        $request->session()->regenerateToken();

        return redirect()->route('customer.login');
    }
}
