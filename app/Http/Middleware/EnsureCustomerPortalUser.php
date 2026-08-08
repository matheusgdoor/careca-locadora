<?php

namespace App\Http\Middleware;

use App\Services\CustomerPortal\CustomerPortalAccountService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCustomerPortalUser
{
    public function __construct(
        private readonly CustomerPortalAccountService $accounts,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('customer')->user();

        if (! $user || ! $this->accounts->partnerForUser($user)) {
            abort(403, 'Acesso restrito ao Portal do Cliente.');
        }

        // Faz os controllers do portal enxergarem o usuário do guard customer,
        // sem interferir na sessão web usada pelo painel administrativo.
        $request->setUserResolver(
            static fn () => $user
        );

        return $next($request);
    }
}
