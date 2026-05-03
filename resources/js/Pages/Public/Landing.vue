<template>
  <div
    class="min-h-screen bg-slate-950 text-slate-100"
    :style="brandingCssVariables"
  >
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <div class="absolute -top-32 left-1/2 h-80 w-80 -translate-x-1/2 rounded-full bg-cyan-500/20 blur-3xl"></div>
      <div class="absolute right-0 top-40 h-96 w-96 rounded-full bg-emerald-500/10 blur-3xl"></div>
      <div class="absolute bottom-0 left-0 h-80 w-80 rounded-full bg-indigo-500/10 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-7xl px-6 py-6 sm:px-8 lg:px-10">
      <header class="sticky top-4 z-30 rounded-[2rem] border border-white/10 bg-slate-950/75 px-5 py-4 shadow-2xl shadow-slate-950/30 backdrop-blur-xl sm:px-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/10 bg-white/5">
              <img
                v-if="branding.logo_url"
                :src="branding.logo_url"
                :alt="branding.app_name || 'LIMS Unleashed'"
                class="h-9 w-9 object-contain"
              />
              <span
                v-else
                class="text-lg font-semibold tracking-[0.2em] text-cyan-200"
              >
                LU
              </span>
            </div>

            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-200/80">
                {{ branding.app_name || 'LIMS Unleashed' }}
              </p>
              <p class="mt-1 text-sm text-slate-400">
                {{ branding.app_slogan || 'Qualidade, rastreabilidade e operação num único sistema.' }}
              </p>
            </div>
          </div>

          <nav class="flex flex-wrap items-center gap-3 text-sm text-slate-300">
            <a href="#capabilities" class="rounded-full px-3 py-2 transition hover:bg-white/5 hover:text-white">Capacidades</a>
            <a href="#request-flow" class="rounded-full px-3 py-2 transition hover:bg-white/5 hover:text-white">Pedido de análise</a>
            <a href="#executive" class="rounded-full px-3 py-2 transition hover:bg-white/5 hover:text-white">Gestão</a>
            <a href="#cta" class="rounded-full px-3 py-2 transition hover:bg-white/5 hover:text-white">Começar</a>
          </nav>

          <div class="flex flex-col gap-3 sm:flex-row">
            <Link
              :href="route('login')"
              class="inline-flex items-center justify-center rounded-full border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white transition hover:border-cyan-400/60 hover:bg-cyan-400/10"
            >
              Entrar no backoffice
            </Link>
            <Link
              v-if="branding.portal_enabled !== false"
              :href="route('portal.login')"
              class="inline-flex items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-100"
            >
              Portal do cliente
            </Link>
          </div>
        </div>
      </header>

      <main class="pb-20 pt-8 sm:pt-10">
        <section class="grid gap-8 xl:grid-cols-[1.04fr,0.96fr] xl:items-start">
          <div class="space-y-8">
            <div class="space-y-6">
              <div class="flex flex-wrap gap-3">
                <span
                  v-for="badge in heroBadges"
                  :key="badge"
                  class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-cyan-100"
                >
                  {{ badge }}
                </span>
              </div>

              <div class="space-y-5">
                <h1 class="max-w-5xl text-4xl font-semibold tracking-tight text-white sm:text-5xl xl:text-7xl">
                  O sistema que faz o laboratório
                  <span class="text-cyan-300">parecer sério</span>
                  antes mesmo da auditoria começar.
                </h1>
                <p class="max-w-3xl text-base leading-8 text-slate-300 sm:text-lg">
                  Pedidos, amostras, resultados, documentos, inventário, recursos e governação ISO 17025
                  conectados numa plataforma com presença executiva, disciplina operacional e experiência de cliente à altura.
                </p>
              </div>

              <div class="flex flex-col gap-3 sm:flex-row">
                <a
                  href="#request-flow"
                  class="inline-flex items-center justify-center rounded-full bg-cyan-400 px-6 py-3 text-sm font-semibold text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-300"
                >
                  Ver o percurso do cliente
                </a>
                <a
                  href="#capabilities"
                  class="inline-flex items-center justify-center rounded-full border border-white/15 bg-white/5 px-6 py-3 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/10"
                >
                  Explorar módulos
                </a>
              </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
              <article
                v-for="metric in metricCards"
                :key="metric.label"
                class="rounded-[1.75rem] border border-white/10 bg-white/5 p-5 shadow-lg shadow-slate-950/20"
              >
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">{{ metric.label }}</p>
                <p class="mt-3 text-3xl font-semibold text-white">{{ metric.value }}</p>
                <p class="mt-3 text-sm leading-6 text-slate-300">{{ metric.caption }}</p>
              </article>
            </div>

            <section class="grid gap-4 lg:grid-cols-3">
              <article
                v-for="proof in proofStrip"
                :key="proof.title"
                class="rounded-[1.75rem] border border-white/10 bg-slate-900/70 p-5"
              >
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">{{ proof.kicker }}</p>
                <h2 class="mt-3 text-lg font-semibold text-white">{{ proof.title }}</h2>
                <p class="mt-3 text-sm leading-7 text-slate-300">{{ proof.description }}</p>
              </article>
            </section>
          </div>

          <div class="space-y-6">
            <section class="overflow-hidden rounded-[2rem] border border-white/10 bg-white/5 p-6 shadow-2xl shadow-slate-950/20">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">Centro de comando</p>
                  <h2 class="mt-2 text-2xl font-semibold text-white">Uma leitura que inspira confiança</h2>
                </div>
                <span class="rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-emerald-200">
                  Visão imediata
                </span>
              </div>

              <div class="mt-6 grid gap-4">
                <article
                  v-for="executiveMetric in executiveMetrics"
                  :key="executiveMetric.title"
                  class="rounded-[1.5rem] border border-white/10 bg-slate-900/80 p-5"
                >
                  <div class="flex items-start justify-between gap-4">
                    <div>
                      <p class="text-sm font-semibold text-white">{{ executiveMetric.title }}</p>
                      <p class="mt-2 text-sm leading-7 text-slate-300">{{ executiveMetric.description }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-right">
                      <p class="text-xs uppercase tracking-[0.18em] text-slate-400">{{ executiveMetric.badge }}</p>
                      <p class="mt-1 text-2xl font-semibold text-white">{{ executiveMetric.value }}</p>
                    </div>
                  </div>
                </article>
              </div>

              <div class="mt-6 rounded-[1.5rem] border border-cyan-400/20 bg-cyan-400/10 p-5">
                <div class="flex items-center justify-between gap-3">
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-100">Pulso operacional</p>
                    <h3 class="mt-2 text-lg font-semibold text-white">O que prova maturidade do sistema</h3>
                  </div>
                  <span class="rounded-full border border-white/10 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-white">
                    Em produção
                  </span>
                </div>

                <div class="mt-5 space-y-3">
                  <article
                    v-for="pulse in heroPulse"
                    :key="pulse.title"
                    class="rounded-[1.25rem] border border-white/10 bg-slate-950/45 p-4"
                  >
                    <div class="flex items-start justify-between gap-4">
                      <div>
                        <p class="text-sm font-semibold text-white">{{ pulse.title }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-300">{{ pulse.description }}</p>
                      </div>
                      <span class="rounded-full border border-white/10 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-100">
                        {{ pulse.badge }}
                      </span>
                    </div>
                  </article>
                </div>
              </div>
            </section>

            <section class="rounded-[2rem] border border-cyan-400/20 bg-cyan-400/10 p-6">
              <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-100">Confiança operacional</p>
              <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div
                  v-for="pillar in trustPillars"
                  :key="pillar.title"
                  class="rounded-[1.5rem] border border-white/10 bg-slate-950/40 p-5"
                >
                  <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-cyan-100">
                    {{ pillar.badge }}
                  </span>
                  <h3 class="mt-4 text-lg font-semibold text-white">{{ pillar.title }}</h3>
                  <p class="mt-2 text-sm leading-7 text-slate-200/90">{{ pillar.description }}</p>
                </div>
              </div>
            </section>
          </div>
        </section>

        <section id="capabilities" class="mt-10 space-y-6">
          <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Capacidades nucleares</p>
              <h2 class="mt-2 text-3xl font-semibold text-white sm:text-4xl">
                Não é apenas um registo. É um sistema operacional do laboratório.
              </h2>
            </div>
            <p class="max-w-2xl text-sm leading-7 text-slate-300">
              Cada módulo suporta a execução diária, a rastreabilidade documental e a leitura executiva do negócio.
            </p>
          </div>

          <div class="grid gap-5 xl:grid-cols-3">
            <article
              v-for="highlight in highlights"
              :key="highlight.title"
              class="rounded-[2rem] border border-white/10 bg-white/5 p-6"
            >
              <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">{{ highlight.kicker }}</p>
              <h3 class="mt-4 text-xl font-semibold text-white">{{ highlight.title }}</h3>
              <p class="mt-3 text-sm leading-7 text-slate-300">{{ highlight.description }}</p>
            </article>
          </div>

          <div class="grid gap-5 xl:grid-cols-3">
            <article
              v-for="assurance in assuranceCards"
              :key="assurance.title"
              class="rounded-[2rem] border border-white/10 bg-slate-900/80 p-6"
            >
              <h3 class="text-lg font-semibold text-white">{{ assurance.title }}</h3>
              <p class="mt-3 text-sm leading-7 text-slate-300">{{ assurance.description }}</p>
            </article>
          </div>
        </section>

        <section id="request-flow" class="mt-10 rounded-[2.25rem] border border-white/10 bg-gradient-to-br from-slate-900 via-slate-900 to-cyan-950/60 p-7 shadow-2xl shadow-slate-950/30 sm:p-8">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">Pedido de análise</p>
              <h2 class="mt-2 text-3xl font-semibold text-white sm:text-4xl">
                Um portal que o cliente quer mesmo usar.
              </h2>
            </div>
            <span class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-slate-200">
              Pré-preenchimento, lote e validação técnica
            </span>
          </div>

          <div class="mt-7 grid gap-5 xl:grid-cols-3">
            <article
              v-for="mode in requestModes"
              :key="mode.title"
              class="rounded-[1.75rem] border border-white/10 bg-white/5 p-5"
            >
              <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-200">{{ mode.kicker }}</p>
              <h3 class="mt-3 text-xl font-semibold text-white">{{ mode.title }}</h3>
              <p class="mt-3 text-sm leading-7 text-slate-300">{{ mode.description }}</p>
            </article>
          </div>

          <div class="mt-7 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article
              v-for="step in requestJourney"
              :key="step.title"
              class="rounded-[1.75rem] border border-white/10 bg-slate-950/55 p-5"
            >
              <div class="flex h-10 w-10 items-center justify-center rounded-full bg-cyan-400/15 text-sm font-semibold text-cyan-100">
                {{ step.index }}
              </div>
              <h3 class="mt-4 text-sm font-semibold uppercase tracking-[0.18em] text-white">{{ step.title }}</h3>
              <p class="mt-2 text-sm leading-6 text-slate-300">{{ step.description }}</p>
            </article>
          </div>
        </section>

        <section id="executive" class="mt-10 grid gap-6 xl:grid-cols-[0.92fr,1.08fr]">
          <article class="rounded-[2rem] border border-white/10 bg-white/5 p-7">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Percursos operacionais</p>
            <div class="mt-6 space-y-4">
              <div
                v-for="step in workflow"
                :key="step.title"
                class="rounded-[1.5rem] border border-white/10 bg-slate-900/80 p-5"
              >
                <div class="flex items-center gap-3">
                  <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/5 text-sm font-semibold text-cyan-200">
                    {{ step.index }}
                  </div>
                  <h3 class="text-lg font-semibold text-white">{{ step.title }}</h3>
                </div>
                <p class="mt-3 text-sm leading-7 text-slate-300">{{ step.description }}</p>
              </div>
            </div>
          </article>

          <article class="rounded-[2rem] border border-white/10 bg-white/5 p-7">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Branding e validação</p>
            <div class="mt-6 grid gap-4 md:grid-cols-2">
              <div class="rounded-[1.5rem] border border-white/10 bg-slate-900/80 p-5">
                <p class="text-sm text-slate-400">Entidade de validação</p>
                <p class="mt-2 text-xl font-semibold text-white">
                  {{ branding.validation_name || 'Entidade configurável no sistema' }}
                </p>
                <p v-if="branding.validation_number" class="mt-2 text-sm text-slate-300">
                  Referência {{ branding.validation_number }}
                </p>
              </div>

              <div class="rounded-[1.5rem] border border-white/10 bg-slate-900/80 p-5">
                <p class="text-sm text-slate-400">Laboratório principal</p>
                <p class="mt-2 text-xl font-semibold text-white">
                  {{ branding.lab_name || 'Laboratório principal' }}
                </p>
                <p v-if="branding.lab_director" class="mt-2 text-sm text-slate-300">
                  Direcção técnica: {{ branding.lab_director }}
                </p>
              </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
              <div
                v-for="track in operationalTracks"
                :key="track.title"
                class="rounded-[1.5rem] border border-white/10 bg-slate-950/40 p-5"
              >
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-200">{{ track.kicker }}</p>
                <h3 class="mt-3 text-lg font-semibold text-white">{{ track.title }}</h3>
                <p class="mt-2 text-sm leading-7 text-slate-300">{{ track.description }}</p>
              </div>
            </div>
          </article>
        </section>

        <section id="cta" class="mt-10 rounded-[2.25rem] border border-white/10 bg-white px-7 py-8 text-slate-950 shadow-2xl shadow-slate-950/20 sm:px-8">
          <div class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
            <div class="max-w-3xl">
              <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-700">Pronto para produção</p>
              <h2 class="mt-3 text-3xl font-semibold sm:text-4xl">
                Faça o cliente confiar no processo e a equipa confiar no sistema.
              </h2>
              <p class="mt-4 text-base leading-8 text-slate-600">
                Desde a proposta e o pedido até ao resultado, certificado, retenção e evidência, a plataforma foi desenhada
                para suportar a operação diária e a leitura auditável do laboratório.
              </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
              <Link
                :href="route('login')"
                class="inline-flex items-center justify-center rounded-full bg-slate-950 px-6 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
              >
                Aceder ao sistema
              </Link>
              <Link
                v-if="branding.portal_enabled !== false"
                :href="route('portal.login')"
                class="inline-flex items-center justify-center rounded-full border border-slate-300 px-6 py-3 text-sm font-semibold text-slate-900 transition hover:border-cyan-500 hover:text-cyan-700"
              >
                Entrar no portal do cliente
              </Link>
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

defineOptions({
  layout: false,
})

const props = defineProps({
  branding: {
    type: Object,
    default: () => ({}),
  },
  metrics: {
    type: Object,
    default: () => ({}),
  },
})

const brandingCssVariables = computed(() => ({
  '--brand-primary': props.branding.primary_color || '#1f87e8',
  '--brand-secondary': props.branding.secondary_color || '#0f172a',
  '--brand-accent': props.branding.accent_color || '#14b8a6',
}))

const numberFormatter = new Intl.NumberFormat('pt-PT')

const heroBadges = [
  'ISO 17025',
  'Portal do cliente',
  'Resultados e certificados',
]

const proofStrip = [
  {
    kicker: 'Presença',
    title: 'Parece uma operação estruturada, não um conjunto de formulários',
    description: 'A primeira impressão comunica disciplina: pedidos claros, documentos premium, métricas executivas e rasto consistente.',
  },
  {
    kicker: 'Execução',
    title: 'A equipa trabalha com contexto em vez de retrabalho',
    description: 'O que o cliente informa, o que o técnico valida e o que a gestão acompanha partem da mesma base de dados operacional.',
  },
  {
    kicker: 'Auditoria',
    title: 'A evidência não é montada depois, nasce durante o processo',
    description: 'Revisões, aprovações, retenção, histórico, inventário e circulação documental ficam ligados ao estado real da operação.',
  },
]

const metricCards = computed(() => [
  {
    label: 'Amostras',
    value: numberFormatter.format(props.metrics.samples || 0),
    caption: 'Entradas controladas, em análise, em retenção ou descartadas com histórico completo.',
  },
  {
    label: 'Certificados',
    value: numberFormatter.format(props.metrics.certificates || 0),
    caption: 'Documentos emitidos com revisão, assinatura, paginação e cadeia de evidência.',
  },
  {
    label: 'Inventário',
    value: numberFormatter.format(props.metrics.inventory_items || 0),
    caption: 'Insumos, reagentes e equipamentos controlados por estado, validade e disponibilidade.',
  },
  {
    label: 'Pedidos',
    value: numberFormatter.format(props.metrics.customer_requests || 0),
    caption: 'Solicitações estruturadas no portal, prontas para validação e encaminhamento técnico.',
  },
])

const highlights = [
  {
    kicker: 'Cliente',
    title: 'Pede análise com contexto real, não com formulários genéricos',
    description: 'Produto, matriz, lote, acondicionamento, prioridade e perfis entram logo no pedido, incluindo submissão em lote quando necessário.',
  },
  {
    kicker: 'Laboratório',
    title: 'Valida e processa sem reintroduzir a mesma informação',
    description: 'O backoffice reaproveita o pré-preenchimento do portal para entrada, triagem, análise, contra-análise e emissão documental.',
  },
  {
    kicker: 'Gestão',
    title: 'Vê risco, backlog, conformidade e entrega numa só leitura',
    description: 'Dashboards executivos, revisões, condições ambientais, recursos críticos e alertas operacionais ligam-se ao mesmo estado do sistema.',
  },
]

const workflow = [
  {
    index: '01',
    title: 'Pedido e recepção',
    description: 'O pedido nasce no portal ou internamente, é validado e convertido no fluxo laboratorial formal sem perda de contexto.',
  },
  {
    index: '02',
    title: 'Análise e contra-análise',
    description: 'Resultados seguem por inserção, verificação, aprovação e, quando necessário, uma contra-análise com identidade própria.',
  },
  {
    index: '03',
    title: 'Certificado, retenção e evidência',
    description: 'A cadeia fecha com emissão documental, retenção, descarte, histórico e rastreabilidade de cada decisão tomada.',
  },
]

const assuranceCards = [
  {
    title: 'Documentos controlados',
    description: 'Versões, revisões, obsolescência, retenção, confidencialidade e aprovações no mesmo gestor documental.',
  },
  {
    title: 'Recursos sob controlo',
    description: 'Competências, manutenção, metrologia, inventário, condições ambientais e disponibilidade operacional visíveis no fluxo.',
  },
  {
    title: 'Resultados auditáveis',
    description: 'Hierarquia de validação, assinatura, unidades, limites, incerteza e rastreabilidade das alterações suportam auditorias reais.',
  },
]

const executiveMetrics = computed(() => [
  {
    title: 'Capacidade operacional',
    description: 'Ritmo entre pedidos, amostras em progresso e gargalos de operação.',
    badge: 'Fluxo',
    value: numberFormatter.format((props.metrics.customer_requests || 0) + (props.metrics.samples || 0)),
  },
  {
    title: 'Entrega documental',
    description: 'Volume de certificados e documentação controlada emitida com revisão.',
    badge: 'Entrega',
    value: numberFormatter.format(props.metrics.certificates || 0),
  },
  {
    title: 'Base controlada',
    description: 'Profundidade de inventário e activos operacionais com rastreabilidade activa.',
    badge: 'Recursos',
    value: numberFormatter.format(props.metrics.inventory_items || 0),
  },
])

const heroPulse = [
  {
    title: 'Pedidos entram com mais estrutura e menos ambiguidade',
    description: 'Produto, matriz, lote, prioridade, recolha e perfis seguem no mesmo percurso até à análise e ao documento final.',
    badge: 'Cliente',
  },
  {
    title: 'Resultados e documentos passam por decisão, não por improviso',
    description: 'Inserção, verificação, aprovação, certificados e controlo documental mantêm actores, datas e contexto técnico ligados.',
    badge: 'Laboratório',
  },
  {
    title: 'A gestão vê capacidade, risco e entrega no mesmo ecrã',
    description: 'Backlog, recursos, certificados, inventário e conformidade alimentam uma leitura executiva mais credível.',
    badge: 'Gestão',
  },
]

const trustPillars = [
  {
    badge: 'Rastreio',
    title: 'A mesma verdade para cliente, técnico e gestão',
    description: 'Pedidos, amostras, resultados, certificados, recolhas e documentos deixam de viver em ilhas desconectadas.',
  },
  {
    badge: 'Controlo',
    title: 'Bloqueios antes do erro operacional',
    description: 'Proposta aceite, competência válida, equipamento apto e condições controladas podem influenciar a execução do trabalho.',
  },
  {
    badge: 'Entrega',
    title: 'Portal útil e elegante',
    description: 'O cliente não abre pedidos vagos; ele encaminha análises, recolhas, documentos e certificados com estrutura real.',
  },
]

const requestModes = [
  {
    kicker: 'Amostra única',
    title: 'Pedido rápido com contexto completo',
    description: 'Produto, matriz, lote, quantidade, embalagem e observações entram num fluxo simples e claro para o cliente.',
  },
  {
    kicker: 'Lote',
    title: 'Submissão em batch com modelo estruturado',
    description: 'Clientes com várias amostras podem usar um template e acelerar o envio sem perder consistência de dados.',
  },
  {
    kicker: 'Validação',
    title: 'Técnico confirma e encaminha',
    description: 'Cada linha pode ser validada, ajustada e colocada no circuito normal de análise sem retrabalho manual.',
  },
]

const requestJourney = [
  {
    index: '01',
    title: 'Cliente prepara',
    description: 'Escolhe o tipo de pedido, identifica a amostra e indica perfis ou necessidades de recolha.',
  },
  {
    index: '02',
    title: 'Portal estrutura',
    description: 'O sistema regista prioridade, referência, metadados e observações operacionais úteis.',
  },
  {
    index: '03',
    title: 'Técnico valida',
    description: 'A equipa confirma, corrige quando necessário e cria a entrada formal da amostra ou da recolha.',
  },
  {
    index: '04',
    title: 'Sistema entrega',
    description: 'Segue para análise, aprovação e emissão documental mantendo a cadeia completa de rastreabilidade.',
  },
]

const operationalTracks = [
  {
    kicker: 'Imagem',
    title: 'Marca, validação e identidade configuráveis',
    description: 'Cores, nome da aplicação, laboratório, entidade de validação e portal podem alinhar-se com a operação real.',
  },
  {
    kicker: 'Studio',
    title: 'Base pronta para documentos premium',
    description: 'Certificados, relatórios e propostas podem evoluir com layouts, cabeçalhos, rodapés e paginação controlados.',
  },
]
</script>
