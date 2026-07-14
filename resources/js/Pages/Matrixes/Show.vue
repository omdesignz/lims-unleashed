<script setup>
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link } from "@inertiajs/vue3";
import {
  ArrowLeftIcon,
  BanknotesIcon,
  BeakerIcon,
  BuildingOffice2Icon,
  CheckBadgeIcon,
  ClipboardDocumentCheckIcon,
  DocumentDuplicateIcon,
  ExclamationTriangleIcon,
  PencilSquareIcon,
  PlusIcon,
  ReceiptPercentIcon,
  RectangleGroupIcon,
} from "@heroicons/vue/24/outline";
import { computed } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, required: true },
});

const { hasPermission } = usePermission();
const matrix = computed(() => props.record?.data ?? props.record ?? {});
const profiles = computed(() => matrix.value.profiles ?? []);
const departmentNames = computed(() => [...new Set(profiles.value
  .map((profile) => profile.category_id?.department)
  .filter(Boolean))]);
const parameterCount = computed(() => profiles.value.reduce((total, profile) => total + (profile.parameters?.length || 0), 0));
const activeParameterCount = computed(() => profiles.value.reduce((total, profile) => {
  return total + (profile.parameters || []).filter((parameter) => parameter.active !== false).length;
}, 0));
const commercialVariance = computed(() => Number(matrix.value.fixed_price || 0) - Number(matrix.value.price || 0));

const metrics = computed(() => [
  { label: "Perfis", value: profiles.value.length, detail: "âmbitos analíticos", icon: ClipboardDocumentCheckIcon },
  { label: "Parâmetros", value: parameterCount.value, detail: `${activeParameterCount.value} activos`, icon: BeakerIcon },
  { label: "Preço composto", value: formatCurrency(matrix.value.price), detail: "soma dos perfis", icon: BanknotesIcon },
  { label: "Preço fixo", value: formatCurrency(matrix.value.fixed_price), detail: `diferenca ${formatCurrency(commercialVariance.value)}`, icon: ReceiptPercentIcon },
]);

function formatCurrency(value) {
  return new Intl.NumberFormat("pt-PT", {
    style: "currency",
    currency: "AOA",
    minimumFractionDigits: 2,
  }).format(Number(value || 0));
}
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-5 py-5 sm:px-6">
        <nav aria-label="Breadcrumb">
          <Link :href="route('matrixes.index')" class="inline-flex items-center gap-1.5 text-xs font-bold text-[var(--ds-text-muted)] hover:text-[rgb(var(--primary-700-rgb))]">
            <ArrowLeftIcon class="h-4 w-4" />
            Matrizes
          </Link>
        </nav>
        <div class="mt-5 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
          <div class="flex min-w-0 items-start gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <RectangleGroupIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <p class="ds-kicker">Matriz #{{ matrix.id }}</p>
              <h1 class="ds-heading mt-1 break-words text-2xl">{{ matrix.code }}</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm">Âmbito comercial controlado para associação a produtos, pedidos e amostras.</p>
              <div class="mt-3 flex flex-wrap gap-2">
                <span class="ds-chip">{{ profiles.length }} perfil(is)</span>
                <span v-for="department in departmentNames" :key="department" class="ds-chip">{{ department }}</span>
                <span class="ds-chip">{{ matrix.charge_tax ? `${matrix.tax_percentage}% imposto` : "Isenta" }}</span>
                <span v-if="matrix.deleted" class="ds-chip bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20">Arquivada</span>
              </div>
            </div>
          </div>
          <div class="flex flex-wrap gap-2 lg:justify-end">
            <Link :href="route('matrixes.index')" class="ds-button ds-button-secondary">
              <ArrowLeftIcon class="h-4 w-4" />
              Voltar
            </Link>
            <Link v-if="hasPermission('add_matrixes')" :href="route('matrixes.create')" class="ds-button ds-button-secondary">
              <PlusIcon class="h-4 w-4" />
              Nova matriz
            </Link>
            <Link v-if="hasPermission('edit_matrixes')" :href="route('matrixes.edit', { matrix: matrix.id })" class="ds-button ds-button-primary">
              <PencilSquareIcon class="h-4 w-4" />
              Editar matriz
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
      <div class="min-w-0">
        <section class="ds-card overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:flex sm:items-start sm:justify-between sm:px-6">
            <div>
              <h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
                <ClipboardDocumentCheckIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" /> Perfis analíticos </h2>
              <p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">Composição departamental e cobertura de parâmetros desta matriz.</p>
            </div>
            <span class="ds-chip mt-3 sm:mt-0">{{ profiles.length }} perfil(is)</span>
          </div>

          <div v-if="profiles.length" class="divide-y divide-[var(--ds-border)]">
            <article v-for="(profile, index) in profiles" :key="profile.id" class="px-5 py-5 sm:px-6">
              <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div class="flex min-w-0 items-start gap-3">
                  <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-sm font-bold text-[var(--ds-text-muted)] ring-1 ring-[var(--ds-border)]">{{ index + 1 }}</span>
                  <div>
                    <div class="flex flex-wrap items-center gap-2">
                      <h3 class="text-sm font-bold text-[var(--ds-text)]">{{ profile.name }}</h3>
                      <span v-if="profile.code" class="ds-chip font-mono">{{ profile.code }}</span>
                    </div>
                    <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ profile.category || "Sem categoria analítica" }}</p>
                  </div>
                </div>
                <p class="text-sm font-bold text-[var(--ds-text)]">{{ formatCurrency(profile.price) }}</p>
              </div>

              <dl class="mt-4 grid gap-4 sm:grid-cols-3">
                <div>
                  <dt class="ds-field-label">Departamento</dt>
                  <dd class="mt-1.5 text-sm font-bold text-[var(--ds-text)]">{{ profile.category_id?.department || "Não definido" }}</dd>
                </div>
                <div>
                  <dt class="ds-field-label">Parâmetros</dt>
                  <dd class="mt-1.5 text-sm font-bold text-[var(--ds-text)]">{{ profile.parameters?.length || 0 }} configurado(s)</dd>
                </div>
                <div>
                  <dt class="ds-field-label">Cobertura activa</dt>
                  <dd class="mt-1.5 text-sm font-bold text-[var(--ds-text)]">{{ (profile.parameters || []).filter((parameter) => parameter.active !== false).length }} activo(s)</dd>
                </div>
              </dl>

              <div v-if="profile.parameters?.length" class="mt-4 flex flex-wrap gap-2">
                <span v-for="parameter in profile.parameters" :key="parameter.id" class="ds-chip">
                  {{ parameter.code || parameter.name }}
                </span>
              </div>
            </article>
          </div>

          <div v-else class="px-5 py-14 text-center sm:px-6">
            <ClipboardDocumentCheckIcon class="mx-auto h-9 w-9 text-[var(--ds-text-soft)]" />
            <h3 class="mt-3 text-sm font-bold text-[var(--ds-text)]">Sem perfis associados</h3>
            <p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">A matriz ainda não possui um âmbito analítico utilizável.</p>
          </div>
        </section>
      </div>

      <aside class="space-y-6">
        <section class="ds-card p-5">
          <h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
            <BuildingOffice2Icon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />
            Identidade e âmbito
          </h2>
          <dl class="mt-5 grid gap-4 text-sm">
            <div>
              <dt class="ds-field-label">Código</dt>
              <dd class="mt-1.5 font-mono font-bold text-[var(--ds-text)]">{{ matrix.code }}</dd>
            </div>
            <div>
              <dt class="ds-field-label">Descrição</dt>
              <dd class="mt-1.5 whitespace-pre-line font-medium leading-6 text-[var(--ds-text-muted)]">{{ matrix.description || "Sem descrição operacional." }}</dd>
            </div>
            <div>
              <dt class="ds-field-label">Departamento</dt>
              <dd class="mt-1.5 font-bold text-[var(--ds-text)]">{{ departmentNames.join(", ") || "Não definido" }}</dd>
            </div>
          </dl>
        </section>

        <section class="ds-card p-5">
          <h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
            <ReceiptPercentIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />
            Regra fiscal
          </h2>
          <dl class="mt-5 grid gap-4 text-sm">
            <div>
              <dt class="ds-field-label">Tratamento</dt>
              <dd class="mt-1.5 font-bold text-[var(--ds-text)]">{{ matrix.charge_tax ? "Tributavel" : "Isenta" }}</dd>
            </div>
            <div v-if="matrix.charge_tax">
              <dt class="ds-field-label">Categoria fiscal</dt>
              <dd class="mt-1.5 font-bold text-[var(--ds-text)]">{{ matrix.tax || matrix.tax_id?.name || "Não definida" }}</dd>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ matrix.tax_percentage || 0 }}%</p>
            </div>
            <div v-else>
              <dt class="ds-field-label">Isencao</dt>
              <dd class="mt-1.5 font-bold text-[var(--ds-text)]">{{ matrix.exemption || matrix.exemption_id?.code || "Não definida" }}</dd>
            </div>
            <div>
              <dt class="ds-field-label">Retencao</dt>
              <dd class="mt-1.5 font-bold text-[var(--ds-text)]">{{ matrix.withhold_tax ? "Aplicavel" : "Não aplicavel" }}</dd>
            </div>
          </dl>
        </section>

        <section class="ds-card p-5">
          <h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
            <CheckBadgeIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" /> Controlo do catálogo </h2>
          <div class="mt-4 grid gap-3">
            <p class="flex items-start gap-2 text-sm font-semibold text-[var(--ds-text-muted)]">
              <span :class="['lims-status-dot mt-1', profiles.length ? 'lims-status-dot-release' : 'lims-status-dot-critical']" />
              {{ profiles.length ? "Âmbito de perfis definido" : "Âmbito em falta" }}
            </p>
            <p class="flex items-start gap-2 text-sm font-semibold text-[var(--ds-text-muted)]">
              <span :class="['lims-status-dot mt-1', departmentNames.length === 1 ? 'lims-status-dot-release' : 'lims-status-dot-hold']" />
              {{ departmentNames.length === 1 ? "Departamento único confirmado" : "Rever departamentos" }}
            </p>
            <p class="flex items-start gap-2 text-sm font-semibold text-[var(--ds-text-muted)]">
              <span :class="['lims-status-dot mt-1', activeParameterCount === parameterCount && parameterCount ? 'lims-status-dot-release' : 'lims-status-dot-hold']" />
              {{ activeParameterCount }} de {{ parameterCount }} parâmetros activos </p>
            <p v-if="commercialVariance < 0" class="flex items-start gap-2 rounded-lg bg-amber-50 p-3 text-sm font-semibold text-amber-800 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20">
              <ExclamationTriangleIcon class="mt-0.5 h-4 w-4 shrink-0" /> O preço fixo esta abaixo do valor composto dos perfis. </p>
          </div>
        </section>

        <section class="ds-command-surface p-5">
          <h2 class="text-base font-bold text-[var(--ds-text)]">Acções</h2>
          <div class="mt-4 grid gap-2">
            <Link v-if="hasPermission('edit_matrixes')" :href="route('matrixes.edit', { matrix: matrix.id })" class="ds-button ds-button-primary w-full">
              <PencilSquareIcon class="h-4 w-4" /> Editar composição </Link>
            <Link v-if="hasPermission('add_matrixes')" :href="route('matrixes.create')" class="ds-button ds-button-secondary w-full">
              <DocumentDuplicateIcon class="h-4 w-4" />
              Criar nova matriz
            </Link>
            <Link :href="route('matrixes.index')" class="ds-button ds-button-secondary w-full">
              <ArrowLeftIcon class="h-4 w-4" /> Voltar ao catálogo </Link>
          </div>
        </section>
      </aside>
    </div>
  </div>
</template>
