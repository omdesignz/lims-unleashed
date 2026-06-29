<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import studioWorkbench from '@/Components/proposal-template/studio-workbench.vue'
import { commercialDocumentThemeClasses } from '@/Composables/useCommercialDocumentTheme'
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

defineOptions({ layout: Layout })

const props = defineProps({
  variables: {
    type: Array,
    default: () => [],
  },
  initialDraft: {
    type: Object,
    default: null,
  },
  presets: {
    type: Array,
    default: () => [],
  },
  studioAssets: {
    type: Array,
    default: () => [],
  },
})

const layoutSchema = ref({
  first_page_header_html: props.initialDraft?.layout_schema?.first_page_header_html ?? '<div style="border-bottom:1px solid #0f172a; padding-bottom:12px;"><h2 style="margin:0;">{{lab_name}}</h2><p style="margin-top:6px;">{{document_code}}</p></div>',
  default_header_html: props.initialDraft?.layout_schema?.default_header_html ?? '<div style="font-size:10px;">{{document_code}} · {{customer_name}}</div>',
  footer_html: props.initialDraft?.layout_schema?.footer_html ?? '<div style="font-size:9px; color:#334155;">Documento controlado · Página {PAGENO}/{nbpg}</div>',
  styles_css: props.initialDraft?.layout_schema?.styles_css ?? '',
  document_font_family: props.initialDraft?.layout_schema?.document_font_family ?? '"Century Gothic", DejaVu Sans, sans-serif',
  variable_catalog: props.initialDraft?.layout_schema?.variable_catalog ?? [],
  canvas_blocks: props.initialDraft?.layout_schema?.canvas_blocks ?? [],
  page_background_color: props.initialDraft?.layout_schema?.page_background_color ?? '#fffdf7',
  background_image_path: props.initialDraft?.layout_schema?.background_image_path ?? '',
  background_size: props.initialDraft?.layout_schema?.background_size ?? 'cover',
  background_position: props.initialDraft?.layout_schema?.background_position ?? 'center center',
  background_repeat: props.initialDraft?.layout_schema?.background_repeat ?? 'no-repeat',
  table_header_background: props.initialDraft?.layout_schema?.table_header_background ?? '#143d37',
  table_header_text_color: props.initialDraft?.layout_schema?.table_header_text_color ?? '#ffffff',
  table_border_color: props.initialDraft?.layout_schema?.table_border_color ?? '#cbd5e1',
  table_font_size: props.initialDraft?.layout_schema?.table_font_size ?? 10,
  table_cell_padding: props.initialDraft?.layout_schema?.table_cell_padding ?? 8,
  table_summary_background: props.initialDraft?.layout_schema?.table_summary_background ?? '#fffdf7',
  table_summary_text_color: props.initialDraft?.layout_schema?.table_summary_text_color ?? '#15231f',
  table_summary_muted_color: props.initialDraft?.layout_schema?.table_summary_muted_color ?? '#64748b',
})

const exportSettings = ref({
  paper_size: props.initialDraft?.export_settings?.paper_size ?? 'A4',
  custom_page_width: props.initialDraft?.export_settings?.custom_page_width ?? null,
  custom_page_height: props.initialDraft?.export_settings?.custom_page_height ?? null,
  orientation: props.initialDraft?.export_settings?.orientation ?? 'P',
  margin_top: props.initialDraft?.export_settings?.margin_top ?? 22,
  margin_right: props.initialDraft?.export_settings?.margin_right ?? 14,
  margin_bottom: props.initialDraft?.export_settings?.margin_bottom ?? 18,
  margin_left: props.initialDraft?.export_settings?.margin_left ?? 14,
  first_page_margin_top: props.initialDraft?.export_settings?.first_page_margin_top ?? 56,
})

const selectedPresetName = ref(props.initialDraft?.name ?? '')

const form = useForm({
  name: props.initialDraft?.name ?? '',
  category: props.initialDraft?.category ?? 'general',
  description: props.initialDraft?.description ?? '',
  theme_preset: props.initialDraft?.theme_preset ?? 'corporate',
  is_active: true,
  content: props.initialDraft?.content ?? '<h2>Proposta comercial</h2><p>Esta proposta consolida o âmbito técnico, os serviços laboratoriais, as condições comerciais e os critérios de aceitação acordados com {customer_name}.</p><p>{items_table}</p><p>{summary_table}</p>',
  layout_schema: layoutSchema.value,
  export_settings: exportSettings.value,
})

function syncStudioSettings() {
  form.layout_schema = { ...layoutSchema.value }
  form.export_settings = {
    paper_size: exportSettings.value.paper_size || 'A4',
    custom_page_width: exportSettings.value.paper_size === 'custom' ? Number(exportSettings.value.custom_page_width || 0) : null,
    custom_page_height: exportSettings.value.paper_size === 'custom' ? Number(exportSettings.value.custom_page_height || 0) : null,
    orientation: exportSettings.value.orientation || 'P',
    margin_top: Number(exportSettings.value.margin_top || 0),
    margin_right: Number(exportSettings.value.margin_right || 0),
    margin_bottom: Number(exportSettings.value.margin_bottom || 0),
    margin_left: Number(exportSettings.value.margin_left || 0),
    first_page_margin_top: Number(exportSettings.value.first_page_margin_top || 0),
  }
}

function submit() {
  syncStudioSettings()
  form.post(route('vap-proposals.templates.store'))
}

function cloneStudioPayload(value, fallback) {
  return JSON.parse(JSON.stringify(value ?? fallback))
}

function selectPreset(preset) {
  if (!preset?.slug) {
    return
  }

  selectedPresetName.value = preset.name ?? ''
  form.name = preset.name ?? form.name
  form.category = preset.category ?? 'general'
  form.description = preset.description ?? ''
  form.theme_preset = preset.theme_preset ?? 'corporate'
  form.content = preset.content ?? form.content
  layoutSchema.value = cloneStudioPayload(preset.layout_schema, layoutSchema.value)
  exportSettings.value = cloneStudioPayload(preset.export_settings, exportSettings.value)
  form.clearErrors()
}
</script>

<template>
  <div :class="commercialDocumentThemeClasses">
    <studio-workbench
      :title="$t('gestlab.general.labels.vap_proposal_templates.create.studio_title')"
      :intro="$t('gestlab.general.labels.vap_proposal_templates.create.studio_intro')"
      :form="form"
      :layout-schema="layoutSchema"
      :export-settings="exportSettings"
      :placeholders="props.variables"
      :presets="props.presets"
      :asset-library="props.studioAssets"
      :initial-draft-label="selectedPresetName ? $t('gestlab.general.labels.vap_proposal_templates.create.initial_draft_label', { name: selectedPresetName }) : ''"
      :preset-action-label="$t('gestlab.general.labels.vap_proposal_templates.create.preset_action_label')"
      :back-href="route('vap-proposals.templates.index')"
      :back-label="$t('gestlab.general.labels.vap_proposal_templates.create.back_label')"
      :submit-label="$t('gestlab.general.labels.vap_proposal_templates.create.submit_label')"
      :draft-preview-href="route('vap-proposals.templates.preview-draft-pdf')"
      @select-preset="selectPreset"
      @submit="submit"
    />
  </div>
</template>
