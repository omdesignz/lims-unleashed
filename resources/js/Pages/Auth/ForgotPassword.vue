<script setup>
import AuthExperienceShell from '@/Components/auth/AuthExperienceShell.vue'
import EmptyLayout from '../../Shared/EmptyLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeftIcon, EnvelopeIcon } from '@heroicons/vue/24/outline'

defineProps({ status: String })
defineOptions({ layout: EmptyLayout })

const form = useForm({ email: '' })
const submit = () => form.post('/forgot-password')
</script>

<template>
  <Head title="Recuperar acesso" />
  <AuthExperienceShell title="Recupere o acesso interno" eyebrow="Área interna" description="Restaure o acesso de forma segura sem comprometer a rastreabilidade operacional." context-title="Recuperação controlada" context-description="O link e enviado apenas para o email associado a conta e deve ser usado pelo titular.">
    <div>
      <p class="ds-kicker">Recuperação</p><h2 class="ds-heading mt-2 text-xl">Restaurar palavra-passe</h2><p class="ds-copy mt-2 text-sm">Enviaremos instruções para o correio electrónico associado a conta.</p>
      <div v-if="status" class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">{{ status }}</div>
      <form class="mt-6 space-y-4" @submit.prevent="submit">
        <div class="ds-field-group"><label for="email" class="ds-field-label">Correio electrónico</label><BaseInput id="email" v-model="form.email" name="email" type="email" autocomplete="email" required class="ds-field" placeholder="utilizador@laboratório.co.ao" :aria-invalid="Boolean(form.errors.email)" /><p v-if="form.errors.email" class="ds-field-error">{{ form.errors.email }}</p></div>
        <button type="submit" class="ds-button ds-button-primary w-full" :disabled="form.processing"><EnvelopeIcon class="h-4 w-4" />{{ form.processing ? 'A enviar...' : 'Enviar instruções' }}</button>
        <Link :href="route('login')" class="ds-button ds-button-ghost w-full"><ArrowLeftIcon class="h-4 w-4" />Voltar ao inicio de sessão</Link>
      </form>
    </div>
  </AuthExperienceShell>
</template>
