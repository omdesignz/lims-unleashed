<template>
  <div class="space-y-6">
    <ModuleHero
      :eyebrow="$t('gestlab.general.labels.vap_labels.show.eyebrow')"
      :title="label.name"
      :description="heroDescription"
    >
      <template #actions>
        <span :class="statusBadgeClass(label.is_active)">
          {{ label.is_active ? $t('gestlab.general.labels.vap_labels.active') : $t('gestlab.general.labels.vap_labels.inactive') }}
        </span>
        <span :class="typeBadgeClass(label.type)">
          {{ $t('gestlab.general.labels.vap_labels.types.' + label.type) }}
        </span>
        <Link
          :href="route('vap_labels.labels.edit', label.id)"
          class="ds-button ds-button-secondary"
        >
          <PencilIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.vap_labels.buttons.edit') }}
        </Link>
        <button
          type="button"
          class="ds-button ds-button-primary"
          @click="previewPDF"
        >
          <EyeIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.vap_labels.preview_pdf') }}
        </button>
      </template>
    </ModuleHero>

    <div class="grid gap-4 lg:grid-cols-3">
      <article class="ds-card bg-[var(--ds-panel-raised)] p-5">
        <p class="ds-kicker">
          {{ $t('gestlab.general.labels.vap_labels.show.pdf_card') }}
        </p>
        <h2 class="ds-heading mt-2 text-xl">
          {{ rendererTitle }}
        </h2>
        <p class="ds-copy mt-3 text-sm">
          {{ pdfRenderer?.chrome?.description || $t('gestlab.general.labels.vap_labels.show.pdf_description') }}
        </p>
        <p
          v-if="pdfRenderer?.chrome?.binary_path"
          class="mt-3 truncate rounded-2xl border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2 font-mono text-xs font-semibold text-[var(--ds-text-muted)]"
        >
          {{ pdfRenderer.chrome.binary_path }}
        </p>
      </article>

      <article class="ds-card bg-[var(--ds-panel-raised)] p-5">
        <p class="ds-kicker">
          {{ $t('gestlab.general.labels.vap_labels.dimensions') }}
        </p>
        <p class="mt-2 text-2xl font-black text-[var(--ds-text)]">
          {{ label.width }} × {{ label.height }} mm
        </p>
        <p class="ds-copy mt-2 text-sm">
          {{ $t('gestlab.general.labels.vap_labels.show.dimensions_hint') }}
        </p>
      </article>

      <article class="ds-card bg-[var(--ds-panel-raised)] p-5">
        <p class="ds-kicker">
          {{ $t('gestlab.general.labels.vap_labels.show.traceability_card') }}
        </p>
        <p class="mt-2 text-2xl font-black text-[var(--ds-text)]">
          {{ traceabilitySummary }}
        </p>
        <p class="ds-copy mt-2 text-sm">
          {{ $t('gestlab.general.labels.vap_labels.show.traceability_hint') }}
        </p>
      </article>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
      <div class="space-y-6">
        <section class="ds-panel p-5 sm:p-6">
          <div class="flex items-start gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[rgb(var(--primary-50-rgb))] text-[rgb(var(--primary-800-rgb))] dark:bg-[rgb(var(--primary-400-rgb)/0.12)] dark:text-[rgb(var(--accent-200-rgb))]">
              <EyeIcon class="h-5 w-5" />
            </span>
            <div>
              <p class="ds-kicker">
                {{ $t('gestlab.general.labels.vap_labels.preview') }}
              </p>
              <h2 class="ds-heading mt-1 text-xl">
                {{ $t('gestlab.general.labels.vap_labels.show.preview_title') }}
              </h2>
              <p class="ds-copy mt-1 text-sm">
                {{ $t('gestlab.general.labels.vap_labels.show.preview_description') }}
              </p>
            </div>
          </div>

          <div class="mt-6 rounded-[1.7rem] border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-5 sm:p-8">
            <div
              class="relative mx-auto flex items-center justify-center overflow-hidden rounded-[1.1rem] shadow-2xl shadow-black/10 ring-1 ring-black/5 dark:shadow-black/35"
              :style="labelPreviewStyle"
            >
              <div
                v-if="label.has_qr_code"
                class="absolute left-3 top-3 flex h-10 w-10 items-center justify-center rounded-xl border border-slate-900 bg-white text-[0.62rem] font-black text-slate-900"
              >
                QR
              </div>

              <div
                v-if="label.has_barcode"
                class="absolute bottom-3 left-3 flex h-7 w-24 items-center justify-center rounded-lg border border-slate-900 bg-white text-[0.56rem] font-black tracking-[0.16em] text-slate-900"
              >
                {{ $t('gestlab.general.labels.vap_labels.barcode') }}
              </div>

              <div
                v-if="label.logo_path"
                class="absolute right-3 top-3 flex h-11 w-11 items-center justify-center rounded-xl border border-dashed border-slate-400 bg-white/80 text-[0.58rem] font-black text-slate-700"
              >
                {{ $t('gestlab.general.labels.vap_labels.logo') }}
              </div>

              <div class="w-full whitespace-pre-line px-3">
                {{ previewData?.sample_text || label.content }}
              </div>
            </div>
          </div>

          <div class="mt-5 grid gap-3 md:grid-cols-3">
            <div class="rounded-2xl border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-3">
              <p class="ds-table-heading">
                {{ $t('gestlab.general.labels.vap_labels.font_size') }}
              </p>
              <p class="mt-1 text-sm font-black text-[var(--ds-text)]">
                {{ label.font_size }}px
              </p>
            </div>
            <div class="rounded-2xl border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-3">
              <p class="ds-table-heading">
                {{ $t('gestlab.general.labels.vap_labels.background_color') }}
              </p>
              <p class="mt-1 flex items-center gap-2 text-sm font-black text-[var(--ds-text)]">
                <span
                  class="h-4 w-4 rounded border border-[var(--ds-border)]"
                  :style="{ backgroundColor: label.background_color }"
                />
                {{ label.background_color }}
              </p>
            </div>
            <div class="rounded-2xl border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-3">
              <p class="ds-table-heading">
                {{ $t('gestlab.general.labels.vap_labels.text_alignment') }}
              </p>
              <p class="mt-1 text-sm font-black capitalize text-[var(--ds-text)]">
                {{ label.text_alignment }}
              </p>
            </div>
          </div>

          <div class="mt-5 rounded-[1.35rem] border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-4">
            <p class="ds-table-heading">
              {{ $t('gestlab.general.labels.vap_labels.content') }}
            </p>
            <pre class="mt-3 whitespace-pre-wrap rounded-2xl border border-[var(--ds-border)] bg-[var(--ds-panel)] p-4 text-sm font-semibold leading-6 text-[var(--ds-text-muted)]">{{ label.content }}</pre>
          </div>
        </section>

        <section class="ds-panel p-5 sm:p-6">
          <div class="flex items-start gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[rgb(var(--primary-50-rgb))] text-[rgb(var(--primary-800-rgb))] dark:bg-[rgb(var(--primary-400-rgb)/0.12)] dark:text-[rgb(var(--accent-200-rgb))]">
              <PrinterIcon class="h-5 w-5" />
            </span>
            <div>
              <p class="ds-kicker">
                {{ $t('gestlab.general.labels.vap_labels.generate_pdf') }}
              </p>
              <h2 class="ds-heading mt-1 text-xl">
                {{ $t('gestlab.general.labels.vap_labels.show.print_title') }}
              </h2>
              <p class="ds-copy mt-1 text-sm">
                {{ $t('gestlab.general.labels.vap_labels.show.print_description') }}
              </p>
            </div>
          </div>

          <div class="mt-6 grid gap-5 lg:grid-cols-2">
            <article class="ds-card bg-[var(--ds-panel-raised)] p-4">
              <h3 class="text-sm font-black text-[var(--ds-text)]">
                {{ $t('gestlab.general.labels.vap_labels.generate_single') }}
              </h3>
              <div class="mt-4 space-y-4">
                <BaseTextarea
                  v-model="singleLabel.content"
                  rows="4"
                  :label="$t('gestlab.general.labels.vap_labels.content')"
                  :placeholder="label.content"
                />
                <BaseInput
                  v-if="label.has_qr_code"
                  v-model="singleLabel.qr_content"
                  :label="$t('gestlab.general.labels.vap_labels.qr_content')"
                  :placeholder="previewData?.sample_qr"
                />
                <BaseInput
                  v-if="label.has_barcode"
                  v-model="singleLabel.barcode_content"
                  :label="$t('gestlab.general.labels.vap_labels.barcode_content')"
                  :placeholder="previewData?.sample_barcode"
                />
                <button
                  type="button"
                  class="ds-button ds-button-primary w-full"
                  :disabled="!singleLabel.content"
                  @click="generateSinglePDF"
                >
                  <PrinterIcon class="h-5 w-5" />
                  {{ $t('gestlab.general.labels.vap_labels.generate_pdf') }}
                </button>
              </div>
            </article>

            <article class="ds-card bg-[var(--ds-panel-raised)] p-4">
              <h3 class="text-sm font-black text-[var(--ds-text)]">
                {{ $t('gestlab.general.labels.vap_labels.generate_multiple') }}
              </h3>
              <div class="mt-4 space-y-3">
                <div
                  v-for="(item, index) in batchLabels"
                  :key="index"
                  class="rounded-[1.25rem] border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3"
                >
                  <div class="mb-3 flex items-center justify-between gap-3">
                    <h4 class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-muted)]">
                      {{ $t('gestlab.general.labels.vap_labels.label') }} #{{ index + 1 }}
                    </h4>
                    <button
                      type="button"
                      class="ds-table-action ds-table-action-danger"
                      @click="removeBatchLabel(index)"
                    >
                      <TrashIcon class="h-4 w-4" />
                    </button>
                  </div>
                  <div class="space-y-3">
                    <BaseInput
                      v-model="item.content"
                      :placeholder="label.content"
                    />
                    <BaseInput
                      v-if="label.has_qr_code"
                      v-model="item.qr_content"
                      :placeholder="$t('gestlab.general.labels.vap_labels.qr_content')"
                    />
                    <BaseInput
                      v-if="label.has_barcode"
                      v-model="item.barcode_content"
                      :placeholder="$t('gestlab.general.labels.vap_labels.barcode_content')"
                    />
                  </div>
                </div>

                <div class="grid gap-2 sm:grid-cols-2">
                  <button
                    type="button"
                    class="ds-button ds-button-secondary"
                    @click="addBatchLabel"
                  >
                    <PlusCircleIcon class="h-5 w-5" />
                    {{ $t('gestlab.general.labels.vap_labels.buttons.add_label') }}
                  </button>
                  <button
                    type="button"
                    class="ds-button ds-button-primary"
                    :disabled="batchLabels.length === 0 || !batchLabelsValid"
                    @click="generateBatchPDF"
                  >
                    <DocumentDuplicateIcon class="h-5 w-5" />
                    {{ $t('gestlab.general.labels.vap_labels.batch_print') }}
                  </button>
                </div>
              </div>
            </article>
          </div>

          <div class="mt-6">
            <LabelPrintSettings v-model="printSettings" />
          </div>
        </section>
      </div>

      <aside class="space-y-6 xl:sticky xl:top-6 xl:self-start">
        <section class="ds-panel p-5 sm:p-6">
          <p class="ds-kicker">
            {{ $t('gestlab.general.labels.vap_labels.details') }}
          </p>
          <dl class="mt-4 space-y-4">
            <div
              v-for="detail in labelDetails"
              :key="detail.label"
              class="rounded-2xl border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-3"
            >
              <dt class="ds-table-heading">
                {{ detail.label }}
              </dt>
              <dd class="mt-1 text-sm font-black text-[var(--ds-text)]">
                {{ detail.value }}
              </dd>
            </div>
          </dl>
        </section>

        <section class="ds-panel p-5 sm:p-6">
          <p class="ds-kicker">
            {{ $t('gestlab.general.labels.vap_labels.features') }}
          </p>
          <div class="mt-4 space-y-3">
            <div
              v-for="feature in featureRows"
              :key="feature.label"
              class="flex items-center justify-between gap-3 rounded-2xl border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] px-3 py-2"
            >
              <span class="text-sm font-bold text-[var(--ds-text-muted)]">{{ feature.label }}</span>
              <span :class="featureBadgeClass(feature.enabled)">
                {{ feature.enabled ? $t('gestlab.general.labels.vap_labels.general.yes') : $t('gestlab.general.labels.vap_labels.general.no') }}
              </span>
            </div>

            <div
              v-if="label.has_qr_code && label.qr_code_content"
              class="rounded-2xl border border-[rgb(var(--primary-200-rgb)/0.7)] bg-[rgb(var(--primary-50-rgb)/0.68)] p-3 dark:border-[rgb(var(--accent-200-rgb)/0.22)] dark:bg-[rgb(var(--primary-400-rgb)/0.1)]"
            >
              <p class="ds-table-heading">
                {{ $t('gestlab.general.labels.vap_labels.qr_content') }}
              </p>
              <p class="mt-1 break-all text-xs font-semibold text-[var(--ds-text-muted)]">
                {{ label.qr_code_content }}
              </p>
            </div>

            <div
              v-if="label.has_barcode && label.barcode_content"
              class="rounded-2xl border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-400/20 dark:bg-emerald-500/10"
            >
              <p class="ds-table-heading">
                {{ $t('gestlab.general.labels.vap_labels.barcode_content') }}
              </p>
              <p class="mt-1 break-all text-xs font-semibold text-[var(--ds-text-muted)]">
                {{ label.barcode_content }}
              </p>
            </div>
          </div>
        </section>

        <section class="ds-panel p-5 sm:p-6">
          <p class="ds-kicker">
            {{ $t('gestlab.general.labels.vap_labels.templates.title') }}
          </p>
          <div class="mt-4 max-h-[22rem] space-y-3 overflow-y-auto pr-1">
            <button
              v-for="template in templatesList"
              :key="template.id"
              type="button"
              class="w-full rounded-[1.2rem] border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-3 text-left transition hover:bg-[var(--ds-panel-subtle)]"
              @click="requestApplyTemplate(template)"
            >
              <span class="flex items-start justify-between gap-3">
                <span>
                  <span class="block text-sm font-black text-[var(--ds-text)]">{{ template.name }}</span>
                  <span class="mt-1 block text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">{{ template.description }}</span>
                  <span class="mt-2 inline-flex rounded-full border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-2 py-1 text-[0.62rem] font-black uppercase tracking-[0.12em] text-[var(--ds-text-muted)]">
                    {{ template.category }}
                  </span>
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

          <div class="mt-4 border-t border-[var(--ds-border)] pt-4">
            <Link
              :href="route('vap_labels.label-templates.index')"
              class="ds-table-action"
            >
              {{ $t('gestlab.general.labels.vap_labels.view_all_templates') }}
              <ArrowRightIcon class="h-4 w-4" />
            </Link>
          </div>
        </section>

        <section class="ds-panel p-5 sm:p-6">
          <p class="ds-kicker">
            {{ $t('gestlab.general.labels.vap_labels.actions.title') }}
          </p>
          <div class="mt-4 space-y-3">
            <button
              type="button"
              class="ds-button ds-button-secondary w-full"
              @click="requestDuplicateLabel"
            >
              <DocumentDuplicateIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labels.duplicate_label') }}
            </button>
            <button
              type="button"
              class="ds-button ds-button-secondary w-full"
              @click="toggleStatus"
            >
              <PowerIcon class="h-5 w-5" />
              {{ label.is_active ? $t('gestlab.general.labels.vap_labels.buttons.deactivate') : $t('gestlab.general.labels.vap_labels.buttons.activate') }}
            </button>
            <button
              type="button"
              class="ds-button ds-button-danger w-full"
              @click="requestDeleteLabel"
            >
              <TrashIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labels.delete_label') }}
            </button>
          </div>
        </section>
      </aside>
    </div>

    <confirm-dialog
      v-if="confirmationAction"
      :title="confirmationTitle"
      :description="confirmationDescription"
      :cancel="$t('gestlab.general.buttons.cancel')"
      :confirm="confirmationConfirm"
      :variant="confirmationVariant"
      @confirmed="runConfirmedAction"
      @canceled="clearConfirmation"
    />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import { useToast } from 'vue-toastification'
import {
  ArrowRightIcon,
  DocumentDuplicateIcon,
  EyeIcon,
  PencilIcon,
  PlusCircleIcon,
  PowerIcon,
  PrinterIcon,
  TagIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline'
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseTextarea from '@/Components/base/BaseTextarea.vue'
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import LabelPrintSettings from '@/Components/LabelPrintSettings.vue'
import ModuleHero from '@/Components/base/ModuleHero.vue'

const props = defineProps({
  label: {
    type: Object,
    required: true,
  },
  previewData: {
    type: Object,
    default: () => ({}),
  },
  templates: {
    type: Array,
    default: () => [],
  },
  pdfRenderer: {
    type: Object,
    default: () => ({}),
  },
  printSettings: {
    type: Object,
    default: () => ({}),
  },
})

const toast = useToast()
const confirmationAction = ref(null)
const pendingTemplate = ref(null)

const singleLabel = ref({
  content: props.previewData?.sample_text || props.label.content,
  qr_content: props.label.has_qr_code ? props.previewData?.sample_qr : null,
  barcode_content: props.label.has_barcode ? props.previewData?.sample_barcode : null,
})

const batchLabels = ref([
  {
    content: props.previewData?.sample_text || props.label.content,
    qr_content: props.label.has_qr_code ? props.previewData?.sample_qr : null,
    barcode_content: props.label.has_barcode ? props.previewData?.sample_barcode : null,
  },
])

const printSettings = ref({
  include_cutouts: true,
  labels_per_page: 1,
  margin: 5,
  columns: 2,
  rows: 4,
  spacing: 5,
  page_size: 'A4',
  orientation: 'portrait',
  ...(props.printSettings || {}),
})

const templatesList = computed(() => Array.isArray(props.templates) ? props.templates : [])
const heroDescription = computed(() => `${trans('gestlab.general.labels.vap_labels.view_description')} ${trans(`gestlab.general.labels.vap_labels.types.${props.label.type}`)} · ${props.label.width} × ${props.label.height} mm`)
const rendererTitle = computed(() => props.pdfRenderer?.chrome?.available
  ? trans('gestlab.general.labels.vap_labels.show.chrome_active')
  : trans('gestlab.general.labels.vap_labels.show.mpdf_active'))
const traceabilitySummary = computed(() => [
  props.label.has_qr_code ? trans('gestlab.general.labels.vap_labels.qr_code') : null,
  props.label.has_barcode ? trans('gestlab.general.labels.vap_labels.barcode') : null,
  props.label.logo_path ? trans('gestlab.general.labels.vap_labels.logo') : null,
].filter(Boolean).join(' + ') || trans('gestlab.general.labels.vap_labels.content'))

const labelPreviewStyle = computed(() => ({
  width: `${Math.min(Number(props.label.width || 50) * 3, 420)}px`,
  minHeight: `${Math.min(Number(props.label.height || 25) * 3, 260)}px`,
  backgroundColor: props.label.background_color,
  color: props.label.text_color,
  fontSize: `${props.label.font_size}px`,
  border: `${props.label.border_width}px solid ${props.label.border_color}`,
  textAlign: props.label.text_alignment,
  justifyContent: props.label.text_alignment === 'left'
    ? 'flex-start'
    : props.label.text_alignment === 'right'
      ? 'flex-end'
      : 'center',
}))

const labelDetails = computed(() => [
  { label: trans('gestlab.general.labels.vap_labels.created_at'), value: formatDate(props.label.created_at) },
  { label: trans('gestlab.general.labels.vap_labels.updated_at'), value: formatDate(props.label.updated_at) },
  props.label.lab ? { label: trans('gestlab.general.labels.vap_labels.lab'), value: props.label.lab.name } : null,
  props.label.department ? { label: trans('gestlab.general.labels.vap_labels.department'), value: props.label.department.name } : null,
  props.label.user ? { label: trans('gestlab.general.labels.vap_labels.created_by'), value: props.label.user.name } : null,
].filter(Boolean))

const featureRows = computed(() => [
  { label: trans('gestlab.general.labels.vap_labels.qr_code'), enabled: props.label.has_qr_code },
  { label: trans('gestlab.general.labels.vap_labels.barcode'), enabled: props.label.has_barcode },
  { label: trans('gestlab.general.labels.vap_labels.logo'), enabled: props.label.logo_path },
])

const batchLabelsValid = computed(() => batchLabels.value.every((label) => String(label.content ?? '').trim() !== ''))

const confirmationTitle = computed(() => {
  const titles = {
    applyTemplate: trans('gestlab.general.labels.vap_labels.apply_template'),
    duplicate: trans('gestlab.general.labels.vap_labels.duplicate_label'),
    delete: trans('gestlab.general.labels.vap_labels.delete_label'),
  }

  return titles[confirmationAction.value] || ''
})

const confirmationDescription = computed(() => {
  const descriptions = {
    applyTemplate: trans('gestlab.general.labels.vap_labels.confirm_apply_template'),
    duplicate: trans('gestlab.general.labels.vap_labels.confirm_duplicate_label'),
    delete: trans('gestlab.general.labels.vap_labels.confirm_delete_label_irreversible'),
  }

  return descriptions[confirmationAction.value] || ''
})

const confirmationConfirm = computed(() => {
  if (confirmationAction.value === 'delete') {
    return trans('gestlab.general.labels.vap_labels.buttons.delete_label')
  }

  return trans('gestlab.general.buttons.confirm')
})

const confirmationVariant = computed(() => confirmationAction.value === 'delete' ? 'danger' : 'question')

const statusBadgeClass = (isActive) => {
  return isActive
    ? 'inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-500/10 dark:text-emerald-200'
    : 'inline-flex items-center rounded-full border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-1 text-xs font-black text-[var(--ds-text-muted)]'
}

const typeBadgeClass = (type) => {
  const classes = {
    equipment: 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-400/20 dark:bg-sky-500/10 dark:text-sky-200',
    material: 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-500/10 dark:text-emerald-200',
    sample: 'border-fuchsia-200 bg-fuchsia-50 text-fuchsia-800 dark:border-fuchsia-400/20 dark:bg-fuchsia-500/10 dark:text-fuchsia-200',
    custom: 'border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)]',
  }

  return `inline-flex items-center rounded-full border px-3 py-1 text-xs font-black ${classes[type] || classes.custom}`
}

const featureBadgeClass = (hasFeature) => {
  return hasFeature
    ? 'inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-black text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-500/10 dark:text-emerald-200'
    : 'inline-flex items-center rounded-full border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-2.5 py-0.5 text-xs font-black text-[var(--ds-text-muted)]'
}

const formatDate = (dateString) => {
  if (!dateString) {
    return '—'
  }

  return new Date(dateString).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const addBatchLabel = () => {
  batchLabels.value.push({
    content: '',
    qr_content: props.label.has_qr_code ? '' : null,
    barcode_content: props.label.has_barcode ? '' : null,
  })
}

const removeBatchLabel = (index) => {
  batchLabels.value.splice(index, 1)
}

const previewPDF = () => {
  window.open(route('vap_labels.preview-pdf', props.label.id), '_blank', 'noopener')
}

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

const appendPrintSettings = (formData) => {
  Object.entries(printSettings.value).forEach(([key, value]) => {
    formData.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : value ?? '')
  })
}

const openPdfResponse = async (url, formData, windowTitle) => {
  const popup = window.open('', '_blank')

  try {
    const response = await fetch(url, {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json, application/pdf',
      },
      credentials: 'same-origin',
    })

    if (!response.ok) {
      const message = response.status === 422
        ? trans('gestlab.general.labels.vap_labels.validation_error')
        : trans('gestlab.general.labels.vap_labels.pdf_generation_error')

      throw new Error(message)
    }

    const contentType = response.headers.get('Content-Type') || ''

    if (!contentType.includes('application/pdf')) {
      throw new Error(trans('gestlab.general.labels.vap_labels.pdf_generation_error'))
    }

    const blob = await response.blob()
    const blobUrl = URL.createObjectURL(blob)

    if (popup) {
      popup.document.title = windowTitle
      popup.location.href = blobUrl
    } else {
      window.open(blobUrl, '_blank', 'noopener')
    }

    toast.success(trans('gestlab.general.labels.vap_labels.print_settings_saved'))
  } catch (error) {
    if (popup) {
      popup.close()
    }

    toast.error(error.message || trans('gestlab.general.labels.vap_labels.pdf_generation_error'))
  }
}

const generateSinglePDF = async () => {
  const formData = new FormData()
  formData.append('data[0][content]', singleLabel.value.content || props.previewData?.sample_text || props.label.content)

  if (props.label.has_qr_code) {
    formData.append('data[0][qr_content]', singleLabel.value.qr_content || props.previewData?.sample_qr || '')
  }

  if (props.label.has_barcode) {
    formData.append('data[0][barcode_content]', singleLabel.value.barcode_content || props.previewData?.sample_barcode || '')
  }

  appendPrintSettings(formData)
  await openPdfResponse(route('vap_labels.generate-pdf', props.label.id), formData, props.label.name)
}

const generateBatchPDF = async () => {
  const formData = new FormData()

  batchLabels.value.forEach((label, index) => {
    formData.append(`data[${index}][content]`, label.content)

    if (props.label.has_qr_code && label.qr_content) {
      formData.append(`data[${index}][qr_content]`, label.qr_content)
    }

    if (props.label.has_barcode && label.barcode_content) {
      formData.append(`data[${index}][barcode_content]`, label.barcode_content)
    }
  })

  appendPrintSettings(formData)
  await openPdfResponse(route('vap_labels.generate-batch-pdf', props.label.id), formData, `${props.label.name} - lote`)
}

const requestApplyTemplate = (template) => {
  pendingTemplate.value = template
  confirmationAction.value = 'applyTemplate'
}

const requestDuplicateLabel = () => {
  confirmationAction.value = 'duplicate'
}

const requestDeleteLabel = () => {
  confirmationAction.value = 'delete'
}

const clearConfirmation = () => {
  confirmationAction.value = null
  pendingTemplate.value = null
}

const runConfirmedAction = () => {
  if (confirmationAction.value === 'applyTemplate' && pendingTemplate.value) {
    router.post(route('vap_labels.apply-template', props.label.id), {
      template_id: pendingTemplate.value.id,
    }, {
      onFinish: clearConfirmation,
    })
    return
  }

  if (confirmationAction.value === 'duplicate') {
    router.post(route('vap_labels.duplicate', props.label.id), {}, {
      onFinish: clearConfirmation,
    })
    return
  }

  if (confirmationAction.value === 'delete') {
    router.delete(route('vap_labels.labels.destroy', props.label.id), {
      onFinish: clearConfirmation,
    })
    return
  }

  clearConfirmation()
}

const toggleStatus = () => {
  router.post(route('vap_labels.toggle-status', props.label.id), {}, {
    preserveScroll: true,
  })
}
</script>
