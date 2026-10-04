<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import {
  CalendarDays as CalendarDaysIcon,
  Box as CubeIcon,
  MapPin as MapPinIcon,
  CircleUser as UserCircleIcon,
} from "@lucide/vue";
import { computed } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, default: () => ({ data: {} }) },
});

const movement = computed(() => props.record?.data ?? {});

function formatDate(value) {
  if (!value) {
    return "—";
  }

  return new Intl.DateTimeFormat("pt-PT", {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(new Date(value));
}
</script>

<template>
  <div class="pl-page space-y-6">
    <PageHeader :trail="[{ title: 'Movimentos', url: route('itransactions.index') }, { title: movement.item || 'Movimento de inventário' }]" :title="movement.item || 'Movimento de inventário'" lede="Registo individual do livro de movimentos de materiais e consumíveis.">
      <template #badges>
        <span class="rounded-full bg-[var(--ds-panel-muted)] px-2.5 py-1 text-xs font-bold text-[var(--ds-text-muted)]">{{ movement.type || "Sem classificação" }}</span>
      </template>
    </PageHeader>

    <dl class="pl-cells">
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Quantidade</dt>
        <dd class="pl-cell-value">{{ movement.qty ?? "—" }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Tipo</dt>
        <dd class="pl-cell-text">{{ movement.type || "—" }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Registado em</dt>
        <dd class="pl-cell-text">{{ formatDate(movement.created_at) }}</dd>
      </div>
    </dl>

    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
        <p class="ds-kicker">Cadeia de custódia</p>
        <h2 class="ds-heading mt-1 text-base">Contexto do movimento</h2>
      </div>
      <dl class="divide-y divide-[var(--ds-border)]">
        <div class="grid gap-2 px-5 py-4 sm:grid-cols-[14rem_1fr] sm:px-6">
          <dt class="flex items-center gap-2 text-sm font-semibold text-[var(--ds-text-muted)]"><CubeIcon class="h-4 w-4" /> Item</dt>
          <dd class="text-sm font-semibold text-[var(--ds-text)]">{{ movement.item || "—" }}</dd>
        </div>
        <div class="grid gap-2 px-5 py-4 sm:grid-cols-[14rem_1fr] sm:px-6">
          <dt class="flex items-center gap-2 text-sm font-semibold text-[var(--ds-text-muted)]"><MapPinIcon class="h-4 w-4" /> Armazém</dt>
          <dd class="text-sm font-semibold text-[var(--ds-text)]">{{ movement.warehouse || "—" }}</dd>
        </div>
        <div class="grid gap-2 px-5 py-4 sm:grid-cols-[14rem_1fr] sm:px-6">
          <dt class="flex items-center gap-2 text-sm font-semibold text-[var(--ds-text-muted)]"><UserCircleIcon class="h-4 w-4" /> Operador</dt>
          <dd class="text-sm font-semibold text-[var(--ds-text)]">{{ movement.user || "—" }}</dd>
        </div>
        <div class="grid gap-2 px-5 py-4 sm:grid-cols-[14rem_1fr] sm:px-6">
          <dt class="flex items-center gap-2 text-sm font-semibold text-[var(--ds-text-muted)]"><CalendarDaysIcon class="h-4 w-4" /> Última actualização</dt>
          <dd class="text-sm font-semibold text-[var(--ds-text)]">{{ formatDate(movement.updated_at) }}</dd>
        </div>
      </dl>
    </section>
  </div>
</template>
