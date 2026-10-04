<script setup>
import Combobox from "@/Components/combobox.vue";
import { loadSelectOptions, optionMappers } from "@/Utils/selectOptions";
import { Mail as EnvelopeIcon, MapPin as MapPinIcon, User as UserIcon } from "@lucide/vue";

const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
});

function loadCustomers(query, setOptions) {
  return loadSelectOptions("/customers/getCustomer", query, setOptions, optionMappers.name);
}

function loadWarehouses(query, setOptions) {
  return loadSelectOptions(
    "/warehouses/getWarehouse",
    query,
    setOptions,
    optionMappers.address,
    { customer_id: props.form.customer_id?.value },
  );
}

function loadCategories(query, setOptions) {
  return loadSelectOptions(
    "/customerrequestcategories/getCustomerRequestCategory",
    query,
    setOptions,
    optionMappers.name,
  );
}

function updateCustomer(customer) {
  if (customer?.value !== props.form.customer_id?.value) {
    props.form.warehouse_id = null;
  }

  props.form.customer_id = customer;
}
</script>

<template>
  <div class="divide-y divide-[var(--ds-border)]">
    <section class="px-5 py-5 sm:px-6">
      <div class="mb-5">
        <p class="ds-kicker">Classificação</p>
        <h2 class="ds-heading mt-1 text-base">Origem e encaminhamento</h2>
        <p class="ds-copy mt-1 text-sm">Associe o pedido ao cliente, local e categoria operacional correctos.</p>
      </div>

      <div class="grid gap-5 lg:grid-cols-2">
        <div class="ds-field-group">
          <Combobox
            v-model="form.category_id"
            :has-error="Boolean(form.errors.category_id)"
            :load-options="loadCategories"
            :title-label="$t('gestlab.general.labels.customer_requests.category_id')"
            placeholder="Pesquisar categoria"
          />
          <p v-if="form.errors.category_id" class="ds-field-error">{{ form.errors.category_id }}</p>
        </div>

        <div class="ds-field-group">
          <Combobox
            :model-value="form.customer_id"
            :disable-input="Boolean(form.id)"
            :has-error="Boolean(form.errors.customer_id)"
            :load-options="loadCustomers"
            :title-label="$t('gestlab.general.labels.customer_requests.customer_id')"
            placeholder="Pesquisar cliente"
            @update:model-value="updateCustomer"
          />
          <p v-if="form.errors.customer_id" class="ds-field-error">{{ form.errors.customer_id }}</p>
        </div>

        <div class="ds-field-group lg:col-span-2">
          <Combobox
            v-model="form.warehouse_id"
            :has-error="Boolean(form.errors.warehouse_id)"
            :load-options="loadWarehouses"
            :disable-input="Boolean(form.id) || !form.customer_id"
            :title-label="$t('gestlab.general.labels.customer_requests.warehouse_id')"
            :placeholder="form.customer_id ? 'Pesquisar local do cliente' : 'Seleccione primeiro o cliente'"
          />
          <p v-if="form.errors.warehouse_id" class="ds-field-error">{{ form.errors.warehouse_id }}</p>
          <p v-else class="ds-field-hint inline-flex items-center gap-1.5">
            <MapPinIcon class="h-3.5 w-3.5" />
            O local determina o contexto logistico e de amostragem do pedido.
          </p>
        </div>
      </div>
    </section>

    <section class="px-5 py-5 sm:px-6">
      <div class="mb-5">
        <p class="ds-kicker">Pedido</p>
        <h2 class="ds-heading mt-1 text-base">Necessidade comunicada</h2>
        <p class="ds-copy mt-1 text-sm">Registe o pedido com detalhe suficiente para triagem sem contacto adicional.</p>
      </div>

      <div class="ds-field-group">
        <label for="customer-request-description" class="ds-field-label">
          {{ $t('gestlab.general.labels.customer_requests.description') }}
          <span class="ds-field-required">*</span>
        </label>
        <textarea
          id="customer-request-description"
          v-model="form.description"
          class="ds-field min-h-36 resize-y"
          :aria-invalid="Boolean(form.errors.description)"
          placeholder="Descreva o serviço, ensaio, prazo ou esclarecimento solicitado"
        />
        <p v-if="form.errors.description" class="ds-field-error">{{ form.errors.description }}</p>
        <p v-else class="ds-field-hint">Inclua matriz, parâmetros, quantidade e prazo quando forem conhecidos.</p>
      </div>
    </section>

    <section class="px-5 py-5 sm:px-6">
      <div class="mb-5">
        <p class="ds-kicker">Contacto</p>
        <h2 class="ds-heading mt-1 text-base">Ponto de comunicação</h2>
        <p class="ds-copy mt-1 text-sm">Dados usados para confirmar o âmbito e comunicar o seguimento.</p>
      </div>

      <div class="grid gap-5 lg:grid-cols-2">
        <div class="ds-field-group">
          <label for="customer-request-contact" class="ds-field-label">
            {{ $t('gestlab.general.labels.customer_requests.contact') }}
            <span class="ds-field-required">*</span>
          </label>
          <div class="relative">
            <UserIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" />
            <BaseInput
              id="customer-request-contact"
              v-model="form.contact"
              type="text"
              class="ds-field pl-9"
              :aria-invalid="Boolean(form.errors.contact)"
              autocomplete="name"
              placeholder="Nome do contacto"
            />
          </div>
          <p v-if="form.errors.contact" class="ds-field-error">{{ form.errors.contact }}</p>
        </div>

        <div class="ds-field-group">
          <label for="customer-request-email" class="ds-field-label">
            {{ $t('gestlab.general.labels.customer_requests.email') }}
            <span class="ds-field-required">*</span>
          </label>
          <div class="relative">
            <EnvelopeIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" />
            <BaseInput
              id="customer-request-email"
              v-model="form.email"
              type="email"
              class="ds-field pl-9"
              :aria-invalid="Boolean(form.errors.email)"
              autocomplete="email"
              placeholder="contacto@cliente.ao"
            />
          </div>
          <p v-if="form.errors.email" class="ds-field-error">{{ form.errors.email }}</p>
        </div>
      </div>
    </section>
  </div>
</template>
