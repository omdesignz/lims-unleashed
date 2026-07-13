<script setup>
import Modal from "@/Components/modal.vue";
import ComboboxEnhanced from "@/Components/combobox-enhanced.vue";
import DocumentValidationSignature from "@/Components/document-validation-signature.vue";
import { computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import {
  ArrowPathIcon,
  CheckBadgeIcon,
  CheckCircleIcon,
  UserGroupIcon,
} from "@heroicons/vue/24/outline";
import { loadSelectOptions, optionMappers } from "@/Utils/selectOptions";

const props = defineProps({
  record: {
    type: Object,
    default: () => ({}),
  },
  action: {
    type: String,
    required: true,
  },
  title: {
    type: String,
    default: "",
  },
  url: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(["close"]);

const form = useForm({
  verified_on_behalf_of:
    props.record?.data?.verified_on_behalf_of || false,
  approve_on_behalf_of:
    props.record?.data?.approve_on_behalf_of || false,
  user_id: props.record?.data?.signed_by_user_id || null,
  signature: null,
  id: props.record?.data?.id || null,
});

const isVerification = computed(() => props.action === "verify");

const isOnBehalfOf = computed(() => {
  return isVerification.value
    ? form.verified_on_behalf_of
    : form.approve_on_behalf_of;
});

const modalTitle = computed(() => {
  if (props.title) {
    return props.title;
  }

  return isVerification.value
    ? trans("gestlab.general.labels.quality_certificates.verify_certificate")
    : trans("gestlab.general.labels.quality_certificates.validate_certificate");
});

const modalDescription = computed(() => {
  return isVerification.value
    ? trans("gestlab.general.labels.quality_certificates.verify_description")
    : trans("gestlab.general.labels.quality_certificates.validate_description");
});

const signatureTitle = computed(() => {
  return isVerification.value
    ? trans("gestlab.general.labels.quality_certificates.verify_signature")
    : trans("gestlab.general.labels.quality_certificates.validate_signature");
});

const actionButtonText = computed(() => {
  return isVerification.value
    ? trans("gestlab.general.labels.quality_certificates.verify_certificate")
    : trans("gestlab.general.labels.quality_certificates.validate_certificate");
});

watch(
  () => props.record,
  (record) => {
    if (!record) {
      return;
    }

    form.verified_on_behalf_of =
      record.data?.verified_on_behalf_of || false;
    form.approve_on_behalf_of =
      record.data?.approve_on_behalf_of || false;
    form.user_id = record.data?.signed_by_user_id || null;
    form.id = record.data?.id || null;
  },
  { immediate: true },
);

function closeModal() {
  emit("close");
}

function toggleOnBehalfOf() {
  if (isVerification.value) {
    form.verified_on_behalf_of = !form.verified_on_behalf_of;
  } else {
    form.approve_on_behalf_of = !form.approve_on_behalf_of;
  }

  if (!isOnBehalfOf.value) {
    form.user_id = null;
  }
}

function updateRecord() {
  const payload = {
    certificate: form.id,
    signature: form.signature,
  };

  if (isVerification.value) {
    payload.verified_on_behalf_of = form.verified_on_behalf_of;

    if (form.verified_on_behalf_of && form.user_id) {
      payload.signed_by_user_id = form.user_id;
    }
  } else {
    payload.approve_on_behalf_of = form.approve_on_behalf_of;

    if (form.approve_on_behalf_of && form.user_id) {
      payload.signed_by_user_id = form.user_id;
    }
  }

  form.transform(() => payload).post(props.url, {
    preserveScroll: true,
    preserveState: false,
    onSuccess: closeModal,
  });
}

function loadUsers(query, setOptions) {
  return loadSelectOptions(
    "/users/getUser",
    query,
    setOptions,
    optionMappers.name,
  );
}
</script>

<template>
  <Modal :show="true" max-width="2xl" @close="closeModal">
    <div class="min-w-0 space-y-5 p-5 sm:p-6">
      <header class="ds-command-surface p-5">
        <div class="flex items-start gap-4">
          <div class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-[rgb(var(--primary-800-rgb))] text-white dark:bg-[rgb(var(--primary-300-rgb))] dark:text-[rgb(var(--primary-950-rgb))]">
            <CheckBadgeIcon class="h-5 w-5" />
          </div>
          <div class="min-w-0">
            <p class="ds-kicker">Gate de libertacao</p>
            <h2 class="ds-heading mt-2 text-lg">{{ modalTitle }}</h2>
            <p class="ds-copy mt-1 text-sm">{{ modalDescription }}</p>
          </div>
        </div>
      </header>

      <form class="space-y-5" @submit.prevent="updateRecord">
        <DocumentValidationSignature
          :title="signatureTitle"
          @save="form.signature = $event"
        />

        <section class="ds-panel overflow-hidden">
          <div class="flex items-center justify-between gap-4 px-5 py-4">
            <div class="flex min-w-0 items-start gap-3">
              <div class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)]">
                <UserGroupIcon class="h-4 w-4" />
              </div>
              <div>
                <h3 class="ds-heading text-sm">
                  {{ $t("gestlab.general.labels.quality_certificates.sign_on_behalf") }}
                </h3>
                <p class="ds-copy mt-1 text-xs">
                  {{ $t("gestlab.general.labels.quality_certificates.sign_on_behalf_description") }}
                </p>
              </div>
            </div>

            <button
              type="button"
              role="switch"
              :aria-checked="isOnBehalfOf"
              :aria-label="isOnBehalfOf
                ? $t('gestlab.general.labels.quality_certificates.disable_on_behalf')
                : $t('gestlab.general.labels.quality_certificates.enable_on_behalf')"
              :class="[
                'relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[var(--ds-focus)]',
                isOnBehalfOf
                  ? 'bg-[rgb(var(--primary-700-rgb))] dark:bg-[rgb(var(--primary-300-rgb))]'
                  : 'bg-[var(--ds-border-strong)]',
              ]"
              @click="toggleOnBehalfOf"
            >
              <span
                :class="[
                  'h-4 w-4 rounded-full bg-[var(--ds-panel-raised)] transition-transform',
                  isOnBehalfOf ? 'translate-x-6' : 'translate-x-1',
                ]"
              />
            </button>
          </div>

          <div
            v-if="isOnBehalfOf"
            class="border-t border-[var(--ds-border)] px-5 py-4"
          >
            <div class="ds-field-group">
              <label class="ds-field-label">
                {{ $t("gestlab.general.labels.quality_certificates.select_user") }}
                <span class="ds-field-required">*</span>
              </label>
              <ComboboxEnhanced
                v-model="form.user_id"
                name="user_id"
                :has-error="Boolean(form.errors.user_id)"
                :load-options="loadUsers"
                :placeholder="$t('gestlab.general.placeholders.select_user')"
              />
              <p v-if="form.errors.user_id" class="ds-field-error">
                {{ form.errors.user_id }}
              </p>
              <p class="ds-field-hint">
                {{ $t("gestlab.general.labels.quality_certificates.on_behalf_of_warning") }}
              </p>
            </div>
          </div>
        </section>

        <div v-if="form.signature" class="lims-status-strip p-4">
          <div class="flex items-start gap-3">
            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-200">
              <CheckCircleIcon class="h-4 w-4" />
            </span>
            <div>
              <h3 class="ds-heading text-sm">
                {{ $t("gestlab.general.labels.quality_certificates.signature_ready") }}
              </h3>
              <p class="ds-copy mt-1 text-xs">
                {{ $t("gestlab.general.labels.quality_certificates.signature_ready_description") }}
              </p>
            </div>
          </div>
        </div>

        <footer class="flex flex-col gap-4 border-t border-[var(--ds-border)] pt-5 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-start gap-2 text-xs font-semibold text-[var(--ds-text-muted)]">
            <span class="lims-status-dot lims-status-dot-hold mt-0.5" />
            {{ $t("gestlab.general.labels.quality_certificates.action_irreversible") }}
          </div>

          <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button
              type="button"
              class="ds-button ds-button-secondary"
              @click="closeModal"
            >
              {{ $t("gestlab.general.buttons.cancel") }}
            </button>

            <button
              type="submit"
              class="ds-button ds-button-primary"
              :disabled="form.processing || !form.signature"
            >
              <template v-if="form.processing">
                <ArrowPathIcon class="h-4 w-4 animate-spin" />
                {{ $t("gestlab.general.buttons.processing") }}
              </template>
              <template v-else>
                <CheckBadgeIcon class="h-4 w-4" />
                {{ actionButtonText }}
              </template>
            </button>
          </div>
        </footer>
      </form>
    </div>
  </Modal>
</template>
