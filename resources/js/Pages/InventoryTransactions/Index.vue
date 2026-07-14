<script setup>
import RecordsTable from "@/Components/records-table.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import {
  ArrowDownTrayIcon,
  ArrowTopRightOnSquareIcon,
  ArrowUpTrayIcon,
  ArrowsRightLeftIcon,
  BuildingStorefrontIcon,
  EyeIcon,
  ShieldCheckIcon,
} from "@heroicons/vue/24/outline";
import { Link } from "@inertiajs/vue3";
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
const incomingCodes = ["stock_in", "stock_adjustment_add", "receipt", "transfer_in"];
const outgoingCodes = ["stock_out", "stock_adjustment_remove", "consumption", "transfer_out"];
const incomingCount = computed(() => rows.value.filter((row) => incomingCodes.includes(row.type_code)).length);
const outgoingCount = computed(() => rows.value.filter((row) => outgoingCodes.includes(row.type_code)).length);
const metrics = computed(() => [
  { label: "Movimentos", value: totalRecords.value, detail: "registos do livro", icon: ArrowsRightLeftIcon },
  { label: "Entradas", value: incomingCount.value, detail: "nesta página", icon: ArrowDownTrayIcon },
  { label: "Saídas", value: outgoingCount.value, detail: "nesta página", icon: ArrowUpTrayIcon },
]);
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <ArrowsRightLeftIcon class="h-5 w-5" />
          </span>
          <div>
            <p class="ds-kicker">Rastreabilidade de materiais</p>
            <h1 class="ds-heading mt-1 text-2xl">Livro de movimentos</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">Histórico de entradas, saídas, consumos e ajustes com item, localização, operador e quantidade rastreáveis.</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-3">
          <Link :href="route('inventory.index')" class="ds-button ds-button-secondary">
            <BuildingStorefrontIcon class="h-4 w-4" />
            Ver existências
          </Link>
          <Link :href="route('vap-inventory.reports.stock-movement')" class="ds-button ds-button-primary">
            <ArrowTopRightOnSquareIcon class="h-4 w-4" />
            Relatório controlado
          </Link>
        </div>
      </div>

      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-3">
        <div v-for="metric in metrics" :key="metric.label" class="border-b border-[var(--ds-border)] px-4 py-3 sm:border-b-0 sm:border-r sm:last:border-r-0">
          <div class="flex items-start justify-between gap-3">
            <div>
              <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</dt>
              <dd class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ metric.value }}</dd>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ metric.detail }}</p>
            </div>
            <component :is="metric.icon" class="h-5 w-5 text-[var(--ds-text-soft)]" />
          </div>
        </div>
      </dl>
    </section>

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
