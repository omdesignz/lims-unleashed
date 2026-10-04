<script setup>
import { usePermission } from "@/Composables/usePermissions";
import PageHeader from "@/Components/plano/PageHeader.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link } from "@inertiajs/vue3";
import {
  ArrowLeft as ArrowLeftIcon,
  FlaskConical as BeakerIcon,
  Building2 as BuildingOffice2Icon,
  BadgeCheck as CheckBadgeIcon,
  Clock as ClockIcon,
  Mail as EnvelopeIcon,
  TriangleAlert as ExclamationTriangleIcon,
  Info as InformationCircleIcon,
  MapPin as MapPinIcon,
  SquarePen as PencilSquareIcon,
  Phone as PhoneIcon,
  Star as StarIcon,
  CircleUser as UserCircleIcon,
} from "@lucide/vue";
import { computed } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, required: true },
  customerState: { type: Object, default: () => ({}) },
});

const { hasPermission } = usePermission();
const customer = computed(() => props.record?.data ?? props.record ?? {});
const summary = computed(() => props.customerState?.summary ?? {});
const sites = computed(() => customer.value.warehouses ?? []);
const primarySite = computed(() => sites.value.find((site) => Number(site.id) === Number(customer.value.warehouse_id)) ?? customer.value.warehouse ?? null);
const recentSamples = computed(() => props.customerState?.recent_samples ?? []);

const metrics = computed(() => [
  { label: "Propostas aceites", value: summary.value.accepted_proposals || 0, detail: "deste laboratório", icon: CheckBadgeIcon },
  { label: "Amostras em curso", value: summary.value.samples_in_progress || 0, detail: "deste laboratório", icon: BeakerIcon },
  { label: "Amostras concluídas", value: summary.value.completed_samples || 0, detail: "deste laboratório", icon: CheckBadgeIcon },
]);

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
  <div class="pl-page space-y-6">
    <PageHeader :trail="[{ title: 'Clientes', url: route('customers.index') }, { title: customer.name }]" :title="customer.name" lede="Identidade e locais partilhados; execução visível apenas para o laboratório activo.">
      <template #badges>
        <span v-if="customer.code" class="ds-chip font-mono">{{ customer.code }}</span>
        <span class="ds-chip">{{ customer.category || "Sem categoria" }}</span>
        <span :class="['ds-chip', customer.deleted ? 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20' : 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20']">
          {{ customer.deleted ? "Arquivado" : "Activo" }}
        </span>
      </template>
      <template #actions>
        <Link v-if="hasPermission('edit_customers')" :href="route('customers.edit', { customer: customer.id })" class="ds-button ds-button-primary">
          <PencilSquareIcon class="h-4 w-4" />
          Editar cliente
        </Link>
      </template>
    </PageHeader>

    <dl class="pl-cells">
      <div v-for="metric in metrics" :key="metric.label" class="pl-cell">
        <dt class="pl-k pl-muted">{{ metric.label }}</dt>
        <dd class="pl-cell-value">{{ metric.value }}</dd>
      </div>
    </dl>

    <div v-if="!primarySite" class="ds-card flex items-start gap-3 px-5 py-4 text-amber-800 dark:text-amber-200">
      <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 shrink-0" />
      <div>
        <p class="text-sm font-bold">Local principal por definir</p>
        <p class="mt-1 text-sm font-medium">O local operacional principal ainda não foi definido.</p>
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
              <p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">Últimas amostras deste cliente no laboratório activo.</p>
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
              <InformationCircleIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" /> Âmbito deste dossier
            </h2>
          </header>
          <p class="px-5 py-4 text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
            Os dados do cliente e os locais são partilhados. Propostas e amostras pertencem apenas ao laboratório activo. Consulte facturas, pedidos do portal e outros documentos nas respectivas áreas, de acordo com as suas permissões.
          </p>
        </section>
      </aside>
    </div>
  </div>
</template>
