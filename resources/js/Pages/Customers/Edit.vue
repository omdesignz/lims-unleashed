<script setup>
import CustomerForm from "@/Components/customers/CustomerForm.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import WarehouseComponent from "@/Pages/Warehouses/warehouse-component.vue";
import { Link, useForm } from "@inertiajs/vue3";
import {
  Eye as EyeIcon,
  MapPin as MapPinIcon,
  Plus as PlusIcon,
} from "@lucide/vue";
import { computed, ref } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, required: true },
});

const form = useForm({
  name: props.record?.name ?? "",
  code: props.record?.code ?? "",
  description: props.record?.description ?? "",
  category_id: props.record?.category_id ?? null,
});

const sites = ref([...(props.record?.warehouses || [])]);
const siteCount = computed(() => sites.value.length);
const focalPointCount = computed(() => sites.value.filter((site) => site.focal_point).length);
const primarySite = computed(() => sites.value.find((site) => Number(site.id) === Number(props.record?.warehouse_id)));

function addSite() {
  sites.value.push({
    id: null,
    name: "",
    code: "",
    description: "",
    email: "",
    invoicing_email: "",
    primary_phone: "",
    alternative_phone: "",
    nif: "",
    address: "",
    municipality: "",
    province: "",
    focal_point: "",
    focal_point_email: "",
    focal_point_contact: "",
    customer_id: { value: props.record.id, label: props.record.name },
  });
}

function removeUnsavedSite(index) {
  sites.value.splice(index, 1);
}

function submit() {
  form.put(route("customers.update", { customer: props.record.id }), {
    preserveScroll: true,
    preserveState: false,
  });
}
</script>

<template>
  <div class="pl-page space-y-6">
    <PageHeader :trail="[{ title: 'Clientes', url: route('customers.index') }, { title: `Editar ${form.name}` }]" :title="`Editar ${form.name}`" lede="Mantenha a identidade da conta e os locais operacionais usados na cadeia de amostras e facturação.">
      <template #badges>
        <span v-if="form.code" class="ds-chip font-mono">{{ form.code }}</span>
        <span class="ds-chip">{{ form.category_id?.label || "Sem categoria" }}</span>
      </template>
      <template #actions>
        <Link :href="route('customers.show', { customer: record.id })" class="ds-button ds-button-secondary">
          <EyeIcon class="h-4 w-4" />
          Ver dossier
        </Link>
        <button type="button" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty" @click="submit">
          {{ form.processing ? "A guardar..." : "Guardar cliente" }}
        </button>
      </template>
    </PageHeader>

    <dl class="pl-cells">
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Locais</dt>
        <dd class="pl-cell-value">{{ siteCount }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Local principal</dt>
        <dd class="pl-cell-text">{{ primarySite?.name || primarySite?.code || "Por definir" }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Pontos focais</dt>
        <dd class="pl-cell-value">{{ focalPointCount }}</dd>
      </div>
    </dl>

    <form class="ds-card overflow-hidden" @submit.prevent="submit">
      <CustomerForm :form="form" />
      <footer class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
        <Link :href="route('customers.show', { customer: record.id })" class="ds-button ds-button-secondary">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
          {{ form.processing ? "A guardar..." : "Guardar cliente" }}
        </button>
      </footer>
    </form>

    <section>
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p class="ds-kicker">Rede operacional</p>
          <h2 class="ds-heading mt-1 text-xl">Locais e contactos</h2>
          <p class="ds-copy mt-1 max-w-3xl text-sm">Cada local mantém a morada, canais de comunicação, NIF e ponto focal usados nos fluxos do laboratório.</p>
        </div>
        <button type="button" class="ds-button ds-button-secondary" @click="addSite">
          <PlusIcon class="h-4 w-4" />
          Adicionar local
        </button>
      </div>

      <div v-if="sites.length" class="space-y-4">
        <WarehouseComponent
          v-for="(site, index) in sites"
          :key="site.id || `new-${index}`"
          :record="site"
          :primary_warehouse="record.warehouse_id"
          @removed-from-array="removeUnsavedSite(index)"
        />
      </div>

      <div v-else class="ds-empty-state py-12 text-center">
        <MapPinIcon class="mx-auto h-9 w-9 text-[var(--ds-text-soft)]" />
        <h3 class="mt-3 text-sm font-bold text-[var(--ds-text)]">Nenhum local registado</h3>
        <p class="ds-copy mx-auto mt-1 max-w-md text-sm">Adicione o primeiro endereço operacional para completar a conta.</p>
        <button type="button" class="ds-button ds-button-primary mt-5" @click="addSite">
          <PlusIcon class="h-4 w-4" />
          Adicionar primeiro local
        </button>
      </div>
    </section>
  </div>
</template>
