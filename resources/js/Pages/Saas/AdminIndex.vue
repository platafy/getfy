<script setup>
import { ref, computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import LayoutInfoprodutor from '@/Layouts/LayoutInfoprodutor.vue';
import Button from '@/components/ui/Button.vue';
import {
    Settings, ToggleLeft, ToggleRight, Plus, Pencil, Trash2, X,
    Crown, Users, CreditCard, TrendingUp, Package, ShoppingCart,
    GraduationCap, Star, Ban, UserPlus
} from 'lucide-vue-next';

defineOptions({ layout: LayoutInfoprodutor });

const props = defineProps({
    saasEnabled: Boolean,
    stats: Object,
    plans: { type: Array, default: () => [] },
    subscriptions: { type: Array, default: () => [] },
    intervalLabels: { type: Object, default: () => ({}) },
});

const page = usePage();
const flash = computed(() => page.props?.flash ?? {});
const activeTab = ref('config');

// --- Toggle SaaS ---
function toggleSaas() {
    router.post('/admin/saas/toggle', {}, { preserveScroll: true });
}

// --- Plan Modal ---
const showPlanModal = ref(false);
const editingPlan = ref(null);

const planForm = useForm({
    name: '',
    description: '',
    price: 0,
    interval: 'monthly',
    max_products: null,
    max_orders_per_month: null,
    max_students: null,
    is_free: false,
    is_active: true,
    is_highlighted: false,
    features: [],
    sort_order: 0,
});

const newFeature = ref('');

function addFeature() {
    if (newFeature.value.trim()) {
        planForm.features.push(newFeature.value.trim());
        newFeature.value = '';
    }
}
function removeFeature(index) {
    planForm.features.splice(index, 1);
}

function openCreatePlan() {
    editingPlan.value = null;
    planForm.reset();
    planForm.features = [];
    showPlanModal.value = true;
}

function openEditPlan(plan) {
    editingPlan.value = plan;
    planForm.name = plan.name;
    planForm.description = plan.description || '';
    planForm.price = plan.price;
    planForm.interval = plan.interval;
    planForm.max_products = plan.max_products;
    planForm.max_orders_per_month = plan.max_orders_per_month;
    planForm.max_students = plan.max_students;
    planForm.is_free = plan.is_free;
    planForm.is_active = plan.is_active;
    planForm.is_highlighted = plan.is_highlighted;
    planForm.features = plan.features ? [...plan.features] : [];
    planForm.sort_order = plan.sort_order;
    showPlanModal.value = true;
}

function closePlanModal() {
    showPlanModal.value = false;
    editingPlan.value = null;
}

function submitPlan() {
    if (editingPlan.value) {
        planForm.put(`/admin/saas/plans/${editingPlan.value.id}`, {
            preserveScroll: true,
            onSuccess: () => closePlanModal(),
        });
    } else {
        planForm.post('/admin/saas/plans', {
            preserveScroll: true,
            onSuccess: () => closePlanModal(),
        });
    }
}

function deletePlan(plan) {
    if (confirm(`Tem certeza que deseja excluir o plano "${plan.name}"?`)) {
        router.delete(`/admin/saas/plans/${plan.id}`, { preserveScroll: true });
    }
}

// --- Subscriptions ---
function cancelSubscription(sub) {
    if (confirm('Tem certeza que deseja cancelar esta assinatura?')) {
        router.post(`/admin/saas/subscriptions/${sub.id}/cancel`, {}, { preserveScroll: true });
    }
}

// --- Assign Plan ---
const showAssignModal = ref(false);
const infoprodutores = ref([]);
const assignForm = useForm({
    user_id: '',
    plan_id: '',
});

async function openAssignModal() {
    try {
        const response = await fetch('/admin/saas/infoprodutores');
        infoprodutores.value = await response.json();
    } catch (e) {
        infoprodutores.value = [];
    }
    assignForm.reset();
    showAssignModal.value = true;
}

function closeAssignModal() {
    showAssignModal.value = false;
}

function submitAssign() {
    assignForm.post('/admin/saas/assign-plan', {
        preserveScroll: true,
        onSuccess: () => closeAssignModal(),
    });
}

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
}

const statusLabels = {
    active: 'Ativo',
    past_due: 'Vencido',
    cancelled: 'Cancelado',
    expired: 'Expirado',
};
const statusColors = {
    active: 'text-green-400',
    past_due: 'text-yellow-400',
    cancelled: 'text-red-400',
    expired: 'text-gray-400',
};
</script>

<template>
<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-3">
                <Crown class="w-7 h-7 text-yellow-400" />
                Módulo SaaS
            </h1>
            <p class="text-gray-400 mt-1">Gerencie planos, assinaturas e limites da plataforma</p>
        </div>
    </div>

    <!-- Flash Messages -->
    <div v-if="flash.success" class="bg-green-900/30 border border-green-500/30 text-green-300 px-4 py-3 rounded-lg">
        {{ flash.success }}
    </div>
    <div v-if="flash.error" class="bg-red-900/30 border border-red-500/30 text-red-300 px-4 py-3 rounded-lg">
        {{ flash.error }}
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 bg-gray-800/50 p-1 rounded-lg">
        <button
            v-for="tab in [
                { key: 'config', label: 'Configuração', icon: Settings },
                { key: 'plans', label: 'Planos', icon: Package },
                { key: 'subscriptions', label: 'Assinaturas', icon: Users },
            ]"
            :key="tab.key"
            @click="activeTab = tab.key"
            class="flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium transition-all"
            :class="activeTab === tab.key ? 'bg-indigo-600 text-white shadow' : 'text-gray-400 hover:text-white hover:bg-gray-700/50'"
        >
            <component :is="tab.icon" class="w-4 h-4" />
            {{ tab.label }}
        </button>
    </div>

    <!-- Tab: Configuração -->
    <div v-if="activeTab === 'config'" class="space-y-6">
        <!-- Status Card -->
        <div class="bg-gray-800/50 border border-gray-700/50 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-white mb-2">Status do Sistema SaaS</h2>
                    <p class="text-gray-400">
                        O modo SaaS está atualmente:
                        <span :class="saasEnabled ? 'text-green-400 font-bold' : 'text-red-400 font-bold'">
                            {{ saasEnabled ? 'HABILITADO' : 'DESABILITADO' }}
                        </span>
                    </p>
                    <p class="text-gray-500 text-sm mt-2">
                        Quando habilitado, infoprodutores precisam de um plano ativo para usar a plataforma.
                    </p>
                </div>
                <button
                    @click="toggleSaas"
                    class="flex items-center gap-2 px-6 py-3 rounded-lg font-semibold transition-all"
                    :class="saasEnabled
                        ? 'bg-red-600 hover:bg-red-700 text-white'
                        : 'bg-green-600 hover:bg-green-700 text-white'"
                >
                    <component :is="saasEnabled ? ToggleRight : ToggleLeft" class="w-5 h-5" />
                    {{ saasEnabled ? 'Desabilitar' : 'Habilitar' }}
                </button>
            </div>
        </div>

        <!-- KPI Stats -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-gray-800/50 border border-gray-700/50 rounded-xl p-4 text-center">
                <Package class="w-8 h-8 text-indigo-400 mx-auto mb-2" />
                <p class="text-2xl font-bold text-white">{{ stats?.total_plans ?? 0 }}</p>
                <p class="text-gray-400 text-sm">Planos Criados</p>
            </div>
            <div class="bg-gray-800/50 border border-gray-700/50 rounded-xl p-4 text-center">
                <Users class="w-8 h-8 text-green-400 mx-auto mb-2" />
                <p class="text-2xl font-bold text-white">{{ stats?.active_subscriptions ?? 0 }}</p>
                <p class="text-gray-400 text-sm">Assinaturas Ativas</p>
            </div>
            <div class="bg-gray-800/50 border border-gray-700/50 rounded-xl p-4 text-center">
                <CreditCard class="w-8 h-8 text-yellow-400 mx-auto mb-2" />
                <p class="text-2xl font-bold text-white">{{ stats?.total_subscriptions ?? 0 }}</p>
                <p class="text-gray-400 text-sm">Total Assinaturas</p>
            </div>
            <div class="bg-gray-800/50 border border-gray-700/50 rounded-xl p-4 text-center">
                <TrendingUp class="w-8 h-8 text-emerald-400 mx-auto mb-2" />
                <p class="text-2xl font-bold text-white">{{ formatCurrency(stats?.revenue_month ?? 0) }}</p>
                <p class="text-gray-400 text-sm">Receita do Mês</p>
            </div>
        </div>
    </div>

    <!-- Tab: Planos -->
    <div v-if="activeTab === 'plans'" class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-white">Planos de Assinatura</h2>
            <button @click="openCreatePlan" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors">
                <Plus class="w-4 h-4" /> Novo Plano
            </button>
        </div>

        <div v-if="plans.length === 0" class="bg-gray-800/50 border border-gray-700/50 rounded-xl p-8 text-center">
            <Package class="w-12 h-12 text-gray-500 mx-auto mb-3" />
            <p class="text-gray-400">Nenhum plano criado ainda.</p>
            <button @click="openCreatePlan" class="mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm">
                Criar primeiro plano
            </button>
        </div>

        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="plan in plans"
                :key="plan.id"
                class="bg-gray-800/50 border rounded-xl p-5 relative"
                :class="plan.is_highlighted ? 'border-indigo-500/50 ring-1 ring-indigo-500/30' : 'border-gray-700/50'"
            >
                <div v-if="plan.is_highlighted" class="absolute -top-3 left-1/2 -translate-x-1/2">
                    <span class="bg-indigo-600 text-white text-xs font-bold px-3 py-1 rounded-full flex items-center gap-1">
                        <Star class="w-3 h-3" /> Popular
                    </span>
                </div>
                <div v-if="!plan.is_active" class="absolute top-3 right-3">
                    <span class="bg-red-900/50 text-red-400 text-xs px-2 py-0.5 rounded">Inativo</span>
                </div>

                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="text-lg font-bold text-white">{{ plan.name }}</h3>
                        <span v-if="plan.is_free" class="text-xs bg-green-900/30 text-green-400 px-2 py-0.5 rounded">Free</span>
                    </div>
                </div>

                <div class="mb-4">
                    <span class="text-3xl font-bold text-white">{{ formatCurrency(plan.price) }}</span>
                    <span class="text-gray-400 text-sm">/{{ intervalLabels[plan.interval] || plan.interval }}</span>
                </div>

                <div class="space-y-2 text-sm text-gray-300 mb-4">
                    <div class="flex items-center gap-2">
                        <Package class="w-4 h-4 text-blue-400" />
                        <span>{{ plan.max_products ?? '∞' }} produtos</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <ShoppingCart class="w-4 h-4 text-green-400" />
                        <span>{{ plan.max_orders_per_month ?? '∞' }} pedidos/mês</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <GraduationCap class="w-4 h-4 text-purple-400" />
                        <span>{{ plan.max_students ?? '∞' }} alunos</span>
                    </div>
                </div>

                <p v-if="plan.description" class="text-gray-500 text-sm mb-4">{{ plan.description }}</p>

                <div v-if="plan.features && plan.features.length" class="border-t border-gray-700/50 pt-3 mb-4">
                    <p v-for="(f, i) in plan.features" :key="i" class="text-sm text-gray-300 flex items-center gap-2">
                        <span class="text-green-400">✓</span> {{ f }}
                    </p>
                </div>

                <div class="flex gap-2">
                    <button @click="openEditPlan(plan)" class="flex-1 flex items-center justify-center gap-1 px-3 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg text-sm transition-colors">
                        <Pencil class="w-3 h-3" /> Editar
                    </button>
                    <button @click="deletePlan(plan)" class="flex items-center justify-center gap-1 px-3 py-2 bg-red-900/30 hover:bg-red-900/50 text-red-400 rounded-lg text-sm transition-colors">
                        <Trash2 class="w-3 h-3" />
                    </button>
                </div>

                <p class="text-xs text-gray-500 mt-2 text-center">
                    {{ plan.activeSubscriptions?.length ?? 0 }} assinaturas ativas
                </p>
            </div>
        </div>
    </div>

    <!-- Tab: Assinaturas -->
    <div v-if="activeTab === 'subscriptions'" class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-white">Assinaturas</h2>
            <button @click="openAssignModal" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors">
                <UserPlus class="w-4 h-4" /> Atribuir Plano
            </button>
        </div>

        <div v-if="subscriptions.length === 0" class="bg-gray-800/50 border border-gray-700/50 rounded-xl p-8 text-center">
            <Users class="w-12 h-12 text-gray-500 mx-auto mb-3" />
            <p class="text-gray-400">Nenhuma assinatura encontrada.</p>
        </div>

        <div v-else class="bg-gray-800/50 border border-gray-700/50 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-700/30 text-gray-400 text-left">
                        <tr>
                            <th class="px-4 py-3">Infoprodutor</th>
                            <th class="px-4 py-3">Plano</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Período</th>
                            <th class="px-4 py-3">Valor</th>
                            <th class="px-4 py-3">Gateway</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700/30">
                        <tr v-for="sub in subscriptions" :key="sub.id" class="hover:bg-gray-700/20">
                            <td class="px-4 py-3">
                                <p class="text-white font-medium">{{ sub.user_name }}</p>
                                <p class="text-gray-500 text-xs">{{ sub.user_email }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-300">{{ sub.plan_name }}</td>
                            <td class="px-4 py-3">
                                <span :class="statusColors[sub.status] || 'text-gray-400'" class="font-medium">
                                    {{ statusLabels[sub.status] || sub.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-400 text-xs">
                                {{ sub.starts_at }} — {{ sub.expires_at }}
                            </td>
                            <td class="px-4 py-3 text-gray-300">{{ formatCurrency(sub.amount_paid) }}</td>
                            <td class="px-4 py-3 text-gray-400 capitalize">{{ sub.gateway || '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    v-if="sub.is_active"
                                    @click="cancelSubscription(sub)"
                                    class="text-red-400 hover:text-red-300 text-xs flex items-center gap-1 ml-auto"
                                >
                                    <Ban class="w-3 h-3" /> Cancelar
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal: Criar/Editar Plano -->
    <Teleport to="body">
        <div v-if="showPlanModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60" @click.self="closePlanModal">
            <div class="bg-gray-800 border border-gray-700 rounded-xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto shadow-2xl">
                <div class="flex items-center justify-between px-6 pt-5 pb-3 border-b border-gray-700">
                    <h3 class="text-lg font-bold text-white">
                        {{ editingPlan ? 'Editar Plano' : 'Novo Plano' }}
                    </h3>
                    <button @click="closePlanModal" class="text-gray-400 hover:text-white"><X class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="submitPlan" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm text-gray-300 mb-1">Nome do Plano *</label>
                        <input v-model="planForm.name" type="text" required class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none" placeholder="Ex: Pro" />
                        <p v-if="planForm.errors.name" class="text-red-400 text-xs mt-1">{{ planForm.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-300 mb-1">Descrição</label>
                        <textarea v-model="planForm.description" rows="2" class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none" placeholder="Descrição breve do plano"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-300 mb-1">Preço (R$) *</label>
                            <input v-model.number="planForm.price" type="number" step="0.01" min="0" required :disabled="planForm.is_free" class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none disabled:opacity-50" />
                        </div>
                        <div>
                            <label class="block text-sm text-gray-300 mb-1">Período *</label>
                            <select v-model="planForm.interval" class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
                                <option v-for="(label, key) in intervalLabels" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm text-gray-300 mb-1">Max Produtos</label>
                            <input v-model.number="planForm.max_products" type="number" min="1" class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none" placeholder="∞" />
                            <p class="text-gray-500 text-xs mt-0.5">Vazio = ilimitado</p>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-300 mb-1">Max Pedidos/Mês</label>
                            <input v-model.number="planForm.max_orders_per_month" type="number" min="1" class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none" placeholder="∞" />
                            <p class="text-gray-500 text-xs mt-0.5">Vazio = ilimitado</p>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-300 mb-1">Max Alunos</label>
                            <input v-model.number="planForm.max_students" type="number" min="1" class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none" placeholder="∞" />
                            <p class="text-gray-500 text-xs mt-0.5">Vazio = ilimitado</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center gap-2 text-sm text-gray-300 cursor-pointer">
                            <input v-model="planForm.is_free" type="checkbox" class="rounded border-gray-600 bg-gray-700 text-indigo-500 focus:ring-indigo-500" />
                            Plano Gratuito
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-300 cursor-pointer">
                            <input v-model="planForm.is_active" type="checkbox" class="rounded border-gray-600 bg-gray-700 text-indigo-500 focus:ring-indigo-500" />
                            Ativo
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-300 cursor-pointer">
                            <input v-model="planForm.is_highlighted" type="checkbox" class="rounded border-gray-600 bg-gray-700 text-indigo-500 focus:ring-indigo-500" />
                            Destacar
                        </label>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-300 mb-1">Ordem de exibição</label>
                        <input v-model.number="planForm.sort_order" type="number" min="0" class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-300 mb-1">Features (benefícios)</label>
                        <div class="flex gap-2 mb-2">
                            <input v-model="newFeature" @keydown.enter.prevent="addFeature" type="text" class="flex-1 bg-gray-700/50 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none" placeholder="Ex: Suporte prioritário" />
                            <button type="button" @click="addFeature" class="px-3 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg text-sm">
                                <Plus class="w-4 h-4" />
                            </button>
                        </div>
                        <div v-for="(f, i) in planForm.features" :key="i" class="flex items-center justify-between bg-gray-700/30 rounded px-3 py-1.5 mb-1 text-sm text-gray-300">
                            <span>✓ {{ f }}</span>
                            <button type="button" @click="removeFeature(i)" class="text-red-400 hover:text-red-300"><X class="w-3 h-3" /></button>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="closePlanModal" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg text-sm">Cancelar</button>
                        <button type="submit" :disabled="planForm.processing" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium disabled:opacity-50">
                            {{ planForm.processing ? 'Salvando...' : (editingPlan ? 'Salvar' : 'Criar Plano') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Modal: Atribuir Plano -->
    <Teleport to="body">
        <div v-if="showAssignModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60" @click.self="closeAssignModal">
            <div class="bg-gray-800 border border-gray-700 rounded-xl w-full max-w-md mx-4 shadow-2xl">
                <div class="flex items-center justify-between px-6 pt-5 pb-3 border-b border-gray-700">
                    <h3 class="text-lg font-bold text-white">Atribuir Plano Manualmente</h3>
                    <button @click="closeAssignModal" class="text-gray-400 hover:text-white"><X class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="submitAssign" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm text-gray-300 mb-1">Infoprodutor *</label>
                        <select v-model="assignForm.user_id" required class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
                            <option value="">Selecionar...</option>
                            <option v-for="u in infoprodutores" :key="u.id" :value="u.id">
                                {{ u.name }} ({{ u.email }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-300 mb-1">Plano *</label>
                        <select v-model="assignForm.plan_id" required class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
                            <option value="">Selecionar...</option>
                            <option v-for="p in plans" :key="p.id" :value="p.id">
                                {{ p.name }} — {{ formatCurrency(p.price) }}
                            </option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="closeAssignModal" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg text-sm">Cancelar</button>
                        <button type="submit" :disabled="assignForm.processing" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium disabled:opacity-50">
                            {{ assignForm.processing ? 'Atribuindo...' : 'Atribuir' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</div>
</template>
