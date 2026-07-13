<script setup>
import AuthExperienceShell from '@/Components/auth/AuthExperienceShell.vue'
import EmptyLayout from '../../Shared/EmptyLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeftIcon, EnvelopeIcon } from '@heroicons/vue/24/outline'

defineOptions({ layout: EmptyLayout })
const form = useForm({ email: '' })
const submit = () => form.post(route('portal.password.email'))
</script>

<template>
  <Head title="Recuperar acesso ao portal" />
  <AuthExperienceShell mode="portal" title="Recupere o acesso ao portal" eyebrow="Portal do cliente" description="Reponha o acesso a propostas, amostras, resultados e certificados sem contacto manual com a equipa." context-title="Pedido seguro por email" context-description="As instrucoes seguem apenas para o endereco associado a conta do cliente.">
    <div><p class="ds-kicker">Recuperacao</p><h2 class="ds-heading mt-2 text-xl">Restaurar palavra-passe</h2><p class="ds-copy mt-2 text-sm">Se existir uma conta ativa, enviaremos um link seguro de redefinicao.</p>
      <form class="mt-6 space-y-4" @submit.prevent="submit"><div class="ds-field-group"><label for="portal-email" class="ds-field-label">Email</label><input id="portal-email" v-model="form.email" name="email" type="email" autocomplete="email" required class="ds-field" placeholder="cliente@empresa.co.ao" :aria-invalid="Boolean(form.errors.email)" /><p v-if="form.errors.email" class="ds-field-error">{{ form.errors.email }}</p></div><button type="submit" class="ds-button ds-button-primary w-full" :disabled="form.processing"><EnvelopeIcon class="h-4 w-4" />{{ form.processing ? 'A enviar...' : 'Enviar instrucoes' }}</button><Link :href="route('portal.login')" class="ds-button ds-button-ghost w-full"><ArrowLeftIcon class="h-4 w-4" />Voltar ao portal</Link></form>
    </div>
  </AuthExperienceShell>
</template>
