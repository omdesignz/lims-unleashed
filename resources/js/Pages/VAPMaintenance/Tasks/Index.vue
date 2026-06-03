<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <WrenchScrewdriverIcon class="h-7 w-7 text-blue-900" />
            Tarefas de Manutenção
          </h1>
          <p class="mt-2 text-gray-600">
            Gerencie todas as tarefas de manutenção e calibração
            <span v-if="stats?.overdue > 0" class="font-semibold text-red-600 ml-2">
              {{ stats?.overdue }} tarefas atrasadas
            </span>
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ tasks?.total }} tarefas
          </span>
          <Link
            :href="route('vap-maintenance.tasks.create')"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
          >
            <PlusIcon class="h-5 w-5" />
            Nova Tarefa
          </Link>
        </div>
      </div>
    </div>

    <!-- FILTERS -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- CATEGORY FILTER -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <TagIcon class="h-4 w-4 inline mr-1" />
            Categoria
          </label>
          <select
            v-model="filters.category_id"
            @change="applyFilters"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="">Todas as Categorias</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </div>

        <!-- EQUIPMENT FILTER -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <CogIcon class="h-4 w-4 inline mr-1" />
            Equipamento
          </label>
          <select
            v-model="filters.equipment_id"
            @change="applyFilters"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="">Todos os Equipamentos</option>
            <option v-for="equipment in equipment" :key="equipment.id" :value="equipment.id">
              {{ equipment.name }} ({{ equipment.internal_code || 'N/A' }})
            </option>
          </select>
        </div>

        <!-- STATUS FILTER -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <CheckCircleIcon class="h-4 w-4 inline mr-1" />
            Estado
          </label>
          <select
            v-model="filters.status"
            @change="applyFilters"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="">Todos os Estados</option>
            <option value="overdue">Atrasadas</option>
            <option value="executed">Executadas</option>
            <option value="planned">Planeadas</option>
          </select>
        </div>

        <!-- DATE RANGE -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <CalendarIcon class="h-4 w-4 inline mr-1" />
            Período
          </label>
          <div class="flex gap-2">
            <input
              v-model="filters.date_from"
              type="date"
              @change="applyFilters"
              class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
              placeholder="De"
            />
            <input
              v-model="filters.date_to"
              type="date"
              @change="applyFilters"
              class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
              placeholder="Até"
            />
          </div>
        </div>
      </div>

      <!-- ADVANCED FILTERS -->
      <div class="mt-6 pt-6 border-t border-gray-200">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <button
            @click="showAdvancedFilters = !showAdvancedFilters"
            class="inline-flex items-center gap-2 text-sm font-medium text-blue-900 hover:text-blue-800"
          >
            <FunnelIcon class="h-4 w-4" />
            Filtros Avançados
            <ChevronDownIcon :class="['h-4 w-4 transition-transform', showAdvancedFilters ? 'rotate-180' : '']" />
          </button>
          <button
            @click="resetFilters"
            class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900"
          >
            <XMarkIcon class="h-4 w-4" />
            Limpar Filtros
          </button>
        </div>

        <div v-if="showAdvancedFilters" class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- SUPPLIER FILTER -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              <TruckIcon class="h-4 w-4 inline mr-1" />
              Fornecedor
            </label>
            <select
              v-model="filters.supplier_id"
              @change="applyFilters"
              class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
            >
              <option value="">Todos os Fornecedores</option>
              <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                {{ supplier.name }}
              </option>
            </select>
          </div>

          <!-- COST RANGE -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              <CurrencyEuroIcon class="h-4 w-4 inline mr-1" />
              Custo
            </label>
            <div class="flex gap-2">
              <input
                v-model="filters.cost_min"
                type="number"
                step="0.01"
                min="0"
                @change="applyFilters"
                class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                placeholder="Mín"
              />
              <input
                v-model="filters.cost_max"
                type="number"
                step="0.01"
                min="0"
                @change="applyFilters"
                class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                placeholder="Máx"
              />
            </div>
          </div>

          <!-- SORT BY -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              <ArrowsUpDownIcon class="h-4 w-4 inline mr-1" />
              Ordenar Por
            </label>
            <select
              v-model="filters.sort_by"
              @change="applyFilters"
              class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
            >
              <option value="due_date">Data de Vencimento</option>
              <option value="created_at">Data de Criação</option>
              <option value="name">Nome da Tarefa</option>
              <option value="cost">Custo</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- BULK ACTIONS -->
    <div v-if="selectedTasks.length > 0" class="bg-gradient-to-r from-blue-900 to-blue-800 rounded-xl shadow-sm p-6">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
          <CheckCircleIcon class="h-5 w-5 text-white" />
          <div class="text-white">
            <div class="font-semibold">{{ selectedTasks.length }} tarefas selecionadas</div>
            <div class="text-sm opacity-90">Selecione uma ação em massa</div>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <select
            v-model="bulkAction"
            class="rounded-lg border border-blue-700 bg-blue-800 text-white px-4 py-2.5 text-sm focus:border-white focus:ring-white"
          >
            <option value="">Seleccionar acção</option>
            <option value="mark_executed">Marcar como Executadas</option>
            <option value="reschedule">Reagendar</option>
            <option value="delete">Eliminar</option>
          </select>
          <button
            @click="executeBulkAction"
            :disabled="!bulkAction"
            class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-blue-900 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <PlayIcon class="h-4 w-4" />
            Aplicar
          </button>
          <button
            @click="clearSelection"
            class="inline-flex items-center gap-2 rounded-lg border border-blue-300 bg-transparent px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700"
          >
            <XMarkIcon class="h-4 w-4" />
            Cancelar
          </button>
        </div>
      </div>
    </div>

    <!-- TASKS TABLE -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="border-b border-gray-200 px-6 py-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <ClipboardDocumentListIcon class="h-5 w-5 text-blue-900" />
            Lista de Tarefas
            <span class="text-sm font-normal text-gray-500 ml-2">
              ({{ tasks.total }} itens)
            </span>
          </h2>
          <div class="flex items-center gap-2">
            <button
              @click="exportTasks"
              class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              <ArrowDownTrayIcon class="h-4 w-4" />
              Exportar
            </button>
          </div>
        </div>
      </div>

      <div class="grid gap-4 p-4 md:hidden" v-if="tasks.data.length > 0">
        <article
          v-for="task in tasks.data"
          :key="`mobile-${task.id}`"
          class="rounded-xl border border-gray-200 p-4 shadow-sm"
        >
          <div class="flex items-start justify-between gap-3">
            <label class="flex items-start gap-3">
              <input
                type="checkbox"
                :checked="selectedTaskIds.includes(task.id)"
                @change="toggleTaskSelection(task.id)"
                class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
              />
              <div>
                <p class="text-sm font-semibold text-gray-900">{{ task.name }}</p>
                <p class="text-xs text-gray-500">{{ task.category?.name || 'Sem categoria' }}</p>
              </div>
            </label>
            <span :class="getStatusClasses(task)">{{ getStatusText(task) }}</span>
          </div>

          <dl class="mt-4 grid grid-cols-1 gap-2 text-sm">
            <div class="rounded-lg bg-gray-50 px-3 py-2">
              <dt class="text-xs uppercase tracking-wide text-gray-500">Equipamento</dt>
              <dd class="mt-1 text-gray-900">{{ task.equipment?.name || 'Equipamento não encontrado' }}</dd>
            </div>
            <div class="grid grid-cols-2 gap-2">
              <div class="rounded-lg bg-gray-50 px-3 py-2">
                <dt class="text-xs uppercase tracking-wide text-gray-500">Vencimento</dt>
                <dd class="mt-1 text-gray-900">{{ formatDate(task.due_date) }}</dd>
              </div>
              <div class="rounded-lg bg-gray-50 px-3 py-2">
                <dt class="text-xs uppercase tracking-wide text-gray-500">Custo</dt>
                <dd class="mt-1 text-gray-900">{{ task.cost > 0 ? formatCurrency(task.cost) : 'Sem custo' }}</dd>
              </div>
            </div>
            <div v-if="task.description" class="rounded-lg bg-gray-50 px-3 py-2">
              <dt class="text-xs uppercase tracking-wide text-gray-500">Descrição</dt>
              <dd class="mt-1 text-gray-900">{{ task.description }}</dd>
            </div>
          </dl>

          <div class="mt-4 flex flex-wrap gap-2">
            <Link :href="route('vap-maintenance.tasks.show', task.id)" class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-2 text-sm font-medium text-blue-900 hover:bg-blue-100">Abrir</Link>
            <button v-if="!task.is_executed" @click="markAsExecuted(task)" class="inline-flex items-center rounded-lg bg-green-50 px-3 py-2 text-sm font-medium text-green-900 hover:bg-green-100">Concluir</button>
            <Link :href="route('vap-maintenance.tasks.show', task.id)" class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Actualizar</Link>
          </div>
        </article>
      </div>

      <div class="hidden overflow-x-auto md:block">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
            <tr>
              <th class="w-12 px-6 py-3">
                <input
                  type="checkbox"
                  :checked="allTasksSelected"
                  @change="toggleAllTasks"
                  class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                />
              </th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Tarefa
              </th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Equipamento
              </th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Datas
              </th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Custo
              </th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Estado
              </th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Acções
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="task in tasks.data"
              :key="task.id"
              :class="['hover:bg-gray-50 transition-colors duration-150', selectedTaskIds.includes(task.id) ? 'bg-blue-50' : '']"
            >
              <td class="px-6 py-4">
                <input
                  type="checkbox"
                  :checked="selectedTaskIds.includes(task.id)"
                  @change="toggleTaskSelection(task.id)"
                  class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                />
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div :class="[
                    'flex-shrink-0 h-10 w-10 rounded-lg flex items-center justify-center',
                    getTaskColor(task).bg
                  ]">
                    <WrenchScrewdriverIcon :class="['h-6 w-6', getTaskColor(task).text]" />
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-semibold text-gray-900">
                      {{ task.name }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ task.maintenance_task_no || 'Sem número' }}
                    </div>
                    <div class="text-xs text-gray-400">
                      {{ task.category?.name || 'Sem categoria' }}
                    </div>
                    <div v-if="task.description" class="text-xs text-gray-500 truncate max-w-xs">
                      {{ task.description }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="space-y-1">
                  <div class="text-sm font-medium text-gray-900">
                    {{ task.equipment?.name || 'Equipamento não encontrado' }}
                  </div>
                  <div v-if="task.equipment" class="text-xs text-gray-500">
                    {{ task.equipment.internal_code || 'Sem código' }}
                    <span v-if="task.equipment.model" class="ml-2">• {{ task.equipment.model }}</span>
                  </div>
                  <div v-if="task.equipment?.location" class="text-xs text-gray-400">
                    <MapPinIcon class="h-3 w-3 inline mr-1" />
                    {{ task.equipment.location }}
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="space-y-1">
                  <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Vencimento:</span>
                    <span :class="getDueDateColor(task)">
                      {{ formatDate(task.due_date) }}
                    </span>
                  </div>
                  <div v-if="task.previous_date" class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Anterior:</span>
                    <span class="text-gray-900">{{ formatDate(task.previous_date) }}</span>
                  </div>
                  <div v-if="task.next_date" class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Próximo:</span>
                    <span class="text-gray-900">{{ formatDate(task.next_date) }}</span>
                  </div>
                  <div v-if="task.periodicity" class="mt-2 text-xs text-gray-500">
                    <ArrowPathRoundedSquareIcon class="h-3 w-3 inline mr-1" />
                    A cada {{ task.periodicity }} {{ getPeriodicityUnitText(task.periodicity_unit) }}
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-center">
                  <div v-if="task.cost > 0" class="text-sm font-bold text-green-900">
                    {{ formatCurrency(task.cost) }}
                  </div>
                  <div v-else class="text-sm text-gray-400">Sem custo</div>
                  <div v-if="task.supplier" class="text-xs text-gray-500 mt-1">
                    {{ task.supplier.name }}
                  </div>
                  <div v-if="task.calibration_certificate_no" class="text-xs text-blue-600 mt-1">
                    <DocumentTextIcon class="h-3 w-3 inline mr-1" />
                    Cert: {{ task.calibration_certificate_no }}
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span :class="getStatusClasses(task)">
                  {{ getStatusText(task) }}
                </span>
                <div v-if="task.is_planned && !task.is_executed" class="mt-2">
                  <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">
                    <CalendarIcon class="mr-1 h-3 w-3" />
                    Planeada
                  </span>
                </div>
                <div v-if="task.executed_by_supplier" class="mt-2">
                  <span class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-800">
                    <TruckIcon class="mr-1 h-3 w-3" />
                    Fornecedor Externo
                  </span>
                </div>
                <div v-if="task.result" class="mt-2">
                  <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">
                    <CheckCircleIcon class="mr-1 h-3 w-3" />
                    Resultado Registado
                  </span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <Link
                    :href="route('vap-maintenance.tasks.show', task.id)"
                    class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-900 hover:bg-blue-100"
                  >
                    <EyeIcon class="h-4 w-4 mr-1" />
                    Ver
                  </Link>
                  <button
                    v-if="!task.is_executed"
                    @click="markAsExecuted(task)"
                    class="inline-flex items-center rounded-lg bg-green-50 px-3 py-1.5 text-sm font-medium text-green-900 hover:bg-green-100"
                  >
                    <CheckCircleIcon class="h-4 w-4 mr-1" />
                    Concluir
                  </button>
                  <Link
                    :href="route('vap-maintenance.tasks.show', task.id)"
                    class="inline-flex items-center rounded-lg bg-gray-50 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100"
                  >
                    <PencilIcon class="h-4 w-4 mr-1" />
                    Editar
                  </Link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- EMPTY STATE -->
      <div v-if="tasks.data.length === 0" class="p-12 text-center">
        <ClipboardDocumentListIcon class="mx-auto h-12 w-12 text-gray-300" />
        <h3 class="mt-4 text-sm font-semibold text-gray-900">
          Nenhuma tarefa encontrada
        </h3>
        <p class="mt-2 text-sm text-gray-500">
          Não foram encontradas tarefas para os filtros aplicados
        </p>
        <div class="mt-6 flex items-center justify-center gap-3">
          <button
            @click="resetFilters"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            <XMarkIcon class="h-5 w-5" />
            Limpar Filtros
          </button>
          <Link
            :href="route('vap-maintenance.tasks.create')"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
          >
            <PlusIcon class="h-5 w-5" />
            Registar primeira tarefa
          </Link>
        </div>
      </div>

      <!-- PAGINATION -->
      <div v-if="tasks.data.length > 0" class="border-t border-gray-200 px-6 py-4">
        <Pagination :links="tasks.links" :from="tasks.from" :to="tasks.to" :total="tasks.total" :current_page="tasks.current_page" :last_page="tasks.last_page" />
        <!-- <Pagination :links="tasks.links" /> -->
      </div>
    </div>

    <!-- QUICK STATS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm font-medium text-gray-600">Tarefas atrasadas</div>
            <div class="text-2xl font-bold text-red-600 mt-1">{{ stats?.overdue }}</div>
          </div>
          <ExclamationTriangleIcon class="h-8 w-8 text-red-600 opacity-50" />
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm font-medium text-gray-600">Tarefas executadas</div>
            <div class="text-2xl font-bold text-green-600 mt-1">{{ stats.executed }}</div>
          </div>
          <CheckCircleIcon class="h-8 w-8 text-green-600 opacity-50" />
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm font-medium text-gray-600">Custo Total</div>
            <div class="text-2xl font-bold text-blue-900 mt-1">{{ formatCurrency(stats.total_cost || 0) }}</div>
          </div>
          <CurrencyEuroIcon class="h-8 w-8 text-blue-900 opacity-50" />
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm font-medium text-gray-600">Média mensal</div>
            <div class="text-2xl font-bold text-purple-900 mt-1">{{ stats.monthly_average }}</div>
          </div>
          <PresentationChartBarIcon class="h-8 w-8 text-purple-900 opacity-50" />
        </div>
      </div>
    </div>

    <!-- EXPORT MODAL -->
    <Modal :show="showExportModal" @close="showExportModal = false">
      <div class="p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Exportar Tarefas</h2>
        
        <div class="space-y-6">
          <!-- EXPORT FORMAT -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Formato
            </label>
            <div class="grid grid-cols-3 gap-3">
              <button
                @click="exportFormat = 'pdf'"
                :class="[
                  'rounded-lg border p-4 text-center transition-all',
                  exportFormat === 'pdf'
                    ? 'border-blue-900 bg-blue-50 text-blue-900'
                    : 'border-gray-300 hover:border-gray-400'
                ]"
              >
                <DocumentTextIcon class="mx-auto h-8 w-8 mb-2" />
                <div class="font-medium">PDF</div>
              </button>
              <button
                @click="exportFormat = 'excel'"
                :class="[
                  'rounded-lg border p-4 text-center transition-all',
                  exportFormat === 'excel'
                    ? 'border-green-900 bg-green-50 text-green-900'
                    : 'border-gray-300 hover:border-gray-400'
                ]"
              >
                <PresentationChartBarIcon class="mx-auto h-8 w-8 mb-2" />
                <div class="font-medium">Excel</div>
              </button>
              <button
                @click="exportFormat = 'csv'"
                :class="[
                  'rounded-lg border p-4 text-center transition-all',
                  exportFormat === 'csv'
                    ? 'border-orange-900 bg-orange-50 text-orange-900'
                    : 'border-gray-300 hover:border-gray-400'
                ]"
              >
                <TableCellsIcon class="mx-auto h-8 w-8 mb-2" />
                <div class="font-medium">CSV</div>
              </button>
            </div>
          </div>

          <!-- EXPORT RANGE -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Intervalo de Exportação
            </label>
            <div class="grid grid-cols-2 gap-3">
              <button
                v-for="range in exportRanges"
                :key="range.value"
                @click="exportRange = range.value"
                :class="[
                  'rounded-lg border p-3 text-center transition-all',
                  exportRange === range.value
                    ? 'border-blue-900 bg-blue-50 text-blue-900'
                    : 'border-gray-300 hover:border-gray-400'
                ]"
              >
                {{ range.label }}
              </button>
            </div>
          </div>

          <!-- INCLUDE FIELDS -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Campos Incluídos
            </label>
            <div class="space-y-2">
              <label class="flex items-center">
                <input v-model="exportFields" type="checkbox" value="all" class="rounded border-gray-300 text-blue-900 focus:ring-blue-900" />
                <span class="ml-2 text-sm text-gray-700">Todos os campos</span>
              </label>
              <div class="grid grid-cols-2 gap-2 ml-6">
                <label class="flex items-center">
                  <input v-model="exportFields" type="checkbox" value="equipment" class="rounded border-gray-300 text-blue-900 focus:ring-blue-900" />
                  <span class="ml-2 text-sm text-gray-700">Equipamento</span>
                </label>
                <label class="flex items-center">
                  <input v-model="exportFields" type="checkbox" value="dates" class="rounded border-gray-300 text-blue-900 focus:ring-blue-900" />
                  <span class="ml-2 text-sm text-gray-700">Datas</span>
                </label>
                <label class="flex items-center">
                  <input v-model="exportFields" type="checkbox" value="cost" class="rounded border-gray-300 text-blue-900 focus:ring-blue-900" />
                  <span class="ml-2 text-sm text-gray-700">Custo</span>
                </label>
                <label class="flex items-center">
                  <input v-model="exportFields" type="checkbox" value="supplier" class="rounded border-gray-300 text-blue-900 focus:ring-blue-900" />
                  <span class="ml-2 text-sm text-gray-700">Fornecedor</span>
                </label>
              </div>
            </div>
          </div>

          <!-- FORM ACTIONS -->
          <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200">
            <button
              @click="showExportModal = false"
              class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              Cancelar
            </button>
            <button
              @click="proceedExport"
              class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
            >
              <ArrowDownTrayIcon class="h-4 w-4" />
              Exportar
            </button>
          </div>
        </div>
      </div>
    </Modal>

    <!-- RESCHEDULE MODAL -->
    <Modal :show="showRescheduleModal" @close="showRescheduleModal = false">
      <div class="p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">
          Reagendar {{ selectedTasks.length }} Tarefas
        </h2>
        
        <div class="space-y-6">
          <!-- NEW DATE -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Nova Data de Vencimento
            </label>
            <input
              v-model="rescheduleDate"
              type="date"
              :min="new Date().toISOString().split('T')[0]"
              class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
            />
          </div>

          <!-- ADD NOTIFICATION -->
          <div>
            <label class="flex items-center">
              <input v-model="sendRescheduleNotification" type="checkbox" class="rounded border-gray-300 text-blue-900 focus:ring-blue-900" />
              <span class="ml-2 text-sm text-gray-700">Enviar notificação aos responsáveis</span>
            </label>
            <p class="mt-1 text-xs text-gray-500">
              Os responsáveis serão notificados sobre a alteração das datas
            </p>
          </div>

          <!-- FORM ACTIONS -->
          <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200">
            <button
              @click="showRescheduleModal = false"
              class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              Cancelar
            </button>
            <button
              @click="executeReschedule"
              :disabled="!rescheduleDate"
              class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <CalendarIcon class="h-4 w-4" />
              Reagendar
            </button>
          </div>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { Link, router } from '@inertiajs/vue3'
import {
  WrenchScrewdriverIcon,
  PlusIcon,
  TagIcon,
  CogIcon,
  CheckCircleIcon,
  CalendarIcon,
  FunnelIcon,
  ChevronDownIcon,
  XMarkIcon,
  TruckIcon,
  CurrencyEuroIcon,
  ArrowsUpDownIcon,
  ClipboardDocumentListIcon,
  ArrowDownTrayIcon,
  EyeIcon,
  PencilIcon,
  MapPinIcon,
  ArrowPathRoundedSquareIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  PresentationChartBarIcon,
  TableCellsIcon,
  PlayIcon,
} from '@heroicons/vue/24/outline'
import Modal from '@/Components/Modal.vue'
import Pagination from '@/Components/Pagination.vue'
import { debounce } from 'lodash'

const props = defineProps({
  tasks: Object,
  categories: Array,
  equipment: Array,
  suppliers: Array,
  filters: Object,
  stats: Object,
})

// State
const showAdvancedFilters = ref(false)
const selectedTaskIds = ref([])
const bulkAction = ref('')
const showExportModal = ref(false)
const showRescheduleModal = ref(false)
const exportFormat = ref('pdf')
const exportRange = ref('filtered')
const exportFields = ref(['all'])
const rescheduleDate = ref('')
const sendRescheduleNotification = ref(true)

// Computed
const allTasksSelected = computed(() => {
  return props.tasks.data.length > 0 && 
         props.tasks.data.every(task => selectedTaskIds.value.includes(task.id))
})

const selectedTasks = computed(() => {
  return props.tasks.data.filter(task => selectedTaskIds.value.includes(task.id))
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
  return new Date(dateString).toLocaleDateString('pt-PT', {
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

const getTaskColor = (task) => {
  if (task.is_executed) {
    return { bg: 'bg-green-100', text: 'text-green-900' }
  }
  
  const dueDate = new Date(task.due_date)
  const today = new Date()
  const daysDiff = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24))
  
  if (daysDiff < 0) {
    return { bg: 'bg-red-100', text: 'text-red-900' }
  } else if (daysDiff <= 7) {
    return { bg: 'bg-orange-100', text: 'text-orange-900' }
  } else if (daysDiff <= 30) {
    return { bg: 'bg-yellow-100', text: 'text-yellow-900' }
  } else {
    return { bg: 'bg-blue-100', text: 'text-blue-900' }
  }
}

const getDueDateColor = (task) => {
  if (task.is_executed) return 'text-green-900'
  
  const dueDate = new Date(task.due_date)
  const today = new Date()
  const daysDiff = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24))
  
  if (daysDiff < 0) return 'text-red-900'
  if (daysDiff <= 7) return 'text-orange-900'
  if (daysDiff <= 30) return 'text-yellow-900'
  return 'text-blue-900'
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
    return 'inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800'
  }
  
  const dueDate = new Date(task.due_date)
  const today = new Date()
  const daysDiff = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24))
  
  if (daysDiff < 0) {
    return 'inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800'
  } else if (daysDiff <= 7) {
    return 'inline-flex items-center rounded-full bg-orange-100 px-3 py-1 text-xs font-medium text-orange-800'
  } else if (daysDiff <= 30) {
    return 'inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800'
  } else {
    return 'inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800'
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
    selectedTaskIds.value = props.tasks.data.map(task => task.id)
  }
}

const clearSelection = () => {
  selectedTaskIds.value = []
  bulkAction.value = ''
}

const executeBulkAction = async () => {
  if (!bulkAction.value || selectedTaskIds.value.length === 0) return

  switch (bulkAction.value) {
    case 'mark_executed':
      if (confirm(`Marcar ${selectedTaskIds.value.length} tarefas como executadas?`)) {
        await executeMarkAsExecuted()
      }
      break
    case 'reschedule':
      showRescheduleModal.value = true
      break
    case 'delete':
      if (confirm(`Eliminar ${selectedTaskIds.value.length} tarefas? Esta ação não pode ser revertida.`)) {
        await executeDelete()
      }
      break
  }
}

const executeMarkAsExecuted = async () => {
  try {
    await axios.post(route('vap-maintenance.tasks.bulk-update'), {
      task_ids: selectedTaskIds.value,
      action: 'mark_executed'
    })
    clearSelection()
    router.reload()
  } catch (error) {
    console.error('Error marking tasks as executed:', error)
    alert('Erro ao marcar tarefas como executadas')
  }
}

const executeReschedule = async () => {
  if (!rescheduleDate.value) return

  try {
    await axios.post(route('vap-maintenance.tasks.bulk-update'), {
      task_ids: selectedTaskIds.value,
      action: 'reschedule',
      new_date: rescheduleDate.value,
      send_notification: sendRescheduleNotification.value
    })
    clearSelection()
    showRescheduleModal.value = false
    router.reload()
  } catch (error) {
    console.error('Error rescheduling tasks:', error)
    alert('Erro ao reagendar tarefas')
  }
}

const executeDelete = async () => {
  try {
    await axios.post(route('vap-maintenance.tasks.bulk-update'), {
      task_ids: selectedTaskIds.value,
      action: 'delete'
    })
    clearSelection()
    router.reload()
  } catch (error) {
    console.error('Error deleting tasks:', error)
    alert('Erro ao eliminar tarefas')
  }
}

const markAsExecuted = async (task) => {
  if (confirm('Marcar esta tarefa como concluída?')) {
    try {
      await axios.put(route('vap-maintenance.tasks.update', task.id), {
        is_executed: true,
        result: 'Concluído via lista'
      })
      router.reload()
    } catch (error) {
      console.error('Error marking task as executed:', error)
      alert('Erro ao marcar tarefa como executada')
    }
  }
}

const exportTasks = () => {
  showExportModal.value = true
}

const proceedExport = () => {
  const params = {
    format: exportFormat.value,
    type: 'tasks',
    range: exportRange.value,
    fields: exportFields.value.join(','),
    ...props.filters
  }

  window.open(route('vap-maintenance.export', params), '_blank')
  showExportModal.value = false
}

// Watch filters
// watch(
//   () => props.filters,
//   applyFilters,
//   { deep: true }
// )

// Initialize reschedule date
rescheduleDate.value = new Date().toISOString().split('T')[0]
</script>
