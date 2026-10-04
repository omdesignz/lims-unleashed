<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import { useForm } from "@inertiajs/vue3";
import {
  Check as CheckIcon,
  TriangleAlert as ExclamationTriangleIcon,
  KeyRound as KeyIcon,
  Search as MagnifyingGlassIcon,
  Users as UserGroupIcon,
  X as XMarkIcon,
} from "@lucide/vue";
import { computed, ref } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, required: true },
  permissions: { type: Array, default: () => [] },
});

const permissionSearch = ref("");
const form = useForm("RoleEditor", {
  id: props.record?.id,
  name: props.record?.name ?? "",
  label: props.record?.label ?? "",
  guard_name: props.record?.guard_name ?? "web",
  permissions: props.record?.permissions?.map((permission) => permission.value) ?? [],
});

const normalizedSearch = computed(() => permissionSearch.value.trim().toLocaleLowerCase());
const filteredPermissions = computed(() => {
  if (!normalizedSearch.value) {
    return props.permissions;
  }

  return props.permissions.filter((permission) =>
    permission.label?.toLocaleLowerCase().includes(normalizedSearch.value),
  );
});
const selectedPermissionIds = computed(() => new Set(form.permissions));
const selectedPermissionsCount = computed(() => form.permissions.length);
const remainingPermissionsCount = computed(() => Math.max(props.permissions.length - selectedPermissionsCount.value, 0));
const selectedVisibleCount = computed(() => filteredPermissions.value.filter((permission) =>
  selectedPermissionIds.value.has(permission.value),
).length);
const areAllVisibleSelected = computed(() =>
  filteredPermissions.value.length > 0 && selectedVisibleCount.value === filteredPermissions.value.length,
);
const isFormValid = computed(() =>
  form.name.trim().length > 0
  && form.label.trim().length > 0
  && form.guard_name.trim().length > 0
  && form.permissions.length > 0,
);

function togglePermission(permission) {
  if (selectedPermissionIds.value.has(permission.value)) {
    form.permissions = form.permissions.filter((permissionId) => permissionId !== permission.value);
    return;
  }

  form.permissions = [...form.permissions, permission.value];
}

function toggleVisiblePermissions() {
  const visiblePermissionIds = new Set(filteredPermissions.value.map((permission) => permission.value));

  if (areAllVisibleSelected.value) {
    form.permissions = form.permissions.filter((permissionId) => !visiblePermissionIds.has(permissionId));
    return;
  }

  form.permissions = [...new Set([...form.permissions, ...visiblePermissionIds])];
}

function submit() {
  if (!isFormValid.value) {
    return;
  }

  form.put(route("roles.update", { role: form.id }), {
    preserveScroll: true,
    preserveState: false,
  });
}
</script>

<template>
  <form class="pl-page space-y-6" @submit.prevent="submit">
    <PageHeader :trail="[{ title: 'Funções', url: route('roles.index') }, { title: 'Editar função' }]" title="Editar função">
      <template #lede>Ajuste a identidade e conceda apenas as permissões necessárias a <span class="font-bold text-[var(--ds-text)]">{{ form.label || form.name }}</span>.</template>
      <template #badges>
        <span
          class="ds-badge"
          :class="form.isDirty ? 'ds-badge-warning' : 'ds-badge-neutral'"
        >
          {{ form.isDirty ? "Alterações por guardar" : "Sem alterações" }}
        </span>
      </template>
      <template #actions>
        <button
          type="submit"
          class="ds-button ds-button-primary"
          :disabled="form.processing || !form.isDirty || !isFormValid"
        >
          <CheckIcon class="h-4 w-4" />
          {{ form.processing ? "A guardar..." : "Guardar função" }}
        </button>
      </template>
    </PageHeader>

    <dl class="pl-cells">
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Permissões atribuídas</dt>
        <dd class="pl-cell-value">{{ selectedPermissionsCount }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Disponíveis</dt>
        <dd class="pl-cell-value">{{ remainingPermissionsCount }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Guard</dt>
        <dd class="pl-cell-text">{{ form.guard_name || "-" }}</dd>
      </div>
    </dl>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_19rem] xl:items-start">
      <div class="space-y-6">
        <section class="ds-panel p-5 sm:p-6">
          <div class="border-b border-[var(--ds-border)] pb-4">
            <p class="ds-kicker">Identidade da função</p>
            <h2 class="ds-heading mt-2 text-lg">Nome, etiqueta e contexto</h2>
            <p class="ds-copy mt-1 text-sm">Mantenha os identificadores estáveis para preservar políticas e integrações.</p>
          </div>

          <div class="mt-6 space-y-6">
            <div class="grid gap-2 sm:grid-cols-[13rem_minmax(0,1fr)] sm:items-start sm:gap-6">
              <div>
                <label for="role-name" class="ds-field-label">Nome técnico</label>
                <p class="ds-field-hint mt-1">Identificador usado pelas políticas.</p>
              </div>
              <div>
                <BaseInput id="role-name" v-model="form.name" type="text" autocomplete="off" class="ds-field font-mono" required />
                <p v-if="form.errors.name" class="ds-field-error mt-2">{{ form.errors.name }}</p>
              </div>
            </div>

            <div class="border-t border-[var(--ds-border)] pt-6">
              <div class="grid gap-2 sm:grid-cols-[13rem_minmax(0,1fr)] sm:items-start sm:gap-6">
                <div>
                  <label for="role-label" class="ds-field-label">Etiqueta</label>
                  <p class="ds-field-hint mt-1">Nome apresentado aos administradores.</p>
                </div>
                <div>
                  <BaseInput id="role-label" v-model="form.label" type="text" class="ds-field" required />
                  <p v-if="form.errors.label" class="ds-field-error mt-2">{{ form.errors.label }}</p>
                </div>
              </div>
            </div>

            <div class="border-t border-[var(--ds-border)] pt-6">
              <div class="grid gap-2 sm:grid-cols-[13rem_minmax(0,1fr)] sm:items-start sm:gap-6">
                <div>
                  <label for="role-guard" class="ds-field-label">Guard</label>
                  <p class="ds-field-hint mt-1">Contexto de autenticação protegido.</p>
                </div>
                <div>
                  <BaseInput id="role-guard" v-model="form.guard_name" type="text" autocomplete="off" class="ds-field font-mono" required />
                  <p v-if="form.errors.guard_name" class="ds-field-error mt-2">{{ form.errors.guard_name }}</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] p-5 sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
              <div>
                <p class="ds-kicker">Matriz de autorização</p>
                <h2 class="ds-heading mt-2 text-lg">Permissões</h2>
                <p class="ds-copy mt-1 text-sm">Pesquise e seleccione as operações necessárias para esta responsabilidade.</p>
              </div>
              <button
                type="button"
                class="ds-button ds-button-secondary whitespace-nowrap"
                :disabled="filteredPermissions.length === 0"
                @click="toggleVisiblePermissions"
              >
                {{ areAllVisibleSelected ? "Desmarcar visíveis" : "Seleccionar visíveis" }}
              </button>
            </div>

            <div class="relative mt-5">
              <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-[var(--ds-text-soft)]" />
              <BaseInput
                id="permission-search"
                v-model="permissionSearch"
                type="search"
                class="ds-field pl-10 pr-10"
                placeholder="Pesquisar permissões"
              />
              <button
                v-if="permissionSearch"
                type="button"
                class="absolute right-2 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-md text-[var(--ds-text-soft)] hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)]"
                title="Limpar pesquisa"
                aria-label="Limpar pesquisa"
                @click="permissionSearch = ''"
              >
                <XMarkIcon class="h-4 w-4" />
              </button>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs font-semibold text-[var(--ds-text-muted)]">
              <span>{{ filteredPermissions.length }} resultados</span>
              <span>{{ selectedVisibleCount }} visíveis seleccionados</span>
              <span>{{ selectedPermissionsCount }} atribuídos no total</span>
            </div>
          </div>

          <div v-if="filteredPermissions.length" class="divide-y divide-[var(--ds-border)]">
            <label
              v-for="permission in filteredPermissions"
              :key="permission.value"
              class="flex cursor-pointer items-start gap-3 px-5 py-4 transition-colors hover:bg-[var(--ds-panel-subtle)] sm:px-6"
            >
              <CheckboxInput
                type="checkbox"
                class="mt-0.5 h-4 w-4 rounded border-[var(--ds-border-strong)] text-[rgb(var(--primary-700-rgb))] focus:ring-[rgb(var(--primary-600-rgb))]"
                :checked="selectedPermissionIds.has(permission.value)"
                @change="togglePermission(permission)"
              />
              <span class="min-w-0 flex-1">
                <span class="block text-sm font-bold text-[var(--ds-text)]">{{ permission.label || `Permissão ${permission.value}` }}</span>
                <span class="mt-1 block text-xs font-semibold text-[var(--ds-text-soft)]">ID {{ permission.value }}</span>
              </span>
              <span v-if="selectedPermissionIds.has(permission.value)" class="ds-badge ds-badge-success">Atribuída</span>
            </label>
          </div>

          <div v-else class="px-5 py-12 text-center sm:px-6">
            <MagnifyingGlassIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
            <h3 class="ds-heading mt-3 text-sm">Nenhuma permissão encontrada</h3>
            <p class="ds-copy mt-1 text-sm">Ajuste a pesquisa para localizar outra operação.</p>
            <button type="button" class="ds-button ds-button-secondary mt-4" @click="permissionSearch = ''">Limpar pesquisa</button>
          </div>

          <p v-if="form.errors.permissions" class="ds-field-error border-t border-[var(--ds-border)] px-5 py-3 sm:px-6">
            {{ form.errors.permissions }}
          </p>
        </section>
      </div>

      <aside class="space-y-4 xl:sticky xl:top-24">
        <section class="ds-panel p-5">
          <div class="flex items-start gap-3">
            <UserGroupIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
            <div>
              <p class="ds-kicker">Resumo de acesso</p>
              <h2 class="ds-heading mt-2 text-base">{{ selectedPermissionsCount }} de {{ permissions.length }}</h2>
            </div>
          </div>
          <dl class="mt-5 divide-y divide-[var(--ds-border)] border-y border-[var(--ds-border)]">
            <div class="flex items-center justify-between gap-4 py-3 text-sm">
              <dt class="font-semibold text-[var(--ds-text-muted)]">Atribuídas</dt>
              <dd class="font-bold text-[var(--ds-text)]">{{ selectedPermissionsCount }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 py-3 text-sm">
              <dt class="font-semibold text-[var(--ds-text-muted)]">Não atribuídas</dt>
              <dd class="font-bold text-[var(--ds-text)]">{{ remainingPermissionsCount }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 py-3 text-sm">
              <dt class="font-semibold text-[var(--ds-text-muted)]">Resultados visíveis</dt>
              <dd class="font-bold text-[var(--ds-text)]">{{ filteredPermissions.length }}</dd>
            </div>
          </dl>
        </section>

        <section class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-amber-950 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-100">
          <div class="flex gap-3">
            <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 shrink-0" />
            <div>
              <h2 class="text-sm font-bold">Princípio do menor privilégio</h2>
              <p class="mt-1 text-sm leading-6">Atribua apenas operações exigidas pela responsabilidade. As alterações afetam todos os utilizadores desta função.</p>
            </div>
          </div>
        </section>

        <section class="ds-panel p-5">
          <div class="flex gap-3">
            <KeyIcon class="mt-0.5 h-5 w-5 shrink-0 text-[var(--ds-text-soft)]" />
            <div>
              <h2 class="text-sm font-bold text-[var(--ds-text)]">Validação antes de guardar</h2>
              <p class="mt-1 text-sm leading-6 text-[var(--ds-text-muted)]">Nome, etiqueta, guard e pelo menos uma permissão são obrigatórios.</p>
            </div>
          </div>
        </section>
      </aside>
    </div>
  </form>
</template>
