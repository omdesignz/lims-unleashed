<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link } from "@inertiajs/vue3";
import {
  ArrowLeftIcon,
  ArrowPathIcon,
  BuildingOffice2Icon,
  CheckCircleIcon,
  DocumentMagnifyingGlassIcon,
  EnvelopeIcon,
  ExclamationTriangleIcon,
  IdentificationIcon,
  InformationCircleIcon,
  MagnifyingGlassIcon,
  MapPinIcon,
  PhoneIcon,
} from "@heroicons/vue/24/outline";
import { computed, ref } from "vue";

defineOptions({ layout: Layout });

const taxNumber = ref("");
const taxData = ref(null);
const isLoading = ref(false);
const searchError = ref("");

const hasTaxData = computed(() => Boolean(taxData.value));
const status = computed(() => {
  if (isLoading.value) {
    return { label: "A consultar", icon: ArrowPathIcon, className: "bg-cyan-50 text-cyan-700 ring-cyan-200 dark:bg-cyan-500/10 dark:text-cyan-200 dark:ring-cyan-400/20" };
  }

  if (searchError.value) {
    return { label: "Consulta falhou", icon: ExclamationTriangleIcon, className: "bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20" };
  }

  if (hasTaxData.value) {
    return { label: "Dados encontrados", icon: CheckCircleIcon, className: "bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20" };
  }

  return { label: "Aguardando NIF", icon: DocumentMagnifyingGlassIcon, className: "bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)] ring-[var(--ds-border)]" };
});

const resultFields = computed(() => [
  { label: "Nome legal", value: taxData.value?.gsmc, icon: BuildingOffice2Icon },
  { label: "Regime de IVA", value: taxData.value?.regimeIva, icon: IdentificationIcon },
  { label: "Correio electrónico", value: taxData.value?.email, icon: EnvelopeIcon },
  { label: "Contacto", value: taxData.value?.lxfs, icon: PhoneIcon },
]);

async function getTaxData() {
  const normalizedTaxNumber = taxNumber.value.trim();

  if (!normalizedTaxNumber) {
    searchError.value = "Introduza um NIF antes de consultar.";
    return;
  }

  isLoading.value = true;
  searchError.value = "";
  taxData.value = null;

  try {
    const response = await fetch(`/customers/tax-data?${new URLSearchParams({ tax_number: normalizedTaxNumber })}`, {
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    if (!response.ok || !response.headers.get("content-type")?.includes("application/json")) {
      throw new Error("A autoridade fiscal não devolveu uma resposta válida.");
    }

    taxData.value = await response.json();
  } catch (error) {
    searchError.value = error instanceof Error ? error.message : "Não foi possível consultar o NIF.";
  } finally {
    isLoading.value = false;
  }
}

function clearSearch() {
  taxNumber.value = "";
  taxData.value = null;
  searchError.value = "";
}
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <nav aria-label="Breadcrumb">
        <Link :href="route('customers.index')" class="inline-flex items-center gap-1.5 text-xs font-bold text-[var(--ds-text-muted)] hover:text-[rgb(var(--primary-700-rgb))]">
          <ArrowLeftIcon class="h-4 w-4" />
          Clientes
        </Link>
      </nav>

      <div class="mt-5 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex min-w-0 items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <DocumentMagnifyingGlassIcon class="h-5 w-5" />
          </span>
          <div class="min-w-0">
            <p class="ds-kicker">Validação fiscal</p>
            <h1 class="ds-heading mt-1 text-2xl">Consulta de NIF</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">Confirme a identidade legal e o regime fiscal antes de concluir o registo ou emitir documentos comerciais.</p>
          </div>
        </div>

        <span :class="['ds-chip', status.className]">
          <component :is="status.icon" :class="['h-4 w-4', isLoading ? 'animate-spin' : '']" />
          {{ status.label }}
        </span>
      </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
      <div class="min-w-0 space-y-6">
        <section class="ds-card overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
            <h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
              <MagnifyingGlassIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />
              Pesquisar contribuinte
            </h2>
            <p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">Introduza o número de identificação fiscal sem espacos adicionais.</p>
          </header>

          <form class="px-5 py-5 sm:px-6" @submit.prevent="getTaxData">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
              <div class="ds-field-group min-w-0 flex-1">
                <label for="tax-number" class="ds-field-label">Número de identificação fiscal <span class="ds-field-required">*</span></label>
                <div class="relative">
                  <IdentificationIcon class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-[var(--ds-text-soft)]" />
                  <BaseInput id="tax-number" v-model="taxNumber" type="text" inputmode="numeric" class="ds-field pl-10 font-mono" autocomplete="off" :aria-invalid="Boolean(searchError)" placeholder="Introduza o NIF" />
                </div>
                <p v-if="searchError" class="ds-field-error">{{ searchError }}</p>
                <p v-else class="ds-field-hint">Prima Enter ou use o botao Consultar.</p>
              </div>

              <div class="flex gap-2 sm:pt-6">
                <button type="submit" class="ds-button ds-button-primary" :disabled="isLoading || !taxNumber.trim()">
                  <ArrowPathIcon v-if="isLoading" class="h-4 w-4 animate-spin" />
                  <MagnifyingGlassIcon v-else class="h-4 w-4" />
                  {{ isLoading ? "A consultar..." : "Consultar" }}
                </button>
                <button v-if="hasTaxData || searchError" type="button" class="ds-button ds-button-secondary" @click="clearSearch">Limpar</button>
              </div>
            </div>
          </form>
        </section>

        <section v-if="hasTaxData" class="ds-card overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:flex sm:items-start sm:justify-between sm:gap-4 sm:px-6">
            <div>
              <h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
                <CheckCircleIcon class="h-5 w-5 text-emerald-600 dark:text-emerald-300" />
                Identidade fiscal encontrada
              </h2>
              <p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">Dados devolvidos para o NIF <span class="font-mono font-bold">{{ taxNumber }}</span>.</p>
            </div>
            <button type="button" class="ds-button ds-button-secondary mt-3 sm:mt-0" :disabled="isLoading" @click="getTaxData">
              <ArrowPathIcon class="h-4 w-4" />
              Actualizar
            </button>
          </header>

          <dl class="grid gap-px bg-[var(--ds-border)] sm:grid-cols-2">
            <div v-for="field in resultFields" :key="field.label" class="bg-[var(--ds-panel)] px-5 py-5 sm:px-6">
              <dt class="flex items-center gap-2 text-xs font-bold uppercase text-[var(--ds-text-soft)]">
                <component :is="field.icon" class="h-4 w-4" />
                {{ field.label }}
              </dt>
              <dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ field.value || "Não disponível" }}</dd>
            </div>
          </dl>

          <div class="border-t border-[var(--ds-border)] px-5 py-5 sm:px-6">
            <dt class="flex items-center gap-2 text-xs font-bold uppercase text-[var(--ds-text-soft)]">
              <MapPinIcon class="h-4 w-4" />
              Endereço fiscal
            </dt>
            <dd class="mt-2 whitespace-pre-line break-words text-sm font-semibold text-[var(--ds-text-muted)]">{{ taxData.addressDbb || "Endereço não disponibilizado pela fonte." }}</dd>
          </div>
        </section>

        <div v-else-if="!isLoading && !searchError" class="ds-empty-state py-14 text-center">
          <DocumentMagnifyingGlassIcon class="mx-auto h-10 w-10 text-[var(--ds-text-soft)]" />
          <h2 class="mt-4 text-sm font-bold text-[var(--ds-text)]">Nenhuma consulta efetuada</h2>
          <p class="ds-copy mx-auto mt-1 max-w-md text-sm">Os dados legais e fiscais aparecerao aqui depois de consultar um NIF válido.</p>
        </div>
      </div>

      <aside class="space-y-6">
        <section class="ds-card overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4">
            <h2 class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]">
              <InformationCircleIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />
              Controlo da consulta
            </h2>
          </header>
          <dl class="divide-y divide-[var(--ds-border)] px-5">
            <div class="flex items-center justify-between gap-3 py-3.5"><dt class="text-xs font-bold text-[var(--ds-text-muted)]">NIF informado</dt><dd class="font-mono text-xs font-bold text-[var(--ds-text)]">{{ taxNumber || "Não" }}</dd></div>
            <div class="flex items-center justify-between gap-3 py-3.5"><dt class="text-xs font-bold text-[var(--ds-text-muted)]">Dados disponíveis</dt><dd class="text-xs font-bold text-[var(--ds-text)]">{{ hasTaxData ? "Sim" : "Não" }}</dd></div>
            <div class="flex items-center justify-between gap-3 py-3.5"><dt class="text-xs font-bold text-[var(--ds-text-muted)]">Em processamento</dt><dd class="text-xs font-bold text-[var(--ds-text)]">{{ isLoading ? "Sim" : "Não" }}</dd></div>
          </dl>
        </section>

        <section class="ds-card p-5">
          <h2 class="text-sm font-bold text-[var(--ds-text)]">Antes de usar os dados</h2>
          <ul class="mt-4 space-y-3 text-sm font-semibold text-[var(--ds-text-muted)]">
            <li class="flex items-start gap-2"><CheckCircleIcon class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-300" />Confirme a designação legal com o cliente.</li>
            <li class="flex items-start gap-2"><CheckCircleIcon class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-300" />Valide o regime de IVA no documento comercial.</li>
            <li class="flex items-start gap-2"><CheckCircleIcon class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-300" />Registe o endereço no local de facturação correcto.</li>
          </ul>
        </section>
      </aside>
    </div>
  </div>
</template>
