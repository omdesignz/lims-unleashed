<script setup>
import ComboboxEnhanced from "@/Components/combobox-enhanced.vue";
import ComboboxMultipleEnhanced from "@/Components/combobox-multiple-enhanced.vue";
import { loadSelectOptions, optionMappers } from "@/Utils/selectOptions";
import { Link, useForm } from "@inertiajs/vue3";
import {
  ArrowLeft as ArrowLeftIcon,
  FlaskConical as BeakerIcon,
  CalendarDays as CalendarDaysIcon,
  BadgeCheck as CheckBadgeIcon,
  ClipboardCheck as ClipboardDocumentCheckIcon,
  Box as CubeIcon,
  MapPin as MapPinIcon,
  ShieldCheck as ShieldCheckIcon,
  Truck as TruckIcon,
  Users as UserGroupIcon,
} from "@lucide/vue";
import { computed } from "vue";

const props = defineProps({
  kind: { type: String, required: true, validator: (value) => ["direct", "programmed"].includes(value) },
  record: { type: Object, required: true },
  ownerOptions: { type: Array, default: () => [] },
});

const source = props.record;
const isScheduled = computed(() => props.kind === "programmed");
const config = computed(() => isScheduled.value
  ? {
      title: "Colheita programada",
      kicker: "Planeamento e cadeia de custódia",
      routePrefix: "programmedcollections",
      icon: CalendarDaysIcon,
    }
  : {
      title: "Colheita directa",
      kicker: "Recepção e cadeia de custódia",
      routePrefix: "directcollections",
      icon: ClipboardDocumentCheckIcon,
    });

function defaultAccessionFields() {
  return {
    product_id: null,
    temperature_id: null,
    collection_id: null,
    pack_id: null,
    result_id: null,
    owner_id: null,
    vehicle_id: null,
    comercial_brand: "",
    du_no: "",
    temperature_value: "",
    term_no: "",
    container_no: "",
    recollection: false,
    obs: "",
    sample_status: "",
    sampling_plan_ref: "",
    customer_submitted_info: "",
    collected_by_lab: false,
    expiry_date: "",
    production_date: "",
    collection_date: "",
    qty: "",
    origin: "",
    location: "",
    collected_qty: "",
    lot: "",
    bl: "",
  };
}

const form = useForm({
  ...defaultAccessionFields(),
  ...source,
  collaborations: source.collaborations || [],
  collectionreasons: source.collectionreasons || [],
  collection_location: source.collection_location || "",
  vehicle_reference: source.vehicle_reference || "",
});

const selectedCustomer = computed(() => form.customer_id?.label || "Cliente não seleccionado");
const totalRequestedQuantity = computed(() => Number.parseFloat(form.qty) || 0);

function loadVehicles(query, setOptions) {
  return loadSelectOptions("/vehicles/getVehicle", query, setOptions, optionMappers.numberPlate);
}

function loadPackagingCategories(query, setOptions) {
  return loadSelectOptions("/packagingcategories/getPackagingCategory", query, setOptions, optionMappers.name);
}

function loadEndResults(query, setOptions) {
  return loadSelectOptions("/collectionendresults/getCollectionEndResult", query, setOptions, optionMappers.name);
}

function loadCollectionCollaborations(query, setOptions) {
  return loadSelectOptions("/collectioncollaborations/getCollectionCollaboration", query, setOptions, optionMappers.name);
}

function loadCollectionReasons(query, setOptions) {
  return loadSelectOptions("/collectionreasons/getCollectionReason", query, setOptions, optionMappers.name);
}

function loadTemperatures(query, setOptions) {
  return loadSelectOptions("/temperatures/getTemperature", query, setOptions, optionMappers.name);
}

function loadUsers(query, setOptions) {
  const search = String(query || "").toLocaleLowerCase("pt-PT");
  setOptions(props.ownerOptions.filter((option) => option.label.toLocaleLowerCase("pt-PT").includes(search)));
}

function fieldError(field) {
  return form.errors[field];
}

function selectionError(field) {
  return form.errors[field] || Object.entries(form.errors).find(([key]) => key.startsWith(`${field}.`))?.[1];
}

function formatNumber(value) {
  return new Intl.NumberFormat("pt-PT", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number.parseFloat(value) || 0);
}

function submit() {
  if (form.processing || !form.isDirty) {
    return;
  }

  form.put(route(`${config.value.routePrefix}.update`, { collection: form.id }), {
    preserveScroll: true,
    preserveState: "errors",
  });
}
</script>

<template>
  <form class="min-w-0 space-y-6 overflow-x-clip" @submit.prevent="submit">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <Link :href="route(`${config.routePrefix}.index`)" class="ds-button ds-button-ghost px-0">
        <ArrowLeftIcon class="h-4 w-4" />
        Voltar à fila
      </Link>

      <div class="mt-4 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex min-w-0 items-start gap-3">
          <span class="grid h-11 w-11 shrink place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <component :is="config.icon" class="h-5 w-5" />
          </span>
          <div class="min-w-0">
            <p class="ds-kicker">{{ config.kicker }}</p>
            <div class="mt-1 flex flex-wrap items-center gap-2">
              <h1 class="ds-heading text-2xl">Editar {{ config.title.toLowerCase() }}</h1>
              <span v-if="source.code" class="ds-badge ds-badge-info font-mono">{{ source.code }}</span>
            </div>
            <p class="ds-copy mt-1 max-w-3xl text-sm">{{ selectedCustomer }}</p>
          </div>
        </div>

        <div class="flex flex-wrap gap-2">
          <span class="ds-badge" :class="form.isDirty ? 'ds-badge-warning' : 'ds-badge-neutral'">
            {{ form.isDirty ? "Alterações por guardar" : "Sem alterações" }}
          </span>
        </div>
      </div>

      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-3">
        <div class="border-b border-[var(--ds-border)] px-4 py-3 sm:border-b sm:border-r">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Modo</dt>
          <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">Revisão de amostra</dd>
        </div>
        <div class="border-b border-[var(--ds-border)] px-4 py-3 sm:border-b sm:border-r">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Amostra</dt>
          <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">1 registo</dd>
        </div>
        <div class="px-4 py-3">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Quantidade solicitada</dt>
          <dd class="mt-2 text-sm font-bold tabular-nums text-[var(--ds-text)]">{{ formatNumber(totalRequestedQuantity) }}</dd>
        </div>
      </dl>
    </section>

    <section class="ds-panel overflow-hidden">
      <header class="border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:px-6">
        <div class="flex items-center gap-3">
          <MapPinIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          <div>
            <h2 class="text-sm font-bold text-[var(--ds-text)]">Contexto da colheita</h2>
            <p class="ds-copy mt-1 text-xs">Data e equipa responsável. Cliente, instalação e produto vêm da entrada de amostra.</p>
          </div>
        </div>
      </header>
      <div class="grid gap-4 px-5 py-5 sm:grid-cols-2 sm:px-6 xl:grid-cols-4">
        <div class="ds-field-group">
          <label for="collection-date" class="ds-field-label">Data da colheita</label>
          <DateTimePicker id="collection-date" v-model="form.collection_date" type="date" class="ds-field" :aria-invalid="Boolean(form.errors.collection_date)" />
          <p v-if="form.errors.collection_date" class="ds-field-error">{{ form.errors.collection_date }}</p>
        </div>
        <div class="ds-field-group">
          <label for="collection-customer" class="ds-field-label">Cliente</label>
          <BaseInput id="collection-customer" :model-value="form.customer_id?.label" readonly class="ds-field" />
          <p v-if="form.errors.customer_id" class="ds-field-error">{{ form.errors.customer_id }}</p>
        </div>
        <div class="ds-field-group">
          <label for="collection-site" class="ds-field-label">Instalação</label>
          <BaseInput id="collection-site" :model-value="form.warehouse_id?.label" readonly class="ds-field" />
          <p v-if="form.errors.warehouse_id" class="ds-field-error">{{ form.errors.warehouse_id }}</p>
        </div>
        <div v-if="isScheduled" class="ds-field-group">
          <label for="collection-location" class="ds-field-label">Local da colheita</label>
          <BaseInput id="collection-location" v-model="form.collection_location" type="text" class="ds-field" :aria-invalid="Boolean(form.errors.collection_location)" />
          <p v-if="form.errors.collection_location" class="ds-field-error">{{ form.errors.collection_location }}</p>
        </div>
        <div class="ds-field-group sm:col-span-2">
          <label class="ds-field-label">Colaboradores</label>
          <ComboboxMultipleEnhanced v-model="form.collaborations" :load-options="loadCollectionCollaborations" multiple />
          <p v-if="selectionError('collaborations')" class="ds-field-error">{{ selectionError("collaborations") }}</p>
        </div>
        <div class="ds-field-group sm:col-span-2">
          <label class="ds-field-label">Motivos da colheita</label>
          <ComboboxMultipleEnhanced v-model="form.collectionreasons" :load-options="loadCollectionReasons" multiple />
          <p v-if="selectionError('collectionreasons')" class="ds-field-error">{{ selectionError("collectionreasons") }}</p>
        </div>
      </div>
    </section>

    <section class="ds-panel min-w-0 overflow-hidden">
      <header class="flex flex-col gap-3 border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div class="flex items-center gap-3">
          <CubeIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          <div>
            <h2 class="text-sm font-bold text-[var(--ds-text)]">Amostras e cadeia de custódia</h2>
            <p class="ds-copy mt-1 text-xs">Conservação, transporte e rastreabilidade. <Link :href="source.sample_entry_url" class="underline underline-offset-2">Consultar entrada de amostra</Link></p>
          </div>
        </div>
      </header>

      <div class="divide-y divide-[var(--ds-border)]">
        <details class="group" open>
          <summary class="flex cursor-pointer list-none items-center justify-between gap-4 bg-[var(--ds-panel-raised)] px-5 py-4 sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
              <span class="grid h-9 w-9 shrink place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[var(--ds-text-soft)]">
                <BeakerIcon class="h-4 w-4" />
              </span>
              <div class="min-w-0">
                <h3 class="truncate text-sm font-bold text-[var(--ds-text)]">{{ form.product_id?.label || "Produto não identificado" }}</h3>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ form.lot ? `Lote ${form.lot}` : "Lote não registado" }} · {{ formatNumber(form.qty) }} un.</p>
              </div>
            </div>
          </summary>

          <div class="space-y-6 border-t border-[var(--ds-border)] px-5 py-5 sm:px-6">
            <div>
              <div class="flex items-center gap-2">
                <BeakerIcon class="h-4 w-4 text-[var(--ds-text-soft)]" />
                <h4 class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Identificação da amostra</h4>
              </div>
              <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="ds-field-group sm:col-span-2">
                  <label for="sample-product" class="ds-field-label">Produto</label>
                  <BaseInput id="sample-product" :model-value="form.product_id?.label" readonly class="ds-field" />
                  <p v-if="fieldError('product_id')" class="ds-field-error">{{ fieldError("product_id") }}</p>
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label">Resultado final da colheita</label>
                  <ComboboxEnhanced v-model="form.result_id" :has-error="fieldError('result_id')" :load-options="loadEndResults" placeholder="Seleccionar resultado" />
                  <p v-if="fieldError('result_id')" class="ds-field-error">{{ fieldError("result_id") }}</p>
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label">Embalagem</label>
                  <ComboboxEnhanced v-model="form.pack_id" :has-error="fieldError('pack_id')" :load-options="loadPackagingCategories" placeholder="Seleccionar embalagem" />
                  <p v-if="fieldError('pack_id')" class="ds-field-error">{{ fieldError("pack_id") }}</p>
                </div>
                <div class="ds-field-group">
                  <label for="sample-origin" class="ds-field-label">Origem</label>
                  <BaseInput id="sample-origin" v-model="form.origin" type="text" class="ds-field" :aria-invalid="Boolean(fieldError('origin'))" />
                  <p v-if="fieldError('origin')" class="ds-field-error">{{ fieldError("origin") }}</p>
                </div>
                <div class="ds-field-group">
                  <label for="sample-brand" class="ds-field-label">Marca comercial</label>
                  <BaseInput id="sample-brand" v-model="form.comercial_brand" type="text" class="ds-field" :aria-invalid="Boolean(fieldError('comercial_brand'))" />
                </div>
                <div class="ds-field-group">
                  <label for="sample-qty" class="ds-field-label">Quantidade solicitada</label>
                  <BaseInput id="sample-qty" v-model="form.qty" type="number" min="0" step="0.01" class="ds-field text-right tabular-nums" :aria-invalid="Boolean(fieldError('qty'))" />
                  <p v-if="fieldError('qty')" class="ds-field-error">{{ fieldError("qty") }}</p>
                </div>
                <div class="ds-field-group">
                  <label for="sample-collected-qty" class="ds-field-label">Quantidade colhida</label>
                  <BaseInput id="sample-collected-qty" v-model="form.collected_qty" type="number" min="0" step="0.01" class="ds-field text-right tabular-nums" :aria-invalid="Boolean(fieldError('collected_qty'))" />
                  <p v-if="fieldError('collected_qty')" class="ds-field-error">{{ fieldError("collected_qty") }}</p>
                </div>
                <div class="ds-field-group">
                  <label for="sample-production-date" class="ds-field-label">Produção</label>
                  <DateTimePicker id="sample-production-date" v-model="form.production_date" type="date" class="ds-field" :aria-invalid="Boolean(fieldError('production_date'))" />
                </div>
                <div class="ds-field-group">
                  <label for="sample-expiry-date" class="ds-field-label">Validade</label>
                  <DateTimePicker id="sample-expiry-date" v-model="form.expiry_date" type="date" class="ds-field" :aria-invalid="Boolean(fieldError('expiry_date'))" />
                </div>
                <div class="ds-field-group sm:col-span-2">
                  <label for="sample-location" class="ds-field-label">Localização / ponto de colheita</label>
                  <BaseInput id="sample-location" v-model="form.location" type="text" class="ds-field" :aria-invalid="Boolean(fieldError('location'))" />
                </div>
              </div>
            </div>

            <div class="border-t border-[var(--ds-border)] pt-6">
              <div class="flex items-center gap-2">
                <TruckIcon class="h-4 w-4 text-[var(--ds-text-soft)]" />
                <h4 class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Conservação e cadeia de custódia</h4>
              </div>
              <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="ds-field-group">
                  <label class="ds-field-label">Viatura</label>
                  <ComboboxEnhanced v-model="form.vehicle_id" :has-error="fieldError('vehicle_id')" :load-options="loadVehicles" placeholder="Seleccionar viatura" />
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label">Condição de temperatura</label>
                  <ComboboxEnhanced v-model="form.temperature_id" :has-error="fieldError('temperature_id')" :load-options="loadTemperatures" placeholder="Seleccionar condição" />
                </div>
                <div class="ds-field-group">
                  <label for="sample-temperature" class="ds-field-label">Temperatura observada</label>
                  <BaseInput id="sample-temperature" v-model="form.temperature_value" type="text" class="ds-field" :aria-invalid="Boolean(fieldError('temperature_value'))" />
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label">Responsável</label>
                  <ComboboxEnhanced v-model="form.owner_id" :has-error="fieldError('owner_id')" :load-options="loadUsers" placeholder="Seleccionar responsável" />
                </div>
                <label class="sm:col-span-2 xl:col-span-4 flex items-start gap-3 border-t border-[var(--ds-border)] pt-4 text-sm font-semibold text-[var(--ds-text-muted)]">
                  <CheckboxInput v-model="form.collected_by_lab" type="checkbox" class="ds-checkbox mt-0.5" />
                  Colhida pela equipa do laboratório
                </label>
              </div>
            </div>

            <div class="border-t border-[var(--ds-border)] pt-6">
              <div class="flex items-center gap-2">
                <ShieldCheckIcon class="h-4 w-4 text-[var(--ds-text-soft)]" />
                <h4 class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Rastreabilidade do lote</h4>
              </div>
              <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <div v-for="field in [
                  { key: 'lot', label: 'Lote' },
                  { key: 'bl', label: 'BL' },
                  { key: 'du_no', label: 'DU' },
                  { key: 'term_no', label: 'Termo' },
                  { key: 'container_no', label: 'Contentor' },
                ]" :key="field.key" class="ds-field-group">
                  <label :for="`sample-${field.key}`" class="ds-field-label">{{ field.label }}</label>
                  <BaseInput :id="`sample-${field.key}`" v-model="form[field.key]" type="text" class="ds-field" :aria-invalid="Boolean(fieldError(field.key))" />
                  <p v-if="fieldError(field.key)" class="ds-field-error">{{ fieldError(field.key) }}</p>
                </div>
              </div>
            </div>

            <div class="border-t border-[var(--ds-border)] pt-6">
              <div class="flex items-center gap-2">
                <UserGroupIcon class="h-4 w-4 text-[var(--ds-text-soft)]" />
                <h4 class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Contexto de qualidade</h4>
              </div>
              <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div class="ds-field-group">
                  <label for="sample-status" class="ds-field-label">Estado da amostra</label>
                  <BaseInput id="sample-status" v-model="form.sample_status" type="text" class="ds-field" :aria-invalid="Boolean(fieldError('sample_status'))" />
                </div>
                <div class="ds-field-group">
                  <label for="sample-plan" class="ds-field-label">Plano de amostragem</label>
                  <BaseInput id="sample-plan" v-model="form.sampling_plan_ref" type="text" class="ds-field" :aria-invalid="Boolean(fieldError('sampling_plan_ref'))" />
                </div>
                <div class="ds-field-group">
                  <label for="sample-customer-info" class="ds-field-label">Informação do cliente</label>
                  <BaseInput id="sample-customer-info" v-model="form.customer_submitted_info" type="text" class="ds-field" :aria-invalid="Boolean(fieldError('customer_submitted_info'))" />
                </div>
                <div class="ds-field-group sm:col-span-2 xl:col-span-3">
                  <label for="sample-notes" class="ds-field-label">Observações</label>
                  <textarea id="sample-notes" v-model="form.obs" rows="3" class="ds-field resize-y" :aria-invalid="Boolean(fieldError('obs'))"></textarea>
                </div>
              </div>
            </div>
          </div>
        </details>
      </div>
    </section>

    <section class="ds-panel flex flex-col-reverse gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
      <p class="text-xs font-semibold text-[var(--ds-text-muted)]">1 amostra · {{ formatNumber(totalRequestedQuantity) }} unidades solicitadas</p>
      <div class="flex flex-col-reverse gap-2 sm:flex-row">
        <Link :href="route(`${config.routePrefix}.index`)" class="ds-button ds-button-secondary">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
          <CheckBadgeIcon class="h-4 w-4" />
          {{ form.processing ? "A guardar..." : "Guardar alterações" }}
        </button>
      </div>
    </section>
  </form>
</template>
