<script setup>
import OccurrenceForm from "@/Pages/Occurrences/OccurrenceForm.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { ArrowLeftIcon, CheckIcon, DocumentMagnifyingGlassIcon, EyeIcon } from "@heroicons/vue/24/outline";
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
  <form class="space-y-6" @submit.prevent="submit">
    <section class="ds-panel p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
          <Link :href="route('occurrences.index')" class="ds-button ds-button-ghost -ml-3 w-fit">
            <ArrowLeftIcon class="h-4 w-4" />
            Ocorrências
          </Link>
          <div class="mt-3 flex items-start gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <DocumentMagnifyingGlassIcon class="h-5 w-5" />
            </span>
            <div>
              <p class="ds-kicker">Dossier {{ occurrence.occurrence_no }}</p>
              <h1 class="ds-heading mt-1 text-2xl">Editar ocorrência</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm">Actualize a investigação, a acção correctiva e a evidência de encerramento.</p>
            </div>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <Link :href="route('occurrences.show', { occurrence: occurrence.id })" class="ds-button ds-button-secondary">
            <EyeIcon class="h-4 w-4" />
            Abrir dossier
          </Link>
          <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty || !canSubmit">
            <CheckIcon class="h-4 w-4" />
            {{ form.processing ? "A guardar..." : "Guardar alterações" }}
          </button>
        </div>
      </div>
    </section>

    <OccurrenceForm :form="form" />
  </form>
</template>
