<script setup>
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link } from "@inertiajs/vue3";
import {
  ArrowLeftIcon,
  BeakerIcon,
  BuildingOffice2Icon,
  CalculatorIcon,
  CheckBadgeIcon,
  ClipboardDocumentCheckIcon,
  ClockIcon,
  CurrencyDollarIcon,
  DocumentDuplicateIcon,
  ExclamationTriangleIcon,
  PencilSquareIcon,
  PlusIcon,
  ScaleIcon,
} from "@heroicons/vue/24/outline";
import { computed } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, required: true },
});

const { hasPermission } = usePermission();
const profile = computed(() => props.record?.data ?? props.record ?? {});
const category = computed(() => profile.value.category_id?.data ?? profile.value.category_id ?? {});
const parameters = computed(() => profile.value.parameters ?? []);
const activeParameters = computed(() => parameters.value.filter((parameter) => parameter.active !== false).length);
const countedParameters = computed(() => parameters.value.filter((parameter) => isCounted(parameter)).length);
const configuredMethods = computed(() => parameters.value.filter((parameter) => {
  const pivot = parameter.pivot ?? {};

  return pivot.protocol_id || pivot.standard_id || pivot.nwp_id;
}).length);

const metrics = computed(() => [
  {
    label: "Ensaios",
    value: parameters.value.length,
    detail: `${activeParameters.value} activos no catálogo`,
    icon: BeakerIcon,
  },
  {
    label: "Preço composto",
    value: formatCurrency(profile.value.price),
    detail: "soma dos ensaios activos",
    icon: CurrencyDollarIcon,
  },
  {
    label: "Métodos definidos",
    value: configuredMethods.value,
    detail: `${parameters.value.length - configuredMethods.value} por completar`,
    icon: ClipboardDocumentCheckIcon,
  },
  {
    label: "Contabilizados",
    value: countedParameters.value,
    detail: "incluidos no resultado final",
    icon: CalculatorIcon,
  },
]);

function parseJsonValue(value, fallback = null) {
  if (value === null || value === undefined || value === "") {
    return fallback;
  }

  if (typeof value !== "string") {
    return value;
  }

  try {
    return JSON.parse(value);
  } catch {
    return value;
  }
}

function extraData(parameter) {
  const parsed = parseJsonValue(parameter.pivot?.extra_data, {});

  return parsed && typeof parsed === "object" && !Array.isArray(parsed) ? parsed : {};
}

function dilutionSteps(parameter) {
  const dilutions = extraData(parameter).dilutions;

  return Array.isArray(dilutions) ? dilutions : [];
}

function formatCollection(value) {
  const parsed = parseJsonValue(value, []);

  if (Array.isArray(parsed)) {
    return parsed.filter(Boolean).join(", ") || "Não definido";
  }

  if (parsed && typeof parsed === "object") {
    return Object.values(parsed).filter(Boolean).join(", ") || "Não definido";
  }

  return parsed || "Não definido";
}

function formatCurrency(value) {
  return new Intl.NumberFormat("pt-PT", {
    style: "currency",
    currency: "AOA",
    minimumFractionDigits: 2,
  }).format(Number(value || 0));
}

function displayValue(value, fallback = "Não definido") {
  return value === null || value === undefined || value === "" ? fallback : value;
}

function referenceRange(parameter) {
  const minimum = parameter.pivot?.min_ref_value;
  const maximum = parameter.pivot?.max_ref_value;

  if (minimum === null || minimum === undefined || minimum === "") {
    return "Não definido";
  }

  return maximum === null || maximum === undefined || maximum === ""
    ? `A partir de ${minimum}`
    : `${minimum} - ${maximum}`;
}

function isCounted(parameter) {
  return ![false, 0, "0"].includes(parameter.pivot?.count);
}
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-5 py-5 sm:px-6">
        <nav aria-label="Breadcrumb">
          <Link :href="route('profiles.index')" class="inline-flex items-center gap-1.5 text-xs font-bold text-[var(--ds-text-muted)] hover:text-[rgb(var(--primary-700-rgb))]">
            <ArrowLeftIcon class="h-4 w-4" /> Perfis analíticos </Link>
        </nav>

        <div class="mt-5 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
          <div class="flex min-w-0 items-start gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <ClipboardDocumentCheckIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <p class="ds-kicker">Perfil analítico #{{ profile.id }}</p>
              <h1 class="ds-heading mt-1 break-words text-2xl">{{ profile.name }}</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm"> Âmbito controlado de ensaios, métodos, critérios de referência e composição comercial. </p>
              <div class="mt-3 flex flex-wrap gap-2">
                <span v-if="profile.code" class="ds-chip font-mono">{{ profile.code }}</span>
                <span class="ds-chip">{{ category.code || profile.category || "Sem categoria" }}</span>
                <span v-if="category.department" class="ds-chip">{{ category.department }}</span>
                <span v-if="profile.deleted" class="ds-chip bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20">
                  Arquivado
                </span>
              </div>
            </div>
          </div>

          <div class="flex flex-wrap gap-2 lg:justify-end">
            <Link :href="route('profiles.index')" class="ds-button ds-button-secondary">
              <ArrowLeftIcon class="h-4 w-4" />
              Voltar
            </Link>
            <Link v-if="hasPermission('add_profiles')" :href="route('profiles.create')" class="ds-button ds-button-secondary">
              <PlusIcon class="h-4 w-4" />
              Novo perfil
            </Link>
            <Link v-if="hasPermission('edit_profiles')" :href="route('profiles.edit', { profile: profile.id })" class="ds-button ds-button-primary">
              <PencilSquareIcon class="h-4 w-4" />
              Editar perfil
            </Link>
          </div>
        </div>
      </div>

      <dl class="grid gap-px bg-[var(--ds-border)] sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="metric in metrics" :key="metric.label" class="bg-[var(--ds-panel)] p-5">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</dt>
              <dd class="mt-3 break-words text-2xl font-bold text-[var(--ds-text)]">{{ metric.value }}</dd>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ metric.detail }}</p>
            </div>
            <component :is="metric.icon" class="h-5 w-5 shrink-0 text-[var(--ds-text-soft)]" />
          </div>
        </div>
      </dl>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
      <main class="min-w-0">
        <section class="ds-card overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:flex sm:items-start sm:justify-between sm:gap-4 sm:px-6">
            <div>
              <h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
                <BeakerIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" /> Composição analítica </h2>
              <p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]"> Métodos, unidades, formulas e critérios aplicados a cada ensaio. </p>
            </div>
            <span class="ds-chip mt-3 sm:mt-0">{{ parameters.length }} ensaio(s)</span>
          </div>

          <div v-if="parameters.length" class="divide-y divide-[var(--ds-border)]">
            <article v-for="(parameter, index) in parameters" :key="parameter.id" class="px-5 py-5 sm:px-6">
              <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div class="flex min-w-0 items-start gap-3">
                  <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-sm font-bold text-[var(--ds-text-muted)] ring-1 ring-[var(--ds-border)]">
                    {{ index + 1 }}
                  </span>
                  <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                      <h3 class="break-words text-sm font-bold text-[var(--ds-text)]">{{ parameter.name }}</h3>
                      <span :class="[
                        'ds-chip',
                        parameter.active !== false
                          ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20'
                          : 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20',
                      ]">
                        {{ parameter.active !== false ? "Activo" : "Inactivo" }}
                      </span>
                      <span v-if="isCounted(parameter)" class="ds-chip">Contabilizado</span>
                    </div>
                    <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
                      <span class="font-mono">{{ parameter.code || "Sem código" }}</span>
                      <span class="mx-2 text-[var(--ds-border-strong)]">/</span>
                      {{ formatCurrency(parameter.price) }}
                    </p>
                  </div>
                </div>

                <div class="flex items-center gap-2 text-xs font-semibold text-[var(--ds-text-muted)]">
                  <ClockIcon class="h-4 w-4" />
                  {{ displayValue(parameter.pivot?.optimal_analysis_time || parameter.optimal_analysis_time, "Tempo não definido") }}
                </div>
              </div>

              <dl class="mt-5 grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                  <dt class="ds-field-label">Unidade</dt>
                  <dd class="mt-1.5 text-sm font-bold text-[var(--ds-text)]">{{ displayValue(parameter.pivot?.unit_label) }}</dd>
                </div>
                <div>
                  <dt class="ds-field-label">Categoria de resultado</dt>
                  <dd class="mt-1.5 text-sm font-bold text-[var(--ds-text)]">{{ displayValue(parameter.pivot?.category_label) }}</dd>
                </div>
                <div>
                  <dt class="ds-field-label">Faixa de referência</dt>
                  <dd class="mt-1.5 text-sm font-bold text-[var(--ds-text)]">{{ referenceRange(parameter) }}</dd>
                </div>
                <div>
                  <dt class="ds-field-label">Origem da referência</dt>
                  <dd class="mt-1.5 text-sm font-bold text-[var(--ds-text)]">{{ displayValue(parameter.pivot?.ref_val_origin) }}</dd>
                </div>
                <div>
                  <dt class="ds-field-label">Protocolo</dt>
                  <dd class="mt-1.5 text-sm font-bold text-[var(--ds-text)]">{{ displayValue(parameter.pivot?.protocol_label) }}</dd>
                </div>
                <div>
                  <dt class="ds-field-label">Norma</dt>
                  <dd class="mt-1.5 text-sm font-bold text-[var(--ds-text)]">{{ displayValue(parameter.pivot?.standard_label) }}</dd>
                </div>
                <div>
                  <dt class="ds-field-label">Procedimento</dt>
                  <dd class="mt-1.5 text-sm font-bold text-[var(--ds-text)]">{{ displayValue(parameter.pivot?.nwp_label) }}</dd>
                </div>
                <div>
                  <dt class="ds-field-label">Fórmula</dt>
                  <dd class="mt-1.5 text-sm font-bold text-[var(--ds-text)]">{{ displayValue(parameter.pivot?.formula_label) }}</dd>
                </div>
              </dl>

              <div v-if="parameter.pivot?.dilutions" class="mt-4 rounded-lg bg-[var(--ds-panel-subtle)] px-4 py-3 ring-1 ring-[var(--ds-border)]">
                <p class="ds-field-label">Diluicoes / analitos</p>
                <p class="mt-1.5 text-sm font-semibold text-[var(--ds-text-muted)]">{{ formatCollection(parameter.pivot.dilutions) }}</p>
              </div>

              <div v-if="dilutionSteps(parameter).length" class="mt-4">
                <p class="ds-field-label">Etapas de diluicao</p>
                <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                  <div v-for="(dilution, dilutionIndex) in dilutionSteps(parameter)" :key="dilutionIndex" class="flex items-center justify-between gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2 text-xs font-semibold text-[var(--ds-text-muted)]">
                    <span>Quantidade: {{ displayValue(dilution.quantity, "-") }}</span>
                    <span>Razao: {{ displayValue(dilution.ratio, "-") }}</span>
                  </div>
                </div>
              </div>
            </article>
          </div>

          <div v-else class="px-5 py-14 text-center sm:px-6">
            <BeakerIcon class="mx-auto h-9 w-9 text-[var(--ds-text-soft)]" />
            <h3 class="mt-3 text-sm font-bold text-[var(--ds-text)]">Sem ensaios configurados</h3>
            <p class="mx-auto mt-1 max-w-md text-sm font-medium text-[var(--ds-text-muted)]"> Este perfil ainda não possui parâmetros analíticos associados. </p>
          </div>
        </section>
      </main>

      <aside class="space-y-6">
        <section class="ds-card p-5">
          <h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
            <BuildingOffice2Icon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />
            Âmbito do perfil
          </h2>
          <dl class="mt-5 grid gap-4 text-sm">
            <div>
              <dt class="ds-field-label">Categoria analítica</dt>
              <dd class="mt-1.5 font-bold text-[var(--ds-text)]">{{ category.name || profile.category || "Não definida" }}</dd>
              <p v-if="category.code" class="mt-1 font-mono text-xs font-semibold text-[var(--ds-text-muted)]">{{ category.code }}</p>
            </div>
            <div>
              <dt class="ds-field-label">Departamento</dt>
              <dd class="mt-1.5 font-bold text-[var(--ds-text)]">{{ category.department || "Não definido" }}</dd>
            </div>
            <div>
              <dt class="ds-field-label">Descrição</dt>
              <dd class="mt-1.5 whitespace-pre-line font-medium leading-6 text-[var(--ds-text-muted)]">
                {{ profile.description || "Sem descrição operacional." }}
              </dd>
            </div>
          </dl>
        </section>

        <section class="ds-card p-5">
          <h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
            <CheckBadgeIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" /> Controlo do catálogo </h2>
          <div class="mt-4 grid gap-3">
            <p class="flex items-start gap-2 text-sm font-semibold text-[var(--ds-text-muted)]">
              <span :class="['lims-status-dot mt-1', parameters.length ? 'lims-status-dot-release' : 'lims-status-dot-critical']" />
              {{ parameters.length ? "Composição analítica definida" : "Composição analítica em falta" }}
            </p>
            <p class="flex items-start gap-2 text-sm font-semibold text-[var(--ds-text-muted)]">
              <span :class="['lims-status-dot mt-1', configuredMethods === parameters.length ? 'lims-status-dot-release' : 'lims-status-dot-hold']" />
              {{ configuredMethods }} de {{ parameters.length }} ensaios com método associado </p>
            <p class="flex items-start gap-2 text-sm font-semibold text-[var(--ds-text-muted)]">
              <span :class="['lims-status-dot mt-1', category.department_id ? 'lims-status-dot-release' : 'lims-status-dot-critical']" />
              {{ category.department_id ? "Departamento responsável definido" : "Departamento responsável em falta" }}
            </p>
            <p v-if="parameters.some((parameter) => parameter.active === false)" class="flex items-start gap-2 rounded-lg bg-amber-50 p-3 text-sm font-semibold text-amber-800 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20">
              <ExclamationTriangleIcon class="mt-0.5 h-4 w-4 shrink-0" /> O perfil inclui parâmetros inactivos e deve ser revisto antes de nova utilização. </p>
          </div>
        </section>

        <section class="ds-command-surface p-5">
          <h2 class="text-base font-bold text-[var(--ds-text)]">Acções</h2>
          <div class="mt-4 grid gap-2">
            <Link v-if="hasPermission('edit_profiles')" :href="route('profiles.edit', { profile: profile.id })" class="ds-button ds-button-primary w-full">
              <PencilSquareIcon class="h-4 w-4" /> Editar composição </Link>
            <Link v-if="hasPermission('add_profiles')" :href="route('profiles.create')" class="ds-button ds-button-secondary w-full">
              <DocumentDuplicateIcon class="h-4 w-4" />
              Criar novo perfil
            </Link>
            <Link :href="route('profiles.index')" class="ds-button ds-button-secondary w-full">
              <ArrowLeftIcon class="h-4 w-4" /> Voltar ao catálogo </Link>
          </div>
        </section>

        <section class="ds-card p-5">
          <h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
            <ScaleIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />
            Resumo comercial
          </h2>
          <p class="mt-4 break-words text-2xl font-bold text-[var(--ds-text)]">{{ formatCurrency(profile.price) }}</p>
          <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">Preço calculado pelos ensaios activos.</p>
        </section>
      </aside>
    </div>
  </div>
</template>
