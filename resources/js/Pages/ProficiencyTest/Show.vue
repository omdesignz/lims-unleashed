<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
      <div class="relative isolate px-6 py-8 text-slate-950 dark:text-white sm:px-8">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_right,rgba(var(--color-primary-500),0.18),transparent_34%),linear-gradient(135deg,rgba(248,250,252,1),rgba(240,253,250,0.86))] dark:bg-[radial-gradient(circle_at_top_right,rgba(var(--color-primary-400),0.16),transparent_36%),linear-gradient(135deg,rgba(15,23,42,1),rgba(20,83,45,0.58))]"></div>
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
          <div class="max-w-4xl">
            <Link :href="route('proficiency_tests.index')" class="inline-flex items-center gap-2 text-sm font-semibold text-primary-700 transition hover:text-primary-900 dark:text-primary-200 dark:hover:text-primary-100">
              <ArrowLeftIcon class="h-4 w-4" />
              Voltar aos ensaios de proficiência
            </Link>
            <div class="mt-5 flex flex-wrap gap-2">
              <span class="rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 dark:bg-slate-950/60 dark:text-slate-200 dark:ring-slate-700">{{ schemeLabel(test.scheme_type) }}</span>
              <span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700 ring-1 ring-primary-200 dark:bg-primary-500/10 dark:text-primary-200 dark:ring-primary-500/20">{{ roleLabel(test.role) }}</span>
              <span class="rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset" :class="statusBadgeClass(test.status)">{{ statusLabel(test.status) }}</span>
            </div>
            <h1 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">{{ test.name }}</h1>
            <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-600 dark:text-slate-300">
              {{ test.scope || 'Registe participantes, parâmetros, resultados, z-scores e evidências para manter a rastreabilidade da ronda.' }}
            </p>
          </div>

          <div class="rounded-3xl border border-white/70 bg-white/80 p-5 shadow-sm backdrop-blur dark:border-slate-700 dark:bg-slate-950/55">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Resultado global</p>
            <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ outcomeLabel(form.outcome) }}</p>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">z-score {{ form.z_score || '—' }}</p>
          </div>
        </div>
      </div>

      <div class="grid gap-4 border-t border-slate-200 bg-slate-50/80 px-6 py-5 dark:border-slate-800 dark:bg-slate-950/40 sm:grid-cols-2 xl:grid-cols-4 sm:px-8">
        <article v-for="card in summaryCards" :key="card.label" class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <div class="flex items-center justify-between gap-3">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ card.label }}</p>
            <component :is="card.icon" class="h-5 w-5 text-primary-600 dark:text-primary-300" />
          </div>
          <p class="mt-3 text-3xl font-semibold text-slate-950 dark:text-white">{{ card.value }}</p>
          <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ card.caption }}</p>
        </article>
      </div>
    </section>

    <section class="grid gap-5 xl:grid-cols-3">
      <article class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
        <div class="flex items-center justify-between gap-3">
          <div>
            <h2 class="text-lg font-semibold text-slate-950 dark:text-white">Distribuição dos z-scores</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Comparação por participante e parâmetro.</p>
          </div>
          <ChartBarIcon class="h-6 w-6 text-primary-600 dark:text-primary-300" />
        </div>
        <ChartWrapper class="mt-5" type="bar" height="340" :series="zScoreChartSeries" :options="zScoreChartOptions" />
      </article>

      <article class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
        <div class="flex items-center justify-between gap-3">
          <div>
            <h2 class="text-lg font-semibold text-slate-950 dark:text-white">Performance</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Classificação automática por z-score.</p>
          </div>
          <CheckBadgeIcon class="h-6 w-6 text-primary-600 dark:text-primary-300" />
        </div>
        <ChartWrapper class="mt-5" type="donut" height="320" :series="performanceChartSeries" :options="performanceChartOptions" />
      </article>

      <article class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
        <div class="flex items-center justify-between gap-3">
          <div>
            <h2 class="text-lg font-semibold text-slate-950 dark:text-white">Estado dos participantes</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Acompanhamento operacional da submissão e revisão.</p>
          </div>
          <UserGroupIcon class="h-6 w-6 text-primary-600 dark:text-primary-300" />
        </div>
        <ChartWrapper class="mt-5" type="donut" height="320" :series="participantStatusChartSeries" :options="participantStatusChartOptions" />
      </article>
    </section>

    <form class="space-y-6" @submit.prevent="submit">
      <section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 class="text-lg font-semibold text-slate-950 dark:text-white">Participantes e parâmetros</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Use este quadro para organizar rondas internas ou externas antes de lançar resultados.</p>
          </div>
          <div class="flex flex-wrap gap-2">
            <a :href="route('proficiency_tests.results.template', test.id)" class="action-button">
              <DocumentArrowDownIcon class="h-4 w-4" />
              Template Excel
            </a>
            <button type="button" class="action-button" @click="triggerImport">
              <ArrowUpTrayIcon class="h-4 w-4" />
              Importar resultados
            </button>
            <input ref="importInput" type="file" accept=".xlsx,.xls,.csv,.txt" class="hidden" @change="importResults" />
            <button type="button" class="action-button" @click="addParticipant">Adicionar participante</button>
            <button type="button" class="action-button" @click="addParameter">Adicionar parâmetro</button>
            <button type="button" class="action-button-primary" @click="ensureResultRows">Sincronizar matriz</button>
          </div>
        </div>

        <div class="mt-5 grid gap-5 xl:grid-cols-2">
          <div class="space-y-3">
            <p class="section-label">Participantes</p>
            <div v-for="(participant, index) in form.participants" :key="`participant-${index}`" class="grid gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950/50 sm:grid-cols-[0.8fr,1.2fr,0.9fr,1fr,auto]">
              <input v-model="participant.code" class="form-field" placeholder="Código" @blur="ensureResultRows" />
              <input v-model="participant.name" class="form-field" placeholder="Laboratório / participante" @blur="ensureResultRows" />
              <select v-model="participant.status" class="form-field">
                <option v-for="option in participantStatusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
              <input v-model="participant.contact" class="form-field" placeholder="Contacto" />
              <button type="button" class="danger-button" @click="removeParticipant(index)">Remover</button>
            </div>
          </div>

          <div class="space-y-3">
            <p class="section-label">Parâmetros</p>
            <div v-for="(parameter, index) in form.parameters" :key="`parameter-${index}`" class="grid gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950/50 sm:grid-cols-[0.8fr,1.2fr,0.7fr,0.9fr,auto]">
              <input v-model="parameter.code" class="form-field" placeholder="Código" @blur="ensureResultRows" />
              <input v-model="parameter.name" class="form-field" placeholder="Parâmetro" @blur="ensureResultRows" />
              <input v-model="parameter.unit" class="form-field" placeholder="Unidade" />
              <input v-model="parameter.assigned_value" type="number" step="0.0001" class="form-field" placeholder="Valor alvo" />
              <button type="button" class="danger-button" @click="removeParameter(index)">Remover</button>
            </div>
          </div>
        </div>
      </section>

      <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-5 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 class="text-lg font-semibold text-slate-950 dark:text-white">Registo de resultados</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Registe valor obtido, z-score, observações e classificação por resultado.</p>
          </div>
          <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
            {{ resultCount }} resultados
          </span>
        </div>

        <div v-if="form.participant_results.length" class="divide-y divide-slate-200 dark:divide-slate-800">
          <article v-for="(participant, participantIndex) in form.participant_results" :key="`result-${participantIndex}`" class="p-5">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <p class="text-sm font-semibold text-slate-950 dark:text-white">{{ participant.name || participant.code || 'Participante sem nome' }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ participant.code || 'Sem código' }}</p>
              </div>
            </div>

            <div class="mt-4 overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                <thead class="bg-slate-50 dark:bg-slate-950/60">
                  <tr>
                    <th class="result-th">Parâmetro</th>
                    <th class="result-th">Valor</th>
                    <th class="result-th">Unidade</th>
                    <th class="result-th">Valor alvo</th>
                    <th class="result-th">z-score</th>
                    <th class="result-th">Estado</th>
                    <th class="result-th">Observações</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                  <tr v-for="(result, resultIndex) in participant.results" :key="`result-${participantIndex}-${resultIndex}`">
                    <td class="result-td min-w-56">
                      <p class="font-semibold text-slate-900 dark:text-white">{{ result.parameter || result.parameter_code || 'Parâmetro' }}</p>
                      <p class="text-xs text-slate-500 dark:text-slate-400">{{ result.parameter_code || 'Sem código' }}</p>
                    </td>
                    <td class="result-td min-w-36"><input v-model="result.value" class="form-field" placeholder="0.00" /></td>
                    <td class="result-td min-w-28"><input v-model="result.unit" class="form-field" placeholder="Un." /></td>
                    <td class="result-td min-w-36"><input v-model="result.assigned_value" type="number" step="0.0001" class="form-field" placeholder="0.00" /></td>
                    <td class="result-td min-w-32"><input v-model="result.z_score" type="number" step="0.01" class="form-field" placeholder="0.00" /></td>
                    <td class="result-td min-w-44">
                      <select v-model="result.outcome" class="form-field">
                        <option value="pending">Pendente</option>
                        <option value="satisfactory">Satisfatório</option>
                        <option value="questionable">Questionável</option>
                        <option value="unsatisfactory">Insatisfatório</option>
                      </select>
                    </td>
                    <td class="result-td min-w-64"><input v-model="result.notes" class="form-field" placeholder="Observações / evidência" /></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </article>
        </div>

        <div v-else class="px-6 py-14 text-center">
          <p class="text-sm font-semibold text-slate-950 dark:text-white">Sem matriz de resultados</p>
          <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Adicione participantes e parâmetros, depois sincronize a matriz.</p>
        </div>
      </section>

      <section class="grid gap-5 lg:grid-cols-2">
        <ComboboxEnhanced
          v-model="selectedOutcome"
          :options="outcomeOptions"
          title-label="Resultado global"
          placeholder="Selecionar resultado"
          :has-error="Boolean(form.errors.outcome)"
        />
        <div class="space-y-2">
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">z-score global</label>
          <input v-model="form.z_score" type="number" step="0.01" class="form-field" />
          <p v-if="form.errors.z_score" class="text-xs font-medium text-red-600 dark:text-red-400">{{ form.errors.z_score }}</p>
        </div>
        <BaseTextarea v-model="form.corrective_actions" label="Ações corretivas" :rows="4" :error="form.errors.corrective_actions" />
        <BaseTextarea v-model="form.notes" label="Notas e evidências" :rows="4" :error="form.errors.notes" />
      </section>

      <div class="sticky bottom-4 z-10 flex justify-end">
        <button type="submit" class="rounded-2xl bg-primary-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-primary-500 dark:hover:bg-primary-400" :disabled="form.processing">
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
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
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
      { y: 3, borderColor: '#ef4444', label: { text: '+3 ação', style: { background: '#ef4444', color: '#fff' } } },
      { y: -3, borderColor: '#ef4444', label: { text: '-3 ação', style: { background: '#ef4444', color: '#fff' } } },
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
    requires_action: 'Requer ação',
  }[value] || 'Pendente'
}

function statusBadgeClass(status) {
  return {
    planned: 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700',
    in_progress: 'bg-sky-100 text-sky-700 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-200 dark:ring-sky-500/20',
    completed: 'bg-blue-100 text-blue-700 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-200 dark:ring-blue-500/20',
    reviewed: 'bg-amber-100 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-500/20',
    closed: 'bg-emerald-100 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/20',
  }[status] || 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700'
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

<style scoped>
.form-field {
  display: block;
  width: 100%;
  border-radius: 1rem;
  border: 1px solid rgb(203 213 225);
  background-color: rgb(255 255 255);
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  color: rgb(15 23 42);
  box-shadow: 0 1px 2px rgb(15 23 42 / 0.04);
  transition: border-color 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
}

.form-field:focus {
  border-color: rgb(var(--color-primary-500));
  box-shadow: 0 0 0 3px rgb(var(--color-primary-500) / 0.18);
  outline: none;
}

:global(.dark) .form-field {
  border-color: rgb(51 65 85);
  background-color: rgb(15 23 42 / 0.72);
  color: rgb(241 245 249);
}

.action-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  border-radius: 1rem;
  border: 1px solid rgb(203 213 225);
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  font-weight: 700;
  color: rgb(51 65 85);
  transition: background-color 150ms ease, border-color 150ms ease;
}

.action-button:hover {
  background-color: rgb(248 250 252);
}

:global(.dark) .action-button {
  border-color: rgb(51 65 85);
  color: rgb(226 232 240);
}

:global(.dark) .action-button:hover {
  background-color: rgb(30 41 59);
}

.action-button-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  border-radius: 1rem;
  background-color: rgb(var(--color-primary-600));
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  font-weight: 700;
  color: white;
  transition: background-color 150ms ease;
}

.action-button-primary:hover {
  background-color: rgb(var(--color-primary-700));
}

.danger-button {
  border-radius: 0.875rem;
  padding: 0.75rem;
  font-size: 0.75rem;
  font-weight: 700;
  color: rgb(225 29 72);
}

:global(.dark) .danger-button {
  color: rgb(253 164 175);
}

.section-label,
.result-th {
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: rgb(100 116 139);
}

:global(.dark) .section-label,
:global(.dark) .result-th {
  color: rgb(148 163 184);
}

.result-th {
  padding: 1rem;
  text-align: left;
}

.result-td {
  padding: 1rem;
  font-size: 0.875rem;
  color: rgb(51 65 85);
}

:global(.dark) .result-td {
  color: rgb(203 213 225);
}
</style>
