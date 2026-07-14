<script setup>
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link } from "@inertiajs/vue3";
import {
  ArrowLeftIcon,
  BanknotesIcon,
  BeakerIcon,
  BuildingOffice2Icon,
  CheckBadgeIcon,
  ClipboardDocumentCheckIcon,
  ClockIcon,
  DocumentTextIcon,
  EnvelopeIcon,
  ExclamationTriangleIcon,
  MapPinIcon,
  PencilSquareIcon,
  PhoneIcon,
  ReceiptPercentIcon,
  StarIcon,
  UserCircleIcon,
} from "@heroicons/vue/24/outline";
import { computed } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, required: true },
  customerState: { type: Object, default: () => ({}) },
  charts: { type: Object, default: () => ({}) },
});

const { hasPermission } = usePermission();
const customer = computed(() => props.record?.data ?? props.record ?? {});
const summary = computed(() => props.customerState?.summary ?? {});
const sites = computed(() => customer.value.warehouses ?? []);
const primarySite = computed(() => sites.value.find((site) => Number(site.id) === Number(customer.value.warehouse_id)) ?? customer.value.warehouse ?? null);
const recentSamples = computed(() => props.customerState?.recent_samples ?? []);
const recentRequests = computed(() => props.customerState?.recent_requests ?? []);
const openFinance = computed(() => props.customerState?.open_finance ?? []);
const hasCommercialAttention = computed(() => Number(summary.value.open_amount_due || 0) > 0 || Number(summary.value.open_requests || 0) > 0);

const metrics = computed(() => [
  { label: "Propostas aceites", value: summary.value.accepted_proposals || 0, detail: "âmbitos comerciais activos", icon: CheckBadgeIcon },
  { label: "Amostras em curso", value: summary.value.samples_in_progress || 0, detail: `${summary.value.completed_samples || 0} concluídas`, icon: BeakerIcon },
  { label: "Saldo em aberto", value: formatCurrency(summary.value.open_amount_due), detail: `${summary.value.open_invoices || 0} factura(s)`, icon: BanknotesIcon },
  { label: "Pedidos abertos", value: summary.value.open_requests || 0, detail: "portal do cliente", icon: ClipboardDocumentCheckIcon },
]);

const governanceFacts = computed(() => [
  { label: "Certificados", value: summary.value.certificates || 0, icon: DocumentTextIcon },
  { label: "Recibos", value: summary.value.receipts || 0, icon: ReceiptPercentIcon },
  { label: "Notas de crédito", value: summary.value.credit_notes || 0, icon: BanknotesIcon },
  { label: "Locais", value: sites.value.length, icon: MapPinIcon },
]);

function formatCurrency(value) {
  return new Intl.NumberFormat("pt-PT", {
    style: "currency",
    currency: "AOA",
    minimumFractionDigits: 2,
  }).format(Number(value || 0));
}

function formatDate(value) {
  if (!value) {
    return "Não definido";
  }

  return new Intl.DateTimeFormat("pt-PT", { dateStyle: "medium" }).format(new Date(value));
}

function statusClass(status) {
  const normalized = String(status || "").toLowerCase();

  if (["completado", "completed", "resolved", "closed"].includes(normalized)) {
    return "bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20";
  }

  if (["pending", "por_iniciar", "in_progress", "en_progreso"].includes(normalized)) {
    return "bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20";
  }

  return "bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)] ring-[var(--ds-border)]";
}
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-5 py-5 sm:px-6">
        <nav aria-label="Breadcrumb">
          <Link :href="route('customers.index')" class="inline-flex items-center gap-1.5 text-xs font-bold text-[var(--ds-text-muted)] hover:text-[rgb(var(--primary-700-rgb))]">
            <ArrowLeftIcon class="h-4 w-4" />
            Clientes
          </Link>
        </nav>

        <div class="mt-5 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
          <div class="flex min-w-0 items-start gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <BuildingOffice2Icon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <p class="ds-kicker">Dossier do cliente #{{ customer.id }}</p>
              <h1 class="ds-heading mt-1 break-words text-2xl">{{ customer.name }}</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm">Visao consolidada da execução laboratorial, relacionamento comercial, locais e contactos da conta.</p>
              <div class="mt-3 flex flex-wrap gap-2">
                <span v-if="customer.code" class="ds-chip font-mono">{{ customer.code }}</span>
                <span class="ds-chip">{{ customer.category || "Sem categoria" }}</span>
                <span :class="['ds-chip', customer.deleted ? 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20' : 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20']">
                  {{ customer.deleted ? "Arquivado" : "Activo" }}
                </span>
              </div>
            </div>
          </div>

          <div class="flex flex-wrap gap-2 lg:justify-end">
            <Link :href="route('customers.index')" class="ds-button ds-button-secondary">
              <ArrowLeftIcon class="h-4 w-4" />
              Voltar
            </Link>
            <Link v-if="hasPermission('edit_customers')" :href="route('customers.edit', { customer: customer.id })" class="ds-button ds-button-primary">
              <PencilSquareIcon class="h-4 w-4" />
              Editar cliente
            </Link>
          </div>
        </div>
      </div>

      <dl class="grid gap-px bg-[var(--ds-border)] sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="metric in metrics" :key="metric.label" class="bg-[var(--ds-panel)] p-5">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</dt>
              <dd class="mt-3 break-words text-xl font-bold text-[var(--ds-text)]">{{ metric.value }}</dd>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ metric.detail }}</p>
            </div>
            <component :is="metric.icon" class="h-5 w-5 shrink-0 text-[var(--ds-text-soft)]" />
          </div>
        </div>
      </dl>
    </section>

    <div v-if="hasCommercialAttention || !primarySite" class="ds-alert ds-alert-warning flex items-start gap-3">
      <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 shrink-0" />
      <div>
        <p class="text-sm font-bold">A conta requer atencao</p>
        <p class="mt-1 text-sm font-medium">
          <span v-if="summary.open_amount_due">Existe {{ formatCurrency(summary.open_amount_due) }} por regularizar. </span>
          <span v-if="summary.open_requests">Ha {{ summary.open_requests }} pedido(s) aberto(s) no portal. </span>
          <span v-if="!primarySite">O local operacional principal ainda não foi definido.</span>
        </p>
      </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
      <div class="min-w-0 space-y-6">
        <section class="ds-card overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:flex sm:items-start sm:justify-between sm:gap-4 sm:px-6">
            <div>
              <h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
                <BeakerIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />
                Execução laboratorial recente
              </h2>
              <p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">Últimas amostras recebidas para esta conta.</p>
            </div>
            <span class="ds-chip mt-3 sm:mt-0">{{ recentSamples.length }} registo(s)</span>
          </header>

          <div v-if="recentSamples.length" class="divide-y divide-[var(--ds-border)]">
            <article v-for="sample in recentSamples" :key="sample.id" class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <h3 class="break-words text-sm font-bold text-[var(--ds-text)]">{{ sample.name || "Amostra sem nome" }}</h3>
                  <span :class="['ds-chip', statusClass(sample.status)]">{{ sample.status || "Sem estado" }}</span>
                </div>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]"><span class="font-mono">{{ sample.code || "Sem código" }}</span> / recebida {{ formatDate(sample.received_at) }}</p>
              </div>
              <div class="inline-flex items-center gap-1.5 text-xs font-semibold text-[var(--ds-text-muted)]">
                <ClockIcon class="h-4 w-4" />
                Fim: {{ formatDate(sample.analysis_end_date) }}
              </div>
            </article>
          </div>
          <div v-else class="ds-empty-state m-5 py-10 text-center sm:m-6">
            <BeakerIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
            <p class="mt-3 text-sm font-bold text-[var(--ds-text)]">Sem amostras recentes</p>
          </div>
        </section>

        <section class="ds-card overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
            <h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
              <ClipboardDocumentCheckIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />
              Pedidos do portal
            </h2>
            <p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">Pedidos recentes submetidos pelo cliente.</p>
          </header>
          <div v-if="recentRequests.length" class="divide-y divide-[var(--ds-border)]">
            <article v-for="request in recentRequests" :key="request.id" class="px-5 py-4 sm:px-6">
              <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                  <h3 class="break-words text-sm font-bold text-[var(--ds-text)]">{{ request.title }}</h3>
                  <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]"><span class="font-mono">{{ request.reference || "Sem referência" }}</span> / {{ request.request_type || "Sem tipo" }}</p>
                </div>
                <span :class="['ds-chip', statusClass(request.status)]">{{ request.status || "Sem estado" }}</span>
              </div>
            </article>
          </div>
          <div v-else class="ds-empty-state m-5 py-10 text-center sm:m-6">
            <ClipboardDocumentCheckIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
            <p class="mt-3 text-sm font-bold text-[var(--ds-text)]">Sem pedidos recentes</p>
          </div>
        </section>

        <section class="ds-card overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:flex sm:items-start sm:justify-between sm:gap-4 sm:px-6">
            <div>
              <h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
                <MapPinIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />
                Locais e pontos focais
              </h2>
              <p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">Endereços operacionais associados a recolha, recepção e facturação.</p>
            </div>
            <span class="ds-chip mt-3 sm:mt-0">{{ sites.length }} local(is)</span>
          </header>

          <div v-if="sites.length" class="divide-y divide-[var(--ds-border)]">
            <article v-for="site in sites" :key="site.id" class="px-5 py-5 sm:px-6">
              <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div class="min-w-0">
                  <div class="flex flex-wrap items-center gap-2">
                    <h3 class="break-words text-sm font-bold text-[var(--ds-text)]">{{ site.name || site.code || "Local sem nome" }}</h3>
                    <span v-if="Number(site.id) === Number(customer.warehouse_id)" class="ds-chip bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20">
                      <StarIcon class="h-3.5 w-3.5" /> Principal
                    </span>
                  </div>
                  <p class="mt-2 text-sm font-semibold text-[var(--ds-text-muted)]">{{ site.address || "Endereço não definido" }}</p>
                  <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">{{ [site.municipality, site.province].filter(Boolean).join(", ") || "Localidade não definida" }}</p>
                </div>
                <div class="grid gap-2 text-xs font-semibold text-[var(--ds-text-muted)] sm:grid-cols-2 md:min-w-72 md:grid-cols-1">
                  <span class="inline-flex items-center gap-2"><UserCircleIcon class="h-4 w-4" />{{ site.focal_point || "Sem ponto focal" }}</span>
                  <span class="inline-flex items-center gap-2"><EnvelopeIcon class="h-4 w-4" />{{ site.focal_point_email || site.email || "Sem email" }}</span>
                  <span class="inline-flex items-center gap-2"><PhoneIcon class="h-4 w-4" />{{ site.focal_point_contact || site.primary_phone || "Sem telefone" }}</span>
                </div>
              </div>
            </article>
          </div>
          <div v-else class="ds-empty-state m-5 py-10 text-center sm:m-6">
            <MapPinIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
            <p class="mt-3 text-sm font-bold text-[var(--ds-text)]">Sem locais associados</p>
          </div>
        </section>
      </div>

      <aside class="space-y-6">
        <section class="ds-card overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4">
            <h2 class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]">
              <BuildingOffice2Icon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />
              Conta
            </h2>
          </header>
          <dl class="divide-y divide-[var(--ds-border)] px-5">
            <div class="py-4"><dt class="ds-field-label">Nome legal</dt><dd class="mt-1.5 break-words text-sm font-bold text-[var(--ds-text)]">{{ customer.name }}</dd></div>
            <div class="py-4"><dt class="ds-field-label">Código</dt><dd class="mt-1.5 break-words font-mono text-sm font-bold text-[var(--ds-text)]">{{ customer.code || "Não definido" }}</dd></div>
            <div class="py-4"><dt class="ds-field-label">Categoria</dt><dd class="mt-1.5 break-words text-sm font-bold text-[var(--ds-text)]">{{ customer.category || "Não definida" }}</dd></div>
            <div class="py-4"><dt class="ds-field-label">Descrição</dt><dd class="mt-1.5 whitespace-pre-line break-words text-sm font-semibold text-[var(--ds-text-muted)]">{{ customer.description || "Sem descrição" }}</dd></div>
          </dl>
        </section>

        <section class="ds-card overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4">
            <h2 class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]">
              <BanknotesIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />
              Facturas em aberto
            </h2>
          </header>
          <div v-if="openFinance.length" class="divide-y divide-[var(--ds-border)]">
            <article v-for="invoice in openFinance" :key="invoice.id" class="px-5 py-4">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0"><p class="break-words font-mono text-xs font-bold text-[var(--ds-text)]">{{ invoice.reference }}</p><p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ formatDate(invoice.date) }}</p></div>
                <p class="text-right text-sm font-bold text-rose-700 dark:text-rose-200">{{ formatCurrency(invoice.amount_due) }}</p>
              </div>
            </article>
          </div>
          <p v-else class="px-5 py-6 text-sm font-semibold text-[var(--ds-text-muted)]">Sem facturas em aberto.</p>
        </section>

        <section class="ds-card overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4">
            <h2 class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]">
              <DocumentTextIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" /> Evidência documental </h2>
          </header>
          <dl class="divide-y divide-[var(--ds-border)]">
            <div v-for="fact in governanceFacts" :key="fact.label" class="flex items-center justify-between gap-3 px-5 py-3.5">
              <dt class="inline-flex items-center gap-2 text-xs font-bold text-[var(--ds-text-muted)]"><component :is="fact.icon" class="h-4 w-4" />{{ fact.label }}</dt>
              <dd class="font-mono text-sm font-bold text-[var(--ds-text)]">{{ fact.value }}</dd>
            </div>
          </dl>
        </section>
      </aside>
    </div>
  </div>
</template>
