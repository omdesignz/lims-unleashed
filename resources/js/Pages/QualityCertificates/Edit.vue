<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import NextStepBar from "@/Components/plano/NextStepBar.vue";
import { computed } from "vue";
import { Link, useForm } from "@inertiajs/vue3";

defineOptions({ layout: Layout });

/**
 * A certificate's identity (customer, site, laboratory code, product) comes from the
 * accession and stays fixed. Until validation only the observation is edited; after
 * validation corrections go through the ISO revision workflow.
 */
const props = defineProps({
  record: { type: Object, required: true },
});

const certificate = computed(() => props.record?.data ?? props.record ?? {});
const form = useForm({ obs: certificate.value.obs ?? "" });

const identity = computed(() => [
  ["Boletim", certificate.value.code || `#${certificate.value.id}`],
  ["Código laboratorial", certificate.value.lab_code || "—"],
  ["Cliente", certificate.value.customer || "—"],
  ["Local", certificate.value.warehouse || "—"],
  ["Produto", certificate.value.product || "—"],
]);

function submit() {
  form.put(route("qualitycertificates.update", { certificate: certificate.value.id }), { preserveScroll: true });
}
</script>

<template>
  <form class="pl-page" data-template="form" @submit.prevent="submit">
    <PageHeader
      :crumbs="[{ title: 'Certificados', url: route('qualitycertificates.index') }, { title: certificate.code || `#${certificate.id}`, url: route('qualitycertificates.show', certificate.id) }, { title: 'Observação' }]"
      title="Observação do boletim"
      lede="A identidade do boletim vem da recepção da amostra e não se altera aqui. Depois de validado, as correcções seguem o fluxo de revisão ISO."
    />

    <section class="pl-form-section">
      <header><h2 class="pl-d3">Identificação</h2><p>Registo laboratorial que dá origem ao boletim.</p></header>
      <dl class="pl-panel pl-facts">
        <div v-for="[label, value] in identity" :key="label" class="pl-fact"><dt>{{ label }}</dt><dd>{{ value }}</dd></div>
      </dl>
    </section>

    <section class="pl-form-section">
      <header><h2 class="pl-d3">Observação</h2><p>Texto que acompanha o boletim emitido.</p></header>
      <div class="ds-field-group">
        <label for="certificate-obs" class="ds-field-label">Observação</label>
        <textarea id="certificate-obs" v-model="form.obs" class="ds-field" rows="6" maxlength="5000" :aria-invalid="Boolean(form.errors.obs)" :aria-describedby="form.errors.obs ? 'certificate-obs-error' : undefined" />
        <p v-if="form.errors.obs" id="certificate-obs-error" class="ds-field-error" role="alert">{{ form.errors.obs }}</p>
      </div>
    </section>

    <NextStepBar>
      Guardar a observação. A validação e assinatura fazem-se no dossier do boletim.
      <template #actions>
        <Link :href="route('qualitycertificates.show', certificate.id)" class="ds-button ds-button-quiet">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">{{ form.processing ? "A guardar…" : "Guardar observação" }}</button>
      </template>
    </NextStepBar>
  </form>
</template>
