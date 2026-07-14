<script setup>
import PortalLayout from "@/Shared/Layouts/PortalLayout.vue";
import { Link } from "@inertiajs/vue3";
import {
  ArrowRightIcon,
  BanknotesIcon,
  BeakerIcon,
  BuildingOffice2Icon,
  CheckBadgeIcon,
  ClipboardDocumentCheckIcon,
  ClockIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  PlusIcon,
  ReceiptPercentIcon,
  ShieldExclamationIcon,
  TruckIcon,
  WrenchScrewdriverIcon,
} from "@heroicons/vue/24/outline";
import { computed } from "vue";

defineOptions({ layout: PortalLayout });

const props = defineProps({
  auth: { type: Object, default: () => ({}) },
  stats: { type: Object, default: () => ({}) },
  charts: { type: Object, default: () => ({}) },
  services: { type: Array, default: () => [] },
  recentRequests: { type: Object, default: () => ({ data: [] }) },
});

const warehouse = computed(() => props.auth?.user?.data ?? props.auth?.user ?? {});
const requests = computed(() => props.recentRequests?.data ?? props.recentRequests ?? []);
const metricCards = computed(() => [
  { label: "Pedidos em aberto", value: props.stats.open_requests || 0, detail: "em triagem ou tratamento", icon: ClockIcon },
  { label: "Colheitas", value: props.stats.collections || 0, detail: "histórico visível no portal", icon: TruckIcon },
  { label: "Certificados", value: props.stats.qualitycertificates || 0, detail: "documentos de qualidade", icon: BeakerIcon },
  { label: "Saldo vencido", value: props.stats.overdue || "AOA 0,00", detail: "facturação por regularizar", icon: BanknotesIcon },
]);

const documentLinks = computed(() => [
  { label: "Facturas", value: props.stats.invoices || 0, href: route("portal.invoices"), icon: BanknotesIcon },
  { label: "Recibos", value: props.stats.receipts || 0, href: route("portal.receipts"), icon: ReceiptPercentIcon },
  { label: "Notas de crédito", value: props.stats.creditnotes || 0, href: route("portal.creditnotes"), icon: DocumentTextIcon },
  { label: "Guias contratuais", value: props.stats.contractguides || 0, href: route("portal.contractguides"), icon: ClipboardDocumentCheckIcon },
]);

function serviceIcon(service) {
  return {
    beaker: BeakerIcon,
    truck: TruckIcon,
    certificate: CheckBadgeIcon,
    document: DocumentTextIcon,
    currency: BanknotesIcon,
    shield: ShieldExclamationIcon,
    support: WrenchScrewdriverIcon,
  }[service.icon] || WrenchScrewdriverIcon;
}

function statusLabel(status) {
  return { pending: "Pendente", in_progress: "Em tratamento", completed: "Concluído", cancelled: "Cancelado" }[status] || "Pendente";
}

function statusClass(status) {
  return {
    pending: "bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20",
    in_progress: "bg-cyan-50 text-cyan-700 ring-cyan-200 dark:bg-cyan-500/10 dark:text-cyan-200 dark:ring-cyan-400/20",
    completed: "bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20",
    cancelled: "bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20",
  }[status] || "bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)] ring-[var(--ds-border)]";
}

function formatDate(value) {
  return value ? new Intl.DateTimeFormat("pt-PT", { dateStyle: "medium" }).format(new Date(value)) : "Sem data";
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
            <div class="min-w-0">
              <p class="ds-kicker">Área do cliente</p>
              <h1 class="ds-heading mt-1 break-words text-2xl">{{ warehouse.name || warehouse.customer || "Resumo da conta" }}</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm">Acompanhe pedidos, amostras, documentos de qualidade e conta corrente num único espaco.</p>
              <div class="mt-3 flex flex-wrap gap-2">
                <span v-if="warehouse.email" class="ds-chip">{{ warehouse.email }}</span>
                <span v-if="warehouse.code" class="ds-chip font-mono">{{ warehouse.code }}</span>
                <span v-if="warehouse.address" class="ds-chip">{{ warehouse.address }}</span>
              </div>
            </div>
          </div>

          <Link :href="route('portal.requests.index', { new: 1 })" class="ds-button ds-button-primary">
            <PlusIcon class="h-4 w-4" /> Nova pedido </Link>
        </div>
      </div>

      <dl class="grid gap-px bg-[var(--ds-border)] sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="metric in metricCards" :key="metric.label" class="bg-[var(--ds-panel)] p-5">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0"><dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</dt><dd class="mt-3 break-words text-xl font-bold text-[var(--ds-text)]">{{ metric.value }}</dd><p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ metric.detail }}</p></div>
            <component :is="metric.icon" class="h-5 w-5 shrink-0 text-[var(--ds-text-soft)]" />
          </div>
        </div>
      </dl>
    </section>

    <section class="ds-card overflow-hidden">
      <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:flex sm:items-start sm:justify-between sm:gap-4 sm:px-6">
        <div><h2 class="text-base font-bold text-[var(--ds-text)]">Pedidos recentes</h2><p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">Últimos pedidos submetidos e respectivo estado.</p></div>
        <Link :href="route('portal.requests.index')" class="ds-button ds-button-secondary mt-3 sm:mt-0">Ver todos <ArrowRightIcon class="h-4 w-4" /></Link>
      </header>
      <div v-if="requests.length" class="divide-y divide-[var(--ds-border)]">
        <article v-for="request in requests" :key="request.id" class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
          <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h3 class="break-words text-sm font-bold text-[var(--ds-text)]">{{ request.title }}</h3><span :class="['ds-chip', statusClass(request.status)]">{{ statusLabel(request.status) }}</span></div><p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]"><span class="font-mono">{{ request.reference || `#${request.id}` }}</span> / {{ formatDate(request.submitted_at) }}</p></div>
          <span class="ds-chip">{{ request.category || request.request_type }}</span>
        </article>
      </div>
      <div v-else class="ds-empty-state m-5 py-10 text-center sm:m-6"><ClipboardDocumentCheckIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" /><p class="mt-3 text-sm font-bold text-[var(--ds-text)]">Sem pedidos recentes</p></div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
      <section>
        <div class="mb-4"><p class="ds-kicker">Atalhos de serviço</p><h2 class="ds-heading mt-1 text-xl">Como podemos ajudar?</h2></div>
        <div class="grid gap-4 sm:grid-cols-2">
          <Link v-for="service in services" :key="service.type" :href="route('portal.requests.index', { request_type: service.type, new: 1, title: service.title })" class="ds-card group p-5 transition hover:border-[rgb(var(--primary-300-rgb))]">
            <div class="flex items-start gap-3"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))] ring-1 ring-[var(--ds-border)]"><component :is="serviceIcon(service)" class="h-5 w-5" /></span><div class="min-w-0"><h3 class="text-sm font-bold text-[var(--ds-text)]">{{ service.title }}</h3><p class="ds-copy mt-1 text-sm">{{ service.description }}</p></div></div>
          </Link>
        </div>
      </section>

      <aside class="ds-card overflow-hidden self-start">
        <header class="border-b border-[var(--ds-border)] px-5 py-4"><h2 class="text-sm font-bold text-[var(--ds-text)]">Documentos da conta</h2></header>
        <nav class="divide-y divide-[var(--ds-border)]">
          <Link v-for="item in documentLinks" :key="item.label" :href="item.href" class="flex items-center justify-between gap-3 px-5 py-3.5 text-sm font-bold text-[var(--ds-text-muted)] hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)]">
            <span class="inline-flex items-center gap-2"><component :is="item.icon" class="h-4 w-4" />{{ item.label }}</span><span class="font-mono text-xs">{{ item.value }}</span>
          </Link>
        </nav>
      </aside>
    </div>
  </div>
</template>
