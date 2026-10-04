<template>
  <div class="space-y-6" :class="commercialDocumentThemeClasses">
    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-5 py-5 sm:flex sm:items-start sm:justify-between sm:gap-6 lg:px-6">
        <div class="min-w-0">
          <p class="ds-kicker">Metrologia e manutenção</p>
          <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="ds-heading text-2xl">Detalhes da tarefa</h1>
            <span :class="getStatusClasses(task)">{{ getStatusText(task) }}</span>
          </div>
          <p class="ds-copy mt-2 flex flex-wrap items-center gap-2 text-sm">
            <CalendarIcon class="h-4 w-4" />
            Criada em {{ formatDateTime(task.created_at) }}
          </p>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2 sm:mt-0 sm:justify-end">
          <Link :href="route('vap-maintenance.tasks')" class="ds-button ds-button-secondary">
            <ArrowLeftIcon class="h-4 w-4" />
            Voltar
          </Link>
          <Link v-if="can.edit" :href="route('vap-maintenance.tasks.edit', task.id)" class="ds-button ds-button-primary">
            <PencilIcon class="h-4 w-4" />
            Editar
          </Link>
        </div>
      </div>

      <div class="grid gap-px bg-[var(--ds-border)] sm:grid-cols-2 xl:grid-cols-4">
        <article class="bg-[var(--ds-panel)] p-5">
          <p class="text-xs font-bold uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Vencimento</p>
          <p :class="['mt-3 text-2xl font-bold', getDueDateColor(task)]">{{ formatDate(task.due_date) || 'Sem data' }}</p>
          <p class="mt-2 text-sm font-medium text-[var(--ds-text-muted)]">{{ task.periodicity ? `A cada ${task.periodicity} ${getPeriodicityUnitText(task.periodicity_unit)}` : 'Sem recorrência' }}</p>
        </article>
        <article class="bg-[var(--ds-panel)] p-5">
          <p class="text-xs font-bold uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Equipamento</p>
          <p class="mt-3 truncate text-2xl font-bold text-[var(--ds-text)]">{{ task.equipment?.name || 'N/A' }}</p>
          <p class="mt-2 text-sm font-medium text-[var(--ds-text-muted)]">{{ task.equipment?.internal_code || 'Sem código' }}</p>
        </article>
        <article class="bg-[var(--ds-panel)] p-5">
          <p class="text-xs font-bold uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Fornecedor</p>
          <p class="mt-3 truncate text-2xl font-bold text-[var(--ds-text)]">{{ task.supplier?.name || 'Interno' }}</p>
          <p class="mt-2 text-sm font-medium text-[var(--ds-text-muted)]">{{ task.executed_by_supplier ? 'Execução externa' : 'Execução interna' }}</p>
        </article>
        <article class="bg-[var(--ds-panel)] p-5">
          <p class="text-xs font-bold uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Custo</p>
          <p class="mt-3 text-2xl font-bold text-[var(--ds-text)]">{{ formatCurrency(task.cost) }}</p>
          <p class="mt-2 text-sm font-medium text-[var(--ds-text-muted)]">{{ task.calibration_certificate_no || 'Sem certificado' }}</p>
        </article>
      </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
      <main class="space-y-6">
        <section class="ds-card overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:flex sm:items-center sm:justify-between sm:gap-4">
            <div>
              <h2 class="text-base font-bold text-[var(--ds-text)]">{{ task.name }}</h2>
              <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">{{ task.maintenance_task_no || 'Sem número' }}</p>
            </div>
            <span class="ds-chip mt-3 sm:mt-0">{{ task.category?.name || 'Sem categoria' }}</span>
          </div>

          <div class="grid gap-5 p-5 md:grid-cols-2">
            <div class="space-y-5">
              <div>
                <p class="ds-field-label">Categoria</p>
                <p class="mt-2 flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]">
                  <TagIcon class="h-4 w-4 text-[var(--ds-text-soft)]" />
                  {{ task.category?.name || 'Não definida' }}
                </p>
              </div>

              <div>
                <p class="ds-field-label">Equipamento</p>
                <div class="mt-2 flex items-start gap-2">
                  <CogIcon class="mt-0.5 h-4 w-4 text-[var(--ds-text-soft)]" />
                  <div>
                    <p class="text-sm font-bold text-[var(--ds-text)]">{{ task.equipment?.name || 'Equipamento não encontrado' }}</p>
                    <p v-if="task.equipment" class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
                      {{ task.equipment.internal_code || 'Sem código' }}
                      <span v-if="task.equipment.model" class="ml-2">{{ task.equipment.model }}</span>
                      <span v-if="task.equipment.serial_number" class="ml-2">S/N: {{ task.equipment.serial_number }}</span>
                    </p>
                  </div>
                </div>
              </div>

              <div>
                <p class="ds-field-label">Descrição</p>
                <p class="mt-2 rounded-lg bg-[var(--ds-panel-subtle)] p-4 text-sm font-medium text-[var(--ds-text-muted)]">
                  {{ task.description || 'Sem descrição' }}
                </p>
              </div>
            </div>

            <div class="space-y-5">
              <div>
                <p class="ds-field-label">Datas</p>
                <dl class="mt-2 grid gap-2 text-sm">
                  <div class="flex items-center justify-between gap-4">
                    <dt class="font-semibold text-[var(--ds-text-soft)]">Vencimento</dt>
                    <dd :class="['font-bold', getDueDateColor(task)]">{{ formatDate(task.due_date) }}</dd>
                  </div>
                  <div v-if="task.previous_date" class="flex items-center justify-between gap-4">
                    <dt class="font-semibold text-[var(--ds-text-soft)]">Anterior</dt>
                    <dd class="font-bold text-[var(--ds-text-muted)]">{{ formatDate(task.previous_date) }}</dd>
                  </div>
                  <div v-if="task.next_date" class="flex items-center justify-between gap-4">
                    <dt class="font-semibold text-[var(--ds-text-soft)]">Próximo</dt>
                    <dd class="font-bold text-[var(--ds-text-muted)]">{{ formatDate(task.next_date) }}</dd>
                  </div>
                </dl>
                <div v-if="task.periodicity" class="ds-command-toolbar mt-3 p-3">
                  <p class="flex items-center gap-2 text-sm font-semibold text-[var(--ds-text-muted)]">
                    <ArrowPathRoundedSquareIcon class="h-4 w-4" />
                    Recorrência: {{ task.periodicity }} {{ getPeriodicityUnitText(task.periodicity_unit) }}
                  </p>
                </div>
              </div>

              <div>
                <p class="ds-field-label">Estado operacional</p>
                <div class="mt-2 grid gap-2">
                  <p class="flex items-center gap-2 text-sm font-semibold text-[var(--ds-text-muted)]">
                    <span :class="['lims-status-dot', task.is_planned ? 'lims-status-dot-instrument' : 'lims-status-dot-hold']" />
                    {{ task.is_planned ? 'Tarefa planeada' : 'Tarefa não planeada' }}
                  </p>
                  <p class="flex items-center gap-2 text-sm font-semibold text-[var(--ds-text-muted)]">
                    <span :class="['lims-status-dot', task.is_executed ? 'lims-status-dot-release' : 'lims-status-dot-hold']" />
                    {{ task.is_executed ? 'Tarefa executada' : 'Tarefa pendente' }}
                  </p>
                  <p class="flex items-center gap-2 text-sm font-semibold text-[var(--ds-text-muted)]">
                    <span :class="['lims-status-dot', task.executed_by_supplier ? 'lims-status-dot-critical' : 'lims-status-dot-instrument']" />
                    {{ task.executed_by_supplier ? 'Executada por fornecedor' : 'Executada internamente' }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="ds-card overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
              <DocumentTextIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb)/1)]" />
              Detalhes técnicos
            </h2>
          </div>

          <div class="grid gap-5 p-5 md:grid-cols-2">
            <div class="space-y-4">
              <div v-if="task.acceptance_criteria">
                <p class="ds-field-label">Critério de aceitação</p>
                <p class="mt-2 rounded-lg bg-emerald-50 p-3 text-sm font-bold text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20">
                  {{ task.acceptance_criteria }}
                </p>
              </div>

              <div v-if="task.range">
                <p class="ds-field-label">Gama</p>
                <p class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ task.range }}</p>
              </div>

              <div v-if="task.calibration_points">
                <p class="ds-field-label">Pontos de calibração</p>
                <p class="mt-2 whitespace-pre-line rounded-lg bg-[var(--ds-panel-subtle)] p-3 text-sm font-medium text-[var(--ds-text-muted)]">
                  {{ task.calibration_points }}
                </p>
              </div>
            </div>

            <div class="space-y-4">
              <div v-if="task.calibration_certificate_no">
                <p class="ds-field-label">Certificado de calibração</p>
                <p class="mt-2 flex items-center gap-2 text-sm font-bold text-[rgb(var(--primary-700-rgb)/1)]">
                  <DocumentTextIcon class="h-4 w-4" />
                  {{ task.calibration_certificate_no }}
                </p>
                <span v-if="task.calibration_status" :class="getCalibrationStatusClasses(task.calibration_status)">
                  {{ getCalibrationStatusText(task.calibration_status) }}
                </span>
              </div>

              <div v-if="task.result">
                <p class="ds-field-label">Resultado da manutenção</p>
                <p class="mt-2 whitespace-pre-line rounded-lg bg-[var(--ds-panel-subtle)] p-3 text-sm font-medium text-[var(--ds-text-muted)]">
                  {{ task.result }}
                </p>
              </div>

              <div v-if="task.obs">
                <p class="ds-field-label">Observações</p>
                <p class="mt-2 whitespace-pre-line rounded-lg bg-amber-50 p-3 text-sm font-medium text-amber-800 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20">
                  {{ task.obs }}
                </p>
              </div>
            </div>
          </div>
        </section>
      </main>

      <aside class="space-y-6">
        <section class="ds-card p-5">
          <h3 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
            <CurrencyEuroIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb)/1)]" />
            Custo e fornecedor
          </h3>
          <p class="mt-5 text-3xl font-bold text-[var(--ds-text)]">{{ formatCurrency(task.cost) }}</p>
          <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">Custo da tarefa</p>

          <div v-if="task.supplier" class="mt-5 border-t border-[var(--ds-border)] pt-5">
            <p class="ds-field-label">Fornecedor</p>
            <div class="mt-2 flex items-start gap-3 rounded-lg bg-[var(--ds-panel-subtle)] p-3">
              <TruckIcon class="mt-0.5 h-5 w-5 text-[var(--ds-text-soft)]" />
              <div>
                <p class="text-sm font-bold text-[var(--ds-text)]">{{ task.supplier.name }}</p>
                <p v-if="task.supplier.email" class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ task.supplier.email }}</p>
                <p v-if="task.supplier.phone" class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ task.supplier.phone }}</p>
              </div>
            </div>
          </div>

          <div v-if="task.executed_by_supplier && !task.supplier" class="mt-5 border-t border-[var(--ds-border)] pt-5">
            <p class="rounded-lg bg-amber-50 p-3 text-center text-sm font-semibold text-amber-800 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20">
              <ExclamationTriangleIcon class="mx-auto mb-2 h-5 w-5" />
              Fornecedor não especificado.
            </p>
          </div>
        </section>

        <section class="ds-command-surface p-5">
          <h3 class="text-base font-bold text-[var(--ds-text)]">Acções</h3>
          <div v-if="completionForm.hasErrors || notificationError || archiveFailed" class="ds-field-error mt-3" role="alert">
            <p v-for="(error, field) in completionForm.errors" :key="field">{{ error }}</p>
            <p v-if="notificationError">{{ notificationError }}</p>
            <p v-if="archiveFailed">{{ archiveMessage }}</p>
          </div>
          <p v-if="notificationMessage" class="ds-copy mt-3 text-sm" role="status">{{ notificationMessage }}</p>
          <div class="mt-4 space-y-3">
            <button v-if="can.edit && !task.is_executed" type="button" class="ds-button ds-button-primary w-full" :disabled="completionForm.processing" :aria-busy="completionForm.processing" @click="markAsExecuted">
              <CheckCircleIcon class="h-4 w-4" />
              Marcar como executada
            </button>

            <button v-if="can.edit" type="button" class="ds-button ds-button-secondary w-full" @click="recordResult">
              <PencilIcon class="h-4 w-4" />
              Registar resultado
            </button>

            <Link v-if="can.create" :href="route('vap-maintenance.tasks.create')" class="ds-button ds-button-secondary w-full">
              <DocumentDuplicateIcon class="h-4 w-4" />
              Nova tarefa
            </Link>

            <button v-if="can.export" type="button" class="ds-button ds-button-secondary w-full" @click="printTask">
              <PrinterIcon class="h-4 w-4" />
              Imprimir
            </button>

            <button v-if="can.edit && task.is_executed" type="button" class="ds-button ds-button-secondary w-full" :disabled="notificationRequest.processing" @click="notifyCompletion">
              <BellAlertIcon class="h-4 w-4" />
              Notificar conclusão
            </button>

            <button v-if="can.delete" type="button" class="ds-button ds-button-danger w-full" :disabled="archiving" :aria-busy="archiving" @click="deleteTask">
              <TrashIcon class="h-4 w-4" />
              {{ archiving ? 'A arquivar…' : 'Arquivar tarefa' }}
            </button>
          </div>
        </section>

        <section class="ds-card p-5">
          <h3 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
            <ClockIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb)/1)]" />
            Histórico
          </h3>
          <dl class="mt-4 grid gap-3 text-sm">
            <div class="flex items-center justify-between gap-4">
              <dt class="font-semibold text-[var(--ds-text-soft)]">Criada em</dt>
              <dd class="font-bold text-[var(--ds-text)]">{{ formatDateTime(task.created_at) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
              <dt class="font-semibold text-[var(--ds-text-soft)]">Actualizada em</dt>
              <dd class="font-bold text-[var(--ds-text)]">{{ formatDateTime(task.updated_at) }}</dd>
            </div>
            <div v-if="task.deleted_at" class="flex items-center justify-between gap-4">
              <dt class="font-semibold text-[var(--ds-text-soft)]">Eliminada em</dt>
              <dd class="font-bold text-rose-700 dark:text-rose-200">{{ formatDateTime(task.deleted_at) }}</dd>
            </div>
          </dl>
        </section>
      </aside>
    </div>

    <section class="ds-table-shell">
      <div class="ds-table-summary px-5 py-4">
        <div>
          <h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
            <CogIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb)/1)]" />
            Histórico do equipamento
          </h2>
          <p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">
            Últimas tarefas e custos associados ao equipamento seleccionado.
          </p>
        </div>
        <Link
          v-if="task.equipment?.id"
          :href="route('vap-inventory.items.show', task.equipment.id)"
          class="ds-button ds-button-secondary"
        >
          <ArrowRightIcon class="h-4 w-4" />
          Ver equipamento
        </Link>
      </div>

      <div v-if="equipmentHistory" class="space-y-0">
        <div class="grid gap-px bg-[var(--ds-border)] md:grid-cols-4">
          <article v-for="card in equipmentHistoryCards" :key="card.label" class="bg-[var(--ds-panel)] p-5">
            <p class="text-xs font-bold uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">{{ card.label }}</p>
            <p class="mt-3 text-2xl font-bold text-[var(--ds-text)]">{{ card.value }}</p>
          </article>
        </div>

        <div class="border-t border-[var(--ds-border)] p-5">
          <h3 class="text-sm font-bold text-[var(--ds-text)]">Tarefas recentes</h3>
          <div v-if="recentEquipmentTasks.length" class="mt-3 grid gap-2">
            <Link
              v-for="historyTask in recentEquipmentTasks"
              :key="historyTask.id"
              :href="route('vap-maintenance.tasks.show', historyTask.id)"
              class="ds-card flex items-center justify-between gap-4 p-3 transition hover:border-[var(--ds-border-strong)]"
            >
              <div class="flex min-w-0 items-center gap-3">
                <span :class="[
                  'inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg ring-1',
                  historyTask.is_executed
                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20'
                    : 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20',
                ]">
                  <WrenchScrewdriverIcon class="h-4 w-4" />
                </span>
                <span class="min-w-0">
                  <span class="block truncate text-sm font-bold text-[var(--ds-text)]">{{ historyTask.name }}</span>
                  <span class="mt-1 block text-xs font-semibold text-[var(--ds-text-muted)]">
                    {{ historyTask.category?.name || 'Sem categoria' }} · {{ formatDate(historyTask.due_date) }}
                  </span>
                </span>
              </div>
              <span class="text-right">
                <span class="block text-sm font-bold text-[var(--ds-text)]">{{ formatCurrency(historyTask.cost) }}</span>
                <span class="mt-1 block text-xs font-semibold text-[var(--ds-text-muted)]">{{ historyTask.is_executed ? 'Executada' : 'Pendente' }}</span>
              </span>
            </Link>
          </div>

          <div v-else class="ds-empty-state mt-3 px-6 py-8 text-center">
            <CogIcon class="mx-auto h-10 w-10 text-[var(--ds-text-soft)]" />
            <p class="mt-3 text-sm font-bold text-[var(--ds-text)]">Nenhuma tarefa recente</p>
          </div>
        </div>
      </div>

      <div v-else class="p-6">
        <div class="ds-empty-state px-6 py-10 text-center">
          <CogIcon class="mx-auto h-10 w-10 text-[var(--ds-text-soft)]" />
          <p class="mt-3 text-sm font-bold text-[var(--ds-text)]">Nenhum histórico disponível para este equipamento</p>
        </div>
      </div>
    </section>
    <Modal :show="showRecordResultModal" @close="showRecordResultModal = false">
      <div class="p-5 sm:p-6">
        <div>
          <p class="ds-kicker">Execução</p>
          <h2 class="ds-heading mt-2 text-lg">Registar resultado da manutenção</h2>
          <p class="ds-copy mt-1 text-sm">Guarde o resultado técnico antes de concluir a tarefa. A agenda é calculada a partir da periodicidade.</p>
        </div>

        <form class="mt-6 space-y-6" @submit.prevent="submitResult">
          <div v-if="resultForm.hasErrors" class="ds-field-error" role="alert">
            <p v-for="(error, field) in resultForm.errors" :key="field">{{ error }}</p>
          </div>
          <label class="ds-field-group">
            <span class="ds-field-label">Resultado da manutenção <span class="ds-field-required">*</span></span>
            <textarea
              v-model="resultForm.result"
              rows="6"
              required
              class="ds-field min-h-36"
              placeholder="Resultados, observações e peças substituídas"
            />
            <span v-if="resultForm.errors.result" class="ds-field-error">{{ resultForm.errors.result }}</span>
          </label>

          <label v-if="isCalibrationTask" class="ds-field-group">
            <span class="ds-field-label">Estado da calibração</span>
            <BaseSelect v-model="resultForm.calibration_status" class="ds-field">
              <option value="">Seleccione um estado</option>
              <option value="approved">Aprovado</option>
              <option value="rejected">Rejeitado</option>
              <option value="pending">Pendente</option>
            </BaseSelect>
          </label>


          <div class="flex items-center justify-end gap-2 border-t border-[var(--ds-border)] pt-5">
            <button type="button" class="ds-button ds-button-secondary" @click="showRecordResultModal = false">
              Cancelar
            </button>
            <button type="submit" class="ds-button ds-button-primary" :disabled="resultForm.processing">
              <CheckCircleIcon class="h-4 w-4" />
              {{ resultForm.processing ? 'A processar...' : 'Registar resultado' }}
            </button>
          </div>
        </form>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { commercialDocumentThemeClasses } from '@/Composables/useCommercialDocumentTheme'
import { Link, useForm, useHttp } from '@inertiajs/vue3'
import { useRecordArchive } from '@/Composables/useRecordArchive'
import {
  Wrench as WrenchScrewdriverIcon,
  ArrowLeft as ArrowLeftIcon,
  Copy as DocumentDuplicateIcon,
  Tag as TagIcon,
  Cog as CogIcon,
  Calendar as CalendarIcon,
  Repeat as ArrowPathRoundedSquareIcon,
  FileText as DocumentTextIcon,
  Euro as CurrencyEuroIcon,
  Truck as TruckIcon,
  TriangleAlert as ExclamationTriangleIcon,
  CircleCheck as CheckCircleIcon,
  Pencil as PencilIcon,
  Printer as PrinterIcon,
  BellRing as BellAlertIcon,
  Trash2 as TrashIcon,
  Clock as ClockIcon,
  ArrowRight as ArrowRightIcon,
} from '@lucide/vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  can: { type: Object, default: () => ({}) },
  task: Object,
  equipmentHistory: Object,
})

// State
const showRecordResultModal = ref(false)
const equipmentHistory = computed(() => props.equipmentHistory ?? null)

// Forms
const completionForm = useForm({ is_executed: true })
const { processing: archiving, message: archiveMessage, failed: archiveFailed, submit: submitArchive } = useRecordArchive({
  destroyUrl: () => route('vap-maintenance.tasks.destroy', props.task.id),
})
const notificationRequest = useHttp({})
const notificationError = ref('')
const notificationMessage = ref('')
const resultForm = useForm({
  result: props.task.result || '',
  calibration_status: props.task.calibration_status || '',
})

// Computed
const isCalibrationTask = computed(() => {
  return props.task.category?.code?.includes('CAL') || 
         ['Calibration', 'Calibração'].includes(props.task.category?.name || '')
})

const equipmentHistoryCards = computed(() => {
  const stats = equipmentHistory.value?.stats ?? {}

  return [
    { label: 'Total de tarefas', value: stats.total_tasks ?? 0 },
    { label: 'Executadas', value: stats.executed_tasks ?? 0 },
    { label: 'Custo total', value: formatCurrency(stats.total_cost ?? 0) },
    { label: 'Custo médio', value: formatCurrency(stats.avg_cost ?? 0) },
  ]
})

const recentEquipmentTasks = computed(() => {
  return (equipmentHistory.value?.tasks ?? []).slice(0, 5)
})

// Methods
const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDateTime = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('pt-PT', {
    style: 'currency',
    currency: 'AOA'
  }).format(amount || 0)
}

const getPeriodicityUnitText = (unit) => {
  const units = {
    hours: 'horas',
    days: 'dias',
    weeks: 'semanas',
    months: 'meses',
    years: 'anos'
  }
  return units[unit] || unit
}

const getStatusClasses = (task) => {
  if (task.is_executed) {
    return 'inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20'
  }
  
  const dueDate = new Date(task.due_date)
  const today = new Date()
  const daysDiff = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24))
  
  if (daysDiff < 0) {
    return 'inline-flex items-center rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20'
  } else if (daysDiff <= 7) {
    return 'inline-flex items-center rounded-full bg-orange-50 px-3 py-1 text-xs font-bold text-orange-700 ring-1 ring-orange-200 dark:bg-orange-500/10 dark:text-orange-200 dark:ring-orange-400/20'
  } else if (daysDiff <= 30) {
    return 'inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20'
  } else {
    return 'inline-flex items-center rounded-full bg-cyan-50 px-3 py-1 text-xs font-bold text-cyan-700 ring-1 ring-cyan-200 dark:bg-cyan-500/10 dark:text-cyan-200 dark:ring-cyan-400/20'
  }
}

const getStatusText = (task) => {
  if (task.is_executed) return 'Concluída'
  
  const dueDate = new Date(task.due_date)
  const today = new Date()
  const daysDiff = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24))
  
  if (daysDiff < 0) return 'Atrasada'
  if (daysDiff <= 7) return 'Vencendo em Breve'
  if (daysDiff <= 30) return 'Próxima'
  return 'Agendada'
}

const getDueDateColor = (task) => {
  if (task.is_executed) return 'text-emerald-700 dark:text-emerald-200'
  
  const dueDate = new Date(task.due_date)
  const today = new Date()
  const daysDiff = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24))
  
  if (daysDiff < 0) return 'text-rose-700 dark:text-rose-200'
  if (daysDiff <= 7) return 'text-orange-700 dark:text-orange-200'
  if (daysDiff <= 30) return 'text-amber-700 dark:text-amber-200'
  return 'text-cyan-700 dark:text-cyan-200'
}

const getCalibrationStatusText = (status) => {
  const statusMap = {
    approved: 'Aprovado',
    rejected: 'Rejeitado',
    pending: 'Pendente'
  }
  return statusMap[status] || status
}

const getCalibrationStatusClasses = (status) => {
  const base = 'mt-2 inline-flex items-center rounded-full px-2 py-1 text-xs font-bold ring-1'

  if (status === 'approved') {
    return `${base} bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20`
  }

  if (status === 'rejected') {
    return `${base} bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20`
  }

  return `${base} bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20`
}

const markAsExecuted = () => {
  if (!props.can.edit || completionForm.processing) return
  if (confirm('Concluir esta tarefa com o resultado registado?')) {
    completionForm.put(route('vap-maintenance.tasks.update', props.task.id))
  }
}

const recordResult = () => {
  resultForm.result = props.task.result ?? ''
  resultForm.calibration_status = props.task.calibration_status ?? ''
  resultForm.clearErrors()
  showRecordResultModal.value = true
}

const submitResult = () => {
  if (!props.can.edit || resultForm.processing) return
  resultForm.put(route('vap-maintenance.tasks.update', props.task.id), {
    onSuccess: () => {
      completionForm.clearErrors()
      showRecordResultModal.value = false
    }
  })
}

const printTask = () => {
  window.open(route('vap-maintenance.export', {
    format: 'pdf',
    type: 'tasks',
    task_id: props.task.id
  }), '_blank')
}

const notifyCompletion = async () => {
  if (!props.can.edit || notificationRequest.processing) return
  notificationError.value = ''
  notificationMessage.value = ''
  try {
    await notificationRequest.post(route('vap-maintenance.tasks.notify-completion', props.task.id), {
      onSuccess: () => { notificationMessage.value = 'Notificação de conclusão enviada.' },
      onError: () => { notificationError.value = 'Não foi possível enviar a notificação.' },
    })
  } catch {
    notificationError.value = 'Não foi possível enviar a notificação. Tente novamente.'
  }
}

const deleteTask = () => {
  if (!props.can.delete || archiving.value) return
  if (confirm('Arquivar esta tarefa? O registo será preservado.')) {
    submitArchive('delete', [props.task.id])
  }
}

</script>
