<script setup>
import ComboboxEnhanced from "@/Components/combobox-enhanced.vue";
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import {
  CalendarDaysIcon,
  CheckBadgeIcon,
  ExclamationTriangleIcon,
  ShieldCheckIcon,
  TruckIcon,
} from "@heroicons/vue/24/outline";
import { router, useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  assessments: { type: Array, default: () => [] },
  suppliers: { type: Array, default: () => [] },
  departments: { type: Array, default: () => [] },
  summary: { type: Object, default: () => ({}) },
});

const { hasPermission } = usePermission();
const editingId = ref(null);
const pendingArchive = ref(null);
const selectedSupplier = ref(null);
const selectedDepartment = ref(null);
const supplierOptions = computed(() => props.suppliers.map((supplier) => ({ value: supplier.id, label: supplier.name })));
const departmentOptions = computed(() => props.departments.map((department) => ({ value: department.id, label: department.name })));
const canEditForm = computed(() => editingId.value ? hasPermission("edit_isuppliers") : hasPermission("add_isuppliers"));
const metrics = computed(() => [
  { label: "Avaliações", value: props.summary.total ?? 0, detail: "registos ativos", icon: TruckIcon },
  { label: "Aprovados", value: props.summary.approved ?? 0, detail: "fornecedores conformes", icon: CheckBadgeIcon },
  { label: "Revisão próxima", value: props.summary.due_reviews ?? 0, detail: "nos próximos 30 dias", icon: CalendarDaysIcon },
  { label: "Risco elevado", value: props.summary.high_risk ?? 0, detail: "alto ou crítico", icon: ExclamationTriangleIcon },
]);
const scoreFields = [
  { key: "delivery_score", label: "Entrega" },
  { key: "quality_score", label: "Qualidade" },
  { key: "compliance_score", label: "Conformidade documental" },
  { key: "responsiveness_score", label: "Resposta e suporte" },
];

function initialFormData() {
  return {
    inventory_item_supplier_id: "",
    department_id: "",
    assessment_date: new Date().toISOString().slice(0, 10),
    next_review_at: "",
    status: "approved",
    risk_level: "medium",
    delivery_score: "",
    quality_score: "",
    compliance_score: "",
    responsiveness_score: "",
    evidence_reference: "",
    approved_supplier: true,
    is_active: true,
    strengths: "",
    gaps: "",
    corrective_actions: "",
    follow_up_actions: "",
    notes: "",
  };
}

const form = useForm(initialFormData());

watch(selectedSupplier, (option) => {
  form.inventory_item_supplier_id = option?.value ?? "";
});

watch(selectedDepartment, (option) => {
  form.department_id = option?.value ?? "";
});

function resetForm() {
  editingId.value = null;
  form.defaults(initialFormData());
  form.reset();
  form.clearErrors();
  selectedSupplier.value = null;
  selectedDepartment.value = null;
}

function editAssessment(assessment) {
  editingId.value = assessment.id;
  form.defaults({
    inventory_item_supplier_id: assessment.inventory_item_supplier_id ?? "",
    department_id: assessment.department_id ?? "",
    assessment_date: assessment.assessment_date ?? "",
    next_review_at: assessment.next_review_at ?? "",
    status: assessment.status ?? "approved",
    risk_level: assessment.risk_level ?? "medium",
    delivery_score: assessment.delivery_score ?? "",
    quality_score: assessment.quality_score ?? "",
    compliance_score: assessment.compliance_score ?? "",
    responsiveness_score: assessment.responsiveness_score ?? "",
    evidence_reference: assessment.evidence_reference ?? "",
    approved_supplier: Boolean(assessment.approved_supplier),
    is_active: Boolean(assessment.is_active),
    strengths: assessment.strengths ?? "",
    gaps: assessment.gaps ?? "",
    corrective_actions: assessment.corrective_actions ?? "",
    follow_up_actions: assessment.follow_up_actions ?? "",
    notes: assessment.notes ?? "",
  });
  form.reset();
  form.clearErrors();
  selectedSupplier.value = supplierOptions.value.find((option) => option.value === assessment.inventory_item_supplier_id) ?? null;
  selectedDepartment.value = departmentOptions.value.find((option) => option.value === assessment.department_id) ?? null;
  window.scrollTo({ top: 0, behavior: "smooth" });
}

function submit() {
  const options = {
    preserveScroll: true,
    onSuccess: resetForm,
  };

  if (editingId.value) {
    form.put(route("supplier-assessments.update", editingId.value), options);
    return;
  }

  form.post(route("supplier-assessments.store"), options);
}

function confirmArchive() {
  if (!pendingArchive.value) {
    return;
  }

  router.delete(route("supplier-assessments.destroy", pendingArchive.value.id), {
    preserveScroll: true,
    onFinish: () => {
      pendingArchive.value = null;
    },
  });
}

function formatDate(value) {
  return value ? new Intl.DateTimeFormat("pt-PT").format(new Date(value)) : "—";
}

function statusLabel(value) {
  return ({ approved: "Aprovado", conditional: "Condicional", suspended: "Suspenso", rejected: "Rejeitado" })[value] ?? value;
}

function statusTone(value) {
  return ({
    approved: "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300",
    conditional: "bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300",
    suspended: "bg-orange-50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-300",
    rejected: "bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300",
  })[value] ?? "bg-[var(--ds-panel-muted)] text-[var(--ds-text-muted)]";
}

function riskLabel(value) {
  return ({ low: "Risco baixo", medium: "Risco médio", high: "Risco elevado", critical: "Risco crítico" })[value] ?? value;
}

function riskTone(value) {
  return ({
    low: "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300",
    medium: "bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300",
    high: "bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300",
    critical: "bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300",
  })[value] ?? "bg-[var(--ds-panel-muted)] text-[var(--ds-text-muted)]";
}
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="flex items-start gap-3">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
          <ShieldCheckIcon class="h-5 w-5" />
        </span>
        <div>
          <p class="ds-kicker">Qualificação externa</p>
          <h1 class="ds-heading mt-1 text-2xl">Avaliação de fornecedores</h1>
          <p class="ds-copy mt-1 max-w-3xl text-sm">Aprovação, risco, desempenho e ações de seguimento para fornecedores que afetam a qualidade laboratorial.</p>
        </div>
      </div>

      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="metric in metrics" :key="metric.label" class="border-b border-[var(--ds-border)] px-4 py-3 sm:border-r xl:border-b-0 xl:last:border-r-0">
          <div class="flex items-start justify-between gap-3">
            <div><dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</dt><dd class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ metric.value }}</dd><p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ metric.detail }}</p></div>
            <component :is="metric.icon" class="h-5 w-5 text-[var(--ds-text-soft)]" />
          </div>
        </div>
      </dl>
    </section>

    <section class="grid gap-5 xl:grid-cols-[minmax(22rem,0.85fr)_minmax(0,1.15fr)]">
      <form v-if="canEditForm" class="ds-panel overflow-hidden" @submit.prevent="submit">
        <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
          <p class="ds-kicker">{{ editingId ? "Revisão" : "Qualificação" }}</p>
          <h2 class="ds-heading mt-1 text-base">{{ editingId ? "Editar avaliação" : "Nova avaliação" }}</h2>
        </div>

        <div class="space-y-6 px-5 py-5 sm:px-6">
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
              <ComboboxEnhanced v-model="selectedSupplier" title-label="Fornecedor" placeholder="Selecione um fornecedor" :options="supplierOptions" />
              <p v-if="form.errors.inventory_item_supplier_id" class="ds-field-error mt-2">{{ form.errors.inventory_item_supplier_id }}</p>
            </div>
            <div class="sm:col-span-2">
              <ComboboxEnhanced v-model="selectedDepartment" title-label="Departamento" placeholder="Selecione um departamento" :options="departmentOptions" />
              <p v-if="form.errors.department_id" class="ds-field-error mt-2">{{ form.errors.department_id }}</p>
            </div>
            <div><label for="assessment_date" class="ds-field-label mb-2 block">Data da avaliação</label><input id="assessment_date" v-model="form.assessment_date" type="date" class="ds-field"></div>
            <div><label for="next_review_at" class="ds-field-label mb-2 block">Próxima revisão</label><input id="next_review_at" v-model="form.next_review_at" type="date" class="ds-field"></div>
            <div><label for="assessment_status" class="ds-field-label mb-2 block">Decisão</label><select id="assessment_status" v-model="form.status" class="ds-field"><option value="approved">Aprovado</option><option value="conditional">Condicional</option><option value="suspended">Suspenso</option><option value="rejected">Rejeitado</option></select></div>
            <div><label for="risk_level" class="ds-field-label mb-2 block">Nível de risco</label><select id="risk_level" v-model="form.risk_level" class="ds-field"><option value="low">Baixo</option><option value="medium">Médio</option><option value="high">Elevado</option><option value="critical">Crítico</option></select></div>
          </div>

          <fieldset>
            <legend class="ds-field-label">Pontuação de desempenho (1 a 5)</legend>
            <div class="mt-3 grid gap-4 sm:grid-cols-2">
              <div v-for="field in scoreFields" :key="field.key">
                <label :for="field.key" class="ds-field-label mb-2 block">{{ field.label }}</label>
                <input :id="field.key" v-model.number="form[field.key]" type="number" min="1" max="5" class="ds-field">
                <p v-if="form.errors[field.key]" class="ds-field-error mt-2">{{ form.errors[field.key] }}</p>
              </div>
            </div>
          </fieldset>

          <div><label for="evidence_reference" class="ds-field-label mb-2 block">Referência de evidência</label><input id="evidence_reference" v-model="form.evidence_reference" type="text" class="ds-field" placeholder="Relatório, auditoria ou registo associado"></div>
          <div><label for="strengths" class="ds-field-label mb-2 block">Pontos fortes</label><textarea id="strengths" v-model="form.strengths" rows="3" class="ds-field min-h-24 resize-y"></textarea></div>
          <div><label for="gaps" class="ds-field-label mb-2 block">Lacunas e riscos</label><textarea id="gaps" v-model="form.gaps" rows="3" class="ds-field min-h-24 resize-y"></textarea></div>
          <div><label for="corrective_actions" class="ds-field-label mb-2 block">Ações corretivas</label><textarea id="corrective_actions" v-model="form.corrective_actions" rows="3" class="ds-field min-h-24 resize-y"></textarea></div>
          <div><label for="follow_up_actions" class="ds-field-label mb-2 block">Seguimento</label><textarea id="follow_up_actions" v-model="form.follow_up_actions" rows="3" class="ds-field min-h-24 resize-y"></textarea></div>
          <div><label for="assessment_notes" class="ds-field-label mb-2 block">Observações</label><textarea id="assessment_notes" v-model="form.notes" rows="3" class="ds-field min-h-24 resize-y"></textarea></div>

          <div class="grid gap-3 sm:grid-cols-2">
            <label class="flex items-center gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3 text-sm font-semibold text-[var(--ds-text-muted)]"><input v-model="form.approved_supplier" type="checkbox" class="ds-checkbox"> Fornecedor aprovado</label>
            <label class="flex items-center gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3 text-sm font-semibold text-[var(--ds-text-muted)]"><input v-model="form.is_active" type="checkbox" class="ds-checkbox"> Avaliação ativa</label>
          </div>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
          <button v-if="editingId" type="button" class="ds-button ds-button-secondary" @click="resetForm">Cancelar</button>
          <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">{{ form.processing ? "A guardar..." : (editingId ? "Atualizar avaliação" : "Guardar avaliação") }}</button>
        </div>
      </form>

      <section class="ds-panel overflow-hidden" :class="{ 'xl:col-span-2': !canEditForm }">
        <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
          <p class="ds-kicker">Registo aprovado</p>
          <h2 class="ds-heading mt-1 text-base">Histórico de qualificação</h2>
        </div>

        <div v-if="assessments.length" class="divide-y divide-[var(--ds-border)]">
          <article v-for="assessment in assessments" :key="assessment.id" class="px-5 py-5 sm:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusTone(assessment.status)">{{ statusLabel(assessment.status) }}</span>
                  <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="riskTone(assessment.risk_level)">{{ riskLabel(assessment.risk_level) }}</span>
                  <span v-if="assessment.approved_supplier" class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">Lista aprovada</span>
                </div>
                <h3 class="mt-2 text-base font-semibold text-[var(--ds-text)]">{{ assessment.supplier?.name || "Fornecedor" }}</h3>
                <p class="mt-2 text-sm leading-6 text-[var(--ds-text-muted)]">{{ assessment.gaps || assessment.notes || "Sem observações adicionais." }}</p>
                <dl class="mt-4 grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                  <div><dt class="inline font-semibold text-[var(--ds-text)]">Departamento:</dt> <dd class="inline text-[var(--ds-text-muted)]">{{ assessment.department?.name || "Transversal" }}</dd></div>
                  <div><dt class="inline font-semibold text-[var(--ds-text)]">Avaliador:</dt> <dd class="inline text-[var(--ds-text-muted)]">{{ assessment.assessed_by?.name || "Sistema" }}</dd></div>
                  <div><dt class="inline font-semibold text-[var(--ds-text)]">Data:</dt> <dd class="inline text-[var(--ds-text-muted)]">{{ formatDate(assessment.assessment_date) }}</dd></div>
                  <div><dt class="inline font-semibold text-[var(--ds-text)]">Revisão:</dt> <dd class="inline text-[var(--ds-text-muted)]">{{ formatDate(assessment.next_review_at) }}</dd></div>
                </dl>
              </div>
              <div class="flex shrink-0 flex-col items-start gap-3 lg:items-end">
                <div><p class="text-2xl font-bold text-[var(--ds-text)]">{{ assessment.total_score }}/100</p><p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">Índice global</p></div>
                <div class="flex flex-wrap gap-2">
                  <button v-if="hasPermission('edit_isuppliers')" type="button" class="ds-button ds-button-secondary min-h-0 px-3 py-2" @click="editAssessment(assessment)">Editar</button>
                  <button v-if="hasPermission('delete_isuppliers')" type="button" class="ds-button ds-button-danger min-h-0 px-3 py-2" @click="pendingArchive = assessment">Arquivar</button>
                </div>
              </div>
            </div>
          </article>
        </div>
        <div v-else class="m-5 rounded-lg border border-dashed border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-10 text-center text-sm font-medium text-[var(--ds-text-muted)]">Ainda não existem avaliações de fornecedores registadas.</div>
      </section>
    </section>

    <ConfirmDialog
      v-if="pendingArchive"
      title="Arquivar avaliação de fornecedor?"
      description="A avaliação deixa de integrar a qualificação ativa, mantendo o histórico disponível para auditoria."
      confirm="Arquivar"
      cancel="Cancelar"
      @canceled="pendingArchive = null"
      @confirmed="confirmArchive"
    />
  </div>
</template>
