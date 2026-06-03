<template>
  <Head title="Confirmar palavra-passe" />
  <AuthExperienceShell
    title="Confirme a sua identidade"
    eyebrow="Área interna"
    description="Antes de continuar para uma área sensível, confirme a palavra-passe da sessão actual."
    context-title="Protecção contextual"
    context-description="Esta confirmação reduz risco de exposição em postos partilhados e mantém o acesso alinhado com boas práticas de segurança."
  >
    <div class="space-y-7">
      <div>
        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[#143d37] dark:text-[#f1d78b]">Área protegida</p>
        <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Confirmar palavra-passe</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Introduza a palavra-passe para continuar.</p>
      </div>
      <form @submit.prevent="submit" class="space-y-5">
        <div>
          <label for="password" class="text-sm font-semibold text-slate-800 dark:text-slate-100">Palavra-passe</label>
          <input id="password" v-model="form.password" name="password" type="password" autocomplete="current-password" required class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 shadow-sm outline-none transition focus:border-[#1f7a68] focus:ring-4 focus:ring-[#1f7a68]/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white" />
          <p v-if="form.errors.password" class="mt-2 text-xs font-medium text-red-600 dark:text-red-400">{{ form.errors.password }}</p>
        </div>
        <button type="submit" :disabled="form.processing" class="inline-flex w-full items-center justify-center rounded-2xl bg-gradient-to-r from-[#143d37] to-[#1f7a68] px-4 py-3 text-sm font-bold text-white shadow-lg shadow-[#143d37]/20 transition hover:from-[#0d2a25] hover:to-[#176452] disabled:cursor-not-allowed disabled:opacity-60">
          {{ form.processing ? 'A confirmar...' : 'Confirmar e continuar' }}
        </button>
      </form>
    </div>
  </AuthExperienceShell>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthExperienceShell from '@/Components/auth/AuthExperienceShell.vue'
import EmptyLayout from '../../Shared/EmptyLayout.vue'

defineOptions({
  layout: EmptyLayout,
})

const form = useForm({
  password: '',
})

const submit = () => {
  form.post('/user/confirm-password', {
    onFinish: () => form.reset(),
  })
}
</script>
