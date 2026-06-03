<template>
  <Head title="Autenticação de dois factores" />
  <AuthExperienceShell
    mode="portal"
    title="Confirmação em dois factores"
    eyebrow="Portal do cliente"
    description="Valide o acesso com o código do autenticador ou use um código de recuperação quando necessário."
    context-title="Sessão reforçada"
    context-description="O segundo factor reduz risco de acesso indevido a propostas, amostras e certificados do cliente."
  >
    <div class="space-y-7">
      <div>
        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[#143d37] dark:text-[#f1d78b]">
          {{ recovery ? 'Código de recuperação' : 'Código de autenticação' }}
        </p>
        <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-950 dark:text-white">
          Validar acesso
        </h2>
        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
          {{ recovery ? 'Insira um dos códigos de recuperação guardados.' : 'Insira o código de 6 dígitos do seu aplicativo autenticador.' }}
        </p>
      </div>

      <form @submit.prevent="submit" class="space-y-5">
        <div v-if="!recovery">
          <label for="code" class="text-sm font-semibold text-slate-800 dark:text-slate-100">
            Código
          </label>
          <input
            id="code"
            ref="code"
            v-model="form.code"
            name="code"
            type="text"
            inputmode="numeric"
            autocomplete="one-time-code"
            required
            maxlength="6"
            placeholder="000000"
            class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-center text-2xl font-black tracking-[0.35em] text-slate-950 shadow-sm outline-none transition focus:border-[#1f7a68] focus:ring-4 focus:ring-[#1f7a68]/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
          />
          <p v-if="form.errors.code" class="mt-2 text-xs font-medium text-red-600 dark:text-red-400">
            {{ form.errors.code }}
          </p>
        </div>

        <div v-else>
          <label for="recovery_code" class="text-sm font-semibold text-slate-800 dark:text-slate-100">
            Código de recuperação
          </label>
          <input
            id="recovery_code"
            ref="recovery_code"
            v-model="form.recovery_code"
            name="recovery_code"
            type="text"
            autocomplete="one-time-code"
            required
            class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 shadow-sm outline-none transition focus:border-[#1f7a68] focus:ring-4 focus:ring-[#1f7a68]/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
          />
          <p v-if="form.errors.recovery_code" class="mt-2 text-xs font-medium text-red-600 dark:text-red-400">
            {{ form.errors.recovery_code }}
          </p>
        </div>

        <div class="grid gap-3 sm:grid-cols-[1fr_0.75fr]">
          <button
            type="button"
            @click.prevent="toggleRecovery"
            class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
          >
            {{ recovery ? 'Usar autenticador' : 'Usar recuperação' }}
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-[#143d37] to-[#1f7a68] px-4 py-3 text-sm font-bold text-white shadow-lg shadow-[#143d37]/20 transition hover:from-[#0d2a25] hover:to-[#176452] disabled:cursor-not-allowed disabled:opacity-60"
          >
            {{ form.processing ? 'A validar...' : 'Entrar' }}
          </button>
        </div>
      </form>
    </div>
  </AuthExperienceShell>
</template>

<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AuthExperienceShell from '@/Components/auth/AuthExperienceShell.vue'
import EmptyLayout from '../../Shared/EmptyLayout.vue'

defineOptions({
  layout: EmptyLayout,
})

const recovery = ref(false)
const form = useForm({
  code: '',
  recovery_code: '',
})

const submit = () => {
  form.post(route('portal.two-factor.login.store'), {
    onFinish: () => form.reset('code', 'recovery_code'),
  })
}

const toggleRecovery = () => {
  recovery.value = !recovery.value
  form.clearErrors()
}
</script>
