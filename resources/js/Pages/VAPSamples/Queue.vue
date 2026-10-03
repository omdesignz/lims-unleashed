<script setup>
import { computed, onUnmounted, ref, watch } from 'vue'
import { Head, Link, useForm, useHttp } from '@inertiajs/vue3'
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue'
import { ArrowRight as ArrowRightIcon, ChevronLeft as ChevronLeftIcon, ChevronRight as ChevronRightIcon, X as XMarkIcon } from '@lucide/vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { sampleDate as date, sampleStatusLabels, sampleTypeLabels } from '@/Utils/samplePresentation'
import PageHeader from '@/Components/plano/PageHeader.vue'
import StateCells from '@/Components/plano/StateCells.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'

defineOptions({ layout: Layout })
const props = defineProps({ lab: Object, filters: Object, counts: { type: Object, default: () => ({}) }, samples: Object })
const filter = useForm({ search: props.filters.search ?? '', status: props.filters.status ?? '', per_page: props.filters.per_page ?? 25 })
const detail = useHttp({})
const previewOpen = ref(false)
const selected = ref(null)
const previewError = ref('')
let requestVersion = 0
const states = { POR_INICIAR: 'Por iniciar', EN_PROGRESO: 'Em análise', EN_PAUSA: 'Em espera', COMPLETADO: 'Concluídas', CANCELADO: 'Canceladas' }
const tones = { POR_INICIAR: 'neutral', EN_PROGRESO: 'run', EN_PAUSA: 'wait', COMPLETADO: 'ok', CANCELADO: 'done' }
const totalCount = computed(() => Object.values(props.counts).reduce((sum, value) => sum + Number(value || 0), 0))
const cells = computed(() => [
  { key: '', label: 'Todas', value: totalCount.value },
  ...Object.entries(states).map(([key, label]) => ({ key, label, value: props.counts[key] ?? 0, tone: key === 'EN_PAUSA' && props.counts[key] ? 'bad' : undefined })),
])
const lede = computed(() => {
  const open = (props.counts.POR_INICIAR ?? 0) + (props.counts.EN_PROGRESO ?? 0) + (props.counts.EN_PAUSA ?? 0)
  const held = props.counts.EN_PAUSA ?? 0
  return `${open} amostras em curso no ${props.lab.name}.${held ? ` ${held} em espera.` : ''}`
})
const hasFilters = computed(() => Boolean(filter.search || filter.status))
const requestedServices = computed(() => {
  const value = selected.value?.details?.requested_services
  if (Array.isArray(value)) return value.filter(item => typeof item === 'string').join(', ') || 'Não registados'
  return typeof value === 'string' ? value : 'Não registados'
})

watch(() => props.filters, value => {
  filter.search = value.search ?? ''
  filter.status = value.status ?? ''
  filter.per_page = value.per_page ?? 25
})

function search(status = filter.status) {
  filter.status = status
  filter.get(route('vap_samples.queue'), { preserveState: true, preserveScroll: true, replace: true })
}

function clearFilters() {
  filter.search = ''
  search('')
}

async function preview(sample) {
  const version = ++requestVersion
  detail.cancel()
  selected.value = null
  previewError.value = ''
  previewOpen.value = true
  try {
    const response = await detail.get(sample.details_url)
    if (version === requestVersion) selected.value = response.data
  } catch {
    if (version === requestVersion && previewOpen.value) previewError.value = 'Não foi possível abrir esta amostra. Pode ter sido removida ou o seu acesso alterado.'
  }
}

function closePreview() {
  requestVersion++
  detail.cancel()
  previewOpen.value = false
}

onUnmounted(() => { requestVersion++; detail.cancel() })
</script>

<template>
  <div class="pl-page" data-template="queue">
    <Head title="Fila de amostras" />
    <PageHeader :crumbs="[{ title: 'Amostras' }, { title: 'Fila' }]" title="Fila de amostras" :lede="lede" />

    <StateCells class="mb-10" :items="cells" :model-value="filter.status" label="Filtrar estado das amostras" @update:model-value="search($event)" />

    <form class="pl-filter" @submit.prevent="search()">
      <label for="queue-search" class="pl-filter-prompt">Filtro://</label>
      <BaseInput id="queue-search" v-model="filter.search" type="search" data-bare class="pl-filter-input" maxlength="100" placeholder="código, nome da amostra" />
      <button class="ds-button ds-button-quiet" type="submit" :disabled="filter.processing">{{ filter.processing ? 'A procurar…' : 'Procurar' }}</button>
      <button v-if="hasFilters" class="ds-chip" type="button" @click="clearFilters">Limpar filtros <XMarkIcon class="h-3.5 w-3.5" aria-hidden="true" /></button>
      <div class="ml-auto flex items-center gap-2"><span id="queue-per-page" class="pl-k pl-muted">Por página</span><div class="w-24"><BaseSelect :model-value="filter.per_page" :options="[25, 50, 100]" aria-labelledby="queue-per-page" @update:model-value="filter.per_page = $event; search()" /></div></div>
    </form>
    <p v-if="filter.hasErrors" class="ds-field-error mb-3" role="alert">{{ Object.values(filter.errors)[0] }}</p>

    <section class="pl-panel" aria-label="Fila de amostras" :aria-busy="filter.processing">
      <DataTable v-if="samples.data.length">
        <thead><tr><th scope="col">Código</th><th scope="col">Cliente</th><th scope="col">Amostra</th><th scope="col">Estado</th><th scope="col" class="text-right">Recepção</th><th scope="col" class="text-right">Retenção até</th><th scope="col"><span class="sr-only">Consulta</span></th></tr></thead>
        <tbody>
          <tr v-for="sample in samples.data" :key="sample.id" :data-selected="selected?.id === sample.id && previewOpen">
            <td><button type="button" class="pl-num whitespace-nowrap text-left font-medium hover:text-[var(--pl-accent-text)]" @click="preview(sample)">{{ sample.code || 'Por atribuir' }}</button></td>
            <td>{{ sample.customer?.name || 'Não associado' }}<span class="text-[var(--pl-muted)]"> · {{ sampleTypeLabels[sample.sample_type] || sample.sample_type || 'Tipo por definir' }}</span></td>
            <td class="max-w-[14rem] truncate">{{ sample.name }}</td>
            <td><StatusChip :tone="tones[sample.status]">{{ sampleStatusLabels[sample.status] || sample.status }}</StatusChip></td>
            <td class="pl-num text-right">{{ date(sample.received_at) }}</td>
            <td class="pl-num text-right">{{ date(sample.retention_due_at) }}</td>
            <td class="text-right"><button type="button" class="ds-icon-button pl-row-go" :aria-label="`Consultar ${sample.name}`" @click="preview(sample)"><ChevronRightIcon aria-hidden="true" /></button></td>
          </tr>
        </tbody>
      </DataTable>
      <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
        <span class="pl-k">{{ hasFilters ? 'Nenhuma amostra encontrada' : 'Uma bancada pronta para começar' }}</span>
        <p class="text-sm text-[var(--pl-muted)]">{{ hasFilters ? 'Experimente outro nome, código ou estado.' : 'As amostras recebidas neste laboratório aparecerão aqui.' }}</p>
        <button v-if="hasFilters" type="button" class="ds-button ds-button-quiet mt-2" @click="clearFilters">Limpar filtros</button>
      </div>
    </section>

    <nav class="mt-6 flex flex-wrap items-center justify-between gap-4" aria-label="Paginação de amostras">
      <p class="pl-k pl-muted" role="status">{{ samples.meta.from ?? 0 }}–{{ samples.meta.to ?? 0 }} de {{ samples.meta.total }} · {{ filter.per_page }} por página</p>
      <div class="pl-pager">
        <Link v-if="samples.links.prev" :href="samples.links.prev" aria-label="Página anterior" preserve-scroll><ChevronLeftIcon aria-hidden="true" />Ant</Link>
        <span v-else aria-disabled="true"><ChevronLeftIcon aria-hidden="true" />Ant</span>
        <span aria-current="page">{{ samples.meta.current_page }} / {{ samples.meta.last_page }}</span>
        <Link v-if="samples.links.next" :href="samples.links.next" aria-label="Página seguinte" preserve-scroll>Seg<ChevronRightIcon aria-hidden="true" /></Link>
        <span v-else aria-disabled="true">Seg<ChevronRightIcon aria-hidden="true" /></span>
      </div>
    </nav>

    <Dialog :open="previewOpen" class="relative z-50" @close="closePreview">
      <div class="ds-modal-backdrop fixed inset-0" aria-hidden="true"></div>
      <div class="fixed inset-0 flex justify-end">
        <DialogPanel class="pl-slideover">
          <header class="pl-panel-head h-auto py-4">
            <div><p class="pl-k pl-muted">Consulta · {{ lab.name }}</p><DialogTitle class="pl-d3 mt-2">{{ selected?.code || 'Detalhes da amostra' }}</DialogTitle></div>
            <button type="button" class="ds-icon-button" aria-label="Fechar consulta" @click="closePreview"><XMarkIcon aria-hidden="true" /></button>
          </header>
          <p v-if="previewError" role="alert" class="pl-banner pl-banner-bad m-4">{{ previewError }}</p>
          <div v-else-if="!selected" class="grid gap-3 p-4" role="status" aria-live="polite"><span class="sr-only">A carregar a amostra…</span><span class="pl-skel h-5 w-2/3" /><span class="pl-skel h-4 w-full" /><span class="pl-skel h-4 w-5/6" /></div>
          <template v-else>
            <p class="px-4 pt-4 text-[15px] font-semibold">{{ selected.name }}</p>
            <dl class="pl-facts mt-3 border-y border-[var(--pl-line)]">
              <div class="pl-fact"><dt>Estado</dt><dd><StatusChip :tone="tones[selected.status]">{{ sampleStatusLabels[selected.status] || selected.status }}</StatusChip></dd></div>
              <div class="pl-fact"><dt>Cliente</dt><dd>{{ selected.customer?.name || 'Não associado' }}</dd></div>
              <div class="pl-fact"><dt>Recepção</dt><dd class="pl-num">{{ date(selected.received_at) }}</dd></div>
              <div class="pl-fact"><dt>Retenção até</dt><dd class="pl-num">{{ date(selected.retention_due_at) }}</dd></div>
              <div class="pl-fact"><dt>Início da análise</dt><dd class="pl-num">{{ date(selected.details?.analysis_started_at) }}</dd></div>
              <div class="pl-fact"><dt>Conclusão</dt><dd class="pl-num">{{ date(selected.details?.analysis_completed_at) }}</dd></div>
            </dl>
            <section class="grid gap-2 p-4"><h3 class="pl-k pl-muted">Serviços solicitados</h3><p class="text-sm">{{ requestedServices }}</p></section>
            <section class="grid gap-2 px-4 pb-4"><h3 class="pl-k pl-muted">Observações de recepção</h3><p class="text-sm">{{ selected.details?.observations || 'Sem observações registadas.' }}</p></section>
            <div class="mt-auto border-t border-[var(--pl-line)] p-4"><Link :href="route('vap_samples.show', selected.id)" class="ds-button ds-button-primary w-full justify-between">Abrir ficha completa<ArrowRightIcon aria-hidden="true" /></Link></div>
          </template>
        </DialogPanel>
      </div>
    </Dialog>
  </div>
</template>
