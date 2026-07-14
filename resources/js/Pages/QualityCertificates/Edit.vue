<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import combobox from "@/Components/combobox-enhanced.vue";
import { computed } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import {
  ArrowLeftIcon,
  CheckBadgeIcon,
  ShieldCheckIcon,
} from "@heroicons/vue/24/outline";
import { loadSelectOptions, optionMappers } from "@/Utils/selectOptions";

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

function optionFromValue(value, label, fallbackLabel) {
  if (!value) {
    return null;
  }

  return {
    value,
    label: label || `${fallbackLabel} #${value}`,
  };
}

const form = useForm({
  customer_id: optionFromValue(
    certificate.customer_id,
    certificate.customer,
    "Cliente",
  ),
  warehouse_id: optionFromValue(
    certificate.warehouse_id,
    certificate.warehouse,
    "Local",
  ),
  cl_id: optionFromValue(
    certificate.cl_id,
    certificate.lab_code,
    "Registo laboratorial",
  ),
  invoice_id: null,
  status: Boolean(certificate.status),
  obs: certificate.obs ?? "",
});

const editMetrics = computed(() => [
  {
    label: "Certificado",
    value: certificate.code || "Sem código",
  },
  {
    label: "Cliente actual",
    value: form.customer_id?.label || "Não definido",
  },
  {
    label: "Estado operacional",
    value: form.status ? "Activo" : "Inactivo",
  },
]);

const releaseContext = computed(() => [
  {
    label: "Código",
    value: certificate.code || "-",
  },
  {
    label: "Cliente",
    value: form.customer_id?.label || "-",
  },
  {
    label: "Local",
    value: form.warehouse_id?.label || "-",
  },
  {
    label: "Validação",
    value: certificate.validated_at || "Pendente",
  },
]);

function submit() {
  form.put(
    route("qualitycertificates.update", { certificate: certificate.id }),
    {
      preserveScroll: true,
    },
  );
}

function loadCustomers(query, setOptions) {
  return loadSelectOptions(
    "/customers/getCustomer",
    query,
    setOptions,
    optionMappers.name,
  );
}

function loadWarehouses(query, setOptions) {
  return loadSelectOptions(
    "/warehouses/getWarehouse",
    query,
    setOptions,
    optionMappers.address,
  );
}

function loadLabCodes(query, setOptions) {
  return loadSelectOptions(
    "/labcodes/getCode",
    query,
    setOptions,
    optionMappers.code,
  );
}
</script>

<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 max-w-3xl">
          <Link
            :href="certificate.links?.show_path || route('qualitycertificates.show', { certificate: certificate.id })"
            class="ds-table-action -ml-2 mb-3"
          >
            <ArrowLeftIcon class="h-4 w-4" />
            Voltar ao certificado
          </Link>
          <p class="ds-kicker">Edição controlada</p>
          <h1 class="ds-heading mt-2 break-words text-2xl">
            Editar certificado {{ certificate.code ? `#${certificate.code}` : "" }}
          </h1>
          <p class="ds-copy mt-2 max-w-2xl text-sm"> Actualize apenas os dados de contexto antes da assinatura final. As alterações ficam ligadas ao dossier de emissão. </p>
        </div>

        <button
          type="button"
          class="ds-button ds-button-primary"
          :disabled="form.processing"
          @click="submit"
        >
          <CheckBadgeIcon class="h-4 w-4" />
          {{ form.processing ? "A guardar..." : "Guardar certificado" }}
        </button>
      </div>

      <dl class="grid border-t border-[var(--ds-border)] sm:grid-cols-3 sm:divide-x sm:divide-[var(--ds-border)]">
        <div
          v-for="metric in editMetrics"
          :key="metric.label"
          class="border-b border-[var(--ds-border)] px-5 py-4 last:border-b-0 sm:border-b-0"
        >
          <dt class="ds-table-heading">{{ metric.label }}</dt>
          <dd class="ds-heading mt-2 truncate text-sm" :title="metric.value">
            {{ metric.value }}
          </dd>
        </div>
      </dl>
    </section>

    <form
      class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem] xl:items-start"
      @submit.prevent="submit"
    >
      <section class="ds-panel overflow-hidden">
        <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
          <p class="ds-kicker">Dados do certificado</p>
          <h2 class="ds-heading mt-2 text-lg">Contexto comercial e laboratorial</h2>
          <p class="ds-copy mt-1 text-sm"> Mantenha o destinatário e a referência laboratorial alinhados com a amostra aprovada. </p>
        </div>

        <div class="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-2">
          <div class="ds-field-group">
            <label class="ds-field-label">
              {{ $t("gestlab.general.labels.quality_certificates.customer_id") }}
            </label>
            <combobox
              v-model="form.customer_id"
              :has-error="Boolean(form.errors.customer_id)"
              :load-options="loadCustomers"
              placeholder="Pesquisar cliente"
            />
            <p v-if="form.errors.customer_id" class="ds-field-error">
              {{ form.errors.customer_id }}
            </p>
          </div>

          <div class="ds-field-group">
            <label class="ds-field-label">
              {{ $t("gestlab.general.labels.quality_certificates.warehouse_id") }}
            </label>
            <combobox
              v-model="form.warehouse_id"
              :has-error="Boolean(form.errors.warehouse_id)"
              :load-options="loadWarehouses"
              placeholder="Pesquisar armazem"
            />
            <p v-if="form.errors.warehouse_id" class="ds-field-error">
              {{ form.errors.warehouse_id }}
            </p>
          </div>

          <div class="ds-field-group">
            <label class="ds-field-label">
              {{ $t("gestlab.general.labels.quality_certificates.cl_id") }}
            </label>
            <combobox
              v-model="form.cl_id"
              :has-error="Boolean(form.errors.cl_id)"
              :load-options="loadLabCodes"
              placeholder="Pesquisar código laboratorial"
            />
            <p v-if="form.errors.cl_id" class="ds-field-error">
              {{ form.errors.cl_id }}
            </p>
          </div>

          <div class="ds-command-toolbar flex items-center justify-between gap-4 p-4">
            <div>
              <p class="ds-field-label">
                {{ $t("gestlab.general.labels.quality_certificates.status") }}
              </p>
              <p class="ds-field-hint mt-1">
                Controla a disponibilidade operacional do registo.
              </p>
            </div>
            <button
              type="button"
              role="switch"
              :aria-checked="form.status"
              :aria-label="form.status ? 'Desactivar certificado' : 'Activar certificado'"
              :class="[
                'relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[var(--ds-focus)]',
                form.status
                  ? 'bg-[rgb(var(--primary-700-rgb))] dark:bg-[rgb(var(--primary-300-rgb))]'
                  : 'bg-[var(--ds-border-strong)]',
              ]"
              @click="form.status = !form.status"
            >
              <span
                :class="[
                  'h-4 w-4 rounded-full bg-[var(--ds-panel-raised)] transition-transform',
                  form.status ? 'translate-x-6' : 'translate-x-1',
                ]"
              />
            </button>
          </div>
        </div>

        <div class="ds-field-group border-t border-[var(--ds-border)] px-5 py-5 sm:px-6">
          <label class="ds-field-label" for="certificate-observations">
            {{ $t("gestlab.general.labels.quality_certificates.obs") }}
          </label>
          <textarea
            id="certificate-observations"
            v-model="form.obs"
            rows="8"
            class="ds-field"
            :aria-invalid="Boolean(form.errors.obs)"
            placeholder="Observações técnicas ou comerciais relevantes para o certificado"
          />
          <p class="ds-field-hint"> Registe apenas informação que deva acompanhar a cadeia documental. </p>
          <p v-if="form.errors.obs" class="ds-field-error">
            {{ form.errors.obs }}
          </p>
        </div>

        <div class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
          <Link
            :href="certificate.links?.show_path || route('qualitycertificates.show', { certificate: certificate.id })"
            class="ds-button ds-button-secondary"
          >
            Cancelar
          </Link>
          <button
            type="submit"
            class="ds-button ds-button-primary"
            :disabled="form.processing || !form.isDirty"
          >
            <CheckBadgeIcon class="h-4 w-4" />
            {{ form.processing ? "A guardar..." : "Guardar alterações" }}
          </button>
        </div>
      </section>

      <aside class="space-y-6">
        <section class="ds-command-surface overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <div class="grid h-10 w-10 place-items-center rounded-lg bg-[var(--ds-panel-raised)] text-[var(--ds-text-muted)]">
              <ShieldCheckIcon class="h-5 w-5" />
            </div>
            <p class="ds-kicker mt-4">Cadeia de emissão</p>
            <h2 class="ds-heading mt-2 text-base">Contexto actual</h2>
          </div>
          <dl class="divide-y divide-[var(--ds-border)]">
            <div
              v-for="item in releaseContext"
              :key="item.label"
              class="flex items-start justify-between gap-4 px-5 py-3"
            >
              <dt class="text-xs font-bold text-[var(--ds-text-muted)]">
                {{ item.label }}
              </dt>
              <dd class="max-w-[12rem] break-words text-right text-xs font-bold text-[var(--ds-text)]">
                {{ item.value }}
              </dd>
            </div>
          </dl>
        </section>

        <section class="lims-status-strip p-5">
          <div class="flex items-start gap-3">
            <span class="lims-status-dot lims-status-dot-hold mt-1" />
            <div>
              <h2 class="ds-heading text-sm">Revisão obrigatória</h2>
              <p class="ds-copy mt-1 text-xs"> Alterações afetam o documento final. Confirme cliente, local e código laboratorial antes de solicitar validação. </p>
            </div>
          </div>
        </section>
      </aside>
    </form>
  </div>
</template>
