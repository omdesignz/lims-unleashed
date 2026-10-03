<script setup>
import CustomerForm from "@/Components/customers/CustomerForm.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { ArrowLeft as ArrowLeftIcon, Building2 as BuildingOffice2Icon } from "@lucide/vue";

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
  <form class="space-y-6" @submit.prevent="submit">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <nav aria-label="Breadcrumb">
        <Link :href="route('customers.index')" class="inline-flex items-center gap-1.5 text-xs font-bold text-[var(--ds-text-muted)] hover:text-[rgb(var(--primary-700-rgb))]">
          <ArrowLeftIcon class="h-4 w-4" />
          Clientes
        </Link>
      </nav>

      <div class="mt-5 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex min-w-0 items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <BuildingOffice2Icon class="h-5 w-5" />
          </span>
          <div class="min-w-0">
            <p class="ds-kicker">Nova conta</p>
            <h1 class="ds-heading mt-1 text-2xl">Registar cliente</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">Crie a identidade comercial antes de associar locais, contactos, propostas e amostras.</p>
          </div>
        </div>

        <div class="flex flex-wrap gap-2 lg:justify-end">
          <Link :href="route('customers.index')" class="ds-button ds-button-secondary">Cancelar</Link>
          <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
            {{ form.processing ? "A criar..." : "Criar cliente" }}
          </button>
        </div>
      </div>
    </section>

    <section class="ds-card overflow-hidden">
      <CustomerForm :form="form" />
      <footer class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
        <Link :href="route('customers.index')" class="ds-button ds-button-secondary">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
          {{ form.processing ? "A criar..." : "Criar cliente" }}
        </button>
      </footer>
    </section>
  </form>
</template>
