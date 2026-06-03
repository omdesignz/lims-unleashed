<template>
  <Head title="Criar conta de cliente" />
  <AuthExperienceShell
    mode="portal"
    title="Crie uma conta de cliente"
    eyebrow="Portal do cliente"
    description="Dê aos clientes uma entrada cuidada para pedidos, propostas, amostras, facturação e certificados."
    context-title="Acesso externo com controlo"
    context-description="O portal permite colaboração com clientes sem perder confidencialidade, evidência e rastreabilidade documental."
  >
    <div class="space-y-7">
      <div>
        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[#143d37] dark:text-[#f1d78b]">
          Novo cliente
        </p>
        <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-950 dark:text-white">
          Registar acesso
        </h2>
        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
          A conta poderá acompanhar documentos comerciais, pedidos e certificados quando associada ao cliente correcto.
        </p>
      </div>

      <form @submit.prevent="submit" class="space-y-5">
        <div>
          <label for="name" class="text-sm font-semibold text-slate-800 dark:text-slate-100">Nome</label>
          <input id="name" v-model="form.name" name="name" type="text" autocomplete="name" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 shadow-sm outline-none transition focus:border-[#1f7a68] focus:ring-4 focus:ring-[#1f7a68]/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white" />
          <p v-if="form.errors.name" class="mt-2 text-xs font-medium text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
        </div>

        <div>
          <label for="email" class="text-sm font-semibold text-slate-800 dark:text-slate-100">Email</label>
          <input id="email" v-model="form.email" name="email" type="email" autocomplete="email" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 shadow-sm outline-none transition focus:border-[#1f7a68] focus:ring-4 focus:ring-[#1f7a68]/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white" />
          <p v-if="form.errors.email" class="mt-2 text-xs font-medium text-red-600 dark:text-red-400">{{ form.errors.email }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label for="password" class="text-sm font-semibold text-slate-800 dark:text-slate-100">Palavra-passe</label>
            <input id="password" v-model="form.password" name="password" type="password" autocomplete="new-password" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 shadow-sm outline-none transition focus:border-[#1f7a68] focus:ring-4 focus:ring-[#1f7a68]/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white" />
            <p v-if="form.errors.password" class="mt-2 text-xs font-medium text-red-600 dark:text-red-400">{{ form.errors.password }}</p>
          </div>
          <div>
            <label for="password_confirmation" class="text-sm font-semibold text-slate-800 dark:text-slate-100">Confirmar</label>
            <input id="password_confirmation" v-model="form.password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 shadow-sm outline-none transition focus:border-[#1f7a68] focus:ring-4 focus:ring-[#1f7a68]/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white" />
            <p v-if="form.errors.password_confirmation" class="mt-2 text-xs font-medium text-red-600 dark:text-red-400">{{ form.errors.password_confirmation }}</p>
          </div>
        </div>

        <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300">
          <input v-model="form.terms" id="terms" name="terms" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#143d37] focus:ring-[#1f7a68] dark:border-slate-700" />
          <span>Aceito as condições de utilização do portal e a política de confidencialidade.</span>
        </label>
        <p v-if="form.errors.terms" class="text-xs font-medium text-red-600 dark:text-red-400">{{ form.errors.terms }}</p>

        <div class="grid gap-3 sm:grid-cols-[1fr_0.75fr]">
          <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-[#143d37] to-[#1f7a68] px-4 py-3 text-sm font-bold text-white shadow-lg shadow-[#143d37]/20 transition hover:from-[#0d2a25] hover:to-[#176452] disabled:cursor-not-allowed disabled:opacity-60">
            {{ form.processing ? 'A criar...' : 'Criar acesso' }}
          </button>
          <Link :href="route('portal.login')" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
            Já tenho conta
          </Link>
        </div>
      </form>
    </div>
  </AuthExperienceShell>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthExperienceShell from '@/Components/auth/AuthExperienceShell.vue'
import EmptyLayout from '../../Shared/EmptyLayout.vue'

defineOptions({
  layout: EmptyLayout,
})

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  terms: false,
})

const submit = () => {
  form.post('/register', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>
