<template>
  <div class="space-y-6">
    <section class="ds-command-surface overflow-hidden">
      <div class="flex flex-col gap-5 px-5 py-5 sm:px-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex min-w-0 items-start gap-4">
          <img
            v-if="record.profile_photo_url"
            :src="record.profile_photo_url"
            :alt="record.name"
            class="h-14 w-14 shrink-0 rounded-lg border border-[var(--ds-border)] object-cover"
          >
          <span v-else class="grid h-14 w-14 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-lg font-bold text-[rgb(var(--primary-700-rgb))]">
            {{ initials }}
          </span>

          <div class="min-w-0">
            <p class="ds-kicker">Dossier de pessoal</p>
            <h1 class="ds-heading mt-1 truncate text-xl sm:text-2xl">{{ form.name }}</h1>
            <p class="mt-1 truncate text-sm font-semibold text-[var(--ds-text-muted)]">{{ form.email }}</p>
            <div class="mt-2 flex flex-wrap items-center gap-2">
              <span v-for="role in form.roles" :key="role.value" class="ds-badge ds-badge-info">{{ role.label }}</span>
              <span v-if="form.roles.length === 0" class="ds-badge ds-badge-neutral">Sem função atribuída</span>
            </div>
          </div>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2">
          <span class="ds-badge" :class="formStatus.className">
            <component :is="formStatus.icon" class="h-3.5 w-3.5" />
            {{ formStatus.label }}
          </span>
          <button type="button" class="ds-button ds-button-secondary" @click="toggleEditMode">
            <XMarkIcon v-if="editUserInfo" class="h-4 w-4" />
            <PencilSquareIcon v-else class="h-4 w-4" />
            {{ editUserInfo ? 'Cancelar edição' : 'Editar dossier' }}
          </button>
        </div>
      </div>

      <dl class="grid border-t border-[var(--ds-border)] sm:grid-cols-2 xl:grid-cols-4 xl:divide-x xl:divide-[var(--ds-border)]">
        <div v-for="metric in competenceMetrics" :key="metric.label" class="border-b border-[var(--ds-border)] px-5 py-4 sm:[&:nth-child(odd)]:border-r xl:border-b-0 xl:[&:nth-child(odd)]:border-r-0">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</dt>
          <dd class="mt-1 flex items-baseline gap-2">
            <span class="text-2xl font-bold tabular-nums" :class="metric.tone">{{ metric.value }}</span>
            <span class="text-xs font-semibold text-[var(--ds-text-soft)]">{{ metric.detail }}</span>
          </dd>
        </div>
      </dl>
    </section>

    <section class="ds-command-surface overflow-hidden">
      <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
        <div class="flex items-start gap-3">
          <IdentificationIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))]" />
          <div>
            <h2 class="ds-heading text-base">Identificação e contacto</h2>
            <p class="ds-copy mt-1 text-sm">Dados usados na atribuição de trabalho, emissão de evidência e contacto interno.</p>
          </div>
        </div>
      </header>

      <div class="grid gap-x-6 gap-y-5 px-5 py-5 sm:grid-cols-2 sm:px-6 xl:grid-cols-3">
        <div class="ds-field-group">
          <label for="user-username" class="ds-field-label">Nome de utilizador</label>
          <BaseInput v-if="editUserInfo" id="user-username" v-model="form.username" type="text" class="ds-field" autocomplete="username" :aria-invalid="Boolean(form.errors.username)" />
          <p v-else class="min-h-10 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2 text-sm font-semibold text-[var(--ds-text)]">{{ displayValue(form.username) }}</p>
          <p v-if="form.errors.username" class="ds-field-error">{{ form.errors.username }}</p>
        </div>

        <div class="ds-field-group">
          <label for="user-name" class="ds-field-label">Nome completo</label>
          <BaseInput v-if="editUserInfo" id="user-name" v-model="form.name" type="text" class="ds-field" autocomplete="name" :aria-invalid="Boolean(form.errors.name)" />
          <p v-else class="min-h-10 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2 text-sm font-semibold text-[var(--ds-text)]">{{ displayValue(form.name) }}</p>
          <p v-if="form.errors.name" class="ds-field-error">{{ form.errors.name }}</p>
        </div>

        <div class="ds-field-group">
          <label for="user-gender" class="ds-field-label">Género</label>
          <BaseSelect v-if="editUserInfo" id="user-gender" v-model="form.gender" class="ds-field" :aria-invalid="Boolean(form.errors.gender)">
            <option v-for="option in genderOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
          </BaseSelect>
          <p v-else class="min-h-10 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2 text-sm font-semibold text-[var(--ds-text)]">{{ genderLabel }}</p>
          <p v-if="form.errors.gender" class="ds-field-error">{{ form.errors.gender }}</p>
        </div>

        <div class="ds-field-group">
          <label for="user-email" class="ds-field-label">Email</label>
          <BaseInput v-if="editUserInfo" id="user-email" v-model="form.email" type="email" class="ds-field" autocomplete="email" :aria-invalid="Boolean(form.errors.email)" />
          <p v-else class="min-h-10 break-all rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2 text-sm font-semibold text-[var(--ds-text)]">{{ displayValue(form.email) }}</p>
          <p v-if="form.errors.email" class="ds-field-error">{{ form.errors.email }}</p>
        </div>

        <div class="ds-field-group">
          <label for="user-id-number" class="ds-field-label">Documento de identificação</label>
          <BaseInput v-if="editUserInfo" id="user-id-number" v-model="form.id_number" type="text" class="ds-field font-mono" :aria-invalid="Boolean(form.errors.id_number)" />
          <p v-else class="min-h-10 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2 font-mono text-sm font-semibold text-[var(--ds-text)]">{{ displayValue(form.id_number) }}</p>
          <p v-if="form.errors.id_number" class="ds-field-error">{{ form.errors.id_number }}</p>
        </div>

        <div class="ds-field-group">
          <label class="ds-field-label">Data de nascimento</label>
          <DatePickerEnhanced v-if="editUserInfo" v-model.string="form.dob" mode="date" locale="pt" :masks="dateMasks" :has-error="Boolean(form.errors.dob)" />
          <p v-else class="min-h-10 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2 text-sm font-semibold text-[var(--ds-text)]">{{ formatDate(form.dob) }}</p>
          <p v-if="form.errors.dob" class="ds-field-error">{{ form.errors.dob }}</p>
        </div>

        <div class="ds-field-group">
          <label for="user-primary-phone" class="ds-field-label">Telefone principal</label>
          <BaseInput v-if="editUserInfo" id="user-primary-phone" v-model="form.primary_phone" type="tel" class="ds-field" autocomplete="tel" :aria-invalid="Boolean(form.errors.primary_phone)" />
          <p v-else class="min-h-10 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2 text-sm font-semibold text-[var(--ds-text)]">{{ displayValue(form.primary_phone) }}</p>
          <p v-if="form.errors.primary_phone" class="ds-field-error">{{ form.errors.primary_phone }}</p>
        </div>

        <div class="ds-field-group">
          <label for="user-secondary-phone" class="ds-field-label">Telefone alternativo</label>
          <BaseInput v-if="editUserInfo" id="user-secondary-phone" v-model="form.secondary_phone" type="tel" class="ds-field" :aria-invalid="Boolean(form.errors.secondary_phone)" />
          <p v-else class="min-h-10 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2 text-sm font-semibold text-[var(--ds-text)]">{{ displayValue(form.secondary_phone) }}</p>
          <p v-if="form.errors.secondary_phone" class="ds-field-error">{{ form.errors.secondary_phone }}</p>
        </div>
      </div>
    </section>

    <section class="ds-command-surface overflow-hidden">
      <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
        <div class="flex items-start gap-3">
          <ShieldCheckIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))]" />
          <div>
            <h2 class="ds-heading text-base">Vínculo organizacional e acesso</h2>
            <p class="ds-copy mt-1 text-sm">Departamentos, funções e autorizações efetivas deste utilizador.</p>
          </div>
        </div>
      </header>

      <div class="divide-y divide-[var(--ds-border)]">
        <div class="grid gap-3 px-5 py-5 sm:px-6 lg:grid-cols-[14rem_minmax(0,1fr)]">
          <div>
            <h3 class="ds-heading text-sm">Departamentos</h3>
            <p class="ds-copy mt-1 text-xs">Unidades onde o colaborador pode operar.</p>
          </div>
          <div>
            <ComboboxMultipleEnhanced v-if="editUserInfo" v-model="form.departments" :load-options="loadDepartments" multiple placeholder="Selecionar departamentos" />
            <div v-else class="flex min-h-10 flex-wrap items-center gap-2">
              <span v-for="department in form.departments" :key="department.value" class="ds-badge ds-badge-neutral">{{ department.label }}</span>
              <span v-if="form.departments.length === 0" class="text-sm font-semibold text-[var(--ds-text-soft)]">Sem departamento atribuído</span>
            </div>
            <p v-if="form.errors.departments" class="ds-field-error mt-2">{{ form.errors.departments }}</p>
          </div>
        </div>

        <div class="grid gap-3 px-5 py-5 sm:px-6 lg:grid-cols-[14rem_minmax(0,1fr)]">
          <div>
            <h3 class="ds-heading text-sm">Funções</h3>
            <p class="ds-copy mt-1 text-xs">Perfis de acesso e responsabilidade.</p>
          </div>
          <div>
            <ComboboxMultipleEnhanced v-if="editUserInfo" v-model="form.roles" :load-options="loadRoles" multiple placeholder="Selecionar funções" />
            <div v-else class="flex min-h-10 flex-wrap items-center gap-2">
              <span v-for="role in form.roles" :key="role.value" class="ds-badge ds-badge-success">{{ role.label }}</span>
              <span v-if="form.roles.length === 0" class="text-sm font-semibold text-[var(--ds-text-soft)]">Sem função atribuída</span>
            </div>
            <p v-if="form.errors.roles" class="ds-field-error mt-2">{{ form.errors.roles }}</p>
          </div>
        </div>

        <div class="grid gap-4 px-5 py-5 sm:px-6 lg:grid-cols-[14rem_minmax(0,1fr)]">
          <div>
            <h3 class="ds-heading text-sm">Permissões diretas</h3>
            <p class="ds-copy mt-1 text-xs">Exceções atribuídas além das permissões herdadas das funções.</p>
            <p class="mt-3 text-xs font-bold tabular-nums text-[var(--ds-text-muted)]">{{ form.permissions.length }} selecionadas</p>
          </div>

          <div v-if="editUserInfo && canEditPermissions" class="min-w-0">
            <label class="relative block">
              <span class="sr-only">Pesquisar permissões</span>
              <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" />
              <BaseInput v-model="permissionQuery" type="search" class="ds-field pl-9" placeholder="Pesquisar permissões" />
            </label>

            <div v-if="filteredPermissions.length" class="mt-3 grid max-h-80 overflow-y-auto rounded-lg border border-[var(--ds-border)] sm:grid-cols-2">
              <label
                v-for="permission in filteredPermissions"
                :key="permission.value"
                class="flex cursor-pointer items-start gap-3 border-b border-[var(--ds-border)] px-3 py-3 transition-colors hover:bg-[var(--ds-panel-subtle)] sm:[&:nth-child(odd)]:border-r"
              >
                <CheckboxInput type="checkbox" class="ds-checkbox mt-0.5" :checked="isPermissionSelected(permission)" @change="togglePermission(permission)" />
                <span class="min-w-0 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">{{ permission.label }}</span>
              </label>
            </div>
            <div v-else class="ds-empty-state mt-3 px-5 py-8 text-center">
              <MagnifyingGlassIcon class="mx-auto h-7 w-7 text-[var(--ds-text-soft)]" />
              <p class="ds-heading mt-2 text-sm">Nenhuma permissão encontrada</p>
            </div>
          </div>

          <div v-else class="flex min-h-10 flex-wrap items-center gap-2">
            <span v-for="permission in form.permissions" :key="permission.value" class="ds-badge ds-badge-neutral">{{ permission.label }}</span>
            <span v-if="form.permissions.length === 0" class="text-sm font-semibold text-[var(--ds-text-soft)]">Sem permissões diretas</span>
          </div>
        </div>
      </div>
    </section>

    <section class="ds-command-surface overflow-hidden">
      <header class="flex flex-col gap-4 border-b border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:items-start sm:justify-between sm:px-6">
        <div class="flex items-start gap-3">
          <AcademicCapIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))]" />
          <div>
            <h2 class="ds-heading text-base">Competência técnica e certificação</h2>
            <p class="ds-copy mt-1 max-w-3xl text-sm">Autorizações, evidência de formação, validade e acompanhamento alinhados com a ISO/IEC 17025.</p>
          </div>
        </div>
        <button v-if="editUserInfo" type="button" class="ds-button ds-button-secondary shrink-0" @click="addQualification">
          <PlusIcon class="h-4 w-4" />
          Adicionar qualificação
        </button>
      </header>

      <div v-if="sortedQualifications.length" class="divide-y divide-[var(--ds-border)]">
        <article v-for="(qualification, index) in sortedQualifications" :key="qualification.id ?? `qualification-${index}`" class="px-5 py-5 sm:px-6">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <h3 class="ds-heading text-sm">{{ qualification.capability || 'Nova qualificação' }}</h3>
                <span class="ds-badge" :class="statusBadge(qualification.monitoring_status).className">{{ statusBadge(qualification.monitoring_status).label }}</span>
                <span class="ds-badge" :class="readinessBadge(qualification.renewal_readiness).className">{{ readinessBadge(qualification.renewal_readiness).label }}</span>
                <span class="ds-badge" :class="followUpBadge(qualification.follow_up_state).className">{{ followUpBadge(qualification.follow_up_state).label }}</span>
              </div>

              <dl v-if="!editUserInfo" class="mt-4 grid overflow-hidden rounded-lg border border-[var(--ds-border)] sm:grid-cols-2 xl:grid-cols-4">
                <div class="border-b border-[var(--ds-border)] px-3 py-3 sm:[&:nth-child(odd)]:border-r xl:border-b-0 xl:border-r xl:last:border-r-0">
                  <dt class="text-[0.68rem] font-bold uppercase text-[var(--ds-text-soft)]">Departamento</dt>
                  <dd class="mt-1 text-xs font-semibold text-[var(--ds-text)]">{{ qualification.department_id?.label || 'Transversal' }}</dd>
                </div>
                <div class="border-b border-[var(--ds-border)] px-3 py-3 xl:border-b-0 xl:border-r">
                  <dt class="text-[0.68rem] font-bold uppercase text-[var(--ds-text-soft)]">Validade</dt>
                  <dd class="mt-1 text-xs font-semibold text-[var(--ds-text)]">{{ qualification.authorized_until ? formatDate(qualification.authorized_until) : 'Sem limite' }}</dd>
                  <p class="mt-1 text-[0.68rem] font-medium text-[var(--ds-text-soft)]">{{ expiryHint(qualification) }}</p>
                </div>
                <div class="border-b border-[var(--ds-border)] px-3 py-3 sm:[&:nth-child(odd)]:border-r xl:border-b-0 xl:border-r">
                  <dt class="text-[0.68rem] font-bold uppercase text-[var(--ds-text-soft)]">Evidência</dt>
                  <dd class="mt-1 text-xs font-semibold text-[var(--ds-text)]">{{ qualification.training_reference || 'Não registada' }}</dd>
                </div>
                <div class="px-3 py-3">
                  <dt class="text-[0.68rem] font-bold uppercase text-[var(--ds-text-soft)]">Próximo acompanhamento</dt>
                  <dd class="mt-1 text-xs font-semibold text-[var(--ds-text)]">{{ qualification.follow_up_due_at ? formatDate(qualification.follow_up_due_at) : 'Por definir' }}</dd>
                  <p class="mt-1 text-[0.68rem] font-medium text-[var(--ds-text-soft)]">Qualificado por {{ qualification.qualified_by || '—' }}</p>
                </div>
              </dl>
            </div>

            <button v-if="editUserInfo" type="button" class="ds-button border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300" @click="removeQualification(qualification)">
              <MinusCircleIcon class="h-4 w-4" />
              Remover
            </button>
          </div>

          <div v-if="editUserInfo" class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="ds-field-group">
              <label :for="`qualification-capability-${index}`" class="ds-field-label">Capacidade ou ensaio <span class="ds-field-required">*</span></label>
              <BaseInput :id="`qualification-capability-${index}`" v-model="qualification.capability" type="text" class="ds-field" placeholder="Ex.: Verificação de resultados" />
              <p v-if="form.errors[`personnel_qualifications.${qualificationIndex(qualification)}.capability`]" class="ds-field-error">{{ form.errors[`personnel_qualifications.${qualificationIndex(qualification)}.capability`] }}</p>
            </div>
            <div class="ds-field-group">
              <label class="ds-field-label">Departamento</label>
              <ComboboxMultipleEnhanced v-model="qualification.department_id" :load-options="loadDepartments" :multiple="false" placeholder="Selecionar departamento" />
            </div>
            <div class="ds-field-group">
              <label :for="`qualification-reference-${index}`" class="ds-field-label">Referência da formação ou certificado</label>
              <BaseInput :id="`qualification-reference-${index}`" v-model="qualification.training_reference" type="text" class="ds-field" placeholder="Ex.: CERT-2026-014" />
            </div>
            <div class="ds-field-group">
              <label :for="`qualification-from-${index}`" class="ds-field-label">Autorizada desde</label>
              <DateTimePicker :id="`qualification-from-${index}`" v-model="qualification.authorized_from" type="date" class="ds-field" />
            </div>
            <div class="ds-field-group">
              <label :for="`qualification-until-${index}`" class="ds-field-label">Autorizada até</label>
              <DateTimePicker :id="`qualification-until-${index}`" v-model="qualification.authorized_until" type="date" class="ds-field" />
              <p v-if="form.errors[`personnel_qualifications.${qualificationIndex(qualification)}.authorized_until`]" class="ds-field-error">{{ form.errors[`personnel_qualifications.${qualificationIndex(qualification)}.authorized_until`] }}</p>
            </div>
            <div class="ds-field-group">
              <label :for="`qualification-training-${index}`" class="ds-field-label">Formação concluída em</label>
              <DateTimePicker :id="`qualification-training-${index}`" v-model="qualification.training_completed_at" type="date" class="ds-field" />
            </div>
            <ToggleField
              :id="`qualification-active-${index}`"
              v-model="qualification.is_active"
              label="Autorização ativa"
              description="Disponibiliza esta competência nos fluxos técnicos aplicáveis."
              class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:col-span-2 xl:col-span-3"
            />
            <div class="ds-field-group sm:col-span-2 xl:col-span-3">
              <label :for="`qualification-notes-${index}`" class="ds-field-label">Plano de acompanhamento e notas</label>
              <textarea :id="`qualification-notes-${index}`" v-model="qualification.notes" rows="4" class="ds-field resize-y" placeholder="Ações de acompanhamento, reciclagem, evidência de auditoria ou dependências para renovação." />
            </div>
          </div>

          <p v-else-if="qualification.notes" class="mt-4 border-l-2 border-[rgb(var(--primary-300-rgb))] pl-3 text-sm leading-6 text-[var(--ds-text-muted)]">{{ qualification.notes }}</p>
        </article>
      </div>

      <div v-else class="p-5 sm:p-6">
        <div class="ds-empty-state px-6 py-10 text-center">
          <AcademicCapIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
          <h3 class="ds-heading mt-3 text-sm">Sem qualificações registadas</h3>
          <p class="ds-copy mx-auto mt-1 max-w-xl text-sm">Registe competências, evidência e janelas de renovação para controlar a autorização técnica.</p>
        </div>
      </div>
    </section>

    <section v-if="editUserInfo && canEditPassword" class="ds-command-surface overflow-hidden">
      <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
        <div class="flex items-start gap-3">
          <KeyIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))]" />
          <div>
            <h2 class="ds-heading text-base">Repor palavra-passe</h2>
            <p class="ds-copy mt-1 text-sm">Defina uma credencial temporária apenas quando a reposição administrativa for necessária.</p>
          </div>
        </div>
      </header>
      <div class="grid gap-5 px-5 py-5 sm:grid-cols-2 sm:px-6">
        <div class="ds-field-group">
          <label for="user-password" class="ds-field-label">Nova palavra-passe</label>
          <BaseInput id="user-password" v-model="passwordForm.password" type="password" class="ds-field" autocomplete="new-password" :aria-invalid="Boolean(passwordForm.errors.password)" />
          <p v-if="passwordForm.errors.password" class="ds-field-error">{{ passwordForm.errors.password }}</p>
        </div>
        <div class="ds-field-group">
          <label for="user-password-confirmation" class="ds-field-label">Confirmar palavra-passe</label>
          <BaseInput id="user-password-confirmation" v-model="passwordForm.password_confirmation" type="password" class="ds-field" autocomplete="new-password" :aria-invalid="Boolean(passwordForm.errors.password_confirmation)" />
          <p v-if="passwordForm.errors.password_confirmation" class="ds-field-error">{{ passwordForm.errors.password_confirmation }}</p>
        </div>
      </div>
    </section>

    <section v-if="isCurrentUser" class="ds-command-surface overflow-hidden">
      <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
        <div class="flex items-start gap-3">
          <DocumentTextIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))]" />
          <div>
            <h2 class="ds-heading text-base">Assinatura documental</h2>
            <p class="ds-copy mt-1 text-sm">Assinatura pessoal usada nos documentos emitidos pela aplicação.</p>
          </div>
        </div>
      </header>
      <div class="p-5 sm:p-6">
        <SignaturePad :current-signature="record.signature_url" @save="saveSignature" @delete="deleteSignature" />
      </div>
    </section>

    <div v-if="editUserInfo" class="ds-command-surface sticky bottom-4 z-20 flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
      <p class="text-xs font-semibold text-[var(--ds-text-muted)]">
        {{ hasUnsavedChanges ? 'Existem alterações por guardar neste dossier.' : 'Nenhuma alteração pendente.' }}
      </p>
      <div class="flex flex-col-reverse gap-2 sm:flex-row">
        <button type="button" class="ds-button ds-button-secondary" @click="toggleEditMode">Cancelar</button>
        <button type="button" class="ds-button ds-button-primary" :disabled="!hasUnsavedChanges || isSaving" @click="requestSave">
          <ArrowPathIcon v-if="isSaving" class="h-4 w-4 animate-spin" />
          <CheckIcon v-else class="h-4 w-4" />
          Guardar alterações
        </button>
      </div>
    </div>

    <ConfirmDialog
      v-if="confirmationAction"
      :title="confirmationAction === 'save' ? 'Guardar alterações do dossier' : 'Descartar alterações'"
      :description="confirmationAction === 'save'
        ? 'Os dados pessoais, acessos e qualificações alterados serão atualizados.'
        : 'As alterações ainda não guardadas serão perdidas.'"
      :confirm="confirmationAction === 'save' ? 'Guardar' : 'Descartar'"
      :variant="confirmationAction === 'save' ? 'question' : 'danger'"
      @confirmed="performConfirmedAction"
      @canceled="confirmationAction = null"
    >
      <dl v-if="confirmationAction === 'save'" class="mt-4 divide-y divide-[var(--ds-border)] border-y border-[var(--ds-border)] text-sm">
        <div class="flex items-center justify-between gap-4 py-3">
          <dt class="font-semibold text-[var(--ds-text-muted)]">Colaborador</dt>
          <dd class="truncate font-bold text-[var(--ds-text)]">{{ form.name }}</dd>
        </div>
        <div class="flex items-center justify-between gap-4 py-3">
          <dt class="font-semibold text-[var(--ds-text-muted)]">Dados do dossier</dt>
          <dd class="font-bold text-[var(--ds-text)]">{{ form.isDirty ? 'Alterados' : 'Sem alterações' }}</dd>
        </div>
        <div class="flex items-center justify-between gap-4 py-3">
          <dt class="font-semibold text-[var(--ds-text-muted)]">Palavra-passe</dt>
          <dd class="font-bold text-[var(--ds-text)]">{{ passwordForm.isDirty ? 'Será reposta' : 'Sem alterações' }}</dd>
        </div>
      </dl>
    </ConfirmDialog>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import {
  AcademicCapIcon,
  ArrowPathIcon,
  CheckCircleIcon,
  CheckIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  IdentificationIcon,
  KeyIcon,
  MagnifyingGlassIcon,
  MinusCircleIcon,
  PencilSquareIcon,
  PlusIcon,
  ShieldCheckIcon,
  UserIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import ComboboxMultipleEnhanced from '@/Components/combobox-multiple-enhanced.vue'
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import DatePickerEnhanced from '@/Components/date-picker-enhanced.vue'
import SignaturePad from '@/Components/signature-pad.vue'
import ToggleField from '@/Components/base/ToggleField.vue'
import { usePermission } from '@/Composables/usePermissions'
import Layout from '@/Shared/Layouts/Layout.vue'

defineOptions({ layout: Layout })

const props = defineProps({
  record: { type: Object, required: true },
  permissions: { type: Array, default: () => [] },
  roles: { type: Array, default: () => [] },
  auth: { type: Object, default: () => ({}) },
  competenceSummary: { type: Object, default: () => ({}) },
})

const { hasPermission } = usePermission()
const editUserInfo = ref(false)
const permissionQuery = ref('')
const confirmationAction = ref(null)
const isSaving = ref(false)

const form = useForm({
  ...props.record,
  gender: props.record.gender || 'O',
  departments: [...(props.record.departments || [])],
  roles: [...(props.record.roles || [])],
  permissions: [...(props.record.permissions || [])],
  personnel_qualifications: (props.record.personnel_qualifications || []).map((qualification) => ({ ...qualification })),
})

const passwordForm = useForm({
  password: '',
  password_confirmation: '',
})

const genderOptions = [
  { value: 'F', label: 'Feminino' },
  { value: 'M', label: 'Masculino' },
  { value: 'O', label: 'Outro' },
]

const dateMasks = {
  modelValue: 'YYYY-MM-DD',
  data: 'YYYY-MM-DD',
  input: 'YYYY-MM-DD',
  model: 'YYYY-MM-DD',
}

const qualificationStatus = {
  active: { label: 'Ativa', className: 'ds-badge-success' },
  expiring_soon: { label: 'Renovação próxima', className: 'ds-badge-warning' },
  expiring_critical: { label: 'Renovação urgente', className: 'ds-badge-danger' },
  expired: { label: 'Expirada', className: 'ds-badge-danger' },
  inactive: { label: 'Inativa', className: 'ds-badge-neutral' },
  scheduled: { label: 'Programada', className: 'ds-badge-info' },
}

const readinessStatus = {
  on_track: { label: 'Em conformidade', className: 'ds-badge-success' },
  ready_for_review: { label: 'Pronta para renovação', className: 'ds-badge-info' },
  training_pending: { label: 'Formação pendente', className: 'ds-badge-warning' },
  missing_evidence: { label: 'Falta evidência', className: 'ds-badge-danger' },
}

const followUpStatus = {
  scheduled: { label: 'Acompanhamento planeado', className: 'ds-badge-info' },
  due_soon: { label: 'Acompanhamento próximo', className: 'ds-badge-warning' },
  overdue: { label: 'Acompanhamento em atraso', className: 'ds-badge-danger' },
  unscheduled: { label: 'Sem plano definido', className: 'ds-badge-neutral' },
}

const initials = computed(() => props.record.name
  .split(/\s+/)
  .filter(Boolean)
  .slice(0, 2)
  .map((part) => part[0])
  .join('')
  .toUpperCase())

const genderLabel = computed(() => genderOptions.find((option) => option.value === form.gender)?.label || 'Não definido')
const canEditPassword = computed(() => hasPermission('reset-password_users') || form.id === props.auth?.user?.id)
const canEditPermissions = computed(() => hasPermission('edit_permissions'))
const isCurrentUser = computed(() => form.id === props.auth?.user?.id)
const hasUnsavedChanges = computed(() => form.isDirty || passwordForm.isDirty)

const formStatus = computed(() => {
  if (isSaving.value) return { label: 'A guardar', className: 'ds-badge-info', icon: ArrowPathIcon }
  if (hasUnsavedChanges.value) return { label: 'Alterações pendentes', className: 'ds-badge-warning', icon: ExclamationTriangleIcon }
  if (editUserInfo.value) return { label: 'Em edição', className: 'ds-badge-info', icon: PencilSquareIcon }

  return { label: 'Dossier atualizado', className: 'ds-badge-success', icon: CheckCircleIcon }
})

const competenceMetrics = computed(() => [
  { label: 'Qualificações ativas', value: props.competenceSummary.active ?? 0, detail: 'autorizadas', tone: 'text-emerald-700 dark:text-emerald-300' },
  { label: 'Renovações próximas', value: props.competenceSummary.expiring_soon ?? 0, detail: 'a acompanhar', tone: 'text-amber-700 dark:text-amber-300' },
  { label: 'Prontas para revisão', value: props.competenceSummary.ready_for_renewal ?? 0, detail: 'com evidência', tone: 'text-sky-700 dark:text-sky-300' },
  { label: 'Evidência em falta', value: props.competenceSummary.missing_evidence ?? 0, detail: 'requer ação', tone: 'text-red-700 dark:text-red-300' },
])

const filteredPermissions = computed(() => {
  const query = permissionQuery.value.trim().toLocaleLowerCase('pt-PT')

  if (!query) return props.permissions

  return props.permissions.filter((permission) => permission.label.toLocaleLowerCase('pt-PT').includes(query))
})

const sortedQualifications = computed(() => {
  const priority = {
    expired: 0,
    expiring_critical: 1,
    expiring_soon: 2,
    scheduled: 3,
    active: 4,
    inactive: 5,
  }

  return [...form.personnel_qualifications].sort((left, right) => {
    const rankDifference = (priority[left.monitoring_status] ?? 99) - (priority[right.monitoring_status] ?? 99)

    return rankDifference || (left.days_until_expiry ?? 99999) - (right.days_until_expiry ?? 99999)
  })
})

function displayValue(value) {
  return value || 'Não definido'
}

function formatDate(value) {
  if (!value) return 'Não definida'

  return new Intl.DateTimeFormat('pt-PT').format(new Date(`${value}T00:00:00`))
}

function expiryHint(qualification) {
  if (qualification.days_until_expiry === null || qualification.days_until_expiry === undefined) return 'Sem data final definida.'
  if (qualification.days_until_expiry < 0) return `Expirada há ${Math.abs(qualification.days_until_expiry)} dias.`
  if (qualification.days_until_expiry === 0) return 'Expira hoje.'

  return `Expira em ${qualification.days_until_expiry} dias.`
}

function statusBadge(status) {
  return qualificationStatus[status] ?? qualificationStatus.inactive
}

function readinessBadge(status) {
  return readinessStatus[status] ?? readinessStatus.training_pending
}

function followUpBadge(status) {
  return followUpStatus[status] ?? followUpStatus.unscheduled
}

function loadDepartments(query, setOptions) {
  if (!query) return

  fetch(`/departments/getDepartment?q=${encodeURIComponent(query)}`)
    .then((response) => response.json())
    .then((results) => setOptions(results.map((result) => ({ value: result.id, label: result.name }))))
}

function loadRoles(query, setOptions) {
  if (!query) return

  fetch(`/roles/getRole?q=${encodeURIComponent(query)}`)
    .then((response) => response.json())
    .then((results) => setOptions(results.map((result) => ({ value: result.id, label: result.label }))))
}

function togglePermission(permission) {
  const index = form.permissions.findIndex((selected) => selected.value === permission.value)

  if (index >= 0) {
    form.permissions.splice(index, 1)
  } else {
    form.permissions.push(permission)
  }
}

function isPermissionSelected(permission) {
  return form.permissions.some((selected) => selected.value === permission.value)
}

function addQualification() {
  form.personnel_qualifications.push({
    id: null,
    capability: '',
    department_id: null,
    authorized_from: null,
    authorized_until: null,
    training_completed_at: null,
    training_reference: '',
    notes: '',
    is_active: true,
    monitoring_status: 'scheduled',
    renewal_readiness: 'training_pending',
    follow_up_due_at: null,
    follow_up_state: 'unscheduled',
    days_until_expiry: null,
    qualified_by: null,
  })
}

function qualificationIndex(qualification) {
  return form.personnel_qualifications.findIndex((item) => item === qualification)
}

function removeQualification(qualification) {
  const index = qualificationIndex(qualification)

  if (index >= 0) form.personnel_qualifications.splice(index, 1)
}

function toggleEditMode() {
  if (!editUserInfo.value) {
    editUserInfo.value = true
    return
  }

  if (hasUnsavedChanges.value) {
    confirmationAction.value = 'discard'
    return
  }

  editUserInfo.value = false
}

function requestSave() {
  if (hasUnsavedChanges.value) confirmationAction.value = 'save'
}

function performConfirmedAction() {
  const action = confirmationAction.value
  confirmationAction.value = null

  if (action === 'discard') {
    form.reset()
    passwordForm.reset()
    form.clearErrors()
    passwordForm.clearErrors()
    editUserInfo.value = false
    return
  }

  saveDossier()
}

function saveDossier() {
  isSaving.value = true

  if (form.isDirty) {
    form.put(route('users.update', { user: form.id }), {
      preserveScroll: true,
      onSuccess: () => {
        if (passwordForm.isDirty) {
          savePassword()
          return
        }

        finishSaving()
      },
      onError: () => {
        isSaving.value = false
      },
    })
    return
  }

  savePassword()
}

function savePassword() {
  passwordForm.put(route('users.setpass', { user: form.id }), {
    preserveScroll: true,
    onSuccess: finishSaving,
    onError: () => {
      isSaving.value = false
    },
  })
}

function finishSaving() {
  form.defaults({ ...form.data() })
  form.reset()
  passwordForm.reset()
  editUserInfo.value = false
  isSaving.value = false
}

function saveSignature(signature) {
  useForm({ signature }).post(route('users.setsignature'), { preserveScroll: true })
}

function deleteSignature() {
  useForm({}).get(route('users.unsetsignature'), { preserveScroll: true })
}
</script>
