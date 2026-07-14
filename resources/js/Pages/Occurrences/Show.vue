<script setup>
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link } from "@inertiajs/vue3";
import {
  ArrowLeftIcon,
  BellAlertIcon,
  BuildingOffice2Icon,
  CalendarDaysIcon,
  CheckCircleIcon,
  ChatBubbleLeftRightIcon,
  ClockIcon,
  DocumentMagnifyingGlassIcon,
  ExclamationTriangleIcon,
  MagnifyingGlassIcon,
  PencilSquareIcon,
  ShieldCheckIcon,
  UserCircleIcon,
  WrenchScrewdriverIcon,
} from "@heroicons/vue/24/outline";
import { computed } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, required: true },
});

const { hasPermission } = usePermission();
const occurrence = computed(() => props.record?.data ?? props.record ?? {});
const isOverdue = computed(() => occurrence.value.implementation_date_overdue && !occurrence.value.date_closed);
const lifecycleState = computed(() => {
  if (occurrence.value.date_closed) {
    return { label: "Encerrada", className: "ds-badge-success" };
  }

  if (occurrence.value.date_resolved) {
    return { label: "Resolvida", className: "ds-badge-info" };
  }

  if (isOverdue.value) {
    return { label: "Prazo excedido", className: "ds-badge-warning" };
  }

  return { label: occurrence.value.status || "Em tratamento", className: "ds-badge-neutral" };
});
const timeline = computed(() => [
  { label: "Registada", date: occurrence.value.date_reported, icon: BellAlertIcon },
  { label: "Notificação interna", date: occurrence.value.notification_date, icon: CalendarDaysIcon },
  { label: "Prazo de implementação", date: occurrence.value.implementation_date, icon: ClockIcon, warning: isOverdue.value },
  { label: "Resolvida", date: occurrence.value.date_resolved, icon: CheckCircleIcon },
  { label: "Encerrada", date: occurrence.value.date_closed, icon: ShieldCheckIcon },
].filter((item) => item.date));
const hasInvestigation = computed(() => [
  occurrence.value.analysis,
  occurrence.value.cause_corrective_actions,
  occurrence.value.effect_corrective_actions,
].some(Boolean));
const hasClientProcess = computed(() => [
  occurrence.value.client_process_open_notification_date,
  occurrence.value.client_process_close_notification_date,
  occurrence.value.client_acceptance,
  occurrence.value.client_acceptance_comments,
].some((value) => value !== null && value !== undefined && value !== ""));

function formatDate(value) {
  if (!value) {
    return "Não registada";
  }

  return new Intl.DateTimeFormat("pt-PT", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    timeZone: "UTC",
  }).format(new Date(value));
}

function booleanLabel(value, trueLabel = "Sim", falseLabel = "Não") {
  if (value === null || value === undefined) {
    return "Não definido";
  }

  return value ? trueLabel : falseLabel;
}
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
          <Link :href="route('occurrences.index')" class="ds-button ds-button-ghost -ml-3 w-fit">
            <ArrowLeftIcon class="h-4 w-4" />
            Ocorrências
          </Link>
          <div class="mt-3 flex items-start gap-3">
            <span
              class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border bg-[var(--ds-panel-raised)]"
              :class="isOverdue ? 'border-amber-300 text-amber-700 dark:border-amber-700 dark:text-amber-300' : 'border-[var(--ds-border)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200'"
            >
              <ExclamationTriangleIcon v-if="isOverdue" class="h-5 w-5" />
              <DocumentMagnifyingGlassIcon v-else class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <p class="ds-kicker">Dossier de ocorrência</p>
              <h1 class="ds-heading mt-1 text-2xl">{{ occurrence.occurrence_no || `Ocorrência ${occurrence.id}` }}</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm">Evidência consolidada da triagem, investigação, acção correctiva e encerramento.</p>
            </div>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <span class="ds-badge" :class="lifecycleState.className">{{ lifecycleState.label }}</span>
          <Link
            v-if="hasPermission('edit_occurrences')"
            :href="route('occurrences.edit', { occurrence: occurrence.id })"
            class="ds-button ds-button-primary"
          >
            <PencilSquareIcon class="h-4 w-4" />
            Editar ocorrência
          </Link>
        </div>
      </div>

      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-2 xl:grid-cols-4">
        <div class="border-b border-[var(--ds-border)] px-4 py-3 sm:border-r xl:border-b-0">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Data do registo</dt>
          <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ formatDate(occurrence.date_reported) }}</dd>
        </div>
        <div class="border-b border-[var(--ds-border)] px-4 py-3 xl:border-b-0 xl:border-r">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Implementação</dt>
          <dd class="mt-2 text-sm font-bold" :class="isOverdue ? 'text-amber-700 dark:text-amber-300' : 'text-[var(--ds-text)]'">{{ formatDate(occurrence.implementation_date) }}</dd>
        </div>
        <div class="border-b border-[var(--ds-border)] px-4 py-3 sm:border-b-0 sm:border-r">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Resolução</dt>
          <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ formatDate(occurrence.date_resolved) }}</dd>
        </div>
        <div class="px-4 py-3">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Encerramento</dt>
          <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ formatDate(occurrence.date_closed) }}</dd>
        </div>
      </dl>
    </section>

    <section v-if="isOverdue" class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-amber-950 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-100">
      <div class="flex gap-3">
        <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 shrink-0" />
        <div>
          <h2 class="text-sm font-bold">Acção correctiva fora do prazo</h2>
          <p class="mt-1 text-sm leading-6">O prazo de implementação terminou e o dossier ainda não tem encerramento registado.</p>
        </div>
      </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem] xl:items-start">
      <div class="space-y-6">
        <section class="ds-panel p-5 sm:p-6">
          <div class="flex items-start gap-3 border-b border-[var(--ds-border)] pb-4">
            <BellAlertIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
            <div>
              <p class="ds-kicker">Ocorrência observada</p>
              <h2 class="ds-heading mt-2 text-lg">Descrição do desvio</h2>
            </div>
          </div>
          <p class="mt-5 whitespace-pre-wrap text-sm leading-7 text-[var(--ds-text)]">{{ occurrence.issue_description || "Sem descrição registada." }}</p>
        </section>

        <section v-if="hasInvestigation" class="ds-panel p-5 sm:p-6">
          <div class="flex items-start gap-3 border-b border-[var(--ds-border)] pb-4">
            <MagnifyingGlassIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
            <div>
              <p class="ds-kicker">Investigação</p>
              <h2 class="ds-heading mt-2 text-lg">Análise de causa e efeito</h2>
            </div>
          </div>
          <dl class="mt-5 divide-y divide-[var(--ds-border)]">
            <div v-if="occurrence.analysis" class="grid gap-2 py-4 first:pt-0 sm:grid-cols-[12rem_minmax(0,1fr)] sm:gap-6">
              <dt class="text-sm font-bold text-[var(--ds-text-muted)]">Análise</dt>
              <dd class="whitespace-pre-wrap text-sm leading-7 text-[var(--ds-text)]">{{ occurrence.analysis }}</dd>
            </div>
            <div v-if="occurrence.cause_corrective_actions" class="grid gap-2 py-4 sm:grid-cols-[12rem_minmax(0,1fr)] sm:gap-6">
              <dt class="text-sm font-bold text-[var(--ds-text-muted)]">Causa identificada</dt>
              <dd class="whitespace-pre-wrap text-sm leading-7 text-[var(--ds-text)]">{{ occurrence.cause_corrective_actions }}</dd>
            </div>
            <div v-if="occurrence.effect_corrective_actions" class="grid gap-2 py-4 last:pb-0 sm:grid-cols-[12rem_minmax(0,1fr)] sm:gap-6">
              <dt class="text-sm font-bold text-[var(--ds-text-muted)]">Efeito observado</dt>
              <dd class="whitespace-pre-wrap text-sm leading-7 text-[var(--ds-text)]">{{ occurrence.effect_corrective_actions }}</dd>
            </div>
          </dl>
        </section>

        <section class="ds-panel p-5 sm:p-6">
          <div class="flex items-start gap-3 border-b border-[var(--ds-border)] pb-4">
            <WrenchScrewdriverIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
            <div>
              <p class="ds-kicker">CAPA</p>
              <h2 class="ds-heading mt-2 text-lg">Acção correctiva e eficácia</h2>
            </div>
          </div>
          <div class="mt-5">
            <p class="whitespace-pre-wrap text-sm leading-7 text-[var(--ds-text)]">{{ occurrence.corrective_action || "Sem acção correctiva registada." }}</p>
            <dl class="mt-5 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-2">
              <div class="border-b border-[var(--ds-border)] px-4 py-3 sm:border-b-0 sm:border-r">
                <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Prazo de implementação</dt>
                <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ formatDate(occurrence.implementation_date) }}</dd>
              </div>
              <div class="px-4 py-3">
                <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Verificação de eficácia</dt>
                <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ booleanLabel(occurrence.was_effective, "Eficaz", "Não eficaz") }}</dd>
              </div>
            </dl>
          </div>
        </section>

        <section v-if="hasClientProcess" class="ds-panel p-5 sm:p-6">
          <div class="flex items-start gap-3 border-b border-[var(--ds-border)] pb-4">
            <ChatBubbleLeftRightIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
            <div>
              <p class="ds-kicker">Comunicação externa</p>
              <h2 class="ds-heading mt-2 text-lg">Processo com o cliente</h2>
            </div>
          </div>
          <dl class="mt-5 divide-y divide-[var(--ds-border)]">
            <div class="grid gap-2 py-3 first:pt-0 sm:grid-cols-[12rem_minmax(0,1fr)] sm:gap-6">
              <dt class="text-sm font-bold text-[var(--ds-text-muted)]">Notificação de abertura</dt>
              <dd class="text-sm text-[var(--ds-text)]">{{ formatDate(occurrence.client_process_open_notification_date) }}</dd>
            </div>
            <div class="grid gap-2 py-3 sm:grid-cols-[12rem_minmax(0,1fr)] sm:gap-6">
              <dt class="text-sm font-bold text-[var(--ds-text-muted)]">Notificação de fecho</dt>
              <dd class="text-sm text-[var(--ds-text)]">{{ formatDate(occurrence.client_process_close_notification_date) }}</dd>
            </div>
            <div class="grid gap-2 py-3 sm:grid-cols-[12rem_minmax(0,1fr)] sm:gap-6">
              <dt class="text-sm font-bold text-[var(--ds-text-muted)]">Aceitação</dt>
              <dd class="text-sm text-[var(--ds-text)]">{{ booleanLabel(occurrence.client_acceptance, "Aceite", "Rejeitada") }}</dd>
            </div>
            <div v-if="occurrence.client_acceptance_comments" class="grid gap-2 py-3 last:pb-0 sm:grid-cols-[12rem_minmax(0,1fr)] sm:gap-6">
              <dt class="text-sm font-bold text-[var(--ds-text-muted)]">Comentários</dt>
              <dd class="whitespace-pre-wrap text-sm leading-7 text-[var(--ds-text)]">{{ occurrence.client_acceptance_comments }}</dd>
            </div>
          </dl>
        </section>

        <section v-if="occurrence.obs" class="ds-panel p-5 sm:p-6">
          <p class="ds-kicker">Observações internas</p>
          <p class="mt-3 whitespace-pre-wrap text-sm leading-7 text-[var(--ds-text)]">{{ occurrence.obs }}</p>
        </section>
      </div>

      <aside class="space-y-6 xl:sticky xl:top-24">
        <section class="ds-panel p-5">
          <div class="flex items-start gap-3">
            <ClockIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
            <div>
              <p class="ds-kicker">Linha temporal</p>
              <h2 class="ds-heading mt-2 text-base">Marcos registados</h2>
            </div>
          </div>
          <ol class="mt-5 space-y-4">
            <li v-for="item in timeline" :key="item.label" class="flex gap-3">
              <span
                class="grid h-8 w-8 shrink-0 place-items-center rounded-lg border bg-[var(--ds-panel-subtle)]"
                :class="item.warning ? 'border-amber-300 text-amber-700 dark:border-amber-700 dark:text-amber-300' : 'border-[var(--ds-border)] text-[var(--ds-text-soft)]'"
              >
                <component :is="item.icon" class="h-4 w-4" />
              </span>
              <div>
                <p class="text-sm font-bold text-[var(--ds-text)]">{{ item.label }}</p>
                <p class="mt-0.5 text-xs font-semibold text-[var(--ds-text-muted)]">{{ formatDate(item.date) }}</p>
              </div>
            </li>
          </ol>
        </section>

        <section class="ds-panel p-5">
          <div class="flex items-start gap-3">
            <BuildingOffice2Icon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
            <div>
              <p class="ds-kicker">Classificação</p>
              <h2 class="ds-heading mt-2 text-base">Origem e responsabilidade</h2>
            </div>
          </div>
          <dl class="mt-5 divide-y divide-[var(--ds-border)] border-y border-[var(--ds-border)]">
            <div class="py-3">
              <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Categoria</dt>
              <dd class="mt-1 text-sm font-bold text-[var(--ds-text)]">{{ occurrence.category || "Não definida" }}</dd>
            </div>
            <div class="py-3">
              <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Origem</dt>
              <dd class="mt-1 text-sm font-bold text-[var(--ds-text)]">{{ occurrence.origin || "Não definida" }}</dd>
            </div>
            <div class="py-3">
              <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Departamento</dt>
              <dd class="mt-1 text-sm font-bold text-[var(--ds-text)]">{{ occurrence.department || "Não definido" }}</dd>
            </div>
            <div class="py-3">
              <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Responsável interno</dt>
              <dd class="mt-1 text-sm font-bold text-[var(--ds-text)]">{{ occurrence.user || "Não atribuído" }}</dd>
            </div>
            <div v-if="occurrence.responsible_name" class="py-3">
              <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Outro responsável</dt>
              <dd class="mt-1 text-sm font-bold text-[var(--ds-text)]">{{ occurrence.responsible_name }}</dd>
            </div>
          </dl>
        </section>

        <section class="ds-panel p-5">
          <div class="flex items-start gap-3">
            <ShieldCheckIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
            <div>
              <p class="ds-kicker">Conformidade</p>
              <h2 class="ds-heading mt-2 text-base">Controlos associados</h2>
            </div>
          </div>
          <dl class="mt-5 space-y-3">
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm font-semibold text-[var(--ds-text-muted)]">Orçamento correctivo</dt>
              <dd class="ds-badge" :class="occurrence.has_risk_correction_budget ? 'ds-badge-success' : 'ds-badge-neutral'">{{ booleanLabel(occurrence.has_risk_correction_budget) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm font-semibold text-[var(--ds-text-muted)]">Termos de NC</dt>
              <dd class="ds-badge" :class="occurrence.has_non_conformity_terms ? 'ds-badge-success' : 'ds-badge-neutral'">{{ booleanLabel(occurrence.has_non_conformity_terms) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-sm font-semibold text-[var(--ds-text-muted)]">Matriz de risco</dt>
              <dd class="ds-badge" :class="occurrence.update_risk_matrix ? 'ds-badge-info' : 'ds-badge-neutral'">{{ booleanLabel(occurrence.update_risk_matrix, "Rever", "Sem alteração") }}</dd>
            </div>
          </dl>
          <div v-if="occurrence.reason_for_no_risk_correction_budget" class="mt-4 border-t border-[var(--ds-border)] pt-4">
            <p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Justificação sem orçamento</p>
            <p class="mt-2 text-sm leading-6 text-[var(--ds-text)]">{{ occurrence.reason_for_no_risk_correction_budget }}</p>
          </div>
        </section>

        <section class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
          <div class="flex gap-3">
            <UserCircleIcon class="mt-0.5 h-5 w-5 shrink-0 text-[var(--ds-text-soft)]" />
            <div>
              <h2 class="text-sm font-bold text-[var(--ds-text)]">Estado registado</h2>
              <p class="mt-1 text-sm leading-6 text-[var(--ds-text-muted)]">{{ occurrence.status || "Sem estado de fluxo definido" }}</p>
            </div>
          </div>
        </section>
      </aside>
    </div>
  </div>
</template>
