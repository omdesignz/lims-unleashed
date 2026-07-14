<script setup>
import CustomerRequestForm from "@/Components/customer-requests/CustomerRequestForm.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { ArrowLeftIcon, ClipboardDocumentCheckIcon } from "@heroicons/vue/24/outline";

defineOptions({
  layout: Layout,
});

const props = defineProps({
  record: {
    type: Object,
    required: true,
  },
});

const request = props.record?.data ?? props.record;

const form = useForm({
  email: request.email || "",
  contact: request.contact || "",
  description: request.description || "",
  category_id: request.category_id ? { value: request.category_id, label: request.category } : null,
  customer_id: request.customer_id ? { value: request.customer_id, label: request.customer } : null,
  warehouse_id: request.warehouse_id ? { value: request.warehouse_id, label: request.warehouse } : null,
});

function submit() {
  form.put(route("customerrequests.update", { request: request.id }), {
    preserveScroll: true,
    onSuccess: () => router.visit(route("customerrequests.index")),
  });
}
</script>

<template>
  <form class="space-y-6" @submit.prevent="submit">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <nav aria-label="Breadcrumb" class="mb-5">
        <Link :href="route('customerrequests.index')" class="inline-flex items-center gap-1.5 text-xs font-bold text-[var(--ds-text-muted)] hover:text-[rgb(var(--primary-700-rgb))]">
          <ArrowLeftIcon class="h-4 w-4" />
          Pedidos de clientes
        </Link>
      </nav>

      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex min-w-0 items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <ClipboardDocumentCheckIcon class="h-5 w-5" />
          </span>
          <div class="min-w-0">
            <p class="ds-kicker">Pedido #{{ request.id }}</p>
            <h1 class="ds-heading mt-1 text-2xl">Editar pedido de cliente</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm"> Actualize a classificação, o local e os dados usados no acompanhamento deste pedido. </p>
            <div class="mt-3 flex flex-wrap gap-2">
              <span v-if="request.reference" class="ds-chip">{{ request.reference }}</span>
              <span class="ds-chip">{{ request.status || "pending" }}</span>
              <span v-if="request.customer" class="ds-chip">{{ request.customer }}</span>
            </div>
          </div>
        </div>

        <div class="flex flex-wrap gap-2 lg:justify-end">
          <Link :href="route('customerrequests.index')" class="ds-button ds-button-secondary">Cancelar</Link>
          <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
            {{ form.processing ? "A guardar..." : "Guardar alterações" }}
          </button>
        </div>
      </div>
    </section>

    <section class="ds-card overflow-hidden">
      <CustomerRequestForm :form="form" />
      <footer class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
        <Link :href="route('customerrequests.index')" class="ds-button ds-button-secondary">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
          {{ form.processing ? "A guardar..." : "Guardar alterações" }}
        </button>
      </footer>
    </section>
  </form>
</template>
