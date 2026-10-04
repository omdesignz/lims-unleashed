<script setup>
import ControlChartForm from '@/Components/control-charts/ControlChartForm.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'
import SlideOver from '@/Components/slide-over.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ChevronRight as ChevronRightIcon, Plus as PlusIcon, Search as MagnifyingGlassIcon } from '@lucide/vue'
import { computed, ref, watch } from 'vue'

defineOptions({ layout: Layout })

const props = defineProps({
  charts: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  totals: { type: Object, default: () => ({}) },
  types: { type: Array, default: () => [] },
  permissions: { type: Object, default: () => ({}) },
})

const search = ref(props.filters.search ?? '')
const status = ref(props.filters.status ?? 'active')
const creating = ref(false)

const form = useForm({
  name: '',
  chart_type: 'mean',
  parameter_id: null,
  control_product_id: null,
  method: '',
  matrix: '',
  control_material: '',
  material_lot: '',
  unit: '',
  centre_line: '',
  standard_deviation: '',
  limits_basis: '',
  notes: '',
})

const cells = computed(() => [
  { label: 'Cartas activas', value: props.totals.active ?? 0 },
  { label: 'Fora de controlo', value: props.totals.out_of_control ?? 0, bad: (props.totals.out_of_control ?? 0) > 0 },
  { label: 'Sem acção registada', value: props.totals.open_actions ?? 0, bad: (props.totals.open_actions ?? 0) > 0 },
  { label: 'Sem limites', value: props.totals.without_limits ?? 0 },
])

const stateTone = (state) => ({ in_control: 'ok', warning: 'wait', out_of_control: 'bad' }[state] ?? 'neutral')

let searchTimer = null
watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(reload, 300)
})

function setStatus(value) {
  status.value = value
  reload()
}

function reload() {
  router.get(route('control-charts.index'), { search: search.value || undefined, status: status.value }, { preserveState: true, preserveScroll: true, replace: true })
}

function submit() {
  form.post(route('control-charts.store'), {
    onSuccess: () => {
      creating.value = false
      form.reset()
    },
  })
}
</script>

<template>
  <Head title="Cartas de controlo" />

  <div class="pl-page space-y-6">
    <PageHeader title="Cartas de controlo" lede="Controlo interno da qualidade dos ensaios: registe os valores de controlo de cada corrida e actue quando um ponto sai de controlo.">
      <template #actions>
        <button v-if="permissions.add" type="button" class="ds-button ds-button-primary" @click="creating = true">
          <PlusIcon class="h-4 w-4" aria-hidden="true" />
          Nova carta
        </button>
      </template>
    </PageHeader>

    <dl class="pl-cells">
      <div v-for="cell in cells" :key="cell.label" class="pl-cell" :class="{ 'pl-cell-bad': cell.bad }">
        <dt class="pl-k pl-muted">{{ cell.label }}</dt>
        <dd class="pl-cell-value">{{ cell.value }}</dd>
      </div>
    </dl>

    <section class="pl-panel">
      <header class="pl-panel-head flex-wrap gap-3">
        <div class="relative min-w-[16rem] flex-1">
          <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--pl-faint)]" aria-hidden="true" />
          <BaseInput v-model="search" type="search" class="ds-field pl-9" placeholder="Pesquisar por carta, método ou material" aria-label="Pesquisar cartas" />
        </div>
        <div class="pl-segmented" role="radiogroup" aria-label="Estado das cartas">
          <button type="button" role="radio" :aria-checked="status === 'active'" @click="setStatus('active')">Activas</button>
          <button type="button" role="radio" :aria-checked="status === 'archived'" @click="setStatus('archived')">Arquivadas</button>
        </div>
      </header>

      <ul v-if="charts.length" class="divide-y divide-[var(--pl-line)]">
        <li v-for="chart in charts" :key="chart.id">
          <Link :href="route('control-charts.show', chart.id)" class="grid gap-3 px-5 py-4 hover:bg-[var(--pl-layer)] md:grid-cols-[minmax(0,2fr)_minmax(0,1.2fr)_8rem_10rem_auto] md:items-center">
            <div class="min-w-0">
              <p class="truncate text-sm font-bold text-[var(--pl-fg)]">{{ chart.name }}</p>
              <p class="mt-1 truncate text-xs text-[var(--pl-muted)]">{{ [chart.parameter, chart.method].filter(Boolean).join(' · ') || chart.type_label }}</p>
            </div>
            <div class="min-w-0 text-xs text-[var(--pl-muted)]">
              <p class="pl-k">{{ chart.type_label }}</p>
              <p class="mt-1 truncate">{{ chart.control_material || '—' }}</p>
            </div>
            <div class="text-xs text-[var(--pl-muted)]">
              <p class="pl-num text-sm text-[var(--pl-fg)]">{{ chart.point_count }} pontos</p>
              <p class="mt-1">{{ chart.last_measured_at || 'Sem pontos' }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <StatusChip v-if="!chart.has_limits" tone="neutral">Sem limites</StatusChip>
              <StatusChip v-else-if="chart.state" :tone="stateTone(chart.state)">{{ chart.state_label }}</StatusChip>
              <StatusChip v-if="chart.open_actions" tone="bad">{{ chart.open_actions }} sem acção</StatusChip>
            </div>
            <ChevronRightIcon class="hidden h-4 w-4 text-[var(--pl-faint)] md:block" aria-hidden="true" />
          </Link>
        </li>
      </ul>

      <div v-else class="px-5 py-12 text-center">
        <p class="text-sm font-bold text-[var(--pl-fg)]">{{ status === 'archived' ? 'Sem cartas arquivadas.' : 'Ainda não há cartas de controlo.' }}</p>
        <p v-if="status !== 'archived'" class="mx-auto mt-2 max-w-md text-sm text-[var(--pl-muted)]">
          Crie uma carta por ensaio e material de controlo. Com 20 pontos registados pode calcular os limites a partir dos próprios dados.
        </p>
      </div>
    </section>

    <SlideOver v-if="creating" title="Nova carta de controlo" description="O tipo de carta não muda depois de criada." :disabled="form.processing" @close="creating = false">
      <template #content>
        <form id="control-chart-create" class="px-6 py-5" @submit.prevent="submit">
          <ControlChartForm :form="form" :types="types" creating />
        </form>
      </template>
      <template #action_buttons>
        <div class="flex justify-end gap-2">
          <button type="button" class="ds-button ds-button-secondary" :disabled="form.processing" @click="creating = false">Cancelar</button>
          <button type="submit" form="control-chart-create" class="ds-button ds-button-primary" :disabled="form.processing">Criar carta</button>
        </div>
      </template>
    </SlideOver>
  </div>
</template>
