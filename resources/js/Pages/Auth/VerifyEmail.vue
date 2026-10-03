<script setup>
import AuthExperienceShell from '@/Components/auth/AuthExperienceShell.vue'
import EmptyLayout from '../../Shared/EmptyLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { LogOut as ArrowRightStartOnRectangleIcon, Mail as EnvelopeIcon } from '@lucide/vue'
import { computed } from 'vue'

const props = defineProps({ status: String })
defineOptions({ layout: EmptyLayout })

const form = useForm({})
const verificationLinkSent = computed(() => props.status === 'verification-link-sent')
const submit = () => form.post('/email/verification-notification')
</script>

<template>
  <Head title="Verificação de correio electrónico" />
  <AuthExperienceShell title="Verifique o correio electrónico da conta" eyebrow="Área interna" description="Confirme o endereço para receber alertas, aprovações e tarefas operacionais." context-title="Notificações confiáveis" context-description="A verificação garante que comunicacoes críticas chegam ao responsável correcto.">
    <div>
      <p class="ds-copy text-sm">Use o ligação enviado para a sua caixa de entrada. Pode solicitar um novo envio.</p>
      <div v-if="verificationLinkSent" class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">Foi enviado um novo ligação de verificação.</div>
      <form class="mt-6 space-y-3" @submit.prevent="submit">
        <button type="submit" class="ds-button ds-button-primary w-full" :disabled="form.processing"><EnvelopeIcon class="h-4 w-4" />{{ form.processing ? 'A reenviar...' : 'Reenviar email de verificação' }}</button>
        <Link :href="route('logout')" method="post" as="button" class="ds-button ds-button-ghost w-full"><ArrowRightStartOnRectangleIcon class="h-4 w-4" />Terminar sessão</Link>
      </form>
    </div>
  </AuthExperienceShell>
</template>
