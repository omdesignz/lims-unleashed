<script setup>
import AuthExperienceShell from '@/Components/auth/AuthExperienceShell.vue'
import EmptyLayout from '../../Shared/EmptyLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ArrowPathIcon, KeyIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline'
import { nextTick, ref } from 'vue'

defineOptions({ layout: EmptyLayout })

const recovery = ref(false)
const codeInput = ref(null)
const recoveryInput = ref(null)
const form = useForm({ code: '', recovery_code: '' })

const submit = () => form.post('/two-factor-challenge', { onFinish: () => form.reset('code', 'recovery_code') })
const toggleRecovery = async () => {
  recovery.value = !recovery.value
  form.clearErrors()
  await nextTick()
  ;(recovery.value ? recoveryInput.value : codeInput.value)?.focus()
}
</script>

<template>
  <Head title="Autenticação de dois fatores" />
  <AuthExperienceShell title="Confirme o segundo fator" eyebrow="Área interna" description="Valide o acesso com o autenticador ou um código de recuperação autorizado." context-title="Acesso reforcado" context-description="A autenticação em dois fatores protege resultados, aprovações e documentos emitidos.">
    <div>
      <p class="ds-kicker">{{ recovery ? 'Código de recuperação' : 'Código do autenticador' }}</p><h2 class="ds-heading mt-2 text-xl">Validar acesso</h2><p class="ds-copy mt-2 text-sm">{{ recovery ? 'Introduza um dos códigos de recuperação guardados.' : 'Introduza o código de 6 digitos da aplicação autenticadora.' }}</p>
      <form class="mt-6 space-y-4" @submit.prevent="submit">
        <div v-if="!recovery" class="ds-field-group"><label for="code" class="ds-field-label">Código de autenticação</label><BaseInput id="code" ref="codeInput" v-model="form.code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" required maxlength="6" placeholder="000000" class="ds-field text-center text-xl font-black tabular-nums" :aria-invalid="Boolean(form.errors.code)" /><p v-if="form.errors.code" class="ds-field-error">{{ form.errors.code }}</p></div>
        <div v-else class="ds-field-group"><label for="recovery_code" class="ds-field-label">Código de recuperação</label><BaseInput id="recovery_code" ref="recoveryInput" v-model="form.recovery_code" name="recovery_code" type="text" autocomplete="one-time-code" required class="ds-field" :aria-invalid="Boolean(form.errors.recovery_code)" /><p v-if="form.errors.recovery_code" class="ds-field-error">{{ form.errors.recovery_code }}</p></div>
        <button type="submit" class="ds-button ds-button-primary w-full" :disabled="form.processing"><ShieldCheckIcon class="h-4 w-4" />{{ form.processing ? 'A validar...' : 'Validar e entrar' }}</button>
        <button type="button" class="ds-button ds-button-ghost w-full" @click="toggleRecovery"><ArrowPathIcon v-if="!recovery" class="h-4 w-4" /><KeyIcon v-else class="h-4 w-4" />{{ recovery ? 'Usar autenticador' : 'Usar código de recuperação' }}</button>
      </form>
    </div>
  </AuthExperienceShell>
</template>
