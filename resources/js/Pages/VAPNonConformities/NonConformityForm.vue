<template>
  <div class="space-y-8">
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
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
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
                <select
                  v-model="form.severity"
                  :class="[
                    'block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900/50',
                    form.errors.severity 
                      ? 'border-red-300' 
                      : 'border-gray-300'
                  ]"
                >
                  <option value="low">{{ $t('gestlab.general.labels.vap_non_conformities.severity_low') }}</option>
                  <option value="medium">{{ $t('gestlab.general.labels.vap_non_conformities.severity_medium') }}</option>
                  <option value="high">{{ $t('gestlab.general.labels.vap_non_conformities.severity_high') }}</option>
                  <option value="critical">{{ $t('gestlab.general.labels.vap_non_conformities.severity_critical') }}</option>
                </select>
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
                <select
                  v-model="form.category"
                  :class="[
                    'block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900/50',
                    form.errors.category 
                      ? 'border-red-300' 
                      : 'border-gray-300'
                  ]"
                >
                  <option value="quality">{{ $t('gestlab.general.labels.vap_non_conformities.category_quality') }}</option>
                  <option value="safety">{{ $t('gestlab.general.labels.vap_non_conformities.category_safety') }}</option>
                  <option value="environmental">{{ $t('gestlab.general.labels.vap_non_conformities.category_environmental') }}</option>
                  <option value="regulatory">{{ $t('gestlab.general.labels.vap_non_conformities.category_regulatory') }}</option>
                  <option value="other">{{ $t('gestlab.general.labels.vap_non_conformities.category_other') }}</option>
                </select>
                <p v-if="form.errors.category" class="text-xs text-red-600">
                  {{ form.errors.category }}
                </p>
              </div>

              <!-- Description -->
              <div class="md:col-span-2 space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <DocumentTextIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_non_conformities.description') }}
                  <span class="text-red-500">*</span>
                </label>
                <textarea
                  v-model="form.description"
                  rows="4"
                  :class="[
                    'block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900/50',
                    form.errors.description 
                      ? 'border-red-300' 
                      : 'border-gray-300'
                  ]"
                  :placeholder="$t('gestlab.general.labels.vap_non_conformities.description_placeholder')"
                />
                <p v-if="form.errors.description" class="text-xs text-red-600">
                  {{ form.errors.description }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- RELATED ENTITIES SECTION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
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
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <WrenchScrewdriverIcon class="h-5 w-5 text-blue-900" />
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
              class="group relative bg-white rounded-lg border border-gray-200 hover:border-blue-900 transition-all duration-200 overflow-hidden shadow-sm"
              v-motion
              :initial="{ opacity: 0, y: 20 }"
              :enter="{ opacity: 1, y: 0 }"
              :delay="index * 50"
            >
              <!-- ITEM HEADER -->
              <div class="bg-gradient-to-r from-blue-50 to-white px-4 py-3 border-b border-gray-200">
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
                        {{ action.due_at ? `Due: ${formatDate(action.due_at)}` : 'No due date' }}
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
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.vap_non_conformities.correction') }}
                  </label>
                  <textarea
                    v-model="action.correction"
                    rows="2"
                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900/50"
                    :placeholder="$t('gestlab.general.labels.vap_non_conformities.correction_placeholder')"
                  />
                </div>

                <!-- Corrective Action -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.vap_non_conformities.corrective_action') }}
                  </label>
                  <textarea
                    v-model="action.corrective_action"
                    rows="2"
                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900/50"
                    :placeholder="$t('gestlab.general.labels.vap_non_conformities.corrective_action_placeholder')"
                  />
                </div>

                <!-- Due Date -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.vap_non_conformities.due_date') }}
                  </label>
                  <input
                    v-model="action.due_at"
                    type="datetime-local"
                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900/50"
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
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
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
              <input
                v-model="form.reported_at"
                type="datetime-local"
                :class="[
                  'block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900/50',
                  form.errors.reported_at 
                    ? 'border-red-300' 
                    : 'border-gray-300'
                ]"
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
              <input
                v-model="form.due_date"
                type="datetime-local"
                :class="[
                  'block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900/50',
                  form.errors.due_date 
                    ? 'border-red-300' 
                    : 'border-gray-300'
                ]"
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
              <select
                v-model="form.status"
                :class="[
                  'block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900/50',
                  form.errors.status 
                    ? 'border-red-300' 
                    : 'border-gray-300'
                ]"
              >
                <option value="opened">{{ $t('gestlab.general.labels.vap_non_conformities.status_opened') }}</option>
                <option value="in_progress">{{ $t('gestlab.general.labels.vap_non_conformities.status_in_progress') }}</option>
                <option value="resolved">{{ $t('gestlab.general.labels.vap_non_conformities.status_resolved') }}</option>
                <option value="closed">{{ $t('gestlab.general.labels.vap_non_conformities.status_closed') }}</option>
              </select>
              <p v-if="form.errors.status" class="text-xs text-red-600">
                {{ form.errors.status }}
              </p>
            </div>
          </div>
        </div>

        <!-- ACTIONS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
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
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
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
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <PaperClipIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_non_conformities.attachments_notes') }}
          </h3>
          <div class="space-y-4">
            <!-- Root Cause -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.vap_non_conformities.root_cause') }}
              </label>
              <textarea
                v-model="form.root_cause"
                rows="3"
                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900/50"
                :placeholder="$t('gestlab.general.labels.vap_non_conformities.root_cause_placeholder')"
              />
            </div>

            <!-- Preventive Actions -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.vap_non_conformities.preventive_actions') }}
              </label>
              <textarea
                v-model="form.preventive_actions"
                rows="3"
                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900/50"
                :placeholder="$t('gestlab.general.labels.vap_non_conformities.preventive_actions_placeholder')"
              />
            </div>

            <!-- Comments -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.vap_non_conformities.comments') }}
              </label>
              <textarea
                v-model="form.comments"
                rows="3"
                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900/50"
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
        Última Actualização: {{ nonConformity?.updated_at ? formatDate(nonConformity.updated_at) : 'Nunca' }}
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
import {
  ExclamationTriangleIcon,
  InformationCircleIcon,
  HashtagIcon,
  TagIcon,
  FolderIcon,
  DocumentTextIcon,
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
  actions: props.nonConformity?.actions || []
})

// Actions
const actions = ref(props.nonConformity?.actions || [])

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

// Submit form
function submit() {
  form.actions = actions.value
  if (props.nonConformity) {
    form.put(route('vap_non_conformities.update', props.nonConformity.id))
  } else {
    form.post(route('vap_non_conformities.store'))
  }
}

// Reset form
function reset() {
  form.reset()
  actions.value = []
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
  opened: 'bg-blue-50 text-blue-700 ring-blue-700/10',
  in_progress: 'bg-yellow-50 text-yellow-700 ring-yellow-700/10',
  resolved: 'bg-green-50 text-green-700 ring-green-700/10',
  closed: 'bg-gray-50 text-gray-700 ring-gray-700/10'
}

// Severity classes
const severityClasses = {
  low: 'text-green-600',
  medium: 'text-yellow-600',
  high: 'text-orange-600',
  critical: 'text-red-600'
}
</script>