<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <BeakerIcon class="h-7 w-7 text-blue-900" />
            {{ $page.props.title || 'Sample Management' }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ activeTab === 'entry' ? 'Register new samples and manage sample information' : 'Record sample discards and generate disposal certificates' }}
            <span v-if="activeTab === 'discard'" class="font-semibold text-blue-900">
              (Discard Mode)
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ stats.total_samples }} Samples
          </span>
          <div class="flex border border-gray-300 rounded-lg overflow-hidden">
            <button
              @click="activeTab = 'entry'"
              :class="[
                'px-4 py-2 text-sm font-medium transition-colors duration-200',
                activeTab === 'entry'
                  ? 'bg-blue-900 text-white'
                  : 'bg-white text-gray-700 hover:bg-gray-50'
              ]"
            >
              Sample Entry
            </button>
            <button
              @click="activeTab = 'discard'"
              :class="[
                'px-4 py-2 text-sm font-medium transition-colors duration-200',
                activeTab === 'discard'
                  ? 'bg-blue-900 text-white'
                  : 'bg-white text-gray-700 hover:bg-gray-50'
              ]"
            >
              Sample Discard
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- SAMPLE ENTRY SECTION -->
    <div v-if="activeTab === 'entry'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- SAMPLE DETAILS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <DocumentTextIcon class="h-5 w-5" />
              Sample Details
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <!-- GRID FORM LAYOUT -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- SAMPLE NAME -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <TagIcon class="h-4 w-4" />
                  Sample Name
                  <span class="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  v-model="form.name"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    form.errors.name 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' 
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                  placeholder="Enter sample name"
                />
                <p v-if="form.errors.name" class="text-xs text-red-600">
                  {{ form.errors.name }}
                </p>
              </div>

              <!-- SAMPLE CODE -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <QrCodeIcon class="h-4 w-4" />
                  Sample Code
                </label>
                <input
                  type="text"
                  v-model="form.code"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    form.errors.code 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' 
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                  placeholder="Will be auto-generated"
                />
              </div>

              <!-- SAMPLE TYPE -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <CubeIcon class="h-4 w-4" />
                  Sample Type
                  <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.sample_type"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    form.errors.sample_type 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' 
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                >
                  <option value="">Select sample type</option>
                  <option value="solid">Solid</option>
                  <option value="liquid">Liquid</option>
                  <option value="gas">Gas</option>
                  <option value="biological">Biological</option>
                  <option value="chemical">Chemical</option>
                  <option value="unknown">Unknown</option>
                </select>
                <p v-if="form.errors.sample_type" class="text-xs text-red-600">
                  {{ form.errors.sample_type }}
                </p>
              </div>

              <!-- CUSTOMER -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <UserGroupIcon class="h-4 w-4" />
                  Customer
                  <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.customer_id"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    form.errors.customer_id 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' 
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                >
                  <option value="">Select customer</option>
                  <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                    {{ customer.name }} ({{ customer.code }})
                  </option>
                </select>
                <p v-if="form.errors.customer_id" class="text-xs text-red-600">
                  {{ form.errors.customer_id }}
                </p>
              </div>

              <!-- LAB -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <BuildingOfficeIcon class="h-4 w-4" />
                  Laboratory
                  <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.lab_id"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    form.errors.lab_id 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' 
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                >
                  <option value="">Select laboratory</option>
                  <option v-for="lab in labs" :key="lab.id" :value="lab.id">
                    {{ lab.name }} ({{ lab.code }})
                  </option>
                </select>
                <p v-if="form.errors.lab_id" class="text-xs text-red-600">
                  {{ form.errors.lab_id }}
                </p>
              </div>

              <!-- DEPARTMENT -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <BuildingLibraryIcon class="h-4 w-4" />
                  Department
                  <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.department_id"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    form.errors.department_id 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' 
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                >
                  <option value="">Select department</option>
                  <option v-for="department in departments" :key="department.id" :value="department.id">
                    {{ department.name }} ({{ department.code }})
                  </option>
                </select>
                <p v-if="form.errors.department_id" class="text-xs text-red-600">
                  {{ form.errors.department_id }}
                </p>
              </div>

              <!-- RECEIVED AT -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <CalendarIcon class="h-4 w-4" />
                  Received Date
                </label>
                <input
                  type="datetime-local"
                  v-model="form.received_at"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                />
              </div>

              <!-- PACKAGING -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <QueueListIcon class="h-4 w-4" />
                  Packaging
                </label>
                <select
                  v-model="form.packaging_id"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                >
                  <option value="">Select packaging</option>
                  <option v-for="packaging in packagingCategories" :key="packaging.id" :value="packaging.id">
                    {{ packaging.name }} ({{ packaging.code }})
                  </option>
                </select>
              </div>

              <!-- WAREHOUSE -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <ArchiveBoxIcon class="h-4 w-4" />
                  Warehouse
                  <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.warehouse_id"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    form.errors.warehouse_id 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' 
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                >
                  <option value="">Select warehouse</option>
                  <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                    {{ warehouse.name }} ({{ warehouse.code }})
                  </option>
                </select>
                <p v-if="form.errors.warehouse_id" class="text-xs text-red-600">
                  {{ form.errors.warehouse_id }}
                </p>
              </div>

              <!-- REQUESTED SERVICES -->
              <div class="space-y-2 md:col-span-2 lg:col-span-3">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <ClipboardDocumentListIcon class="h-4 w-4" />
                  Requested Services
                </label>
                <textarea
                  v-model="form.requested_services"
                  rows="3"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                  placeholder="List all requested analysis and services..."
                ></textarea>
              </div>

              <!-- OBSERVATIONS -->
              <div class="space-y-2 md:col-span-2 lg:col-span-3">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <ChatBubbleLeftRightIcon class="h-4 w-4" />
                  Observations
                </label>
                <textarea
                  v-model="form.obs"
                  rows="2"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                  placeholder="Any additional observations or notes..."
                ></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- ANALYSIS TIMELINE CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <ClockIcon class="h-5 w-5 text-blue-900" />
                Analysis Timeline
              </h2>
            </div>
          </div>

          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- ANALYSIS START DATE -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <PlayIcon class="h-4 w-4" />
                  Analysis Start Date
                </label>
                <input
                  type="datetime-local"
                  v-model="form.analysis_start_date"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                />
              </div>

              <!-- ANALYSIS END DATE -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <StopIcon class="h-4 w-4" />
                  Analysis End Date
                </label>
                <input
                  type="datetime-local"
                  v-model="form.analysis_end_date"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                />
              </div>

              <!-- COLLECTION INFO -->
              <div class="md:col-span-2 space-y-4">
                <div class="flex items-center gap-4">
                  <div class="flex items-center">
                    <input
                      type="checkbox"
                      v-model="form.collected_by_lab"
                      id="collected_by_lab"
                      class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                    />
                    <label for="collected_by_lab" class="ml-2 text-sm text-gray-700">
                      Collected by Laboratory
                    </label>
                  </div>
                  <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700">
                      Collection Date
                    </label>
                    <input
                      type="datetime-local"
                      v-model="form.collected_at"
                      :class="[
                        'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                        'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                      ]"
                    />
                  </div>
                </div>
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
            Actions
          </h3>
          <div class="space-y-4">
            <button 
              @click="submitSample"
              :disabled="form.processing || !isFormValid"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                form.processing || !isFormValid
                  ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                  : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
              ]"
            >
              <CheckCircleIcon class="h-5 w-5" />
              {{ form.processing ? 'Processing...' : 'Save Sample' }}
            </button>
            
            <button 
              @click="resetForm"
              type="button"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <ArrowPathIcon class="h-5 w-5" />
              Reset Form
            </button>

            <!-- QUICK STATS -->
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                Statistics
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Total Samples</span>
                  <span class="font-semibold text-blue-900">{{ stats.total_samples }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Pending Analysis</span>
                  <span class="font-semibold text-yellow-500">{{ stats.pending_analysis }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Completed Analysis</span>
                  <span class="font-semibold text-green-500">{{ stats.completed_analysis }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- STATUS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <Cog6ToothIcon class="h-5 w-5 text-blue-900" />
            Sample Status
          </h3>
          <div class="space-y-3">
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Current Status
              </label>
              <select
                v-model="form.status"
                :class="[
                  'w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                  'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                ]"
              >
                <option value="POR_INICIAR">Por Iniciar</option>
                <option value="EN_PROGRESO">En Progreso</option>
                <option value="COMPLETADO">Completado</option>
                <option value="CANCELADO">Cancelado</option>
                <option value="EN_PAUSA">En Pausa</option>
              </select>
            </div>
            
            <div class="flex items-center justify-between pt-2">
              <span class="text-sm text-gray-600">Form Completion</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                isFormValid ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
              ]">
                {{ isFormValid ? 'Complete' : 'Incomplete' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- SAMPLE DISCARD SECTION -->
    <div v-if="activeTab === 'discard'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- DISCARD FORM CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-red-600 to-red-500 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <TrashIcon class="h-5 w-5" />
              Discard Sample
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- SAMPLE SELECTION -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <BeakerIcon class="h-4 w-4" />
                  Select Sample
                  <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="discardForm.sample_id"
                  @change="onSampleSelect"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    discardForm.errors.sample_id 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' 
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                >
                  <option value="">Select a sample to discard</option>
                  <option v-for="sample in discardableSamples" :key="sample.id" :value="sample.id">
                    {{ sample.code }} - {{ sample.name }} ({{ sample.status }})
                  </option>
                </select>
                <p v-if="discardForm.errors.sample_id" class="text-xs text-red-600">
                  {{ discardForm.errors.sample_id }}
                </p>
              </div>

              <!-- DISCARD METHOD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <CogIcon class="h-4 w-4" />
                  Discard Method
                  <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="discardForm.discard_method"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    discardForm.errors.discard_method 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' 
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                >
                  <option value="">Select discard method</option>
                  <option value="incineration">Incineration</option>
                  <option value="chemical_treatment">Chemical Treatment</option>
                  <option value="autoclave">Autoclave</option>
                  <option value="landfill">Landfill</option>
                  <option value="recycling">Recycling</option>
                  <option value="return_to_client">Return to Client</option>
                </select>
                <p v-if="discardForm.errors.discard_method" class="text-xs text-red-600">
                  {{ discardForm.errors.discard_method }}
                </p>
              </div>

              <!-- QUANTITY -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <ScaleIcon class="h-4 w-4" />
                  Quantity
                  <span class="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  v-model="discardForm.qty"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    discardForm.errors.qty 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' 
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                  placeholder="e.g., 250g, 500ml, 1 unit"
                />
                <p v-if="discardForm.errors.qty" class="text-xs text-red-600">
                  {{ discardForm.errors.qty }}
                </p>
              </div>

              <!-- DISCARDED AT -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <CalendarIcon class="h-4 w-4" />
                  Discarded Date
                </label>
                <input
                  type="datetime-local"
                  v-model="discardForm.discarded_at"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                />
              </div>

              <!-- LAB -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <BuildingOfficeIcon class="h-4 w-4" />
                  Laboratory
                </label>
                <select
                  v-model="discardForm.lab_id"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                >
                  <option value="">Select laboratory</option>
                  <option v-for="lab in labs" :key="lab.id" :value="lab.id">
                    {{ lab.name }} ({{ lab.code }})
                  </option>
                </select>
              </div>

              <!-- DEPARTMENT -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <BuildingLibraryIcon class="h-4 w-4" />
                  Department
                </label>
                <select
                  v-model="discardForm.department_id"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                >
                  <option value="">Select department</option>
                  <option v-for="department in departments" :key="department.id" :value="department.id">
                    {{ department.name }} ({{ department.code }})
                  </option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- RECENT DISCARDED SAMPLES -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <ArchiveBoxXMarkIcon class="h-5 w-5 text-red-600" />
                Recent Discards
                <span class="text-sm font-normal text-gray-500 ml-2">
                  ({{ recentDiscards.length }} items)
                </span>
              </h2>
            </div>
          </div>

          <!-- EMPTY STATE -->
          <div v-if="recentDiscards.length === 0" class="p-12 text-center">
            <ArchiveBoxXMarkIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              No Discards Yet
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              No samples have been discarded yet. When you discard a sample, it will appear here.
            </p>
          </div>

          <!-- DISCARDED SAMPLES LIST -->
          <div v-else class="divide-y divide-gray-200">
            <div 
              v-for="discard in recentDiscards"
              :key="discard.id"
              class="px-6 py-4 hover:bg-gray-50 transition-colors duration-200"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 text-red-800 font-semibold">
                    <TrashIcon class="h-4 w-4" />
                  </div>
                  <div>
                    <h3 class="text-sm font-semibold text-gray-900">
                      {{ discard.sample?.name || 'Unknown Sample' }}
                    </h3>
                    <div class="flex items-center gap-3 mt-1">
                      <span class="text-xs text-gray-500">
                        {{ discard.sample?.code || 'No Code' }}
                      </span>
                      <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-800">
                        {{ discard.discard_method }}
                      </span>
                      <span class="text-xs text-gray-500">
                        {{ formatDate(discard.discarded_at) }}
                      </span>
                    </div>
                  </div>
                </div>
                <div class="text-right">
                  <span class="text-sm font-semibold text-red-600">
                    {{ discard.qty }}
                  </span>
                  <p class="text-xs text-gray-500 mt-1">
                    by {{ discard.discarded_by?.name || 'Unknown' }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6">
        <!-- SELECTED SAMPLE INFO -->
        <div v-if="selectedSample" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <InformationCircleIcon class="h-5 w-5 text-blue-900" />
            Selected Sample Information
          </h3>
          <div class="space-y-4">
            <div>
              <h4 class="text-sm font-medium text-gray-900">{{ selectedSample.name }}</h4>
              <p class="text-xs text-gray-500 mt-1">{{ selectedSample.code }}</p>
            </div>
            
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Status</span>
                <span :class="[
                  'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                  selectedSample.status === 'COMPLETADO' ? 'bg-green-100 text-green-800' :
                  selectedSample.status === 'EN_PROGRESO' ? 'bg-blue-100 text-blue-800' :
                  selectedSample.status === 'POR_INICIAR' ? 'bg-yellow-100 text-yellow-800' :
                  'bg-gray-100 text-gray-800'
                ]">
                  {{ selectedSample.status }}
                </span>
              </div>
              
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Sample Type</span>
                <span class="text-sm font-medium text-gray-900">{{ selectedSample.sample_type }}</span>
              </div>
              
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Received</span>
                <span class="text-sm text-gray-900">{{ formatDate(selectedSample.received_at) }}</span>
              </div>
              
              <div v-if="selectedSample.analysis_start_date" class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Analysis Start</span>
                <span class="text-sm text-gray-900">{{ formatDate(selectedSample.analysis_start_date) }}</span>
              </div>
              
              <div v-if="selectedSample.analysis_end_date" class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Analysis End</span>
                <span class="text-sm text-gray-900">{{ formatDate(selectedSample.analysis_end_date) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- DISCARD ACTIONS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Discard Actions
          </h3>
          <div class="space-y-4">
            <button 
              @click="submitDiscard"
              :disabled="discardForm.processing || !isDiscardFormValid"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                discardForm.processing || !isDiscardFormValid
                  ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                  : 'bg-gradient-to-r from-red-600 to-red-500 text-white hover:from-red-500 hover:to-red-400 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2'
              ]"
            >
              <TrashIcon class="h-5 w-5" />
              {{ discardForm.processing ? 'Processing...' : 'Discard Sample' }}
            </button>
            
            <button 
              @click="resetDiscardForm"
              type="button"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <ArrowPathIcon class="h-5 w-5" />
              Clear Form
            </button>

            <!-- DISCARD STATS -->
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                Discard Statistics
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Total Discarded</span>
                  <span class="font-semibold text-red-600">{{ stats.total_discarded }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Discarded This Month</span>
                  <span class="font-semibold text-red-600">{{ stats.discarded_this_month }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- WARNING CARD -->
        <div class="bg-red-50 border border-red-200 rounded-xl p-6">
          <div class="flex items-start gap-3">
            <ExclamationTriangleIcon class="h-5 w-5 text-red-600 mt-0.5" />
            <div>
              <h4 class="text-sm font-semibold text-red-900 mb-2">
                ⚠️ Discard Warning
              </h4>
              <p class="text-xs text-red-700">
                Discarding a sample is an irreversible action. Once discarded, the sample cannot be recovered. Please ensure this is the correct sample before proceeding.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- FOOTER ACTIONS -->
    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
      <div class="text-sm text-gray-500">
        Last updated: {{ formatDate(new Date()) }}
      </div>
      <div class="flex items-center gap-4">
        <button 
          @click="exportData"
          type="button"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
        >
          <ArrowDownTrayIcon class="h-5 w-5" />
          Export Data
        </button>
        <button 
          @click="refreshData"
          type="button"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
        >
          <ArrowPathIcon class="h-5 w-5" />
          Refresh
        </button>
      </div>
    </div>

    <!-- Success/Error Messages -->
    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="transform opacity-0 translate-y-2"
      enter-to-class="transform opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="transform opacity-100 translate-y-0"
      leave-to-class="transform opacity-0 translate-y-2"
    >
      <div 
        v-if="$page.props.flash.message"
        :class="[
          'fixed bottom-4 right-4 rounded-lg p-4 shadow-lg z-50 max-w-md',
          $page.props.flash.type === 'error' ? 'bg-red-50 border border-red-200' :
          $page.props.flash.type === 'warning' ? 'bg-yellow-50 border border-yellow-200' :
          'bg-green-50 border border-green-200'
        ]"
      >
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <CheckCircleIcon 
              v-if="$page.props.flash.type === 'success'" 
              class="h-5 w-5 text-green-400" 
            />
            <ExclamationTriangleIcon 
              v-else 
              class="h-5 w-5 text-red-400" 
            />
          </div>
          <div class="ml-3">
            <p :class="[
              'text-sm font-medium',
              $page.props.flash.type === 'error' ? 'text-red-800' :
              $page.props.flash.type === 'warning' ? 'text-yellow-800' :
              'text-green-800'
            ]">
              {{ $page.props.flash.message }}
            </p>
            <div v-if="$page.props.flash.sample_id" class="mt-2">
              <button
                @click="generateEntryPdf($page.props.flash.sample_id)"
                class="text-sm font-medium text-blue-600 hover:text-blue-500"
              >
                Generate Sample Entry PDF
              </button>
            </div>
            <div v-if="$page.props.flash.discard_id" class="mt-2">
              <button
                @click="generateDiscardPdf($page.props.flash.discard_id)"
                class="text-sm font-medium text-blue-600 hover:text-blue-500"
              >
                Generate Discard Certificate PDF
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, reactive, watch } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { 
  BeakerIcon,
  DocumentTextIcon,
  TagIcon,
  QrCodeIcon,
  CubeIcon,
  UserGroupIcon,
  BuildingOfficeIcon,
  BuildingLibraryIcon,
  CalendarIcon,
  QueueListIcon,
  ArchiveBoxIcon,
  ClipboardDocumentListIcon,
  ChatBubbleLeftRightIcon,
  ClockIcon,
  PlayIcon,
  StopIcon,
  CheckCircleIcon,
  ArrowPathIcon,
  Cog6ToothIcon,
  TrashIcon,
  ArchiveBoxXMarkIcon,
  InformationCircleIcon,
  ExclamationTriangleIcon,
  ArrowDownTrayIcon,
  CogIcon,
  ScaleIcon
} from '@heroicons/vue/24/outline'

// Get page props
const page = usePage()

// Reactive state
const activeTab = ref('entry')
const selectedSample = ref(null)

// Data from controller
const stats = computed(() => page.props.stats)
const discardableSamples = computed(() => page.props.discardableSamples)
const recentDiscards = computed(() => page.props.recentDiscards)
const customers = computed(() => page.props.customers)
const labs = computed(() => page.props.labs)
const departments = computed(() => page.props.departments)
const warehouses = computed(() => page.props.warehouses)
const packagingCategories = computed(() => page.props.packagingCategories)

// Sample entry form
const form = useForm({
  name: '',
  code: '',
  sample_type: '',
  customer_id: '',
  lab_id: '',
  department_id: '',
  warehouse_id: '',
  packaging_id: '',
  received_at: '',
  requested_services: '',
  obs: '',
  status: 'POR_INICIAR',
  analysis_start_date: '',
  analysis_end_date: '',
  collected_by_lab: false,
  collected_at: '',
  client_submitted_info: ''
})

// Discard form
const discardForm = useForm({
  sample_id: '',
  discard_method: '',
  qty: '',
  discarded_at: '',
  lab_id: '',
  department_id: ''
})

// Computed properties
const isFormValid = computed(() => {
  return form.name && 
         form.sample_type && 
         form.customer_id && 
         form.lab_id && 
         form.department_id && 
         form.warehouse_id
})

const isDiscardFormValid = computed(() => {
  return discardForm.sample_id && 
         discardForm.discard_method && 
         discardForm.qty
})

// Methods
const submitSample = () => {
  form.post(route('vap_samples.samples.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
    }
  })
}

const submitDiscard = () => {
  discardForm.post(route('vap_samples.discards.store'), {
    preserveScroll: true,
    onSuccess: () => {
      discardForm.reset()
      selectedSample.value = null
    }
  })
}

const resetForm = () => {
  form.reset()
  form.status = 'POR_INICIAR'
  form.collected_by_lab = false
}

const resetDiscardForm = () => {
  discardForm.reset()
  selectedSample.value = null
}

const onSampleSelect = () => {
  const sample = discardableSamples.value.find(s => s.id == discardForm.sample_id)
  if (sample) {
    selectedSample.value = sample
    // Pre-fill lab and department from sample
    discardForm.lab_id = sample.lab_id
    discardForm.department_id = sample.department_id
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  } catch (e) {
    return 'Invalid date'
  }
}

const exportData = () => {
  if (activeTab.value === 'discard') {
    window.open(route('vap_samples.discards.export'), '_blank')
  } else {
    // Export sample data logic here
    console.log('Export sample data')
  }
}

const refreshData = () => {
  window.location.reload()
}

const generateEntryPdf = (sampleId) => {
  window.open(route('vap_samples.samples.pdf', sampleId), '_blank')
}

const generateDiscardPdf = (discardId) => {
  window.open(route('vap_samples.discards.pdf', discardId), '_blank')
}

// Auto-generate code when sample type is selected
watch(() => form.sample_type, (newType) => {
  if (newType && !form.code) {
    const prefix = newType.substring(0, 3).toUpperCase()
    const year = new Date().getFullYear()
    const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0')
    form.code = `SMP-${year}-${prefix}-${random}`
  }
})

// Set default discarded_at to current datetime
watch(() => discardForm, (newForm) => {
  if (!newForm.discarded_at) {
    const now = new Date()
    const timezoneOffset = now.getTimezoneOffset() * 60000
    const localISOTime = new Date(now - timezoneOffset).toISOString().slice(0, 16)
    discardForm.discarded_at = localISOTime
  }
}, { immediate: true, deep: true })

// Set default received_at to current datetime for sample entry
watch(() => form, (newForm) => {
  if (!newForm.received_at) {
    const now = new Date()
    const timezoneOffset = now.getTimezoneOffset() * 60000
    const localISOTime = new Date(now - timezoneOffset).toISOString().slice(0, 16)
    form.received_at = localISOTime
  }
}, { immediate: true, deep: true })
</script>

<style scoped>
/* Custom styles if needed */
</style>