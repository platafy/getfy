<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\SaasUsageCounter;
use App\Models\Setting;
use App\Models\User;

class SaasService
{
    /**
     * Verifica se o módulo SaaS está habilitado.
     */
    public function isEnabled(): bool
    {
        return (bool) Setting::get('saas_enabled', false);
    }

    /**
     * Habilita ou desabilita o módulo SaaS.
     */
    public function setEnabled(bool $enabled): void
    {
        Setting::set('saas_enabled', $enabled ? '1' : '0');
    }

    /**
     * Retorna a assinatura SaaS ativa do infoprodutor.
     */
    public function getActiveSubscription(int $userId): ?SaasSubscription
    {
        return SaasSubscription::forUser($userId)
            ->active()
            ->with('plan')
            ->latest('starts_at')
            ->first();
    }

    /**
     * Retorna o plano SaaS ativo do infoprodutor.
     */
    public function getUserPlan(int $userId): ?SaasPlan
    {
        $sub = $this->getActiveSubscription($userId);
        return $sub?->plan;
    }

    /**
     * Verifica se o infoprodutor tem acesso (plano ativo).
     * Se SaaS não está habilitado, todos têm acesso.
     */
    public function hasAccess(int $userId): bool
    {
        if (! $this->isEnabled()) {
            return true;
        }

        return $this->getActiveSubscription($userId) !== null;
    }

    /**
     * Atribui o plano free automaticamente ao infoprodutor.
     */
    public function assignFreePlan(User $user): bool
    {
        if (! $this->isEnabled()) {
            return true;
        }

        // Já tem plano?
        if ($this->getActiveSubscription($user->id)) {
            return true;
        }

        // Já foi atribuído antes?
        if ($user->saas_free_plan_assigned) {
            return true;
        }

        // Buscar plano free
        $freePlan = SaasPlan::free()->first();
        if (! $freePlan) {
            return false;
        }

        // Criar assinatura free
        SaasSubscription::create([
            'user_id' => $user->id,
            'saas_plan_id' => $freePlan->id,
            'status' => SaasSubscription::STATUS_ACTIVE,
            'starts_at' => now()->toDateString(),
            'expires_at' => now()->addDays($freePlan->intervalDays())->toDateString(),
            'gateway' => 'free',
            'amount_paid' => 0,
        ]);

        // Marcar como atribuído
        $user->update(['saas_free_plan_assigned' => true]);

        return true;
    }

    /**
     * Verifica se o infoprodutor pode criar um novo produto.
     * Retorna ['allowed' => bool, 'message' => string]
     */
    public function checkProductLimit(int $userId): array
    {
        if (! $this->isEnabled()) {
            return ['allowed' => true, 'message' => ''];
        }

        $plan = $this->getUserPlan($userId);

        if (! $plan) {
            return [
                'allowed' => false,
                'message' => 'Você precisa ter um plano ativo para criar produtos.',
                'upgrade' => true,
            ];
        }

        // Sem limite
        if ($plan->max_products === null) {
            return ['allowed' => true, 'message' => ''];
        }

        // Contar produtos do infoprodutor
        $count = Product::where('tenant_id', $userId)->count();

        if ($count >= $plan->max_products) {
            return [
                'allowed' => false,
                'message' => "Você atingiu o limite de {$plan->max_products} produtos do seu plano ({$plan->name}).",
                'upgrade' => true,
            ];
        }

        return ['allowed' => true, 'message' => ''];
    }

    /**
     * Verifica se o infoprodutor pode criar um novo pedido neste mês.
     * Retorna ['allowed' => bool, 'message' => string]
     */
    public function checkOrderLimit(int $userId): array
    {
        if (! $this->isEnabled()) {
            return ['allowed' => true, 'message' => ''];
        }

        $plan = $this->getUserPlan($userId);

        if (! $plan) {
            return [
                'allowed' => false,
                'message' => 'Você precisa ter um plano ativo para receber pedidos.',
                'upgrade' => true,
            ];
        }

        // Sem limite
        if ($plan->max_orders_per_month === null) {
            return ['allowed' => true, 'message' => ''];
        }

        $counter = SaasUsageCounter::currentMonth($userId);

        if ($counter->orders_count >= $plan->max_orders_per_month) {
            return [
                'allowed' => false,
                'message' => "Você atingiu o limite de {$plan->max_orders_per_month} pedidos/mês do seu plano ({$plan->name}).",
                'upgrade' => true,
            ];
        }

        return ['allowed' => true, 'message' => ''];
    }

    /**
     * Ativa uma assinatura após pagamento confirmado.
     */
    public function activateSubscription(
        int $userId,
        int $planId,
        string $gateway,
        ?string $gatewayPaymentId = null,
        ?string $gatewaySubscriptionId = null,
        ?float $amountPaid = null
    ): SaasSubscription {
        // Cancelar assinaturas anteriores
        SaasSubscription::forUser($userId)
            ->active()
            ->update(['status' => SaasSubscription::STATUS_CANCELLED]);

        $plan = SaasPlan::findOrFail($planId);

        return SaasSubscription::create([
            'user_id' => $userId,
            'saas_plan_id' => $plan->id,
            'status' => SaasSubscription::STATUS_ACTIVE,
            'starts_at' => now()->toDateString(),
            'expires_at' => now()->addDays($plan->intervalDays())->toDateString(),
            'gateway' => $gateway,
            'gateway_payment_id' => $gatewayPaymentId,
            'gateway_subscription_id' => $gatewaySubscriptionId,
            'amount_paid' => $amountPaid ?? $plan->price,
        ]);
    }

    /**
     * Retorna estatísticas para o painel admin.
     */
    public function getAdminStats(): array
    {
        return [
            'total_plans' => SaasPlan::count(),
            'active_plans' => SaasPlan::where('is_active', true)->count(),
            'total_subscriptions' => SaasSubscription::count(),
            'active_subscriptions' => SaasSubscription::active()->count(),
            'revenue_month' => SaasSubscription::where('starts_at', '>=', now()->startOfMonth()->toDateString())
                ->sum('amount_paid'),
        ];
    }

    /**
     * Retorna dados do infoprodutor sobre seu plano SaaS.
     */
    public function getUserSaasInfo(int $userId): array
    {
        $subscription = $this->getActiveSubscription($userId);
        $plan = $subscription?->plan;
        $counter = SaasUsageCounter::currentMonth($userId);
        $productCount = Product::where('tenant_id', $userId)->count();

        return [
            'has_active_plan' => $subscription !== null,
            'plan' => $plan ? [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => (float) $plan->price,
                'interval' => $plan->interval,
                'interval_label' => $plan->intervalLabel(),
                'max_products' => $plan->max_products,
                'max_orders_per_month' => $plan->max_orders_per_month,
                'max_students' => $plan->max_students,
                'is_free' => $plan->is_free,
                'features' => $plan->features ?? [],
            ] : null,
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'starts_at' => $subscription->starts_at?->format('d/m/Y'),
                'expires_at' => $subscription->expires_at?->format('d/m/Y'),
                'days_remaining' => max(0, (int) now()->diffInDays($subscription->expires_at, false)),
            ] : null,
            'usage' => [
                'products_count' => $productCount,
                'orders_this_month' => $counter->orders_count,
            ],
        ];
    }
}
