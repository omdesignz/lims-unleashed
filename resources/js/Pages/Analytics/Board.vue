<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ArrowDown, ArrowUp, Ellipsis, Pencil, Plus, RotateCcw, Trash2 } from '@lucide/vue'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import PlanoChart from '@/Components/plano/PlanoChart.vue'
import SlideOver from '@/Components/slide-over.vue'
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import ColorInput from '@/Components/base/ColorInput.vue'
import { categoricalPalette, isDarkTheme } from '@/Support/charts'
import { confusablePairs } from '@/Support/colourDistance'

/**
 * The analytics board: charts a person builds on their laboratory's data.
 * The builder only offers what the registry declares; the server checks the
 * definition again and computes every value live.
 */
const props = defineProps({
  charts: { type: Array, default: () => [] },
  datasets: { type: Array, default: () => [] },
  periods: { type: Array, default: () => [] },
})

const kinds = [
  { key: 'column', label: 'Colunas' },
  { key: 'bar', label: 'Barras' },
  { key: 'line', label: 'Linhas' },
  { key: 'area', label: 'Área' },
  { key: 'donut', label: 'Donut' },
]

// Starting points offered on an empty board, kept only when their dataset is available.
const starters = [
  { title: 'Amostras recebidas por semana', dataset: 'samples', measure: 'count', dimension: 'week', split: null, kind: 'column', period: '90d' },
  { title: 'Amostras por estado', dataset: 'samples', measure: 'count', dimension: 'status', split: null, kind: 'donut', period: 'all' },
  { title: 'Não conformidades por severidade', dataset: 'nonconformities', measure: 'count', dimension: 'severity', split: null, kind: 'bar', period: '12m' },
  { title: 'Facturação por mês', dataset: 'invoices', measure: 'total', dimension: 'month', split: null, kind: 'column', period: '12m' },
  { title: 'Custo de manutenção por categoria', dataset: 'maintenance', measure: 'cost', dimension: 'category', split: null, kind: 'bar', period: '12m' },
]

const datasetByKey = computed(() => Object.fromEntries(props.datasets.map((dataset) => [dataset.key, dataset])))
const periodLabel = (key) => props.periods.find((period) => period.key === key)?.label ?? key
const availableStarters = computed(() => starters.filter((starter) => datasetByKey.value[starter.dataset]))

// --- Builder -------------------------------------------------------------

const builderOpen = ref(false)
const editing = ref(null)
const form = useForm({ title: '', dataset: '', measure: '', dimension: '', split: null, kind: 'column', period: '90d', colors: {} })

const dataset = computed(() => datasetByKey.value[form.dataset] ?? null)
const measure = computed(() => dataset.value?.measures.find((item) => item.key === form.measure) ?? null)
const dimension = computed(() => dataset.value?.dimensions.find((item) => item.key === form.dimension) ?? null)
const isTime = computed(() => dimension.value?.type === 'time')
const hasSplit = computed(() => Boolean(form.split))

const datasetOptions = computed(() => props.datasets.map((item) => ({ value: item.key, label: item.label })))
const measureOptions = computed(() => (dataset.value?.measures ?? []).map((item) => ({
  value: item.key,
  label: item.requires && item.requires !== form.dimension ? `${item.label} (por ${dataset.value.dimensions.find((entry) => entry.key === item.requires)?.label.toLowerCase()})` : item.label,
})))
const dimensionOptions = computed(() => (dataset.value?.dimensions ?? []).map((item) => ({ value: item.key, label: item.type === 'time' ? `${item.label} (tempo)` : item.label })))
const splitOptions = computed(() => [
  { value: '', label: 'Sem divisão' },
  ...(dataset.value?.dimensions ?? []).filter((item) => item.type === 'category' && item.key !== form.dimension).map((item) => ({ value: item.key, label: item.label })),
])

/** Why a chart form does not fit the current choices (mirrors the server's rules). */
function kindProblem(kind) {
  if (['line', 'area'].includes(kind) && !isTime.value) return 'Linhas e áreas mostram evolução: agrupe por dia, semana ou mês.'
  if (kind === 'area' && hasSplit.value) return 'Uma área mostra uma só série.'
  if (kind === 'donut' && (isTime.value || hasSplit.value || (measure.value && !measure.value.additive))) return 'Um donut mostra partes de um total: agrupe por categoria, sem divisão.'
  return null
}

function chooseDataset(key) {
  form.dataset = key
  const next = datasetByKey.value[key]
  form.measure = next?.measures[0]?.key ?? ''
  form.dimension = next?.dimensions.find((item) => item.key === 'month')?.key ?? next?.dimensions[0]?.key ?? ''
  form.split = null
  form.colors = {}
}

// Keep the form coherent: a measure tied to one grouping takes that grouping,
// and an unfit chart form falls back to the first one that fits.
watch(() => form.measure, () => {
  if (measure.value?.requires) form.dimension = measure.value.requires
})
watch(() => form.dimension, () => {
  if (form.split === form.dimension) form.split = null
  if (measure.value?.requires && measure.value.requires !== form.dimension) form.measure = dataset.value?.measures.find((item) => !item.requires)?.key ?? form.measure
})
watch(() => [form.dimension, form.split, form.measure], () => {
  if (kindProblem(form.kind)) form.kind = kinds.find((kind) => !kindProblem(kind.key))?.key ?? 'column'
})

function openBuilder(chart = null, starter = null) {
  const source = chart ?? starter
  editing.value = chart
  form.clearErrors()
  form.defaults({ title: '', dataset: '', measure: '', dimension: '', split: null, kind: 'column', period: '90d', colors: {} })
  form.reset()

  if (source) {
    Object.assign(form, { title: source.title, dataset: source.dataset, measure: source.measure, dimension: source.dimension, split: source.split || null, kind: source.kind, period: source.period, colors: { ...(source.colors ?? {}) } })
  } else if (props.datasets[0]) {
    chooseDataset(props.datasets[0].key)
  }

  preview.value = chart?.data ?? null
  builderOpen.value = true
}

function closeBuilder() {
  builderOpen.value = false
  previewAbort?.abort()
}

function save() {
  const options = { preserveScroll: true, onSuccess: closeBuilder }
  const payload = (data) => ({ ...data, split: data.split || null, colors: Object.keys(data.colors).length ? data.colors : null })

  if (editing.value) {
    form.transform(payload).put(route('analytics.charts.update', editing.value.id), options)
  } else {
    form.transform(payload).post(route('analytics.charts.store'), options)
  }
}

// --- Live preview --------------------------------------------------------

const preview = ref(null)
const previewLoading = ref(false)
const previewError = ref('')
let previewAbort = null
let previewTimer = null

const definition = computed(() => ({ dataset: form.dataset, measure: form.measure, dimension: form.dimension, split: form.split || '', kind: form.kind, period: form.period }))

async function loadPreview() {
  if (!builderOpen.value || !form.dataset || !form.measure || !form.dimension || kindProblem(form.kind)) return

  previewAbort?.abort()
  previewAbort = new AbortController()
  previewLoading.value = true
  previewError.value = ''

  try {
    const response = await fetch(route('analytics.charts.preview', definition.value), {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      signal: previewAbort.signal,
    })
    const body = await response.json()

    if (!response.ok) {
      previewError.value = Object.values(body.errors ?? {}).flat()[0] ?? 'Não foi possível desenhar a pré-visualização.'
      return
    }

    preview.value = body
  } catch (error) {
    if (error.name !== 'AbortError') previewError.value = 'Ligação interrompida. A pré-visualização será tentada de novo na próxima alteração.'
  } finally {
    previewLoading.value = false
  }
}

watch(definition, () => {
  clearTimeout(previewTimer)
  previewTimer = setTimeout(loadPreview, 250)
}, { deep: true })

watch(builderOpen, (open) => { if (open) nextTick(loadPreview) })

// In one column the preview stays pinned above the fields, so it is drawn shorter.
const singleColumn = ref(false)
let columnQuery = null
const followColumns = (event) => { singleColumn.value = event.matches }

onMounted(() => {
  columnQuery = window.matchMedia('(max-width: 899.98px)')
  singleColumn.value = columnQuery.matches
  columnQuery.addEventListener('change', followColumns)
})

onBeforeUnmount(() => {
  clearTimeout(previewTimer)
  previewAbort?.abort()
  columnQuery?.removeEventListener('change', followColumns)
})

// --- Colours -------------------------------------------------------------

const palette = computed(() => (isDarkTheme() ? categoricalPalette.dark : categoricalPalette.light))

/** The names a colour can belong to: the series, or the slices of a donut. */
const colourTargets = computed(() => {
  if (!preview.value) return []
  return form.kind === 'donut' ? preview.value.categories.slice(0, 6) : preview.value.series.map((item) => item.name)
})

const effectiveColour = (name, index) => form.colors[name] ?? palette.value[Math.min(index, palette.value.length - 1)]

// Chosen colours stay free, but neighbours that readers would confuse are named.
const colourWarnings = computed(() => confusablePairs(colourTargets.value.map((name, index) => ({ name, color: effectiveColour(name, index) })))
  .map((pair) => (pair.reason === 'cvd'
    ? `${pair.first} e ${pair.second} confundem-se para quem não distingue vermelho e verde.`
    : `${pair.first} e ${pair.second} têm cores demasiado próximas.`)))

function setColour(name, colour) {
  form.colors = { ...form.colors, [name]: colour }
}

function resetColour(name) {
  const { [name]: _removed, ...rest } = form.colors
  form.colors = rest
}

// --- Board ---------------------------------------------------------------

const pendingRemoval = ref(null)
const working = ref(false)

function move(chart, offset) {
  const order = props.charts.map((item) => item.id)
  const from = order.indexOf(chart.id)
  const to = from + offset
  if (to < 0 || to >= order.length || working.value) return
  ;[order[from], order[to]] = [order[to], order[from]]
  working.value = true
  router.patch(route('analytics.charts.reorder'), { order }, { preserveScroll: true, onFinish: () => { working.value = false } })
}

function remove() {
  const chart = pendingRemoval.value
  pendingRemoval.value = null
  router.delete(route('analytics.charts.destroy', chart.id), { preserveScroll: true })
}

const describe = (chart) => {
  const source = datasetByKey.value[chart.dataset]
  const measureLabel = source?.measures.find((item) => item.key === chart.measure)?.label
  const dimensionLabel = source?.dimensions.find((item) => item.key === chart.dimension)?.label
  return [source?.label, measureLabel && dimensionLabel ? `${measureLabel} por ${dimensionLabel.toLowerCase()}` : null, periodLabel(chart.period)].filter(Boolean).join(' · ')
}

const colourFields = reactive({})
</script>

<template>
  <div class="pl-page" data-template="page">
    <Head title="Análises" />
    <PageHeader :trail="[{ title: 'Análises' }]" title="Análises" lede="Gráficos que constrói sobre os dados do seu laboratório. Os valores são recalculados a cada visita e respeitam as suas permissões.">
      <template #actions>
        <button v-if="datasets.length" type="button" class="ds-button ds-button-primary" @click="openBuilder()">Novo gráfico<Plus aria-hidden="true" /></button>
      </template>
    </PageHeader>

    <div v-if="!datasets.length" class="ds-empty-state grid justify-items-start gap-2 p-6">
      <span class="pl-k">Sem dados disponíveis</span>
      <p class="text-sm text-[var(--pl-muted)]">A sua conta ainda não tem acesso a nenhum conjunto de dados. Peça ao administrador as permissões dos módulos que quer acompanhar.</p>
    </div>

    <section v-else-if="!charts.length" class="pl-board-empty" aria-labelledby="board-empty-title">
      <div>
        <h2 id="board-empty-title" class="pl-d3">Comece por um gráfico</h2>
        <p class="pl-lede mt-2">Escolha um ponto de partida ou construa o seu: um conjunto de dados, uma medida, um agrupamento e a forma do gráfico.</p>
      </div>
      <ul class="pl-board-starters">
        <li v-for="starter in availableStarters" :key="starter.title">
          <button type="button" class="pl-board-starter" @click="openBuilder(null, starter)">
            <span class="pl-k pl-muted">{{ datasetByKey[starter.dataset].label }}</span>
            <span class="font-semibold">{{ starter.title }}</span>
            <Plus aria-hidden="true" />
          </button>
        </li>
        <li>
          <button type="button" class="pl-board-starter" @click="openBuilder()">
            <span class="pl-k pl-muted">Em branco</span>
            <span class="font-semibold">Construir de raiz</span>
            <Plus aria-hidden="true" />
          </button>
        </li>
      </ul>
    </section>

    <section v-else class="pl-board" aria-label="Gráficos do painel">
      <article v-for="(chart, index) in charts" :key="chart.id" class="pl-panel pl-board-card" :data-kind="chart.kind">
        <header class="pl-panel-head">
          <div class="min-w-0">
            <h2 class="truncate text-[15px] font-semibold">{{ chart.title }}</h2>
            <p class="pl-k pl-muted mt-1 truncate">{{ describe(chart) }}</p>
          </div>
          <Menu as="div" class="relative flex-none">
            <MenuButton class="ds-icon-button" :aria-label="`Acções de ${chart.title}`"><Ellipsis aria-hidden="true" /></MenuButton>
            <transition enter-active-class="transition-[opacity,translate] duration-150 ease-[cubic-bezier(0.23,1,0.32,1)]" enter-from-class="-translate-y-1 opacity-0" leave-active-class="transition-opacity duration-100 ease-out" leave-to-class="opacity-0">
              <MenuItems class="ds-floating-panel absolute right-0 z-20 mt-1 w-56 origin-top-right focus:outline-none">
                <MenuItem v-slot="{ active }"><button type="button" class="pl-menu-item" :data-active="active" @click="openBuilder(chart)"><Pencil aria-hidden="true" />Editar</button></MenuItem>
                <MenuItem v-slot="{ active, disabled }" :disabled="index === 0 || working"><button type="button" class="pl-menu-item" :data-active="active" :disabled="disabled" @click="move(chart, -1)"><ArrowUp aria-hidden="true" />Mover para cima</button></MenuItem>
                <MenuItem v-slot="{ active, disabled }" :disabled="index === charts.length - 1 || working"><button type="button" class="pl-menu-item" :data-active="active" :disabled="disabled" @click="move(chart, 1)"><ArrowDown aria-hidden="true" />Mover para baixo</button></MenuItem>
                <div class="pl-menu-sep" />
                <MenuItem v-slot="{ active }"><button type="button" class="pl-menu-item pl-menu-item-danger" :data-active="active" @click="pendingRemoval = chart"><Trash2 aria-hidden="true" />Remover do painel</button></MenuItem>
              </MenuItems>
            </transition>
          </Menu>
        </header>
        <div class="p-4">
          <p v-if="chart.unavailable" class="pl-banner pl-banner-bad" role="status">{{ chart.unavailable }}</p>
          <PlanoChart
            v-else
            :kind="chart.kind"
            :label="chart.title"
            :categories="chart.data.categories"
            :series="chart.data.series"
            :format="chart.data.format"
            :unit="chart.data.unit"
            :colors="chart.colors"
            :height="260"
            empty-text="Sem registos neste período."
          />
        </div>
      </article>
    </section>

    <SlideOver v-if="builderOpen" :title="editing ? 'Editar gráfico' : 'Novo gráfico'" description="As escolhas que não se combinam ficam indisponíveis, com o motivo." :disabled="form.processing" @close="closeBuilder">
      <template #content>
        <form id="chart-builder" class="pl-builder" @submit.prevent="save">
          <div class="pl-builder-fields">
            <div class="ds-field-group">
              <label for="chart-title" class="ds-field-label">Título</label>
              <BaseInput id="chart-title" v-model="form.title" maxlength="120" placeholder="Ex.: Amostras recebidas por semana" :aria-invalid="Boolean(form.errors.title)" />
              <p v-if="form.errors.title" class="ds-field-error" role="alert">{{ form.errors.title }}</p>
            </div>

            <BaseSelect :model-value="form.dataset" :options="datasetOptions" label="Dados" :hint="dataset?.description" :error="form.errors.dataset" @update:model-value="chooseDataset" />
            <BaseSelect v-model="form.measure" :options="measureOptions" label="Medida" :error="form.errors.measure" />
            <div class="grid gap-4 sm:grid-cols-2">
              <BaseSelect v-model="form.dimension" :options="dimensionOptions" label="Agrupar por" :error="form.errors.dimension" />
              <BaseSelect :model-value="form.split || ''" :options="splitOptions" label="Dividir em séries" :error="form.errors.split" @update:model-value="form.split = $event || null" />
            </div>

            <fieldset class="grid gap-2">
              <legend class="ds-field-label">Forma</legend>
              <div class="pl-segmented" role="radiogroup" aria-label="Forma do gráfico">
                <button
                  v-for="kind in kinds"
                  :key="kind.key"
                  type="button"
                  role="radio"
                  :aria-checked="form.kind === kind.key"
                  :disabled="Boolean(kindProblem(kind.key))"
                  :title="kindProblem(kind.key) || kind.label"
                  @click="form.kind = kind.key"
                >{{ kind.label }}</button>
              </div>
              <p v-if="form.errors.kind" class="ds-field-error" role="alert">{{ form.errors.kind }}</p>
            </fieldset>

            <fieldset class="grid gap-2">
              <legend class="ds-field-label">Período</legend>
              <div class="pl-segmented" role="radiogroup" aria-label="Período">
                <button v-for="period in periods" :key="period.key" type="button" role="radio" :aria-checked="form.period === period.key" @click="form.period = period.key">{{ period.label }}</button>
              </div>
            </fieldset>

            <fieldset v-if="colourTargets.length" class="grid gap-2">
              <legend class="ds-field-label">Cores</legend>
              <p class="ds-field-hint">As cores da paleta mantêm-se distinguíveis entre si, também para quem vê as cores de outra forma.</p>
              <ul class="pl-builder-colours">
                <li v-for="(name, index) in colourTargets" :key="name">
                  <span class="min-w-0 truncate text-sm">{{ name }}</span>
                  <div class="flex flex-wrap items-center gap-1" role="radiogroup" :aria-label="`Cor de ${name}`">
                    <button
                      v-for="swatch in palette"
                      :key="swatch"
                      type="button"
                      role="radio"
                      class="pl-swatch"
                      :style="{ background: swatch }"
                      :aria-checked="effectiveColour(name, index) === swatch"
                      :aria-label="swatch"
                      @click="setColour(name, swatch)"
                    />
                    <ColorInput v-model="colourFields[name]" :value="form.colors[name] ?? palette[Math.min(index, palette.length - 1)]" :aria-label="`Cor personalizada para ${name}`" @update:model-value="setColour(name, $event)" />
                    <button v-if="form.colors[name]" type="button" class="ds-icon-button" :aria-label="`Repor a cor de ${name}`" @click="resetColour(name)"><RotateCcw aria-hidden="true" /></button>
                  </div>
                </li>
              </ul>
              <p v-for="warning in colourWarnings" :key="warning" class="pl-banner pl-banner-warn text-sm" role="status">{{ warning }}</p>
              <p v-if="form.errors.colors" class="ds-field-error" role="alert">{{ form.errors.colors }}</p>
            </fieldset>
          </div>

          <section class="pl-builder-preview" aria-label="Pré-visualização" aria-live="polite">
            <p class="pl-k pl-muted" :class="singleColumn ? 'mb-1' : 'mb-3'">Pré-visualização · {{ periodLabel(form.period) }}</p>
            <p v-if="previewError" class="pl-banner pl-banner-bad" role="alert">{{ previewError }}</p>
            <PlanoChart
              v-else
              :kind="form.kind"
              :label="form.title || 'Pré-visualização'"
              :categories="preview?.categories ?? []"
              :series="preview?.series ?? []"
              :format="preview?.format ?? 'count'"
              :unit="preview?.unit ?? ''"
              :colors="form.colors"
              :loading="previewLoading"
              :height="singleColumn ? 150 : 240"
              empty-text="Sem registos neste período. Experimente um período mais longo."
            />
          </section>
        </form>
      </template>
      <template #action_buttons>
        <div class="flex w-full justify-end gap-2">
          <button type="button" class="ds-button ds-button-quiet" :disabled="form.processing" @click="closeBuilder">Cancelar</button>
          <button type="submit" form="chart-builder" class="ds-button ds-button-primary" :disabled="form.processing">{{ form.processing ? 'A guardar…' : editing ? 'Guardar alterações' : 'Adicionar ao painel' }}</button>
        </div>
      </template>
    </SlideOver>

    <ConfirmDialog
      v-if="pendingRemoval"
      :title="`Remover ${pendingRemoval.title}?`"
      description="O gráfico sai do painel. Os dados não são afectados."
      confirm="Remover"
      variant="danger"
      @confirmed="remove"
      @canceled="pendingRemoval = null"
    />
  </div>
</template>
