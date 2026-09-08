<script setup>
import { ref, computed } from 'vue';
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
</script>

<template>
    <div class="min-h-screen w-full grid lg:grid-cols-2 bg-[#07090d] text-white font-sans overflow-x-hidden selection:bg-amber-500 selection:text-black">
        <!-- Coluna da Esquerda (Hero + Notificações de Vendas) -->
        <div class="hidden lg:flex relative flex-col justify-end p-12 xl:p-16 overflow-hidden bg-[#07090d]">
            <!-- Imagem de fundo do Checkout Platafy -->
            <img
                src="/images/auth/login_bg.jpg"
                alt="Background"
                class="absolute inset-0 w-full h-full object-cover object-center"
            />

            <!-- Gradiente de sombra sobre a imagem -->
            <div class="absolute inset-0 bg-gradient-to-t from-[#07090d] via-black/35 to-black/20 pointer-events-none" />

            <!-- Notificações flutuantes no topo esquerdo -->
            <div class="absolute top-10 left-10 z-20 flex flex-col gap-3.5 pointer-events-none">
                <!-- Card 1: Venda Aprovada -->
                <div class="flex items-center gap-3.5 px-4 py-3 rounded-2xl border border-white/15 bg-[#0f1419]/75 backdrop-blur-xl shadow-2xl shadow-black/60 w-72 transform hover:scale-[1.02] transition-transform duration-300">
                    <div class="w-10 h-10 rounded-full border border-amber-500/40 bg-black/60 flex items-center justify-center overflow-hidden p-1.5 shrink-0">
                        <img :src="iconUrl" :alt="appName" class="w-full h-full object-contain" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-semibold text-amber-400 uppercase tracking-wider">Venda Aprovada</span>
                            <span class="text-[10px] text-zinc-400">Agora</span>
                        </div>
                        <div class="text-sm font-bold text-white mt-0.5">R$ 231,90</div>
                        <div class="text-[11px] text-zinc-300 truncate">Lucas R. acabou de comprar</div>
                    </div>
                </div>

                <!-- Card 2: PIX Gerado -->
                <div class="flex items-center gap-3.5 px-4 py-3 rounded-2xl border border-white/15 bg-[#0f1419]/75 backdrop-blur-xl shadow-2xl shadow-black/60 w-72 transform hover:scale-[1.02] transition-transform duration-300">
                    <div class="w-10 h-10 rounded-full border border-amber-500/40 bg-black/60 flex items-center justify-center overflow-hidden p-1.5 shrink-0">
                        <img :src="iconUrl" :alt="appName" class="w-full h-full object-contain" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-semibold text-amber-400 uppercase tracking-wider">PIX Gerado</span>
                            <span class="text-[10px] text-zinc-400">Agora</span>
                        </div>
                        <div class="text-sm font-bold text-white mt-0.5">R$ 102,90</div>
                        <div class="text-[11px] text-zinc-300 truncate">Gabriel S. acabou de comprar</div>
                    </div>
                </div>
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
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-4px); }
    40%, 80% { transform: translateX(4px); }
}
.animate-shake {
    animation: shake 0.4s ease-in-out;
}
</style>
