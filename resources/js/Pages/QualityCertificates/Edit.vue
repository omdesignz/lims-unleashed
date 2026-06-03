<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import combobox from "@/Components/combobox-enhanced.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { loadSelectOptions, optionMappers } from "@/Utils/selectOptions";
import { Link, useForm } from "@inertiajs/vue3";
import {
  ArrowLeftIcon,
  CheckBadgeIcon,
  DocumentTextIcon,
  ShieldCheckIcon,
} from "@heroicons/vue/24/outline";

defineOptions({
  layout: Layout,
});

const props = defineProps({
  record: {
    type: Object,
    required: true,
  },
});

const certificate = props.record?.data ?? {};

const optionFromValue = (value, label) => {
  if (!value) {
    return null;
  }

  return {
    value,
    label: label || String(value),
  };
};

const form = useForm({
  customer_id: optionFromValue(certificate.customer_id, certificate.customer),
  warehouse_id: optionFromValue(certificate.warehouse_id, certificate.warehouse),
  cl_id: optionFromValue(certificate.cl_id, certificate.lab_code),
  invoice_id: null,
  status: Boolean(certificate.status),
  obs: certificate.obs ?? "",
});

const submit = () => {
  form.put(route("qualitycertificates.update", { certificate: certificate.id }), {
    preserveScroll: true,
  });
};

function loadCustomers(query, setOptions) {
  return loadSelectOptions("/customers/getCustomer", query, setOptions, optionMappers.name);
}

function loadWarehouses(query, setOptions) {
  return loadSelectOptions("/warehouses/getWarehouse", query, setOptions, optionMappers.address);
}

function loadLabCodes(query, setOptions) {
  return loadSelectOptions("/labcodes/getCode", query, setOptions, optionMappers.code);
}
</script>

<template>
  <div class="quality-certificate-edit space-y-6" :class="commercialDocumentThemeClasses">
    <section class="overflow-hidden rounded-[2rem] border border-[rgb(var(--primary-200-rgb)/0.6)] bg-[radial-gradient(circle_at_top_left,_rgba(var(--primary-500-rgb),0.16),_transparent_34%),linear-gradient(135deg,_#fffdf7,_#f8fafc)] shadow-[0_22px_70px_-36px_rgba(15,23,42,0.32)] dark:border-[rgb(var(--primary-700-rgb)/0.45)] dark:bg-[radial-gradient(circle_at_top_left,_rgba(var(--primary-400-rgb),0.2),_transparent_36%),linear-gradient(135deg,_#07110f,_#111827)]">
      <div class="flex flex-col gap-6 p-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="max-w-3xl">
          <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-[rgb(var(--primary-900-rgb))] text-white shadow-lg shadow-[rgb(var(--primary-900-rgb)/0.2)] dark:bg-[rgb(var(--primary-300-rgb))] dark:text-[#07110f]">
            <DocumentTextIcon class="h-6 w-6" />
          </div>
          <p class="mt-5 text-[11px] font-semibold uppercase tracking-[0.24em] text-[rgb(var(--primary-800-rgb))] dark:text-[rgb(var(--primary-200-rgb))]">
            Certificado final
          </p>
          <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">
            Editar certificado {{ certificate.code ? `#${certificate.code}` : "" }}
          </h1>
          <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
            Ajuste cliente, armazem, codigo laboratorial e observacoes antes da emissao ou validacao final.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <Link
            :href="certificate.links?.show_path || route('qualitycertificates.show', { certificate: certificate.id })"
            class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white/90 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-[rgb(var(--primary-700-rgb))] hover:text-[rgb(var(--primary-900-rgb))] dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-200 dark:hover:border-[rgb(var(--primary-300-rgb))] dark:hover:text-[rgb(var(--primary-100-rgb))]"
          >
            <ArrowLeftIcon class="h-4 w-4" />
            Voltar ao certificado
          </Link>
          <button
            type="button"
            :disabled="form.processing"
            @click="submit"
            class="inline-flex items-center gap-2 rounded-2xl bg-[rgb(var(--primary-900-rgb))] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[rgb(var(--primary-900-rgb)/0.2)] transition hover:bg-[rgb(var(--primary-800-rgb))] disabled:cursor-not-allowed disabled:opacity-60 dark:bg-[rgb(var(--primary-300-rgb))] dark:text-[#07110f] dark:hover:bg-[rgb(var(--primary-200-rgb))]"
          >
            <CheckBadgeIcon class="h-4 w-4" />
            {{ form.processing ? "A guardar..." : "Guardar certificado" }}
          </button>
        </div>
      </div>
    </section>

    <form @submit.prevent="submit" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
      <section class="rounded-[2rem] border border-slate-200 bg-white/95 p-6 shadow-[0_20px_60px_-34px_rgba(15,23,42,0.28)] dark:border-slate-800 dark:bg-slate-950/85">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
              Dados do certificado
            </p>
            <h2 class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">
              Contexto comercial e laboratorial
            </h2>
          </div>
          <span
            :class="[
              'rounded-full px-3 py-1 text-xs font-semibold ring-1',
              form.status
                ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20'
                : 'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20',
            ]"
          >
            {{ form.status ? "Ativo" : "Inativo" }}
          </span>
        </div>

        <div class="mt-6 grid gap-5 lg:grid-cols-2">
          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">
              {{ $t("gestlab.general.labels.quality_certificates.customer_id") }}
            </span>
            <combobox
              v-model="form.customer_id"
              :hasError="Boolean(form.errors.customer_id)"
              :load-options="loadCustomers"
              placeholder="Pesquisar cliente"
            />
            <p v-if="form.errors.customer_id" class="mt-2 text-sm text-red-600 dark:text-red-400">
              {{ form.errors.customer_id }}
            </p>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">
              {{ $t("gestlab.general.labels.quality_certificates.warehouse_id") }}
            </span>
            <combobox
              v-model="form.warehouse_id"
              :hasError="Boolean(form.errors.warehouse_id)"
              :load-options="loadWarehouses"
              placeholder="Pesquisar armazem"
            />
            <p v-if="form.errors.warehouse_id" class="mt-2 text-sm text-red-600 dark:text-red-400">
              {{ form.errors.warehouse_id }}
            </p>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">
              {{ $t("gestlab.general.labels.quality_certificates.cl_id") }}
            </span>
            <combobox
              v-model="form.cl_id"
              :hasError="Boolean(form.errors.cl_id)"
              :load-options="loadLabCodes"
              placeholder="Pesquisar codigo laboratorial"
            />
            <p v-if="form.errors.cl_id" class="mt-2 text-sm text-red-600 dark:text-red-400">
              {{ form.errors.cl_id }}
            </p>
          </label>

          <label class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/70">
            <span>
              <span class="block text-sm font-semibold text-slate-800 dark:text-slate-100">
                {{ $t("gestlab.general.labels.quality_certificates.status") }}
              </span>
              <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">
                Controla se o certificado permanece disponivel para operacoes.
              </span>
            </span>
            <input
              v-model="form.status"
              type="checkbox"
              class="h-5 w-5 rounded-md border-slate-300 text-[rgb(var(--primary-700-rgb))] focus:ring-[rgb(var(--primary-500-rgb))] dark:border-slate-700 dark:bg-slate-900"
            />
          </label>
        </div>

        <label class="mt-5 block">
          <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">
            {{ $t("gestlab.general.labels.quality_certificates.obs") }}
          </span>
          <textarea
            v-model="form.obs"
            rows="7"
            class="block w-full rounded-2xl border border-[#d8cbb8] bg-[#fffdf7] px-4 py-3 text-sm text-[#15231f] shadow-sm transition placeholder:text-[#8d9b94] focus:border-[rgb(var(--primary-500-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.18)] dark:border-[#315149] dark:bg-[#10231f] dark:text-[#f7f1e7] dark:placeholder:text-[#657970]"
            placeholder="Observacoes tecnicas ou comerciais relevantes para o certificado"
          />
          <p v-if="form.errors.obs" class="mt-2 text-sm text-red-600 dark:text-red-400">
            {{ form.errors.obs }}
          </p>
        </label>
      </section>

      <aside class="space-y-5">
        <section class="rounded-[2rem] border border-slate-200 bg-white/95 p-5 shadow-[0_20px_60px_-34px_rgba(15,23,42,0.28)] dark:border-slate-800 dark:bg-slate-950/85">
          <div class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-[rgb(var(--primary-900-rgb))] text-white dark:bg-[rgb(var(--primary-300-rgb))] dark:text-[#07110f]">
            <ShieldCheckIcon class="h-5 w-5" />
          </div>
          <h3 class="mt-4 text-base font-semibold text-slate-950 dark:text-white">
            Cadeia de emissao
          </h3>
          <dl class="mt-4 space-y-3 text-sm">
            <div class="flex items-start justify-between gap-3">
              <dt class="text-slate-500 dark:text-slate-400">Codigo</dt>
              <dd class="font-semibold text-slate-900 dark:text-white">{{ certificate.code || "-" }}</dd>
            </div>
            <div class="flex items-start justify-between gap-3">
              <dt class="text-slate-500 dark:text-slate-400">Cliente</dt>
              <dd class="text-right font-semibold text-slate-900 dark:text-white">{{ certificate.customer || "-" }}</dd>
            </div>
            <div class="flex items-start justify-between gap-3">
              <dt class="text-slate-500 dark:text-slate-400">Validado</dt>
              <dd class="text-right font-semibold text-slate-900 dark:text-white">{{ certificate.validated_at || "Pendente" }}</dd>
            </div>
          </dl>
        </section>

        <section class="rounded-[2rem] border border-amber-200 bg-amber-50/80 p-5 text-sm leading-6 text-amber-900 dark:border-amber-400/20 dark:bg-amber-500/10 dark:text-amber-100">
          Alteracoes feitas aqui afetam a emissao do documento final. Mantenha os dados alinhados com a amostra, cliente e codigo laboratorial antes de aprovar.
        </section>
      </aside>
    </form>
  </div>
</template>

<style scoped>
.quality-certificate-edit :deep(.commercial-document-theme input),
.quality-certificate-edit :deep(input:not([type='checkbox']):not([type='radio'])),
.quality-certificate-edit :deep(select) {
  color-scheme: light dark;
}
</style>
