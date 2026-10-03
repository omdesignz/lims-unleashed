<script setup>
import ContractGuideForm from "@/Pages/ContractGuides/ContractGuideForm.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { ArrowLeft as ArrowLeftIcon, ClipboardCheck as ClipboardDocumentCheckIcon } from "@lucide/vue";
import { Link, useForm } from "@inertiajs/vue3";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, required: true },
});
const guide = props.record?.data ?? props.record;

function normalizedDate(value) {
  return value ? String(value).slice(0, 10) : null;
}

const form = useForm("ContractGuideEdit", {
  id: guide.id,
  obs: guide.obs ?? "",
  guide_no: guide.guide_no ?? "",
  ref_no: guide.ref_no ?? "",
  entry_point: guide.entry_point ?? "",
  collection_point: guide.collection_point ?? "",
  du_no: guide.du_no ?? "",
  nif: guide.nif ?? "",
  contact: guide.contact ?? "",
  email: guide.email ?? "",
  bl: guide.bl ?? "",
  date: normalizedDate(guide.date),
  customer_id: guide.customer_id ?? null,
  warehouse_id: guide.warehouse_id ?? null,
  collection_id: guide.collection_id ?? null,
  items: (guide.items ?? []).map((item) => ({
    ...item,
    date: normalizedDate(item.date),
  })),
});

if (!form.items.length) {
  form.items.push({
    id: null,
    guide_id: guide.id,
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
  });
}

function submit() {
  form.put(route("contractguides.update", { guide: guide.id }), {
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
          <ClipboardDocumentCheckIcon class="h-5 w-5" />
        </span>
        <div>
          <p class="ds-kicker">Controlo de circulação</p>
          <h1 class="ds-heading mt-1 text-2xl">Editar guia {{ guide.guide_no }}</h1>
          <p class="ds-copy mt-1 max-w-3xl text-sm">Actualize a guia e os produtos numa única transacção rastreável.</p>
        </div>
      </div>
    </section>

    <ContractGuideForm :form="form" :guide-number="guide.guide_no" submit-label="Guardar alterações" />
  </form>
</template>
