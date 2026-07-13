<script setup>
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import BaseTextarea from '@/Components/base/BaseTextarea.vue'
import { Link, useForm } from '@inertiajs/vue3'
import {
  ArrowLeftIcon,
  CheckIcon,
  DocumentTextIcon,
  EyeIcon,
} from '@heroicons/vue/24/outline'
import { computed, ref } from 'vue'

const props = defineProps({
  template: {
    type: Object,
    default: null,
  },
  categories: {
    type: Object,
    default: () => ({}),
  },
  labs: {
    type: Array,
    default: () => [],
  },
})

const isEditing = computed(() => Boolean(props.template?.id))
const activeSection = ref('identity')
const selectedLab = ref('')
const selectedLabel = ref('')
const selectedLabelData = ref(null)
const availableLabels = ref([])

const sections = [
  { value: 'identity', label: 'Identificação' },
  { value: 'design', label: 'Conteúdo e design' },
  { value: 'traceability', label: 'Rastreabilidade' },
]

const templateData = props.template?.template_data || {}
const form = useForm({
  name: props.template?.name || '',
  description: props.template?.description || '',
  category: props.template?.category || '',
  template_data: {
    type: templateData.type || 'custom',
    content: templateData.content || '',
    width: templateData.width || 50,
    height: templateData.height || 25,
    background_color: templateData.background_color || '#ffffff',
    text_color: templateData.text_color || '#111827',
    font_size: templateData.font_size || 12,
    border_width: templateData.border_width ?? 1,
    border_color: templateData.border_color || '#111827',
    text_alignment: templateData.text_alignment || 'center',
    has_qr_code: Boolean(templateData.has_qr_code),
    qr_code_content: templateData.qr_code_content || '',
    qr_code_size: templateData.qr_code_size || 20,
    has_barcode: Boolean(templateData.has_barcode),
    barcode_content: templateData.barcode_content || '',
    barcode_type: templateData.barcode_type || 'CODE128',
    barcode_width: templateData.barcode_width || 40,
    barcode_height: templateData.barcode_height || 12,
    logo_path: templateData.logo_path || '',
    logo_size: templateData.logo_size || 16,
  },
  is_active: props.template?.is_active ?? true,
  is_featured: props.template?.is_featured ?? false,
})

const previewStyle = computed(() => {
  const width = Math.min(Math.max(Number(form.template_data.width) * 2, 120), 300)
  const height = Math.min(Math.max(Number(form.template_data.height) * 2, 64), 220)
  const align = form.template_data.text_alignment

  return {
    width: `${width}px`,
    height: `${height}px`,
    backgroundColor: form.template_data.background_color,
    color: form.template_data.text_color,
    fontSize: `${Math.min(Math.max(Number(form.template_data.font_size), 8), 28)}px`,
    borderWidth: `${form.template_data.border_width || 0}px`,
    borderColor: form.template_data.border_color,
    textAlign: align,
    justifyContent: align === 'left' ? 'flex-start' : align === 'right' ? 'flex-end' : 'center',
  }
})

async function loadLabels() {
  selectedLabel.value = ''
  selectedLabelData.value = null
  availableLabels.value = []

  if (!selectedLab.value) {
    return
  }

  const response = await fetch(route('vap_labels.templates.list', { lab_id: selectedLab.value }))
  availableLabels.value = response.ok ? await response.json() : []
}

async function loadLabelData() {
  selectedLabelData.value = null

  if (!selectedLabel.value) {
    return
  }

  const response = await fetch(route('vap_labels.labels.show', selectedLabel.value), {
    headers: { Accept: 'application/json' },
  })

  if (response.ok) {
    selectedLabelData.value = (await response.json()).label
  }
}

function applyLabelData() {
  if (!selectedLabelData.value) {
    return
  }

  const label = selectedLabelData.value
  form.template_data = {
    ...form.template_data,
    type: label.type,
    content: label.content,
    width: label.width,
    height: label.height,
    background_color: label.background_color,
    text_color: label.text_color,
    font_size: label.font_size,
    border_width: label.border_width,
    border_color: label.border_color,
    text_alignment: label.text_alignment,
    has_qr_code: Boolean(label.has_qr_code),
    qr_code_content: label.qr_code_content || '',
    qr_code_size: label.qr_code_size || 20,
    has_barcode: Boolean(label.has_barcode),
    barcode_content: label.barcode_content || '',
    barcode_type: label.barcode_type || 'CODE128',
    barcode_width: label.barcode_width || 40,
    barcode_height: label.barcode_height || 12,
    logo_path: label.logo_path || '',
    logo_size: label.logo_size || 16,
  }
  activeSection.value = 'design'
}

function submit() {
  if (isEditing.value) {
    form.put(route('vap_labels.label-templates.update', props.template.id))
    return
  }

  form.post(route('vap_labels.label-templates.store'))
}
</script>

<template>
  <form class="min-w-0 space-y-6" @submit.prevent="submit">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-4 px-5 py-5 lg:flex-row lg:items-start lg:justify-between lg:px-6">
        <div class="min-w-0 max-w-3xl">
          <p class="ds-kicker">Biblioteca de etiquetas</p>
          <div class="mt-2 flex items-start gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))]">
              <DocumentTextIcon class="h-5 w-5" />
            </span>
            <div>
              <h1 class="ds-heading text-2xl">
                {{ isEditing ? $t('gestlab.general.labels.vap_labels.templates.edit_title') : $t('gestlab.general.labels.vap_labels.templates.create_title') }}
              </h1>
              <p class="ds-copy mt-1 text-sm">
                {{ isEditing ? $t('gestlab.general.labels.vap_labels.templates.edit_description') : $t('gestlab.general.labels.vap_labels.templates.create_description') }}
              </p>
            </div>
          </div>
        </div>
        <Link :href="route('vap_labels.label-templates.index')" class="ds-button ds-button-secondary shrink-0">
          <ArrowLeftIcon class="h-4 w-4" />
          {{ $t('gestlab.general.labels.vap_labels.buttons.cancel') }}
        </Link>
      </div>

      <nav class="flex overflow-x-auto border-t border-[var(--ds-border)] px-3 sm:px-5" aria-label="Etapas do modelo de etiqueta">
        <button
          v-for="section in sections"
          :key="section.value"
          type="button"
          class="-mb-px min-h-12 shrink-0 border-b-2 px-4 text-sm font-bold transition"
          :class="activeSection === section.value ? 'border-[rgb(var(--primary-700-rgb))] text-[rgb(var(--primary-800-rgb))] dark:border-[rgb(var(--accent-200-rgb))] dark:text-[rgb(var(--accent-100-rgb))]' : 'border-transparent text-[var(--ds-text-muted)] hover:border-[var(--ds-border-strong)] hover:text-[var(--ds-text)]'"
          @click="activeSection = section.value"
        >
          {{ section.label }}
        </button>
      </nav>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
      <div class="space-y-6">
        <section v-show="activeSection === 'identity'" class="ds-panel overflow-hidden">
          <div class="ds-table-summary px-5 py-4">
            <div>
              <p class="ds-kicker">Identidade do modelo</p>
              <h2 class="ds-heading mt-1 text-base">Nome, categoria e âmbito de utilização</h2>
            </div>
          </div>
          <div class="grid gap-4 p-5 md:grid-cols-2">
            <BaseInput v-model="form.name" :label="$t('gestlab.general.labels.vap_labels.templates.name')" :error="form.errors.name" required />
            <BaseSelect v-model="form.category" :label="$t('gestlab.general.labels.vap_labels.templates.category')" :error="form.errors.category" required>
              <option value="">{{ $t('gestlab.general.labels.vap_labels.templates.select_category') }}</option>
              <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
            </BaseSelect>
            <BaseTextarea v-model="form.description" class="md:col-span-2" :label="$t('gestlab.general.labels.vap_labels.templates.description')" :error="form.errors.description" rows="4" />
          </div>

          <div v-if="!isEditing && labs.length" class="border-t border-[var(--ds-border)] p-5">
            <p class="text-sm font-bold text-[var(--ds-text)]">Partir de uma etiqueta existente</p>
            <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">Importe dimensões, conteúdo e recursos de rastreabilidade e depois ajuste o modelo.</p>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
              <BaseSelect v-model="selectedLab" :label="$t('gestlab.general.labels.vap_labels.lab')" @change="loadLabels">
                <option value="">{{ $t('gestlab.general.labels.vap_labels.templates.select_lab') }}</option>
                <option v-for="lab in labs" :key="lab.id" :value="lab.id">{{ lab.name }}</option>
              </BaseSelect>
              <BaseSelect v-model="selectedLabel" :label="$t('gestlab.general.labels.vap_labels.templates.select_label')" :disabled="!availableLabels.length" @change="loadLabelData">
                <option value="">{{ $t('gestlab.general.labels.vap_labels.templates.select_label') }}</option>
                <option v-for="label in availableLabels" :key="label.id" :value="label.id">{{ label.name }}</option>
              </BaseSelect>
            </div>
            <div v-if="selectedLabelData" class="mt-4 flex flex-col gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <p class="text-sm font-bold text-[var(--ds-text)]">{{ selectedLabelData.name }}</p>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ selectedLabelData.width }} × {{ selectedLabelData.height }} mm</p>
              </div>
              <button type="button" class="ds-button ds-button-secondary" @click="applyLabelData">Aplicar dados</button>
            </div>
          </div>
        </section>

        <section v-show="activeSection === 'design'" class="ds-panel overflow-hidden">
          <div class="ds-table-summary px-5 py-4">
            <div>
              <p class="ds-kicker">Conteúdo e design</p>
              <h2 class="ds-heading mt-1 text-base">Formato físico e hierarquia visual</h2>
            </div>
          </div>
          <div class="grid gap-4 p-5 md:grid-cols-2">
            <BaseTextarea v-model="form.template_data.content" class="md:col-span-2" :label="$t('gestlab.general.labels.vap_labels.content')" :error="form.errors['template_data.content']" rows="5" required />
            <BaseInput v-model="form.template_data.width" type="number" min="1" max="1000" step="0.1" :label="$t('gestlab.general.labels.vap_labels.width')" />
            <BaseInput v-model="form.template_data.height" type="number" min="1" max="1000" step="0.1" :label="$t('gestlab.general.labels.vap_labels.height')" />

            <label v-for="control in [{ field: 'background_color', label: $t('gestlab.general.labels.vap_labels.background_color') }, { field: 'text_color', label: $t('gestlab.general.labels.vap_labels.text_color') }, { field: 'border_color', label: $t('gestlab.general.labels.vap_labels.border_color') }]" :key="control.field" class="ds-field-group">
              <span class="ds-field-label">{{ control.label }}</span>
              <span class="flex gap-2">
                <ColorInput v-model="form.template_data[control.field]" type="color" class="h-10 w-12 shrink-0 cursor-pointer rounded-md border border-[var(--ds-border)] bg-[var(--ds-panel)] p-1" />
                <BaseInput v-model="form.template_data[control.field]" type="text" class="ds-field font-mono" />
              </span>
            </label>

            <BaseSelect v-model="form.template_data.text_alignment" :label="$t('gestlab.general.labels.vap_labels.text_alignment')">
              <option value="left">Esquerda</option>
              <option value="center">Centro</option>
              <option value="right">Direita</option>
              <option value="justify">Justificado</option>
            </BaseSelect>

            <label class="ds-field-group">
              <span class="ds-field-label">{{ $t('gestlab.general.labels.vap_labels.font_size') }} · {{ form.template_data.font_size }}px</span>
              <RangeInput v-model="form.template_data.font_size" type="range" min="6" max="72" class="mt-2 w-full accent-[rgb(var(--primary-700-rgb))]" />
            </label>
            <label class="ds-field-group">
              <span class="ds-field-label">{{ $t('gestlab.general.labels.vap_labels.border_width') }} · {{ form.template_data.border_width }}px</span>
              <RangeInput v-model="form.template_data.border_width" type="range" min="0" max="10" class="mt-2 w-full accent-[rgb(var(--primary-700-rgb))]" />
            </label>
          </div>
        </section>

        <section v-show="activeSection === 'traceability'" class="ds-panel overflow-hidden">
          <div class="ds-table-summary px-5 py-4">
            <div>
              <p class="ds-kicker">Rastreabilidade</p>
              <h2 class="ds-heading mt-1 text-base">QR, código de barras e identidade laboratorial</h2>
            </div>
          </div>
          <div class="divide-y divide-[var(--ds-border)]">
            <div class="grid gap-4 p-5 md:grid-cols-[12rem_minmax(0,1fr)]">
              <label class="flex items-center gap-3 text-sm font-bold text-[var(--ds-text)]"><CheckboxInput v-model="form.template_data.has_qr_code" type="checkbox" class="ds-checkbox" /> Código QR</label>
              <div v-if="form.template_data.has_qr_code" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_10rem]">
                <BaseInput v-model="form.template_data.qr_code_content" label="Conteúdo QR" />
                <BaseInput v-model="form.template_data.qr_code_size" type="number" min="1" max="80" label="Tamanho (mm)" />
              </div>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-[12rem_minmax(0,1fr)]">
              <label class="flex items-center gap-3 text-sm font-bold text-[var(--ds-text)]"><CheckboxInput v-model="form.template_data.has_barcode" type="checkbox" class="ds-checkbox" /> Código de barras</label>
              <div v-if="form.template_data.has_barcode" class="grid gap-3 sm:grid-cols-2">
                <BaseInput v-model="form.template_data.barcode_content" label="Conteúdo" />
                <BaseSelect v-model="form.template_data.barcode_type" label="Simbologia"><option value="CODE128">CODE128</option><option value="EAN13">EAN-13</option><option value="CODE39">CODE39</option></BaseSelect>
                <BaseInput v-model="form.template_data.barcode_width" type="number" min="1" max="80" label="Largura (mm)" />
                <BaseInput v-model="form.template_data.barcode_height" type="number" min="1" max="80" label="Altura (mm)" />
              </div>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-[12rem_minmax(0,1fr)]">
              <span class="text-sm font-bold text-[var(--ds-text)]">Logótipo</span>
              <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_10rem]">
                <BaseInput v-model="form.template_data.logo_path" label="Caminho do ficheiro" placeholder="/storage/media/logo.svg" />
                <BaseInput v-model="form.template_data.logo_size" type="number" min="1" max="80" label="Tamanho (mm)" />
              </div>
            </div>
          </div>
        </section>
      </div>

      <aside class="space-y-4 xl:sticky xl:top-20 xl:self-start">
        <section class="ds-panel overflow-hidden">
          <div class="flex items-center gap-2 border-b border-[var(--ds-border)] px-5 py-4">
            <EyeIcon class="h-4 w-4 text-[rgb(var(--primary-700-rgb))]" />
            <h2 class="ds-heading text-base">{{ $t('gestlab.general.labels.vap_labels.templates.preview') }}</h2>
          </div>
          <div class="bg-[var(--ds-panel-subtle)] p-5">
            <div class="flex min-h-52 items-center justify-center overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-4">
              <div class="relative flex items-center overflow-hidden whitespace-pre-line p-3" :style="previewStyle">
                <span v-if="form.template_data.has_qr_code" class="absolute left-2 top-2 grid h-8 w-8 place-items-center border border-current bg-white text-[0.55rem] font-bold text-slate-900">QR</span>
                <span v-if="form.template_data.has_barcode" class="absolute bottom-2 left-2 border border-current bg-white px-2 py-1 text-[0.5rem] font-bold tracking-widest text-slate-900">||||||||</span>
                <span class="w-full">{{ form.template_data.content || $t('gestlab.general.labels.vap_labels.templates.sample_content') }}</span>
              </div>
            </div>
            <p class="mt-3 text-center text-xs font-bold text-[var(--ds-text-muted)]">{{ form.template_data.width }} × {{ form.template_data.height }} mm</p>
          </div>
        </section>

        <section class="ds-panel p-5">
          <p class="ds-kicker">Disponibilidade</p>
          <div class="mt-4 space-y-3">
            <label class="flex items-start justify-between gap-4 rounded-lg border border-[var(--ds-border)] p-3">
              <span><span class="block text-sm font-bold text-[var(--ds-text)]">Modelo ativo</span><span class="mt-1 block text-xs font-semibold text-[var(--ds-text-muted)]">Disponível no editor de etiquetas.</span></span>
              <CheckboxInput v-model="form.is_active" type="checkbox" class="ds-checkbox mt-0.5" />
            </label>
            <label class="flex items-start justify-between gap-4 rounded-lg border border-[var(--ds-border)] p-3">
              <span><span class="block text-sm font-bold text-[var(--ds-text)]">Modelo em destaque</span><span class="mt-1 block text-xs font-semibold text-[var(--ds-text-muted)]">Priorizado na seleção de modelos.</span></span>
              <CheckboxInput v-model="form.is_featured" type="checkbox" class="ds-checkbox mt-0.5" />
            </label>
          </div>
        </section>

        <section class="ds-command-surface p-4">
          <button type="submit" class="ds-button ds-button-primary w-full" :disabled="form.processing">
            <CheckIcon class="h-4 w-4" />
            {{ form.processing ? $t('gestlab.general.labels.vap_labels.buttons.processing') : isEditing ? $t('gestlab.general.labels.vap_labels.buttons.update_template') : $t('gestlab.general.labels.vap_labels.buttons.save_template') }}
          </button>
        </section>
      </aside>
    </div>
  </form>
</template>
