<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <BuildingOfficeIcon class="h-7 w-7 text-blue-900" />
            {{ props.record.data?.name }}
            <span v-if="props.record.data?.code" class="text-lg font-normal text-blue-900 bg-blue-50 px-3 py-1 rounded-full ml-2">
              {{ props.record.data?.code }}
            </span>
          </h1>
          <div class="mt-2 flex items-center gap-4 text-sm text-gray-600">
            <span class="flex items-center gap-1">
              <UserCircleIcon class="h-4 w-4" />
              {{ props.record.data?.customer || $t('gestlab.general.labels.warehouses.no_customer') }}
            </span>
            <span v-if="props.record.data?.is_primary" class="flex items-center gap-1 text-green-600">
              <StarIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.warehouses.primary_warehouse') }}
            </span>
            <span v-if="hasPassword" class="flex items-center gap-1 text-green-600">
              <KeyIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.warehouses.password_set') }}
            </span>
          </div>
        </div>
        <div class="flex items-center gap-4">
          <Link
            v-if="hasPermission('edit_warehouses')"
            as="button" 
            :href="route('warehouses.edit', { warehouse: props.record.data?.id })"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <PencilSquareIcon class="h-5 w-5" />
            {{ $t('gestlab.general.buttons.edit') }}
        </Link>
          <Link
            as="button" 
            :href="route('customers.show', { customer: props.record.data?.customer_id })"
            v-if="props.record.data?.customer_id && hasPermission('view_customers')"
            class="inline-flex items-center gap-2 rounded-lg border border-blue-900 px-4 py-2.5 text-sm font-semibold text-blue-900 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <ArrowLeftIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.warehouses.back_to_customer') }}
        </Link>
        </div>
      </div>
    </div>

    <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
      <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">Saúde da conta</h2>
            <p class="mt-1 text-sm text-gray-500">
              Leitura rápida entre faturação e pedidos respondidos deste armazém.
            </p>
          </div>
          <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Itens monitorizados</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ accountHealthTotal }}</p>
          </div>
        </div>

        <div class="mt-6">
          <apexchart type="bar" height="300" :options="accountHealthChartOptions" :series="accountHealthChartSeries" />
        </div>
      </article>

      <div class="grid gap-6">
        <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">Execução operacional</h2>
              <p class="mt-1 text-sm text-gray-500">Colheitas e certificados ligados ao armazém.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-800">
              {{ operationsTotal }} registos
            </span>
          </div>

          <div class="mt-6">
            <apexchart type="donut" height="300" :options="operationsChartOptions" :series="operationsChartSeries" />
          </div>
        </article>

        <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">Mapa documental</h2>
              <p class="mt-1 text-sm text-gray-500">Distribuição dos principais documentos comerciais e regulatórios.</p>
            </div>
          </div>

          <div class="mt-6">
            <apexchart type="bar" height="250" :options="documentsChartOptions" :series="documentsChartSeries" />
          </div>
        </article>
      </div>
    </section>

    <!-- MAIN CONTENT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- WAREHOUSE DETAILS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <InformationCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.warehouses.details') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- CONTACT INFORMATION -->
              <div class="space-y-6">
                <div>
                  <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <PhoneIcon class="h-4 w-4 text-blue-900" />
                    {{ $t('gestlab.general.labels.warehouses.contact_info') }}
                  </h3>
                  <div class="space-y-3">
                    <div v-if="props.record.data?.email" class="flex items-start gap-2">
                      <EnvelopeIcon class="h-5 w-5 text-gray-400 mt-0.5 flex-shrink-0" />
                      <div>
                        <p class="text-sm font-medium text-gray-900">{{ props.record.data?.email }}</p>
                        <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.warehouses.email') }}</p>
                      </div>
                    </div>
                    <div v-if="props.record.data?.invoicing_email" class="flex items-start gap-2">
                      <EnvelopeIcon class="h-5 w-5 text-gray-400 mt-0.5 flex-shrink-0" />
                      <div>
                        <p class="text-sm font-medium text-gray-900">{{ props.record.data?.invoicing_email }}</p>
                        <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.warehouses.invoicing_email') }}</p>
                      </div>
                    </div>
                    <div v-if="props.record.data?.primary_phone" class="flex items-start gap-2">
                      <PhoneIcon class="h-5 w-5 text-gray-400 mt-0.5 flex-shrink-0" />
                      <div>
                        <p class="text-sm font-medium text-gray-900">{{ props.record.data?.primary_phone }}</p>
                        <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.warehouses.primary_phone') }}</p>
                      </div>
                    </div>
                    <div v-if="props.record.data?.alternative_phone" class="flex items-start gap-2">
                      <PhoneIcon class="h-5 w-5 text-gray-400 mt-0.5 flex-shrink-0" />
                      <div>
                        <p class="text-sm font-medium text-gray-900">{{ props.record.data?.alternative_phone }}</p>
                        <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.warehouses.alternative_phone') }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- FOCAL POINT -->
                <div v-if="props.record.data?.focal_point">
                  <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <UserCircleIcon class="h-4 w-4 text-blue-900" />
                    {{ $t('gestlab.general.labels.warehouses.focal_point') }}
                  </h3>
                  <div class="space-y-3 bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                      <p class="text-sm font-medium text-gray-900">{{ props.record.data?.focal_point }}</p>
                    </div>
                    <div v-if="props.record.data?.focal_point_contact" class="flex items-center gap-2">
                      <PhoneIcon class="h-4 w-4 text-gray-400" />
                      <p class="text-sm text-gray-700">{{ props.record.data?.focal_point_contact }}</p>
                    </div>
                    <div v-if="props.record.data?.focal_point_email" class="flex items-center gap-2">
                      <EnvelopeIcon class="h-4 w-4 text-gray-400" />
                      <p class="text-sm text-blue-900">{{ props.record.data?.focal_point_email }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- LOCATION INFORMATION -->
              <div class="space-y-6">
                <div>
                  <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <MapPinIcon class="h-4 w-4 text-blue-900" />
                    {{ $t('gestlab.general.labels.warehouses.location') }}
                  </h3>
                  <div class="space-y-3">
                    <div v-if="props.record.data?.address" class="flex items-start gap-2">
                      <MapPinIcon class="h-5 w-5 text-gray-400 mt-0.5 flex-shrink-0" />
                      <div>
                        <p class="text-sm font-medium text-gray-900">{{ props.record.data?.address }}</p>
                        <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.warehouses.address') }}</p>
                      </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                      <div v-if="props.record.data?.municipality" class="bg-gray-50 rounded-lg p-3">
                        <p class="text-sm font-medium text-gray-900">{{ props.record.data?.municipality }}</p>
                        <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.warehouses.municipality') }}</p>
                      </div>
                      <div v-if="props.record.data?.province" class="bg-gray-50 rounded-lg p-3">
                        <p class="text-sm font-medium text-gray-900">{{ props.record.data?.province }}</p>
                        <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.warehouses.province') }}</p>
                      </div>
                    </div>
                    <div v-if="props.record.data?.nif" class="bg-gray-50 rounded-lg p-3">
                      <p class="text-sm font-medium text-gray-900">{{ props.record.data?.nif }}</p>
                      <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.warehouses.nif') }}</p>
                    </div>
                  </div>
                </div>

                <!-- DESCRIPTION -->
                <div v-if="props.record.data?.description">
                  <h3 class="text-sm font-semibold text-gray-900 mb-3">
                    {{ $t('gestlab.general.labels.warehouses.description') }}
                  </h3>
                  <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-700 leading-relaxed">{{ props.record.data?.description }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- PASSWORD MANAGEMENT CARD (Only for admins) -->
        <div v-if="hasPermission('edit_warehouses')" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <KeyIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.warehouses.password_management') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <!-- PASSWORD STATUS -->
            <div class="mb-6 p-4 rounded-lg" :class="hasPassword ? 'bg-green-50 border border-green-200' : 'bg-yellow-50 border border-yellow-200'">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div :class="[
                    'flex h-8 w-8 items-center justify-center rounded-full',
                    hasPassword ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                  ]">
                    <KeyIcon class="h-4 w-4" />
                  </div>
                  <div>
                    <h3 class="text-sm font-semibold" :class="hasPassword ? 'text-green-900' : 'text-yellow-900'">
                      {{ hasPassword ? $t('gestlab.general.labels.warehouses.password_set') : $t('gestlab.general.labels.warehouses.password_not_set') }}
                    </h3>
                    <p class="text-xs mt-1" :class="hasPassword ? 'text-green-700' : 'text-yellow-700'">
                      {{ hasPassword ? $t('gestlab.general.labels.warehouses.password_last_updated') : $t('gestlab.general.labels.warehouses.set_password_info') }}
                    </p>
                  </div>
                </div>
                <button 
                  @click="showPasswordForm = !showPasswordForm"
                  class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-colors duration-200"
                  :class="showPasswordForm 
                    ? 'bg-blue-900 text-white hover:bg-blue-800' 
                    : hasPassword 
                      ? 'bg-white text-blue-900 border border-blue-900 hover:bg-blue-50'
                      : 'bg-blue-900 text-white hover:bg-blue-800'"
                >
                  <KeyIcon class="h-4 w-4" />
                  {{ showPasswordForm 
                    ? $t('gestlab.general.buttons.cancel') 
                    : hasPassword 
                      ? $t('gestlab.general.labels.warehouses.change_password')
                      : $t('gestlab.general.labels.warehouses.set_password') }}
                </button>
              </div>
            </div>

            <!-- PASSWORD UPDATE FORM -->
            <div v-if="showPasswordForm" class="border border-gray-200 rounded-lg p-6 bg-gray-50">
              <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">
                {{ hasPassword ? $t('gestlab.general.labels.warehouses.change_password') : $t('gestlab.general.labels.warehouses.set_new_password') }}
              </h3>
              
              <form @submit.prevent="updatePassword" class="space-y-4">
                <!-- New Password -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700 dark:text-slate-200">
                    {{ $t('gestlab.general.labels.warehouses.new_password') }}
                    <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <input
                      v-model="passwordForm.password"
                      :type="showNewPassword ? 'text' : 'password'"
                      :class="[
                        'block w-full rounded-lg border-0 py-2.5 px-3 text-gray-900 dark:text-slate-100 dark:bg-slate-950 shadow-sm ring-1 ring-inset placeholder:text-gray-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-inset sm:text-sm transition-colors duration-200 pr-10',
                        passwordForm.errors.password
                          ? 'ring-red-300 focus:ring-red-500 bg-red-50'
                          : 'ring-gray-300 dark:ring-slate-700 focus:ring-blue-900'
                      ]"
                      :placeholder="$t('gestlab.general.labels.warehouses.placeholders.new_password')"
                    />
                    <button
                      type="button"
                      @click="showNewPassword = !showNewPassword"
                      class="absolute inset-y-0 right-0 pr-3 flex items-center"
                    >
                      <EyeIcon v-if="showNewPassword" class="h-5 w-5 text-gray-400" />
                      <EyeSlashIcon v-else class="h-5 w-5 text-gray-400" />
                    </button>
                  </div>
                  <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div class="flex items-center gap-2">
                      <CheckCircleIcon 
                        :class="[
                          'h-4 w-4',
                          passwordStrength.length ? 'text-green-500' : 'text-gray-300'
                        ]" 
                      />
                      <span class="text-xs text-gray-600 dark:text-slate-300">{{ $t('gestlab.general.labels.warehouses.min_8_chars') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <CheckCircleIcon 
                        :class="[
                          'h-4 w-4',
                          passwordStrength.mixed ? 'text-green-500' : 'text-gray-300'
                        ]" 
                      />
                      <span class="text-xs text-gray-600 dark:text-slate-300">{{ $t('gestlab.general.labels.warehouses.mixed_case') }}</span>
                    </div>
                  </div>
                  <p v-if="passwordForm.errors.password" class="text-xs text-red-600 mt-2">
                    {{ passwordForm.errors.password }}
                  </p>
                </div>

                <!-- Confirm Password -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700 dark:text-slate-200">
                    {{ $t('gestlab.general.labels.warehouses.confirm_password') }}
                    <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <input
                      v-model="passwordForm.password_confirmation"
                      :type="showConfirmPassword ? 'text' : 'password'"
                      :class="[
                        'block w-full rounded-lg border-0 py-2.5 px-3 text-gray-900 dark:text-slate-100 dark:bg-slate-950 shadow-sm ring-1 ring-inset placeholder:text-gray-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-inset sm:text-sm transition-colors duration-200 pr-10',
                        passwordForm.errors.password_confirmation
                          ? 'ring-red-300 focus:ring-red-500 bg-red-50'
                          : 'ring-gray-300 dark:ring-slate-700 focus:ring-blue-900'
                      ]"
                      :placeholder="$t('gestlab.general.labels.warehouses.placeholders.confirm_password')"
                    />
                    <button
                      type="button"
                      @click="showConfirmPassword = !showConfirmPassword"
                      class="absolute inset-y-0 right-0 pr-3 flex items-center"
                    >
                      <EyeIcon v-if="showConfirmPassword" class="h-5 w-5 text-gray-400" />
                      <EyeSlashIcon v-else class="h-5 w-5 text-gray-400" />
                    </button>
                  </div>
                  <p v-if="passwordForm.errors.password_confirmation" class="text-xs text-red-600">
                    {{ passwordForm.errors.password_confirmation }}
                  </p>
                </div>

                <!-- Password Strength Indicator -->
                <div v-if="passwordForm.password" class="space-y-2">
                  <div class="flex justify-between items-center">
                    <span class="text-xs font-medium text-gray-700 dark:text-slate-300">{{ $t('gestlab.general.labels.warehouses.password_strength') }}</span>
                    <span class="text-xs font-medium" :class="passwordStrengthClass">
                      {{ passwordStrengthLabel }}
                    </span>
                  </div>
                  <div class="h-2 bg-gray-200 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div 
                      class="h-full transition-all duration-300"
                      :class="passwordStrengthBarClass"
                      :style="{ width: passwordStrengthPercentage + '%' }"
                    ></div>
                  </div>
                </div>

                <!-- ACTION BUTTONS -->
                <div class="flex items-center justify-end gap-3 pt-4">
                  <button
                    type="button"
                    @click="cancelPasswordUpdate"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 transition-colors duration-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:hover:bg-slate-900"
                  >
                    {{ $t('gestlab.general.buttons.cancel') }}
                  </button>
                  <button
                    type="submit"
                    :disabled="passwordForm.processing || !isPasswordFormValid"
                    :class="[
                      'rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2',
                      passwordForm.processing || !isPasswordFormValid
                        ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                        : 'bg-blue-900 text-white hover:bg-blue-800 focus:ring-blue-900'
                    ]"
                  >
                    <ArrowPathIcon v-if="passwordForm.processing" class="h-4 w-4 animate-spin inline mr-2" />
                    {{ passwordForm.processing 
                      ? $t('gestlab.general.buttons.updating') 
                      : hasPassword 
                        ? $t('gestlab.general.labels.warehouses.update_password')
                        : $t('gestlab.general.labels.warehouses.set_password') }}
                  </button>
                </div>
              </form>
            </div>

            <!-- SEND PASSWORD RESET EMAIL -->
            <div v-if="hasPassword && !showPasswordForm" class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <EnvelopeIcon class="h-5 w-5 text-blue-900" />
                  <div>
                    <h3 class="text-sm font-semibold text-blue-900">
                      {{ $t('gestlab.general.labels.warehouses.send_password_reset') }}
                    </h3>
                    <p class="text-xs text-blue-700 mt-1">
                      {{ $t('gestlab.general.labels.warehouses.send_reset_email_info') }}
                    </p>
                  </div>
                </div>
                <button 
                  @click="sendPasswordResetEmail"
                  :disabled="resetEmailSending"
                  :class="[
                    'inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-colors duration-200',
                    resetEmailSending
                      ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                      : 'bg-white text-blue-900 border border-blue-900 hover:bg-blue-50'
                  ]"
                >
                  <ArrowPathIcon v-if="resetEmailSending" class="h-4 w-4 animate-spin" />
                  <EnvelopeIcon v-else class="h-4 w-4" />
                  {{ resetEmailSending 
                    ? $t('gestlab.general.labels.warehouses.sending') 
                    : $t('gestlab.general.labels.warehouses.send_reset_email') }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ... Rest of the existing statistics and activity sections ... -->
        <!-- STATISTICS CARDS -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <!-- ... existing statistics cards ... -->

          <!-- INVOICES STATS -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:border-blue-900 transition-colors duration-200">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.warehouses.invoices') }}</p>
                <p class="text-2xl font-bold text-blue-900 mt-1">{{ stats.invoices.total || 0 }}</p>
              </div>
              <DocumentTextIcon class="h-8 w-8 text-blue-900" />
            </div>
            <div class="mt-4 space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">{{ $t('gestlab.general.status.paid') }}</span>
                <span class="font-medium text-green-600">{{ stats.invoices.paid || 0 }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">{{ $t('gestlab.general.status.pending') }}</span>
                <span class="font-medium text-yellow-600">{{ stats.invoices.pending || 0 }}</span>
              </div>
            </div>
          </div>


          <!-- COLLECTIONS STATS -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:border-blue-900 transition-colors duration-200">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.warehouses.collections') }}</p>
                <p class="text-2xl font-bold text-blue-900 mt-1">{{ stats.collections.total || 0 }}</p>
              </div>
              <ArchiveBoxIcon class="h-8 w-8 text-blue-900" />
            </div>
            <div class="mt-4 space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">{{ $t('gestlab.general.status.processed') }}</span>
                <span class="font-medium text-green-600">{{ stats.collections.processed || 0 }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">{{ $t('gestlab.general.status.pending') }}</span>
                <span class="font-medium text-yellow-600">{{ stats.collections.pending || 0 }}</span>
              </div>
            </div>
          </div>


          <!-- QUALITY CERTIFICATES -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:border-blue-900 transition-colors duration-200">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.warehouses.quality_certificates') }}</p>
                <p class="text-2xl font-bold text-blue-900 mt-1">{{ stats.quality_certificates.total || 0 }}</p>
              </div>
              <DocumentCheckIcon class="h-8 w-8 text-blue-900" />
            </div>
            <div class="mt-4">
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">{{ $t('gestlab.general.status.validated') }}</span>
                <span class="font-medium text-green-600">{{ stats.quality_certificates.validated || 0 }}</span>
              </div>
            </div>
          </div>


          <!-- REQUESTS STATS -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:border-blue-900 transition-colors duration-200">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.warehouses.requests') }}</p>
                <p class="text-2xl font-bold text-blue-900 mt-1">{{ stats.requests.total || 0 }}</p>
              </div>
              <ChatBubbleLeftRightIcon class="h-8 w-8 text-blue-900" />
            </div>
            <div class="mt-4 space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">{{ $t('gestlab.general.status.answered') }}</span>
                <span class="font-medium text-green-600">{{ stats.requests.answered || 0 }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">{{ $t('gestlab.general.status.pending') }}</span>
                <span class="font-medium text-yellow-600">{{ stats.requests.pending || 0 }}</span>
              </div>
            </div>
          </div>
        


        </div>

        <!-- RECENT ACTIVITY -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- ... existing recent activity ... -->

          <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <ClockIcon class="h-5 w-5 text-blue-900" />
              {{ $t('gestlab.general.labels.warehouses.recent_activity') }}
            </h2>
          </div>
          <div class="p-6">
            <div v-if="recentActivity.length === 0" class="text-center py-8">
              <p class="text-sm text-gray-500">{{ $t('gestlab.general.labels.warehouses.no_recent_activity') }}</p>
            </div>
            <div v-else class="space-y-4">
                <div v-for="activity in recentActivity" :key="activity.id" class="flex items-start gap-3 p-3 hover:bg-gray-50 rounded-lg transition-colors duration-200">
                  <div :class="[
                    'flex h-8 w-8 items-center justify-center rounded-lg',
                    activity.type === 'invoice' ? 'bg-blue-100 text-blue-900' :
                    activity.type === 'collection' ? 'bg-green-100 text-green-900' :
                    activity.type === 'certificate' ? 'bg-purple-100 text-purple-900' :
                    'bg-gray-100 text-gray-900'
                  ]">
                  <component :is="resolveActivityIcon(activity.icon)" class="h-4 w-4" />
                </div>
                <div class="flex-1">
                  <p class="text-sm font-medium text-gray-900">{{ activity.title }}</p>
                  <p class="text-xs text-gray-500 mt-1">{{ activity.description }}</p>
                </div>
                <div class="text-xs text-gray-400">{{ activity.time }}</div>
              </div>
            </div>
          </div>


        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6">
        <!-- ... existing right column sections ... -->
        <!-- QUICK LINKS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <!-- ... existing quick links ... -->

          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.warehouses.quick_links') }}
          </h3>
          <div class="space-y-3">
            <Link
              v-if="hasPermission('view_invoices')"
               
              :href="route('invoices.index', { warehouse: props.record.data?.id })"
              class="flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:border-blue-900 hover:bg-blue-50 transition-colors duration-200 group"
            >
              <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100">
                  <DocumentTextIcon class="h-4 w-4 text-blue-900" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900 group-hover:text-blue-900">
                    {{ $t('gestlab.general.labels.warehouses.invoices') }}
                  </p>
                  <p class="text-xs text-gray-500">{{ stats.invoices.total || 0 }} {{ $t('gestlab.general.labels.warehouses.items') }}</p>
                </div>
              </div>
              <ChevronRightIcon class="h-5 w-5 text-gray-400 group-hover:text-blue-900" />
            </Link>

            <Link
              v-if="hasPermission('view_direct_collections')"
               
              :href="route('directcollections.index', { warehouse: props.record.data?.id })"
              class="flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:border-blue-900 hover:bg-blue-50 transition-colors duration-200 group"
            >
              <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-100">
                  <ArchiveBoxIcon class="h-4 w-4 text-green-900" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900 group-hover:text-blue-900">
                    {{ $t('gestlab.general.labels.warehouses.collections') }}
                  </p>
                  <p class="text-xs text-gray-500">{{ stats.collections.total || 0 }} {{ $t('gestlab.general.labels.warehouses.items') }}</p>
                </div>
              </div>
              <ChevronRightIcon class="h-5 w-5 text-gray-400 group-hover:text-blue-900" />
            </Link>

            <Link
              v-if="hasPermission('view_quality_certificates')"
               
              :href="route('qualitycertificates.index', { warehouse: props.record.data?.id })"
              class="flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:border-blue-900 hover:bg-blue-50 transition-colors duration-200 group"
            >
              <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-100">
                  <DocumentCheckIcon class="h-4 w-4 text-purple-900" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900 group-hover:text-blue-900">
                    {{ $t('gestlab.general.labels.warehouses.quality_certificates') }}
                  </p>
                  <p class="text-xs text-gray-500">{{ stats.quality_certificates.total || 0 }} {{ $t('gestlab.general.labels.warehouses.items') }}</p>
                </div>
              </div>
              <ChevronRightIcon class="h-5 w-5 text-gray-400 group-hover:text-blue-900" />
            </Link>

            <Link
              v-if="hasPermission('view_contract_guides')"
               
              :href="route('contractguides.index', { warehouse: props.record.data?.id })"
              class="flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:border-blue-900 hover:bg-blue-50 transition-colors duration-200 group"
            >
              <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-yellow-100">
                  <DocumentArrowUpIcon class="h-4 w-4 text-yellow-900" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900 group-hover:text-blue-900">
                    {{ $t('gestlab.general.labels.warehouses.contract_guides') }}
                  </p>
                  <p class="text-xs text-gray-500">{{ stats.contract_guides.total || 0 }} {{ $t('gestlab.general.labels.warehouses.items') }}</p>
                </div>
              </div>
              <ChevronRightIcon class="h-5 w-5 text-gray-400 group-hover:text-blue-900" />
            </Link>
          </div>
        </div>

        <!-- FINANCIAL SUMMARY -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <CurrencyDollarIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.warehouses.financial_summary') }}
          </h3>
          <div class="space-y-4">
            <div class="bg-blue-50 rounded-lg p-4">
              <p class="text-sm font-medium text-blue-900">{{ $t('gestlab.general.labels.warehouses.total_revenue') }}</p>
              <p class="text-2xl font-bold text-blue-900 mt-1">{{ formatCurrency(stats.financial.total_revenue || 0) }}</p>
            </div>
            <div class="space-y-3">
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.warehouses.paid_amount') }}</span>
                <span class="text-sm font-medium text-green-600">{{ formatCurrency(stats.financial.paid || 0) }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.warehouses.pending_amount') }}</span>
                <span class="text-sm font-medium text-yellow-600">{{ formatCurrency(stats.financial.pending || 0) }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.warehouses.credit_notes') }}</span>
                <span class="text-sm font-medium text-red-600">{{ formatCurrency(stats.financial.credit_notes || 0) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- IMPORT/EXPORT SUMMARY -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.warehouses.import_export') }}
          </h3>
          <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="bg-green-50 rounded-lg p-4">
                <p class="text-sm font-medium text-green-900">{{ $t('gestlab.general.labels.warehouses.imports') }}</p>
                <p class="text-xl font-bold text-green-900 mt-1">{{ stats.imports.total || 0 }}</p>
              </div>
              <div class="bg-blue-50 rounded-lg p-4">
                <p class="text-sm font-medium text-blue-900">{{ $t('gestlab.general.labels.warehouses.exports') }}</p>
                <p class="text-xl font-bold text-blue-900 mt-1">{{ stats.exports.total || 0 }}</p>
              </div>
            </div>
            <div class="space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.warehouses.invoiced_certificates') }}</span>
                <span class="font-medium text-green-600">{{ stats.imports.invoiced || 0 }} / {{ stats.exports.invoiced || 0 }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.warehouses.total_value') }}</span>
                <span class="font-medium text-blue-900">{{ formatCurrency(stats.imports.value || 0) }} / {{ formatCurrency(stats.exports.value || 0) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- DOCUMENT STATUS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.warehouses.document_status') }}
          </h3>
          <div class="space-y-4">
            <div v-for="docType in documentStatus" :key="docType.type" class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div :class="[
                  'flex h-8 w-8 items-center justify-center rounded-lg',
                  docType.color
                ]">
                  <component :is="docType.icon" class="h-4 w-4 text-white" />
                </div>
                <span class="text-sm font-medium text-gray-900">{{ docType.label }}</span>
              </div>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                docType.count > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
              ]">
                {{ docType.count }}
              </span>
            </div>
          </div>
        </div>

        <!-- METADATA -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <Cog6ToothIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.warehouses.metadata') }}
          </h3>
          <div class="space-y-3">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.warehouses.created_at') }}</span>
              <span class="font-medium text-gray-900">{{ formatDate(props.record.data?.created_at) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.warehouses.updated_at') }}</span>
              <span class="font-medium text-gray-900">{{ formatDate(props.record.data?.updated_at) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.warehouses.customer_id') }}</span>
              <span class="font-medium text-blue-900">{{ props.record.data?.customer || '—' }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.warehouses.status') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                props.record.data?.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
              ]">
                {{ props.record.data?.status || 'active' }}
              </span>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref, computed, onMounted, reactive } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { 
  BuildingOfficeIcon,
  UserCircleIcon,
  PhoneIcon,
  MapPinIcon,
  EnvelopeIcon,
  PencilSquareIcon,
  StarIcon,
  ArrowLeftIcon,
  InformationCircleIcon,
  DocumentTextIcon,
  ArchiveBoxIcon,
  DocumentCheckIcon,
  ChatBubbleLeftRightIcon,
  ClockIcon,
  ChevronRightIcon,
  CurrencyDollarIcon,
  DocumentArrowUpIcon,
  Cog6ToothIcon,
  CreditCardIcon,
  ReceiptPercentIcon,
  ChatBubbleLeftIcon,
  GlobeAltIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
  KeyIcon,
  EyeIcon,
  EyeSlashIcon,
  ArrowPathIcon
} from "@heroicons/vue/24/outline";
import { usePermission } from '@/Composables/usePermissions'

const { hasRole, hasPermission } = usePermission();

defineOptions({
  layout: Layout
});

const props = defineProps({
  record: Object,
  stats: {
    type: Object,
    default: () => ({
      invoices: { total: 0, paid: 0, pending: 0 },
      collections: { total: 0, processed: 0, pending: 0 },
      quality_certificates: { total: 0, validated: 0 },
      requests: { total: 0, answered: 0, pending: 0 },
      quotes: { total: 0 },
      receipts: { total: 0 },
      credit_notes: { total: 0 },
      contract_guides: { total: 0 },
      imports: { total: 0, invoiced: 0, value: 0 },
      exports: { total: 0, invoiced: 0, value: 0 },
      financial: { total_revenue: 0, paid: 0, pending: 0, credit_notes: 0 }
    })
  },
  recentActivity: {
    type: Array,
    default: () => []
  },
  charts: {
    type: Object,
    default: () => ({})
  }
});

// Password management
const showPasswordForm = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);
const resetEmailSending = ref(false);

// Check if warehouse has a password set
const hasPassword = computed(() => {
  return !!props.record.data?.has_password;
});

// Password form
const passwordForm = useForm({
  password: '',
  password_confirmation: '',
});

// Password strength calculator
const passwordStrength = computed(() => {
  const password = passwordForm.password;
  if (!password) return { score: 0, length: false, mixed: false, numbers: false, special: false };
  
  const length = password.length >= 8;
  const mixed = /[a-z]/.test(password) && /[A-Z]/.test(password);
  const numbers = /\d/.test(password);
  const special = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
  
  let score = 0;
  if (length) score++;
  if (mixed) score++;
  if (numbers) score++;
  if (special) score++;
  
  return { score, length, mixed, numbers, special };
});

const passwordStrengthPercentage = computed(() => {
  return (passwordStrength.value.score / 4) * 100;
});

const passwordStrengthLabel = computed(() => {
  const score = passwordStrength.value.score;
  if (score === 0) return 'Muito Fraca';
  if (score === 1) return 'Fraca';
  if (score === 2) return 'Normal';
  if (score === 3) return 'Boa';
  return 'Forte';
});

const passwordStrengthClass = computed(() => {
  const score = passwordStrength.value.score;
  if (score === 0) return 'text-red-600';
  if (score === 1) return 'text-red-500';
  if (score === 2) return 'text-yellow-500';
  if (score === 3) return 'text-green-500';
  return 'text-green-600';
});

const passwordStrengthBarClass = computed(() => {
  const score = passwordStrength.value.score;
  if (score === 0) return 'bg-red-500';
  if (score === 1) return 'bg-red-400';
  if (score === 2) return 'bg-yellow-400';
  if (score === 3) return 'bg-green-400';
  return 'bg-green-500';
});

const isPasswordFormValid = computed(() => {
  if (!passwordForm.password || passwordForm.password.length < 8) return false;
  if (passwordForm.password !== passwordForm.password_confirmation) return false;
  if (passwordStrength.value.score < 2) return false; // Require at least "Fair" strength
  return true;
});

// Password update function
const updatePassword = () => {
  if (!isPasswordFormValid.value) return;
  
  passwordForm.put(route('warehouses.setpass', { warehouse: props.record.data?.id }), {
    preserveScroll: true,
    onSuccess: () => {
      showPasswordForm.value = false;
      passwordForm.reset();
      // Show success message (you could use a toast notification here)
    },
    onError: (errors) => {
      // Errors are automatically handled by the form
    }
  });
};

const cancelPasswordUpdate = () => {
  showPasswordForm.value = false;
  passwordForm.reset();
  showNewPassword.value = false;
  showConfirmPassword.value = false;
};

const sendPasswordResetEmail = () => {
  if (resetEmailSending.value) return;
  
  resetEmailSending.value = true;
  
  router.post(route('warehouses.send-password-reset', { warehouse: props.record.data?.id }), {}, {
    preserveScroll: true,
    onSuccess: () => {
      resetEmailSending.value = false;
      // Show success message
    },
    onError: () => {
      resetEmailSending.value = false;
      // Show error message
    }
  });
};

// ... rest of the existing functions (formatDate, formatCurrency, etc.)

// Format date for display
const formatDate = (dateString) => {
  if (!dateString) return '—';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

// Format currency
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'AOA'
  }).format(amount);
};

const accountHealthChartSeries = computed(() => [
  {
    name: 'Registos',
    data: props.charts?.account_health?.series || [],
  }
]);

const accountHealthTotal = computed(() => accountHealthChartSeries.value[0]?.data?.reduce((sum, value) => sum + value, 0) || 0);

const operationsChartSeries = computed(() => props.charts?.operations?.series || []);
const operationsTotal = computed(() => operationsChartSeries.value.reduce((sum, value) => sum + value, 0));

const documentsChartSeries = computed(() => [
  {
    name: 'Documentos',
    data: props.charts?.documents?.series || [],
  }
]);

const accountHealthChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  colors: ['#1d4ed8'],
  plotOptions: {
    bar: {
      borderRadius: 10,
      columnWidth: '50%',
      distributed: true,
    },
  },
  dataLabels: { enabled: false },
  grid: {
    borderColor: '#e5e7eb',
    strokeDashArray: 4,
  },
  xaxis: {
    categories: props.charts?.account_health?.labels || [],
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: {
      style: { colors: '#6b7280' },
    },
  },
  yaxis: {
    min: 0,
    forceNiceScale: true,
    labels: {
      style: { colors: '#6b7280' },
    },
  },
  colors: ['#10b981', '#f59e0b', '#0ea5e9', '#ef4444'],
  legend: { show: false },
}));

const operationsChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  labels: props.charts?.operations?.labels || [],
  colors: ['#16a34a', '#f59e0b', '#7c3aed', '#c084fc'],
  stroke: {
    colors: ['#ffffff'],
  },
  legend: {
    position: 'bottom',
    labels: { colors: '#334155' },
  },
  dataLabels: {
    enabled: true,
    formatter: (value) => `${Math.round(value)}%`,
  },
  plotOptions: {
    pie: {
      donut: {
        size: '68%',
        labels: {
          show: true,
          total: {
            show: true,
            label: 'Fluxo',
            formatter: () => `${operationsTotal.value}`,
          },
        },
      },
    },
  },
}));

const documentsChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  plotOptions: {
    bar: {
      horizontal: true,
      borderRadius: 8,
      barHeight: '58%',
      distributed: true,
    },
  },
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.charts?.documents?.labels || [],
    labels: {
      style: { colors: '#6b7280' },
    },
  },
  grid: {
    borderColor: '#e5e7eb',
    strokeDashArray: 4,
  },
  colors: ['#1d4ed8', '#16a34a', '#dc2626', '#d97706', '#0f766e', '#7c3aed'],
  legend: { show: false },
}));

const resolveActivityIcon = (icon) => ({
  invoice: DocumentTextIcon,
  collection: ArchiveBoxIcon,
  certificate: DocumentCheckIcon,
  request: ChatBubbleLeftRightIcon,
}[icon] || ClockIcon);

// Document status summary
const documentStatus = computed(() => [
  {
    type: 'quotes',
    label: 'Proformas',
    icon: ChatBubbleLeftIcon,
    count: props.stats.quotes?.total || 0,
    color: 'bg-blue-900'
  },
  {
    type: 'receipts',
    label: 'Recibos',
    icon: ReceiptPercentIcon,
    count: props.stats.receipts?.total || 0,
    color: 'bg-green-900'
  },
  {
    type: 'credit_notes',
    label: 'Notas de Crédito',
    icon: CreditCardIcon,
    count: props.stats.credit_notes?.total || 0,
    color: 'bg-red-900'
  },
  {
    type: 'import_export',
    label: 'Fitos de Import/Export',
    icon: GlobeAltIcon,
    count: (props.stats.imports?.total || 0) + (props.stats.exports?.total || 0),
    color: 'bg-purple-900'
  }
]);

onMounted(() => {
  // You could fetch additional data here if needed
});
</script>
