<template>
  <div class="space-y-6" :class="commercialDocumentThemeClasses">
    <Head title="Tarefas de manutenção" />
    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-5 py-5 sm:flex sm:items-start sm:justify-between sm:gap-6 lg:px-6">
        <div class="min-w-0">
          <p class="ds-kicker">Metrologia e manutenção</p>
          <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="ds-heading text-2xl">Tarefas de manutenção</h1>
            <span
              :class="[
                'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold ring-1',
                stats?.overdue > 0
                  ? 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20'
                  : 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20',
              ]"
            >
              {{ stats?.overdue > 0 ? `${stats.overdue} atrasadas` : 'Agenda em dia' }}
            </span>
          </div>
          <p class="ds-copy mt-2 max-w-3xl text-sm">
            Controle calibrações, preventivas, fornecedores e custos com uma lista operacional densa para rastreabilidade diária.
          </p>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2 sm:mt-0 sm:justify-end">
          <span class="ds-chip">{{ taskTotal }} tarefas</span>
          <Link :href="route('vap-maintenance.tasks', archived ? {} : { archived: 1 })" class="ds-button ds-button-secondary">
            {{ archived ? 'Tarefas activas' : 'Arquivo' }}
          </Link>
          <Link v-if="can.create" :href="route('vap-maintenance.tasks.create')" class="ds-button ds-button-primary">
            <PlusIcon class="h-4 w-4" />
            Nova tarefa
          </Link>
          <Link v-if="can.create" :href="route('maintenancetasks.import.form')" class="ds-button ds-button-secondary">Importar CSV</Link>
        </div>
      </div>

      <div class="grid gap-px bg-[var(--ds-border)] sm:grid-cols-2 xl:grid-cols-4">
        <article v-for="card in statsCards" :key="card.label" class="bg-[var(--ds-panel)] p-5">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-bold uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">{{ card.label }}</p>
              <p class="mt-3 text-3xl font-bold text-[var(--ds-text)]">{{ card.value }}</p>
              <p class="mt-2 text-sm font-medium text-[var(--ds-text-muted)]">{{ card.caption }}</p>
            </div>
            <span :class="['inline-flex h-10 w-10 items-center justify-center rounded-lg ring-1', card.tone]">
              <component :is="card.icon" class="h-5 w-5" />
            </span>
          </div>
        </article>
      </div>
    </section>

    <div v-if="completionRequest.hasErrors || completionError" class="ds-panel ds-field-error p-4" role="alert">
      <p v-if="completionError">{{ completionError }}</p>
      <p v-for="(error, key) in completionRequest.errors" :key="key">{{ error }}</p>
    </div>

    <section class="ds-command-surface p-4">
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <label class="ds-field-group">
          <span class="ds-field-label">
            <TagIcon class="mr-1 inline h-4 w-4" />
            Categoria
          </span>
          <BaseSelect v-model="filters.category_id" class="ds-field" @change="applyFilters">
            <option value="">Todas as categorias</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </BaseSelect>
        </label>

        <label class="ds-field-group">
          <span class="ds-field-label">
            <CogIcon class="mr-1 inline h-4 w-4" />
            Equipamento
          </span>
          <BaseSelect v-model="filters.equipment_id" class="ds-field" @change="applyFilters">
            <option value="">Todos os equipamentos</option>
            <option v-for="equipmentItem in equipment" :key="equipmentItem.id" :value="equipmentItem.id">
              {{ equipmentItem.name }} ({{ equipmentItem.internal_code || 'N/A' }})
            </option>
          </BaseSelect>
        </label>

        <label class="ds-field-group">
          <span class="ds-field-label">
            <CheckCircleIcon class="mr-1 inline h-4 w-4" />
            Estado
          </span>
          <BaseSelect v-model="filters.status" class="ds-field" @change="applyFilters">
            <option value="">Todos os estados</option>
            <option value="overdue">Atrasadas</option>
            <option value="executed">Executadas</option>
            <option value="planned">Planeadas</option>
          </BaseSelect>
        </label>

        <div class="ds-field-group">
          <span class="ds-field-label">
            <CalendarIcon class="mr-1 inline h-4 w-4" />
            Período
          </span>
          <div class="grid grid-cols-2 gap-2">
            <DateTimePicker
              v-model="filters.date_from"
              type="date"
              class="ds-field"
              placeholder="De"
              @change="applyFilters" />
            <DateTimePicker
              v-model="filters.date_to"
              type="date"
              class="ds-field"
              placeholder="Até"
              @change="applyFilters" />
          </div>
        </div>
      </div>

      <div class="mt-4 border-t border-[var(--ds-border)] pt-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <button
            type="button"
            class="ds-button ds-button-secondary"
            @click="showAdvancedFilters = !showAdvancedFilters"
          >
            <FunnelIcon class="h-4 w-4" />
            Filtros avançados
            <ChevronDownIcon :class="['h-4 w-4 transition-transform', showAdvancedFilters ? 'rotate-180' : '']" />
          </button>

          <div class="flex flex-wrap items-center gap-2">
            <span v-if="hasActiveFilters" class="ds-chip">Filtros activos</span>
            <button type="button" class="ds-button ds-button-secondary" @click="resetFilters">
              <XMarkIcon class="h-4 w-4" />
              Limpar filtros
            </button>
          </div>
        </div>

        <div v-if="showAdvancedFilters" class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          <label class="ds-field-group">
            <span class="ds-field-label">
              <TruckIcon class="mr-1 inline h-4 w-4" />
              Fornecedor
            </span>
            <BaseSelect v-model="filters.supplier_id" class="ds-field" @change="applyFilters">
              <option value="">Todos os fornecedores</option>
              <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                {{ supplier.name }}
              </option>
            </BaseSelect>
          </label>

          <div class="ds-field-group">
            <span class="ds-field-label">
              <CurrencyEuroIcon class="mr-1 inline h-4 w-4" />
              Custo
            </span>
            <div class="grid grid-cols-2 gap-2">
              <BaseInput
                v-model="filters.cost_min"
                type="number"
                step="0.01"
                min="0"
                class="ds-field"
                placeholder="Mín"
                @change="applyFilters"
              />
              <BaseInput
                v-model="filters.cost_max"
                type="number"
                step="0.01"
                min="0"
                class="ds-field"
                placeholder="Máx"
                @change="applyFilters"
              />
            </div>
          </div>

          <label class="ds-field-group">
            <span class="ds-field-label">
              <ArrowsUpDownIcon class="mr-1 inline h-4 w-4" />
              Ordenar por
            </span>
            <BaseSelect v-model="filters.sort_by" class="ds-field" @change="applyFilters">
              <option value="due_date">Data de vencimento</option>
              <option value="created_at">Data de criação</option>
              <option value="name">Nome da tarefa</option>
              <option value="cost">Custo</option>
            </BaseSelect>
          </label>
        </div>
      </div>
    </section>

    <section v-if="canSelect && selectedTasks.length > 0" class="ds-command-toolbar px-5 py-4">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-start gap-3">
          <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[rgb(var(--primary-50-rgb)/0.9)] text-[rgb(var(--primary-800-rgb)/1)] ring-1 ring-[rgb(var(--primary-200-rgb)/0.8)] dark:bg-[rgb(var(--primary-400-rgb)/0.12)] dark:text-[rgb(var(--accent-100-rgb)/1)] dark:ring-[rgb(var(--primary-300-rgb)/0.18)]">
            <CheckCircleIcon class="h-5 w-5" />
          </span>
          <div>
            <p class="font-bold text-[var(--ds-text)]">{{ selectedTasks.length }} tarefas seleccionadas</p>
            <p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">Aplique alterações em lote apenas a tarefas verificadas.</p>
          </div>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
          <BaseSelect v-model="bulkAction" class="ds-field min-w-60">
            <option value="">Seleccionar acção</option>
            <option v-if="can.edit && !archived" value="mark_executed">Marcar como executadas</option>
            <option v-if="can.edit && !archived" value="reschedule">Reagendar</option>
            <option v-if="can.delete && !archived" value="delete">Arquivar</option>
            <option v-if="can.restore && archived" value="restore">Restaurar</option>
          </BaseSelect>
          <button type="button" class="ds-button ds-button-primary" :disabled="!bulkAction || completionRequest.processing" :aria-busy="completionRequest.processing" @click="executeBulkAction">
            <PlayIcon class="h-4 w-4" />
            Aplicar
          </button>
          <button type="button" class="ds-button ds-button-secondary" :disabled="completionRequest.processing" @click="clearSelection">
            <XMarkIcon class="h-4 w-4" />
            Cancelar
          </button>
        </div>
      </div>
    </section>

    <section class="ds-table-shell">
      <div class="ds-table-summary px-5 py-4">
        <div>
          <h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
            <ClipboardDocumentListIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb)/1)]" />
            {{ archived ? 'Arquivo de tarefas' : 'Lista de tarefas' }}
          </h2>
          <p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">
            {{ taskItems.length }} tarefas nesta página, {{ taskTotal }} no total.
          </p>
        </div>
        <button v-if="can.export && !archived" type="button" class="ds-button ds-button-secondary" @click="exportTasks">
          <ArrowDownTrayIcon class="h-4 w-4" />
          Exportar
        </button>
      </div>

      <div v-if="taskItems.length > 0" class="grid gap-3 p-4 md:hidden">
        <article
          v-for="task in taskItems"
          :key="`mobile-${task.id}`"
          :class="[
            'ds-card p-4',
            selectedTaskIds.includes(task.id) ? 'ring-2 ring-[rgb(var(--primary-500-rgb)/0.45)]' : '',
          ]"
        >
          <div class="flex items-start justify-between gap-3">
            <label class="flex min-w-0 items-start gap-3">
              <CheckboxInput
                :disabled="!canSelect || completionRequest.processing"
                type="checkbox"
                :checked="selectedTaskIds.includes(task.id)"
                :aria-label="`Seleccionar ${task.maintenance_task_no || task.name}`"
                class="ds-checkbox mt-1 shrink-0"
                @change="toggleTaskSelection(task.id)"
              />
              <span class="min-w-0">
                <span class="block truncate text-sm font-bold text-[var(--ds-text)]">{{ task.name }}</span>
                <span class="mt-1 block text-xs font-semibold text-[var(--ds-text-muted)]">{{ task.category?.name || 'Sem categoria' }}</span>
              </span>
            </label>
            <span :class="getStatusClasses(task)">{{ getStatusText(task) }}</span>
          </div>

          <dl class="mt-4 grid gap-2 text-sm">
            <div class="rounded-lg bg-[var(--ds-panel-subtle)] px-3 py-2">
              <dt class="text-xs font-bold uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Equipamento</dt>
              <dd class="mt-1 font-semibold text-[var(--ds-text)]">{{ task.equipment?.name || 'Equipamento não encontrado' }}</dd>
            </div>
            <div class="grid grid-cols-2 gap-2">
              <div class="rounded-lg bg-[var(--ds-panel-subtle)] px-3 py-2">
                <dt class="text-xs font-bold uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Vencimento</dt>
                <dd :class="['mt-1 font-bold', getDueDateColor(task)]">{{ formatDate(task.due_date) }}</dd>
              </div>
              <div class="rounded-lg bg-[var(--ds-panel-subtle)] px-3 py-2">
                <dt class="text-xs font-bold uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Custo</dt>
                <dd class="mt-1 font-bold text-[var(--ds-text)]">{{ task.cost > 0 ? formatCurrency(task.cost) : 'Sem custo' }}</dd>
              </div>
            </div>
            <div v-if="task.description" class="rounded-lg bg-[var(--ds-panel-subtle)] px-3 py-2">
              <dt class="text-xs font-bold uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Descrição</dt>
              <dd class="mt-1 text-[var(--ds-text-muted)]">{{ task.description }}</dd>
            </div>
          </dl>

          <div class="mt-4 flex flex-wrap gap-2">
            <Link v-if="!task.deleted_at" :href="route('vap-maintenance.tasks.show', task.id)" class="ds-table-action">
              <EyeIcon class="mr-1 h-4 w-4" />
              Abrir
            </Link>
            <button v-if="can.edit && !task.deleted_at && !task.is_executed" type="button" class="ds-table-action" :disabled="completionRequest.processing" :aria-busy="completionRequest.processing" @click="markAsExecuted(task)">
              <CheckCircleIcon class="mr-1 h-4 w-4" />
              Concluir
            </button>
            <Link v-if="can.edit && !task.deleted_at" :href="route('vap-maintenance.tasks.edit', task.id)" class="ds-table-action">
              <PencilIcon class="mr-1 h-4 w-4" />
              Actualizar
            </Link>
          </div>
        </article>
      </div>

      <div v-if="taskItems.length > 0" class="hidden overflow-x-auto md:block">
        <DataTable class="min-w-full align-middle text-sm">
          <thead class="ds-table-head">
            <tr>
              <th class="w-12 px-5 py-3">
                <CheckboxInput
                  :disabled="!canSelect || completionRequest.processing"
                  type="checkbox"
                  :checked="allTasksSelected"
                  aria-label="Seleccionar todas as tarefas desta página"
                  class="ds-checkbox"
                  @change="toggleAllTasks"
                />
              </th>
              <th class="ds-table-heading px-5 py-3 text-left">Tarefa</th>
              <th class="ds-table-heading px-5 py-3 text-left">Equipamento</th>
              <th class="ds-table-heading px-5 py-3 text-left">Datas</th>
              <th class="ds-table-heading px-5 py-3 text-left">Custo</th>
              <th class="ds-table-heading px-5 py-3 text-left">Estado</th>
              <th class="ds-table-heading px-5 py-3 text-right">Acções</th>
            </tr>
          </thead>
          <tbody class="ds-table-body divide-y divide-[var(--ds-border)]">
            <tr
              v-for="task in taskItems"
              :key="task.id"
              :class="[
                'ds-table-row',
                selectedTaskIds.includes(task.id) ? 'bg-[rgb(var(--primary-50-rgb)/0.85)] dark:bg-[rgb(var(--primary-400-rgb)/0.1)]' : '',
              ]"
            >
              <td class="px-5 py-4">
                <CheckboxInput
                  :disabled="!canSelect || completionRequest.processing"
                  type="checkbox"
                  :checked="selectedTaskIds.includes(task.id)"
                  :aria-label="`Seleccionar ${task.maintenance_task_no || task.name}`"
                  class="ds-checkbox"
                  @change="toggleTaskSelection(task.id)"
                />
              </td>
              <td class="px-5 py-4">
                <div class="flex items-start gap-3">
                  <span :class="['inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ring-1', getTaskColor(task).bg]">
                    <WrenchScrewdriverIcon :class="['h-5 w-5', getTaskColor(task).text]" />
                  </span>
                  <div class="min-w-0">
                    <p class="font-bold text-[var(--ds-text)]">{{ task.name }}</p>
                    <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
                      {{ task.maintenance_task_no || 'Sem número' }}
                    </p>
                    <p class="mt-1 text-xs font-medium text-[var(--ds-text-soft)]">
                      {{ task.category?.name || 'Sem categoria' }}
                    </p>
                    <p v-if="task.description" class="mt-1 max-w-xs truncate text-xs font-medium text-[var(--ds-text-muted)]">
                      {{ task.description }}
                    </p>
                  </div>
                </div>
              </td>
              <td class="ds-table-cell px-5 py-4">
                <p class="font-bold text-[var(--ds-text)]">
                  {{ task.equipment?.name || 'Equipamento não encontrado' }}
                </p>
                <p v-if="task.equipment" class="mt-1 text-xs text-[var(--ds-text-soft)]">
                  {{ task.equipment.internal_code || 'Sem código' }}
                  <span v-if="task.equipment.model" class="ml-2">{{ task.equipment.model }}</span>
                </p>
                <p v-if="task.equipment?.location" class="mt-1 flex items-center gap-1 text-xs text-[var(--ds-text-soft)]">
                  <MapPinIcon class="h-3 w-3" />
                  {{ task.equipment.location }}
                </p>
              </td>
              <td class="px-5 py-4">
                <dl class="grid gap-1 text-xs">
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
                  <div v-if="task.periodicity" class="mt-2 flex items-center gap-1 font-semibold text-[var(--ds-text-soft)]">
                    <ArrowPathRoundedSquareIcon class="h-3 w-3" />
                    A cada {{ task.periodicity }} {{ getPeriodicityUnitText(task.periodicity_unit) }}
                  </div>
                </dl>
              </td>
              <td class="px-5 py-4">
                <p v-if="task.cost > 0" class="font-bold text-emerald-700 dark:text-emerald-200">
                  {{ formatCurrency(task.cost) }}
                </p>
                <p v-else class="font-semibold text-[var(--ds-text-soft)]">Sem custo</p>
                <p v-if="task.supplier" class="mt-1 text-xs font-medium text-[var(--ds-text-muted)]">
                  {{ task.supplier.name }}
                </p>
                <p v-if="task.calibration_certificate_no" class="mt-1 flex items-center gap-1 text-xs font-semibold text-[rgb(var(--primary-700-rgb)/1)]">
                  <DocumentTextIcon class="h-3 w-3" />
                  Cert: {{ task.calibration_certificate_no }}
                </p>
              </td>
              <td class="px-5 py-4">
                <div class="flex flex-wrap gap-2">
                  <span :class="getStatusClasses(task)">{{ getStatusText(task) }}</span>
                  <span v-if="task.is_planned && !task.is_executed" class="inline-flex items-center rounded-full bg-sky-50 px-2 py-1 text-xs font-bold text-sky-700 ring-1 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-200 dark:ring-sky-400/20">
                    <CalendarIcon class="mr-1 h-3 w-3" />
                    Planeada
                  </span>
                  <span v-if="task.executed_by_supplier" class="inline-flex items-center rounded-full bg-violet-50 px-2 py-1 text-xs font-bold text-violet-700 ring-1 ring-violet-200 dark:bg-violet-500/10 dark:text-violet-200 dark:ring-violet-400/20">
                    <TruckIcon class="mr-1 h-3 w-3" />
                    Fornecedor
                  </span>
                  <span v-if="task.result" class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20">
                    <CheckCircleIcon class="mr-1 h-3 w-3" />
                    Resultado
                  </span>
                </div>
              </td>
              <td class="px-5 py-4 text-right">
                <div class="inline-flex flex-wrap items-center justify-end gap-1">
                  <Link v-if="!task.deleted_at" :href="route('vap-maintenance.tasks.show', task.id)" class="ds-table-action">
                    <EyeIcon class="mr-1 h-4 w-4" />
                    Ver
                  </Link>
                  <button v-if="can.edit && !task.deleted_at && !task.is_executed" type="button" class="ds-table-action" :disabled="completionRequest.processing" :aria-busy="completionRequest.processing" @click="markAsExecuted(task)">
                    <CheckCircleIcon class="mr-1 h-4 w-4" />
                    Concluir
                  </button>
                  <Link v-if="can.edit && !task.deleted_at" :href="route('vap-maintenance.tasks.edit', task.id)" class="ds-table-action">
                    <PencilIcon class="mr-1 h-4 w-4" />
                    Editar
                  </Link>
                </div>
              </td>
            </tr>
          </tbody>
        </DataTable>
      </div>

      <div v-if="taskItems.length === 0" class="p-6">
        <div class="ds-empty-state px-6 py-10 text-center">
          <ClipboardDocumentListIcon class="mx-auto h-10 w-10 text-[var(--ds-text-soft)]" />
          <h3 class="mt-4 text-sm font-bold text-[var(--ds-text)]">Nenhuma tarefa encontrada</h3>
          <p class="mt-2 text-sm font-medium text-[var(--ds-text-muted)]">
            Não foram encontradas tarefas para os filtros aplicados.
          </p>
          <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
            <button type="button" class="ds-button ds-button-secondary" @click="resetFilters">
              <XMarkIcon class="h-4 w-4" />
              Limpar filtros
            </button>
            <Link :href="route('vap-maintenance.tasks.create')" class="ds-button ds-button-primary">
              <PlusIcon class="h-4 w-4" />
              Registar primeira tarefa
            </Link>
          </div>
        </div>
      </div>

      <div v-if="taskItems.length > 0" class="border-t border-[var(--ds-border)] px-5 py-4">
        <Pagination
          :links="tasks.links"
          :from="tasks.from"
          :to="tasks.to"
          :total="tasks.total"
          :current_page="tasks.current_page"
          :last_page="tasks.last_page"
        />
      </div>
    </section>

    <Modal :show="showExportModal" :closeable="!downloading" @close="showExportModal = false">
      <div class="p-5 sm:p-6">
        <div>
          <p class="ds-kicker">Relatório operacional</p>
          <h2 class="ds-heading mt-2 text-lg">Exportar tarefas</h2>
          <p class="ds-copy mt-1 text-sm">Prepare uma extração para auditoria, planeamento ou análise de custos.</p>
        </div>

        <div class="mt-6 space-y-6">
          <div class="ds-field-group">
            <span class="ds-field-label">Formato</span>
            <div class="grid grid-cols-3 gap-3">
              <button
                type="button"
                :class="[
                  'ds-card p-4 text-center transition',
                  exportFormat === 'pdf'
                    ? 'border-[rgb(var(--primary-500-rgb)/1)] bg-[rgb(var(--primary-50-rgb)/0.75)] text-[rgb(var(--primary-900-rgb)/1)]'
                    : 'text-[var(--ds-text-muted)] hover:border-[var(--ds-border-strong)]',
                ]"
                @click="exportFormat = 'pdf'"
              >
                <DocumentTextIcon class="mx-auto h-7 w-7" />
                <span class="mt-2 block text-sm font-bold">PDF</span>
              </button>
              <button
                type="button"
                :class="[
                  'ds-card p-4 text-center transition',
                  exportFormat === 'excel'
                    ? 'border-emerald-500 bg-emerald-50 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-200'
                    : 'text-[var(--ds-text-muted)] hover:border-[var(--ds-border-strong)]',
                ]"
                @click="exportFormat = 'excel'"
              >
                <PresentationChartBarIcon class="mx-auto h-7 w-7" />
                <span class="mt-2 block text-sm font-bold">Excel</span>
              </button>
              <button
                type="button"
                :class="[
                  'ds-card p-4 text-center transition',
                  exportFormat === 'csv'
                    ? 'border-amber-500 bg-amber-50 text-amber-800 dark:bg-amber-500/10 dark:text-amber-200'
                    : 'text-[var(--ds-text-muted)] hover:border-[var(--ds-border-strong)]',
                ]"
                @click="exportFormat = 'csv'"
              >
                <TableCellsIcon class="mx-auto h-7 w-7" />
                <span class="mt-2 block text-sm font-bold">CSV</span>
              </button>
            </div>
          </div>

          <div class="ds-field-group">
            <span class="ds-field-label">Intervalo de exportação</span>
            <div class="grid grid-cols-2 gap-3">
              <button
                v-for="range in exportRanges"
                :key="range.value"
                type="button"
                :class="[
                  'ds-card px-3 py-3 text-center text-sm font-bold transition',
                  exportRange === range.value
                    ? 'border-[rgb(var(--primary-500-rgb)/1)] bg-[rgb(var(--primary-50-rgb)/0.75)] text-[rgb(var(--primary-900-rgb)/1)]'
                    : 'text-[var(--ds-text-muted)] hover:border-[var(--ds-border-strong)]',
                ]"
                @click="exportRange = range.value"
              >
                {{ range.label }}
              </button>
            </div>
          </div>

          <p class="ds-copy text-sm">Inclui identificação, categoria, equipamento, datas, estado, custo e fornecedor. Máximo: 5000 tarefas. Os valores reflectem os registos seleccionados, não apenas esta página.</p>
          <p v-if="downloadError" class="ds-field-error" role="alert">{{ downloadError }}</p>
          <p v-if="downloading" class="ds-copy text-sm" role="status">A preparar o ficheiro…</p>

          <div class="flex items-center justify-end gap-2 border-t border-[var(--ds-border)] pt-5">
            <button type="button" class="ds-button ds-button-secondary" :disabled="downloading" @click="showExportModal = false">
              Cancelar
            </button>
            <button type="button" class="ds-button ds-button-primary" :disabled="downloading" :aria-busy="downloading" @click="proceedExport">
              <ArrowDownTrayIcon class="h-4 w-4" />
              Exportar
            </button>
          </div>
        </div>
      </div>
    </Modal>

    <Modal :show="showRescheduleModal" :closeable="!completionRequest.processing" @close="showRescheduleModal = false">
      <div class="p-5 sm:p-6">
        <div>
          <p class="ds-kicker">Ajuste de agenda</p>
          <h2 class="ds-heading mt-2 text-lg">Reagendar {{ selectedTasks.length }} tarefas</h2>
          <p class="ds-copy mt-1 text-sm">Registe uma nova data de vencimento para o conjunto seleccionado.</p>
        </div>

        <div class="mt-6 space-y-6">
          <div v-if="completionRequest.hasErrors || completionError" class="ds-field-error" role="alert">
            <p v-if="completionError">{{ completionError }}</p>
            <p v-for="(error, key) in completionRequest.errors" :key="key">{{ error }}</p>
          </div>
          <label class="ds-field-group">
            <span class="ds-field-label">Nova data de vencimento</span>
            <DateTimePicker
              v-model="rescheduleDate"
              type="date"
              class="ds-field" />
          </label>

          <div class="flex items-center justify-end gap-2 border-t border-[var(--ds-border)] pt-5">
            <button type="button" class="ds-button ds-button-secondary" :disabled="completionRequest.processing" @click="showRescheduleModal = false">
              Cancelar
            </button>
            <button
              type="button"
              class="ds-button ds-button-primary"
              :disabled="!rescheduleDate || completionRequest.processing"
              :aria-busy="completionRequest.processing"
              @click="executeReschedule"
            >
              <CalendarIcon class="h-4 w-4" />
              {{ completionRequest.processing ? 'A guardar…' : 'Reagendar' }}
            </button>
          </div>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useFileDownload } from '@/Composables/useFileDownload'
import { commercialDocumentThemeClasses } from '@/Composables/useCommercialDocumentTheme'
import { Head, Link, router, useHttp } from '@inertiajs/vue3'
import {
  Wrench as WrenchScrewdriverIcon,
  Plus as PlusIcon,
  Tag as TagIcon,
  Cog as CogIcon,
  CircleCheck as CheckCircleIcon,
  Calendar as CalendarIcon,
  Funnel as FunnelIcon,
  ChevronDown as ChevronDownIcon,
  X as XMarkIcon,
  Truck as TruckIcon,
  Euro as CurrencyEuroIcon,
  ArrowUpDown as ArrowsUpDownIcon,
  ClipboardList as ClipboardDocumentListIcon,
  Download as ArrowDownTrayIcon,
  Eye as EyeIcon,
  Pencil as PencilIcon,
  MapPin as MapPinIcon,
  Repeat as ArrowPathRoundedSquareIcon,
  FileText as DocumentTextIcon,
  TriangleAlert as ExclamationTriangleIcon,
  Presentation as PresentationChartBarIcon,
  Table as TableCellsIcon,
  Play as PlayIcon,
} from '@lucide/vue'
import Modal from '@/Components/Modal.vue'
import Pagination from '@/Components/Pagination.vue'
import { debounce } from 'lodash'

const props = defineProps({
  can: { type: Object, default: () => ({}) },
  tasks: Object,
  categories: Array,
  equipment: Array,
  suppliers: Array,
  filters: Object,
  stats: Object,
  today: String,
})

// State
const showAdvancedFilters = ref(false)
const selectedTaskIds = ref([])
const completionRequest = useHttp({ task_ids: [], action: 'mark_executed', new_date: null })
const completionError = ref('')
const bulkAction = ref('')
const showExportModal = ref(false)
const showRescheduleModal = ref(false)
const exportFormat = ref('pdf')
const exportRange = ref('filtered')
const { download, processing: downloading, error: downloadError } = useFileDownload()
const rescheduleDate = ref(props.today)
const archived = computed(() => Boolean(Number(props.filters?.archived)))
const canSelect = computed(() => archived.value ? props.can.restore : props.can.edit || props.can.delete)

// Computed
const taskItems = computed(() => props.tasks?.data ?? [])

const taskTotal = computed(() => props.tasks?.total ?? taskItems.value.length)

const hasActiveFilters = computed(() => {
  return Object.values(props.filters ?? {}).some(value => value !== null && value !== undefined && value !== '')
})

const allTasksSelected = computed(() => {
  return taskItems.value.length > 0 &&
         taskItems.value.every(task => selectedTaskIds.value.includes(task.id))
})

const selectedTasks = computed(() => {
  return taskItems.value.filter(task => selectedTaskIds.value.includes(task.id))
})

const exportRanges = [
  { value: 'filtered', label: 'Tarefas Filtradas' },
  { value: 'all', label: 'Todas as Tarefas' },
  { value: 'overdue', label: 'Tarefas Atrasadas' },
  { value: 'upcoming', label: 'Tarefas Futuras' },
  { value: 'executed', label: 'Tarefas Executadas' },
]

// Methods
const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(`${dateString}T12:00:00`).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('pt-PT', {
    style: 'currency',
    currency: 'AOA'
  }).format(amount)
}

const statsCards = computed(() => [
  {
    label: 'Tarefas atrasadas',
    value: props.stats?.overdue ?? 0,
    caption: 'Fora do prazo planeado',
    icon: ExclamationTriangleIcon,
    tone: 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20',
  },
  {
    label: 'Executadas',
    value: props.stats?.executed ?? 0,
    caption: 'Com resultado registado',
    icon: CheckCircleIcon,
    tone: 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20',
  },
  {
    label: 'Custo total',
    value: formatCurrency(props.stats?.total_cost ?? 0),
    caption: 'Custo dos resultados filtrados',
    icon: CurrencyEuroIcon,
    tone: 'bg-cyan-50 text-cyan-700 ring-cyan-200 dark:bg-cyan-500/10 dark:text-cyan-200 dark:ring-cyan-400/20',
  },
  {
    label: 'Vencem este mês',
    value: props.stats?.due_this_month ?? 0,
    caption: 'Tarefas pendentes nos resultados',
    icon: PresentationChartBarIcon,
    tone: 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20',
  },
])

const getTaskColor = (task) => {
  if (task.is_executed) {
    return {
      bg: 'bg-emerald-50 ring-emerald-200 dark:bg-emerald-500/10 dark:ring-emerald-400/20',
      text: 'text-emerald-700 dark:text-emerald-200',
    }
  }
  
  const daysDiff = task.days_until_due ?? Infinity
  
  if (daysDiff < 0) {
    return {
      bg: 'bg-rose-50 ring-rose-200 dark:bg-rose-500/10 dark:ring-rose-400/20',
      text: 'text-rose-700 dark:text-rose-200',
    }
  } else if (daysDiff <= 7) {
    return {
      bg: 'bg-orange-50 ring-orange-200 dark:bg-orange-500/10 dark:ring-orange-400/20',
      text: 'text-orange-700 dark:text-orange-200',
    }
  } else if (daysDiff <= 30) {
    return {
      bg: 'bg-amber-50 ring-amber-200 dark:bg-amber-500/10 dark:ring-amber-400/20',
      text: 'text-amber-700 dark:text-amber-200',
    }
  } else {
    return {
      bg: 'bg-cyan-50 ring-cyan-200 dark:bg-cyan-500/10 dark:ring-cyan-400/20',
      text: 'text-cyan-700 dark:text-cyan-200',
    }
  }
}

const getDueDateColor = (task) => {
  if (task.is_executed) return 'text-emerald-700 dark:text-emerald-200'
  
  const daysDiff = task.days_until_due ?? Infinity
  
  if (daysDiff < 0) return 'text-rose-700 dark:text-rose-200'
  if (daysDiff <= 7) return 'text-orange-700 dark:text-orange-200'
  if (daysDiff <= 30) return 'text-amber-700 dark:text-amber-200'
  return 'text-cyan-700 dark:text-cyan-200'
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
  
  const daysDiff = task.days_until_due ?? Infinity
  
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
  if (task.deleted_at) return 'Arquivada'
  if (task.is_executed) return 'Concluída'
  
  const daysDiff = task.days_until_due ?? Infinity
  
  if (daysDiff < 0) return 'Atrasada'
  if (daysDiff <= 7) return 'Vencendo em Breve'
  if (daysDiff <= 30) return 'Próxima'
  return 'Agendada'
}

const applyFilters = debounce(() => {
  router.get(route('vap-maintenance.tasks'), props.filters, {
    preserveState: true,
    replace: true,
  })
}, 300)

const resetFilters = () => {
  router.get(route('vap-maintenance.tasks'), {}, {
    preserveState: true,
    replace: true,
  })
}

const toggleTaskSelection = (taskId) => {
  const index = selectedTaskIds.value.indexOf(taskId)
  if (index > -1) {
    selectedTaskIds.value.splice(index, 1)
  } else {
    selectedTaskIds.value.push(taskId)
  }
}

const toggleAllTasks = () => {
  if (allTasksSelected.value) {
    selectedTaskIds.value = []
  } else {
    selectedTaskIds.value = taskItems.value.map(task => task.id)
  }
}

const clearSelection = () => {
  selectedTaskIds.value = []
  bulkAction.value = ''
}

watch(() => props.tasks, clearSelection)

const executeBulkAction = async () => {
  if (completionRequest.processing || !bulkAction.value || selectedTaskIds.value.length === 0) return

  switch (bulkAction.value) {
    case 'mark_executed':
      if (confirm(`Concluir ${selectedTaskIds.value.length} tarefas? Todas devem ter um resultado registado. As já concluídas permanecem inalteradas.`)) {
        await executeMarkAsExecuted()
      }
      break
    case 'reschedule':
      showRescheduleModal.value = true
      break
    case 'delete':
      if (confirm(`Arquivar ${selectedTaskIds.value.length} tarefas? Os registos serão preservados e poderão ser restaurados por utilizadores autorizados.`)) {
        await executeDelete()
      }
      break
    case 'restore':
      await executeLifecycle('restore')
      break
  }
}

const executeMarkAsExecuted = async () => {
  if (completionRequest.processing || selectedTaskIds.value.length === 0) return
  completionError.value = ''
  completionRequest.action = 'mark_executed'
  completionRequest.new_date = null
  completionRequest.task_ids = [...selectedTaskIds.value]
  try {
    await completionRequest.post(route('vap-maintenance.tasks.bulk-update'), {
      onSuccess: () => {
        clearSelection()
        router.reload()
      },
    })
  } catch {
    if (!completionRequest.hasErrors) {
      completionError.value = 'Não foi possível concluir as tarefas. A selecção foi mantida; tente novamente.'
    }
  }
}

const executeReschedule = async () => {
  if (!rescheduleDate.value || completionRequest.processing) return
  await executeLifecycle('reschedule', rescheduleDate.value)
}

const executeDelete = async () => {
  await executeLifecycle('delete')
}

const executeLifecycle = async (action, newDate = null) => {
  if (completionRequest.processing || selectedTaskIds.value.length === 0) return
  completionError.value = ''
  completionRequest.action = action
  completionRequest.new_date = newDate
  completionRequest.task_ids = [...selectedTaskIds.value]
  try {
    await completionRequest.post(route('vap-maintenance.tasks.bulk-update'), {
      onSuccess: () => {
        clearSelection()
        showRescheduleModal.value = false
        router.reload()
      },
    })
  } catch {
    if (!completionRequest.hasErrors) {
      completionError.value = 'Não foi possível guardar a alteração. A selecção foi mantida; tente novamente.'
    }
  }
}

const markAsExecuted = async (task) => {
  if (!props.can.edit || completionRequest.processing) return
  if (!confirm('Concluir esta tarefa com o resultado registado?')) return
  completionError.value = ''
  completionRequest.action = 'mark_executed'
  completionRequest.new_date = null
  completionRequest.task_ids = [task.id]
  try {
    await completionRequest.post(route('vap-maintenance.tasks.bulk-update'), {
      onSuccess: () => router.reload(),
    })
  } catch {
    completionError.value = 'Não foi possível concluir a tarefa. Tente novamente.'
  }
}

const exportTasks = () => {
  showExportModal.value = true
}

const proceedExport = async () => {
  if (downloading.value) return
  const params = {
    ...Object.fromEntries(Object.entries(props.filters ?? {}).filter(([key]) => key !== 'archived')),
    format: exportFormat.value,
    type: 'tasks',
    range: exportRange.value,
  }
  if (await download(route('vap-maintenance.export', params))) {
    showExportModal.value = false
  }
}

</script>
