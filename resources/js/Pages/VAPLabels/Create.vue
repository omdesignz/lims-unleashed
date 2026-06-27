<template>
  <div class="space-y-6">
    <ModuleHero
      :eyebrow="$t('gestlab.general.labels.vap_labels.index.eyebrow')"
      :title="pageTitle"
      :description="pageDescription"
    >
      <template #actions>
        <span class="ds-chip">
          {{ templatesList.length }} {{ $t('gestlab.general.labels.vap_labels.available_templates') }}
        </span>
      </template>

      <div
        v-if="selectedTemplate"
        class="ds-card bg-[var(--ds-panel-raised)] p-4"
      >
        <p class="ds-kicker text-[0.64rem]">
          {{ $t('gestlab.general.labels.vap_labels.index.stats_scope') }}
        </p>
        <p class="mt-2 text-sm font-black text-[var(--ds-text)]">
          {{ selectedTemplate.name }}
        </p>
      </div>
    </ModuleHero>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_25rem]">
      <div class="space-y-6">
        <section class="ds-panel p-5 sm:p-6">
          <div class="flex items-start gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[rgb(var(--primary-50-rgb))] text-[rgb(var(--primary-800-rgb))] dark:bg-[rgb(var(--primary-400-rgb)/0.12)] dark:text-[rgb(var(--accent-200-rgb))]">
              <Cog6ToothIcon class="h-5 w-5" />
            </span>
            <div>
              <p class="ds-kicker">
                {{ $t('gestlab.general.labels.vap_labels.basic_settings') }}
              </p>
              <h2 class="ds-heading mt-1 text-xl">
                {{ $t('gestlab.general.labels.vap_labels.editor.identity_title') }}
              </h2>
              <p class="ds-copy mt-1 text-sm">
                {{ $t('gestlab.general.labels.vap_labels.editor.identity_description') }}
              </p>
            </div>
          </div>

          <div class="mt-5 grid gap-4 md:grid-cols-2">
            <BaseInput
              v-model="form.name"
              :label="$t('gestlab.general.labels.vap_labels.name')"
              :placeholder="$t('gestlab.general.labels.vap_labels.name_placeholder')"
              :error="form.errors.name"
              required
            >
              <template #leading>
                <TagIcon class="h-5 w-5" />
              </template>
            </BaseInput>

            <BaseSelect
              v-model="form.type"
              :label="$t('gestlab.general.labels.vap_labels.type')"
              :error="form.errors.type"
              required
            >
              <option value="equipment">
                {{ $t('gestlab.general.labels.vap_labels.types.equipment') }}
              </option>
              <option value="material">
                {{ $t('gestlab.general.labels.vap_labels.types.material') }}
              </option>
              <option value="sample">
                {{ $t('gestlab.general.labels.vap_labels.types.sample') }}
              </option>
              <option value="custom">
                {{ $t('gestlab.general.labels.vap_labels.types.custom') }}
              </option>
            </BaseSelect>

            <BaseInput
              v-model="form.width"
              type="number"
              step="0.1"
              min="1"
              max="1000"
              :label="$t('gestlab.general.labels.vap_labels.width')"
              :placeholder="$t('gestlab.general.labels.vap_labels.width_placeholder')"
              :error="form.errors.width"
              required
            >
              <template #leading>
                <ArrowsPointingOutIcon class="h-5 w-5" />
              </template>
              <template #trailing>
                <span class="text-xs font-black uppercase tracking-[0.14em]">mm</span>
              </template>
            </BaseInput>

            <BaseInput
              v-model="form.height"
              type="number"
              step="0.1"
              min="1"
              max="1000"
              :label="$t('gestlab.general.labels.vap_labels.height')"
              :placeholder="$t('gestlab.general.labels.vap_labels.height_placeholder')"
              :error="form.errors.height"
              required
            >
              <template #leading>
                <ArrowsPointingOutIcon class="h-5 w-5 rotate-90" />
              </template>
              <template #trailing>
                <span class="text-xs font-black uppercase tracking-[0.14em]">mm</span>
              </template>
            </BaseInput>
          </div>

          <div class="mt-5">
            <p class="ds-field-label">
              {{ $t('gestlab.general.labels.vap_labels.editor.source_title') }}
            </p>
            <div class="mt-2 grid gap-3 md:grid-cols-3">
              <button
                v-for="option in sourceTypeOptions"
                :key="option.value"
                type="button"
                :class="[
                  'rounded-[1.35rem] border p-4 text-left transition duration-200',
                  form.source_type === option.value
                    ? 'border-[rgb(var(--primary-300-rgb)/0.8)] bg-[rgb(var(--primary-50-rgb)/0.82)] text-[rgb(var(--primary-900-rgb))] shadow-[var(--ds-shadow-control)] dark:border-[rgb(var(--accent-200-rgb)/0.38)] dark:bg-[rgb(var(--primary-400-rgb)/0.12)] dark:text-[rgb(var(--accent-100-rgb))]'
                    : 'border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[var(--ds-text)] hover:border-[rgb(var(--primary-300-rgb)/0.58)] hover:bg-[var(--ds-panel-subtle)]'
                ]"
                @click="selectSourceType(option.value)"
              >
                <span class="text-sm font-black">{{ option.label }}</span>
                <span class="mt-1 block text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">{{ option.description }}</span>
              </button>
            </div>
          </div>

          <div
            v-if="availableSources.length"
            class="mt-5"
          >
            <BaseSelect
              v-model="form.source_id"
              :label="$t('gestlab.general.labels.vap_labels.editor.source_record')"
            >
              <option value="">
                {{ $t('gestlab.general.labels.vap_labels.editor.select_source') }}
              </option>
              <option
                v-for="source in availableSources"
                :key="source.id"
                :value="source.id"
              >
                {{ source.label }}
              </option>
            </BaseSelect>
          </div>
        </section>

        <section class="ds-panel p-5 sm:p-6">
          <div class="flex items-start gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[rgb(var(--primary-50-rgb))] text-[rgb(var(--primary-800-rgb))] dark:bg-[rgb(var(--primary-400-rgb)/0.12)] dark:text-[rgb(var(--accent-200-rgb))]">
              <DocumentTextIcon class="h-5 w-5" />
            </span>
            <div>
              <p class="ds-kicker">
                {{ $t('gestlab.general.labels.vap_labels.label_content') }}
              </p>
              <h2 class="ds-heading mt-1 text-xl">
                {{ $t('gestlab.general.labels.vap_labels.editor.content_title') }}
              </h2>
              <p class="ds-copy mt-1 text-sm">
                {{ $t('gestlab.general.labels.vap_labels.editor.content_description') }}
              </p>
            </div>
          </div>

          <BaseTextarea
            v-model="form.content"
            class="mt-5"
            rows="5"
            :label="$t('gestlab.general.labels.vap_labels.content')"
            :placeholder="$t('gestlab.general.labels.vap_labels.content_placeholder')"
            :error="form.errors.content"
            required
          />

          <div class="mt-4 flex flex-wrap items-center gap-2">
            <span class="text-xs font-black uppercase tracking-[0.16em] text-[var(--ds-text-muted)]">
              {{ $t('gestlab.general.labels.vap_labels.template_variables') }}
            </span>
            <code
              v-for="placeholder in supportedPlaceholders"
              :key="placeholder"
              class="rounded-full border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-2.5 py-1 text-xs font-black text-[var(--ds-text-muted)]"
            >
              {{ placeholder }}
            </code>
          </div>

          <div
            v-if="sourcePreview"
            class="mt-4 rounded-[1.35rem] border border-[rgb(var(--primary-200-rgb)/0.72)] bg-[rgb(var(--primary-50-rgb)/0.72)] p-4 text-sm font-semibold leading-6 text-[rgb(var(--primary-900-rgb))] dark:border-[rgb(var(--accent-200-rgb)/0.22)] dark:bg-[rgb(var(--primary-400-rgb)/0.1)] dark:text-[rgb(var(--accent-100-rgb))]"
          >
            {{ $t('gestlab.general.labels.vap_labels.editor.source_hint') }}
          </div>
        </section>

        <section class="ds-panel p-5 sm:p-6">
          <div class="flex items-start gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[rgb(var(--primary-50-rgb))] text-[rgb(var(--primary-800-rgb))] dark:bg-[rgb(var(--primary-400-rgb)/0.12)] dark:text-[rgb(var(--accent-200-rgb))]">
              <PaintBrushIcon class="h-5 w-5" />
            </span>
            <div>
              <p class="ds-kicker">
                {{ $t('gestlab.general.labels.vap_labels.appearance') }}
              </p>
              <h2 class="ds-heading mt-1 text-xl">
                {{ $t('gestlab.general.labels.vap_labels.editor.appearance_title') }}
              </h2>
            </div>
          </div>

          <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div
              v-for="colorControl in colorControls"
              :key="colorControl.field"
              class="ds-card bg-[var(--ds-panel-raised)] p-4"
            >
              <label class="ds-field-label">
                {{ colorControl.label }}
              </label>
              <div class="mt-2 flex items-center gap-3">
                <input
                  v-model="form[colorControl.field]"
                  type="color"
                  class="h-12 w-16 cursor-pointer rounded-2xl border border-[var(--ds-border)] bg-[var(--ds-panel)] p-1"
                />
                <BaseInput
                  v-model="form[colorControl.field]"
                  :placeholder="colorControl.placeholder"
                  :error="form.errors[colorControl.field]"
                />
              </div>
            </div>

            <div class="ds-card bg-[var(--ds-panel-raised)] p-4">
              <label class="ds-field-label">
                {{ $t('gestlab.general.labels.vap_labels.font_size') }}
              </label>
              <input
                v-model="form.font_size"
                type="range"
                min="6"
                max="72"
                class="mt-4 h-2 w-full cursor-pointer appearance-none rounded-full bg-[var(--ds-panel-muted)] accent-[rgb(var(--primary-700-rgb))]"
              />
              <div class="mt-3 flex justify-between text-xs font-black text-[var(--ds-text-muted)]">
                <span>6px</span>
                <span>{{ form.font_size }}px</span>
                <span>72px</span>
              </div>
              <p
                v-if="form.errors.font_size"
                class="ds-field-error mt-2"
              >
                {{ form.errors.font_size }}
              </p>
            </div>

            <div class="ds-card bg-[var(--ds-panel-raised)] p-4">
              <label class="ds-field-label">
                {{ $t('gestlab.general.labels.vap_labels.border_width') }}
              </label>
              <input
                v-model="form.border_width"
                type="range"
                min="0"
                max="10"
                class="mt-4 h-2 w-full cursor-pointer appearance-none rounded-full bg-[var(--ds-panel-muted)] accent-[rgb(var(--primary-700-rgb))]"
              />
              <div class="mt-3 flex justify-between text-xs font-black text-[var(--ds-text-muted)]">
                <span>0px</span>
                <span>{{ form.border_width }}px</span>
                <span>10px</span>
              </div>
              <p
                v-if="form.errors.border_width"
                class="ds-field-error mt-2"
              >
                {{ form.errors.border_width }}
              </p>
            </div>

            <div class="ds-card bg-[var(--ds-panel-raised)] p-4 md:col-span-2 xl:col-span-1">
              <label class="ds-field-label">
                {{ $t('gestlab.general.labels.vap_labels.text_alignment') }}
              </label>
              <div class="mt-3 grid grid-cols-2 gap-2">
                <button
                  v-for="align in alignmentOptions"
                  :key="align.value"
                  type="button"
                  :class="[
                    'ds-button min-h-0 justify-center px-3 py-2',
                    form.text_alignment === align.value ? 'ds-button-primary' : 'ds-button-secondary'
                  ]"
                  :title="align.label"
                  @click="form.text_alignment = align.value"
                >
                  {{ align.short }}
                </button>
              </div>
            </div>
          </div>
        </section>

        <section class="ds-panel p-5 sm:p-6">
          <div class="flex items-start gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[rgb(var(--primary-50-rgb))] text-[rgb(var(--primary-800-rgb))] dark:bg-[rgb(var(--primary-400-rgb)/0.12)] dark:text-[rgb(var(--accent-200-rgb))]">
              <QrCodeIcon class="h-5 w-5" />
            </span>
            <div>
              <p class="ds-kicker">
                {{ $t('gestlab.general.labels.vap_labels.features') }}
              </p>
              <h2 class="ds-heading mt-1 text-xl">
                {{ $t('gestlab.general.labels.vap_labels.editor.traceability_title') }}
              </h2>
              <p class="ds-copy mt-1 text-sm">
                {{ $t('gestlab.general.labels.vap_labels.editor.traceability_description') }}
              </p>
            </div>
          </div>

          <div class="mt-5 grid gap-4 xl:grid-cols-2">
            <article
              v-for="element in advancedElements"
              :key="element.key"
              class="ds-card bg-[var(--ds-panel-raised)] p-4"
            >
              <div class="flex items-start justify-between gap-3">
                <div>
                  <h3 class="text-sm font-black text-[var(--ds-text)]">
                    {{ element.label }}
                  </h3>
                  <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">
                    {{ element.description }}
                  </p>
                </div>
                <button
                  type="button"
                  :class="[
                    'relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition focus:outline-none focus:ring-2 focus:ring-[var(--ds-focus)]',
                    form[element.enabledField] ? 'bg-[rgb(var(--primary-800-rgb))] dark:bg-[rgb(var(--accent-200-rgb))]' : 'bg-[var(--ds-panel-muted)]'
                  ]"
                  @click="form[element.enabledField] = !form[element.enabledField]"
                >
                  <span
                    :class="[
                      'inline-block h-5 w-5 transform rounded-full bg-white shadow transition dark:bg-[rgb(var(--primary-950-rgb))]',
                      form[element.enabledField] ? 'translate-x-6' : 'translate-x-1'
                    ]"
                  />
                </button>
              </div>

              <div
                v-if="form[element.enabledField]"
                class="mt-4 grid gap-3 sm:grid-cols-2"
              >
                <BaseInput
                  v-model="form[element.contentField]"
                  class="sm:col-span-2"
                  :label="element.contentLabel"
                  :placeholder="element.placeholder"
                />
                <BaseInput
                  v-model="form[element.sizeField]"
                  type="number"
                  min="1"
                  max="80"
                  :label="element.sizeLabel"
                />
                <BaseInput
                  v-if="element.secondarySizeField"
                  v-model="form[element.secondarySizeField]"
                  type="number"
                  min="1"
                  max="80"
                  :label="element.secondarySizeLabel"
                />
              </div>
            </article>

            <article class="ds-card bg-[var(--ds-panel-raised)] p-4 xl:col-span-2">
              <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                  <h3 class="text-sm font-black text-[var(--ds-text)]">
                    {{ $t('gestlab.general.labels.vap_labels.editor.logo_title') }}
                  </h3>
                  <p class="mt-1 max-w-2xl text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">
                    {{ $t('gestlab.general.labels.vap_labels.editor.logo_description') }}
                  </p>
                </div>
                <button
                  type="button"
                  :class="[
                    'relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition focus:outline-none focus:ring-2 focus:ring-[var(--ds-focus)]',
                    form.logo_path !== null ? 'bg-[rgb(var(--primary-800-rgb))] dark:bg-[rgb(var(--accent-200-rgb))]' : 'bg-[var(--ds-panel-muted)]'
                  ]"
                  @click="form.logo_path = form.logo_path !== null ? null : ''"
                >
                  <span
                    :class="[
                      'inline-block h-5 w-5 transform rounded-full bg-white shadow transition dark:bg-[rgb(var(--primary-950-rgb))]',
                      form.logo_path !== null ? 'translate-x-6' : 'translate-x-1'
                    ]"
                  />
                </button>
              </div>
              <div
                v-if="form.logo_path !== null"
                class="mt-4 grid gap-3 md:grid-cols-[minmax(0,1fr)_10rem]"
              >
                <BaseInput
                  v-model="form.logo_path"
                  :label="$t('gestlab.general.labels.vap_labels.logo_file')"
                  placeholder="/storage/media/logo.svg"
                />
                <BaseInput
                  v-model="form.logo_size"
                  type="number"
                  min="1"
                  max="80"
                  :label="$t('gestlab.general.labels.vap_labels.logo_size')"
                />
              </div>
            </article>
          </div>
        </section>
      </div>

      <aside class="space-y-6 xl:sticky xl:top-6 xl:self-start">
        <section class="ds-panel p-5 sm:p-6">
          <p class="ds-kicker">
            {{ $t('gestlab.general.labels.vap_labels.preview') }}
          </p>
          <h2 class="ds-heading mt-1 text-xl">
            {{ $t('gestlab.general.labels.vap_labels.editor.preview_title') }}
          </h2>

          <div class="mt-5 rounded-[1.6rem] border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
            <div
              class="relative mx-auto flex items-center justify-center overflow-hidden rounded-[1rem] shadow-2xl shadow-black/10 ring-1 ring-black/5 dark:shadow-black/35"
              :style="previewStyle"
            >
              <div class="whitespace-pre-line px-3 text-center">
                {{ form.content || $t('gestlab.general.labels.vap_labels.preview_text') }}
              </div>
            </div>
          </div>
          <p class="mt-4 text-center text-sm font-black text-[var(--ds-text-muted)]">
            {{ form.width }} × {{ form.height }} mm
          </p>
        </section>

        <section class="ds-panel p-5 sm:p-6">
          <p class="ds-kicker">
            {{ $t('gestlab.general.labels.vap_labels.templates.title') }}
          </p>
          <h2 class="ds-heading mt-1 text-xl">
            {{ $t('gestlab.general.labels.vap_labels.editor.template_title') }}
          </h2>

          <div class="mt-4 max-h-[24rem] space-y-3 overflow-y-auto pr-1">
            <button
              v-for="template in templatesList"
              :key="template.id"
              type="button"
              :class="[
                'w-full rounded-[1.25rem] border p-4 text-left transition duration-200',
                selectedTemplate?.id === template.id
                  ? 'border-[rgb(var(--primary-300-rgb)/0.8)] bg-[rgb(var(--primary-50-rgb)/0.82)] dark:border-[rgb(var(--accent-200-rgb)/0.32)] dark:bg-[rgb(var(--primary-400-rgb)/0.12)]'
                  : 'border-[var(--ds-border)] bg-[var(--ds-panel-raised)] hover:bg-[var(--ds-panel-subtle)]'
              ]"
              @click="applyTemplate(template)"
            >
              <span class="flex items-start justify-between gap-3">
                <span>
                  <span class="block text-sm font-black text-[var(--ds-text)]">{{ template.name }}</span>
                  <span class="mt-1 block text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">{{ template.description }}</span>
                </span>
                <span
                  v-if="template.is_featured"
                  class="rounded-full border border-amber-200 bg-amber-50 px-2 py-1 text-[0.62rem] font-black uppercase tracking-[0.12em] text-amber-800 dark:border-amber-400/20 dark:bg-amber-500/10 dark:text-amber-200"
                >
                  {{ $t('gestlab.general.labels.vap_labels.featured') }}
                </span>
              </span>
            </button>
          </div>
        </section>

        <section class="ds-panel p-5 sm:p-6">
          <p class="ds-kicker">
            {{ $t('gestlab.general.labels.vap_labels.assignment') }}
          </p>
          <div class="mt-4 space-y-4">
            <BaseSelect
              v-model="form.lab_id"
              :label="$t('gestlab.general.labels.vap_labels.lab')"
            >
              <option value="">
                {{ $t('gestlab.general.labels.vap_labels.select_lab') }}
              </option>
              <option
                v-for="lab in labsList"
                :key="lab.id"
                :value="lab.id"
              >
                {{ lab.name }}
              </option>
            </BaseSelect>

            <BaseSelect
              v-model="form.department_id"
              :label="$t('gestlab.general.labels.vap_labels.department')"
            >
              <option value="">
                {{ $t('gestlab.general.labels.vap_labels.select_department') }}
              </option>
              <option
                v-for="dept in departmentsList"
                :key="dept.id"
                :value="dept.id"
              >
                {{ dept.name }}
              </option>
            </BaseSelect>
          </div>
        </section>

        <section class="ds-panel p-5 sm:p-6">
          <p class="ds-kicker">
            {{ $t('gestlab.general.labels.vap_labels.actions.title') }}
          </p>
          <div class="mt-4 space-y-3">
            <button
              type="button"
              class="ds-button ds-button-primary w-full"
              :disabled="form.processing"
              @click="submit"
            >
              <CheckIcon class="h-5 w-5" />
              {{ form.processing ? $t('gestlab.general.labels.vap_labels.buttons.processing') : submitLabel }}
            </button>

            <Link
              :href="props.label ? route('vap_labels.labels.show', props.label.id) : route('vap_labels.labels.index')"
              class="ds-button ds-button-secondary w-full"
            >
              <ArrowLeftIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labels.buttons.cancel') }}
            </Link>
          </div>
        </section>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import {
  ArrowLeftIcon,
  ArrowsPointingOutIcon,
  CheckIcon,
  Cog6ToothIcon,
  DocumentTextIcon,
  PaintBrushIcon,
  QrCodeIcon,
  TagIcon,
} from '@heroicons/vue/24/outline'
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import BaseTextarea from '@/Components/base/BaseTextarea.vue'
import ModuleHero from '@/Components/base/ModuleHero.vue'

const props = defineProps({
  templates: {
    type: Array,
    default: () => [],
  },
  selectedTemplateId: {
    type: Number,
    default: null,
  },
  labs: {
    type: Array,
    default: () => [],
  },
  departments: {
    type: Array,
    default: () => [],
  },
  defaultSettings: {
    type: Object,
    default: () => ({}),
  },
  label: {
    type: Object,
    default: null,
  },
  sourcePreview: {
    type: Object,
    default: null,
  },
  sourceOptions: {
    type: Object,
    default: () => ({}),
  },
  supportedPlaceholders: {
    type: Array,
    default: () => ['{name}', '{code}', '{lot}'],
  },
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
  {
    value: 'sample_entry',
    label: trans('gestlab.general.labels.vap_labels.editor.source_sample'),
    description: trans('gestlab.general.labels.vap_labels.editor.source_sample_description'),
  },
  {
    value: 'equipment',
    label: trans('gestlab.general.labels.vap_labels.editor.source_equipment'),
    description: trans('gestlab.general.labels.vap_labels.editor.source_equipment_description'),
  },
  {
    value: 'reagent',
    label: trans('gestlab.general.labels.vap_labels.editor.source_reagent'),
    description: trans('gestlab.general.labels.vap_labels.editor.source_reagent_description'),
  },
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
    placeholder: '{verification_url}',
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
  { value: 'left', label: trans('gestlab.general.labels.vap_labels.align_left'), short: 'L' },
  { value: 'center', label: trans('gestlab.general.labels.vap_labels.align_center'), short: 'C' },
  { value: 'right', label: trans('gestlab.general.labels.vap_labels.align_right'), short: 'R' },
  { value: 'justify', label: trans('gestlab.general.labels.vap_labels.align_justify'), short: 'J' },
])

const colorControls = computed(() => [
  {
    field: 'background_color',
    label: trans('gestlab.general.labels.vap_labels.background_color'),
    placeholder: '#ffffff',
  },
  {
    field: 'text_color',
    label: trans('gestlab.general.labels.vap_labels.text_color'),
    placeholder: '#000000',
  },
  {
    field: 'border_color',
    label: trans('gestlab.general.labels.vap_labels.border_color'),
    placeholder: '#000000',
  },
])

const form = useForm({
  name: props.label?.name || '',
  type: props.label?.type || 'custom',
  content: props.label?.content || '',
  width: props.label?.width || props.defaultSettings?.width || 50,
  height: props.label?.height || props.defaultSettings?.height || 25,
  background_color: props.label?.background_color || props.label?.template_data?.background_color || '#ffffff',
  text_color: props.label?.text_color || props.label?.template_data?.text_color || '#000000',
  font_size: props.label?.font_size || props.defaultSettings?.font_size || 12,
  border_width: props.label?.border_width || props.defaultSettings?.border_width || 1,
  border_color: props.label?.border_color || props.label?.template_data?.border_color || '#000000',
  text_alignment: props.label?.text_alignment || 'center',
  lab_id: props.label?.lab_id || null,
  department_id: props.label?.department_id || null,
  logo_path: props.label?.logo_path || null,
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

const previewStyle = computed(() => ({
  width: `${Math.min(Number(form.width || 50) * 3, 320)}px`,
  minHeight: `${Math.min(Number(form.height || 25) * 3, 220)}px`,
  backgroundColor: form.background_color,
  color: form.text_color,
  fontSize: `${form.font_size}px`,
  border: `${form.border_width}px solid ${form.border_color}`,
  textAlign: form.text_alignment,
}))

const selectSourceType = (sourceType) => {
  form.source_type = sourceType
  form.source_id = null
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
