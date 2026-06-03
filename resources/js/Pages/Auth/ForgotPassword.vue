<template>
  <Head title="Recuperar acesso" />
  <AuthExperienceShell
    title="Recupere o acesso interno"
    eyebrow="Área interna"
    description="Restaure o acesso de forma segura sem quebrar rastreabilidade, confidencialidade ou continuidade operacional."
    context-title="Recuperação controlada"
    context-description="O link de recuperação é enviado para o email associado à conta e deve ser usado apenas pelo titular."
  >
    <div class="space-y-7">
      <div>
        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[#143d37] dark:text-[#f1d78b]">Recuperação</p>
        <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Restaurar palavra-passe</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Informe o email da conta. Enviaremos instruções para redefinir o acesso.</p>
      </div>

      <div v-if="status" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-200">
        {{ status }}
      </div>

      <form @submit.prevent="submit" class="space-y-5">
        <div>
          <label for="email" class="text-sm font-semibold text-slate-800 dark:text-slate-100">Email</label>
          <input id="email" v-model="form.email" name="email" type="email" autocomplete="email" required placeholder="utilizador@laboratorio.co.ao" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 shadow-sm outline-none transition focus:border-[#1f7a68] focus:ring-4 focus:ring-[#1f7a68]/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white" />
          <p v-if="form.errors.email" class="mt-2 text-xs font-medium text-red-600 dark:text-red-400">{{ form.errors.email }}</p>
        </div>

        <div class="grid gap-3 sm:grid-cols-[0.45fr_1fr]">
          <Link :href="route('login')" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Voltar</Link>
          <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-[#143d37] to-[#1f7a68] px-4 py-3 text-sm font-bold text-white shadow-lg shadow-[#143d37]/20 transition hover:from-[#0d2a25] hover:to-[#176452] disabled:cursor-not-allowed disabled:opacity-60">
            {{ form.processing ? 'A enviar...' : 'Enviar instruções' }}
          </button>
        </div>
      </form>
    </div>
  </AuthExperienceShell>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthExperienceShell from '@/Components/auth/AuthExperienceShell.vue'
import EmptyLayout from '../../Shared/EmptyLayout.vue'

defineProps({
  status: String,
})

defineOptions({
  layout: EmptyLayout,
})

const form = useForm({
  email: '',
})

const submit = () => {
  form.post('/forgot-password', {
    onFinish: () => form.reset('email'),
  })
}
</script>
