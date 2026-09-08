<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import {
    Crown, Package, ShoppingCart, GraduationCap, Clock, CheckCircle,
    AlertTriangle, ArrowUpRight, Star, Zap
} from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    saasEnabled: Boolean,
    userSaas: Object,
    plans: { type: Array, default: () => [] },
});

const page = usePage();
const flash = computed(() => page.props?.flash ?? {});

const currentPlan = computed(() => props.userSaas?.plan);
const subscription = computed(() => props.userSaas?.subscription);
const usage = computed(() => props.userSaas?.usage ?? {});

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
}

function usagePercent(used, max) {
    if (!max) return 0;
    return Math.min(100, Math.round((used / max) * 100));
}

function usageColor(percent) {
    if (percent >= 90) return 'bg-red-500';
    if (percent >= 70) return 'bg-yellow-500';
    return 'bg-green-500';
}

function isCurrentPlan(plan) {
    return currentPlan.value && currentPlan.value.id === plan.id;
}
</script>

<template>
<div class="max-w-6xl mx-auto p-4 sm:p-6 space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-3">
            <Crown class="w-7 h-7 text-yellow-400" />
            Meu Plano
        </h1>
        <p class="text-gray-400 mt-1">Gerencie sua assinatura e veja seus limites de uso</p>
    </div>

    <!-- Flash -->
    <div v-if="flash.warning" class="bg-yellow-900/30 border border-yellow-500/30 text-yellow-300 px-4 py-3 rounded-lg flex items-center gap-2">
        <AlertTriangle class="w-5 h-5 flex-shrink-0" />
        {{ flash.warning }}
    </div>
    <div v-if="flash.success" class="bg-green-900/30 border border-green-500/30 text-green-300 px-4 py-3 rounded-lg">
        {{ flash.success }}
    </div>

    <!-- SaaS Disabled -->
    <div v-if="!saasEnabled" class="bg-gray-800/50 border border-gray-700/50 rounded-xl p-8 text-center">
        <CheckCircle class="w-12 h-12 text-green-400 mx-auto mb-3" />
        <h2 class="text-xl font-bold text-white mb-2">Acesso Livre</h2>
        <p class="text-gray-400">O sistema de planos não está ativo. Você tem acesso total à plataforma.</p>
    </div>

    <!-- SaaS Enabled -->
    <template v-else>
        <!-- Current Plan Card -->
        <div class="bg-gradient-to-br from-gray-800/80 to-gray-800/40 border border-gray-700/50 rounded-xl p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-gray-400 text-sm mb-1">Plano Atual</p>
                    <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                        <template v-if="currentPlan">
                            {{ currentPlan.name }}
                            <span v-if="currentPlan.is_free" class="text-xs bg-green-900/30 text-green-400 px-2 py-0.5 rounded">Free</span>
                        </template>
                        <template v-else>
                            <span class="text-red-400">Sem Plano Ativo</span>
                        </template>
                    </h2>
                    <template v-if="subscription">
                        <p class="text-gray-400 text-sm mt-1">
                            <Clock class="w-3.5 h-3.5 inline-block mr-1" />
                            Válido até {{ subscription.expires_at }}
                            <span v-if="subscription.days_remaining <= 7" class="text-yellow-400 font-medium ml-1">
                                ({{ subscription.days_remaining }} dias restantes)
                            </span>
                        </p>
                    </template>
                </div>
                <div v-if="currentPlan" class="text-right">
                    <p class="text-3xl font-bold text-white">{{ formatCurrency(currentPlan.price) }}</p>
                    <p class="text-gray-400 text-sm">/{{ currentPlan.interval_label }}</p>
                </div>
            </div>

            <!-- Usage Bars -->
            <div v-if="currentPlan" class="grid sm:grid-cols-3 gap-4 mt-6">
                <div class="bg-gray-900/50 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-gray-400 text-sm flex items-center gap-1.5">
                            <Package class="w-4 h-4 text-blue-400" /> Produtos
                        </span>
                        <span class="text-white text-sm font-medium">
                            {{ usage.products_count ?? 0 }} / {{ currentPlan.max_products ?? '∞' }}
                        </span>
                    </div>
                    <div v-if="currentPlan.max_products" class="h-2 bg-gray-700 rounded-full overflow-hidden">
                        <div
                            :class="usageColor(usagePercent(usage.products_count, currentPlan.max_products))"
                            class="h-full rounded-full transition-all duration-500"
                            :style="{ width: usagePercent(usage.products_count, currentPlan.max_products) + '%' }"
                        ></div>
                    </div>
                    <div v-else class="h-2 bg-green-900/30 rounded-full">
                        <div class="h-full bg-green-500/50 rounded-full w-full"></div>
                    </div>
                </div>

                <div class="bg-gray-900/50 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-gray-400 text-sm flex items-center gap-1.5">
                            <ShoppingCart class="w-4 h-4 text-green-400" /> Pedidos/Mês
                        </span>
                        <span class="text-white text-sm font-medium">
                            {{ usage.orders_this_month ?? 0 }} / {{ currentPlan.max_orders_per_month ?? '∞' }}
                        </span>
                    </div>
                    <div v-if="currentPlan.max_orders_per_month" class="h-2 bg-gray-700 rounded-full overflow-hidden">
                        <div
                            :class="usageColor(usagePercent(usage.orders_this_month, currentPlan.max_orders_per_month))"
                            class="h-full rounded-full transition-all duration-500"
                            :style="{ width: usagePercent(usage.orders_this_month, currentPlan.max_orders_per_month) + '%' }"
                        ></div>
                    </div>
                    <div v-else class="h-2 bg-green-900/30 rounded-full">
                        <div class="h-full bg-green-500/50 rounded-full w-full"></div>
                    </div>
                </div>

                <div class="bg-gray-900/50 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-gray-400 text-sm flex items-center gap-1.5">
                            <GraduationCap class="w-4 h-4 text-purple-400" /> Alunos
                        </span>
                        <span class="text-white text-sm font-medium">
                            — / {{ currentPlan.max_students ?? '∞' }}
                        </span>
                    </div>
                    <div class="h-2 bg-green-900/30 rounded-full">
                        <div class="h-full bg-green-500/50 rounded-full w-full"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Plans -->
        <div>
            <h2 class="text-xl font-semibold text-white mb-4 flex items-center gap-2">
                <Zap class="w-5 h-5 text-yellow-400" />
                {{ currentPlan ? 'Fazer Upgrade' : 'Escolha seu Plano' }}
            </h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    class="bg-gray-800/50 border rounded-xl p-5 relative transition-all hover:border-indigo-500/40"
                    :class="[
                        plan.is_highlighted ? 'border-indigo-500/50 ring-1 ring-indigo-500/30' : 'border-gray-700/50',
                        isCurrentPlan(plan) ? 'ring-2 ring-green-500/40 border-green-500/30' : ''
                    ]"
                >
                    <div v-if="plan.is_highlighted && !isCurrentPlan(plan)" class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span class="bg-indigo-600 text-white text-xs font-bold px-3 py-1 rounded-full flex items-center gap-1">
                            <Star class="w-3 h-3" /> Popular
                        </span>
                    </div>
                    <div v-if="isCurrentPlan(plan)" class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span class="bg-green-600 text-white text-xs font-bold px-3 py-1 rounded-full flex items-center gap-1">
                            <CheckCircle class="w-3 h-3" /> Atual
                        </span>
                    </div>

                    <h3 class="text-lg font-bold text-white mb-1">{{ plan.name }}</h3>
                    <p v-if="plan.description" class="text-gray-500 text-sm mb-3">{{ plan.description }}</p>

                    <div class="mb-4">
                        <span class="text-3xl font-bold text-white">{{ formatCurrency(plan.price) }}</span>
                        <span class="text-gray-400 text-sm">/{{ plan.interval_label }}</span>
                    </div>

                    <div class="space-y-2 text-sm text-gray-300 mb-4">
                        <div class="flex items-center gap-2">
                            <Package class="w-4 h-4 text-blue-400" />
                            <span>{{ plan.max_products ?? 'Ilimitado' }} produtos</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <ShoppingCart class="w-4 h-4 text-green-400" />
                            <span>{{ plan.max_orders_per_month ?? 'Ilimitado' }} pedidos/mês</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <GraduationCap class="w-4 h-4 text-purple-400" />
                            <span>{{ plan.max_students ?? 'Ilimitado' }} alunos</span>
                        </div>
                    </div>

                    <div v-if="plan.features && plan.features.length" class="border-t border-gray-700/50 pt-3 mb-4">
                        <p v-for="(f, i) in plan.features" :key="i" class="text-sm text-gray-300 flex items-center gap-2 py-0.5">
                            <span class="text-green-400 flex-shrink-0">✓</span> {{ f }}
                        </p>
                    </div>

                    <button
                        v-if="!isCurrentPlan(plan) && !plan.is_free"
                        disabled
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600/50 text-white/70 rounded-lg font-medium text-sm cursor-not-allowed"
                    >
                        <ArrowUpRight class="w-4 h-4" />
                        Em breve
                    </button>
                    <div v-else-if="isCurrentPlan(plan)" class="w-full text-center py-2.5 text-green-400 font-medium text-sm">
                        ✓ Plano Atual
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
</template>
