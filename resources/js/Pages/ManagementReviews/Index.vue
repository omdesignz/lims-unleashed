<script setup>
import Pagination from "@/Components/pagination.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { router } from "@inertiajs/vue3";
import {
  CircleCheck as CheckCircleIcon,
  ClipboardList as ClipboardDocumentListIcon,
  Clock as ClockIcon,
} from "@lucide/vue";
import { computed, ref } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  reviews: { type: Object, default: () => ({ data: [] }) },
  filters: { type: Object, default: () => ({}) },
  stats: { type: Object, default: () => ({}) },
});

const statusFilter = ref(props.filters?.status ?? "");
const rows = computed(() => props.reviews?.data ?? []);
const metrics = computed(() => [
  { label: "Planeadas", value: props.stats?.planned ?? 0, detail: "agenda de revisão", icon: ClipboardDocumentListIcon },
  { label: "Em curso", value: props.stats?.in_progress ?? 0, detail: "decisões em elaboração", icon: ClockIcon },
  { label: "Concluídas", value: props.stats?.completed ?? 0, detail: "evidência aprovada", icon: CheckCircleIcon },
]);

const statusLabels = {
  planned: "Planeada",
  in_progress: "Em curso",
  completed: "Concluída",
};

function statusClass(status) {
  return {
    planned: "ds-badge-neutral",
    in_progress: "ds-badge-warning",
    completed: "ds-badge-success",
  }[status] ?? "ds-badge-neutral";
}

function formatDate(value) {
  if (!value) {
    return "-";
  }

  return new Intl.DateTimeFormat("pt-PT", { dateStyle: "medium", timeZone: "UTC" }).format(new Date(value));
}

function applyFilter() {
  router.get(route("management-reviews.index"), {
    status: statusFilter.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}
</script>

<template>
  <div class="pl-page space-y-6">
    <PageHeader title="Revisões pela gestão" lede="Planeamento, decisões, riscos, oportunidades e melhoria acompanhados pela direcção." />

    <dl class="pl-cells">
      <div v-for="metric in metrics" :key="metric.label" class="pl-cell">
        <dt class="pl-k pl-muted">{{ metric.label }}</dt>
        <dd class="pl-cell-value">{{ metric.value }}</dd>
      </div>
    </dl>

    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-3 border-b border-[var(--ds-border)] p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
        <div>
          <h2 class="ds-heading text-base">Registo de revisões</h2>
          <p class="ds-copy mt-1 text-sm">{{ reviews.total ?? rows.length }} revisões no histórico de gestão.</p>
        </div>
        <BaseSelect v-model="statusFilter" class="ds-field w-full sm:w-56" @change="applyFilter">
          <option value="">Todos os estados</option>
          <option value="planned">Planeadas</option>
          <option value="in_progress">Em curso</option>
          <option value="completed">Concluídas</option>
        </BaseSelect>
      </div>

      <div class="overflow-x-auto">
        <DataTable class="min-w-full divide-y divide-[var(--ds-border)] text-sm">
          <thead class="bg-[var(--ds-panel-subtle)]">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase text-[var(--ds-text-soft)] sm:px-5">Referência</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase text-[var(--ds-text-soft)] sm:px-5">Data e âmbito</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase text-[var(--ds-text-soft)] sm:px-5">Condução</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase text-[var(--ds-text-soft)] sm:px-5">Aprovação</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase text-[var(--ds-text-soft)] sm:px-5">Estado</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--ds-border)]">
            <tr v-for="review in rows" :key="review.id" class="transition-colors hover:bg-[var(--ds-panel-subtle)]">
              <td class="whitespace-nowrap px-4 py-4 font-mono text-xs font-bold text-[var(--ds-text)] sm:px-5">{{ review.reference || `#${review.id}` }}</td>
              <td class="min-w-72 px-4 py-4 sm:px-5">
                <p class="font-bold text-[var(--ds-text)]">{{ formatDate(review.review_date) }}</p>
                <p class="mt-1 line-clamp-2 text-xs leading-5 text-[var(--ds-text-muted)]">{{ review.scope || "Âmbito não descrito" }}</p>
              </td>
              <td class="min-w-44 px-4 py-4 font-semibold text-[var(--ds-text-muted)] sm:px-5">{{ review.conducted_by?.name || "Não atribuída" }}</td>
              <td class="min-w-44 px-4 py-4 sm:px-5">
                <p class="font-semibold text-[var(--ds-text)]">{{ review.approved_by?.name || "Pendente" }}</p>
                <p v-if="review.approved_at" class="mt-1 text-xs text-[var(--ds-text-muted)]">{{ formatDate(review.approved_at) }}</p>
              </td>
              <td class="whitespace-nowrap px-4 py-4 sm:px-5"><span class="ds-badge" :class="statusClass(review.status)">{{ statusLabels[review.status] || review.status }}</span></td>
            </tr>
            <tr v-if="!rows.length">
              <td colspan="5" class="px-5 py-12 text-center">
                <ClipboardDocumentListIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
                <p class="ds-heading mt-3 text-sm">Nenhuma revisão encontrada</p>
                <p class="ds-copy mt-1 text-sm">Não existem revisões para o estado seleccionado.</p>
              </td>
            </tr>
          </tbody>
        </DataTable>
      </div>

      <div v-if="reviews.last_page" class="border-t border-[var(--ds-border)]">
        <Pagination v-bind="reviews" />
      </div>
    </section>
  </div>
</template>
