<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import {
  ArrowLeft as ArrowLeftIcon,
  ArrowLeftRight as ArrowsRightLeftIcon,
  CalendarDays as CalendarDaysIcon,
  Box as CubeIcon,
  MapPin as MapPinIcon,
  CircleUser as UserCircleIcon,
} from "@lucide/vue";
import { Link } from "@inertiajs/vue3";
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
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <ArrowsRightLeftIcon class="h-5 w-5" />
          </span>
          <div>
            <p class="ds-kicker">Movimento #{{ movement.id }}</p>
            <div class="mt-1 flex flex-wrap items-center gap-2">
              <h1 class="ds-heading text-2xl">{{ movement.item || "Movimento de inventário" }}</h1>
              <span class="rounded-full bg-[var(--ds-panel-muted)] px-2.5 py-1 text-xs font-bold text-[var(--ds-text-muted)]">{{ movement.type || "Sem classificação" }}</span>
            </div>
            <p class="ds-copy mt-1 text-sm">Registo individual do livro de movimentos de materiais e consumíveis.</p>
          </div>
        </div>
        <Link :href="route('itransactions.index')" class="ds-button ds-button-secondary">
          <ArrowLeftIcon class="h-4 w-4" />
          Voltar ao livro
        </Link>
      </div>

      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-3">
        <div class="border-b border-[var(--ds-border)] px-4 py-4 sm:border-b-0 sm:border-r">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Quantidade</dt>
          <dd class="mt-2 text-2xl font-bold text-[var(--ds-text)]">{{ movement.qty ?? "—" }}</dd>
        </div>
        <div class="border-b border-[var(--ds-border)] px-4 py-4 sm:border-b-0 sm:border-r">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Tipo</dt>
          <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ movement.type || "—" }}</dd>
        </div>
        <div class="px-4 py-4">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Registado em</dt>
          <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ formatDate(movement.created_at) }}</dd>
        </div>
      </dl>
    </section>

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
