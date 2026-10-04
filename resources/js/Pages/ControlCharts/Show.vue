<script setup>
import BaseTextarea from '@/Components/base/BaseTextarea.vue'
import ControlChartForm from '@/Components/control-charts/ControlChartForm.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import PlanoChart from '@/Components/plano/PlanoChart.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'
import SlideOver from '@/Components/slide-over.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { Calculator as CalculatorIcon, FileDown as DocumentArrowDownIcon, SquarePen as PencilIcon } from '@lucide/vue'
import { computed, ref } from 'vue'

defineOptions({ layout: Layout })

const props = defineProps({
  chart: { type: Object, required: true },
  limits: { type: Object, default: null },
  points: { type: Array, default: () => [] },
  statistics: { type: Object, required: true },
  limitHistory: { type: Array, default: () => [] },
  rules: { type: Object, default: () => ({}) },
  minimumPointsForLimits: { type: Number, default: 10 },
  recommendedPointsForLimits: { type: Number, default: 20 },
  permissions: { type: Object, default: () => ({}) },
})

const number = (value) => {
  if (value === null || value === undefined || value === '') return '—'
  const magnitude = Math.abs(Number(value))
  const digits = magnitude >= 1000 ? 1 : magnitude >= 100 ? 2 : magnitude >= 1 ? 3 : 4
  return new Intl.NumberFormat('pt-PT', { maximumFractionDigits: digits }).format(Number(value))
}

const stateTone = (state) => ({ in_control: 'ok', warning: 'wait', out_of_control: 'bad', excluded: 'neutral' }[state] ?? 'neutral')
const canEdit = computed(() => props.permissions.edit && props.chart.status === 'active')
const included = computed(() => props.points.filter((point) => !point.excluded))
const latest = computed(() => included.value[included.value.length - 1] ?? null)
const canCompute = computed(() => props.permissions.edit && props.statistics.count >= props.minimumPointsForLimits)

const cells = computed(() => [
  { label: 'Último ponto', value: latest.value ? latest.value.state_label : 'Sem pontos', bad: latest.value?.state === 'out_of_control' },
  { label: 'Pontos incluídos', value: props.statistics.count },
  { label: 'Fora de controlo', value: props.statistics.out_of_control, bad: props.statistics.out_of_control > 0 },
  { label: 'Sem acção registada', value: props.statistics.open_actions, bad: props.statistics.open_actions > 0 },
  { label: 'Dentro dos limites de aviso', value: props.statistics.within_warning === null ? '—' : `${number(props.statistics.within_warning)} %` },
])

const references = computed(() => {
  if (!props.limits) return null
  return [
    { value: props.limits.upper_action, label: `LSA ${number(props.limits.upper_action)}`, tone: 'bad' },
    { value: props.limits.upper_warning, label: `LSV ${number(props.limits.upper_warning)}`, tone: 'warn' },
    { value: props.limits.centre, label: `LC ${number(props.limits.centre)}` },
    props.limits.lower_warning !== null ? { value: props.limits.lower_warning, label: `LIV ${number(props.limits.lower_warning)}`, tone: 'warn' } : null,
    props.limits.lower_action !== null ? { value: props.limits.lower_action, label: `LIA ${number(props.limits.lower_action)}`, tone: 'bad' } : null,
  ].filter(Boolean)
})

// The point number, as in the table and the PDF, and the day it was measured.
const chartCategories = computed(() => included.value.map((point) => `${point.sequence} · ${point.measured_at?.slice(0, 5) ?? ''}`))
const chartSeries = computed(() => [{ name: props.chart.is_range ? 'Amplitude' : 'Valor', data: included.value.map((point) => point.value) }])
const chartMarks = computed(() => included.value
  .map((point, index) => ({ index, tone: point.state === 'out_of_control' ? 'bad' : point.state === 'warning' ? 'warn' : null }))
  .filter((mark) => mark.tone))

// Recording a point.
const pointForm = useForm({
  measured_at: new Date().toISOString().slice(0, 10),
  value: '',
  replicate_a: '',
  replicate_b: '',
  run_reference: '',
  notes: '',
})

function recordPoint() {
  pointForm.transform((data) => (props.chart.is_range
    ? { ...data, value: undefined }
    : { ...data, replicate_a: undefined, replicate_b: undefined }))
  pointForm.post(route('control-charts.points.store', props.chart.id), {
    preserveScroll: true,
    onSuccess: () => pointForm.reset('value', 'replicate_a', 'replicate_b', 'notes'),
  })
}

// Reviewing a point: exclusion and the action taken.
const reviewing = ref(null)
const reviewForm = useForm({ excluded: false, exclusion_reason: '', corrective_action: '' })

function openReview(point) {
  reviewing.value = point
  reviewForm.defaults({ excluded: point.excluded, exclusion_reason: point.exclusion_reason ?? '', corrective_action: point.corrective_action ?? '' })
  reviewForm.reset()
  reviewForm.clearErrors()
}

function saveReview() {
  reviewForm.put(route('control-charts.points.update', [props.chart.id, reviewing.value.id]), {
    preserveScroll: true,
    onSuccess: () => { reviewing.value = null },
  })
}

// Editing the chart and its limits.
const editing = ref(false)
const chartForm = useForm({
  name: props.chart.name,
  parameter_id: props.chart.parameter_id,
  method: props.chart.method ?? '',
  matrix: props.chart.matrix ?? '',
  control_material: props.chart.control_material ?? '',
  material_lot: props.chart.material_lot ?? '',
  unit: props.chart.unit ?? '',
  centre_line: props.chart.centre_line ?? '',
  standard_deviation: props.chart.standard_deviation ?? '',
  limits_basis: props.chart.limits_basis ?? '',
  notes: props.chart.notes ?? '',
  chart_type: props.chart.chart_type,
})

function saveChart() {
  chartForm.transform(({ chart_type, ...data }) => data)
  chartForm.put(route('control-charts.update', props.chart.id), {
    preserveScroll: true,
    onSuccess: () => { editing.value = false },
  })
}

function setStatus(status) {
  router.put(route('control-charts.update', props.chart.id), { status }, { preserveScroll: true })
}

const computeForm = useForm({ limits_basis: '' })
function computeLimits() {
  computeForm.post(route('control-charts.limits', props.chart.id), { preserveScroll: true })
}

function destroyChart() {
  if (window.confirm('Eliminar esta carta e todos os seus pontos?')) {
    router.delete(route('control-charts.destroy', props.chart.id))
  }
}
</script>

<template>
  <Head :title="chart.name" />

  <div class="pl-page space-y-6">
    <PageHeader :trail="[{ title: 'Cartas de controlo', url: route('control-charts.index') }, { title: chart.name }]" :title="chart.name" :lede="[chart.type_label, chart.parameter, chart.method, chart.control_material].filter(Boolean).join(' · ')">
      <template #badges>
        <StatusChip v-if="chart.status === 'archived'" tone="done">Arquivada</StatusChip>
      </template>
      <template #actions>
        <a :href="route('control-charts.pdf', chart.id)" class="ds-button ds-button-secondary">
          <DocumentArrowDownIcon class="h-4 w-4" aria-hidden="true" />
          PDF
        </a>
        <button v-if="permissions.edit" type="button" class="ds-button ds-button-secondary" @click="editing = true">
          <PencilIcon class="h-4 w-4" aria-hidden="true" />
          Editar
        </button>
      </template>
    </PageHeader>

    <dl class="pl-cells">
      <div v-for="cell in cells" :key="cell.label" class="pl-cell" :class="{ 'pl-cell-bad': cell.bad }">
        <dt class="pl-k pl-muted">{{ cell.label }}</dt>
        <dd class="pl-cell-value">{{ cell.value }}</dd>
      </div>
    </dl>

    <div v-if="!limits" class="pl-banner pl-banner-warn flex flex-wrap items-center justify-between gap-3 px-5 py-4">
      <div>
        <p class="text-sm font-bold">Limites por definir: os pontos ficam registados, mas não são avaliados.</p>
        <p class="mt-1 text-sm">
          Indique-os em «Editar», ou calcule-os a partir de {{ minimumPointsForLimits }} pontos incluídos ou mais
          ({{ recommendedPointsForLimits }} recomendados). Tem {{ statistics.count }}.
        </p>
      </div>
      <button v-if="permissions.edit" type="button" class="ds-button ds-button-primary" :disabled="!canCompute || computeForm.processing" @click="computeLimits">
        <CalculatorIcon class="h-4 w-4" aria-hidden="true" />
        Calcular limites
      </button>
    </div>
    <p v-if="computeForm.errors.limits" class="ds-field-error">{{ computeForm.errors.limits }}</p>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
      <article class="pl-panel min-w-0">
        <header class="pl-panel-head">
          <h2 class="pl-k">{{ chart.type_label }}</h2>
          <span class="text-xs text-[var(--pl-muted)]">{{ chart.unit || 'Sem unidade' }}</span>
        </header>
        <div class="p-4">
          <PlanoChart
            kind="line"
            :label="`${chart.type_label}: ${chart.name}`"
            :categories="chartCategories"
            :series="chartSeries"
            :reference="references"
            :points="chartMarks.length ? chartMarks : true"
            format="decimal"
            :unit="chart.unit || ''"
            :height="320"
            empty-text="Registe o primeiro valor de controlo para ver a carta."
          />
        </div>
      </article>

      <aside class="pl-panel min-w-0">
        <header class="pl-panel-head"><h2 class="pl-k">Limites</h2></header>
        <dl v-if="limits" class="divide-y divide-[var(--pl-line)] text-sm">
          <div class="flex justify-between gap-3 px-4 py-2.5"><dt class="text-[var(--pl-muted)]">{{ chart.is_range ? 'Amplitude média (R̄)' : 'Linha central' }}</dt><dd class="pl-num">{{ number(limits.centre) }}</dd></div>
          <div v-if="!chart.is_range" class="flex justify-between gap-3 px-4 py-2.5"><dt class="text-[var(--pl-muted)]">Desvio-padrão (s)</dt><dd class="pl-num">{{ number(chart.standard_deviation) }}</dd></div>
          <div class="flex justify-between gap-3 px-4 py-2.5"><dt class="text-[var(--pl-muted)]">Aviso</dt><dd class="pl-num">{{ chart.is_range ? `≤ ${number(limits.upper_warning)}` : `${number(limits.lower_warning)} – ${number(limits.upper_warning)}` }}</dd></div>
          <div class="flex justify-between gap-3 px-4 py-2.5"><dt class="text-[var(--pl-muted)]">Acção</dt><dd class="pl-num">{{ chart.is_range ? `≤ ${number(limits.upper_action)}` : `${number(limits.lower_action)} – ${number(limits.upper_action)}` }}</dd></div>
          <div class="px-4 py-2.5">
            <dt class="text-[var(--pl-muted)]">{{ chart.limits_source_label }}<template v-if="chart.limits_point_count"> · {{ chart.limits_point_count }} pontos</template></dt>
            <dd class="mt-1 text-xs text-[var(--pl-muted)]">{{ [chart.limits_set_at, chart.limits_set_by].filter(Boolean).join(' · ') }}</dd>
            <dd v-if="chart.limits_basis" class="mt-2 text-xs text-[var(--pl-fg)]">{{ chart.limits_basis }}</dd>
          </div>
          <div v-if="permissions.edit" class="px-4 py-3">
            <button type="button" class="ds-button ds-button-secondary w-full" :disabled="!canCompute || computeForm.processing" @click="computeLimits">
              <CalculatorIcon class="h-4 w-4" aria-hidden="true" />
              Recalcular a partir dos pontos
            </button>
            <p class="mt-2 text-xs text-[var(--pl-muted)]">Usa os {{ statistics.count }} pontos incluídos. Recalcule só com o método sob controlo.</p>
          </div>
        </dl>
        <p v-else class="px-4 py-3 text-sm text-[var(--pl-muted)]">Sem limites definidos.</p>

        <header class="pl-panel-head border-t border-[var(--pl-line)]"><h2 class="pl-k">Fora de controlo quando</h2></header>
        <ul class="space-y-2 px-4 py-3 text-xs text-[var(--pl-muted)]">
          <li v-for="(label, key) in rules" :key="key">{{ label }}</li>
        </ul>
      </aside>
    </section>

    <section v-if="canEdit" class="pl-panel">
      <header class="pl-panel-head"><h2 class="pl-k">Registar valor de controlo</h2></header>
      <form class="grid gap-4 p-4 md:grid-cols-[10rem_repeat(2,minmax(0,1fr))_minmax(0,1.4fr)_auto] md:items-end" @submit.prevent="recordPoint">
        <div class="ds-field-group">
          <label class="ds-field-label" for="point-date">Data</label>
          <BaseInput id="point-date" v-model="pointForm.measured_at" type="date" class="ds-field" />
          <p v-if="pointForm.errors.measured_at" class="ds-field-error">{{ pointForm.errors.measured_at }}</p>
        </div>
        <template v-if="chart.is_range">
          <div class="ds-field-group">
            <label class="ds-field-label" for="point-a">1.ª réplica</label>
            <BaseInput id="point-a" v-model="pointForm.replicate_a" class="ds-field" inputmode="decimal" />
            <p v-if="pointForm.errors.replicate_a" class="ds-field-error">{{ pointForm.errors.replicate_a }}</p>
          </div>
          <div class="ds-field-group">
            <label class="ds-field-label" for="point-b">2.ª réplica</label>
            <BaseInput id="point-b" v-model="pointForm.replicate_b" class="ds-field" inputmode="decimal" />
            <p v-if="pointForm.errors.replicate_b" class="ds-field-error">{{ pointForm.errors.replicate_b }}</p>
          </div>
        </template>
        <div v-else class="ds-field-group md:col-span-2">
          <label class="ds-field-label" for="point-value">Valor{{ chart.unit ? ` (${chart.unit})` : '' }}</label>
          <BaseInput id="point-value" v-model="pointForm.value" class="ds-field" inputmode="decimal" />
          <p v-if="pointForm.errors.value" class="ds-field-error">{{ pointForm.errors.value }}</p>
        </div>
        <div class="ds-field-group">
          <label class="ds-field-label" for="point-run">Corrida ou lote analítico</label>
          <BaseInput id="point-run" v-model="pointForm.run_reference" class="ds-field" maxlength="120" />
        </div>
        <button type="submit" class="ds-button ds-button-primary" :disabled="pointForm.processing">Registar</button>
      </form>
    </section>

    <section class="pl-panel">
      <header class="pl-panel-head">
        <h2 class="pl-k">Pontos</h2>
        <span class="pl-k pl-faint">{{ points.length }} registados<template v-if="statistics.excluded"> · {{ statistics.excluded }} excluídos</template></span>
      </header>
      <div v-if="points.length">
        <DataTable class="min-w-full text-sm">
          <thead class="ds-table-head">
            <tr>
              <th class="ds-table-heading px-4 py-2 text-right">N.º</th>
              <th class="ds-table-heading px-4 py-2 text-left">Data</th>
              <th class="ds-table-heading px-4 py-2 text-left">Corrida</th>
              <th v-if="chart.is_range" class="ds-table-heading px-4 py-2 text-right">Réplicas</th>
              <th class="ds-table-heading px-4 py-2 text-right">{{ chart.is_range ? 'Amplitude' : 'Valor' }}</th>
              <th class="ds-table-heading px-4 py-2 text-left">Avaliação</th>
              <th class="ds-table-heading px-4 py-2 text-left">Acção tomada</th>
              <th class="ds-table-heading px-4 py-2"><span class="sr-only">Rever</span></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--pl-line)]">
            <tr v-for="point in [...points].reverse()" :key="point.id" :class="{ 'opacity-60': point.excluded }">
              <td class="pl-num px-4 py-2 text-right">{{ point.sequence }}</td>
              <td class="px-4 py-2">{{ point.measured_at }}<span v-if="point.recorded_by" class="block text-xs text-[var(--pl-muted)]">{{ point.recorded_by }}</span></td>
              <td class="px-4 py-2">{{ point.run_reference || '—' }}</td>
              <td v-if="chart.is_range" class="pl-num px-4 py-2 text-right">{{ number(point.replicate_a) }} / {{ number(point.replicate_b) }}</td>
              <td class="pl-num px-4 py-2 text-right font-bold" :class="{ 'line-through': point.excluded }">{{ number(point.value) }}</td>
              <td class="px-4 py-2">
                <StatusChip :tone="stateTone(point.state)">{{ point.state_label }}</StatusChip>
                <span v-for="rule in point.rule_labels" :key="rule" class="mt-1 block text-xs text-[var(--pl-muted)]">{{ rule }}</span>
                <span v-if="point.excluded && point.exclusion_reason" class="mt-1 block text-xs text-[var(--pl-muted)]">{{ point.exclusion_reason }}</span>
              </td>
              <td class="max-w-xs px-4 py-2 text-xs">
                <template v-if="point.corrective_action">
                  {{ point.corrective_action }}
                  <span class="mt-1 block text-[var(--pl-muted)]">{{ [point.corrective_action_by, point.corrective_action_at].filter(Boolean).join(' · ') }}</span>
                </template>
                <StatusChip v-else-if="point.needs_action" tone="bad">Por registar</StatusChip>
              </td>
              <td class="px-4 py-2 text-right">
                <button v-if="permissions.edit" type="button" class="ds-table-action" @click="openReview(point)">Rever</button>
              </td>
            </tr>
          </tbody>
        </DataTable>
      </div>
      <p v-else class="px-4 py-8 text-center text-sm text-[var(--pl-muted)]">Ainda não há valores de controlo registados.</p>
    </section>

    <section class="grid gap-6 lg:grid-cols-2">
      <article class="pl-panel">
        <header class="pl-panel-head"><h2 class="pl-k">Pontos incluídos</h2></header>
        <dl class="grid grid-cols-2 divide-x divide-y divide-[var(--pl-line)] text-sm">
          <div class="px-4 py-3"><dt class="pl-k pl-muted">Média</dt><dd class="pl-num mt-1">{{ number(statistics.mean) }}</dd></div>
          <div class="px-4 py-3"><dt class="pl-k pl-muted">Desvio-padrão</dt><dd class="pl-num mt-1">{{ number(statistics.standard_deviation) }}</dd></div>
          <div class="px-4 py-3"><dt class="pl-k pl-muted">Mínimo</dt><dd class="pl-num mt-1">{{ number(statistics.minimum) }}</dd></div>
          <div class="px-4 py-3"><dt class="pl-k pl-muted">Máximo</dt><dd class="pl-num mt-1">{{ number(statistics.maximum) }}</dd></div>
        </dl>
        <p class="px-4 py-3 text-xs text-[var(--pl-muted)]">
          Com o método sob controlo, cerca de 95 % dos pontos ficam dentro dos limites de aviso. Uma média ou dispersão afastadas dos limites indicam que estes devem ser revistos.
        </p>
      </article>
      <article class="pl-panel">
        <header class="pl-panel-head"><h2 class="pl-k">Histórico dos limites</h2></header>
        <ol v-if="limitHistory.length" class="divide-y divide-[var(--pl-line)] text-sm">
          <li v-for="entry in limitHistory" :key="entry.id" class="px-4 py-3">
            <p class="pl-num">{{ chart.is_range ? 'R̄' : 'LC' }} {{ number(entry.centre_line) }}<template v-if="!chart.is_range"> · s {{ number(entry.standard_deviation) }}</template></p>
            <p class="mt-1 text-xs text-[var(--pl-muted)]">{{ [entry.at, entry.by, entry.source].filter(Boolean).join(' · ') }}</p>
            <p v-if="entry.basis" class="mt-1 text-xs">{{ entry.basis }}</p>
          </li>
        </ol>
        <p v-else class="px-4 py-3 text-sm text-[var(--pl-muted)]">Os limites ainda não foram definidos.</p>
      </article>
    </section>

    <SlideOver v-if="reviewing" :title="`Ponto ${reviewing.sequence} · ${reviewing.measured_at}`" :description="`${reviewing.state_label} · ${number(reviewing.value)}${chart.unit ? ' ' + chart.unit : ''}`" size="narrow" :disabled="reviewForm.processing" @close="reviewing = null">
      <template #content>
        <form id="control-point-review" class="space-y-5 px-6 py-5" @submit.prevent="saveReview">
          <p v-if="reviewing.rule_labels.length" class="pl-banner pl-banner-bad px-4 py-3 text-sm">{{ reviewing.rule_labels.join(' ') }}</p>
          <BaseTextarea
            v-model="reviewForm.corrective_action"
            label="Acção tomada"
            :rows="4"
            :error="reviewForm.errors.corrective_action"
            placeholder="Causa encontrada, correcção, resultados da corrida retidos ou repetidos."
          />
          <label class="flex items-start gap-3 text-sm">
            <CheckboxInput v-model="reviewForm.excluded" class="mt-1" />
            <span>
              <span class="font-bold">Excluir o ponto</span>
              <span class="block text-xs text-[var(--pl-muted)]">Por erro de registo ou causa identificada. O valor fica no registo, fora das regras e dos limites calculados.</span>
            </span>
          </label>
          <BaseTextarea v-if="reviewForm.excluded" v-model="reviewForm.exclusion_reason" label="Motivo da exclusão" :rows="2" :error="reviewForm.errors.exclusion_reason" />
        </form>
      </template>
      <template #action_buttons>
        <div class="flex justify-end gap-2">
          <button type="button" class="ds-button ds-button-secondary" :disabled="reviewForm.processing" @click="reviewing = null">Cancelar</button>
          <button type="submit" form="control-point-review" class="ds-button ds-button-primary" :disabled="reviewForm.processing">Guardar</button>
        </div>
      </template>
    </SlideOver>

    <SlideOver v-if="editing" title="Editar carta de controlo" description="Uma alteração aos limites fica no histórico da carta." :disabled="chartForm.processing" @close="editing = false">
      <template #content>
        <form id="control-chart-edit" class="space-y-6 px-6 py-5" @submit.prevent="saveChart">
          <ControlChartForm :form="chartForm" />
          <div class="flex flex-wrap gap-2 border-t border-[var(--pl-line)] pt-5">
            <button v-if="chart.status === 'active'" type="button" class="ds-button ds-button-secondary" @click="setStatus('archived')">Arquivar carta</button>
            <button v-else type="button" class="ds-button ds-button-secondary" @click="setStatus('active')">Reactivar carta</button>
            <button v-if="permissions.delete" type="button" class="ds-button ds-button-danger" @click="destroyChart">Eliminar</button>
          </div>
        </form>
      </template>
      <template #action_buttons>
        <div class="flex justify-end gap-2">
          <button type="button" class="ds-button ds-button-secondary" :disabled="chartForm.processing" @click="editing = false">Cancelar</button>
          <button type="submit" form="control-chart-edit" class="ds-button ds-button-primary" :disabled="chartForm.processing">Guardar</button>
        </div>
      </template>
    </SlideOver>
  </div>
</template>
