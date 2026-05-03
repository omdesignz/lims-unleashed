<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="overflow-hidden rounded-[28px] border border-slate-200/80 bg-white/95 p-6 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.28)] backdrop-blur dark:border-slate-800 dark:bg-slate-950/85">
      <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <div class="mb-2 flex flex-wrap items-center gap-4">
            <h1 class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-white">
              <DocumentTextIcon class="h-7 w-7 text-blue-900 dark:text-blue-300" />
              {{ $t('gestlab.general.labels.direct_collections.page_title') }}
            </h1>
            <span :class="[
              'inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold', 
              getStatusBadgeColor(props.record.data?.sample_status || 'pending')
            ]">
              {{ getStatusLabel(props.record.data?.sample_status || 'pending') }}
            </span>
          </div>
          <p class="text-sm text-slate-600 dark:text-slate-300">
            {{ $t('gestlab.general.labels.direct_collections.page_show_description') }}
            <span class="font-semibold text-blue-900 dark:text-blue-300">{{ props.record.data?.cl }}</span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <Link
            :href="route('directcollections.edit', { collection: props.record.data?.id })"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <PencilIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.direct_collections.edit') }}
          </Link>
          <button
            @click="router.reload" 
            as="button"
            class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-yellow-500 to-yellow-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-yellow-600 hover:to-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition-colors duration-200"
          >
            <ArrowPathRoundedSquareIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.direct_collections.update_status') }}
        </button>
          <Link
            :href="route('directcollections.index')"
            class="inline-flex items-center gap-2 rounded-lg bg-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200"
          >
            <ArrowLeftIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.direct_collections.back') }}
          </Link>
        </div>
      </div>
    </div>

    <!-- ANALYSIS PROGRESS TRACKER -->
    <div class="overflow-hidden rounded-[26px] border border-slate-200 bg-white/95 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
      <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
        <h2 class="text-lg font-semibold text-white flex items-center gap-2">
          <ClipboardDocumentCheckIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.direct_collections.analysis_progress') }}
        </h2>
      </div>
      
      <div class="p-6">
        <!-- ANALYSIS TIMELINE -->
        <div class="mb-8">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-sm font-semibold text-gray-900">{{ $t('gestlab.general.labels.direct_collections.analysis_timeline') }}</h3>
            <div class="flex items-center gap-4 text-sm text-gray-600">
              <div class="flex items-center gap-2">
                <div class="h-2 w-2 rounded-full bg-blue-900"></div>
                <span>{{ $t('gestlab.general.labels.direct_collections.planned') }}</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="h-2 w-2 rounded-full bg-green-500"></div>
                <span>{{ $t('gestlab.general.labels.direct_collections.completed') }}</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="h-2 w-2 rounded-full bg-yellow-500"></div>
                <span>{{ $t('gestlab.general.labels.direct_collections.in_progress') }}</span>
              </div>
            </div>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- COLLECTION STEP -->
            <div class="relative">
              <div class="flex flex-col items-center">
                <div :class="[
                  'h-12 w-12 rounded-full flex items-center justify-center mb-2 border-2',
                  isStepComplete('collection') ? 'bg-green-100 border-green-500 text-green-700' : 'bg-blue-50 border-blue-200 text-blue-900'
                ]">
                  <CircleStackIcon class="h-6 w-6" />
                </div>
                <span class="text-xs font-medium text-gray-900">{{ $t('gestlab.general.labels.direct_collections.collection') }}</span> 
                <span class="text-xs text-gray-500">{{ formatDate(props.record.data?.collection_date) }}</span>
              </div>
              <div class="absolute top-6 left-1/2 transform -translate-x-1/2 h-0.5 w-full bg-gray-200 -z-10"></div>
            </div>

            <!-- RECEPTION STEP -->
            <div class="relative">
              <div class="flex flex-col items-center">
                <div :class="[
                  'h-12 w-12 rounded-full flex items-center justify-center mb-2 border-2',
                  isStepComplete('reception') ? 'bg-green-100 border-green-500 text-green-700' : 'bg-blue-50 border-blue-200 text-blue-900'
                ]">
                  <InboxIcon class="h-6 w-6" />
                </div>
                <span class="text-xs font-medium text-gray-900">{{ $t('gestlab.general.labels.direct_collections.reception') }}</span>
                <span class="text-xs text-gray-500">{{ formatDate(props.record.data?.created_at) }}</span>
              </div>
            </div>

            <!-- ANALYSIS STEP -->
            <div class="relative">
              <div class="flex flex-col items-center">
                <div :class="[
                  'h-12 w-12 rounded-full flex items-center justify-center mb-2 border-2',
                  isStepComplete('analysis') ? 'bg-green-100 border-green-500 text-green-700' : 
                  props.record.data?.placed_analysis ? 'bg-yellow-100 border-yellow-500 text-yellow-700' : 'bg-blue-50 border-blue-200 text-blue-900'
                ]">
                  <BeakerIcon class="h-6 w-6" />
                </div>
                <span class="text-xs font-medium text-gray-900">{{ $t('gestlab.general.labels.direct_collections.analysis') }}</span>
                <span v-if="props.record.data?.analysis_start_date" class="text-xs text-gray-500">
                  {{ formatDate(props.record.data?.analysis_start_date) }}
                </span>
              </div>
            </div>

            <!-- VERIFICATION STEP -->
            <div class="relative">
              <div class="flex flex-col items-center">
                <div :class="[
                  'h-12 w-12 rounded-full flex items-center justify-center mb-2 border-2',
                  isStepComplete('verification') ? 'bg-green-100 border-green-500 text-green-700' : 'bg-blue-50 border-blue-200 text-blue-900'
                ]">
                  <CheckCircleIcon class="h-6 w-6" />
                </div>
                <span class="text-xs font-medium text-gray-900">{{ $t('gestlab.general.labels.direct_collections.verification') }}</span>
                <span v-if="props.record.data?.verified_date" class="text-xs text-gray-500">
                  {{ formatDate(props.record.data?.verified_date) }}
                </span>
              </div>
            </div>

            <!-- APPROVAL STEP -->
            <div class="relative">
              <div class="flex flex-col items-center">
                <div :class="[
                  'h-12 w-12 rounded-full flex items-center justify-center mb-2 border-2',
                  isStepComplete('approval') ? 'bg-green-100 border-green-500 text-green-700' : 'bg-blue-50 border-blue-200 text-blue-900'
                ]">
                  <DocumentCheckIcon class="h-6 w-6" />
                </div>
                <span class="text-xs font-medium text-gray-900">{{ $t('gestlab.general.labels.direct_collections.approval') }}</span>
                <span v-if="props.record.data?.approved_date" class="text-xs text-gray-500">
                  {{ formatDate(props.record.data?.approved_date) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- ANALYSIS SUMMARY -->
        <div class="rounded-[24px] border border-blue-100 bg-gradient-to-r from-blue-50 to-white p-4 dark:border-blue-500/20 dark:from-blue-500/10 dark:to-slate-950/60">
          <h4 class="mb-3 text-sm font-semibold text-slate-900 dark:text-white">{{ $t('gestlab.general.labels.direct_collections.analysis_summary') }}</h4>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center">
              <div class="text-2xl font-bold text-blue-900">{{ props.record.data?.total_samples || 0 }}</div>
              <div class="text-xs text-gray-600">{{ $t('gestlab.general.labels.direct_collections.total_samples') }}</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-green-600">{{ props.record.data?.completed_analysis || 0 }}</div>
              <div class="text-xs text-gray-600">{{ $t('gestlab.general.labels.direct_collections.completed') }}</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-yellow-600">{{ props.record.data?.in_progress_analysis || 0 }}</div>
              <div class="text-xs text-gray-600">{{ $t('gestlab.general.labels.direct_collections.in_progress') }}</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-blue-900">{{ props.record.data?.pending_analysis || 0 }}</div>
              <div class="text-xs text-gray-600">{{ $t('gestlab.general.labels.direct_collections.pending') }}</div>
            </div>
          </div>
        </div>

        <div
          v-if="props.record.data?.scope_control?.required_parameter_count || props.record.data?.scope_control?.conditioning_status"
          class="mt-6 rounded-lg border border-amber-200 bg-amber-50/70 p-4"
        >
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <h4 class="text-sm font-semibold text-gray-900">Escopo controlado da receção</h4>
              <p class="mt-1 text-xs text-gray-600">
                Esta colheita herda o planeamento analítico e o estado de condicionamento definidos no momento da receção.
              </p>
            </div>
            <div class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-200">
              {{ props.record.data?.scope_control?.required_parameter_count || 0 }} parâmetros previstos
            </div>
          </div>

          <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div class="rounded-lg border border-white/70 bg-white/80 p-3">
              <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Condicionamento</p>
              <p class="mt-2 text-sm font-medium text-gray-900">
                {{ getConditioningLabel(props.record.data?.scope_control?.conditioning_status) }}
              </p>
              <p v-if="props.record.data?.scope_control?.packaging_condition" class="mt-2 text-xs text-gray-600">
                Embalagem: {{ props.record.data.scope_control.packaging_condition }}
              </p>
              <p v-if="props.record.data?.scope_control?.temperature_condition" class="mt-1 text-xs text-gray-600">
                Temperatura: {{ props.record.data.scope_control.temperature_condition }}
              </p>
            </div>

            <div class="rounded-lg border border-white/70 bg-white/80 p-3">
              <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Perfis resolvidos</p>
              <div v-if="props.record.data?.scope_control?.resolved_profiles?.length" class="mt-2 flex flex-wrap gap-2">
                <span
                  v-for="profile in props.record.data.scope_control.resolved_profiles"
                  :key="profile.id"
                  class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-900"
                >
                  {{ profile.name }}
                </span>
              </div>
              <p v-else class="mt-2 text-xs text-gray-500">
                Nenhum perfil resolvido foi registado para esta colheita.
              </p>
            </div>
          </div>

          <div v-if="props.record.data?.scope_control?.required_parameters?.length" class="mt-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Checklist de parâmetros</p>
            <div class="mt-2 grid gap-2 md:grid-cols-2 xl:grid-cols-3">
              <div
                v-for="parameter in props.record.data.scope_control.required_parameters"
                :key="parameter.id"
                class="rounded-lg border border-white/70 bg-white/80 px-3 py-2"
              >
                <p class="text-sm font-medium text-gray-900">
                  {{ parameter.code || 'N/D' }} · {{ parameter.name }}
                </p>
                <p class="mt-1 text-xs text-gray-600">
                  {{ parameter.profiles?.join(', ') }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- SAMPLE DETAILS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <DocumentTextIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.direct_collections.sample_details') }}
            </h2>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- QR CODE -->
              <div class="md:col-span-2 lg:col-span-1 space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.direct_collections.qr_code') }}
                </label>
                <div class="bg-white p-3 rounded-lg border border-gray-200 flex justify-center">
                  <img :src="props.record.data?.qr" alt="QR Code" class="w-32 h-32" />
                </div>
              </div>

              <!-- BASIC INFO -->
              <div class="md:col-span-2 lg:col-span-2 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.direct_collections.cl') }}
                    </label>
                    <div class="text-sm text-gray-900 font-medium bg-gray-50 rounded-lg p-3 border border-gray-200">
                      {{ props.record.data?.cl }}
                    </div>
                  </div>
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.direct_collections.type') }}
                    </label>
                    <div class="text-sm text-gray-900 font-medium bg-gray-50 rounded-lg p-3 border border-gray-200">
                      {{ props.record.data?.type }}
                    </div>
                  </div>
                </div>

                <!-- STATUS INDICATORS -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.direct_collections.placed_analysis') }}
                    </label>
                    <div class="flex items-center">
                      <StatusToggle :value="props.record.data?.placed_analysis" />
                      <span class="ml-2 text-sm text-gray-600">
                        {{ props.record.data?.placed_analysis ? 'Em análise' : 'Aguardando' }}
                      </span>
                    </div>
                  </div>
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.direct_collections.invoiced') }}
                    </label>
                    <div class="flex items-center">
                      <StatusToggle :value="props.record.data?.invoiced" />
                      <span class="ml-2 text-sm text-gray-600">
                        {{ props.record.data?.invoiced ? 'Facturado' : 'Por facturar' }}
                      </span>
                    </div>
                  </div>
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.direct_collections.processed') }}
                    </label>
                    <div class="flex items-center">
                      <StatusToggle :value="props.record.data?.processed" />
                      <span class="ml-2 text-sm text-gray-600">
                        {{ props.record.data?.processed ? 'Processado' : 'Por processar' }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- COLLECTION DETAILS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <CircleStackIcon class="h-5 w-5 text-blue-900" />
              {{ $t('gestlab.general.labels.direct_collections.collection_details') }}
            </h2>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- TIMING INFO -->
              <div class="space-y-4">
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.direct_collections.collection_date') }}
                  </label>
                  <div class="flex items-center text-sm text-gray-900 font-medium bg-blue-50 rounded-lg p-3 border border-blue-200">
                    <CalendarIcon class="h-4 w-4 mr-2 text-blue-900" />
                    {{ formatDate(props.record.data?.collection_date) }}
                  </div>
                </div>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.direct_collections.expiry_date') }}
                  </label>
                  <div class="flex items-center text-sm text-gray-900 font-medium bg-red-50 rounded-lg p-3 border border-red-200">
                    <CalendarIcon class="h-4 w-4 mr-2 text-red-600" />
                    {{ formatDate(props.record.data?.expiry_date) }}
                  </div>
                </div>
              </div>

              <!-- PRODUCT INFO -->
              <div class="space-y-4">
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.direct_collections.product') }}
                  </label>
                  <div class="text-sm text-gray-900 font-medium bg-gray-50 rounded-lg p-3 border border-gray-200">
                    {{ props.record.data?.product }}
                  </div>
                </div>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.direct_collections.commercial_brand') }}
                  </label>
                  <div class="text-sm text-gray-900 font-medium bg-gray-50 rounded-lg p-3 border border-gray-200">
                    {{ props.record.data?.comercial_brand || '-' }}
                  </div>
                </div>
              </div>

              <!-- QUANTITY INFO -->
              <div class="space-y-4">
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.direct_collections.qty') }}
                  </label>
                  <div class="grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                      <div class="text-xs text-gray-500">{{ $t('gestlab.general.labels.direct_collections.qty') }}</div>
                      <div class="text-sm font-semibold text-gray-900">{{ props.record.data?.qty || '-' }}</div>
                    </div>
                    <div class="bg-green-50 rounded-lg p-3 border border-green-200">
                      <div class="text-xs text-gray-500">{{ $t('gestlab.general.labels.direct_collections.collected_qty') }}</div>
                      <div class="text-sm font-semibold text-green-800">{{ props.record.data?.collected_qty || '-' }}</div>
                    </div>
                  </div>
                </div>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.direct_collections.temperature_id') }}
                  </label>
                  <div class="flex items-center text-sm text-gray-900 font-medium bg-blue-50 rounded-lg p-3 border border-blue-200">
                    <EyeDropperIcon class="h-4 w-4 mr-2 text-blue-900" />
                    {{ props.record.data?.temperature_value || '-' }} {{ props.record.data?.temperature || '°C' }}
                  </div>
                </div>
              </div>
            </div>

            <!-- ADDITIONAL DETAILS -->
            <div class="mt-6 pt-6 border-t border-gray-200">
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.direct_collections.location') }}
                  </label>
                  <div class="text-sm text-gray-900 font-medium">{{ props.record.data?.location || '-' }}</div>
                </div>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.direct_collections.vehicle_id') }}
                  </label>
                  <div class="text-sm text-gray-900 font-medium">{{ props.record.data?.vehicle || '-' }}</div>
                </div>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.direct_collections.pack_id') }}
                  </label>
                  <div class="text-sm text-gray-900 font-medium">{{ props.record.data?.pack || '-' }}</div>
                </div>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.direct_collections.lot') }}
                  </label>
                  <div class="text-sm text-gray-900 font-medium">{{ props.record.data?.lot || '-' }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ANALYSIS RESULTS SECTION -->
        <div v-if="props.record.data?.analysis_results && hasRole('admin')" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <BeakerIcon class="h-5 w-5 text-blue-900" />
              {{ $t('gestlab.general.labels.direct_collections.analysis_results') }}
              <span class="text-sm font-normal text-gray-500 ml-2">
                ({{ props.record.data?.analysis_results?.length || 0 }} resultados)
              </span>
            </h2>
          </div>
          
          <div class="p-6">
            <div v-for="(result, index) in props.record.data?.analysis_results" :key="index" class="mb-4 last:mb-0">
              <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="flex items-center justify-between mb-3">
                  <div class="flex items-center gap-3">
                    <span class="text-sm font-semibold text-gray-900">{{ result.parameter_label }}</span>
                    <span :class="[
                      'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                      getResultStatusColor(result.status)
                    ]">
                      {{ getResultStatusLabel(result.status) }}
                    </span>
                  </div>
                  <div class="text-sm text-gray-600">{{ result.unit_label }}</div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div class="space-y-2">
                    <div class="text-xs text-gray-500">{{ $t('gestlab.general.labels.direct_collections.result_value') }}</div>
                    <div class="text-lg font-bold text-blue-900">{{ result.verified_value || result.inserted_value || '-' }}</div>
                  </div>
                  <div class="space-y-2">
                    <div class="text-xs text-gray-500">{{ $t('gestlab.general.labels.direct_collections.reference_range') }}</div>
                    <div class="text-sm font-medium text-gray-900">
                      {{ result.min_ref_value || '-' }} - {{ result.max_ref_value || '-' }}
                    </div>
                  </div>
                  <div class="space-y-2">
                    <div class="text-xs text-gray-500">{{ $t('gestlab.general.labels.direct_collections.last_update') }}</div>
                    <div class="text-sm text-gray-600">{{ formatDate(result.updated_at) }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6">
        <!-- QUICK ACTIONS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.actions') }}
          </h3>

          <div class="space-y-3 border-b border-gray-200 pb-4" v-for="sample in props.record.data?.samples || []" :key="sample.id">
            <h4 class="text-sm font-semibold text-gray-900 mb-2">Resultados: {{ sample?.analysis?.department?.name }}</h4>
            <div class="mb-2">
              <Link
                :href="route('analysis.edit', sample.analysis.id)" 
                as="button"
                class="w-full inline-flex justify-center mb-2 items-center gap-2 rounded-lg bg-gradient-to-r from-green-600 to-green-700 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 transition-colors duration-200"
                
              >
                  <CheckCircleIcon class="h-5 w-5" />
                  <!-- {{ $t('gestlab.general.labels.direct_collections.verify_results') }} -->
                  Gerir Resultados  
              </Link>
              <!-- <Link 
                  :href="route('analysis.edit', sample.analysis.id)" 
                  as="button"
                  class="w-full inline-flex justify-center mb-2 items-center gap-2 rounded-lg bg-gradient-to-r from-purple-600 to-purple-700 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:from-purple-700 hover:to-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2 transition-colors duration-200"
                  
                >
                    <DocumentCheckIcon class="h-5 w-5" />
                    {{ $t('gestlab.general.labels.direct_collections.approve_results') }}
              </Link> -->
            </div>

          </div>

          <!-- <div class="space-y-3">
            <button 
              type="button"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
              @click="startAnalysis"
            >
              <PlayIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.direct_collections.start_analysis') }}
            </button>
            <button 
              type="button"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 transition-colors duration-200"
              @click="generateReport"
            >
              <DocumentArrowDownIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.direct_collections.generate_report') }}
            </button>
            <button 
              type="button"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-gradient-to-r from-green-600 to-green-700 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 transition-colors duration-200"
              @click="verifyResults"
            >
              <CheckCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.direct_collections.verify_results') }}
            </button>
            <button 
              type="button"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-gradient-to-r from-purple-600 to-purple-700 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:from-purple-700 hover:to-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2 transition-colors duration-200"
              @click="approveResults"
            >
              <DocumentCheckIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.direct_collections.approve_results') }}
            </button>
          </div> -->

        </div>

        <!-- ANALYSIS STATUS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ChartBarIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.direct_collections.analysis_status') }}
          </h3>
          <div class="space-y-4">
            <!-- PROGRESS BAR -->
            <div>
              <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.direct_collections.completion') }}</span>
                <span class="font-semibold text-blue-900">{{ getCompletionPercentage() }}%</span>
              </div>
              <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                <div 
                  class="h-full bg-gradient-to-r from-blue-900 to-blue-800 transition-all duration-500"
                  :style="{ width: getCompletionPercentage() + '%' }"
                ></div>
              </div>
            </div>

            <!-- STATUS BREAKDOWN -->
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="h-2 w-2 rounded-full bg-blue-900"></div>
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.direct_collections.pending') }}</span>
                </div>
                <span class="text-sm font-semibold text-blue-900">{{ props.record.data?.pending_analysis || 0 }}</span>
              </div>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="h-2 w-2 rounded-full bg-yellow-500"></div>
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.direct_collections.in_progress') }}</span>
                </div>
                <span class="text-sm font-semibold text-yellow-600">{{ props.record.data?.in_progress_analysis || 0 }}</span>
              </div>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="h-2 w-2 rounded-full bg-green-500"></div>
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.direct_collections.completed') }}</span>
                </div>
                <span class="text-sm font-semibold text-green-600">{{ props.record.data?.completed_analysis || 0 }}</span>
              </div>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="h-2 w-2 rounded-full bg-red-500"></div>
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.direct_collections.critical') }}</span>
                </div>
                <span class="text-sm font-semibold text-red-600">{{ props.record.data?.critical_analysis || 0 }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- QUALITY CERTIFICATES -->
        <div v-if="props.record.data?.quality_certificate && hasPermission('view_quality_certificates')" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <DocumentCheckIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.direct_collections.quality_certificate') }}
          </h3>
          <div class="space-y-3">
            <div class="bg-blue-50 rounded-lg p-3 border border-blue-200">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-semibold text-blue-900">{{ props.record.data?.quality_certificate.code }}</span>
                <span :class="[
                  'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                  props.record.data?.quality_certificate.status ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                ]">
                  {{ props.record.data?.quality_certificate.validated_by_id ? 'Validado' : 'Pendente' }}
                </span>
              </div>
              <div class="text-xs text-gray-600">
                {{ $t('gestlab.general.labels.direct_collections.validated_by') }}: {{ props.record.data?.quality_certificate.validated_by || '-' }}
              </div>
              <div class="text-xs text-gray-600">
                {{ $t('gestlab.general.labels.direct_collections.validated_at') }}: {{ formatDate(props.record.data?.quality_certificate.validated_at) }}
              </div>
            </div>
          </div>
        </div>

        <!-- DOCUMENTS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <PaperClipIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.direct_collections.documents') }}
          </h3>
          <div class="space-y-3">
            <a 
              v-if="props.record.data?.links?.pdf_path"
              :href="props.record.data.links.pdf_path"
              target="_blank"
              class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition-colors duration-200 group"
            >
              <div class="flex items-center gap-3">
                <DocumentIcon class="h-5 w-5 text-gray-400 group-hover:text-blue-900" />
                <span class="text-sm font-medium text-gray-900">{{ $t('gestlab.general.labels.direct_collections.work_sheet') }}</span>
              </div>
              <ArrowTopRightOnSquareIcon class="h-4 w-4 text-gray-400 group-hover:text-blue-900" />
            </a>
            <a 
              v-if="props.record.data?.links?.pdf_collection_labels"
              :href="props.record.data.links.pdf_collection_labels"
              target="_blank"
              class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition-colors duration-200 group"
            >
              <div class="flex items-center gap-3">
                <TagIcon class="h-5 w-5 text-gray-400 group-hover:text-blue-900" />
                <span class="text-sm font-medium text-gray-900">{{ $t('gestlab.general.labels.direct_collections.labels') }}</span>
              </div>
              <ArrowTopRightOnSquareIcon class="h-4 w-4 text-gray-400 group-hover:text-blue-900" />
            </a>
            <a 
              v-if="props.record.data?.links?.pdf_collection_term"
              :href="props.record.data.links.pdf_collection_term"
              target="_blank"
              class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition-colors duration-200 group"
            >
              <div class="flex items-center gap-3">
                <DocumentTextIcon class="h-5 w-5 text-gray-400 group-hover:text-blue-900" />
                <span class="text-sm font-medium text-gray-900">{{ $t('gestlab.general.labels.direct_collections.collection_term') }}</span>
              </div>
              <ArrowTopRightOnSquareIcon class="h-4 w-4 text-gray-400 group-hover:text-blue-900" />
            </a>
            <a 
              v-if="props.record.data?.links?.pdf_quality_certificate && hasPermission('view_quality_certificates')"
              :href="props.record.data.links.pdf_quality_certificate"
              target="_blank"
              class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition-colors duration-200 group"
            >
              <div class="flex items-center gap-3">
                <Square2StackIcon class="h-5 w-5 text-gray-400 group-hover:text-blue-900" />
                <span class="text-sm font-medium text-gray-900">{{ $t('gestlab.general.labels.direct_collections.quality_certificate') }}</span>
              </div>
              <ArrowTopRightOnSquareIcon class="h-4 w-4 text-gray-400 group-hover:text-blue-900" />
            </a>

          </div>
        </div>
      </div>
    </div>

    <!-- SAMPLE STATUS COMPONENT -->
    <!-- <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="border-b border-gray-200 pb-4 mb-4">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
          <ChartBarIcon class="h-5 w-5 text-blue-900" />
          {{ $t('gestlab.general.labels.direct_collections.sample_evolution') }}
        </h3>
      </div>
      <sampleStatus :collection-id="props.record.data?.id" />
    </div> -->

  </div>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { usePermission } from "@/Composables/usePermissions";
import { Link, router } from "@inertiajs/vue3";
import sampleStatus from '@/Components/sample-status.vue';
import {
  ArrowLeftIcon,
  ArrowPathRoundedSquareIcon,
  ArrowTopRightOnSquareIcon,
  BeakerIcon,
  CalendarIcon,
  ChartBarIcon,
  CheckCircleIcon,
  ClipboardDocumentCheckIcon,
  CircleStackIcon,
  DocumentArrowDownIcon,
  DocumentCheckIcon,
  DocumentIcon,
  DocumentTextIcon,
  InboxIcon,
  PaperClipIcon,
  PencilIcon,
  PlayIcon,
  TagIcon,
  EyeDropperIcon,
  TrashIcon,
  Square2StackIcon
} from "@heroicons/vue/24/outline";

const { hasRole, hasPermission } = usePermission();
const props = defineProps({
  record: Object,
});

defineOptions({
  layout: Layout
});

const getStatusBadgeColor = (status) => {
  const statusColors = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'in_progress': 'bg-blue-100 text-blue-800',
    'completed': 'bg-green-100 text-green-800',
    'verified': 'bg-purple-100 text-purple-800',
    'approved': 'bg-indigo-100 text-indigo-800',
    'rejected': 'bg-red-100 text-red-800',
    'cancelled': 'bg-gray-100 text-gray-800'
  };
  return statusColors[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
  const statusLabels = {
    'pending': 'Pendente',
    'in_progress': 'Em Análise',
    'completed': 'Completo',
    'verified': 'Verificado',
    'approved': 'Aprovado',
    'rejected': 'Rejeitado',
    'cancelled': 'Cancelado'
  };
  return statusLabels[status] || status;
};

const getResultStatusColor = (status) => {
  const statusColors = {
    0: 'bg-gray-100 text-gray-800', // pending
    1: 'bg-yellow-100 text-yellow-800', // in progress
    2: 'bg-green-100 text-green-800', // completed
    3: 'bg-blue-100 text-blue-800', // verified
    4: 'bg-purple-100 text-purple-800', // approved
    5: 'bg-red-100 text-red-800' // rejected
  };
  return statusColors[status] || 'bg-gray-100 text-gray-800';
};

const getResultStatusLabel = (status) => {
  const statusLabels = {
    0: 'Pendente',
    1: 'Em Progresso',
    2: 'Completo',
    3: 'Verificado',
    4: 'Aprovado',
    5: 'Rejeitado'
  };
  return statusLabels[status] || 'Desconhecido';
};

const getConditioningLabel = (status) => {
  const labels = {
    accepted: 'Aceite',
    restricted: 'Aceite com restrições',
    rejected: 'Rejeitado / quarentena',
  }

  return labels[status] || 'Não avaliado'
}

const isStepComplete = (step) => {
  switch (step) {
    case 'collection':
      return !!props.record.data?.collection_date;
    case 'reception':
      return !!props.record.data?.created_at;
    case 'analysis':
      return props.record.data?.placed_analysis && props.record.data?.analysis_start_date;
    case 'verification':
      return !!props.record.data?.verified_date;
    case 'approval':
      return !!props.record.data?.approved_date;
    default:
      return false;
  }
};

const getCompletionPercentage = () => {
  const steps = ['collection', 'reception', 'analysis', 'verification', 'approval'];
  const completedSteps = steps.filter(step => isStepComplete(step)).length;
  return Math.round((completedSteps / steps.length) * 100);
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const startAnalysis = () => {
  return;
};

const generateReport = () => {
  return;
};

const verifyResults = () => {
  return;
};

const approveResults = () => {
  return;
};
</script>

<script>
// StatusToggle Component
const StatusToggle = {
  props: ['value'],
  template: `
    <button type="button" class="relative inline-flex h-6 w-11 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2" :class="value ? 'bg-blue-900' : 'bg-gray-200'">
      <span class="sr-only">Toggle status</span>
      <span class="translate-x-0 pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="value ? 'translate-x-5' : 'translate-x-0'">
        <span class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" :class="value ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in'">
          <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 12 12">
            <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </span>
        <span class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" :class="value ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out'">
          <svg class="h-3 w-3 text-blue-900" fill="currentColor" viewBox="0 0 12 12">
            <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
          </svg>
        </span>
      </span>
    </button>
  `
};
</script>
