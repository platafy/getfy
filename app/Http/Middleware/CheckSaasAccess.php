<?php

namespace App\Http\Middleware;

use App\Services\SaasService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSaasAccess
{
    public function __construct(
        private readonly SaasService $saas
    ) {}

    /**
     * Verifica se o infoprodutor tem um plano SaaS ativo.
     * Se o SaaS não está habilitado, permite tudo.
     * Admins sempre passam.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->saas->isEnabled()) {
            return $next($request);
        }

        $user = $request->user();

        // Admins passam sempre
        if (! $user || $user->isAdmin()) {
            return $next($request);
        }

        // Verificar acesso
        if (! $this->saas->hasAccess($user->id)) {
            // Se é infoprodutor, tentar atribuir plano free
            if ($user->isInfoprodutor()) {
                $this->saas->assignFreePlan($user);

                // Verificar novamente
                if ($this->saas->hasAccess($user->id)) {
                    return $next($request);
                }
            }

            // Redirecionar para página de planos
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Você precisa de um plano ativo para acessar este recurso.',
                    'upgrade' => true,
                ], 403);
            }

            return redirect()->route('saas.my-plan')
                ->with('warning', 'Você precisa de um plano ativo para acessar este recurso.');
        }

        return $next($request);
    }
}
