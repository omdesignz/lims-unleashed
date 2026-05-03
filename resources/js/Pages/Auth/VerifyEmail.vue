<template>
    <Head title="Email Verification" />
    
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 flex flex-col lg:flex-row">
        <!-- Left Panel: Verification Form -->
        <div class="flex-1 flex items-center justify-center p-6 lg:p-12">
            <div class="w-full max-w-md">
                <!-- Header Card -->
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-8 mb-8">
                    <!-- Logo & Header -->
                    <div class="text-center mb-8">
                        <div class="flex justify-center mb-6">
                            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-blue-900 to-blue-800">
                                <EnvelopeIcon class="h-8 w-8 text-white" />
                            </div>
                        </div>
                        
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-3">
                            Verificação de Email
                        </h1>
                        
                        <div class="mt-4 p-4 bg-primary-50 dark:bg-gray-800 rounded-lg border border-blue-100">
                            <div class="flex items-center gap-2">
                                <InformationCircleIcon class="h-5 w-5 text-primary-600 dark:text-primary-400 flex-shrink-0" />
                                <p class="text-sm text-gray-700">
                                    Obrigado por inscrever-se! Por favor, verifique seu endereço de email para continuar.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Status Indicator -->
                    <div class="flex items-center justify-center mb-6">
                        <span class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium bg-primary-100 dark:bg-primary-900/20 text-blue-800 ring-1 ring-inset ring-blue-200">
                            <ShieldCheckIcon class="h-4 w-4" />
                            Verificação Pendente
                        </span>
                    </div>

                    <!-- Success Message -->
                    <div v-if="verificationLinkSent" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-start gap-2">
                            <CheckCircleIcon class="h-5 w-5 text-green-700 mt-0.5 flex-shrink-0" />
                            <div>
                                <p class="text-sm font-medium text-green-800 mb-1">Novo link enviado!</p>
                                <p class="text-sm text-green-700">
                                    Um novo link de verificação foi enviado para o endereço de email fornecido durante o registro.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="mb-8 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex items-start gap-2">
                            <ExclamationCircleIcon class="h-5 w-5 text-yellow-700 mt-0.5 flex-shrink-0" />
                            <div>
                                <p class="text-sm text-gray-700">
                                    Antes de começar, você poderia verificar seu endereço de email clicando no link que acabamos de enviar para você? 
                                    Se você não recebeu o email, teremos o prazer de enviar outro.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-4">
                        <!-- Resend Button -->
                        <button
                            @click="submit"
                            :disabled="form.processing"
                            :class="[
                                'inline-flex items-center justify-center gap-3 rounded-lg px-4 py-3.5 text-sm font-semibold shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 w-full',
                                form.processing
                                    ? 'bg-gray-300 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                                    : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:ring-primary-500'
                            ]"
                        >
                            <template v-if="form.processing">
                                <ArrowPathIcon class="h-5 w-5 animate-spin" />
                                Enviando...
                            </template>
                            <template v-else>
                                <PaperAirplaneIcon class="h-5 w-5" />
                                Reenviar Email de Verificação
                            </template>
                        </button>

                        <!-- Logout Button -->
                        <Link 
                            :href="route('logout')" 
                            method="post" 
                            as="button"
                            class="inline-flex items-center justify-center gap-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-3.5 text-sm font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:bg-gray-50 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 w-full"
                        >
                            <ArrowRightOnRectangleIcon class="h-5 w-5" />
                            Sair
                        </Link>
                    </div>

                    <!-- Help Section -->
                    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <div class="space-y-4">
                            <div class="flex items-center gap-2">
                                <QuestionMarkCircleIcon class="h-5 w-5 text-primary-800 dark:text-primary-400 flex-shrink-0" />
                                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Problemas com a verificação?</h3>
                            </div>
                            
                            <div class="space-y-3 pl-7">
                                <!-- Step 1 -->
                                <div class="flex items-start gap-2">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/20 mt-0.5 flex-shrink-0">
                                        <span class="text-xs font-medium text-primary-800 dark:text-primary-400">1</span>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            <span class="font-medium text-primary-800 dark:text-primary-400">Verifique sua caixa de entrada:</span> Procure o email de verificação em sua caixa de entrada principal.
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Step 2 -->
                                <div class="flex items-start gap-2">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/20 mt-0.5 flex-shrink-0">
                                        <span class="text-xs font-medium text-primary-800 dark:text-primary-400">2</span>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            <span class="font-medium text-primary-800 dark:text-primary-400">Verifique a pasta de spam:</span> Às vezes, emails de verificação podem ser classificados como spam.
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Step 3 -->
                                <div class="flex items-start gap-2">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/20 mt-0.5 flex-shrink-0">
                                        <span class="text-xs font-medium text-primary-800 dark:text-primary-400">3</span>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            <span class="font-medium text-primary-800 dark:text-primary-400">Solicite novo link:</span> Use o botão acima para solicitar um novo link de verificação.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Security Note -->
                            <div class="mt-4 p-3 bg-green-50 rounded-lg border border-green-200">
                                <div class="flex items-start gap-2">
                                    <ShieldCheckIcon class="h-4 w-4 text-green-700 mt-0.5 flex-shrink-0" />
                                    <p class="text-xs text-green-800">
                                        <span class="font-medium">Por que verificamos?</span> A verificação de email garante a segurança da sua conta e protege seus dados do laboratório.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel: Visual Context -->
        <div class="hidden lg:flex lg:w-1/2 relative">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-900/90 to-blue-800/90">
                <!-- Animated Background Pattern -->
                <div class="absolute inset-0 opacity-20">
                    <div class="absolute top-1/4 left-1/4 w-64 h-64 rounded-full bg-white dark:bg-gray-900/10 animate-pulse"></div>
                    <div class="absolute top-1/3 right-1/4 w-48 h-48 rounded-full bg-white dark:bg-gray-900/10 animate-pulse delay-300"></div>
                    <div class="absolute bottom-1/4 left-1/3 w-32 h-32 rounded-full bg-white dark:bg-gray-900/10 animate-pulse delay-700"></div>
                </div>
                
                <!-- Email Illustration SVG -->
                <div class="absolute inset-0 flex items-center justify-center opacity-30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3/4 w-3/4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.5">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                </div>
            </div>
            
            <div class="relative z-10 flex flex-col justify-center p-12 text-white">
                <div class="max-w-lg">
                    <div class="mb-10">
                        <EnvelopeOpenIcon class="h-16 w-16 text-white mb-6" />
                        <h2 class="text-3xl font-bold mb-4">Proteção de Conta e Dados</h2>
                        <p class="text-blue-100 text-lg">
                            A verificação de email é essencial para garantir que apenas você tenha acesso à sua conta e aos dados sensíveis do laboratório.
                        </p>
                    </div>
                    
                    <div class="space-y-6">
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white dark:bg-gray-900/20">
                                <ShieldCheckIcon class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold mb-1">Segurança da Conta</h3>
                                <p class="text-blue-100">Verificamos sua identidade para proteger informações confidenciais do laboratório.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white dark:bg-gray-900/20">
                                <BellAlertIcon class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold mb-1">Notificações Importantes</h3>
                                <p class="text-blue-100">Seu email será usado para notificações críticas sobre resultados de testes e alertas do sistema.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white dark:bg-gray-900/20">
                                <DocumentCheckIcon class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold mb-1">Conformidade Regulatória</h3>
                                <p class="text-blue-100">Verificação de identidade é necessária para cumprir regulamentos de dados de laboratório.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Progress Indicator -->
                    <div class="mt-10 pt-6 border-t border-white/20">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-blue-100">Progresso da Configuração</span>
                            <span class="text-sm font-bold text-white">75%</span>
                        </div>
                        <div class="w-full bg-white dark:bg-gray-900/20 rounded-full h-2">
                            <div class="bg-white dark:bg-gray-900 h-2 rounded-full w-3/4 animate-pulse"></div>
                        </div>
                        <p class="text-xs text-blue-100 mt-2">
                            Complete a verificação de email para finalizar a configuração da sua conta.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import EmptyLayout from "../../Shared/EmptyLayout.vue";
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import Logo from '../../Shared/logo.vue'
import {
    EnvelopeIcon,
    ShieldCheckIcon,
    InformationCircleIcon,
    CheckCircleIcon,
    ExclamationCircleIcon,
    ArrowPathIcon,
    PaperAirplaneIcon,
    ArrowRightOnRectangleIcon,
    QuestionMarkCircleIcon,
    EnvelopeOpenIcon,
    BellAlertIcon,
    DocumentCheckIcon
} from '@heroicons/vue/24/outline';

export default {
    layout: EmptyLayout,
    components: {
        Logo,
        EnvelopeIcon,
        ShieldCheckIcon,
        InformationCircleIcon,
        CheckCircleIcon,
        ExclamationCircleIcon,
        ArrowPathIcon,
        PaperAirplaneIcon,
        ArrowRightOnRectangleIcon,
        QuestionMarkCircleIcon,
        EnvelopeOpenIcon,
        BellAlertIcon,
        DocumentCheckIcon
    }
}
</script>

<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';

import EmptyLayout from "../../Shared/EmptyLayout.vue";

defineOptions({
    layout: EmptyLayout
});

const props = defineProps({
    status: String
});

let form = useForm({});

let verificationLinkSent = computed(() => {
    return props.status === 'verification-link-sent';
});

let submit = () => {
    form.post('/email/verification-notification', {
        onFinish: () => {
            // Optional: Add any post-submission logic
        }
    });
};
</script>

<style scoped>
/* Custom animations for interactive elements */
@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-10px);
    }
}

@keyframes shimmer {
    0% {
        background-position: -1000px 0;
    }
    100% {
        background-position: 1000px 0;
    }
}

/* Button hover effects */
button:not(:disabled):hover,
a:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(30, 58, 138, 0.1);
}

/* Success message animation */
.bg-green-50 {
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Progress bar animation */
.bg-white dark:bg-gray-900.h-2 {
    background: linear-gradient(90deg, #fff 0%, #93c5fd 50%, #fff 100%);
    background-size: 1000px 100%;
    animation: shimmer 2s infinite linear;
}

/* Email icon animation */
svg.h-3\/4.w-3\/4 {
    animation: float 6s ease-in-out infinite;
}

/* Focus states for accessibility */
button:focus,
a:focus {
    outline: 2px solid transparent;
    outline-offset: 2px;
    box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.5);
}

/* Smooth transitions */
button, a, .bg-green-50, .bg-yellow-50 {
    transition: all 200ms ease-in-out;
}

/* Disabled state styling */
button:disabled {
    cursor: not-allowed;
    opacity: 0.6;
    transform: none !important;
    box-shadow: none !important;
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .text-3xl {
        font-size: 1.5rem;
    }
    
    .text-lg {
        font-size: 1rem;
    }
}
</style>