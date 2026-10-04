<script setup>
import CustomerForm from "@/Components/customers/CustomerForm.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import NextStepBar from "@/Components/plano/NextStepBar.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, useForm } from "@inertiajs/vue3";

defineOptions({ layout: Layout });

const form = useForm({
  name: "",
  code: "",
  description: "",
  category_id: null,
});

function submit() {
  form.post(route("customers.store"), { preserveScroll: true });
}
</script>

<template>
  <form class="pl-page space-y-6" @submit.prevent="submit">
    <PageHeader :trail="[{ title: 'Clientes', url: route('customers.index') }, { title: 'Registar cliente' }]" title="Registar cliente" lede="Crie a identidade comercial antes de associar locais, contactos, propostas e amostras." />

    <section class="ds-card overflow-hidden">
      <CustomerForm :form="form" />
    </section>
    <NextStepBar>
      {{ form.processing ? 'A guardar…' : form.isDirty ? 'Alterações por guardar.' : 'Sem alterações por guardar.' }}
      <template #actions>
        <Link :href="route('customers.index')" class="ds-button ds-button-secondary">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
          {{ form.processing ? "A criar..." : "Criar cliente" }}
        </button>
      </template>
    </NextStepBar>
  </form>
</template>
