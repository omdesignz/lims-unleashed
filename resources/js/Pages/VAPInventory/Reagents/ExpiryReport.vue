<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <ModuleHero
      eyebrow="Expiry control"
      title="Relatório de Validade de Reagentes"
      :description="`Monitore a data de validade de reagentes e gerencie a rotação de estoque. ${stats.expired} itens vencidos.`"
    >
      <template #actions>
        <div class="flex items-center gap-3">
          <button
            @click="exportReport"
            class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white/80 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950/50 dark:text-slate-200 dark:hover:bg-slate-800"
          >
            <ArrowDownTrayIcon class="h-4 w-4" />
            Exportar Relatório
          </button>
          <Link
            :href="route('vap-inventory.items.index')"
            class="inline-flex items-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-400"
          >
            <ArrowLeftIcon class="h-4 w-4" />
            
            Voltar ao Inventário
          </Link>
        </div>
      </template>
    </ModuleHero>

    <!-- FILTERS -->
    <ModuleCard title="Filtros de validade" description="Refine por estado de validade, categoria, armazém e ordenação.">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- STATUS FILTER -->
        <div>
          <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">
            <CheckCircleIcon class="h-4 w-4 inline mr-1" />
            Estado
          </label>
          <BaseSelect v-model="filters.status">
            <option value="">Todos os Estados</option>
            <option value="expired">Vencido</option>
            <option value="expiring_soon">Vencendo em Breve (≤ 60 dias)</option>
            <option value="good">Bom (> 60 dias)</option>
          </BaseSelect>
        </div>

        <!-- CATEGORY FILTER -->
        <div>
          <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">
            <TagIcon class="h-4 w-4 inline mr-1" />
            Categoria
          </label>
          <BaseSelect v-model="filters.category_id">
            <option value="">Todas as Categorias</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </BaseSelect>
        </div>

        <!-- WAREHOUSE FILTER -->
        <div>
          <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">
            <BuildingLibraryIcon class="h-4 w-4 inline mr-1" />
            Armazém
          </label>
          <BaseSelect v-model="filters.warehouse_id">
            <option value="">Todos os Armazéns</option>
            <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
              {{ warehouse.name }}
            </option>
          </BaseSelect>
        </div>

        <!-- SORT BY -->
        <div>
          <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">
            <ArrowsUpDownIcon class="h-4 w-4 inline mr-1" />
            Ordenar Por
          </label>
          <BaseSelect v-model="filters.sort_by">
            <option value="expiry_date">Data de Validade</option>
            <option value="days_to_expiry">Dias para Vencimento</option>
            <option value="name">Nome do Reagente</option>
            <option value="current_stock">Estoque Actual</option>
          </BaseSelect>
        </div>
      </div>
    </ModuleCard>

    <!-- STATS CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-gradient-to-r from-red-900 to-red-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Vencido</p>
            <p class="text-2xl font-bold mt-1">{{ stats.expired }}</p>
          </div>
          <XCircleIcon class="h-8 w-8 opacity-50" />
        </div>
        <p class="text-xs opacity-80 mt-2">Past expiry date</p>
      </div>

      <div class="bg-gradient-to-r from-orange-900 to-orange-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Vencendo em Breve</p>
            <p class="text-2xl font-bold mt-1">{{ stats.expiring_soon }}</p>
          </div>
          <ExclamationTriangleIcon class="h-8 w-8 opacity-50" />
        </div>
        <p class="text-xs opacity-80 mt-2">Dentro de 60 dias</p>
      </div>

      <div class="bg-gradient-to-r from-yellow-900 to-yellow-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Bom</p>
            <p class="text-2xl font-bold mt-1">{{ stats.total_reagents - (stats.expired + stats.expiring_soon) }}</p>
          </div>
          <CheckCircleIcon class="h-8 w-8 opacity-50" />
        </div>
        <p class="text-xs opacity-80 mt-2">Mais de 60 dias</p>
      </div>

      <div class="bg-gradient-to-r from-blue-900 to-blue-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">Total de Reagentes</p>
            <p class="text-2xl font-bold mt-1">{{ stats.total_reagents }}</p>
          </div>
          <BeakerIcon class="h-8 w-8 opacity-50" />
        </div>
        <p class="text-xs opacity-80 mt-2">Com datas de validade</p>
      </div>
    </div>

    <!-- REAGENTS TABLE -->
    <ModuleCard class="overflow-hidden" title="Detalhes de Validade de Reagentes">
      <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
            Detalhes de Validade de Reagentes
            <span class="ml-2 text-sm font-normal text-slate-500 dark:text-slate-400">
              ({{ reagents.data.length }} itens)
            </span>
          </h2>
          <div class="flex items-center gap-2">
            <button
              @click="markAllExpired"
              class="inline-flex items-center gap-2 rounded-lg bg-red-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-800"
            >
              <ExclamationTriangleIcon class="h-4 w-4" />
              Marcar Todos como Descartados
            </button>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
          <thead class="bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-900/70">
            <tr>
              <th :class="tableHeadClass">
                Detalhes do Reagente
              </th>
              <th :class="tableHeadClass">
                Informações de Validade
              </th>
              <th :class="tableHeadClass">
                Informações de Estoque
              </th>
              <th :class="tableHeadClass">
                Estado
              </th>
              <th :class="tableHeadClass">
                Acções
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-950">
            <tr
              v-for="reagent in reagents.data"
              :key="reagent.id"
              class="transition-colors duration-150 hover:bg-blue-50/60 dark:hover:bg-blue-950/20"
            >
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div :class="[
                      'h-10 w-10 rounded-lg flex items-center justify-center',
                      getExpiryColor(reagent).bg
                    ]">
                      <BeakerIcon :class="['h-6 w-6', getExpiryColor(reagent).text]" />
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-semibold text-slate-900 dark:text-white">
                      {{ reagent.name }}
                    </div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">
                      {{ reagent.internal_code || 'Sem Código Interno' }}
                    </div>
                    <div class="text-xs text-slate-400 dark:text-slate-500">
                      {{ reagent.category?.name || 'Sem Categoria' }}
                      <span v-if="reagent.supplier" class="ml-2">
                        • {{ reagent.supplier.name }}
                      </span>
                    </div>
                    <div v-if="reagent.lot" class="text-xs text-slate-400 dark:text-slate-500">
                      Lote: {{ reagent.lot }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600 dark:text-slate-400">Data de Validade:</span>
                    <span class="font-semibold" :class="getExpiryDateColor(reagent)">
                      {{ formatDate(reagent.reagent_expiry_date) }}
                    </span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600 dark:text-slate-400">Dias para Vencimento:</span>
                    <span class="font-semibold" :class="getDaysColor(reagent.days_to_expiry)">
                      {{ reagent.days_to_expiry }}
                    </span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600 dark:text-slate-400">Data de Abertura:</span>
                    <span class="font-semibold text-slate-900 dark:text-white">
                      {{ formatDate(reagent.reagent_open_date) || 'Não aberto' }}
                    </span>
                  </div>
                  <div v-if="reagent.reagent_open_date" class="mt-2">
                    <div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                      <div
                        :class="['h-full rounded-full', getShelfLifeBarColor(reagent)]"
                        :style="{ width: getShelfLifePercentage(reagent) + '%' }"
                      ></div>
                    </div>
                    <div class="mt-1 flex justify-between text-xs text-slate-500 dark:text-slate-400">
                      <span>Aberto</span>
                      <span>{{ getShelfLifePercentage(reagent).toFixed(0) }}% usado</span>
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600 dark:text-slate-400">Total de Estoque:</span>
                    <span class="font-semibold text-blue-900 dark:text-blue-300">{{ reagent.total_stock || 0 }}</span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600 dark:text-slate-400">Armazéns:</span>
                    <span class="font-semibold text-slate-900 dark:text-white">{{ reagent.warehouse_count || 1 }}</span>
                  </div>
                  <div v-if="reagent.refrigerated" class="mt-2">
                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-500/10 dark:text-blue-200">
                      <SunIcon class="mr-1 h-3 w-3" />
                      Requer Refrigeração
                    </span>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span :class="getStatusClasses(reagent)">
                  {{ getStatusText(reagent) }}
                </span>
                <div v-if="reagent.is_expired" class="mt-2">
                  <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-500/10 dark:text-red-200">
                    <ExclamationTriangleIcon class="mr-1 h-3 w-3" />
                    Requer Descarte
                  </span>
                </div>
                <div v-if="reagent.days_to_expiry <= 30 && reagent.days_to_expiry > 0" class="mt-2">
                  <span class="inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-800 dark:bg-orange-500/10 dark:text-orange-200">
                    <ClockIcon class="mr-1 h-3 w-3" />
                    Prioridade de Uso
                  </span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <Link
                    :href="route('vap-inventory.items.show', reagent.id)"
                    class="inline-flex items-center rounded-xl bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-900 transition hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-200 dark:hover:bg-blue-500/20"
                  >
                    <EyeIcon class="h-4 w-4 mr-1" />
                    
                    Visualizar
                  </Link>
                  <Link
                    :href="route('vap-inventory.items.edit', reagent.id)"
                    class="inline-flex items-center rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                  >
                    <PencilSquareIcon class="h-4 w-4 mr-1" />
                    Modificar
                  </Link>
                  <button
                    v-if="reagent.is_expired"
                    @click="markDisposed(reagent)"
                    class="inline-flex items-center rounded-xl bg-red-50 px-3 py-1.5 text-sm font-semibold text-red-900 transition hover:bg-red-100 dark:bg-red-500/10 dark:text-red-200 dark:hover:bg-red-500/20"
                  >
                    <TrashIcon class="h-4 w-4 mr-1" />
                    Descartar
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- EMPTY STATE -->
      <div v-if="reagents.data.length === 0" class="p-12 text-center">
        <BeakerIcon class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-700" />
        <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">
          Nenhum reagente encontrado
        </h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
          Nenhum reagente com datas de validade encontrado correspondendo aos seus filtros
        </p>
        <Link
          :href="route('vap-inventory.items.index')"
          class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
        >
          <ArrowLeftIcon class="h-5 w-5" />
          
          Voltar ao Inventário
        </Link>
      </div>

      <!-- PAGINATION -->
      <div v-if="reagents.data.length > 0" class="border-t border-slate-200 px-6 py-4 dark:border-slate-800">
        <Pagination :links="reagents.links" />
      </div>
    </ModuleCard>

    <!-- UPCOMING EXPIRIES -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- EXPIRY TIMELINE -->
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
        <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">
          Linha do Tempo de Validade (Próximos 90 Dias)
        </h3>
        <div class="space-y-4">
          <div
            v-for="month in expiryTimeline"
            :key="month.month"
            class="border-l-4 border-blue-900 pl-4 py-2"
          >
            <div class="flex items-center justify-between">
              <div>
                <div class="font-medium text-slate-900 dark:text-white">{{ month.month }}</div>
                <div class="text-sm text-slate-500 dark:text-slate-400">{{ month.count }} itens vencendo em breve</div>
              </div>
              <div class="text-sm font-semibold" :class="month.color">
                {{ month.percentage }}%
              </div>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
              <div
                class="h-full rounded-full"
                :class="month.barColor"
                :style="{ width: month.percentage + '%' }"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- QUICK ACTIONS -->
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
        <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">
          Acções Rápidas
        </h3>
        <div class="space-y-3">
          <button
            @click="generateDisposalReport"
            class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white p-4 text-left transition hover:border-red-300 hover:bg-red-50/60 dark:border-slate-800 dark:bg-slate-950/50 dark:hover:border-red-500/40 dark:hover:bg-red-500/10"
          >
            <div class="flex items-center gap-3">
              <div class="rounded-lg bg-red-100 p-2">
                <DocumentTextIcon class="h-5 w-5 text-red-900" />
              </div>
              <div>
                <div class="font-medium text-slate-900 dark:text-white">Relatório de Descarte</div>
                <div class="text-sm text-slate-500 dark:text-slate-400">Gerar documentação de descarte</div>
              </div>
            </div>
            <ChevronRightIcon class="h-5 w-5 text-slate-400 dark:text-slate-500" />
          </button>

          <button
            @click="sendExpiryAlerts"
            class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white p-4 text-left transition hover:border-orange-300 hover:bg-orange-50/60 dark:border-slate-800 dark:bg-slate-950/50 dark:hover:border-orange-500/40 dark:hover:bg-orange-500/10"
          >
            <div class="flex items-center gap-3">
              <div class="rounded-lg bg-orange-100 p-2">
                <BellAlertIcon class="h-5 w-5 text-orange-900" />
              </div>
              <div>
                <div class="font-medium text-slate-900 dark:text-white">Enviar Alertas</div>
                <div class="text-sm text-slate-500 dark:text-slate-400">Notificar a equipa sobre reagentes próximos da validade</div>
              </div>
            </div>
            <ChevronRightIcon class="h-5 w-5 text-slate-400 dark:text-slate-500" />
          </button>

          <Link
            :href="route('vap-inventory.orders.create')"
            class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-green-300 hover:bg-green-50/60 dark:border-slate-800 dark:bg-slate-950/50 dark:hover:border-emerald-500/40 dark:hover:bg-emerald-500/10"
          >
            <div class="flex items-center gap-3">
              <div class="rounded-lg bg-green-100 p-2">
                <ShoppingCartIcon class="h-5 w-5 text-green-900" />
              </div>
              <div>
                <div class="font-medium text-slate-900 dark:text-white">Reabastecer Vencimento</div>
                <div class="text-sm text-slate-500 dark:text-slate-400">Criar pedidos de compra para substituições</div>
              </div>
            </div>
            <ChevronRightIcon class="h-5 w-5 text-slate-400 dark:text-slate-500" />
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import BaseSelect from '@/Components/base/BaseSelect.vue'
import ModuleCard from '@/Components/base/ModuleCard.vue'
import ModuleHero from '@/Components/base/ModuleHero.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref, computed, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
  ClockIcon,
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  CheckCircleIcon,
  TagIcon,
  BuildingLibraryIcon,
  ArrowsUpDownIcon,
  XCircleIcon,
  ExclamationTriangleIcon,
  BeakerIcon,
  EyeIcon,
  PencilSquareIcon,
  TrashIcon,
  SunIcon,
  DocumentTextIcon,
  BellAlertIcon,
  ShoppingCartIcon,
  ChevronRightIcon,
} from '@heroicons/vue/24/outline'
import Pagination from '@/Components/Pagination.vue'
import { debounce } from 'lodash'

const props = defineProps({
  reagents: Object,
  filters: Object,
  categories: Array,
  warehouses: Array,
  stats: Object,
})

const tableHeadClass = 'px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300'

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getExpiryColor = (reagent) => {
  if (reagent.is_expired) {
    return { bg: 'bg-red-100 dark:bg-red-500/10', text: 'text-red-900 dark:text-red-200' }
  } else if (reagent.days_to_expiry <= 30) {
    return { bg: 'bg-orange-100 dark:bg-orange-500/10', text: 'text-orange-900 dark:text-orange-200' }
  } else if (reagent.days_to_expiry <= 60) {
    return { bg: 'bg-yellow-100 dark:bg-amber-500/10', text: 'text-yellow-900 dark:text-amber-200' }
  } else {
    return { bg: 'bg-green-100 dark:bg-emerald-500/10', text: 'text-green-900 dark:text-emerald-200' }
  }
}

const getExpiryDateColor = (reagent) => {
  if (reagent.is_expired) return 'text-red-900 dark:text-red-200'
  if (reagent.days_to_expiry <= 30) return 'text-orange-900 dark:text-orange-200'
  if (reagent.days_to_expiry <= 60) return 'text-yellow-900 dark:text-amber-200'
  return 'text-green-900 dark:text-emerald-200'
}

const getDaysColor = (days) => {
  if (days <= 0) return 'text-red-900 dark:text-red-200'
  if (days <= 30) return 'text-orange-900 dark:text-orange-200'
  if (days <= 60) return 'text-yellow-900 dark:text-amber-200'
  return 'text-green-900 dark:text-emerald-200'
}

const getShelfLifeBarColor = (reagent) => {
  if (reagent.is_expired) return 'bg-red-900'
  const percentage = getShelfLifePercentage(reagent)
  if (percentage > 80) return 'bg-orange-900'
  if (percentage > 50) return 'bg-yellow-900'
  return 'bg-green-900'
}

const getShelfLifePercentage = (reagent) => {
  if (!reagent.reagent_open_date || !reagent.reagent_expiry_date) return 0
  
  const openDate = new Date(reagent.reagent_open_date)
  const expiryDate = new Date(reagent.reagent_expiry_date)
  const today = new Date()
  
  const totalShelfLife = expiryDate - openDate
  const usedShelfLife = today - openDate
  
  if (totalShelfLife <= 0) return 100
  return Math.min(100, (usedShelfLife / totalShelfLife) * 100)
}

const getStatusClasses = (reagent) => {
  if (reagent.is_expired) {
    return 'inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800 dark:bg-red-500/10 dark:text-red-200'
  } else if (reagent.days_to_expiry <= 30) {
    return 'inline-flex items-center rounded-full bg-orange-100 px-3 py-1 text-xs font-medium text-orange-800 dark:bg-orange-500/10 dark:text-orange-200'
  } else if (reagent.days_to_expiry <= 60) {
    return 'inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800 dark:bg-amber-500/10 dark:text-amber-200'
  } else {
    return 'inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800 dark:bg-emerald-500/10 dark:text-emerald-200'
  }
}

const getStatusText = (reagent) => {
  if (reagent.is_expired) return 'Vencido'
  if (reagent.days_to_expiry <= 30) return 'Vencendo em Breve'
  if (reagent.days_to_expiry <= 60) return 'Vencendo em Curto Prazo'
  return 'Bom'
}

const expiryTimeline = computed(() => {
  const months = []
  const today = new Date()
  
  for (let i = 0; i < 3; i++) {
    const monthDate = new Date(today.getFullYear(), today.getMonth() + i, 1)
    const monthName = monthDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
    
    // Count reagents expiring in this month
    const count = props.reagents.data.filter(reagent => {
      if (!reagent.reagent_expiry_date) return false
      const expiryDate = new Date(reagent.reagent_expiry_date)
      return expiryDate.getMonth() === monthDate.getMonth() && 
             expiryDate.getFullYear() === monthDate.getFullYear()
    }).length
    
    const percentage = Math.min(100, (count / props.reagents.data.length) * 100)
    
    let color = 'text-green-900'
    let barColor = 'bg-green-900'
    if (i === 0) {
      color = 'text-orange-900'
      barColor = 'bg-orange-900'
    } else if (i === 1) {
      color = 'text-yellow-900'
      barColor = 'bg-yellow-900'
    }
    
    months.push({
      month: monthName,
      count,
      percentage: percentage.toFixed(1),
      color,
      barColor
    })
  }
  
  return months
})

const exportReport = () => {
  const params = new URLSearchParams(props.filters)
  window.open(route('vap-inventory.reports.export', {
    report_type: 'expiry',
    format: 'pdf',
    filters: JSON.stringify(props.filters)
  }), '_blank')
}

const markAllExpired = async () => {
  if (confirm('Tem a certeza que deseja marcar todos os reagentes vencidos como descartados? Esta ação não pode ser desfeita.')) {
    try {
      await router.post(route('vap-inventory.reagents.mark-disposed'), {
        reagent_ids: props.reagents.data
          .filter(r => r.is_expired)
          .map(r => r.id)
      })
      router.reload()
    } catch (error) {
      console.error('Error marking reagents as disposed:', error)
    }
  }
}

const markDisposed = async (reagent) => {
  if (confirm(`Deseja marcar ${reagent.name} como descartado? Isso removerá ele do inventário.`)) {
    try {
      await router.post(route('vap-inventory.reagents.dispose', reagent.id))
      router.reload()
    } catch (error) {
      console.error('Error marking reagent as disposed:', error)
    }
  }
}

const generateDisposalReport = () => {
  window.open(route('vap-inventory.reports.export', {
    report_type: 'expiry',
    format: 'pdf',
    filters: JSON.stringify({ ...props.filters, status: 'expired' })
  }), '_blank')
}

const sendExpiryAlerts = async () => {
  try {
    await router.post(route('vap-inventory.reagents.send-alerts'), {
      days_threshold: 30
    })
    alert('Alertas de validade enviados com sucesso!')
  } catch (error) {
    console.error('Error sending alerts:', error)
  }
}

// Watch filters
watch(
  () => props.filters,
  debounce((value) => {
    router.get(route('vap-inventory.reagents.expiry'), value, {
      preserveState: true,
      replace: true,
    })
  }, 300),
  { deep: true }
)
</script>
