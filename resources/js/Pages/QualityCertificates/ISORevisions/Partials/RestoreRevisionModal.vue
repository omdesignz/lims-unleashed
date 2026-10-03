<script setup>
import Modal from "@/Components/Modal.vue";
import { computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import {
  RefreshCw as ArrowPathIcon,
  Check as CheckIcon,
  TriangleAlert as ExclamationTriangleIcon,
  X as XMarkIcon,
} from "@lucide/vue";

const props = defineProps({
  show: Boolean,
  revision: {
    type: Object,
    default: null,
  },
  certificate: {
    type: Object,
    default: () => ({}),
  },
  approvers: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["close", "restored"]);

const form = useForm({
  restore_reason: "",
  iso_section: "8.9.1",
  approved_by_id: "",
  restore_scope: "FULL",
  selected_fields: [],
  change_category: "CORRECTION",
  risk_assessment: "MEDIUM",
  additional_notes: "",
  confirmed: false,
});

const restorableFields = [
  { name: "status", label: "Estado do certificado" },
  { name: "obs", label: "Observações" },
  { name: "validated_by", label: "Validado por" },
  { name: "validated_at", label: "Data de validação" },
  { name: "extra_data", label: "Dados adicionais" },
];

const sourceDetails = computed(() => [
  {
    label: "Versão",
    value: `v${props.revision?.version || "-"}`,
  },
  {
    label: "Revisão",
    value: props.revision?.revision_number ?? "-",
  },
  {
    label: "Data efectiva",
    value: formatDate(props.revision?.effective_date),
  },
  {
    label: "Tipo",
    value: props.revision?.change_type || "Não indicado",
  },
]);

const currentDetails = computed(() => [
  {
    label: "Versão",
    value: `v${props.certificate?.current_revision?.version || "1.0"}`,
  },
  {
    label: "Revisão",
    value: props.certificate?.current_revision?.revision_number ?? "-",
  },
  {
    label: "Data efectiva",
    value: formatDate(
      props.certificate?.current_revision?.effective_date ||
      props.certificate?.validated_at,
    ),
  },
  {
    label: "Estado",
    value: props.certificate?.status ? "Activo" : "Inactivo",
  },
]);

const isReady = computed(() => {
  const selectiveScopeReady =
    form.restore_scope === "FULL" || form.selected_fields.length > 0;

  return (
    form.restore_reason.length >= 20 &&
    form.iso_section &&
    form.approved_by_id &&
    form.change_category &&
    form.confirmed &&
    selectiveScopeReady &&
    !form.processing
  );
});

watch(
  () => props.revision,
  (revision) => {
    if (!revision) {
      return;
    }

    form.restore_reason = `Reposição controlada da revisão v${revision.version}: ${revision.change_reason || "motivo a documentar"}`;
    form.iso_section = revision.compliance_metadata?.iso_section || "8.9.1";
    form.risk_assessment =
      revision.compliance_metadata?.risk_assessment || "MEDIUM";
  },
  { immediate: true },
);

function formatDate(date) {
  if (!date) {
    return "Não registada";
  }

  return new Date(date).toLocaleString("pt-PT", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function fieldError(field) {
  return form.errors[field] || form.errors[`approval_data.${field}`];
}

function toggleSelectedField(fieldName) {
  const index = form.selected_fields.indexOf(fieldName);
  if (index >= 0) {
    form.selected_fields.splice(index, 1);
    return;
  }

  form.selected_fields.push(fieldName);
  form.clearErrors("selected_fields", "approval_data.selected_fields");
}

function closeModal() {
  form.reset();
  form.clearErrors();
  form.restore_scope = "FULL";
  form.selected_fields = [];
  form.confirmed = false;
  emit("close");
}

function restoreRevision() {
  if (form.restore_scope === "SELECTIVE" && !form.selected_fields.length) {
    form.setError("selected_fields", "Seleccione pelo menos um campo para repor.");
    return;
  }

  const approvalData = {
    approved_by_id: form.approved_by_id,
    iso_section: form.iso_section,
    change_category: form.change_category,
    risk_assessment: form.risk_assessment,
    additional_notes: form.additional_notes,
    restore_scope: form.restore_scope,
    selected_fields: form.selected_fields,
  };

  form
    .transform((data) => ({
      ...data,
      approval_data: approvalData,
    }))
    .post(
      route("qualitycertificates.iso-revisions.restore", {
        certificate: props.certificate.id,
        revision: props.revision.id,
      }),
      {
        preserveScroll: true,
        onSuccess: () => {
          closeModal();
          emit("restored");
        },
      },
    );
}
</script>

<template>
  <Modal :show="show" max-width="4xl" @close="closeModal">
    <form class="min-w-0" @submit.prevent="restoreRevision">
      <header class="flex items-start justify-between gap-4 border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
        <div class="flex min-w-0 items-start gap-3">
          <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-amber-100 text-amber-800 dark:bg-amber-400/15 dark:text-amber-200">
            <ArrowPathIcon class="h-5 w-5" />
          </div>
          <div>
            <p class="ds-kicker">Acção controlada</p>
            <h2 class="ds-heading mt-2 text-lg">Repor revisão v{{ revision?.version || "-" }}</h2>
            <p class="ds-copy mt-1 text-xs"> A reposição cria uma nova revisão e preserva todo o histórico anterior. </p>
          </div>
        </div>
        <button type="button" class="ds-icon-button" title="Fechar" @click="closeModal">
          <XMarkIcon class="h-5 w-5" />
          <span class="sr-only">Fechar</span>
        </button>
      </header>

      <div class="max-h-[75vh] space-y-6 overflow-y-auto px-5 py-5 sm:px-6">
        <section class="lims-status-strip p-4">
          <div class="flex items-start gap-3">
            <ExclamationTriangleIcon class="h-5 w-5 shrink-0 text-[var(--lims-hold)]" />
            <div>
              <h3 class="ds-heading text-sm">A versão actual será substituída</h3>
              <p class="ds-copy mt-1 text-xs"> Confirme o âmbito, aprovador, secção ISO e justificação. Esta acção não elimina revisões existentes. </p>
            </div>
          </div>
        </section>

        <div class="grid gap-4 lg:grid-cols-2">
          <section class="ds-command-surface overflow-hidden">
            <div class="border-b border-[var(--ds-border)] px-4 py-3">
              <p class="ds-kicker">Origem</p>
              <h3 class="ds-heading mt-2 text-sm">Revisão a repor</h3>
            </div>
            <dl class="divide-y divide-[var(--ds-border)]">
              <div
                v-for="detail in sourceDetails"
                :key="detail.label"
                class="flex items-start justify-between gap-4 px-4 py-3"
              >
                <dt class="text-xs font-bold text-[var(--ds-text-muted)]">{{ detail.label }}</dt>
                <dd class="text-right text-xs font-bold text-[var(--ds-text)]">{{ detail.value }}</dd>
              </div>
            </dl>
          </section>

          <section class="ds-command-surface overflow-hidden">
            <div class="border-b border-[var(--ds-border)] px-4 py-3">
              <p class="ds-kicker">Destino</p>
              <h3 class="ds-heading mt-2 text-sm">Versão atualmente efectiva</h3>
            </div>
            <dl class="divide-y divide-[var(--ds-border)]">
              <div
                v-for="detail in currentDetails"
                :key="detail.label"
                class="flex items-start justify-between gap-4 px-4 py-3"
              >
                <dt class="text-xs font-bold text-[var(--ds-text-muted)]">{{ detail.label }}</dt>
                <dd class="text-right text-xs font-bold text-[var(--ds-text)]">{{ detail.value }}</dd>
              </div>
            </dl>
          </section>
        </div>

        <div class="ds-command-toolbar p-4">
          <p class="ds-table-heading">Motivo original da revisão</p>
          <p class="ds-copy mt-2 text-sm">{{ revision?.change_reason || "Não registado." }}</p>
        </div>

        <section class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <p class="ds-kicker">Âmbito de reposição</p>
            <h3 class="ds-heading mt-2 text-base">Definir dados a recuperar</h3>
          </div>
          <div class="grid gap-3 px-5 py-5 sm:grid-cols-2">
            <button
              type="button"
              :class="[
                'ds-command-toolbar p-4 text-left',
                form.restore_scope === 'FULL' ? 'ring-2 ring-[rgb(var(--primary-500-rgb))]' : '',
              ]"
              @click="form.restore_scope = 'FULL'"
            >
              <span class="flex items-start gap-3">
                <span
                  :class="[
                    'mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full border',
                    form.restore_scope === 'FULL'
                      ? 'border-[rgb(var(--primary-700-rgb))] bg-[rgb(var(--primary-700-rgb))] text-white'
                      : 'border-[var(--ds-border-strong)]',
                  ]"
                >
                  <CheckIcon v-if="form.restore_scope === 'FULL'" class="h-3.5 w-3.5" />
                </span>
                <span>
                  <span class="ds-heading block text-sm">Reposição completa</span>
                  <span class="ds-copy mt-1 block text-xs">Recupera todos os campos elegíveis da captura.</span>
                </span>
              </span>
            </button>

            <button
              type="button"
              :class="[
                'ds-command-toolbar p-4 text-left',
                form.restore_scope === 'SELECTIVE' ? 'ring-2 ring-[rgb(var(--primary-500-rgb))]' : '',
              ]"
              @click="form.restore_scope = 'SELECTIVE'"
            >
              <span class="flex items-start gap-3">
                <span
                  :class="[
                    'mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full border',
                    form.restore_scope === 'SELECTIVE'
                      ? 'border-[rgb(var(--primary-700-rgb))] bg-[rgb(var(--primary-700-rgb))] text-white'
                      : 'border-[var(--ds-border-strong)]',
                  ]"
                >
                  <CheckIcon v-if="form.restore_scope === 'SELECTIVE'" class="h-3.5 w-3.5" />
                </span>
                <span>
                  <span class="ds-heading block text-sm">Reposição selectiva</span>
                  <span class="ds-copy mt-1 block text-xs">Escolha campos especificos do certificado.</span>
                </span>
              </span>
            </button>
          </div>

          <div
            v-if="form.restore_scope === 'SELECTIVE'"
            class="grid border-t border-[var(--ds-border)] sm:grid-cols-2"
          >
            <button
              v-for="field in restorableFields"
              :key="field.name"
              type="button"
              role="checkbox"
              :aria-checked="form.selected_fields.includes(field.name)"
              class="flex items-center gap-3 border-b border-[var(--ds-border)] px-5 py-3 text-left hover:bg-[var(--ds-panel-subtle)] sm:odd:border-r"
              @click="toggleSelectedField(field.name)"
            >
              <span
                :class="[
                  'grid h-5 w-5 shrink-0 place-items-center rounded border',
                  form.selected_fields.includes(field.name)
                    ? 'border-[rgb(var(--primary-700-rgb))] bg-[rgb(var(--primary-700-rgb))] text-white'
                    : 'border-[var(--ds-border-strong)] bg-[var(--ds-panel-raised)]',
                ]"
              >
                <CheckIcon v-if="form.selected_fields.includes(field.name)" class="h-3.5 w-3.5" />
              </span>
              <span class="text-sm font-bold text-[var(--ds-text)]">{{ field.label }}</span>
            </button>
          </div>
          <p v-if="fieldError('selected_fields')" class="ds-field-error px-5 py-3">
            {{ fieldError("selected_fields") }}
          </p>
        </section>

        <section class="grid gap-5 lg:grid-cols-2">
          <div class="ds-field-group lg:col-span-2">
            <label class="ds-field-label" for="restore-reason"> Justificação da reposição <span class="ds-field-required">*</span>
            </label>
            <textarea
              id="restore-reason"
              v-model="form.restore_reason"
              class="ds-field min-h-28"
              :aria-invalid="Boolean(fieldError('restore_reason'))"
              placeholder="Explique a decisão e o impacto esperado"
            />
            <p class="ds-field-hint">Minimo de 20 caracteres.</p>
            <p v-if="fieldError('restore_reason')" class="ds-field-error">
              {{ fieldError("restore_reason") }}
            </p>
          </div>

          <div class="ds-field-group">
            <label class="ds-field-label" for="restore-iso-section"> Secção ISO <span class="ds-field-required">*</span>
            </label>
            <BaseInput
              id="restore-iso-section"
              v-model="form.iso_section"
              class="ds-field"
              :aria-invalid="Boolean(fieldError('iso_section'))"
            />
            <p v-if="fieldError('iso_section')" class="ds-field-error">
              {{ fieldError("iso_section") }}
            </p>
          </div>

          <div class="ds-field-group">
            <label class="ds-field-label" for="restore-approver">
              Aprovador <span class="ds-field-required">*</span>
            </label>
            <BaseSelect
              id="restore-approver"
              v-model="form.approved_by_id"
              class="ds-field"
              :aria-invalid="Boolean(fieldError('approved_by_id'))"
            >
              <option value="">Seleccionar aprovador</option>
              <option v-for="approver in approvers" :key="approver.id" :value="approver.id">
                {{ approver.name }}
              </option>
            </BaseSelect>
            <p v-if="!approvers.length" class="ds-field-hint">
              Nenhum aprovador elegivel foi fornecido para este fluxo.
            </p>
            <p v-if="fieldError('approved_by_id')" class="ds-field-error">
              {{ fieldError("approved_by_id") }}
            </p>
          </div>

          <div class="ds-field-group">
            <label class="ds-field-label" for="restore-category">
              Categoria <span class="ds-field-required">*</span>
            </label>
            <BaseSelect id="restore-category" v-model="form.change_category" class="ds-field">
              <option value="CORRECTION">Correcao</option>
              <option value="REISSUE">Reemissao</option>
              <option value="EMERGENCY">Emergencia</option>
              <option value="REGULATORY">Regulatoria</option>
            </BaseSelect>
          </div>

          <div class="ds-field-group">
            <label class="ds-field-label" for="restore-risk">Avaliacao de risco</label>
            <BaseSelect id="restore-risk" v-model="form.risk_assessment" class="ds-field">
              <option value="LOW">Baixo</option>
              <option value="MEDIUM">Medio</option>
              <option value="HIGH">Alto</option>
            </BaseSelect>
          </div>

          <div class="ds-field-group lg:col-span-2">
            <label class="ds-field-label" for="restore-notes">Notas adicionais</label>
            <textarea id="restore-notes" v-model="form.additional_notes" class="ds-field" rows="3" />
          </div>
        </section>

        <label class="lims-status-strip flex items-start gap-3 p-4">
          <CheckboxInput v-model="form.confirmed" type="checkbox" class="ds-checkbox mt-0.5" />
          <span>
            <span class="ds-heading block text-sm">Confirmo a reposição controlada</span>
            <span class="ds-copy mt-1 block text-xs"> Compreendo que uma nova revisão será criada e passará a representar o estado efectivo do certificado. </span>
          </span>
        </label>
        <p v-if="fieldError('confirmed')" class="ds-field-error">
          {{ fieldError("confirmed") }}
        </p>
      </div>

      <footer class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
        <button type="button" class="ds-button ds-button-secondary" @click="closeModal">
          Cancelar
        </button>
        <button type="submit" class="ds-button ds-button-primary" :disabled="!isReady">
          <ArrowPathIcon class="h-4 w-4" />
          {{ form.processing ? "A repor..." : "Repor versão" }}
        </button>
      </footer>
    </form>
  </Modal>
</template>
