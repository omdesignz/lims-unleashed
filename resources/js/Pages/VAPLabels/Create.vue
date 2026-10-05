<template>
  <div class="pl-page space-y-6">
    <PageHeader
      :trail="[{ title: 'Etiquetas', url: route('vap_labels.labels.index') }, { title: pageTitle }]"
      :title="form.name || pageTitle"
      :lede="pageDescription"
    >
      <template #actions>
        <Link
          :href="props.label ? route('vap_labels.labels.show', props.label.id) : route('vap_labels.labels.index')"
          class="ds-button ds-button-secondary"
        >
          <ArrowLeftIcon class="h-4 w-4" aria-hidden="true" />
          {{ $t('gestlab.general.labels.vap_labels.buttons.cancel') }}
        </Link>
        <button type="button" class="ds-button ds-button-primary" :disabled="form.processing" @click="submit">
          <CheckIcon class="h-4 w-4" aria-hidden="true" />
          {{ form.processing ? $t('gestlab.general.labels.vap_labels.buttons.processing') : submitLabel }}
        </button>
      </template>
    </PageHeader>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
      <div class="min-w-0 space-y-6">
        <!-- The label itself, first and widest. -->
        <section class="pl-panel min-w-0" aria-label="Etiqueta">
          <header class="pl-panel-head flex-wrap gap-3">
            <div>
              <h2 class="pl-k">{{ $t('gestlab.general.labels.vap_labels.editor.preview_title') }}</h2>
              <p class="mt-1 text-xs text-[var(--pl-muted)]">{{ labelWidth }} × {{ labelHeight }} mm · {{ zoomCaption }}</p>
            </div>
            <div class="pl-segmented" role="radiogroup" aria-label="Escala da etiqueta">
              <button v-for="option in zoomOptions" :key="option.value" type="button" role="radio" :aria-checked="zoom === option.value" @click="zoom = option.value">
                {{ option.label }}
              </button>
            </div>
          </header>

          <div ref="stage" class="label-stage min-h-[22rem] p-8">
            <LabelPreview :label="form" :values="previewValues" :scale="stageScale" :title="form.name" empty-text="Escreva o conteúdo da etiqueta" />
          </div>

          <footer class="border-t border-[var(--pl-line)] px-4 py-3 text-xs text-[var(--pl-muted)]">
            <template v-if="props.sourcePreview">
              Marcadores preenchidos com <strong class="text-[var(--pl-fg)]">{{ props.sourcePreview.code || props.sourcePreview.name }}</strong>.
            </template>
            <template v-else>
              Marcadores preenchidos com valores de exemplo. Escolha um registo de origem para os ver com dados reais.
            </template>
          </footer>
        </section>

        <!-- Where the label comes from: the record it labels and a model to start from. -->
        <div class="grid gap-6 lg:grid-cols-2" aria-label="Origem e modelo">
          <section class="pl-panel">
            <header class="pl-panel-head"><h2 class="pl-k">{{ $t('gestlab.general.labels.vap_labels.editor.source_title') }}</h2></header>
            <div class="space-y-4 p-4">
              <div class="grid gap-2">
                <button
                  v-for="option in sourceTypeOptions"
                  :key="option.value"
                  type="button"
                  class="border px-3 py-2.5 text-left"
                  :class="form.source_type === option.value ? 'border-[var(--pl-accent)] bg-[var(--pl-layer)]' : 'border-[var(--pl-line)] hover:bg-[var(--pl-layer)]'"
                  :aria-pressed="form.source_type === option.value"
                  @click="selectSourceType(option.value)"
                >
                  <span class="block text-sm font-bold text-[var(--pl-fg)]">{{ option.label }}</span>
                  <span class="mt-0.5 block text-xs text-[var(--pl-muted)]">{{ option.description }}</span>
                </button>
              </div>
              <BaseSelect
                v-if="availableSources.length"
                v-model="form.source_id"
                :label="$t('gestlab.general.labels.vap_labels.editor.source_record')"
                @update:model-value="loadSourcePreview"
              >
                <option value="">{{ $t('gestlab.general.labels.vap_labels.editor.select_source') }}</option>
                <option v-for="source in availableSources" :key="source.id" :value="source.id">{{ source.label }}</option>
              </BaseSelect>
              <p v-else-if="form.source_type" class="text-xs text-[var(--pl-muted)]">Sem registos deste tipo neste laboratório.</p>
            </div>
          </section>

          <section class="pl-panel">
            <header class="pl-panel-head">
              <h2 class="pl-k">{{ $t('gestlab.general.labels.vap_labels.editor.template_title') }}</h2>
              <span class="text-xs text-[var(--pl-muted)]">{{ templatesList.length }}</span>
            </header>
            <ul v-if="templatesList.length" class="max-h-80 divide-y divide-[var(--pl-line)] overflow-y-auto">
              <li v-for="template in templatesList" :key="template.id">
                <button
                  type="button"
                  class="w-full px-4 py-3 text-left hover:bg-[var(--pl-layer)]"
                  :aria-pressed="selectedTemplate?.id === template.id"
                  :class="selectedTemplate?.id === template.id ? 'bg-[var(--pl-layer)]' : ''"
                  @click="applyTemplate(template)"
                >
                  <span class="flex items-start justify-between gap-2">
                    <span class="text-sm font-bold text-[var(--pl-fg)]">{{ template.name }}</span>
                    <StatusChip v-if="template.is_featured" tone="wait">{{ $t('gestlab.general.labels.vap_labels.featured') }}</StatusChip>
                  </span>
                  <span v-if="template.description" class="mt-1 block text-xs text-[var(--pl-muted)]">{{ template.description }}</span>
                </button>
              </li>
            </ul>
            <p v-else class="px-4 py-3 text-sm text-[var(--pl-muted)]">Sem modelos activos.</p>
          </section>

        </div>
      </div>

      <!-- Everything the label prints, in the order a person reads it. -->
      <aside class="pl-panel divide-y divide-[var(--pl-line)] xl:self-start" aria-label="Propriedades da etiqueta">
        <section class="space-y-4 p-4">
          <h2 class="pl-k">{{ $t('gestlab.general.labels.vap_labels.editor.identity_title') }}</h2>
          <BaseInput
            v-model="form.name"
            :label="$t('gestlab.general.labels.vap_labels.name')"
            :placeholder="$t('gestlab.general.labels.vap_labels.name_placeholder')"
            :error="form.errors.name"
            required
          />
          <BaseSelect v-model="form.type" :label="$t('gestlab.general.labels.vap_labels.type')" :error="form.errors.type" required>
            <option value="equipment">{{ $t('gestlab.general.labels.vap_labels.types.equipment') }}</option>
            <option value="material">{{ $t('gestlab.general.labels.vap_labels.types.material') }}</option>
            <option value="sample">{{ $t('gestlab.general.labels.vap_labels.types.sample') }}</option>
            <option value="custom">{{ $t('gestlab.general.labels.vap_labels.types.custom') }}</option>
          </BaseSelect>
          <div class="grid grid-cols-2 gap-3">
            <BaseInput v-model="form.width" type="number" step="0.1" min="1" max="1000" :label="`${$t('gestlab.general.labels.vap_labels.width')} (mm)`" :error="form.errors.width" required />
            <BaseInput v-model="form.height" type="number" step="0.1" min="1" max="1000" :label="`${$t('gestlab.general.labels.vap_labels.height')} (mm)`" :error="form.errors.height" required />
          </div>
          <div class="flex flex-wrap gap-2" aria-label="Formatos comuns">
            <button
              v-for="size in commonSizes"
              :key="size.label"
              type="button"
              class="pl-chip"
              :class="Number(form.width) === size.width && Number(form.height) === size.height ? 'pl-chip-run' : ''"
              @click="setSize(size)"
            >
              {{ size.label }}
            </button>
          </div>
          <div class="grid gap-3 border-t border-[var(--pl-line)] pt-4">
            <div>
              <p class="ds-field-label">{{ $t('gestlab.general.labels.vap_labels.lab') }}</p>
              <p class="mt-1.5 text-sm font-bold text-[var(--pl-fg)]">{{ labsList[0]?.name || 'Laboratório indisponível' }}</p>
            </div>
            <BaseSelect v-model="form.department_id" :label="$t('gestlab.general.labels.vap_labels.department')">
              <option value="">{{ $t('gestlab.general.labels.vap_labels.select_department') }}</option>
              <option v-for="dept in departmentsList" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
            </BaseSelect>
          </div>
        </section>

        <section class="space-y-3 p-4">
          <h2 class="pl-k">{{ $t('gestlab.general.labels.vap_labels.editor.content_title') }}</h2>
          <BaseTextarea
            v-model="form.content"
            rows="4"
            :label="$t('gestlab.general.labels.vap_labels.content')"
            :placeholder="$t('gestlab.general.labels.vap_labels.content_placeholder')"
            :error="form.errors.content"
            required
          />
          <div class="flex flex-wrap gap-1.5" :aria-label="$t('gestlab.general.labels.vap_labels.template_variables')">
            <button
              v-for="placeholder in supportedPlaceholders"
              :key="placeholder"
              type="button"
              class="pl-chip pl-chip-plain"
              :title="`Inserir ${placeholder}`"
              @click="insertPlaceholder(placeholder)"
            >
              {{ placeholder }}
            </button>
          </div>
        </section>

        <section class="space-y-4 p-4">
          <h2 class="pl-k">{{ $t('gestlab.general.labels.vap_labels.editor.appearance_title') }}</h2>
          <div class="grid gap-3">
            <div v-for="colorControl in colorControls" :key="colorControl.field" class="flex items-center gap-3">
              <ColorInput v-model="form[colorControl.field]" type="color" class="h-9 w-12 shrink-0 cursor-pointer border border-[var(--pl-line)] p-0.5" :aria-label="colorControl.label" />
              <BaseInput v-model="form[colorControl.field]" class="flex-1" :label="colorControl.label" :placeholder="colorControl.placeholder" :error="form.errors[colorControl.field]" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="ds-field-label" for="label-font-size">{{ $t('gestlab.general.labels.vap_labels.font_size') }} · {{ form.font_size }} px</label>
              <RangeInput id="label-font-size" v-model="form.font_size" type="range" min="6" max="72" class="mt-3 w-full" />
            </div>
            <div>
              <label class="ds-field-label" for="label-border-width">{{ $t('gestlab.general.labels.vap_labels.border_width') }} · {{ form.border_width }} px</label>
              <RangeInput id="label-border-width" v-model="form.border_width" type="range" min="0" max="10" class="mt-3 w-full" />
            </div>
          </div>
          <div>
            <p class="ds-field-label">{{ $t('gestlab.general.labels.vap_labels.text_alignment') }}</p>
            <div class="pl-segmented mt-1.5" role="radiogroup" :aria-label="$t('gestlab.general.labels.vap_labels.text_alignment')">
              <button v-for="align in alignmentOptions" :key="align.value" type="button" role="radio" :aria-checked="form.text_alignment === align.value" @click="form.text_alignment = align.value">
                {{ align.label }}
              </button>
            </div>
          </div>
        </section>

        <section class="space-y-3 p-4">
          <h2 class="pl-k">{{ $t('gestlab.general.labels.vap_labels.editor.traceability_title') }}</h2>
          <div v-for="element in advancedElements" :key="element.key" class="border border-[var(--pl-line)]">
            <ToggleField :id="`label-${element.key}`" v-model="form[element.enabledField]" :label="element.label" :description="element.description" />
            <div v-if="form[element.enabledField]" class="grid grid-cols-2 gap-3 border-t border-[var(--pl-line)] p-3">
              <BaseInput v-model="form[element.contentField]" class="col-span-2" :label="element.contentLabel" :placeholder="element.placeholder" />
              <BaseInput v-model="form[element.sizeField]" type="number" min="1" max="80" :label="`${element.sizeLabel} (mm)`" />
              <BaseInput v-if="element.secondarySizeField" v-model="form[element.secondarySizeField]" type="number" min="1" max="80" :label="`${element.secondarySizeLabel} (mm)`" />
            </div>
          </div>
          <div class="border border-[var(--pl-line)]">
            <ToggleField
              id="label-logo"
              :model-value="form.logo_path !== null"
              :label="$t('gestlab.general.labels.vap_labels.editor.logo_title')"
              :description="$t('gestlab.general.labels.vap_labels.editor.logo_description')"
              @update:model-value="form.logo_path = $event ? '' : null"
            />
            <div v-if="form.logo_path !== null" class="grid grid-cols-[minmax(0,1fr)_6rem] gap-3 border-t border-[var(--pl-line)] p-3">
              <BaseInput v-model="form.logo_path" :label="$t('gestlab.general.labels.vap_labels.logo_file')" placeholder="/storage/media/logo.png" />
              <BaseInput v-model="form.logo_size" type="number" min="1" max="80" :label="`${$t('gestlab.general.labels.vap_labels.logo_size')} (mm)`" />
            </div>
          </div>
        </section>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import { ArrowLeft as ArrowLeftIcon, Check as CheckIcon } from '@lucide/vue'
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import BaseTextarea from '@/Components/base/BaseTextarea.vue'
import ToggleField from '@/Components/base/ToggleField.vue'
import LabelPreview from '@/Components/labels/LabelPreview.vue'
import { labelExampleValues } from '@/Support/label-codes.mjs'
import PageHeader from '@/Components/plano/PageHeader.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'

const props = defineProps({
  templates: { type: Array, default: () => [] },
  selectedTemplateId: { type: Number, default: null },
  labs: { type: Array, default: () => [] },
  departments: { type: Array, default: () => [] },
  defaultSettings: { type: Object, default: () => ({}) },
  label: { type: Object, default: null },
  sourcePreview: { type: Object, default: null },
  sourceOptions: { type: Object, default: () => ({}) },
  supportedPlaceholders: { type: Array, default: () => ['{name}', '{code}', '{lot}'] },
})

const selectedTemplate = ref(null)
const templatesList = computed(() => Array.isArray(props.templates) ? props.templates : [])
const labsList = computed(() => Array.isArray(props.labs) ? props.labs : [])
const departmentsList = computed(() => Array.isArray(props.departments) ? props.departments : [])

const pageTitle = computed(() => props.label
  ? trans('gestlab.general.labels.vap_labels.edit_title')
  : trans('gestlab.general.labels.vap_labels.create_title'))
const pageDescription = computed(() => props.label
  ? trans('gestlab.general.labels.vap_labels.edit_description')
  : trans('gestlab.general.labels.vap_labels.create_description'))
const submitLabel = computed(() => props.label
  ? trans('gestlab.general.labels.vap_labels.buttons.update_label')
  : trans('gestlab.general.labels.vap_labels.buttons.save_label'))

const sourceTypeOptions = computed(() => [
  { value: 'sample_entry', label: trans('gestlab.general.labels.vap_labels.editor.source_sample'), description: trans('gestlab.general.labels.vap_labels.editor.source_sample_description') },
  { value: 'equipment', label: trans('gestlab.general.labels.vap_labels.editor.source_equipment'), description: trans('gestlab.general.labels.vap_labels.editor.source_equipment_description') },
  { value: 'reagent', label: trans('gestlab.general.labels.vap_labels.editor.source_reagent'), description: trans('gestlab.general.labels.vap_labels.editor.source_reagent_description') },
])

const advancedElements = computed(() => [
  {
    key: 'qr',
    label: trans('gestlab.general.labels.vap_labels.qr_code'),
    description: trans('gestlab.general.labels.vap_labels.qr_code_description'),
    enabledField: 'has_qr_code',
    contentField: 'qr_code_content',
    sizeField: 'qr_code_size',
    contentLabel: trans('gestlab.general.labels.vap_labels.qr_content'),
    sizeLabel: trans('gestlab.general.labels.vap_labels.qr_code_size'),
    placeholder: '{code}',
  },
  {
    key: 'barcode',
    label: trans('gestlab.general.labels.vap_labels.barcode'),
    description: trans('gestlab.general.labels.vap_labels.barcode_description'),
    enabledField: 'has_barcode',
    contentField: 'barcode_content',
    sizeField: 'barcode_width',
    secondarySizeField: 'barcode_height',
    contentLabel: trans('gestlab.general.labels.vap_labels.barcode_content'),
    sizeLabel: trans('gestlab.general.labels.vap_labels.barcode_width'),
    secondarySizeLabel: trans('gestlab.general.labels.vap_labels.barcode_height'),
    placeholder: '{code}',
  },
])

const alignmentOptions = computed(() => [
  { value: 'left', label: trans('gestlab.general.labels.vap_labels.align_left') },
  { value: 'center', label: trans('gestlab.general.labels.vap_labels.align_center') },
  { value: 'right', label: trans('gestlab.general.labels.vap_labels.align_right') },
  { value: 'justify', label: trans('gestlab.general.labels.vap_labels.align_justify') },
])

const colorControls = computed(() => [
  { field: 'background_color', label: trans('gestlab.general.labels.vap_labels.background_color'), placeholder: '#ffffff' },
  { field: 'text_color', label: trans('gestlab.general.labels.vap_labels.text_color'), placeholder: '#000000' },
  { field: 'border_color', label: trans('gestlab.general.labels.vap_labels.border_color'), placeholder: '#000000' },
])

// Formats sold for laboratory label printers, width × height in mm.
const commonSizes = [
  { label: '25 × 13', width: 25, height: 13 },
  { label: '38 × 25', width: 38, height: 25 },
  { label: '50 × 25', width: 50, height: 25 },
  { label: '57 × 32', width: 57, height: 32 },
  { label: '70 × 37', width: 70, height: 37 },
  { label: '100 × 50', width: 100, height: 50 },
]

const form = useForm({
  name: props.label?.name || '',
  type: props.label?.type || 'custom',
  content: props.label?.content || '',
  width: props.label?.width || props.defaultSettings?.width || 50,
  height: props.label?.height || props.defaultSettings?.height || 25,
  background_color: props.label?.background_color || props.label?.template_data?.background_color || '#ffffff',
  text_color: props.label?.text_color || props.label?.template_data?.text_color || '#000000',
  font_size: props.label?.font_size || props.defaultSettings?.font_size || 12,
  border_width: props.label?.border_width ?? props.defaultSettings?.border_width ?? 1,
  border_color: props.label?.border_color || props.label?.template_data?.border_color || '#000000',
  text_alignment: props.label?.text_alignment || 'center',
  department_id: props.label?.department_id || null,
  logo_path: props.label?.logo_path ?? null,
  logo_size: props.label?.logo_size || null,
  has_qr_code: props.label?.has_qr_code || false,
  qr_code_content: props.label?.qr_code_content || null,
  qr_code_size: props.label?.qr_code_size || null,
  has_barcode: props.label?.has_barcode || false,
  barcode_content: props.label?.barcode_content || null,
  barcode_type: props.label?.barcode_type || 'CODE128',
  barcode_width: props.label?.barcode_width || null,
  barcode_height: props.label?.barcode_height || null,
  is_active: props.label?.is_active ?? true,
  source_type: props.label?.template_data?.source_type || props.sourcePreview?.source_type || null,
  source_id: props.label?.template_data?.source_id || props.sourcePreview?.source_id || null,
  template_id: props.label?.template_data?.template_id || null,
})

// A code switched on without a size gets one that fits this label, and reads {code}.
watch(() => form.has_qr_code, (enabled) => {
  if (enabled && !form.qr_code_size) {
    form.qr_code_size = Math.max(6, Math.round(Math.min(labelHeight.value - 4, 14)))
  }
  if (enabled && !form.qr_code_content) {
    form.qr_code_content = '{code}'
  }
})
watch(() => form.has_barcode, (enabled) => {
  if (enabled && !form.barcode_width) {
    form.barcode_width = Math.max(10, Math.round(Math.min(labelWidth.value - 4, 40)))
    form.barcode_height = Math.max(4, Math.round(Math.min(labelHeight.value / 3, 10)))
  }
  if (enabled && !form.barcode_content) {
    form.barcode_content = '{code}'
  }
})

const labelWidth = computed(() => Number(form.width) || 50)
const labelHeight = computed(() => Number(form.height) || 25)

// Real size on a 96 dpi screen, fitted to the stage, or twice the real size.
const REAL_PX_PER_MM = 96 / 25.4
const zoom = ref('fit')
const zoomOptions = [
  { value: 'fit', label: 'Ajustar' },
  { value: 'real', label: 'Tamanho real' },
  { value: 'double', label: '2×' },
]
// "Ajustar" fits the label to the stage as it is laid out on this screen.
const stage = ref(null)
const stageWidth = ref(560)
let stageObserver = null
onMounted(() => {
  if (typeof ResizeObserver !== 'undefined' && stage.value) {
    stageObserver = new ResizeObserver(([entry]) => { stageWidth.value = entry.contentRect.width })
    stageObserver.observe(stage.value)
  }
})
onBeforeUnmount(() => stageObserver?.disconnect())
const stageScale = computed(() => ({
  real: REAL_PX_PER_MM,
  double: REAL_PX_PER_MM * 2,
}[zoom.value] ?? Math.max(1, Math.min(stageWidth.value / labelWidth.value, 320 / labelHeight.value, REAL_PX_PER_MM * 6))))
const zoomCaption = computed(() => `${Math.round((stageScale.value / REAL_PX_PER_MM) * 100)} % do tamanho real`)

// What fills the placeholders: the chosen record, or recognisable example values.
const previewValues = computed(() => ({
  ...labelExampleValues(),
  ...Object.fromEntries(Object.entries(props.sourcePreview || {}).filter(([, value]) => value !== null && value !== '')),
}))

const availableSources = computed(() => {
  if (form.source_type === 'sample_entry' || form.source_type === 'sample') {
    return props.sourceOptions?.samples ?? []
  }

  if (form.source_type === 'equipment' || form.source_type === 'reagent') {
    return props.sourceOptions?.inventory ?? []
  }

  if (form.source_type === 'collection_product') {
    return props.sourceOptions?.collection_products ?? []
  }

  return []
})

const selectSourceType = (sourceType) => {
  form.source_type = form.source_type === sourceType ? null : sourceType
  form.source_id = null
}

// The server resolves the record; only the preview values are reloaded.
const loadSourcePreview = (sourceId) => {
  if (!sourceId || !form.source_type) {
    return
  }

  router.reload({
    data: { source_type: form.source_type, source_id: sourceId },
    only: ['sourcePreview'],
    preserveScroll: true,
    preserveState: true,
  })
}

const setSize = (size) => {
  form.width = size.width
  form.height = size.height
}

const insertPlaceholder = (placeholder) => {
  const content = String(form.content || '')
  form.content = content === '' || content.endsWith('\n') || content.endsWith(' ') ? `${content}${placeholder}` : `${content} ${placeholder}`
}

const applyTemplate = (template) => {
  selectedTemplate.value = template
  form.template_id = template.id

  if (template.template_data) {
    Object.keys(template.template_data).forEach((key) => {
      if (key in form) {
        form[key] = template.template_data[key]
      }
    })
  }
}

onMounted(() => {
  if (props.label || !props.selectedTemplateId) {
    return
  }

  const preselectedTemplate = templatesList.value.find((template) => template.id === props.selectedTemplateId)

  if (preselectedTemplate) {
    applyTemplate(preselectedTemplate)
  }
})

const submit = () => {
  if (props.label) {
    form.put(route('vap_labels.labels.update', props.label.id))
    return
  }

  form.post(route('vap_labels.labels.store'))
}
</script>
