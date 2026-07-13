<script setup>
import Modal from "@/Components/Modal.vue";
import { computed, ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import {
  CheckIcon,
  DocumentPlusIcon,
  ExclamationTriangleIcon,
  XMarkIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
  show: Boolean,
  certificate: {
    type: Object,
    default: () => ({}),
  },
  approvers: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["close", "created"]);

const form = useForm({
  change_type: "UPDATED",
  change_reason: "",
  iso_section: "8.9.1",
  risk_assessment: "LOW",
  approved_by_id: "",
  fields: {},
});

const selectedFields = ref([]);
const updatableFields = [
  { name: "status", label: "Estado do certificado" },
  { name: "obs", label: "Observacoes" },
  { name: "validated_by", label: "Validado por" },
  { name: "extra_data", label: "Dados adicionais" },
];

const isReady = computed(() => {
  return (
    form.change_type &&
    form.change_reason.length >= 10 &&
    form.iso_section &&
    form.risk_assessment &&
    selectedFields.value.length > 0 &&
    !form.processing
  );
});

function toggleField(fieldName) {
  const index = selectedFields.value.indexOf(fieldName);
  if (index >= 0) {
    selectedFields.value.splice(index, 1);
    return;
  }

  selectedFields.value.push(fieldName);
  form.clearErrors("fields");
}

function closeModal() {
  form.reset();
  form.clearErrors();
  selectedFields.value = [];
  emit("close");
}

function createRevision() {
  if (!selectedFields.value.length) {
    form.setError("fields", "Selecione pelo menos um campo.");
    return;
  }

  form.fields = Object.fromEntries(
    selectedFields.value.map((field) => [field, props.certificate[field]]),
  );

  form.post(
    route("qualitycertificates.iso-revisions.store", props.certificate.id),
    {
      preserveScroll: true,
      onSuccess: () => {
        closeModal();
        emit("created");
      },
    },
  );
}
</script>

<template>
  <Modal :show="show" max-width="2xl" @close="closeModal">
    <form class="min-w-0" @submit.prevent="createRevision">
      <header class="flex items-start justify-between gap-4 border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
        <div class="flex min-w-0 items-start gap-3">
          <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)]">
            <DocumentPlusIcon class="h-5 w-5" />
          </div>
          <div>
            <p class="ds-kicker">Alteracao rapida</p>
            <h2 class="ds-heading mt-2 text-lg">Criar revisao ISO</h2>
            <p class="ds-copy mt-1 text-xs">
              {{ certificate.code || "Certificado" }} - v{{ certificate.current_revision?.version || "1.0" }}
            </p>
          </div>
        </div>
        <button type="button" class="ds-icon-button" title="Fechar" @click="closeModal">
          <XMarkIcon class="h-5 w-5" />
          <span class="sr-only">Fechar</span>
        </button>
      </header>

      <div class="space-y-5 px-5 py-5 sm:px-6">
        <div class="grid gap-4 sm:grid-cols-2">
          <div class="ds-field-group">
            <label class="ds-field-label" for="modal-change-type">
              Tipo de alteracao <span class="ds-field-required">*</span>
            </label>
            <select id="modal-change-type" v-model="form.change_type" class="ds-field">
              <option value="UPDATED">Atualizacao</option>
              <option value="CORRECTED">Correcao</option>
              <option value="REISSUED">Reemissao</option>
              <option value="WITHDRAWN">Retirada</option>
            </select>
          </div>

          <div class="ds-field-group">
            <label class="ds-field-label" for="modal-risk">
              Avaliacao de risco <span class="ds-field-required">*</span>
            </label>
            <select id="modal-risk" v-model="form.risk_assessment" class="ds-field">
              <option value="LOW">Baixo</option>
              <option value="MEDIUM">Medio</option>
              <option value="HIGH">Alto</option>
              <option value="CRITICAL">Critico</option>
            </select>
          </div>

          <div class="ds-field-group">
            <label class="ds-field-label" for="modal-iso-section">
              Secao ISO <span class="ds-field-required">*</span>
            </label>
            <input id="modal-iso-section" v-model="form.iso_section" class="ds-field" />
          </div>

          <div class="ds-field-group">
            <label class="ds-field-label" for="modal-approver">Aprovador</label>
            <select id="modal-approver" v-model="form.approved_by_id" class="ds-field">
              <option value="">Sem aprovador designado</option>
              <option v-for="approver in approvers" :key="approver.id" :value="approver.id">
                {{ approver.name }}
              </option>
            </select>
          </div>
        </div>

        <div class="ds-field-group">
          <label class="ds-field-label" for="modal-change-reason">
            Justificacao <span class="ds-field-required">*</span>
          </label>
          <textarea
            id="modal-change-reason"
            v-model="form.change_reason"
            class="ds-field min-h-28"
            :aria-invalid="Boolean(form.errors.change_reason)"
            placeholder="Descreva o motivo da revisao"
          />
          <p class="ds-field-hint">Minimo de 10 caracteres.</p>
          <p v-if="form.errors.change_reason" class="ds-field-error">
            {{ form.errors.change_reason }}
          </p>
        </div>

        <section class="ds-command-surface overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-4 py-3">
            <p class="ds-table-heading">Campos a preservar na nova revisao</p>
          </div>
          <div class="grid sm:grid-cols-2">
            <button
              v-for="field in updatableFields"
              :key="field.name"
              type="button"
              role="checkbox"
              :aria-checked="selectedFields.includes(field.name)"
              class="flex items-center gap-3 border-b border-[var(--ds-border)] px-4 py-3 text-left hover:bg-[var(--ds-panel-subtle)] sm:odd:border-r"
              @click="toggleField(field.name)"
            >
              <span
                :class="[
                  'grid h-5 w-5 shrink-0 place-items-center rounded border',
                  selectedFields.includes(field.name)
                    ? 'border-[rgb(var(--primary-700-rgb))] bg-[rgb(var(--primary-700-rgb))] text-white'
                    : 'border-[var(--ds-border-strong)] bg-[var(--ds-panel-raised)]',
                ]"
              >
                <CheckIcon v-if="selectedFields.includes(field.name)" class="h-3.5 w-3.5" />
              </span>
              <span class="text-sm font-bold text-[var(--ds-text)]">{{ field.label }}</span>
            </button>
          </div>
        </section>
        <p v-if="form.errors.fields" class="ds-field-error">{{ form.errors.fields }}</p>

        <div v-if="form.hasErrors" class="lims-status-strip p-4">
          <div class="flex items-start gap-3">
            <ExclamationTriangleIcon class="h-5 w-5 shrink-0 text-[var(--lims-critical)]" />
            <p class="text-xs font-semibold text-[var(--ds-text-muted)]">
              Reveja os campos assinalados antes de criar a revisao.
            </p>
          </div>
        </div>
      </div>

      <footer class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
        <button type="button" class="ds-button ds-button-secondary" @click="closeModal">
          Cancelar
        </button>
        <button type="submit" class="ds-button ds-button-primary" :disabled="!isReady">
          <DocumentPlusIcon class="h-4 w-4" />
          {{ form.processing ? "A criar..." : "Criar revisao" }}
        </button>
      </footer>
    </form>
  </Modal>
</template>
