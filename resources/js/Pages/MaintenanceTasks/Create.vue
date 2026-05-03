<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <WrenchScrewdriverIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.maintenance_tasks.create_title') }}
          </h1>
          <p class="mt-2 text-sm text-gray-600">
            {{ $t('gestlab.general.labels.maintenance_tasks.create_description') }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <Link 
            :href="route('maintenancetasks.index')"
            as="button"
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
              {{ $t('gestlab.general.labels.maintenance_tasks.basic_information') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- TASK NAME -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <TagIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.maintenance_tasks.name') }}
                  <span class="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  v-model="form.name"
                  :placeholder="$t('gestlab.general.labels.maintenance_tasks.name_placeholder')"
                  :class="[
                    'w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-transparent transition-colors duration-200',
                    form.errors.name ? 'border-red-500' : 'border-gray-300'
                  ]"
                />
                <p v-if="form.errors.name" class="text-xs text-red-600">
                  {{ form.errors.name }}
                </p>
              </div>

              <!-- CATEGORY -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <FolderIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.maintenance_tasks.category_id') }}
                  <span class="text-red-500">*</span>
                </label>
                <combobox-enhanced
                  v-model="form.category_id"
                  :hasError="form.errors.category_id"
                  :placeholder="$t('gestlab.general.labels.maintenance_tasks.category_placeholder')"
                  :load-options="loadCategories"
                />
                <p v-if="form.errors.category_id" class="text-xs text-red-600">
                  {{ form.errors.category_id }}
                </p>
              </div>

              <!-- EQUIPMENT -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <CogIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.maintenance_tasks.equipment_id') }}
                  <span class="text-red-500">*</span>
                </label>
                <combobox-enhanced
                  v-model="form.equipment_id"
                  :hasError="form.errors.equipment_id"
                  :placeholder="$t('gestlab.general.labels.maintenance_tasks.equipment_placeholder')"
                  :load-options="loadEquipment"
                />
                <p v-if="form.errors.equipment_id" class="text-xs text-red-600">
                  {{ form.errors.equipment_id }}
                </p>
              </div>

              <!-- DESCRIPTION -->
              <div class="md:col-span-2 space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <DocumentTextIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.maintenance_tasks.description') }}
                </label>
                <textarea
                  v-model="form.description"
                  rows="3"
                  :placeholder="$t('gestlab.general.labels.maintenance_tasks.description_placeholder')"
                  :class="[
                    'w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-transparent transition-colors duration-200',
                    form.errors.description ? 'border-red-500' : 'border-gray-300'
                  ]"
                />
                <p v-if="form.errors.description" class="text-xs text-red-600">
                  {{ form.errors.description }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- SCHEDULING CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <CalendarIcon class="h-5 w-5 text-blue-900" />
              {{ $t('gestlab.general.labels.maintenance_tasks.scheduling') }}
            </h2>
          </div>

          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- DUE DATE -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <CalendarDaysIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.maintenance_tasks.due_date') }}
                </label>
                <date-picker-enhanced
                  v-model.string="form.due_date"
                  locale="pt" 
                  color="blue" 
                  mode="date" 
                  :popover-placement="'bottom-start'"
                  :placeholder="$t('gestlab.general.labels.maintenance_tasks.select_date')"
                />
                <p v-if="form.errors.due_date" class="text-xs text-red-600">
                  {{ form.errors.due_date }}
                </p>
              </div>

              <!-- PERIODICITY -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <ArrowPathIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.maintenance_tasks.periodicity') }}
                </label>
                <div class="flex gap-2">
                  <input
                    type="number"
                    v-model="form.periodicity"
                    min="1"
                    placeholder="0"
                    :class="[
                      'w-20 rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-transparent transition-colors duration-200',
                      form.errors.periodicity ? 'border-red-500' : 'border-gray-300'
                    ]"
                  />

                  <combobox-enhanced
                    v-model="form.periodicity_unit"
                    :hasError="form.errors.periodicity_unit"
                    :placeholder="$t('gestlab.general.labels.maintenance_tasks.select_unit')"
                    :options="props.periodicityUnits.data"
                    />
                </div>
                <p v-if="form.errors.periodicity" class="text-xs text-red-600">
                  {{ form.errors.periodicity }}
                </p>
              </div>

              <!-- Previous and Next Date could be auto-calculated based on Periodicity, but for now they are manual inputs. -->

                <!-- PREVIOUS DATE -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                    <CalendarDaysIcon class="h-4 w-4" />
                    {{ $t('gestlab.general.labels.maintenance_tasks.previous_date') }}
                  </label>
                  <date-picker-enhanced
                    v-model.string="form.previous_date"
                    locale="pt" 
                    color="blue" 
                    mode="date" 
                    :popover-placement="'bottom-start'"
                    :placeholder="$t('gestlab.general.labels.maintenance_tasks.select_date')"
                  />
                  <p v-if="form.errors.previous_date" class="text-xs text-red-600">
                    {{ form.errors.previous_date }}
                  </p>
                </div>

                <!-- NEXT DATE -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                    <CalendarDaysIcon class="h-4 w-4" />
                    {{ $t('gestlab.general.labels.maintenance_tasks.next_date') }}
                  </label>
                  <date-picker-enhanced
                    v-model.string="form.next_date"
                    locale="pt"
                    color="blue"
                    mode="date"
                    :popover-placement="'bottom-start'"
                    :placeholder="$t('gestlab.general.labels.maintenance_tasks.select_date')"
                    />
                    <p v-if="form.errors.next_date" class="text-xs text-red-600">
                        {{ form.errors.next_date }}
                    </p>
                </div>

            </div>
          </div>
        </div>

        <!-- CALIBRATION CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" v-if="form.category_id?.value === 4 || form.category_id?.value === 5">
          <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <BeakerIcon class="h-5 w-5 text-blue-900" />
              {{ $t('gestlab.general.labels.maintenance_tasks.calibration_details') }}
            </h2>
          </div>

          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- CALIBRATION STATUS -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <CheckCircleIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.maintenance_tasks.calibration_status') }}
                </label>
                <select
                  v-model="form.calibration_status"
                  :class="[
                    'w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-transparent transition-colors duration-200',
                    form.errors.calibration_status ? 'border-red-500' : 'border-gray-300'
                  ]"
                >
                  <option value="">{{ $t('gestlab.general.labels.maintenance_tasks.select_status') }}</option>
                  <option value="pending">{{ $t('gestlab.general.labels.maintenance_tasks.status.pending') }}</option>
                  <option value="in_progress">{{ $t('gestlab.general.labels.maintenance_tasks.status.in_progress') }}</option>
                  <option value="completed">{{ $t('gestlab.general.labels.maintenance_tasks.status.completed') }}</option>
                  <option value="failed">{{ $t('gestlab.general.labels.maintenance_tasks.status.failed') }}</option>
                </select>
              </div>

              <!-- CERTIFICATE NUMBER -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <DocumentIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.maintenance_tasks.calibration_certificate_no') }}
                </label>
                <input
                  type="text"
                  v-model="form.calibration_certificate_no"
                  :placeholder="$t('gestlab.general.labels.maintenance_tasks.calibration_certificate_placeholder')"
                  :class="[
                    'w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-transparent transition-colors duration-200',
                    form.errors.calibration_certificate_no ? 'border-red-500' : 'border-gray-300'
                  ]"
                />
              </div>

              <!-- ACCEPTANCE CRITERIA -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <ScaleIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.maintenance_tasks.acceptance_criteria') }}
                </label>
                <input
                  type="text"
                  v-model="form.acceptance_criteria"
                  :placeholder="$t('gestlab.general.labels.maintenance_tasks.acceptance_placeholder')"
                  :class="[
                    'w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-transparent transition-colors duration-200',
                    form.errors.acceptance_criteria ? 'border-red-500' : 'border-gray-300'
                  ]"
                />
              </div>

              <!-- RANGE -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <ArrowsRightLeftIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.maintenance_tasks.range') }}
                </label>
                <input
                  type="text"
                  v-model="form.range"
                  :placeholder="$t('gestlab.general.labels.maintenance_tasks.range_placeholder')"
                  :class="[
                    'w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-transparent transition-colors duration-200',
                    form.errors.range ? 'border-red-500' : 'border-gray-300'
                  ]"
                />
              </div>

              <!-- CALIBRATION POINTS -->
              <div class="md:col-span-2 space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <ListBulletIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.maintenance_tasks.calibration_points') }}
                </label>
                <textarea
                  v-model="form.calibration_points"
                  rows="2"
                  :placeholder="$t('gestlab.general.labels.maintenance_tasks.calibration_points_placeholder')"
                  :class="[
                    'w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-transparent transition-colors duration-200',
                    form.errors.calibration_points ? 'border-red-500' : 'border-gray-300'
                  ]"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Result Card -->

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" v-if="form.is_executed">
          <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <HashtagIcon class="h-5 w-5 text-blue-900" />
              {{ $t('gestlab.general.labels.maintenance_tasks.result') }}
            </h2>
          </div>

          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
              <!-- RESULT -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <DocumentTextIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.maintenance_tasks.result') }}
                </label>
                <textarea
                  v-model="form.result"
                  rows="4"
                  :class="[
                    'w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-transparent transition-colors duration-200',
                    form.errors.result ? 'border-red-500' : 'border-gray-300'
                  ]"
                />
              </div>

              <!-- COMMENT -->

                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                      <ChatBubbleLeftRightIcon class="h-4 w-4" />
                      {{ $t('gestlab.general.labels.maintenance_tasks.obs') }}  
                    </label>
                    <textarea
                      v-model="form.comment"
                      rows="4"
                      :class="[
                        'w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-transparent transition-colors duration-200',
                        form.errors.comment ? 'border-red-500' : 'border-gray-300'
                      ]"
                    />
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
            {{ $t('gestlab.general.labels.maintenance_tasks.actions.title') }}
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
              @click="form.reset()"
              type="button"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <ArrowPathIcon class="h-5 w-5" />
              {{ $t('gestlab.general.buttons.reset') }}
            </button>
          </div>
        </div>

        <!-- SUPPLIER & COST CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <TruckIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.maintenance_tasks.supplier_cost') }}
          </h3>
          <div class="space-y-4">
            <!-- EXECUTED BY SUPPLIER -->
            <div class="flex items-center justify-between">
              <label class="text-sm text-gray-700">
                {{ $t('gestlab.general.labels.maintenance_tasks.executed_by_supplier') }}
              </label>
              <button 
                type="button"
                @click="form.executed_by_supplier = !form.executed_by_supplier"
                :class="[
                  'relative inline-flex h-6 w-11 flex-shrink-0 rounded-full border-2 transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
                  form.executed_by_supplier ? 'bg-blue-900 border-blue-900' : 'bg-gray-200 border-gray-300'
                ]"
              >
                <span class="sr-only">{{ $t('gestlab.general.labels.maintenance_tasks.executed_by_supplier') }}</span>
                <span :class="[
                  'pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                  form.executed_by_supplier ? 'translate-x-5' : 'translate-x-0'
                ]" />
              </button>
            </div>

            <!-- SUPPLIER -->
            <div v-if="form.executed_by_supplier" class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.maintenance_tasks.supplier_id') }}
              </label>
                <combobox-enhanced
                    v-model="form.supplier_id"
                    :hasError="form.errors.supplier_id"
                    :placeholder="$t('gestlab.general.labels.maintenance_tasks.supplier_placeholder')"
                    :load-options="loadSuppliers"
                />
              <p v-if="form.errors.supplier_id" class="text-xs text-red-600">
                {{ form.errors.supplier_id }}
              </p>
            </div>

            <!-- COST -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.maintenance_tasks.cost') }}
              </label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">AOA</span>
                <input
                  type="number"
                  v-model="form.cost"
                  step="0.01"
                  min="0"
                  class="w-full rounded-lg border border-gray-300 pl-8 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-transparent transition-colors duration-200"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- STATUS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ClipboardDocumentCheckIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.maintenance_tasks.status.title') }}
          </h3>
          <div class="space-y-3">
            <!-- IS PLANNED -->
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.maintenance_tasks.is_planned') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                form.is_planned ? 'bg-green-100 text-green-800' : 'text-gray-800'
              ]">
                  <button 
                        type="button"
                        @click="form.is_planned = !form.is_planned"
                        :class="[
                        'relative inline-flex h-6 w-11 flex-shrink-0 rounded-full border-2 transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
                        form.is_planned ? 'bg-blue-900 border-blue-900' : 'bg-gray-200 border-gray-300'
                        ]"
                    >
                        <span class="sr-only">{{ $t('gestlab.general.labels.maintenance_tasks.is_planned') }}</span>
                        <span :class="[
                        'pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                        form.is_planned ? 'translate-x-5' : 'translate-x-0'
                        ]" />
                    </button>
              </span>
            </div>

            <!-- IS EXECUTED -->
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.maintenance_tasks.is_executed') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                form.is_executed ? 'bg-blue-100 text-blue-800' : 'text-gray-800'
              ]">

                <button 
                        type="button"
                        @click="form.is_executed = !form.is_executed"
                        :class="[
                        'relative inline-flex h-6 w-11 flex-shrink-0 rounded-full border-2 transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
                        form.is_executed ? 'bg-blue-900 border-blue-900' : 'bg-gray-200 border-gray-300'
                        ]"
                    >
                        <span class="sr-only">{{ $t('gestlab.general.labels.maintenance_tasks.is_executed') }}</span>
                        <span :class="[
                        'pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                        form.is_executed ? 'translate-x-5' : 'translate-x-0'
                        ]" />
                    </button>
              </span>
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
                {{ $t("gestlab.general.labels.maintenance_tasks.category_id") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.category_id?.label }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.maintenance_tasks.name") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.name }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.maintenance_tasks.description") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.description }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.maintenance_tasks.equipment_id") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.equipment_id?.label }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="form.category_id?.value === 4 || form.category_id?.value === 5">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.maintenance_tasks.range") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.range }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="form.category_id?.value === 4 || form.category_id?.value === 5">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.maintenance_tasks.calibration_points") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.calibration_points }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="form.category_id?.value === 4 || form.category_id?.value === 5">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.maintenance_tasks.calibration_status") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.calibration_status }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="form.category_id?.value === 4 || form.category_id?.value === 5">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.maintenance_tasks.calibration_certificate_no") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.calibration_certificate_no }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.maintenance_tasks.supplier_id") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.supplier_id?.label }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.maintenance_tasks.due_date") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.due_date }}
              </dd>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.maintenance_tasks.previous_date") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.previous_date }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.maintenance_tasks.next_date") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.next_date }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.maintenance_tasks.acceptance_criteria") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.acceptance_criteria }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.maintenance_tasks.periodicity") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.periodicity }}
              </dd>
            </div>
            
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.maintenance_tasks.periodicity_unit") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.periodicity_unit?.label }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.maintenance_tasks.executed_by_supplier") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.executed_by_supplier ? $t('gestlab.buttons.yes') : $t('gestlab.general.buttons.no') }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.maintenance_tasks.is_planned") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.is_planned ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.maintenance_tasks.cost") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.cost }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.maintenance_tasks.is_executed") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.is_executed ? $t('gestlab.buttons.yes') : $t('gestlab.general.buttons.no') }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.maintenance_tasks.result") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.result }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.maintenance_tasks.obs") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.obs }}
              </dd>
            </div>
          </dl>
        </div>

      </div>

    </div>
  </confirm-dialog>

</template>

<script setup>
import { ref, reactive } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import {
  WrenchScrewdriverIcon,
  ArrowLeftIcon,
  InformationCircleIcon,
  TagIcon,
  FolderIcon,
  CogIcon,
  DocumentTextIcon,
  CalendarIcon,
  CalendarDaysIcon,
  ArrowPathIcon,
  HashtagIcon,
  BeakerIcon,
  CheckCircleIcon,
  DocumentIcon,
  ScaleIcon,
  ArrowsRightLeftIcon,
  ListBulletIcon,
  PlusCircleIcon,
  TruckIcon,
  ClipboardDocumentCheckIcon,
  ChatBubbleLeftRightIcon
} from '@heroicons/vue/24/outline'
import confirmDialog from "@/Components/confirm-dialog.vue";
import comboboxEnhanced from "@/Components/combobox-enhanced.vue";
import datePickerEnhanced from "@/Components/date-picker-enhanced.vue";

const props = defineProps({
   periodicityUnits: {
    type: Array,
    default: []
  },
})

const form = useForm({
  name: '',
  description: '',
  category_id: '',
  equipment_id: '',
  due_date: '',
  previous_date: '',
  next_date: '',
  acceptance_criteria: '',
  range: '',
  calibration_status: '',
  calibration_certificate_no: '',
  periodicity: '',
  periodicity_unit: '',
  executed_by_supplier: false,
  supplier_id: '',
  obs: '',
  cost: '0.00',
  is_planned: false,
  is_executed: false,
  calibration_points: '',
  result: '',
})

const showDeleteConfirmation = ref(false);


let submit = () => {
  form.post(route("maintenancetasks.store"), {
      preserveScroll: true,
      preserveState: true,
      onError: () => {
            showDeleteConfirmation.value = false;
      },
      onSuccess: () => {
        // openslideover.value = false;
        form.reset();
      },
    });
};

function loadCategories(query, setOptions) {
  fetch("/maintenancecategories/getMaintenanceCategory?q=" + query)
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

function loadSuppliers(query, setOptions) {
  fetch("/isuppliers/getInventoryItemSupplier?q=" + query)
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

function loadEquipment(query, setOptions) {
  fetch("/iitems/getInventoryItem?q=" + query)
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