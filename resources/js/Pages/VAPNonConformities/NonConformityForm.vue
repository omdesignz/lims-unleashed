<template>
  <div class="nc-form-surface space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <!-- <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ExclamationTriangleIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_non_conformities.title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.vap_non_conformities.description') }}
            <span class="font-semibold text-blue-900">
              #{{ nonConformity?.nc_number || 'Nova' }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span 
            :class="[
              'inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset',
              statusClasses[form.status || 'opened']
            ]"
          >
            {{ $t(`gestlab.general.labels.vap_non_conformities.status.${form.status || 'opened'}`) }}
          </span>
        </div>
      </div>
    </div> -->

    <!-- MAIN CONTENT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        
        <!-- BASIC INFORMATION SECTION -->
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-primary-700 to-primary-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <InformationCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_non_conformities.basic_info') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <!-- GRID FORM LAYOUT -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- NC Number -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <HashtagIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_non_conformities.nc_number') }}
                  <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.nc_number"
                  :class="[
                    'block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900/50',
                    form.errors.nc_number 
                      ? 'border-red-300' 
                      : 'border-gray-300'
                  ]"
                  :placeholder="$t('gestlab.general.labels.vap_non_conformities.nc_number_placeholder')"
                />
                <p v-if="form.errors.nc_number" class="text-xs text-red-600">
                  {{ form.errors.nc_number }}
                </p>
              </div>

              <!-- Title -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <TagIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_non_conformities.title') }}
                  <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.title"
                  :class="[
                    'block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900/50',
                    form.errors.title 
                      ? 'border-red-300' 
                      : 'border-gray-300'
                  ]"
                  :placeholder="$t('gestlab.general.labels.vap_non_conformities.title_placeholder')"
                />
                <p v-if="form.errors.title" class="text-xs text-red-600">
                  {{ form.errors.title }}
                </p>
              </div>

              <!-- Severity -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <ExclamationTriangleIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_non_conformities.severity.title') }}
                  <span class="text-red-500">*</span>
                </label>
                <ComboboxEnhanced
                  v-model="selectedSeverity"
                  :options="severityOptions"
                  :has-error="Boolean(form.errors.severity)"
                  :placeholder="$t('gestlab.general.labels.vap_non_conformities.severity.title')"
                />
                <p v-if="form.errors.severity" class="text-xs text-red-600">
                  {{ form.errors.severity }}
                </p>
              </div>

              <!-- Category -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <FolderIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_non_conformities.category') }}
                  <span class="text-red-500">*</span>
                </label>
                <ComboboxEnhanced
                  v-model="selectedCategory"
                  :options="categoryOptions"
                  :has-error="Boolean(form.errors.category)"
                  :placeholder="$t('gestlab.general.labels.vap_non_conformities.category')"
                />
                <p v-if="form.errors.category" class="text-xs text-red-600">
                  {{ form.errors.category }}
                </p>
              </div>

              <!-- Description -->
              <div class="md:col-span-2">
                <BaseTextarea
                  v-model="form.description"
                  :label="$t('gestlab.general.labels.vap_non_conformities.description')"
                  :required="true"
                  :rows="4"
                  :has-error="Boolean(form.errors.description)"
                  :error="form.errors.description"
                  :placeholder="$t('gestlab.general.labels.vap_non_conformities.description_placeholder')"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- RELATED ENTITIES SECTION -->
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-primary-700 to-primary-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <LinkIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_non_conformities.related_entities') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Sample ID -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <BeakerIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_non_conformities.sample_id') }}
                </label>
                <input
                  v-model="form.sample_id"
                  :class="[
                    'block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900/50',
                    form.errors.sample_id 
                      ? 'border-red-300' 
                      : 'border-gray-300'
                  ]"
                  :placeholder="$t('gestlab.general.labels.vap_non_conformities.sample_id_placeholder')"
                />
                <p v-if="form.errors.sample_id" class="text-xs text-red-600">
                  {{ form.errors.sample_id }}
                </p>
              </div>

              <!-- Test Method -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <ClipboardDocumentCheckIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_non_conformities.test_method') }}
                </label>
                <input
                  v-model="form.test_method"
                  :class="[
                    'block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900/50',
                    form.errors.test_method 
                      ? 'border-red-300' 
                      : 'border-gray-300'
                  ]"
                  :placeholder="$t('gestlab.general.labels.vap_non_conformities.test_method_placeholder')"
                />
                <p v-if="form.errors.test_method" class="text-xs text-red-600">
                  {{ form.errors.test_method }}
                </p>
              </div>

              <!-- Equipment ID -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <CpuChipIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_non_conformities.equipment_id') }}
                </label>
                <input
                  v-model="form.equipment_id"
                  :class="[
                    'block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900/50',
                    form.errors.equipment_id 
                      ? 'border-red-300' 
                      : 'border-gray-300'
                  ]"
                  :placeholder="$t('gestlab.general.labels.vap_non_conformities.equipment_id_placeholder')"
                />
                <p v-if="form.errors.equipment_id" class="text-xs text-red-600">
                  {{ form.errors.equipment_id }}
                </p>
              </div>

              <!-- Batch Number -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <QueueListIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_non_conformities.batch_number') }}
                </label>
                <input
                  v-model="form.batch_number"
                  :class="[
                    'block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900/50',
                    form.errors.batch_number 
                      ? 'border-red-300' 
                      : 'border-gray-300'
                  ]"
                  :placeholder="$t('gestlab.general.labels.vap_non_conformities.batch_number_placeholder')"
                />
                <p v-if="form.errors.batch_number" class="text-xs text-red-600">
                  {{ form.errors.batch_number }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- CORRECTIVE ACTIONS SECTION -->
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
            <div class="flex items-center justify-between">
              <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-950 dark:text-white">
                <WrenchScrewdriverIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                {{ $t('gestlab.general.labels.vap_non_conformities.corrective_actions') }}
                <span class="text-sm font-normal text-gray-500 ml-2">
                  ({{ actions.length }} {{ $t('gestlab.general.labels.vap_non_conformities.general.items') }})
                </span>
              </h2>
              <button 
                @click="addAction"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
              >
                <PlusCircleIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.vap_non_conformities.buttons.add_action') }}
              </button>
            </div>
          </div>

          <!-- EMPTY STATE -->
          <div v-if="actions.length === 0" class="p-12 text-center">
            <WrenchScrewdriverIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              {{ $t('gestlab.general.labels.vap_non_conformities.no_actions_title') }}
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              {{ $t('gestlab.general.labels.vap_non_conformities.no_actions_description') }}
            </p>
            <button 
              @click="addAction"
              type="button"
              class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
            >
              <PlusCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_non_conformities.buttons.add_first_action') }}
            </button>
          </div>

          <!-- ACTIONS GRID -->
          <div v-else class="grid grid-cols-1 gap-6 p-6">
            <!-- ACTION CARD TEMPLATE -->
            <div 
              v-for="(action, index) in actions"
              :key="index"
              class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all duration-200 hover:border-primary-600 dark:border-slate-800 dark:bg-slate-950/60 dark:hover:border-primary-400"
              v-motion
              :initial="{ opacity: 0, y: 20 }"
              :enter="{ opacity: 1, y: 0 }"
              :delay="index * 50"
            >
              <!-- ITEM HEADER -->
              <div class="border-b border-slate-200 bg-gradient-to-r from-primary-50 to-white px-4 py-3 dark:border-slate-800 dark:from-primary-500/10 dark:to-slate-950/70">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-900 text-white font-semibold">
                      {{ index + 1 }}
                    </div>
                    <div>
                      <h3 class="text-sm font-semibold text-gray-900">
                        {{ $t('gestlab.general.labels.vap_non_conformities.action') }} #{{ index + 1 }}
                      </h3>
                      <p class="text-xs text-gray-500">
                        {{ action.due_at ? $t('gestlab.general.labels.vap_non_conformities.due') + ': ' + formatDate(action.due_at) : $t('gestlab.general.labels.vap_non_conformities.no_due_date') }}
                      </p>
                    </div>
                  </div>
                  <button 
                    @click="removeAction(index)"
                    type="button"
                    class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-600 transition-all duration-200 p-1 rounded-full hover:bg-red-50"
                    :title="$t('gestlab.general.labels.vap_non_conformities.buttons.remove_action')"
                  >
                    <TrashIcon class="h-5 w-5" />
                  </button>
                </div>
              </div>
              
              <!-- ITEM CONTENT -->
              <div class="p-4 space-y-4">
                <!-- Correction -->
                <div>
                  <BaseTextarea
                    v-model="action.correction"
                    :label="$t('gestlab.general.labels.vap_non_conformities.correction')"
                    :rows="2"
                    :placeholder="$t('gestlab.general.labels.vap_non_conformities.correction_placeholder')"
                  />
                </div>

                <!-- Corrective Action -->
                <div>
                  <BaseTextarea
                    v-model="action.corrective_action"
                    :label="$t('gestlab.general.labels.vap_non_conformities.corrective_action')"
                    :rows="2"
                    :placeholder="$t('gestlab.general.labels.vap_non_conformities.corrective_action_placeholder')"
                  />
                </div>

                <!-- Due Date -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.vap_non_conformities.due_date') }}
                  </label>
                  <DatePickerEnhanced
                    v-model="action.due_at"
                    :is-dark="isDarkMode"
                    :show-clear="true"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6">
        <!-- TIMELINE & ASSIGNMENT CARD -->
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <h3 class="mb-4 text-lg font-semibold text-slate-950 dark:text-white">
            {{ $t('gestlab.general.labels.vap_non_conformities.timeline_assignment') }}
          </h3>
          <div class="space-y-6">
            <!-- Reported By -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.vap_non_conformities.reported_by') }}
              </label>
              <input
                v-model="form.reported_by"
                :class="[
                  'block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900/50',
                  form.errors.reported_by 
                    ? 'border-red-300' 
                    : 'border-gray-300'
                ]"
                :placeholder="$t('gestlab.general.labels.vap_non_conformities.reported_by_placeholder')"
              />
              <p v-if="form.errors.reported_by" class="text-xs text-red-600">
                {{ form.errors.reported_by }}
              </p>
            </div>

            <!-- Assigned To -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.vap_non_conformities.assigned_to') }}
              </label>
              <input
                v-model="form.assigned_to"
                :class="[
                  'block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900/50',
                  form.errors.assigned_to 
                    ? 'border-red-300' 
                    : 'border-gray-300'
                ]"
                :placeholder="$t('gestlab.general.labels.vap_non_conformities.assigned_to_placeholder')"
              />
              <p v-if="form.errors.assigned_to" class="text-xs text-red-600">
                {{ form.errors.assigned_to }}
              </p>
            </div>

            <!-- Reported At -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.vap_non_conformities.reported_at') }}
              </label>
              <DatePickerEnhanced
                v-model="form.reported_at"
                :is-dark="isDarkMode"
                :has-error="Boolean(form.errors.reported_at)"
                :error-message="form.errors.reported_at"
                :show-clear="false"
              />
              <p v-if="form.errors.reported_at" class="text-xs text-red-600">
                {{ form.errors.reported_at }}
              </p>
            </div>

            <!-- Due Date -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.vap_non_conformities.due_date') }}
              </label>
              <DatePickerEnhanced
                v-model="form.due_date"
                :is-dark="isDarkMode"
                :has-error="Boolean(form.errors.due_date)"
                :error-message="form.errors.due_date"
                :show-clear="true"
              />
              <p v-if="form.errors.due_date" class="text-xs text-red-600">
                {{ form.errors.due_date }}
              </p>
            </div>

            <!-- Status -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.vap_non_conformities.status.title') }}
              </label>
              <ComboboxEnhanced
                v-model="selectedStatus"
                :options="statusOptions"
                :has-error="Boolean(form.errors.status)"
                :placeholder="$t('gestlab.general.labels.vap_non_conformities.status.title')"
              />
              <p v-if="form.errors.status" class="text-xs text-red-600">
                {{ form.errors.status }}
              </p>
            </div>
          </div>
        </div>

        <!-- ACTIONS CARD -->
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <h3 class="mb-4 text-lg font-semibold text-slate-950 dark:text-white">
            {{ $t('gestlab.general.labels.vap_non_conformities.actions.title') }}
          </h3>
          <div class="space-y-4">
            <button 
              @click="submit"
              :disabled="form.processing"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                form.processing
                  ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                  : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
              ]"
            >
              <CheckCircleIcon class="h-5 w-5" />
              {{ form.processing ? $t('gestlab.general.labels.vap_non_conformities.buttons.processing') : $t('gestlab.general.labels.vap_non_conformities.buttons.save') }}
            </button>

            <button 
              @click="reset"
              type="button"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
            >
              <ArrowPathIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_non_conformities.buttons.reset') }}
            </button>

            <!-- QUICK STATS -->
            <div class="border-t border-slate-200 pt-4 dark:border-slate-800">
              <h4 class="mb-2 text-sm font-medium text-slate-950 dark:text-white">
                {{ $t('gestlab.general.labels.vap_non_conformities.stats.title') }}
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.vap_non_conformities.severity.title') }}</span>
                  <span :class="severityClasses[form.severity || 'medium']">
                    {{ $t(`gestlab.general.labels.vap_non_conformities.severity_${form.severity || 'medium'}`) }}
                  </span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.vap_non_conformities.actions_count') }}</span>
                  <span class="font-semibold text-blue-900">{{ actions.length }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.vap_non_conformities.days_open') }}</span>
                  <span class="font-semibold text-blue-900">
                    {{ calculateDaysOpen() }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ATTACHMENTS & NOTES -->
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold text-slate-950 dark:text-white">
            <PaperClipIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
            {{ $t('gestlab.general.labels.vap_non_conformities.attachments_notes') }}
          </h3>
          <div class="space-y-4">
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-950/50">
              <label class="flex cursor-pointer flex-col items-center justify-center gap-3 text-center">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary-600 text-white shadow-sm shadow-primary-600/20">
                  <PaperClipIcon class="h-5 w-5" />
                </span>
                <span>
                  <span class="block text-sm font-semibold text-slate-900 dark:text-slate-100">
                    {{ $t('gestlab.general.labels.vap_non_conformities.attachments_notes') }}
                  </span>
                  <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">
                    PDF, imagens ou documentos de evidência até 10MB.
                  </span>
                </span>
                <input
                  type="file"
                  multiple
                  class="sr-only"
                  @change="selectAttachmentFiles"
                />
              </label>

              <div v-if="selectedAttachmentFiles.length" class="mt-4 space-y-2">
                <div
                  v-for="(file, index) in selectedAttachmentFiles"
                  :key="`${file.name}-${file.size}-${index}`"
                  class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-900"
                >
                  <span class="truncate text-slate-700 dark:text-slate-200">{{ file.name }}</span>
                  <button
                    type="button"
                    class="rounded-lg p-1 text-slate-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10 dark:hover:text-red-300"
                    @click="removeAttachmentFile(index)"
                  >
                    <TrashIcon class="h-4 w-4" />
                  </button>
                </div>
              </div>

              <p v-if="form.errors.attachment_files" class="mt-2 text-xs font-medium text-red-600 dark:text-red-400">
                {{ form.errors.attachment_files }}
              </p>
            </div>

            <div v-if="existingAttachments.length" class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950/40">
              <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Anexos já registados</p>
              <div class="mt-3 space-y-2">
                <a
                  v-for="attachment in existingAttachments"
                  :key="attachment.id"
                  :href="attachment.url"
                  target="_blank"
                  class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-700 transition hover:bg-primary-50 hover:text-primary-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-primary-500/10 dark:hover:text-primary-200"
                >
                  <span class="truncate">{{ attachment.name || attachment.file_name }}</span>
                  <span class="text-xs text-slate-500 dark:text-slate-400">{{ attachment.human_readable_size }}</span>
                </a>
              </div>
            </div>

            <!-- Root Cause -->
            <div>
              <BaseTextarea
                v-model="form.root_cause"
                :label="$t('gestlab.general.labels.vap_non_conformities.root_cause')"
                :rows="3"
                :placeholder="$t('gestlab.general.labels.vap_non_conformities.root_cause_placeholder')"
              />
            </div>

            <!-- Preventive Actions -->
            <div>
              <BaseTextarea
                v-model="form.preventive_actions"
                :label="$t('gestlab.general.labels.vap_non_conformities.preventive_actions')"
                :rows="3"
                :placeholder="$t('gestlab.general.labels.vap_non_conformities.preventive_actions_placeholder')"
              />
            </div>

            <!-- Comments -->
            <div>
              <BaseTextarea
                v-model="form.comments"
                :label="$t('gestlab.general.labels.vap_non_conformities.comments')"
                :rows="3"
                :placeholder="$t('gestlab.general.labels.vap_non_conformities.comments_placeholder')"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- FOOTER ACTIONS -->
    <div class="flex items-center justify-between pt-6">
      <div class="text-sm text-gray-500">
        {{ $t('gestlab.general.labels.vap_non_conformities.last_updated') }}: {{ nonConformity?.updated_at ? formatDate(nonConformity.updated_at) : $t('gestlab.general.labels.vap_non_conformities.never') }}
      </div>
      <div class="flex items-center gap-4">
        <button 
          @click="cancel"
          type="button"
          class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
        >
          {{ $t('gestlab.general.labels.vap_non_conformities.buttons.cancel') }}
        </button>
        <button 
          @click="submit"
          :disabled="form.processing"
          :class="[
            'rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
            form.processing
              ? 'bg-gray-400 cursor-not-allowed'
              : 'bg-gradient-to-r from-blue-900 to-blue-800 hover:from-blue-800 hover:to-blue-700'
          ]"
        >
          {{ $t('gestlab.general.labels.vap_non_conformities.buttons.save_changes') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import BaseTextarea from '@/Components/base/BaseTextarea.vue'
import ComboboxEnhanced from '@/Components/combobox-enhanced.vue'
import DatePickerEnhanced from '@/Components/date-picker-enhanced.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import {
  ExclamationTriangleIcon,
  InformationCircleIcon,
  HashtagIcon,
  TagIcon,
  FolderIcon,
  LinkIcon,
  BeakerIcon,
  ClipboardDocumentCheckIcon,
  CpuChipIcon,
  QueueListIcon,
  WrenchScrewdriverIcon,
  CheckCircleIcon,
  ArrowPathIcon,
  PaperClipIcon,
  PlusCircleIcon,
  TrashIcon
} from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  nonConformity: {
    type: Object,
    default: null
  },
  labs: {
    type: Array,
    default: () => []
  },
  departments: {
    type: Array,
    default: () => []
  },
  isEditing: {
    type: Boolean,
    default: false
  }
})

// Form
const form = useForm({
  lab_id: props.nonConformity?.lab_id || '',
  department_id: props.nonConformity?.department_id || '',
  nc_number: props.nonConformity?.nc_number || generateNcNumber(),
  title: props.nonConformity?.title || '',
  description: props.nonConformity?.description || '',
  status: props.nonConformity?.status || 'opened',
  severity: props.nonConformity?.severity || 'medium',
  category: props.nonConformity?.category || 'quality',
  sample_id: props.nonConformity?.sample_id || '',
  test_method: props.nonConformity?.test_method || '',
  equipment_id: props.nonConformity?.equipment_id || '',
  batch_number: props.nonConformity?.batch_number || '',
  reported_by: props.nonConformity?.reported_by || '',
  reported_by_id: props.nonConformity?.reported_by_id || '',
  assigned_to: props.nonConformity?.assigned_to || '',
  assigned_to_id: props.nonConformity?.assigned_to_id || '',
  reported_at: formatDateForInput(props.nonConformity?.reported_at) || formatDateForInput(new Date()),
  due_date: formatDateForInput(props.nonConformity?.due_date) || '',
  occurrence_area: props.nonConformity?.occurrence_area || '',
  root_cause: props.nonConformity?.root_cause || '',
  corrective_actions: props.nonConformity?.corrective_actions || '',
  preventive_actions: props.nonConformity?.preventive_actions || '',
  comments: props.nonConformity?.comments || '',
  attachments: props.nonConformity?.attachments || [],
  attachment_files: [],
  actions: props.nonConformity?.actions || []
})

// Actions
const actions = ref(props.nonConformity?.actions || [])
const selectedAttachmentFiles = ref([])
const existingAttachments = computed(() => props.nonConformity?.media_attachments || [])

const isDarkMode = computed(() => {
  if (typeof document === 'undefined') return false

  return document.documentElement.classList.contains('dark')
})

const severityOptions = computed(() => [
  { value: 'low', label: transLabel('severity_low') },
  { value: 'medium', label: transLabel('severity_medium') },
  { value: 'high', label: transLabel('severity_high') },
  { value: 'critical', label: transLabel('severity_critical') },
])

const categoryOptions = computed(() => [
  { value: 'quality', label: transLabel('category_quality') },
  { value: 'safety', label: transLabel('category_safety') },
  { value: 'environmental', label: transLabel('category_environmental') },
  { value: 'regulatory', label: transLabel('category_regulatory') },
  { value: 'other', label: transLabel('category_other') },
])

const statusOptions = computed(() => [
  { value: 'opened', label: transLabel('status_opened') },
  { value: 'in_progress', label: transLabel('status_in_progress') },
  { value: 'resolved', label: transLabel('status_resolved') },
  { value: 'closed', label: transLabel('status_closed') },
])

const selectedSeverity = optionProxy('severity', severityOptions)
const selectedCategory = optionProxy('category', categoryOptions)
const selectedStatus = optionProxy('status', statusOptions)

function transLabel(key) {
  return trans(`gestlab.general.labels.vap_non_conformities.${key}`)
}

function optionProxy(field, options) {
  return computed({
    get() {
      return options.value.find(option => option.value === form[field]) ?? null
    },
    set(option) {
      form[field] = option?.value ?? ''
    }
  })
}

// Generate NC Number
function generateNcNumber() {
  const prefix = 'NC'
  const date = new Date()
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0')
  return `${prefix}-${year}${month}-${random}`
}

// Add new action
function addAction() {
  actions.value.push({
    correction: '',
    corrective_action: '',
    due_at: ''
  })
}

// Remove action
function removeAction(index) {
  actions.value.splice(index, 1)
}

function selectAttachmentFiles(event) {
  selectedAttachmentFiles.value = Array.from(event.target.files || [])
  form.attachment_files = selectedAttachmentFiles.value
}

function removeAttachmentFile(index) {
  selectedAttachmentFiles.value.splice(index, 1)
  form.attachment_files = selectedAttachmentFiles.value
}

// Submit form
function submit() {
  form.actions = actions.value
  form.attachment_files = selectedAttachmentFiles.value

  if (props.isEditing) {
    form
      .transform((data) => ({
        ...data,
        _method: 'put',
      }))
      .post(route('vap_non_conformities.update', props.nonConformity.id), {
        forceFormData: true,
        onFinish: () => form.transform((data) => data),
      })
  } else {
    form.post(route('vap_non_conformities.store'), {
      forceFormData: true,
    })
  }
}

// Reset form
function reset() {
  form.reset()
  actions.value = []
  selectedAttachmentFiles.value = []
}

// Cancel
function cancel() {
  window.history.back()
}

// Format date for display
function formatDate(dateString) {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString() + ' ' + date.toLocaleTimeString()
}

// Format date for input
function formatDateForInput(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toISOString().slice(0, 16)
}

// Calculate days open
function calculateDaysOpen() {
  if (!form.reported_at) return 0
  const reported = new Date(form.reported_at)
  const now = new Date()
  const diff = now - reported
  return Math.floor(diff / (1000 * 60 * 60 * 24))
}

// Status classes
const statusClasses = {
  opened: 'bg-blue-50 text-blue-700 ring-blue-700/10 dark:bg-blue-500/10 dark:text-blue-200 dark:ring-blue-400/20',
  in_progress: 'bg-yellow-50 text-yellow-700 ring-yellow-700/10 dark:bg-yellow-500/10 dark:text-yellow-200 dark:ring-yellow-400/20',
  resolved: 'bg-green-50 text-green-700 ring-green-700/10 dark:bg-green-500/10 dark:text-green-200 dark:ring-green-400/20',
  closed: 'bg-slate-50 text-slate-700 ring-slate-700/10 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-600'
}

// Severity classes
const severityClasses = {
  low: 'text-green-600 dark:text-green-300',
  medium: 'text-yellow-600 dark:text-yellow-300',
  high: 'text-orange-600 dark:text-orange-300',
  critical: 'text-red-600 dark:text-red-300'
}
</script>

<style scoped>
.nc-form-surface :deep(.bg-white) {
  border-radius: 1.5rem;
}

:global(.dark) .nc-form-surface :deep(.bg-white) {
  background-color: rgb(15 23 42 / 0.8);
}

:global(.dark) .nc-form-surface :deep(.bg-gray-50),
:global(.dark) .nc-form-surface :deep(.from-blue-50) {
  background-color: rgb(2 6 23 / 0.65);
}

:global(.dark) .nc-form-surface :deep(.border-gray-200),
:global(.dark) .nc-form-surface :deep(.border-gray-300) {
  border-color: rgb(51 65 85);
}

:global(.dark) .nc-form-surface :deep(.text-gray-900) {
  color: rgb(248 250 252);
}

:global(.dark) .nc-form-surface :deep(.text-gray-700),
:global(.dark) .nc-form-surface :deep(.text-gray-600) {
  color: rgb(203 213 225);
}

:global(.dark) .nc-form-surface :deep(.text-gray-500),
:global(.dark) .nc-form-surface :deep(.text-gray-400) {
  color: rgb(148 163 184);
}

:global(.dark) .nc-form-surface :deep(input),
:global(.dark) .nc-form-surface :deep(select),
:global(.dark) .nc-form-surface :deep(textarea) {
  border-color: rgb(51 65 85);
  background-color: rgb(2 6 23);
  color: rgb(241 245 249);
}

:global(.dark) .nc-form-surface :deep(input::placeholder),
:global(.dark) .nc-form-surface :deep(textarea::placeholder) {
  color: rgb(100 116 139);
}

:global(.dark) .nc-form-surface :deep(.bg-gradient-to-r.from-blue-900),
.nc-form-surface :deep(.bg-gradient-to-r.from-blue-900) {
  background-image: linear-gradient(90deg, rgb(var(--color-primary-700, 14 116 144)), rgb(var(--color-primary-600, 8 145 178)));
}
</style>
