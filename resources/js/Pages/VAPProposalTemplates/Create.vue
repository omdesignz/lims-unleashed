<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import fancyTextarea from '@/Components/fancy-textarea.vue'
import { computed, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

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
})

const layoutSchema = ref({
  first_page_header_html: props.initialDraft?.layout_schema?.first_page_header_html ?? '<div style="border-bottom:1px solid #0f172a; padding-bottom:12px;"><h2 style="margin:0;">{{lab_name}}</h2><p style="margin-top:6px;">{{document_code}}</p></div>',
  default_header_html: props.initialDraft?.layout_schema?.default_header_html ?? '<div style="font-size:10px;">{{document_code}} · {{customer_name}}</div>',
  footer_html: props.initialDraft?.layout_schema?.footer_html ?? '<div style="font-size:9px; color:#334155;">Documento controlado · Página {PAGENO}/{nbpg}</div>',
  styles_css: props.initialDraft?.layout_schema?.styles_css ?? '',
  background_image_path: props.initialDraft?.layout_schema?.background_image_path ?? '',
})

const exportSettings = ref({
  margin_top: props.initialDraft?.export_settings?.margin_top ?? 22,
  margin_right: props.initialDraft?.export_settings?.margin_right ?? 14,
  margin_bottom: props.initialDraft?.export_settings?.margin_bottom ?? 18,
  margin_left: props.initialDraft?.export_settings?.margin_left ?? 14,
  first_page_margin_top: props.initialDraft?.export_settings?.first_page_margin_top ?? 56,
})

const form = useForm({
  name: props.initialDraft?.name ?? '',
  category: props.initialDraft?.category ?? 'general',
  description: props.initialDraft?.description ?? '',
  theme_preset: props.initialDraft?.theme_preset ?? 'corporate',
  is_active: true,
  content: props.initialDraft?.content ?? '<h2>Proposta comercial</h2><p>Introduza aqui a proposta, os termos técnicos, a decisão aplicada e a tabela de serviços.</p><p>{items_table}</p>',
  layout_schema: layoutSchema.value,
  export_settings: exportSettings.value,
})

const contentLength = computed(() => form.content?.length ?? 0)
const previewContent = computed(() => {
  return (form.content || '')
    .replaceAll('{proposal_number}', 'PR-2026-001')
    .replaceAll('{customer_name}', 'Cliente Exemplo')
    .replaceAll('{items_table}', '<table style="width:100%; border-collapse:collapse;"><tr><th style="border:1px solid #cbd5e1; padding:6px;">Serviço</th><th style="border:1px solid #cbd5e1; padding:6px;">Valor</th></tr><tr><td style="border:1px solid #cbd5e1; padding:6px;">Análise</td><td style="border:1px solid #cbd5e1; padding:6px;">AOA 25.000,00</td></tr></table>')
  })

function syncStudioSettings() {
  form.layout_schema = { ...layoutSchema.value }
  form.export_settings = {
    margin_top: Number(exportSettings.value.margin_top || 0),
    margin_right: Number(exportSettings.value.margin_right || 0),
    margin_bottom: Number(exportSettings.value.margin_bottom || 0),
    margin_left: Number(exportSettings.value.margin_left || 0),
    first_page_margin_top: Number(exportSettings.value.first_page_margin_top || 0),
  }
}

function insertPlaceholder(placeholder) {
  form.content = `${form.content || ''} ${placeholder}`.trim()
}

function submit() {
  syncStudioSettings()

  form.post(route('vap-proposals.templates.store'))
}
</script>

<template>
  <Head title="Novo modelo de proposta" />

  <div class="space-y-8">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Studio de propostas</h1>
          <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-400">
            Crie um modelo flexível com editor rico, cabeçalhos e rodapés por página, estilos dedicados e controlo de margens para exportação premium.
          </p>
          <p v-if="props.initialDraft?.slug" class="mt-3 inline-flex rounded-full bg-primary-50 px-3 py-1 text-xs font-medium text-primary-700 dark:bg-primary-500/10 dark:text-primary-300">
            Pré-definido: {{ props.initialDraft.name }}
          </p>
        </div>
        <Link
          :href="route('vap-proposals.templates.index')"
          class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
        >
          Voltar à biblioteca
        </Link>
      </div>
    </div>

    <div v-if="props.presets.length" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
      <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Começar a partir de um modelo pré-definido</h2>
      <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Escolha uma base pronta para acelerar a construção da proposta.</p>
      <div class="mt-5 grid gap-4 lg:grid-cols-3">
        <Link
          v-for="preset in props.presets"
          :key="preset.slug"
          :href="route('vap-proposals.templates.create', { preset: preset.slug })"
          class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 text-left transition hover:border-primary-400 hover:bg-primary-50 dark:border-slate-700 dark:bg-slate-950/50 dark:hover:border-primary-500/50 dark:hover:bg-primary-500/5"
        >
          <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ preset.name }}</div>
          <div class="mt-1 text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ preset.category }}</div>
          <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">{{ preset.description }}</p>
        </Link>
      </div>
    </div>

    <div class="grid gap-8 xl:grid-cols-[minmax(0,1.7fr)_minmax(360px,0.9fr)]">
      <section class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <div class="grid gap-5 md:grid-cols-2">
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-900 dark:text-slate-100">Nome do modelo</label>
              <input
                v-model="form.name"
                type="text"
                class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
              />
              <p v-if="form.errors.name" class="mt-2 text-xs text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
            </div>

            <div>
              <label class="mb-2 block text-sm font-medium text-slate-900 dark:text-slate-100">Categoria</label>
              <input
                v-model="form.category"
                type="text"
                class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
              />
            </div>

            <div>
              <label class="mb-2 block text-sm font-medium text-slate-900 dark:text-slate-100">Tema visual</label>
              <input
                v-model="form.theme_preset"
                type="text"
                class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
              />
            </div>

            <div class="flex items-end">
              <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-300">
                <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-primary-700 focus:ring-primary-500 dark:border-slate-600" />
                Modelo activo para novas propostas
              </label>
            </div>
          </div>

          <div class="mt-5">
            <label class="mb-2 block text-sm font-medium text-slate-900 dark:text-slate-100">Descrição operacional</label>
            <textarea
              v-model="form.description"
              rows="3"
              class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
              placeholder="Explique quando este modelo deve ser usado e que tipo de proposta cobre."
            />
          </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
              <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Conteúdo da proposta</h2>
              <p class="text-sm text-slate-500 dark:text-slate-400">Caracteres: {{ contentLength }}</p>
            </div>
          </div>

          <fancy-textarea v-model="form.content" />
          <p v-if="form.errors.content" class="mt-2 text-xs text-red-600 dark:text-red-400">{{ form.errors.content }}</p>
        </div>
      </section>

      <aside class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Variáveis disponíveis</h2>
          <div class="mt-4 flex flex-wrap gap-2">
            <button
              v-for="placeholder in props.variables"
              :key="placeholder"
              type="button"
              @click="insertPlaceholder(placeholder)"
              class="rounded-full border border-primary-200 bg-primary-50 px-3 py-1.5 text-xs font-medium text-primary-800 transition hover:bg-primary-100 dark:border-primary-900/40 dark:bg-primary-950/40 dark:text-primary-300"
            >
              {{ placeholder }}
            </button>
          </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Layout multi-página</h2>
          <div class="mt-4 space-y-4">
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-900 dark:text-slate-100">Cabeçalho da primeira página</label>
              <textarea v-model="layoutSchema.first_page_header_html" rows="4" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-xs text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </div>
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-900 dark:text-slate-100">Cabeçalho padrão</label>
              <textarea v-model="layoutSchema.default_header_html" rows="3" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-xs text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </div>
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-900 dark:text-slate-100">Rodapé</label>
              <textarea v-model="layoutSchema.footer_html" rows="3" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-xs text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </div>
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-900 dark:text-slate-100">Imagem de fundo</label>
              <input v-model="layoutSchema.background_image_path" type="text" placeholder="URL ou caminho do storage" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </div>
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-900 dark:text-slate-100">CSS adicional</label>
              <textarea v-model="layoutSchema.styles_css" rows="4" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-xs text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </div>
          </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Exportação</h2>
          <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <label class="text-sm text-slate-700 dark:text-slate-300">Margem superior
              <input v-model="exportSettings.margin_top" type="number" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </label>
            <label class="text-sm text-slate-700 dark:text-slate-300">Margem direita
              <input v-model="exportSettings.margin_right" type="number" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </label>
            <label class="text-sm text-slate-700 dark:text-slate-300">Margem inferior
              <input v-model="exportSettings.margin_bottom" type="number" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </label>
            <label class="text-sm text-slate-700 dark:text-slate-300">Margem esquerda
              <input v-model="exportSettings.margin_left" type="number" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </label>
            <label class="text-sm text-slate-700 dark:text-slate-300 sm:col-span-2">Margem do topo da primeira página
              <input v-model="exportSettings.first_page_margin_top" type="number" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
            </label>
          </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Pré-visualização rápida</h2>
          <div class="prose mt-4 max-w-none rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm dark:border-slate-700 dark:bg-slate-950 dark:prose-invert" v-html="previewContent" />
        </div>

        <div class="flex flex-col gap-3">
          <button
            type="button"
            @click="submit"
            :disabled="form.processing"
            class="inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-primary-900 to-primary-700 px-4 py-3 text-sm font-semibold text-white shadow-lg transition hover:from-primary-800 hover:to-primary-600 disabled:cursor-not-allowed disabled:opacity-60 dark:from-primary-700 dark:to-primary-500"
          >
            {{ form.processing ? 'A guardar…' : 'Guardar modelo' }}
          </button>
        </div>
      </aside>
    </div>
  </div>
</template>
