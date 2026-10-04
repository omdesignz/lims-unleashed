<template>
  <div class="pl-page space-y-6">
    <PageHeader title="Ensaios de proficiência e comparações interlaboratoriais" lede="Planeie rondas externas, acompanhe prazos, registe z-score, gere acções correctivas e mantenha evidência pronta para auditorias.">
      <template #actions>
        <button
          v-if="permissions.add"
          type="button"
          class="ds-button ds-button-primary"
          @click="openCreate"
        >
          <PlusIcon class="h-4 w-4" />
          Novo programa
        </button>
      </template>
    </PageHeader>

    <dl class="pl-cells">
      <div v-for="card in summaryCards" :key="card.label" class="pl-cell">
        <dt class="pl-k pl-muted">{{ card.label }}</dt>
        <dd class="pl-cell-value">{{ card.value }}</dd>
      </div>
    </dl>

    <section class="ds-command-surface p-5">
      <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr),16rem,16rem,auto]">
        <div class="relative">
          <MagnifyingGlassIcon class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" />
          <BaseInput
            v-model="filters.search"
            type="search"
            placeholder="Pesquisar por programa, provedor ou ronda"
            class="ds-field pl-10"
          />
        </div>

        <ComboboxEnhanced
          v-model="selectedScheme"
          :options="schemeFilterOptions"
          placeholder="Todos os tipos"
        />

        <ComboboxEnhanced
          v-model="selectedStatus"
          :options="statusFilterOptions"
          placeholder="Todos os estados"
        />

        <div class="flex flex-wrap gap-2">
          <button
            type="button"
            class="ds-button"
            :class="filters.filter === 'trashed' ? 'ds-button-primary' : 'ds-button-secondary'"
            :aria-pressed="filters.filter === 'trashed'"
            @click="toggleArchived"
          >
            <ArchiveBoxIcon class="h-4 w-4" />
            Arquivados
          </button>
          <button
            type="button"
            class="ds-button ds-button-secondary"
            @click="clearFilters"
          >
            Limpar
          </button>
        </div>
      </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-3" aria-label="Indicadores das rondas">
      <article class="pl-panel min-w-0">
        <header class="pl-panel-head"><h2 class="pl-k">Estado das rondas</h2></header>
        <div class="p-4">
          <PlanoChart kind="bar" label="Rondas por estado" :categories="charts?.status?.labels ?? []" :series="[{ name: 'Rondas', data: charts?.status?.series ?? [] }]" :height="220" />
        </div>
      </article>
      <article class="pl-panel min-w-0">
        <header class="pl-panel-head"><h2 class="pl-k">Resultados</h2></header>
        <div class="p-4">
          <PlanoChart kind="donut" label="Rondas por resultado" :categories="charts?.outcome?.labels ?? []" :series="[{ name: 'Rondas', data: charts?.outcome?.series ?? [] }]" :tones="{ Pendente: 'neutral', Satisfatório: 'ok', Questionável: 'warn', Insatisfatório: 'bad' }" />
        </div>
      </article>
      <article class="pl-panel min-w-0">
        <header class="pl-panel-head"><h2 class="pl-k">Papel do laboratório</h2></header>
        <dl class="pl-cells border-0">
          <div v-for="(label, index) in charts?.role?.labels ?? []" :key="label" class="pl-cell">
            <dt class="pl-k pl-muted">{{ label }}</dt>
            <dd class="pl-cell-value">{{ charts?.role?.series?.[index] ?? 0 }}</dd>
          </div>
        </dl>
      </article>
    </section>

    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-3 border-b border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p class="ds-kicker">Registo de proficiência</p>
          <h2 class="ds-heading mt-1 text-base">Monitorização operacional</h2>
          <p class="ds-copy mt-1 text-sm">Prazos, resultados e evidências críticas num único quadro.</p>
        </div>
        <span class="ds-badge ds-badge-neutral">
          {{ records.length }} registos nesta página
        </span>
      </div>

      <div v-if="records.length">
        <div class="hidden overflow-x-auto lg:block">
          <DataTable class="min-w-full divide-y divide-[var(--ds-border)]">
            <thead class="ds-table-head">
              <tr>
                <th class="ds-table-heading px-5 py-3 text-left">Programa</th>
                <th class="ds-table-heading px-5 py-3 text-left">Provedor</th>
                <th class="ds-table-heading px-5 py-3 text-left">Prazo</th>
                <th class="ds-table-heading px-5 py-3 text-left">Estado</th>
                <th class="ds-table-heading px-5 py-3 text-left">Resultado</th>
                <th class="ds-table-heading px-5 py-3 text-right">Acções</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--ds-border)]">
              <tr v-for="item in records" :key="item.id" class="ds-table-row">
                <td class="max-w-sm px-5 py-4">
                  <div class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
                      <BeakerIcon class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                      <Link v-if="!item.deleted" :href="route('proficiency_tests.show', item.id)" class="truncate text-sm font-bold text-[var(--ds-text)] hover:text-[rgb(var(--primary-700-rgb))]">{{ item.name }}</Link>
                      <p v-else class="truncate text-sm font-bold text-[var(--ds-text)]">{{ item.name }}</p>
                      <p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">{{ item.round_reference || 'Sem referência' }}</p>
                      <div class="mt-2 flex flex-wrap gap-2">
                        <span class="ds-badge ds-badge-neutral">{{ schemeLabel(item.scheme_type) }}</span>
                        <span class="ds-badge ds-badge-info">{{ roleLabel(item.role) }}</span>
                      </div>
                    </div>
                  </div>
                </td>
                <td class="ds-table-cell px-5 py-4">{{ item.provider_name || '—' }}</td>
                <td class="ds-table-cell px-5 py-4">
                  <p class="font-bold text-[var(--ds-text)]">{{ formatDate(item.deadline_date || item.date) }}</p>
                  <p :class="deadlineTextClass(item.deadline_state)" class="mt-1 text-xs font-semibold">{{ deadlineLabel(item) }}</p>
                </td>
                <td class="ds-table-cell px-5 py-4">
                  <span class="ds-badge" :class="statusBadgeClass(item.status)">
                    {{ statusLabel(item.status) }}
                  </span>
                </td>
                <td class="ds-table-cell px-5 py-4">
                  <p class="font-bold text-[var(--ds-text)]">{{ outcomeLabel(item.outcome) }}</p>
                  <p v-if="item.z_score !== null && item.z_score !== ''" class="mt-1 text-xs text-[var(--ds-text-soft)]">z-score {{ item.z_score }}</p>
                </td>
                <td class="ds-table-cell px-5 py-4">
                  <div class="flex justify-end gap-2">
                    <Link v-if="!item.deleted" :href="route('proficiency_tests.show', item.id)" class="ds-table-action">Abrir</Link>
                    <button v-if="permissions.edit && !item.deleted" type="button" class="ds-table-action" @click="openEdit(item)">Editar</button>
                    <button
                      v-if="permissions.delete && !item.deleted"
                      type="button"
                      class="ds-table-action ds-table-action-danger"
                      @click="destroy(item)"
                    >
                      Arquivar
                    </button>
                    <button
                      v-if="permissions.restore && item.deleted"
                      type="button"
                      class="ds-table-action"
                      @click="restore(item)"
                    >
                      Restaurar
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </DataTable>
        </div>

        <div class="divide-y divide-[var(--ds-border)] lg:hidden">
          <article v-for="item in records" :key="item.id" class="space-y-4 px-5 py-5">
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="text-sm font-bold text-[var(--ds-text)]">{{ item.name }}</p>
                <p class="mt-1 text-sm text-[var(--ds-text-muted)]">{{ item.provider_name || '—' }}</p>
              </div>
              <span class="ds-badge shrink-0" :class="statusBadgeClass(item.status)">
                {{ statusLabel(item.status) }}
              </span>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
              <div class="ds-card p-4">
                <p class="ds-kicker">Ronda</p>
                <p class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ item.round_reference || 'Sem referência' }}</p>
              </div>
              <div class="ds-card p-4">
                <p class="ds-kicker">Prazo</p>
                <p class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ formatDate(item.deadline_date || item.date) }}</p>
                <p :class="deadlineTextClass(item.deadline_state)" class="mt-1 text-xs font-semibold">{{ deadlineLabel(item) }}</p>
              </div>
              <div class="ds-card p-4">
                <p class="ds-kicker">Tipo</p>
                <p class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ schemeLabel(item.scheme_type) }} - {{ roleLabel(item.role) }}</p>
              </div>
              <div class="ds-card p-4">
                <p class="ds-kicker">Resultado</p>
                <p class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ outcomeLabel(item.outcome) }}</p>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
              <Link v-if="!item.deleted" :href="route('proficiency_tests.show', item.id)" class="ds-button ds-button-secondary">Abrir</Link>
              <button v-if="permissions.edit && !item.deleted" type="button" class="ds-button ds-button-secondary" @click="openEdit(item)">Editar</button>
              <button
                v-if="permissions.delete && !item.deleted"
                type="button"
                class="ds-button ds-button-danger"
                @click="destroy(item)"
              >
                Arquivar
              </button>
              <button
                v-if="permissions.restore && item.deleted"
                type="button"
                class="ds-button ds-button-secondary"
                @click="restore(item)"
              >
                Restaurar
              </button>
            </div>
          </article>
        </div>
      </div>

      <div v-else class="ds-empty-state m-5 px-6 py-14 text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
          <BeakerIcon class="h-7 w-7" />
        </div>
        <p class="ds-heading mt-5 text-base">Sem programas registados</p>
        <p class="ds-copy mx-auto mt-2 max-w-md text-sm">{{ permissions.add ? 'Crie o primeiro ensaio de proficiência ou interlaboratorial e acompanhe-o até ao encerramento.' : 'Nenhum ensaio de proficiência disponível neste laboratório.' }}</p>
      </div>
    </section>

    <div v-if="paginationLinks.length" class="ds-pagination flex flex-wrap gap-2 p-3">
      <Link
        v-for="link in paginationLinks"
        :key="`${link.label}-${link.url}`"
        :href="link.url || '#'"
        class="ds-button min-h-9 px-3 py-1.5"
        :class="link.active ? 'ds-button-primary' : 'ds-button-ghost'"
        preserve-scroll
        v-html="link.label"
      />
    </div>

    <TransitionRoot as="template" :show="showEditor">
      <Dialog as="div" class="relative z-50" @close="closeEditor">
        <div class="ds-modal-backdrop" />
        <div class="fixed inset-0 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4">
            <DialogPanel class="ds-modal-panel w-full max-w-5xl overflow-hidden">
              <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
                <p class="ds-kicker">Programa de proficiência</p>
                <h2 class="ds-heading mt-1 text-xl">{{ editorTitle }}</h2>
                <p class="ds-copy mt-1 text-sm">Registe planeamento, prazo, resultado e acções correctivas associadas.</p>
              </div>

              <form class="space-y-6 px-6 py-6" @submit.prevent="submit">
                <div class="grid gap-5 md:grid-cols-2">
                  <div class="space-y-2">
                    <label class="ds-field-label">Nome</label>
                    <BaseInput v-model="form.name" type="text" class="ds-field" />
                    <p v-if="form.errors.name" class="ds-field-error">{{ form.errors.name }}</p>
                  </div>
                  <div class="space-y-2">
                    <label class="ds-field-label">Provedor</label>
                    <BaseInput v-model="form.provider_name" type="text" class="ds-field" />
                    <p v-if="form.errors.provider_name" class="ds-field-error">{{ form.errors.provider_name }}</p>
                  </div>
                  <ComboboxEnhanced
                    v-model="selectedFormScheme"
                    :options="schemeFormOptions"
                    title-label="Tipo"
                    placeholder="Seleccionar tipo"
                    :has-error="Boolean(form.errors.scheme_type)"
                  />
                  <ComboboxEnhanced
                    v-model="selectedFormRole"
                    :options="roleFormOptions"
                    title-label="Papel do laboratório"
                    placeholder="Seleccionar papel"
                    :has-error="Boolean(form.errors.role)"
                  />
                  <div class="space-y-2">
                    <label class="ds-field-label">Ronda / referência</label>
                    <BaseInput v-model="form.round_reference" type="text" class="ds-field" />
                    <p v-if="form.errors.round_reference" class="ds-field-error">{{ form.errors.round_reference }}</p>
                  </div>
                  <ComboboxEnhanced
                    v-model="selectedFormStatus"
                    :options="statusFormOptions"
                    title-label="Estado"
                    placeholder="Seleccionar estado"
                    :has-error="Boolean(form.errors.status)"
                  />
                  <ComboboxEnhanced
                    v-model="selectedFormOutcome"
                    :options="outcomeFormOptions"
                    title-label="Resultado"
                    placeholder="Seleccionar resultado"
                    :has-error="Boolean(form.errors.outcome)"
                  />
                  <DatePickerEnhanced
                    v-model="form.date"
                    label="Data base"
                    :required="true"
                    :is-dark="isDarkMode"
                    :has-error="Boolean(form.errors.date)"
                    :error-message="form.errors.date"
                  />
                  <DatePickerEnhanced
                    v-model="form.scheduled_at"
                    label="Prazo / data prevista"
                    :is-dark="isDarkMode"
                    :has-error="Boolean(form.errors.scheduled_at)"
                    :error-message="form.errors.scheduled_at"
                  />
                  <DatePickerEnhanced
                    v-model="form.enrollment_deadline_at"
                    label="Prazo de inscrição"
                    :is-dark="isDarkMode"
                    :has-error="Boolean(form.errors.enrollment_deadline_at)"
                    :error-message="form.errors.enrollment_deadline_at"
                  />
                  <DatePickerEnhanced
                    v-model="form.submission_deadline_at"
                    label="Prazo de submissão"
                    :is-dark="isDarkMode"
                    :has-error="Boolean(form.errors.submission_deadline_at)"
                    :error-message="form.errors.submission_deadline_at"
                  />
                  <DatePickerEnhanced
                    v-model="form.closed_at"
                    label="Data de fecho"
                    :is-dark="isDarkMode"
                    :has-error="Boolean(form.errors.closed_at)"
                    :error-message="form.errors.closed_at"
                  />
                  <div class="space-y-2">
                    <label class="ds-field-label">z-score</label>
                    <BaseInput v-model="form.z_score" type="number" step="0.01" class="ds-field" />
                    <p v-if="form.errors.z_score" class="ds-field-error">{{ form.errors.z_score }}</p>
                  </div>
                </div>

                <div v-if="form.role === 'organizer'" class="ds-command-surface p-5">
                  <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                      <h3 class="ds-heading text-base">Modo organizador</h3>
                      <p class="ds-copy mt-1 text-sm">Registe participantes e parâmetros para que a ronda possa receber resultados estruturados.</p>
                    </div>
                    <div class="flex gap-2">
                      <button type="button" class="ds-button ds-button-secondary" @click="addParticipant">Adicionar participante</button>
                      <button type="button" class="ds-button ds-button-secondary" @click="addParameter">Adicionar parâmetro</button>
                    </div>
                  </div>

                  <div class="mt-5 space-y-2">
                    <label class="ds-field-label">Organizador responsável</label>
                    <BaseInput v-model="form.organizer_name" type="text" class="ds-field" placeholder="Nome da unidade, laboratório ou coordenação organizadora" />
                    <p v-if="form.errors.organizer_name" class="ds-field-error">{{ form.errors.organizer_name }}</p>
                  </div>

                  <div class="mt-5 grid gap-5 lg:grid-cols-2">
                    <div class="space-y-3">
                      <p class="ds-kicker">Participantes</p>
                      <div v-for="(participant, index) in form.participants" :key="`participant-${index}`" class="ds-card grid gap-2 p-3 sm:grid-cols-[0.9fr,1.4fr,1fr,auto]">
                        <BaseInput v-model="participant.code" class="ds-field" placeholder="Código" />
                        <BaseInput v-model="participant.name" class="ds-field" placeholder="Laboratório / participante" />
                        <BaseSelect v-model="participant.status" class="ds-field">
                          <option v-for="option in participantStatusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </BaseSelect>
                        <button type="button" class="ds-table-action ds-table-action-danger" @click="removeParticipant(index)">Remover</button>
                      </div>
                    </div>
                    <div class="space-y-3">
                      <p class="ds-kicker">Parâmetros</p>
                      <div v-for="(parameter, index) in form.parameters" :key="`parameter-${index}`" class="ds-card grid gap-2 p-3 sm:grid-cols-[1fr,1.4fr,0.8fr,auto]">
                        <BaseInput v-model="parameter.code" class="ds-field" placeholder="Código" />
                        <BaseInput v-model="parameter.name" class="ds-field" placeholder="Parâmetro" />
                        <BaseInput v-model="parameter.unit" class="ds-field" placeholder="Unidade" />
                        <button type="button" class="ds-table-action ds-table-action-danger" @click="removeParameter(index)">Remover</button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                  <BaseTextarea v-model="form.scope" label="Âmbito" :rows="3" :error="form.errors.scope" />
                  <BaseTextarea v-model="form.corrective_actions" label="Acções correctivas" :rows="3" :error="form.errors.corrective_actions" />
                </div>

                <BaseTextarea v-model="form.notes" label="Notas e evidências" :rows="3" :error="form.errors.notes" />

                <div class="flex flex-col gap-3 border-t border-[var(--ds-border)] pt-5 sm:flex-row sm:justify-end">
                  <button type="button" class="ds-button ds-button-secondary" @click="closeEditor">Cancelar</button>
                  <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing">
                    {{ form.processing ? 'A guardar...' : 'Guardar programa' }}
                  </button>
                </div>
              </form>
            </DialogPanel>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </div>
</template>

<script setup>
import BaseTextarea from '@/Components/base/BaseTextarea.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import PlanoChart from '@/Components/plano/PlanoChart.vue'
import ComboboxEnhanced from '@/Components/combobox-enhanced.vue'
import DatePickerEnhanced from '@/Components/date-picker-enhanced.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import {
  Archive as ArchiveBoxIcon,
  FlaskConical as BeakerIcon,
  BellRing as BellAlertIcon,
  BadgeCheck as CheckBadgeIcon,
  TriangleAlert as ExclamationTriangleIcon,
  Search as MagnifyingGlassIcon,
  Plus as PlusIcon,
} from '@lucide/vue'
import { Dialog, DialogPanel, TransitionRoot } from '@headlessui/vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

defineOptions({
  layout: Layout,
})

const props = defineProps({
  record: { type: Object, required: true },
  query: { type: Object, default: () => ({}) },
  statusOptions: { type: Array, default: () => [] },
  schemeOptions: { type: Array, default: () => [] },
  roleOptions: { type: Array, default: () => [] },
  charts: { type: Object, default: () => ({}) },
  permissions: { type: Object, default: () => ({}) },
})

const records = computed(() => props.record?.data ?? [])
const paginationLinks = computed(() => props.record?.meta?.links ?? props.record?.links ?? [])

const filters = useForm({
  search: props.query?.search || '',
  status: props.query?.status || '',
  scheme_type: props.query?.scheme_type || '',
  filter: props.query?.filter || '',
})

let filterTimer = null
watch(() => [filters.search, filters.status, filters.scheme_type, filters.filter], () => {
  window.clearTimeout(filterTimer)
  filterTimer = window.setTimeout(() => {
    router.get(route('proficiency_tests.index'), filters.data(), {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    })
  }, 250)
}, { deep: true })

const isDarkMode = computed(() => {
  if (typeof document === 'undefined') {
    return false
  }

  return document.documentElement.classList.contains('dark')
})

const schemeFormOptions = computed(() => props.schemeOptions.map((value) => ({ value, label: schemeLabel(value) })))
const roleFormOptions = computed(() => props.roleOptions.map((value) => ({ value, label: roleLabel(value) })))
const participantStatusOptions = computed(() => ['pending', 'enrolled', 'submitted', 'reviewed', 'requires_action'].map((value) => ({ value, label: participantStatusLabel(value) })))
const statusFormOptions = computed(() => props.statusOptions.map((value) => ({ value, label: statusLabel(value) })))
const outcomeFormOptions = computed(() => ['pending', 'satisfactory', 'questionable', 'unsatisfactory'].map((value) => ({ value, label: outcomeLabel(value) })))
const schemeFilterOptions = computed(() => [{ value: '', label: 'Todos os tipos' }, ...schemeFormOptions.value])
const statusFilterOptions = computed(() => [{ value: '', label: 'Todos os estados' }, ...statusFormOptions.value])

const optionProxy = (field, options, target = filters) => computed({
  get() {
    return options.value.find((option) => option.value === target[field]) ?? null
  },
  set(option) {
    target[field] = option?.value ?? ''
  },
})

const selectedScheme = optionProxy('scheme_type', schemeFilterOptions)
const selectedStatus = optionProxy('status', statusFilterOptions)

const summaryCards = computed(() => {
  const total = records.value.length
  const closed = records.value.filter((item) => item.status === 'closed').length
  const attention = records.value.filter((item) => ['questionable', 'unsatisfactory'].includes(item.outcome) || ['overdue', 'due_soon'].includes(item.deadline_state)).length
  const interlaboratory = records.value.filter((item) => item.scheme_type === 'interlaboratory').length

  return [
    { label: 'Programas visíveis', value: total, caption: 'Janela filtrada para acompanhamento operacional.', icon: BeakerIcon },
    { label: 'Encerrados', value: closed, caption: 'Rondas com evidência e fecho registados.', icon: CheckBadgeIcon },
    { label: 'Requer atenção', value: attention, caption: 'Prazos próximos, vencidos ou resultados críticos.', icon: BellAlertIcon },
    { label: 'Interlaboratoriais', value: interlaboratory, caption: 'Comparações externas entre laboratórios.', icon: ExclamationTriangleIcon },
  ]
})

const showEditor = ref(false)
const editingRecordId = ref(null)
const form = useForm({
  name: '',
  scheme_type: 'proficiency',
  role: 'participant',
  provider_name: '',
  organizer_name: '',
  participants: [],
  parameters: [],
  round_reference: '',
  status: 'planned',
  date: '',
  scheduled_at: '',
  enrollment_deadline_at: '',
  submission_deadline_at: '',
  closed_at: '',
  scope: '',
  outcome: 'pending',
  z_score: '',
  corrective_actions: '',
  notes: '',
  results: [],
  participant_results: [],
})

const selectedFormScheme = optionProxy('scheme_type', schemeFormOptions, form)
const selectedFormRole = optionProxy('role', roleFormOptions, form)
const selectedFormStatus = optionProxy('status', statusFormOptions, form)
const selectedFormOutcome = optionProxy('outcome', outcomeFormOptions, form)
const editorTitle = computed(() => editingRecordId.value ? 'Editar programa' : 'Novo programa')

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

function participantStatusLabel(value) {
  return {
    pending: 'Pendente',
    enrolled: 'Inscrito',
    submitted: 'Submetido',
    reviewed: 'Revisto',
    requires_action: 'Requer acção',
  }[value] || 'Pendente'
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

function statusBadgeClass(status) {
  return {
    planned: 'ds-badge-neutral',
    in_progress: 'ds-badge-info',
    completed: 'ds-badge-success',
    reviewed: 'ds-badge-warning',
    closed: 'ds-badge-success',
  }[status] || 'ds-badge-neutral'
}

function deadlineTextClass(state) {
  return {
    overdue: 'text-rose-600 dark:text-rose-300',
    due_soon: 'text-amber-600 dark:text-amber-300',
    on_track: 'text-emerald-600 dark:text-emerald-300',
    closed: 'text-[var(--ds-text-soft)]',
  }[state] || 'text-[var(--ds-text-soft)]'
}

function deadlineLabel(item) {
  if (item.deadline_state === 'closed') {
    return 'Encerrado'
  }

  if (item.deadline_state === 'overdue') {
    return 'Vencido'
  }

  if (item.deadline_state === 'due_soon') {
    return `${Math.max(Number(item.days_until_deadline ?? 0), 0)} dias restantes`
  }

  if (item.deadline_state === 'unscheduled') {
    return 'Sem prazo'
  }

  return 'No prazo'
}

function formatDate(value) {
  return value ? new Date(`${value}T00:00:00`).toLocaleDateString('pt-PT') : '—'
}

function resetForm() {
  form.defaults({
    name: '',
    scheme_type: 'proficiency',
    role: 'participant',
    provider_name: '',
    organizer_name: '',
    participants: [],
    parameters: [],
    round_reference: '',
    status: 'planned',
    date: '',
    scheduled_at: '',
    enrollment_deadline_at: '',
    submission_deadline_at: '',
    closed_at: '',
    scope: '',
    outcome: 'pending',
    z_score: '',
    corrective_actions: '',
    notes: '',
    results: [],
    participant_results: [],
  })
  form.reset()
  form.clearErrors()
}

function openCreate() {
  editingRecordId.value = null
  resetForm()
  showEditor.value = true
}

function openEdit(item) {
  editingRecordId.value = item.id
  form.defaults({
    name: item.name || '',
    scheme_type: item.scheme_type || 'proficiency',
    role: item.role || 'participant',
    provider_name: item.provider_name || '',
    organizer_name: item.organizer_name || '',
    participants: item.participants || [],
    parameters: item.parameters || [],
    round_reference: item.round_reference || '',
    status: item.status || 'planned',
    date: item.date || '',
    scheduled_at: item.scheduled_at || '',
    enrollment_deadline_at: item.enrollment_deadline_at || '',
    submission_deadline_at: item.submission_deadline_at || '',
    closed_at: item.closed_at || '',
    scope: item.scope || '',
    outcome: item.outcome || 'pending',
    z_score: item.z_score ?? '',
    corrective_actions: item.corrective_actions || '',
    notes: item.notes || '',
    results: item.results || [],
    participant_results: item.participant_results || [],
  })
  form.reset()
  form.clearErrors()
  showEditor.value = true
}

function closeEditor() {
  showEditor.value = false
  editingRecordId.value = null
  resetForm()
}

function submit() {
  if (editingRecordId.value) {
    form.put(route('proficiency_tests.update', { test: editingRecordId.value }), {
      preserveScroll: true,
      onSuccess: closeEditor,
    })

    return
  }

  form.post(route('proficiency_tests.store'), {
    preserveScroll: true,
    onSuccess: closeEditor,
  })
}

function destroy(item) {
  router.post(item.links.delete_path, { recordIds: [item.id] }, { preserveScroll: true })
}

function restore(item) {
  router.post(item.links.restore_path, { recordIds: [item.id] }, { preserveScroll: true })
}

function toggleArchived() {
  filters.filter = filters.filter === 'trashed' ? '' : 'trashed'
}

function clearFilters() {
  filters.search = ''
  filters.status = ''
  filters.scheme_type = ''
  filters.filter = ''
}

function addParticipant() {
  form.participants.push({ code: '', name: '', contact: '', status: 'pending' })
}

function removeParticipant(index) {
  form.participants.splice(index, 1)
}

function addParameter() {
  form.parameters.push({ code: '', name: '', unit: '', assigned_value: '', standard_deviation: '' })
}

function removeParameter(index) {
  form.parameters.splice(index, 1)
}
</script>
