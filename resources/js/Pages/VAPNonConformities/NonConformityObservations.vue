<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({ record: { type: Object, required: true } })
const form = useForm({ comments: props.record.comments ?? '' })
function submit() {
  if (form.processing) return
  form.put(route('vap_non_conformities.update', props.record.id), {
    preserveScroll: true,
    onHttpException: () => { form.setError('request', 'Não foi possível guardar. Observações preservadas.'); return false },
    onNetworkError: () => { form.setError('request', 'Ligação interrompida. Tente novamente.'); return false },
    onCancel: () => form.setError('request', 'Pedido cancelado. Observações preservadas.'),
  })
}
</script>

<template>
  <form class="ds-panel space-y-4 p-5" @submit.prevent="submit">
    <h2 class="ds-heading">Dossier encerrado</h2>
    <p class="text-sm text-[var(--ds-text-muted)]">Dados, acções e anexos estão bloqueados. Apenas as observações podem ser alteradas; para corrigir o dossier, use a reabertura auditada.</p>
    <label for="closed-comments" class="block text-sm font-semibold">Observações</label>
    <textarea id="closed-comments" v-model="form.comments" class="ds-input w-full" rows="5" maxlength="20000" :disabled="form.processing" :aria-invalid="!!form.errors.comments" aria-describedby="comments-errors" />
    <div id="comments-errors" role="alert"><p v-for="(error, key) in form.errors" :key="key">{{ error }}</p></div>
    <button class="ds-button ds-button-primary" :disabled="form.processing">{{ form.processing ? 'A guardar…' : 'Guardar observações' }}</button>
  </form>
</template>
