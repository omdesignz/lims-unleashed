<script setup>
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import RecordsTable from '@/Components/records-table.vue'
import { usePermission } from '@/Composables/usePermissions'
import Layout from '@/Shared/Layouts/Layout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ArchiveBoxIcon, CheckBadgeIcon, CubeIcon, PlusIcon, ReceiptPercentIcon } from '@heroicons/vue/24/outline'
import { computed, ref } from 'vue'

defineOptions({ layout: Layout })

const props = defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
const { hasPermission } = usePermission()
const selectedAction = ref(null)
const showActionConfirmation = ref(false)
const pageRecords = computed(() => props.record.data || [])
const metrics = computed(() => [
  { label: 'Serviços', value: props.record.meta?.total ?? pageRecords.value.length, detail: 'catálogo total', icon: CubeIcon },
  { label: 'Activos nesta página', value: pageRecords.value.filter((item) => !item.deleted).length, detail: 'disponíveis para venda', icon: CheckBadgeIcon },
  { label: 'Tributados', value: pageRecords.value.filter((item) => item.charge_tax).length, detail: 'com imposto configurado', icon: ReceiptPercentIcon },
  { label: 'Arquivados', value: pageRecords.value.filter((item) => item.deleted).length, detail: 'fora da selecção activa', icon: ArchiveBoxIcon },
])
const actions = [{ id: null, label: 'gestlab.actions.bulk_actions_text' }, { id: 'delete', label: 'gestlab.actions.delete' }, { id: 'restore', label: 'gestlab.actions.restore' }]

function requestBulkAction(action) { selectedAction.value = action; showActionConfirmation.value = true }
function executeBulkAction() {
  const recordIds = pageRecords.value.filter((item) => item.selected).map((item) => item.id)
  if (!recordIds.length || !['delete', 'restore'].includes(selectedAction.value)) { showActionConfirmation.value = false; return }
  router.get(route(`paidservices.${selectedAction.value}`), { recordIds }, { preserveScroll: true, onFinish: () => { showActionConfirmation.value = false; selectedAction.value = null } })
}
</script>

<template>
  <div class="space-y-5">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex min-w-0 items-start gap-3"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200"><CubeIcon class="h-5 w-5" /></span><div><p class="ds-kicker">Catálogo comercial</p><h1 class="ds-heading mt-1 text-xl sm:text-2xl">Serviços facturáveis</h1><p class="ds-copy mt-1 max-w-3xl text-sm">Ofertas controladas com preço, fiscalidade e descrição reutilizáveis nos documentos comerciais.</p></div></div>
        <Link v-if="hasPermission('add_paid_services')" :href="route('paidservices.create')" class="ds-button ds-button-primary"><PlusIcon class="h-4 w-4" />Novo serviço</Link>
      </div>
      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="metric in metrics" :key="metric.label" class="border-b border-[var(--ds-border)] px-4 py-3 sm:[&:nth-child(odd)]:border-r sm:[&:nth-child(n+3)]:border-b-0 xl:border-b-0 xl:border-r xl:last:border-r-0"><div class="flex items-start justify-between gap-3"><div><dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</dt><dd class="mt-2 text-xl font-black tabular-nums text-[var(--ds-text)]">{{ metric.value }}</dd><p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ metric.detail }}</p></div><component :is="metric.icon" class="h-5 w-5 text-[var(--ds-text-soft)]" /></div></div>
      </dl>
    </section>

    <RecordsTable :record="record" :model="model" :abilities="abilities" :fields="fields" :slide-over-edit="slideOverEdit" :query="query" :actions="actions" :create-action="false" @execute-action="requestBulkAction" @create-record="router.visit(route('paidservices.create'))" @slideover-on="(service) => router.visit(route('paidservices.edit', service.id))" />

    <ConfirmDialog v-if="showActionConfirmation" :title="selectedAction === 'restore' ? 'Restaurar serviços seleccionados?' : 'Arquivar serviços seleccionados?'" :description="selectedAction === 'restore' ? 'Os serviços voltarao a estar disponíveis no catálogo comercial.' : 'Os serviços deixam de estar disponíveis para novas selecoes.'" :variant="selectedAction === 'restore' ? 'success' : 'danger'" confirm="Confirmar" cancel="Cancelar" @confirmed="executeBulkAction" @canceled="showActionConfirmation = false" />
  </div>
</template>
