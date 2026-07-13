<script setup>
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import RecordsTable from '@/Components/records-table.vue'
import SlideOver from '@/Components/slide-over.vue'
import { usePermission } from '@/Composables/usePermissions'
import WarehouseComponent from '@/Pages/Warehouses/warehouse-component.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import {
  BuildingOffice2Icon,
  CheckCircleIcon,
  EyeIcon,
  KeyIcon,
  MapPinIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline'
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
  ? 'Os locais voltam a ficar disponiveis nas operacoes e pesquisas do laboratorio.'
  : 'Os locais ficam inactivos, mas o historico e a rastreabilidade sao preservados.')

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
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-5 py-5 sm:px-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
          <div class="flex min-w-0 items-start gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <BuildingOffice2Icon class="h-5 w-5" />
            </span>
            <div>
              <p class="ds-kicker">Rede de clientes</p>
              <h1 class="ds-heading mt-1 text-2xl">Locais operacionais</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm">Registo controlado dos enderecos usados na recepcao, recolha, faturacao e acesso ao portal do cliente.</p>
            </div>
          </div>
          <div class="flex flex-wrap gap-2 lg:justify-end">
            <Link :href="route('customers.index')" class="ds-button ds-button-secondary"><UsersIcon class="h-4 w-4" />Ver clientes</Link>
            <button v-if="hasPermission('add_warehouses')" type="button" class="ds-button ds-button-primary" @click="openCreateEditor"><MapPinIcon class="h-4 w-4" />Novo local</button>
          </div>
        </div>
      </div>

      <dl class="grid gap-px bg-[var(--ds-border)] sm:grid-cols-2 xl:grid-cols-4">
        <div class="bg-[var(--ds-panel)] p-5"><div class="flex items-start justify-between gap-3"><div><dt class="ds-field-label">Total registado</dt><dd class="mt-2 text-2xl font-bold text-[var(--ds-text)]">{{ totalSites }}</dd></div><BuildingOffice2Icon class="h-5 w-5 text-[var(--ds-text-soft)]" /></div></div>
        <div class="bg-[var(--ds-panel)] p-5"><div class="flex items-start justify-between gap-3"><div><dt class="ds-field-label">Activos nesta pagina</dt><dd class="mt-2 text-2xl font-bold text-[var(--ds-text)]">{{ activeSites }}</dd></div><CheckCircleIcon class="h-5 w-5 text-emerald-600 dark:text-emerald-300" /></div></div>
        <div class="bg-[var(--ds-panel)] p-5"><div class="flex items-start justify-between gap-3"><div><dt class="ds-field-label">Locais principais</dt><dd class="mt-2 text-2xl font-bold text-[var(--ds-text)]">{{ primarySites }}</dd></div><MapPinIcon class="h-5 w-5 text-[var(--ds-text-soft)]" /></div></div>
        <div class="bg-[var(--ds-panel)] p-5"><div class="flex items-start justify-between gap-3"><div><dt class="ds-field-label">Portal configurado</dt><dd class="mt-2 text-2xl font-bold text-[var(--ds-text)]">{{ portalReadySites }}</dd></div><KeyIcon class="h-5 w-5 text-[var(--ds-text-soft)]" /></div></div>
      </dl>
    </section>

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
