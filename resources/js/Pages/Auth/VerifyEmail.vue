<template>
  <Head title="Verificação de email" />
  <AuthExperienceShell
    title="Verifique o email da conta"
    eyebrow="Área interna"
    description="Confirme o email para receber alertas críticos, notificações de amostras, aprovações e tarefas operacionais."
    context-title="Notificações confiáveis"
    context-description="A verificação ajuda a garantir que comunicações críticas chegam ao responsável certo."
  >
    <div class="space-y-7">
      <div>
        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[#143d37] dark:text-[#f1d78b]">Verificação</p>
        <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Confirme o seu endereço de email</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Use o link enviado. Se necessário, solicite um novo envio.</p>
      </div>
      <div v-if="verificationLinkSent" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-200">Foi enviado um novo link de verificação.</div>
      <form @submit.prevent="submit" class="space-y-3">
        <button type="submit" :disabled="form.processing" class="inline-flex w-full items-center justify-center rounded-2xl bg-gradient-to-r from-[#143d37] to-[#1f7a68] px-4 py-3 text-sm font-bold text-white shadow-lg shadow-[#143d37]/20 transition hover:from-[#0d2a25] hover:to-[#176452] disabled:cursor-not-allowed disabled:opacity-60">
          {{ form.processing ? 'A reenviar...' : 'Reenviar email de verificação' }}
        </button>
        <Link :href="route('logout')" method="post" as="button" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Sair</Link>
      </form>
    </div>
  </AuthExperienceShell>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthExperienceShell from '@/Components/auth/AuthExperienceShell.vue'
import EmptyLayout from '../../Shared/EmptyLayout.vue'

const props = defineProps({
  status: String,
})

defineOptions({
  layout: EmptyLayout,
})

const form = useForm({})
const verificationLinkSent = computed(() => props.status === 'verification-link-sent')

const submit = () => {
  form.post('/email/verification-notification')
}
</script>
