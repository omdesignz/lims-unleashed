<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { Check as CheckIcon, Paperclip as PaperClipIcon } from '@lucide/vue'
import { computed } from 'vue'

defineOptions({ layout: Layout })

const props = defineProps({
  record: { type: Object, required: true },
  attachments: { type: Array, default: () => [] },
})

const message = computed(() => props.record?.data || {})
const form = useForm({ message: message.value.message || '', attachments: [] })
const replacementFiles = computed(() => Array.from(form.attachments || []))
const handleFiles = (event) => { form.attachments = event.target.files }
const submit = () => form.transform((data) => ({ ...data, _method: 'put' })).post(route('messages.update', message.value.id))
</script>

<template>
  <div class="pl-page space-y-5">
    <PageHeader :trail="[{ title: 'Mensagens', url: route('messages.index') }, { title: 'Rever mensagem' }]" title="Rever mensagem" lede="Actualize o conteúdo ou substitua o conjunto de anexos." />

    <form class="grid items-start gap-5 lg:grid-cols-[minmax(0,1.35fr)_minmax(18rem,0.65fr)]" @submit.prevent="submit">
      <section class="ds-panel overflow-hidden">
        <header class="border-b border-[var(--ds-border)] px-5 py-4"><p class="ds-kicker">Edição controlada</p><h2 class="ds-heading mt-1 text-base">Conteúdo da mensagem</h2></header>
        <div class="space-y-5 p-5 sm:p-6">
          <div class="grid gap-4 sm:grid-cols-2"><div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4"><p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Emissor</p><p class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ message.sender || 'Sistema' }}</p></div><div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4"><p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Destinatário</p><p class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ message.receiver || 'Não atribuido' }}</p></div></div>
          <div class="ds-field-group"><label for="message" class="ds-field-label">Mensagem <span class="ds-field-required">*</span></label><textarea id="message" v-model="form.message" rows="9" class="ds-field resize-y" :aria-invalid="Boolean(form.errors.message)" /><p v-if="form.errors.message" class="ds-field-error">{{ form.errors.message }}</p></div>
          <div class="ds-field-group"><label for="attachments" class="ds-field-label">Substituir anexos <span class="font-normal text-[var(--ds-text-soft)]">(opcional)</span></label><FileInput id="attachments" type="file" multiple class="ds-field" accept=".jpg,.jpeg,.png,.pdf,.docx,.mp3,.wav" @change="handleFiles" /><p class="ds-field-help">Ao seleccionar novos ficheiros, o conjunto actual será substituido.</p><p v-if="form.errors.attachments" class="ds-field-error">{{ form.errors.attachments }}</p></div>
        </div>
        <footer class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:justify-end"><Link :href="route('messages.index')" class="ds-button ds-button-ghost">Cancelar</Link><button type="submit" class="ds-button ds-button-primary" :disabled="form.processing"><CheckIcon class="h-4 w-4" />{{ form.processing ? 'A guardar...' : 'Guardar alterações' }}</button></footer>
      </section>

      <aside class="ds-panel overflow-hidden lg:sticky lg:top-5">
        <header class="border-b border-[var(--ds-border)] px-5 py-4"><p class="ds-kicker">Evidência</p><h2 class="ds-heading mt-1 text-base">Anexos associados</h2></header>
        <div class="p-5">
          <div v-if="attachments.length" class="space-y-2"><div v-for="attachment in attachments" :key="attachment.id" class="flex items-center gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-3"><PaperClipIcon class="h-4 w-4 shrink-0 text-[var(--ds-text-soft)]" /><div class="min-w-0"><p class="truncate text-xs font-bold text-[var(--ds-text)]">{{ attachment.file_type }}</p><p class="mt-0.5 truncate text-xs font-semibold text-[var(--ds-text-muted)]">{{ attachment.file_path }}</p></div></div></div>
          <p v-else class="ds-copy text-sm">A mensagem não possui anexos.</p>
          <div v-if="replacementFiles.length" class="mt-5 border-t border-[var(--ds-border)] pt-4"><p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Nova selecção</p><ul class="mt-3 space-y-2"><li v-for="file in replacementFiles" :key="`${file.name}-${file.size}`" class="truncate text-xs font-semibold text-[var(--ds-text-muted)]">{{ file.name }}</li></ul></div>
        </div>
      </aside>
    </form>
  </div>
</template>
