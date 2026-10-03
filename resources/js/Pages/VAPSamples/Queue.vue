<script setup>
import { computed, onUnmounted, ref, watch } from 'vue'
import { Head, Link, useForm, useHttp } from '@inertiajs/vue3'
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue'
import { ArrowRightIcon, BeakerIcon, ChevronLeftIcon, ChevronRightIcon, MagnifyingGlassIcon, PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import Layout from '@/Shared/Layouts/Layout.vue'
import { sampleDate as date } from '@/Utils/samplePresentation'

defineOptions({ layout: Layout })
const props = defineProps({ lab: Object, filters: Object, samples: Object })
const filter = useForm({ search: props.filters.search ?? '', status: props.filters.status ?? '', per_page: props.filters.per_page ?? 25 })
const detail = useHttp({})
const previewOpen = ref(false)
const selected = ref(null)
const previewError = ref('')
let requestVersion = 0
const states = { POR_INICIAR: 'Por iniciar', EN_PROGRESO: 'Em análise', EN_PAUSA: 'Em espera', COMPLETADO: 'Concluídas', CANCELADO: 'Canceladas' }
const tones = { POR_INICIAR: 'received', EN_PROGRESO: 'analysis', EN_PAUSA: 'hold', COMPLETADO: 'complete', CANCELADO: 'hold' }
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
  <div class="workbench-page">
    <Head title="Amostras" />
    <header class="lab-page-head"><div><h1>Cada amostra, no seu lugar.</h1><p>{{ lab.name }} · Da recepção ao próximo passo.</p></div><Link :href="route('vap_samples.index')" class="lab-btn lab-primary"><PlusIcon />Receber amostra</Link></header>
    <nav class="lab-tabs" aria-label="Filtrar estado das amostras"><button type="button" class="lab-tab" :aria-pressed="!filter.status" @click="search('')">Todas</button><button v-for="(label, status) in states" :key="status" type="button" class="lab-tab" :aria-pressed="filter.status === status" @click="search(status)">{{ label }}</button></nav>
    <form class="lab-toolbar" @submit.prevent="search()"><label class="lab-inline-search"><MagnifyingGlassIcon /><input v-model="filter.search" type="search" maxlength="100" aria-label="Pesquisar amostras" placeholder="Pesquisar nome ou código" /></label><button class="lab-btn" type="submit" :disabled="filter.processing">{{ filter.processing ? 'A pesquisar…' : 'Pesquisar' }}</button><button v-if="hasFilters" class="lab-link lab-small" type="button" @click="clearFilters">Limpar filtros</button><div class="lab-spacer"></div><label class="lab-flex lab-small">Por página<select v-model="filter.per_page" class="lab-field" @change="search()"><option :value="25">25</option><option :value="50">50</option><option :value="100">100</option></select></label></form>
    <p v-if="filter.hasErrors" class="lab-field-error" role="alert">{{ Object.values(filter.errors)[0] }}</p>
    <section aria-label="Fila de amostras" :aria-busy="filter.processing">
      <div v-if="samples.data.length" class="lab-table-wrap"><table class="lab-table"><thead><tr><th scope="col">Amostra</th><th scope="col">Cliente</th><th scope="col">Estado</th><th scope="col">Recepção</th><th scope="col">Retenção até</th><th scope="col"><span class="sr-only">Consulta</span></th></tr></thead><tbody><tr v-for="sample in samples.data" :key="sample.id"><td><button type="button" class="lab-link" @click="preview(sample)">{{ sample.code || 'Código por atribuir' }}</button><small>{{ sample.name }}</small></td><td>{{ sample.customer?.name || 'Não associado' }}<small>{{ sample.sample_type || 'Tipo por definir' }}</small></td><td><span class="lab-pill" :data-tone="tones[sample.status]"><span class="lab-dot"></span>{{ states[sample.status] || sample.status }}</span></td><td>{{ date(sample.received_at) }}</td><td>{{ date(sample.retention_due_at) }}</td><td><button type="button" class="lab-link" :aria-label="`Consultar ${sample.name}`" @click="preview(sample)">Consultar <ArrowRightIcon /></button></td></tr></tbody></table></div>
      <div v-else class="lab-empty"><BeakerIcon class="lab-empty-icon" /><strong>{{ hasFilters ? 'Nenhuma amostra encontrada' : 'Uma bancada pronta para começar' }}</strong>{{ hasFilters ? 'Experimente outro nome, código ou estado.' : 'As amostras recebidas neste laboratório aparecerão aqui.' }}<div v-if="hasFilters" class="lab-empty-action"><button type="button" class="lab-btn" @click="clearFilters">Limpar filtros</button></div></div>
      <nav class="lab-pagination" aria-label="Paginação de amostras"><p role="status">{{ samples.meta.from ?? 0 }}–{{ samples.meta.to ?? 0 }} de {{ samples.meta.total }} amostras</p><div class="lab-flex"><Link v-if="samples.links.prev" :href="samples.links.prev" class="lab-btn lab-icon-btn" aria-label="Página anterior" preserve-scroll><ChevronLeftIcon /></Link><button v-else type="button" class="lab-btn lab-icon-btn" aria-label="Página anterior" disabled><ChevronLeftIcon /></button><span>Página {{ samples.meta.current_page }} de {{ samples.meta.last_page }}</span><Link v-if="samples.links.next" :href="samples.links.next" class="lab-btn lab-icon-btn" aria-label="Página seguinte" preserve-scroll><ChevronRightIcon /></Link><button v-else type="button" class="lab-btn lab-icon-btn" aria-label="Página seguinte" disabled><ChevronRightIcon /></button></div></nav>
    </section>
    <Dialog :open="previewOpen" class="lab-quick-view" @close="closePreview">
      <div class="lab-quick-backdrop" aria-hidden="true"></div>
      <div class="lab-quick-position"><DialogPanel class="lab-quick-panel"><header><div><p>Consulta de amostra · {{ lab.name }}</p><DialogTitle>{{ selected?.code || 'Detalhes da amostra' }}</DialogTitle></div><button type="button" aria-label="Fechar consulta" @click="closePreview"><XMarkIcon /></button></header>
        <p v-if="previewError" role="alert" class="lab-quick-message">{{ previewError }}</p>
        <div v-else-if="!selected" class="lab-quick-message" role="status" aria-live="polite">A carregar a amostra…</div>
        <template v-else><h3>{{ selected.name }}</h3><dl><div><dt>Estado</dt><dd>{{ states[selected.status] || selected.status }}</dd></div><div><dt>Cliente</dt><dd>{{ selected.customer?.name || 'Não associado' }}</dd></div><div><dt>Recepção</dt><dd>{{ date(selected.received_at) }}</dd></div><div><dt>Retenção até</dt><dd>{{ date(selected.retention_due_at) }}</dd></div><div><dt>Início da análise</dt><dd>{{ date(selected.details?.analysis_started_at) }}</dd></div><div><dt>Conclusão da análise</dt><dd>{{ date(selected.details?.analysis_completed_at) }}</dd></div></dl><section><h3>Serviços solicitados</h3><p>{{ requestedServices }}</p></section><section><h3>Observações de recepção</h3><p>{{ selected.details?.observations || 'Sem observações registadas.' }}</p></section><Link :href="route('vap_samples.show', selected.id)" class="lab-quick-record-link">Abrir ficha completa<ArrowRightIcon /></Link></template>
      </DialogPanel></div>
    </Dialog>
  </div>
</template>
