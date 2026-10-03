import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

export function useRecordArchive({ destroyUrl, restoreUrl, onSuccess = () => {} }) {
  const form = useForm({ recordIds: [] })
  const submitting = ref(false)
  const processing = computed(() => submitting.value || form.processing)
  const message = ref('')
  const failed = ref(false)

  function fail(text) {
    failed.value = true
    message.value = text
  }

  function submit(operation, recordIds) {
    if (processing.value || !['delete', 'restore'].includes(operation) || !recordIds?.length) return false

    const resolveUrl = operation === 'delete' ? destroyUrl : restoreUrl
    if (!resolveUrl) return false

    form.recordIds = [...recordIds]
    form.clearErrors()
    failed.value = false
    message.value = ''
    submitting.value = true

    try {
      form.submit(operation === 'delete' ? 'delete' : 'patch', resolveUrl([...form.recordIds]), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          message.value = 'Operação concluída.'
          form.reset()
          onSuccess()
        },
        onError: errors => fail(Object.values(errors).flat().join(' ') || 'Não foi possível concluir a operação.'),
        onHttpException: response => {
          fail([403, 404].includes(response.status)
            ? 'O registo ou a autorização já não está disponível. Actualize a lista.'
            : 'Não foi possível confirmar a operação. Actualize a lista antes de tentar novamente.')
          return false
        },
        onNetworkError: () => {
          fail('Falha de ligação. Não foi possível confirmar a operação. Actualize a lista antes de tentar novamente.')
          return false
        },
        onCancel: () => fail('Operação interrompida. Actualize a lista para confirmar o estado dos registos.'),
        onFinish: () => { submitting.value = false },
      })
    } catch (error) {
      submitting.value = false
      fail('Não foi possível iniciar a operação. Actualize a lista antes de tentar novamente.')
      return false
    }

    return true
  }

  return { form, processing, message, failed, submit }
}
