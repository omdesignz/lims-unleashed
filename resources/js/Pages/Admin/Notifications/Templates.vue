<script setup>
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import BaseTextarea from '@/Components/base/BaseTextarea.vue'
import CheckboxInput from '@/Components/base/CheckboxInput.vue'
import NotificationAdminHeader from '@/Components/notifications/NotificationAdminHeader.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { useForm } from '@inertiajs/vue3'
import {
  ArrowPathIcon,
  CheckCircleIcon,
  EnvelopeIcon,
  MagnifyingGlassIcon,
  SignalIcon,
} from '@heroicons/vue/24/outline'
import { computed, ref, watch } from 'vue'

defineOptions({ layout: Layout })

const props = defineProps({
  templates: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  laboratory: { type: Object, required: true },
  canEdit: { type: Boolean, default: false },
})

const search = ref('')
const category = ref('')
const selectedKey = ref(props.templates[0]?.key ?? null)

const filteredTemplates = computed(() => props.templates.filter((template) => {
  const matchesCategory = !category.value || template.category === category.value
  const term = search.value.trim().toLocaleLowerCase()
  const matchesSearch = !term || [template.name, template.key, template.description]
    .filter(Boolean)
    .some((value) => value.toLocaleLowerCase().includes(term))

  return matchesCategory && matchesSearch
}))

const selectedTemplate = computed(() => props.templates.find((template) => template.key === selectedKey.value) ?? null)
const categoryOptions = computed(() => [
  { value: '', label: 'Todas as áreas' },
  ...props.categories.map((item) => ({ value: item, label: item })),
])
const priorityOptions = [
  { value: 'low', label: 'Baixa' },
  { value: 'normal', label: 'Normal' },
  { value: 'high', label: 'Alta' },
  { value: 'urgent', label: 'Urgente' },
]

const form = useForm({
  title_template: '',
  in_app_template: '',
  email_subject_template: '',
  email_template: '',
  action_label_template: '',
  action_url_template: '',
  channels: [],
  priority: 'normal',
  enabled: true,
})

const resetForm = useForm({})
const busy = computed(() => form.processing || resetForm.processing)

const loadTemplate = (template) => {
  if (!template) return

  form.defaults({
    title_template: template.title_template ?? '',
    in_app_template: template.in_app_template ?? '',
    email_subject_template: template.email_subject_template ?? '',
    email_template: template.email_template ?? '',
    action_label_template: template.action_label_template ?? '',
    action_url_template: template.action_url_template ?? '',
    channels: [...(template.channels ?? [])],
    priority: template.priority ?? 'normal',
    enabled: Boolean(template.enabled),
  })
  form.reset()
  form.clearErrors()
}

watch([() => props.laboratory.id, selectedKey], () => loadTemplate(selectedTemplate.value), { immediate: true })

function selectTemplate(template) {
  if (busy.value || template.key === selectedKey.value) return
  if (form.isDirty && !window.confirm('Descartar as alterações não guardadas?')) return
  selectedKey.value = template.key
}

const toggleChannel = (channel) => {
  if (busy.value || !props.canEdit) return
  form.channels = form.channels.includes(channel)
    ? form.channels.filter((value) => value !== channel)
    : [...form.channels, channel]
}

const variableToken = (variable) => `{{${variable}}}`

function save() {
  if (!selectedTemplate.value || busy.value || !props.canEdit || !form.isDirty) return

  form.put(route('admin.notification-templates.update', { key: selectedTemplate.value.key }), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => loadTemplate(selectedTemplate.value),
  })
}

function restorePreset() {
  if (!selectedTemplate.value?.is_overridden || busy.value || !props.canEdit) return
  if (!window.confirm('Remover a personalização deste laboratório e restaurar o modelo partilhado?')) return
  resetForm.delete(route('admin.notification-templates.destroy', { key: selectedTemplate.value.key }), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => loadTemplate(selectedTemplate.value),
  })
}
</script>

<template>
  <div class="space-y-5">
    <NotificationAdminHeader
      title="Modelos de comunicação"
      :description="`Personalizações de ${laboratory.name}. Os modelos de origem permanecem partilhados.`"
    />

    <div class="grid min-h-[42rem] gap-5 xl:grid-cols-[20rem_minmax(0,1fr)]">
      <aside class="ds-panel overflow-hidden">
        <header class="border-b border-[var(--ds-border)] p-4">
          <p class="ds-kicker">Catálogo</p>
          <div class="relative mt-3">
            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" />
            <BaseInput v-model="search" :disabled="busy" aria-label="Pesquisar modelos" class="ds-field pl-9" type="search" placeholder="Pesquisar modelos" />
          </div>
          <BaseSelect v-model="category" :disabled="busy" aria-label="Filtrar área" class="mt-2" :options="categoryOptions" />
        </header>

        <div class="max-h-[34rem] overflow-y-auto p-2">
          <button
            v-for="template in filteredTemplates"
            :key="template.key"
            type="button"
            class="w-full rounded-md border px-3 py-3 text-left transition-colors duration-150 disabled:opacity-50"
            :class="selectedKey === template.key
              ? 'border-[rgb(var(--primary-500-rgb)/0.5)] bg-[rgb(var(--primary-50-rgb)/0.8)] dark:bg-[rgb(var(--primary-500-rgb)/0.1)]'
              : 'border-transparent hover:border-[var(--ds-border)] hover:bg-[var(--ds-panel-subtle)]'"
            :disabled="busy"
            :aria-pressed="selectedKey === template.key"
            @click="selectTemplate(template)"
          >
            <span class="flex items-center justify-between gap-2">
              <span class="truncate text-sm font-bold text-[var(--ds-text)]">{{ template.name }}</span>
              <span class="h-2 w-2 shrink-0 rounded-full" :class="template.enabled ? 'bg-emerald-500' : 'bg-gray-300'" />
            </span>
            <span class="mt-1 block truncate text-xs font-semibold text-[var(--ds-text-muted)]">{{ template.key }}</span>
          </button>

          <p v-if="!filteredTemplates.length" class="px-3 py-8 text-center text-sm font-semibold text-[var(--ds-text-muted)]">
            Nenhum modelo corresponde aos filtros.
          </p>
        </div>
      </aside>

      <form v-if="selectedTemplate" class="ds-panel overflow-hidden" :aria-busy="busy" @submit.prevent="save">
        <fieldset class="contents" :disabled="busy || !canEdit">
          <header class="flex flex-col gap-4 border-b border-[var(--ds-border)] px-5 py-5 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <p class="ds-kicker">{{ selectedTemplate.category }} · {{ laboratory.name }}</p>
              <p class="ds-copy text-sm" role="status">{{ selectedTemplate.is_overridden ? 'Personalização deste laboratório' : 'Modelo partilhado · sem personalização' }}</p>
              <h2 class="ds-heading mt-1 text-xl">{{ selectedTemplate.name }}</h2>
              <p class="ds-copy mt-1 text-sm">{{ selectedTemplate.description }}</p>
            </div>
            <label class="flex shrink-0 items-center gap-3 text-sm font-bold text-[var(--ds-text)]">
              <CheckboxInput v-model="form.enabled" />
              Modelo activo
            </label>
          </header>
          <p v-if="form.errors.enabled" role="alert" class="ds-field-error px-5">{{ form.errors.enabled }}</p>
          <div class="grid gap-6 p-5 lg:grid-cols-2">
            <section class="space-y-4">
              <div>
                <p class="ds-kicker flex items-center gap-2"><SignalIcon class="h-4 w-4" /> Aplicação</p>
                <p class="ds-copy mt-1 text-sm">Mensagem curta para o centro de notificações e alertas em tempo real.</p>
              </div>
              <BaseInput id="template-title" v-model="form.title_template" label="Título" :error="form.errors.title_template" maxlength="255" />
              <BaseTextarea id="template-message" v-model="form.in_app_template" label="Mensagem" :error="form.errors.in_app_template" rows="5" maxlength="2000" />
              <div class="grid gap-4 sm:grid-cols-2">
                <BaseInput id="template-action" v-model="form.action_label_template" label="Acção" :error="form.errors.action_label_template" maxlength="100" placeholder="Abrir registo" />
                <BaseInput id="template-url" v-model="form.action_url_template" label="Destino definido pelo sistema" readonly :error="form.errors.action_url_template" />
              </div>
            </section>
            <section class="space-y-4">
              <div>
                <p class="ds-kicker flex items-center gap-2"><EnvelopeIcon class="h-4 w-4" /> Correio electrónico</p>
                <p class="ds-copy mt-1 text-sm">Conteúdo mais detalhado enviado quando o canal de email estiver activo.</p>
              </div>
              <BaseInput id="template-subject" v-model="form.email_subject_template" label="Assunto" :error="form.errors.email_subject_template" maxlength="255" />
              <BaseTextarea id="template-email" v-model="form.email_template" label="Corpo da mensagem" :error="form.errors.email_template" rows="5" maxlength="5000" />
              <BaseSelect id="template-priority" v-model="form.priority" :disabled="busy || !canEdit" label="Prioridade" :error="form.errors.priority" :options="priorityOptions" />
            </section>
          </div>
          <section class="border-t border-[var(--ds-border)] px-5 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
              <div>
                <p class="ds-field-label">Canais de entrega</p>
                <div class="mt-2 flex flex-wrap gap-2">
                  <button
                    v-for="channel in ['database', 'broadcast', 'mail']"
                    :key="channel"
                    type="button"
                    class="ds-button"
                    :class="form.channels.includes(channel) ? 'ds-button-primary' : 'ds-button-secondary'"
                    :aria-pressed="form.channels.includes(channel)"
                    @click="toggleChannel(channel)"
                  >
                    <CheckCircleIcon v-if="form.channels.includes(channel)" class="h-4 w-4" />
                    {{ { database: 'Caixa de entrada', broadcast: 'Tempo real', mail: 'Email' }[channel] }}
                  </button>
                </div>
                <p v-if="form.errors.channels" class="ds-field-error mt-2">{{ form.errors.channels }}</p>
              </div>
              <div class="flex shrink-0 gap-2">
                <button v-if="selectedTemplate.is_overridden && canEdit" type="button" class="ds-button ds-button-secondary" :disabled="busy" @click="restorePreset">
                  {{ resetForm.processing ? 'A restaurar…' : 'Usar modelo partilhado' }}
                </button>
                <button type="button" class="ds-button ds-button-secondary" :disabled="busy || !form.isDirty" @click="form.reset()">
                  Descartar alterações
                </button>
                <button type="submit" class="ds-button ds-button-primary" :disabled="busy || !canEdit || !form.isDirty">
                  <ArrowPathIcon v-if="form.processing" class="h-4 w-4 animate-spin motion-reduce:animate-none" />
                  {{ form.processing ? 'A guardar…' : 'Guardar para este laboratório' }}
                </button>
              </div>
            </div>
            <div v-if="selectedTemplate.variables?.length" class="mt-4 border-t border-[var(--ds-border)] pt-4">
              <p class="ds-field-label">Variáveis disponíveis</p>
              <div class="mt-2 flex flex-wrap gap-2">
                <code v-for="variable in selectedTemplate.variables" :key="variable" class="rounded bg-[var(--ds-panel-subtle)] px-2 py-1 text-xs font-bold text-[var(--ds-text-muted)]">{{ variableToken(variable) }}</code>
              </div>
            </div>
          </section>
        </fieldset>
        <p v-if="!canEdit" class="ds-copy p-5 text-sm">Acesso de leitura. É necessária permissão para editar as definições.</p>
        <p v-for="(error, field) in resetForm.errors" :key="field" role="alert" class="ds-field-error p-5">{{ error }}</p>
      </form>
    </div>
  </div>
</template>
