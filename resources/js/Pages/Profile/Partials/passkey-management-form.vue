<script setup>
import { router } from "@inertiajs/vue3";
import { ArrowPathIcon, FingerPrintIcon, TrashIcon } from "@heroicons/vue/24/outline";
import { startRegistration } from "@simplewebauthn/browser";
import { ref } from "vue";
import ConfirmDialog from "@/Components/confirm-dialog.vue";

const props = defineProps({
  passkeys: { type: Array, default: () => [] },
  routes: {
    type: Object,
    default: () => ({
      registrationOptions: "security.passkeys.registration-options",
      store: "security.passkeys.store",
      destroy: "security.passkeys.destroy",
    }),
  },
});

const passkeyName = ref("");
const isRegistering = ref(false);
const removingPasskeyId = ref(null);
const errorMessage = ref("");
const successMessage = ref("");
const pendingPasskey = ref(null);
const showRemoveConfirmation = ref(false);

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";
}

function formatDate(value) {
  if (!value) {
    return "N/D";
  }

  const date = new Date(value);

  return Number.isNaN(date.getTime())
    ? "N/D"
    : new Intl.DateTimeFormat("pt-PT", { dateStyle: "medium", timeStyle: "short" }).format(date);
}

async function registerPasskey() {
  errorMessage.value = "";
  successMessage.value = "";
  isRegistering.value = true;

  try {
    const optionsResponse = await fetch(route(props.routes.registrationOptions), {
      method: "POST",
      credentials: "same-origin",
      headers: { Accept: "application/json", "X-CSRF-TOKEN": csrfToken() },
    });

    if (!optionsResponse.ok) {
      throw new Error("Não foi possível iniciar o registo da chave de acesso.");
    }

    const options = await optionsResponse.json();
    const registration = await startRegistration({ optionsJSON: options });
    const storeResponse = await fetch(route(props.routes.store), {
      method: "POST",
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": csrfToken(),
      },
      body: JSON.stringify({
        name: passkeyName.value || "Chave de acesso principal",
        passkey: JSON.stringify(registration),
      }),
    });
    const payload = await storeResponse.json().catch(() => ({}));

    if (!storeResponse.ok) {
      throw new Error(payload.message || "Não foi possível guardar a chave de acesso.");
    }

    passkeyName.value = "";
    successMessage.value = payload.message || "Chave de acesso registada com êxito.";
    router.reload({ only: ["passkeys", "security"], preserveScroll: true });
  } catch (error) {
    errorMessage.value = error?.message || "Não foi possível registar a chave de acesso.";
  } finally {
    isRegistering.value = false;
  }
}

function requestPasskeyRemoval(passkey) {
  pendingPasskey.value = passkey;
  showRemoveConfirmation.value = true;
}

async function removePasskey() {
  const passkey = pendingPasskey.value;

  if (!passkey) {
    return;
  }

  showRemoveConfirmation.value = false;
  errorMessage.value = "";
  successMessage.value = "";
  removingPasskeyId.value = passkey.id;

  try {
    const response = await fetch(route(props.routes.destroy, passkey.id), {
      method: "DELETE",
      credentials: "same-origin",
      headers: { Accept: "application/json", "X-CSRF-TOKEN": csrfToken() },
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(payload.message || "Não foi possível remover a chave de acesso.");
    }

    successMessage.value = payload.message || "Chave de acesso removida com sucesso.";
    router.reload({ only: ["passkeys", "security"], preserveScroll: true });
  } catch (error) {
    errorMessage.value = error?.message || "Não foi possível remover a chave de acesso.";
  } finally {
    removingPasskeyId.value = null;
    pendingPasskey.value = null;
  }
}
</script>

<template>
  <div class="space-y-5">
    <form class="grid gap-4 border-b border-[var(--ds-border)] pb-5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end" @submit.prevent="registerPasskey">
      <div class="ds-field-group"><label class="ds-field-label">Nome da chave de acesso</label><BaseInput v-model="passkeyName" type="text" maxlength="255" class="ds-field" placeholder="Ex.: MacBook do laboratório" /><p class="ds-field-hint">Use um nome que identifique claramente o dispositivo.</p></div>
      <button type="submit" class="ds-button ds-button-primary" :disabled="isRegistering"><ArrowPathIcon v-if="isRegistering" class="h-4 w-4 animate-spin" /><FingerPrintIcon v-else class="h-4 w-4" />{{ isRegistering ? "A registar..." : "Registar chave de acesso" }}</button>
    </form>

    <p v-if="successMessage" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-400/20 dark:bg-emerald-500/10 dark:text-emerald-200">{{ successMessage }}</p>
    <p v-if="errorMessage" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-400/20 dark:bg-rose-500/10 dark:text-rose-200">{{ errorMessage }}</p>

    <div v-if="passkeys.length" class="divide-y divide-[var(--ds-border)] rounded-lg border border-[var(--ds-border)]">
      <div v-for="passkey in passkeys" :key="passkey.id" class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex min-w-0 items-start gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))] ring-1 ring-[var(--ds-border)]"><FingerPrintIcon class="h-4 w-4" /></span><div class="min-w-0"><h4 class="break-words text-sm font-bold text-[var(--ds-text)]">{{ passkey.name }}</h4><p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">Registada em {{ formatDate(passkey.created_at) }}</p><p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">Última utilização: {{ passkey.last_used_at ? formatDate(passkey.last_used_at) : "Ainda não utilizada" }}</p></div></div>
        <button type="button" class="ds-button ds-button-secondary text-rose-700 dark:text-rose-200" :disabled="removingPasskeyId === passkey.id" @click="requestPasskeyRemoval(passkey)"><ArrowPathIcon v-if="removingPasskeyId === passkey.id" class="h-4 w-4 animate-spin" /><TrashIcon v-else class="h-4 w-4" />{{ removingPasskeyId === passkey.id ? "A remover..." : "Remover" }}</button>
      </div>
    </div>
    <div v-else class="ds-empty-state py-8 text-center"><FingerPrintIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" /><p class="mt-3 text-sm font-bold text-[var(--ds-text)]">Sem chaves de acesso registadas</p><p class="ds-copy mt-1 text-sm">Associe um dispositivo para autenticação sem palavra-passe.</p></div>

    <ConfirmDialog
      v-if="showRemoveConfirmation"
      title="Remover chave de acesso"
      :description="`A chave de acesso ${pendingPasskey?.name || ''} deixará de poder autenticar esta conta.`"
      confirm="Remover"
      @confirmed="removePasskey"
      @canceled="showRemoveConfirmation = false; pendingPasskey = null"
    />
  </div>
</template>
