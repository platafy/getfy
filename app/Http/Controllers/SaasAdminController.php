<?php

namespace App\Http\Controllers;

use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\User;
use App\Services\SaasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaasAdminController extends Controller
{
    public function __construct(
        private readonly SaasService $saas
    ) {}

    /**
     * Página principal SaaS: configuração + planos + assinaturas.
     */
    public function index(): Response
    {
        $plans = SaasPlan::orderBy('sort_order')->orderBy('price')->get();
        $subscriptions = SaasSubscription::with(['user', 'plan'])
            ->latest('created_at')
            ->limit(100)
            ->get()
            ->map(fn ($sub) => [
                'id' => $sub->id,
                'user_name' => $sub->user?->name ?? '—',
                'user_email' => $sub->user?->email ?? '—',
                'plan_name' => $sub->plan?->name ?? '—',
                'status' => $sub->status,
                'starts_at' => $sub->starts_at?->format('d/m/Y'),
                'expires_at' => $sub->expires_at?->format('d/m/Y'),
                'amount_paid' => (float) $sub->amount_paid,
                'gateway' => $sub->gateway,
                'is_active' => $sub->isActive(),
            ]);

        return Inertia::render('Saas/AdminIndex', [
            'saasEnabled' => $this->saas->isEnabled(),
            'stats' => $this->saas->getAdminStats(),
            'plans' => $plans,
            'subscriptions' => $subscriptions,
            'intervalLabels' => SaasPlan::intervalLabels(),
        ]);
    }

    /**
     * Habilitar/Desabilitar SaaS.
     */
    public function toggleSaas(Request $request): RedirectResponse
    {
        $this->saas->setEnabled(! $this->saas->isEnabled());

        return back()->with('success', $this->saas->isEnabled()
            ? 'Módulo SaaS habilitado com sucesso!'
            : 'Módulo SaaS desabilitado.');
    }

    /**
     * Criar um novo plano.
     */
    public function storePlan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'interval' => ['required', 'in:monthly,quarterly,semi_annual,annual'],
            'max_products' => ['nullable', 'integer', 'min:1'],
            'max_orders_per_month' => ['nullable', 'integer', 'min:1'],
            'max_students' => ['nullable', 'integer', 'min:1'],
            'is_free' => ['boolean'],
            'is_active' => ['boolean'],
            'is_highlighted' => ['boolean'],
            'features' => ['nullable', 'array'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        // Se marcou como free, preço = 0
        if (! empty($validated['is_free'])) {
            $validated['price'] = 0;
        }

        SaasPlan::create($validated);

        return back()->with('success', 'Plano criado com sucesso!');
    }

    /**
     * Atualizar um plano.
     */
    public function updatePlan(Request $request, SaasPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'interval' => ['required', 'in:monthly,quarterly,semi_annual,annual'],
            'max_products' => ['nullable', 'integer', 'min:1'],
            'max_orders_per_month' => ['nullable', 'integer', 'min:1'],
            'max_students' => ['nullable', 'integer', 'min:1'],
            'is_free' => ['boolean'],
            'is_active' => ['boolean'],
            'is_highlighted' => ['boolean'],
            'features' => ['nullable', 'array'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        if (! empty($validated['is_free'])) {
            $validated['price'] = 0;
        }

        $plan->update($validated);

        return back()->with('success', 'Plano atualizado com sucesso!');
    }

    /**
     * Excluir um plano.
     */
    public function destroyPlan(SaasPlan $plan): RedirectResponse
    {
        // Verificar se tem assinaturas ativas
        if ($plan->activeSubscriptions()->count() > 0) {
            return back()->with('error', 'Não é possível excluir um plano com assinaturas ativas.');
        }

        $plan->delete();

        return back()->with('success', 'Plano excluído com sucesso!');
    }

    /**
     * Cancelar uma assinatura manualmente (admin).
     */
    public function cancelSubscription(SaasSubscription $subscription): RedirectResponse
    {
        $subscription->update(['status' => SaasSubscription::STATUS_CANCELLED]);

        return back()->with('success', 'Assinatura cancelada.');
    }

    /**
     * Atribuir plano manualmente a um infoprodutor.
     */
    public function assignPlan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'plan_id' => ['required', 'exists:saas_plans,id'],
        ]);

        $this->saas->activateSubscription(
            $validated['user_id'],
            $validated['plan_id'],
            'manual',
            null,
            null,
            0
        );

        return back()->with('success', 'Plano atribuído com sucesso!');
    }

    /**
     * Listar infoprodutores para atribuição manual.
     */
    public function getInfoprodutores(): JsonResponse
    {
        $users = User::where('role', User::ROLE_INFOPRODUTOR)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json($users);
    }
}
