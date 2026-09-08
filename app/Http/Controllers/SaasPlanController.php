<?php

namespace App\Http\Controllers;

use App\Models\SaasPlan;
use App\Services\SaasService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaasPlanController extends Controller
{
    public function __construct(
        private readonly SaasService $saas
    ) {}

    /**
     * Página "Meu Plano" do infoprodutor — mostra plano atual e opções de upgrade.
     */
    public function index(): Response
    {
        $user = auth()->user();
        $userInfo = $this->saas->getUserSaasInfo($user->id);
        $plans = SaasPlan::active()->get();

        return Inertia::render('Saas/MyPlan', [
            'saasEnabled' => $this->saas->isEnabled(),
            'userSaas' => $userInfo,
            'plans' => $plans->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
                'price' => (float) $p->price,
                'interval' => $p->interval,
                'interval_label' => $p->intervalLabel(),
                'max_products' => $p->max_products,
                'max_orders_per_month' => $p->max_orders_per_month,
                'max_students' => $p->max_students,
                'is_free' => $p->is_free,
                'is_highlighted' => $p->is_highlighted,
                'features' => $p->features ?? [],
            ]),
        ]);
    }
}
