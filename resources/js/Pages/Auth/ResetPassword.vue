<template>
    <Head title="Password Reset" />
    
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 flex flex-col lg:flex-row">
        <!-- Left Panel: Password Reset Form -->
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
                            Redefinir Senha
                        </h1>
                        
                        <div class="mt-4 p-4 bg-green-50 rounded-lg border border-green-200">
                            <div class="flex items-center gap-2">
                                <CheckCircleIcon class="h-5 w-5 text-green-700 flex-shrink-0" />
                                <p class="text-sm text-gray-700">
                                    Você está quase lá! Crie sua nova senha abaixo.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Status Indicator -->
                    <div class="flex items-center justify-center mb-6">
                        <span class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium bg-primary-100 dark:bg-primary-900/20 text-blue-800 ring-1 ring-inset ring-blue-200">
                            <LockClosedIcon class="h-4 w-4" />
                            Finalizar Redefinição
                        </span>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Hidden Token Field -->
                        <input type="hidden" v-model="form.token" />
                        
                        <!-- Email Field (read-only) -->
                        <div class="space-y-3">
                            <label for="email" class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                                <AtSymbolIcon class="h-4 w-4 text-primary-800 dark:text-primary-400" />
                                Endereço de Email
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.email"
                                    id="email"
                                    name="email"
                                    type="email"
                                    autocomplete="email"
                                    readonly
                                    :class="[
                                        'block w-full rounded-lg border px-4 py-3.5 text-sm shadow-sm bg-gray-50 cursor-not-allowed',
                                        form.errors.email
                                            ? 'border-red-300 text-red-700'
                                            : 'border-gray-300 dark:border-gray-700 text-gray-500 dark:text-gray-400'
                                    ]"
                                />
                                <div class="absolute right-3 top-3.5">
                                    <CheckCircleIcon class="h-5 w-5 text-green-500" />
                                </div>
                            </div>
                            <p v-if="form.errors.email" class="text-xs text-red-600 flex items-center gap-1">
                                <ExclamationCircleIcon class="h-4 w-4" />
                                {{ form.errors.email }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Este é o email associado à sua conta. Não pode ser alterado.
                            </p>
                        </div>

                        <!-- Password Field -->
                        <div class="space-y-3">
                            <label for="password" class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                                <LockClosedIcon class="h-4 w-4 text-primary-800 dark:text-primary-400" />
                                Nova Senha
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.password"
                                    id="password"
                                    name="password"
                                    type="password"
                                    autocomplete="new-password"
                                    required
                                    placeholder="Digite sua nova senha"
                                    :class="[
                                        'block w-full rounded-lg border px-4 py-3.5 text-sm shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-1',
                                        form.errors.password
                                            ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                                            : 'border-gray-300 dark:border-gray-700 focus:border-primary-500 dark:border-primary-400 focus:ring-primary-500'
                                    ]"
                                />
                                <div class="absolute right-3 top-3.5">
                                    <EyeSlashIcon class="h-5 w-5 text-gray-400" />
                                </div>
                            </div>
                            <p v-if="form.errors.password" class="text-xs text-red-600 flex items-center gap-1">
                                <ExclamationCircleIcon class="h-4 w-4" />
                                {{ form.errors.password }}
                            </p>
                            
                            <!-- Password Requirements -->
                            <div class="mt-2 p-3 bg-primary-50 dark:bg-gray-800 rounded-lg border border-blue-200">
                                <p class="text-xs font-medium text-primary-800 dark:text-primary-400 mb-2">Requisitos da senha:</p>
                                <ul class="space-y-1">
                                    <li class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                        <CheckCircleIcon class="h-3 w-3 text-green-500 flex-shrink-0" />
                                        Mínimo de 8 caracteres
                                    </li>
                                    <li class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                        <CheckCircleIcon class="h-3 w-3 text-green-500 flex-shrink-0" />
                                        Letras maiúsculas e minúsculas
                                    </li>
                                    <li class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                        <CheckCircleIcon class="h-3 w-3 text-green-500 flex-shrink-0" />
                                        Pelo menos um número
                                    </li>
                                    <li class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                        <CheckCircleIcon class="h-3 w-3 text-green-500 flex-shrink-0" />
                                        Pelo menos um caractere especial
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Password Confirmation Field -->
                        <div class="space-y-3">
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                                <LockClosedIcon class="h-4 w-4 text-primary-800 dark:text-primary-400" />
                                Confirmar Nova Senha
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.password_confirmation"
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    autocomplete="new-password"
                                    required
                                    placeholder="Digite a senha novamente"
                                    :class="[
                                        'block w-full rounded-lg border px-4 py-3.5 text-sm shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-1',
                                        form.errors.password_confirmation
                                            ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                                            : 'border-gray-300 dark:border-gray-700 focus:border-primary-500 dark:border-primary-400 focus:ring-primary-500'
                                    ]"
                                />
                                <div class="absolute right-3 top-3.5">
                                    <EyeSlashIcon class="h-5 w-5 text-gray-400" />
                                </div>
                            </div>
                            <p v-if="form.errors.password_confirmation" class="text-xs text-red-600 flex items-center gap-1">
                                <ExclamationCircleIcon class="h-4 w-4" />
                                {{ form.errors.password_confirmation }}
                            </p>
                        </div>

                        <!-- Password Match Indicator -->
                        <div v-if="form.password && form.password_confirmation" class="p-3 rounded-lg border" :class="{
                            'bg-green-50 border-green-200': passwordsMatch,
                            'bg-red-50 border-red-200': !passwordsMatch && form.password_confirmation.length > 0
                        }">
                            <div class="flex items-center gap-2">
                                <template v-if="passwordsMatch">
                                    <CheckCircleIcon class="h-5 w-5 text-green-600" />
                                    <p class="text-sm text-green-700 font-medium">As senhas coincidem</p>
                                </template>
                                <template v-else>
                                    <XCircleIcon class="h-5 w-5 text-red-600" />
                                    <p class="text-sm text-red-700 font-medium">As senhas não coincidem</p>
                                </template>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="grid grid-cols-1 gap-4 mt-8">
                            <!-- Back to Login Link -->
                            <div class="text-center">
                                <Link 
                                    :href="route('login')"
                                    class="inline-flex items-center gap-2 text-sm font-medium text-primary-800 dark:text-primary-400 hover:text-primary-600 dark:text-primary-400 transition-colors duration-200"
                                >
                                    <ArrowLeftIcon class="h-4 w-4" />
                                    Lembrou da sua senha? Faça login
                                </Link>
                            </div>

                            <!-- Submit Button -->
                            <button
                                type="submit"
                                :disabled="form.processing || !isFormValid"
                                :class="[
                                    'inline-flex items-center justify-center gap-3 rounded-lg px-4 py-3.5 text-sm font-semibold shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2',
                                    form.processing || !isFormValid
                                        ? 'bg-gray-300 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                                        : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:ring-primary-500'
                                ]"
                            >
                                <template v-if="form.processing">
                                    <ArrowPathIcon class="h-5 w-5 animate-spin" />
                                    Redefinindo...
                                </template>
                                <template v-else>
                                    <KeyIcon class="h-5 w-5" />
                                    Redefinir Senha
                                </template>
                            </button>
                        </div>
                    </form>

                    <!-- Help Section -->
                    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <div class="space-y-4">
                            <div class="flex items-center gap-2">
                                <InformationCircleIcon class="h-5 w-5 text-primary-800 dark:text-primary-400 flex-shrink-0" />
                                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Dicas de segurança:</h3>
                            </div>
                            
                            <div class="space-y-3 pl-7">
                                <!-- Tip 1 -->
                                <div class="flex items-start gap-2">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/20 mt-0.5 flex-shrink-0">
                                        <ShieldCheckIcon class="h-3 w-3 text-primary-800 dark:text-primary-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            <span class="font-medium text-primary-800 dark:text-primary-400">Senha Única:</span> Não reutilize senhas de outros serviços.
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Tip 2 -->
                                <div class="flex items-start gap-2">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/20 mt-0.5 flex-shrink-0">
                                        <DocumentTextIcon class="h-3 w-3 text-primary-800 dark:text-primary-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            <span class="font-medium text-primary-800 dark:text-primary-400">Gerenciador de Senhas:</span> Considere usar um gerenciador de senhas para maior segurança.
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Tip 3 -->
                                <div class="flex items-start gap-2">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/20 mt-0.5 flex-shrink-0">
                                        <ClockIcon class="h-3 w-3 text-primary-800 dark:text-primary-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            <span class="font-medium text-primary-800 dark:text-primary-400">Atualização Regular:</span> Recomendamos alterar sua senha a cada 90 dias.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Success Note -->
                            <div class="mt-4 p-3 bg-green-50 rounded-lg border border-green-200">
                                <div class="flex items-start gap-2">
                                    <CheckBadgeIcon class="h-4 w-4 text-green-700 mt-0.5 flex-shrink-0" />
                                    <p class="text-xs text-green-800">
                                        <span class="font-medium">Pronto para começar:</span> Após redefinir sua senha, você será redirecionado para fazer login com suas novas credenciais.
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
                    alt="Laboratory security and access background" 
                />
            </div>
            
            <div class="relative z-10 flex flex-col justify-center p-12 text-white">
                <div class="max-w-lg">
                    <div class="mb-10">
                        <LockClosedIcon class="h-16 w-16 text-white mb-6" />
                        <h2 class="text-3xl font-bold mb-4">Proteção de Dados do Laboratório</h2>
                        <p class="text-blue-100 text-lg">
                            Uma senha forte é essencial para proteger informações sensíveis do laboratório. 
                            Certifique-se de criar uma senha única e segura.
                        </p>
                    </div>
                    
                    <div class="space-y-6">
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white dark:bg-gray-900/20">
                                <ShieldCheckIcon class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold mb-1">Segurança de Dados</h3>
                                <p class="text-blue-100">Proteja resultados de testes e informações confidenciais do laboratório.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white dark:bg-gray-900/20">
                                <UserGroupIcon class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold mb-1">Controle de Acesso</h3>
                                <p class="text-blue-100">Garanta que apenas pessoal autorizado acesse sistemas do laboratório.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white dark:bg-gray-900/20">
                                <DocumentCheckIcon class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold mb-1">Conformidade</h3>
                                <p class="text-blue-100">Atenda aos requisitos de segurança de dados regulatórios.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import EmptyLayout from "../../Shared/EmptyLayout.vue";
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import Logo from '../../Shared/logo.vue'
import {
    KeyIcon,
    LockClosedIcon,
    AtSymbolIcon,
    ExclamationCircleIcon,
    CheckCircleIcon,
    EyeSlashIcon,
    ArrowLeftIcon,
    ArrowPathIcon,
    InformationCircleIcon,
    ShieldCheckIcon,
    ClockIcon,
    DocumentTextIcon,
    CheckBadgeIcon,
    XCircleIcon,
    UserGroupIcon,
    DocumentCheckIcon,
    ShieldExclamationIcon
} from '@heroicons/vue/24/outline';

export default {
    layout: EmptyLayout,
    components: {
        Logo,
        KeyIcon,
        LockClosedIcon,
        AtSymbolIcon,
        ExclamationCircleIcon,
        CheckCircleIcon,
        EyeSlashIcon,
        ArrowLeftIcon,
        ArrowPathIcon,
        InformationCircleIcon,
        ShieldCheckIcon,
        ClockIcon,
        DocumentTextIcon,
        CheckBadgeIcon,
        XCircleIcon,
        UserGroupIcon,
        DocumentCheckIcon,
        ShieldExclamationIcon
    }
}
</script>

<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';

defineProps({
    layout: null,
    email: String,
    token: String,
});

let form = useForm({
    token: route().params.token,
    email: route().params.email,
    password: '',
    password_confirmation: '',
});

// Computed properties
const passwordsMatch = computed(() => {
    return form.password === form.password_confirmation && form.password.length > 0;
});

const isFormValid = computed(() => {
    return form.password && form.password_confirmation && passwordsMatch.value;
});

let submit = () => {
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<style scoped>
/* Custom styles for password inputs */
input[type="password"] {
    caret-color: #1e3a8a; /* blue-900 */
    letter-spacing: 0.1em;
}

input[type="email"]:read-only {
    background-color: #f9fafb; /* gray-50 */
    cursor: not-allowed;
}

/* Hide browser's default password reveal button */
input[type="password"]::-ms-reveal,
input[type="password"]::-ms-clear {
    display: none;
}

/* Smooth transitions for interactive elements */
button, input, a {
    transition: all 200ms ease-in-out;
}

/* Focus styles */
input:focus:not(:read-only) {
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

/* Password match indicator animation */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.bg-green-50, .bg-red-50 {
    animation: slideIn 0.3s ease-out;
}

/* Requirement list styling */
ul {
    list-style-type: none;
}
</style>