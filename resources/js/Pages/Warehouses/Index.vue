<script setup>
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import RecordsTable from '@/Components/records-table.vue'
import SlideOver from '@/Components/slide-over.vue'
import { usePermission } from '@/Composables/usePermissions'
import WarehouseComponent from '@/Pages/Warehouses/warehouse-component.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import {
  Eye as EyeIcon,
  MapPin as MapPinIcon,
  Users as UsersIcon,
} from '@lucide/vue'
import { Link, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

defineOptions({ layout: Layout })

const { hasPermission } = usePermission()

const props = defineProps({
  record: { type: Object, required: true },
  fields: { type: Array, default: () => [] },
  model: String,
  abilities: { type: Array, default: () => [] },
  query: { type: Object, default: () => ({}) },
  slideOverEdit: { type: Boolean, default: true },
})

const actions = [
  { id: null, label: 'gestlab.actions.bulk_actions_text' },
  { id: 'delete', label: 'gestlab.actions.delete' },
  { id: 'restore', label: 'gestlab.actions.restore' },
]

const registryFields = [
  { name: 'gestlab.general.labels.warehouses.customer_id', value: 'customer' },
  { name: 'gestlab.general.labels.warehouses.name', value: 'name' },
  { name: 'gestlab.general.labels.warehouses.primary_phone', value: 'primary_phone' },
  { name: 'gestlab.general.labels.warehouses.email', value: 'email' },
  { name: 'gestlab.general.labels.warehouses.focal_point', value: 'focal_point' },
]

const editorOpen = ref(false)
const editorKey = ref(0)
const selectedSite = ref(null)
const actionId = ref(null)
const showBulkConfirmation = ref(false)

const pageSites = computed(() => props.record?.data ?? [])
const totalSites = computed(() => props.record?.meta?.total ?? pageSites.value.length)
const primarySites = computed(() => pageSites.value.filter((site) => site.is_primary && !site.deleted).length)
const portalReadySites = computed(() => pageSites.value.filter((site) => site.has_password && !site.deleted).length)
const activeSites = computed(() => pageSites.value.filter((site) => !site.deleted).length)
const selectedRecordIds = computed(() => pageSites.value.filter((site) => site.selected).map((site) => site.id))

const editorTitle = computed(() => selectedSite.value?.id ? 'Editar local operacional' : 'Novo local operacional')
const editorDescription = computed(() => selectedSite.value?.id
  ? `${selectedSite.value.customer || 'Cliente'} · ${selectedSite.value.name || selectedSite.value.code || `Local #${selectedSite.value.id}`}`
  : 'Registe a conta cliente, morada, canais operacionais e ponto focal.')

const confirmationTitle = computed(() => actionId.value === 'restore' ? 'Restaurar locais seleccionados?' : 'Arquivar locais seleccionados?')
const confirmationDescription = computed(() => actionId.value === 'restore'
  ? 'Os locais voltam a ficar disponíveis nas operações e pesquisas do laboratório.'
  : 'Os locais ficam inactivos, mas o histórico e a rastreabilidade são preservados.')

function emptySite() {
  return {
    id: null,
    name: '',
    code: '',
    description: '',
    email: '',
    invoicing_email: '',
    primary_phone: '',
    alternative_phone: '',
    nif: '',
    address: '',
    municipality: '',
    province: '',
    focal_point: '',
    focal_point_email: '',
    focal_point_contact: '',
    customer_id: null,
  }
}

function openCreateEditor() {
  selectedSite.value = emptySite()
  editorKey.value += 1
  editorOpen.value = true
}

function openEditEditor(site) {
  selectedSite.value = { ...site }
  editorKey.value += 1
  editorOpen.value = true
}

function closeEditor() {
  editorOpen.value = false
  selectedSite.value = null
}

function prepareBulkAction(selectedActionId) {
  actionId.value = selectedActionId
  showBulkConfirmation.value = true
}

function executeBulkAction() {
  if (!selectedRecordIds.value.length || !['delete', 'restore'].includes(actionId.value)) {
    showBulkConfirmation.value = false
    return
  }

  router.get(route(`warehouses.${actionId.value}`), { recordIds: selectedRecordIds.value }, {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      showBulkConfirmation.value = false
      actionId.value = null
    },
  })
}
</script>

<template>
  <div class="pl-page space-y-6">
    <PageHeader title="Locais operacionais" lede="Registo controlado dos endereços usados na recepcao, recolha, facturação e acesso ao portal do cliente.">
      <template #actions>
        <Link :href="route('customers.index')" class="ds-button ds-button-secondary"><UsersIcon class="h-4 w-4" />Ver clientes</Link>
        <button v-if="hasPermission('add_warehouses')" type="button" class="ds-button ds-button-primary" @click="openCreateEditor"><MapPinIcon class="h-4 w-4" />Novo local</button>
      </template>
    </PageHeader>

    <dl class="pl-cells">
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Total registado</dt>
        <dd class="pl-cell-value">{{ totalSites }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Activos nesta página</dt>
        <dd class="pl-cell-value">{{ activeSites }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Locais principais</dt>
        <dd class="pl-cell-value">{{ primarySites }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Portal configurado</dt>
        <dd class="pl-cell-value">{{ portalReadySites }}</dd>
      </div>
    </dl>

    <RecordsTable
      :record="record"
      :model="model"
      :abilities="abilities"
      :fields="registryFields"
      :slide-over-edit="slideOverEdit"
      :query="query"
      :actions="actions"
      @execute-action="prepareBulkAction"
      @create-record="openCreateEditor"
      @slideover-on="openEditEditor"
    >
      <template #actions="{ data }">
        <Link :href="route('warehouses.show', { warehouse: data.id })" class="ds-icon-button" :aria-label="`Abrir dossier de ${data.name || data.customer || 'local'}`" title="Abrir dossier">
          <EyeIcon class="h-4 w-4" />
        </Link>
      </template>
    </RecordsTable>

    <SlideOver v-if="editorOpen && selectedSite" :title="editorTitle" :description="editorDescription" @close="closeEditor">
      <template #content>
        <div class="p-4 sm:p-6">
          <WarehouseComponent
            :key="editorKey"
            :record="selectedSite"
            :primary_warehouse="selectedSite.is_primary ? selectedSite.id : null"
            show-customer-selector
            :allow-delete="false"
            :manage-primary="false"
            @saved="closeEditor"
          />
        </div>
      </template>
    </SlideOver>

    <ConfirmDialog
      v-if="showBulkConfirmation"
      :title="confirmationTitle"
      :description="confirmationDescription"
      confirm="Confirmar"
      cancel="Cancelar"
      @canceled="showBulkConfirmation = false"
      @close="showBulkConfirmation = false"
      @confirmed="executeBulkAction"
    />
  </div>
</template>
