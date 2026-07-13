<script setup>
import AuthExperienceShell from '@/Components/auth/AuthExperienceShell.vue'
import EmptyLayout from '../../Shared/EmptyLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowRightStartOnRectangleIcon, EnvelopeIcon } from '@heroicons/vue/24/outline'
import { computed } from 'vue'

const props = defineProps({ status: String })
defineOptions({ layout: EmptyLayout })

const form = useForm({})
const verificationLinkSent = computed(() => props.status === 'verification-link-sent')
const submit = () => form.post('/email/verification-notification')
</script>

<template>
  <Head title="Verificacao de email" />
  <AuthExperienceShell title="Verifique o email da conta" eyebrow="Area interna" description="Confirme o endereco para receber alertas, aprovacoes e tarefas operacionais." context-title="Notificacoes confiaveis" context-description="A verificacao garante que comunicacoes criticas chegam ao responsavel correto.">
    <div>
      <p class="ds-kicker">Verificacao</p><h2 class="ds-heading mt-2 text-xl">Confirme o seu email</h2><p class="ds-copy mt-2 text-sm">Use o link enviado para a sua caixa de entrada. Pode solicitar um novo envio.</p>
      <div v-if="verificationLinkSent" class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">Foi enviado um novo link de verificacao.</div>
      <form class="mt-6 space-y-3" @submit.prevent="submit">
        <button type="submit" class="ds-button ds-button-primary w-full" :disabled="form.processing"><EnvelopeIcon class="h-4 w-4" />{{ form.processing ? 'A reenviar...' : 'Reenviar email de verificacao' }}</button>
        <Link :href="route('logout')" method="post" as="button" class="ds-button ds-button-ghost w-full"><ArrowRightStartOnRectangleIcon class="h-4 w-4" />Terminar sessao</Link>
      </form>
    </div>
  </AuthExperienceShell>
</template>
