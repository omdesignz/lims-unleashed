<script setup>
import ComboboxEnhanced from "@/Components/combobox-enhanced.vue";
import {
  BuildingOffice2Icon,
  CheckCircleIcon,
  ClipboardDocumentListIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  GlobeAltIcon,
  PlusIcon,
  ShieldCheckIcon,
  TrashIcon,
  TruckIcon,
} from "@heroicons/vue/24/outline";
import { computed, watch } from "vue";

const props = defineProps({
  form: { type: Object, required: true },
  submitLabel: { type: String, default: "Guardar guia" },
  guideNumber: { type: String, default: "" },
});

const completedItems = computed(() => props.form.items.filter((item) => (
  item.product_id?.value
  && item.country_id?.value
  && item.manufacturer?.trim()
  && item.brand?.trim()
)));
const isReady = computed(() => (
  props.form.customer_id?.value
  && props.form.warehouse_id?.value
  && props.form.ref_no?.trim()
  && props.form.items.length > 0
  && completedItems.value.length === props.form.items.length
));
const statusLabel = computed(() => {
  if (isReady.value) {
    return "Pronta para registo";
  }

  if (props.form.customer_id?.value || props.form.items.some((item) => item.product_id?.value)) {
    return "Em preparação";
  }

  return "Dados em falta";
});
const statusClass = computed(() => {
  if (isReady.value) {
    return "ds-badge ds-badge-success";
  }

  if (props.form.customer_id?.value || props.form.items.some((item) => item.product_id?.value)) {
    return "ds-badge ds-badge-warning";
  }

  return "ds-badge ds-badge-danger";
});

watch(
  () => props.form.customer_id?.value,
  (customerId, previousCustomerId) => {
    if (!customerId || (previousCustomerId && customerId !== previousCustomerId)) {
      props.form.warehouse_id = null;
    }
  },
);

function emptyItem() {
  return {
    id: null,
    guide_id: null,
    product_id: null,
    country_id: null,
    manufacturer: "",
    brand: "",
    lot: "",
    bl: "",
    du_no: "",
    collection_id: null,
    date: null,
    obs: "",
  };
}

function addItem() {
  props.form.items.push(emptyItem());
}

function removeItem(index) {
  if (props.form.items.length === 1) {
    props.form.items.splice(0, 1, emptyItem());
    return;
  }

  props.form.items.splice(index, 1);
}

function loadRemoteOptions(path, query, setOptions, labelKey = "name") {
  return fetch(path + "?q=" + encodeURIComponent(query || ""))
    .then((response) => response.json())
    .then((results) => {
      setOptions(results.map((result) => ({
        value: result.id,
        label: result[labelKey],
      })));
    })
    .catch(() => setOptions([]));
}

function loadCustomers(query, setOptions) {
  return loadRemoteOptions("/customers/getCustomer", query, setOptions);
}

function loadCountries(query, setOptions) {
  return loadRemoteOptions("/countries/getCountry", query, setOptions);
}

function loadProducts(query, setOptions) {
  return loadRemoteOptions("/products/getProduct", query, setOptions);
}

function loadWarehouses(query, setOptions) {
  if (!props.form.customer_id?.value) {
    setOptions([]);
    return Promise.resolve();
  }

  return fetch("/warehouses/getWarehouse?q=" + encodeURIComponent(query || "") + "&customer_id=" + props.form.customer_id.value)
    .then((response) => response.json())
    .then((results) => {
      setOptions(results.map((result) => ({
        value: result.id,
        label: result.address,
      })));
    })
    .catch(() => setOptions([]));
}
</script>

<template>
  <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_19rem] xl:items-start">
    <div class="space-y-6">
      <section class="ds-panel p-5 sm:p-6">
        <div class="flex items-start gap-3 border-b border-[var(--ds-border)] pb-4">
          <BuildingOffice2Icon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          <div>
            <p class="ds-kicker">Destino e referência</p>
            <h2 class="ds-heading mt-2 text-lg">Identificação da guia</h2>
            <p class="ds-copy mt-1 text-sm">Associe a guia à conta, local e referência documental corretos.</p>
          </div>
        </div>

        <div class="mt-6 grid gap-5 md:grid-cols-2">
          <div>
            <label class="ds-field-label">Cliente <span class="text-red-600">*</span></label>
            <ComboboxEnhanced
              v-model="form.customer_id"
              class="mt-2"
              :has-error="Boolean(form.errors.customer_id)"
              :load-options="loadCustomers"
              placeholder="Pesquisar cliente"
            />
            <p v-if="form.errors.customer_id" class="ds-field-error mt-2">{{ form.errors.customer_id }}</p>
          </div>

          <div>
            <label class="ds-field-label">Local do cliente <span class="text-red-600">*</span></label>
            <ComboboxEnhanced
              v-model="form.warehouse_id"
              class="mt-2"
              :disable-input="!form.customer_id"
              :has-error="Boolean(form.errors.warehouse_id)"
              :load-options="loadWarehouses"
              :placeholder="form.customer_id ? 'Pesquisar local' : 'Selecione primeiro o cliente'"
            />
            <p v-if="form.errors.warehouse_id" class="ds-field-error mt-2">{{ form.errors.warehouse_id }}</p>
          </div>

          <div>
            <label for="contract-guide-reference" class="ds-field-label">Referência externa <span class="text-red-600">*</span></label>
            <BaseInput id="contract-guide-reference" v-model="form.ref_no" type="text" class="ds-field mt-2" placeholder="Referência do cliente ou despacho" />
            <p v-if="form.errors.ref_no" class="ds-field-error mt-2">{{ form.errors.ref_no }}</p>
          </div>

          <div>
            <label for="contract-guide-date" class="ds-field-label">Data documental</label>
            <DateTimePicker id="contract-guide-date" v-model="form.date" type="date" class="ds-field mt-2" />
            <p v-if="form.errors.date" class="ds-field-error mt-2">{{ form.errors.date }}</p>
          </div>

          <div v-if="guideNumber" class="md:col-span-2">
            <p class="ds-field-label">Número controlado</p>
            <p class="mt-2 font-mono text-sm font-bold text-[var(--ds-text)]">{{ guideNumber }}</p>
          </div>
        </div>
      </section>

      <section class="ds-panel p-5 sm:p-6">
        <div class="flex items-start gap-3 border-b border-[var(--ds-border)] pb-4">
          <TruckIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          <div>
            <p class="ds-kicker">Transporte e contacto</p>
            <h2 class="ds-heading mt-2 text-lg">Dados de circulação</h2>
            <p class="ds-copy mt-1 text-sm">Preserve os identificadores usados no transporte, desalfandegamento e comunicação.</p>
          </div>
        </div>

        <div class="mt-6 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
          <div>
            <label for="contract-guide-collection-point" class="ds-field-label">Ponto de recolha</label>
            <BaseInput id="contract-guide-collection-point" v-model="form.collection_point" type="text" class="ds-field mt-2" />
            <p v-if="form.errors.collection_point" class="ds-field-error mt-2">{{ form.errors.collection_point }}</p>
          </div>
          <div class="lg:col-span-2">
            <label for="contract-guide-entry-point" class="ds-field-label">Ponto de entrada</label>
            <BaseInput id="contract-guide-entry-point" v-model="form.entry_point" type="text" class="ds-field mt-2" />
            <p v-if="form.errors.entry_point" class="ds-field-error mt-2">{{ form.errors.entry_point }}</p>
          </div>
          <div>
            <label for="contract-guide-du" class="ds-field-label">Declaração única</label>
            <BaseInput id="contract-guide-du" v-model="form.du_no" type="text" class="ds-field mt-2" />
            <p v-if="form.errors.du_no" class="ds-field-error mt-2">{{ form.errors.du_no }}</p>
          </div>
          <div>
            <label for="contract-guide-bl" class="ds-field-label">Conhecimento de embarque</label>
            <BaseInput id="contract-guide-bl" v-model="form.bl" type="text" class="ds-field mt-2" />
            <p v-if="form.errors.bl" class="ds-field-error mt-2">{{ form.errors.bl }}</p>
          </div>
          <div>
            <label for="contract-guide-nif" class="ds-field-label">NIF</label>
            <BaseInput id="contract-guide-nif" v-model="form.nif" type="text" class="ds-field mt-2" />
            <p v-if="form.errors.nif" class="ds-field-error mt-2">{{ form.errors.nif }}</p>
          </div>
          <div>
            <label for="contract-guide-contact" class="ds-field-label">Contacto</label>
            <BaseInput id="contract-guide-contact" v-model="form.contact" type="text" class="ds-field mt-2" />
            <p v-if="form.errors.contact" class="ds-field-error mt-2">{{ form.errors.contact }}</p>
          </div>
          <div class="md:col-span-2">
            <label for="contract-guide-email" class="ds-field-label">Email</label>
            <BaseInput id="contract-guide-email" v-model="form.email" type="email" class="ds-field mt-2" />
            <p v-if="form.errors.email" class="ds-field-error mt-2">{{ form.errors.email }}</p>
          </div>
        </div>
      </section>

      <section class="ds-table-shell">
        <div class="ds-table-summary px-5 py-4 sm:px-6">
          <div>
            <p class="ds-kicker">Conteúdo controlado</p>
            <h2 class="ds-heading mt-2 text-lg">Produtos transportados</h2>
            <p class="ds-copy mt-1 text-sm">Registe origem, fabricante, marca, lote e referências de cada produto.</p>
          </div>
          <button type="button" class="ds-button ds-button-secondary" @click="addItem">
            <PlusIcon class="h-4 w-4" />
            Adicionar produto
          </button>
        </div>

        <div class="divide-y divide-[var(--ds-border)]">
          <article v-for="(item, index) in form.items" :key="item.id || index" class="p-5 sm:p-6">
            <div class="flex items-start justify-between gap-4">
              <div class="flex items-start gap-3">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] font-mono text-xs font-bold text-[var(--ds-text)]">{{ index + 1 }}</span>
                <div>
                  <h3 class="text-sm font-bold text-[var(--ds-text)]">{{ item.product_id?.label || "Produto por identificar" }}</h3>
                  <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">Linha de rastreabilidade {{ index + 1 }}</p>
                </div>
              </div>
              <button type="button" class="ds-icon-button hover:!text-red-600" title="Remover produto" @click="removeItem(index)">
                <TrashIcon class="h-4 w-4" />
              </button>
            </div>

            <div class="mt-5 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
              <div>
                <label class="ds-field-label">Produto <span class="text-red-600">*</span></label>
                <ComboboxEnhanced
                  v-model="item.product_id"
                  class="mt-2"
                  :has-error="Boolean(form.errors['items.' + index + '.product_id'])"
                  :load-options="loadProducts"
                  placeholder="Pesquisar produto"
                />
                <p v-if="form.errors['items.' + index + '.product_id']" class="ds-field-error mt-2">{{ form.errors["items." + index + ".product_id"] }}</p>
              </div>
              <div>
                <label class="ds-field-label">País de origem <span class="text-red-600">*</span></label>
                <ComboboxEnhanced
                  v-model="item.country_id"
                  class="mt-2"
                  :has-error="Boolean(form.errors['items.' + index + '.country_id'])"
                  :load-options="loadCountries"
                  placeholder="Pesquisar país"
                />
                <p v-if="form.errors['items.' + index + '.country_id']" class="ds-field-error mt-2">{{ form.errors["items." + index + ".country_id"] }}</p>
              </div>
              <div>
                <label :for="'contract-guide-manufacturer-' + index" class="ds-field-label">Fabricante <span class="text-red-600">*</span></label>
                <BaseInput :id="'contract-guide-manufacturer-' + index" v-model="item.manufacturer" type="text" class="ds-field mt-2" />
                <p v-if="form.errors['items.' + index + '.manufacturer']" class="ds-field-error mt-2">{{ form.errors["items." + index + ".manufacturer"] }}</p>
              </div>
              <div>
                <label :for="'contract-guide-brand-' + index" class="ds-field-label">Marca <span class="text-red-600">*</span></label>
                <BaseInput :id="'contract-guide-brand-' + index" v-model="item.brand" type="text" class="ds-field mt-2" />
                <p v-if="form.errors['items.' + index + '.brand']" class="ds-field-error mt-2">{{ form.errors["items." + index + ".brand"] }}</p>
              </div>
              <div>
                <label :for="'contract-guide-lot-' + index" class="ds-field-label">Lote</label>
                <BaseInput :id="'contract-guide-lot-' + index" v-model="item.lot" type="text" class="ds-field mt-2" />
              </div>
              <div>
                <label :for="'contract-guide-item-date-' + index" class="ds-field-label">Data do produto</label>
                <DateTimePicker :id="'contract-guide-item-date-' + index" v-model="item.date" type="date" class="ds-field mt-2" />
                <p v-if="form.errors['items.' + index + '.date']" class="ds-field-error mt-2">{{ form.errors["items." + index + ".date"] }}</p>
              </div>
              <div>
                <label :for="'contract-guide-item-bl-' + index" class="ds-field-label">Conhecimento de embarque</label>
                <BaseInput :id="'contract-guide-item-bl-' + index" v-model="item.bl" type="text" class="ds-field mt-2" />
              </div>
              <div>
                <label :for="'contract-guide-item-du-' + index" class="ds-field-label">Declaração única</label>
                <BaseInput :id="'contract-guide-item-du-' + index" v-model="item.du_no" type="text" class="ds-field mt-2" />
              </div>
              <div class="md:col-span-2 lg:col-span-3">
                <label :for="'contract-guide-item-obs-' + index" class="ds-field-label">Observações do produto</label>
                <textarea :id="'contract-guide-item-obs-' + index" v-model="item.obs" class="ds-field mt-2 min-h-24 resize-y" />
              </div>
            </div>
          </article>
        </div>
      </section>

      <section class="ds-panel p-5 sm:p-6">
        <div class="flex items-start gap-3">
          <DocumentTextIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          <div class="min-w-0 flex-1">
            <label for="contract-guide-observations" class="ds-field-label">Observações gerais</label>
            <textarea id="contract-guide-observations" v-model="form.obs" class="ds-field mt-2 min-h-32 resize-y" placeholder="Condições, exceções ou informação útil para interpretação futura" />
            <p v-if="form.errors.obs" class="ds-field-error mt-2">{{ form.errors.obs }}</p>
          </div>
        </div>
      </section>
    </div>

    <aside class="space-y-4 xl:sticky xl:top-24">
      <section class="ds-panel p-5">
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="ds-kicker">Estado do registo</p>
            <h2 class="ds-heading mt-2 text-base">Prontidão</h2>
          </div>
          <component :is="isReady ? CheckCircleIcon : ExclamationTriangleIcon" class="h-5 w-5 text-[var(--ds-text-soft)]" />
        </div>
        <span :class="[statusClass, 'mt-4']">{{ statusLabel }}</span>
        <dl class="mt-4 divide-y divide-[var(--ds-border)] border-y border-[var(--ds-border)]">
          <div class="flex items-center justify-between gap-3 py-3 text-sm">
            <dt class="font-semibold text-[var(--ds-text-muted)]">Produtos</dt>
            <dd class="font-bold tabular-nums text-[var(--ds-text)]">{{ form.items.length }}</dd>
          </div>
          <div class="flex items-center justify-between gap-3 py-3 text-sm">
            <dt class="font-semibold text-[var(--ds-text-muted)]">Completos</dt>
            <dd class="font-bold tabular-nums text-[var(--ds-text)]">{{ completedItems.length }}</dd>
          </div>
          <div class="flex items-center justify-between gap-3 py-3 text-sm">
            <dt class="font-semibold text-[var(--ds-text-muted)]">Cliente</dt>
            <dd class="max-w-36 truncate font-bold text-[var(--ds-text)]">{{ form.customer_id?.label || "Pendente" }}</dd>
          </div>
        </dl>
        <button type="submit" class="ds-button ds-button-primary mt-5 w-full" :disabled="form.processing || !isReady">
          <ClipboardDocumentListIcon class="h-4 w-4" />
          {{ form.processing ? "A guardar..." : submitLabel }}
        </button>
      </section>

      <section class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
        <div class="flex gap-3">
          <ShieldCheckIcon class="mt-0.5 h-5 w-5 shrink-0 text-[var(--ds-text-soft)]" />
          <div>
            <h2 class="text-sm font-bold text-[var(--ds-text)]">Rastreabilidade mínima</h2>
            <ul class="mt-2 space-y-2 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">
              <li class="flex gap-2"><GlobeAltIcon class="mt-0.5 h-4 w-4 shrink-0" /> Origem declarada por produto.</li>
              <li class="flex gap-2"><CheckCircleIcon class="mt-0.5 h-4 w-4 shrink-0" /> Fabricante e marca identificados.</li>
              <li class="flex gap-2"><TruckIcon class="mt-0.5 h-4 w-4 shrink-0" /> Referências de transporte preservadas.</li>
            </ul>
          </div>
        </div>
      </section>
    </aside>
  </div>
</template>
