<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-start">
        <div class="min-w-0">
          <p class="text-xs font-black uppercase tracking-[0.18em] text-[var(--ds-text-soft)]">
            Inventario laboratorial
          </p>
          <div class="mt-3 flex flex-wrap items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <CubeIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="text-2xl font-black tracking-tight text-[var(--ds-text)]">
                Itens de inventario
              </h1>
              <p class="mt-1 max-w-3xl text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
                Controle reagentes, equipamentos e consumiveis com stock, estado, validade e bloqueios metrologicos em uma unica fila.
              </p>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row xl:justify-end">
          <button
            type="button"
            class="ds-button ds-button-secondary"
            @click="exportItems"
          >
            <ArrowDownTrayIcon class="h-4 w-4" />
            Exportar CSV
          </button>
          <Link
            :href="route('vap-inventory.items.create')"
            class="ds-button ds-button-primary"
          >
            <PlusCircleIcon class="h-4 w-4" />
            Adicionar item
          </Link>
        </div>
      </div>

      <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article
          v-for="stat in statsCards"
          :key="stat.label"
          class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-4"
        >
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">
                {{ stat.label }}
              </p>
              <p class="mt-3 text-2xl font-black text-[var(--ds-text)]">
                {{ stat.value }}
              </p>
            </div>
            <component :is="stat.icon" :class="['h-5 w-5', stat.tone]" />
          </div>
          <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
            {{ stat.detail }}
          </p>
        </article>
      </div>
    </section>

    <section class="ds-command-surface p-5 sm:p-6">
      <div class="grid gap-4 xl:grid-cols-[minmax(16rem,1.4fr)_repeat(3,minmax(12rem,1fr))]">
        <label class="ds-field-group">
          <span class="ds-field-label">Pesquisar</span>
          <span class="relative block">
            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" />
            <input
              v-model="localFilters.search"
              type="search"
              placeholder="Nome, codigo ou referencia"
              class="ds-field pl-10"
            />
          </span>
        </label>

        <label class="ds-field-group">
          <span class="ds-field-label">Categoria</span>
          <comboboxEnhanced
            v-model="selectedCategory"
            :hasError="false"
            :options="categories.map((category) => ({ value: category.id, label: category.name }))"
            placeholder="Todas as categorias"
          />
        </label>

        <label class="ds-field-group">
          <span class="ds-field-label">Tipo</span>
          <comboboxEnhanced
            v-model="selectedType"
            :hasError="false"
            :options="types.map((type) => ({ value: type.id, label: type.name }))"
            placeholder="Todos os tipos"
          />
        </label>

        <label class="ds-field-group">
          <span class="ds-field-label">Estado</span>
          <comboboxEnhanced
            v-model="selectedStatus"
            :hasError="false"
            :options="statuses.map((status) => ({ value: status.id, label: status.name }))"
            placeholder="Todos os estados"
          />
        </label>
      </div>

      <div class="ds-command-toolbar mt-5 grid gap-3 p-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
        <div class="min-w-0">
          <p class="text-sm font-bold text-[var(--ds-text)]">
            Mostrando {{ items.from || 0 }} a {{ items.to || 0 }} de {{ items.total || 0 }} itens
          </p>
          <div v-if="activeFilterPills.length" class="mt-2 flex flex-wrap gap-2">
            <span
              v-for="pill in activeFilterPills"
              :key="pill.label"
              class="ds-chip"
            >
              {{ pill.label }}
            </span>
          </div>
          <p v-else class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
            Sem filtros adicionais aplicados.
          </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row">
          <button
            type="button"
            class="ds-button ds-button-secondary"
            :disabled="!hasActiveFilters"
            @click="clearFilters"
          >
            <FunnelIcon class="h-4 w-4" />
            Limpar filtros
          </button>
          <button
            type="button"
            class="ds-button ds-button-secondary"
            @click="exportItems"
          >
            <ArrowDownTrayIcon class="h-4 w-4" />
            Exportar
          </button>
        </div>
      </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
      <section class="ds-table-shell">
        <div class="ds-table-summary px-5 py-4">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">
              Registo de stock
            </p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">
              Itens registados
            </h2>
          </div>
          <span class="ds-chip">
            {{ itemRows.length }} nesta pagina
          </span>
        </div>

        <div v-if="itemRows.length" class="divide-y divide-[var(--ds-border)] lg:hidden">
          <article
            v-for="item in itemRows"
            :key="item.id"
            class="space-y-4 p-5"
          >
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <h3 class="text-base font-black text-[var(--ds-text)]">
                  {{ item.name }}
                </h3>
                <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">
                  Codigo: {{ item.code || 'N/D' }}
                </p>
                <p v-if="item.internal_code" class="text-sm font-semibold text-[var(--ds-text-muted)]">
                  Interno: {{ item.internal_code }}
                </p>
              </div>
              <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
                <CubeIcon class="h-5 w-5" />
              </span>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <p class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">
                  Categoria
                </p>
                <p class="mt-2 text-sm font-bold text-[var(--ds-text)]">
                  {{ item.category?.name || 'N/D' }}
                </p>
                <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">
                  {{ item.type?.name || 'N/D' }}
                </p>
              </div>
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <p class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">
                  Stock
                </p>
                <p class="mt-2 text-2xl font-black text-[rgb(var(--primary-800-rgb))] dark:text-cyan-100">
                  {{ item.inventory_sum_qty_available || 0 }}
                </p>
                <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">
                  Reabastecimento: {{ item.reorder_qty > 0 ? item.reorder_qty : '-' }}
                </p>
              </div>
            </div>

            <div class="flex flex-wrap gap-2">
              <span
                v-if="item.status"
                :class="['inline-flex items-center rounded-full border px-3 py-1 text-xs font-black', getStatusColor(item.status)]"
              >
                {{ item.status.name }}
              </span>
              <span
                v-if="item.is_reagent && item.is_expired"
                class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-black text-rose-800 dark:border-rose-400/30 dark:bg-rose-400/10 dark:text-rose-100"
              >
                <ExclamationTriangleIcon class="mr-1 h-3 w-3" />
                Vencido
              </span>
              <span
                v-else-if="item.is_reagent && item.days_to_expiry <= 30"
                class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-black text-amber-800 dark:border-amber-400/30 dark:bg-amber-400/10 dark:text-amber-100"
              >
                <ExclamationTriangleIcon class="mr-1 h-3 w-3" />
                Vence em {{ item.days_to_expiry }} dias
              </span>
              <span
                v-if="item.metrology_status && item.metrology_status !== 'not_required'"
                :class="['inline-flex items-center rounded-full border px-3 py-1 text-xs font-black', getMetrologyClasses(item.metrology_status)]"
              >
                <ExclamationTriangleIcon class="mr-1 h-3 w-3" />
                {{ getMetrologyText(item.metrology_status) }}
              </span>
            </div>

            <div class="flex flex-wrap gap-2">
              <Link :href="route('vap-inventory.items.show', item.id)" class="ds-table-action">
                <EyeIcon class="mr-1 h-4 w-4" />
                Visualizar
              </Link>
              <Link :href="route('vap-inventory.items.edit', item.id)" class="ds-table-action">
                <PencilSquareIcon class="mr-1 h-4 w-4" />
                Modificar
              </Link>
              <button
                type="button"
                class="ds-table-action ds-table-action-danger"
                @click="confirmDelete(item)"
              >
                <TrashIcon class="mr-1 h-4 w-4" />
                Excluir
              </button>
            </div>
          </article>
        </div>

        <div v-if="itemRows.length" class="hidden overflow-x-auto lg:block">
          <table class="min-w-full divide-y divide-[var(--ds-border)]">
            <thead class="ds-table-head">
              <tr>
                <th class="px-5 py-3 text-left ds-table-heading">Item</th>
                <th class="px-5 py-3 text-left ds-table-heading">Categoria</th>
                <th class="px-5 py-3 text-left ds-table-heading">Stock</th>
                <th class="px-5 py-3 text-left ds-table-heading">Estado</th>
                <th class="px-5 py-3 text-left ds-table-heading">Acoes</th>
              </tr>
            </thead>
            <tbody class="ds-table-body divide-y divide-[var(--ds-border)]">
              <tr v-for="item in itemRows" :key="item.id" class="ds-table-row">
                <td class="px-5 py-4">
                  <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
                      <CubeIcon class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                      <p class="truncate text-sm font-black text-[var(--ds-text)]">
                        {{ item.name }}
                      </p>
                      <p class="text-xs font-semibold text-[var(--ds-text-muted)]">
                        Codigo: {{ item.code || 'N/D' }}
                      </p>
                      <p v-if="item.internal_code" class="text-xs font-semibold text-[var(--ds-text-muted)]">
                        Interno: {{ item.internal_code }}
                      </p>
                      <p v-if="item.barcode" class="text-xs font-semibold text-[var(--ds-text-muted)]">
                        Barcode: {{ item.barcode }}
                      </p>
                    </div>
                  </div>
                </td>
                <td class="ds-table-cell px-5 py-4">
                  <p class="font-bold text-[var(--ds-text)]">
                    {{ item.category?.name || 'N/D' }}
                  </p>
                  <p class="text-xs">
                    {{ item.type?.name || 'N/D' }}
                  </p>
                </td>
                <td class="px-5 py-4">
                  <p class="text-xl font-black text-[rgb(var(--primary-800-rgb))] dark:text-cyan-100">
                    {{ item.inventory_sum_qty_available || 0 }}
                  </p>
                  <p class="text-xs font-semibold text-[var(--ds-text-muted)]">
                    Reabastecimento: {{ item.reorder_qty > 0 ? item.reorder_qty : '-' }}
                  </p>
                </td>
                <td class="px-5 py-4">
                  <div class="flex max-w-sm flex-wrap gap-2">
                    <span
                      v-if="item.status"
                      :class="['inline-flex items-center rounded-full border px-3 py-1 text-xs font-black', getStatusColor(item.status)]"
                    >
                      {{ item.status.name }}
                    </span>
                    <span
                      v-if="item.is_reagent && item.is_expired"
                      class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-black text-rose-800 dark:border-rose-400/30 dark:bg-rose-400/10 dark:text-rose-100"
                    >
                      <ExclamationTriangleIcon class="mr-1 h-3 w-3" />
                      Vencido
                    </span>
                    <span
                      v-else-if="item.is_reagent && item.days_to_expiry <= 30"
                      class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-black text-amber-800 dark:border-amber-400/30 dark:bg-amber-400/10 dark:text-amber-100"
                    >
                      <ExclamationTriangleIcon class="mr-1 h-3 w-3" />
                      Vence em {{ item.days_to_expiry }} dias
                    </span>
                    <span
                      v-if="item.metrology_status && item.metrology_status !== 'not_required'"
                      :class="['inline-flex items-center rounded-full border px-3 py-1 text-xs font-black', getMetrologyClasses(item.metrology_status)]"
                    >
                      <ExclamationTriangleIcon class="mr-1 h-3 w-3" />
                      {{ getMetrologyText(item.metrology_status) }}
                    </span>
                  </div>
                </td>
                <td class="px-5 py-4">
                  <div class="flex flex-wrap gap-1.5">
                    <Link :href="route('vap-inventory.items.show', item.id)" class="ds-table-action">
                      <EyeIcon class="h-4 w-4" />
                      <span class="sr-only">Visualizar</span>
                    </Link>
                    <Link :href="route('vap-inventory.items.edit', item.id)" class="ds-table-action">
                      <PencilSquareIcon class="h-4 w-4" />
                      <span class="sr-only">Modificar</span>
                    </Link>
                    <button
                      type="button"
                      class="ds-table-action ds-table-action-danger"
                      @click="confirmDelete(item)"
                    >
                      <TrashIcon class="h-4 w-4" />
                      <span class="sr-only">Excluir</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="!itemRows.length" class="ds-empty-state m-5 p-8 text-center">
          <CubeIcon class="mx-auto h-11 w-11 text-[var(--ds-text-soft)]" />
          <h3 class="mt-4 text-base font-black text-[var(--ds-text)]">
            Nenhum item encontrado
          </h3>
          <p class="mx-auto mt-2 max-w-md text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
            Ajuste os filtros ou adicione o primeiro item com dados de stock, validade e rastreabilidade.
          </p>
          <Link
            :href="route('vap-inventory.items.create')"
            class="ds-button ds-button-primary mt-6"
          >
            <PlusCircleIcon class="h-4 w-4" />
            Adicionar primeiro item
          </Link>
        </div>

        <div v-if="itemRows.length" class="border-t border-[var(--ds-border)] px-5 py-4">
          <Pagination
            :links="items.links"
            :from="items.from"
            :to="items.to"
            :total="items.total"
            :current_page="items.current_page"
            :last_page="items.last_page"
          />
        </div>
      </section>

      <aside class="space-y-6 xl:sticky xl:top-24 xl:self-start">
        <section class="ds-card p-5">
          <h2 class="flex items-center gap-2 text-base font-black text-[var(--ds-text)]">
            <ChartBarIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
            Estado geral
          </h2>

          <div class="mt-5 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">
              Itens registados
            </p>
            <p class="mt-2 text-3xl font-black text-[var(--ds-text)]">
              {{ stats.total_items }}
            </p>
          </div>

          <div class="mt-4 grid grid-cols-2 gap-3">
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-400/30 dark:bg-emerald-400/10">
              <p class="text-xs font-black uppercase tracking-[0.12em] text-emerald-800 dark:text-emerald-100">
                Reagentes
              </p>
              <p class="mt-2 text-xl font-black text-emerald-900 dark:text-emerald-50">
                {{ stats.reagents_count }}
              </p>
            </div>
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-400/30 dark:bg-amber-400/10">
              <p class="text-xs font-black uppercase tracking-[0.12em] text-amber-800 dark:text-amber-100">
                Equipamentos
              </p>
              <p class="mt-2 text-xl font-black text-amber-900 dark:text-amber-50">
                {{ stats.equipment_count }}
              </p>
            </div>
          </div>

          <div class="mt-5 space-y-3">
            <Link
              v-for="alert in quickAlerts"
              :key="alert.label"
              :href="alert.href"
              class="flex items-center justify-between rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] px-3 py-2 text-sm font-bold text-[var(--ds-text-muted)] transition hover:border-[rgb(var(--primary-300-rgb)/0.72)] hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)]"
            >
              <span>{{ alert.label }}</span>
              <span class="ds-chip">{{ alert.value }}</span>
            </Link>
          </div>
        </section>

        <section class="ds-command-surface p-5">
          <h2 class="text-base font-black text-[var(--ds-text)]">
            Acoes rapidas
          </h2>
          <div class="mt-5 space-y-3">
            <Link
              v-for="action in quickActions"
              :key="action.title"
              :href="action.href"
              class="group flex items-center justify-between rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-3 transition hover:border-[rgb(var(--primary-300-rgb)/0.72)] hover:bg-[var(--ds-panel-subtle)]"
            >
              <span class="flex min-w-0 items-center gap-3">
                <span :class="['grid h-10 w-10 shrink-0 place-items-center rounded-lg border', action.tone]">
                  <component :is="action.icon" class="h-5 w-5" />
                </span>
                <span class="min-w-0">
                  <span class="block text-sm font-black text-[var(--ds-text)]">{{ action.title }}</span>
                  <span class="block text-xs font-semibold text-[var(--ds-text-muted)]">{{ action.description }}</span>
                </span>
              </span>
              <ArrowTopRightOnSquareIcon class="h-4 w-4 shrink-0 text-[var(--ds-text-soft)] transition group-hover:text-[rgb(var(--primary-700-rgb))]" />
            </Link>
          </div>
        </section>
      </aside>
    </div>

    <ConfirmationModal :show="showDeleteModal" @close="showDeleteModal = false" @confirm="deleteItem">
      <template #title>Excluir item</template>
      <template #content>
        Tem a certeza que deseja excluir <span class="font-semibold">{{ itemToDelete?.name }}</span>?
        Esta acao nao pode ser desfeita.
      </template>
      <template #confirmButton>
        <button
          type="button"
          class="ds-button ds-button-danger"
          @click="deleteItem"
        >
          <TrashIcon class="h-4 w-4" />
          Excluir item
        </button>
      </template>
    </ConfirmationModal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import {
  ArrowsRightLeftIcon,
  ArrowDownTrayIcon,
  ArrowTopRightOnSquareIcon,
  ChartBarIcon,
  CubeIcon,
  ExclamationTriangleIcon,
  EyeIcon,
  FunnelIcon,
  MagnifyingGlassIcon,
  PencilSquareIcon,
  PlusCircleIcon,
  ShoppingCartIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline'
import Pagination from '@/Components/Pagination.vue'
import ConfirmationModal from '@/Components/confirm-dialog.vue'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'

const props = defineProps({
  items: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  categories: {
    type: Array,
    default: () => [],
  },
  types: {
    type: Array,
    default: () => [],
  },
  statuses: {
    type: Array,
    default: () => [],
  },
  suppliers: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    required: true,
  },
})

const localFilters = reactive({
  search: props.filters.search || '',
  category_id: props.filters.category_id || '',
  type_id: props.filters.type_id || '',
  status_id: props.filters.status_id || '',
})

const selectedCategory = ref(null)
const selectedType = ref(null)
const selectedStatus = ref(null)
const showDeleteModal = ref(false)
const itemToDelete = ref(null)

const itemRows = computed(() => props.items?.data ?? [])

const statsCards = computed(() => [
  {
    label: 'Equipamentos',
    value: props.stats.equipment_count,
    detail: 'Ativos sujeitos a controlo',
    icon: CubeIcon,
    tone: 'text-cyan-700 dark:text-cyan-200',
  },
  {
    label: 'Reagentes',
    value: props.stats.reagents_count,
    detail: 'Com validade e lote',
    icon: ExclamationTriangleIcon,
    tone: 'text-emerald-700 dark:text-emerald-200',
  },
  {
    label: 'Consumiveis',
    value: props.stats.consumables_count,
    detail: 'Uso operacional',
    icon: ShoppingCartIcon,
    tone: 'text-amber-700 dark:text-amber-200',
  },
  {
    label: 'Bloqueios',
    value: props.stats.items_on_metrology_hold || 0,
    detail: 'Em revisao metrologica',
    icon: ExclamationTriangleIcon,
    tone: 'text-rose-700 dark:text-rose-200',
  },
])

const quickAlerts = computed(() => [
  {
    href: route('vap-inventory.items.calibration.schedule'),
    label: 'Calibracao em breve',
    value: props.stats.items_needing_calibration || 0,
  },
  {
    href: route('vap-inventory.items.reagents.expiry'),
    label: 'Reagentes vencidos',
    value: props.stats.expired_reagents || 0,
  },
  {
    href: route('vap-inventory.reports.low-stock'),
    label: 'Itens com pouco stock',
    value: props.stats.low_stock_items || 0,
  },
])

const quickActions = computed(() => [
  {
    href: route('vap-inventory.orders.create'),
    title: 'Criar ordem',
    description: 'Registar uma nova compra.',
    icon: ShoppingCartIcon,
    tone: 'border-cyan-200 bg-cyan-50 text-cyan-800 dark:border-cyan-400/30 dark:bg-cyan-400/10 dark:text-cyan-100',
  },
  {
    href: route('vap-inventory.transfers.create'),
    title: 'Transferir itens',
    description: 'Mover stock entre armazens.',
    icon: ArrowsRightLeftIcon,
    tone: 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-400/30 dark:bg-emerald-400/10 dark:text-emerald-100',
  },
  {
    href: route('vap-inventory.items.create'),
    title: 'Novo item',
    description: 'Adicionar reagente, equipamento ou consumivel.',
    icon: PlusCircleIcon,
    tone: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-400/30 dark:bg-amber-400/10 dark:text-amber-100',
  },
])

watch(selectedCategory, (newValue) => {
  localFilters.category_id = newValue?.value || ''
})

watch(selectedType, (newValue) => {
  localFilters.type_id = newValue?.value || ''
})

watch(selectedStatus, (newValue) => {
  localFilters.status_id = newValue?.value || ''
})

const activeFilterPills = computed(() => {
  return [
    localFilters.search ? { label: `Pesquisa: ${localFilters.search}` } : null,
    selectedCategory.value ? { label: `Categoria: ${selectedCategory.value.label}` } : null,
    selectedType.value ? { label: `Tipo: ${selectedType.value.label}` } : null,
    selectedStatus.value ? { label: `Estado: ${selectedStatus.value.label}` } : null,
  ].filter(Boolean)
})

const hasActiveFilters = computed(() => activeFilterPills.value.length > 0)

const getStatusColor = (status) => {
  const colors = {
    Active: 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-400/30 dark:bg-emerald-400/10 dark:text-emerald-100',
    Inactive: 'border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)]',
    Maintenance: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-400/30 dark:bg-amber-400/10 dark:text-amber-100',
    'Calibration Due': 'border-orange-200 bg-orange-50 text-orange-800 dark:border-orange-400/30 dark:bg-orange-400/10 dark:text-orange-100',
    Expired: 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-400/30 dark:bg-rose-400/10 dark:text-rose-100',
    'Out of Stock': 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-400/30 dark:bg-rose-400/10 dark:text-rose-100',
    'Low Stock': 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-400/30 dark:bg-amber-400/10 dark:text-amber-100',
  }

  return colors[status.name] || 'border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)]'
}

const getMetrologyClasses = (status) => {
  if (status === 'hold') {
    return 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-400/30 dark:bg-rose-400/10 dark:text-rose-100'
  }

  if (status === 'incomplete') {
    return 'border-orange-200 bg-orange-50 text-orange-800 dark:border-orange-400/30 dark:bg-orange-400/10 dark:text-orange-100'
  }

  if (status === 'review_due') {
    return 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-400/30 dark:bg-amber-400/10 dark:text-amber-100'
  }

  return 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-400/30 dark:bg-emerald-400/10 dark:text-emerald-100'
}

const getMetrologyText = (status) => {
  if (status === 'hold') {
    return 'Metrologia bloqueada'
  }

  if (status === 'incomplete') {
    return 'Metrologia incompleta'
  }

  if (status === 'review_due') {
    return 'Revisao metrologica'
  }

  return 'Metrologia validada'
}

const confirmDelete = (item) => {
  itemToDelete.value = item
  showDeleteModal.value = true
}

const deleteItem = () => {
  if (!itemToDelete.value) {
    return
  }

  router.delete(route('vap-inventory.items.destroy', itemToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false
      itemToDelete.value = null
    },
  })
}

const clearFilters = () => {
  localFilters.search = ''
  localFilters.category_id = ''
  localFilters.type_id = ''
  localFilters.status_id = ''
  selectedCategory.value = null
  selectedType.value = null
  selectedStatus.value = null
}

const applyFilters = debounce(() => {
  router.get(route('vap-inventory.items.index'), { ...localFilters }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}, 350)

watch(localFilters, applyFilters, { deep: true })

const exportItems = () => {
  const params = new URLSearchParams()

  Object.entries(localFilters).forEach(([key, value]) => {
    if (value) {
      params.set(key, value)
    }
  })

  const query = params.toString()
  const url = route('vap-inventory.items.export.inventory')
  window.location.assign(query ? `${url}?${query}` : url)
}

onMounted(() => {
  if (props.filters.category_id) {
    const category = props.categories.find((item) => item.id == props.filters.category_id)

    if (category) {
      selectedCategory.value = { value: category.id, label: category.name }
    }
  }

  if (props.filters.type_id) {
    const type = props.types.find((item) => item.id == props.filters.type_id)

    if (type) {
      selectedType.value = { value: type.id, label: type.name }
    }
  }

  if (props.filters.status_id) {
    const status = props.statuses.find((item) => item.id == props.filters.status_id)

    if (status) {
      selectedStatus.value = { value: status.id, label: status.name }
    }
  }
})
</script>
