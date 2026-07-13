<script setup>
import { router, useForm } from "@inertiajs/vue3";
import Combobox from "@/Components/combobox.vue";
import {
  ArrowPathIcon,
  BuildingOffice2Icon,
  CheckIcon,
  EnvelopeIcon,
  MapPinIcon,
  PhoneIcon,
  StarIcon,
  TrashIcon,
  UserCircleIcon,
} from "@heroicons/vue/24/outline";
import { computed } from "vue";

const props = defineProps({
  record: { type: Object, required: true },
  primary_warehouse: Number,
  showCustomerSelector: { type: Boolean, default: false },
  allowDelete: { type: Boolean, default: true },
  managePrimary: { type: Boolean, default: true },
});

const emit = defineEmits(["removed-from-array", "saved"]);

const form = useForm({
  id: props.record?.id ?? null,
  name: props.record?.name ?? "",
  code: props.record?.code ?? "",
  description: props.record?.description ?? "",
  email: props.record?.email ?? "",
  invoicing_email: props.record?.invoicing_email ?? "",
  primary_phone: props.record?.primary_phone ?? "",
  alternative_phone: props.record?.alternative_phone ?? "",
  nif: props.record?.nif ?? "",
  address: props.record?.address ?? "",
  municipality: props.record?.municipality ?? "",
  province: props.record?.province ?? "",
  focal_point: props.record?.focal_point ?? "",
  focal_point_email: props.record?.focal_point_email ?? "",
  focal_point_contact: props.record?.focal_point_contact ?? "",
  customer_id: typeof props.record?.customer_id === "object"
    ? props.record.customer_id
    : props.record?.customer_id
      ? { value: props.record.customer_id, label: props.record.customer || `Cliente #${props.record.customer_id}` }
      : null,
});

const isPrimary = computed(() => Number(props.primary_warehouse) === Number(form.id));
const siteTitle = computed(() => form.name || form.code || "Novo local operacional");

function submit() {
  const options = {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => emit("saved"),
  };

  if (form.id) {
    form.put(route("warehouses.update", { warehouse: form.id }), options);
    return;
  }

  form.post(route("warehouses.store"), options);
}

async function loadCustomers(query, setOptions) {
  const response = await fetch(`/customers/getCustomer?q=${encodeURIComponent(query)}`, {
    credentials: "same-origin",
    headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
  });

  if (!response.ok) {
    setOptions([]);
    return;
  }

  const results = await response.json();
  setOptions(results.map((customer) => ({ value: customer.id, label: customer.name })));
}

function removeSite() {
  if (!form.id) {
    emit("removed-from-array");
    return;
  }

  router.get(route("warehouses.destroy"), { recordIds: [form.id] }, {
    preserveScroll: true,
    preserveState: false,
  });
}

function makePrimary() {
  const customerId = form.customer_id?.value ?? form.customer_id;

  if (!form.id || !customerId || isPrimary.value) {
    return;
  }

  router.put(route("customers.changePrimaryWarehouse", { customer: customerId }), { warehouse_id: form.id }, {
    preserveScroll: true,
    preserveState: false,
  });
}
</script>

<template>
  <article class="ds-card overflow-hidden">
    <header class="flex flex-col gap-4 border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
      <div class="flex min-w-0 items-start gap-3">
        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
          <BuildingOffice2Icon class="h-5 w-5" />
        </span>
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <h3 class="break-words text-sm font-bold text-[var(--ds-text)]">{{ siteTitle }}</h3>
            <span v-if="isPrimary" class="ds-chip bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20">Principal</span>
            <span v-else class="ds-chip">Secundario</span>
          </div>
          <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ form.code || "Codigo por definir" }}</p>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <button v-if="managePrimary && form.id && !isPrimary" type="button" class="ds-button ds-button-secondary min-h-0 px-3 py-2 text-xs" @click="makePrimary">
          <StarIcon class="h-4 w-4" />
          Tornar principal
        </button>
        <button v-if="allowDelete" type="button" class="ds-icon-button text-rose-600" title="Remover local" @click="removeSite">
          <TrashIcon class="h-4 w-4" />
          <span class="sr-only">Remover local</span>
        </button>
      </div>
    </header>

    <form :id="`warehouse-form-${form.id || 'new'}`" class="divide-y divide-[var(--ds-border)]" @submit.prevent="submit">
      <section v-if="showCustomerSelector" class="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-[13rem_minmax(0,1fr)] lg:gap-8">
        <div>
          <div class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]">
            <BuildingOffice2Icon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />
            Conta cliente
          </div>
          <p class="ds-copy mt-2 text-sm">Associe o local ao cliente responsavel pelas amostras e documentos.</p>
        </div>
        <div class="ds-field-group">
          <label class="ds-field-label">Cliente <span class="ds-field-required">*</span></label>
          <Combobox v-model="form.customer_id" :load-options="loadCustomers" placeholder="Pesquisar cliente" :has-error="Boolean(form.errors.customer_id)" />
          <p v-if="form.errors.customer_id" class="ds-field-error">{{ form.errors.customer_id }}</p>
        </div>
      </section>

      <section class="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-[13rem_minmax(0,1fr)] lg:gap-8">
        <div>
          <div class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]">
            <MapPinIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />
            Identificacao e morada
          </div>
          <p class="ds-copy mt-2 text-sm">Local usado na rececao, recolha e emissao documental.</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <div class="ds-field-group">
            <label class="ds-field-label">Nome do local</label>
            <input v-model="form.name" type="text" class="ds-field" :aria-invalid="Boolean(form.errors.name)" />
            <p v-if="form.errors.name" class="ds-field-error">{{ form.errors.name }}</p>
          </div>
          <div class="ds-field-group">
            <label class="ds-field-label">Codigo</label>
            <input v-model="form.code" type="text" class="ds-field font-mono uppercase" :aria-invalid="Boolean(form.errors.code)" />
            <p v-if="form.errors.code" class="ds-field-error">{{ form.errors.code }}</p>
          </div>
          <div class="ds-field-group sm:col-span-2">
            <label class="ds-field-label">Endereco <span class="ds-field-required">*</span></label>
            <input v-model="form.address" type="text" class="ds-field" autocomplete="street-address" :aria-invalid="Boolean(form.errors.address)" />
            <p v-if="form.errors.address" class="ds-field-error">{{ form.errors.address }}</p>
          </div>
          <div class="ds-field-group">
            <label class="ds-field-label">Municipio</label>
            <input v-model="form.municipality" type="text" class="ds-field" />
          </div>
          <div class="ds-field-group">
            <label class="ds-field-label">Provincia</label>
            <input v-model="form.province" type="text" class="ds-field" />
          </div>
          <div class="ds-field-group">
            <label class="ds-field-label">NIF</label>
            <input v-model="form.nif" type="text" class="ds-field font-mono" />
          </div>
          <div class="ds-field-group">
            <label class="ds-field-label">Descricao</label>
            <input v-model="form.description" type="text" class="ds-field" />
          </div>
        </div>
      </section>

      <section class="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-[13rem_minmax(0,1fr)] lg:gap-8">
        <div>
          <div class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]">
            <EnvelopeIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />
            Canais da conta
          </div>
          <p class="ds-copy mt-2 text-sm">Enderecos e telefones usados nas comunicacoes operacionais e financeiras.</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <div class="ds-field-group">
            <label class="ds-field-label">Email operacional <span class="ds-field-required">*</span></label>
            <input v-model="form.email" type="email" class="ds-field" autocomplete="email" :aria-invalid="Boolean(form.errors.email)" />
            <p v-if="form.errors.email" class="ds-field-error">{{ form.errors.email }}</p>
          </div>
          <div class="ds-field-group">
            <label class="ds-field-label">Email de faturacao</label>
            <input v-model="form.invoicing_email" type="email" class="ds-field" :aria-invalid="Boolean(form.errors.invoicing_email)" />
            <p v-if="form.errors.invoicing_email" class="ds-field-error">{{ form.errors.invoicing_email }}</p>
          </div>
          <div class="ds-field-group">
            <label class="ds-field-label">Telefone principal</label>
            <div class="relative">
              <PhoneIcon class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-[var(--ds-text-soft)]" />
              <input v-model="form.primary_phone" type="tel" class="ds-field pl-10" autocomplete="tel" />
            </div>
          </div>
          <div class="ds-field-group">
            <label class="ds-field-label">Telefone alternativo</label>
            <input v-model="form.alternative_phone" type="tel" class="ds-field" />
          </div>
        </div>
      </section>

      <section class="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-[13rem_minmax(0,1fr)] lg:gap-8">
        <div>
          <div class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]">
            <UserCircleIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />
            Ponto focal
          </div>
          <p class="ds-copy mt-2 text-sm">Pessoa a contactar para amostras, agenda e esclarecimentos tecnicos.</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
          <div class="ds-field-group">
            <label class="ds-field-label">Nome</label>
            <input v-model="form.focal_point" type="text" class="ds-field" />
          </div>
          <div class="ds-field-group">
            <label class="ds-field-label">Email</label>
            <input v-model="form.focal_point_email" type="email" class="ds-field" :aria-invalid="Boolean(form.errors.focal_point_email)" />
            <p v-if="form.errors.focal_point_email" class="ds-field-error">{{ form.errors.focal_point_email }}</p>
          </div>
          <div class="ds-field-group">
            <label class="ds-field-label">Telefone</label>
            <input v-model="form.focal_point_contact" type="tel" class="ds-field" />
          </div>
        </div>
      </section>
    </form>

    <footer class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6">
      <span v-if="isPrimary" class="mr-auto inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 dark:text-emerald-200">
        <StarIcon class="h-4 w-4" />
        Local principal da conta
      </span>
      <button type="submit" :form="`warehouse-form-${form.id || 'new'}`" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
        <ArrowPathIcon v-if="form.processing" class="h-4 w-4 animate-spin" />
        <CheckIcon v-else class="h-4 w-4" />
        {{ form.processing ? "A guardar..." : form.id ? "Guardar local" : "Criar local" }}
      </button>
    </footer>
  </article>
</template>
