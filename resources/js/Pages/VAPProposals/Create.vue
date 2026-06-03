<template>
  <div class="proposal-editor-shell space-y-8" :class="commercialDocumentThemeClasses">
    <section class="overflow-hidden rounded-[34px] border border-[#ded2bb] bg-[#fbfaf6] shadow-[0_26px_70px_-44px_rgba(20,61,55,0.55)] dark:border-white/10 dark:bg-slate-950">
      <div class="border-b border-[#ded2bb] bg-[radial-gradient(circle_at_top_left,rgba(199,154,67,0.2),transparent_34%),linear-gradient(135deg,#fffaf0,#f7f1e6_62%,#143d37_62%,#143d37)] px-6 py-7 dark:border-white/10 dark:bg-[radial-gradient(circle_at_top_left,rgba(199,154,67,0.18),transparent_34%),linear-gradient(135deg,#17231f,#101815_62%,#0b1210_62%,#0b1210)] sm:px-8">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
          <div class="max-w-4xl">
            <span class="inline-flex items-center gap-2 rounded-full border border-[#c79a43]/40 bg-white/85 px-3 py-1 text-xs font-black uppercase tracking-[0.24em] text-[#143d37] shadow-sm dark:bg-white/10 dark:text-amber-100">
              <DocumentPlusIcon class="h-4 w-4 text-[#c79a43]" />
              {{ $t('gestlab.general.labels.vap_proposals.surface.new_proposal') }}
            </span>
            <h1 class="mt-5 text-3xl font-black tracking-[-0.04em] text-[#10221d] dark:text-white sm:text-5xl">
              {{ $t('gestlab.general.labels.vap_proposals.create.title') }}
            </h1>
            <p class="mt-4 max-w-3xl text-base font-medium leading-7 text-[#59665f] dark:text-slate-300">
              {{ $t('gestlab.general.labels.vap_proposals.create.description') }}
            </p>
          </div>

          <div class="grid gap-3 sm:grid-cols-3 xl:min-w-[35rem]">
            <div class="rounded-[24px] border border-white/40 bg-white/85 p-4 shadow-[0_18px_50px_-34px_rgba(20,61,55,0.45)] backdrop-blur dark:border-white/10 dark:bg-white/10">
              <p class="text-xs font-black uppercase tracking-[0.2em] text-[#78847c] dark:text-slate-400">{{ $t('gestlab.general.labels.vap_proposals.surface.expected_code') }}</p>
              <p class="mt-2 text-lg font-black text-[#143d37] dark:text-emerald-100">{{ nextProposalNo }}</p>
            </div>
            <div class="rounded-[24px] border border-white/40 bg-white/85 p-4 shadow-[0_18px_50px_-34px_rgba(20,61,55,0.45)] backdrop-blur dark:border-white/10 dark:bg-white/10">
              <p class="text-xs font-black uppercase tracking-[0.2em] text-[#78847c] dark:text-slate-400">{{ $t('gestlab.general.labels.vap_proposals.surface.items') }}</p>
              <p class="mt-2 text-lg font-black text-[#143d37] dark:text-emerald-100">{{ form.items.length }}</p>
            </div>
            <div class="rounded-[24px] border border-white/40 bg-white/85 p-4 shadow-[0_18px_50px_-34px_rgba(20,61,55,0.45)] backdrop-blur dark:border-white/10 dark:bg-white/10">
              <p class="text-xs font-black uppercase tracking-[0.2em] text-[#78847c] dark:text-slate-400">{{ $t('gestlab.general.labels.vap_proposals.surface.total') }}</p>
              <p class="mt-2 text-lg font-black text-[#143d37] dark:text-emerald-100">AOA {{ formatNumber(proposalTotal) }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="flex flex-col gap-3 px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-8">
        <div class="text-sm font-semibold text-[#59665f] dark:text-slate-300">
          {{ $t('gestlab.general.labels.vap_proposals.create.footer_note') }}
        </div>
        <Link
          :href="route('vap-proposals.index')"
          class="inline-flex items-center justify-center gap-2 rounded-[20px] border border-[#ded2bb] bg-white px-5 py-3 text-sm font-black text-[#143d37] shadow-[0_18px_42px_-32px_rgba(20,61,55,0.55)] transition hover:border-[#c79a43] hover:bg-[#fff7e5] dark:border-white/10 dark:bg-white/10 dark:text-emerald-100 dark:hover:bg-white/15"
        >
          <ArrowLeftIcon class="h-5 w-5" />
          {{ $t('gestlab.general.buttons.back') }}
        </Link>
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
        <div class="overflow-hidden rounded-[30px] border border-[#ded2bb] bg-white/90 shadow-[0_22px_70px_-46px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90">
          <div class="border-b border-[#ded2bb] bg-[#fbfaf6] px-6 py-5 dark:border-white/10 dark:bg-white/5">
            <p class="text-xs font-black uppercase tracking-[0.28em] text-[#c79a43]">{{ $t('gestlab.general.labels.vap_proposals.surface.commercial_base') }}</p>
            <h2 class="mt-2 flex items-center gap-2 text-2xl font-black tracking-[-0.03em] text-[#10221d] dark:text-white">
              <InformationCircleIcon class="h-6 w-6 text-[#143d37] dark:text-emerald-100" />
              {{ $t('gestlab.general.labels.vap_proposals.create.basic_info') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- CLIENT SELECTION -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 flex items-center gap-1">
                  <UserIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.create.customer') }}
                  <span class="text-red-500">*</span>
                </label>
                <comboboxEnhanced 
                    :hasError="form.errors.customer_id"
                    v-model="selectedCustomer"
                    :options="customers.map(customer => ({
                    value: customer.id,
                    label: customer.name,
                    }))"
                    :placeholder="$t('gestlab.general.labels.vap_proposals.create.select_customer')"
                />
                <p v-if="form.errors.customer_id" class="text-xs text-red-600">
                  {{ form.errors.customer_id }}
                </p>
              </div>

              <!-- WAREHOUSE SELECTION -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 flex items-center gap-1">
                  <BuildingStorefrontIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.create.warehouse') }}
                  <span class="text-red-500">*</span>
                </label>
                <comboboxEnhanced 
                    :disableInput="!form.customer_id"
                    :hasError="form.errors.warehouse_id" 
                    v-model="selectedWarehouse" 
                    :load-options="loadWarehouses"
                    :loading="loadingWarehouses"
                    :placeholder="$t('gestlab.general.labels.vap_proposals.create.select_warehouse')"
                />
                <p v-if="form.errors.warehouse_id" class="text-xs text-red-600">
                  {{ form.errors.warehouse_id }}
                </p>
              </div>

              <!-- DEPARTMENT SELECTION -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 flex items-center gap-1">
                  <BuildingOfficeIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.create.department') }}
                  <span class="text-red-500">*</span>
                </label>
                <comboboxEnhanced 
                    :hasError="form.errors.department_id"
                    v-model="selectedDepartment"
                    :options="departments.map(department => ({
                    value: department.id,
                    label: department.name,
                    }))"
                    :placeholder="$t('gestlab.general.labels.vap_proposals.create.select_department')"
                />
                <p v-if="form.errors.department_id" class="text-xs text-red-600">
                  {{ form.errors.department_id }}
                </p>
              </div>

              <!-- TEMPLATE SELECTION -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 flex items-center gap-1">
                  <DocumentTextIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.create.template') }}
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
                    :placeholder="$t('gestlab.general.labels.vap_proposals.create.select_template')"
                />
                <p v-if="form.errors.template_id" class="text-xs text-red-600">
                  {{ form.errors.template_id }}
                </p>
              </div>

              <!-- LAB CODE SELECTION -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 flex items-center gap-1">
                  <DocumentTextIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.create.labcode') }}
                </label>
                <comboboxEnhanced 
                    v-model="labcode_id"
                    :load-options="loadLabCodes"
                    :placeholder="$t('gestlab.general.labels.vap_proposals.create.select_labcode')"
                />
                <div v-if="loadingParameters" class="text-xs font-semibold text-[#143d37] dark:text-emerald-200">
                  {{ $t('gestlab.general.labels.vap_proposals.create.loading_parameters') }}
                </div>
                <div v-if="loadErrorMessage" class="text-xs font-semibold text-red-600 dark:text-red-300">
                  {{ loadErrorMessage }}
                </div>
              </div>

              <!-- SERVICE LOCATION -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 flex items-center gap-1">
                  <MapPinIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.create.service_location') }}
                  <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.service_location"
                  type="text"
                  class="w-full rounded-lg border border-slate-300 dark:border-slate-700 px-4 py-2.5 text-sm focus:border-[#143d37] focus:outline-none focus:ring-2 focus:ring-[#c79a43]"
                  :class="{ 'border-red-500': form.errors.service_location }"
                  :placeholder="$t('gestlab.general.labels.vap_proposals.create.service_location')"
                />
                <p v-if="form.errors.service_location" class="text-xs text-red-600">
                  {{ form.errors.service_location }}
                </p>
              </div>

              <!-- TOLERANCE DAYS -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 flex items-center gap-1">
                  <ClockIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposals.create.tolerance_days') }}
                  <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.tolerance_days"
                  type="number"
                  min="1"
                  max="365"
                  class="w-full rounded-lg border border-slate-300 dark:border-slate-700 px-4 py-2.5 text-sm focus:border-[#143d37] focus:outline-none focus:ring-2 focus:ring-[#c79a43]"
                  :class="{ 'border-red-500': form.errors.tolerance_days }"
                  :placeholder="$t('gestlab.general.labels.vap_proposals.create.tolerance_days_placeholder')"
                />
                <p v-if="form.errors.tolerance_days" class="text-xs text-red-600">
                  {{ form.errors.tolerance_days }}
                </p>
              </div>

              <!-- USE MATRIX PRICE TOGGLE -->
              <div class="flex items-center space-x-2 col-span-1 md:col-span-2 lg:col-span-1">
                <input
                  v-model="form.use_matrix_price"
                  type="checkbox"
                  id="use_matrix_price"
                  class="h-4 w-4 rounded border-slate-300 dark:border-slate-700 text-[#143d37] dark:text-emerald-200 focus:ring-[#c79a43]"
                />
                <label for="use_matrix_price" class="text-sm text-slate-700 dark:text-slate-200">
                  {{ $t('gestlab.general.labels.vap_proposals.create.use_matrix_price') }}
                </label>
              </div>

              <!-- WITHHOLD TAX -->
              <div class="flex items-center space-x-2">
                <input
                  v-model="form.withhold_tax"
                  type="checkbox"
                  id="withhold_tax"
                  class="h-4 w-4 rounded border-slate-300 dark:border-slate-700 text-[#143d37] dark:text-emerald-200 focus:ring-[#c79a43]"
                />
                <label for="withhold_tax" class="text-sm text-slate-700 dark:text-slate-200">
                  {{ $t('gestlab.general.labels.vap_proposals.create.withhold_tax') }}
                </label>
              </div>
            </div>

            <!-- OBSERVATIONS -->
            <div class="mt-6">
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                {{ $t('gestlab.general.labels.vap_proposals.create.observations') }}
              </label>
              <textarea
                v-model="form.obs"
                rows="3"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-700 px-4 py-2.5 text-sm focus:border-[#143d37] focus:outline-none focus:ring-2 focus:ring-[#c79a43]"
                :placeholder="$t('gestlab.general.labels.vap_proposals.create.observations_placeholder')"
              ></textarea>
            </div>
          </div>
        </div>

        <!-- SERVICE ITEMS CARD -->
        <div class="overflow-hidden rounded-[30px] border border-[#ded2bb] bg-white/90 shadow-[0_22px_70px_-46px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90">
          <div class="border-b border-[#ded2bb] bg-[#fbfaf6] px-6 py-5 dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center justify-between">
              <h2 class="flex items-center gap-2 text-2xl font-black tracking-[-0.03em] text-[#10221d] dark:text-white">
                <ListBulletIcon class="h-6 w-6 text-[#143d37] dark:text-emerald-100" />
                {{ $t('gestlab.general.labels.vap_proposals.create.items.title') }}
                <span class="ml-2 rounded-full bg-white px-3 py-1 text-sm font-black text-[#143d37] ring-1 ring-[#ded2bb] dark:bg-white/10 dark:text-emerald-100 dark:ring-white/10">
                  ({{ form.items.length }} {{ $t('gestlab.general.buttons.items') }})
                </span>
              </h2>
              <div class="flex items-center gap-2">
                <button 
                  @click="loadParametersBasedOnLabCode(labcode_id?.value)"
                  v-if="labcode_id && !form.items.length"
                  :disabled="!labcode_id || loadingParameters"
                  class="inline-flex items-center gap-2 rounded-[18px] bg-emerald-700 px-4 py-2.5 text-sm font-black text-white shadow-[0_16px_38px_-26px_rgba(21,128,61,0.75)] transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2"
                >
                  <DocumentTextIcon class="h-5 w-5" />
                  {{ $t('gestlab.general.labels.vap_proposals.assign_lab_code') }}
                </button>
                <button 
                  @click="addItem"
                  type="button"
                  class="inline-flex items-center gap-2 rounded-[18px] bg-[#143d37] px-4 py-2.5 text-sm font-black text-white shadow-[0_16px_38px_-26px_rgba(20,61,55,0.75)] transition hover:bg-[#0f302b] focus:outline-none focus:ring-2 focus:ring-[#c79a43] focus:ring-offset-2"
                >
                  <PlusCircleIcon class="h-5 w-5" />
                  {{ $t('gestlab.general.labels.vap_proposals.create.items.add_item') }}
                </button>
              </div>
            </div>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
              {{ $t('gestlab.general.labels.vap_proposals.create.items.description') }}
            </p>
          </div>

          <!-- EMPTY STATE -->
          <div v-if="form.items.length === 0" class="p-12 text-center">
            <DocumentTextIcon class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-700" />
            <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">
              {{ $t('gestlab.general.labels.vap_proposals.create.items.title') }}
            </h3>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
              {{ $t('gestlab.general.labels.vap_proposals.create.items.description') }}
            </p>
            <div class="mt-6 flex justify-center gap-3">
              <button 
                v-if="labcode_id"
                @click="loadParametersBasedOnLabCode(labcode_id?.value)"
                :disabled="!labcode_id || loadingParameters"
                class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2"
              >
                <DocumentTextIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.vap_proposals.assign_lab_code') }}
              </button>
              <button 
                @click="addItem"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-[#143d37] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#0f302b] focus:outline-none focus:ring-2 focus:ring-[#c79a43] focus:ring-offset-2"
              >
                <PlusCircleIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.vap_proposals.create.items.add_item') }}
              </button>
            </div>
          </div>

          <!-- ITEMS GRID -->
          <div v-else class="space-y-6 p-6">
            <!-- ITEM CARD TEMPLATE -->
            <div 
              v-for="(item, index) in form.items"
              :key="index"
              class="group relative rounded-2xl bg-white/95 dark:bg-slate-950/85 border border-slate-200 dark:border-slate-800 hover:border-[#143d37] transition-all duration-200 overflow-hidden shadow-sm"
              v-motion
              :initial="{ opacity: 0, y: 20 }"
              :enter="{ opacity: 1, y: 0 }"
              :delay="index * 50"
            >
              <!-- ITEM HEADER -->
              <div class="bg-gradient-to-r from-[#f7f1e6] to-white px-4 py-3 border-b border-slate-200 dark:border-slate-800">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-[#143d37] text-white font-semibold">
                      {{ index + 1 }}
                    </div>
                    <div>
                      <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                        {{ item.item_description || $t('gestlab.general.labels.vap_proposals.create.items.item_description') }}
                      </h3>
                      <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ $t('gestlab.general.buttons.item') }} #{{ index + 1 }}
                      </p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <button 
                      @click="duplicateItem(index)"
                      type="button"
                      class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-[#143d37] transition-all duration-200 p-1 rounded-full hover:bg-[#f7f1e6]"
                      :title="$t('gestlab.general.labels.vap_proposals.create.items.duplicate_item')"
                    >
                      <DocumentDuplicateIcon class="h-5 w-5" />
                    </button>
                    <button 
                      @click="removeItem(index)"
                      type="button"
                      class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-red-600 transition-all duration-200 p-1 rounded-full hover:bg-red-50"
                      :title="$t('gestlab.general.labels.vap_proposals.create.items.remove_item')"
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
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-200">
                        {{ $t('gestlab.general.labels.vap_proposals.create.items.item') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <comboboxEnhanced 
                        v-model="item.item_selection"
                        :load-options="form.use_matrix_price ? loadMatrixes : loadParameters"
                        :placeholder="form.use_matrix_price 
                        ? $t('gestlab.general.labels.vap_proposals.create.items.select_matrix')
                        : $t('gestlab.general.labels.vap_proposals.create.items.select_parameter')"
                        @update:model-value="onSelectedItem(item, index)"
                    />
                    </div>

                  <!-- ITEM DESCRIPTION -->
                  <div class="space-y-2">
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-200">
                      {{ $t('gestlab.general.labels.vap_proposals.create.items.item_description') }}
                      <span class="text-red-500">*</span>
                    </label>
                    <input
                      v-model="item.item_description"
                      type="text"
                      class="w-full rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm focus:border-[#143d37] focus:outline-none focus:ring-2 focus:ring-[#c79a43]"
                      :placeholder="$t('gestlab.general.labels.vap_proposals.create.items.item_description_placeholder')"
                      @input="calculateItemTotal(index)"
                    />
                  </div>

                  <!-- STANDARD SELECTION -->
                  <div class="space-y-2">
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-200">
                        {{ $t('gestlab.general.labels.vap_proposals.create.items.standard') }}
                    </label>
                    <comboboxEnhanced 
                        :hasError="item.standard_id"
                        v-model="item.standard_display"
                        :options="standardOptions"
                        :placeholder="$t('gestlab.general.labels.vap_proposals.create.items.select_standard')"
                        @update:model-value="onStandardChange(item, index)"
                    />
                    </div>

                  <!-- UNIT SELECTION -->
                  <div class="space-y-2">
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-200">
                        {{ $t('gestlab.general.labels.vap_proposals.create.items.unit') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <comboboxEnhanced 
                        :hasError="item.unit_id"
                        v-model="item.unit_display"
                        :options="unitOptions"
                        :placeholder="$t('gestlab.general.labels.vap_proposals.create.items.select_unit')"
                        @update:model-value="onUnitChange(item, index)"
                    />
                    </div>

                  <!-- QUANTITY -->
                  <div class="space-y-2">
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-200">
                      {{ $t('gestlab.general.labels.vap_proposals.create.items.quantity') }}
                      <span class="text-red-500">*</span>
                    </label>
                    <input
                      v-model="item.qty"
                      type="number"
                      min="0.01"
                      step="0.01"
                      class="w-full rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm focus:border-[#143d37] focus:outline-none focus:ring-2 focus:ring-[#c79a43]"
                      @input="calculateItemTotal(index)"
                    />
                  </div>

                  <!-- UNIT PRICE -->
                  <div class="space-y-2">
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-200">
                      {{ $t('gestlab.general.labels.vap_proposals.create.items.unit_price') }}
                      <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                      <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-500 dark:text-slate-400 text-sm">AOA</span>
                      <input
                        v-model="item.unit_price"
                        type="number"
                        min="0"
                        step="0.01"
                        class="w-full rounded-lg border border-slate-300 dark:border-slate-700 pl-10 pr-3 py-2 text-sm focus:border-[#143d37] focus:outline-none focus:ring-2 focus:ring-[#c79a43]"
                        @input="calculateItemTotal(index)"
                      />
                    </div>
                  </div>

                  <!-- DISCOUNT TYPE -->
                  <div class="space-y-2">
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-200">
                      {{ $t('gestlab.general.labels.vap_proposals.create.items.discount') }}
                    </label>
                    <div class="flex gap-2">
                      <select 
                        v-model="item.discount_id"
                        class="w-1/3 rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm focus:border-[#143d37] focus:outline-none focus:ring-2 focus:ring-[#c79a43]"
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
                          class="w-full rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm focus:border-[#143d37] focus:outline-none focus:ring-2 focus:ring-[#c79a43]"
                          @input="calculateItemTotal(index)"
                        />
                        <input
                          v-model="item.discount_percentage"
                          v-else
                          type="number"
                          min="0"
                          max="100"
                          step="0.01"
                          class="w-full rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm focus:border-[#143d37] focus:outline-none focus:ring-2 focus:ring-[#c79a43]"
                          @input="calculateItemTotal(index)"
                        />
                        <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-500 dark:text-slate-400 text-sm">
                          {{ item.discount_id == 2 ? 'AOA' : '%' }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- TAX PERCENTAGE -->
                  <div class="space-y-2" v-if="item.charge_tax">
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-200">
                      {{ $t('gestlab.general.labels.vap_proposals.create.items.tax') }}
                    </label>
                    <div class="relative">
                      <input
                        v-model="item.tax_percentage"
                        type="number"
                        min="0"
                        max="100"
                        step="0.01"
                        class="w-full rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm focus:border-[#143d37] focus:outline-none focus:ring-2 focus:ring-[#c79a43]"
                        @input="calculateItemTotal(index)"
                      />
                      <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-500 dark:text-slate-400 text-sm">%</span>
                    </div>
                  </div>

                  <!-- CHARGE TAX CHECKBOX -->
                  <div class="flex items-center space-x-2">
                    <input
                      v-model="item.charge_tax"
                      type="checkbox"
                      :id="`charge_tax_${index}`"
                      class="h-4 w-4 rounded border-slate-300 dark:border-slate-700 text-[#143d37] dark:text-emerald-200 focus:ring-[#c79a43]"
                      @change="calculateItemTotal(index)"
                    />
                    <label :for="`charge_tax_${index}`" class="text-xs text-slate-700 dark:text-slate-200">
                      {{ $t('gestlab.general.labels.vap_proposals.create.items.charge_tax') }}
                    </label>
                  </div>

                  <!-- TOTAL -->
                  <div class="space-y-2">
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-200">
                      {{ $t('gestlab.general.labels.vap_proposals.create.items.total') }}
                    </label>
                    <div class="flex items-center">
                      <div class="flex-1 bg-slate-50 dark:bg-slate-900/70 rounded-lg px-3 py-2 text-sm font-semibold text-slate-900 dark:text-white">
                        AOA {{ formatNumber(itemsWithSubTotal[index]?.total || 0) }}
                      </div>
                    </div>
                  </div>

                  <!-- OBSERVATIONS -->
                  <div class="space-y-2 col-span-full">
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-200">
                      {{ $t('gestlab.general.labels.vap_proposals.create.items.observations') }}
                    </label>
                    <textarea
                      v-model="item.obs"
                      rows="2"
                      class="w-full rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm focus:border-[#143d37] focus:outline-none focus:ring-2 focus:ring-[#c79a43]"
                      :placeholder="$t('gestlab.general.labels.vap_proposals.create.items.observations_placeholder')"
                    ></textarea>
                  </div>
                </div>

                <!-- ITEM BREAKDOWN -->
                <div v-if="item.qty && item.unit_price" class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                  <div class="grid grid-cols-2 md:grid-cols-5 gap-2 text-xs">
                    <div class="bg-slate-50 dark:bg-slate-900/70 rounded p-2">
                      <div class="text-slate-500 dark:text-slate-400">Quantidade</div>
                      <div class="font-medium">{{ item.qty }}</div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-900/70 rounded p-2">
                      <div class="text-slate-500 dark:text-slate-400">Preço Unitário</div>
                      <div class="font-medium">AOA {{ formatNumber(item.unit_price) }}</div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-900/70 rounded p-2" v-if="itemsWithSubTotal[index]?.discount_amount > 0">
                      <div class="text-slate-500 dark:text-slate-400">Desconto</div>
                      <div class="font-medium text-green-600">-AOA {{ formatNumber(itemsWithSubTotal[index]?.discount_amount || 0) }}</div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-900/70 rounded p-2" v-if="itemsWithSubTotal[index]?.tax_amount > 0">
                      <div class="text-slate-500 dark:text-slate-400">Taxa ({{ item.tax_percentage }}%)</div>
                      <div class="font-medium text-[#143d37]">AOA {{ formatNumber(itemsWithSubTotal[index]?.tax_amount || 0) }}</div>
                    </div>
                    <div class="bg-[#f7f1e6] rounded p-2">
                      <div class="text-[#263c36] font-medium">{{ $t('gestlab.general.labels.vap_proposals.create.items.item_total') }}</div>
                      <div class="text-[#143d37] dark:text-emerald-200 font-bold">AOA {{ formatNumber(itemsWithSubTotal[index]?.total || 0) }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- TOTALS SUMMARY -->
          <div v-if="form.items.length > 0" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4 bg-slate-50 dark:bg-slate-900/70">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div class="text-sm text-slate-600 dark:text-slate-300">
                {{ form.items.length }} {{ $t('gestlab.general.buttons.items') }}
              </div>
              <div class="text-right">
                <div class="text-sm text-slate-600 dark:text-slate-300">
                  {{ $t('gestlab.general.labels.vap_proposals.create.items.subtotal') }}
                </div>
                <div class="text-lg font-bold text-[#143d37] dark:text-emerald-200">
                  AOA {{ formatNumber(calculateSubtotal) }}
                </div>
              </div>
              <div class="text-right">
                <div class="text-sm text-slate-600 dark:text-slate-300">
                  {{ $t('gestlab.general.labels.vap_proposals.create.items.discount') }}
                </div>
                <div class="text-lg font-bold text-green-600">
                  -AOA {{ formatNumber(discountTotal) }}
                </div>
              </div>
              <div class="text-right">
                <div class="text-sm text-slate-600 dark:text-slate-300">
                  {{ $t('gestlab.general.labels.vap_proposals.create.items.total') }}
                </div>
                <div class="text-2xl font-bold text-[#143d37] dark:text-emerald-200">
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
        <div class="rounded-[30px] border border-[#ded2bb] bg-white/90 p-6 shadow-[0_22px_70px_-46px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90">
          <p class="text-xs font-black uppercase tracking-[0.28em] text-[#c79a43]">{{ $t('gestlab.general.labels.vap_proposals.surface.decision') }}</p>
          <h3 class="mb-4 mt-2 text-2xl font-black tracking-[-0.03em] text-[#10221d] dark:text-white">
            {{ $t('gestlab.general.labels.vap_proposals.actions.title') }}
          </h3>
          <div class="space-y-4">
            <button 
              @click="submit"
              :disabled="form.processing || !isFormValid"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-[20px] px-4 py-3 text-sm font-black shadow-[0_18px_42px_-26px_rgba(20,61,55,0.72)] transition-all duration-200',
                form.processing || !isFormValid
                  ? 'bg-slate-200 dark:bg-slate-800 text-slate-500 dark:text-slate-400 cursor-not-allowed'
                  : 'bg-[#143d37] text-white hover:bg-[#0f302b] focus:outline-none focus:ring-2 focus:ring-[#c79a43] focus:ring-offset-2'
              ]"
            >
              <CheckCircleIcon class="h-5 w-5" />
              {{ form.processing ? $t('actions.processing') : $t('gestlab.general.labels.vap_proposals.create.submit') }}
            </button>
            
            <button 
              @click="saveDraft"
              :disabled="form.processing"
              class="inline-flex w-full items-center justify-center gap-2 rounded-[20px] border border-[#ded2bb] bg-[#fbfaf6] px-4 py-3 text-sm font-black text-[#143d37] shadow-[0_14px_32px_-28px_rgba(20,61,55,0.55)] transition hover:border-[#c79a43] hover:bg-[#fff7e5] focus:outline-none focus:ring-2 focus:ring-[#c79a43] focus:ring-offset-2 dark:border-white/10 dark:bg-white/5 dark:text-emerald-100 dark:hover:bg-white/10"
            >
              <DocumentTextIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_proposals.create.save_draft') }}
            </button>
            
            <!-- QUICK STATS -->
            <div class="border-t border-slate-200 dark:border-slate-800 pt-4">
              <h4 class="text-sm font-medium text-slate-900 dark:text-white mb-2">
                {{ $t('gestlab.general.labels.vap_proposals.stats.title') }}
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.vap_proposals.stats.total_items') }}</span>
                  <span class="font-semibold text-[#143d37] dark:text-emerald-200">{{ form.items.length }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.vap_proposals.create.items.subtotal') }}</span>
                  <span class="font-semibold text-[#143d37] dark:text-emerald-200">AOA {{ formatNumber(calculateSubtotal) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.vap_proposals.create.items.discount') }}</span>
                  <span class="font-semibold text-green-600">-AOA {{ formatNumber(discountTotal) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.vap_proposals.create.tolerance_days') }}</span>
                  <span class="font-semibold text-[#143d37] dark:text-emerald-200">{{ form.tolerance_days || 7 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.vap_proposals.create.items.total') }}</span>
                  <span class="font-semibold text-[#143d37] dark:text-emerald-200">AOA {{ formatNumber(proposalTotal) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- STATUS CARD -->
        <div class="rounded-[30px] border border-[#ded2bb] bg-white/90 p-6 shadow-[0_22px_70px_-46px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90">
          <p class="text-xs font-black uppercase tracking-[0.28em] text-[#c79a43]">{{ $t('gestlab.general.labels.vap_proposals.surface.readiness') }}</p>
          <h3 class="mb-4 mt-2 flex items-center gap-2 text-2xl font-black tracking-[-0.03em] text-[#10221d] dark:text-white">
            <Cog6ToothIcon class="h-6 w-6 text-[#143d37] dark:text-emerald-100" />
            {{ $t('gestlab.general.labels.vap_proposals.status.title') }}
          </h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.vap_proposals.create.basic_info') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                hasBasicInfo ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
              ]">
                {{ hasBasicInfo ? $t('gestlab.general.labels.vap_proposals.status.complete') : $t('gestlab.general.labels.vap_proposals.status.incomplete') }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.vap_proposals.create.items.title') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                hasItems ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
              ]">
                {{ hasItems ? $t('gestlab.general.labels.vap_proposals.status.complete') : $t('gestlab.general.labels.vap_proposals.status.required') }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.vap_proposals.surface.expiry') }}</span>
              <span class="text-sm font-medium text-[#143d37] dark:text-emerald-200">
                {{ calculateExpiryDate }}
              </span>
            </div>
          </div>
        </div>

        <!-- SELECTED TEMPLATE PREVIEW -->
        <div v-if="selectedTemplate" class="rounded-[30px] border border-[#ded2bb] bg-white/90 p-6 shadow-[0_22px_70px_-46px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90">
          <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
            <DocumentTextIcon class="h-5 w-5 text-[#143d37] dark:text-emerald-200" />
            {{ $t('gestlab.general.labels.vap_proposals.create.template_preview') }}
          </h3>
          <div class="space-y-3">
            <div>
              <h4 class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ selectedTemplate.name }}</h4>
              <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                {{ $t('gestlab.general.labels.vap_proposals.show.template.created_by') }} {{ selectedTemplate.user.name }}
              </p>
            </div>
            <div class="mt-4 max-h-32 overflow-y-auto text-sm text-slate-600 line-clamp-4 dark:text-slate-300">
              {{ stripHtml(selectedTemplate.content) }}
            </div>
            <button 
              @click="showTemplatePreview = true"
              class="mt-2 text-xs font-medium text-[#143d37] hover:text-[#0f302b] dark:text-emerald-200 dark:hover:text-emerald-100"
            >
              {{ $t('gestlab.general.labels.vap_proposals.create.view_full_template') }}
            </button>
          </div>
        </div>

        <!-- QUICK ADD ITEMS -->
        <div v-if="quickItems.length" class="rounded-[30px] border border-[#ded2bb] bg-white/90 p-6 shadow-[0_22px_70px_-46px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90">
          <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
            <BoltIcon class="h-5 w-5 text-[#c79a43]" />
            {{ $t('gestlab.general.labels.vap_proposals.create.quick_add') }}
          </h3>
          <div class="space-y-3">
            <button 
              v-for="quickItem in quickItems"
              :key="quickItem.name"
              @click="addQuickItem(quickItem)"
              class="w-full rounded-2xl border border-slate-200 p-3 text-left transition-all duration-200 hover:border-[#d8cbb4] hover:bg-[#f7f1e6] dark:border-slate-800 dark:hover:bg-emerald-400/10"
            >
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ quickItem.name }}</div>
                  <div class="text-xs text-slate-500 dark:text-slate-400">{{ quickItem.description }}</div>
                </div>
                <div class="text-sm font-semibold text-[#143d37] dark:text-emerald-200">AOA {{ formatNumber(quickItem.price) }}</div>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- FOOTER ACTIONS -->
    <div class="flex flex-col gap-4 rounded-[30px] border border-[#ded2bb] bg-[#fbfaf6] p-5 shadow-[0_22px_70px_-48px_rgba(20,61,55,0.42)] dark:border-white/10 dark:bg-slate-950/90 sm:flex-row sm:items-center sm:justify-between">
      <div class="text-sm text-slate-500 dark:text-slate-400">
        {{ $t('gestlab.general.labels.vap_proposals.create.footer_note') }}
      </div>
      <div class="flex items-center gap-4">
        <button 
          @click="resetForm"
          type="button"
          class="inline-flex items-center gap-2 rounded-2xl border border-[#d8cbb4] bg-white px-4 py-2.5 text-sm font-semibold text-[#263c36] shadow-sm hover:bg-[#f7f1e6] focus:outline-none focus:ring-2 focus:ring-[#143d37] focus:ring-offset-2 dark:border-white/10 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
        >
          <ArrowPathIcon class="h-5 w-5" />
          {{ $t('gestlab.general.buttons.reset') }}
        </button>
        <button 
          @click="submit"
          :disabled="form.processing || !isFormValid"
          :class="[
            'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200',
            form.processing || !isFormValid
              ? 'bg-slate-200 dark:bg-slate-800 text-slate-500 dark:text-slate-400 cursor-not-allowed'
              : 'bg-[#143d37] text-white hover:bg-[#0f302b] focus:outline-none focus:ring-2 focus:ring-[#c79a43] focus:ring-offset-2'
          ]"
        >
          <CheckCircleIcon class="h-5 w-5" />
          {{ form.processing ? $t('actions.processing') : $t('gestlab.general.labels.vap_proposals.create.submit') }}
        </button>
      </div>
    </div>

    <!-- TEMPLATE PREVIEW MODAL -->
    <Modal :show="showTemplatePreview" @close="showTemplatePreview = false" max-width="4xl">
      <div class="p-6 dark:bg-slate-950">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
            {{ selectedTemplate?.name }}
          </h2>
          <button @click="showTemplatePreview = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>
        <div class="prose max-w-none rounded-2xl border border-slate-200 p-6 max-h-[60vh] overflow-y-auto dark:border-slate-800 dark:prose-invert">
          <div v-html="selectedTemplate?.content"></div>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { commercialDocumentThemeClasses } from '@/Composables/useCommercialDocumentTheme'
import { useForm, Link } from '@inertiajs/vue3'
import { 
  DocumentPlusIcon, ArrowLeftIcon, InformationCircleIcon,
  UserIcon, BuildingStorefrontIcon, BuildingOfficeIcon,
  DocumentTextIcon, MapPinIcon, ClockIcon, ListBulletIcon,
  PlusCircleIcon, DocumentDuplicateIcon, TrashIcon,
  CheckCircleIcon, Cog6ToothIcon, BoltIcon, ArrowPathIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'
import Modal from '@/Components/modal.vue'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'

const props = defineProps({
  customers: Array,
  warehouses: Array,
  departments: Array,
  templates: Array,
  nextProposalNo: String,
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
  customer_id: null,
  warehouse_id: null,
  department_id: null,
  template_id: null,
  service_location: '',
  tolerance_days: 7,
  withhold_tax: false,
  obs: '',
  items: [],
  use_matrix_price: true,
})

// UI State
const showTemplatePreview = ref(false)
const loadingWarehouses = ref(false)
const loadingParameters = ref(false)
const formErrorMessage = ref('')
const loadErrorMessage = ref('')
const resetConfirmationPending = ref(false)

// Selection refs
const selectedCustomer = ref(null)
const selectedWarehouse = ref(null)
const selectedDepartment = ref(null)
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
    throw new Error(`Resposta inválida (${response.status})`)
  }

  return response.json()
}

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
    loadErrorMessage.value = 'Não foi possível carregar os parâmetros deste código laboratorial.'
  } finally {
    loadingParameters.value = false
  }
}

// Watch for customer changes
watch(selectedCustomer, (newVal) => { 
  form.customer_id = newVal?.value || ''
  
  if (!form.customer_id) {
    selectedWarehouse.value = null
    form.warehouse_id = ''
    return
  }

  loadingWarehouses.value = true
  fetch(route('vap-proposals.options.warehouses', { q: '', customer_id: form.customer_id }))
    .then(parseJsonResponse)
    .then(results => {
      const warehouses = results.map(result => ({
        value: result.id,
        label: result.address,
      }))
      
      selectedWarehouse.value = warehouses[0] || ''
      
      loadingWarehouses.value = false
    })
    .catch(() => {
      selectedWarehouse.value = null
      form.warehouse_id = ''
      loadingWarehouses.value = false
    })
})

watch(selectedWarehouse, (newVal) => form.warehouse_id = newVal?.value || '')
watch(templateSelected, (newVal) => form.template_id = newVal?.value || '')
watch(selectedDepartment, (newVal) => form.department_id = newVal?.value || '')

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

function loadWarehouses(query, setOptions) {
  fetch(route('vap-proposals.options.warehouses', { q: query || '', customer_id: form.customer_id }))
    .then(parseJsonResponse)
    .then(results => {
      setOptions(
        results.map(result => ({
          value: result.id,
          label: result.address,
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
  return form.customer_id && form.warehouse_id && form.department_id && 
         form.template_id && form.service_location
})

const hasItems = computed(() => {
  return form.items.length > 0
})

const isFormValid = computed(() => {
  return hasBasicInfo.value && hasItems.value
})

// UPDATED CALCULATION LOGIC (from old version)
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

// Line calculation methods from old version
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

// Updated computed totals (from old version)
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
  itemToDuplicate.item_description = `${itemToDuplicate.item_description} (Cópia)`
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
    formErrorMessage.value = 'A proposta deve ter pelo menos um item.'
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

const stripHtml = (html) => {
  if (!html) return ''
  const text = html.replace(/<[^>]*>/g, '').trim()

  return text.length > 200 ? `${text.substring(0, 200)}...` : text
}

const resetForm = () => {
  if (!resetConfirmationPending.value) {
    resetConfirmationPending.value = true
    formErrorMessage.value = 'Clique novamente em Repor para descartar as alterações deste formulário.'
    window.setTimeout(() => {
      resetConfirmationPending.value = false
    }, 5000)

    return
  }

  resetConfirmationPending.value = false
  formErrorMessage.value = ''
  loadErrorMessage.value = ''
  form.reset()
  form.items = []
  form.tolerance_days = 7
  form.use_matrix_price = true
  labcode_id.value = null
  selectedCustomer.value = null
  selectedWarehouse.value = null
  selectedDepartment.value = null
  templateSelected.value = null
}

const saveDraft = () => {
  formErrorMessage.value = 'O modo rascunho ficará disponível quando existir persistência dedicada para propostas incompletas.'
}

const submit = () => {
  formErrorMessage.value = ''

  if (!isFormValid.value) {
    formErrorMessage.value = 'Preencha os campos obrigatórios e adicione pelo menos um item.'
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
    .post(route('vap-proposals.store'), {
      preserveScroll: true,
      onSuccess: () => {
        // Redirecionamento tratado pelo controller
      },
      onError: () => {
        formErrorMessage.value = 'Não foi possível guardar a proposta. Revise os campos assinalados.'
      }
    })
}

// Initialize with one empty item
addItem()
</script>

<style scoped>
.v-motion {
  transition: all 0.3s ease;
}

.line-clamp-4 {
  display: -webkit-box;
  -webkit-line-clamp: 4;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.proposal-editor-shell :deep(input:not([type='checkbox'])),
.proposal-editor-shell :deep(select),
.proposal-editor-shell :deep(textarea) {
  background: rgba(255, 255, 255, 0.98);
  color: #18231f;
  border-color: #d8cbb4;
  box-shadow: 0 14px 30px -28px rgba(20, 61, 55, 0.55);
}

.proposal-editor-shell :deep(input::placeholder),
.proposal-editor-shell :deep(textarea::placeholder) {
  color: #7a867f;
}

:global(.dark) .proposal-editor-shell :deep(input:not([type='checkbox'])),
:global(.dark) .proposal-editor-shell :deep(select),
:global(.dark) .proposal-editor-shell :deep(textarea) {
  background: rgba(15, 23, 42, 0.92);
  color: #f8fafc;
  border-color: rgba(255, 255, 255, 0.12);
}

:global(.dark) .proposal-editor-shell :deep(input::placeholder),
:global(.dark) .proposal-editor-shell :deep(textarea::placeholder) {
  color: #94a3b8;
}
</style>
