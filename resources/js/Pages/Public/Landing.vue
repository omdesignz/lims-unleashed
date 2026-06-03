<template>
  <div
    class="landing-shell min-h-screen overflow-hidden bg-[#f4efe4] text-[#111814] dark:bg-[#07110f] dark:text-[#f8f4e8]"
    :style="brandingCssVariables"
  >
    <Head :title="`${brandName} | LIMS para laboratórios ISO/IEC 17025`" />

    <div class="pointer-events-none fixed inset-0 z-0 overflow-hidden">
      <div class="absolute inset-0 bg-[linear-gradient(115deg,rgba(9,34,31,0.08),transparent_38%,rgba(190,134,47,0.12)_72%,transparent)] dark:bg-[linear-gradient(115deg,rgba(52,211,153,0.08),transparent_38%,rgba(190,134,47,0.08)_72%,transparent)]"></div>
      <div class="absolute -left-28 top-12 h-[34rem] w-[34rem] rounded-full bg-[#0f3f3a]/12 blur-3xl dark:bg-emerald-500/10"></div>
      <div class="absolute -right-24 top-72 h-[32rem] w-[32rem] rounded-full bg-[#be862f]/20 blur-3xl dark:bg-[#be862f]/10"></div>
      <div class="absolute inset-0 opacity-[0.18] dark:opacity-[0.12] laboratory-grid"></div>
    </div>

    <div class="relative z-10 mx-auto max-w-[92rem] px-4 py-5 sm:px-6 lg:px-8">
      <header class="landing-nav">
        <Link href="/" class="flex min-w-0 items-center gap-3">
          <span class="brand-mark">
            <img
              v-if="branding.logo_url"
              :src="branding.logo_url"
              :alt="brandName"
              class="max-h-9 max-w-9 object-contain"
            />
            <span v-else class="text-lg font-black tracking-[-0.04em] text-[#f8f4e8]">LU</span>
          </span>
          <span class="min-w-0">
            <span class="block truncate text-sm font-black uppercase tracking-[0.18em] text-[#111814]">
              {{ brandName }}
            </span>
            <span class="block text-xs font-extrabold leading-5 text-[#33433a] sm:truncate">
              Evidência técnica e confiança comercial
            </span>
          </span>
        </Link>

        <nav class="hidden items-center gap-1 rounded-full border border-[#d8cfbd] bg-[#fffaf0]/80 p-1 shadow-sm backdrop-blur-xl lg:flex">
          <a v-for="item in navigation" :key="item.href" :href="item.href" class="nav-pill">
            {{ item.label }}
          </a>
        </nav>

        <div class="flex items-center gap-2">
          <Link :href="route('login')" class="secondary-action">
            Área interna
          </Link>
          <Link v-if="branding.portal_enabled !== false" :href="route('portal.login')" class="primary-action">
            Portal
          </Link>
        </div>
      </header>

      <main>
        <section class="grid min-h-[calc(100vh-7rem)] gap-12 pb-20 pt-12 lg:grid-cols-[1.02fr_0.98fr] lg:items-center lg:pb-24 lg:pt-20">
          <div class="max-w-5xl">
            <div class="reveal-badge">
              <span class="h-2 w-2 rounded-full bg-[#1f7a68] shadow-[0_0_0_6px_rgba(31,122,104,0.12)]"></span>
              ISO/IEC 17025 · ensaio · calibração · qualidade
            </div>

            <h1 class="mt-7 max-w-5xl text-[clamp(3.2rem,7.4vw,7.75rem)] font-black leading-[0.92] tracking-[-0.06em] text-[#111814] dark:text-[#f8f4e8]">
              <span v-for="line in heroLines" :key="line" class="mask-line">
                <span>{{ line }}</span>
              </span>
            </h1>

            <p class="mt-8 max-w-2xl animate-soft-in text-lg font-bold leading-8 text-[#344239] dark:text-[#d7e5dc]">
              Um LIMS sério para laboratórios de análise que precisam provar competência, proteger a imparcialidade, controlar amostras e emitir certificados com rastreabilidade completa, não apenas “registar dados”.
            </p>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
              <a href="#iso17025" class="primary-cta">
                Ver cobertura ISO 17025
                <IconArrow />
              </a>
              <a href="#workflow" class="outline-cta">
                Entender o fluxo técnico
              </a>
            </div>

            <div class="mt-10 grid max-w-4xl gap-3 sm:grid-cols-3">
              <article v-for="assurance in assuranceCards" :key="assurance.title" class="assurance-card">
                <p class="text-[0.7rem] font-black uppercase tracking-[0.18em] text-[#1f6f5f] dark:text-[#93d6c2]">{{ assurance.kicker }}</p>
                <h2 class="mt-3 text-lg font-black leading-6 tracking-[-0.025em] text-[#111814]">{{ assurance.title }}</h2>
                <p class="mt-2 text-sm font-bold leading-6 text-[#344239]">{{ assurance.description }}</p>
              </article>
            </div>
          </div>

          <div class="relative">
            <div class="absolute -inset-6 rounded-[3rem] bg-[#0f3f3a]/15 blur-3xl dark:bg-emerald-400/10"></div>
            <section class="lab-console">
              <div class="console-header">
                <div>
                  <p class="eyebrow text-[#93d6c2]">Centro de controlo</p>
                  <h2 class="mt-2 text-2xl font-black tracking-[-0.03em] text-white">Amostras, risco e certificados em tempo real</h2>
                </div>
                <span class="rounded-full border border-[#93d6c2]/30 bg-[#93d6c2]/10 px-3 py-1.5 text-xs font-black uppercase tracking-[0.18em] text-[#c9f7e6]">
                  Operação viva
                </span>
              </div>

              <div class="grid gap-4 p-4 lg:grid-cols-[0.92fr_1.08fr]">
                <div class="space-y-4">
                  <article v-for="item in commandTiles" :key="item.label" class="console-tile">
                    <p class="text-[0.68rem] font-black uppercase tracking-[0.18em] text-[#d9b05f]">{{ item.phase }}</p>
                    <p class="mt-2 text-sm font-black text-white">{{ item.label }}</p>
                    <p class="mt-2 text-xs font-bold leading-5 text-[#d7e5dc]">{{ item.description }}</p>
                  </article>
                </div>

                <article id="analytics" class="rounded-[1.75rem] border border-white/10 bg-[#0d1d19] p-5">
                  <div class="flex items-start justify-between gap-4">
                    <div>
                      <p class="eyebrow text-[#d9b05f]">Indicadores laboratoriais</p>
                      <h3 class="mt-2 text-xl font-black tracking-[-0.03em] text-white">Prazos, risco e capacidade por área</h3>
                    </div>
                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-black text-[#dce7df]">Gráficos prontos</span>
                  </div>

                  <div class="mt-7">
                    <svg viewBox="0 0 520 220" class="h-56 w-full overflow-visible">
                      <defs>
                        <linearGradient id="lineGradient" x1="0" x2="1" y1="0" y2="0">
                          <stop offset="0%" stop-color="#93d6c2" />
                          <stop offset="100%" stop-color="#d9b05f" />
                        </linearGradient>
                        <linearGradient id="areaGradient" x1="0" x2="0" y1="0" y2="1">
                          <stop offset="0%" stop-color="#93d6c2" stop-opacity="0.24" />
                          <stop offset="100%" stop-color="#93d6c2" stop-opacity="0" />
                        </linearGradient>
                      </defs>
                      <path d="M0 196 H520" stroke="rgba(255,255,255,.10)" />
                      <path d="M0 146 H520" stroke="rgba(255,255,255,.08)" />
                      <path d="M0 96 H520" stroke="rgba(255,255,255,.08)" />
                      <path d="M0 46 H520" stroke="rgba(255,255,255,.08)" />
                      <path d="M0 178 C72 130 108 152 154 104 C206 48 256 82 304 66 C376 42 424 88 520 34 L520 220 L0 220 Z" fill="url(#areaGradient)" />
                      <path d="M0 178 C72 130 108 152 154 104 C206 48 256 82 304 66 C376 42 424 88 520 34" fill="none" stroke="url(#lineGradient)" stroke-linecap="round" stroke-width="6" />
                      <circle v-for="point in chartPoints" :key="point.x" :cx="point.x" :cy="point.y" r="6" fill="#f4efe4" stroke="#0d1d19" stroke-width="3" />
                    </svg>
                  </div>

                  <div class="grid grid-cols-3 gap-2">
                    <div v-for="signal in chartSignals" :key="signal.label" class="rounded-2xl border border-white/10 bg-white/[0.04] p-3">
                      <p class="text-[0.62rem] font-black uppercase tracking-[0.18em] text-[#7e9187]">{{ signal.label }}</p>
                      <p class="mt-1 text-sm font-black leading-5 text-white">{{ signal.value }}</p>
                    </div>
                  </div>
                </article>
              </div>
            </section>
          </div>
        </section>

        <section class="border-y border-[#d8cfbd] py-6 dark:border-white/10">
          <div class="grid gap-4 text-sm font-black uppercase tracking-[0.18em] text-[#59645d] dark:text-[#b8c6bd] md:grid-cols-4">
            <span v-for="proof in trustStrip" :key="proof" class="flex items-center gap-3">
              <span class="h-1.5 w-1.5 rounded-full bg-[#1f7a68]"></span>
              {{ proof }}
            </span>
          </div>
        </section>

        <section id="iso17025" class="py-20 lg:py-28">
          <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr]">
            <div class="max-w-xl">
              <p class="text-2xl font-black tracking-[-0.035em] text-[#9d6d1f] dark:text-[#d9b05f]">Qualidade que deixa rasto</p>
              <h2 class="mt-4 text-4xl font-black tracking-[-0.055em] text-[#111814] sm:text-6xl dark:text-[#f8f4e8]">
                ISO 17025 não deve viver numa pasta. Deve viver no processo.
              </h2>
              <p class="mt-6 text-lg font-bold leading-8 text-[#344239] dark:text-[#d7e5dc]">
                A norma exige competência, imparcialidade e operação consistente. O sistema deve produzir evidência enquanto o laboratório trabalha, sem depender de registos dispersos.
              </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <article v-for="feature in isoFeatures" :key="feature.title" class="iso-card">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#143d37] text-[#d9b05f] shadow-lg shadow-[#143d37]/20">
                  <component :is="feature.icon" />
                </div>
                <p class="mt-5 text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#1f7a68] dark:text-[#93d6c2]">{{ feature.kicker }}</p>
                <h3 class="mt-3 text-xl font-black tracking-[-0.03em] text-[#111814]">{{ feature.title }}</h3>
                <p class="mt-3 text-sm font-bold leading-7 text-[#344239]">{{ feature.description }}</p>
              </article>
            </div>
          </div>
        </section>

        <section id="workflow" class="grid gap-10 py-20 lg:grid-cols-[1.08fr_0.92fr] lg:items-center">
          <div class="workflow-panel">
            <div class="flex flex-col gap-6 border-b border-white/10 p-6 md:flex-row md:items-center md:justify-between">
              <div>
                <p class="eyebrow text-[#d9b05f]">Cadeia de custódia digital</p>
                <h2 class="mt-2 text-3xl font-black tracking-[-0.045em] text-white">Da proposta ao certificado, sem perder contexto</h2>
              </div>
              <span class="rounded-full bg-white/10 px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-[#dce7df]">Auditável</span>
            </div>

            <div class="grid gap-0 md:grid-cols-2">
              <article v-for="step in workflowSteps" :key="step.title" class="workflow-step">
                <p class="text-4xl font-black tracking-[-0.06em] text-[#d9b05f]">{{ step.index }}</p>
                <h3 class="mt-4 text-xl font-black tracking-[-0.035em] text-white">{{ step.title }}</h3>
                <p class="mt-3 text-sm font-semibold leading-7 text-[#b8c6bd]">{{ step.description }}</p>
              </article>
            </div>
          </div>

          <div>
            <p class="text-2xl font-black tracking-[-0.035em] text-[#9d6d1f] dark:text-[#d9b05f]">Menos fricção, mais confiança</p>
            <h2 class="mt-4 text-4xl font-black tracking-[-0.055em] text-[#111814] sm:text-6xl dark:text-[#f8f4e8]">
              O cliente percebe profissionalismo antes da primeira amostra.
            </h2>
            <p class="mt-6 text-lg font-bold leading-8 text-[#344239] dark:text-[#d7e5dc]">
              Propostas com escopo claro, decisão aceite, pedido de análise pré-preenchido, amostra ligada ao produto e matriz, resultado com incerteza, contra-análise quando aplicável e certificado verificável.
            </p>

            <div class="mt-8 grid gap-3">
              <div v-for="item in workflowProof" :key="item" class="proof-row">
                <span class="flex h-7 w-7 flex-none items-center justify-center rounded-full bg-[#1f7a68] text-xs font-black text-white">✓</span>
                {{ item }}
              </div>
            </div>
          </div>
        </section>

        <section id="reports" class="py-20">
          <div class="rounded-[2.5rem] bg-[#111814] p-4 shadow-2xl shadow-[#111814]/20 dark:bg-[#0a1512]">
            <div class="grid overflow-hidden rounded-[2rem] border border-white/10 lg:grid-cols-[0.95fr_1.05fr]">
              <div class="p-7 sm:p-10">
                <p class="eyebrow text-[#d9b05f]">Relatórios premium</p>
                <h2 class="mt-4 text-4xl font-black tracking-[-0.055em] text-white sm:text-6xl">
                  Documentos que parecem acreditáveis porque são.
                </h2>
                <p class="mt-6 text-lg font-semibold leading-8 text-[#c8d5cc]">
                  Certificados, relatórios de análise, propostas, facturas, recibos e etiquetas devem conservar cabeçalhos, rodapés, paginação, assinaturas, código QR, variáveis reais e identidade institucional.
                </p>
              </div>

              <div class="grid gap-px bg-white/10 sm:grid-cols-2">
                <article v-for="document in documentStudio" :key="document.title" class="bg-[#15211d] p-6">
                  <p class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#d9b05f]">{{ document.kicker }}</p>
                  <h3 class="mt-4 text-2xl font-black tracking-[-0.04em] text-white">{{ document.title }}</h3>
                  <p class="mt-3 text-sm font-semibold leading-7 text-[#b8c6bd]">{{ document.description }}</p>
                </article>
              </div>
            </div>
          </div>
        </section>

        <section class="py-20">
          <div class="grid gap-4 lg:grid-cols-3">
            <article v-for="pillar in businessPillars" :key="pillar.title" class="business-card">
              <p class="text-[0.68rem] font-black uppercase tracking-[0.2em] text-[#1f7a68] dark:text-[#93d6c2]">{{ pillar.kicker }}</p>
              <h3 class="mt-4 text-2xl font-black tracking-[-0.04em] text-[#111814]">{{ pillar.title }}</h3>
              <p class="mt-3 text-sm font-bold leading-7 text-[#344239]">{{ pillar.description }}</p>
            </article>
          </div>
        </section>

        <section id="cta" class="pb-10 pt-16">
          <div class="cta-panel">
            <div class="relative mx-auto max-w-4xl text-center">
              <p class="text-2xl font-black tracking-[-0.035em] text-[#8b621f]">Para laboratórios que não podem parecer improvisados</p>
              <h2 class="mt-5 text-4xl font-black tracking-[-0.055em] text-[#111814] sm:text-6xl">
                Transforme análise, qualidade e gestão num sistema de confiança.
              </h2>
              <p class="mx-auto mt-6 max-w-2xl text-lg font-bold leading-8 text-[#344239]">
                Configure a marca, ligue o portal, organize amostras, resultados, equipamentos, SGQ e relatórios. A experiência precisa vender seriedade antes da auditoria e depois dela.
              </p>
              <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <Link :href="route('login')" class="primary-cta">
                  Aceder ao sistema
                  <IconArrow />
                </Link>
                <Link v-if="branding.portal_enabled !== false" :href="route('portal.login')" class="outline-cta">
                  Ver portal do cliente
                </Link>
              </div>
            </div>
          </div>
        </section>
      </main>

      <footer class="border-t border-[#d8cfbd] py-9 dark:border-white/10">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.24em] text-[#1f7a68] dark:text-[#93d6c2]">{{ brandName }}</p>
            <h2 class="mt-3 max-w-3xl text-3xl font-black tracking-[-0.04em] text-[#111814] dark:text-[#f8f4e8]">
              Competência, rastreabilidade e comunicação profissional para laboratórios de análise.
            </h2>
          </div>
          <div class="grid gap-3 text-sm font-bold text-[#59645d] dark:text-[#b8c6bd] sm:grid-cols-2">
            <a v-for="item in navigation" :key="`footer-${item.href}`" :href="item.href" class="transition hover:text-[#1f7a68] dark:hover:text-[#93d6c2]">{{ item.label }}</a>
          </div>
        </div>
        <div class="mt-8 flex flex-col gap-3 text-sm font-semibold text-[#69736b] dark:text-[#92a39a] sm:flex-row sm:items-center sm:justify-between">
          <span>© {{ currentYear }} {{ brandName }}. Sistema de gestão laboratorial.</span>
          <span>{{ branding.validation_name || 'Preparado para fluxos ISO/IEC 17025' }}</span>
        </div>
      </footer>
    </div>
  </div>
</template>

<script setup>
import { computed, h } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { buildBrandingCssVariables } from '@/Utils/brandingPalette'

defineOptions({
  layout: false,
})

const props = defineProps({
  branding: {
    type: Object,
    default: () => ({}),
  },
})

const brandingCssVariables = computed(() => buildBrandingCssVariables(props.branding))
const brandName = computed(() => props.branding.app_name || 'LIMS Unleashed')
const currentYear = new Date().getFullYear()

const heroLines = ['Resultados', 'que merecem', 'confiança.']

const navigation = [
  { label: 'ISO 17025', href: '#iso17025' },
  { label: 'Fluxo técnico', href: '#workflow' },
  { label: 'Indicadores', href: '#analytics' },
  { label: 'Relatórios', href: '#reports' },
]

const assuranceCards = [
  {
    kicker: 'Recepção',
    title: 'A amostra entra com contexto técnico',
    description: 'Produto, matriz, lote, origem, recolha, prioridade e requisitos ficam ligados ao processo desde o início.',
  },
  {
    kicker: 'Validação',
    title: 'O resultado passa por decisão formal',
    description: 'Inserção, cálculo, incerteza, verificação, aprovação e contra-análise seguem uma cadeia auditável.',
  },
  {
    kicker: 'Emissão',
    title: 'O certificado preserva evidência',
    description: 'Assinatura, código QR, cabeçalhos, rodapés, paginação e histórico acompanham o documento final.',
  },
]

const commandTiles = [
  {
    phase: 'Triagem',
    label: 'Pedidos sem ambiguidade operacional',
    description: 'O portal recolhe dados laboratoriais úteis antes de criar trabalho técnico para a equipa.',
  },
  {
    phase: 'Controlo',
    label: 'Bloqueios antes do erro',
    description: 'Competência, equipamento, método, incerteza e decisão deixam de depender de memória informal.',
  },
  {
    phase: 'Comunicação',
    label: 'Notificações com responsabilidade',
    description: 'Amostras paradas, validações pendentes e clientes sem resposta ficam visíveis antes de virarem reclamação.',
  },
]

const chartPoints = [
  { x: 0, y: 178 },
  { x: 154, y: 104 },
  { x: 304, y: 66 },
  { x: 520, y: 34 },
]

const chartSignals = [
  { label: 'Prazos', value: 'Atrasos por etapa' },
  { label: 'Risco', value: 'Pendências críticas' },
  { label: 'Resposta', value: 'Acções responsáveis' },
]

const trustStrip = [
  'Imparcialidade',
  'Confidencialidade',
  'Rastreabilidade metrológica',
  'Relatórios válidos',
]

const isoFeatures = [
  {
    kicker: 'Cláusulas 4.1 / 4.2',
    title: 'Imparcialidade e confidencialidade',
    description: 'Permissões, trilhos de auditoria, segregação de responsabilidades e comunicação controlada para reduzir risco de influência indevida.',
    icon: () => h(IconShield),
  },
  {
    kicker: 'Cláusula 6.2',
    title: 'Competência técnica',
    description: 'Matriz de responsabilidade, competências por método, certificações, acompanhamento e evidência de autorização para executar tarefas críticas.',
    icon: () => h(IconUserCheck),
  },
  {
    kicker: 'Cláusulas 6.4 / 6.5',
    title: 'Equipamento e rastreabilidade',
    description: 'Manutenção, calibração, estado operacional, etiquetas e ligação entre instrumento, amostra, resultado e certificado.',
    icon: () => h(IconWrench),
  },
  {
    kicker: 'Cláusulas 7.2 / 7.6',
    title: 'Métodos, fórmulas e incerteza',
    description: 'Parâmetros certificados, fórmulas de cálculo, fontes de incerteza, unidades e regras técnicas ligadas à inserção de resultados.',
    icon: () => h(IconFormula),
  },
  {
    kicker: 'Cláusula 7.8',
    title: 'Certificados e relatórios',
    description: 'Modelos multi-página com cabeçalhos, rodapés, paginação, decisão, código QR, assinaturas e variáveis reais do laboratório.',
    icon: () => h(IconReport),
  },
  {
    kicker: 'Cláusulas 8.7 / 8.9',
    title: 'Não conformidades e melhoria',
    description: 'Ocorrências, acções correctivas, reclamações, análises críticas, SGQ e painéis para melhoria contínua.',
    icon: () => h(IconLoop),
  },
]

const workflowSteps = [
  {
    index: '01',
    title: 'Proposta aceite',
    description: 'Escopo, preço, regra de decisão, condições e assinatura ficam claros antes da execução.',
  },
  {
    index: '02',
    title: 'Entrada da amostra',
    description: 'Produto, matriz, lote, origem, prioridade e requisitos técnicos entram sem perder contexto.',
  },
  {
    index: '03',
    title: 'Resultado validado',
    description: 'Inserção, cálculo, incerteza, verificação, aprovação e contra-análise ficam rastreáveis.',
  },
  {
    index: '04',
    title: 'Certificado verificável',
    description: 'O documento final carrega assinatura, código QR, histórico, paginação e identidade institucional.',
  },
]

const workflowProof = [
  'Recepção da amostra como ponto de entrada natural do ciclo',
  'Contra-análise tratada como decisão técnica, não remendo manual',
  'Notificações para evitar amostras esquecidas ou clientes sem resposta',
  'Inventário, equipamentos e reagentes ligados à validade do resultado',
]

const documentStudio = [
  {
    kicker: 'Estúdio de propostas',
    title: 'Propostas que fecham escopo',
    description: 'Cabeçalhos, rodapés, tabelas, regras de decisão, blocos de assinatura e anexos com apresentação comercial séria.',
  },
  {
    kicker: 'Estúdio de relatórios',
    title: 'Certificados com autoridade',
    description: 'Modelos flexíveis para resultados, incerteza, comentários técnicos, código QR e paginação consistente.',
  },
  {
    kicker: 'Relatórios executivos',
    title: 'Gestão com indicadores',
    description: 'Prazos, carga pendente, capacidade, risco, não conformidades, produtividade e tendência por laboratório ou departamento.',
  },
  {
    kicker: 'Estúdio de etiquetas',
    title: 'Etiquetas operacionais',
    description: 'Amostras, equipamentos, reagentes e lotes com código QR, tamanhos customizados e impressão controlada.',
  },
]

const businessPillars = [
  {
    kicker: 'Cliente',
    title: 'Confiança desde o primeiro contacto',
    description: 'Portal, propostas, pedidos de análise, certificados e documentos comerciais reduzem fricção e aumentam credibilidade.',
  },
  {
    kicker: 'Técnico',
    title: 'Execução orientada por evidência',
    description: 'A equipa trabalha por prioridade, estado, método, competência, equipamento e validação, não por listas soltas.',
  },
  {
    kicker: 'Direcção',
    title: 'Decisão com dados laboratoriais',
    description: 'Painéis, notificações e relatórios executivos mostram capacidade, risco, atrasos e melhoria contínua.',
  },
]

function IconShield() {
  return h('svg', { class: 'h-6 w-6', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '1.8' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M12 3 5 6v5c0 4.2 2.8 8.1 7 10 4.2-1.9 7-5.8 7-10V6l-7-3Z' }),
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'm9 12 2 2 4-4' }),
  ])
}

function IconUserCheck() {
  return h('svg', { class: 'h-6 w-6', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '1.8' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM4 21a7 7 0 0 1 11-5.7' }),
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'm16 19 2 2 4-5' }),
  ])
}

function IconWrench() {
  return h('svg', { class: 'h-6 w-6', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '1.8' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M14.7 6.3a4 4 0 0 0 5 5L11 20l-4-4 8.7-8.7Z' }),
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'm7 16-3 3 1 1 3-3' }),
  ])
}

function IconFormula() {
  return h('svg', { class: 'h-6 w-6', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '1.8' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M5 5h14M7 19h10M9 5l6 14M15 5 9 19' }),
  ])
}

function IconReport() {
  return h('svg', { class: 'h-6 w-6', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '1.8' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M7 3h7l4 4v14H7V3Z' }),
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M14 3v5h5M9 14h6M9 18h4' }),
  ])
}

function IconLoop() {
  return h('svg', { class: 'h-6 w-6', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '1.8' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M4 12a8 8 0 0 1 13.7-5.6L20 8M20 4v4h-4M20 12a8 8 0 0 1-13.7 5.6L4 16M4 20v-4h4' }),
  ])
}

function IconArrow() {
  return h('svg', { class: 'h-4 w-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M5 12h14m-6-6 6 6-6 6' }),
  ])
}
</script>

<style scoped>
@import url('https://fonts.bunny.net/css?family=manrope:500,600,700,800,900');

.landing-shell {
  font-family: "Manrope", "Aptos", sans-serif;
}

.laboratory-grid {
  background-image:
    linear-gradient(rgba(17, 24, 20, 0.10) 1px, transparent 1px),
    linear-gradient(90deg, rgba(17, 24, 20, 0.10) 1px, transparent 1px);
  background-size: 52px 52px;
  -webkit-mask-image: radial-gradient(circle at 50% 22%, black 0%, transparent 72%);
  mask-image: radial-gradient(circle at 50% 22%, black 0%, transparent 72%);
}

.landing-nav {
  align-items: center;
  background: rgba(255, 252, 244, 0.96);
  border: 1px solid rgba(171, 154, 122, 0.42);
  border-radius: 999px;
  box-shadow: 0 24px 70px rgba(17, 24, 20, 0.12);
  display: flex;
  gap: 1rem;
  justify-content: space-between;
  padding: 0.75rem;
  position: sticky;
  top: 1rem;
  z-index: 40;
  backdrop-filter: blur(24px);
}

:global(.dark) .landing-nav {
  background: rgba(255, 252, 244, 0.96);
  border-color: rgba(171, 154, 122, 0.42);
  box-shadow: 0 24px 70px rgba(0, 0, 0, 0.26);
}

.brand-mark {
  align-items: center;
  background: linear-gradient(145deg, #143d37, #111814);
  border-radius: 1.25rem;
  display: flex;
  height: 3rem;
  justify-content: center;
  width: 3rem;
  box-shadow: 0 18px 36px rgba(17, 24, 20, 0.18);
}

.nav-pill {
  border-radius: 999px;
  color: #21342b;
  font-size: 0.86rem;
  font-weight: 900;
  padding: 0.65rem 0.95rem;
  transition: all 180ms ease;
}

.nav-pill:hover {
  background: #143d37;
  color: #f8f4e8;
}

:global(.dark) .nav-pill {
  color: #21342b;
}

.primary-action,
.secondary-action,
.primary-cta,
.outline-cta {
  align-items: center;
  border-radius: 999px;
  display: inline-flex;
  font-size: 0.9rem;
  font-weight: 950;
  justify-content: center;
  line-height: 1;
  transition: transform 180ms ease, box-shadow 180ms ease, background 180ms ease, border-color 180ms ease;
}

.primary-action {
  background: #143d37;
  color: #f8f4e8;
  padding: 0.85rem 1.1rem;
}

.secondary-action {
  border: 1px solid rgba(20, 61, 55, 0.16);
  color: #143d37;
  padding: 0.85rem 1.05rem;
}

:global(.dark) .secondary-action {
  border-color: rgba(20, 61, 55, 0.16);
  color: #143d37;
}

.primary-cta {
  background: #143d37;
  box-shadow: 0 24px 50px rgba(20, 61, 55, 0.24);
  color: #f8f4e8;
  gap: 0.65rem;
  padding: 1rem 1.35rem;
}

.outline-cta {
  background: rgba(255, 252, 244, 0.84);
  border: 1px solid rgba(20, 61, 55, 0.18);
  color: #143d37;
  padding: 1rem 1.35rem;
}

:global(.dark) .outline-cta {
  background: rgba(255, 252, 244, 0.88);
  border-color: rgba(171, 154, 122, 0.38);
  color: #143d37;
}

.cta-panel .outline-cta,
:global(.dark) .cta-panel .outline-cta {
  background: rgba(255, 252, 244, 0.74);
  border-color: rgba(20, 61, 55, 0.18);
  color: #143d37;
}

.primary-action:hover,
.secondary-action:hover,
.primary-cta:hover,
.outline-cta:hover {
  transform: translateY(-2px);
}

.reveal-badge {
  align-items: center;
  animation: microScaleFade 600ms cubic-bezier(0.32, 0.72, 0, 1) both;
  background: rgba(255, 250, 240, 0.72);
  border: 1px solid rgba(216, 207, 189, 0.9);
  border-radius: 999px;
  color: #143d37;
  display: inline-flex;
  font-size: 0.72rem;
  font-weight: 950;
  gap: 0.65rem;
  letter-spacing: 0.2em;
  padding: 0.65rem 0.9rem;
  text-transform: uppercase;
}

:global(.dark) .reveal-badge {
  background: rgba(255, 255, 255, 0.06);
  border-color: rgba(255, 255, 255, 0.12);
  color: #d8efe4;
}

.mask-line {
  display: block;
  overflow: hidden;
}

.mask-line > span {
  animation: maskRevealUp 760ms cubic-bezier(0.22, 1, 0.36, 1) both;
  display: block;
  filter: blur(6px);
  transform: translateY(30px);
}

.mask-line:nth-child(2) > span {
  animation-delay: 90ms;
}

.mask-line:nth-child(3) > span {
  animation-delay: 180ms;
}

.animate-soft-in {
  animation: maskRevealUp 760ms cubic-bezier(0.22, 1, 0.36, 1) 260ms both;
}

.assurance-card,
.iso-card,
.business-card {
  background: rgba(255, 252, 244, 0.96);
  border: 1px solid rgba(171, 154, 122, 0.38);
  border-radius: 1.75rem;
  box-shadow: 0 22px 64px rgba(17, 24, 20, 0.09);
  padding: 1.25rem;
}

:global(.dark) .assurance-card,
:global(.dark) .iso-card,
:global(.dark) .business-card {
  background: rgba(255, 252, 244, 0.96);
  border-color: rgba(171, 154, 122, 0.38);
  color: #111814;
}

.lab-console,
.workflow-panel {
  background: #111814;
  border: 1px solid rgba(248, 244, 232, 0.12);
  border-radius: 2.25rem;
  box-shadow: 0 38px 90px rgba(17, 24, 20, 0.35);
  overflow: hidden;
}

.console-header {
  align-items: flex-start;
  background:
    radial-gradient(circle at top right, rgba(147, 214, 194, 0.20), transparent 34%),
    linear-gradient(135deg, #143d37, #111814);
  display: flex;
  gap: 1rem;
  justify-content: space-between;
  padding: 1.5rem;
}

.console-tile {
  background: rgba(255, 255, 255, 0.075);
  border: 1px solid rgba(255, 255, 255, 0.14);
  border-radius: 1.5rem;
  padding: 1rem;
}

.eyebrow {
  font-size: 0.68rem;
  font-weight: 950;
  letter-spacing: 0.22em;
  text-transform: uppercase;
}

.iso-card,
.business-card {
  padding: 1.5rem;
  transition: transform 180ms ease, box-shadow 180ms ease;
}

.iso-card:hover,
.business-card:hover {
  box-shadow: 0 30px 80px rgba(17, 24, 20, 0.12);
  transform: translateY(-4px);
}

.workflow-step {
  border-color: rgba(255, 255, 255, 0.10);
  border-style: solid;
  border-width: 0 1px 1px 0;
  min-height: 16rem;
  padding: 1.5rem;
}

.proof-row {
  align-items: flex-start;
  background: rgba(255, 250, 240, 0.72);
  border: 1px solid rgba(216, 207, 189, 0.86);
  border-radius: 1.25rem;
  color: #26332c;
  display: flex;
  font-size: 0.95rem;
  font-weight: 850;
  gap: 0.85rem;
  padding: 1rem;
}

:global(.dark) .proof-row {
  background: rgba(255, 255, 255, 0.055);
  border-color: rgba(255, 255, 255, 0.10);
  color: #dce7df;
}

.cta-panel {
  background:
    radial-gradient(circle at top left, rgba(31, 122, 104, 0.16), transparent 32%),
    radial-gradient(circle at bottom right, rgba(217, 176, 95, 0.22), transparent 36%),
    rgba(255, 250, 240, 0.72);
  border: 1px solid rgba(216, 207, 189, 0.9);
  border-radius: 2.5rem;
  box-shadow: 0 32px 90px rgba(17, 24, 20, 0.10);
  overflow: hidden;
  padding: clamp(2rem, 5vw, 4.5rem);
}

:global(.dark) .cta-panel {
  background:
    radial-gradient(circle at top left, rgba(31, 122, 104, 0.14), transparent 32%),
    radial-gradient(circle at bottom right, rgba(217, 176, 95, 0.20), transparent 36%),
    rgba(255, 252, 244, 0.92);
  border-color: rgba(171, 154, 122, 0.38);
  color: #111814;
}

@keyframes maskRevealUp {
  from {
    opacity: 0;
    filter: blur(6px);
    transform: translateY(30px);
  }

  to {
    opacity: 1;
    filter: blur(0);
    transform: translateY(0);
  }
}

@keyframes microScaleFade {
  from {
    opacity: 0;
    transform: scale(0.96);
  }

  to {
    opacity: 1;
    transform: scale(1);
  }
}

@media (max-width: 768px) {
  .landing-nav {
    align-items: stretch;
    border-radius: 1.5rem;
    flex-direction: column;
  }

  .workflow-step {
    border-right-width: 0;
  }
}
</style>
