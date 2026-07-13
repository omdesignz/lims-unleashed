<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { computed, ref } from "vue";
import { Link, router } from "@inertiajs/vue3";
import {
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  ArrowsRightLeftIcon,
  ChevronDownIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  PrinterIcon,
  UserIcon,
} from "@heroicons/vue/24/outline";

defineOptions({
  layout: Layout,
});

const props = defineProps({
  certificate: {
    type: Object,
    default: () => ({}),
  },
  revisionA: {
    type: Object,
    default: () => ({}),
  },
  revisionB: {
    type: Object,
    default: () => ({}),
  },
  differences: {
    type: Array,
    default: () => [],
  },
});

const expandedGroups = ref({});

const differenceGroups = computed(() => {
  if (props.differences.every((difference) => Array.isArray(difference.items))) {
    return props.differences.map((group) => ({
      ...group,
      items: group.items ?? [],
      count: group.count ?? group.items?.length ?? 0,
    }));
  }

  const labels = {
    certificate: "Dados do certificado",
    metadata: "Metadados da revisao",
    related: "Dados relacionados",
    iso: "Conformidade ISO",
  };
  const groups = new Map();

  props.differences.forEach((difference) => {
    const category = difference.category || "other";
    const group = groups.get(category) ?? {
      category,
      label: labels[category] || "Outras alteracoes",
      items: [],
    };
    group.items.push(difference);
    groups.set(category, group);
  });

  return Array.from(groups.values()).map((group) => ({
    ...group,
    count: group.items.length,
  }));
});

const flattenedDifferences = computed(() => {
  return differenceGroups.value.flatMap((group) => group.items);
});

const highImpactChanges = computed(() => {
  return flattenedDifferences.value.filter(
    (difference) => difference.impact === "HIGH" || difference.impact === "CRITICAL",
  ).length;
});

const comparisonMetrics = computed(() => [
  {
    label: "Alteracoes encontradas",
    value: flattenedDifferences.value.length,
    note: `${differenceGroups.value.length} categoria(s)`,
  },
  {
    label: "Impacto elevado",
    value: highImpactChanges.value,
    note: "exigem revisao prioritaria",
  },
  {
    label: "Intervalo temporal",
    value: timeBetweenRevisions.value,
    note: "entre datas efetivas",
  },
  {
    label: "Direcao",
    value: `v${props.revisionA?.version || "-"} to v${props.revisionB?.version || "-"}`,
    note: "A para B",
  },
]);

const timeBetweenRevisions = computed(() => {
  if (!props.revisionA?.effective_date || !props.revisionB?.effective_date) {
    return "Nao calculado";
  }

  const firstDate = new Date(props.revisionA.effective_date);
  const secondDate = new Date(props.revisionB.effective_date);
  const days = Math.ceil(Math.abs(secondDate - firstDate) / 86400000);

  if (days === 0) {
    return "Mesmo dia";
  }

  if (days < 30) {
    return `${days} dia(s)`;
  }

  if (days < 365) {
    return `${Math.floor(days / 30)} mes(es)`;
  }

  return `${Math.floor(days / 365)} ano(s)`;
});

function revisionDetails(revision) {
  return [
    {
      label: "Revisao",
      value: revision?.revision_number ?? "-",
    },
    {
      label: "Data efetiva",
      value: formatDate(revision?.effective_date),
    },
    {
      label: "Tipo",
      value: changeTypeLabel(revision?.change_type),
    },
    {
      label: "Criado por",
      value: revision?.created_by?.name || "Sistema",
    },
    {
      label: "Aprovado por",
      value: revision?.approved_by?.name || "Pendente",
    },
    {
      label: "Risco",
      value: revision?.compliance_metadata?.risk_assessment || "Nao avaliado",
    },
  ];
}

function formatDate(date) {
  if (!date) {
    return "Nao registada";
  }

  return new Date(date).toLocaleString("pt-PT", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function formatValue(value) {
  if (value === null || value === undefined || value === "") {
    return "Nao registado";
  }

  if (typeof value === "boolean") {
    return value ? "Sim" : "Nao";
  }

  if (Array.isArray(value)) {
    return value.length ? value.join(", ") : "Sem valores";
  }

  if (typeof value === "object") {
    return JSON.stringify(value);
  }

  return String(value);
}

function changeTypeLabel(changeType) {
  const labels = {
    CREATED: "Criacao",
    UPDATED: "Atualizacao",
    CORRECTED: "Correcao",
    REISSUED: "Reemissao",
    WITHDRAWN: "Retirada",
    ADDED: "Adicionado",
    REMOVED: "Removido",
    MODIFIED: "Modificado",
  };

  return labels[changeType] || changeType || "Alteracao";
}

function impactDot(impact) {
  const tones = {
    CRITICAL: "lims-status-dot-critical",
    HIGH: "lims-status-dot-critical",
    MEDIUM: "lims-status-dot-hold",
    LOW: "lims-status-dot-release",
  };

  return tones[impact] || "lims-status-dot-instrument";
}

function toggleGroup(category) {
  expandedGroups.value[category] = !expandedGroups.value[category];
}

function swapRevisions() {
  router.get(
    route("qualitycertificates.iso-revisions.compare-two", {
      certificate: props.certificate.id,
      revision_a: props.revisionB.id,
      revision_b: props.revisionA.id,
    }),
  );
}

function printComparison() {
  window.print();
}
</script>

<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden print:shadow-none">
      <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 max-w-3xl">
          <Link
            :href="route('qualitycertificates.iso-revisions.index', certificate.id)"
            class="ds-table-action -ml-2 mb-3 print:hidden"
          >
            <ArrowLeftIcon class="h-4 w-4" />
            Voltar ao historico
          </Link>
          <div class="flex flex-wrap items-center gap-2">
            <p class="ds-kicker">Comparacao controlada</p>
            <span class="ds-chip font-mono">{{ certificate.code || "Sem codigo" }}</span>
          </div>
          <h1 class="ds-heading mt-2 text-2xl">Comparar revisoes</h1>
          <p class="ds-copy mt-2 max-w-2xl text-sm">
            Leitura lado a lado das diferencas de conteudo, relacoes e metadados ISO.
          </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row print:hidden">
          <button type="button" class="ds-button ds-button-secondary" @click="swapRevisions">
            <ArrowsRightLeftIcon class="h-4 w-4" />
            Inverter
          </button>
          <a
            :href="route('iso-revisions.export-comparison', { certificate: certificate.id, revision_a: revisionA.id, revision_b: revisionB.id })"
            class="ds-button ds-button-secondary"
          >
            <ArrowDownTrayIcon class="h-4 w-4" />
            Exportar PDF
          </a>
          <button type="button" class="ds-button ds-button-primary" @click="printComparison">
            <PrinterIcon class="h-4 w-4" />
            Imprimir
          </button>
        </div>
      </div>

      <dl class="grid border-t border-[var(--ds-border)] sm:grid-cols-2 xl:grid-cols-4 xl:divide-x xl:divide-[var(--ds-border)]">
        <div
          v-for="metric in comparisonMetrics"
          :key="metric.label"
          class="border-b border-[var(--ds-border)] px-5 py-4 last:border-b-0 sm:[&:nth-last-child(-n+2)]:border-b-0 xl:border-b-0"
        >
          <dt class="ds-table-heading">{{ metric.label }}</dt>
          <dd class="ds-heading mt-2 text-lg">{{ metric.value }}</dd>
          <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">{{ metric.note }}</p>
        </div>
      </dl>
    </section>

    <section class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] lg:items-stretch">
      <article class="ds-command-surface overflow-hidden">
        <div class="border-b border-[var(--ds-border)] px-5 py-4">
          <div class="flex items-center justify-between gap-3">
            <div>
              <p class="ds-kicker">Revisao A</p>
              <h2 class="ds-heading mt-2 text-lg">v{{ revisionA.version }}</h2>
            </div>
            <span class="ds-chip">
              <span class="lims-status-dot lims-status-dot-instrument" />
              {{ changeTypeLabel(revisionA.change_type) }}
            </span>
          </div>
        </div>
        <dl class="divide-y divide-[var(--ds-border)]">
          <div
            v-for="detail in revisionDetails(revisionA)"
            :key="detail.label"
            class="flex items-start justify-between gap-4 px-5 py-3"
          >
            <dt class="text-xs font-bold text-[var(--ds-text-muted)]">{{ detail.label }}</dt>
            <dd class="max-w-[14rem] break-words text-right text-xs font-bold text-[var(--ds-text)]">
              {{ detail.value }}
            </dd>
          </div>
        </dl>
        <div class="border-t border-[var(--ds-border)] px-5 py-4">
          <p class="ds-table-heading">Motivo</p>
          <p class="ds-copy mt-2 text-xs">{{ revisionA.change_reason || "Nao registado." }}</p>
        </div>
      </article>

      <div class="hidden items-center justify-center lg:flex print:hidden">
        <button
          type="button"
          class="ds-icon-button bg-[var(--ds-panel-raised)]"
          title="Inverter revisoes"
          @click="swapRevisions"
        >
          <ArrowsRightLeftIcon class="h-5 w-5" />
          <span class="sr-only">Inverter revisoes</span>
        </button>
      </div>

      <article class="ds-command-surface overflow-hidden">
        <div class="border-b border-[var(--ds-border)] px-5 py-4">
          <div class="flex items-center justify-between gap-3">
            <div>
              <p class="ds-kicker">Revisao B</p>
              <h2 class="ds-heading mt-2 text-lg">v{{ revisionB.version }}</h2>
            </div>
            <span class="ds-chip">
              <span class="lims-status-dot lims-status-dot-release" />
              {{ changeTypeLabel(revisionB.change_type) }}
            </span>
          </div>
        </div>
        <dl class="divide-y divide-[var(--ds-border)]">
          <div
            v-for="detail in revisionDetails(revisionB)"
            :key="detail.label"
            class="flex items-start justify-between gap-4 px-5 py-3"
          >
            <dt class="text-xs font-bold text-[var(--ds-text-muted)]">{{ detail.label }}</dt>
            <dd class="max-w-[14rem] break-words text-right text-xs font-bold text-[var(--ds-text)]">
              {{ detail.value }}
            </dd>
          </div>
        </dl>
        <div class="border-t border-[var(--ds-border)] px-5 py-4">
          <p class="ds-table-heading">Motivo</p>
          <p class="ds-copy mt-2 text-xs">{{ revisionB.change_reason || "Nao registado." }}</p>
        </div>
      </article>
    </section>

    <section class="ds-panel overflow-hidden print:shadow-none">
      <div class="flex flex-col gap-3 border-b border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div>
          <p class="ds-kicker">Matriz de diferencas</p>
          <h2 class="ds-heading mt-2 text-lg">Alteracoes por categoria</h2>
          <p class="ds-copy mt-1 text-sm">
            Valores da revisao A comparados com a revisao B.
          </p>
        </div>
        <span v-if="highImpactChanges" class="ds-chip">
          <span class="lims-status-dot lims-status-dot-critical" />
          {{ highImpactChanges }} impacto(s) elevado(s)
        </span>
      </div>

      <div v-if="differenceGroups.length" class="divide-y divide-[var(--ds-border)]">
        <article v-for="group in differenceGroups" :key="group.category">
          <button
            type="button"
            class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left hover:bg-[var(--ds-panel-subtle)] sm:px-6"
            :aria-expanded="Boolean(expandedGroups[group.category])"
            @click="toggleGroup(group.category)"
          >
            <span>
              <span class="ds-heading block text-sm">{{ group.label }}</span>
              <span class="mt-1 block text-xs font-semibold text-[var(--ds-text-muted)]">
                {{ group.count }} alteracao(oes)
              </span>
            </span>
            <ChevronDownIcon
              :class="[
                'h-5 w-5 text-[var(--ds-text-muted)] transition-transform',
                expandedGroups[group.category] ? 'rotate-180' : '',
              ]"
            />
          </button>

          <div v-if="expandedGroups[group.category]" class="border-t border-[var(--ds-border)]">
            <div class="hidden overflow-x-auto md:block">
              <DataTable class="min-w-full">
                <thead class="ds-table-head">
                  <tr>
                    <th class="ds-table-heading px-5 py-3 text-left">Campo</th>
                    <th class="ds-table-heading px-4 py-3 text-left">Revisao A</th>
                    <th class="ds-table-heading px-4 py-3 text-left">Revisao B</th>
                    <th class="ds-table-heading px-5 py-3 text-right">Impacto</th>
                  </tr>
                </thead>
                <tbody class="ds-table-body divide-y divide-[var(--ds-border)]">
                  <tr v-for="item in group.items" :key="item.field" class="ds-table-row">
                    <td class="max-w-xs px-5 py-4">
                      <p class="text-sm font-bold text-[var(--ds-text)]">{{ item.label || item.field }}</p>
                      <p v-if="item.description" class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
                        {{ item.description }}
                      </p>
                    </td>
                    <td class="ds-table-cell max-w-xs break-words px-4 py-4">{{ formatValue(item.valueA) }}</td>
                    <td class="ds-table-cell max-w-xs break-words px-4 py-4">{{ formatValue(item.valueB) }}</td>
                    <td class="px-5 py-4 text-right">
                      <span class="inline-flex items-center gap-2 text-xs font-bold text-[var(--ds-text)]">
                        <span :class="['lims-status-dot', impactDot(item.impact)]" />
                        {{ item.impact || "INFO" }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </DataTable>
            </div>

            <div class="divide-y divide-[var(--ds-border)] md:hidden">
              <article v-for="item in group.items" :key="item.field" class="space-y-3 px-5 py-4">
                <div class="flex items-start justify-between gap-3">
                  <h3 class="ds-heading text-sm">{{ item.label || item.field }}</h3>
                  <span class="inline-flex items-center gap-2 text-xs font-bold text-[var(--ds-text)]">
                    <span :class="['lims-status-dot', impactDot(item.impact)]" />
                    {{ item.impact || "INFO" }}
                  </span>
                </div>
                <div class="grid gap-2">
                  <div class="ds-command-toolbar p-3">
                    <p class="ds-table-heading">Revisao A</p>
                    <p class="mt-2 break-words text-sm font-semibold text-[var(--ds-text)]">{{ formatValue(item.valueA) }}</p>
                  </div>
                  <div class="ds-command-toolbar p-3">
                    <p class="ds-table-heading">Revisao B</p>
                    <p class="mt-2 break-words text-sm font-semibold text-[var(--ds-text)]">{{ formatValue(item.valueB) }}</p>
                  </div>
                </div>
              </article>
            </div>
          </div>
        </article>
      </div>

      <div v-else class="ds-empty-state m-5 p-10 text-center">
        <ClockIcon class="mx-auto h-7 w-7 text-[var(--ds-text-soft)]" />
        <h3 class="ds-heading mt-3 text-sm">Nenhuma diferenca encontrada</h3>
        <p class="ds-copy mt-1 text-xs">As duas revisoes preservam o mesmo conteudo comparavel.</p>
      </div>
    </section>

    <section v-if="highImpactChanges" class="lims-status-strip p-5 print:hidden">
      <div class="flex items-start gap-3">
        <ExclamationTriangleIcon class="h-5 w-5 shrink-0 text-[var(--lims-critical)]" />
        <div>
          <h2 class="ds-heading text-sm">Revisao tecnica necessaria</h2>
          <p class="ds-copy mt-1 text-xs">
            Existem alteracoes de impacto elevado. Confirme a rastreabilidade e
            a aprovacao antes de utilizar a revisao B como evidencia.
          </p>
        </div>
      </div>
    </section>
  </div>
</template>
