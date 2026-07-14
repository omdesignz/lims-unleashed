<script setup>
import ComboboxEnhanced from "@/Components/combobox-enhanced.vue";
import ToggleField from "@/Components/base/ToggleField.vue";
import {
  BellAlertIcon,
  BuildingOffice2Icon,
  CalendarDaysIcon,
  ChatBubbleLeftRightIcon,
  ClipboardDocumentCheckIcon,
  MagnifyingGlassIcon,
  ShieldCheckIcon,
  UserCircleIcon,
  WrenchScrewdriverIcon,
} from "@heroicons/vue/24/outline";

defineProps({
  form: { type: Object, required: true },
});

async function loadOptions(endpoint, query, setOptions) {
  try {
    const response = await fetch(`${endpoint}?q=${encodeURIComponent(query ?? "")}`);
    const results = await response.json();
    const options = results.map((result) => ({
      value: result.id,
      label: result.name,
    }));

    setOptions(options);
    return options;
  } catch {
    setOptions([]);
    return [];
  }
}

const loadUsers = (query, setOptions) => loadOptions("/users/getUser", query, setOptions);
const loadStatuses = (query, setOptions) => loadOptions("/occurrencestatuses/getOccurrenceStatus", query, setOptions);
const loadOrigins = (query, setOptions) => loadOptions("/occurrenceorigins/getOccurrenceOrigin", query, setOptions);
const loadDepartments = (query, setOptions) => loadOptions("/departments/getDepartment", query, setOptions);
const loadCategories = (query, setOptions) => loadOptions("/occurrencecategories/getOccurrenceCategory", query, setOptions);
</script>

<template>
  <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem] xl:items-start">
    <div class="space-y-6">
      <section class="ds-panel p-5 sm:p-6">
        <div class="flex items-start gap-3 border-b border-[var(--ds-border)] pb-4">
          <BellAlertIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          <div>
            <p class="ds-kicker">Registo e triagem</p>
            <h2 class="ds-heading mt-2 text-lg">Identificação da ocorrência</h2>
            <p class="ds-copy mt-1 text-sm">Documente o desvio observado, a proveniência e a responsabilidade inicial.</p>
          </div>
        </div>

        <div class="mt-6 grid gap-6 sm:grid-cols-2">
          <div>
            <label for="occurrence-date-reported" class="ds-field-label">Data do registo</label>
            <DateTimePicker id="occurrence-date-reported" v-model="form.date_reported" type="date" class="ds-field mt-2" required />
            <p v-if="form.errors.date_reported" class="ds-field-error mt-2">{{ form.errors.date_reported }}</p>
          </div>

          <div>
            <label for="occurrence-category" class="ds-field-label">Categoria</label>
            <ComboboxEnhanced
              id="occurrence-category"
              v-model="form.category_id"
              class="mt-2"
              :has-error="Boolean(form.errors.category_id)"
              :load-options="loadCategories"
              placeholder="Seleccionar categoria"
            />
            <p v-if="form.errors.category_id" class="ds-field-error mt-2">{{ form.errors.category_id }}</p>
          </div>

          <div>
            <label for="occurrence-origin" class="ds-field-label">Origem</label>
            <ComboboxEnhanced
              id="occurrence-origin"
              v-model="form.origin_id"
              class="mt-2"
              :has-error="Boolean(form.errors.origin_id)"
              :load-options="loadOrigins"
              placeholder="Seleccionar origem"
            />
            <p v-if="form.errors.origin_id" class="ds-field-error mt-2">{{ form.errors.origin_id }}</p>
          </div>

          <div>
            <label for="occurrence-department" class="ds-field-label">Departamento</label>
            <ComboboxEnhanced
              id="occurrence-department"
              v-model="form.department_id"
              class="mt-2"
              :has-error="Boolean(form.errors.department_id)"
              :load-options="loadDepartments"
              placeholder="Seleccionar departamento"
            />
            <p v-if="form.errors.department_id" class="ds-field-error mt-2">{{ form.errors.department_id }}</p>
          </div>

          <div>
            <label for="occurrence-user" class="ds-field-label">Responsável interno</label>
            <ComboboxEnhanced
              id="occurrence-user"
              v-model="form.user_id"
              class="mt-2"
              :has-error="Boolean(form.errors.user_id)"
              :load-options="loadUsers"
              placeholder="Seleccionar colaborador"
            />
            <p v-if="form.errors.user_id" class="ds-field-error mt-2">{{ form.errors.user_id }}</p>
          </div>

          <div>
            <label for="occurrence-responsible-name" class="ds-field-label">Outro responsável</label>
            <BaseInput id="occurrence-responsible-name" v-model="form.responsible_name" type="text" class="ds-field mt-2" />
            <p class="ds-field-hint mt-2">Use quando a responsabilidade não corresponde a um utilizador do sistema.</p>
            <p v-if="form.errors.responsible_name" class="ds-field-error mt-2">{{ form.errors.responsible_name }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="occurrence-description" class="ds-field-label">Descrição do desvio</label>
            <textarea
              id="occurrence-description"
              v-model="form.issue_description"
              class="ds-field mt-2 min-h-32 resize-y"
              placeholder="Descreva factos observáveis, impacto e evidência disponível"
              required
            />
            <p v-if="form.errors.issue_description" class="ds-field-error mt-2">{{ form.errors.issue_description }}</p>
          </div>
        </div>
      </section>

      <section class="ds-panel p-5 sm:p-6">
        <div class="flex items-start gap-3 border-b border-[var(--ds-border)] pb-4">
          <MagnifyingGlassIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          <div>
            <p class="ds-kicker">Investigação</p>
            <h2 class="ds-heading mt-2 text-lg">Análise de causa e efeito</h2>
            <p class="ds-copy mt-1 text-sm">Separe a avaliação factual da causa identificada e do efeito no processo.</p>
          </div>
        </div>

        <div class="mt-6 space-y-6">
          <div>
            <label for="occurrence-analysis" class="ds-field-label">Análise</label>
            <textarea id="occurrence-analysis" v-model="form.analysis" class="ds-field mt-2 min-h-28 resize-y" />
            <p v-if="form.errors.analysis" class="ds-field-error mt-2">{{ form.errors.analysis }}</p>
          </div>

          <div class="grid gap-6 md:grid-cols-2">
            <div>
              <label for="occurrence-cause" class="ds-field-label">Causa identificada</label>
              <textarea id="occurrence-cause" v-model="form.cause_corrective_actions" class="ds-field mt-2 min-h-28 resize-y" />
              <p v-if="form.errors.cause_corrective_actions" class="ds-field-error mt-2">{{ form.errors.cause_corrective_actions }}</p>
            </div>
            <div>
              <label for="occurrence-effect" class="ds-field-label">Efeito observado</label>
              <textarea id="occurrence-effect" v-model="form.effect_corrective_actions" class="ds-field mt-2 min-h-28 resize-y" />
              <p v-if="form.errors.effect_corrective_actions" class="ds-field-error mt-2">{{ form.errors.effect_corrective_actions }}</p>
            </div>
          </div>
        </div>
      </section>

      <section class="ds-panel p-5 sm:p-6">
        <div class="flex items-start gap-3 border-b border-[var(--ds-border)] pb-4">
          <WrenchScrewdriverIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          <div>
            <p class="ds-kicker">CAPA</p>
            <h2 class="ds-heading mt-2 text-lg">Acção correctiva e eficácia</h2>
            <p class="ds-copy mt-1 text-sm">Defina a acção, o prazo de implementação e a verificação de eficácia.</p>
          </div>
        </div>

        <div class="mt-6 grid gap-6 sm:grid-cols-2">
          <div class="sm:col-span-2">
            <label for="occurrence-corrective-action" class="ds-field-label">Acção correctiva</label>
            <textarea id="occurrence-corrective-action" v-model="form.corrective_action" class="ds-field mt-2 min-h-28 resize-y" />
            <p v-if="form.errors.corrective_action" class="ds-field-error mt-2">{{ form.errors.corrective_action }}</p>
          </div>
          <div>
            <label for="occurrence-implementation-date" class="ds-field-label">Prazo de implementação</label>
            <DateTimePicker id="occurrence-implementation-date" v-model="form.implementation_date" type="date" class="ds-field mt-2" />
            <p v-if="form.errors.implementation_date" class="ds-field-error mt-2">{{ form.errors.implementation_date }}</p>
          </div>
          <div>
            <label for="occurrence-effectiveness" class="ds-field-label">Resultado da eficácia</label>
            <BaseSelect id="occurrence-effectiveness" v-model="form.was_effective" class="ds-field mt-2">
              <option :value="null">Por verificar</option>
              <option :value="true">Eficaz</option>
              <option :value="false">Não eficaz</option>
            </BaseSelect>
            <p v-if="form.errors.was_effective" class="ds-field-error mt-2">{{ form.errors.was_effective }}</p>
          </div>
        </div>
      </section>

      <section class="ds-panel p-5 sm:p-6">
        <div class="flex items-start gap-3 border-b border-[var(--ds-border)] pb-4">
          <ChatBubbleLeftRightIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          <div>
            <p class="ds-kicker">Comunicação externa</p>
            <h2 class="ds-heading mt-2 text-lg">Processo com o cliente</h2>
            <p class="ds-copy mt-1 text-sm">Registe notificações e aceitação quando a ocorrência envolve o cliente.</p>
          </div>
        </div>

        <div class="mt-6 grid gap-6 sm:grid-cols-2">
          <div>
            <label for="occurrence-client-open" class="ds-field-label">Notificação de abertura</label>
            <DateTimePicker id="occurrence-client-open" v-model="form.client_process_open_notification_date" type="date" class="ds-field mt-2" />
            <p v-if="form.errors.client_process_open_notification_date" class="ds-field-error mt-2">{{ form.errors.client_process_open_notification_date }}</p>
          </div>
          <div>
            <label for="occurrence-client-close" class="ds-field-label">Notificação de fecho</label>
            <DateTimePicker id="occurrence-client-close" v-model="form.client_process_close_notification_date" type="date" class="ds-field mt-2" />
            <p v-if="form.errors.client_process_close_notification_date" class="ds-field-error mt-2">{{ form.errors.client_process_close_notification_date }}</p>
          </div>
          <div>
            <label for="occurrence-client-acceptance" class="ds-field-label">Aceitação do cliente</label>
            <BaseSelect id="occurrence-client-acceptance" v-model="form.client_acceptance" class="ds-field mt-2">
              <option :value="null">Não registada</option>
              <option :value="true">Aceite</option>
              <option :value="false">Rejeitada</option>
            </BaseSelect>
            <p v-if="form.errors.client_acceptance" class="ds-field-error mt-2">{{ form.errors.client_acceptance }}</p>
          </div>
          <div>
            <label for="occurrence-client-comments" class="ds-field-label">Comentários do cliente</label>
            <textarea id="occurrence-client-comments" v-model="form.client_acceptance_comments" class="ds-field mt-2 min-h-24 resize-y" />
            <p v-if="form.errors.client_acceptance_comments" class="ds-field-error mt-2">{{ form.errors.client_acceptance_comments }}</p>
          </div>
        </div>
      </section>
    </div>

    <aside class="space-y-6 xl:sticky xl:top-24">
      <section class="ds-panel p-5">
        <div class="flex items-start gap-3">
          <CalendarDaysIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          <div>
            <p class="ds-kicker">Ciclo de vida</p>
            <h2 class="ds-heading mt-2 text-base">Estado e datas</h2>
          </div>
        </div>

        <div class="mt-5 space-y-5">
          <div>
            <label for="occurrence-status" class="ds-field-label">Estado</label>
            <ComboboxEnhanced
              id="occurrence-status"
              v-model="form.status_id"
              class="mt-2"
              :has-error="Boolean(form.errors.status_id)"
              :load-options="loadStatuses"
              placeholder="Seleccionar estado"
            />
            <p v-if="form.errors.status_id" class="ds-field-error mt-2">{{ form.errors.status_id }}</p>
          </div>
          <div>
            <label for="occurrence-notification-date" class="ds-field-label">Notificação interna</label>
            <DateTimePicker id="occurrence-notification-date" v-model="form.notification_date" type="date" class="ds-field mt-2" />
            <p v-if="form.errors.notification_date" class="ds-field-error mt-2">{{ form.errors.notification_date }}</p>
          </div>
          <div>
            <label for="occurrence-resolved-date" class="ds-field-label">Data de resolução</label>
            <DateTimePicker id="occurrence-resolved-date" v-model="form.date_resolved" type="date" class="ds-field mt-2" />
            <p v-if="form.errors.date_resolved" class="ds-field-error mt-2">{{ form.errors.date_resolved }}</p>
          </div>
          <div>
            <label for="occurrence-closed-date" class="ds-field-label">Data de encerramento</label>
            <DateTimePicker id="occurrence-closed-date" v-model="form.date_closed" type="date" class="ds-field mt-2" />
            <p v-if="form.errors.date_closed" class="ds-field-error mt-2">{{ form.errors.date_closed }}</p>
          </div>
        </div>
      </section>

      <section class="ds-panel overflow-hidden">
        <div class="flex items-start gap-3 border-b border-[var(--ds-border)] p-5">
          <ShieldCheckIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          <div>
            <p class="ds-kicker">Risco e conformidade</p>
            <h2 class="ds-heading mt-2 text-base">Controlos associados</h2>
          </div>
        </div>
        <div class="divide-y divide-[var(--ds-border)]">
          <ToggleField
            id="occurrence-budget"
            v-model="form.has_risk_correction_budget"
            label="Orçamento de correcção"
            description="Existe verba aprovada para mitigar o risco."
          />
          <ToggleField
            id="occurrence-terms"
            v-model="form.has_non_conformity_terms"
            label="Termos de não conformidade"
            description="A ocorrência cumpre critérios formais de NC."
          />
          <ToggleField
            id="occurrence-risk-matrix"
            v-model="form.update_risk_matrix"
            label="Actualizar matriz de risco"
            description="A avaliação de risco precisa de revisão."
          />
        </div>
        <div v-if="!form.has_risk_correction_budget" class="border-t border-[var(--ds-border)] p-4">
          <label for="occurrence-no-budget-reason" class="ds-field-label">Justificação sem orçamento</label>
          <textarea id="occurrence-no-budget-reason" v-model="form.reason_for_no_risk_correction_budget" class="ds-field mt-2 min-h-24 resize-y" />
          <p v-if="form.errors.reason_for_no_risk_correction_budget" class="ds-field-error mt-2">{{ form.errors.reason_for_no_risk_correction_budget }}</p>
        </div>
      </section>

      <section class="ds-panel p-5">
        <div class="flex items-start gap-3">
          <ClipboardDocumentCheckIcon class="mt-0.5 h-5 w-5 shrink-0 text-[var(--ds-text-soft)]" />
          <div class="min-w-0 flex-1">
            <label for="occurrence-notes" class="text-sm font-bold text-[var(--ds-text)]">Observações internas</label>
            <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Contexto adicional para auditoria e acompanhamento.</p>
          </div>
        </div>
        <textarea id="occurrence-notes" v-model="form.obs" class="ds-field mt-4 min-h-28 resize-y" />
        <p v-if="form.errors.obs" class="ds-field-error mt-2">{{ form.errors.obs }}</p>
      </section>

      <section class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
        <div class="flex gap-3">
          <BuildingOffice2Icon class="mt-0.5 h-5 w-5 shrink-0 text-[var(--ds-text-soft)]" />
          <div>
            <h2 class="text-sm font-bold text-[var(--ds-text)]">Rastreabilidade</h2>
            <p class="mt-1 text-sm leading-6 text-[var(--ds-text-muted)]">Datas, responsáveis e decisões ficam associados ao dossier da ocorrência.</p>
          </div>
        </div>
      </section>
    </aside>
  </div>
</template>
