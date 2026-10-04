<script setup>
import OccurrenceForm from "@/Pages/Occurrences/OccurrenceForm.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { Check as CheckIcon, Eye as EyeIcon } from "@lucide/vue";
import { computed } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, required: true },
});

const occurrence = props.record?.data ?? props.record;
const option = (value, label) => value ? { value, label } : null;
const form = useForm({
  id: occurrence.id,
  date_reported: occurrence.date_reported ?? "",
  issue_description: occurrence.issue_description ?? "",
  corrective_action: occurrence.corrective_action ?? "",
  date_resolved: occurrence.date_resolved ?? "",
  notification_date: occurrence.notification_date ?? "",
  client_process_open_notification_date: occurrence.client_process_open_notification_date ?? "",
  analysis: occurrence.analysis ?? "",
  has_risk_correction_budget: Boolean(occurrence.has_risk_correction_budget),
  reason_for_no_risk_correction_budget: occurrence.reason_for_no_risk_correction_budget ?? "",
  has_non_conformity_terms: Boolean(occurrence.has_non_conformity_terms),
  effect_corrective_actions: occurrence.effect_corrective_actions ?? "",
  cause_corrective_actions: occurrence.cause_corrective_actions ?? "",
  implementation_date: occurrence.implementation_date ?? "",
  update_risk_matrix: Boolean(occurrence.update_risk_matrix),
  client_process_close_notification_date: occurrence.client_process_close_notification_date ?? "",
  client_acceptance: occurrence.client_acceptance,
  client_acceptance_comments: occurrence.client_acceptance_comments ?? "",
  date_closed: occurrence.date_closed ?? "",
  obs: occurrence.obs ?? "",
  was_effective: occurrence.was_effective,
  responsible_name: occurrence.responsible_name ?? "",
  status_id: option(occurrence.status_id, occurrence.status),
  department_id: option(occurrence.department_id, occurrence.department),
  user_id: option(occurrence.user_id, occurrence.user),
  origin_id: option(occurrence.origin_id, occurrence.origin),
  category_id: option(occurrence.category_id, occurrence.category),
});

const canSubmit = computed(() => form.date_reported && form.issue_description.trim().length > 0);

function submit() {
  if (form.processing || !form.isDirty || !canSubmit.value) {
    return;
  }

  form.put(route("occurrences.update", { occurrence: occurrence.id }), {
    preserveScroll: true,
    preserveState: "errors",
  });
}
</script>

<template>
  <form class="pl-page space-y-6" @submit.prevent="submit">
    <PageHeader :trail="[{ title: 'Ocorrências', url: route('occurrences.index') }, { title: 'Editar ocorrência' }]" title="Editar ocorrência" lede="Actualize a investigação, a acção correctiva e a evidência de encerramento.">
      <template #actions>
        <Link :href="route('occurrences.show', { occurrence: occurrence.id })" class="ds-button ds-button-secondary">
          <EyeIcon class="h-4 w-4" />
          Abrir dossier
        </Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty || !canSubmit">
          <CheckIcon class="h-4 w-4" />
          {{ form.processing ? "A guardar..." : "Guardar alterações" }}
        </button>
      </template>
    </PageHeader>

    <OccurrenceForm :form="form" />
  </form>
</template>
