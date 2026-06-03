<template>
  <div class="reagent-consumption-show-shell space-y-8" :class="commercialDocumentThemeClasses">
    <ModuleHero
      :icon="BeakerIcon"
      :title="`Registo de Consumo #${consumption.id}`"
      subtitle="Visualize o consumo de reagente, impacto em stock e rastreabilidade do registo."
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-3">
          <button
            @click="goBack"
            class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white/90 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-white dark:border-white/10 dark:bg-white/10 dark:text-white dark:hover:bg-white/15"
          >
            <ArrowLeftIcon class="h-5 w-5" />
            Voltar
          </button>
          <button
            @click="deleteConsumption"
            class="inline-flex items-center gap-2 rounded-2xl border border-red-200 bg-white/90 px-4 py-2 text-sm font-semibold text-red-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-red-50 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200 dark:hover:bg-red-500/20"
          >
            <TrashIcon class="h-5 w-5" />
            Excluir
          </button>
        </div>
      </template>
    </ModuleHero>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- CONSUMPTION DETAILS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ClipboardDocumentListIcon class="h-5 w-5 text-blue-900" />
            Detalhes do Consumo
          </h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Reagent Information -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Reagente
              </label>
              <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                  <BeakerIcon class="h-5 w-5 text-blue-900" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ consumption.reagent_name }}</p>
                  <p class="text-xs text-gray-500">{{ consumption.item?.internal_code || 'N/A' }}</p>
                </div>
              </div>
            </div>

            <!-- Quantity Used -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Quantidade Usada
              </label>
              <div class="p-3 bg-gray-50 rounded-lg">
                <p class="text-2xl font-bold text-red-600">{{ consumption.quantity_used }}</p>
                <p class="text-xs text-gray-500">{{ consumption.item?.unit?.code || 'units' }}</p>
              </div>
            </div>

            <!-- Warehouse -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Armazém
              </label>
              <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                  <BuildingStorefrontIcon class="h-5 w-5 text-green-600" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ consumption.warehouse?.name }}</p>
                  <p class="text-xs text-gray-500">{{ consumption.warehouse?.location?.name || 'N/A' }}</p>
                </div>
              </div>
            </div>

            <!-- Used By -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Usado Por
              </label>
              <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                  <UserIcon class="h-5 w-5 text-purple-600" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ consumption.used_by }}</p>
                  <p class="text-xs text-gray-500">Registado por: {{ consumption.user?.name || 'Sistema' }}</p>
                </div>
              </div>
            </div>

            <!-- Dates -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Datas
              </label>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Data de Consumo:</span>
                  <span class="font-medium text-gray-900">{{ formatDate(consumption.date) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Data de Registro:</span>
                  <span class="font-medium text-gray-900">{{ formatDateTime(consumption.created_at) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Usado Em:</span>
                  <span class="font-medium text-gray-900">{{ formatDateTime(consumption.used_at) }}</span>
                </div>
              </div>
            </div>

            <!-- Status Information -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Informações de Registro
              </label>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">ID de Registro:</span>
                  <span class="font-medium text-blue-900">#{{ consumption.id }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Estado:</span>
                  <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                    Registrado
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Remarks -->
          <div v-if="consumption.remarks" class="mt-6 space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Observações
            </label>
            <div class="p-3 bg-gray-50 rounded-lg">
              <p class="text-sm text-gray-700 whitespace-pre-line">{{ consumption.remarks }}</p>
            </div>
          </div>
        </div>

        <!-- REAGENT INFORMATION -->
        <div v-if="consumption.item" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <InformationCircleIcon class="h-5 w-5 text-blue-900" />
            Informações do Reagente
          </h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <div class="p-4 bg-gray-50 rounded-lg">
                <div class="space-y-2">
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Nome:</span>
                    <span class="font-medium text-gray-900">{{ consumption.item.name }}</span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Código Interno:</span>
                    <span class="font-medium text-gray-900">{{ consumption.item.internalcode }}</span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Categoria:</span>
                    <span class="font-medium text-gray-900">{{ consumption.item.category?.name || 'N/A' }}</span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Unidade:</span>
                    <span class="font-medium text-gray-900">{{ consumption.item.unit?.code || 'N/A' }}</span>
                  </div>
                  <div v-if="consumption.item.reagent_expiry_date" class="flex justify-between text-sm">
                    <span class="text-gray-600">Data de Validade:</span>
                    <span :class="[
                      'font-medium',
                      isReagentExpired ? 'text-red-600' : 'text-gray-900'
                    ]">
                      {{ formatDate(consumption.item.reagent_expiry_date) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div class="space-y-2">
              <div class="p-4 bg-gray-50 rounded-lg">
                <div class="space-y-2">
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Marca:</span>
                    <span class="font-medium text-gray-900">{{ consumption.item.brand || 'N/A' }}</span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Modelo:</span>
                    <span class="font-medium text-gray-900">{{ consumption.item.model || 'N/A' }}</span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Número de Série:</span>
                    <span class="font-medium text-gray-900">{{ consumption.item.serial_number || 'N/A' }}</span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Fornecedor:</span>
                    <span class="font-medium text-gray-900">{{ consumption.item.supplier?.name || 'N/A' }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6">
        <!-- ACTIONS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Acções
          </h3>
          <div class="space-y-3">
            <!-- Edit Button -->
            <a 
              :href="route('vap-inventory.items.show', consumption.reagent_id)"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
            >
              <PencilIcon class="h-5 w-5" />
            
              Visualizar Detalhes do Reagente
            </a>

            <!-- Print Button -->
            <button 
              @click="printConsumption"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
            >
              <PrinterIcon class="h-5 w-5" />
              
              Imprimir Registro
            </button>

            <!-- Export Button -->
            <button 
              @click="exportConsumption"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
            >
              <ArrowDownTrayIcon class="h-5 w-5" />
              
              Exportar como PDF
            </button>
          </div>
        </div>

        <!-- RECORD TIMELINE -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ClockIcon class="h-5 w-5 text-blue-900" />
            Linha do Tempo do Registro
          </h3>
          <div class="space-y-4">
            <div class="flex items-start">
              <div class="flex-shrink-0 h-6 w-6 rounded-full bg-green-500 flex items-center justify-center">
                <CheckIcon class="h-3 w-3 text-white" />
              </div>
              <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">Registro de Consumo</p>
                <p class="text-xs text-gray-500">{{ formatDateTime(consumption.created_at) }}</p>
                <p class="text-xs text-gray-500">por {{ consumption.user?.name || 'Sistema' }}</p>
              </div>
            </div>
            
            <div class="flex items-start">
              <div class="flex-shrink-0 h-6 w-6 rounded-full bg-blue-500 flex items-center justify-center">
                <CubeIcon class="h-3 w-3 text-white" />
              </div>
              <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">Estoque Atualizado</p>
                <p class="text-xs text-gray-500">{{ formatDateTime(consumption.created_at) }}</p>
                <p class="text-xs text-gray-500">Stock reduzido em {{ consumption.quantity_used }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- STOCK INFORMATION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Impacto no Estoque
          </h3>
          <div class="space-y-3">
            <div>
              <p class="text-sm font-medium text-gray-600">Quantidade Consumida</p>
              <p class="mt-1 text-lg font-bold text-red-600">
                {{ consumption.quantity_used }}
              </p>
            </div>
            <div v-if="consumption.item?.inventory">
              <p class="text-sm font-medium text-gray-600">Estoque Actual no Armazém</p>
              <p class="mt-1 text-lg font-bold text-gray-900">
                {{ getCurrentStockInWarehouse() }}
              </p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600">Impacto</p>
              <p class="text-xs text-gray-500">
                Este consumo reduziu o estoque no {{ consumption.warehouse?.name }} por {{ consumption.quantity_used }} unidades.
              </p>
            </div>
          </div>
        </div>

        <!-- WARNING CARD -->
        <div v-if="isReagentExpired" class="bg-yellow-50 rounded-xl border border-yellow-200 p-6">
          <div class="flex">
            <ExclamationTriangleIcon class="h-5 w-5 text-yellow-600 flex-shrink-0" />
            <div class="ml-3">
              <h3 class="text-sm font-medium text-yellow-800">Reagente Vencido</h3>
              <div class="mt-2 text-sm text-yellow-700">
                <p>Este reagente venceu em {{ formatDate(consumption.item.reagent_expiry_date) }}</p>
                <p class="mt-1">A consumo de reagentes vencidos deve ser documentado separadamente.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { router } from '@inertiajs/vue3'
import ModuleHero from '@/Components/base/ModuleHero.vue'
import {
  BeakerIcon,
  ArrowLeftIcon,
  TrashIcon,
  ClipboardDocumentListIcon,
  BuildingStorefrontIcon,
  UserIcon,
  InformationCircleIcon,
  PencilIcon,
  PrinterIcon,
  ArrowDownTrayIcon,
  ClockIcon,
  CheckIcon,
  CubeIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  consumption: {
    type: Object,
    required: true
  }
})

const isReagentExpired = computed(() => {
  if (!props.consumption.item || !props.consumption.item.reagent_expiry_date) return false
  
  const expiryDate = new Date(props.consumption.item.reagent_expiry_date)
  return expiryDate < new Date()
})

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

function formatDateTime(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function getCurrentStockInWarehouse() {
  if (!props.consumption.item || !props.consumption.item.inventory) return 'N/A'
  
  const inventory = props.consumption.item.inventory.find(
    inv => inv.warehouse_id === props.consumption.warehouse_id
  )
  
  return inventory ? inventory.qty_available : 'N/A'
}

function deleteConsumption() {
  if (!confirm('Tem a certeza de que pretende eliminar este registo de consumo? O stock será restaurado.')) {
    return
  }

  router.delete(route('vap-inventory.reagents.consumption.destroy', props.consumption.id), {
    preserveScroll: true,
    onSuccess: () => {
      router.visit(route('vap-inventory.reagents.consumption.index'))
    }
  })
}

function printConsumption() {
  window.print()
}

function exportConsumption() {
  // Implement export functionality
  alert('A exportação deste registo ainda não está configurada.')
}

function goBack() {
  router.visit(route('vap-inventory.reagents.consumption.index'))
}
</script>

<style scoped>
.reagent-consumption-show-shell :deep(.bg-white.rounded-xl),
.reagent-consumption-show-shell :deep(.rounded-xl.border.border-gray-200) {
  border-color: rgb(226 232 240);
  border-radius: 1.5rem;
  background: rgb(255 255 255);
  box-shadow: 0 1px 2px rgb(15 23 42 / 0.06);
}

.reagent-consumption-show-shell :deep(.bg-gray-50) {
  border-color: rgb(226 232 240);
  background: rgb(248 250 252 / 0.84);
}

.reagent-consumption-show-shell :deep(.text-blue-900) {
  color: rgb(var(--color-primary-900, 30 58 138));
}

.reagent-consumption-show-shell :deep(.bg-blue-100) {
  background-color: rgb(var(--color-primary-100, 219 234 254));
}

.reagent-consumption-show-shell :deep(.border-gray-200),
.reagent-consumption-show-shell :deep(.border-gray-300) {
  border-color: rgb(226 232 240);
}

.reagent-consumption-show-shell :deep(.hover\:bg-gray-50:hover) {
  background: rgb(var(--color-primary-50, 239 246 255) / 0.58);
}

:global(.dark) .reagent-consumption-show-shell :deep(.bg-white.rounded-xl),
:global(.dark) .reagent-consumption-show-shell :deep(.rounded-xl.border.border-gray-200) {
  border-color: rgb(30 41 59);
  background:
    radial-gradient(circle at top right, rgb(var(--color-primary-500, 59 130 246) / 0.1), transparent 30%),
    rgb(2 6 23);
}

:global(.dark) .reagent-consumption-show-shell :deep(.bg-white) {
  background: rgb(2 6 23);
}

:global(.dark) .reagent-consumption-show-shell :deep(.bg-gray-50),
:global(.dark) .reagent-consumption-show-shell :deep(.hover\:bg-gray-50:hover) {
  border-color: rgb(51 65 85);
  background: rgb(15 23 42 / 0.72);
}

:global(.dark) .reagent-consumption-show-shell :deep(.bg-yellow-50) {
  border-color: rgb(245 158 11 / 0.32);
  background: rgb(245 158 11 / 0.1);
}

:global(.dark) .reagent-consumption-show-shell :deep(.bg-blue-100),
:global(.dark) .reagent-consumption-show-shell :deep(.bg-green-100),
:global(.dark) .reagent-consumption-show-shell :deep(.bg-purple-100) {
  background-color: rgb(var(--color-primary-500, 59 130 246) / 0.1);
}

:global(.dark) .reagent-consumption-show-shell :deep(.text-gray-900),
:global(.dark) .reagent-consumption-show-shell :deep(.text-gray-800),
:global(.dark) .reagent-consumption-show-shell :deep(.text-gray-700) {
  color: rgb(226 232 240);
}

:global(.dark) .reagent-consumption-show-shell :deep(.text-gray-600),
:global(.dark) .reagent-consumption-show-shell :deep(.text-gray-500),
:global(.dark) .reagent-consumption-show-shell :deep(.text-gray-400) {
  color: rgb(148 163 184);
}

:global(.dark) .reagent-consumption-show-shell :deep(.border-gray-200),
:global(.dark) .reagent-consumption-show-shell :deep(.border-gray-300) {
  border-color: rgb(30 41 59);
}

:global(.dark) .reagent-consumption-show-shell :deep(.text-blue-900) {
  color: rgb(var(--color-primary-200, 191 219 254));
}

:global(.dark) .reagent-consumption-show-shell :deep(.text-green-600) {
  color: rgb(110 231 183);
}

:global(.dark) .reagent-consumption-show-shell :deep(.text-red-600),
:global(.dark) .reagent-consumption-show-shell :deep(.text-red-700) {
  color: rgb(252 165 165);
}

:global(.dark) .reagent-consumption-show-shell :deep(.text-yellow-800),
:global(.dark) .reagent-consumption-show-shell :deep(.text-yellow-700),
:global(.dark) .reagent-consumption-show-shell :deep(.text-yellow-600) {
  color: rgb(253 230 138);
}
</style>
