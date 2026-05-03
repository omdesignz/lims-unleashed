<template>
    <Head title="Two-factor Confirmation" />
    
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
                            <KeyIcon class="h-7 w-7 text-primary-800 dark:text-primary-400" />
                            Autenticação de Dois Fatores
                        </h1>
                        
                        <div class="mt-4 p-4 bg-primary-50 dark:bg-gray-800 rounded-lg border border-blue-100">
                            <p v-if="!recovery" class="text-sm text-gray-700">
                                Digite o código de 6 dígitos do seu aplicativo autenticador.
                            </p>
                            <p v-else class="text-sm text-gray-700">
                                Digite um dos seus códigos de recuperação de emergência.
                            </p>
                        </div>
                    </div>

                    <!-- Form Section -->
                    <div class="space-y-6">
                        <!-- Status Indicator -->
                        <div class="flex items-center justify-center">
                            <span :class="[
                                'inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium',
                                !recovery 
                                    ? 'bg-primary-100 dark:bg-primary-900/20 text-blue-800 ring-1 ring-inset ring-blue-200'
                                    : 'bg-yellow-100 text-yellow-800 ring-1 ring-inset ring-yellow-200'
                            ]">
                                <ShieldCheckIcon class="h-4 w-4" />
                                {{ !recovery ? 'Modo: Código de Autenticação' : 'Modo: Código de Recuperação' }}
                            </span>
                        </div>

                        <!-- Form -->
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- 6-Digit Code Input -->
                            <div v-if="!recovery" class="space-y-4">
                                <label class="block text-sm font-medium text-gray-700 flex items-center justify-center gap-2 mb-4">
                                    <FingerPrintIcon class="h-4 w-4 text-primary-800 dark:text-primary-400" />
                                    Código de 6 Dígitos
                                    <span class="text-red-500">*</span>
                                </label>
                                
                                <!-- Code Inputs Container -->
                                <div class="flex flex-col items-center">
                                    <div class="flex items-center justify-center space-x-2 md:space-x-3 mb-6">
                                        <div 
                                            v-for="(digit, index) in codeDigits" 
                                            :key="index"
                                            class="relative flex flex-col items-center"
                                        >
                                            <input
                                                :ref="el => codeInputs[index] = el"
                                                v-model="codeDigits[index]"
                                                @input="(e) => handleCodeInput(e, index)"
                                                @keydown="(e) => handleCodeKeyDown(e, index)"
                                                @paste="handlePaste"
                                                @focus="handleCodeFocus(index)"
                                                type="text"
                                                inputmode="numeric"
                                                pattern="[0-9]*"
                                                maxlength="1"
                                                :class="[
                                                    'h-14 w-14 text-center text-2xl font-bold rounded-lg border shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-1 mx-0',
                                                    form.errors.code
                                                        ? 'border-red-300 text-red-700 focus:border-red-500 focus:ring-red-500'
                                                        : 'border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:border-primary-500 dark:border-primary-400 focus:ring-primary-500'
                                                ]"
                                            />
                                            <!-- Digit indicator -->
                                            <span class="mt-2 text-xs text-gray-400 font-medium">
                                                {{ index + 1 }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Code Display (Hidden) -->
                                    <input type="hidden" v-model="form.code" />
                                    
                                    <!-- Error Message -->
                                    <div v-if="form.errors.code" class="w-full max-w-md">
                                        <p class="text-xs text-red-600 flex items-center gap-1 justify-center mt-2">
                                            <ExclamationCircleIcon class="h-4 w-4" />
                                            {{ form.errors.code }}
                                        </p>
                                    </div>
                                    
                                    <!-- Code Status -->
                                    <div v-if="codeDigits.every(d => d)" class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg w-full max-w-md">
                                        <div class="flex items-center justify-center gap-2 text-sm text-green-800">
                                            <CheckCircleIcon class="h-5 w-5" />
                                            Código completo. Pressione Enter para continuar.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recovery Code Input Field -->
                            <div v-else class="space-y-3">
                                <label for="recovery_code" class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                                    <LifebuoyIcon class="h-4 w-4 text-primary-800 dark:text-primary-400" />
                                    Código de Recuperação
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        ref="recovery_code"
                                        v-model="form.recovery_code"
                                        id="recovery_code"
                                        name="recovery_code"
                                        type="text"
                                        autocomplete="one-time-code"
                                        required
                                        placeholder="Digite o código de recuperação"
                                        :class="[
                                            'block w-full rounded-lg border px-4 py-3.5 text-sm shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-1',
                                            form.errors.recovery_code
                                                ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                                                : 'border-gray-300 dark:border-gray-700 focus:border-primary-500 dark:border-primary-400 focus:ring-primary-500'
                                        ]"
                                    />
                                    <div class="absolute right-3 top-3.5">
                                        <LifebuoyIcon class="h-5 w-5 text-gray-400" />
                                    </div>
                                </div>
                                <p v-if="form.errors.recovery_code" class="text-xs text-red-600 flex items-center gap-1">
                                    <ExclamationCircleIcon class="h-4 w-4" />
                                    {{ form.errors.recovery_code }}
                                </p>
                            </div>

                            <!-- Action Buttons -->
                            <div class="grid grid-cols-1 gap-4 mt-8">
                                <!-- Switch Mode Button -->
                                <button
                                    type="button"
                                    @click.prevent="toggleRecovery"
                                    class="group inline-flex items-center justify-center gap-3 rounded-lg border border-primary-500 dark:border-primary-400 bg-white dark:bg-gray-900 px-4 py-3.5 text-sm font-semibold text-primary-800 dark:text-primary-400 shadow-sm transition-all duration-200 hover:bg-primary-50 dark:bg-gray-800 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                                >
                                    <ArrowPathIcon class="h-5 w-5 transition-transform group-hover:rotate-180" />
                                    <template v-if="!recovery">
                                        Usar código de recuperação
                                    </template>
                                    <template v-else>
                                        Usar código de autenticação
                                    </template>
                                </button>

                                <!-- Submit Button -->
                                <button
                                    type="submit"
                                    :disabled="form.processing || (!recovery && !isCodeComplete)"
                                    :class="[
                                        'inline-flex items-center justify-center gap-3 rounded-lg px-4 py-3.5 text-sm font-semibold shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2',
                                        form.processing || (!recovery && !isCodeComplete)
                                            ? 'bg-gray-300 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                                            : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:ring-primary-500'
                                    ]"
                                >
                                    <template v-if="form.processing">
                                        <ArrowPathIcon class="h-5 w-5 animate-spin" />
                                        Verificando...
                                    </template>
                                    <template v-else>
                                        <ArrowRightOnRectangleIcon class="h-5 w-5" />
                                        {{ !recovery ? 'Verificar Código' : 'Entrar no Sistema' }}
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
                                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Dicas de uso:</h3>
                            </div>
                            
                            <div class="space-y-3 pl-7">
                                <!-- Tip 1: Pasting -->
                                <div v-if="!recovery" class="flex items-start gap-2">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/20 mt-0.5 flex-shrink-0">
                                        <span class="text-xs font-medium text-primary-800 dark:text-primary-400">1</span>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            <span class="font-medium text-primary-800 dark:text-primary-400">Cole o código completo:</span> Você pode colar os 6 dígitos diretamente em qualquer campo.
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Tip 2: Navigation -->
                                <div class="flex items-start gap-2">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/20 mt-0.5 flex-shrink-0">
                                        <span class="text-xs font-medium text-primary-800 dark:text-primary-400">{{ !recovery ? '2' : '1' }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            <span class="font-medium text-primary-800 dark:text-primary-400">Navegação rápida:</span> Use <kbd class="px-1.5 py-0.5 text-xs font-medium bg-gray-100 border border-gray-300 dark:border-gray-700 rounded">Tab</kbd> para avançar e <kbd class="px-1.5 py-0.5 text-xs font-medium bg-gray-100 border border-gray-300 dark:border-gray-700 rounded">Shift+Tab</kbd> para voltar.
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Tip 3: Auto-submit -->
                                <div v-if="!recovery" class="flex items-start gap-2">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/20 mt-0.5 flex-shrink-0">
                                        <span class="text-xs font-medium text-primary-800 dark:text-primary-400">3</span>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            <span class="font-medium text-primary-800 dark:text-primary-400">Submissão automática:</span> Pressione <kbd class="px-1.5 py-0.5 text-xs font-medium bg-gray-100 border border-gray-300 dark:border-gray-700 rounded">Enter</kbd> quando todos os dígitos estiverem preenchidos.
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Tip 4: Arrow keys -->
                                <div v-if="!recovery" class="flex items-start gap-2">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/20 mt-0.5 flex-shrink-0">
                                        <span class="text-xs font-medium text-primary-800 dark:text-primary-400">4</span>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            <span class="font-medium text-primary-800 dark:text-primary-400">Setas de navegação:</span> Use as setas <kbd class="px-1.5 py-0.5 text-xs font-medium bg-gray-100 border border-gray-300 dark:border-gray-700 rounded">←</kbd> e <kbd class="px-1.5 py-0.5 text-xs font-medium bg-gray-100 border border-gray-300 dark:border-gray-700 rounded">→</kbd> para mover entre os campos.
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Tip 5: Recovery code format -->
                                <div v-else class="flex items-start gap-2">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/20 mt-0.5 flex-shrink-0">
                                        <span class="text-xs font-medium text-primary-800 dark:text-primary-400">2</span>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            <span class="font-medium text-primary-800 dark:text-primary-400">Formato do código:</span> Códigos de recuperação são compostos por letras e números, geralmente separados por hífens.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Security Note -->
                            <div class="mt-4 p-3 bg-primary-50 dark:bg-gray-800 rounded-lg border border-blue-200">
                                <div class="flex items-start gap-2">
                                    <ShieldExclamationIcon class="h-4 w-4 text-primary-800 dark:text-primary-400 mt-0.5 flex-shrink-0" />
                                    <p class="text-xs text-blue-800">
                                        <span class="font-medium">Importante:</span> Os códigos de autenticação são válidos por apenas 30 segundos. Verifique se o horário do seu dispositivo está sincronizado.
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
                        <ShieldCheckIcon class="h-16 w-16 text-white mb-6" />
                        <h2 class="text-3xl font-bold mb-4">Segurança em Primeiro Lugar</h2>
                        <p class="text-blue-100 text-lg">
                            A autenticação de dois fatores adiciona uma camada extra de segurança à sua conta no LIMS. 
                            Mesmo que alguém descubra sua senha, eles não poderão acessar sem este código.
                        </p>
                    </div>
                    
                    <div class="space-y-6">
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white dark:bg-gray-900/20">
                                <LockClosedIcon class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold mb-1">Códigos Dinâmicos</h3>
                                <p class="text-blue-100">Cada código é válido por apenas 30 segundos, renovando automaticamente.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white dark:bg-gray-900/20">
                                <DevicePhoneMobileIcon class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold mb-1">Aplicativos Suportados</h3>
                                <p class="text-blue-100">Google Authenticator, Authy, Microsoft Authenticator e outros.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white dark:bg-gray-900/20">
                                <ClockIcon class="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold mb-1">Sincronização de Tempo</h3>
                                <p class="text-blue-100">Verifique se o horário do seu dispositivo está correto para códigos válidos.</p>
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
import { ref, computed, watch, nextTick } from 'vue'
import { useForm } from '@inertiajs/vue3';
import Logo from '../../Shared/logo.vue'
import {
    KeyIcon,
    ShieldCheckIcon,
    FingerPrintIcon,
    LifebuoyIcon,
    ExclamationCircleIcon,
    ArrowPathIcon,
    ArrowRightOnRectangleIcon,
    InformationCircleIcon,
    LockClosedIcon,
    DevicePhoneMobileIcon,
    ClockIcon,
    CheckCircleIcon,
    ShieldExclamationIcon
} from '@heroicons/vue/24/outline';

export default {
    layout: EmptyLayout,
    components: {
        Logo,
        KeyIcon,
        ShieldCheckIcon,
        FingerPrintIcon,
        LifebuoyIcon,
        ExclamationCircleIcon,
        ArrowPathIcon,
        ArrowRightOnRectangleIcon,
        InformationCircleIcon,
        LockClosedIcon,
        DevicePhoneMobileIcon,
        ClockIcon,
        CheckCircleIcon,
        ShieldExclamationIcon
    }
}
</script>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { useForm } from '@inertiajs/vue3';

defineProps({
    layout: null,
});

let recovery = ref(false);

// 6-digit code array
const codeDigits = ref(['', '', '', '', '', '']);
const codeInputs = ref([]);
const recovery_code = ref(null);

// Computed properties
const isCodeComplete = computed(() => {
    return codeDigits.value.every(digit => digit !== '');
});

const formattedCode = computed(() => {
    return codeDigits.value.join('');
});

// Watch for code changes and update form
watch(formattedCode, (newCode) => {
    form.code = newCode;
});

let form = useForm({
    code: '',
    recovery_code: '',
});

// Code input handling methods
const handleCodeInput = (event, index) => {
    const value = event.target.value;
    
    // Only allow numbers
    if (!/^\d*$/.test(value)) {
        codeDigits.value[index] = '';
        return;
    }
    
    // Take only the last character if multiple were pasted
    if (value.length > 1) {
        codeDigits.value[index] = value.charAt(value.length - 1);
    } else {
        codeDigits.value[index] = value;
    }
    
    // Auto-advance to next input
    if (value && index < 5) {
        nextTick(() => {
            codeInputs.value[index + 1]?.focus();
        });
    }
    
    // Auto-submit when all digits are filled
    if (isCodeComplete.value && index === 5) {
        nextTick(() => {
            const submitBtn = document.querySelector('[type="submit"]');
            submitBtn?.focus();
        });
    }
};

const handleCodeKeyDown = (event, index) => {
    // Handle backspace
    if (event.key === 'Backspace') {
        if (!codeDigits.value[index] && index > 0) {
            // Move to previous input and clear it
            codeDigits.value[index - 1] = '';
            nextTick(() => {
                codeInputs.value[index - 1]?.focus();
            });
        } else {
            // Clear current input
            codeDigits.value[index] = '';
        }
        event.preventDefault();
    }
    
    // Handle arrow keys
    else if (event.key === 'ArrowLeft' && index > 0) {
        nextTick(() => {
            codeInputs.value[index - 1]?.focus();
        });
        event.preventDefault();
    }
    else if (event.key === 'ArrowRight' && index < 5) {
        nextTick(() => {
            codeInputs.value[index + 1]?.focus();
        });
        event.preventDefault();
    }
    
    // Handle tab - only allow forward tab
    else if (event.key === 'Tab') {
        if (!event.shiftKey && index < 5) {
            event.preventDefault();
            nextTick(() => {
                codeInputs.value[index + 1]?.focus();
            });
        }
    }
    
    // Handle Enter to submit when complete
    else if (event.key === 'Enter' && isCodeComplete.value) {
        event.preventDefault();
        submit();
    }
};

const handlePaste = (event) => {
    event.preventDefault();
    const pastedData = event.clipboardData.getData('text').trim();
    
    // Only accept 6-digit numbers
    if (/^\d{6}$/.test(pastedData)) {
        const digits = pastedData.split('');
        digits.forEach((digit, index) => {
            if (index < 6) {
                codeDigits.value[index] = digit;
            }
        });
        
        // Focus last input
        nextTick(() => {
            codeInputs.value[5]?.focus();
        });
    }
};

const handleCodeFocus = (index) => {
    // Select the text when focused for easy replacement
    nextTick(() => {
        codeInputs.value[index]?.select();
    });
};

const submit = () => {
    if (!recovery.value && !isCodeComplete.value) {
        return;
    }
    
    form.post('/two-factor-challenge', {
        onFinish: () => {
            if (!recovery.value) {
                // Reset code digits on error
                codeDigits.value = ['', '', '', '', '', ''];
                nextTick(() => {
                    codeInputs.value[0]?.focus();
                });
            } else {
                form.reset();
            }
        },
    });
};

const toggleRecovery = async () => {
    recovery.value = !recovery.value;
    await nextTick();
    
    if (recovery.value) {
        recovery_code.value?.focus();
    } else {
        // Reset code digits and focus first input
        codeDigits.value = ['', '', '', '', '', ''];
        nextTick(() => {
            codeInputs.value[0]?.focus();
        });
    }
};
</script>

<style scoped>
/* Custom styles for code inputs */
input[type="text"] {
    caret-color: #1e3a8a; /* blue-900 */
    text-align: center;
}

/* Hide spin buttons on number inputs */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Ensure consistent input sizing */
.code-input {
    width: 3.5rem !important;
    height: 3.5rem !important;
    min-width: 3.5rem !important;
    min-height: 3.5rem !important;
    max-width: 3.5rem !important;
    max-height: 3.5rem !important;
}

/* Animation for successful code entry */
@keyframes pulse-success {
    0% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
    }
    70% {
        box-shadow: 0 0 0 6px rgba(16, 185, 129, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
    }
}

.code-complete {
    animation: pulse-success 1.5s infinite;
}

/* Make sure inputs don't overflow on mobile */
@media (max-width: 640px) {
    .code-input {
        width: 3rem !important;
        height: 3rem !important;
        min-width: 3rem !important;
        min-height: 3rem !important;
        max-width: 3rem !important;
        max-height: 3rem !important;
        font-size: 1.5rem !important;
    }
}

/* Keyboard key styling */
kbd {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    line-height: 1;
    min-width: 1.5rem;
    display: inline-block;
    text-align: center;
}
</style>