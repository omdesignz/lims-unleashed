<template>
  <div class="pl-page space-y-6">
    <PageHeader title="Biblioteca de fórmulas" lede="Definições versionáveis usadas para transformar dados brutos em resultados calculados e reportáveis.">
      <template #actions>
        <Link v-if="hasPermission('view_variables')" :href="route('variables.index')" class="ds-button ds-button-secondary">
          <VariableIcon class="h-4 w-4" />
          Variáveis globais
        </Link>
        <Link v-if="hasPermission('add_formulas')" :href="route('formulas.create')" class="ds-button ds-button-primary">
          <PlusIcon class="h-4 w-4" />
          Nova fórmula
        </Link>
      </template>
    </PageHeader>

    <section class="ds-command-surface overflow-hidden">
      <div class="grid gap-3 border-b border-[var(--ds-border)] p-4 sm:grid-cols-[minmax(0,1fr)_13rem] sm:p-5">
        <label class="relative block">
          <span class="sr-only">Pesquisar fórmulas</span>
          <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" />
          <BaseInput v-model="query.search" type="search" class="ds-field pl-9" placeholder="Nome, código ou categoria" />
        </label>
        <BaseSelect v-model="query.filter" class="ds-field" aria-label="Estado das fórmulas">
          <option value="">Fórmulas activas</option>
          <option value="trashed">Incluir arquivadas</option>
        </BaseSelect>
      </div>

      <div v-if="records.length" class="overflow-x-auto">
        <DataTable class="min-w-full divide-y divide-[var(--ds-border)]">
          <thead class="ds-table-head">
            <tr>
              <th class="ds-table-heading px-4 py-3 text-left sm:px-5">Fórmula</th>
              <th class="ds-table-heading px-4 py-3 text-left">Expressão</th>
              <th class="ds-table-heading px-4 py-3 text-left">Aplicação</th>
              <th class="ds-table-heading px-4 py-3 text-left">Dependências</th>
              <th class="ds-table-heading px-4 py-3 text-right sm:px-5"><span class="sr-only">Acções</span></th>
            </tr>
          </thead>
          <tbody class="ds-table-body divide-y divide-[var(--ds-border)]">
            <tr v-for="formula in records" :key="formula.id" class="ds-table-row">
              <td class="ds-table-cell min-w-52 px-4 py-4 sm:px-5">
                <div class="flex items-start gap-3">
                  <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))]">
                    <CalculatorIcon class="h-4 w-4" />
                  </span>
                  <div class="min-w-0">
                    <p class="font-bold text-[var(--ds-text)]">{{ formula.name }}</p>
                    <p class="mt-1 font-mono text-xs text-[var(--ds-text-soft)]">{{ formula.code }}</p>
                    <span class="mt-2 ds-badge" :class="formula.deleted ? 'ds-badge-danger' : formula.is_active ? 'ds-badge-success' : 'ds-badge-neutral'">
                      {{ formula.deleted ? 'Arquivada' : formula.is_active ? 'Activa' : 'Inactiva' }}
                    </span>
                  </div>
                </div>
              </td>
              <td class="ds-table-cell min-w-72 px-4 py-4">
                <div class="max-w-xl overflow-x-auto rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2">
                  <FormulaDisplay :formula="formula.expression" />
                </div>
                <p v-if="formula.output_unit" class="mt-2 text-xs font-semibold text-[var(--ds-text-soft)]">Saída: {{ formula.output_unit }} · {{ formula.decimal_places }} casas decimais</p>
              </td>
              <td class="ds-table-cell px-4 py-4">
                <span class="ds-badge ds-badge-info">{{ categoryLabel(formula.category) }}</span>
              </td>
              <td class="ds-table-cell px-4 py-4">
                <p class="font-bold tabular-nums text-[var(--ds-text)]">{{ formula.variables_count || 0 }} variáveis</p>
                <p class="mt-1 text-xs text-[var(--ds-text-soft)]">{{ formula.parameters_count || 0 }} parâmetros associados</p>
              </td>
              <td class="ds-table-cell px-4 py-4 text-right sm:px-5">
                <div class="inline-flex items-center gap-1">
                  <Link
                    v-if="!formula.deleted && hasPermission('edit_formulas')"
                    :href="formula.links.edit_path"
                    class="ds-icon-button"
                    title="Editar fórmula"
                  >
                    <PencilSquareIcon class="h-4 w-4" />
                  </Link>
                  <button
                    v-if="formula.deleted ? hasPermission('restore_formulas') : hasPermission('delete_formulas')"
                    type="button"
                    class="ds-icon-button"
                    :class="formula.deleted ? 'hover:!text-emerald-600' : 'hover:!text-red-600'"
                    :title="formula.deleted ? 'Restaurar fórmula' : 'Arquivar fórmula'"
                    @click="requestRecordAction(formula)"
                  >
                    <ArrowUturnLeftIcon v-if="formula.deleted" class="h-4 w-4" />
                    <ArchiveBoxXMarkIcon v-else class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </DataTable>
      </div>

      <div v-else class="p-5 sm:p-8">
        <div class="ds-empty-state px-6 py-10 text-center">
          <CalculatorIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
          <h2 class="ds-heading mt-3 text-sm">Nenhuma fórmula encontrada</h2>
          <p class="ds-copy mt-1 text-sm">Ajuste os filtros ou crie a primeira definição de cálculo.</p>
          <Link v-if="hasPermission('add_formulas')" :href="route('formulas.create')" class="ds-button ds-button-primary mt-4">
            <PlusIcon class="h-4 w-4" />
            Nova fórmula
          </Link>
        </div>
      </div>
    </section>

    <Pagination v-if="record.meta?.last_page > 1" v-bind="record.meta" />

    <ConfirmDialog
      v-if="actionRecord"
      :title="actionRecord.deleted ? 'Restaurar fórmula' : 'Arquivar fórmula'"
      :description="actionRecord.deleted
        ? `A fórmula “${actionRecord.name}” voltará a estar disponível para configuração.`
        : `A fórmula “${actionRecord.name}” será retirada das novas configurações.`"
      :confirm="actionRecord.deleted ? 'Restaurar' : 'Arquivar'"
      :variant="actionRecord.deleted ? 'question' : 'danger'"
      @confirmed="executeRecordAction"
      @canceled="actionRecord = null"
    />
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import { Link, router } from '@inertiajs/vue3'
import debounce from 'lodash/debounce'
import {
  ArchiveX as ArchiveBoxXMarkIcon,
  Undo2 as ArrowUturnLeftIcon,
  Calculator as CalculatorIcon,
  Search as MagnifyingGlassIcon,
  SquarePen as PencilSquareIcon,
  Plus as PlusIcon,
  Variable as VariableIcon,
} from '@lucide/vue'
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import FormulaDisplay from '@/Components/formula-display.vue'
import Pagination from '@/Components/pagination.vue'
import { usePermission } from '@/Composables/usePermissions'
import Layout from '@/Shared/Layouts/Layout.vue'

defineOptions({ layout: Layout })

const props = defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  fields: { type: Array, default: () => [] },
  model: String,
  abilities: { type: Array, default: () => [] },
  query: { type: Object, default: () => ({}) },
  slideOverEdit: { type: Boolean, default: false },
})

const { hasPermission } = usePermission()
const query = reactive({
  search: props.query?.search || '',
  filter: props.query?.filter || '',
})
const actionRecord = ref(null)

const records = computed(() => props.record.data || [])

const categoryLabels = {
  general: 'Geral',
  microbiology: 'Microbiologia',
  physicochemical: 'Físico-química',
  custom: 'Personalizada',
}

function categoryLabel(category) {
  return categoryLabels[category] || category || 'Não definida'
}

function requestRecordAction(formula) {
  actionRecord.value = formula
}

function executeRecordAction() {
  const formula = actionRecord.value
  const target = formula.deleted ? formula.links.restore_path : formula.links.delete_path

  router.get(target, {}, {
    preserveScroll: true,
    onFinish: () => {
      actionRecord.value = null
    },
  })
}

watch(query, debounce(() => {
  router.get(route('formulas.index'), {
    search: query.search || undefined,
    filter: query.filter || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}, 300), { deep: true })
</script>
