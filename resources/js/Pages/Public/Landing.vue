<template>
  <div class="landing-shell min-h-screen bg-white text-slate-950 dark:bg-slate-950 dark:text-white" :style="brandingCssVariables">
    <Head :title="`${brandName} | LIMS para operações laboratoriais rastreáveis`" />

    <header class="sticky top-0 z-50 border-b border-slate-200 bg-white/95 backdrop-blur dark:border-white/10 dark:bg-slate-950/95">
      <div class="mx-auto flex h-16 max-w-[90rem] items-center justify-between gap-6 px-4 sm:px-6 lg:px-8">
        <Link href="/" class="flex min-w-0 items-center gap-3" aria-label="Página inicial">
          <span class="brand-mark">
            <img v-if="branding.logo_url" :src="branding.logo_url" :alt="brandName" class="h-8 w-8 object-contain" />
            <BeakerIcon v-else class="h-6 w-6" aria-hidden="true" />
          </span>
          <span class="min-w-0">
            <span class="block truncate text-sm font-semibold text-slate-950 dark:text-white">{{ brandName }}</span>
            <span class="hidden truncate text-xs text-slate-500 sm:block dark:text-slate-400">Gestão da informação laboratorial</span>
          </span>
        </Link>

        <nav class="hidden items-center gap-7 lg:flex" aria-label="Navegação principal">
          <a v-for="item in navigation" :key="item.href" :href="item.href" class="nav-link">{{ item.label }}</a>
        </nav>

        <div class="hidden items-center gap-2 sm:flex">
          <Link :href="route('login')" class="button-secondary">Área interna</Link>
          <Link v-if="branding.portal_enabled !== false" :href="route('portal.login')" class="button-primary">
            Portal do cliente
            <ArrowRightIcon class="h-4 w-4" aria-hidden="true" />
          </Link>
        </div>

        <button
          type="button"
          class="icon-button sm:hidden"
          :aria-expanded="mobileNavigationOpen"
          aria-controls="mobile-navigation"
          aria-label="Abrir navegação"
          @click="mobileNavigationOpen = !mobileNavigationOpen"
        >
          <XMarkIcon v-if="mobileNavigationOpen" class="h-5 w-5" aria-hidden="true" />
          <Bars3Icon v-else class="h-5 w-5" aria-hidden="true" />
        </button>
      </div>

      <div v-if="mobileNavigationOpen" id="mobile-navigation" class="border-t border-slate-200 bg-white px-4 py-4 sm:hidden dark:border-white/10 dark:bg-slate-950">
        <nav class="grid gap-1" aria-label="Navegação móvel">
          <a v-for="item in navigation" :key="`mobile-${item.href}`" :href="item.href" class="mobile-nav-link" @click="mobileNavigationOpen = false">
            {{ item.label }}
          </a>
        </nav>
        <div class="mt-4 grid grid-cols-2 gap-2 border-t border-slate-200 pt-4 dark:border-white/10">
          <Link :href="route('login')" class="button-secondary">Área interna</Link>
          <Link v-if="branding.portal_enabled !== false" :href="route('portal.login')" class="button-primary">Portal</Link>
        </div>
      </div>
    </header>

    <main>
      <section class="hero-section" aria-labelledby="hero-heading">
        <picture class="absolute inset-0 z-0">
          <source srcset="/images/lims-laboratory-hero.webp" type="image/webp" />
          <img
            src="/images/lims-laboratory-hero.png"
            alt="Analista a processar amostras num laboratório moderno"
            class="h-full w-full object-cover object-[82%_center] sm:object-[64%_center]"
            fetchpriority="high"
          />
        </picture>
        <div class="hero-overlay absolute inset-0 z-[1]"></div>

        <div class="relative z-10 mx-auto grid min-h-[calc(100svh-9.5rem)] max-w-[90rem] content-between gap-10 px-4 py-10 sm:px-6 sm:py-14 lg:grid-cols-[minmax(0,0.88fr)_minmax(25rem,0.52fr)] lg:items-end lg:px-8">
          <div class="max-w-3xl self-center">
            <div class="hero-kicker">
              <span class="status-dot" aria-hidden="true"></span>
              ISO/IEC 17025 · do pedido ao certificado
            </div>

            <h1 id="hero-heading" class="mt-5 max-w-3xl text-[2.65rem] font-semibold leading-[1.04] text-white sm:text-6xl lg:text-[4.5rem]">
              O LIMS para operações laboratoriais rastreáveis.
            </h1>
            <p class="mt-6 max-w-2xl text-base leading-7 text-slate-200 sm:text-lg sm:leading-8">
              Controle amostras, métodos, equipamentos, resultados e documentos num único fluxo auditável, com contexto técnico preservado em cada decisão.
            </p>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
              <Link v-if="branding.portal_enabled !== false" :href="route('portal.login')" class="hero-primary-action">
                Aceder ao portal
                <ArrowRightIcon class="h-4 w-4" aria-hidden="true" />
              </Link>
              <a href="#workflow" class="hero-secondary-action">Ver fluxo laboratorial</a>
            </div>

            <div class="mt-8 flex flex-wrap gap-x-6 gap-y-3 text-sm text-slate-200">
              <span v-for="item in heroAssurances" :key="item" class="flex items-center gap-2">
                <CheckCircleIcon class="h-4 w-4 text-emerald-300" aria-hidden="true" />
                {{ item }}
              </span>
            </div>
          </div>

          <section class="operations-readout hidden lg:block" aria-label="Estado operacional do laboratório">
            <div class="flex items-center justify-between gap-4 border-b border-white/10 px-4 py-3">
              <div>
                <p class="text-xs font-semibold uppercase text-emerald-300">Controlo operacional</p>
                <h2 class="mt-1 text-sm font-semibold text-white">Fluxo técnico em tempo real</h2>
              </div>
              <span class="live-status"><span class="status-dot" aria-hidden="true"></span>Activo</span>
            </div>

            <div class="divide-y divide-white/10">
              <div v-for="item in liveWorkflow" :key="item.label" class="grid grid-cols-[1fr_auto] items-center gap-4 px-4 py-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-medium text-white">{{ item.label }}</p>
                  <p class="mt-0.5 truncate text-xs text-slate-400">{{ item.detail }}</p>
                </div>
                <span :class="['workflow-status', item.tone]">{{ item.status }}</span>
              </div>
            </div>

            <div class="grid grid-cols-3 divide-x divide-white/10 border-t border-white/10">
              <div v-for="signal in operationalSignals" :key="signal.label" class="px-3 py-3">
                <p class="text-[0.65rem] uppercase text-slate-500">{{ signal.label }}</p>
                <p class="mt-1 text-xs font-semibold text-slate-200">{{ signal.value }}</p>
              </div>
            </div>
          </section>
        </div>
      </section>

      <section class="border-b border-slate-200 bg-slate-50 dark:border-white/10 dark:bg-slate-900" aria-label="Indicadores do sistema">
        <div class="mx-auto grid max-w-[90rem] grid-cols-2 divide-x divide-y divide-slate-200 px-4 sm:px-6 md:grid-cols-4 md:divide-y-0 lg:px-8 dark:divide-white/10">
          <div v-for="metric in publicMetrics" :key="metric.label" class="px-4 py-5 first:pl-0 md:px-6">
            <p class="text-2xl font-semibold text-slate-950 dark:text-white">{{ metric.value }}</p>
            <p class="mt-1 text-xs font-medium uppercase text-slate-500 dark:text-slate-400">{{ metric.label }}</p>
          </div>
        </div>
      </section>

      <section id="workflow" class="section-band bg-white dark:bg-slate-950" aria-labelledby="workflow-heading">
        <div class="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8">
          <div class="section-heading-grid">
            <div>
              <p class="section-eyebrow">Cadeia de custódia digital</p>
              <h2 id="workflow-heading" class="section-title">Um processo contínuo, sem perda de contexto.</h2>
            </div>
            <p class="section-lead">
              A informação comercial, técnica e de qualidade acompanha a amostra desde o pedido inicial até ao documento emitido e verificável.
            </p>
          </div>

          <ol class="mt-12 grid border-y border-slate-200 md:grid-cols-4 dark:border-white/10">
            <li v-for="step in workflowSteps" :key="step.index" class="workflow-stage">
              <div class="flex items-center justify-between gap-4">
                <span class="stage-index">{{ step.index }}</span>
                <component :is="step.icon" class="h-5 w-5 text-slate-400" aria-hidden="true" />
              </div>
              <h3 class="mt-8 text-lg font-semibold text-slate-950 dark:text-white">{{ step.title }}</h3>
              <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400">{{ step.description }}</p>
            </li>
          </ol>
        </div>
      </section>

      <section id="control" class="section-band border-y border-slate-200 bg-slate-50 dark:border-white/10 dark:bg-slate-900" aria-labelledby="control-heading">
        <div class="mx-auto grid max-w-[90rem] gap-12 px-4 sm:px-6 lg:grid-cols-[0.72fr_1.28fr] lg:items-center lg:px-8">
          <div class="max-w-xl">
            <p class="section-eyebrow">Trabalho técnico visível</p>
            <h2 id="control-heading" class="section-title">Prioridade, responsabilidade e risco na mesma vista.</h2>
            <p class="mt-6 text-base leading-7 text-slate-600 dark:text-slate-300">
              As equipas trabalham sobre uma fila controlada: amostra, método, prazo, responsável e bloqueios técnicos permanecem legíveis sem alternar entre registos dispersos.
            </p>

            <dl class="mt-8 divide-y divide-slate-200 border-y border-slate-200 dark:divide-white/10 dark:border-white/10">
              <div v-for="item in controlPrinciples" :key="item.term" class="grid grid-cols-[8rem_1fr] gap-4 py-4 text-sm">
                <dt class="font-semibold text-slate-950 dark:text-white">{{ item.term }}</dt>
                <dd class="leading-6 text-slate-600 dark:text-slate-400">{{ item.description }}</dd>
              </div>
            </dl>
          </div>

          <div class="product-surface" aria-label="Exemplo de fila de trabalho laboratorial">
            <div class="flex flex-col gap-4 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-white/10">
              <div>
                <p class="text-xs font-semibold uppercase text-emerald-700 dark:text-emerald-300">Bancada analítica</p>
                <h3 class="mt-1 text-base font-semibold text-slate-950 dark:text-white">Amostras em processamento</h3>
              </div>
              <div class="flex items-center gap-2">
                <span class="filter-chip">Todas as áreas</span>
                <span class="filter-chip">Prazo: hoje</span>
              </div>
            </div>

            <div class="overflow-x-auto">
              <div class="min-w-[43rem] w-full text-left" role="table" aria-label="Amostras prioritárias">
                <div class="grid grid-cols-[1.05fr_1.45fr_0.9fr_0.9fr_0.8fr] bg-slate-100/80 text-[0.68rem] font-semibold uppercase text-slate-500 dark:bg-white/[0.04] dark:text-slate-400" role="row">
                  <div class="px-4 py-3" role="columnheader">Amostra</div>
                  <div class="px-4 py-3" role="columnheader">Ensaio / matriz</div>
                  <div class="px-4 py-3" role="columnheader">Prazo</div>
                  <div class="px-4 py-3" role="columnheader">Responsável</div>
                  <div class="px-4 py-3" role="columnheader">Estado</div>
                </div>
                <div class="divide-y divide-slate-200 text-sm dark:divide-white/10" role="rowgroup">
                  <div v-for="sample in sampleQueue" :key="sample.code" class="grid grid-cols-[1.05fr_1.45fr_0.9fr_0.9fr_0.8fr] items-center bg-white dark:bg-slate-950" role="row">
                    <div class="px-4 py-4" role="cell">
                      <p class="font-mono text-xs font-semibold text-slate-950 dark:text-white">{{ sample.code }}</p>
                      <p class="mt-1 text-xs text-slate-500">{{ sample.lot }}</p>
                    </div>
                    <div class="px-4 py-4" role="cell">
                      <p class="font-medium text-slate-900 dark:text-slate-100">{{ sample.test }}</p>
                      <p class="mt-1 text-xs text-slate-500">{{ sample.matrix }}</p>
                    </div>
                    <div class="px-4 py-4 text-slate-600 dark:text-slate-300" role="cell">{{ sample.due }}</div>
                    <div class="px-4 py-4 text-slate-600 dark:text-slate-300" role="cell">{{ sample.owner }}</div>
                    <div class="px-4 py-4" role="cell"><span :class="['queue-status', sample.tone]">{{ sample.status }}</span></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex items-center justify-between gap-4 border-t border-slate-200 px-4 py-3 text-xs text-slate-500 dark:border-white/10 dark:text-slate-400">
              <span>4 de 18 amostras prioritárias</span>
              <span class="font-medium text-slate-700 dark:text-slate-200">Actualizado agora</span>
            </div>
          </div>
        </div>
      </section>

      <section id="quality" class="section-band bg-white dark:bg-slate-950" aria-labelledby="quality-heading">
        <div class="mx-auto max-w-[90rem] px-4 sm:px-6 lg:px-8">
          <div class="section-heading-grid">
            <div>
              <p class="section-eyebrow">ISO/IEC 17025 no processo</p>
              <h2 id="quality-heading" class="section-title">Controlos que produzem evidência enquanto a equipa trabalha.</h2>
            </div>
            <p class="section-lead">
              Competência, rastreabilidade metrológica, imparcialidade e validade dos resultados tornam-se parte da execução diária, não uma camada documental posterior.
            </p>
          </div>

          <div class="mt-12 grid border-y border-slate-200 md:grid-cols-2 lg:grid-cols-3 dark:border-white/10">
            <article v-for="control in qualityControls" :key="control.title" class="quality-control">
              <component :is="control.icon" class="h-6 w-6 text-emerald-700 dark:text-emerald-300" aria-hidden="true" />
              <p class="mt-6 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{{ control.clause }}</p>
              <h3 class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">{{ control.title }}</h3>
              <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400">{{ control.description }}</p>
            </article>
          </div>
        </div>
      </section>

      <section id="documents" class="section-band bg-slate-950 text-white" aria-labelledby="documents-heading">
        <div class="mx-auto grid max-w-[90rem] gap-12 px-4 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:items-center lg:px-8">
          <div class="max-w-xl">
            <p class="section-eyebrow text-emerald-300">Saída controlada</p>
            <h2 id="documents-heading" class="section-title text-white">Documentos consistentes com a autoridade do laboratório.</h2>
            <p class="mt-6 text-base leading-7 text-slate-300">
              Certificados, relatórios, propostas, facturas e recibos partilham identidade institucional, dados aprovados e mecanismos de verificação.
            </p>

            <ul class="mt-8 grid gap-4 text-sm text-slate-200">
              <li v-for="item in documentControls" :key="item" class="flex items-start gap-3">
                <CheckCircleIcon class="mt-0.5 h-5 w-5 flex-none text-emerald-300" aria-hidden="true" />
                <span class="leading-6">{{ item }}</span>
              </li>
            </ul>
          </div>

          <div class="document-workspace">
            <div class="document-toolbar">
              <div class="flex items-center gap-2">
                <span class="toolbar-dot bg-rose-400"></span>
                <span class="toolbar-dot bg-amber-300"></span>
                <span class="toolbar-dot bg-emerald-400"></span>
              </div>
              <span class="text-xs font-medium text-slate-400">Certificado de análise · versão aprovada</span>
            </div>

            <div class="grid gap-4 bg-slate-900 p-4 sm:grid-cols-[1fr_12rem] sm:p-6">
              <article class="document-page">
                <div class="flex items-start justify-between gap-6 border-b-2 border-slate-900 pb-5">
                  <div>
                    <div class="h-7 w-28 bg-slate-900"></div>
                    <p class="mt-2 text-[0.56rem] font-semibold uppercase text-slate-500">Laboratório de ensaios</p>
                  </div>
                  <div class="text-right">
                    <p class="text-[0.62rem] font-bold uppercase text-slate-900">Certificado de análise</p>
                    <p class="mt-1 font-mono text-[0.55rem] text-slate-500">CA-2026-00482</p>
                  </div>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-4 text-[0.55rem] text-slate-600">
                  <div><span class="font-semibold text-slate-900">Amostra</span><p class="mt-1">LU-260714-0184</p></div>
                  <div><span class="font-semibold text-slate-900">Matriz</span><p class="mt-1">Água de processo</p></div>
                  <div><span class="font-semibold text-slate-900">Recepção</span><p class="mt-1">14/07/2026 · 08:42</p></div>
                  <div><span class="font-semibold text-slate-900">Emissão</span><p class="mt-1">14/07/2026 · 16:20</p></div>
                </div>

                <div class="mt-6 text-[0.52rem] text-slate-600" role="table" aria-label="Resultados certificados">
                  <div class="grid grid-cols-[1.3fr_0.8fr_0.7fr_1fr] border-y border-slate-300 bg-slate-100 font-semibold text-slate-900" role="row">
                    <div class="px-2 py-2" role="columnheader">Parâmetro</div><div class="px-2 py-2" role="columnheader">Resultado</div><div class="px-2 py-2" role="columnheader">Unidade</div><div class="px-2 py-2" role="columnheader">Método</div>
                  </div>
                  <div class="divide-y divide-slate-200" role="rowgroup">
                    <div class="grid grid-cols-[1.3fr_0.8fr_0.7fr_1fr]" role="row"><div class="px-2 py-2" role="cell">pH</div><div class="px-2 py-2 font-semibold text-slate-900" role="cell">7,21</div><div class="px-2 py-2" role="cell">-</div><div class="px-2 py-2" role="cell">ISO 10523</div></div>
                    <div class="grid grid-cols-[1.3fr_0.8fr_0.7fr_1fr]" role="row"><div class="px-2 py-2" role="cell">Condutividade</div><div class="px-2 py-2 font-semibold text-slate-900" role="cell">412</div><div class="px-2 py-2" role="cell">µS/cm</div><div class="px-2 py-2" role="cell">ISO 7888</div></div>
                    <div class="grid grid-cols-[1.3fr_0.8fr_0.7fr_1fr]" role="row"><div class="px-2 py-2" role="cell">Turbidez</div><div class="px-2 py-2 font-semibold text-slate-900" role="cell">0,18</div><div class="px-2 py-2" role="cell">NTU</div><div class="px-2 py-2" role="cell">ISO 7027-1</div></div>
                  </div>
                </div>

                <div class="mt-8 flex items-end justify-between gap-6 border-t border-slate-300 pt-4">
                  <div class="text-[0.5rem] text-slate-500"><p class="font-semibold text-slate-900">Aprovação técnica</p><p class="mt-1">Assinatura digital validada</p></div>
                  <QrCodeIcon class="h-12 w-12 text-slate-900" aria-label="Código de verificação" />
                </div>
              </article>

              <aside class="grid content-start gap-3" aria-label="Controlos do documento">
                <div v-for="item in documentChecklist" :key="item.label" class="document-check">
                  <CheckCircleIcon class="h-4 w-4 text-emerald-300" aria-hidden="true" />
                  <div>
                    <p class="text-xs font-medium text-white">{{ item.label }}</p>
                    <p class="mt-1 text-[0.68rem] leading-4 text-slate-400">{{ item.detail }}</p>
                  </div>
                </div>
              </aside>
            </div>
          </div>
        </div>
      </section>

      <section class="border-b border-slate-200 bg-white py-16 sm:py-20 dark:border-white/10 dark:bg-slate-950" aria-labelledby="closing-heading">
        <div class="mx-auto flex max-w-[90rem] flex-col gap-8 px-4 sm:px-6 lg:flex-row lg:items-end lg:justify-between lg:px-8">
          <div class="max-w-3xl">
            <p class="section-eyebrow">{{ branding.lab_name || brandName }}</p>
            <h2 id="closing-heading" class="mt-3 text-3xl font-semibold leading-tight text-slate-950 sm:text-5xl dark:text-white">
              Converta cada resultado numa evidência técnica confiável.
            </h2>
            <p class="mt-5 max-w-2xl text-base leading-7 text-slate-600 dark:text-slate-300">{{ brandSlogan }}</p>
          </div>

          <div class="flex flex-col gap-3 sm:flex-row">
            <Link :href="route('login')" class="button-secondary min-h-11">Aceder ao sistema</Link>
            <Link v-if="branding.portal_enabled !== false" :href="route('portal.login')" class="button-primary min-h-11">
              Portal do cliente
              <ArrowRightIcon class="h-4 w-4" aria-hidden="true" />
            </Link>
          </div>
        </div>
      </section>
    </main>

    <footer class="bg-slate-50 dark:bg-slate-900">
      <div class="mx-auto flex max-w-[90rem] flex-col gap-6 px-4 py-8 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
        <div class="flex items-center gap-3">
          <span class="brand-mark"><BeakerIcon class="h-5 w-5" aria-hidden="true" /></span>
          <div>
            <p class="text-sm font-semibold text-slate-950 dark:text-white">{{ brandName }}</p>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Sistema de gestão laboratorial</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-600 dark:text-slate-400">
          <a v-for="item in navigation" :key="`footer-${item.href}`" :href="item.href" class="hover:text-slate-950 dark:hover:text-white">{{ item.label }}</a>
        </div>
        <p class="text-xs text-slate-500 dark:text-slate-400">© {{ currentYear }} {{ brandName }}</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import {
  ArrowRightIcon,
  Bars3Icon,
  BeakerIcon,
  ChartBarSquareIcon,
  CheckCircleIcon,
  ClipboardDocumentCheckIcon,
  DocumentCheckIcon,
  LockClosedIcon,
  QrCodeIcon,
  ShieldCheckIcon,
  UserGroupIcon,
  WrenchScrewdriverIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import { buildBrandingCssVariables } from '@/Utils/brandingPalette'

defineOptions({ layout: false })

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

const mobileNavigationOpen = ref(false)
const brandingCssVariables = computed(() => buildBrandingCssVariables(props.branding))
const brandName = computed(() => props.branding.app_name || 'LIMS Unleashed')
const brandSlogan = computed(() => props.branding.app_slogan || 'Rastreabilidade, qualidade e conformidade para laboratórios modernos.')
const currentYear = new Date().getFullYear()

const numberFormatter = new Intl.NumberFormat('pt-PT')
const formatMetric = value => Number(value || 0) > 0 ? numberFormatter.format(Number(value)) : 'Pronto'

const publicMetrics = computed(() => [
  { label: 'Amostras rastreadas', value: formatMetric(props.metrics.samples) },
  { label: 'Certificados emitidos', value: formatMetric(props.metrics.certificates) },
  { label: 'Itens sob controlo', value: formatMetric(props.metrics.inventory_items) },
  { label: 'Pedidos de clientes', value: formatMetric(props.metrics.customer_requests) },
])

const navigation = [
  { label: 'Fluxo laboratorial', href: '#workflow' },
  { label: 'Controlo técnico', href: '#control' },
  { label: 'Qualidade', href: '#quality' },
  { label: 'Documentos', href: '#documents' },
]

const heroAssurances = ['Cadeia de custódia', 'Controlo ISO 17025', 'Certificados verificáveis']

const liveWorkflow = [
  { label: 'Recepção e triagem', detail: 'Identidade, matriz e prioridade', status: 'Conforme', tone: 'success' },
  { label: 'Execução analítica', detail: 'Método, equipamento e competência', status: 'Em curso', tone: 'active' },
  { label: 'Revisão técnica', detail: 'Cálculo, incerteza e decisão', status: '3 pendentes', tone: 'warning' },
  { label: 'Emissão documental', detail: 'Assinatura e verificação', status: 'Controlado', tone: 'neutral' },
]

const operationalSignals = [
  { label: 'Integridade', value: 'Trilho activo' },
  { label: 'Metrologia', value: 'Estado válido' },
  { label: 'Documentos', value: 'Versão aprovada' },
]

const workflowSteps = [
  { index: '01', title: 'Pedido e âmbito', description: 'Cliente, matriz, métodos, prazos, preços e regra de decisão ficam definidos antes da execução.', icon: ClipboardDocumentCheckIcon },
  { index: '02', title: 'Recepção da amostra', description: 'Identificação, condição, lote, origem, recolha e custódia entram no mesmo registo técnico.', icon: BeakerIcon },
  { index: '03', title: 'Análise e revisão', description: 'Competência, equipamento, cálculo, incerteza, verificação e aprovação compõem uma decisão rastreável.', icon: ChartBarSquareIcon },
  { index: '04', title: 'Emissão controlada', description: 'O resultado aprovado alimenta certificados, relatórios e comunicação ao cliente sem transcrição manual.', icon: DocumentCheckIcon },
]

const controlPrinciples = [
  { term: 'Prioridade', description: 'Prazos e risco tornam a próxima acção objectiva.' },
  { term: 'Responsável', description: 'Cada etapa tem competência e decisão atribuídas.' },
  { term: 'Bloqueio', description: 'O sistema evidência desvios antes da emissão.' },
]

const sampleQueue = [
  { code: 'LU-260714-0184', lot: 'Lote AQ-782', test: 'pH e condutividade', matrix: 'Água de processo', due: 'Hoje · 14:30', owner: 'M. Joaquim', status: 'Em análise', tone: 'active' },
  { code: 'LU-260714-0179', lot: 'Lote AL-219', test: 'Metais por ICP-OES', matrix: 'Solo agrícola', due: 'Hoje · 16:00', owner: 'A. Manuel', status: 'Revisão', tone: 'warning' },
  { code: 'LU-260713-0162', lot: 'Lote PF-041', test: 'Resíduo de pesticidas', matrix: 'Produto vegetal', due: 'Amanhã · 10:00', owner: 'C. Domingos', status: 'Preparação', tone: 'neutral' },
  { code: 'LU-260713-0158', lot: 'Lote MB-327', test: 'Contagem microbiológica', matrix: 'Alimento processado', due: 'Amanhã · 12:00', owner: 'S. Mateus', status: 'Incubação', tone: 'success' },
]

const qualityControls = [
  { clause: 'Cláusulas 4.1 e 4.2', title: 'Imparcialidade e confidencialidade', description: 'Acesso, responsabilidade e histórico reduzem exposição indevida e influência sobre decisões técnicas.', icon: LockClosedIcon },
  { clause: 'Cláusula 6.2', title: 'Competência técnica', description: 'Autorizações por função, método e actividade sustentam a alocação de trabalho e a aprovação de resultados.', icon: UserGroupIcon },
  { clause: 'Cláusulas 6.4 e 6.5', title: 'Equipamento e metrologia', description: 'Estado, manutenção, calibração e rastreabilidade permanecem ligados à execução analítica.', icon: WrenchScrewdriverIcon },
  { clause: 'Cláusulas 7.2 e 7.6', title: 'Métodos e incerteza', description: 'Versões, fórmulas, unidades, limites e fontes de incerteza acompanham o resultado calculado.', icon: BeakerIcon },
  { clause: 'Cláusula 7.8', title: 'Relato de resultados', description: 'Modelos controlados recebem apenas dados revistos, aprovados e coerentes com o âmbito contratado.', icon: DocumentCheckIcon },
  { clause: 'Cláusulas 8.7 e 8.9', title: 'Melhoria e análise crítica', description: 'Ocorrências, acções correctivas, reclamações e indicadores preservam evidência para decisão da direcção.', icon: ShieldCheckIcon },
]

const documentControls = [
  'Dados técnicos provenientes do resultado aprovado, sem dupla digitação.',
  'Cabeçalhos, paginação, assinaturas e identidade institucional consistentes.',
  'Código QR e trilho de versão para validação do documento emitido.',
]

const documentChecklist = [
  { label: 'Dados aprovados', detail: 'Resultado e método bloqueados' },
  { label: 'Assinatura válida', detail: 'Responsável técnico identificado' },
  { label: 'Versão controlada', detail: 'Histórico e emissão preservados' },
  { label: 'QR verificável', detail: 'Autenticidade consultável' },
]
</script>

<style scoped>
.landing-shell {
  font-family: var(--font-sans);
  letter-spacing: 0;
}

.brand-mark {
  align-items: center;
  background: rgb(var(--primary-900-rgb));
  border-radius: 8px;
  color: white;
  display: inline-flex;
  flex: none;
  height: 2.5rem;
  justify-content: center;
  width: 2.5rem;
}

.nav-link {
  color: rgb(71 85 105);
  font-size: 0.875rem;
  font-weight: 500;
  transition: color 160ms ease;
}

.nav-link:hover {
  color: rgb(var(--primary-600-rgb));
}

:global(.dark) .nav-link {
  color: rgb(148 163 184);
}

:global(.dark) .nav-link:hover {
  color: rgb(var(--primary-300-rgb));
}

.button-primary,
.button-secondary,
.hero-primary-action,
.hero-secondary-action {
  align-items: center;
  border-radius: 6px;
  display: inline-flex;
  font-size: 0.875rem;
  font-weight: 600;
  gap: 0.5rem;
  justify-content: center;
  min-height: 2.5rem;
  padding: 0.625rem 1rem;
  transition: background-color 160ms ease, border-color 160ms ease, color 160ms ease;
}

.button-primary {
  background: rgb(var(--primary-700-rgb));
  color: white;
}

.button-primary:hover {
  background: rgb(var(--primary-800-rgb));
}

.button-secondary {
  background: white;
  border: 1px solid rgb(203 213 225);
  color: rgb(30 41 59);
}

.button-secondary:hover {
  background: rgb(248 250 252);
  border-color: rgb(148 163 184);
}

:global(.dark) .button-secondary {
  background: rgb(15 23 42);
  border-color: rgb(51 65 85);
  color: white;
}

.icon-button {
  align-items: center;
  border: 1px solid rgb(203 213 225);
  border-radius: 6px;
  color: rgb(30 41 59);
  display: inline-flex;
  height: 2.5rem;
  justify-content: center;
  width: 2.5rem;
}

:global(.dark) .icon-button {
  border-color: rgb(51 65 85);
  color: white;
}

.mobile-nav-link {
  border-radius: 6px;
  color: rgb(51 65 85);
  font-size: 0.9rem;
  font-weight: 500;
  padding: 0.75rem;
}

.mobile-nav-link:hover {
  background: rgb(241 245 249);
}

:global(.dark) .mobile-nav-link {
  color: rgb(203 213 225);
}

:global(.dark) .mobile-nav-link:hover {
  background: rgb(30 41 59);
}

.hero-section {
  background: rgb(15 23 42);
  isolation: isolate;
  min-height: 36rem;
  overflow: hidden;
  position: relative;
}

.hero-overlay {
  background:
    linear-gradient(90deg, rgba(2, 12, 15, 0.96) 0%, rgba(2, 16, 18, 0.88) 38%, rgba(2, 16, 18, 0.36) 72%, rgba(2, 16, 18, 0.14) 100%),
    linear-gradient(0deg, rgba(2, 10, 13, 0.78) 0%, transparent 42%);
}

.hero-kicker,
.live-status {
  align-items: center;
  display: inline-flex;
  gap: 0.55rem;
}

.hero-kicker {
  color: rgb(209 250 229);
  font-size: 0.72rem;
  font-weight: 600;
  text-transform: uppercase;
}

.status-dot {
  background: rgb(52 211 153);
  border-radius: 999px;
  box-shadow: 0 0 0 4px rgba(52, 211, 153, 0.14);
  height: 0.45rem;
  width: 0.45rem;
}

.hero-primary-action {
  background: white;
  color: rgb(15 23 42);
  min-height: 3rem;
  padding-inline: 1.2rem;
}

.hero-primary-action:hover {
  background: rgb(226 232 240);
}

.hero-secondary-action {
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.32);
  color: white;
  min-height: 3rem;
  padding-inline: 1.2rem;
}

.hero-secondary-action:hover {
  background: rgba(15, 23, 42, 0.76);
  border-color: rgba(255, 255, 255, 0.56);
}

.operations-readout {
  align-self: end;
  backdrop-filter: blur(16px);
  background: rgba(2, 10, 13, 0.86);
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 8px;
  box-shadow: 0 24px 50px rgba(0, 0, 0, 0.24);
  overflow: hidden;
}

.live-status {
  background: rgba(16, 185, 129, 0.12);
  border: 1px solid rgba(52, 211, 153, 0.22);
  border-radius: 999px;
  color: rgb(167 243 208);
  font-size: 0.68rem;
  font-weight: 600;
  padding: 0.35rem 0.55rem;
  text-transform: uppercase;
}

.workflow-status,
.queue-status {
  border-radius: 999px;
  display: inline-flex;
  font-size: 0.68rem;
  font-weight: 600;
  padding: 0.35rem 0.55rem;
  white-space: nowrap;
}

.workflow-status.success,
.queue-status.success {
  background: rgba(16, 185, 129, 0.14);
  color: rgb(110 231 183);
}

.workflow-status.active,
.queue-status.active {
  background: rgba(14, 165, 233, 0.14);
  color: rgb(125 211 252);
}

.workflow-status.warning,
.queue-status.warning {
  background: rgba(245, 158, 11, 0.16);
  color: rgb(252 211 77);
}

.workflow-status.neutral,
.queue-status.neutral {
  background: rgba(148, 163, 184, 0.14);
  color: rgb(203 213 225);
}

.section-band {
  padding-block: 5rem;
}

.section-heading-grid {
  display: grid;
  gap: 2rem;
}

.section-eyebrow {
  color: rgb(var(--primary-600-rgb));
  font-size: 0.72rem;
  font-weight: 600;
  text-transform: uppercase;
}

:global(.dark) .section-eyebrow {
  color: rgb(var(--primary-300-rgb));
}

.section-title {
  color: rgb(15 23 42);
  font-size: clamp(2rem, 4vw, 3.4rem);
  font-weight: 600;
  line-height: 1.1;
  margin-top: 0.75rem;
  max-width: 48rem;
}

:global(.dark) .section-title {
  color: white;
}

.section-lead {
  align-self: end;
  color: rgb(71 85 105);
  font-size: 1rem;
  line-height: 1.75;
  max-width: 38rem;
}

:global(.dark) .section-lead {
  color: rgb(203 213 225);
}

.workflow-stage {
  border-bottom: 1px solid rgb(226 232 240);
  padding: 1.5rem 0;
}

:global(.dark) .workflow-stage {
  border-color: rgba(255, 255, 255, 0.1);
}

.stage-index {
  align-items: center;
  background: rgb(var(--primary-50-rgb));
  border: 1px solid rgb(var(--primary-200-rgb));
  border-radius: 999px;
  color: rgb(var(--primary-700-rgb));
  display: inline-flex;
  font-family: var(--font-mono);
  font-size: 0.72rem;
  font-weight: 600;
  height: 2rem;
  justify-content: center;
  width: 2rem;
}

:global(.dark) .stage-index {
  background: rgba(52, 211, 153, 0.08);
  border-color: rgba(52, 211, 153, 0.2);
  color: rgb(110 231 183);
}

.product-surface {
  background: white;
  border: 1px solid rgb(203 213 225);
  border-radius: 8px;
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
  overflow: hidden;
}

:global(.dark) .product-surface {
  background: rgb(2 6 23);
  border-color: rgb(51 65 85);
  box-shadow: none;
}

.filter-chip {
  background: rgb(248 250 252);
  border: 1px solid rgb(203 213 225);
  border-radius: 6px;
  color: rgb(71 85 105);
  font-size: 0.72rem;
  font-weight: 500;
  padding: 0.4rem 0.55rem;
}

:global(.dark) .filter-chip {
  background: rgb(15 23 42);
  border-color: rgb(51 65 85);
  color: rgb(203 213 225);
}

.product-surface .queue-status.success {
  background: rgb(236 253 245);
  color: rgb(4 120 87);
}

.product-surface .queue-status.active {
  background: rgb(240 249 255);
  color: rgb(3 105 161);
}

.product-surface .queue-status.warning {
  background: rgb(255 251 235);
  color: rgb(180 83 9);
}

.product-surface .queue-status.neutral {
  background: rgb(241 245 249);
  color: rgb(71 85 105);
}

:global(.dark) .product-surface .queue-status.success {
  background: rgba(16, 185, 129, 0.12);
  color: rgb(110 231 183);
}

:global(.dark) .product-surface .queue-status.active {
  background: rgba(14, 165, 233, 0.12);
  color: rgb(125 211 252);
}

:global(.dark) .product-surface .queue-status.warning {
  background: rgba(245, 158, 11, 0.12);
  color: rgb(252 211 77);
}

:global(.dark) .product-surface .queue-status.neutral {
  background: rgba(148, 163, 184, 0.12);
  color: rgb(203 213 225);
}

.quality-control {
  border-bottom: 1px solid rgb(226 232 240);
  padding: 2rem 0;
}

:global(.dark) .quality-control {
  border-color: rgba(255, 255, 255, 0.1);
}

.document-workspace {
  border: 1px solid rgb(51 65 85);
  border-radius: 8px;
  box-shadow: 0 28px 60px rgba(0, 0, 0, 0.28);
  overflow: hidden;
}

.document-toolbar {
  align-items: center;
  background: rgb(30 41 59);
  display: flex;
  justify-content: space-between;
  padding: 0.75rem 1rem;
}

.toolbar-dot {
  border-radius: 999px;
  height: 0.55rem;
  width: 0.55rem;
}

.document-page {
  aspect-ratio: 1 / 1.12;
  background: white;
  color: rgb(15 23 42);
  min-height: 25rem;
  padding: clamp(1.25rem, 4vw, 2.25rem);
}

.document-check {
  align-items: flex-start;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 6px;
  display: flex;
  gap: 0.65rem;
  padding: 0.75rem;
}

@media (min-width: 768px) {
  .section-heading-grid {
    grid-template-columns: minmax(0, 1fr) minmax(20rem, 0.72fr);
  }

  .workflow-stage {
    border-bottom: 0;
    border-right: 1px solid rgb(226 232 240);
    min-height: 18rem;
    padding: 1.75rem;
  }

  .workflow-stage:first-child {
    padding-left: 0;
  }

  .workflow-stage:last-child {
    border-right: 0;
    padding-right: 0;
  }

  :global(.dark) .workflow-stage {
    border-color: rgba(255, 255, 255, 0.1);
  }

  .quality-control {
    border-right: 1px solid rgb(226 232 240);
    padding: 2rem;
  }

  .quality-control:nth-child(2n) {
    border-right: 0;
  }

  :global(.dark) .quality-control {
    border-color: rgba(255, 255, 255, 0.1);
  }
}

@media (min-width: 1024px) {
  .quality-control:nth-child(2n) {
    border-right: 1px solid rgb(226 232 240);
  }

  .quality-control:nth-child(3n) {
    border-right: 0;
  }

  :global(.dark) .quality-control:nth-child(2n) {
    border-color: rgba(255, 255, 255, 0.1);
  }
}

@media (max-width: 639px) {
  .hero-section {
    min-height: auto;
  }

  .hero-overlay {
    background: linear-gradient(90deg, rgba(2, 12, 15, 0.96) 0%, rgba(2, 12, 15, 0.88) 100%);
  }

  .operations-readout {
    margin-top: 1rem;
  }

  .section-band {
    padding-block: 4rem;
  }
}

@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    scroll-behavior: auto !important;
    transition-duration: 0.01ms !important;
  }
}
</style>
