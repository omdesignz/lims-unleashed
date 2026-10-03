import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

export function useConsumptionReversal({ canReverse, reverseUrl }) {
  const form = useForm({})
  const pending = ref(null)
  const submitting = ref(false)
  const processing = computed(() => submitting.value || form.processing)
  const error = ref('')

  function open(record) {
    if (!canReverse() || processing.value || pending.value || record.reversal) return false
    pending.value = Object.freeze({
      id: record.id, reagent_name: record.reagent_name, quantity_used: record.quantity_used,
      warehouse: Object.freeze({ name: record.warehouse?.name }),
    })
    error.value = ''
    return true
  }

  function close() {
    if (processing.value) return false
    pending.value = null
    error.value = ''
    return true
  }

  function confirm() {
    if (!canReverse() || processing.value || !pending.value) return false
    const id = pending.value.id
    submitting.value = true
    error.value = ''
    try {
      form.post(reverseUrl(id), {
        preserveState: true, preserveScroll: true,
        onSuccess: () => { pending.value = null },
        onError: errors => { error.value = Object.values(errors).flat().join(' ') || 'Não foi possível reverter o consumo.' },
        onHttpException: response => {
          error.value = [403, 404].includes(response.status)
            ? 'O consumo ou a autorização já não está disponível. Actualize a página.'
            : 'Não foi possível confirmar a reposição. Actualize a página antes de tentar novamente.'
          return false
        },
        onNetworkError: () => {
          error.value = 'Falha de ligação. Actualize a página para confirmar se o consumo foi revertido.'
          return false
        },
        onCancel: () => { error.value = 'Operação interrompida. Actualize a página para confirmar o estado do consumo.' },
        onFinish: () => { submitting.value = false },
      })
    } catch {
      submitting.value = false
      error.value = 'Não foi possível iniciar a reposição. Tente novamente.'
      return false
    }
    return true
  }

  return { pending, processing, error, open, close, confirm }
}
