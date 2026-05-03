<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { ref, computed, onMounted } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { ResultsDataService } from '@/Services/ResultsDataService.js';
import InsertResultComponent from '@/Components/results/InsertResultComponent.vue';
import VerifyResultComponent from '@/Components/results/VerifyResultComponent.vue';
import ApproveResultComponent from '@/Components/results/ApproveResultComponent.vue';
import CalculationModal from '@/Components/results/CalculationModal.vue';
import { ClipboardDocumentCheckIcon } from "@heroicons/vue/24/outline";
import axios from 'axios';

defineOptions({
  layout: Layout
});

const props = defineProps({
    action: String,
    record: Object,
    parameters: Array,
    results_summary: {
        type: Object,
        default: () => ({
            total: 0,
            pending_insertion: 0,
            pending_verification: 0,
            pending_approval: 0,
            approved: 0,
        }),
    },
    report_studio: {
        type: Object,
        default: null,
    },
    scope_audit: {
        type: Object,
        default: () => ({}),
    },
    worksheet_brief: {
        type: Object,
        default: null,
    },
    can_insert: {
        type: Boolean,
        default: false,
    },
    can_verify: {
        type: Boolean,
        default: false,
    },
    can_approve: {
        type: Boolean,
        default: false,
    },
});

// Reactive state
const showCalculationModal = ref(false);
const calculationParameters = ref([]);
const existingCalculationData = ref({});

// Main form
const form = useForm({
    action: props.action,
    id: props.record?.id,
    cl_id: props.record?.cl_id,
    sample_id: props.record?.sample_id,
    department_id: props.record?.department_id,
    type_id: props.record?.type_id,
    results: [],
    // Common fields for all actions
    notes: '',
    status: 'pending', // Will be 'verified', 'approved', etc.
    performed_by: null,
    performed_at: null
});

// Computed properties
const workflowComponents = {
    analyze: InsertResultComponent,
    verify: VerifyResultComponent,
    approve: ApproveResultComponent
};

const CurrentComponent = computed(() => workflowComponents[props.action] || InsertResultComponent);
const workflowLabel = computed(() => (
    props.action === 'analyze'
        ? 'Inserção'
        : props.action === 'verify'
            ? 'Verificação'
            : 'Aprovação'
));
const workflowStatusCards = computed(() => ([
    { label: 'Pendentes de inserção', value: props.results_summary?.pending_insertion ?? 0 },
    { label: 'Pendentes de verificação', value: props.results_summary?.pending_verification ?? 0 },
    { label: 'Pendentes de aprovação', value: props.results_summary?.pending_approval ?? 0 },
    { label: 'Aprovados', value: props.results_summary?.approved ?? 0 },
]));

const conditioningLabels = {
    accepted: 'Aceite',
    restricted: 'Aceite com restrições',
    rejected: 'Rejeitado / quarentena',
};

const scopeSummaryCards = computed(() => ([
    { label: 'Perfil analítico', value: props.scope_audit?.expected_count ?? 0 },
    { label: 'Planeado na receção', value: props.scope_audit?.reception_count ?? 0 },
    { label: 'Resultados lançados', value: props.scope_audit?.results_count ?? 0 },
    { label: 'Faltam no fluxo', value: props.scope_audit?.missing_from_results?.length ?? 0 },
]));

const hasScopeDrift = computed(() => {
    return Boolean(
        (props.scope_audit?.scope_drift?.reception_only?.length ?? 0)
        || (props.scope_audit?.scope_drift?.profile_only?.length ?? 0)
        || (props.scope_audit?.outside_profile_scope?.length ?? 0)
    );
});

const scopeDriftSummary = computed(() => ([
    {
        label: 'Na receção, fora do perfil',
        items: props.scope_audit?.scope_drift?.reception_only ?? [],
    },
    {
        label: 'No perfil, fora da receção',
        items: props.scope_audit?.scope_drift?.profile_only ?? [],
    },
    {
        label: 'Resultados fora do perfil',
        items: props.scope_audit?.outside_profile_scope ?? [],
    },
]));

const hasCalculatedParameters = computed(() => {
    return form.results?.some(p => p.requires_calculation && p.active);
});

const calculationReadiness = computed(() => {
    return (form.results ?? [])
        .filter((result) => result.requires_calculation && result.active)
        .map((result) => ({
            code: result.parameter_id?.code || result.parameter_label,
            name: result.parameter_id?.name || result.parameter_label,
            ...ResultsDataService.getCalculationReadiness(result, form.results, props.action),
        }));
});

const executionControlCards = computed(() => ([
    {
        label: 'Manuais',
        value: separatedResults.value.manualParams?.length ?? 0,
    },
    {
        label: 'Variáveis de entrada',
        value: separatedResults.value.inputVariables?.length ?? 0,
    },
    {
        label: 'Calculados',
        value: separatedResults.value.calculatedParams?.length ?? 0,
    },
    {
        label: 'Cálculos bloqueados',
        value: calculationReadiness.value.filter((item) => !item.ready).length,
    },
]));

const blockingCalculatedParameters = computed(() => {
    return calculationReadiness.value.filter((item) => !item.ready);
});

const createWorksheetDraft = () => {
    router.post(route('analysis.worksheet-draft', props.record?.id), {}, {
        preserveScroll: true,
    });
};

// Separate results by type for better organization
const separatedResults = computed(() => {
    return ResultsDataService.separateCalculatedParameters(form.results);
});

// Load data
onMounted(async () => {
    await loadResultParameters();
});

async function loadResultParameters() {
    try {
        const response = await axios.get('/results/getDefaultResultsData', {
            params: {
                action: props.action,
                sample_id: props.record?.sample_id?.value
            }
        });
        
        form.results = ResultsDataService.normalizeResults(response.data);
        
        // Prepare calculation data if needed
        if (hasCalculatedParameters.value) {
            calculationParameters.value = separatedResults.value.calculatedParams;
            existingCalculationData.value = ResultsDataService.prepareForCalculation(
                calculationParameters.value,
                form.results
            );
        }
        
    } catch (error) {
        console.error('Error loading results:', error);
        form.results = [];
    }
}

// Open calculation modal
const openCalculationModal = () => {
    calculationParameters.value = separatedResults.value.calculatedParams;
    existingCalculationData.value = ResultsDataService.prepareForCalculation(
        calculationParameters.value,
        form.results
    );
    
    if (calculationParameters.value.length > 0) {
        showCalculationModal.value = true;
    } else {
        alert("Nenhum parâmetro calculado definido para esta amostra.");
    }
};

// Handle calculation results
const handleCalculatedResults = (calculationPayload) => {
    form.results = ResultsDataService.mergeCalculationResults(
        form.results,
        calculationPayload,
        props.action
    );
    showCalculationModal.value = false;

};

// Submit results
const submitResults = () => {
    if (form.processing) {
        return;
    }

    // Prepare form data based on action
    const submissionData = {
        action: form.action,
        sample_id: form.sample_id,
        results: form.results,
        notes: form.notes,
        status: form.status,
        performed_by: form.performed_by,
        performed_at: form.performed_at
    };

     // Add action-specific fields
    if (form.action === 'verify') {
        submissionData.verification_notes = form.notes;
        submissionData.verification_status = form.status;
        submissionData.verified_by = form.performed_by;
        submissionData.verified_at = form.performed_at;
    } else if (form.action === 'approve') {
        submissionData.approval_notes = form.notes;
        submissionData.approval_status = form.status;
        submissionData.approved_by = form.performed_by;
        submissionData.approved_at = form.performed_at;
    }
    form.post(route('results.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};

// Expose to child components
defineExpose({
    form,
    openCalculationModal,
    submitResults,
    hasCalculatedParameters
});
</script>

<template>
<div class="min-h-screen bg-slate-50 dark:bg-slate-950">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <section class="mb-8 overflow-hidden rounded-[2rem] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.32),_transparent_34%),radial-gradient(circle_at_top_right,_rgba(16,185,129,0.14),_transparent_26%),linear-gradient(135deg,_#020617,_#0f172a_48%,_#0b1120)] p-6 text-white shadow-sm">
            <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
                <div class="max-w-4xl">
                    <div class="mb-4 inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-cyan-100">
                        Workflow laboratorial controlado
                    </div>
                    <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">
                        {{ action === 'analyze' ? 'Inserção de Resultados' : 
                           action === 'verify' ? 'Verificação de Resultados' : 
                           'Validação de Resultados' }}
                    </h1>
                    <p class="mt-3 text-sm leading-7 text-slate-200 sm:text-base">
                        Uma superfície única para lançar resultados, rever coerência analítica, resolver cálculos e fechar a aprovação
                        final com contexto de receção, worksheet e template de relatório sempre visíveis.
                    </p>
                    <p class="mt-3 text-sm text-slate-300">
                        {{ record?.cl_id?.label }} - {{ record?.department_id?.label }}
                    </p>
                </div>
                
                <!-- Calculation Button (only for analyze) -->
                <button v-if="action === 'analyze' && hasCalculatedParameters"
                        @click="openCalculationModal"
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/10 bg-white/10 px-5 py-3 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/15">
                    <ClipboardDocumentCheckIcon class="h-5 w-5" />
                    Calcular Parâmetros
                </button>
            </div>
        </section>

        <div class="mb-8 grid gap-4 lg:grid-cols-[minmax(0,2fr),minmax(320px,1fr)]">
            <section class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">
                            Gestão do fluxo analítico
                        </p>
                        <h2 class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
                            Etapa atual: {{ workflowLabel }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                            O laboratório pode inserir, rever e aprovar resultados sem perder visibilidade do estado pendente.
                        </p>
                    </div>
                    <div class="rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        {{ props.results_summary?.total ?? form.results.length }} parâmetros no fluxo
                    </div>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article
                        v-for="card in workflowStatusCards"
                        :key="card.label"
                        class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/70"
                    >
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            {{ card.label }}
                        </p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">
                            {{ card.value }}
                        </p>
                    </article>
                </div>
            </section>

            <section class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">
                    Studio do relatório
                </p>
                <div v-if="report_studio" class="mt-3 space-y-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ report_studio.name }}</h3>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                            {{ report_studio.description || 'Template ativo para a emissão final do relatório analítico.' }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 dark:bg-blue-500/15 dark:text-blue-200">
                            {{ report_studio.renderer === 'canva' ? 'Canva premium' : 'Renderização interna' }}
                        </span>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            {{ report_studio.theme_preset || 'Tema padrão' }}
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a
                            v-if="report_studio.canva_design_url"
                            :href="report_studio.canva_design_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center rounded-2xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
                        >
                            Abrir template Canva
                        </a>
                        <a
                            v-if="$page.props.auth?.user?.roles?.includes?.('admin')"
                            :href="route('report-studios.index')"
                            class="inline-flex items-center rounded-2xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                        >
                            Gerir studios
                        </a>
                    </div>
                </div>
                <div v-else class="mt-3 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-300">
                    Nenhum studio analítico ativo foi configurado ainda. Ative um template para padronizar a emissão dos relatórios.
                </div>
            </section>
        </div>

        <div class="grid gap-4 xl:grid-cols-[minmax(0,2fr),minmax(320px,1fr)]">
            <section class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                            Escopo controlado
                        </p>
                        <h2 class="mt-1 text-lg font-semibold text-slate-900">
                            Parâmetros esperados para esta amostra
                        </h2>
                        <p class="mt-1 text-sm text-slate-600">
                            O técnico trabalha contra o perfil analítico atribuído e a fotografia registada na receção.
                        </p>
                    </div>
                    <span
                        :class="[
                            'rounded-full px-3 py-1 text-xs font-semibold ring-1',
                            hasScopeDrift
                                ? 'bg-amber-50 text-amber-800 ring-amber-200'
                                : 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                        ]"
                    >
                        {{ hasScopeDrift ? 'Deriva detectada' : 'Escopo consistente' }}
                    </span>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article
                        v-for="card in scopeSummaryCards"
                        :key="card.label"
                        class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3"
                    >
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                            {{ card.label }}
                        </p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">
                            {{ card.value }}
                        </p>
                    </article>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-900">Perfis resolvidos na receção</p>
                        <div v-if="props.scope_audit?.resolved_profiles?.length" class="mt-3 flex flex-wrap gap-2">
                            <span
                                v-for="profile in props.scope_audit.resolved_profiles"
                                :key="profile.id"
                                class="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-900"
                            >
                                {{ profile.name }}
                            </span>
                        </div>
                        <p v-else class="mt-3 text-sm text-slate-500">
                            A receção não registou perfis resolvidos.
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-900">Condicionamento ISO 17025</p>
                        <dl class="mt-3 space-y-2 text-sm text-slate-600">
                            <div class="flex items-center justify-between gap-3">
                                <dt>Decisão</dt>
                                <dd class="font-medium text-slate-900">
                                    {{ conditioningLabels[props.scope_audit?.conditioning_status] || 'Não avaliado' }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt>Embalagem</dt>
                                <dd class="font-medium text-slate-900">
                                    {{ props.scope_audit?.packaging_condition || 'N/A' }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt>Condição térmica</dt>
                                <dd class="font-medium text-slate-900">
                                    {{ props.scope_audit?.temperature_condition || 'N/A' }}
                                </dd>
                            </div>
                        </dl>
                        <p v-if="props.scope_audit?.integrity_observations" class="mt-3 text-xs text-slate-600">
                            Integridade: {{ props.scope_audit.integrity_observations }}
                        </p>
                        <p v-if="props.scope_audit?.chain_of_custody_notes" class="mt-2 text-xs text-slate-600">
                            Cadeia de custódia: {{ props.scope_audit.chain_of_custody_notes }}
                        </p>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="text-sm font-semibold text-slate-900">Parâmetros em falta nos resultados</p>
                        <ul v-if="props.scope_audit?.missing_from_results?.length" class="mt-3 space-y-2 text-sm text-slate-700">
                            <li v-for="parameter in props.scope_audit.missing_from_results" :key="parameter.id">
                                {{ parameter.code || 'N/D' }} · {{ parameter.name }}
                            </li>
                        </ul>
                        <p v-else class="mt-3 text-sm text-emerald-700">
                            Não há parâmetros em falta no conjunto atual de resultados.
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="text-sm font-semibold text-slate-900">Deriva entre receção e perfil</p>
                        <div v-if="hasScopeDrift" class="mt-3 space-y-3">
                            <div v-for="bucket in scopeDriftSummary" :key="bucket.label">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ bucket.label }}
                                </p>
                                <ul v-if="bucket.items.length" class="mt-2 space-y-1 text-sm text-amber-800">
                                    <li v-for="parameter in bucket.items" :key="`${bucket.label}-${parameter.id}`">
                                        {{ parameter.code || 'N/D' }} · {{ parameter.name }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <p v-else class="mt-3 text-sm text-emerald-700">
                            O escopo registado na receção coincide com o perfil e os resultados atuais.
                        </p>
                    </div>
                </div>
            </section>

            <section class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                    Worksheet
                </p>
                <div v-if="props.worksheet_brief?.exists" class="mt-3 space-y-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">{{ props.worksheet_brief.name }}</h3>
                        <p class="mt-1 text-sm text-slate-600">
                            Última atualização: {{ new Date(props.worksheet_brief.updated_at).toLocaleString() }}
                        </p>
                    </div>
                    <a
                        :href="route('worksheets.show', props.worksheet_brief.id)"
                        class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    >
                        Abrir worksheet
                    </a>
                </div>
                <div v-else class="mt-3 space-y-3">
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600">
                        Nenhuma worksheet foi vinculada automaticamente a esta análise ainda.
                    </div>
                    <button
                        type="button"
                        @click="createWorksheetDraft"
                        class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
                    >
                        Criar worksheet inicial
                    </button>
                </div>
            </section>
        </div>

        <div class="mt-4 rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                        Controlo de execução
                    </p>
                    <h2 class="mt-1 text-lg font-semibold text-slate-900">
                        Estado dos parâmetros na bancada
                    </h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Distingue parâmetros manuais, variáveis de entrada e cálculos ainda bloqueados por dados em falta.
                    </p>
                </div>
                <span
                    :class="[
                        'rounded-full px-3 py-1 text-xs font-semibold ring-1',
                        blockingCalculatedParameters.length
                            ? 'bg-amber-50 text-amber-800 ring-amber-200'
                            : 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                    ]"
                >
                    {{ blockingCalculatedParameters.length ? 'Entradas em falta' : 'Pronto para cálculo' }}
                </span>
            </div>

            <div v-if="Object.keys(form.errors).length" class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4">
                <p class="text-sm font-semibold text-red-900">
                    A submissão foi bloqueada por validação do fluxo controlado.
                </p>
                <ul class="mt-3 space-y-2 text-sm text-red-800">
                    <li v-for="(error, key) in form.errors" :key="key">
                        {{ Array.isArray(error) ? error.join(', ') : error }}
                    </li>
                </ul>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="card in executionControlCards"
                    :key="card.label"
                    class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3"
                >
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                        {{ card.label }}
                    </p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">
                        {{ card.value }}
                    </p>
                </article>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-sm font-semibold text-slate-900">Parâmetros calculados prontos</p>
                    <ul v-if="calculationReadiness.filter((item) => item.ready).length" class="mt-3 space-y-2 text-sm text-emerald-800">
                        <li v-for="item in calculationReadiness.filter((entry) => entry.ready)" :key="`ready-${item.code}`">
                            {{ item.code }} · {{ item.name }}
                        </li>
                    </ul>
                    <p v-else class="mt-3 text-sm text-slate-500">
                        Ainda não existem cálculos com todos os dados de entrada disponíveis.
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-sm font-semibold text-slate-900">Cálculos bloqueados por entradas em falta</p>
                    <div v-if="blockingCalculatedParameters.length" class="mt-3 space-y-3">
                        <div
                            v-for="item in blockingCalculatedParameters"
                            :key="`blocked-${item.code}`"
                            class="rounded-lg border border-amber-200 bg-amber-50 p-3"
                        >
                            <p class="text-sm font-semibold text-amber-900">
                                {{ item.code }} · {{ item.name }}
                            </p>
                            <p class="mt-1 text-xs text-amber-800">
                                Faltam: {{ item.missingVariables.join(', ') }}
                            </p>
                        </div>
                    </div>
                    <p v-else class="mt-3 text-sm text-emerald-700">
                        Nenhum cálculo está bloqueado por falta de variáveis de entrada.
                    </p>
                </div>
            </div>
        </div>

        <!-- Workflow Component -->
        <component :is="CurrentComponent"
                   :form="form"
                   :record="record"
                   :action="action"
                   :separated-results="separatedResults"
                   @open-calculation="openCalculationModal"
                   @submit="submitResults" />
    </div>

    <!-- Calculation Modal -->
    <CalculationModal
        v-if="showCalculationModal"
        :parameters="calculationParameters"
        :existing-results="existingCalculationData"
        @close="showCalculationModal = false"
        @calculated="handleCalculatedResults"
    />
</div>
</template>
