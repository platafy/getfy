<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import { Eye, EyeOff, User, Lock, ArrowRight, Key, UserPlus } from 'lucide-vue-next';

const showPassword = ref(false);
const page = usePage();
const flashError = computed(() => page.props.flash?.error ?? null);

const branding = computed(() => page.props.public_branding ?? {});
const appName = computed(() => branding.value.app_name || 'Platafy');
const logoUrl = computed(() => branding.value.app_logo || branding.value.app_logo_dark || '/images/auth/platafy_logo.png');
const iconUrl = computed(() => branding.value.app_logo_icon || '/images/auth/platafy_icon.png');

const redirectAfterLogin = computed(() => page.props.redirect ?? null);

const form = useForm({
    email: '',
    password: '',
    remember: false,
    redirect: redirectAfterLogin.value || '',
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}

// --------------------------------------------------------------------------
// Animação de notificações flutuantes de vendas (Idêntica ao Checkout Platafy)
// --------------------------------------------------------------------------
const notifications = ref([]);
let nextId = 0;
let timerId = null;

const buyerNames = ['Gabriel S.', 'Amanda M.', 'Lucas R.', 'Beatriz C.', 'João P.', 'Fernanda L.', 'Matheus B.', 'Camila R.', 'Rodrigo A.'];
const transactionTypes = [
    { type: 'Venda Aprovada', min: 97, max: 497 },
    { type: 'PIX Gerado', min: 47, max: 297 },
    { type: 'Venda Cartão', min: 147, max: 597 },
];

function addNotification() {
    const randomName = buyerNames[Math.floor(Math.random() * buyerNames.length)];
    const randomTx = transactionTypes[Math.floor(Math.random() * transactionTypes.length)];
    const rawVal = Math.floor(Math.random() * (randomTx.max - randomTx.min) + randomTx.min) + 0.90;
    const formattedVal = rawVal.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

    const id = ++nextId;
    notifications.value.push({
        id,
        type: randomTx.type,
        value: formattedVal,
        name: randomName,
    });

    if (notifications.value.length > 3) {
        notifications.value.shift();
    }

    setTimeout(() => {
        notifications.value = notifications.value.filter(n => n.id !== id);
    }, 4000);
}

function startNotificationLoop() {
    addNotification();
    const nextTime = Math.random() * 1800 + 1600; // a cada 1.6s a 3.4s
    timerId = setTimeout(startNotificationLoop, nextTime);
}

onMounted(() => {
    addNotification();
    setTimeout(() => {
        startNotificationLoop();
    }, 1200);
});

onUnmounted(() => {
    if (timerId) clearTimeout(timerId);
});
</script>

<template>
    <div class="min-h-screen w-full grid lg:grid-cols-2 bg-[#07090d] text-white font-sans overflow-x-hidden selection:bg-amber-500 selection:text-black">
        <!-- Coluna da Esquerda (Hero + Animação Flutuante de Vendas) -->
        <div class="hidden lg:flex relative flex-col justify-end p-12 xl:p-16 overflow-hidden bg-[#07090d]">
            <!-- Imagem de fundo do Checkout Platafy -->
            <img
                src="/images/auth/login_bg.jpg"
                alt="Background"
                class="absolute inset-0 w-full h-full object-cover object-center"
            />

            <!-- Gradiente de sombra sobre a imagem -->
            <div class="absolute inset-0 bg-gradient-to-t from-[#07090d] via-black/35 to-black/20 pointer-events-none" />

            <!-- Notificações flutuantes animadas no topo esquerdo -->
            <div class="absolute top-12 left-10 z-20 flex flex-col gap-3.5 pointer-events-none w-80">
                <TransitionGroup name="notif">
                    <div
                        v-for="notif in notifications"
                        :key="notif.id"
                        class="notification-card glass-effect rounded-2xl p-4 flex items-center gap-3.5 w-76 shadow-2xl transition-all"
                    >
                        <div class="w-10 h-10 rounded-full border border-amber-500/40 bg-black/60 flex items-center justify-center overflow-hidden p-1.5 shrink-0 shadow-inner">
                            <img :src="iconUrl" :alt="appName" class="w-full h-full object-contain" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start mb-0.5">
                                <p class="text-[11px] font-bold text-amber-400 tracking-wider truncate uppercase">
                                    {{ notif.type }}
                                </p>
                                <span class="text-[10px] text-zinc-400 shrink-0 ml-2">Agora</span>
                            </div>
                            <p class="text-sm font-extrabold text-white leading-tight">
                                {{ notif.value }}
                            </p>
                            <p class="text-[11px] text-zinc-300 truncate mt-0.5">
                                {{ notif.name }} acabou de comprar
                            </p>
                        </div>
                    </div>
                </TransitionGroup>
            </div>

            <!-- Textos no rodapé da coluna esquerda -->
            <div class="relative z-20 max-w-lg mb-4">
                <h1 class="text-4xl xl:text-5xl font-extrabold text-white leading-tight mb-4 tracking-tight">
                    Escale suas vendas <br />
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 via-amber-500 to-yellow-500">
                        sem limites.
                    </span>
                </h1>
                <p class="text-zinc-300 text-base xl:text-lg leading-relaxed">
                    Junte-se a milhares de empreendedores que faturam todos os dias com nossa tecnologia de alta performance.
                </p>
            </div>
        </div>

        <!-- Coluna da Direita (Painel de Login) -->
        <div class="flex items-center justify-center p-6 sm:p-10 lg:p-12 min-h-screen bg-[#07090d]">
            <div class="w-full max-w-[420px] space-y-7 my-auto">
                <!-- Cabeçalho com Logo -->
                <div class="text-center">
                    <div class="inline-flex justify-center items-center mb-6">
                        <img
                            :src="logoUrl"
                            :alt="appName"
                            class="h-14 sm:h-16 w-auto object-contain drop-shadow-md"
                        />
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">
                        Bem-vindo de volta!
                    </h2>
                    <p class="text-zinc-400 text-sm mt-2">
                        Acesse sua conta para gerenciar seu império.
                    </p>
                </div>

                <!-- Mensagem de erro de flash ou validação -->
                <div
                    v-if="flashError"
                    class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-400 flex items-center gap-2.5 animate-shake"
                    role="alert"
                >
                    <span class="font-medium">{{ flashError }}</span>
                </div>

                <!-- Formulário -->
                <form class="space-y-5" @submit.prevent="submit">
                    <!-- Campo Usuário / E-mail -->
                    <div class="space-y-2">
                        <label for="email" class="block text-zinc-300 text-sm font-semibold ml-1">
                            Usuário
                        </label>
                        <div class="relative group">
                            <User
                                class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-zinc-400 group-focus-within:text-amber-400 transition-colors"
                                aria-hidden="true"
                            />
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                autocomplete="username"
                                required
                                placeholder="suporte@platafy.com"
                                class="w-full rounded-2xl border border-white/10 bg-[#0f1419] py-3.5 pl-12 pr-4 text-sm text-white placeholder-zinc-500 transition-all duration-200 focus:border-amber-500 focus:bg-[#161c22] focus:outline-none focus:ring-1 focus:ring-amber-500"
                            />
                        </div>
                        <p v-if="form.errors.email" class="text-xs text-red-400 ml-1">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Campo Senha -->
                    <div class="space-y-2">
                        <label for="password" class="block text-zinc-300 text-sm font-semibold ml-1">
                            Senha
                        </label>
                        <div class="relative group">
                            <Lock
                                class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-zinc-400 group-focus-within:text-amber-400 transition-colors"
                                aria-hidden="true"
                            />
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password"
                                required
                                placeholder="••••••••"
                                class="w-full rounded-2xl border border-white/10 bg-[#0f1419] py-3.5 pl-12 pr-12 text-sm text-white placeholder-zinc-500 transition-all duration-200 focus:border-amber-500 focus:bg-[#161c22] focus:outline-none focus:ring-1 focus:ring-amber-500"
                            />
                            <button
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-zinc-400 hover:text-zinc-200 focus:outline-none"
                                :aria-label="showPassword ? 'Ocultar senha' : 'Exibir senha'"
                                @click="showPassword = !showPassword"
                            >
                                <Eye v-if="showPassword" class="h-4 w-4" />
                                <EyeOff v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="text-xs text-red-400 ml-1">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Linha auxiliar: Lembrar & Esqueci senha -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2.5 cursor-pointer group select-none">
                            <div class="relative flex items-center justify-center">
                                <input
                                    v-model="form.remember"
                                    type="checkbox"
                                    class="peer sr-only"
                                />
                                <div
                                    class="w-5 h-5 rounded-md border border-white/20 bg-[#0f1419] peer-checked:bg-amber-500 peer-checked:border-amber-500 transition-all flex items-center justify-center"
                                >
                                    <svg
                                        v-show="form.remember"
                                        class="w-3.5 h-3.5 text-black font-bold"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="3"
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                </div>
                            </div>
                            <span class="text-xs sm:text-sm text-zinc-400 group-hover:text-zinc-300 transition-colors">
                                Lembrar meu acesso
                            </span>
                        </label>

                        <Link
                            href="/esqueci-senha"
                            class="text-xs sm:text-sm text-zinc-400 hover:text-amber-400 transition-colors inline-flex items-center gap-1.5 font-medium"
                        >
                            <Key class="w-3.5 h-3.5" />
                            <span>Esqueci minha senha</span>
                        </Link>
                    </div>

                    <!-- Botão Entrar -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-4 px-6 rounded-2xl font-bold text-black text-base shadow-xl bg-gradient-to-r from-amber-400 via-amber-500 to-yellow-500 hover:from-amber-300 hover:to-amber-400 active:scale-[0.99] transition-all duration-300 flex items-center justify-center gap-2 group cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                        style="box-shadow: 0 10px 25px -5px rgba(245, 158, 11, 0.35);"
                    >
                        <span>{{ form.processing ? 'Entrando…' : 'Entrar' }}</span>
                        <ArrowRight
                            class="w-5 h-5 group-hover:translate-x-1 transition-transform"
                            aria-hidden="true"
                        />
                    </button>
                </form>

                <!-- Divisor e Botão Criar Conta -->
                <div class="pt-6 border-t border-white/10 text-center space-y-3">
                    <p class="text-zinc-400 text-xs sm:text-sm">
                        Ainda não tem uma conta?
                    </p>
                    <Link
                        href="/meu-plano"
                        class="w-full py-3.5 px-6 rounded-2xl font-semibold text-zinc-200 text-sm border border-white/15 bg-white/[0.03] hover:bg-white/[0.08] hover:border-amber-500/50 transition-all duration-300 flex items-center justify-center gap-2 group cursor-pointer"
                    >
                        <UserPlus class="w-4 h-4 text-amber-400 group-hover:scale-110 transition-transform" />
                        <span>Criar Conta Grátis</span>
                    </Link>
                </div>

                <!-- Rodapé de copyright -->
                <p class="text-center text-xs text-zinc-500 pt-2">
                    © {{ new Date().getFullYear() }} {{ appName }}. Todos os direitos reservados.
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes float-up {
    0% {
        opacity: 0;
        transform: translateY(35px) scale(0.92);
    }
    12% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    88% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    100% {
        opacity: 0;
        transform: translateY(-35px) scale(0.92);
    }
}

.notification-card {
    animation: float-up 4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.glass-effect {
    background: rgba(15, 20, 25, 0.82);
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-left: 4px solid #f59e0b;
    box-shadow: 
        0 14px 40px rgba(0, 0, 0, 0.5),
        inset 0 1px 0 rgba(255, 255, 255, 0.12),
        0 0 20px rgba(245, 158, 11, 0.15);
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-4px); }
    40%, 80% { transform: translateX(4px); }
}
.animate-shake {
    animation: shake 0.4s ease-in-out;
}
</style>
