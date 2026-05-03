<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ExclamationTriangleIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.occurrences.page_title') }}
          </h1>
          <p class="mt-2 text-sm text-gray-600">
            {{ $t('gestlab.general.labels.occurrences.page_update_description') }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <Link
            as="button"
            :href="route('occurrences.index')"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <ArrowLeftIcon class="h-4 w-4" />
            {{ $t('gestlab.general.buttons.back') }}
        </Link>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- BASIC INFORMATION CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <InformationCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.occurrences.basic_information') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

              <!-- DATE REPORTED -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <CalendarIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.occurrences.date_reported') }}
                  <span class="text-red-500">*</span>
                </label>
                <date-picker-enhanced
                    v-model="form.date_reported"
                    locale="pt"
                    color="blue"
                    mode="date"
                    :popover-placement="'bottom-start'"
                    :placeholder="$t('gestlab.general.labels.occurrences.select_date')"

                />
                <p v-if="form.errors.date_reported" class="text-xs text-red-600">
                  {{ form.errors.date_reported }}
                </p>
              </div>

              <!-- ISSUE DESCRIPTION -->
              <div class="md:col-span-2 space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <DocumentTextIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.occurrences.issue_description') }}
                  <span class="text-red-500">*</span>
                </label>
                <textarea
                  v-model="form.issue_description"
                  rows="4"
                  :placeholder="$t('gestlab.general.labels.occurrences.issue_description_placeholder')"
                  :class="[
                    'w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-transparent transition-colors duration-200',
                    form.errors.issue_description ? 'border-red-500' : 'border-gray-300'
                  ]"
                />
                <p v-if="form.errors.issue_description" class="text-xs text-red-600">
                  {{ form.errors.issue_description }}
                </p>
              </div>

              <!-- CATEGORY -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <FolderIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.occurrences.category_id') }}
                </label>
                <combobox-enhanced
                  v-model="form.category_id"
                  :hasError="form.errors.category_id"
                  :load-options="loadCategories"
                  :placeholder="$t('gestlab.general.labels.occurrences.select_category')"
                />
              </div>

              <!-- ORIGIN -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <MapPinIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.occurrences.origin') }}
                </label>
                <combobox-enhanced
                  v-model="form.origin_id"
                  :hasError="form.errors.origin"
                  :load-options="loadOrigins"
                  :placeholder="$t('gestlab.general.labels.occurrences.select_origin')"
                />
              </div>

              <!-- DEPARTMENT -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <BuildingOfficeIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.occurrences.department_id') }}
                </label>
                <combobox-enhanced
                  v-model="form.department_id"
                  :hasError="form.errors.department_id"
                  :load-options="loadDepartments"
                  :placeholder="$t('gestlab.general.labels.occurrences.select_department')"
                />
              </div>

              <!-- RESPONSIBLE NAME -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <UserIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.occurrences.responsible_name') }}
                </label>
                <input
                  type="text"
                  v-model="form.responsible_name"
                  :placeholder="$t('gestlab.general.labels.occurrences.responsible_name_placeholder')"
                  :class="[
                    'w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-transparent transition-colors duration-200',
                    form.errors.responsible_name ? 'border-red-500' : 'border-gray-300'
                  ]"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- ANALYSIS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <MagnifyingGlassIcon class="h-5 w-5 text-blue-900" />
              {{ $t('gestlab.general.labels.occurrences.analysis_investigation') }}
            </h2>
          </div>

          <div class="p-6">
            <div class="space-y-6">
              <!-- ANALYSIS -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.occurrences.analysis') }}
                </label>
                <textarea
                  v-model="form.analysis"
                  rows="3"
                  :placeholder="$t('gestlab.general.labels.occurrences.analysis_placeholder')"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-transparent transition-colors duration-200"
                />
              </div>

              <!-- CAUSE ANALYSIS -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.occurrences.cause_corrective_actions') }}
                </label>
                <textarea
                  v-model="form.cause_corrective_actions"
                  rows="2"
                  :placeholder="$t('gestlab.general.labels.occurrences.cause_analysis_placeholder')"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-transparent transition-colors duration-200"
                />
              </div>

              <!-- EFFECT ANALYSIS -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.occurrences.effect_corrective_actions') }}
                </label>
                <textarea
                  v-model="form.effect_corrective_actions"
                  rows="2"
                  :placeholder="$t('gestlab.general.labels.occurrences.effect_analysis_placeholder')"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-transparent transition-colors duration-200"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- CORRECTIVE ACTION CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <WrenchIcon class="h-5 w-5 text-blue-900" />
              {{ $t('gestlab.general.labels.occurrences.corrective_action') }}
            </h2>
          </div>

          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- CORRECTIVE ACTION -->
              <div class="md:col-span-2 space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.occurrences.corrective_action_description') }}
                </label>
                <textarea
                  v-model="form.corrective_action"
                  rows="3"
                  :placeholder="$t('gestlab.general.labels.occurrences.corrective_action_placeholder')"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-transparent transition-colors duration-200"
                />
              </div>

              <!-- IMPLEMENTATION DATE -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.occurrences.implementation_date') }}
                </label>
                <date-picker-enhanced
                    v-model="form.implementation_date"
                    locale="pt"
                    color="blue"
                    mode="date"
                    :popover-placement="'bottom-start'"
                    :placeholder="$t('gestlab.general.labels.occurrences.select_date')"
                />
              </div>

              <!-- WAS EFFECTIVE -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.occurrences.was_effective') }}
                </label>
                <select
                  v-model="form.was_effective"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-transparent transition-colors duration-200"
                >
                  <option :value="null">{{ $t('gestlab.general.labels.occurrences.select_effectiveness') }}</option>
                  <option :value="true">{{ $t('gestlab.general.labels.occurrences.effective') }}</option>
                  <option :value="false">{{ $t('gestlab.general.labels.occurrences.not_effective') }}</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6">
        <!-- ACTIONS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.occurrences.actions.title') }}
          </h3>
          <div class="space-y-4">
            <button 
              v-if="form.isDirty"
              @click="showDeleteConfirmation = true"
              :disabled="form.processing"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                form.processing
                  ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                  : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
              ]"
            >
              <PlusCircleIcon class="h-5 w-5" />
              {{ form.processing ? $t('gestlab.general.buttons.processing') : $t('gestlab.general.buttons.submit') }}
            </button>
            
            <button 
              @click="resetForm"
              type="button"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <ArrowPathIcon class="h-5 w-5" />
              {{ $t('gestlab.general.buttons.reset') }}
            </button>
          </div>
        </div>

        <!-- STATUS & DATES CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ClockIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.occurrences.status_dates') }}
          </h3>
          <div class="space-y-4">
            <!-- STATUS -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.occurrences.status') }}
              </label>
                <combobox-enhanced
                    v-model="form.status_id"
                    :hasError="form.errors.status_id"
                    :load-options="loadStatuses"
                    :placeholder="$t('gestlab.general.labels.occurrences.select_status')"
                />
            </div>

            <!-- NOTIFICATION DATE -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.occurrences.notification_date') }}
              </label>

                <date-picker-enhanced
                    v-model="form.notification_date"
                    locale="pt"
                    color="blue"
                    mode="date"
                    :popover-placement="'bottom-start'"
                    :placeholder="$t('gestlab.general.labels.occurrences.select_date')"
                />
            </div>

            <!-- DATE RESOLVED -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.occurrences.date_resolved') }}
              </label>
                <date-picker-enhanced
                    v-model="form.date_resolved"
                    locale="pt"
                    color="blue"
                    mode="date"
                    :popover-placement="'bottom-start'"
                    :placeholder="$t('gestlab.general.labels.occurrences.select_date')"
                />
            </div>

            <!-- DATE CLOSED -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.occurrences.date_closed') }}
              </label>
                <date-picker-enhanced
                    v-model="form.date_closed"
                    locale="pt"
                    color="blue"
                    mode="date"
                    :popover-placement="'bottom-start'"
                    :placeholder="$t('gestlab.general.labels.occurrences.select_date')"
                />
            </div>
          </div>
        </div>

        <!-- CLIENT PROCESS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <UsersIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.occurrences.client_process') }}
          </h3>
          <div class="space-y-4">
            <!-- CLIENT ACCEPTANCE -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.occurrences.client_acceptance') }}
              </label>
              <select
                v-model="form.client_acceptance"
                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-transparent transition-colors duration-200"
              >
                <option :value="null">{{ $t('gestlab.general.labels.occurrences.select_acceptance') }}</option>
                <option :value="true">{{ $t('gestlab.general.labels.occurrences.accepted') }}</option>
                <option :value="false">{{ $t('gestlab.general.labels.occurrences.rejected') }}</option>
              </select>
            </div>

            <!-- ACCEPTANCE COMMENTS -->
            <div v-if="form.client_acceptance !== null" class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.occurrences.client_acceptance_comments') }}
              </label>
              <textarea
                v-model="form.client_acceptance_comments"
                rows="2"
                :placeholder="$t('gestlab.general.labels.occurrences.acceptance_comments_placeholder')"
                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-transparent transition-colors duration-200"
              />
            </div>

            <!-- CLIENT PROCESS DATES -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.occurrences.client_process_open_notification_date') }}
              </label>

                <date-picker-enhanced
                    v-model="form.client_process_open_notification_date"
                    locale="pt"
                    color="blue"
                    mode="date"
                    :popover-placement="'bottom-start'"
                    :placeholder="$t('gestlab.general.labels.occurrences.select_date')"
                />
            </div>

            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.occurrences.client_process_close_notification_date') }}
              </label>

                <date-picker-enhanced
                    v-model="form.client_process_close_notification_date"
                    locale="pt"
                    color="blue"
                    mode="date"
                    :popover-placement="'bottom-start'"
                    :placeholder="$t('gestlab.general.labels.occurrences.select_date')"
                />
            </div>
          </div>
        </div>

        <!-- RISK & BUDGET CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <CurrencyDollarIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.occurrences.risk_budget') }}
          </h3>
          <div class="space-y-4">
            <!-- HAS RISK CORRECTION BUDGET -->
            <div class="flex items-center justify-between">
              <label class="text-sm text-gray-700">
                {{ $t('gestlab.general.labels.occurrences.has_risk_correction_budget') }}
              </label>
              <button 
                type="button"
                @click="form.has_risk_correction_budget = !form.has_risk_correction_budget"
                :class="[
                  'relative inline-flex h-6 w-11 flex-shrink-0 rounded-full border-2 transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
                  form.has_risk_correction_budget ? 'bg-blue-900 border-blue-900' : 'bg-gray-200 border-gray-300'
                ]"
              >
                <span class="sr-only">{{ $t('gestlab.general.labels.occurrences.has_risk_correction_budget') }}</span>
                <span :class="[
                  'pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                  form.has_risk_correction_budget ? 'translate-x-5' : 'translate-x-0'
                ]" />
              </button>
            </div>

            <!-- REASON FOR NO BUDGET -->
            <div v-if="!form.has_risk_correction_budget" class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.occurrences.reason_for_no_risk_correction_budget') }}
              </label>
              <input
                type="text"
                v-model="form.reason_for_no_risk_correction_budget"
                :placeholder="$t('gestlab.general.labels.occurrences.reason_placeholder')"
                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-transparent transition-colors duration-200"
              />
            </div>

            <!-- HAS NON-CONFORMITY TERMS -->
            <div class="flex items-center justify-between">
              <label class="text-sm text-gray-700">
                {{ $t('gestlab.general.labels.occurrences.has_non_conformity_terms') }}
              </label>
              <button 
                type="button"
                @click="form.has_non_conformity_terms = !form.has_non_conformity_terms"
                :class="[
                  'relative inline-flex h-6 w-11 flex-shrink-0 rounded-full border-2 transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
                  form.has_non_conformity_terms ? 'bg-blue-900 border-blue-900' : 'bg-gray-200 border-gray-300'
                ]"
              >
                <span class="sr-only">{{ $t('gestlab.general.labels.occurrences.has_non_conformity_terms') }}</span>
                <span :class="[
                  'pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                  form.has_non_conformity_terms ? 'translate-x-5' : 'translate-x-0'
                ]" />
              </button>
            </div>

            <!-- UPDATE RISK MATRIX -->
            <div class="flex items-center justify-between">
              <label class="text-sm text-gray-700">
                {{ $t('gestlab.general.labels.occurrences.update_risk_matrix') }}
              </label>
              <button 
                type="button"
                @click="form.update_risk_matrix = !form.update_risk_matrix"
                :class="[
                  'relative inline-flex h-6 w-11 flex-shrink-0 rounded-full border-2 transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
                  form.update_risk_matrix ? 'bg-blue-900 border-blue-900' : 'bg-gray-200 border-gray-300'
                ]"
              >
                <span class="sr-only">{{ $t('gestlab.general.labels.occurrences.update_risk_matrix') }}</span>
                <span :class="[
                  'pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                  form.update_risk_matrix ? 'translate-x-5' : 'translate-x-0'
                ]" />
              </button>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>

    <confirm-dialog size="sm:max-w-2xl" alignment="sm:items-start" @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="submit" v-if="showDeleteConfirmation" :title="$t('gestlab.actions.confirmation_dialog_title.default')" :description="$t('gestlab.actions.confirmation_dialog_description.default')" confirm="Sim" cancel="Não">
    <div class="mt-4">
      <div class="font-semibold inline-flex px-2 py-1 leading-4 text-xs rounded-full text-white bg-blue-900 sm:text-xs mb-2"><p class="text-xs">{{ $t('gestlab.general.labels.summary') }}</p></div>
      <div>
        <div class="px-4 sm:px-0 rounded-full text-white bg-blue-900">
          <!-- <h3 class="text-base font-semibold leading-7 text-gray-900">Resumo</h3>
          <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-500">Personal details and application.</p> -->
        </div>

        <div class="mt-6 border-t border-gray-100">
          <dl class="divide-y divide-gray-100">

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.date_reported") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.date_reported }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.category_id") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.category_id?.label }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.origin_id") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.origin_id?.label }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.issue_description") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.issue_description }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.department_id") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.department_id?.label }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.notification_date") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.notification_date }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="form.category_id?.value === 3">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.client_process_open_notification_date") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.client_process_open_notification_date }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.analysis") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.analysis }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.has_risk_correction_budget") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.has_risk_correction_budget ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="!form.has_risk_correction_budget">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.reason_for_no_risk_correction_budget") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.reason_for_no_risk_correction_budget }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.has_non_conformity_terms") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.has_non_conformity_terms ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.corrective_action") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.corrective_action }}
              </dd>
            </div>

            <!-- <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.date_resolved") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.date_resolved }}
              </dd>
            </div> -->

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.effect_corrective_actions") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.effect_corrective_actions }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.cause_corrective_actions") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.cause_corrective_actions }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.implementation_date") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.implementation_date }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.user_id") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.user_id?.label }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.responsible_name") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.responsible_name }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.update_risk_matrix") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.update_risk_matrix ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="form.category_id?.value === 3">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.client_process_close_notification_date") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.client_process_close_notification_date }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="form.category_id?.value === 3">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.client_acceptance") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.client_acceptance ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.client_acceptance_comments") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.client_acceptance_comments }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.date_closed") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.date_closed }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.was_effective") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.was_effective ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}
              </dd>
            </div>

            <!-- <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.occurrences.obs") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.obs }}
              </dd>
            </div> -->
            
          </dl>
        </div>

      </div>

    </div>
  </confirm-dialog>
</template>

<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import {
  ExclamationTriangleIcon,
  ArrowLeftIcon,
  InformationCircleIcon,
  HashtagIcon,
  CalendarIcon,
  DocumentTextIcon,
  FolderIcon,
  MapPinIcon,
  BuildingOfficeIcon,
  UserIcon,
  MagnifyingGlassIcon,
  WrenchIcon,
  PlusCircleIcon,
  ArrowPathIcon,
  ClockIcon,
  UsersIcon,
  CurrencyDollarIcon
} from '@heroicons/vue/24/outline'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import datePickerEnhanced from '@/Components/date-picker-enhanced.vue'
import confirmDialog from "@/Components/confirm-dialog.vue";


const props = defineProps({
  record: Object
})

const showDeleteConfirmation = ref(false);

const form = useForm({
  id: props.record.data?.id,
  date_reported: props.record.data?.date_reported,
  issue_description: props.record.data?.issue_description,
  corrective_action: props.record.data?.corrective_action,
  date_resolved: props.record.data?.date_resolved,
  notification_date: props.record.data?.notification_date,
  client_process_open_notification_date: props.record.data?.client_process_open_notification_date,
  analysis: props.record.data?.analysis,
  has_risk_correction_budget: props.record.data?.has_risk_correction_budget,
  reason_for_no_risk_correction_budget: props.record.data?.reason_for_no_risk_correction_budget,
  has_non_conformity_terms: props.record.data?.has_non_conformity_terms,
  effect_corrective_actions: props.record.data?.effect_corrective_actions,
  cause_corrective_actions: props.record.data?.cause_corrective_actions,
  implementation_date: props.record.data?.implementation_date,
  update_risk_matrix: props.record.data?.update_risk_matrix,
  client_process_close_notification_date: props.record.data?.client_process_close_notification_date,
  client_acceptance: props.record.data?.client_acceptance,
  client_acceptance_comments: props.record.data?.client_acceptance_comments,
  date_closed: props.record.data?.date_closed,
  obs: props.record.data?.obs,
  was_effective: props.record.data?.was_effective,
  responsible_name: props.record.data?.responsible_name,
  status_id: {
    value: props.record.data?.status_id,
    label: props.record.data?.status
  },
  department_id: {
    value: props.record.data?.department_id,
    label: props.record.data?.department
  },
  user_id: {
    value: props.record.data?.user_id,
    label: props.record.data?.user
  },
  origin_id: {
    value: props.record.data?.origin_id,
    label: props.record.data?.origin
  },
  category_id: {
    value: props.record.data?.category_id,
    label: props.record.data?.category
  },
})

let submit = () => {
   form.put(route("occurrences.update", { occurrence: form.id }), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        form.reset();
      },
      onError: () => {
            showDeleteConfirmation.value = false;
      },
    });

};

function loadUsers(query, setOptions) {
  fetch("/users/getUser?q=" + query)
    .then((response) => response.json())
    .then((results) => {
      setOptions(
        results.map((result) => {
          return {
            value: result.id,
            label: result.name,
          };
        }),
      );
    });
}

function loadStatuses(query, setOptions) {
  fetch("/occurrencestatuses/getOccurrenceStatus?q=" + query)
    .then((response) => response.json())
    .then((results) => {
      setOptions(
        results.map((result) => {
          return {
            value: result.id,
            label: result.name,
          };
        }),
      );
    });
}

function loadOrigins(query, setOptions) {
  fetch("/occurrenceorigins/getOccurrenceOrigin?q=" + query)
  .then((response) => response.json())
  .then((results) => {
    setOptions(
      results.map((result) => {
        return {
          value: result.id,
          label: result.name,
        };
      }),
    );
  });
}

function loadDepartments(query, setOptions) {
  fetch("/departments/getDepartment?q=" + query)
  .then((response) => response.json())
  .then((results) => {
    setOptions(
      results.map((result) => {
        return {
          value: result.id,
          label: result.name,
        };
      }),
    );
  });
}

function loadCategories(query, setOptions) {
  fetch("/occurrencecategories/getOccurrenceCategory?q=" + query)
    .then((response) => response.json())
    .then((results) => {
      setOptions(
        results.map((result) => {
          return {
            value: result.id,
            label: result.name,
          };
        }),
      );
    });
}
</script>