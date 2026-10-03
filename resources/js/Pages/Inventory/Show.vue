<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import {
  ArrowLeftIcon,
  ArrowTopRightOnSquareIcon,
  BuildingStorefrontIcon,
  CubeIcon,
  ExclamationTriangleIcon,
  MapPinIcon,
} from "@heroicons/vue/24/outline";
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, default: () => ({ data: {} }) },
});

const position = computed(() => props.record?.data ?? {});
const quantity = computed(() => Number(position.value.qty_available ?? 0));
const reorderPoint = computed(() => Number(position.value.reorder_point ?? 0));
const stockStatus = computed(() => {
  if (quantity.value <= 0) {
    return { label: "Sem existências", className: "bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300" };
  }

  if (quantity.value <= reorderPoint.value) {
    return { label: "Reposição necessária", className: "bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300" };
  }

  return { label: "Disponível", className: "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300" };
});
const metrics = computed(() => [
  { label: "Disponível", value: quantity.value },
  { label: "Nível mínimo", value: Number(position.value.min_stock_level ?? 0) },
  { label: "Ponto de reposição", value: reorderPoint.value },
]);
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <BuildingStorefrontIcon class="h-5 w-5" />
          </span>
          <div>
            <p class="ds-kicker">Posição de existências #{{ position.id }}</p>
            <div class="mt-1 flex flex-wrap items-center gap-2">
              <h1 class="ds-heading text-2xl">{{ position.item || "Item de inventário" }}</h1>
              <span class="rounded-full px-2.5 py-1 text-xs font-bold" :class="stockStatus.className">{{ stockStatus.label }}</span>
            </div>
            <p class="ds-copy mt-1 text-sm">Saldo e limites operacionais na localização seleccionada.</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-3">
          <Link :href="route('inventory.index')" class="ds-button ds-button-secondary">
            <ArrowLeftIcon class="h-4 w-4" />
            Voltar ao existências
          </Link>
          <Link
            v-if="position.can_open_item"
            :href="route('vap-inventory.items.show', { item: position.item_id })"
            class="ds-button ds-button-primary"
          >
            <ArrowTopRightOnSquareIcon class="h-4 w-4" />
            Gerir item
          </Link>
        </div>
      </div>

      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-3">
        <div v-for="metric in metrics" :key="metric.label" class="border-b border-[var(--ds-border)] px-4 py-4 sm:border-b-0 sm:border-r sm:last:border-r-0">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</dt>
          <dd class="mt-2 text-2xl font-bold text-[var(--ds-text)]">{{ metric.value }}</dd>
        </div>
      </dl>
    </section>

    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
        <p class="ds-kicker">Rastreabilidade</p>
        <h2 class="ds-heading mt-1 text-base">Identificação da posição</h2>
      </div>
      <dl class="divide-y divide-[var(--ds-border)]">
        <div class="grid gap-2 px-5 py-4 sm:grid-cols-[14rem_1fr] sm:px-6">
          <dt class="flex items-center gap-2 text-sm font-semibold text-[var(--ds-text-muted)]"><CubeIcon class="h-4 w-4" /> Item</dt>
          <dd class="text-sm font-semibold text-[var(--ds-text)]">{{ position.item || "—" }}</dd>
        </div>
        <div class="grid gap-2 px-5 py-4 sm:grid-cols-[14rem_1fr] sm:px-6">
          <dt class="flex items-center gap-2 text-sm font-semibold text-[var(--ds-text-muted)]"><MapPinIcon class="h-4 w-4" /> Armazém</dt>
          <dd class="text-sm font-semibold text-[var(--ds-text)]">{{ position.warehouse || "—" }}</dd>
        </div>
        <div class="grid gap-2 px-5 py-4 sm:grid-cols-[14rem_1fr] sm:px-6">
          <dt class="flex items-center gap-2 text-sm font-semibold text-[var(--ds-text-muted)]"><ExclamationTriangleIcon class="h-4 w-4" /> Categoria</dt>
          <dd class="text-sm font-semibold text-[var(--ds-text)]">{{ position.category || "—" }}</dd>
        </div>
      </dl>
    </section>
  </div>
</template>
