<script setup>
import AuthExperienceShell from '@/Components/auth/AuthExperienceShell.vue'
import EmptyLayout from '../../Shared/EmptyLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { LogOut as ArrowRightStartOnRectangleIcon, Mail as EnvelopeIcon } from '@lucide/vue'
import { computed } from 'vue'

const props = defineProps({ status: String })
defineOptions({ layout: EmptyLayout })
const form = useForm({})
const verificationLinkSent = computed(() => props.status === 'verification-link-sent')
const submit = () => form.post(route('portal.verification.send'))
</script>

<template>
  <Head title="Verificar correio electrónico do portal" />
  <AuthExperienceShell mode="portal" title="Verifique o correio electrónico da conta" eyebrow="Portal do cliente" description="Confirme o endereço para receber propostas, certificados e notificações laboratoriais." context-title="Comunicação confiável" context-description="O correio electrónico verificado garante que documentos e alertas chegam ao contacto correcto.">
    <div><p class="ds-copy text-sm">Use a ligação enviada para a sua caixa de entrada ou solicite um novo envio.</p><div v-if="verificationLinkSent" class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">Foi enviada uma nova ligação de verificação.</div><form class="mt-6 space-y-3" @submit.prevent="submit"><button type="submit" class="ds-button ds-button-primary w-full" :disabled="form.processing"><EnvelopeIcon class="h-4 w-4" />{{ form.processing ? 'A reenviar...' : 'Reenviar correio de verificação' }}</button><Link :href="route('portal.logout')" method="post" as="button" class="ds-button ds-button-ghost w-full"><ArrowRightStartOnRectangleIcon class="h-4 w-4" />Terminar sessão</Link></form></div>
  </AuthExperienceShell>
</template>
