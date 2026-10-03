<script setup>
import Pagination from '@/Components/pagination.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { Link, router } from '@inertiajs/vue3'
import { MessagesSquare as ChatBubbleLeftRightIcon, Eye as EyeIcon, Search as MagnifyingGlassIcon, Send as PaperAirplaneIcon, Paperclip as PaperClipIcon, X as XMarkIcon } from '@lucide/vue'
import { computed, ref } from 'vue'

defineOptions({ layout: Layout })

const props = defineProps({
  record: { type: Object, required: true },
  query: { type: Object, default: () => ({}) },
})

const search = ref(props.query.search || '')
const attachmentCount = computed(() => props.record.data.reduce((total, message) => total + (message.attachments?.length || 0), 0))

const applySearch = () => router.get(route('messages.index'), { search: search.value || undefined }, { preserveState: true, replace: true })
const clearSearch = () => { search.value = ''; applySearch() }
</script>

<template>
  <div class="space-y-5">
    <section class="ds-panel overflow-hidden">
      <header class="flex flex-col gap-4 border-b border-[var(--ds-border)] px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div class="flex min-w-0 items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200"><ChatBubbleLeftRightIcon class="h-5 w-5" /></span>
          <div><p class="ds-kicker">Comunicação interna</p><h1 class="ds-heading mt-1 text-xl sm:text-2xl">Mensagens da equipa</h1><p class="ds-copy mt-1 text-sm">Instruções operacionais e respectivo histórico de comunicação.</p></div>
        </div>
        <Link :href="route('messages.create')" class="ds-button ds-button-primary"><PaperAirplaneIcon class="h-4 w-4" />Nova mensagem</Link>
      </header>
      <div class="grid divide-y divide-[var(--ds-border)] sm:grid-cols-3 sm:divide-x sm:divide-y-0">
        <div class="p-4"><p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Total</p><p class="mt-1 text-xl font-black tabular-nums text-[var(--ds-text)]">{{ record.meta?.total ?? record.data.length }}</p></div>
        <div class="p-4"><p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Nesta página</p><p class="mt-1 text-xl font-black tabular-nums text-[var(--ds-text)]">{{ record.data.length }}</p></div>
        <div class="p-4"><p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Anexos visíveis</p><p class="mt-1 text-xl font-black tabular-nums text-[var(--ds-text)]">{{ attachmentCount }}</p></div>
      </div>
    </section>

    <section class="ds-panel overflow-hidden">
      <form class="flex flex-col gap-3 border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4 sm:flex-row sm:items-center" @submit.prevent="applySearch">
        <div class="relative min-w-0 flex-1"><MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" /><BaseInput v-model="search" type="search" class="ds-field pl-9" placeholder="Pesquisar no conteúdo das mensagens" /></div>
        <button v-if="search" type="button" class="ds-button ds-button-ghost" @click="clearSearch"><XMarkIcon class="h-4 w-4" />Limpar</button>
        <button type="submit" class="ds-button ds-button-secondary"><MagnifyingGlassIcon class="h-4 w-4" />Pesquisar</button>
      </form>

      <div class="overflow-x-auto">
        <DataTable class="min-w-full divide-y divide-[var(--ds-border)] text-left">
          <thead class="bg-[var(--ds-panel-subtle)]"><tr><th class="px-5 py-3 text-xs font-bold uppercase text-[var(--ds-text-soft)]">Fluxo</th><th class="px-4 py-3 text-xs font-bold uppercase text-[var(--ds-text-soft)]">Mensagem</th><th class="px-4 py-3 text-xs font-bold uppercase text-[var(--ds-text-soft)]">Anexos</th><th class="px-5 py-3 text-right text-xs font-bold uppercase text-[var(--ds-text-soft)]">Acção</th></tr></thead>
          <tbody v-if="record.data.length" class="divide-y divide-[var(--ds-border)]">
            <tr v-for="message in record.data" :key="message.id" class="hover:bg-[var(--ds-panel-subtle)]">
              <td class="whitespace-nowrap px-5 py-4"><p class="text-sm font-bold text-[var(--ds-text)]">{{ message.sender || 'Sistema' }}</p><p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">para {{ message.receiver || 'Não atribuido' }}</p></td>
              <td class="max-w-2xl px-4 py-4"><p class="line-clamp-2 text-sm leading-6 text-[var(--ds-text)]">{{ message.message }}</p></td>
              <td class="px-4 py-4"><span class="ds-badge bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)] ring-1 ring-inset ring-[var(--ds-border)]"><PaperClipIcon class="h-3.5 w-3.5" />{{ message.attachments?.length || 0 }}</span></td>
              <td class="px-5 py-4 text-right"><Link :href="route('messages.edit', message.id)" class="ds-icon-button" title="Abrir mensagem"><EyeIcon class="h-4 w-4" /></Link></td>
            </tr>
          </tbody>
        </DataTable>
      </div>
      <div v-if="!record.data.length" class="px-5 py-14 text-center"><ChatBubbleLeftRightIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" /><p class="mt-3 text-sm font-bold text-[var(--ds-text)]">Sem mensagens internas</p><p class="ds-copy mt-1 text-sm">As instruções trocadas pela equipa serão apresentadas aqui.</p></div>
      <div v-if="record.data.length && record.meta" class="border-t border-[var(--ds-border)]"><Pagination v-bind="record.meta" /></div>
    </section>
  </div>
</template>
