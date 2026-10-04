<script setup>
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import RecordsTable from '@/Components/records-table.vue'
import { usePermission } from '@/Composables/usePermissions'
import Layout from '@/Shared/Layouts/Layout.vue'
import { Link, router } from '@inertiajs/vue3'
import { Plus as PlusIcon } from '@lucide/vue'
import { computed, ref } from 'vue'

defineOptions({ layout: Layout })

const props = defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
const { hasPermission } = usePermission()
const selectedAction = ref(null)
const showActionConfirmation = ref(false)
const pageRecords = computed(() => props.record.data || [])
const actions = [{ id: null, label: 'gestlab.actions.bulk_actions_text' }, { id: 'delete', label: 'gestlab.actions.delete' }, { id: 'restore', label: 'gestlab.actions.restore' }]

function requestBulkAction(action) { selectedAction.value = action; showActionConfirmation.value = true }
function executeBulkAction() {
  const recordIds = pageRecords.value.filter((item) => item.selected).map((item) => item.id)
  if (!recordIds.length || !['delete', 'restore'].includes(selectedAction.value)) { showActionConfirmation.value = false; return }
  router.get(route(`paidservices.${selectedAction.value}`), { recordIds }, { preserveScroll: true, onFinish: () => { showActionConfirmation.value = false; selectedAction.value = null } })
}
</script>

<template>
  <div class="pl-page space-y-5">
    <PageHeader title="Serviços facturáveis" lede="Ofertas controladas com preço, fiscalidade e descrição reutilizáveis nos documentos comerciais.">
      <template #actions>
        <Link v-if="hasPermission('add_paid_services')" :href="route('paidservices.create')" class="ds-button ds-button-primary"><PlusIcon class="h-4 w-4" />Novo serviço</Link>
      </template>
    </PageHeader>

    <RecordsTable :record="record" :model="model" :abilities="abilities" :fields="fields" :slide-over-edit="slideOverEdit" :query="query" :actions="actions" :create-action="false" @execute-action="requestBulkAction" @create-record="router.visit(route('paidservices.create'))" @slideover-on="(service) => router.visit(route('paidservices.edit', service.id))" />

    <ConfirmDialog v-if="showActionConfirmation" :title="selectedAction === 'restore' ? 'Restaurar serviços seleccionados?' : 'Arquivar serviços seleccionados?'" :description="selectedAction === 'restore' ? 'Os serviços voltarao a estar disponíveis no catálogo comercial.' : 'Os serviços deixam de estar disponíveis para novas selecoes.'" :variant="selectedAction === 'restore' ? 'success' : 'danger'" confirm="Confirmar" cancel="Cancelar" @confirmed="executeBulkAction" @canceled="showActionConfirmation = false" />
  </div>
</template>
