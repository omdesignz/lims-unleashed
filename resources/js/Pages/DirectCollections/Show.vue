<template>
  <div class="direct-collection-show space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="overflow-hidden rounded-[28px] border border-slate-200/80 bg-white/95 p-6 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.28)] backdrop-blur dark:border-slate-800 dark:bg-slate-950/85">
      <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <div class="mb-2 flex flex-wrap items-center gap-4">
            <h1 class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-white">
              <DocumentTextIcon class="h-7 w-7 text-primary-900 dark:text-primary-300" />
              {{ collectionTitle }}
            </h1>
            <span :class="[
              'inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold', 
              getStatusBadgeColor(props.record.data?.sample_status || 'pending')
            ]">
              {{ getStatusLabel(props.record.data?.sample_status || 'pending') }}
            </span>
          </div>
          <p class="text-sm text-slate-600 dark:text-slate-300">
            {{ collectionDescription }}
            <span v-if="props.record.data?.cl" class="font-semibold text-primary-900 dark:text-primary-300">{{ props.record.data.cl }}</span>
          </p>
        </div>
        <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center">
          <Link
            :href="collectionEditUrl"
            class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-400 sm:w-auto"
          >
            <PencilIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.direct_collections.edit') }}
          </Link>
          <button
            @click="router.reload" 
            as="button"
            class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-900 shadow-sm transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:border-amber-500/25 dark:bg-amber-500/10 dark:text-amber-200 dark:hover:bg-amber-500/15 sm:w-auto"
          >
            <ArrowPathRoundedSquareIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.direct_collections.update_status') }}
          </button>
          <Link
            :href="collectionIndexUrl"
            class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 sm:w-auto"
          >
            <ArrowLeftIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.direct_collections.back') }}
          </Link>
        </div>
      </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-[minmax(0,1.45fr),minmax(280px,0.75fr)]">
      <section class="rounded-[26px] border border-primary-100 bg-gradient-to-br from-white via-primary-50/70 to-white p-5 shadow-[0_18px_50px_-26px_rgba(15,23,42,0.25)] dark:border-primary-500/20 dark:from-slate-950 dark:via-primary-500/10 dark:to-slate-900/80">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-primary-700 dark:text-primary-300">
              Entrada canónica do processo
            </p>
            <h2 class="mt-2 text-xl font-semibold text-slate-900 dark:text-white">
              {{ sampleEntry ? (sampleEntry.code || `Sample Entry #${sampleEntry.id}`) : 'Registo legado de colheita' }}
            </h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-600 dark:text-slate-300">
              {{ sampleEntry
                ? 'Esta colheita nasceu da receção da amostra; produto, matriz, escopo analítico e códigos laboratoriais ficam rastreáveis a partir da Sample Entry.'
                : 'Este registo ainda foi criado pela rota antiga de colheitas. Novos fluxos devem começar pela Sample Entry para preservar rastreabilidade ponta a ponta.' }}
            </p>
          </div>

          <Link
            v-if="sampleEntry"
            :href="sampleEntry.show_url"
            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-400"
          >
            <ArrowTopRightOnSquareIcon class="size-4" aria-hidden="true" />
            Abrir Sample Entry
          </Link>
        </div>
      </section>

      <section class="rounded-[26px] border border-slate-200 bg-white/95 p-5 shadow-[0_18px_50px_-26px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
          Etapa atual
        </p>
        <div class="mt-3 flex items-start gap-3">
          <div class="rounded-2xl bg-primary-50 p-3 text-primary-900 dark:bg-primary-500/10 dark:text-primary-200">
            <CircleStackIcon class="size-6" aria-hidden="true" />
          </div>
          <div>
            <p class="text-base font-semibold text-slate-900 dark:text-white">{{ collectionTitle }}</p>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
              {{ props.record.data?.entry_origin?.label || 'Etapa operacional ligada ao código laboratorial' }}
            </p>
          </div>
        </div>
      </section>
    </div>

    <!-- ANALYSIS PROGRESS TRACKER -->
    <div class="overflow-hidden rounded-[26px] border border-slate-200 bg-white/95 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
      <div class="bg-gradient-to-r from-primary-900 to-primary-700 px-6 py-4 dark:from-slate-950 dark:to-primary-950">
        <h2 class="text-lg font-semibold text-white flex items-center gap-2">
          <ClipboardDocumentCheckIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.direct_collections.analysis_progress') }}
        </h2>
      </div>
      
      <div class="p-6">
        <!-- ANALYSIS TIMELINE -->
        <div class="mb-8">
          <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $t('gestlab.general.labels.direct_collections.analysis_timeline') }}</h3>
            <div class="flex flex-wrap items-center gap-4 text-sm text-slate-600 dark:text-slate-300">
              <div class="flex items-center gap-2">
                <div class="h-2 w-2 rounded-full bg-primary-800 dark:bg-primary-300"></div>
                <span>{{ $t('gestlab.general.labels.direct_collections.planned') }}</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="h-2 w-2 rounded-full bg-emerald-500"></div>
                <span>{{ $t('gestlab.general.labels.direct_collections.completed') }}</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="h-2 w-2 rounded-full bg-amber-500"></div>
                <span>{{ $t('gestlab.general.labels.direct_collections.in_progress') }}</span>
              </div>
            </div>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- COLLECTION STEP -->
            <div class="relative">
              <div class="flex flex-col items-center">
                <div :class="[
                  'mb-2 flex h-12 w-12 items-center justify-center rounded-full border-2 shadow-sm',
                  isStepComplete('collection') ? 'border-green-500 bg-green-100 text-green-700 dark:border-green-400 dark:bg-green-500/10 dark:text-green-200' : 'border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-400/40 dark:bg-blue-500/10 dark:text-blue-200'
                ]">
                  <CircleStackIcon class="h-6 w-6" />
                </div>
                <span class="text-xs font-medium text-slate-900 dark:text-white">{{ $t('gestlab.general.labels.direct_collections.collection') }}</span>
                <span class="text-xs text-slate-500 dark:text-slate-400">{{ formatDate(props.record.data?.collection_date) }}</span>
              </div>
              <div class="absolute left-1/2 top-6 -z-10 h-0.5 w-full -translate-x-1/2 bg-slate-200 dark:bg-slate-800"></div>
            </div>

            <!-- RECEPTION STEP -->
            <div class="relative">
              <div class="flex flex-col items-center">
                <div :class="[
                  'mb-2 flex h-12 w-12 items-center justify-center rounded-full border-2 shadow-sm',
                  isStepComplete('reception') ? 'border-green-500 bg-green-100 text-green-700 dark:border-green-400 dark:bg-green-500/10 dark:text-green-200' : 'border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-400/40 dark:bg-blue-500/10 dark:text-blue-200'
                ]">
                  <InboxIcon class="h-6 w-6" />
                </div>
                <span class="text-xs font-medium text-slate-900 dark:text-white">{{ $t('gestlab.general.labels.direct_collections.reception') }}</span>
                <span class="text-xs text-slate-500 dark:text-slate-400">{{ formatDate(props.record.data?.created_at) }}</span>
              </div>
            </div>

            <!-- ANALYSIS STEP -->
            <div class="relative">
              <div class="flex flex-col items-center">
                <div :class="[
                  'mb-2 flex h-12 w-12 items-center justify-center rounded-full border-2 shadow-sm',
                  isStepComplete('analysis') ? 'border-green-500 bg-green-100 text-green-700 dark:border-green-400 dark:bg-green-500/10 dark:text-green-200' :
                  props.record.data?.placed_analysis ? 'border-yellow-500 bg-yellow-100 text-yellow-700 dark:border-amber-400 dark:bg-amber-500/10 dark:text-amber-200' : 'border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-400/40 dark:bg-blue-500/10 dark:text-blue-200'
                ]">
                  <BeakerIcon class="h-6 w-6" />
                </div>
                <span class="text-xs font-medium text-slate-900 dark:text-white">{{ $t('gestlab.general.labels.direct_collections.analysis') }}</span>
                <span v-if="props.record.data?.analysis_start_date" class="text-xs text-slate-500 dark:text-slate-400">
                  {{ formatDate(props.record.data?.analysis_start_date) }}
                </span>
              </div>
            </div>

            <!-- VERIFICATION STEP -->
            <div class="relative">
              <div class="flex flex-col items-center">
                <div :class="[
                  'mb-2 flex h-12 w-12 items-center justify-center rounded-full border-2 shadow-sm',
                  isStepComplete('verification') ? 'border-green-500 bg-green-100 text-green-700 dark:border-green-400 dark:bg-green-500/10 dark:text-green-200' : 'border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-400/40 dark:bg-blue-500/10 dark:text-blue-200'
                ]">
                  <CheckCircleIcon class="h-6 w-6" />
                </div>
                <span class="text-xs font-medium text-slate-900 dark:text-white">{{ $t('gestlab.general.labels.direct_collections.verification') }}</span>
                <span v-if="props.record.data?.verified_date" class="text-xs text-slate-500 dark:text-slate-400">
                  {{ formatDate(props.record.data?.verified_date) }}
                </span>
              </div>
            </div>

            <!-- APPROVAL STEP -->
            <div class="relative">
              <div class="flex flex-col items-center">
                <div :class="[
                  'mb-2 flex h-12 w-12 items-center justify-center rounded-full border-2 shadow-sm',
                  isStepComplete('approval') ? 'border-green-500 bg-green-100 text-green-700 dark:border-green-400 dark:bg-green-500/10 dark:text-green-200' : 'border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-400/40 dark:bg-blue-500/10 dark:text-blue-200'
                ]">
                  <DocumentCheckIcon class="h-6 w-6" />
                </div>
                <span class="text-xs font-medium text-slate-900 dark:text-white">{{ $t('gestlab.general.labels.direct_collections.approval') }}</span>
                <span v-if="props.record.data?.approved_date" class="text-xs text-slate-500 dark:text-slate-400">
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
              <div class="text-2xl font-bold text-blue-900 dark:text-blue-200">{{ props.record.data?.total_samples || 0 }}</div>
              <div class="text-xs text-slate-600 dark:text-slate-400">{{ $t('gestlab.general.labels.direct_collections.total_samples') }}</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-green-600 dark:text-green-300">{{ props.record.data?.completed_analysis || 0 }}</div>
              <div class="text-xs text-slate-600 dark:text-slate-400">{{ $t('gestlab.general.labels.direct_collections.completed') }}</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-yellow-600 dark:text-amber-300">{{ props.record.data?.in_progress_analysis || 0 }}</div>
              <div class="text-xs text-slate-600 dark:text-slate-400">{{ $t('gestlab.general.labels.direct_collections.in_progress') }}</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-blue-900 dark:text-blue-200">{{ props.record.data?.pending_analysis || 0 }}</div>
              <div class="text-xs text-slate-600 dark:text-slate-400">{{ $t('gestlab.general.labels.direct_collections.pending') }}</div>
            </div>
          </div>
        </div>

        <div
          v-if="props.record.data?.scope_control?.required_parameter_count || props.record.data?.scope_control?.conditioning_status"
          class="mt-6 rounded-[24px] border border-amber-200 bg-amber-50/80 p-4 shadow-sm dark:border-amber-400/20 dark:bg-amber-500/10"
        >
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Escopo controlado da receção</h4>
              <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">
                Esta colheita herda o planeamento analítico e o estado de condicionamento definidos no momento da receção.
              </p>
            </div>
            <div class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-200 dark:bg-slate-950/70 dark:text-amber-200 dark:ring-amber-400/30">
              {{ props.record.data?.scope_control?.required_parameter_count || 0 }} parâmetros previstos
            </div>
          </div>

          <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div class="rounded-2xl border border-white/70 bg-white/85 p-3 shadow-sm dark:border-slate-700 dark:bg-slate-950/70">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Condicionamento</p>
              <p class="mt-2 text-sm font-medium text-slate-900 dark:text-white">
                {{ getConditioningLabel(props.record.data?.scope_control?.conditioning_status) }}
              </p>
              <p v-if="props.record.data?.scope_control?.packaging_condition" class="mt-2 text-xs text-slate-600 dark:text-slate-300">
                Embalagem: {{ props.record.data.scope_control.packaging_condition }}
              </p>
              <p v-if="props.record.data?.scope_control?.temperature_condition" class="mt-1 text-xs text-slate-600 dark:text-slate-300">
                Temperatura: {{ props.record.data.scope_control.temperature_condition }}
              </p>
            </div>

            <div class="rounded-2xl border border-white/70 bg-white/85 p-3 shadow-sm dark:border-slate-700 dark:bg-slate-950/70">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Perfis resolvidos</p>
              <div v-if="props.record.data?.scope_control?.resolved_profiles?.length" class="mt-2 flex flex-wrap gap-2">
                <span
                  v-for="profile in props.record.data.scope_control.resolved_profiles"
                  :key="profile.id"
                  class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-900 dark:bg-blue-500/10 dark:text-blue-200"
                >
                  {{ profile.name }}
                </span>
              </div>
              <p v-else class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                Nenhum perfil resolvido foi registado para esta colheita.
              </p>
            </div>
          </div>

          <div v-if="props.record.data?.scope_control?.required_parameters?.length" class="mt-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Checklist de parâmetros</p>
            <div class="mt-2 grid gap-2 md:grid-cols-2 xl:grid-cols-3">
              <div
                v-for="parameter in props.record.data.scope_control.required_parameters"
                :key="parameter.id"
                class="rounded-2xl border border-white/70 bg-white/85 px-3 py-2 shadow-sm dark:border-slate-700 dark:bg-slate-950/70"
              >
                <p class="text-sm font-medium text-slate-900 dark:text-white">
                  {{ parameter.code || 'N/D' }} · {{ parameter.name }}
                </p>
                <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">
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
        <div class="overflow-hidden rounded-[26px] border border-slate-200 bg-white/95 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
          <div class="bg-gradient-to-r from-primary-900 to-primary-700 px-6 py-4 dark:from-slate-950 dark:to-primary-950">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <DocumentTextIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.direct_collections.sample_details') }}
            </h2>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- QR CODE -->
              <div class="md:col-span-2 lg:col-span-1 space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                  {{ $t('gestlab.general.labels.direct_collections.qr_code') }}
                </label>
                <div class="flex justify-center rounded-2xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-700 dark:bg-white">
                  <img :src="props.record.data?.qr" alt="QR Code" class="w-32 h-32" />
                </div>
              </div>

              <!-- BASIC INFO -->
              <div class="md:col-span-2 lg:col-span-2 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                      {{ $t('gestlab.general.labels.direct_collections.cl') }}
                    </label>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 text-sm font-medium text-slate-900 dark:border-slate-700 dark:bg-slate-900/80 dark:text-white">
                      {{ props.record.data?.cl }}
                    </div>
                  </div>
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                      {{ $t('gestlab.general.labels.direct_collections.type') }}
                    </label>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 text-sm font-medium text-slate-900 dark:border-slate-700 dark:bg-slate-900/80 dark:text-white">
                      {{ props.record.data?.type }}
                    </div>
                  </div>
                </div>

                <!-- STATUS INDICATORS -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                      {{ $t('gestlab.general.labels.direct_collections.placed_analysis') }}
                    </label>
                    <div class="flex items-center">
                      <StatusToggle :value="props.record.data?.placed_analysis" />
                      <span class="ml-2 text-sm text-slate-600 dark:text-slate-300">
                        {{ props.record.data?.placed_analysis ? 'Em análise' : 'Aguardando' }}
                      </span>
                    </div>
                  </div>
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                      {{ $t('gestlab.general.labels.direct_collections.invoiced') }}
                    </label>
                    <div class="flex items-center">
                      <StatusToggle :value="props.record.data?.invoiced" />
                      <span class="ml-2 text-sm text-slate-600 dark:text-slate-300">
                        {{ props.record.data?.invoiced ? 'Facturado' : 'Por facturar' }}
                      </span>
                    </div>
                  </div>
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                      {{ $t('gestlab.general.labels.direct_collections.processed') }}
                    </label>
                    <div class="flex items-center">
                      <StatusToggle :value="props.record.data?.processed" />
                      <span class="ml-2 text-sm text-slate-600 dark:text-slate-300">
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
        <div class="overflow-hidden rounded-[26px] border border-slate-200 bg-white/95 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
          <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
            <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
              <CircleStackIcon class="h-5 w-5 text-blue-900 dark:text-blue-300" />
              {{ $t('gestlab.general.labels.direct_collections.collection_details') }}
            </h2>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- TIMING INFO -->
              <div class="space-y-4">
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ $t('gestlab.general.labels.direct_collections.collection_date') }}
                  </label>
                  <div class="flex items-center rounded-2xl border border-blue-200 bg-blue-50 p-3 text-sm font-medium text-slate-900 dark:border-blue-400/30 dark:bg-blue-500/10 dark:text-blue-100">
                    <CalendarIcon class="mr-2 h-4 w-4 text-blue-900 dark:text-blue-300" />
                    {{ formatDate(props.record.data?.collection_date) }}
                  </div>
                </div>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ $t('gestlab.general.labels.direct_collections.expiry_date') }}
                  </label>
                  <div class="flex items-center rounded-2xl border border-red-200 bg-red-50 p-3 text-sm font-medium text-slate-900 dark:border-red-400/30 dark:bg-red-500/10 dark:text-red-100">
                    <CalendarIcon class="mr-2 h-4 w-4 text-red-600 dark:text-red-300" />
                    {{ formatDate(props.record.data?.expiry_date) }}
                  </div>
                </div>
              </div>

              <!-- PRODUCT INFO -->
              <div class="space-y-4">
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ $t('gestlab.general.labels.direct_collections.product') }}
                  </label>
                  <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 text-sm font-medium text-slate-900 dark:border-slate-700 dark:bg-slate-900/80 dark:text-white">
                    {{ props.record.data?.product }}
                  </div>
                </div>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ $t('gestlab.general.labels.direct_collections.commercial_brand') }}
                  </label>
                  <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 text-sm font-medium text-slate-900 dark:border-slate-700 dark:bg-slate-900/80 dark:text-white">
                    {{ props.record.data?.comercial_brand || '-' }}
                  </div>
                </div>
              </div>

              <!-- QUANTITY INFO -->
              <div class="space-y-4">
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ $t('gestlab.general.labels.direct_collections.qty') }}
                  </label>
                  <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-900/80">
                      <div class="text-xs text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.direct_collections.qty') }}</div>
                      <div class="text-sm font-semibold text-slate-900 dark:text-white">{{ props.record.data?.qty || '-' }}</div>
                    </div>
                    <div class="rounded-2xl border border-green-200 bg-green-50 p-3 dark:border-green-400/30 dark:bg-green-500/10">
                      <div class="text-xs text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.direct_collections.collected_qty') }}</div>
                      <div class="text-sm font-semibold text-green-800 dark:text-green-200">{{ props.record.data?.collected_qty || '-' }}</div>
                    </div>
                  </div>
                </div>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ $t('gestlab.general.labels.direct_collections.temperature_id') }}
                  </label>
                  <div class="flex items-center rounded-2xl border border-blue-200 bg-blue-50 p-3 text-sm font-medium text-slate-900 dark:border-blue-400/30 dark:bg-blue-500/10 dark:text-blue-100">
                    <EyeDropperIcon class="mr-2 h-4 w-4 text-blue-900 dark:text-blue-300" />
                    {{ props.record.data?.temperature_value || '-' }} {{ props.record.data?.temperature || '°C' }}
                  </div>
                </div>
              </div>
            </div>

            <!-- ADDITIONAL DETAILS -->
            <div class="mt-6 border-t border-slate-200 pt-6 dark:border-slate-800">
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ $t('gestlab.general.labels.direct_collections.location') }}
                  </label>
                  <div class="text-sm font-medium text-slate-900 dark:text-white">{{ props.record.data?.location || '-' }}</div>
                </div>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ $t('gestlab.general.labels.direct_collections.vehicle_id') }}
                  </label>
                  <div class="text-sm font-medium text-slate-900 dark:text-white">{{ props.record.data?.vehicle || '-' }}</div>
                </div>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ $t('gestlab.general.labels.direct_collections.pack_id') }}
                  </label>
                  <div class="text-sm font-medium text-slate-900 dark:text-white">{{ props.record.data?.pack || '-' }}</div>
                </div>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ $t('gestlab.general.labels.direct_collections.lot') }}
                  </label>
                  <div class="text-sm font-medium text-slate-900 dark:text-white">{{ props.record.data?.lot || '-' }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ANALYSIS RESULTS SECTION -->
        <div v-if="props.record.data?.analysis_results && hasRole('admin')" class="overflow-hidden rounded-[26px] border border-slate-200 bg-white/95 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
          <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
            <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
              <BeakerIcon class="h-5 w-5 text-blue-900 dark:text-blue-300" />
              {{ $t('gestlab.general.labels.direct_collections.analysis_results') }}
              <span class="ml-2 text-sm font-normal text-slate-500 dark:text-slate-400">
                ({{ props.record.data?.analysis_results?.length || 0 }} resultados)
              </span>
            </h2>
          </div>
          
          <div class="p-6">
            <div v-for="(result, index) in props.record.data?.analysis_results" :key="index" class="mb-4 last:mb-0">
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/80">
                <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                  <div class="flex items-center gap-3">
                    <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ result.parameter_label }}</span>
                    <span :class="[
                      'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                      getResultStatusColor(result.status)
                    ]">
                      {{ getResultStatusLabel(result.status) }}
                    </span>
                  </div>
                  <div class="text-sm text-slate-600 dark:text-slate-300">{{ result.unit_label }}</div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div class="space-y-2">
                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.direct_collections.result_value') }}</div>
                    <div class="text-lg font-bold text-blue-900 dark:text-blue-200">{{ result.verified_value || result.inserted_value || '-' }}</div>
                  </div>
                  <div class="space-y-2">
                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.direct_collections.reference_range') }}</div>
                    <div class="text-sm font-medium text-slate-900 dark:text-white">
                      {{ result.min_ref_value || '-' }} - {{ result.max_ref_value || '-' }}
                    </div>
                  </div>
                  <div class="space-y-2">
                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.direct_collections.last_update') }}</div>
                    <div class="text-sm text-slate-600 dark:text-slate-300">{{ formatDate(result.updated_at) }}</div>
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
        <div class="rounded-[26px] border border-slate-200 bg-white/95 p-6 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
          <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">
            {{ $t('gestlab.general.labels.actions') }}
          </h3>

          <div class="space-y-3 border-b border-slate-200 pb-4 dark:border-slate-800" v-for="sample in props.record.data?.samples || []" :key="sample.id">
            <h4 class="mb-2 text-sm font-semibold text-slate-900 dark:text-white">Resultados: {{ sample?.analysis?.department?.name }}</h4>
            <div class="mb-2">
              <Link
                :href="route('analysis.edit', sample.analysis.id)" 
                as="button"
                class="mb-2 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-green-600 to-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 dark:focus:ring-offset-slate-950"
                
              >
                  <CheckCircleIcon class="h-5 w-5" />
                  <!-- {{ $t('gestlab.general.labels.direct_collections.verify_results') }} -->
                  Gerir Resultados  
              </Link>
            </div>

          </div>

        </div>

        <!-- ANALYSIS STATUS CARD -->
        <div class="rounded-[26px] border border-slate-200 bg-white/95 p-6 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
          <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
            <ChartBarIcon class="h-5 w-5 text-blue-900 dark:text-blue-300" />
            {{ $t('gestlab.general.labels.direct_collections.analysis_status') }}
          </h3>
          <div class="space-y-4">
            <!-- PROGRESS BAR -->
            <div>
              <div class="flex justify-between text-sm mb-1">
                <span class="text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.direct_collections.completion') }}</span>
                <span class="font-semibold text-blue-900 dark:text-blue-200">{{ getCompletionPercentage() }}%</span>
              </div>
              <div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                <div 
                  class="h-full bg-gradient-to-r from-primary-800 to-primary-600 transition-all duration-500"
                  :style="{ width: getCompletionPercentage() + '%' }"
                ></div>
              </div>
            </div>

            <!-- STATUS BREAKDOWN -->
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="h-2 w-2 rounded-full bg-blue-900"></div>
                  <span class="text-sm text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.direct_collections.pending') }}</span>
                </div>
                <span class="text-sm font-semibold text-blue-900 dark:text-blue-200">{{ props.record.data?.pending_analysis || 0 }}</span>
              </div>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="h-2 w-2 rounded-full bg-yellow-500"></div>
                  <span class="text-sm text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.direct_collections.in_progress') }}</span>
                </div>
                <span class="text-sm font-semibold text-yellow-600 dark:text-amber-300">{{ props.record.data?.in_progress_analysis || 0 }}</span>
              </div>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="h-2 w-2 rounded-full bg-green-500"></div>
                  <span class="text-sm text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.direct_collections.completed') }}</span>
                </div>
                <span class="text-sm font-semibold text-green-600 dark:text-green-300">{{ props.record.data?.completed_analysis || 0 }}</span>
              </div>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="h-2 w-2 rounded-full bg-red-500"></div>
                  <span class="text-sm text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.direct_collections.critical') }}</span>
                </div>
                <span class="text-sm font-semibold text-red-600 dark:text-red-300">{{ props.record.data?.critical_analysis || 0 }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- QUALITY CERTIFICATES -->
        <div v-if="props.record.data?.quality_certificate && hasPermission('view_quality_certificates')" class="rounded-[26px] border border-slate-200 bg-white/95 p-6 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
          <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
            <DocumentCheckIcon class="h-5 w-5 text-blue-900 dark:text-blue-300" />
            {{ $t('gestlab.general.labels.direct_collections.quality_certificate') }}
          </h3>
          <div class="space-y-3">
            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-3 dark:border-blue-400/30 dark:bg-blue-500/10">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-semibold text-blue-900 dark:text-blue-200">{{ props.record.data?.quality_certificate.code }}</span>
                <span :class="[
                  'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                  props.record.data?.quality_certificate.status ? 'bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-amber-500/10 dark:text-amber-200'
                ]">
                  {{ props.record.data?.quality_certificate.validated_by_id ? 'Validado' : 'Pendente' }}
                </span>
              </div>
              <div class="text-xs text-slate-600 dark:text-slate-300">
                {{ $t('gestlab.general.labels.direct_collections.validated_by') }}: {{ props.record.data?.quality_certificate.validated_by || '-' }}
              </div>
              <div class="text-xs text-slate-600 dark:text-slate-300">
                {{ $t('gestlab.general.labels.direct_collections.validated_at') }}: {{ formatDate(props.record.data?.quality_certificate.validated_at) }}
              </div>
            </div>
          </div>
        </div>

        <!-- DOCUMENTS CARD -->
        <div class="rounded-[26px] border border-slate-200 bg-white/95 p-6 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
          <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
            <PaperClipIcon class="h-5 w-5 text-blue-900 dark:text-blue-300" />
            {{ $t('gestlab.general.labels.direct_collections.documents') }}
          </h3>
          <div class="space-y-3">
            <a 
              v-if="props.record.data?.links?.pdf_path"
              :href="props.record.data.links.pdf_path"
              target="_blank"
              class="group flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 p-3 transition-colors duration-200 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:hover:bg-slate-800"
            >
              <div class="flex items-center gap-3">
                <DocumentIcon class="h-5 w-5 text-slate-400 group-hover:text-blue-900 dark:text-slate-500 dark:group-hover:text-blue-300" />
                <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $t('gestlab.general.labels.direct_collections.work_sheet') }}</span>
              </div>
              <ArrowTopRightOnSquareIcon class="h-4 w-4 text-slate-400 group-hover:text-blue-900 dark:text-slate-500 dark:group-hover:text-blue-300" />
            </a>
            <a 
              v-if="props.record.data?.links?.pdf_collection_labels"
              :href="props.record.data.links.pdf_collection_labels"
              target="_blank"
              class="group flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 p-3 transition-colors duration-200 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:hover:bg-slate-800"
            >
              <div class="flex items-center gap-3">
                <TagIcon class="h-5 w-5 text-slate-400 group-hover:text-blue-900 dark:text-slate-500 dark:group-hover:text-blue-300" />
                <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $t('gestlab.general.labels.direct_collections.labels') }}</span>
              </div>
              <ArrowTopRightOnSquareIcon class="h-4 w-4 text-slate-400 group-hover:text-blue-900 dark:text-slate-500 dark:group-hover:text-blue-300" />
            </a>
            <a 
              v-if="props.record.data?.links?.pdf_collection_term"
              :href="props.record.data.links.pdf_collection_term"
              target="_blank"
              class="group flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 p-3 transition-colors duration-200 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:hover:bg-slate-800"
            >
              <div class="flex items-center gap-3">
                <DocumentTextIcon class="h-5 w-5 text-slate-400 group-hover:text-blue-900 dark:text-slate-500 dark:group-hover:text-blue-300" />
                <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $t('gestlab.general.labels.direct_collections.collection_term') }}</span>
              </div>
              <ArrowTopRightOnSquareIcon class="h-4 w-4 text-slate-400 group-hover:text-blue-900 dark:text-slate-500 dark:group-hover:text-blue-300" />
            </a>
            <a 
              v-if="props.record.data?.links?.pdf_quality_certificate && hasPermission('view_quality_certificates')"
              :href="props.record.data.links.pdf_quality_certificate"
              target="_blank"
              class="group flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 p-3 transition-colors duration-200 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:hover:bg-slate-800"
            >
              <div class="flex items-center gap-3">
                <Square2StackIcon class="h-5 w-5 text-slate-400 group-hover:text-blue-900 dark:text-slate-500 dark:group-hover:text-blue-300" />
                <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $t('gestlab.general.labels.direct_collections.quality_certificate') }}</span>
              </div>
              <ArrowTopRightOnSquareIcon class="h-4 w-4 text-slate-400 group-hover:text-blue-900 dark:text-slate-500 dark:group-hover:text-blue-300" />
            </a>

          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { usePermission } from "@/Composables/usePermissions";
import { Link, router } from "@inertiajs/vue3";
import { computed } from "vue";
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
  DocumentCheckIcon,
  DocumentIcon,
  DocumentTextIcon,
  InboxIcon,
  PaperClipIcon,
  PencilIcon,
  TagIcon,
  EyeDropperIcon,
  Square2StackIcon
} from "@heroicons/vue/24/outline";

const { hasRole, hasPermission } = usePermission();
const props = defineProps({
  record: Object,
  collectionPresentation: { type: Object, default: () => ({}) },
});

defineOptions({
  layout: Layout
});

const presentation = computed(() => props.collectionPresentation || {});
const sampleEntry = computed(() => props.record?.data?.sample_entry || null);
const collectionTitle = computed(() => presentation.value.title || 'Colheita direta');
const collectionDescription = computed(() => presentation.value.description || 'Etapa operacional ligada à Sample Entry e ao lab code.');
const collectionIndexUrl = computed(() => presentation.value.index_url || route('directcollections.index'));
const collectionEditUrl = computed(() => presentation.value.edit_url || (props.record?.data?.id ? route('directcollections.edit', { collection: props.record.data.id }) : '#'));

const getStatusBadgeColor = (status) => {
  const statusColors = {
    'pending': 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-200',
    'in_progress': 'bg-primary-100 text-primary-800 dark:bg-primary-500/10 dark:text-primary-200',
    'completed': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-200',
    'verified': 'bg-primary-100 text-primary-800 dark:bg-primary-500/10 dark:text-primary-200',
    'approved': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-200',
    'rejected': 'bg-rose-100 text-rose-800 dark:bg-rose-500/10 dark:text-rose-200',
    'cancelled': 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200'
  };
  return statusColors[status] || 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200';
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
    0: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200', // pending
    1: 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-200', // in progress
    2: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-200', // completed
    3: 'bg-primary-100 text-primary-800 dark:bg-primary-500/10 dark:text-primary-200', // verified
    4: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-200', // approved
    5: 'bg-rose-100 text-rose-800 dark:bg-rose-500/10 dark:text-rose-200' // rejected
  };
  return statusColors[status] || 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200';
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

</script>

<style scoped>
.direct-collection-show [class~="text-blue-900"],
.direct-collection-show [class~="text-blue-800"],
.direct-collection-show [class~="text-blue-700"] {
  color: #0f766e;
}

.direct-collection-show [class~="text-green-800"],
.direct-collection-show [class~="text-green-700"],
.direct-collection-show [class~="text-green-600"] {
  color: #047857;
}

.direct-collection-show [class~="text-yellow-700"],
.direct-collection-show [class~="text-yellow-600"] {
  color: #b45309;
}

.direct-collection-show [class~="text-red-600"],
.direct-collection-show [class~="text-red-700"] {
  color: #be123c;
}

.direct-collection-show [class~="bg-blue-50"],
.direct-collection-show [class~="bg-blue-100"] {
  background-color: rgb(240 253 250 / 0.88);
}

.direct-collection-show [class~="bg-green-50"],
.direct-collection-show [class~="bg-green-100"] {
  background-color: rgb(236 253 245 / 0.9);
}

.direct-collection-show [class~="bg-yellow-100"] {
  background-color: rgb(254 243 199 / 0.9);
}

.direct-collection-show [class~="bg-red-50"],
.direct-collection-show [class~="bg-red-100"] {
  background-color: rgb(255 241 242 / 0.9);
}

.direct-collection-show [class~="border-blue-100"],
.direct-collection-show [class~="border-blue-200"] {
  border-color: rgb(153 246 228 / 0.86);
}

.direct-collection-show [class~="border-green-200"] {
  border-color: rgb(167 243 208 / 0.86);
}

.direct-collection-show [class~="border-red-200"] {
  border-color: rgb(254 205 211 / 0.9);
}

:global(.dark) .direct-collection-show [class~="dark:text-blue-300"],
:global(.dark) .direct-collection-show [class~="dark:text-blue-200"],
:global(.dark) .direct-collection-show [class~="dark:text-green-300"],
:global(.dark) .direct-collection-show [class~="dark:text-green-200"] {
  color: #99f6e4;
}

:global(.dark) .direct-collection-show [class~="dark:text-red-300"],
:global(.dark) .direct-collection-show [class~="dark:text-red-100"] {
  color: #fecdd3;
}

:global(.dark) .direct-collection-show [class~="dark:bg-blue-500/10"],
:global(.dark) .direct-collection-show [class~="dark:bg-green-500/10"] {
  background-color: rgb(20 184 166 / 0.12);
}

:global(.dark) .direct-collection-show [class~="dark:bg-red-500/10"] {
  background-color: rgb(244 63 94 / 0.12);
}
</style>

<script>
// StatusToggle Component
const StatusToggle = {
  props: ['value'],
  template: `
    <button type="button" class="relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-700 focus:ring-offset-2 dark:focus:ring-primary-300 dark:focus:ring-offset-slate-950" :class="value ? 'bg-primary-800 dark:bg-primary-500' : 'bg-slate-200 dark:bg-slate-700'">
      <span class="sr-only">Toggle status</span>
      <span class="translate-x-0 pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="value ? 'translate-x-5' : 'translate-x-0'">
        <span class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" :class="value ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in'">
          <svg class="h-3 w-3 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 12 12">
            <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </span>
        <span class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" :class="value ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out'">
          <svg class="h-3 w-3 text-primary-900 dark:text-primary-600" fill="currentColor" viewBox="0 0 12 12">
            <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
          </svg>
        </span>
      </span>
    </button>
  `
};
</script>
