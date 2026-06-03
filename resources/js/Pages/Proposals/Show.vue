<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { computed } from "vue";
import { usePermission } from "@/Composables/usePermissions";
import { ArrowPathRoundedSquareIcon, ChartBarSquareIcon, DocumentIcon, DocumentTextIcon, PaperClipIcon, PencilIcon, TagIcon, TrashIcon } from "@heroicons/vue/24/outline";
import sampleStatus from '@/Components/sample-status.vue';
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";


const { hasRole, hasPermission } = usePermission();
const props = defineProps({
    record: Object,
    charts: {
        type: Object,
        default: () => ({})
    }
});

const financialBreakdownChartSeries = computed(() => [
    {
        name: 'AOA',
        data: props.charts?.financial_breakdown?.series || [],
    }
]);

const itemCompositionChartSeries = computed(() => props.charts?.item_composition?.series || []);
const workflowSummaryChartSeries = computed(() => [
    {
        name: 'Indicadores',
        data: props.charts?.workflow_summary?.series || [],
    }
]);

const financialBreakdownChartOptions = computed(() => ({
    chart: {
        toolbar: { show: false },
        fontFamily: 'inherit',
    },
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 8,
            barHeight: '58%',
            distributed: true,
        },
    },
    dataLabels: { enabled: false },
    xaxis: {
        categories: props.charts?.financial_breakdown?.labels || [],
        labels: {
            style: { colors: '#6b7280' },
        },
    },
    grid: {
        borderColor: '#e5e7eb',
        strokeDashArray: 4,
    },
    colors: ['#1d4ed8', '#f59e0b', '#0ea5e9', '#ef4444', '#16a34a'],
    legend: { show: false },
}));

const itemCompositionChartOptions = computed(() => ({
    chart: {
        toolbar: { show: false },
        fontFamily: 'inherit',
    },
    labels: props.charts?.item_composition?.labels || [],
    colors: ['#0ea5e9', '#f59e0b', '#ef4444', '#64748b'],
    stroke: {
        colors: ['#ffffff'],
    },
    legend: {
        position: 'bottom',
        labels: { colors: '#334155' },
    },
    dataLabels: {
        enabled: true,
        formatter: (value) => `${Math.round(value)}%`,
    },
    plotOptions: {
        pie: {
            donut: {
                size: '68%',
            },
        },
    },
}));

const workflowSummaryChartOptions = computed(() => ({
    chart: {
        toolbar: { show: false },
        fontFamily: 'inherit',
    },
    plotOptions: {
        bar: {
            borderRadius: 10,
            columnWidth: '48%',
            distributed: true,
        },
    },
    dataLabels: { enabled: false },
    xaxis: {
        categories: props.charts?.workflow_summary?.labels || [],
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            style: { colors: '#6b7280' },
        },
    },
    yaxis: {
        min: 0,
        forceNiceScale: true,
        labels: {
            style: { colors: '#6b7280' },
        },
    },
    grid: {
        borderColor: '#e5e7eb',
        strokeDashArray: 4,
    },
    colors: ['#7c3aed', '#1d4ed8', '#0f766e', '#f59e0b'],
    legend: { show: false },
}));

defineOptions({
  layout: Layout
});

</script>
<template>
<!-- <div class="border-b border-gray-200 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.direct_collections.page_title') }}</h3>
</div> -->

<div :class="commercialDocumentThemeClasses">
    <section class="mb-6 grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
      <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">Resumo financeiro</h2>
            <p class="mt-1 text-sm text-gray-500">
              Estrutura de subtotal, descontos, imposto, retenção e total da proposta.
            </p>
          </div>
          <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Total</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ props.record.data?.total || 0 }}</p>
          </div>
        </div>

        <div class="mt-6">
          <apexchart type="bar" height="300" :options="financialBreakdownChartOptions" :series="financialBreakdownChartSeries" />
        </div>
      </article>

      <div class="grid gap-6">
        <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">Composição dos itens</h2>
              <p class="mt-1 text-sm text-gray-500">Veja a distribuição entre itens tributáveis, descontados e com retenção.</p>
            </div>
            <ChartBarSquareIcon class="h-6 w-6 text-blue-900" />
          </div>

          <div class="mt-6">
            <apexchart type="donut" height="300" :options="itemCompositionChartOptions" :series="itemCompositionChartSeries" />
          </div>
        </article>

        <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">Pulso do workflow</h2>
              <p class="mt-1 text-sm text-gray-500">Revisões, tolerância, volume de itens e tempo útil restante.</p>
            </div>
          </div>

          <div class="mt-6">
            <apexchart type="bar" height="250" :options="workflowSummaryChartOptions" :series="workflowSummaryChartSeries" />
          </div>
        </article>
      </div>
    </section>

    <div class="px-4 sm:px-0 flex justify-end items-center space-x-2">
      <button type="button" class="rounded-full bg-blue-900 p-2 text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">
            <PencilIcon class="h-4 w-4" aria-hidden="true" />
        </button>
        <button type="button" class="rounded-full bg-blue-900 p-2 text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">
            <ArrowPathRoundedSquareIcon class="h-4 w-4" aria-hidden="true" />
        </button>
        <button type="button" class="rounded-full bg-blue-900 p-2 text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">
            <TrashIcon class="h-4 w-4" aria-hidden="true" />
        </button>
    </div>
    <div class="mt-6 border-t border-gray-100">
      <dl class="divide-y divide-gray-100">
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">Código QR</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
            <img :src="props.record.data?.qr" alt="QR Code" class="w-16 h-16" />
          </dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.proposals.proposal_no') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.proposal_no }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.proposals.customer_id') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.customer }} - {{ props.record.data?.warehouse }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.proposals.expiry_date') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.expiry_date }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.proposals.service_location') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.service_location }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.proposals.user_id') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.user }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.proposals.obs') }}</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ props.record.data?.obs }}</dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">Colocado em Análise</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">

            <button type="button" class="bg-blue-900 relative inline-flex h-6 w-11 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2" role="switch" :aria-checked="props.record.data?.placed_analysis" :class="{ 'bg-blue-900': props.record.data?.placed_analysis, 'bg-gray-200': !props.record.data?.placed_analysis }">
                <span class="sr-only"></span>
                <!-- Enabled: "translate-x-5", Not Enabled: "translate-x-0" -->
                <span class="translate-x-0 pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="{ 'translate-x-5': props.record.data?.placed_analysis, 'translate-x-0': !props.record.data?.placed_analysis }">
                    <!-- Enabled: "opacity-0 duration-100 ease-out", Not Enabled: "opacity-100 duration-200 ease-in" -->
                    <span class="opacity-100 duration-200 ease-in absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" aria-hidden="true" v-if="!props.record.data?.placed_analysis" :class="{ 'opacity-0 duration-100 ease-out': props.record.data?.placed_analysis, 'opacity-100 duration-200 ease-in': !props.record.data?.placed_analysis }">
                    <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 12 12">
                        <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    </span>
                    <!-- Enabled: "opacity-100 duration-200 ease-in", Not Enabled: "opacity-0 duration-100 ease-out" -->
                    <span class="opacity-0 duration-100 ease-out absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" aria-hidden="true" v-if="props.record.data?.placed_analysis" :class="{ 'opacity-100 duration-200 ease-in': props.record.data?.placed_analysis, 'opacity-0 duration-100 ease-out': !props.record.data?.placed_analysis }">
                    <svg class="h-3 w-3 text-blue-900" fill="currentColor" viewBox="0 0 12 12">
                        <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                    </svg>
                    </span>
                </span>
            </button>

        </dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">Facturado</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">

            <button type="button" class="bg-blue-900 relative inline-flex h-6 w-11 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2" role="switch" :aria-checked="props.record.data?.invoiced" :class="{ 'bg-blue-900': props.record.data?.invoiced, 'bg-gray-200': !props.record.data?.invoiced }">
                <span class="sr-only"></span>
                <!-- Enabled: "translate-x-5", Not Enabled: "translate-x-0" -->
                <span class="translate-x-0 pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="{ 'translate-x-5': props.record.data?.invoiced, 'translate-x-0': !props.record.data?.invoiced }">
                    <!-- Enabled: "opacity-0 duration-100 ease-out", Not Enabled: "opacity-100 duration-200 ease-in" -->
                    <span class="opacity-100 duration-200 ease-in absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" aria-hidden="true" v-if="!props.record.data?.invoiced" :class="{ 'opacity-0 duration-100 ease-out': props.record.data?.invoiced, 'opacity-100 duration-200 ease-in': !props.record.data?.invoiced }">
                    <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 12 12">
                        <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    </span>
                    <!-- Enabled: "opacity-100 duration-200 ease-in", Not Enabled: "opacity-0 duration-100 ease-out" -->
                    <span class="opacity-0 duration-100 ease-out absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" aria-hidden="true" v-if="props.record.data?.invoiced" :class="{ 'opacity-100 duration-200 ease-in': props.record.data?.invoiced, 'opacity-0 duration-100 ease-out': !props.record.data?.invoiced }">
                    <svg class="h-3 w-3 text-blue-900" fill="currentColor" viewBox="0 0 12 12">
                        <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                    </svg>
                    </span>
                </span>
            </button>

        </dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">Processado</dt>
          <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">

            <button type="button" class="bg-blue-900 relative inline-flex h-6 w-11 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2" role="switch" :aria-checked="props.record.data?.processed" :class="{ 'bg-blue-900': props.record.data?.processed, 'bg-gray-200': !props.record.data?.processed }">
                <span class="sr-only"></span>
                <!-- Enabled: "translate-x-5", Not Enabled: "translate-x-0" -->
                <span class="translate-x-0 pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="{ 'translate-x-5': props.record.data?.processed, 'translate-x-0': !props.record.data?.processed }">
                    <!-- Enabled: "opacity-0 duration-100 ease-out", Not Enabled: "opacity-100 duration-200 ease-in" -->
                    <span class="opacity-100 duration-200 ease-in absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" aria-hidden="true" v-if="!props.record.data?.processed" :class="{ 'opacity-0 duration-100 ease-out': props.record.data?.processed, 'opacity-100 duration-200 ease-in': !props.record.data?.processed }">
                    <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 12 12">
                        <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    </span>
                    <!-- Enabled: "opacity-100 duration-200 ease-in", Not Enabled: "opacity-0 duration-100 ease-out" -->
                    <span class="opacity-0 duration-100 ease-out absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" aria-hidden="true" v-if="props.record.data?.processed" :class="{ 'opacity-100 duration-200 ease-in': props.record.data?.processed, 'opacity-0 duration-100 ease-out': !props.record.data?.processed }">
                    <svg class="h-3 w-3 text-blue-900" fill="currentColor" viewBox="0 0 12 12">
                        <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                    </svg>
                    </span>
                </span>
            </button>

          </dd>
        </div>
        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
          <dt class="text-sm font-medium leading-6 text-gray-900">Documentos</dt>
          <dd class="mt-2 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
            <ul role="list" class="divide-y divide-gray-100 rounded-md border border-gray-200">
             <!-- <li class="flex items-center justify-between py-4 pl-4 pr-5 text-sm leading-6">
                <div class="flex w-0 flex-1 items-center">
                  <DocumentIcon class="h-5 w-5 flex-shrink-0 text-gray-400" aria-hidden="true" />
                  <div class="ml-4 flex min-w-0 flex-1 gap-2">
                    <span class="truncate font-medium">Folha de Trabalho</span>
                    <span class="flex-shrink-0 text-gray-400">4.5mb</span>
                  </div>
                </div>
                <div class="ml-4 flex-shrink-0">
                  <a target="_blank" :href="props.record.data?.links?.pdf_path" class="font-medium text-blue-900 hover:text-blue-800">Visualizar</a>
                </div>
              </li>
              <li class="flex items-center justify-between py-4 pl-4 pr-5 text-sm leading-6" v-if="props.data?.type == 'programmed'">
                <div class="flex w-0 flex-1 items-center">
                  <DocumentTextIcon class="h-5 w-5 flex-shrink-0 text-gray-400" aria-hidden="true" />
                  <div class="ml-4 flex min-w-0 flex-1 gap-2">
                    <span class="truncate font-medium">Termo de Colheita</span>
                    <span class="flex-shrink-0 text-gray-400">2.4mb</span>
                  </div>
                </div>
                <div class="ml-4 flex-shrink-0">
                  <a target="_blank" :href="props.record.data?.links?.pdf_collection_term" class="font-medium text-blue-900 hover:text-blue-800">Visualizar</a>
                </div>
              </li> -->
              
            </ul>
          </dd>
        </div>

      </dl>
    </div>
</div>

</template>
