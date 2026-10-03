<script setup>
import SlideOver from '@/Components/slide-over.vue'
import { useForm } from '@inertiajs/vue3'

const emit = defineEmits(['close'])
const form = useForm({ email: '' })
function close() {
  if (form.processing) return
  emit('close')
}
function submit() {
  if (form.processing) return
  form.clearErrors()
  form.transform(({ email }) => ({ email })).post(route('users.membership.store'), {
    preserveScroll: true,
    onSuccess: () => { form.reset(); emit('close') },
    onNetworkError: () => form.setError('request', 'Ligação interrompida. A adesão não foi confirmada.'),
    onHttpException: () => form.setError('request', 'Não foi possível adicionar o membro. O email foi preservado.'),
  })
}
</script>

<template>
  <SlideOver title="Adicionar membro" description="Adicione uma conta existente à equipa deste laboratório. Os dados pessoais, a palavra-passe e os acessos globais não serão alterados." :disabled="form.processing" @close="close">
    <template #content>
      <form id="laboratory-membership-form" class="space-y-4 px-6 py-5" @submit.prevent="submit">
        <p class="ds-copy text-sm">Use o email registado de uma conta activa e verificada. Novas contas são criadas pelo administrador do sistema. A adesão não concede acesso à rede nem gestão de marca.</p>
        <label for="membership-email" class="ds-field-label">Email registado</label>
        <input id="membership-email" v-model="form.email" class="ds-field" type="email" autocomplete="off" required maxlength="255" :disabled="form.processing" :aria-invalid="Boolean(form.errors.email)" :aria-describedby="form.hasErrors ? 'membership-error' : undefined" />
        <p v-if="form.hasErrors" id="membership-error" role="alert" class="ds-field-error">{{ form.errors.email || form.errors.request }}</p>
      </form>
    </template>
    <template #action_buttons>
      <button type="button" class="ds-button ds-button-secondary" :disabled="form.processing" @click="close">Cancelar</button>
      <button type="submit" form="laboratory-membership-form" class="ds-button ds-button-primary" :disabled="form.processing">{{ form.processing ? 'A adicionar…' : 'Adicionar membro' }}</button>
    </template>
  </SlideOver>
</template>
