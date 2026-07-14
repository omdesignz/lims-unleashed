<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
          <div class="min-w-0 max-w-4xl">
            <Link :href="route('proficiency_tests.index')" class="ds-button ds-button-ghost px-0">
              <ArrowLeftIcon class="h-4 w-4" />
              Voltar aos ensaios de proficiência
            </Link>
            <div class="mt-4 flex flex-wrap gap-2">
              <span class="ds-badge ds-badge-neutral">{{ schemeLabel(test.scheme_type) }}</span>
              <span class="ds-badge ds-badge-info">{{ roleLabel(test.role) }}</span>
              <span class="ds-badge" :class="statusBadgeClass(test.status)">{{ statusLabel(test.status) }}</span>
            </div>
            <p class="ds-kicker mt-5">Garantia da validade dos resultados</p>
            <h1 class="ds-heading mt-1 text-2xl">{{ test.name }}</h1>
            <p class="ds-copy mt-2 max-w-3xl text-sm">
              {{ test.scope || 'Registe participantes, parâmetros, resultados, z-scores e evidências para manter a rastreabilidade da ronda.' }}
            </p>
          </div>

          <div class="ds-command-surface w-full p-4 lg:max-w-xs">
            <p class="ds-kicker">Resultado global</p>
            <p class="ds-heading mt-2 text-xl">{{ outcomeLabel(form.outcome) }}</p>
            <p class="ds-copy mt-1 text-sm">z-score {{ form.z_score || '—' }}</p>
          </div>
      </div>

      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="card in summaryCards" :key="card.label" class="border-b border-[var(--ds-border)] px-4 py-3 sm:[&:nth-child(odd)]:border-r sm:[&:nth-child(n+3)]:border-b-0 xl:border-b-0 xl:border-r xl:last:border-r-0">
          <div class="flex items-center justify-between gap-3">
            <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ card.label }}</dt>
            <component :is="card.icon" class="h-5 w-5 text-[var(--ds-text-soft)]" />
          </div>
          <dd class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ card.value }}</dd>
          <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ card.caption }}</p>
        </div>
      </dl>
    </section>

    <section class="grid gap-5 xl:grid-cols-3">
      <article class="ds-panel overflow-hidden p-5">
        <div class="flex items-center justify-between gap-3">
          <div>
            <h2 class="ds-heading text-base">Distribuição dos z-scores</h2>
            <p class="ds-copy mt-1 text-sm">Comparação por participante e parâmetro.</p>
          </div>
          <ChartBarIcon class="h-5 w-5 text-[var(--ds-text-soft)]" />
        </div>
        <ChartWrapper class="mt-5" type="bar" height="340" :series="zScoreChartSeries" :options="zScoreChartOptions" />
      </article>

      <article class="ds-panel overflow-hidden p-5">
        <div class="flex items-center justify-between gap-3">
          <div>
            <h2 class="ds-heading text-base">Performance</h2>
            <p class="ds-copy mt-1 text-sm">Classificação automática por z-score.</p>
          </div>
          <CheckBadgeIcon class="h-5 w-5 text-[var(--ds-text-soft)]" />
        </div>
        <ChartWrapper class="mt-5" type="donut" height="320" :series="performanceChartSeries" :options="performanceChartOptions" />
      </article>

      <article class="ds-panel overflow-hidden p-5">
        <div class="flex items-center justify-between gap-3">
          <div>
            <h2 class="ds-heading text-base">Estado dos participantes</h2>
            <p class="ds-copy mt-1 text-sm">Acompanhamento operacional da submissão e revisão.</p>
          </div>
          <UserGroupIcon class="h-5 w-5 text-[var(--ds-text-soft)]" />
        </div>
        <ChartWrapper class="mt-5" type="donut" height="320" :series="participantStatusChartSeries" :options="participantStatusChartOptions" />
      </article>
    </section>

    <form class="space-y-6" @submit.prevent="submit">
      <section class="ds-panel overflow-hidden p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="ds-kicker">Configuração da ronda</p>
            <h2 class="ds-heading mt-1 text-base">Participantes e parâmetros</h2>
            <p class="ds-copy mt-1 text-sm">Organize a ronda interna ou externa antes de lançar resultados.</p>
          </div>
          <div class="flex flex-wrap gap-2">
            <a :href="route('proficiency_tests.results.template', test.id)" class="ds-button ds-button-secondary">
              <DocumentArrowDownIcon class="h-4 w-4" />
              Modelo Excel
            </a>
            <button type="button" class="ds-button ds-button-secondary" @click="triggerImport">
              <ArrowUpTrayIcon class="h-4 w-4" />
              Importar resultados
            </button>
            <FileInput ref="importInput" type="file" accept=".xlsx,.xls,.csv,.txt" class="hidden" @change="importResults" />
            <button type="button" class="ds-button ds-button-secondary" @click="addParticipant">Adicionar participante</button>
            <button type="button" class="ds-button ds-button-secondary" @click="addParameter">Adicionar parâmetro</button>
            <button type="button" class="ds-button ds-button-primary" @click="ensureResultRows">Sincronizar matriz</button>
          </div>
        </div>

        <div class="mt-5 grid gap-5 xl:grid-cols-2">
          <div class="space-y-3">
            <p class="ds-kicker">Participantes</p>
            <div v-for="(participant, index) in form.participants" :key="`participant-${index}`" class="ds-card grid gap-2 p-3 sm:grid-cols-[0.8fr,1.2fr,0.9fr,1fr,auto]">
              <BaseInput v-model="participant.code" class="ds-field" placeholder="Código" @blur="ensureResultRows" />
              <BaseInput v-model="participant.name" class="ds-field" placeholder="Laboratório / participante" @blur="ensureResultRows" />
              <BaseSelect v-model="participant.status" class="ds-field">
                <option v-for="option in participantStatusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
              </BaseSelect>
              <BaseInput v-model="participant.contact" class="ds-field" placeholder="Contacto" />
              <button type="button" class="ds-table-action ds-table-action-danger" @click="removeParticipant(index)">Remover</button>
            </div>
          </div>

          <div class="space-y-3">
            <p class="ds-kicker">Parâmetros</p>
            <div v-for="(parameter, index) in form.parameters" :key="`parameter-${index}`" class="ds-card grid gap-2 p-3 sm:grid-cols-[0.8fr,1.2fr,0.7fr,0.9fr,auto]">
              <BaseInput v-model="parameter.code" class="ds-field" placeholder="Código" @blur="ensureResultRows" />
              <BaseInput v-model="parameter.name" class="ds-field" placeholder="Parâmetro" @blur="ensureResultRows" />
              <BaseInput v-model="parameter.unit" class="ds-field" placeholder="Unidade" />
              <BaseInput v-model="parameter.assigned_value" type="number" step="0.0001" class="ds-field" placeholder="Valor alvo" />
              <button type="button" class="ds-table-action ds-table-action-danger" @click="removeParameter(index)">Remover</button>
            </div>
          </div>
        </div>
      </section>

      <section class="ds-panel overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="ds-kicker">Matriz analítica</p>
            <h2 class="ds-heading mt-1 text-base">Registo de resultados</h2>
            <p class="ds-copy mt-1 text-sm">Registe valor obtido, z-score, observações e classificação por resultado.</p>
          </div>
          <span class="ds-badge ds-badge-neutral">
            {{ resultCount }} resultados
          </span>
        </div>

        <div v-if="form.participant_results.length" class="divide-y divide-[var(--ds-border)]">
          <article v-for="(participant, participantIndex) in form.participant_results" :key="`result-${participantIndex}`" class="p-5">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <p class="text-sm font-bold text-[var(--ds-text)]">{{ participant.name || participant.code || 'Participante sem nome' }}</p>
                <p class="text-xs font-semibold text-[var(--ds-text-soft)]">{{ participant.code || 'Sem código' }}</p>
              </div>
            </div>

            <div class="ds-table-shell mt-4 overflow-x-auto">
              <DataTable class="min-w-full divide-y divide-[var(--ds-border)]">
                <thead class="ds-table-head">
                  <tr>
                    <th class="ds-table-heading px-4 py-3 text-left">Parâmetro</th>
                    <th class="ds-table-heading px-4 py-3 text-left">Valor</th>
                    <th class="ds-table-heading px-4 py-3 text-left">Unidade</th>
                    <th class="ds-table-heading px-4 py-3 text-left">Valor alvo</th>
                    <th class="ds-table-heading px-4 py-3 text-left">z-score</th>
                    <th class="ds-table-heading px-4 py-3 text-left">Estado</th>
                    <th class="ds-table-heading px-4 py-3 text-left">Observações</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-[var(--ds-border)]">
                  <tr v-for="(result, resultIndex) in participant.results" :key="`result-${participantIndex}-${resultIndex}`" class="ds-table-row">
                    <td class="ds-table-cell min-w-56 px-4 py-3">
                      <p class="font-bold text-[var(--ds-text)]">{{ result.parameter || result.parameter_code || 'Parâmetro' }}</p>
                      <p class="text-xs text-[var(--ds-text-soft)]">{{ result.parameter_code || 'Sem código' }}</p>
                    </td>
                    <td class="ds-table-cell min-w-36 px-4 py-3"><BaseInput v-model="result.value" class="ds-field" placeholder="0.00" /></td>
                    <td class="ds-table-cell min-w-28 px-4 py-3"><BaseInput v-model="result.unit" class="ds-field" placeholder="Un." /></td>
                    <td class="ds-table-cell min-w-36 px-4 py-3"><BaseInput v-model="result.assigned_value" type="number" step="0.0001" class="ds-field" placeholder="0.00" /></td>
                    <td class="ds-table-cell min-w-32 px-4 py-3"><BaseInput v-model="result.z_score" type="number" step="0.01" class="ds-field" placeholder="0.00" /></td>
                    <td class="ds-table-cell min-w-44 px-4 py-3">
                      <BaseSelect v-model="result.outcome" class="ds-field">
                        <option value="pending">Pendente</option>
                        <option value="satisfactory">Satisfatório</option>
                        <option value="questionable">Questionável</option>
                        <option value="unsatisfactory">Insatisfatório</option>
                      </BaseSelect>
                    </td>
                    <td class="ds-table-cell min-w-64 px-4 py-3"><BaseInput v-model="result.notes" class="ds-field" placeholder="Observações / evidência" /></td>
                  </tr>
                </tbody>
              </DataTable>
            </div>
          </article>
        </div>

        <div v-else class="ds-empty-state m-5 px-6 py-12 text-center">
          <p class="text-sm font-bold text-[var(--ds-text)]">Sem matriz de resultados</p>
          <p class="ds-copy mt-2 text-sm">Adicione participantes e parâmetros, depois sincronize a matriz.</p>
        </div>
      </section>

      <section class="ds-panel grid gap-5 p-5 lg:grid-cols-2">
        <ComboboxEnhanced
          v-model="selectedOutcome"
          :options="outcomeOptions"
          title-label="Resultado global"
          placeholder="Seleccionar resultado"
          :has-error="Boolean(form.errors.outcome)"
        />
        <div class="space-y-2">
          <label class="ds-field-label">z-score global</label>
          <BaseInput v-model="form.z_score" type="number" step="0.01" class="ds-field" />
          <p v-if="form.errors.z_score" class="ds-field-error">{{ form.errors.z_score }}</p>
        </div>
        <BaseTextarea v-model="form.corrective_actions" label="Acções correctivas" :rows="4" :error="form.errors.corrective_actions" />
        <BaseTextarea v-model="form.notes" label="Notas e evidências" :rows="4" :error="form.errors.notes" />
      </section>

      <div class="ds-command-surface sticky bottom-4 z-10 flex justify-end p-3">
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing">
          {{ form.processing ? 'A guardar resultados...' : 'Guardar resultados e evidência' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import BaseTextarea from '@/Components/base/BaseTextarea.vue'
import ChartWrapper from '@/Components/apex-chart/ChartWrapper.vue'
import ComboboxEnhanced from '@/Components/combobox-enhanced.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import {
  ArrowLeftIcon,
  ArrowUpTrayIcon,
  BeakerIcon,
  ChartBarIcon,
  CheckBadgeIcon,
  ClipboardDocumentCheckIcon,
  DocumentArrowDownIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline'
import { Link, useForm } from '@inertiajs/vue3'
import { computed, onMounted, ref } from 'vue'

defineOptions({
  layout: Layout,
})

const props = defineProps({
  test: { type: Object, required: true },
  charts: { type: Object, default: () => ({}) },
})

const cloneRows = (rows) => JSON.parse(JSON.stringify(rows || []))

const form = useForm({
  participants: cloneRows(props.test.participants),
  parameters: cloneRows(props.test.parameters),
  participant_results: cloneRows(props.test.participant_results),
  results: cloneRows(props.test.results),
  z_score: props.test.z_score ?? '',
  outcome: props.test.outcome || 'pending',
  corrective_actions: props.test.corrective_actions || '',
  notes: props.test.notes || '',
})

const importInput = ref(null)
const importForm = useForm({
  file: null,
})

const isDarkMode = computed(() => {
  if (typeof document === 'undefined') {
    return false
  }

  return document.documentElement.classList.contains('dark')
})

const outcomeOptions = computed(() => ['pending', 'satisfactory', 'questionable', 'unsatisfactory'].map((value) => ({ value, label: outcomeLabel(value) })))
const participantStatusOptions = computed(() => ['pending', 'enrolled', 'submitted', 'reviewed', 'requires_action'].map((value) => ({ value, label: participantStatusLabel(value) })))
const selectedOutcome = computed({
  get() {
    return outcomeOptions.value.find((option) => option.value === form.outcome) ?? outcomeOptions.value[0]
  },
  set(option) {
    form.outcome = option?.value ?? 'pending'
  },
})

const resultCount = computed(() => form.participant_results.reduce((total, participant) => total + (participant.results?.length || 0), 0))

const summaryCards = computed(() => [
  {
    label: 'Participantes',
    value: form.participants.length,
    caption: props.test.role === 'organizer' ? 'Laboratórios inscritos na ronda.' : 'Registo do laboratório participante.',
    icon: UserGroupIcon,
  },
  {
    label: 'Parâmetros',
    value: form.parameters.length,
    caption: 'Ensaios avaliados nesta ronda.',
    icon: BeakerIcon,
  },
  {
    label: 'Resultados',
    value: resultCount.value,
    caption: 'Entradas analíticas registadas.',
    icon: ClipboardDocumentCheckIcon,
  },
  {
    label: 'Maior |z|',
    value: props.test.performance_summary?.max_abs_z_score ?? '—',
    caption: 'Indicador rápido de risco técnico.',
    icon: ChartBarIcon,
  },
])

const chartTheme = computed(() => ({
  chart: { toolbar: { show: false }, foreColor: isDarkMode.value ? '#cbd5e1' : '#475569' },
  grid: { borderColor: isDarkMode.value ? '#334155' : '#e2e8f0' },
  legend: { labels: { colors: isDarkMode.value ? '#cbd5e1' : '#475569' } },
}))

const zScoreChartSeries = computed(() => props.charts?.z_scores?.series ?? [{ name: 'z-score', data: [] }])
const zScoreChartOptions = computed(() => ({
  ...chartTheme.value,
  xaxis: { categories: props.charts?.z_scores?.categories ?? [] },
  colors: ['#0ea5e9'],
  annotations: {
    yaxis: [
      { y: 2, borderColor: '#f59e0b', label: { text: '+2 alerta', style: { background: '#f59e0b', color: '#fff' } } },
      { y: -2, borderColor: '#f59e0b', label: { text: '-2 alerta', style: { background: '#f59e0b', color: '#fff' } } },
      { y: 3, borderColor: '#ef4444', label: { text: '+3 acção', style: { background: '#ef4444', color: '#fff' } } },
      { y: -3, borderColor: '#ef4444', label: { text: '-3 acção', style: { background: '#ef4444', color: '#fff' } } },
    ],
  },
}))

const performanceChartSeries = computed(() => props.charts?.performance?.series ?? [])
const performanceChartOptions = computed(() => ({
  ...chartTheme.value,
  labels: props.charts?.performance?.labels ?? [],
  colors: ['#10b981', '#f59e0b', '#ef4444'],
}))
const participantStatusChartSeries = computed(() => props.charts?.participant_status?.series ?? [])
const participantStatusChartOptions = computed(() => ({
  ...chartTheme.value,
  labels: props.charts?.participant_status?.labels ?? [],
  colors: ['#94a3b8', '#38bdf8', '#6366f1', '#10b981', '#f97316'],
}))

function schemeLabel(value) {
  return {
    proficiency: 'Proficiência',
    interlaboratory: 'Interlaboratorial',
  }[value] || value || '—'
}

function roleLabel(value) {
  return {
    participant: 'Participante',
    organizer: 'Organizador',
  }[value] || value || '—'
}

function statusLabel(value) {
  return {
    planned: 'Planeado',
    in_progress: 'Em curso',
    completed: 'Concluído',
    reviewed: 'Revisto',
    closed: 'Fechado',
  }[value] || value || '—'
}

function outcomeLabel(value) {
  return {
    pending: 'Pendente',
    satisfactory: 'Satisfatório',
    questionable: 'Questionável',
    unsatisfactory: 'Insatisfatório',
  }[value] || 'Pendente'
}

function participantStatusLabel(value) {
  return {
    pending: 'Pendente',
    enrolled: 'Inscrito',
    submitted: 'Submetido',
    reviewed: 'Revisto',
    requires_action: 'Requer acção',
  }[value] || 'Pendente'
}

function statusBadgeClass(status) {
  return {
    planned: 'ds-badge-neutral',
    in_progress: 'ds-badge-info',
    completed: 'ds-badge-success',
    reviewed: 'ds-badge-warning',
    closed: 'ds-badge-success',
  }[status] || 'ds-badge-neutral'
}

function addParticipant() {
  form.participants.push({ code: `LAB-${String(form.participants.length + 1).padStart(2, '0')}`, name: '', contact: '', status: 'pending' })
  ensureResultRows()
}

function removeParticipant(index) {
  form.participants.splice(index, 1)
  ensureResultRows()
}

function addParameter() {
  form.parameters.push({ code: `P-${String(form.parameters.length + 1).padStart(2, '0')}`, name: '', unit: '', assigned_value: '', standard_deviation: '' })
  ensureResultRows()
}

function removeParameter(index) {
  form.parameters.splice(index, 1)
  ensureResultRows()
}

function ensureParticipantSeed() {
  if (form.participants.length === 0) {
    form.participants.push({
      code: props.test.role === 'organizer' ? 'LAB-01' : 'LAB',
      name: props.test.role === 'organizer' ? '' : 'Laboratório interno',
      contact: '',
      status: 'pending',
    })
  }
}

function ensureResultRows() {
  ensureParticipantSeed()

  const existingParticipants = form.participant_results || []

  form.participant_results = form.participants.map((participant) => {
    const participantKey = participant.code || participant.name
    const existingParticipant = existingParticipants.find((row) => (row.code || row.name) === participantKey) || {}
    const existingResults = existingParticipant.results || []

    return {
      code: participant.code || '',
      name: participant.name || '',
      results: form.parameters.map((parameter) => {
        const parameterKey = parameter.code || parameter.name
        const existingResult = existingResults.find((result) => (result.parameter_code || result.parameter) === parameterKey) || {}

        return {
          parameter_code: parameter.code || '',
          parameter: parameter.name || '',
          unit: existingResult.unit ?? parameter.unit ?? '',
          assigned_value: existingResult.assigned_value ?? parameter.assigned_value ?? '',
          value: existingResult.value ?? '',
          z_score: existingResult.z_score ?? '',
          outcome: existingResult.outcome ?? 'pending',
          notes: existingResult.notes ?? '',
        }
      }),
    }
  })
}

function submit() {
  ensureResultRows()

  form.put(route('proficiency_tests.results.update', props.test.id), {
    preserveScroll: true,
  })
}

function triggerImport() {
  importInput.value?.click()
}

function importResults(event) {
  const file = event.target.files?.[0]

  if (!file) {
    return
  }

  importForm.file = file
  importForm.post(route('proficiency_tests.results.import', props.test.id), {
    forceFormData: true,
    preserveScroll: true,
    onFinish: () => {
      importForm.reset('file')
      event.target.value = ''
    },
  })
}

onMounted(() => {
  ensureParticipantSeed()

  if (!form.participant_results.length && form.parameters.length) {
    ensureResultRows()
  }
})
</script>
