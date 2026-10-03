<script setup>
import ContractGuideForm from "@/Pages/ContractGuides/ContractGuideForm.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { ArchiveRestore as ArchiveBoxArrowDownIcon, ArrowLeft as ArrowLeftIcon } from "@lucide/vue";
import { Link, useForm } from "@inertiajs/vue3";

defineOptions({ layout: Layout });

const today = new Date();
const localDate = new Date(today.getTime() - today.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
const form = useForm("ContractGuideCreate", {
  obs: "",
  ref_no: "",
  entry_point: "",
  collection_point: "",
  du_no: "",
  nif: "",
  contact: "",
  email: "",
  bl: "",
  date: localDate,
  customer_id: null,
  warehouse_id: null,
  collection_id: null,
  items: [{
    id: null,
    guide_id: null,
    product_id: null,
    country_id: null,
    manufacturer: "",
    brand: "",
    lot: "",
    du_no: "",
    bl: "",
    collection_id: null,
    obs: "",
    date: null,
  }],
});

function submit() {
  form.post(route("contractguides.store"), {
    preserveScroll: true,
  });
}
</script>

<template>
  <form class="space-y-6" @submit.prevent="submit">
    <section class="ds-panel p-5 sm:p-6">
      <Link :href="route('contractguides.index')" class="ds-button ds-button-ghost -ml-3 w-fit">
        <ArrowLeftIcon class="h-4 w-4" />
        Guias de contrato
      </Link>
      <div class="mt-3 flex items-start gap-3">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
          <ArchiveBoxArrowDownIcon class="h-5 w-5" />
        </span>
        <div>
          <p class="ds-kicker">Controlo de circulação</p>
          <h1 class="ds-heading mt-1 text-2xl">Nova guia de contrato</h1>
          <p class="ds-copy mt-1 max-w-3xl text-sm">Registe destino, referências de transporte e rastreabilidade dos produtos.</p>
        </div>
      </div>
    </section>

    <ContractGuideForm :form="form" submit-label="Criar guia" />
  </form>
</template>
