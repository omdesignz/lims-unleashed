<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ArrowLeftIcon, PaperAirplaneIcon, PaperClipIcon, UserIcon } from '@heroicons/vue/24/outline'
import { computed } from 'vue'

defineOptions({ layout: Layout })
const props = defineProps({ receivers: { type: Array, default: () => [] } })
const form = useForm({ receiver_id: '', message: '', attachments: [] })
const selectedReceiver = computed(() => props.receivers.find((receiver) => receiver.id === Number(form.receiver_id) || receiver.id === form.receiver_id))
const selectedFiles = computed(() => Array.from(form.attachments || []))
const handleFiles = (event) => { form.attachments = event.target.files }
const submit = () => form.post(route('messages.store'))
</script>

<template>
  <div class="space-y-5">
    <section class="ds-panel overflow-hidden">
      <header class="flex flex-col gap-4 border-b border-[var(--ds-border)] px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div class="flex items-start gap-3"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200"><PaperAirplaneIcon class="h-5 w-5" /></span><div><p class="ds-kicker">Comunicação interna</p><h1 class="ds-heading mt-1 text-xl sm:text-2xl">Nova mensagem</h1><p class="ds-copy mt-1 text-sm">Envie uma instrução curta e associe evidência quando necessário.</p></div></div>
        <Link :href="route('messages.index')" class="ds-button ds-button-secondary"><ArrowLeftIcon class="h-4 w-4" />Voltar</Link>
      </header>
    </section>

    <form class="grid items-start gap-5 lg:grid-cols-[minmax(0,1.35fr)_minmax(18rem,0.65fr)]" @submit.prevent="submit">
      <section class="ds-panel overflow-hidden">
        <header class="border-b border-[var(--ds-border)] px-5 py-4"><p class="ds-kicker">Conteúdo</p><h2 class="ds-heading mt-1 text-base">Instrução operacional</h2></header>
        <div class="space-y-5 p-5 sm:p-6">
          <div class="ds-field-group"><label for="receiver" class="ds-field-label">Destinatário <span class="ds-field-required">*</span></label><BaseSelect id="receiver" v-model="form.receiver_id" class="ds-field" :aria-invalid="Boolean(form.errors.receiver_id)"><option value="">Seleccione o destinatário</option><option v-for="receiver in receivers" :key="receiver.id" :value="receiver.id">{{ receiver.name }} - {{ receiver.email }}</option></BaseSelect><p v-if="form.errors.receiver_id" class="ds-field-error">{{ form.errors.receiver_id }}</p></div>
          <div class="ds-field-group"><label for="message" class="ds-field-label">Mensagem <span class="ds-field-required">*</span></label><textarea id="message" v-model="form.message" rows="8" class="ds-field resize-y" :aria-invalid="Boolean(form.errors.message)" placeholder="Indique a instrução, contexto e acção esperada." /><p v-if="form.errors.message" class="ds-field-error">{{ form.errors.message }}</p></div>
          <div class="ds-field-group"><label for="attachments" class="ds-field-label">Anexos <span class="font-normal text-[var(--ds-text-soft)]">(opcional)</span></label><FileInput id="attachments" type="file" multiple class="ds-field" accept=".jpg,.jpeg,.png,.pdf,.docx,.mp3,.wav" @change="handleFiles" /><p class="ds-field-help">JPG, PNG, PDF, DOCX, MP3 ou WAV. Maximo de 2 MB por ficheiro.</p><p v-if="form.errors.attachments" class="ds-field-error">{{ form.errors.attachments }}</p></div>
        </div>
        <footer class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:justify-end"><Link :href="route('messages.index')" class="ds-button ds-button-ghost">Cancelar</Link><button type="submit" class="ds-button ds-button-primary" :disabled="form.processing"><PaperAirplaneIcon class="h-4 w-4" />{{ form.processing ? 'A enviar...' : 'Enviar mensagem' }}</button></footer>
      </section>

      <aside class="ds-panel overflow-hidden lg:sticky lg:top-5">
        <header class="border-b border-[var(--ds-border)] px-5 py-4"><p class="ds-kicker">Resumo</p><h2 class="ds-heading mt-1 text-base">Entrega</h2></header>
        <div class="p-5"><div class="flex items-start gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[var(--ds-text-soft)]"><UserIcon class="h-4 w-4" /></span><div class="min-w-0"><p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Destinatário</p><p class="mt-1 truncate text-sm font-bold text-[var(--ds-text)]">{{ selectedReceiver?.name || 'Por seleccionar' }}</p><p class="truncate text-xs font-semibold text-[var(--ds-text-muted)]">{{ selectedReceiver?.email || 'Sem email seleccionado' }}</p></div></div><div class="mt-5 border-t border-[var(--ds-border)] pt-4"><p class="flex items-center gap-2 text-xs font-bold uppercase text-[var(--ds-text-soft)]"><PaperClipIcon class="h-4 w-4" />Ficheiros ({{ selectedFiles.length }})</p><ul v-if="selectedFiles.length" class="mt-3 space-y-2"><li v-for="file in selectedFiles" :key="`${file.name}-${file.size}`" class="truncate text-xs font-semibold text-[var(--ds-text-muted)]">{{ file.name }}</li></ul><p v-else class="ds-copy mt-2 text-xs">Nenhum anexo seleccionado.</p></div></div>
      </aside>
    </form>
  </div>
</template>
