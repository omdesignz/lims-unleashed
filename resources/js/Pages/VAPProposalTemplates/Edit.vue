<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import studioWorkbench from '@/Components/proposal-template/studio-workbench.vue'
import { commercialDocumentThemeClasses } from '@/Composables/useCommercialDocumentTheme'
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

defineOptions({ layout: Layout })

const props = defineProps({
  template: {
    type: Object,
    required: true,
  },
  variables: {
    type: Array,
    default: () => [],
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
  first_page_header_html: props.template.layout_schema?.first_page_header_html ?? '',
  default_header_html: props.template.layout_schema?.default_header_html ?? '',
  footer_html: props.template.layout_schema?.footer_html ?? '',
  styles_css: props.template.layout_schema?.styles_css ?? '',
  document_font_family: props.template.layout_schema?.document_font_family ?? '"Century Gothic", DejaVu Sans, sans-serif',
  variable_catalog: props.template.layout_schema?.variable_catalog ?? [],
  canvas_blocks: props.template.layout_schema?.canvas_blocks ?? [],
  background_image_path: props.template.layout_schema?.background_image_path ?? '',
  background_size: props.template.layout_schema?.background_size ?? 'cover',
  background_position: props.template.layout_schema?.background_position ?? 'center center',
  background_repeat: props.template.layout_schema?.background_repeat ?? 'no-repeat',
  table_header_background: props.template.layout_schema?.table_header_background ?? '#143d37',
  table_header_text_color: props.template.layout_schema?.table_header_text_color ?? '#ffffff',
  table_border_color: props.template.layout_schema?.table_border_color ?? '#cbd5e1',
  table_font_size: props.template.layout_schema?.table_font_size ?? 10,
  table_cell_padding: props.template.layout_schema?.table_cell_padding ?? 8,
})

const exportSettings = ref({
  paper_size: props.template.export_settings?.paper_size ?? 'A4',
  orientation: props.template.export_settings?.orientation ?? 'P',
  margin_top: props.template.export_settings?.margin_top ?? 22,
  margin_right: props.template.export_settings?.margin_right ?? 14,
  margin_bottom: props.template.export_settings?.margin_bottom ?? 18,
  margin_left: props.template.export_settings?.margin_left ?? 14,
  first_page_margin_top: props.template.export_settings?.first_page_margin_top ?? 56,
})

const form = useForm({
  name: props.template.name ?? '',
  category: props.template.category ?? 'general',
  description: props.template.description ?? '',
  theme_preset: props.template.theme_preset ?? 'corporate',
  is_active: props.template.is_active ?? true,
  content: props.template.content ?? '',
  layout_schema: layoutSchema.value,
  export_settings: exportSettings.value,
})

function syncStudioSettings() {
  form.layout_schema = { ...layoutSchema.value }
  form.export_settings = {
    paper_size: exportSettings.value.paper_size || 'A4',
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
  form.put(route('vap-proposals.templates.update', props.template.id))
}

function cloneStudioPayload(value, fallback) {
  return JSON.parse(JSON.stringify(value ?? fallback))
}

function selectPreset(preset) {
  if (!preset?.slug) {
    return
  }

  form.category = preset.category ?? form.category
  form.description = preset.description ?? form.description
  form.theme_preset = preset.theme_preset ?? form.theme_preset
  form.content = preset.content ?? form.content
  layoutSchema.value = cloneStudioPayload(preset.layout_schema, layoutSchema.value)
  exportSettings.value = cloneStudioPayload(preset.export_settings, exportSettings.value)
  form.clearErrors()
}
</script>

<template>
  <div :class="commercialDocumentThemeClasses">
    <studio-workbench
      :title="$t('gestlab.general.labels.vap_proposal_templates.edit.studio_title')"
      :intro="$t('gestlab.general.labels.vap_proposal_templates.edit.studio_intro')"
      :form="form"
      :layout-schema="layoutSchema"
      :export-settings="exportSettings"
      :placeholders="props.variables"
      :presets="props.presets"
      :asset-library="props.studioAssets"
      :back-href="route('vap-proposals.templates.show', props.template.id)"
      :back-label="$t('gestlab.general.labels.vap_proposal_templates.edit.back_label')"
      :preset-action-label="$t('gestlab.general.labels.vap_proposal_templates.edit.preset_action_label')"
      :submit-label="$t('gestlab.general.labels.vap_proposal_templates.edit.submit_label')"
      @select-preset="selectPreset"
      @submit="submit"
    />
  </div>
</template>
