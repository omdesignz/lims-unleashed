<script setup>
import AuthExperienceShell from '@/Components/auth/AuthExperienceShell.vue'
import EmptyLayout from '../../Shared/EmptyLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Eye as EyeIcon, EyeOff as EyeSlashIcon, ShieldCheck as ShieldCheckIcon } from '@lucide/vue'
import { ref } from 'vue'

defineOptions({ layout: EmptyLayout })

const showPassword = ref(false)
const form = useForm({ password: '' })
const submit = () => form.post('/user/confirm-password', { onFinish: () => form.reset() })
</script>

<template>
  <Head title="Confirmar palavra-passe" />
  <AuthExperienceShell title="Confirme a sua identidade" eyebrow="Área protegida" description="Antes de continuar para uma operação sensível, confirme a credencial da sessão actual." context-title="Protecção contextual" context-description="Esta confirmação reduz o risco de exposição em postos partilhados.">
    <div>
      <p class="ds-copy text-sm">Introduza a sua palavra-passe para continuar.</p>
      <form class="mt-6 space-y-4" @submit.prevent="submit">
        <div class="ds-field-group"><label for="password" class="ds-field-label">Palavra-passe</label><div class="relative"><BaseInput id="password" v-model="form.password" name="password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" required class="ds-field pr-11" :aria-invalid="Boolean(form.errors.password)" /><button type="button" class="absolute right-1.5 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-lg text-[var(--ds-text-soft)] hover:bg-[var(--ds-panel-subtle)]" :title="showPassword ? 'Ocultar palavra-passe' : 'Mostrar palavra-passe'" @click="showPassword = !showPassword"><EyeSlashIcon v-if="showPassword" class="h-4 w-4" /><EyeIcon v-else class="h-4 w-4" /></button></div><p v-if="form.errors.password" class="ds-field-error">{{ form.errors.password }}</p></div>
        <button type="submit" class="ds-button ds-button-primary w-full" :disabled="form.processing"><ShieldCheckIcon class="h-4 w-4" />{{ form.processing ? 'A confirmar...' : 'Confirmar e continuar' }}</button>
      </form>
    </div>
  </AuthExperienceShell>
</template>
