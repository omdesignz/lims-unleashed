<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-6 py-7 sm:px-8">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
          <div class="max-w-4xl">
            <div class="flex flex-wrap items-center gap-3">
              <span class="inline-flex items-center gap-2 rounded-full border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] px-3 py-1 text-xs font-black uppercase tracking-[0.24em] text-[var(--ds-text)] shadow-sm">
                <DocumentTextIcon class="h-4 w-4 text-[var(--ds-text-soft)]" />
                {{ $t('gestlab.general.labels.vap_proposals.surface.commercial_revision') }}
              </span>
              <span :class="[
                'inline-flex items-center rounded-full px-3 py-1 text-xs font-black uppercase tracking-[0.18em]',
                proposal.status_badge.class
              ]">
                {{ proposal.status_badge.text }}
              </span>
            </div>
            <h1 class="mt-5 text-3xl font-black tracking-[-0.04em] text-[var(--ds-text)] sm:text-5xl">
              {{ proposal.proposal_number }}
            </h1>
            <p class="ds-copy mt-4 max-w-3xl text-base leading-7">
              {{ $t('gestlab.general.labels.vap_proposals.edit.editing_proposal') }}:
              <span class="font-black text-[var(--ds-text)]">{{ proposal.customer?.name || proposal.proposal_number }}</span>
            </p>
          </div>

          <div class="grid gap-3 sm:grid-cols-3 xl:min-w-[35rem]">
            <div class="ds-card bg-[var(--ds-panel-raised)] p-4">
              <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--ds-text-muted)]">{{ $t('gestlab.general.labels.vap_proposals.surface.items') }}</p>
              <p class="mt-2 text-lg font-black text-[var(--ds-text)]">{{ form.items.length }}</p>
            </div>
            <div class="ds-card bg-[var(--ds-panel-raised)] p-4">
              <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--ds-text-muted)]">{{ $t('gestlab.general.labels.vap_proposals.surface.expiry') }}</p>
              <p class="mt-2 text-lg font-black text-[var(--ds-text)]">{{ calculateExpiryDate }}</p>
            </div>
            <div class="ds-card bg-[var(--ds-panel-raised)] p-4">
              <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--ds-text-muted)]">{{ $t('gestlab.general.labels.vap_proposals.surface.total') }}</p>
              <p class="mt-2 text-lg font-black text-[var(--ds-text)]">AOA {{ formatNumber(proposalTotal) }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="flex flex-col gap-3 px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-8">
        <div v-if="!proposal.is_original" class="inline-flex items-start gap-3 rounded-[22px] border border-amber-300/60 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900 dark:border-amber-300/20 dark:bg-amber-400/10 dark:text-amber-100">
          <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 shrink-0 text-amber-700 dark:text-amber-200" />
          <span>
            {{ $t('gestlab.general.labels.vap_proposals.edit.revision_warning') }}
            {{ $t('gestlab.general.labels.vap_proposals.edit.original_proposal') }}: {{ proposal.proposal_number }}
          </span>
        </div>
        <div class="flex flex-col gap-3 sm:ml-auto sm:flex-row">
          <Link
            :href="route('vap-proposals.show', proposal.id)"
            class="ds-button ds-button-secondary"
          >
            <ArrowLeftIcon class="h-5 w-5" />
            {{ $t('gestlab.general.buttons.back') }}
          </Link>
          <Link
            :href="route('vap-proposals.index')"
            class="ds-button ds-button-secondary"
          >
            <ListBulletIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_proposals.title') }}
          </Link>
        </div>
      </div>
    </section>

    <div
      v-if="formErrorMessage"
      class="rounded-[22px] border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-800 shadow-[0_18px_40px_-30px_rgba(185,28,28,0.45)] dark:border-red-300/20 dark:bg-red-400/10 dark:text-red-200"
    >
      {{ formErrorMessage }}
    </div>

    <!-- MAIN CONTENT SECTION -->
    <div class="grid grid-cols-1 gap-8 xl:grid-cols-[minmax(0,1fr)_25rem]">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="space-y-6">
        <!-- BASIC INFORMATION CARD -->
        <div class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-6 py-5">
            <p class="ds-kicker">{{ $t('gestlab.general.labels.vap_proposals.surface.commercial_base') }}</p>
            <h2 class="ds-heading mt-2 flex items-center gap-2 text-2xl">
              <InformationCircleIcon class="h-6 w-6 text-[var(--ds-text-soft)]" />
              {{ $t('gestlab.general.labels.vap_proposals.edit.basic_info') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- CLIENT SELECTION -->
              <div class="space-y-2">
                <label class="ds-field-label flex items-center gap-1">
                  <UserIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.edit.customer') }}
                  <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-2 rounded-[var(--ds-radius-control)] border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-2.5">
                  <UserIcon class="h-4 w-4 text-[var(--ds-text-soft)]" />
                  <span class="text-sm font-black text-[var(--ds-text)]">
                    {{ proposal.customer?.name || $t('gestlab.general.labels.vap_proposals.edit.not_available') }}
                  </span>
                </div>
                <p class="ds-field-hint">
                  {{ $t('gestlab.general.labels.vap_proposals.edit.customer_locked') }}
                </p>
              </div>

              <!-- WAREHOUSE SELECTION -->
              <div class="space-y-2">
                <label class="ds-field-label flex items-center gap-1">
                  <BuildingStorefrontIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.edit.warehouse') }}
                  <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-2 rounded-[var(--ds-radius-control)] border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-2.5">
                  <BuildingStorefrontIcon class="h-4 w-4 text-[var(--ds-text-soft)]" />
                  <span class="text-sm font-black text-[var(--ds-text)]">
                    {{ proposal.warehouse?.address || $t('gestlab.general.labels.vap_proposals.edit.not_available') }}
                  </span>
                </div>
              </div>

              <!-- DEPARTMENT SELECTION -->
              <div class="space-y-2">
                <label class="ds-field-label flex items-center gap-1">
                  <BuildingOfficeIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.edit.department') }}
                  <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-2 rounded-[var(--ds-radius-control)] border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-2.5">
                  <BuildingOfficeIcon class="h-4 w-4 text-[var(--ds-text-soft)]" />
                  <span class="text-sm font-black text-[var(--ds-text)]">
                    {{ proposal.department?.name || $t('gestlab.general.labels.vap_proposals.edit.not_available') }}
                  </span>
                </div>
              </div>

              <!-- TEMPLATE SELECTION -->
              <div class="space-y-2">
                <label class="ds-field-label flex items-center gap-1">
                  <DocumentTextIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.edit.template') }}
                  <span class="text-red-500">*</span>
                </label>
                <comboboxEnhanced 
                    :hasError="form.errors.template_id"
                    v-model="templateSelected"
                    :options="templates.map(template => ({
                    value: template.id,
                    label: template.name,
                    name: template.name,
                    user: template.user,
                    content: template.content,
                    }))"
                    :placeholder="$t('gestlab.general.labels.vap_proposals.edit.select_template')"
                />
                <p v-if="form.errors.template_id" class="ds-field-error">
                  {{ form.errors.template_id }}
                </p>
              </div>

              <!-- LAB CODE SELECTION -->
              <div class="space-y-2">
                <label class="ds-field-label flex items-center gap-1">
                  <DocumentTextIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.edit.labcode') }}
                </label>
                <comboboxEnhanced 
                    v-model="labcode_id"
                    :load-options="loadLabCodes"
                    :placeholder="$t('gestlab.general.labels.vap_proposals.edit.select_labcode')"
                />
                <div v-if="loadingParameters" class="text-xs font-semibold text-[var(--ds-text)]">
                  {{ $t('gestlab.general.labels.vap_proposals.edit.loading_parameters') }}
                </div>
                <div v-if="loadErrorMessage" class="text-xs font-semibold text-red-600 dark:text-red-300">
                  {{ loadErrorMessage }}
                </div>
              </div>

              <!-- SERVICE LOCATION -->
              <div class="space-y-2">
                <label class="ds-field-label flex items-center gap-1">
                  <MapPinIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.edit.service_location') }}
                  <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.service_location"
                  type="text"
                  class="ds-field"
                  :class="{ 'border-red-500': form.errors.service_location }"
                  :placeholder="$t('gestlab.general.labels.vap_proposals.edit.service_location')"
                />
                <p v-if="form.errors.service_location" class="ds-field-error">
                  {{ form.errors.service_location }}
                </p>
              </div>

              <!-- TOLERANCE DAYS -->
              <div class="space-y-2">
                <label class="ds-field-label flex items-center gap-1">
                  <ClockIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.edit.tolerance_days') }}
                  <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.tolerance_days"
                  type="number"
                  min="1"
                  max="365"
                  class="ds-field"
                  :class="{ 'border-red-500': form.errors.tolerance_days }"
                  :placeholder="$t('gestlab.general.labels.vap_proposals.edit.tolerance_days_placeholder')"
                />
                <p v-if="form.errors.tolerance_days" class="ds-field-error">
                  {{ form.errors.tolerance_days }}
                </p>
              </div>

              <!-- USE MATRIX PRICE TOGGLE -->
              <div class="col-span-1 flex items-center gap-2 md:col-span-2 lg:col-span-1">
                <input
                  v-model="form.use_matrix_price"
                  type="checkbox"
                  id="use_matrix_price"
                  class="ds-checkbox"
                />
                <label for="use_matrix_price" class="text-sm font-semibold text-[var(--ds-text-muted)]">
                  {{ $t('gestlab.general.labels.vap_proposals.edit.use_matrix_price') }}
                </label>
              </div>

              <!-- WITHHOLD TAX -->
              <div class="flex items-center gap-2">
                <input
                  v-model="form.withhold_tax"
                  type="checkbox"
                  id="withhold_tax"
                  class="ds-checkbox"
                />
                <label for="withhold_tax" class="text-sm font-semibold text-[var(--ds-text-muted)]">
                  {{ $t('gestlab.general.labels.vap_proposals.edit.withhold_tax') }}
                </label>
              </div>
            </div>

            <!-- REVISION REASON -->
            <div class="mt-6">
              <label class="ds-field-label mb-2">
                {{ $t('gestlab.general.labels.vap_proposals.edit.revision_reason') }}
                <span class="text-red-500">*</span>
              </label>
              <textarea
                v-model="form.revision_reason"
                rows="3"
                class="ds-field min-h-28"
                :class="{ 'border-red-500': form.errors.revision_reason }"
                :placeholder="$t('gestlab.general.labels.vap_proposals.edit.revision_reason_placeholder')"
              ></textarea>
              <p v-if="form.errors.revision_reason" class="ds-field-error">
                {{ form.errors.revision_reason }}
              </p>
            </div>

            <!-- OBSERVATIONS -->
            <div class="mt-6">
              <label class="ds-field-label mb-2">
                {{ $t('gestlab.general.labels.vap_proposals.edit.observations') }}
              </label>
              <textarea
                v-model="form.obs"
                rows="3"
                class="ds-field min-h-28"
                :placeholder="$t('gestlab.general.labels.vap_proposals.edit.observations_placeholder')"
              ></textarea>
            </div>
          </div>
        </div>

        <!-- SERVICE ITEMS CARD -->
        <div class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-6 py-5">
            <div class="flex items-center justify-between">
              <h2 class="ds-heading flex items-center gap-2 text-2xl">
                <ListBulletIcon class="h-6 w-6 text-[var(--ds-text-soft)]" />
                {{ $t('gestlab.general.labels.vap_proposals.edit.items.title') }}
                <span class="ml-2 rounded-full border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] px-3 py-1 text-sm font-black text-[var(--ds-text)]">
                  ({{ form.items.length }} {{ $t('gestlab.general.buttons.items') }})
                </span>
              </h2>
              <div class="flex items-center gap-2">
                <button 
                  @click="loadParametersBasedOnLabCode(labcode_id?.value)"
                  v-if="labcode_id && !form.items.length"
                  :disabled="!labcode_id || loadingParameters"
                  class="ds-button bg-emerald-700 text-white hover:bg-emerald-800"
                >
                  <DocumentTextIcon class="h-5 w-5" />
                  {{ $t('gestlab.general.labels.vap_proposals.assign_lab_code') }}
                </button>
                <button 
                  @click="addItem"
                  type="button"
                  class="ds-button ds-button-primary"
                >
                  <PlusCircleIcon class="h-5 w-5" />
                  {{ $t('gestlab.general.labels.vap_proposals.edit.items.add_item') }}
                </button>
              </div>
            </div>
            <p class="ds-copy mt-1 text-sm">
              {{ $t('gestlab.general.labels.vap_proposals.edit.items.description') }}
            </p>
          </div>

          <!-- EMPTY STATE -->
          <div v-if="form.items.length === 0" class="p-12 text-center">
            <DocumentTextIcon class="mx-auto h-12 w-12 text-[var(--ds-text-soft)]" />
            <h3 class="mt-4 text-sm font-black text-[var(--ds-text)]">
              {{ $t('gestlab.general.labels.vap_proposals.edit.items.title') }}
            </h3>
            <p class="ds-copy mt-2 text-sm">
              {{ $t('gestlab.general.labels.vap_proposals.edit.items.description') }}
            </p>
            <div class="mt-6 flex justify-center gap-3">
              <button 
                v-if="labcode_id"
                @click="loadParametersBasedOnLabCode(labcode_id?.value)"
                :disabled="!labcode_id || loadingParameters"
                class="ds-button bg-emerald-700 text-white hover:bg-emerald-800"
              >
                <DocumentTextIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.vap_proposals.assign_lab_code') }}
              </button>
              <button 
                @click="addItem"
                type="button"
                class="ds-button ds-button-primary"
              >
                <PlusCircleIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.vap_proposals.edit.items.add_item') }}
              </button>
            </div>
          </div>

          <!-- ITEMS GRID -->
          <div v-else class="space-y-6 p-6">
            <!-- ITEM CARD TEMPLATE -->
            <div 
              v-for="(item, index) in form.items"
              :key="index"
              class="group relative overflow-hidden rounded-[1.5rem] border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] shadow-[var(--ds-shadow-control)] transition duration-200 hover:-translate-y-0.5 hover:border-[rgb(var(--primary-300-rgb)/0.72)]"
            >
              <!-- ITEM HEADER -->
              <div class="border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-4 py-3">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-2xl border border-[var(--ds-border)] bg-[rgb(var(--primary-50-rgb))] text-sm font-black text-[rgb(var(--primary-800-rgb))] dark:bg-[rgb(var(--primary-400-rgb)/0.12)] dark:text-[rgb(var(--accent-100-rgb))]">
                      {{ index + 1 }}
                    </div>
                    <div>
                      <h3 class="text-sm font-black text-[var(--ds-text)]">
                        {{ item.item_description || $t('gestlab.general.labels.vap_proposals.edit.items.item_description') }}
                      </h3>
                      <p class="text-xs font-semibold text-[var(--ds-text-muted)]">
                        {{ $t('gestlab.general.buttons.item') }} #{{ index + 1 }}
                        <span v-if="item.item_id" class="ml-2 text-[var(--ds-text-soft)]">
                          ID: {{ item.item_id }}
                        </span>
                      </p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <button 
                      @click="duplicateItem(index)"
                      type="button"
                      class="ds-table-action"
                      :title="$t('gestlab.general.labels.vap_proposals.edit.items.duplicate_item')"
                    >
                      <DocumentDuplicateIcon class="h-5 w-5" />
                    </button>
                    <button 
                      @click="removeItem(index)"
                      type="button"
                      class="ds-table-action ds-table-action-danger"
                      :title="$t('gestlab.general.labels.vap_proposals.edit.items.remove_item')"
                    >
                      <TrashIcon class="h-5 w-5" />
                    </button>
                  </div>
                </div>
              </div>
              
              <!-- ITEM CONTENT -->
              <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                  <!-- ITEM SELECTION (Matrix or Parameter) -->
                  <div class="space-y-2">
                    <label class="ds-field-label text-xs">
                      {{ $t('gestlab.general.labels.vap_proposals.edit.items.item') }}
                      <span class="text-red-500">*</span>
                    </label>
                    <comboboxEnhanced 
                      v-model="item.item_selection"
                      :load-options="form.use_matrix_price ? loadMatrixes : loadParameters"
                      :placeholder="form.use_matrix_price 
                        ? $t('gestlab.general.labels.vap_proposals.edit.items.select_matrix')
                        : $t('gestlab.general.labels.vap_proposals.edit.items.select_parameter')"
                      @update:model-value="onSelectedItem(item, index)"
                    />
                  </div>

                  <!-- ITEM DESCRIPTION -->
                  <div class="space-y-2">
                    <label class="ds-field-label text-xs">
                      {{ $t('gestlab.general.labels.vap_proposals.edit.items.item_description') }}
                      <span class="text-red-500">*</span>
                    </label>
                    <input
                      v-model="item.item_description"
                      type="text"
                      class="ds-field"
                      :placeholder="$t('gestlab.general.labels.vap_proposals.edit.items.item_description_placeholder')"
                      @input="calculateItemTotal(index)"
                    />
                  </div>

                  <!-- STANDARD SELECTION -->
                  <div class="space-y-2">
                    <label class="ds-field-label text-xs">
                      {{ $t('gestlab.general.labels.vap_proposals.edit.items.standard') }}
                    </label>
                    <comboboxEnhanced 
                      :hasError="item.standard_id"
                      v-model="item.standard_display"
                      :options="standardOptions"
                      :placeholder="$t('gestlab.general.labels.vap_proposals.edit.items.select_standard')"
                      @update:model-value="onStandardChange(item, index)"
                    />
                  </div>

                  <!-- UNIT SELECTION -->
                  <div class="space-y-2">
                    <label class="ds-field-label text-xs">
                      {{ $t('gestlab.general.labels.vap_proposals.edit.items.unit') }}
                      <span class="text-red-500">*</span>
                    </label>
                    <comboboxEnhanced 
                      :hasError="item.unit_id"
                      v-model="item.unit_display"
                      :options="unitOptions"
                      :placeholder="$t('gestlab.general.labels.vap_proposals.edit.items.select_unit')"
                      @update:model-value="onUnitChange(item, index)"
                    />
                  </div>

                  <!-- QUANTITY -->
                  <div class="space-y-2">
                    <label class="ds-field-label text-xs">
                      {{ $t('gestlab.general.labels.vap_proposals.edit.items.quantity') }}
                      <span class="text-red-500">*</span>
                    </label>
                    <input
                      v-model="item.qty"
                      type="number"
                      min="0.01"
                      step="0.01"
                      class="ds-field"
                      @input="calculateItemTotal(index)"
                    />
                  </div>

                  <!-- UNIT PRICE -->
                  <div class="space-y-2">
                    <label class="ds-field-label text-xs">
                      {{ $t('gestlab.general.labels.vap_proposals.edit.items.unit_price') }}
                      <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                      <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-[var(--ds-text-soft)]">AOA</span>
                      <input
                        v-model="item.unit_price"
                        type="number"
                        min="0"
                        step="0.01"
                        class="ds-field pl-12"
                        @input="calculateItemTotal(index)"
                      />
                    </div>
                  </div>

                  <!-- DISCOUNT TYPE -->
                  <div class="space-y-2">
                    <label class="ds-field-label text-xs">
                      {{ $t('gestlab.general.labels.vap_proposals.edit.items.discount') }}
                    </label>
                    <div class="flex gap-2">
                      <select 
                        v-model="item.discount_id"
                        class="ds-field min-w-24"
                        @change="calculateItemTotal(index)"
                      >
                        <option v-for="type in discount_categories" :key="type.value" :value="type.value">
                          {{ type.label }}
                        </option>
                      </select>
                      <div class="relative flex-1">
                        <input
                          v-model="item.discount_amount"
                          v-if="item.discount_id == 2"
                          type="number"
                          min="0"
                          step="0.01"
                          class="ds-field"
                          @input="calculateItemTotal(index)"
                        />
                        <input
                          v-model="item.discount_percentage"
                          v-else
                          type="number"
                          min="0"
                          max="100"
                          step="0.01"
                          class="ds-field"
                          @input="calculateItemTotal(index)"
                        />
                        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm font-bold text-[var(--ds-text-soft)]">
                          {{ item.discount_id == 2 ? 'AOA' : '%' }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- TAX PERCENTAGE -->
                  <div class="space-y-2" v-if="item.charge_tax">
                    <label class="ds-field-label text-xs">
                      {{ $t('gestlab.general.labels.vap_proposals.edit.items.tax') }}
                    </label>
                    <div class="relative">
                      <input
                        v-model="item.tax_percentage"
                        type="number"
                        min="0"
                        max="100"
                        step="0.01"
                        class="ds-field pr-10"
                        @input="calculateItemTotal(index)"
                      />
                      <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm font-bold text-[var(--ds-text-soft)]">%</span>
                    </div>
                  </div>

                  <!-- CHARGE TAX CHECKBOX -->
                  <div class="flex items-center space-x-2">
                    <input
                      v-model="item.charge_tax"
                      type="checkbox"
                      :id="`charge_tax_${index}`"
                      class="ds-checkbox"
                      @change="calculateItemTotal(index)"
                    />
                    <label :for="`charge_tax_${index}`" class="text-xs font-semibold text-[var(--ds-text-muted)]">
                      {{ $t('gestlab.general.labels.vap_proposals.edit.items.charge_tax') }}
                    </label>
                  </div>

                  <!-- TOTAL -->
                  <div class="space-y-2">
                    <label class="ds-field-label text-xs">
                      {{ $t('gestlab.general.labels.vap_proposals.edit.items.total') }}
                    </label>
                    <div class="flex items-center">
                      <div class="flex-1 rounded-2xl border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2 text-sm font-black text-[var(--ds-text)]">
                        AOA {{ formatNumber(itemsWithSubTotal[index]?.total || 0) }}
                      </div>
                    </div>
                  </div>

                  <!-- OBSERVATIONS -->
                  <div class="space-y-2 col-span-full">
                    <label class="ds-field-label text-xs">
                      {{ $t('gestlab.general.labels.vap_proposals.edit.items.observations') }}
                    </label>
                    <textarea
                      v-model="item.obs"
                      rows="2"
                      class="ds-field min-h-24"
                      :placeholder="$t('gestlab.general.labels.vap_proposals.edit.items.observations_placeholder')"
                    ></textarea>
                  </div>
                </div>

                <!-- ITEM BREAKDOWN -->
                <div v-if="item.qty && item.unit_price" class="mt-4 border-t border-[var(--ds-border)] pt-4">
                  <div class="grid grid-cols-2 md:grid-cols-5 gap-2 text-xs">
                    <div class="rounded-2xl border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-2">
                      <div class="font-bold text-[var(--ds-text-muted)]">{{ $t('gestlab.general.labels.vap_proposals.edit.items.quantity') }}</div>
                      <div class="font-black text-[var(--ds-text)]">{{ item.qty }}</div>
                    </div>
                    <div class="rounded-2xl border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-2">
                      <div class="font-bold text-[var(--ds-text-muted)]">{{ $t('gestlab.general.labels.vap_proposals.edit.items.unit_price') }}</div>
                      <div class="font-black text-[var(--ds-text)]">AOA {{ formatNumber(item.unit_price) }}</div>
                    </div>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-2 dark:border-emerald-300/20 dark:bg-emerald-400/10" v-if="itemsWithSubTotal[index]?.discount_amount > 0">
                      <div class="font-bold text-emerald-800 dark:text-emerald-100">{{ $t('gestlab.general.labels.vap_proposals.edit.items.discount') }}</div>
                      <div class="font-black text-emerald-700 dark:text-emerald-200">-AOA {{ formatNumber(itemsWithSubTotal[index]?.discount_amount || 0) }}</div>
                    </div>
                    <div class="rounded-2xl border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-2" v-if="itemsWithSubTotal[index]?.tax_amount > 0">
                      <div class="font-bold text-[var(--ds-text-muted)]">{{ $t('gestlab.general.labels.vap_proposals.edit.items.tax') }} ({{ item.tax_percentage }}%)</div>
                      <div class="font-black text-[var(--ds-text)]">AOA {{ formatNumber(itemsWithSubTotal[index]?.tax_amount || 0) }}</div>
                    </div>
                    <div class="rounded-2xl border border-[rgb(var(--primary-200-rgb))] bg-[rgb(var(--primary-50-rgb))] p-2 dark:border-[rgb(var(--primary-300-rgb)/0.22)] dark:bg-[rgb(var(--primary-400-rgb)/0.12)]">
                      <div class="font-bold text-[rgb(var(--primary-800-rgb))] dark:text-[rgb(var(--accent-100-rgb))]">{{ $t('gestlab.general.labels.vap_proposals.edit.items.item_total') }}</div>
                      <div class="font-black text-[rgb(var(--primary-900-rgb))] dark:text-[rgb(var(--accent-100-rgb))]">AOA {{ formatNumber(itemsWithSubTotal[index]?.total || 0) }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- TOTALS SUMMARY -->
          <div v-if="form.items.length > 0" class="border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div class="text-sm font-semibold text-[var(--ds-text-muted)]">
                {{ form.items.length }} {{ $t('gestlab.general.buttons.items') }}
              </div>
              <div class="text-right">
                <div class="text-sm font-semibold text-[var(--ds-text-muted)]">
                  {{ $t('gestlab.general.labels.vap_proposals.edit.items.subtotal') }}
                </div>
                <div class="text-lg font-black text-[var(--ds-text)]">
                  AOA {{ formatNumber(calculateSubtotal) }}
                </div>
              </div>
              <div class="text-right">
                <div class="text-sm font-semibold text-[var(--ds-text-muted)]">
                  {{ $t('gestlab.general.labels.vap_proposals.edit.items.discount') }}
                </div>
                <div class="text-lg font-black text-emerald-700 dark:text-emerald-200">
                  -AOA {{ formatNumber(discountTotal) }}
                </div>
              </div>
              <div class="text-right">
                <div class="text-sm font-semibold text-[var(--ds-text-muted)]">
                  {{ $t('gestlab.general.labels.vap_proposals.edit.items.total') }}
                </div>
                <div class="text-2xl font-black text-[var(--ds-text)]">
                  AOA {{ formatNumber(proposalTotal) }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6 xl:sticky xl:top-6 xl:self-start">
        <!-- ACTIONS CARD -->
        <div class="ds-panel p-6">
          <p class="ds-kicker">{{ $t('gestlab.general.labels.vap_proposals.surface.decision') }}</p>
          <h3 class="ds-heading mb-4 mt-2 text-2xl">
            {{ $t('gestlab.general.labels.vap_proposals.edit.actions.title') }}
          </h3>
          <div class="space-y-4">
            <button 
              @click="submit"
              :disabled="form.processing || !isFormValid || !form.revision_reason"
              :class="[
                'ds-button w-full justify-center',
                form.processing || !isFormValid || !form.revision_reason
                  ? 'cursor-not-allowed border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[var(--ds-text-soft)]'
                  : 'ds-button-primary'
              ]"
            >
              <CheckCircleIcon class="h-5 w-5" />
              {{ form.processing ? $t('actions.processing') : $t('gestlab.general.labels.vap_proposals.edit.submit') }}
            </button>
            
            <button 
              @click="resetForm"
              type="button"
              class="ds-button ds-button-secondary w-full justify-center"
            >
              <ArrowPathIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_proposals.edit.reset_changes') }}
            </button>
            
            <!-- QUICK STATS -->
            <div class="border-t border-[var(--ds-border)] pt-4">
              <h4 class="mb-2 text-sm font-black text-[var(--ds-text)]">
                {{ $t('gestlab.general.labels.vap_proposals.edit.stats.title') }}
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-[var(--ds-text-muted)]">{{ $t('gestlab.general.labels.vap_proposals.edit.stats.total_items') }}</span>
                  <span class="font-black text-[var(--ds-text)]">{{ form.items.length }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-[var(--ds-text-muted)]">{{ $t('gestlab.general.labels.vap_proposals.edit.items.subtotal') }}</span>
                  <span class="font-black text-[var(--ds-text)]">AOA {{ formatNumber(calculateSubtotal) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-[var(--ds-text-muted)]">{{ $t('gestlab.general.labels.vap_proposals.edit.items.discount') }}</span>
                  <span class="font-black text-emerald-700 dark:text-emerald-200">-AOA {{ formatNumber(discountTotal) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-[var(--ds-text-muted)]">{{ $t('gestlab.general.labels.vap_proposals.edit.tolerance_days') }}</span>
                  <span class="font-black text-[var(--ds-text)]">{{ form.tolerance_days || 7 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-[var(--ds-text-muted)]">{{ $t('gestlab.general.labels.vap_proposals.edit.items.total') }}</span>
                  <span class="font-black text-[var(--ds-text)]">AOA {{ formatNumber(proposalTotal) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- PROPOSAL STATUS CARD -->
        <div class="ds-panel p-6">
          <p class="ds-kicker">{{ $t('gestlab.general.labels.vap_proposals.surface.status') }}</p>
          <h3 class="ds-heading mb-4 mt-2 flex items-center gap-2 text-2xl">
            <Cog6ToothIcon class="h-6 w-6 text-[var(--ds-text-soft)]" />
            {{ $t('gestlab.general.labels.vap_proposals.edit.status.title') }}
          </h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm font-semibold text-[var(--ds-text-muted)]">{{ $t('gestlab.general.labels.vap_proposals.edit.status.current') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                proposal.status_badge.class
              ]">
                {{ proposal.status_badge.text }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm font-semibold text-[var(--ds-text-muted)]">{{ $t('gestlab.general.labels.vap_proposals.edit.status.original') }}</span>
              <span class="text-sm font-black text-[var(--ds-text)]">
                {{ proposal.is_original ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no') }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm font-semibold text-[var(--ds-text-muted)]">{{ $t('gestlab.general.labels.vap_proposals.edit.status.created_by') }}</span>
              <span class="text-sm font-black text-[var(--ds-text)]">
                {{ proposal.user?.name || $t('gestlab.general.labels.vap_proposals.edit.not_available') }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm font-semibold text-[var(--ds-text-muted)]">{{ $t('gestlab.general.labels.vap_proposals.edit.status.created_at') }}</span>
              <span class="text-sm font-black text-[var(--ds-text)]">
                {{ formatDate(proposal.created_at) }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm font-semibold text-[var(--ds-text-muted)]">{{ $t('gestlab.general.labels.vap_proposals.surface.expiry') }}</span>
              <span class="text-sm font-black text-[var(--ds-text)]">
                {{ calculateExpiryDate }}
              </span>
            </div>
          </div>
        </div>

        <!-- SELECTED TEMPLATE PREVIEW -->
        <div v-if="selectedTemplate" class="ds-panel p-6">
          <h3 class="mb-4 flex items-center gap-2 text-lg font-black text-[var(--ds-text)]">
            <DocumentTextIcon class="h-5 w-5 text-[var(--ds-text-soft)]" />
            {{ $t('gestlab.general.labels.vap_proposals.edit.template_preview') }}
          </h3>
          <div class="space-y-3">
            <div>
              <h4 class="text-sm font-black text-[var(--ds-text)]">{{ selectedTemplate.name }}</h4>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
                {{ $t('gestlab.general.labels.vap_proposals.show.template.created_by') }} {{ selectedTemplate.user.name }}
              </p>
            </div>
            <div class="ds-copy mt-4 line-clamp-4 max-h-32 overflow-y-auto text-sm">
              {{ stripHtml(selectedTemplate.content) }}
            </div>
            <button 
              @click="showTemplatePreview = true"
              class="mt-2 text-xs font-black text-[rgb(var(--primary-700-rgb))] underline decoration-[rgb(var(--primary-300-rgb))] underline-offset-4 dark:text-[rgb(var(--accent-100-rgb))]"
            >
              {{ $t('gestlab.general.labels.vap_proposals.edit.view_full_template') }}
            </button>
          </div>
        </div>

        <!-- QUICK ADD ITEMS -->
        <div v-if="quickItems.length" class="ds-panel p-6">
          <h3 class="mb-4 flex items-center gap-2 text-lg font-black text-[var(--ds-text)]">
            <BoltIcon class="h-5 w-5 text-[var(--ds-text-soft)]" />
            {{ $t('gestlab.general.labels.vap_proposals.edit.quick_add') }}
          </h3>
          <div class="space-y-3">
            <button 
              v-for="quickItem in quickItems"
              :key="quickItem.name"
              @click="addQuickItem(quickItem)"
              class="w-full rounded-2xl border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-3 text-left transition hover:border-[rgb(var(--primary-300-rgb)/0.72)]"
            >
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-sm font-black text-[var(--ds-text)]">{{ quickItem.name }}</div>
                  <div class="text-xs font-semibold text-[var(--ds-text-muted)]">{{ quickItem.description }}</div>
                </div>
                <div class="text-sm font-black text-[var(--ds-text)]">AOA {{ formatNumber(quickItem.price) }}</div>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- FOOTER ACTIONS -->
    <div class="ds-panel flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
      <div class="ds-copy text-sm">
        {{ $t('gestlab.general.labels.vap_proposals.edit.footer_note') }}
      </div>
      <div class="flex items-center gap-4">
        <button 
          @click="resetForm"
          type="button"
          class="ds-button ds-button-secondary"
        >
          <ArrowPathIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.vap_proposals.edit.reset_changes') }}
        </button>
        <button 
          @click="submit"
          :disabled="form.processing || !isFormValid || !form.revision_reason"
          :class="[
            'ds-button',
            form.processing || !isFormValid || !form.revision_reason
              ? 'cursor-not-allowed border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[var(--ds-text-soft)]'
              : 'ds-button-primary'
          ]"
        >
          <CheckCircleIcon class="h-5 w-5" />
          {{ form.processing ? $t('actions.processing') : $t('gestlab.general.labels.vap_proposals.edit.submit') }}
        </button>
      </div>
    </div>

    <!-- TEMPLATE PREVIEW MODAL -->
    <Modal :show="showTemplatePreview" @close="showTemplatePreview = false" max-width="4xl">
      <div class="p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="ds-heading text-lg">
            {{ selectedTemplate?.name }}
          </h2>
          <button @click="showTemplatePreview = false" class="ds-icon-button">
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>
        <div class="prose max-h-[60vh] max-w-none overflow-y-auto rounded-2xl border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-6 dark:prose-invert">
          <div v-html="selectedTemplate?.content"></div>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { commercialDocumentThemeClasses } from '@/Composables/useCommercialDocumentTheme'
import { useForm, Link } from '@inertiajs/vue3'
import { 
  DocumentTextIcon, ArrowLeftIcon, InformationCircleIcon,
  UserIcon, BuildingStorefrontIcon, BuildingOfficeIcon,
  MapPinIcon, ClockIcon, ListBulletIcon,
  PlusCircleIcon, DocumentDuplicateIcon, TrashIcon,
  CheckCircleIcon, Cog6ToothIcon, BoltIcon, ArrowPathIcon,
  XMarkIcon, ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'
import Modal from '@/Components/modal.vue'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import { trans } from 'laravel-vue-i18n'

const props = defineProps({
  proposal: Object,
  customers: Array,
  warehouses: Array,
  departments: Array,
  templates: Array,
  units: Array,
  standards: Array,
  discount_categories: {
    type: Array,
    default: () => [
      { value: 1, label: '%' },
      { value: 2, label: 'AOA' }
    ]
  }
})

const form = useForm({
  template_id: props.proposal.template_id,
  service_location: props.proposal.service_location,
  tolerance_days: props.proposal.tolerance_days,
  withhold_tax: props.proposal.withhold_tax,
  use_matrix_price: props.proposal.use_matrix_price ?? true,
  obs: props.proposal.obs || '',
  revision_reason: '',
  items: [],
})

// UI State
const showTemplatePreview = ref(false)
const loadingParameters = ref(false)
const formErrorMessage = ref('')
const loadErrorMessage = ref('')
const resetConfirmationPending = ref(false)

// Selection refs
const templateSelected = ref(null)
const labcode_id = ref(null)

// Create refs for combobox display values
const unitOptions = computed(() => 
  props.units.map(unit => ({
    value: unit.id,
    label: unit.code || unit.description,
    data: unit
  }))
)

const standardOptions = computed(() => 
  props.standards.map(standard => ({
    value: standard.id,
    label: standard.code || standard.description,
    data: standard
  }))
)

const parseJsonResponse = async (response) => {
  const contentType = response.headers.get('content-type') || ''

  if (!response.ok || !contentType.includes('application/json')) {
    throw new Error(trans('gestlab.general.labels.vap_proposals.form.invalid_json_response', { status: response.status }))
  }

  return response.json()
}

// Initialize form with proposal data
onMounted(() => {
  // Set template selected
  if (props.proposal.template_id) {
    const template = props.templates.find(t => t.id === props.proposal.template_id)
    if (template) {
      templateSelected.value = {
        value: template.id,
        label: template.name,
        name: template.name,
        user: template.user,
        content: template.content,
      }
    }
  }
  
  // Load proposal items
  if (props.proposal.items && props.proposal.items.length > 0) {
    form.items = props.proposal.items.map(item => {
      // Find unit display value
      const unitDisplay = unitOptions.value.find(u => u.value === item.unit_id) || null
      
      // Find standard display value
      const standardDisplay = standardOptions.value.find(s => s.value === item.standard_id) || null
      
      // Create item selection object if itemable data exists
      let itemSelection = null
      if (item.itemable_type && item.itemable_id) {
        const type = item.itemable_type.includes('Matrix') ? 'matrix' : 'parameter'
        itemSelection = {
          value: item.itemable_id,
          label: item.item_description || '',
          type: type,
          price: item.unit_price || 0,
          tax_percentage: item.tax_percentage || 0,
          charge_tax: item.charge_tax ?? true,
          tax_id: item.tax_id || null,
          exemption_id: item.exemption_id || null,
          exemption_code: item.exemption_code || '',
        }
      }
      
      return {
        item_id: item.item_id || item.itemable_id,
        itemable_type: item.itemable_type,
        itemable_id: item.itemable_id,
        item_selection: itemSelection,
        item_description: item.item_description,
        standard_id: item.standard_id,
        standard_display: standardDisplay,
        unit_id: item.unit_id,
        unit_display: unitDisplay,
        qty: item.qty,
        unit_price: item.unit_price,
        discount_percentage: item.discount_percentage || 0,
        discount_id: item.discount_id || 1,
        discount_amount: item.discount_amount || 0,
        total: item.total || 0,
        tax_percentage: item.tax_percentage || 0,
        tax_amount: item.tax_amount || 0,
        tax_id: item.tax_id || null,
        charge_tax: item.charge_tax ?? true,
        withhold_tax: item.withhold_tax ?? false,
        exemption_id: item.exemption_id || null,
        exemption_code: item.exemption_code || '',
        obs: item.obs || '',
      }
    })
  }
})

// Watch for lab code changes
watch(labcode_id, (newVal) => {
  if (newVal?.value) {
    loadParametersBasedOnLabCode(newVal.value)
  }
})

// Load parameters based on lab code
const loadParametersBasedOnLabCode = async (code_id) => {
  if (!code_id) return
  
  loadingParameters.value = true
  loadErrorMessage.value = ''
  
  try {
    const response = await fetch(route('vap-proposals.options.lab-code-parameters', {
      code_id,
      use_matrix_price: form.use_matrix_price ? 1 : 0,
    }))
    const results = await parseJsonResponse(response)
    
    // Clear existing items and add new ones from lab code
    form.items = []
    
    results.forEach(item => {
      const itemableType = form.use_matrix_price ? 'matrix' : 'parameter'
      
      const newItem = {
        item_id: item.item_id,
        itemable_type: `App\\Models\\${itemableType.charAt(0).toUpperCase() + itemableType.slice(1)}`,
        itemable_id: item.item_id,
        item_selection: { 
          value: item.item_id,
          label: item.name || item.description,
          price: item.price,
          tax_id: item.tax_id,
          charge_tax: item.charge_tax,
          tax_percentage: item.tax_percentage,
          exemption_id: item.exemption_id,
          exemption_code: item.exemption_code,
          type: itemableType
        },
        item_description: item.item_description || item.name,
        standard_id: item.standard_id || null,
        standard_display: standardOptions.value.find(s => s.value === item.standard_id) || null,
        unit_id: item.unit_id || props.units[0]?.id || null,
        unit_display: unitOptions.value.find(u => u.value === (item.unit_id || props.units[0]?.id)) || null,
        qty: item.qty || 1,
        unit_price: item.unit_price || item.price || 0,
        discount_percentage: 0,
        discount_id: 1,
        discount_amount: 0,
        total: 0,
        tax_percentage: item.tax_percentage || 0,
        tax_amount: 0,
        tax_id: item.tax_id || null,
        charge_tax: item.charge_tax ?? true,
        withhold_tax: item.withhold_tax || false,
        exemption_id: item.exemption_id || null,
        exemption_code: item.exemption_code || '',
        obs: item.obs || '',
      }
      
      // Calculate initial total
      newItem.total = lineSubTotalAmount(newItem)
      newItem.tax_amount = lineTaxAmount(newItem)
      
      form.items.push(newItem)
    })
  } catch {
    loadErrorMessage.value = trans('gestlab.general.labels.vap_proposals.form.load_parameters_error')
  } finally {
    loadingParameters.value = false
  }
}

watch(templateSelected, (newVal) => form.template_id = newVal?.value || '')

// Function to load items from matrix or parameter
function loadMatrixes(query, setOptions) {
  fetch(route('vap-proposals.options.matrixes', { q: query || '' }))
    .then(parseJsonResponse)
    .then(results => {
      setOptions(
        results.map(result => ({
          value: result.id,
          label: result.description,
          price: result.price ?? result.fixed_price ?? 0,
          tax_id: result.tax_id,
          charge_tax: result.charge_tax,
          tax_percentage: result.tax_percentage,
          exemption_id: result.exemption_id,
          exemption_code: result.exemption_code,
          type: 'matrix'
        }))
      )
    })
    .catch(() => setOptions([]))
}

function loadParameters(query, setOptions) {
  fetch(route('vap-proposals.options.parameters', { q: query || '' }))
    .then(parseJsonResponse)
    .then(results => {
      setOptions(
        results.map(result => ({
          value: result.id,
          label: result.name,
          price: result.price,
          tax_id: result.tax_id,
          charge_tax: result.charge_tax,
          tax_percentage: result.tax_percentage,
          exemption_id: result.exemption_id,
          exemption_code: result.exemption_code,
          type: 'parameter'
        }))
      )
    })
    .catch(() => setOptions([]))
}

function loadLabCodes(query, setOptions) {
  fetch(route('vap-proposals.options.lab-codes', { q: query || '' }))
    .then(parseJsonResponse)
    .then(results => {
      setOptions(
        results.map(result => ({
          value: result.id,
          label: result.code,
        }))
      )
    })
    .catch(() => setOptions([]))
}

// When an item is selected from matrix/parameter
const onSelectedItem = (item, index) => {
  if (item.item_selection) {
    const selection = item.item_selection
    
    // Set polymorphic relationship
    form.items[index].itemable_type = `App\\Models\\${selection.type.charAt(0).toUpperCase() + selection.type.slice(1)}`
    form.items[index].itemable_id = selection.value
    form.items[index].item_id = selection.value
    
    // Set price and other data
    form.items[index].unit_price = selection.price || 0
    form.items[index].tax_percentage = selection.tax_percentage || 0
    form.items[index].charge_tax = selection.charge_tax ?? true
    form.items[index].tax_id = selection.tax_id || null
    form.items[index].exemption_id = selection.exemption_id || null
    form.items[index].exemption_code = selection.exemption_code || ''
    
    // Set description if empty
    if (!form.items[index].item_description) {
      form.items[index].item_description = selection.label
    }
    
    calculateItemTotal(index)
  }
}

// Handle unit selection change
const onUnitChange = (item, index) => {
  if (item.unit_display) {
    form.items[index].unit_id = item.unit_display.value
    calculateItemTotal(index)
  }
}

// Handle standard selection change
const onStandardChange = (item, index) => {
  if (item.standard_display) {
    form.items[index].standard_id = item.standard_display.value
  }
}

const quickItems = ref([])

// Computed Properties
const selectedTemplate = computed(() => {
  return props.templates.find(t => t.id === form.template_id)
})

const hasBasicInfo = computed(() => {
  return form.template_id && form.service_location && form.revision_reason
})

const hasItems = computed(() => {
  return form.items.length > 0
})

const isFormValid = computed(() => {
  return hasBasicInfo.value && hasItems.value && form.revision_reason
})

// CALCULATION LOGIC (same as create)
const itemsWithSubTotal = computed(() => {
  return form.items.map(item => ({
    item,
    tax: lineTaxPercentage(item),
    qty: parseFloat(item.qty) || 1,
    unit_id: item.unit_id,
    standard_id: item.standard_id,
    charge_tax: lineChargeTax(item),
    withhold_tax: lineWithholdTax(item),
    unit_price: lineUnitPrice(item),
    product_price: onSelectedItemPrice(item),
    total: lineSubTotalAmount(item),
    discount_amount: lineDiscountAmount(item),
    tax_amount: lineTaxAmount(item),
    discount_percentage: item.discount_percentage || 0,
  }))
})

// Line calculation methods
const lineChargeTax = (item) => {
  return item.charge_tax ?? true
}

const lineWithholdTax = (item) => {
  return item.withhold_tax ?? false
}

const lineTaxPercentage = (item) => {
  return parseFloat(item.tax_percentage) || 0
}

const lineUnitPrice = (item) => {
  const price = onSelectedItemPrice(item)
  const discount = lineDiscountAmount(item)
  return parseFloat((price - parseFloat(discount)).toFixed(2))
}

const lineSubTotalAmount = (item) => {
  const unitPrice = lineUnitPrice(item)
  const qty = parseFloat(item.qty) || 1
  const subtotal = unitPrice * qty
  return parseFloat(subtotal.toFixed(2))
}

const lineDiscountAmount = (item) => {
  const discountId = item.discount_id || 1
  const price = onSelectedItemPrice(item)
  const qty = parseFloat(item.qty) || 1
  const totalPrice = price * qty
  
  if (discountId == 2) {
    const discount = parseFloat(item.discount_amount) || 0
    return parseFloat((discount <= totalPrice ? discount : 0).toFixed(2))
  } else if (discountId == 1) {
    const discountPercent = parseFloat(item.discount_percentage) || 0
    return parseFloat(((discountPercent <= 100 ? (discountPercent / 100) * totalPrice : 0)).toFixed(2))
  } else {
    return parseFloat(0)
  }
}

const lineTaxAmount = (item) => {
  const subTotal = lineSubTotalAmount(item)
  const taxPercent = lineTaxPercentage(item)
  const shouldChargeTax = lineChargeTax(item)
  
  return shouldChargeTax ? parseFloat((subTotal * taxPercent / 100).toFixed(2)) : 0
}

const onSelectedItemPrice = (item) => {
  return parseFloat(item.unit_price) || 0
}

// Updated computed totals
const calculateSubtotal = computed(() => {
  return parseFloat(itemsWithSubTotal.value.map(item => item.total).reduce((prev, curr) => prev + curr, 0)).toFixed(2)
})

const taxTotal = computed(() => {
  return parseFloat(itemsWithSubTotal.value.map(item => (item.tax_amount ? item.tax_amount : 0)).reduce((prev, curr) => prev + curr, 0)).toFixed(2)
})

const discountTotal = computed(() => {
  return parseFloat(itemsWithSubTotal.value.map(item => (item.discount_amount ? item.discount_amount : 0)).reduce((prev, curr) => prev + curr, 0)).toFixed(2)
})

const proposalTotal = computed(() => {
  const subtotal = parseFloat(calculateSubtotal.value)
  const tax = parseFloat(taxTotal.value)
  return (subtotal + tax).toFixed(2)
})

const calculateExpiryDate = computed(() => {
  if (!form.tolerance_days) return '-'
  const days = parseInt(form.tolerance_days)
  const date = new Date()
  date.setDate(date.getDate() + days)
  return date.toLocaleDateString('pt-AO')
})

// Methods
const addItem = () => {
  const defaultUnitId = props.units[0]?.id || null
  const defaultUnitDisplay = unitOptions.value.find(u => u.value === defaultUnitId) || null
  
  form.items.push({
    item_id: null,
    itemable_type: null,
    itemable_id: null,
    item_selection: null,
    item_description: '',
    standard_id: null,
    standard_display: null,
    unit_id: defaultUnitId,
    unit_display: defaultUnitDisplay,
    qty: 1,
    unit_price: 0,
    discount_percentage: 0,
    discount_id: 1,
    discount_amount: 0,
    total: 0,
    tax_percentage: 0,
    tax_amount: 0,
    tax_id: null,
    charge_tax: true,
    withhold_tax: false,
    exemption_id: null,
    exemption_code: '',
    obs: '',
  })
}

const duplicateItem = (index) => {
  const itemToDuplicate = { ...form.items[index] }
  itemToDuplicate.item_description = `${itemToDuplicate.item_description} ${trans('gestlab.general.labels.vap_proposals.form.duplicate_suffix')}`
  form.items.splice(index + 1, 0, itemToDuplicate)
  
  // Recalculate the new item
  const newIndex = index + 1
  form.items[newIndex].total = lineSubTotalAmount(form.items[newIndex])
  form.items[newIndex].tax_amount = lineTaxAmount(form.items[newIndex])
}

const removeItem = (index) => {
  if (form.items.length > 1) {
    form.items.splice(index, 1)
  } else {
    formErrorMessage.value = trans('gestlab.general.labels.vap_proposals.form.minimum_one_item')
  }
}

const addQuickItem = (quickItem) => {
  const defaultUnitId = quickItem.unit_id || props.units[0]?.id || null
  const defaultUnitDisplay = unitOptions.value.find(u => u.value === defaultUnitId) || null
  
  const newItem = {
    item_id: null,
    itemable_type: null,
    itemable_id: null,
    item_selection: null,
    item_description: quickItem.item_description,
    standard_id: null,
    standard_display: null,
    unit_id: defaultUnitId,
    unit_display: defaultUnitDisplay,
    qty: quickItem.qty,
    unit_price: quickItem.unit_price,
    discount_percentage: 0,
    discount_id: 1,
    discount_amount: 0,
    total: quickItem.qty * quickItem.unit_price,
    tax_percentage: 0,
    tax_amount: 0,
    charge_tax: true,
    withhold_tax: false,
  }
  form.items.push(newItem)
}

const calculateItemTotal = (index) => {
  const item = form.items[index]
  
  if (!item) return
  
  // Update total using the new calculation logic
  form.items[index].total = lineSubTotalAmount(item)
  
  // Also update tax amount if needed
  form.items[index].tax_amount = lineTaxAmount(item)
}

const formatNumber = (number) => {
  return new Intl.NumberFormat('pt-AO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number(number || 0))
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('pt-AO')
}

const stripHtml = (html) => {
  if (!html) return ''
  const text = html.replace(/<[^>]*>/g, '').trim()

  return text.length > 200 ? `${text.substring(0, 200)}...` : text
}

const resetForm = () => {
  if (!resetConfirmationPending.value) {
    resetConfirmationPending.value = true
    formErrorMessage.value = trans('gestlab.general.labels.vap_proposals.form.reset_edit_confirm')
    window.setTimeout(() => {
      resetConfirmationPending.value = false
    }, 5000)

    return
  }

  resetConfirmationPending.value = false
  formErrorMessage.value = ''
  loadErrorMessage.value = ''
  // Reload the form with original data
  form.template_id = props.proposal.template_id
  form.service_location = props.proposal.service_location
  form.tolerance_days = props.proposal.tolerance_days
  form.withhold_tax = props.proposal.withhold_tax
  form.use_matrix_price = props.proposal.use_matrix_price ?? true
  form.obs = props.proposal.obs || ''
  form.revision_reason = ''

  // Reload items
  if (props.proposal.items && props.proposal.items.length > 0) {
    form.items = props.proposal.items.map(item => {
      const unitDisplay = unitOptions.value.find(u => u.value === item.unit_id) || null
      const standardDisplay = standardOptions.value.find(s => s.value === item.standard_id) || null

      let itemSelection = null
      if (item.itemable_type && item.itemable_id) {
        const type = item.itemable_type.includes('Matrix') ? 'matrix' : 'parameter'
        itemSelection = {
          value: item.itemable_id,
          label: item.item_description || '',
          type: type,
          price: item.unit_price || 0,
          tax_percentage: item.tax_percentage || 0,
          charge_tax: item.charge_tax ?? true,
          tax_id: item.tax_id || null,
          exemption_id: item.exemption_id || null,
          exemption_code: item.exemption_code || '',
        }
      }

      return {
        item_id: item.item_id || item.itemable_id,
        itemable_type: item.itemable_type,
        itemable_id: item.itemable_id,
        item_selection: itemSelection,
        item_description: item.item_description,
        standard_id: item.standard_id,
        standard_display: standardDisplay,
        unit_id: item.unit_id,
        unit_display: unitDisplay,
        qty: item.qty,
        unit_price: item.unit_price,
        discount_percentage: item.discount_percentage || 0,
        discount_id: item.discount_id || 1,
        discount_amount: item.discount_amount || 0,
        total: item.total || 0,
        tax_percentage: item.tax_percentage || 0,
        tax_amount: item.tax_amount || 0,
        tax_id: item.tax_id || null,
        charge_tax: item.charge_tax ?? true,
        withhold_tax: item.withhold_tax ?? false,
        exemption_id: item.exemption_id || null,
        exemption_code: item.exemption_code || '',
        obs: item.obs || '',
      }
    })
  } else {
    form.items = []
  }
}

const submit = () => {
  formErrorMessage.value = ''

  if (!isFormValid.value) {
    formErrorMessage.value = trans('gestlab.general.labels.vap_proposals.form.required_fields')
    return
  }

  // Transform data for submission
  const transformedData = {
    ...form.data(),
    tax: taxTotal.value,
    total: proposalTotal.value,
    sub_total: calculateSubtotal.value,
    discount: discountTotal.value,
    use_matrix_price: form.use_matrix_price,
    items: form.items.map((item, index) => {
      const itemData = itemsWithSubTotal.value[index]
      
      return {
        item_id: item.item_id || item.itemable_id,
        itemable_type: item.itemable_type,
        itemable_id: item.itemable_id,
        item_description: item.item_description || item.item_selection?.label || '',
        discount_id: item.discount_id || 1,
        discount_percentage: item.discount_percentage || 0,
        discount_amount: itemData?.discount_amount || 0,
        qty: item.qty || 1,
        obs: item.obs || '',
        exemption_id: item.exemption_id || null,
        exemption_code: item.exemption_code || '',
        tax_id: item.tax_id || null,
        tax_percentage: item.tax_percentage || item.item_selection?.tax_percentage || 0,
        charge_tax: item.charge_tax ?? true,
        withhold_tax: item.withhold_tax ?? false,
        unit_price: itemData?.unit_price || item.unit_price || 0,
        unit_id: item.unit_id,
        standard_id: item.standard_id,
        total: itemData?.total || 0,
        tax_amount: itemData?.tax_amount || 0,
      }
    })
  }

  form.transform(() => transformedData)
    .put(route('vap-proposals.update', { proposal: props.proposal.id }), {
      preserveScroll: true,
      onError: () => {
        formErrorMessage.value = trans('gestlab.general.labels.vap_proposals.form.update_error')
      }
    })
}
</script>
