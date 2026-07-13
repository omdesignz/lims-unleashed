<script setup>
import Pagination from "@/Components/pagination.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { router } from "@inertiajs/vue3";
import {
  CheckCircleIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  MagnifyingGlassIcon,
  ShieldExclamationIcon,
} from "@heroicons/vue/24/outline";
import { computed, reactive } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  complaints: { type: Object, default: () => ({ data: [] }) },
  filters: { type: Object, default: () => ({}) },
  stats: { type: Object, default: () => ({}) },
});

const filterForm = reactive({
  search: props.filters?.search ?? "",
  status: props.filters?.status ?? "",
});
const rows = computed(() => props.complaints?.data ?? []);
const metrics = computed(() => [
  { label: "Total", value: props.stats?.total ?? 0, detail: "reclamações registadas", icon: ShieldExclamationIcon },
  { label: "Em tratamento", value: props.stats?.open ?? 0, detail: "abertas ou em análise", icon: ClockIcon },
  { label: "Concluídas", value: props.stats?.resolved ?? 0, detail: "resolvidas ou encerradas", icon: CheckCircleIcon },
]);

const statusLabels = {
  open: "Aberta",
  in_review: "Em análise",
  resolved: "Resolvida",
  closed: "Encerrada",
};
const severityLabels = {
  low: "Baixa",
  medium: "Média",
  high: "Alta",
  critical: "Crítica",
};

function statusClass(status) {
  return {
    open: "ds-badge-warning",
    in_review: "ds-badge-info",
    resolved: "ds-badge-success",
    closed: "ds-badge-neutral",
  }[status] ?? "ds-badge-neutral";
}

function severityClass(severity) {
  return {
    low: "ds-badge-neutral",
    medium: "ds-badge-info",
    high: "ds-badge-warning",
    critical: "ds-badge-danger",
  }[severity] ?? "ds-badge-neutral";
}

function formatDate(value) {
  if (!value) {
    return "-";
  }

  return new Intl.DateTimeFormat("pt-PT", { dateStyle: "medium" }).format(new Date(value));
}

function applyFilters() {
  router.get(route("complaints.index"), {
    search: filterForm.search || undefined,
    status: filterForm.status || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

function clearFilters() {
  filterForm.search = "";
  filterForm.status = "";
  applyFilters();
}
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="flex items-start gap-3">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-amber-700 dark:text-amber-300">
          <ShieldExclamationIcon class="h-5 w-5" />
        </span>
        <div>
          <p class="ds-kicker">Voz do cliente</p>
          <h1 class="ds-heading mt-1 text-2xl">Reclamações</h1>
          <p class="ds-copy mt-1 max-w-3xl text-sm">Registo, priorização e acompanhamento de reclamações com rastreabilidade ISO 17025.</p>
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

    <section class="ds-panel overflow-hidden">
      <form class="border-b border-[var(--ds-border)] p-4 sm:p-5" @submit.prevent="applyFilters">
        <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_14rem_auto]">
          <div class="relative">
            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-[var(--ds-text-soft)]" />
            <input v-model="filterForm.search" type="search" class="ds-field pl-10" placeholder="Pesquisar referência, título ou descrição" />
          </div>
          <select v-model="filterForm.status" class="ds-field">
            <option value="">Todos os estados</option>
            <option value="open">Aberta</option>
            <option value="in_review">Em análise</option>
            <option value="resolved">Resolvida</option>
            <option value="closed">Encerrada</option>
          </select>
          <div class="flex gap-2">
            <button type="submit" class="ds-button ds-button-primary">Aplicar</button>
            <button v-if="filterForm.search || filterForm.status" type="button" class="ds-button ds-button-secondary" @click="clearFilters">Limpar</button>
          </div>
        </div>
      </form>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-[var(--ds-border)] text-sm">
          <thead class="bg-[var(--ds-panel-subtle)]">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase text-[var(--ds-text-soft)] sm:px-5">Referência</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase text-[var(--ds-text-soft)] sm:px-5">Reclamação</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase text-[var(--ds-text-soft)] sm:px-5">Responsabilidade</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase text-[var(--ds-text-soft)] sm:px-5">Receção</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase text-[var(--ds-text-soft)] sm:px-5">Estado</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase text-[var(--ds-text-soft)] sm:px-5">Severidade</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--ds-border)]">
            <tr v-for="complaint in rows" :key="complaint.id" class="transition-colors hover:bg-[var(--ds-panel-subtle)]">
              <td class="whitespace-nowrap px-4 py-4 font-mono text-xs font-bold text-[var(--ds-text)] sm:px-5">{{ complaint.reference || `#${complaint.id}` }}</td>
              <td class="min-w-72 px-4 py-4 sm:px-5">
                <p class="font-bold text-[var(--ds-text)]">{{ complaint.title }}</p>
                <p class="mt-1 line-clamp-2 text-xs leading-5 text-[var(--ds-text-muted)]">{{ complaint.description }}</p>
                <p v-if="complaint.customer?.name" class="mt-2 text-xs font-semibold text-[var(--ds-text-soft)]">{{ complaint.customer.name }}</p>
              </td>
              <td class="min-w-48 px-4 py-4 sm:px-5">
                <p class="font-semibold text-[var(--ds-text)]">{{ complaint.assigned_to?.name || "Não atribuída" }}</p>
                <p class="mt-1 text-xs text-[var(--ds-text-muted)]">Reportada por {{ complaint.reported_by_name }}</p>
              </td>
              <td class="whitespace-nowrap px-4 py-4 font-semibold text-[var(--ds-text-muted)] sm:px-5">{{ formatDate(complaint.received_at) }}</td>
              <td class="whitespace-nowrap px-4 py-4 sm:px-5"><span class="ds-badge" :class="statusClass(complaint.status)">{{ statusLabels[complaint.status] || complaint.status }}</span></td>
              <td class="whitespace-nowrap px-4 py-4 sm:px-5"><span class="ds-badge" :class="severityClass(complaint.severity)">{{ severityLabels[complaint.severity] || complaint.severity }}</span></td>
            </tr>
            <tr v-if="!rows.length">
              <td colspan="6" class="px-5 py-12 text-center">
                <ExclamationTriangleIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
                <p class="ds-heading mt-3 text-sm">Nenhuma reclamação encontrada</p>
                <p class="ds-copy mt-1 text-sm">Ajuste os filtros para consultar outro conjunto de registos.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="complaints.last_page" class="border-t border-[var(--ds-border)]">
        <Pagination v-bind="complaints" />
      </div>
    </section>
  </div>
</template>
