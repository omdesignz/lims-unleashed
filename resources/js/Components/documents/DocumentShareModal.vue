<script setup>
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseTextarea from '@/Components/base/BaseTextarea.vue'
import { useForm } from '@inertiajs/vue3'
import { FileText as DocumentTextIcon, Mail as EnvelopeIcon, Send as PaperAirplaneIcon, X as XMarkIcon } from '@lucide/vue'
import { computed, watch } from 'vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  documentType: { type: String, required: true },
  documentId: { type: Number, required: true },
  documentLabel: { type: String, required: true },
  documentNumber: { type: String, default: '' },
  defaultRecipients: { type: Array, default: () => [] },
})

const emit = defineEmits(['close'])

const form = useForm({
  document_type: props.documentType,
  document_id: props.documentId,
  recipients_text: '',
  cc_text: '',
  recipients: [],
  cc: [],
  subject: '',
  message: '',
})

const splitEmails = (value) => [...new Set(value
  .split(/[;,\s]+/)
  .map((email) => email.trim())
  .filter(Boolean))]

const attachmentName = computed(() => `${props.documentLabel} ${props.documentNumber}`.trim())

const resetForm = () => {
  const recipients = props.defaultRecipients.filter(Boolean)
  form.defaults({
    document_type: props.documentType,
    document_id: props.documentId,
    recipients_text: recipients.join(', '),
    cc_text: '',
    recipients,
    cc: [],
    subject: `${props.documentLabel} ${props.documentNumber}`.trim(),
    message: `Segue em anexo ${attachmentName.value} para consulta.`,
  })
  form.reset()
  form.clearErrors()
}

watch(() => props.open, (open) => {
  if (open) resetForm()
})

const submit = () => {
  form.recipients = splitEmails(form.recipients_text)
  form.cc = splitEmails(form.cc_text)

  form.transform(({ recipients_text, cc_text, ...data }) => data).post(route('documents.share'), {
    preserveScroll: true,
    onSuccess: () => emit('close'),
  })
}
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[80] overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="document-share-title">
      <button class="fixed inset-0 bg-slate-950/45 backdrop-blur-[2px]" type="button" aria-label="Fechar" @click="emit('close')" />

      <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
        <form class="w-full max-w-2xl overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] shadow-2xl" @submit.prevent="submit">
          <header class="flex items-start justify-between gap-4 border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
            <div class="flex min-w-0 items-start gap-3">
              <span class="grid h-10 w-10 shrink-0 place-items-center rounded-md bg-[rgb(var(--primary-50-rgb))] text-[rgb(var(--primary-700-rgb))] dark:bg-[rgb(var(--primary-500-rgb)/0.12)]">
                <EnvelopeIcon class="h-5 w-5" />
              </span>
              <div class="min-w-0">
                <p class="ds-kicker">Entrega de documento</p>
                <h2 id="document-share-title" class="ds-heading mt-1 text-lg">Enviar {{ attachmentName }}</h2>
                <p class="ds-copy mt-1 text-sm">O PDF é gerado no servidor e o envio fica registado para auditoria.</p>
              </div>
            </div>
            <button type="button" class="ds-icon-button" title="Fechar" @click="emit('close')"><XMarkIcon class="h-5 w-5" /></button>
          </header>

          <div class="space-y-4 p-5 sm:p-6">
            <div class="rounded-md border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
              <div class="flex items-center gap-3">
                <DocumentTextIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />
                <div>
                  <p class="text-sm font-bold text-[var(--ds-text)]">{{ attachmentName }}.pdf</p>
                  <p class="text-xs font-semibold text-[var(--ds-text-muted)]">Anexo PDF gerado no momento do envio</p>
                </div>
              </div>
            </div>

            <BaseInput
              id="share-recipients"
              v-model="form.recipients_text"
              label="Destinatários"
              placeholder="email@cliente.ao, financeiro@cliente.ao"
              hint="Separe vários endereços por vírgula."
              :error="form.errors.recipients || form.errors['recipients.0']"
              autofocus
            />

            <BaseInput
              id="share-cc"
              v-model="form.cc_text"
              label="Cc (opcional)"
              placeholder="supervisor@laboratorio.ao"
              :error="form.errors.cc"
            />

            <BaseInput
              id="share-subject"
              v-model="form.subject"
              label="Assunto"
              :error="form.errors.subject"
              maxlength="255"
            />

            <BaseTextarea
              id="share-message"
              v-model="form.message"
              label="Mensagem"
              :error="form.errors.message"
              maxlength="5000"
            />
          </div>

          <footer class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
            <button type="button" class="ds-button ds-button-secondary" @click="emit('close')">Cancelar</button>
            <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing">
              <PaperAirplaneIcon class="h-4 w-4" />
              {{ form.processing ? 'A agendar...' : 'Enviar PDF' }}
            </button>
          </footer>
        </form>
      </div>
    </div>
  </Teleport>
</template>
