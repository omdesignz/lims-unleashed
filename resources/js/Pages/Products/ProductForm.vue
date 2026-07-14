<script setup>
import ComboboxEnhanced from "@/Components/combobox-enhanced.vue";
import ToggleField from "@/Components/base/ToggleField.vue";
import {
  ArrowLeftIcon,
  BanknotesIcon,
  BeakerIcon,
  CheckIcon,
  CubeIcon,
  ReceiptPercentIcon,
} from "@heroicons/vue/24/outline";
import { Link } from "@inertiajs/vue3";
import { computed, watch } from "vue";

const props = defineProps({
  form: { type: Object, required: true },
  mode: { type: String, default: "create" },
});

const emit = defineEmits(["submit"]);
const isEditing = computed(() => props.mode === "edit");
const pageTitle = computed(() => isEditing.value ? "Editar produto analítico" : "Novo produto analítico");
const effectivePrice = computed(() => Number(props.form.fixed_price || props.form.price || 0));
const taxLabel = computed(() => props.form.charge_tax
  ? `${Number(props.form.tax_percentage || 0)}% de imposto`
  : props.form.exemption_id?.label || "Isenção por seleccionar");

watch(() => props.form.charge_tax, (chargesTax) => {
  if (chargesTax) {
    props.form.exemption_id = null;
    props.form.exemption_code = "";
    return;
  }

  props.form.tax_id = null;
  props.form.tax_percentage = 0;
});

function toBoolean(value) {
  return value === true || value === 1 || value === "1";
}

async function loadMatrices(query, setOptions) {
  const response = await fetch(`/matrixes/getMatrix?q=${encodeURIComponent(query)}`);
  const results = await response.json();
  setOptions(results.map((matrix) => ({
    value: matrix.id,
    label: matrix.code ? `${matrix.code} · ${matrix.description}` : matrix.description,
    price: matrix.price,
    fixed_price: matrix.fixed_price,
    charge_tax: toBoolean(matrix.charge_tax),
    withhold_tax: toBoolean(matrix.withhold_tax),
  })));
}

async function loadExemptions(query, setOptions) {
  const response = await fetch(`/taxexemptions/getExemption?q=${encodeURIComponent(query)}`);
  const results = await response.json();
  setOptions(results.map((exemption) => ({
    value: exemption.id,
    label: exemption.code ? `${exemption.code} · ${exemption.law || exemption.reason || "Isenção"}` : exemption.law,
    code: exemption.code,
  })));
}

async function loadTaxTypes(query, setOptions) {
  const response = await fetch(`/taxtypes/getTaxType?q=${encodeURIComponent(query)}`);
  const results = await response.json();
  setOptions(results.map((taxType) => ({
    value: taxType.id,
    label: `${taxType.name} · ${taxType.percent}%`,
    percent: taxType.percent,
  })));
}

function onMatrixSelect(matrix) {
  if (!matrix) {
    return;
  }

  props.form.price = matrix.price ?? matrix.fixed_price ?? props.form.price;
  props.form.fixed_price = matrix.fixed_price ?? props.form.fixed_price;
  props.form.charge_tax = Boolean(matrix.charge_tax);
  props.form.withhold_tax = Boolean(matrix.withhold_tax);
}

function onTaxTypeSelect(taxType) {
  props.form.tax_percentage = taxType?.percent ?? 0;
}

function onExemptionSelect(exemption) {
  props.form.exemption_code = exemption?.code ?? "";
}

function formatMoney(value) {
  return new Intl.NumberFormat("pt-AO", { style: "currency", currency: "AOA", maximumFractionDigits: 2 }).format(Number(value || 0));
}
</script>

<template>
  <form class="space-y-6" @submit.prevent="emit('submit')">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <CubeIcon class="h-5 w-5" aria-hidden="true" />
          </span>
          <div>
            <p class="ds-kicker">Catálogo comercial analítico</p>
            <h1 class="ds-heading mt-1 text-2xl">{{ pageTitle }}</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">Defina a matriz laboratorial, o preço aplicável e o enquadramento fiscal da oferta.</p>
          </div>
        </div>

        <Link :href="route('products.index')" class="ds-button ds-button-secondary">
          <ArrowLeftIcon class="h-4 w-4" aria-hidden="true" />
          Voltar ao catálogo
        </Link>
      </div>

      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-3">
        <div class="border-b border-[var(--ds-border)] px-4 py-3 sm:border-b-0 sm:border-r">
          <dt class="flex items-center gap-2 text-xs font-bold uppercase text-[var(--ds-text-soft)]"><BeakerIcon class="h-4 w-4" aria-hidden="true" /> Matriz</dt>
          <dd class="mt-2 truncate text-sm font-semibold text-[var(--ds-text)]">{{ form.matrix_id?.label || "Por seleccionar" }}</dd>
        </div>
        <div class="border-b border-[var(--ds-border)] px-4 py-3 sm:border-b-0 sm:border-r">
          <dt class="flex items-center gap-2 text-xs font-bold uppercase text-[var(--ds-text-soft)]"><BanknotesIcon class="h-4 w-4" aria-hidden="true" /> Preço</dt>
          <dd class="mt-2 text-sm font-semibold text-[var(--ds-text)]">{{ formatMoney(effectivePrice) }}</dd>
        </div>
        <div class="px-4 py-3">
          <dt class="flex items-center gap-2 text-xs font-bold uppercase text-[var(--ds-text-soft)]"><ReceiptPercentIcon class="h-4 w-4" aria-hidden="true" /> Fiscalidade</dt>
          <dd class="mt-2 truncate text-sm font-semibold text-[var(--ds-text)]">{{ taxLabel }}</dd>
        </div>
      </dl>
    </section>

    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
        <p class="ds-kicker">Âmbito do serviço</p>
        <h2 class="ds-heading mt-1 text-base">Identificação e matriz</h2>
      </div>

      <div class="grid gap-5 px-5 py-5 sm:grid-cols-2 sm:px-6">
        <div>
          <label for="product-name" class="ds-field-label mb-2 block">Designação do produto</label>
          <BaseInput id="product-name" v-model="form.name" type="text" class="ds-field" autofocus />
          <p v-if="form.errors.name" class="ds-field-error mt-2">{{ form.errors.name }}</p>
        </div>

        <div>
          <ComboboxEnhanced
            v-model="form.matrix_id"
            title-label="Matriz analítica"
            placeholder="Pesquisar código ou matriz"
            :has-error="Boolean(form.errors.matrix_id)"
            :load-options="loadMatrices"
            @update:model-value="onMatrixSelect"
          />
          <p v-if="form.errors.matrix_id" class="ds-field-error mt-2">{{ form.errors.matrix_id }}</p>
        </div>

        <div class="sm:col-span-2">
          <label for="product-description" class="ds-field-label mb-2 block">Descrição comercial</label>
          <textarea id="product-description" v-model="form.description" rows="4" class="ds-field min-h-28 resize-y"></textarea>
          <p v-if="form.errors.description" class="ds-field-error mt-2">{{ form.errors.description }}</p>
        </div>
      </div>
    </section>

    <section class="grid gap-5 xl:grid-cols-2">
      <div class="ds-panel overflow-hidden">
        <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
          <p class="ds-kicker">Valorização</p>
          <h2 class="ds-heading mt-1 text-base">Preço do produto</h2>
        </div>

        <div class="grid gap-5 px-5 py-5 sm:grid-cols-2 sm:px-6">
          <div>
            <label for="product-price" class="ds-field-label mb-2 block">Preço calculado (AOA)</label>
            <BaseInput id="product-price" v-model.number="form.price" type="number" min="0" step="0.01" class="ds-field" />
            <p v-if="form.errors.price" class="ds-field-error mt-2">{{ form.errors.price }}</p>
          </div>
          <div>
            <label for="product-fixed-price" class="ds-field-label mb-2 block">Preço fixo (AOA)</label>
            <BaseInput id="product-fixed-price" v-model.number="form.fixed_price" type="number" min="0" step="0.01" class="ds-field" />
            <p v-if="form.errors.fixed_price" class="ds-field-error mt-2">{{ form.errors.fixed_price }}</p>
          </div>
        </div>
      </div>

      <div class="ds-panel overflow-hidden">
        <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
          <p class="ds-kicker">Enquadramento AGT</p>
          <h2 class="ds-heading mt-1 text-base">Tratamento fiscal</h2>
        </div>

        <div class="space-y-5 px-5 py-5 sm:px-6">
          <div class="grid gap-3 sm:grid-cols-2">
            <ToggleField id="product-charge-tax" v-model="form.charge_tax" label="Cobrar imposto" description="Aplica uma categoria fiscal ao produto." />
            <ToggleField id="product-withhold-tax" v-model="form.withhold_tax" label="Sujeito a retenção" description="Assinala retenção fiscal na facturação." />
          </div>

          <div v-if="form.charge_tax" class="grid gap-5 sm:grid-cols-2">
            <div>
              <ComboboxEnhanced
                v-model="form.tax_id"
                title-label="Categoria fiscal"
                placeholder="Pesquisar imposto"
                :has-error="Boolean(form.errors.tax_id)"
                :load-options="loadTaxTypes"
                @update:model-value="onTaxTypeSelect"
              />
              <p v-if="form.errors.tax_id" class="ds-field-error mt-2">{{ form.errors.tax_id }}</p>
            </div>
            <div>
              <label for="product-tax-percentage" class="ds-field-label mb-2 block">Taxa (%)</label>
              <BaseInput id="product-tax-percentage" v-model.number="form.tax_percentage" type="number" min="0" step="0.01" class="ds-field" />
              <p v-if="form.errors.tax_percentage" class="ds-field-error mt-2">{{ form.errors.tax_percentage }}</p>
            </div>
          </div>

          <div v-else>
            <ComboboxEnhanced
              v-model="form.exemption_id"
              title-label="Motivo de isenção"
              placeholder="Pesquisar código ou diploma"
              :has-error="Boolean(form.errors.exemption_id)"
              :load-options="loadExemptions"
              @update:model-value="onExemptionSelect"
            />
            <p v-if="form.errors.exemption_id" class="ds-field-error mt-2">{{ form.errors.exemption_id }}</p>
            <p v-if="form.errors.exemption_code" class="ds-field-error mt-2">{{ form.errors.exemption_code }}</p>
          </div>
        </div>
      </div>
    </section>

    <section class="ds-panel flex flex-col-reverse gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6">
      <Link :href="route('products.index')" class="ds-button ds-button-secondary">Cancelar</Link>
      <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
        <CheckIcon class="h-4 w-4" aria-hidden="true" />
        {{ form.processing ? "A guardar..." : (isEditing ? "Actualizar produto" : "Guardar produto") }}
      </button>
    </section>
  </form>
</template>
