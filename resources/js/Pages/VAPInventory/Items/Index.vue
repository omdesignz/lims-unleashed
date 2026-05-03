<template>
  <div class="space-y-8">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
      <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="space-y-3">
          <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-blue-900">
            <CubeIcon class="h-4 w-4" />
            Inventário laboratorial
          </div>
          <div>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Itens</h1>
            <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-600">
              Controle reagentes, equipamentos e consumíveis com filtros rápidos, rastreabilidade de stock e alertas metrológicos.
            </p>
          </div>
        </div>

        <div class="flex flex-wrap gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ stats.equipment_count }} Equipamentos
          </span>
          <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-900 ring-1 ring-inset ring-emerald-700/10">
            {{ stats.reagents_count }} Reagentes
          </span>
          <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-sm font-medium text-amber-900 ring-1 ring-inset ring-amber-700/10">
            {{ stats.consumables_count }} Consumíveis
          </span>
          <span class="inline-flex items-center rounded-full bg-red-50 px-3 py-1 text-sm font-medium text-red-900 ring-1 ring-inset ring-red-700/10">
            {{ stats.items_on_metrology_hold || 0 }} Em bloqueio metrológico
          </span>
        </div>
      </div>
    </section>

    <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr),20rem]">
      <div class="space-y-8">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
          <div class="flex flex-col gap-6">
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700">Pesquisar</label>
                <div class="relative">
                  <MagnifyingGlassIcon class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                  <input
                    v-model="localFilters.search"
                    type="text"
                    placeholder="Nome, código ou referência"
                    class="block w-full rounded-2xl border border-slate-300 py-3 pl-11 pr-4 text-sm text-slate-900 shadow-sm focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20"
                  />
                </div>
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700">Categoria</label>
                <comboboxEnhanced
                  v-model="selectedCategory"
                  :hasError="false"
                  :options="categories.map((category) => ({ value: category.id, label: category.name }))"
                  placeholder="Todas as categorias"
                />
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700">Tipo</label>
                <comboboxEnhanced
                  v-model="selectedType"
                  :hasError="false"
                  :options="types.map((type) => ({ value: type.id, label: type.name }))"
                  placeholder="Todos os tipos"
                />
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700">Estado</label>
                <comboboxEnhanced
                  v-model="selectedStatus"
                  :hasError="false"
                  :options="statuses.map((status) => ({ value: status.id, label: status.name }))"
                  placeholder="Todos os estados"
                />
              </div>
            </div>

            <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
              <div class="space-y-2">
                <p class="text-sm font-medium text-slate-700">
                  Mostrando {{ items.from || 0 }} a {{ items.to || 0 }} de {{ items.total || 0 }} itens
                </p>
                <div v-if="activeFilterPills.length" class="flex flex-wrap gap-2">
                  <span
                    v-for="pill in activeFilterPills"
                    :key="pill.label"
                    class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-700 ring-1 ring-inset ring-slate-200"
                  >
                    {{ pill.label }}
                  </span>
                </div>
                <p v-else class="text-xs text-slate-500">Sem filtros adicionais aplicados.</p>
              </div>

              <div class="flex flex-col gap-3 sm:flex-row">
                <button
                  type="button"
                  class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100"
                  @click="clearFilters"
                >
                  <FunnelIcon class="h-4 w-4" />
                  Limpar filtros
                </button>
                <button
                  type="button"
                  class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100"
                  @click="exportItems"
                >
                  <ArrowDownTrayIcon class="h-4 w-4" />
                  Exportar CSV
                </button>
                <Link
                  :href="route('vap-inventory.items.create')"
                  class="inline-flex items-center justify-center gap-2 rounded-full bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800"
                >
                  <PlusCircleIcon class="h-5 w-5" />
                  Adicionar item
                </Link>
              </div>
            </div>
          </div>
        </section>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
          <div v-if="items.data.length" class="divide-y divide-slate-200 lg:hidden">
            <article
              v-for="item in items.data"
              :key="item.id"
              class="space-y-4 p-5"
            >
              <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                  <p class="text-base font-semibold text-slate-900">{{ item.name }}</p>
                  <p class="mt-1 text-sm text-slate-500">Código: {{ item.code || 'N/D' }}</p>
                  <p v-if="item.internal_code" class="text-sm text-slate-500">Interno: {{ item.internal_code }}</p>
                </div>
                <div class="rounded-2xl bg-blue-50 p-3 text-blue-900">
                  <CubeIcon class="h-5 w-5" />
                </div>
              </div>

              <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                  <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Categoria</p>
                  <p class="mt-2 text-sm font-medium text-slate-900">{{ item.category?.name || 'N/D' }}</p>
                  <p class="mt-1 text-sm text-slate-500">{{ item.type?.name || 'N/D' }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                  <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Stock</p>
                  <p class="mt-2 text-2xl font-semibold text-blue-900">{{ item.inventory_sum_qty_available || 0 }}</p>
                  <p class="mt-1 text-sm text-slate-500">
                    Reabastecimento: {{ item.reorder_qty > 0 ? item.reorder_qty : '—' }}
                  </p>
                </div>
              </div>

              <div class="flex flex-wrap gap-2">
                <span
                  v-if="item.status"
                  :class="['inline-flex items-center rounded-full px-3 py-1 text-xs font-medium', getStatusColor(item.status)]"
                >
                  {{ item.status.name }}
                </span>
                <span
                  v-if="item.is_reagent && item.is_expired"
                  class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800"
                >
                  <ExclamationTriangleIcon class="mr-1 h-3 w-3" />
                  Vencido
                </span>
                <span
                  v-else-if="item.is_reagent && item.days_to_expiry <= 30"
                  class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800"
                >
                  <ExclamationTriangleIcon class="mr-1 h-3 w-3" />
                  Vence em {{ item.days_to_expiry }} dias
                </span>
                <span
                  v-if="item.metrology_status && item.metrology_status !== 'not_required'"
                  :class="['inline-flex items-center rounded-full px-3 py-1 text-xs font-medium', getMetrologyClasses(item.metrology_status)]"
                >
                  <ExclamationTriangleIcon class="mr-1 h-3 w-3" />
                  {{ getMetrologyText(item.metrology_status) }}
                </span>
              </div>

              <div class="flex flex-col gap-2 sm:flex-row">
                <Link
                  :href="route('vap-inventory.items.show', item.id)"
                  class="inline-flex items-center justify-center rounded-full bg-blue-50 px-4 py-2.5 text-sm font-medium text-blue-900 transition hover:bg-blue-100"
                >
                  <EyeIcon class="mr-1 h-4 w-4" />
                  Visualizar
                </Link>
                <Link
                  :href="route('vap-inventory.items.edit', item.id)"
                  class="inline-flex items-center justify-center rounded-full bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-200"
                >
                  <PencilSquareIcon class="mr-1 h-4 w-4" />
                  Modificar
                </Link>
                <button
                  type="button"
                  class="inline-flex items-center justify-center rounded-full bg-red-50 px-4 py-2.5 text-sm font-medium text-red-700 transition hover:bg-red-100"
                  @click="confirmDelete(item)"
                >
                  <TrashIcon class="mr-1 h-4 w-4" />
                  Excluir
                </button>
              </div>
            </article>
          </div>

          <div v-if="items.data.length" class="hidden lg:block overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-slate-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-600">Item</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-600">Categoria</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-600">Stock</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-600">Estado</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-600">Ações</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 bg-white">
                <tr v-for="(item, index) in items.data" :key="item.id" class="transition hover:bg-slate-50">
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-4">
                      <div class="rounded-2xl bg-blue-50 p-3 text-blue-900">
                        <CubeIcon class="h-5 w-5" />
                      </div>
                      <div>
                        <p class="text-sm font-semibold text-slate-900">{{ item.name }}</p>
                        <p class="text-sm text-slate-500">Código: {{ item.code || 'N/D' }}</p>
                        <p v-if="item.internal_code" class="text-sm text-slate-500">Interno: {{ item.internal_code }}</p>
                        <p v-if="item.barcode" class="text-sm text-slate-500">Barcode: {{ item.barcode }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <p class="text-sm font-medium text-slate-900">{{ item.category?.name || 'N/D' }}</p>
                    <p class="text-sm text-slate-500">{{ item.type?.name || 'N/D' }}</p>
                  </td>
                  <td class="px-6 py-4">
                    <p class="text-2xl font-semibold text-blue-900">{{ item.inventory_sum_qty_available || 0 }}</p>
                    <p class="text-sm text-slate-500">Reabastecimento: {{ item.reorder_qty > 0 ? item.reorder_qty : '—' }}</p>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex flex-wrap gap-2">
                      <span
                        v-if="item.status"
                        :class="['inline-flex items-center rounded-full px-3 py-1 text-xs font-medium', getStatusColor(item.status)]"
                      >
                        {{ item.status.name }}
                      </span>
                      <span
                        v-if="item.is_reagent && item.is_expired"
                        class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800"
                      >
                        <ExclamationTriangleIcon class="mr-1 h-3 w-3" />
                        Vencido
                      </span>
                      <span
                        v-else-if="item.is_reagent && item.days_to_expiry <= 30"
                        class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800"
                      >
                        <ExclamationTriangleIcon class="mr-1 h-3 w-3" />
                        Vence em {{ item.days_to_expiry }} dias
                      </span>
                      <span
                        v-if="item.metrology_status && item.metrology_status !== 'not_required'"
                        :class="['inline-flex items-center rounded-full px-3 py-1 text-xs font-medium', getMetrologyClasses(item.metrology_status)]"
                      >
                        <ExclamationTriangleIcon class="mr-1 h-3 w-3" />
                        {{ getMetrologyText(item.metrology_status) }}
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex flex-wrap gap-2">
                      <Link
                        :href="route('vap-inventory.items.show', item.id)"
                        class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-900 transition hover:bg-blue-100"
                      >
                        <EyeIcon class="mr-1 h-4 w-4" />
                        Visualizar
                      </Link>
                      <Link
                        :href="route('vap-inventory.items.edit', item.id)"
                        class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-200"
                      >
                        <PencilSquareIcon class="mr-1 h-4 w-4" />
                        Modificar
                      </Link>
                      <button
                        type="button"
                        class="inline-flex items-center rounded-full bg-red-50 px-3 py-1.5 text-sm font-medium text-red-700 transition hover:bg-red-100"
                        @click="confirmDelete(item)"
                      >
                        <TrashIcon class="mr-1 h-4 w-4" />
                        Excluir
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="!items.data.length" class="p-12 text-center">
            <CubeIcon class="mx-auto h-12 w-12 text-slate-300" />
            <h3 class="mt-4 text-sm font-semibold text-slate-900">Nenhum item encontrado</h3>
            <p class="mt-2 text-sm text-slate-500">Ajuste os filtros ou adicione o primeiro item do inventário.</p>
            <Link
              :href="route('vap-inventory.items.create')"
              class="mt-6 inline-flex items-center gap-2 rounded-full bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800"
            >
              <PlusCircleIcon class="h-5 w-5" />
              Adicionar primeiro item
            </Link>
          </div>

          <div v-if="items.data.length" class="border-t border-slate-200 px-6 py-4">
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
      </div>

      <aside class="space-y-6 xl:sticky xl:top-24 xl:self-start">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900">
            <ChartBarIcon class="h-5 w-5 text-blue-900" />
            Estado geral
          </h2>

          <div class="mt-5 space-y-4">
            <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4">
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Itens registados</p>
              <p class="mt-2 text-3xl font-semibold text-blue-950">{{ stats.total_items }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Reagentes</p>
                <p class="mt-2 text-xl font-semibold text-emerald-950">{{ stats.reagents_count }}</p>
              </div>
              <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Equipamentos</p>
                <p class="mt-2 text-xl font-semibold text-amber-950">{{ stats.equipment_count }}</p>
              </div>
            </div>

            <div class="space-y-3">
              <QuickLink
                :href="route('vap-inventory.items.calibration.schedule')"
                label="Calibração em breve"
                :value="stats.items_needing_calibration"
              />
              <QuickLink
                :href="route('vap-inventory.items.reagents.expiry')"
                label="Reagentes vencidos"
                :value="stats.expired_reagents"
              />
              <QuickLink
                :href="route('vap-inventory.reports.low-stock')"
                label="Itens com pouco stock"
                :value="stats.low_stock_items || 0"
              />
            </div>
          </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 class="text-lg font-semibold text-slate-900">Ações rápidas</h2>
          <div class="mt-5 space-y-3">
            <QuickAction
              :href="route('vap-inventory.orders.create')"
              title="Criar ordem"
              description="Registar uma nova compra."
              color="blue"
              :icon="ShoppingCartIcon"
            />
            <QuickAction
              :href="route('vap-inventory.transfers.create')"
              title="Transferir itens"
              description="Mover stock entre armazéns."
              color="emerald"
              :icon="ArrowsRightLeftIcon"
            />
            <QuickAction
              :href="route('vap-inventory.items.create')"
              title="Novo item"
              description="Adicionar reagente, equipamento ou consumível."
              color="violet"
              :icon="PlusCircleIcon"
            />
          </div>
        </section>
      </aside>
    </div>

    <ConfirmationModal :show="showDeleteModal" @close="showDeleteModal = false" @confirm="deleteItem">
      <template #title>Excluir item</template>
      <template #content>
        Tem a certeza que deseja excluir <span class="font-semibold">{{ itemToDelete?.name }}</span>?
        Esta ação não pode ser desfeita.
      </template>
      <template #confirmButton>
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-full bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500"
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
  ChartBarIcon,
  CheckCircleIcon,
  ChevronRightIcon,
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

const QuickLink = {
  props: {
    href: {
      type: String,
      required: true,
    },
    label: {
      type: String,
      required: true,
    },
    value: {
      type: [String, Number],
      required: true,
    },
  },
  components: { Link },
  template: `
    <div class="flex items-center justify-between text-sm">
      <span class="text-slate-600">{{ label }}</span>
      <Link :href="href" class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-800 transition hover:bg-slate-200">
        {{ value }}
      </Link>
    </div>
  `,
}

const QuickAction = {
  props: {
    href: {
      type: String,
      required: true,
    },
    title: {
      type: String,
      required: true,
    },
    description: {
      type: String,
      required: true,
    },
    color: {
      type: String,
      required: true,
    },
    icon: {
      type: Object,
      required: true,
    },
  },
  components: { Link },
  computed: {
    badgeClasses() {
      return {
        blue: 'bg-blue-100 text-blue-900',
        emerald: 'bg-emerald-100 text-emerald-900',
        violet: 'bg-violet-100 text-violet-900',
      }[this.color]
    },
  },
  template: `
    <Link :href="href" class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-4 transition hover:bg-slate-50">
      <div class="flex items-center gap-3">
        <div class="rounded-2xl p-2" :class="badgeClasses">
          <component :is="icon" class="h-5 w-5" />
        </div>
        <div>
          <p class="font-medium text-slate-900">{{ title }}</p>
          <p class="text-sm text-slate-500">{{ description }}</p>
        </div>
      </div>
      <ChevronRightIcon class="h-5 w-5 text-slate-400" />
    </Link>
  `,
}

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

const getStatusColor = (status) => {
  const colors = {
    Active: 'bg-emerald-100 text-emerald-800',
    Inactive: 'bg-slate-100 text-slate-700',
    Maintenance: 'bg-amber-100 text-amber-800',
    'Calibration Due': 'bg-orange-100 text-orange-800',
    Expired: 'bg-red-100 text-red-800',
    'Out of Stock': 'bg-red-100 text-red-800',
    'Low Stock': 'bg-amber-100 text-amber-800',
  }

  return colors[status.name] || 'bg-slate-100 text-slate-700'
}

const getMetrologyClasses = (status) => {
  if (status === 'hold') {
    return 'bg-red-100 text-red-800'
  }

  if (status === 'incomplete') {
    return 'bg-orange-100 text-orange-800'
  }

  if (status === 'review_due') {
    return 'bg-amber-100 text-amber-800'
  }

  return 'bg-emerald-100 text-emerald-800'
}

const getMetrologyText = (status) => {
  if (status === 'hold') {
    return 'Metrologia bloqueada'
  }

  if (status === 'incomplete') {
    return 'Metrologia incompleta'
  }

  if (status === 'review_due') {
    return 'Revisão metrológica'
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
  window.open(query ? `${url}?${query}` : url, '_blank')
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
