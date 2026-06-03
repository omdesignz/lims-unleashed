<template>
  <div class="space-y-6" :class="commercialDocumentThemeClasses">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-700">{{ labels.eyebrow }}</p>
          <h1 class="mt-2 text-3xl font-semibold text-slate-900">{{ labels.title }}</h1>
          <p class="mt-2 max-w-3xl text-sm text-slate-600">{{ labels.subtitle }}</p>
        </div>

        <div class="flex flex-wrap gap-3">
          <button
            type="button"
            class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
            @click="exportCollections"
          >
            {{ labels.export }}
          </button>
          <Link
            :href="route('portal.requests.index', { new: 1, request_type: 'collection_request' })"
            class="rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
          >
            {{ labels.newRequest }}
          </Link>
        </div>
      </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
      <article v-for="card in statCards" :key="card.key" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-slate-500">{{ card.label }}</p>
        <p class="mt-3 text-3xl font-semibold text-slate-900">{{ card.value }}</p>
      </article>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="grid gap-4 lg:grid-cols-[minmax(0,1.5fr)_repeat(3,minmax(0,1fr))]">
        <label class="block">
          <span class="mb-2 block text-sm font-medium text-slate-700">{{ labels.search }}</span>
          <input
            v-model="filters.search"
            type="search"
            class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100"
            :placeholder="labels.searchPlaceholder"
          >
        </label>

        <label class="block">
          <span class="mb-2 block text-sm font-medium text-slate-700">{{ labels.status }}</span>
          <select v-model="filters.status_filter" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">
            <option value="">{{ labels.allStatuses }}</option>
            <option value="certificate_ready">{{ labels.certificateReady }}</option>
            <option value="analysis_pending">{{ labels.analysisPending }}</option>
            <option value="in_progress">{{ labels.inProgress }}</option>
          </select>
        </label>

        <label class="block">
          <span class="mb-2 block text-sm font-medium text-slate-700">{{ labels.collectionType }}</span>
          <select v-model="filters.type_filter" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">
            <option value="">{{ labels.allTypes }}</option>
            <option value="direct">{{ labels.direct }}</option>
            <option value="programmed">{{ labels.programmed }}</option>
          </select>
        </label>

        <label class="block">
          <span class="mb-2 block text-sm font-medium text-slate-700">{{ labels.period }}</span>
          <select v-model="filters.date_filter" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100">
            <option value="all">{{ labels.allTime }}</option>
            <option value="last_week">{{ labels.lastWeek }}</option>
            <option value="last_month">{{ labels.lastMonth }}</option>
            <option value="last_quarter">{{ labels.lastQuarter }}</option>
          </select>
        </label>
      </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white shadow-sm">
      <header class="flex flex-col gap-2 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-lg font-semibold text-slate-900">{{ labels.tableTitle }}</h2>
          <p class="text-sm text-slate-500">{{ labels.tableSubtitle }}</p>
        </div>
        <p class="text-sm text-slate-500">
          {{ paginationLabel }}
        </p>
      </header>

      <div v-if="!collections.length" class="px-6 py-16 text-center">
        <p class="text-lg font-semibold text-slate-900">{{ labels.emptyTitle }}</p>
        <p class="mt-2 text-sm text-slate-500">{{ labels.emptyDescription }}</p>
      </div>

      <div v-else class="divide-y divide-slate-200">
        <article v-for="collection in collections" :key="collection.id" class="px-6 py-5">
          <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
            <div class="space-y-4">
              <div class="flex flex-wrap items-center gap-3">
                <h3 class="text-lg font-semibold text-slate-900">
                  {{ collection.cl || labels.unavailableCode }}
                </h3>
                <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="trackingClasses(collection.tracking?.status)">
                  {{ collection.tracking?.label || labels.pending }}
                </span>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                  {{ collectionTypeLabel(collection.type) }}
                </span>
              </div>

              <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                  <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">{{ labels.product }}</p>
                  <p class="mt-1 text-sm font-medium text-slate-900">{{ collection.product || '-' }}</p>
                  <p class="mt-1 text-xs text-slate-500">{{ labels.brand }}: {{ collection.comercial_brand || '-' }}</p>
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">{{ labels.lot }}</p>
                  <p class="mt-1 text-sm font-medium text-slate-900">{{ collection.lot || '-' }}</p>
                  <p class="mt-1 text-xs text-slate-500">{{ labels.quantity }}: {{ collection.qty || '0' }}</p>
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">{{ labels.collectionDate }}</p>
                  <p class="mt-1 text-sm font-medium text-slate-900">{{ formatDate(collection.collection_date) }}</p>
                  <p class="mt-1 text-xs text-slate-500">{{ labels.samples }}: {{ collection.tracking?.total_samples ?? 0 }}</p>
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">{{ labels.analysisStatus }}</p>
                  <p class="mt-1 text-sm font-medium text-slate-900">
                    {{ labels.completed }}: {{ collection.tracking?.completed_analysis ?? 0 }}
                  </p>
                  <p class="mt-1 text-xs text-slate-500">
                    {{ labels.pendingShort }}: {{ collection.tracking?.pending_analysis ?? 0 }} · {{ labels.inProgressShort }}: {{ collection.tracking?.in_progress_analysis ?? 0 }}
                  </p>
                </div>
              </div>
            </div>

            <div class="flex flex-wrap gap-3 xl:w-72 xl:justify-end">
              <a
                v-if="collection.links?.pdf_quality_certificate"
                :href="collection.links.pdf_quality_certificate"
                target="_blank"
                class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-100"
              >
                {{ labels.viewCertificate }}
              </a>
              <a
                v-if="collection.links?.pdf_path"
                :href="collection.links.pdf_path"
                target="_blank"
                class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
              >
                {{ labels.analysisPdf }}
              </a>
              <a
                v-if="collection.links?.xlsx_path"
                :href="collection.links.xlsx_path"
                class="rounded-2xl border border-cyan-200 bg-cyan-50 px-4 py-2.5 text-sm font-semibold text-cyan-800 transition hover:bg-cyan-100"
              >
                {{ labels.analysisSheet }}
              </a>
              <Link
                :href="route('portal.requests.index', { new: 1, request_type: 'certificate_support', title: `Seguimento ${collection.cl || ''}`.trim() })"
                class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
              >
                {{ labels.followUp }}
              </Link>
            </div>
          </div>
        </article>
      </div>

      <div v-if="record.meta?.links?.length" class="border-t border-slate-200 px-6 py-4">
        <Pagination
          :links="record.meta.links"
          :from="record.meta.from"
          :to="record.meta.to"
          :total="record.meta.total"
          :current_page="record.meta.current_page"
          :last_page="record.meta.last_page"
        />
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, reactive, watch } from 'vue'
import debounce from 'lodash/debounce'
import { Link, router, usePage } from '@inertiajs/vue3'
import Pagination from '@/Components/pagination.vue'
import Layout from '@/Shared/Layouts/PortalLayout.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";

defineOptions({
  layout: Layout,
})

const props = defineProps({
  record: Object,
  query: Object,
  summary: Object,
})

const page = usePage()

const dictionary = {
  en: {
    eyebrow: 'Tracking',
    title: 'Sample and collection tracking',
    subtitle: 'Follow collection progress, analysis readiness, and certificate availability from one place.',
    export: 'Export analysis sheet',
    newRequest: 'Request collection',
    search: 'Search',
    searchPlaceholder: 'Search by code, product, brand, or lot',
    totalCollections: 'Total collections',
    status: 'Status',
    allStatuses: 'All statuses',
    certificateReady: 'Certificate ready',
    analysisPending: 'Awaiting analysis',
    inProgress: 'In progress',
    collectionType: 'Collection type',
    allTypes: 'All types',
    direct: 'Direct',
    programmed: 'Programmed',
    period: 'Period',
    allTime: 'All time',
    lastWeek: 'Last week',
    lastMonth: 'Last month',
    lastQuarter: 'Last quarter',
    tableTitle: 'Tracked collections',
    tableSubtitle: 'Each row shows the latest operational status for the customer collection.',
    emptyTitle: 'No tracked collections yet',
    emptyDescription: 'Once your first collection is registered, it will appear here with its analysis progress.',
    unavailableCode: 'Collection without code',
    pending: 'Pending',
    product: 'Product',
    brand: 'Brand',
    lot: 'Lot',
    quantity: 'Quantity',
    collectionDate: 'Collection date',
    samples: 'Samples',
    analysisStatus: 'Analysis status',
    completed: 'Completed',
    pendingShort: 'Pending',
    inProgressShort: 'In progress',
    viewCertificate: 'View certificate',
    analysisPdf: 'Analysis PDF',
    analysisSheet: 'XLSX sheet',
    followUp: 'Request support',
    resultsLabel: 'Showing :from-:to of :total records',
  },
  pt: {
    eyebrow: 'Acompanhamento',
    title: 'Acompanhamento de amostras e colheitas',
    subtitle: 'Consulte o progresso de cada colheita, o estado das análises e a disponibilidade de certificados num único ecrã.',
    export: 'Exportar folha de análise',
    newRequest: 'Solicitar colheita',
    search: 'Pesquisa',
    searchPlaceholder: 'Pesquisar por código, produto, marca ou lote',
    totalCollections: 'Total de colheitas',
    status: 'Estado',
    allStatuses: 'Todos os estados',
    certificateReady: 'Certificado emitido',
    analysisPending: 'Aguarda análise',
    inProgress: 'Em análise',
    collectionType: 'Tipo de colheita',
    allTypes: 'Todos os tipos',
    direct: 'Directa',
    programmed: 'Programada',
    period: 'Período',
    allTime: 'Todo o período',
    lastWeek: 'Última semana',
    lastMonth: 'Último mês',
    lastQuarter: 'Último trimestre',
    tableTitle: 'Colheitas acompanhadas',
    tableSubtitle: 'Cada linha mostra o estado operacional mais recente da colheita do cliente.',
    emptyTitle: 'Ainda não existem colheitas registadas',
    emptyDescription: 'Assim que a primeira colheita for registada, ela aparecerá aqui com o respetivo progresso analítico.',
    unavailableCode: 'Colheita sem código',
    pending: 'Pendente',
    product: 'Produto',
    brand: 'Marca',
    lot: 'Lote',
    quantity: 'Quantidade',
    collectionDate: 'Data de colheita',
    samples: 'Amostras',
    analysisStatus: 'Estado analítico',
    completed: 'Concluídas',
    pendingShort: 'Pendentes',
    inProgressShort: 'Em curso',
    viewCertificate: 'Ver certificado',
    analysisPdf: 'PDF analítico',
    analysisSheet: 'Folha XLSX',
    followUp: 'Pedir apoio',
    resultsLabel: 'A mostrar :from-:to de :total registos',
  },
}

const labels = computed(() => dictionary[page.props?.language] ?? dictionary.pt)

const filters = reactive({
  search: props.query?.search ?? '',
  status_filter: props.query?.status_filter ?? '',
  type_filter: props.query?.type_filter ?? '',
  date_filter: props.query?.date_filter ?? 'all',
})

const collections = computed(() => props.record?.data ?? [])

const statCards = computed(() => [
  { key: 'total', label: labels.value.totalCollections, value: props.summary?.total ?? 0 },
  { key: 'recent', label: labels.value.lastMonth, value: props.summary?.recent ?? 0 },
  { key: 'certificate_ready', label: labels.value.certificateReady, value: props.summary?.certificate_ready ?? 0 },
  { key: 'analysis_pending', label: labels.value.analysisPending, value: props.summary?.analysis_pending ?? 0 },
  { key: 'in_progress', label: labels.value.inProgress, value: props.summary?.in_progress ?? 0 },
])

const paginationLabel = computed(() => {
  const meta = props.record?.meta

  if (!meta?.total) {
    return ''
  }

  return labels.value.resultsLabel
    .replace(':from', meta.from ?? 0)
    .replace(':to', meta.to ?? 0)
    .replace(':total', meta.total ?? 0)
})

watch(
  filters,
  debounce((value) => {
    router.get(route('portal.collections'), value, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    })
  }, 300),
  { deep: true },
)

const formatDate = (value) => {
  if (!value) {
    return '-'
  }

  return new Date(value).toLocaleDateString(page.props?.language === 'en' ? 'en-GB' : 'pt-PT', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

const collectionTypeLabel = (value) => {
  if (value === 'programmed') {
    return labels.value.programmed
  }

  if (value === 'direct') {
    return labels.value.direct
  }

  return value || '-'
}

const trackingClasses = (status) => {
  const classes = {
    certificate_ready: 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200',
    analysis_completed: 'bg-cyan-50 text-cyan-800 ring-1 ring-cyan-200',
    analysis_in_progress: 'bg-amber-50 text-amber-800 ring-1 ring-amber-200',
    analysis_queued: 'bg-violet-50 text-violet-800 ring-1 ring-violet-200',
    sample_registered: 'bg-slate-100 text-slate-700 ring-1 ring-slate-200',
    awaiting_collection: 'bg-rose-50 text-rose-800 ring-1 ring-rose-200',
  }

  return classes[status] ?? classes.awaiting_collection
}

const exportCollections = () => {
  const query = new URLSearchParams()

  if (filters.search) query.set('search', filters.search)
  if (filters.status_filter) query.set('status_filter', filters.status_filter)
  if (filters.type_filter) query.set('type_filter', filters.type_filter)
  if (filters.date_filter && filters.date_filter !== 'all') query.set('date_filter', filters.date_filter)

  window.location.href = `${route('portal.collections.export')}?${query.toString()}`
}
</script>
