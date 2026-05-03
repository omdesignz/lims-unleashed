<template>
    <Head title="Password Confirmation" />
    
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 flex flex-col lg:flex-row">
        <!-- Left Panel: Authentication Form -->
        <div class="flex-1 flex items-center justify-center p-6 lg:p-12">
            <div class="w-full max-w-md">
                <!-- Header Card -->
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-8 mb-8">
                    <!-- Logo -->
                    <div class="flex justify-center mb-6">
                        <Logo class="text-primary-800 dark:text-primary-400" width="280" height="64" />
                    </div>

                    <!-- Title & Description -->
                    <div class="text-center mb-8">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-3 flex items-center justify-center gap-3">
                            <LockClosedIcon class="h-7 w-7 text-primary-800 dark:text-primary-400" />
                            Confirmação de Segurança
                        </h1>
                        
                        <div class="mt-4 p-4 bg-primary-50 dark:bg-gray-800 rounded-lg border border-blue-100">
                            <p class="text-sm text-gray-700">
                                Por favor, confirme sua senha para acessar esta área protegida.
                            </p>
                        </div>
                    </div>

                    <!-- Form Section -->
                    <div class="space-y-6">
                        <!-- Status Indicator -->
                        <div class="flex items-center justify-center">
                            <span class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium bg-primary-100 dark:bg-primary-900/20 text-blue-800 ring-1 ring-inset ring-blue-200">
                                <ShieldCheckIcon class="h-4 w-4" />
                                Área Protegida - Confirmação Requerida
                            </span>
                        </div>

                        <!-- Form -->
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Password Input Field -->
                            <div class="space-y-3">
                                <label for="password" class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                                    <KeyIcon class="h-4 w-4 text-primary-800 dark:text-primary-400" />
                                    Senha
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        v-model="form.password"
                                        id="password"
                                        name="password"
                                        type="password"
                                        autocomplete="current-password"
                                        required
                                        placeholder="Digite sua senha atual"
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
                            </div>

                            <!-- Security Check (Optional) -->
                            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <div class="flex items-start gap-2">
                                    <ExclamationTriangleIcon class="h-5 w-5 text-yellow-700 mt-0.5 flex-shrink-0" />
                                    <div>
                                        <p class="text-sm text-yellow-800">
                                            <span class="font-medium">Esta ação requer verificação adicional.</span> Por favor, confirme sua identidade inserindo sua senha atual.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="grid grid-cols-1 gap-4 mt-8">
                                <!-- Cancel Button -->
                                <button
                                    type="button"
                                    @click="cancel"
                                    class="group inline-flex items-center justify-center gap-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-3.5 text-sm font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:bg-gray-50 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                                >
                                    <ArrowUturnLeftIcon class="h-5 w-5 transition-transform group-hover:-translate-x-1" />
                                    Cancelar e Voltar
                                </button>

                                <!-- Submit Button -->
                                <button
                                    type="submit"
                                    :disabled="form.processing || !form.password"
                                    :class="[
                                        'inline-flex items-center justify-center gap-3 rounded-lg px-4 py-3.5 text-sm font-semibold shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2',
                                        form.processing || !form.password
                                            ? 'bg-gray-300 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                                            : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:ring-primary-500'
                                    ]"
                                >
                                    <template v-if="form.processing">
                                        <ArrowPathIcon class="h-5 w-5 animate-spin" />
                                        Verificando...
                                    </template>
                                    <template v-else>
                                        <CheckCircleIcon class="h-5 w-5" />
                                        Confirmar Senha
                                    </template>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Help Section -->
                    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <div class="space-y-4">
                            <div class="flex items-center gap-2">
                                <InformationCircleIcon class="h-5 w-5 text-primary-800 dark:text-primary-400 flex-shrink-0" />
                                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Por que esta verificação?</h3>
                            </div>
                            
                            <div class="space-y-3 pl-7">
                                <!-- Reason 1 -->
                                <div class="flex items-start gap-2">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/20 mt-0.5 flex-shrink-0">
                                        <ShieldExclamationIcon class="h-3 w-3 text-primary-800 dark:text-primary-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            <span class="font-medium text-primary-800 dark:text-primary-400">Proteção de Conta:</span> Esta verificação garante que apenas você pode acessar configurações sensíveis.
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Reason 2 -->
                                <div class="flex items-start gap-2">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/20 mt-0.5 flex-shrink-0">
                                        <ClockIcon class="h-3 w-3 text-primary-800 dark:text-primary-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            <span class="font-medium text-primary-800 dark:text-primary-400">Sessão Expirada:</span> Sua sessão de segurança expirou e precisa ser revalidada.
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Reason 3 -->
                                <div class="flex items-start gap-2">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/20 mt-0.5 flex-shrink-0">
                                        <Cog6ToothIcon class="h-3 w-3 text-primary-800 dark:text-primary-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            <span class="font-medium text-primary-800 dark:text-primary-400">Ação Sensível:</span> Você está tentando acessar ou modificar configurações importantes do sistema.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Security Note -->
                            <div class="mt-4 p-3 bg-green-50 rounded-lg border border-green-200">
                                <div class="flex items-start gap-2">
                                    <CheckBadgeIcon class="h-4 w-4 text-green-700 mt-0.5 flex-shrink-0" />
                                    <p class="text-xs text-green-800">
                                        <span class="font-medium">Sua segurança é importante:</span> Esta camada adicional de proteção ajuda a prevenir acessos não autorizados à sua conta.
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
                    src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" 
                    alt="Laboratory security background" 
                />
            </div>
            
            <div class="relative z-10 flex flex-col justify-center p-12 text-white">
                <div class="max-w-lg">
                    <div class="mb-10">
                        <LockClosedIcon class="h-16 w-16 text-white mb-6" />
                        <h2 class="text-3xl font-bold mb-4">Proteção de Dados do Laboratório</h2>
                        <p class="text-blue-100 text-lg">
                            Nosso sistema de confirmação de senha garante que apenas pessoal autorizado possa acessar dados sensíveis do laboratório e executar operações críticas.
                        </p>
                    </div>
                    
                    <div class="space-y-6">
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white dark:bg-gray-900/20">
                                <DocumentCheckIcon class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold mb-1">Conformidade com Normativas</h3>
                                <p class="text-blue-100">Garantimos conformidade com regulamentos de proteção de dados de laboratório.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white dark:bg-gray-900/20">
                                <UserGroupIcon class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold mb-1">Controle de Acesso</h3>
                                <p class="text-blue-100">Restringimos operações sensíveis apenas a usuários verificados.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white dark:bg-gray-900/20">
                                <ChartBarIcon class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold mb-1">Auditoria de Ações</h3>
                                <p class="text-blue-100">Todas as ações em áreas protegidas são registradas para auditoria.</p>
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
import Logo from '../../Shared/logo.vue'
import {
    LockClosedIcon,
    ShieldCheckIcon,
    KeyIcon,
    EyeSlashIcon,
    ExclamationCircleIcon,
    ExclamationTriangleIcon,
    ArrowUturnLeftIcon,
    ArrowPathIcon,
    CheckCircleIcon,
    InformationCircleIcon,
    ShieldExclamationIcon,
    ClockIcon,
    Cog6ToothIcon,
    CheckBadgeIcon,
    DocumentCheckIcon,
    UserGroupIcon,
    ChartBarIcon
} from '@heroicons/vue/24/outline';

export default {
    layout: EmptyLayout,
    components: {
        Logo,
        LockClosedIcon,
        ShieldCheckIcon,
        KeyIcon,
        EyeSlashIcon,
        ExclamationCircleIcon,
        ExclamationTriangleIcon,
        ArrowUturnLeftIcon,
        ArrowPathIcon,
        CheckCircleIcon,
        InformationCircleIcon,
        ShieldExclamationIcon,
        ClockIcon,
        Cog6ToothIcon,
        CheckBadgeIcon,
        DocumentCheckIcon,
        UserGroupIcon,
        ChartBarIcon
    }
}
</script>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';

defineProps({
    layout: null,
});

let form = useForm({
    password: '',
});

let submit = () => {
    form.post('/user/confirm-password', {
        onFinish: () => form.reset(),
    });
};

let cancel = () => {
    router.get('/dashboard');
};
</script>

<style scoped>
/* Custom styles for password input */
input[type="password"] {
    caret-color: #1e3a8a; /* blue-900 */
    letter-spacing: 0.1em;
}

/* Hide browser's default password reveal button */
input[type="password"]::-ms-reveal,
input[type="password"]::-ms-clear {
    display: none;
}

/* Smooth transitions for interactive elements */
button, input {
    transition: all 200ms ease-in-out;
}

/* Focus styles */
input:focus {
    outline: 2px solid transparent;
    outline-offset: 2px;
    box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
}

/* Button hover effects */
button:not(:disabled):hover {
    transform: translateY(-1px);
}
</style>