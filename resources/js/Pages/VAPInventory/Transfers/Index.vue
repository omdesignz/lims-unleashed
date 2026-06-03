<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <ModuleHero
      eyebrow="Multi-location stock"
      title="Transferências de Estoque"
      description="Monitore e gerencie movimentos de estoque entre armazéns, com receção, cancelamento e rastreabilidade operacional."
    >
      <template #actions>
        <div class="flex items-center gap-3">
          <Link
            :href="route('vap-inventory.transfers.create')"
            class="inline-flex items-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-400"
          >
            <PlusCircleIcon class="h-5 w-5" />
            Nova Transferência
          </Link>
        </div>
      </template>
    </ModuleHero>

    <!-- FILTERS AND STATS -->
    <div class="grid grid-cols-1 gap-8 xl:grid-cols-3">
      <div class="lg:col-span-2 space-y-6">
        <!-- FILTERS -->
        <ModuleCard title="Filtros de transferência" description="Filtre por estado, armazém de origem/destino e item antes de receber ou cancelar movimentos.">
          <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
            <!-- STATUS FILTER -->
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">
                <CheckCircleIcon class="h-4 w-4 inline mr-1" />
                Estado
              </label>
              <BaseSelect v-model="filters.status">
                <option value="">Todos os Estados</option>
                <option value="pending">Pendente</option>
                <option value="sent">Enviado</option>
                <option value="received">Recebido</option>
              </BaseSelect>
            </div>

            <!-- SOURCE WAREHOUSE -->
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">
                <ArrowUpTrayIcon class="h-4 w-4 inline mr-1" />
                Armazém de Origem
              </label>
              <BaseSelect v-model="filters.source_id">
                <option value="">Todos os Armazéns</option>
                <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                  {{ warehouse.name }}
                </option>
              </BaseSelect>
            </div>

            <!-- DESTINATION WAREHOUSE -->
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">
                <ArrowDownTrayIcon class="h-4 w-4 inline mr-1" />
                Armazém de Destino
              </label>
              <BaseSelect v-model="filters.destination_id">
                <option value="">Todos os Armazéns</option>
                <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                  {{ warehouse.name }}
                </option>
              </BaseSelect>
            </div>

            <!-- SEARCH -->
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">
                <MagnifyingGlassIcon class="h-4 w-4 inline mr-1" />
                Pesquisar
              </label>
              <BaseInput v-model="filters.search" placeholder="Pesquisar itens..." />
            </div>
          </div>
        </ModuleCard>

        <!-- TRANSFERS TABLE -->
        <ModuleCard class="overflow-hidden" title="Movimentos entre armazéns">
          <div class="grid gap-4 p-4 md:hidden">
            <article
              v-for="transfer in transfers.data"
              :key="`mobile-${transfer.id}`"
              class="rounded-xl border border-gray-200 p-4 shadow-sm"
            >
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-sm font-semibold text-gray-900">{{ transfer.item?.name }}</p>
                  <p class="mt-1 text-xs text-gray-500">{{ transfer.source?.name }} → {{ transfer.destination?.name }}</p>
                </div>
                <span :class="getStatusClasses(transfer)">{{ getStatusText(transfer) }}</span>
              </div>

              <dl class="mt-4 grid grid-cols-1 gap-2 text-sm">
                <div class="rounded-lg bg-gray-50 px-3 py-2">
                  <dt class="text-xs uppercase tracking-wide text-gray-500">Quantidade</dt>
                  <dd class="mt-1 text-gray-900">{{ transfer.qty }} {{ transfer.item?.unit?.code || 'unidades' }}</dd>
                </div>
                <div class="grid grid-cols-2 gap-2">
                  <div class="rounded-lg bg-gray-50 px-3 py-2">
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Envio</dt>
                    <dd class="mt-1 text-gray-900">{{ formatDate(transfer.sent_date) || 'Por enviar' }}</dd>
                  </div>
                  <div class="rounded-lg bg-gray-50 px-3 py-2">
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Recepção</dt>
                    <dd class="mt-1 text-gray-900">{{ formatDate(transfer.received_date) || 'Pendente' }}</dd>
                  </div>
                </div>
                <div v-if="transfer.obs" class="rounded-lg bg-gray-50 px-3 py-2">
                  <dt class="text-xs uppercase tracking-wide text-gray-500">Observações</dt>
                  <dd class="mt-1 text-gray-900">{{ transfer.obs }}</dd>
                </div>
              </dl>

              <div class="mt-4 flex flex-wrap gap-2">
                <Link :href="route('vap-inventory.transfers.show', transfer.id)" class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-2 text-sm font-medium text-blue-900 hover:bg-blue-100">Abrir</Link>
                <button v-if="!transfer.received_date && transfer.sent_date" @click="receiveTransfer(transfer)" class="inline-flex items-center rounded-lg bg-green-50 px-3 py-2 text-sm font-medium text-green-900 hover:bg-green-100">Receber</button>
                <button v-if="!transfer.received_date" @click="cancelTransfer(transfer)" class="inline-flex items-center rounded-lg bg-red-50 px-3 py-2 text-sm font-medium text-red-900 hover:bg-red-100">Cancelar</button>
              </div>
            </article>
          </div>
          <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gradient-to-r from-blue-50 to-blue-100">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-blue-900 uppercase tracking-wider">
                    Detalhes da transferência
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-blue-900 uppercase tracking-wider">
                    Quantidade
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-blue-900 uppercase tracking-wider">
                    Datas
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-blue-900 uppercase tracking-wider">
                    Estado
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-blue-900 uppercase tracking-wider">
                    Acções
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr 
                  v-for="transfer in transfers.data"
                  :key="transfer.id"
                  class="hover:bg-blue-50/50 transition-colors duration-150"
                >
                  <td class="px-6 py-4">
                    <div>
                      <div class="text-sm font-semibold text-gray-900">
                        {{ transfer.item?.name }}
                      </div>
                      <div class="text-sm text-gray-500">
                        <div class="flex items-center gap-2 mt-1">
                          <ArrowUpTrayIcon class="h-4 w-4" />
                          <span>{{ transfer.source?.name }}</span>
                          <ArrowRightIcon class="h-4 w-4 mx-1" />
                          <ArrowDownTrayIcon class="h-4 w-4" />
                          <span>{{ transfer.destination?.name }}</span>
                        </div>
                        <div v-if="transfer.obs" class="mt-1 text-xs text-gray-400">
                          {{ transfer.obs.substring(0, 50) }}...
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="text-2xl font-bold text-blue-900">
                      {{ transfer.qty }}
                    </div>
                    <div class="text-xs text-gray-500">
                      {{ transfer.item?.unit?.code || 'unidades' }}
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="space-y-1">
                      <div class="text-sm">
                        <span class="font-medium">Enviado:</span>
                        <span class="ml-1">{{ formatDate(transfer.sent_date) || 'Não enviado' }}</span>
                      </div>
                      <div class="text-sm">
                        <span class="font-medium">Recebido:</span>
                        <span class="ml-1">{{ formatDate(transfer.received_date) || 'Pendente' }}</span>
                      </div>
                      <div v-if="transfer.expected_date" class="text-xs text-gray-500">
                        Previsto: {{ formatDate(transfer.expected_date) }}
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <span :class="getStatusClasses(transfer)">
                      {{ getStatusText(transfer) }}
                    </span>
                    <div v-if="isOverdue(transfer)" class="mt-1">
                      <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">
                        <ExclamationTriangleIcon class="mr-1 h-3 w-3" />
                        Vencido
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-2">
                      <Link
                        :href="route('vap-inventory.transfers.show', transfer.id)"
                        class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-900 hover:bg-blue-100"
                      >
                        <EyeIcon class="h-4 w-4 mr-1" />
                        
                        Visualizar
                      </Link>
                      <button
                        v-if="!transfer.received_date && transfer.sent_date"
                        @click="receiveTransfer(transfer)"
                        class="inline-flex items-center rounded-lg bg-green-50 px-3 py-1.5 text-sm font-medium text-green-900 hover:bg-green-100"
                      >
                        <CheckCircleIcon class="h-4 w-4 mr-1" />
                        Receber
                      </button>
                      <button
                        v-if="!transfer.received_date"
                        @click="cancelTransfer(transfer)"
                        class="inline-flex items-center rounded-lg bg-red-50 px-3 py-1.5 text-sm font-medium text-red-900 hover:bg-red-100"
                      >
                        <XCircleIcon class="h-4 w-4 mr-1" />
                        Cancelar
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- EMPTY STATE -->
          <div v-if="transfers.data.length === 0" class="p-12 text-center">
            <ArrowsRightLeftIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              Nenhuma transferência encontrada
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              Registe a primeira transferência para acompanhar movimentos entre armazéns
            </p>
            <Link
              :href="route('vap-inventory.transfers.create')"
              class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
            >
              <PlusCircleIcon class="h-5 w-5" />
              Registar transferência
            </Link>
          </div>

          <!-- PAGINATION -->
          <div v-if="transfers.data.length > 0" class="border-t border-gray-200 px-6 py-4">
            <Pagination :links="transfers.links" :from="transfers.from" :to="transfers.to" :total="transfers.total" :current_page="transfers.current_page" :last_page="transfers.last_page" />
          </div>
        </ModuleCard>
      </div>

      <!-- RIGHT COLUMN - STATS -->
      <div class="space-y-6">
        <!-- STATS CARD -->
        <ModuleCard title="Indicadores de transferência">
          <div class="space-y-4">
            <div class="bg-gradient-to-r from-blue-50 to-white rounded-lg border border-blue-100 p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-600">Transferências pendentes</p>
                  <p class="text-2xl font-bold text-blue-900">{{ stats.pending_transfers }}</p>
                </div>
                <ClockIcon class="h-8 w-8 text-blue-900/20" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="bg-gradient-to-r from-green-50 to-white rounded-lg border border-green-100 p-4">
                <p class="text-sm font-medium text-gray-600">Enviadas hoje</p>
                <p class="text-xl font-bold text-green-900">{{ stats.sent_today }}</p>
              </div>
              <div class="bg-gradient-to-r from-purple-50 to-white rounded-lg border border-purple-100 p-4">
                <p class="text-sm font-medium text-gray-600">Recebidas hoje</p>
                <p class="text-xl font-bold text-purple-900">{{ stats.received_today }}</p>
              </div>
            </div>

            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Total de transferências</span>
                <span class="font-semibold text-blue-900">{{ stats.total_transfers }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Estado operacional</span>
                <span class="font-semibold text-green-900">{{ stats.pending_transfers > 0 ? 'Em acompanhamento' : 'Estável' }}</span>
              </div>
            </div>
          </div>
        </ModuleCard>

        <!-- QUICK ACTIONS -->
        <ModuleCard title="Acções rápidas">
          <div class="space-y-3">
            <Link
              :href="route('vap-inventory.transfers.create')"
              class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 hover:bg-gray-50 transition-colors"
            >
              <div class="flex items-center gap-3">
                <div class="rounded-lg bg-blue-100 p-2">
                  <PlusCircleIcon class="h-5 w-5 text-blue-900" />
                </div>
                <div>
                  <div class="font-medium text-gray-900">Nova transferência</div>
                  <div class="text-sm text-gray-500">Mover stock entre armazéns</div>
                </div>
              </div>
              <ChevronRightIcon class="h-5 w-5 text-gray-400" />
            </Link>

            <Link
              :href="route('vap-inventory.transfers.create')"
              class="w-full flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 hover:bg-gray-50 transition-colors"
            >
              <div class="flex items-center gap-3">
                <div class="rounded-lg bg-green-100 p-2">
                  <DocumentDuplicateIcon class="h-5 w-5 text-green-900" />
                </div>
                <div>
                  <div class="font-medium text-gray-900">Nova transferência guiada</div>
                  <div class="text-sm text-gray-500">Preparar a próxima movimentação com contexto</div>
                </div>
              </div>
              <ChevronRightIcon class="h-5 w-5 text-gray-400" />
            </Link>

            <Link
              :href="route('vap-inventory.items.index')"
              class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 hover:bg-gray-50 transition-colors"
            >
              <div class="flex items-center gap-3">
                <div class="rounded-lg bg-purple-100 p-2">
                  <CubeIcon class="h-5 w-5 text-purple-900" />
                </div>
                <div>
                  <div class="font-medium text-gray-900">Abrir inventário</div>
                  <div class="text-sm text-gray-500">Consultar níveis de stock e disponibilidade</div>
                  
                </div>
              </div>
              <ChevronRightIcon class="h-5 w-5 text-gray-400" />
            </Link>
          </div>
        </ModuleCard>
      </div>
    </div>

    <!-- RECEIVE TRANSFER MODAL -->
    <ConfirmationModal
      :show="showReceiveModal"
      @close="showReceiveModal = false"
      @confirm="confirmReceive"
    >
      <template #title>
        Receber Transferência
      </template>
      <template #content>
        <div class="space-y-4">
          <div class="bg-blue-50 p-4 rounded-lg">
            <div class="text-sm font-medium text-blue-900">Detalhes da Transferência</div>
            <div class="mt-2 space-y-1 text-sm text-gray-600">
              <div>Item: {{ selectedTransfer?.item?.name }}</div>
              <div>Quantidade: {{ selectedTransfer?.qty }} {{ selectedTransfer?.item?.unit?.code || 'unidades' }}</div>
              <div>De: {{ selectedTransfer?.source?.name }}</div>
              <div>Para: {{ selectedTransfer?.destination?.name }}</div>
            </div>
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Quantidade Actual Recebida
              <span class="text-red-500">*</span>
            </label>
            <input
              v-model="receiveForm.actual_qty"
              type="number"
              :min="1"
              :max="selectedTransfer?.qty"
              required
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
              placeholder="Digite a quantidade actual"
            />
            <p class="text-xs text-gray-500">
              Máximo: {{ selectedTransfer?.qty }} unidades
            </p>
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Data de Recebimento
              <span class="text-red-500">*</span>
            </label>
            <input
              v-model="receiveForm.received_date"
              type="date"
              required
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
            />
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Observações
            </label>
            <textarea
              v-model="receiveForm.notes"
              rows="3"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
              placeholder="Qualquer observação sobre o recebimento..."
            ></textarea>
          </div>
        </div>
      </template>
      <template #confirmButton>
        <button
          type="button"
          :disabled="receiving"
          :class="[
            'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm',
            receiving
              ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
              : 'bg-gradient-to-r from-green-900 to-green-800 text-white hover:from-green-800 hover:to-green-700'
          ]"
          @click="confirmReceive"
        >
          <CheckCircleIcon class="h-4 w-4" />
          {{ receiving ? 'Recebendo...' : 'Confirmar Recebimento' }}
        </button>
      </template>
    </ConfirmationModal>

    <!-- CANCEL TRANSFER MODAL -->
    <ConfirmationModal
      :show="showCancelModal"
      @close="showCancelModal = false"
      @confirm="confirmCancel"
    >
      <template #title>
        Cancelar Transferência
      </template>
      <template #content>
        <div class="space-y-4">
          <div class="bg-red-50 p-4 rounded-lg">
            <div class="text-sm font-medium text-red-900">Aviso</div>
            <p class="mt-1 text-sm text-red-700">
              Cancelando esta transferência irá devolver {{ selectedTransfer?.qty }} unidades para
              {{ selectedTransfer?.source?.name }}. Esta acção não pode ser desfeita.
            </p>
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Motivo do Cancelamento
              <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="cancelForm.reason"
              rows="3"
              required
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
              placeholder="Por que está cancelando esta transferência?"
            ></textarea>
          </div>
        </div>
      </template>
      <template #confirmButton>
        <button
          type="button"
          :disabled="cancelling"
          :class="[
            'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm',
            cancelling
              ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
              : 'bg-gradient-to-r from-red-900 to-red-800 text-white hover:from-red-800 hover:to-red-700'
          ]"
          @click="confirmCancel"
        >
          <XCircleIcon class="h-4 w-4" />
          {{ cancelling ? 'Cancelando...' : 'Cancelar Transferência' }}
        </button>
      </template>
    </ConfirmationModal>
  </div>
</template>

<script setup>
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import ModuleCard from '@/Components/base/ModuleCard.vue'
import ModuleHero from '@/Components/base/ModuleHero.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
  ArrowsRightLeftIcon,
  PlusCircleIcon,
  CheckCircleIcon,
  MagnifyingGlassIcon,
  ArrowUpTrayIcon,
  ArrowDownTrayIcon,
  ArrowRightIcon,
  ExclamationTriangleIcon,
  EyeIcon,
  XCircleIcon,
  ChartBarIcon,
  ClockIcon,
  DocumentDuplicateIcon,
  CubeIcon,
  ChevronRightIcon,
} from '@heroicons/vue/24/outline'
import Pagination from '@/Components/Pagination.vue'
import ConfirmationModal from '@/Components/confirm-dialog.vue'
import { debounce } from 'lodash'

const props = defineProps({
  transfers: Object,
  filters: Object,
  warehouses: Array,
  stats: Object,
})

const showReceiveModal = ref(false)
const showCancelModal = ref(false)
const selectedTransfer = ref(null)
const receiving = ref(false)
const cancelling = ref(false)

const receiveForm = ref({
  actual_qty: '',
  received_date: '',
  notes: '',
})

const cancelForm = ref({
  reason: '',
})

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getStatusClasses = (transfer) => {
  if (transfer.received_date) {
    return 'inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800'
  } else if (transfer.sent_date) {
    return 'inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800'
  } else {
    return 'inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-800'
  }
}

const getStatusText = (transfer) => {
  if (transfer.received_date) return 'Recebido'
  if (transfer.sent_date) return 'Em Trânsito'
  return 'Pendente'
}

const isOverdue = (transfer) => {
  if (transfer.received_date || !transfer.expected_date) return false
  return new Date(transfer.expected_date) < new Date()
}

const receiveTransfer = (transfer) => {
  selectedTransfer.value = transfer
  receiveForm.value = {
    actual_qty: transfer.qty,
    received_date: new Date().toISOString().split('T')[0],
    notes: '',
  }
  showReceiveModal.value = true
}

const confirmReceive = async () => {
  if (!receiveForm.value.actual_qty || !receiveForm.value.received_date) {
    return
  }

  receiving.value = true
  try {
    await router.post(route('vap-inventory.transfers.receive', selectedTransfer.value.id), receiveForm.value)
    showReceiveModal.value = false
    selectedTransfer.value = null
  } catch (error) {
    console.error('Error receiving transfer:', error)
  } finally {
    receiving.value = false
  }
}

const cancelTransfer = (transfer) => {
  selectedTransfer.value = transfer
  cancelForm.value = { reason: '' }
  showCancelModal.value = true
}

const confirmCancel = async () => {
  if (!cancelForm.value.reason) return

  cancelling.value = true
  try {
    await router.post(route('vap-inventory.transfers.cancel', selectedTransfer.value.id), cancelForm.value)
    showCancelModal.value = false
    selectedTransfer.value = null
  } catch (error) {
    console.error('Error cancelling transfer:', error)
  } finally {
    cancelling.value = false
  }
}

// Watch filters for updates
watch(
  () => props.filters,
  debounce((value) => {
    router.get(route('vap-inventory.transfers.index'), value, {
      preserveState: true,
      replace: true,
    })
  }, 300),
  { deep: true }
)
</script>
