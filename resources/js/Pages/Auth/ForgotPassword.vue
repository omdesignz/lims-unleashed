<template>
    <Head title="Forgot Password" />
    
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 flex flex-col lg:flex-row">
        <!-- Left Panel: Password Recovery Form -->
        <div class="flex-1 flex items-center justify-center p-6 lg:p-12">
            <div class="w-full max-w-md">
                <!-- Header Card -->
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-8 mb-8">
                    <!-- Logo & Header -->
                    <div class="text-center mb-8">
                        <div class="flex justify-center mb-6">
                            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-blue-900 to-blue-800">
                                <KeyIcon class="h-8 w-8 text-white" />
                            </div>
                        </div>
                        
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-3">
                            Recuperação de Senha
                        </h1>
                        
                        <div class="mt-4 p-4 bg-primary-50 dark:bg-gray-800 rounded-lg border border-blue-100">
                            <p class="text-sm text-gray-700">
                                Preencha seu endereço de email para receber instruções de redefinição de senha.
                            </p>
                        </div>
                    </div>

                    <!-- Status Indicator -->
                    <div class="flex items-center justify-center mb-6">
                        <span class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium bg-primary-100 dark:bg-primary-900/20 text-blue-800 ring-1 ring-inset ring-blue-200">
                            <EnvelopeIcon class="h-4 w-4" />
                            Recuperação por Email
                        </span>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Email Input Field -->
                        <div class="space-y-3">
                            <label for="email" class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                                <AtSymbolIcon class="h-4 w-4 text-primary-800 dark:text-primary-400" />
                                Endereço de Email
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.email"
                                    id="email"
                                    name="email"
                                    type="email"
                                    autocomplete="email"
                                    required
                                    placeholder="seu.email@exemplo.com"
                                    :class="[
                                        'block w-full rounded-lg border px-4 py-3.5 text-sm shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-1',
                                        form.errors.email
                                            ? 'border-red-300 text-red-700 focus:border-red-500 focus:ring-red-500'
                                            : 'border-gray-300 dark:border-gray-700 focus:border-primary-500 dark:border-primary-400 focus:ring-primary-500'
                                    ]"
                                />
                                <div class="absolute right-3 top-3.5">
                                    <EnvelopeIcon class="h-5 w-5 text-gray-400" />
                                </div>
                            </div>
                            <p v-if="form.errors.email" class="text-xs text-red-600 flex items-center gap-1">
                                <ExclamationCircleIcon class="h-4 w-4" />
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <!-- Success Message (if email was sent) -->
                        <div v-if="status" class="p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-start gap-2">
                                <CheckCircleIcon class="h-5 w-5 text-green-700 mt-0.5 flex-shrink-0" />
                                <div>
                                    <p class="text-sm font-medium text-green-800 mb-1">Email enviado com sucesso!</p>
                                    <p class="text-sm text-green-700">
                                        Verifique sua caixa de entrada (e spam) para as instruções de redefinição de senha.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="grid grid-cols-1 gap-4 mt-8">
                            <!-- Back Button -->
                            <Link 
                                :href="route('login')"
                                class="group inline-flex items-center justify-center gap-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-3.5 text-sm font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:bg-gray-50 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                            >
                                <ArrowUturnLeftIcon class="h-5 w-5 transition-transform group-hover:-translate-x-1" />
                                Voltar para o Login
                            </Link>

                            <!-- Submit Button -->
                            <button
                                type="submit"
                                :disabled="form.processing || !form.email"
                                :class="[
                                    'inline-flex items-center justify-center gap-3 rounded-lg px-4 py-3.5 text-sm font-semibold shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2',
                                    form.processing || !form.email
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
                                    Enviar Instruções por Email
                                </template>
                            </button>
                        </div>
                    </form>

                    <!-- Help Section -->
                    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <div class="space-y-4">
                            <div class="flex items-center gap-2">
                                <InformationCircleIcon class="h-5 w-5 text-primary-800 dark:text-primary-400 flex-shrink-0" />
                                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">O que acontece a seguir?</h3>
                            </div>
                            
                            <div class="space-y-3 pl-7">
                                <!-- Step 1 -->
                                <div class="flex items-start gap-2">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/20 mt-0.5 flex-shrink-0">
                                        <span class="text-xs font-medium text-primary-800 dark:text-primary-400">1</span>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            <span class="font-medium text-primary-800 dark:text-primary-400">Email de Recuperação:</span> Você receberá um email com um link de redefinição de senha.
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
                                            <span class="font-medium text-primary-800 dark:text-primary-400">Link Válido:</span> O link será válido por 60 minutos a partir do envio.
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
                                            <span class="font-medium text-primary-800 dark:text-primary-400">Crie Nova Senha:</span> Clique no link para criar uma nova senha segura.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Security Note -->
                            <div class="mt-4 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                                <div class="flex items-start gap-2">
                                    <ShieldExclamationIcon class="h-4 w-4 text-yellow-700 mt-0.5 flex-shrink-0" />
                                    <p class="text-xs text-yellow-800">
                                        <span class="font-medium">Importante:</span> Se não receber o email em alguns minutos, verifique sua pasta de spam ou lixo eletrônico.
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
                <img 
                    class="absolute inset-0 h-full w-full object-cover mix-blend-overlay opacity-40" 
                    src="https://images.unsplash.com/photo-1581094794329-c8112a89af12?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" 
                    alt="Laboratory access security background" 
                />
            </div>
            
            <div class="relative z-10 flex flex-col justify-center p-12 text-white">
                <div class="max-w-lg">
                    <div class="mb-10">
                        <LockOpenIcon class="h-16 w-16 text-white mb-6" />
                        <h2 class="text-3xl font-bold mb-4">Recuperação de Acesso Segura</h2>
                        <p class="text-blue-100 text-lg">
                            Nosso sistema de recuperação de senha garante que apenas o proprietário da conta possa redefinir o acesso aos dados do laboratório.
                        </p>
                    </div>
                    
                    <div class="space-y-6">
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white dark:bg-gray-900/20">
                                <EnvelopeOpenIcon class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold mb-1">Email de Verificação</h3>
                                <p class="text-blue-100">Enviaremos um link seguro para o email registrado na conta.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white dark:bg-gray-900/20">
                                <ClockIcon class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold mb-1">Links Temporários</h3>
                                <p class="text-blue-100">Cada link de redefinição é válido por tempo limitado para maior segurança.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white dark:bg-gray-900/20">
                                <ShieldCheckIcon class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold mb-1">Proteção de Conta</h3>
                                <p class="text-blue-100">Evita acessos não autorizados mantendo seus dados de laboratório seguros.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import {
    KeyIcon,
    EnvelopeIcon,
    AtSymbolIcon,
    ExclamationCircleIcon,
    ArrowUturnLeftIcon,
    ArrowPathIcon,
    PaperAirplaneIcon,
    InformationCircleIcon,
    ShieldExclamationIcon,
    ClockIcon,
    CheckCircleIcon,
    LockOpenIcon,
    EnvelopeOpenIcon,
    ShieldCheckIcon
} from '@heroicons/vue/24/outline';

import EmptyLayout from "../../Shared/EmptyLayout.vue";

defineOptions({
    layout: EmptyLayout
});

defineProps({
    status: String, // For success message after email sent
});

let form = useForm({
    email: '',
});

let submit = () => {
    form.post('/forgot-password', {
        onFinish: () => {
            if (!form.hasErrors) {
                form.reset('email');
            }
        },
    });
};
</script>

<style scoped>
/* Custom styles for email input */
input[type="email"] {
    caret-color: #1e3a8a; /* blue-900 */
}

/* Smooth transitions for interactive elements */
button, input, a {
    transition: all 200ms ease-in-out;
}

/* Focus styles */
input:focus {
    outline: 2px solid transparent;
    outline-offset: 2px;
    box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
}

/* Button hover effects */
button:not(:disabled):hover,
a:hover {
    transform: translateY(-1px);
}

/* Disabled state */
button:disabled {
    cursor: not-allowed;
    opacity: 0.6;
}

/* Success message animation */
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

.bg-green-50 {
    animation: fadeIn 0.5s ease-out;
}
</style>