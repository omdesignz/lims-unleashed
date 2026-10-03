<script setup>
import OccurrenceForm from "@/Pages/Occurrences/OccurrenceForm.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { ArrowLeft as ArrowLeftIcon, Check as CheckIcon, TriangleAlert as ExclamationTriangleIcon } from "@lucide/vue";
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
  <form class="space-y-6" @submit.prevent="submit">
    <section class="ds-panel p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
          <Link :href="route('occurrences.index')" class="ds-button ds-button-ghost -ml-3 w-fit">
            <ArrowLeftIcon class="h-4 w-4" />
            Ocorrências
          </Link>
          <div class="mt-3 flex items-start gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-amber-700 dark:text-amber-300">
              <ExclamationTriangleIcon class="h-5 w-5" />
            </span>
            <div>
              <p class="ds-kicker">Qualidade e conformidade</p>
              <h1 class="ds-heading mt-1 text-2xl">Nova ocorrência</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm">Abra um registo rastreável para triagem, investigação e acção correctiva.</p>
            </div>
          </div>
        </div>

        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !canSubmit">
          <CheckIcon class="h-4 w-4" />
          {{ form.processing ? "A registar..." : "Registar ocorrência" }}
        </button>
      </div>
    </section>

    <OccurrenceForm :form="form" />
  </form>
</template>
