<script setup>
import RecordsTable from "@/Components/records-table.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import {
  Download as ArrowDownTrayIcon,
  ExternalLink as ArrowTopRightOnSquareIcon,
  Upload as ArrowUpTrayIcon,
  ArrowLeftRight as ArrowsRightLeftIcon,
  Store as BuildingStorefrontIcon,
  Eye as EyeIcon,
  ShieldCheck as ShieldCheckIcon,
} from "@lucide/vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  fields: { type: Array, default: () => [] },
  query: { type: Object, default: () => ({}) },
});

const { hasPermission } = usePermission();
const rows = computed(() => props.record?.data ?? []);
const totalRecords = computed(() => props.record?.meta?.total ?? rows.value.length);
const incomingCount = computed(() => rows.value.filter((row) => row.is_addition).length);
const outgoingCount = computed(() => rows.value.filter((row) => row.is_deduction).length);
const metrics = computed(() => [
  { label: "Movimentos", value: totalRecords.value, detail: "registos do livro", icon: ArrowsRightLeftIcon },
  { label: "Entradas", value: incomingCount.value, detail: "nesta página", icon: ArrowDownTrayIcon },
  { label: "Saídas", value: outgoingCount.value, detail: "nesta página", icon: ArrowUpTrayIcon },
]);
</script>

<template>
  <div class="pl-page space-y-6">
    <Head title="Livro de movimentos" />
    <PageHeader title="Livro de movimentos" lede="Histórico de entradas, saídas, consumos e ajustes com item, localização, operador e quantidade rastreáveis.">
      <template #actions>
        <Link :href="route('inventory.index')" class="ds-button ds-button-secondary">
          <BuildingStorefrontIcon class="h-4 w-4" />
          Ver existências
        </Link>
        <Link :href="route('vap-inventory.reports.stock-movement')" class="ds-button ds-button-primary">
          <ArrowTopRightOnSquareIcon class="h-4 w-4" />
          Relatório controlado
        </Link>
      </template>
    </PageHeader>

    <dl class="pl-cells">
      <div v-for="metric in metrics" :key="metric.label" class="pl-cell">
        <dt class="pl-k pl-muted">{{ metric.label }}</dt>
        <dd class="pl-cell-value">{{ metric.value }}</dd>
      </div>
    </dl>

    <section class="flex gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
      <ShieldCheckIcon class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-300" />
      <div>
        <p class="text-sm font-semibold text-[var(--ds-text)]">Registo de auditoria em modo de consulta</p>
        <p class="mt-1 text-sm leading-6 text-[var(--ds-text-muted)]">Novos movimentos e correcções devem ser realizados no fluxo controlado do item, preservando o saldo e a rastreabilidade numa única operação.</p>
      </div>
    </section>

    <RecordsTable
      :record="record"
      model="immutable_itransactions"
      :fields="fields"
      :slide-over-edit="false"
      :query="query"
      :actions="[]"
      :create-action="false"
    >
      <template #actions="{ id }">
        <Link
          v-if="hasPermission('view_itransactions')"
          :href="route('itransactions.show', { transaction: id })"
          class="ds-icon-button"
          title="Consultar movimento"
          aria-label="Consultar movimento"
        >
          <EyeIcon class="h-4 w-4" />
        </Link>
      </template>
    </RecordsTable>
  </div>
</template>
