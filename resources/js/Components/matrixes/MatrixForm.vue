<script setup>
import ToggleField from "@/Components/base/ToggleField.vue";
import Combobox from "@/Components/combobox.vue";
import { createEmptyMatrixProfile } from "@/Components/matrixes/matrixFormData";
import { loadSelectOptions, optionMappers } from "@/Utils/selectOptions";
import {
  Banknote as BanknotesIcon,
  Building2 as BuildingOffice2Icon,
  BadgeCheck as CheckBadgeIcon,
  ClipboardList as ClipboardDocumentListIcon,
  TriangleAlert as ExclamationTriangleIcon,
  IdCard as IdentificationIcon,
  Plus as PlusIcon,
  Trash2 as TrashIcon,
} from "@lucide/vue";
import { computed, watch } from "vue";

const props = defineProps({
  form: { type: Object, required: true },
});

const totalPrice = computed(() => props.form.profiles.reduce((total, profile) => {
  return total + Number(profile.profile_id?.parameters_price ?? profile.profile_id?.price ?? profile.price ?? 0);
}, 0));

const duplicateProfileLabels = computed(() => {
  const counts = new Map();

  props.form.profiles.forEach((profile) => {
    const id = profile.profile_id?.value;
    if (id) counts.set(id, (counts.get(id) || 0) + 1);
  });

  return props.form.profiles
    .filter((profile) => profile.profile_id?.value && counts.get(profile.profile_id.value) > 1)
    .map((profile) => profile.profile_id?.label)
    .filter((label, index, labels) => label && labels.indexOf(label) === index);
});

const departmentNames = computed(() => [...new Set(props.form.profiles
  .map((profile) => profile.profile_id?.department_name)
  .filter(Boolean))]);

const profilesWithoutActiveParameters = computed(() => props.form.profiles
  .filter((profile) => profile.profile_id && Number(profile.profile_id.active_parameter_count || 0) === 0)
  .map((profile) => profile.profile_id?.label)
  .filter(Boolean));

const governanceIssues = computed(() => {
  const issues = [];

  if (duplicateProfileLabels.value.length) {
    issues.push(`Perfis repetidos: ${duplicateProfileLabels.value.join(", ")}.`);
  }

  if (departmentNames.value.length > 1) {
    issues.push(`Departamentos misturados: ${departmentNames.value.join(", ")}.`);
  }

  if (profilesWithoutActiveParameters.value.length) {
    issues.push(`Perfis sem parâmetros activos: ${profilesWithoutActiveParameters.value.join(", ")}.`);
  }

  if (props.form.profiles.some((profile) => profile.profile_id && !profile.profile_id.department_id)) {
    issues.push("Todos os perfis devem estar ligados a uma categoria com departamento definido.");
  }

  if (!props.form.profiles.length) {
    issues.push("Adicione pelo menos um perfil para definir o âmbito da matriz.");
  }

  return issues;
});

const commercialVariance = computed(() => Number(props.form.fixed_price || 0) - totalPrice.value);

watch(totalPrice, (price) => {
  props.form.price = price;
}, { immediate: true });

watch(() => props.form.charge_tax, (chargeTax) => {
  if (chargeTax) {
    props.form.exemption_id = null;
    props.form.exemption_code = null;
    return;
  }

  props.form.tax_id = null;
  props.form.tax_percentage = 0;
});

function profileOption(item) {
  return {
    ...item,
    value: item.id,
    label: [item.code, item.name].filter(Boolean).join(" - ") || `Perfil #${item.id}`,
    price: Number(item.parameters_price ?? item.price ?? 0),
    parameters_price: Number(item.parameters_price ?? item.price ?? 0),
    active_parameter_count: Number(item.active_parameter_count || 0),
    total_parameter_count: Number(item.total_parameter_count || 0),
  };
}

function loadProfiles(query, setOptions) {
  return loadSelectOptions("/profiles/getProfile", query, setOptions, profileOption);
}

function loadExemptions(query, setOptions) {
  return loadSelectOptions("/taxexemptions/getExemption", query, setOptions, optionMappers.code);
}

function loadTaxTypes(query, setOptions) {
  return loadSelectOptions(
    "/taxtypes/getTaxType",
    query,
    setOptions,
    (item) => ({ value: item.id, label: item.name, percent: Number(item.percent || 0) }),
  );
}

function addProfile() {
  props.form.profiles.push(createEmptyMatrixProfile());
}

function removeProfile(index) {
  props.form.profiles.splice(index, 1);
}

function selectProfile(profile, selectedProfile) {
  profile.profile_id = selectedProfile;
  profile.profile = selectedProfile?.label || "";
  profile.price = Number(selectedProfile?.parameters_price ?? selectedProfile?.price ?? 0);
}

function selectTaxType(taxType) {
  props.form.tax_id = taxType;
  props.form.tax_percentage = Number(taxType?.percent || 0);
}

function formatCurrency(value) {
  return new Intl.NumberFormat("pt-PT", {
    style: "currency",
    currency: "AOA",
    minimumFractionDigits: 2,
  }).format(Number(value || 0));
}
</script>

<template>
  <div class="divide-y divide-[var(--ds-border)]">
    <section class="px-5 py-5 sm:px-6">
      <div class="mb-5 flex items-start gap-3">
        <IdentificationIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
        <div>
          <p class="ds-kicker">Identidade e catálogo</p>
          <h2 class="ds-heading mt-1 text-base">Matriz de serviço</h2>
          <p class="ds-copy mt-1 text-sm">Defina a matriz comercial usada para agrupar perfis compativeis no registo de produtos e amostras.</p>
        </div>
      </div>

      <div class="grid gap-5 lg:grid-cols-2">
        <div class="ds-field-group">
          <label for="matrix-code" class="ds-field-label">Código <span class="ds-field-required">*</span></label>
          <BaseInput id="matrix-code" v-model="form.code" type="text" class="ds-field font-mono" :aria-invalid="Boolean(form.errors.code)" placeholder="Ex.: MAT-AGUA" />
          <p v-if="form.errors.code" class="ds-field-error">{{ form.errors.code }}</p>
        </div>
        <div class="ds-field-group">
          <label for="matrix-fixed-price" class="ds-field-label">Preço fixo <span class="ds-field-required">*</span></label>
          <BaseInput id="matrix-fixed-price" v-model.number="form.fixed_price" type="number" min="0" step="0.01" class="ds-field" :aria-invalid="Boolean(form.errors.fixed_price)" />
          <p v-if="form.errors.fixed_price" class="ds-field-error">{{ form.errors.fixed_price }}</p>
        </div>
        <div class="ds-field-group lg:col-span-2">
          <label for="matrix-description" class="ds-field-label">Descrição operacional</label>
          <textarea id="matrix-description" v-model="form.description" class="ds-field min-h-28 resize-y" :aria-invalid="Boolean(form.errors.description)" placeholder="Tipo de amostra, aplicação ou restricoes de utilização" />
          <p v-if="form.errors.description" class="ds-field-error">{{ form.errors.description }}</p>
        </div>
      </div>

      <dl class="mt-5 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-3">
        <div class="border-b border-[var(--ds-border)] p-4 sm:border-b-0 sm:border-r">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Preço composto</dt>
          <dd class="mt-2 text-lg font-bold text-[var(--ds-text)]">{{ formatCurrency(totalPrice) }}</dd>
        </div>
        <div class="border-b border-[var(--ds-border)] p-4 sm:border-b-0 sm:border-r">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Preço fixo</dt>
          <dd class="mt-2 text-lg font-bold text-[var(--ds-text)]">{{ formatCurrency(form.fixed_price) }}</dd>
        </div>
        <div class="p-4">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Diferença comercial</dt>
          <dd :class="['mt-2 text-lg font-bold', commercialVariance < 0 ? 'text-rose-700 dark:text-rose-200' : 'text-[var(--ds-text)]']">{{ formatCurrency(commercialVariance) }}</dd>
        </div>
      </dl>
    </section>

    <section class="px-5 py-5 sm:px-6">
      <div class="mb-5 flex items-start gap-3">
        <BanknotesIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
        <div>
          <p class="ds-kicker">Fiscalidade</p>
          <h2 class="ds-heading mt-1 text-base">Imposto e isencao</h2>
          <p class="ds-copy mt-1 text-sm">Mantenha a regra fiscal explícita para propostas, cotações e facturação.</p>
        </div>
      </div>

      <div class="overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] divide-y divide-[var(--ds-border)]">
        <ToggleField v-model="form.charge_tax" id="matrix-charge-tax" label="Aplicar imposto" description="Usa uma categoria fiscal e a respectiva percentagem na comercialização desta matriz." />
        <ToggleField v-model="form.withhold_tax" id="matrix-withhold-tax" label="Sujeito a retencao" description="Indica que a matriz participa no cálculo de retencao aplicavel ao documento comercial." />
      </div>

      <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <div v-if="form.charge_tax" class="ds-field-group">
          <label class="ds-field-label">Categoria fiscal <span class="ds-field-required">*</span></label>
          <Combobox :model-value="form.tax_id" :load-options="loadTaxTypes" :has-error="Boolean(form.errors.tax_id)" placeholder="Seleccione o imposto" @update:model-value="selectTaxType" />
          <p v-if="form.errors.tax_id" class="ds-field-error">{{ form.errors.tax_id }}</p>
        </div>
        <div v-if="form.charge_tax" class="ds-field-group">
          <label for="matrix-tax-percentage" class="ds-field-label">Percentagem</label>
          <BaseInput id="matrix-tax-percentage" :value="form.tax_percentage" type="text" class="ds-field" readonly />
        </div>
        <div v-else class="ds-field-group lg:col-span-2">
          <label class="ds-field-label">Motivo de isencao <span class="ds-field-required">*</span></label>
          <Combobox v-model="form.exemption_id" :load-options="loadExemptions" :has-error="Boolean(form.errors.exemption_id)" placeholder="Seleccione a isencao" />
          <p v-if="form.errors.exemption_id" class="ds-field-error">{{ form.errors.exemption_id }}</p>
        </div>
      </div>
    </section>

    <section class="px-5 py-5 sm:px-6">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-3">
          <ClipboardDocumentListIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          <div>
            <p class="ds-kicker">Âmbito analítico</p>
            <h2 class="ds-heading mt-1 text-base">Perfis da matriz</h2>
            <p class="ds-copy mt-1 text-sm">Agrupe apenas perfis do mesmo departamento e com cobertura analítica activa.</p>
          </div>
        </div>
        <button type="button" class="ds-button ds-button-secondary" @click="addProfile">
          <PlusIcon class="h-4 w-4" />
          Adicionar perfil
        </button>
      </div>

      <div class="mt-5 flex flex-wrap gap-2">
        <span class="ds-chip">{{ form.profiles.length }} perfil(is)</span>
        <span class="ds-chip">{{ departmentNames.length || 0 }} departamento(s)</span>
        <span :class="['ds-chip', governanceIssues.length ? 'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20' : 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20']">
          {{ governanceIssues.length ? `${governanceIssues.length} ponto(s) a rever` : "Âmbito coerente" }}
        </span>
      </div>

      <div v-if="governanceIssues.length" class="mt-4 rounded-lg bg-amber-50 p-4 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:ring-amber-400/20">
        <div class="flex items-start gap-3">
          <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 shrink-0 text-amber-700 dark:text-amber-200" />
          <div>
            <p class="text-sm font-bold text-amber-900 dark:text-amber-100">Rever antes de guardar</p>
            <ul class="mt-1 grid gap-1 text-sm font-semibold text-amber-800 dark:text-amber-200">
              <li v-for="issue in governanceIssues" :key="issue">{{ issue }}</li>
            </ul>
          </div>
        </div>
      </div>
      <p v-if="form.errors.profiles" class="ds-field-error mt-3">{{ form.errors.profiles }}</p>

      <div v-if="form.profiles.length" class="mt-5 divide-y divide-[var(--ds-border)] border-y border-[var(--ds-border)]">
        <article v-for="(profile, index) in form.profiles" :key="index" class="py-5">
          <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_11rem_auto] lg:items-start">
            <div class="ds-field-group">
              <label class="ds-field-label">Perfil {{ index + 1 }} <span class="ds-field-required">*</span></label>
              <Combobox :model-value="profile.profile_id" :load-options="loadProfiles" :has-error="Boolean(form.errors[`profiles.${index}.profile_id`])" placeholder="Pesquisar por código ou nome" @update:model-value="selectProfile(profile, $event)" />
              <p v-if="form.errors[`profiles.${index}.profile_id`]" class="ds-field-error">{{ form.errors[`profiles.${index}.profile_id`] }}</p>
              <div v-if="profile.profile_id" class="mt-2 flex flex-wrap gap-2">
                <span v-if="profile.profile_id.category_name" class="ds-chip">{{ profile.profile_id.category_name }}</span>
                <span v-if="profile.profile_id.department_name" class="ds-chip"><BuildingOffice2Icon class="h-3.5 w-3.5" /> {{ profile.profile_id.department_name }}</span>
                <span class="ds-chip">{{ profile.profile_id.active_parameter_count || 0 }}/{{ profile.profile_id.total_parameter_count || 0 }} parâmetros activos</span>
              </div>
            </div>
            <div>
              <p class="ds-field-label">Valor do perfil</p>
              <p class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ formatCurrency(profile.profile_id?.parameters_price ?? profile.profile_id?.price ?? profile.price) }}</p>
            </div>
            <button type="button" class="ds-table-action ds-table-action-danger lg:mt-5" title="Remover perfil" @click="removeProfile(index)">
              <TrashIcon class="h-4 w-4" />
              <span class="sr-only">Remover perfil</span>
            </button>
          </div>
        </article>
      </div>

      <div v-else class="mt-5 rounded-lg border border-dashed border-[var(--ds-border-strong)] px-5 py-10 text-center">
        <CheckBadgeIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
        <p class="mt-3 text-sm font-bold text-[var(--ds-text)]">Nenhum perfil adicionado</p>
        <p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">A matriz precisa de pelo menos um perfil analítico controlado.</p>
        <button type="button" class="ds-button ds-button-secondary mt-4" @click="addProfile">
          <PlusIcon class="h-4 w-4" />
          Adicionar primeiro perfil
        </button>
      </div>
    </section>
  </div>
</template>
