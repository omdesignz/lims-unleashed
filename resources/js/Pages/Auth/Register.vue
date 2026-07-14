<script setup>
import AuthExperienceShell from '@/Components/auth/AuthExperienceShell.vue'
import EmptyLayout from '../../Shared/EmptyLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { EyeIcon, EyeSlashIcon, UserPlusIcon } from '@heroicons/vue/24/outline'
import { ref } from 'vue'

defineOptions({ layout: EmptyLayout })

const showPassword = ref(false)
const form = useForm({ name: '', email: '', password: '', password_confirmation: '', terms: false })

const submit = () => {
  form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') })
}
</script>

<template>
  <Head title="Criar conta" />
  <AuthExperienceShell
    title="Crie uma conta interna"
    eyebrow="Área interna"
    description="Prepare um utilizador para operar amostras, resultados, documentos e processos do SGQ com rastreabilidade."
    context-title="Onboarding com responsabilidade"
    context-description="Associe posteriormente a funcao, departamento e permissoes adequadas ao perfil operacional."
  >
    <div>
      <p class="ds-kicker">Registo</p>
      <h2 class="ds-heading mt-2 text-xl">Criar utilizador</h2>
      <p class="ds-copy mt-2 text-sm">Introduza os dados de identificação e defina uma credencial segura.</p>

      <form class="mt-6 space-y-4" @submit.prevent="submit">
        <div class="ds-field-group"><label for="name" class="ds-field-label">Nome</label><BaseInput id="name" v-model="form.name" name="name" type="text" autocomplete="name" class="ds-field" :aria-invalid="Boolean(form.errors.name)" /><p v-if="form.errors.name" class="ds-field-error">{{ form.errors.name }}</p></div>
        <div class="ds-field-group"><label for="email" class="ds-field-label">Correio electrónico</label><BaseInput id="email" v-model="form.email" name="email" type="email" autocomplete="email" class="ds-field" :aria-invalid="Boolean(form.errors.email)" /><p v-if="form.errors.email" class="ds-field-error">{{ form.errors.email }}</p></div>
        <div class="ds-field-group">
          <label for="password" class="ds-field-label">Palavra-passe</label>
          <div class="relative"><BaseInput id="password" v-model="form.password" name="password" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" class="ds-field pr-11" :aria-invalid="Boolean(form.errors.password)" /><button type="button" class="absolute right-1.5 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-lg text-[var(--ds-text-soft)] hover:bg-[var(--ds-panel-subtle)]" :title="showPassword ? 'Ocultar palavra-passe' : 'Mostrar palavra-passe'" @click="showPassword = !showPassword"><EyeSlashIcon v-if="showPassword" class="h-4 w-4" /><EyeIcon v-else class="h-4 w-4" /></button></div>
          <p v-if="form.errors.password" class="ds-field-error">{{ form.errors.password }}</p>
        </div>
        <div class="ds-field-group"><label for="password_confirmation" class="ds-field-label">Confirmar palavra-passe</label><BaseInput id="password_confirmation" v-model="form.password_confirmation" name="password_confirmation" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" class="ds-field" :aria-invalid="Boolean(form.errors.password_confirmation)" /><p v-if="form.errors.password_confirmation" class="ds-field-error">{{ form.errors.password_confirmation }}</p></div>

        <label class="flex items-start gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3 text-sm font-semibold leading-6 text-[var(--ds-text-muted)]"><CheckboxInput id="terms" v-model="form.terms" name="terms" type="checkbox" class="ds-checkbox mt-1" /><span>Confirmo o uso conforme as politicas internas de confidencialidade, imparcialidade e seguranca.</span></label>
        <p v-if="form.errors.terms" class="ds-field-error">{{ form.errors.terms }}</p>

        <button type="submit" class="ds-button ds-button-primary w-full" :disabled="form.processing"><UserPlusIcon class="h-4 w-4" />{{ form.processing ? 'A criar...' : 'Criar conta' }}</button>
        <p class="text-center text-sm font-semibold text-[var(--ds-text-muted)]">Já tem conta? <Link :href="route('login')" class="font-bold text-[rgb(var(--primary-700-rgb))] hover:underline">Iniciar sessão</Link></p>
      </form>
    </div>
  </AuthExperienceShell>
</template>
