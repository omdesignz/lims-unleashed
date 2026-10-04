<script setup>
import { useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({ record: { type: Object, required: true }, can: { type: Object, default: () => ({}) } })
const selected = ref('')
const form = useForm({ request_id: '', workflow_revision: props.record.workflow_revision, evidence: '' })
const labels = { resolve: 'Registar resolução', verify: 'Verificar resolução', close: 'Encerrar dossier', reopen: 'Reabrir dossier', edited: 'Edição registada', verification_invalidated: 'Verificação invalidada' }
const choices = computed(() => {
  if (props.record.deleted_at) return []
  const status = props.record.status
  return [
    ...(['opened', 'in_progress'].includes(status) ? ['resolve'] : []),
    ...(status === 'resolved' && props.record.resolution_evidence && !props.record.verified_at ? ['verify'] : []),
    ...(status === 'resolved' && props.record.verified_at && props.record.verification_evidence ? ['close'] : []),
    ...(['resolved', 'closed'].includes(status) ? ['reopen'] : []),
  ].filter(action => props.can[action])
})
const history = computed(() => [...(props.record.workflow_history ?? [])].reverse())
function choose(action) {
  selected.value = action
  form.clearErrors()
  form.evidence = ''
  form.request_id = crypto.randomUUID()
  form.workflow_revision = props.record.workflow_revision
}
function submit() {
  if (form.processing || !selected.value) return
  form.post(route('vap_non_conformities.transition', [props.record.id, selected.value]), {
    preserveScroll: true,
    onSuccess: () => { selected.value = ''; form.reset('evidence') },
    onHttpException: response => {
      form.setError('request', response.status === 409 ? 'O dossier mudou. Actualize a página antes de continuar; copie a evidência se necessário.' : 'Não foi possível registar a etapa. Evidência preservada.')
      return false
    },
    onNetworkError: () => { form.setError('request', 'Ligação interrompida. Pode repetir: a mesma operação não será registada duas vezes.'); return false },
    onCancel: () => form.setError('request', 'Pedido cancelado. Evidência preservada.'),
  })
}
</script>

<template>
  <section class="ds-panel space-y-4 p-5" aria-labelledby="lifecycle-heading">
    <div>
      <h2 id="lifecycle-heading" class="ds-heading">Resolução → Verificação → Encerramento</h2>
      <p class="mt-1 text-sm text-[var(--ds-text-muted)]">{{ record.status === 'closed' ? 'Dossier bloqueado. Observações continuam editáveis; correcções exigem reabertura.' : 'Cada etapa regista responsável, data e evidência. Alterações ao dossier resolvido exigem nova verificação.' }}</p>
      <p v-if="record.verified_at" class="mt-2 text-sm font-semibold">Verificação registada · {{ new Date(record.verified_at).toLocaleString('pt-PT') }}</p>
      <p v-if="record.status === 'resolved' && !record.resolution_evidence" class="mt-2 text-sm">Este registo não tem evidência de resolução no novo fluxo. Reabra para iniciar um ciclo documentado.</p>
    </div>
    <div v-if="choices.length" class="flex flex-wrap gap-2">
      <button v-for="action in choices" :key="action" type="button" class="ds-button ds-button-secondary" :disabled="form.processing" :aria-pressed="selected === action" @click="choose(action)">{{ labels[action] }}</button>
    </div>
    <form v-if="selected" class="space-y-3 border-t border-[var(--ds-border)] pt-4" @submit.prevent="submit">
      <label for="transition-evidence" class="block text-sm font-semibold">{{ selected === 'reopen' ? 'Motivo da reabertura' : selected === 'close' ? 'Observação de encerramento (opcional)' : 'Evidência da etapa' }}</label>
      <p v-if="selected === 'verify'" class="text-sm text-[var(--ds-text-muted)]">Confirme a revisão da resolução e documente a conclusão que permite encerrar o dossier.</p>
      <textarea id="transition-evidence" v-model="form.evidence" class="ds-input w-full" rows="4" maxlength="20000" :required="selected !== 'close'" :disabled="form.processing" :aria-invalid="!!form.errors.evidence" aria-describedby="transition-errors" />
      <div id="transition-errors" role="alert"><p v-for="(error, key) in form.errors" :key="key">{{ error }}</p></div>
      <div class="flex flex-wrap gap-2">
        <button class="ds-button ds-button-primary" :disabled="form.processing">{{ form.processing ? 'A registar…' : `Confirmar: ${labels[selected]}` }}</button>
        <button type="button" class="ds-button ds-button-secondary" :disabled="form.processing" @click="selected = ''">Cancelar</button>
      </div>
    </form>
    <details v-if="history.length" class="border-t border-[var(--ds-border)] pt-3">
      <summary class="cursor-pointer py-2 text-sm font-semibold">Histórico do fluxo · {{ history.length }}</summary>
      <ol class="space-y-3 pt-3">
        <li v-for="entry in history" :key="entry.revision" class="border-l-2 border-[var(--ds-border)] pl-3">
          <p class="text-sm font-semibold">{{ labels[entry.action] ?? entry.action }} · {{ entry.actor_name }}</p>
          <p class="text-xs text-[var(--ds-text-muted)]">{{ new Date(entry.at).toLocaleString('pt-PT') }} · Revisão {{ entry.revision }}</p>
          <p v-if="entry.evidence" class="mt-1 whitespace-pre-line text-sm">{{ entry.evidence }}</p>
        </li>
      </ol>
    </details>
  </section>
</template>
