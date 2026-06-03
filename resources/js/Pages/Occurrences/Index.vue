<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import RecordsTable from "@/Components/records-table.vue";
import confirmDialog from "@/Components/confirm-dialog.vue";
import { TransitionRoot } from "@headlessui/vue";
import slideOver from "@/Components/slide-over.vue";
import { ref, computed } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import combobox from "@/Components/combobox.vue";
import datePicker from "@/Components/date-picker.vue";
import { EyeIcon } from "@heroicons/vue/24/outline";
import occurrenceImportForm from "@/Pages/Occurrences/occurrences-import-form.vue";

const props = defineProps({
  record: Object,
  fields: Array,
  model: String,
  abilities: Array,
  query: Object,
  slideOverEdit: {
    type: Boolean,
    default: false,
  },
});

defineOptions({
  layout: Layout,
});

let form = useForm({
  date_reported: null,
  issue_description: "",
  corrective_action: "",
  date_resolved: null,
  notification_date: null,
  client_process_open_notification_date: null,
  analysis: "",
  has_risk_correction_budget: false,
  reason_for_no_risk_correction_budget: "",
  has_non_conformity_terms: false,
  effect_corrective_actions: "",
  cause_corrective_actions: "",
  implementation_date: null,
  update_risk_matrix: false,
  client_process_close_notification_date: null,
  client_acceptance: false,
  client_acceptance_comments: "",
  date_closed: null,
  obs: "",
  was_effective: false,
  status_id: {
    value: null,
    label: null,
  },
  responsible_name: "",
  department_id: "",
  user_id: "",
  origin_id: "",
  category_id: "",
 
  id: null,
});

const actionId = ref(null);

const masks = ref({
  modelValue: "YYYY-MM-DD",
  data: "YYYY-MM-DD",
});

const slideOverDescription = computed(() => {
  return !form.id
    ? trans("gestlab.slideover.creating.description") + form.category_id?.label
    : trans("gestlab.slideover.updating.description") + form.category_id?.label;
});

const slideOverTitle = computed(() => {
  return !form.id
    ? trans("gestlab.slideover.creating.title")
    : trans("gestlab.slideover.updating.description");
});

const confirmationDialogTitle = computed(() => {
  return trans("gestlab.actions.confirmation_dialog_title." + actionId.value);
});

const confirmationDialogDescription = computed(() => {
  return trans(
    "gestlab.actions.confirmation_dialog_description." + actionId.value,
  );
});

const openslideover = ref(false);

let actions = [
  {
    id: null,
    label: "gestlab.actions.bulk_actions_text",
  },
  {
    id: "delete",
    label: "gestlab.actions.delete",
  },
  {
    id: "restore",
    label: "gestlab.actions.restore",
  },
];

const close = () => {
  openslideover.value = false;
  form.clearErrors();
  // form.reset();
};

const showDeleteConfirmation = ref(false);
const showDeleteConfirmationSlideover = ref(false);

const openSlideoverWithData = (data) => {
  openslideover.value = true;
  form.id = data.id;
  form.date_reported = data.date_reported;
  form.issue_description = data.issue_description;
  form.corrective_action = data.corrective_action;
  form.date_resolved = data.date_resolved;
  form.notification_date = data.notification_date;
  form.client_process_open_notification_date = data.client_process_open_notification_date;
  form.analysis = data.analysis;
  form.has_risk_correction_budget = data.has_risk_correction_budget;
  form.has_non_conformity_terms = data.has_non_conformity_terms;
  form.effect_corrective_actions = data.effect_corrective_actions;
  form.cause_corrective_actions = data.cause_corrective_actions;
  form.implementation_date = data.implementation_date;
  form.update_risk_matrix = data.update_risk_matrix;
  form.client_process_close_notification_date = data.client_process_close_notification_date;
  form.client_acceptance = data.client_acceptance;
  form.client_acceptance_comments = data.client_acceptance_comments;
  form.date_closed = data.date_closed;
  form.obs = data.obs;
  form.was_effective = data.was_effective;
  form.status_id = {
    value: data.status_id,
    label: data.status,
  };
  form.responsible_name = data.responsible_name;
  form.department_id = {
    value: data.department_id,
    label: data.department,
  };
  form.user_id = {
    value: data.user_id,
    label: data.user,
  };
  form.origin_id = {
    value: data.origin_id,
    label: data.origin,
  };
  form.category_id = {
    value: data.category_id,
    label: data.category,
  };
};

let submit = () => {
  if (!form.id) {
    form.post(route("occurrences.store"), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        openslideover.value = false;
        form.reset();
      },
    });
  } else {
    form.put(route("occurrences.update", { occurrence: form.id }), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        openslideover.value = false;
        form.reset();
      },
    });
  }
};

const confirmAction = () => {
  executeAction(actionId.value);
};

const handleCreateRecord = () => {
  router.get(route("occurrences.create"), {
    preserveState: false,
    preserveScroll: true,
    onSuccess: () => {
    },
  });
};

const executeAction = (actionId) => {
  const recordIds = props.record.data
    .filter((record) => record.selected)
    .map((record) => record.id);

  if (!recordIds.length) return;

  switch (actionId) {
    case "delete":
      router.get(
        route("occurrences.destroy"),
        {
          recordIds: recordIds,
        },
        {
          preserveState: false,
          preserveScroll: true,
          onSuccess: () => {
            showDeleteConfirmation.value = false;
            actionId = null;
          },
        },
      );
      showDeleteConfirmation.value = false;
      break;

    case "restore":
      router.get(
        route("occurrences.restore"),
        {
          recordIds: recordIds,
        },
        {
          preserveState: false,
          preserveScroll: true,
          onSuccess: () => {
            showDeleteConfirmation.value = false;
            actionId = null;
          },
        },
      );
      showDeleteConfirmation.value = false;
  }
};

function loadUsers(query, setOptions) {
  fetch("/users/getUser?q=" + query)
    .then((response) => response.json())
    .then((results) => {
      setOptions(
        results.map((result) => {
          return {
            value: result.id,
            label: result.name,
          };
        }),
      );
    });
}

function loadStatuses(query, setOptions) {
  fetch("/occurrencestatuses/getOccurrenceStatus?q=" + query)
    .then((response) => response.json())
    .then((results) => {
      setOptions(
        results.map((result) => {
          return {
            value: result.id,
            label: result.name,
          };
        }),
      );
    });
}

function loadOrigins(query, setOptions) {
  fetch("/occurrenceorigins/getOccurrenceOrigin?q=" + query)
  .then((response) => response.json())
  .then((results) => {
    setOptions(
      results.map((result) => {
        return {
          value: result.id,
          label: result.name,
        };
      }),
    );
  });
}

function loadDepartments(query, setOptions) {
  fetch("/departments/getDepartment?q=" + query)
  .then((response) => response.json())
  .then((results) => {
    setOptions(
      results.map((result) => {
        return {
          value: result.id,
          label: result.name,
        };
      }),
    );
  });
}

function loadCategories(query, setOptions) {
  fetch("/occurrencecategories/getOccurrenceCategory?q=" + query)
    .then((response) => response.json())
    .then((results) => {
      setOptions(
        results.map((result) => {
          return {
            value: result.id,
            label: result.name,
          };
        }),
      );
    });
}


</script>
<template>
  <div class="border-b border-gray-200 pb-5" :class="commercialDocumentThemeClasses">
    <h3 class="text-base font-semibold leading-6 text-gray-900">
      {{ $t("gestlab.general.labels.occurrences.page_title") }}
    </h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500"></p>
  </div>

  <occurrence-import-form />

  <records-table
    :record="props.record"
    :model="props.model"
    :abilities="props.abilities"
    :fields="props.fields"
    :slideOverEdit="props.slideOverEdit"
    :query="props.query"
    :actions="actions"
    @execute-action="
      ($event) => {
        showDeleteConfirmation = true;
        actionId = $event;
      }
    "
    @create-record="handleCreateRecord"
    @slideover-on="openSlideoverWithData"
  >
  <template #actions="{ id, data }">
      <Link
                    :href="route('occurrences.show', {occurrence: id})"
                    class="text-ft-gray hover:text-blue-900 transform transition-all duration-200 hover:scale-150"
                    :class="[data?.implementation_date_overdue ? 'text-red-600' : '']"
                  >
                  <EyeIcon class="h-4 w-4" />
            </Link>
    </template>
</records-table>
  <br />

  <slide-over
    v-if="openslideover"
    :class="commercialDocumentThemeClasses"
    @close="close"
    :title="slideOverTitle"
    :description="slideOverDescription"
  >
    <template #content>
      <div
        class="space-y-6 py-6 sm:space-y-0 sm:divide-y sm:divide-gray-200 sm:py-0"
      >

      <!-- Date Reported -->
      <div
            class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
          >
            <div>
              <label
                for="date_reported"
                class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                >{{ $t("gestlab.general.labels.occurrences.date_reported") }}</label
              >
            </div>
            <div class="sm:col-span-2">
              <date-picker
                class="py-1.5"
                v-model.string="form.date_reported"
                locale="pt"
                color="blue"
                mode="date"
                :input-debounce="500"
                @update:model-value="(e) => form.date_reported = e"
                :masks="masks"
              />
              <p
                v-if="form.errors.date_reported"
                class="mt-2 text-sm text-red-600"
                id="date_reported-error"
              >
                {{ form.errors.date_reported }}
              </p>
            </div>
          </div>
          
        <!-- Categoria -->
        <div
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="category_id"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.occurrences.category_id") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <combobox
              :hasError="form.errors.category_id"
              v-model="form.category_id"
              :load-options="loadCategories"
            />
            <p
              v-if="form.errors.category_id"
              class="mt-2 text-sm text-red-600"
              id="name-error"
            >
              {{ form.errors.category_id }}
            </p>
          </div>
        </div>

        <!-- Origin -->
        <div
            class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
          >
            <div>
              <label
                for="origin_id"
                class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                >{{ $t("gestlab.general.labels.occurrences.origin_id") }}</label
              >
            </div>
            <div class="sm:col-span-2">
              <combobox
                :hasError="form.errors.origin_id"
                v-model="form.origin_id"
                :load-options="loadOrigins"
              />
              <p
                v-if="form.errors.origin_id"
                class="mt-2 text-sm text-red-600"
                id="name-error"
              >
                {{ form.errors.origin_id }}
              </p>
            </div>
          </div>

          <!-- Issue Description -->
            <div
              class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
            >
              <div>
                <label
                  for="issue_description"
                  class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                  >{{ $t("gestlab.general.labels.occurrences.issue_description") }}</label
                >
              </div>
              <div class="sm:col-span-2">
                <textarea
                  v-model="form.issue_description"
                  name="issue_description"
                  id="issue_description"
                  class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                  :class="[
                    form.errors.issue_description
                      ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                      : '',
                  ]"
                />
                <p
                  v-if="form.errors.issue_description"
                  class="mt-2 text-sm text-red-600"
                  id="issue_description-error"
                >
                  {{ form.errors.issue_description }}
                </p>
              </div>
            </div>

            <!-- Department -->
            <div
                    class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
                  >
                    <div>
                      <label
                        for="department_id"
                        class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                        >{{ $t("gestlab.general.labels.occurrences.department_id") }}</label
                      >
                    </div>
                    <div class="sm:col-span-2">
                      <combobox
                        :hasError="form.errors.department_id"
                        v-model="form.department_id"
                        :load-options="loadDepartments"
                      />
                      <p
                        v-if="form.errors.department_id"
                        class="mt-2 text-sm text-red-600"
                        id="name-error"
                      >
                        {{ form.errors.department_id }}
                      </p>
                    </div>
                  </div>

            <!-- Notification Date -->
            <div
                class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
              >
                <div>
                  <label
                    for="notification_date"
                    class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                    >{{ $t("gestlab.general.labels.occurrences.notification_date") }}</label
                  >
                </div>
                <div class="sm:col-span-2">
                  <date-picker
                    class="py-1.5"
                    v-model.string="form.notification_date"
                    locale="pt"
                    color="blue"
                    mode="date"
                    :input-debounce="500"
                    @update:model-value="(e) => form.notification_date = e"
                    :masks="masks"
                  />
                  <p
                    v-if="form.errors.notification_date"
                    class="mt-2 text-sm text-red-600"
                    id="notification_date-error"
                  >
                    {{ form.errors.notification_date }}
                  </p>
                </div>
              </div>
              
              
              <!-- Client Process Open Notification Date -->
              <div v-if="form.category_id?.value === 3"
                class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
              >
                <div>
                  <label
                    for="client_process_open_notification_date"
                    class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                    >{{ $t("gestlab.general.labels.occurrences.client_process_open_notification_date") }}</label
                  >
                </div>
                <div class="sm:col-span-2">
                  <date-picker
                    class="py-1.5"
                    v-model.string="form.client_process_open_notification_date"
                    locale="pt"
                    color="blue"
                    mode="date"
                    :input-debounce="500"
                    @update:model-value="(e) => form.client_process_open_notification_date = e"
                    :masks="masks"
                  />
                  <p
                    v-if="form.errors.client_process_open_notification_date"
                    class="mt-2 text-sm text-red-600"
                    id="client_process_open_notification_date-error"
                  >
                    {{ form.errors.client_process_open_notification_date }}
                  </p>
                </div>
              </div>

              <!-- Analysis -->
              <div
                class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
              >
                <div>
                  <label
                    for="analysis"
                    class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                    >{{ $t("gestlab.general.labels.occurrences.analysis") }}</label
                  >
                </div>
                <div class="sm:col-span-2">
                  <textarea
                    v-model="form.analysis"
                    name="analysis"
                    id="analysis"
                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                    :class="[
                      form.errors.analysis
                        ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                        : '',
                    ]"
                  />
                  <p
                    v-if="form.errors.analysis"
                    class="mt-2 text-sm text-red-600"
                    id="analysis-error"
                  >
                    {{ form.errors.analysis }}
                  </p>
                </div>
              </div>

              <!-- Has Risk Correction Budget -->
              <div
                class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
              >
                <div>
                  <label
                    for="has_risk_correction_budget"
                    class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                    >{{ $t("gestlab.general.labels.occurrences.has_risk_correction_budget") }}</label
                  >
                </div>
                <div class="sm:col-span-2">
                  <input
                    v-model="form.has_risk_correction_budget"
                    type="checkbox"
                    name="has_risk_correction_budget"
                    id="has_risk_correction_budget"
                    class="h-5 w-5 rounded-full border-0 py-1.5 text-blue-900 shadow-sm ring-1 ring-inset ring-blue-900 placeholder:text-blue-900 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                    :class="[
                      form.errors.has_risk_correction_budget
                        ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                        : '',
                    ]"
                  />
                  <small class="text-sm text-gray-500 ml-2"> {{ form.has_risk_correction_budget ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}</small>
                  <p
                    v-if="form.errors.has_risk_correction_budget"
                    class="mt-2 text-sm text-red-600"
                    id="has_risk_correction_budget-error"
                  >
                    {{ form.errors.has_risk_correction_budget }}
                  </p>
                </div>
              </div>

            <!-- Reason For No Risk Correction Budget -->
              <div v-if="!form.has_risk_correction_budget"
                class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
              >
                <div>
                  <label
                    for="reason_for_no_risk_correction_budget"
                    class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                    >{{ $t("gestlab.general.labels.occurrences.reason_for_no_risk_correction_budget") }}</label
                  >
                </div>
                <div class="sm:col-span-2">
                  <textarea
                    v-model="form.reason_for_no_risk_correction_budget"
                    name="reason_for_no_risk_correction_budget"
                    id="reason_for_no_risk_correction_budget"
                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                    :class="[
                      form.errors.reason_for_no_risk_correction_budget
                        ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                        : '',
                    ]"
                  />
                  <p
                    v-if="form.errors.reason_for_no_risk_correction_budget"
                    class="mt-2 text-sm text-red-600"
                    id="reason_for_no_risk_correction_budget-error"
                  >
                    {{ form.errors.reason_for_no_risk_correction_budget }}
                  </p>
                </div>
              </div>

              <!-- Has Non Conformity Terms -->
              <div
                class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
              >
                <div>
                  <label
                    for="has_non_conformity_terms"
                    class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                    >{{ $t("gestlab.general.labels.occurrences.has_non_conformity_terms") }}</label
                  >
                </div>
                <div class="sm:col-span-2">
                  <input
                    v-model="form.has_non_conformity_terms"
                    type="checkbox"
                    name="has_non_conformity_terms"
                    id="has_non_conformity_terms"
                    class="h-5 w-5 rounded-full border-0 py-1.5 text-blue-900 shadow-sm ring-1 ring-inset ring-blue-900 placeholder:text-blue-900 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                    :class="[
                      form.errors.has_non_conformity_terms
                        ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                        : '',
                    ]"
                  />
                  <small class="text-sm text-gray-500 ml-2"> {{ form.has_non_conformity_terms ? $t('gestlab.general.buttons.yes') + ' (basear as ações no nível de risco).' : $t('gestlab.general.buttons.no') }}</small>
                  <p
                    v-if="form.errors.has_non_conformity_terms"
                    class="mt-2 text-sm text-red-600"
                    id="has_non_conformity_terms-error"
                  >
                    {{ form.errors.has_non_conformity_terms }}
                  </p>
                </div>
              </div>

              <!-- Effect Corrective Actions -->
              <!-- <div
                class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
              >
                <div>
                  <label
                    for="effect_corrective_actions"
                    class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                    >{{ $t("gestlab.general.labels.occurrences.effect_corrective_actions") }}</label
                  >
                </div>
                <div class="sm:col-span-2">
                  <textarea
                    v-model="form.effect_corrective_actions"
                    name="effect_corrective_actions"
                    id="effect_corrective_actions"
                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                    :class="[
                      form.errors.effect_corrective_actions
                        ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                        : '',
                    ]"
                  />
                  <p
                    v-if="form.errors.effect_corrective_actions"
                    class="mt-2 text-sm text-red-600"
                    id="effect_corrective_actions-error"
                  >
                    {{ form.errors.effect_corrective_actions }}
                  </p>
                </div>
              </div> -->

            <!-- Date Resolved -->
              <!-- <div
                class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
              >
                <div>
                  <label
                    for="date_resolved"
                    class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                    >{{ $t("gestlab.general.labels.occurrences.date_resolved") }}</label
                  >
                </div>
                <div class="sm:col-span-2">
                  <date-picker
                    class="py-1.5"
                    v-model.string="form.date_resolved"
                    locale="pt"
                    color="blue"
                    mode="date"
                    :input-debounce="500"
                    @update:model-value="(e) => form.date_resolved = e"
                    :masks="masks"
                  />
                  <p
                    v-if="form.errors.date_resolved"
                    class="mt-2 text-sm text-red-600"
                    id="date_resolved-error"
                  >
                    {{ form.errors.date_resolved }}
                  </p>
                </div>
              </div> -->

            <!-- Corrective Action -->
            <div
                class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
              >
                <div>
                  <label
                    for="effect_corrective_actions"
                    class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                    >{{ $t("gestlab.general.labels.occurrences.effect_corrective_actions") }}</label
                  >
                </div>
                <div class="sm:col-span-2">
                  <textarea
                    v-model="form.effect_corrective_actions"
                    name="effect_corrective_actions"
                    id="effect_corrective_actions"
                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                    :class="[
                      form.errors.effect_corrective_actions
                        ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                        : '',
                    ]"
                  />
                  <p
                    v-if="form.errors.effect_corrective_actions"
                    class="mt-2 text-sm text-red-600"
                    id="effect_corrective_actions-error"
                  >
                    {{ form.errors.effect_corrective_actions }}
                  </p>
                </div>
              </div>

            <!-- Cause Corrective Actions -->
              <div
                class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
              >
                <div>
                  <label
                    for="cause_corrective_actions"
                    class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                    >{{ $t("gestlab.general.labels.occurrences.cause_corrective_actions") }}</label
                  >
                </div>
                <div class="sm:col-span-2">
                  <textarea
                    v-model="form.cause_corrective_actions"
                    name="cause_corrective_actions"
                    id="cause_corrective_actions"
                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                    :class="[
                      form.errors.cause_corrective_actions
                        ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                        : '',
                    ]"
                  />
                  <p
                    v-if="form.errors.cause_corrective_actions"
                    class="mt-2 text-sm text-red-600"
                    id="cause_corrective_actions-error"
                  >
                    {{ form.errors.cause_corrective_actions }}
                  </p>
                </div>
              </div>

            <!-- Implementation Date -->
              <div
                class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
              >
                <div>
                  <label
                    for="implementation_date"
                    class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                    >{{ $t("gestlab.general.labels.occurrences.implementation_date") }}</label
                  >
                </div>
                <div class="sm:col-span-2">
                  <date-picker
                    class="py-1.5"
                    v-model.string="form.implementation_date"
                    locale="pt"
                    color="blue"
                    mode="date"
                    :input-debounce="500"
                    @update:model-value="(e) => form.implementation_date = e"
                    :masks="masks"
                  />
                  <p
                    v-if="form.errors.implementation_date"
                    class="mt-2 text-sm text-red-600"
                    id="implementation_date-error"
                  >
                    {{ form.errors.implementation_date }}
                  </p>
                </div>
              </div>

              <!-- User -->
              <!-- <div
                      class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
                    >
                      <div>
                        <label
                          for="user_id"
                          class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                          >{{ $t("gestlab.general.labels.occurrences.user_id") }}</label
                        >
                      </div>
                      <div class="sm:col-span-2">
                        <combobox
                          :hasError="form.errors.user_id"
                          v-model="form.user_id"
                          :load-options="loadUsers"
                        />
                        <p
                          v-if="form.errors.user_id"
                          class="mt-2 text-sm text-red-600"
                          id="name-error"
                        >
                          {{ form.errors.user_id }}
                        </p>
                      </div>
                    </div> -->
            
            <!-- Responsible Name -->
            <div
                  class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
                >
                  <div>
                    <label
                      for="responsible_name"
                      class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                      >{{ $t("gestlab.general.labels.occurrences.responsible_name") }}</label
                    >
                  </div>
                  <div class="sm:col-span-2">
                    <input
                      v-model="form.responsible_name"
                      type="text"
                      name="responsible_name"
                      id="responsible_name"
                      class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                      :class="[
                        form.errors.responsible_name
                          ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                          : '',
                      ]"
                    />
                    <p
                      v-if="form.errors.responsible_name"
                      class="mt-2 text-sm text-red-600"
                      id="responsible_name-error"
                    >
                      {{ form.errors.responsible_name }}
                    </p>
                  </div>
                </div>        

            <!-- Update Risk Matrix -->
              <div
                class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
              >
                <div>
                  <label
                    for="update_risk_matrix"
                    class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                    >{{ $t("gestlab.general.labels.occurrences.update_risk_matrix") }}</label
                  >
                </div>
                <div class="sm:col-span-2">
                  <input
                    v-model="form.update_risk_matrix"
                    type="checkbox"
                    name="update_risk_matrix"
                    id="update_risk_matrix"
                    class="h-5 w-5 rounded-full border-0 py-1.5 text-blue-900 shadow-sm ring-1 ring-inset ring-blue-900 placeholder:text-blue-900 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                    :class="[
                      form.errors.update_risk_matrix
                        ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                        : '',
                    ]"
                  />
                  <small class="text-sm text-gray-500 ml-2"> {{ form.update_risk_matrix ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}</small>
                  <p
                    v-if="form.errors.update_risk_matrix"
                    class="mt-2 text-sm text-red-600"
                    id="update_risk_matrix-error"
                  >
                    {{ form.errors.update_risk_matrix }}
                  </p>
                </div>
              </div>

            <!-- Client Process Close Notification Date -->
              <div v-if="form.category_id?.value === 3"
                class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
              >
                <div>
                  <label
                    for="client_process_close_notification_date"
                    class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                    >{{ $t("gestlab.general.labels.occurrences.client_process_close_notification_date") }}</label
                  >
                </div>
                <div class="sm:col-span-2">
                  <date-picker
                    class="py-1.5"
                    v-model.string="form.client_process_close_notification_date"
                    locale="pt"
                    color="blue"
                    mode="date"
                    :input-debounce="500"
                    @update:model-value="(e) => form.client_process_close_notification_date = e"
                    :masks="masks"
                  />
                  <p
                    v-if="form.errors.client_process_close_notification_date"
                    class="mt-2 text-sm text-red-600"
                    id="client_process_close_notification_date-error"
                  >
                    {{ form.errors.client_process_close_notification_date }}
                  </p>
                </div>
              </div>

            <!-- Client Acceptance -->
              <div v-if="form.category_id?.value === 3"
                class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
              >
                <div>
                  <label
                    for="client_acceptance"
                    class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                    >{{ $t("gestlab.general.labels.occurrences.client_acceptance") }}</label
                  >
                </div>
                <div class="sm:col-span-2">
                  <input
                    v-model="form.client_acceptance"
                    type="checkbox"
                    name="client_acceptance"
                    id="client_acceptance"
                    class="h-5 w-5 rounded-full border-0 py-1.5 text-blue-900 shadow-sm ring-1 ring-inset ring-blue-900 placeholder:text-blue-900 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                    :class="[
                      form.errors.client_acceptance
                        ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                        : '',
                    ]"
                  />
                  <small class="text-sm text-gray-500 ml-2"> {{ form.client_acceptance ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}</small>
                  <p
                    v-if="form.errors.client_acceptance"
                    class="mt-2 text-sm text-red-600"
                    id="client_acceptance-error"
                  >
                    {{ form.errors.client_acceptance }}
                  </p>
                </div>
              </div>

            <!-- Status   -->
              <div
                class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
              >
                <div>
                  <label
                    for="status_id"
                    class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                    >{{ $t("gestlab.general.labels.occurrences.status_id") }}</label
                  >
                </div>
                <div class="sm:col-span-2">
                  <combobox :hasError="form.errors.status_id" v-model="form.status_id" :load-options="loadStatuses"/>
                  <p v-if="form.errors.status_id" class="mt-2 text-sm text-red-600" id="status_id-error">{{ form.errors.status_id }}</p>
                </div>
              </div>

            <!-- Date Closed -->
              <div
                class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
              >
                <div>
                  <label
                    for="date_closed"
                    class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                    >{{ $t("gestlab.general.labels.occurrences.date_closed") }}</label
                  >
                </div>
                <div class="sm:col-span-2">
                  <date-picker
                    class="py-1.5"
                    v-model.string="form.date_closed"
                    locale="pt"
                    color="blue"
                    mode="date"
                    :input-debounce="500"
                    @update:model-value="(e) => form.date_closed = e"
                    :masks="masks"
                  />
                  <p
                    v-if="form.errors.date_closed"
                    class="mt-2 text-sm text-red-600"
                    id="date_closed-error"
                  >
                    {{ form.errors.date_closed }}
                  </p>
                </div>
              </div>

              <!-- Was Effective -->
              <div
                class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
              >
                <div>
                  <label
                    for="was_effective"
                    class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                    >{{ $t("gestlab.general.labels.occurrences.was_effective") }}</label
                  >
                </div>
                <div class="sm:col-span-2">
                  <input
                    v-model="form.was_effective"
                    type="checkbox"
                    name="was_effective"
                    id="was_effective"
                    class="h-5 w-5 rounded-full border-0 py-1.5 text-blue-900 shadow-sm ring-1 ring-inset ring-blue-900 placeholder:text-blue-900 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                    :class="[
                      form.errors.was_effective
                        ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                        : '',
                    ]"
                  />
                  <small class="text-sm text-gray-500 ml-2"> {{ form.was_effective ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}</small>
                  <p
                    v-if="form.errors.was_effective"
                    class="mt-2 text-sm text-red-600"
                    id="was_effective-error"
                  >
                    {{ form.errors.was_effective }}
                  </p>
                </div>
              </div>

            <!-- Obs -->
              <!-- <div
                class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
              >
                <div>
                  <label
                    for="obs"
                    class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                    >{{ $t("gestlab.general.labels.occurrences.obs") }}</label
                  >
                </div>
                <div class="sm:col-span-2">
                  <textarea
                    v-model="form.obs"
                    name="obs"
                    id="obs"
                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                    :class="[
                      form.errors.obs
                        ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                        : '',
                    ]"
                  />
                  <p
                    v-if="form.errors.obs"
                    class="mt-2 text-sm text-red-600"
                    id="obs-error"
                  >
                    {{ form.errors.obs }}
                  </p>
                </div>
              </div> -->

               <!-- Client Acceptance Comments -->
               <div
                class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
              >
                <div>
                  <label
                    for="client_acceptance_comments"
                    class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                    >{{ $t("gestlab.general.labels.occurrences.client_acceptance_comments") }}</label
                  >
                </div>
                <div class="sm:col-span-2">
                  <textarea
                    v-model="form.client_acceptance_comments"
                    name="client_acceptance_comments"
                    id="client_acceptance_comments"
                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                    :class="[
                      form.errors.client_acceptance_comments
                        ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                        : '',
                    ]"
                  />
                  <p
                    v-if="form.errors.client_acceptance_comments"
                    class="mt-2 text-sm text-red-600"
                    id="client_acceptance_comments-error"
                  >
                    {{ form.errors.client_acceptance_comments }}
                  </p>
                </div>
              </div>

      </div>          
    </template>

    <template #action_buttons>
      <div class="flex justify-end space-x-3">
        <button
          type="button"
          class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
          @click="
            openslideover = false;
            form.reset();
          "
        >
          {{ $t("gestlab.general.buttons.cancel") }}
        </button>
        <!-- <TransitionRoot
            :show="!form.isDirty"
            enter="transition-opacity duration-75"
            enter-from="opacity-0"
            enter-to="opacity-100"
            leave="transition-opacity duration-150"
            leave-from="opacity-100"
            leave-to="opacity-0"
        >
            I will appear and disappear.
        </TransitionRoot> -->
        <button
          v-if="form.isDirty"
          @click="showDeleteConfirmationSlideover = true"
          :disabled="form.processing"
          type="button"
          class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-900"
        >
          {{
            !form.id
              ? $t("gestlab.general.buttons.submit")
              : $t("gestlab.general.buttons.update")
          }}
        </button>
      </div>
    </template>
  </slide-over>

  <confirm-dialog
    @canceled="showDeleteConfirmation = false"
    @close="showDeleteConfirmation = false"
    @confirmed="confirmAction"
    v-if="showDeleteConfirmation"
    :title="confirmationDialogTitle"
    :description="confirmationDialogDescription"
    confirm="Sim"
    cancel="Não"
  />

  <confirm-dialog
    size="sm:max-w-2xl"
    alignment="sm:items-start"
    @canceled="showDeleteConfirmationSlideover = false"
    @close="showDeleteConfirmationSlideover = false"
    @confirmed="submit"
    v-if="showDeleteConfirmationSlideover"
    :title="$t('gestlab.actions.confirmation_dialog_title.default')"
    :description="$t('gestlab.actions.confirmation_dialog_description.default')"
    confirm="Sim"
    cancel="Não"
  >
    <div class="mt-4">
      <div
        class="font-semibold inline-flex px-2 py-1 leading-4 text-xs rounded-full text-white bg-blue-900 sm:text-xs mb-2"
      >
        <p class="text-xs">{{ $t("gestlab.general.labels.summary") }}</p>
      </div>
      <div>
        <div class="px-4 sm:px-0 rounded-full text-white bg-blue-900">
          <!-- <h3 class="text-base font-semibold leading-7 text-gray-900">Resumo</h3>
          <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-500">Personal details and application.</p> -->
        </div>

        <div class="mt-6 border-t border-gray-100">
          <dl class="divide-y divide-gray-100">

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.date_reported") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.date_reported }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.category_id") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.category_id?.label }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.origin_id") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.origin_id?.label }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.issue_description") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.issue_description }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.department_id") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.department_id?.label }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.notification_date") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.notification_date }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="form.category_id?.value === 3">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.client_process_open_notification_date") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.client_process_open_notification_date }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.analysis") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.analysis }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.has_risk_correction_budget") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.has_risk_correction_budget ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="!form.has_risk_correction_budget">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.reason_for_no_risk_correction_budget") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.reason_for_no_risk_correction_budget }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.has_non_conformity_terms") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.has_non_conformity_terms ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.corrective_action") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.corrective_action }}
              </dd>
            </div>

            <!-- <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.date_resolved") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.date_resolved }}
              </dd>
            </div> -->

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.effect_corrective_actions") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.effect_corrective_actions }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.cause_corrective_actions") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.cause_corrective_actions }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.implementation_date") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.implementation_date }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.user_id") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.user_id?.label }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.responsible_name") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.responsible_name }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.update_risk_matrix") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.update_risk_matrix ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="form.category_id?.value === 3">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.client_process_close_notification_date") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.client_process_close_notification_date }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="form.category_id?.value === 3">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.client_acceptance") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.client_acceptance ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.client_acceptance_comments") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.client_acceptance_comments }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.date_closed") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.date_closed }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.was_effective") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.was_effective ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}
              </dd>
            </div>

            <!-- <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.obs") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.obs }}
              </dd>
            </div> -->
            
          </dl>
        </div>
        
      </div>
    </div>
  </confirm-dialog>
</template>
