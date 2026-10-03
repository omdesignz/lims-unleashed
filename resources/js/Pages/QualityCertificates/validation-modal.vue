<script setup>
import Modal from "@/Components/modal.vue";
import ComboboxEnhanced from "@/Components/combobox-enhanced.vue";
import DocumentValidationSignature from "@/Components/document-validation-signature.vue";
import StatusChip from "@/Components/plano/StatusChip.vue";
import { computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import { loadSelectOptions, optionMappers } from "@/Utils/selectOptions";

/**
 * Validating a certificate signs every result behind it, so the validator sees each
 * result with who inserted, verified and approved it. When the responsible validator
 * is absent, a colleague signs on their behalf: the signer stays accountable and the
 * absent validator is recorded alongside.
 */
const props = defineProps({
  record: { type: Object, default: () => ({}) },
  action: { type: String, default: "approve" },
  title: { type: String, default: "" },
  url: { type: String, required: true },
  release: { type: Object, default: () => ({}) },
  results: { type: Array, default: () => [] },
});

const emit = defineEmits(["close"]);

const certificate = computed(() => props.record?.data ?? {});
const ready = computed(() => Boolean(props.release?.ready));
const pending = computed(() => Math.max(0, (props.release?.results ?? 0) - (props.release?.approved ?? 0)));

const form = useForm({
  signature: null,
  approve_on_behalf_of: false,
  signed_by_user_id: null,
});

const identity = computed(() => [
  ["Boletim", certificate.value.code || `#${certificate.value.id}`],
  ["Código laboratorial", certificate.value.lab_code || "—"],
  ["Cliente", certificate.value.customer || "—"],
  ["Produto", certificate.value.product || "—"],
]);

function formatDate(value) {
  if (!value) {
    return "";
  }

  return new Date(value).toLocaleString("pt-PT", { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" });
}

function closeModal() {
  emit("close");
}

function toggleOnBehalfOf() {
  form.approve_on_behalf_of = !form.approve_on_behalf_of;

  if (!form.approve_on_behalf_of) {
    form.signed_by_user_id = null;
  }
}

function submit() {
  form
    .transform((data) => ({
      signature: data.signature,
      approve_on_behalf_of: data.approve_on_behalf_of,
      signed_by_user_id: data.approve_on_behalf_of ? data.signed_by_user_id : null,
    }))
    .post(props.url, { preserveScroll: true, preserveState: false, onSuccess: closeModal });
}

function loadUsers(query, setOptions) {
  return loadSelectOptions("/users/getUser", query, setOptions, optionMappers.name);
}
</script>

<template>
  <Modal :show="true" max-width="5xl" @close="closeModal">
    <form class="min-w-0 space-y-6 p-5 sm:p-6" @submit.prevent="submit">
      <header class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
          <p class="pl-k pl-muted">Validação e assinatura</p>
          <h2 class="pl-d3 mt-1">{{ title || "Validação do Boletim de Resultados" }}</h2>
          <p class="mt-1 text-sm text-[var(--pl-muted)]">Ao assinar, confirma todos os resultados abaixo. Depois de validado, o boletim só muda através de uma revisão ISO.</p>
        </div>
        <StatusChip :tone="ready ? 'ok' : 'wait'">{{ ready ? "Pronto a validar" : `${pending} por aprovar` }}</StatusChip>
      </header>

      <dl class="pl-panel pl-facts pl-facts-2">
        <div v-for="[label, value] in identity" :key="label" class="pl-fact"><dt>{{ label }}</dt><dd>{{ value }}</dd></div>
      </dl>

      <section class="pl-panel" tabindex="0" aria-label="Resultados a validar">
        <div class="pl-panel-head">
          <h3 class="pl-k">Resultados a validar</h3>
          <span class="pl-k pl-faint pl-num">{{ release.approved ?? 0 }} / {{ release.results ?? results.length }} aprovados</span>
        </div>
        <DataTable v-if="results.length">
          <thead>
            <tr>
              <th scope="col">Parâmetro</th>
              <th scope="col">Resultado</th>
              <th scope="col">Inserido</th>
              <th scope="col">Verificado</th>
              <th scope="col">Aprovado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="result in results" :key="result.id">
              <td>{{ result.parameter || `Resultado #${result.id}` }}</td>
              <td class="pl-num">
                {{ result.value ?? "—" }}<template v-if="result.uncertainty"> ± {{ result.uncertainty }}</template>
                <span v-if="result.unit" class="text-[var(--pl-muted)]"> {{ result.unit }}</span>
              </td>
              <td v-for="stage in ['inserted', 'verified', 'approved']" :key="stage">
                <template v-if="result[stage]?.at">
                  <span class="block">{{ result[stage].by || "—" }}</span>
                  <span class="pl-num block text-[12px] text-[var(--pl-muted)]">{{ formatDate(result[stage].at) }}</span>
                </template>
                <StatusChip v-else tone="wait">Pendente</StatusChip>
              </td>
            </tr>
          </tbody>
        </DataTable>
        <p v-else class="p-4 text-sm text-[var(--pl-muted)]">Este boletim ainda não tem resultados registados.</p>
      </section>

      <p v-if="!ready" class="ds-field-error" role="alert">O boletim só pode ser validado quando todos os resultados estiverem aprovados.</p>

      <template v-else>
        <DocumentValidationSignature title="Assinatura do validador" @save="form.signature = $event" />
        <p v-if="form.errors.signature" class="ds-field-error" role="alert">{{ form.errors.signature }}</p>

        <section class="pl-panel">
          <div class="flex items-center justify-between gap-4 p-4">
            <div class="min-w-0">
              <h3 class="text-sm font-semibold">Assinar em nome de outro validador</h3>
              <p class="mt-1 text-[13px] text-[var(--pl-muted)]">Para ausências do validador responsável. A sua assinatura e identidade ficam registadas, junto com o validador ausente.</p>
            </div>
            <button
              type="button"
              role="switch"
              :aria-checked="form.approve_on_behalf_of"
              aria-label="Assinar em nome de outro validador"
              :class="['relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[var(--ds-focus)]', form.approve_on_behalf_of ? 'bg-[var(--pl-accent)]' : 'bg-[var(--pl-line-strong)]']"
              @click="toggleOnBehalfOf"
            >
              <span :class="['h-4 w-4 rounded-full bg-[var(--pl-raised)] transition-transform', form.approve_on_behalf_of ? 'translate-x-6' : 'translate-x-1']" />
            </button>
          </div>
          <div v-if="form.approve_on_behalf_of" class="ds-field-group border-t border-[var(--pl-line)] p-4">
            <label class="ds-field-label">Validador ausente <span class="ds-field-required">*</span></label>
            <ComboboxEnhanced v-model="form.signed_by_user_id" name="signed_by_user_id" :has-error="Boolean(form.errors.signed_by_user_id)" :load-options="loadUsers" placeholder="Pesquisar validador do laboratório" />
            <p v-if="form.errors.signed_by_user_id" class="ds-field-error" role="alert">{{ form.errors.signed_by_user_id }}</p>
          </div>
        </section>
      </template>

      <footer class="flex flex-col-reverse gap-2 border-t border-[var(--pl-line)] pt-5 sm:flex-row sm:items-center sm:justify-end">
        <button type="button" class="ds-button ds-button-quiet" @click="closeModal">Cancelar</button>
        <button
          type="submit"
          class="ds-button ds-button-primary"
          :disabled="form.processing || !ready || !form.signature || (form.approve_on_behalf_of && !form.signed_by_user_id)"
        >
          {{ form.processing ? "A validar…" : "Validar e assinar boletim" }}
        </button>
      </footer>
    </form>
  </Modal>
</template>
