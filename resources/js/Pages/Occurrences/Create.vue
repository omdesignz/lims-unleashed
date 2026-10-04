<script setup>
import OccurrenceForm from "@/Pages/Occurrences/OccurrenceForm.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { useForm } from "@inertiajs/vue3";
import { Check as CheckIcon } from "@lucide/vue";
import { computed } from "vue";

defineOptions({ layout: Layout });

const form = useForm({
  date_reported: new Date().toISOString().slice(0, 10),
  issue_description: "",
  corrective_action: "",
  date_resolved: "",
  notification_date: "",
  client_process_open_notification_date: "",
  analysis: "",
  has_risk_correction_budget: false,
  reason_for_no_risk_correction_budget: "",
  has_non_conformity_terms: false,
  effect_corrective_actions: "",
  cause_corrective_actions: "",
  implementation_date: "",
  update_risk_matrix: false,
  client_process_close_notification_date: "",
  client_acceptance: null,
  client_acceptance_comments: "",
  date_closed: "",
  obs: "",
  was_effective: null,
  responsible_name: "",
  status_id: null,
  department_id: null,
  user_id: null,
  origin_id: null,
  category_id: null,
});

const canSubmit = computed(() => form.date_reported && form.issue_description.trim().length > 0);

function submit() {
  if (form.processing || !canSubmit.value) {
    return;
  }

  form.post(route("occurrences.store"), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
}
</script>

<template>
  <form class="pl-page space-y-6" @submit.prevent="submit">
    <PageHeader :trail="[{ title: 'Ocorrências', url: route('occurrences.index') }, { title: 'Nova ocorrência' }]" title="Nova ocorrência" lede="Abra um registo rastreável para triagem, investigação e acção correctiva.">
      <template #actions>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !canSubmit">
          <CheckIcon class="h-4 w-4" />
          {{ form.processing ? "A registar..." : "Registar ocorrência" }}
        </button>
      </template>
    </PageHeader>

    <OccurrenceForm :form="form" />
  </form>
</template>
