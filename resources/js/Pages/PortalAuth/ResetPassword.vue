<template>
  <Head title="Redefinir palavra-passe" />
  <AuthExperienceShell
    mode="portal"
    title="Defina uma nova palavra-passe"
    eyebrow="Portal do cliente"
    description="Conclua a recuperação de acesso com uma palavra-passe forte para proteger propostas, certificados e comunicação comercial."
    context-title="Acesso revalidado"
    context-description="Depois de redefinir a palavra-passe, o cliente volta ao fluxo normal do portal com sessão segura."
  >
    <div class="space-y-7">
      <div>
        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[#143d37] dark:text-[#f1d78b]">
          Segurança
        </p>
        <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-950 dark:text-white">
          Nova palavra-passe
        </h2>
        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
          Use uma palavra-passe única e robusta. O email vem do link de recuperação e fica associado ao pedido.
        </p>
      </div>

      <form @submit.prevent="submit" class="space-y-5">
        <input type="hidden" v-model="form.token" />

        <div>
          <label for="email" class="text-sm font-semibold text-slate-800 dark:text-slate-100">
            Email
          </label>
          <input
            id="email"
            v-model="form.email"
            name="email"
            type="email"
            autocomplete="email"
            readonly
            class="mt-2 block w-full cursor-not-allowed rounded-2xl border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400"
          />
          <p v-if="form.errors.email" class="mt-2 text-xs font-medium text-red-600 dark:text-red-400">
            {{ form.errors.email }}
          </p>
        </div>

        <div>
          <label for="password" class="text-sm font-semibold text-slate-800 dark:text-slate-100">
            Palavra-passe
          </label>
          <input
            id="password"
            v-model="form.password"
            name="password"
            type="password"
            autocomplete="new-password"
            required
            class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 shadow-sm outline-none transition focus:border-[#1f7a68] focus:ring-4 focus:ring-[#1f7a68]/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
          />
          <p v-if="form.errors.password" class="mt-2 text-xs font-medium text-red-600 dark:text-red-400">
            {{ form.errors.password }}
          </p>
        </div>

        <div>
          <label for="password_confirmation" class="text-sm font-semibold text-slate-800 dark:text-slate-100">
            Confirmar palavra-passe
          </label>
          <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            name="password_confirmation"
            type="password"
            autocomplete="new-password"
            required
            class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 shadow-sm outline-none transition focus:border-[#1f7a68] focus:ring-4 focus:ring-[#1f7a68]/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
          />
          <p v-if="form.errors.password_confirmation" class="mt-2 text-xs font-medium text-red-600 dark:text-red-400">
            {{ form.errors.password_confirmation }}
          </p>
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="inline-flex w-full items-center justify-center rounded-2xl bg-gradient-to-r from-[#143d37] to-[#1f7a68] px-4 py-3 text-sm font-bold text-white shadow-lg shadow-[#143d37]/20 transition hover:from-[#0d2a25] hover:to-[#176452] disabled:cursor-not-allowed disabled:opacity-60"
        >
          {{ form.processing ? 'A actualizar...' : 'Redefinir palavra-passe' }}
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
  token: route().params.token,
  email: route().params.email,
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.post(route('portal.password.update'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>
