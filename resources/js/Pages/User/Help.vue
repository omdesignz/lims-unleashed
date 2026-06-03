<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import { BookOpenIcon, ArrowDownTrayIcon, BeakerIcon, DocumentTextIcon, ShieldCheckIcon, ChartBarSquareIcon } from '@heroicons/vue/24/outline'
import { commercialDocumentThemeClasses } from '@/Composables/useCommercialDocumentTheme'

defineOptions({
  layout: Layout,
})

const manualSections = [
  {
    icon: BeakerIcon,
    title: 'Entrada de amostras',
    text: 'Comece pela Sample Entry para ligar cliente, produto, matriz, armazém, perfis, condicionamento, colheita, análise e certificado.',
  },
  {
    icon: DocumentTextIcon,
    title: 'Documentos e propostas',
    text: 'Use os studios para controlar cabeçalhos, rodapés, assinaturas, QR codes, fundos, variáveis e saída PDF.',
  },
  {
    icon: ShieldCheckIcon,
    title: 'SGQ e ISO 17025',
    text: 'Acompanhe competências, imparcialidade, não conformidades, proficiência, fornecedores e evidência auditável.',
  },
  {
    icon: ChartBarSquareIcon,
    title: 'Indicadores',
    text: 'Use painéis, relatórios executivos e gráficos para detectar atrasos, risco e capacidade antes de virarem problemas.',
  },
]
</script>

<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <section class="overflow-hidden rounded-[2rem] border border-[#ded3bf] bg-[#fffdf7] shadow-[0_26px_80px_rgba(20,61,55,0.10)] ring-1 ring-white/70 dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10">
      <div class="relative isolate p-6 sm:p-8 lg:p-10">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,rgb(var(--primary-200-rgb)/0.45),transparent_34%),linear-gradient(135deg,#fffdf7,#f4efe4)] dark:bg-[radial-gradient(circle_at_top_left,rgb(var(--primary-500-rgb)/0.18),transparent_36%),linear-gradient(135deg,#07110f,#10231f)]" />
        <div class="inline-flex rounded-full border border-[#d9b05f]/60 bg-[#fffaf0] px-3 py-1 text-xs font-black uppercase tracking-[0.28em] text-[#143d37] dark:border-[#d9b05f]/30 dark:bg-[#d9b05f]/10 dark:text-[#f1d78b]">
          Manual operacional
        </div>
        <div class="mt-6 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
          <div class="max-w-4xl">
            <h1 class="text-3xl font-black tracking-tight text-[#15231f] dark:text-[#f7f1e7] sm:text-4xl">
              Guia de utilização para uma operação laboratorial rastreável.
            </h1>
            <p class="mt-4 text-sm font-medium leading-6 text-[#475a53] dark:text-[#cbd8cf] sm:text-base">
              Este manual resume os fluxos essenciais da plataforma: receção de amostras, análise, resultados, documentos, SGQ, notificações, inventário e administração.
            </p>
          </div>
          <a
            :href="route('users.manual.pdf')"
            target="_blank"
            rel="noopener"
            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[#143d37] px-5 py-3 text-sm font-black text-white shadow-[0_18px_45px_rgba(20,61,55,0.22)] transition hover:-translate-y-0.5 hover:bg-[#1f7a68] dark:bg-[#f1d78b] dark:text-[#07110f]"
          >
            <ArrowDownTrayIcon class="h-4 w-4" />
            Descarregar PDF
          </a>
        </div>
      </div>
    </section>

    <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
      <article
        v-for="section in manualSections"
        :key="section.title"
        class="rounded-[1.75rem] border border-[#ded3bf] bg-[#fffdf7] p-5 shadow-sm dark:border-[#25443c] dark:bg-[#07110f]"
      >
        <component :is="section.icon" class="h-7 w-7 text-[#143d37] dark:text-[#f1d78b]" />
        <h2 class="mt-5 text-base font-black text-[#15231f] dark:text-[#f7f1e7]">{{ section.title }}</h2>
        <p class="mt-2 text-sm leading-6 text-[#475a53] dark:text-[#cbd8cf]">{{ section.text }}</p>
      </article>
    </section>

    <section class="rounded-[2rem] border border-[#ded3bf] bg-[#fffdf7] p-6 shadow-sm dark:border-[#25443c] dark:bg-[#07110f] sm:p-8">
      <div class="flex items-start gap-4">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#143d37] text-white dark:bg-[#f1d78b] dark:text-[#07110f]">
          <BookOpenIcon class="h-6 w-6" />
        </div>
        <div>
          <h2 class="text-xl font-black text-[#15231f] dark:text-[#f7f1e7]">Como usar este manual</h2>
          <p class="mt-2 max-w-3xl text-sm leading-6 text-[#475a53] dark:text-[#cbd8cf]">
            Consulte o PDF para formação inicial, auditorias internas, onboarding de técnicos e alinhamento com clientes. O documento foi preparado para ser impresso ou guardado como evidência de orientação operacional.
          </p>
        </div>
      </div>
    </section>
  </div>
</template>
