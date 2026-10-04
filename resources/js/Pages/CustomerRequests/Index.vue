<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import CustomerRequestForm from "@/Components/customer-requests/CustomerRequestForm.vue";
import RecordsTable from "@/Components/records-table.vue";
import SlideOver from "@/Components/slide-over.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import { router, useForm } from "@inertiajs/vue3";
import {
  Plus as PlusIcon,
} from "@lucide/vue";
import { computed, ref } from "vue";
import { trans } from "laravel-vue-i18n";

defineOptions({
  layout: Layout,
});

const props = defineProps({
  record: {
    type: Object,
    default: () => ({ data: [], meta: {} }),
  },
  fields: {
    type: Array,
    default: () => [],
  },
  model: String,
  abilities: {
    type: Array,
    default: () => [],
  },
  query: {
    type: Object,
    default: () => ({}),
  },
  slideOverEdit: {
    type: Boolean,
    default: false,
  },
});

const { hasPermission } = usePermission();
const isPanelOpen = ref(false);
const showActionConfirmation = ref(false);
const selectedAction = ref(null);

const emptyForm = () => ({
  email: "",
  contact: "",
  description: "",
  category_id: null,
  customer_id: null,
  warehouse_id: null,
  id: null,
});

const form = useForm(emptyForm());
const invitationForm = useForm({ recipient_email: "" });

function issueInvitation() {
  if (invitationForm.processing) return;
  invitationForm.post(route("customerrequests.invitations.store"), {
    preserveScroll: true,
    onSuccess: () => invitationForm.reset(),
  });
}

const panelTitle = computed(() => form.id ? `Editar pedido #${form.id}` : "Novo pedido de cliente");
const panelDescription = computed(() => form.id
  ? "Actualize a classificação e os dados de contacto. O cliente, local e laboratório permanecem fixos."
  : "Registe uma nova necessidade para triagem comercial e laboratorial.");

const confirmationDialogTitle = computed(() => {
  return trans(`gestlab.actions.confirmation_dialog_title.${selectedAction.value}`);
});

const confirmationDialogDescription = computed(() => {
  return trans(`gestlab.actions.confirmation_dialog_description.${selectedAction.value}`);
});

const pageRecords = computed(() => props.record?.data || []);

const actions = [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  { id: "delete", label: "gestlab.actions.delete" },
  { id: "restore", label: "gestlab.actions.restore" },
];

function openCreatePanel() {
  form.defaults(emptyForm());
  form.reset();
  form.clearErrors();
  isPanelOpen.value = true;
}

function openEditPanel(data) {
  form.defaults({
    email: data.email || "",
    contact: data.contact || "",
    description: data.description || "",
    category_id: data.category_id ? { value: data.category_id, label: data.category } : null,
    customer_id: data.customer_id ? { value: data.customer_id, label: data.customer } : null,
    warehouse_id: data.warehouse_id ? { value: data.warehouse_id, label: data.warehouse } : null,
    id: data.id,
  });
  form.reset();
  form.clearErrors();
  isPanelOpen.value = true;
}

function closePanel() {
  isPanelOpen.value = false;
  form.clearErrors();
}

function submit() {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      closePanel();
      form.defaults(emptyForm());
      form.reset();
    },
  };

  if (form.id) {
    form.put(route("customerrequests.update", { request: form.id }), options);
    return;
  }

  form.post(route("customerrequests.store"), options);
}

function requestBulkAction(action) {
  selectedAction.value = action;
  showActionConfirmation.value = true;
}

function executeBulkAction() {
  const recordIds = pageRecords.value.filter((record) => record.selected).map((record) => record.id);

  if (!recordIds.length || !["delete", "restore"].includes(selectedAction.value)) {
    showActionConfirmation.value = false;
    return;
  }

  router.visit(
    route(`customerrequests.${selectedAction.value === 'delete' ? 'destroy' : 'restore'}`),
    {
      method: selectedAction.value === "delete" ? "delete" : "post",
      data: { recordIds },
      preserveScroll: true,
      onFinish: () => {
        showActionConfirmation.value = false;
        selectedAction.value = null;
      },
    },
  );
}
</script>

<template>
  <div class="pl-page space-y-6">
    <PageHeader :title="$t('gestlab.general.labels.customer_requests.page_title')" lede="Registo, classificação e encaminhamento de necessidades comunicadas pelos clientes ao laboratório.">
      <template #actions>
        <button
          v-if="hasPermission('add_customer_requests')"
          type="button"
          class="ds-button ds-button-primary"
          @click="openCreatePanel"
        >
          <PlusIcon class="h-4 w-4" />
          Novo pedido
        </button>
      </template>
    </PageHeader>

    <section v-if="hasPermission('add_customer_requests')" class="ds-panel p-5 sm:p-6" aria-labelledby="service-invitation-heading">
      <h2 id="service-invitation-heading" class="ds-heading text-base">Convidar cliente pelo portal</h2>
      <p class="ds-copy mt-1 text-sm">Convite de uso único, válido por 30 dias. O pedido fica privado neste laboratório.</p>
      <form class="mt-4 space-y-3" @submit.prevent="issueInvitation">
        <label class="ds-field-group" for="service-invitation-email">
          <span class="ds-field-label">Email da conta verificada do portal</span>
          <BaseInput id="service-invitation-email" v-model="invitationForm.recipient_email" type="email" required autocomplete="off" class="ds-field" :aria-invalid="Boolean(invitationForm.errors.recipient_email)" aria-describedby="service-invitation-error" />
        </label>
        <p v-if="invitationForm.errors.recipient_email" id="service-invitation-error" class="ds-field-error" role="alert">{{ invitationForm.errors.recipient_email }}</p>
        <p v-if="invitationForm.recentlySuccessful" role="status" class="ds-copy">Convite disponível na conta do portal.</p>
        <button type="submit" class="ds-button ds-button-secondary" :disabled="invitationForm.processing" :aria-busy="invitationForm.processing">{{ invitationForm.processing ? "A emitir…" : "Emitir convite" }}</button>
      </form>
    </section>

    <RecordsTable
      :record="props.record"
      :model="props.model"
      :abilities="props.abilities"
      :fields="props.fields"
      :slide-over-edit="props.slideOverEdit"
      :query="props.query"
      :actions="actions"
      :action-methods="{ delete: 'delete', restore: 'post' }"
      :create-action="false"
      @execute-action="requestBulkAction"
      @create-record="openCreatePanel"
      @slideover-on="openEditPanel"
    />

    <SlideOver
      v-if="isPanelOpen"
      :title="panelTitle"
      :description="panelDescription"
      @close="closePanel"
    >
      <template #content>
        <form id="customer-request-form" @submit.prevent="submit">
          <CustomerRequestForm :form="form" />
        </form>
      </template>

      <template #action_buttons>
        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
          <button type="button" class="ds-button ds-button-secondary" @click="closePanel">
            {{ $t('gestlab.general.buttons.cancel') }}
          </button>
          <button
            type="submit"
            form="customer-request-form"
            class="ds-button ds-button-primary"
            :disabled="form.processing || !form.isDirty"
          >
            {{ form.processing ? "A guardar..." : form.id ? "Guardar alterações" : "Registar pedido" }}
          </button>
        </div>
      </template>
    </SlideOver>

    <ConfirmDialog
      v-if="showActionConfirmation"
      :title="confirmationDialogTitle"
      :description="confirmationDialogDescription"
      :variant="selectedAction === 'restore' ? 'question' : 'danger'"
      confirm="Sim"
      cancel="Não"
      @canceled="showActionConfirmation = false"
      @confirmed="executeBulkAction"
    />
  </div>
</template>
