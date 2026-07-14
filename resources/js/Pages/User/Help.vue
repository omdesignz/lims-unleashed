<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link } from "@inertiajs/vue3";
import {
  ArrowDownTrayIcon,
  BeakerIcon,
  BellIcon,
  BookOpenIcon,
  ChartBarSquareIcon,
  CheckBadgeIcon,
  ChevronRightIcon,
  ClipboardDocumentCheckIcon,
  DocumentTextIcon,
  HomeIcon,
  IdentificationIcon,
  ShieldCheckIcon,
} from "@heroicons/vue/24/outline";

defineOptions({ layout: Layout });

const navigation = [
  { label: "Fluxo laboratorial", href: "#fluxo" },
  { label: "Qualidade e decisão", href: "#qualidade" },
  { label: "Evidência e registos", href: "#evidencia" },
  { label: "Acesso e suporte", href: "#suporte" },
];

const workflowStages = [
  {
    icon: IdentificationIcon,
    title: "Recepção e identificação",
    text: "Confirme cliente, local, matriz, produto, condição da amostra e cadeia de custódia antes da aceitação.",
  },
  {
    icon: BeakerIcon,
    title: "Preparação e execução",
    text: "Associe perfis, métodos, equipamentos, reagentes e técnicos autorizados ao trabalho analítico.",
  },
  {
    icon: ClipboardDocumentCheckIcon,
    title: "Verificação e aprovação",
    text: "Revise resultados, cálculos, desvios e critérios de aceitação antes de libertar a decisão técnica.",
  },
  {
    icon: DocumentTextIcon,
    title: "Emissão e rastreabilidade",
    text: "Emita o documento controlado e preserve versão, assinaturas, validação e histórico de distribuição.",
  },
];

const qualityControls = [
  {
    icon: ShieldCheckIcon,
    title: "Competência válida",
    text: "A execução, verificação e aprovação devem respeitar autorizações activas e evidência de formação.",
  },
  {
    icon: CheckBadgeIcon,
    title: "Decisão independente",
    text: "Registe revisão, imparcialidade e aprovação em etapas distintas sempre que o procedimento exigir.",
  },
  {
    icon: ChartBarSquareIcon,
    title: "Risco visível",
    text: "Use filas, prazos, alertas e estados para tratar atrasos, desvios e capacidade antes da libertação.",
  },
];

const evidenceItems = [
  { term: "Quem", description: "Utilizador autenticado, função e competência aplicável." },
  { term: "Quando", description: "Data, hora e sequência da acção no processo." },
  { term: "O quê", description: "Valor, estado ou documento antes e depois da alteração." },
  { term: "Porquê", description: "Justificação, ocorrência, método ou referência normativa." },
];
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
          <p class="ds-kicker">Referência operacional</p>
          <div class="mt-3 flex items-start gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <BookOpenIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="ds-heading text-2xl">Manual operacional</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm">
                Pontos de controlo para executar trabalho laboratorial rastreável e manter evidência compatível com a ISO/IEC 17025.
              </p>
            </div>
          </div>
        </div>

        <a :href="route('users.manual.pdf')" target="_blank" rel="noopener" class="ds-button ds-button-primary whitespace-nowrap">
          <ArrowDownTrayIcon class="h-4 w-4" />
          Descarregar PDF
        </a>
      </div>
    </section>

    <div class="grid items-start gap-6 lg:grid-cols-[14rem_minmax(0,1fr)]">
      <aside class="ds-panel p-3 lg:sticky lg:top-24" aria-label="Secções do manual">
        <p class="px-3 pb-2 pt-1 text-xs font-bold uppercase text-[var(--ds-text-soft)]">Neste manual</p>
        <nav>
          <ul class="space-y-1">
            <li v-for="item in navigation" :key="item.href">
              <a
                :href="item.href"
                class="flex items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm font-semibold text-[var(--ds-text-muted)] transition hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--ds-focus)]"
              >
                {{ item.label }}
                <ChevronRightIcon class="h-4 w-4 shrink-0 text-[var(--ds-text-soft)]" />
              </a>
            </li>
          </ul>
        </nav>
      </aside>

      <article class="ds-panel divide-y divide-[var(--ds-border)] overflow-hidden">
        <section id="fluxo" class="scroll-mt-24 p-5 sm:p-6">
          <p class="ds-kicker">Do pedido ao relatório</p>
          <h2 class="ds-heading mt-2 text-lg">Fluxo laboratorial</h2>
          <p class="ds-copy mt-1 max-w-3xl text-sm">Cada etapa deve deixar contexto suficiente para a seguinte e uma linha de auditoria verificável.</p>

          <ol class="mt-6 divide-y divide-[var(--ds-border)] border-y border-[var(--ds-border)]">
            <li v-for="(stage, index) in workflowStages" :key="stage.title" class="grid gap-3 py-4 sm:grid-cols-[2rem_2.5rem_minmax(0,1fr)] sm:items-start">
              <span class="text-sm font-bold text-[var(--ds-text-soft)]">{{ String(index + 1).padStart(2, "0") }}</span>
              <span class="grid h-9 w-9 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
                <component :is="stage.icon" class="h-4.5 w-4.5" />
              </span>
              <div>
                <h3 class="text-sm font-bold text-[var(--ds-text)]">{{ stage.title }}</h3>
                <p class="mt-1 text-sm leading-6 text-[var(--ds-text-muted)]">{{ stage.text }}</p>
              </div>
            </li>
          </ol>
        </section>

        <section id="qualidade" class="scroll-mt-24 p-5 sm:p-6">
          <p class="ds-kicker">Gates de controlo</p>
          <h2 class="ds-heading mt-2 text-lg">Qualidade e decisão</h2>
          <div class="mt-5 divide-y divide-[var(--ds-border)]">
            <div v-for="control in qualityControls" :key="control.title" class="flex gap-4 py-4 first:pt-0 last:pb-0">
              <component :is="control.icon" class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
              <div>
                <h3 class="text-sm font-bold text-[var(--ds-text)]">{{ control.title }}</h3>
                <p class="mt-1 text-sm leading-6 text-[var(--ds-text-muted)]">{{ control.text }}</p>
              </div>
            </div>
          </div>
        </section>

        <section id="evidencia" class="scroll-mt-24 p-5 sm:p-6">
          <p class="ds-kicker">Linha de auditoria</p>
          <h2 class="ds-heading mt-2 text-lg">Evidência mínima do registo</h2>
          <dl class="mt-5 divide-y divide-[var(--ds-border)] border-y border-[var(--ds-border)]">
            <div v-for="item in evidenceItems" :key="item.term" class="grid gap-1 py-3 sm:grid-cols-[8rem_minmax(0,1fr)] sm:gap-4">
              <dt class="text-sm font-bold text-[var(--ds-text)]">{{ item.term }}</dt>
              <dd class="text-sm leading-6 text-[var(--ds-text-muted)]">{{ item.description }}</dd>
            </div>
          </dl>
        </section>

        <section id="suporte" class="scroll-mt-24 p-5 sm:p-6">
          <p class="ds-kicker">Atalhos controlados</p>
          <h2 class="ds-heading mt-2 text-lg">Acesso e suporte</h2>
          <div class="mt-5 grid gap-3 sm:grid-cols-3">
            <Link :href="route('dashboard')" class="ds-button ds-button-secondary justify-between">
              <span class="inline-flex items-center gap-2"><HomeIcon class="h-4 w-4" />Painel</span>
              <ChevronRightIcon class="h-4 w-4" />
            </Link>
            <Link :href="route('notifications.index')" class="ds-button ds-button-secondary justify-between">
              <span class="inline-flex items-center gap-2"><BellIcon class="h-4 w-4" />Notificações</span>
              <ChevronRightIcon class="h-4 w-4" />
            </Link>
            <Link :href="route('security')" class="ds-button ds-button-secondary justify-between">
              <span class="inline-flex items-center gap-2"><ShieldCheckIcon class="h-4 w-4" />Segurança</span>
              <ChevronRightIcon class="h-4 w-4" />
            </Link>
          </div>
        </section>
      </article>
    </div>
  </div>
</template>
