<template>
  <div class="space-y-8">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-700">Estúdios de saída</p>
          <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">Estúdio executivo, de propostas e de relatórios analíticos</h1>
          <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
            Centralize layouts de relatórios executivos, propostas e relatórios analíticos. Os modelos podem ser renderizados internamente ou ligados a designs premium no Canva, preservando cabeçalhos, rodapés e paginação.
          </p>
        </div>
        <button
          type="button"
          class="rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
          @click="openCreate"
        >
          Novo modelo de estúdio
        </button>
      </div>

      <div class="mt-6 grid gap-4 md:grid-cols-5">
        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <div class="text-sm text-slate-500">Total de modelos</div>
          <div class="mt-2 text-2xl font-semibold text-slate-900">{{ summary.total }}</div>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <div class="text-sm text-slate-500">Relatórios analíticos</div>
          <div class="mt-2 text-2xl font-semibold text-slate-900">{{ summary.analysis }}</div>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <div class="text-sm text-slate-500">Relatórios executivos</div>
          <div class="mt-2 text-2xl font-semibold text-slate-900">{{ summary.executive }}</div>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <div class="text-sm text-slate-500">Propostas</div>
          <div class="mt-2 text-2xl font-semibold text-slate-900">{{ summary.proposal }}</div>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <div class="text-sm text-slate-500">Ligados ao Canva</div>
          <div class="mt-2 text-2xl font-semibold text-slate-900">{{ summary.canva }}</div>
        </article>
      </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">{{ editingTemplate?.id ? 'Editar modelo' : 'Novo modelo' }}</h2>
            <p class="mt-1 text-sm text-slate-500">Configure cabeçalho da primeira página, cabeçalhos recorrentes, rodapés, paginação e padrões de renderização num único modelo governado.</p>
          </div>
          <button type="button" class="text-sm font-medium text-slate-500 hover:text-slate-700" @click="resetForm">
            Limpar formulário
          </button>
        </div>

        <form class="mt-6 space-y-4" @submit.prevent="submit">
          <div>
              <label class="mb-2 block text-sm font-medium text-slate-700">Nome</label>
            <input v-model="form.name" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
            <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
          </div>

          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-700">Tipo de estúdio</label>
              <select v-model="form.studio_type" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                <option value="analysis">Relatório analítico</option>
                <option value="executive">Relatório executivo</option>
                <option value="proposal">Proposta</option>
              </select>
            </div>
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-700">Renderização</label>
              <select v-model="form.renderer" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                <option value="internal">Renderizador interno</option>
                <option value="canva">Ligado ao Canva</option>
              </select>
            </div>
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-700">Estado</label>
              <select v-model="form.status" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                <option value="draft">Rascunho</option>
                <option value="active">Activo</option>
                <option value="archived">Arquivado</option>
              </select>
            </div>
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-700">Tema base</label>
              <input v-model="form.theme_preset" type="text" placeholder="institucional, executivo, laboratorial..." class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
            </div>
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">URL do design no Canva</label>
            <input v-model="form.canva_design_url" type="url" placeholder="https://www.canva.com/design/..." class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Descrição</label>
            <textarea v-model="form.description" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"></textarea>
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Esquema de layout (JSON)</label>
            <textarea v-model="layoutSchemaJson" rows="8" class="w-full rounded-2xl border border-slate-300 px-4 py-3 font-mono text-xs"></textarea>
            <p class="mt-1 text-xs text-slate-500">Use chaves como <code>first_page_header_html</code>, <code>default_header_html</code>, <code>footer_html</code>, <code>styles_css</code> e margens de exportação para controlar documentos com várias páginas.</p>
          </div>

          <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
            <label class="inline-flex items-center gap-3 text-sm text-slate-700">
              <input v-model="form.is_default" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
              Definir como padrão para este tipo de estúdio
            </label>
          </div>

          <div class="flex flex-col gap-3 sm:flex-row">
            <button type="submit" class="rounded-2xl bg-cyan-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-800" :disabled="form.processing">
              {{ form.processing ? 'A guardar...' : (editingTemplate?.id ? 'Actualizar modelo' : 'Criar modelo') }}
            </button>
            <button v-if="editingTemplate?.id" type="button" class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="resetForm">
              Cancelar edição
            </button>
          </div>
        </form>
      </div>

      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">Modelos guardados</h2>
            <p class="mt-1 text-sm text-slate-500">Os modelos já controlam a saída PDF em produção e podem evoluir para layouts premium com Canva sem perder rastreabilidade.</p>
          </div>
        </div>

        <div v-if="templates.length" class="mt-6 space-y-4">
          <article v-for="template in templates" :key="template.id" class="rounded-2xl border border-slate-200 p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
              <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">{{ template.studio_type }}</span>
                  <span class="rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-semibold text-cyan-700">{{ template.renderer }}</span>
                  <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ template.status }}</span>
                  <span v-if="template.is_default" class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">padrão</span>
                </div>
                <h3 class="text-lg font-semibold text-slate-900">{{ template.name }}</h3>
                <p class="text-sm leading-6 text-slate-600">{{ template.description || 'Sem descrição registada.' }}</p>
                <div class="text-xs text-slate-400">
                  Actualizado em {{ formatDate(template.updated_at) }}{{ template.updated_by ? ` · ${template.updated_by}` : '' }}
                </div>
                <a v-if="template.canva_design_url" :href="template.canva_design_url" target="_blank" rel="noopener noreferrer" class="inline-flex text-sm font-medium text-cyan-700 hover:text-cyan-800">
                  Abrir design no Canva
                </a>
              </div>
              <div class="flex flex-wrap gap-2">
                <button type="button" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="editTemplate(template)">
                  Editar
                </button>
                <button type="button" class="rounded-xl border border-rose-200 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50" @click="destroyTemplate(template)">
                  Arquivar
                </button>
              </div>
            </div>
          </article>
        </div>
        <div v-else class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500">
          Ainda não existem modelos de estúdio configurados.
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Layout from '@/Shared/Layouts/Layout.vue'

defineOptions({
  layout: Layout,
})

const props = defineProps({
  templates: {
    type: Array,
    default: () => [],
  },
  summary: {
    type: Object,
    default: () => ({ total: 0, analysis: 0, executive: 0, proposal: 0, canva: 0 }),
  },
})

const editingTemplate = ref(null)
const defaultLayoutSchema = {
  first_page_header_html: '<div style="border-bottom:1px solid #0f172a; padding-bottom:10px;"><h2 style="margin:0;">{{lab_name}}</h2><p style="margin-top:6px;">{{document_code}}</p></div>',
  default_header_html: '<div style="font-size:10px;">{{document_code}} · {{issue_date}}</div>',
  footer_html: '<div style="font-size:9px; color:#334155;">Documento controlado · Página {PAGENO}/{nbpg}</div>',
  styles_css: 'table { width: 100%; border-collapse: collapse; } th, td { padding: 6px; border: 1px solid #cbd5e1; }',
  sections: [
    { key: 'summary', label: 'Resumo', visible: true },
    { key: 'results', label: 'Tabela de resultados', visible: true },
    { key: 'interpretation', label: 'Interpretação', visible: true },
  ],
}

const form = useForm({
  name: '',
  studio_type: 'analysis',
  renderer: 'internal',
  status: 'draft',
  is_default: false,
  theme_preset: '',
  canva_design_url: '',
  description: '',
  layout_schema: structuredClone(defaultLayoutSchema),
  export_settings: {
    paper_size: 'A4',
    orientation: 'P',
    margin_top: 20,
    margin_bottom: 20,
    margin_left: 14,
    margin_right: 14,
    first_page_margin_top: 52,
  },
})

const layoutSchemaJson = ref(JSON.stringify(defaultLayoutSchema, null, 2))

const resetForm = () => {
  editingTemplate.value = null
  form.reset()
  form.studio_type = 'analysis'
  form.renderer = 'internal'
  form.status = 'draft'
  form.is_default = false
  form.layout_schema = structuredClone(defaultLayoutSchema)
  form.export_settings = {
    paper_size: 'A4',
    orientation: 'P',
    margin_top: 20,
    margin_bottom: 20,
    margin_left: 14,
    margin_right: 14,
    first_page_margin_top: 52,
  }
  layoutSchemaJson.value = JSON.stringify(defaultLayoutSchema, null, 2)
}

const openCreate = () => {
  resetForm()
}

const editTemplate = (template) => {
  editingTemplate.value = template
  form.name = template.name
  form.studio_type = template.studio_type
  form.renderer = template.renderer
  form.status = template.status
  form.is_default = Boolean(template.is_default)
  form.theme_preset = template.theme_preset || ''
  form.canva_design_url = template.canva_design_url || ''
  form.description = template.description || ''
  form.layout_schema = template.layout_schema || structuredClone(defaultLayoutSchema)
  form.export_settings = template.export_settings || {}
  layoutSchemaJson.value = JSON.stringify(form.layout_schema, null, 2)
}

const submit = () => {
  try {
    form.layout_schema = JSON.parse(layoutSchemaJson.value || '{}')
  } catch (error) {
    form.setError('layout_schema', 'O JSON do layout não é válido.')
    return
  }

  const options = {
    preserveScroll: true,
    onSuccess: () => resetForm(),
  }

  if (editingTemplate.value?.id) {
    form.put(route('report-studios.update', editingTemplate.value.id), options)
    return
  }

  form.post(route('report-studios.store'), options)
}

const destroyTemplate = (template) => {
  if (!window.confirm(`Arquivar o template "${template.name}"?`)) {
    return
  }

  form.delete(route('report-studios.destroy', template.id), {
    preserveScroll: true,
    onSuccess: () => {
      if (editingTemplate.value?.id === template.id) {
        resetForm()
      }
    },
  })
}

const formatDate = (value) => {
  if (!value) {
    return 'agora'
  }

  return new Date(value).toLocaleString('pt-PT')
}
</script>
