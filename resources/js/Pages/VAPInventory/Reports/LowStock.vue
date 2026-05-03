<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ExclamationTriangleIcon class="h-7 w-7 text-orange-900" />
            Relatório de Estoque Baixo
          </h1>
          <p class="mt-2 text-gray-600">
            Monitore os itens com estoque baixo que requerem reabastecimento
            <span class="font-semibold text-orange-900">
              {{ stats.total_low_stock }} itens precisam de atenção
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <button
            @click="exportReport"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            <ArrowDownTrayIcon class="h-4 w-4" />
            Exportar Relatório
          </button>
          <Link
            :href="route('vap-inventory.items.index')"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
          >
            <ArrowLeftIcon class="h-4 w-4" />
            Voltar para Inventário
          </Link>
        </div>
      </div>
    </div>

    <!-- FILTERS -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- WAREHOUSE FILTER -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <BuildingLibraryIcon class="h-4 w-4 inline mr-1" />
            Armazém
          </label>
          <select
            v-model="filters.warehouse_id"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="">Todos os Armazéns</option>
            <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
              {{ warehouse.name }}
            </option>
          </select>
        </div>

        <!-- CATEGORY FILTER -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <TagIcon class="h-4 w-4 inline mr-1" />
            Categoria
          </label>
          <select
            v-model="filters.category_id"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="">Todas as Categorias</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </div>

        <!-- STOCK LEVEL FILTER -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <ScaleIcon class="h-4 w-4 inline mr-1" />
            Nível de Estoque
          </label>
          <select
            v-model="filters.severity"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="">Todos os Níveis</option>
            <option value="critical">Crítico (Abaixo do Mínimo)</option>
            <option value="low">Baixo (Abaixo do Reabastecimento)</option>
            <option value="warning">Aviso (Próximo Reabastecimento)</option>
          </select>
        </div>

        <!-- SORT BY -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            <ArrowsUpDownIcon class="h-4 w-4 inline mr-1" />
            Ordenar Por
          </label>
          <select
            v-model="filters.sort_by"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="severity">Severidade</option>
            <option value="current_stock">Estoque Atual</option>
            <option value="reorder_point">Ponto de Reabastecimento</option>
            <option value="item_name">Nome do Item</option>
          </select>
        </div>
      </div>
    </div>

    <!-- STATS CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-gradient-to-r from-red-900 to-red-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Estoque Crítico</p>
            <p class="text-2xl font-bold mt-1">{{ stats.critical_stock }}</p>
          </div>
          <ExclamationTriangleIcon class="h-8 w-8 opacity-50" />
        </div>
        <p class="text-xs opacity-80 mt-2">Abaixo do nível mínimo de estoque</p>
      </div>

      <div class="bg-gradient-to-r from-orange-900 to-orange-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Estoque Baixo</p>
            <p class="text-2xl font-bold mt-1">{{ stats.total_low_stock - stats.critical_stock }}</p>
          </div>
          <ExclamationCircleIcon class="h-8 w-8 opacity-50" />
        </div>
        <p class="text-xs opacity-80 mt-2">Abaixo do ponto de reabastecimento</p>
      </div>

      <div class="bg-gradient-to-r from-blue-900 to-blue-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Sem Estoque</p>
            <p class="text-2xl font-bold mt-1">{{ stats.out_of_stock }}</p>
          </div>
          <XCircleIcon class="h-8 w-8 opacity-50" />
        </div>
        <p class="text-xs opacity-80 mt-2">Sem estoque disponível</p>
      </div>

      <div class="bg-gradient-to-r from-gray-900 to-gray-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Total de Itens</p>
            <p class="text-2xl font-bold mt-1">{{ stats.total_items || 0 }}</p>
          </div>
          <CubeIcon class="h-8 w-8 opacity-50" />
        </div>
        <p class="text-xs opacity-80 mt-2">Em Inventário Monitorado</p>
      </div>
    </div>

    <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
      <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">Severidade da fila</h2>
            <p class="mt-1 text-sm text-slate-500">
              Distribuição entre itens sem stock, críticos e abaixo do ponto de reabastecimento.
            </p>
          </div>
          <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Itens em atenção</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ severityMixTotal }}</p>
          </div>
        </div>

        <div class="mt-6">
          <apexchart type="bar" height="300" :options="severityMixChartOptions" :series="severityMixChartSeries" />
        </div>
      </article>

      <div class="grid gap-6">
        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">Exposição por armazém</h2>
              <p class="mt-1 text-sm text-slate-500">Onde a pressão de reabastecimento está mais concentrada.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-orange-50 px-3 py-1 text-sm font-medium text-orange-800">
              {{ warehouseExposureTotal }} armazéns
            </span>
          </div>

          <div class="mt-6">
            <apexchart type="donut" height="300" :options="warehouseExposureChartOptions" :series="warehouseExposureChartSeries" />
          </div>
        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">Gap de reabastecimento</h2>
              <p class="mt-1 text-sm text-slate-500">Itens com maior distância até ao ponto mínimo desejado.</p>
            </div>
          </div>

          <div class="mt-6">
            <apexchart type="bar" height="250" :options="replenishmentGapChartOptions" :series="replenishmentGapChartSeries" />
          </div>
        </article>
      </div>
    </section>

    <!-- LOW STOCK ITEMS -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900">
            Itens com Estoque Baixo
            <span class="text-sm font-normal text-gray-500 ml-2">
              ({{ inventory.data.length }} itens)
            </span>
          </h2>
          <div class="flex items-center gap-2">
            <button
              @click="generateOrder"
              class="inline-flex items-center gap-2 rounded-lg bg-green-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-800"
            >
              <ShoppingCartIcon class="h-4 w-4" />
              Criar Pedido de Compra
            </button>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Detalhes do Item
              </th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Armazém
              </th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                Níveis de Estoque
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
              v-for="item in inventory.data"
              :key="item.id"
              class="hover:bg-gray-50 transition-colors duration-150"
            >
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div :class="[
                      'h-10 w-10 rounded-lg flex items-center justify-center',
                      getSeverityColor(item).bg
                    ]">
                      <CubeIcon :class="['h-6 w-6', getSeverityColor(item).text]" />
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-semibold text-gray-900">
                      {{ item.item?.name }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ item.item?.internal_code || 'Sem Código Interno' }}
                    </div>
                    <div class="text-xs text-gray-400">
                      {{ item.item?.category?.name || 'Sem Categoria' }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-900">{{ item.warehouse?.name }}</div>
                <div class="text-sm text-gray-500">{{ item.warehouse?.location?.name || 'Sem Localização' }}</div>
              </td>
              <td class="px-6 py-4">
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Actual:</span>
                    <span class="font-semibold text-blue-900">{{ item.qty_available }}</span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Ponto de Reabastecimento:</span>
                    <span class="font-semibold text-orange-900">{{ item.reorder_point }}</span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Nível Mínimo de Estoque:</span>
                    <span class="font-semibold text-red-900">{{ item.min_stock_level }}</span>
                  </div>
                  <div class="mt-2">
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                      <div
                        :class="['h-full rounded-full transition-all duration-500', getStockBarColor(item)]"
                        :style="{ width: getStockPercentage(item) + '%' }"
                      ></div>
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span :class="getStatusClasses(item)">
                  {{ getStatusText(item) }}
                </span>
                <div v-if="item.qty_available === 0" class="mt-2">
                  <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">
                    <XCircleIcon class="mr-1 h-3 w-3" />
                    Sem Estoque
                  </span>
                </div>
                <div v-if="item.item?.needs_calibration" class="mt-2">
                  <span class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-800">
                    <WrenchIcon class="mr-1 h-3 w-3" />
                    Requer Calibração
                  </span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <Link
                    :href="route('vap-inventory.items.show', item.item_id)"
                    class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-900 hover:bg-blue-100"
                  >
                    <EyeIcon class="h-4 w-4 mr-1" />
                    Visualizar
                  </Link>
                  <Link
                    :href="route('vap-inventory.items.edit', item.item_id)"
                    class="inline-flex items-center rounded-lg bg-gray-50 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100"
                  >
                    <PencilSquareIcon class="h-4 w-4 mr-1" />
                    Modificar Estoque
                  </Link>
                  <button
                    @click="createOrderForItem(item)"
                    class="inline-flex items-center rounded-lg bg-green-50 px-3 py-1.5 text-sm font-medium text-green-900 hover:bg-green-100"
                  >
                    <ShoppingCartIcon class="h-4 w-4 mr-1" />
                    Pedido de Compra
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- EMPTY STATE -->
      <div v-if="inventory.data.length === 0" class="p-12 text-center">
        <CheckCircleIcon class="mx-auto h-12 w-12 text-green-300" />
        <h3 class="mt-4 text-sm font-semibold text-gray-900">
          Nenhum item com estoque baixo encontrado
        </h3>
        <p class="mt-2 text-sm text-gray-500">
          Todos os itens estão no nível de estoque saudável. Boa sorte!
        </p>
        <Link
          :href="route('vap-inventory.items.index')"
          class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
        >
          <ArrowLeftIcon class="h-5 w-5" />
          Voltar para Inventário
        </Link>
      </div>

      <!-- PAGINATION -->
      <div v-if="inventory.data.length > 0" class="border-t border-gray-200 px-6 py-4">
        <Pagination :links="inventory.links" />
      </div>
    </div>

    <!-- RECOMMENDED ORDERS -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">
        Quantidades Recomendadas de Compra
      </h3>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Item
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Estoque Actual
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Quantidade Recomendada
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Fornecedor
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Preço Estimado
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="item in recommendedOrders"
              :key="item.id"
              class="hover:bg-gray-50"
            >
              <td class="px-6 py-4">
                <div class="text-sm font-medium text-gray-900">{{ item.name }}</div>
                <div class="text-sm text-gray-500">{{ item.code }}</div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ item.current_stock }}</td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-800">
                  {{ item.recommended_qty }} unidades
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">
                {{ item.supplier?.name || 'Sem Fornecedor' }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">
                AOA{{ (item.recommended_qty * (item.unit_price || 0)).toFixed(2) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
  ExclamationTriangleIcon,
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  BuildingLibraryIcon,
  TagIcon,
  ScaleIcon,
  ArrowsUpDownIcon,
  ExclamationCircleIcon,
  XCircleIcon,
  CubeIcon,
  ShoppingCartIcon,
  CheckCircleIcon,
  EyeIcon,
  PencilSquareIcon,
  WrenchIcon,
} from '@heroicons/vue/24/outline'
import Pagination from '@/Components/Pagination.vue'
import { debounce } from 'lodash'

const props = defineProps({
  inventory: Object,
  filters: Object,
  warehouses: Array,
  categories: Array,
  stats: Object,
  charts: {
    type: Object,
    default: () => ({})
  },
})

const filters = reactive({
  warehouse_id: props.filters?.warehouse_id ?? '',
  category_id: props.filters?.category_id ?? '',
  severity: props.filters?.severity ?? '',
  sort_by: props.filters?.sort_by ?? 'severity',
})

const severityMixChartSeries = computed(() => [
  {
    name: 'Itens',
    data: props.charts?.severity_mix?.series || [],
  }
])

const severityMixTotal = computed(() => (props.charts?.severity_mix?.series || []).reduce((total, value) => total + Number(value || 0), 0))

const warehouseExposureChartSeries = computed(() => props.charts?.warehouse_exposure?.series || [])
const warehouseExposureTotal = computed(() => props.charts?.warehouse_exposure?.labels?.length || 0)

const replenishmentGapChartSeries = computed(() => props.charts?.replenishment_gap?.series || [])

const severityMixChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  colors: ['#b91c1c'],
  dataLabels: { enabled: false },
  plotOptions: {
    bar: {
      borderRadius: 6,
      horizontal: true,
    },
  },
  xaxis: {
    categories: props.charts?.severity_mix?.labels || [],
  },
  legend: { show: false },
}))

const warehouseExposureChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  labels: props.charts?.warehouse_exposure?.labels || [],
  colors: ['#ea580c', '#b91c1c', '#1d4ed8', '#7c3aed', '#0f766e', '#475569'],
  legend: {
    position: 'bottom',
  },
  dataLabels: {
    formatter: (value) => `${value.toFixed(0)}%`,
  },
  stroke: {
    width: 0,
  },
}))

const replenishmentGapChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  colors: ['#1e3a8a'],
  dataLabels: { enabled: false },
  plotOptions: {
    bar: {
      borderRadius: 6,
      columnWidth: '48%',
    },
  },
  xaxis: {
    categories: props.charts?.replenishment_gap?.labels || [],
    labels: {
      rotate: -25,
      trim: true,
    },
  },
  legend: { show: false },
}))

const getSeverityColor = (item) => {
  if (item.qty_available <= item.min_stock_level || item.qty_available === 0) {
    return { bg: 'bg-red-100', text: 'text-red-900' }
  } else if (item.qty_available <= item.reorder_point) {
    return { bg: 'bg-orange-100', text: 'text-orange-900' }
  } else {
    return { bg: 'bg-yellow-100', text: 'text-yellow-900' }
  }
}

const getStockBarColor = (item) => {
  if (item.qty_available <= item.min_stock_level) return 'bg-red-900'
  if (item.qty_available <= item.reorder_point) return 'bg-orange-900'
  return 'bg-green-900'
}

const getStockPercentage = (item) => {
  const max = Math.max(item.reorder_point * 2, item.min_stock_level * 3, item.qty_available)
  return (item.qty_available / max) * 100
}

const getStatusClasses = (item) => {
  if (item.qty_available === 0) {
    return 'inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800'
  } else if (item.qty_available <= item.min_stock_level) {
    return 'inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800'
  } else if (item.qty_available <= item.reorder_point) {
    return 'inline-flex items-center rounded-full bg-orange-100 px-3 py-1 text-xs font-medium text-orange-800'
  } else {
    return 'inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800'
  }
}

const getStatusText = (item) => {
  if (item.qty_available === 0) return 'Sem Estoque'
  if (item.qty_available <= item.min_stock_level) return 'Crítico'
  if (item.qty_available <= item.reorder_point) return 'Baixo'
  return 'Normal'
}

const recommendedOrders = computed(() => {
  return props.inventory.data.map(item => {
    const recommended = Math.max(
      item.reorder_point * 2 - item.qty_available,
      item.min_stock_level * 3 - item.qty_available,
      1
    )
    
    return {
      id: item.item_id,
      name: item.item?.name,
      code: item.item?.code,
      current_stock: item.qty_available,
      recommended_qty: recommended,
      supplier: item.item?.supplier,
      unit_price: 100, // Placeholder - you should add this to your model
    }
  })
})

const exportReport = () => {
  window.open(route('vap-inventory.reports.export', {
    report_type: 'low_stock',
    format: 'pdf',
    filters: JSON.stringify({ ...filters })
  }), '_blank')
}

const generateOrder = () => {
  router.visit(route('vap-inventory.orders.create', {
    items: recommendedOrders.value.map(item => ({
      item_id: item.id,
      qty: item.recommended_qty
    }))
  }))
}

const createOrderForItem = (item) => {
  router.visit(route('vap-inventory.orders.create', {
    items: [{
      item_id: item.item_id,
      qty: Math.max(item.reorder_point * 2 - item.qty_available, 1)
    }]
  }))
}

// Watch filters
watch(
  filters,
  debounce((value) => {
    router.get(route('vap-inventory.reports.low-stock'), value, {
      preserveState: true,
      replace: true,
    })
  }, 300),
  { deep: true }
)
</script>
