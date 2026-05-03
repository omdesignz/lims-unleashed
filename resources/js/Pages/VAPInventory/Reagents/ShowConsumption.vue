<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <BeakerIcon class="h-7 w-7 text-blue-900" />
            Registro de Consumo #{{ consumption.id }}
          </h1>
          <p class="mt-2 text-gray-600">
            Visualizar detalhes do consumo de reagente
          </p>
        </div>
        <div class="flex items-center gap-3">
          <button
            @click="goBack"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
          >
            <ArrowLeftIcon class="h-5 w-5" />
            
            Voltar
          </button>
          <button
            @click="deleteConsumption"
            class="inline-flex items-center gap-2 rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-700 shadow-sm hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
          >
            <TrashIcon class="h-5 w-5" />
            
            Excluir
          </button>
        </div>
      </div>
    </div>

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
                  <p class="text-xs text-gray-500">Recorded by: {{ consumption.user?.name || 'System' }}</p>
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
                <p class="text-xs text-gray-500">by {{ consumption.user?.name || 'System' }}</p>
              </div>
            </div>
            
            <div class="flex items-start">
              <div class="flex-shrink-0 h-6 w-6 rounded-full bg-blue-500 flex items-center justify-center">
                <CubeIcon class="h-3 w-3 text-white" />
              </div>
              <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">Estoque Atualizado</p>
                <p class="text-xs text-gray-500">{{ formatDateTime(consumption.created_at) }}</p>
                <p class="text-xs text-gray-500">Stock reduced by {{ consumption.quantity_used }}</p>
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
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
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
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

function formatDateTime(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('en-US', {
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
  if (!confirm('Are you sure you want to delete this consumption record? This will restore the stock.')) {
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
  alert('Export functionality would be implemented here')
}

function goBack() {
  router.visit(route('vap-inventory.reagents.consumption.index'))
}
</script>