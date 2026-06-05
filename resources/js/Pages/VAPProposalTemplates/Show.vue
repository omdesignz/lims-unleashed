<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <section class="overflow-hidden rounded-[34px] border border-[#ded2bb] bg-[#fbfaf6] shadow-[0_26px_70px_-44px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950">
      <div class="bg-[radial-gradient(circle_at_top_left,rgba(199,154,67,0.22),transparent_34%),linear-gradient(135deg,#fffaf0,#f7f1e6_58%,#143d37_58%,#143d37)] px-6 py-7 dark:bg-[radial-gradient(circle_at_top_left,rgba(199,154,67,0.18),transparent_34%),linear-gradient(135deg,#17231f,#101815_58%,#0b1210_58%,#0b1210)] sm:px-8">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
          <div class="max-w-4xl">
            <div class="flex flex-wrap items-center gap-3">
              <span class="inline-flex items-center gap-2 rounded-full border border-[#c79a43]/40 bg-white/85 px-3 py-1 text-xs font-black uppercase tracking-[0.24em] text-[#143d37] shadow-sm dark:bg-white/10 dark:text-amber-100">
                <component
                  :is="getCategoryIcon(template.category)"
                  class="h-4 w-4 text-[#c79a43]"
                />
                {{ getCategoryLabel(template.category) }}
              </span>
              <span :class="[
                'inline-flex items-center rounded-full px-3 py-1 text-xs font-black uppercase tracking-[0.18em]',
                template.is_active
                  ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-400/10 dark:text-emerald-100'
                  : 'bg-[#f7f1e6] text-[#59665f] dark:bg-white/10 dark:text-slate-300'
              ]">
                {{ template.is_active ? $t('gestlab.general.labels.vap_proposal_templates.active') : $t('gestlab.general.labels.vap_proposal_templates.inactive') }}
              </span>
            </div>
            <h1 class="mt-5 text-3xl font-black tracking-[-0.04em] text-[#10221d] dark:text-white sm:text-5xl">
              {{ template.name }}
            </h1>
            <p class="mt-4 max-w-3xl text-base font-medium leading-7 text-[#59665f] dark:text-slate-300">
              {{ template.description || $t('gestlab.general.labels.vap_proposal_templates.show.description') }}
            </p>
          </div>

          <div class="flex flex-col gap-3 sm:flex-row">
            <Link
              :href="route('vap-proposals.templates.index')"
              class="inline-flex items-center justify-center gap-2 rounded-[20px] border border-white/45 bg-white/85 px-5 py-3 text-sm font-black text-[#143d37] shadow-[0_18px_42px_-28px_rgba(20,61,55,0.65)] transition hover:bg-[#fff7e5] dark:border-white/10 dark:bg-white/10 dark:text-emerald-100 dark:hover:bg-white/15"
            >
              <ArrowLeftIcon class="h-5 w-5 text-[#c79a43]" />
              {{ $t('gestlab.general.buttons.back') }}
            </Link>
            <Link
              :href="route('vap-proposals.create') + '?template_id=' + template.id"
              class="inline-flex items-center justify-center gap-2 rounded-[20px] bg-[#143d37] px-5 py-3 text-sm font-black text-white shadow-[0_18px_42px_-24px_rgba(20,61,55,0.75)] transition hover:bg-[#0f302b] focus:outline-none focus:ring-2 focus:ring-[#c79a43] focus:ring-offset-2 dark:ring-offset-slate-950"
            >
              <DocumentPlusIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_proposal_templates.show.use_this_template') }}
            </Link>
          </div>
        </div>
      </div>

      <div class="grid gap-4 border-t border-[#ded2bb] bg-white/55 px-6 py-5 dark:border-white/10 dark:bg-white/5 sm:grid-cols-2 xl:grid-cols-4 sm:px-8">
        <article class="rounded-[24px] border border-[#ded2bb] bg-white/85 p-4 shadow-[0_18px_48px_-36px_rgba(20,61,55,0.48)] dark:border-white/10 dark:bg-white/5">
          <p class="text-xs font-black uppercase tracking-[0.18em] text-[#78847c] dark:text-slate-400">{{ $t('gestlab.general.labels.vap_proposal_templates.used') }}</p>
          <p class="mt-3 text-2xl font-black tracking-[-0.04em] text-[#10221d] dark:text-white">{{ template.proposals_count || 0 }}</p>
        </article>
        <article class="rounded-[24px] border border-[#ded2bb] bg-white/85 p-4 shadow-[0_18px_48px_-36px_rgba(20,61,55,0.48)] dark:border-white/10 dark:bg-white/5">
          <p class="text-xs font-black uppercase tracking-[0.18em] text-[#78847c] dark:text-slate-400">{{ $t('gestlab.general.labels.vap_proposal_templates.acceptance_rate') }}</p>
          <p class="mt-3 text-2xl font-black tracking-[-0.04em] text-[#10221d] dark:text-white">{{ calculateAcceptanceRate }}%</p>
        </article>
        <article class="rounded-[24px] border border-[#ded2bb] bg-white/85 p-4 shadow-[0_18px_48px_-36px_rgba(20,61,55,0.48)] dark:border-white/10 dark:bg-white/5">
          <p class="text-xs font-black uppercase tracking-[0.18em] text-[#78847c] dark:text-slate-400">{{ $t('gestlab.general.labels.vap_proposal_templates.show.variables') }}</p>
          <p class="mt-3 text-2xl font-black tracking-[-0.04em] text-[#10221d] dark:text-white">{{ variablesCount }}</p>
        </article>
        <article class="rounded-[24px] border border-[#ded2bb] bg-white/85 p-4 shadow-[0_18px_48px_-36px_rgba(20,61,55,0.48)] dark:border-white/10 dark:bg-white/5">
          <p class="text-xs font-black uppercase tracking-[0.18em] text-[#78847c] dark:text-slate-400">{{ $t('gestlab.general.labels.vap_proposal_templates.show.created_by') }}</p>
          <p class="mt-3 truncate text-lg font-black tracking-[-0.03em] text-[#10221d] dark:text-white">{{ template.user.name }}</p>
        </article>
      </div>
    </section>

    <!-- MAIN CONTENT GRID -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- TEMPLATE CONTENT CARD -->
        <div class="overflow-hidden rounded-[30px] border border-[#ded2bb] bg-white/90 shadow-[0_22px_70px_-46px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90">
          <div class="border-b border-[#ded2bb] bg-[#143d37] px-6 py-4 dark:border-white/10">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <DocumentTextIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_proposal_templates.show.content') }}
            </h2>
          </div>
          
          <div class="p-6">
            <!-- VARIABLES PREVIEW -->
            <div class="mb-6">
              <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-medium text-slate-900 dark:text-slate-100">
                  {{ $t('gestlab.general.labels.vap_proposal_templates.show.variables') }}
                </h3>
                <span class="text-xs text-slate-500 dark:text-slate-400">
                  {{ variablesCount }} {{ $t('gestlab.general.labels.vap_proposal_templates.show.variables') }}
                </span>
              </div>
              <div class="flex flex-wrap gap-2">
                <span 
                  v-for="variable in templateVariables"
                  :key="variable"
                  class="inline-flex items-center gap-1 rounded-full border border-[#d8cbb4] bg-[#f7f1e6] px-3 py-1.5 text-sm font-medium text-[#143d37] dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-200"
                >
                  <CodeBracketIcon class="h-3 w-3" />
                  {{ variable }}
                </span>
              </div>
            </div>

            <!-- TEMPLATE CONTENT -->
            <div class="border-t border-[#ded2bb] pt-6 dark:border-white/10">
              <div class="prose min-h-[400px] max-w-none rounded-[24px] border border-[#ded2bb] bg-[#fbfaf6] p-6 dark:border-white/10 dark:bg-white/5 dark:prose-invert">
                <div v-html="formattedTemplateContent"></div>
              </div>
            </div>

            <!-- RAW CONTENT VIEW -->
            <div class="mt-6 border-t border-[#ded2bb] pt-6 dark:border-white/10">
              <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-medium text-slate-900 dark:text-slate-100">
                  {{ $t('gestlab.general.labels.vap_proposal_templates.show.raw_content') }}
                </h3>
                <button
                  @click="toggleRawView"
                  class="text-xs font-medium text-[#143d37] hover:text-[#0f302b] dark:text-emerald-200 dark:hover:text-emerald-100"
                >
                  {{ showRawView ? $t('gestlab.general.labels.vap_proposal_templates.show.hide_raw') : $t('gestlab.general.labels.vap_proposal_templates.show.show_raw') }}
                </button>
              </div>
              <div v-if="showRawView" class="relative">
                <textarea
                  v-model="template.content"
                  rows="10"
                  readonly
                  class="w-full resize-none rounded-lg bg-slate-950 p-4 font-mono text-sm text-slate-100"
                ></textarea>
                <button
                  @click="copyRawContent"
                  class="absolute top-3 right-3 inline-flex items-center gap-1 rounded-lg bg-gray-800 px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-700"
                >
                  <DocumentDuplicateIcon class="h-3 w-3" />
                  {{ copied ? $t('gestlab.general.buttons.copied') : $t('gestlab.general.buttons.copy') }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- TEMPLATE USAGE HISTORY -->
        <div class="overflow-hidden rounded-[30px] border border-[#ded2bb] bg-white/90 shadow-[0_22px_70px_-46px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90" v-if="template.proposals_count > 0">
          <div class="border-b border-[#ded2bb] px-6 py-4 dark:border-white/10">
            <div class="flex items-center justify-between">
              <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
                <ChartBarIcon class="h-5 w-5 text-[#143d37] dark:text-emerald-200" />
                {{ $t('gestlab.general.labels.vap_proposal_templates.show.usage') }}
                <span class="ml-2 text-sm font-normal text-slate-500 dark:text-slate-400">
                  ({{ template.proposals_count }} {{ $t('gestlab.general.labels.vap_proposal_templates.show.proposals_using') }})
                </span>
              </h2>
              <div class="text-sm font-semibold text-[#143d37] dark:text-emerald-200">
                {{ calculateAcceptanceRate }}% {{ $t('gestlab.general.labels.vap_proposal_templates.acceptance_rate') }}
              </div>
            </div>
          </div>
          
          <div class="p-6">
            <!-- USAGE STATS -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
              <div class="rounded-2xl border border-[#d8cbb4] bg-[#f7f1e6] p-4 text-center dark:border-white/10 dark:bg-white/5">
                <div class="text-2xl font-bold text-[#143d37] dark:text-emerald-100">{{ template.proposals_count }}</div>
                <div class="mt-1 text-sm text-[#59665f] dark:text-slate-300">{{ $t('gestlab.general.labels.vap_proposal_templates.used') }}</div>
              </div>
              
              <div class="rounded-2xl border border-green-100 bg-green-50 p-4 text-center dark:border-green-500/20 dark:bg-green-500/10">
                <div class="text-2xl font-bold text-green-900 dark:text-green-200">{{ acceptedProposalsCount }}</div>
                <div class="mt-1 text-sm text-green-700 dark:text-green-300">{{ $t('gestlab.general.labels.vap_proposal_templates.show.accepted') }}</div>
              </div>
              
              <div class="rounded-2xl border border-yellow-100 bg-yellow-50 p-4 text-center dark:border-yellow-500/20 dark:bg-yellow-500/10">
                <div class="text-2xl font-bold text-yellow-900 dark:text-yellow-200">{{ pendingProposalsCount }}</div>
                <div class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">{{ $t('gestlab.general.labels.vap_proposal_templates.show.pending') }}</div>
              </div>
              
              <div class="rounded-2xl border border-red-100 bg-red-50 p-4 text-center dark:border-red-500/20 dark:bg-red-500/10">
                <div class="text-2xl font-bold text-red-900 dark:text-red-200">{{ rejectedProposalsCount }}</div>
                <div class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $t('gestlab.general.labels.vap_proposal_templates.show.rejected') }}</div>
              </div>
            </div>

            <!-- RECENT PROPOSALS -->
            <div v-if="recentProposals.length > 0">
              <h3 class="mb-4 text-sm font-medium text-slate-900 dark:text-slate-100">
                {{ $t('gestlab.general.labels.vap_proposal_templates.show.recent_proposals') }}
              </h3>
              <div class="space-y-3">
                <div 
                  v-for="proposal in recentProposals"
                  :key="proposal.id"
                  class="flex items-center justify-between rounded-2xl border border-[#ded2bb] p-3 transition-colors hover:bg-[#fbfaf6] dark:border-white/10 dark:hover:bg-white/5"
                >
                  <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                      <div class="flex h-8 w-8 items-center justify-center rounded-full bg-[#f7f1e6] dark:bg-emerald-400/10">
                        <DocumentTextIcon class="h-4 w-4 text-[#143d37] dark:text-emerald-200" />
                      </div>
                    </div>
                    <div>
                      <div class="text-sm font-medium text-slate-900 dark:text-slate-100">
                        {{ proposal.proposal_number }}
                      </div>
                      <div class="text-xs text-slate-500 dark:text-slate-400">
                        {{ proposal.customer?.name || $t('gestlab.general.labels.vap_proposal_templates.show.customer_unknown') }}
                      </div>
                    </div>
                  </div>
                  
                  <div class="flex items-center gap-4">
                    <span :class="[
                      'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                      getStatusBadge(proposal.status).class
                    ]">
                      {{ getStatusBadge(proposal.status).text }}
                    </span>
                    <span class="text-sm font-medium text-slate-900 dark:text-slate-100">
                      AOA {{ formatCurrency(proposal.total) }}
                    </span>
                    <Link 
                      :href="route('vap-proposals.show', proposal.id)"
                      class="text-[#143d37] hover:text-[#0f302b] dark:text-emerald-200 dark:hover:text-emerald-100"
                      :title="$t('gestlab.general.labels.vap_proposal_templates.show.view_proposal')"
                    >
                      <ArrowRightIcon class="h-4 w-4" />
                    </Link>
                  </div>
                </div>
              </div>
              
              <div class="mt-4 text-center">
                <Link 
                  :href="route('vap-proposals.index', { template_id: template.id })"
                  class="text-sm font-medium text-[#143d37] hover:text-[#0f302b] dark:text-emerald-200 dark:hover:text-emerald-100"
                >
                  {{ $t('gestlab.general.labels.vap_proposal_templates.show.view_all_proposals') }}
                </Link>
              </div>
            </div>
            
            <div v-else class="text-center py-8">
              <DocumentTextIcon class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-700" />
              <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">
                {{ $t('gestlab.general.labels.vap_proposal_templates.show.no_proposals_yet') }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6">
        <!-- TEMPLATE INFO CARD -->
        <div class="rounded-[30px] border border-[#ded2bb] bg-white/90 p-6 shadow-[0_22px_70px_-46px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90">
          <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">
            {{ $t('gestlab.general.labels.vap_proposal_templates.show.title') }}
          </h3>
          <div class="space-y-4">
            <div>
              <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">
                {{ $t('gestlab.general.labels.vap_proposal_templates.show.created_by') }}
              </label>
              <div class="flex items-center gap-2 mt-1">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-200 dark:bg-slate-800">
                  <UserIcon class="h-4 w-4 text-slate-600 dark:text-slate-300" />
                </div>
                <span class="text-sm text-slate-900 dark:text-slate-100">{{ template.user.name }}</span>
              </div>
            </div>

            <details class="rounded-2xl border border-[#ded2bb] bg-[#fbfaf6] p-4 dark:border-white/10 dark:bg-white/5">
              <summary class="cursor-pointer list-none text-sm font-semibold text-slate-900 dark:text-slate-100">
                {{ $t('gestlab.general.labels.vap_proposal_templates.show.metadata_status') }}
              </summary>
              <div class="mt-4 space-y-4">
                <div>
                  <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">
                    {{ $t('gestlab.general.labels.vap_proposal_templates.show.created_at') }}
                  </label>
                  <p class="mt-1 text-sm text-slate-900 dark:text-slate-100">{{ formatDateTime(template.created_at) }}</p>
                </div>

                <div>
                  <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">
                    {{ $t('gestlab.general.labels.vap_proposal_templates.show.updated_at') }}
                  </label>
                  <p class="mt-1 text-sm text-slate-900 dark:text-slate-100">{{ formatDateTime(template.updated_at) }}</p>
                </div>

                <div>
                  <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">
                    {{ $t('gestlab.general.labels.vap_proposal_templates.show.category') }}
                  </label>
                  <div class="mt-1 flex items-center gap-2">
                    <div :class="[
                      'flex h-8 w-8 items-center justify-center rounded-lg',
                      getCategoryColor(template.category).bg
                    ]">
                      <component
                        :is="getCategoryIcon(template.category)"
                        class="h-4 w-4"
                        :class="getCategoryColor(template.category).text"
                      />
                    </div>
                    <span class="text-sm text-slate-900 dark:text-slate-100">{{ getCategoryLabel(template.category) }}</span>
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">
                    {{ $t('gestlab.general.labels.vap_proposal_templates.show.status') }}
                  </label>
                  <div class="mt-1">
                    <span :class="[
                      'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                      template.is_active ? 'bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-300' : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300'
                    ]">
                      {{ template.is_active ? $t('gestlab.general.labels.vap_proposal_templates.active') : $t('gestlab.general.labels.vap_proposal_templates.inactive') }}
                    </span>
                  </div>
                </div>
              </div>
            </details>
          </div>
        </div>

        <!-- ACTIONS CARD -->
        <div class="rounded-[30px] border border-[#ded2bb] bg-white/90 p-6 shadow-[0_22px_70px_-46px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90">
          <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">
            {{ $t('gestlab.general.labels.vap_proposal_templates.actions.title') }}
          </h3>
          <div class="space-y-3">
            <Link 
              :href="route('vap-proposals.templates.edit', template.id)"
              class="w-full inline-flex justify-center items-center gap-2 rounded-2xl bg-[#143d37] px-4 py-3 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-[#0f302b] focus:outline-none focus:ring-2 focus:ring-[#c79a43] focus:ring-offset-2"
            >
              <PencilSquareIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_proposal_templates.show.edit_template') }}
            </Link>
            
            <Link 
              :href="route('vap-proposals.create') + '?template_id=' + template.id"
              class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-[#ded2bb] bg-[#fbfaf6] px-4 py-3 text-sm font-black text-[#143d37] shadow-sm transition-all duration-200 hover:border-[#c79a43] hover:bg-[#fff7e5] focus:outline-none focus:ring-2 focus:ring-[#c79a43]/30 dark:border-white/10 dark:bg-white/5 dark:text-emerald-100 dark:hover:bg-white/10"
            >
              <DocumentPlusIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_proposal_templates.show.create_proposal') }}
            </Link>
            
            <button
              @click="requestStatusToggle"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-2xl px-4 py-3 text-sm font-black shadow-sm transition-all duration-200',
                template.is_active 
                  ? 'border border-yellow-300 bg-white text-yellow-700 hover:bg-yellow-50 dark:border-yellow-500/30 dark:bg-slate-900 dark:text-yellow-300 dark:hover:bg-yellow-500/10'
                  : 'border border-green-300 bg-white text-green-700 hover:bg-green-50 dark:border-green-500/30 dark:bg-slate-900 dark:text-green-300 dark:hover:bg-green-500/10'
              ]"
            >
              <ArrowPathIcon class="h-5 w-5" />
              {{ template.is_active ? $t('gestlab.general.labels.vap_proposal_templates.show.deactivate') : $t('gestlab.general.labels.vap_proposal_templates.show.activate') }}
            </button>
          </div>
          
          <details class="mt-4 border-t border-[#ded2bb] pt-4 dark:border-white/10">
            <summary class="cursor-pointer list-none text-sm font-medium text-slate-900 dark:text-slate-100">
              {{ $t('gestlab.general.labels.vap_proposal_templates.show.export') }}
            </summary>
            <h4 class="mb-2 text-sm font-medium text-slate-900 dark:text-slate-100">
              {{ $t('gestlab.general.labels.vap_proposal_templates.show.export') }}
            </h4>
            <div class="mt-3 space-y-2">
              <button
                @click="exportTemplate"
                class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
              >
                <ArrowDownTrayIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.vap_proposal_templates.show.export_json') }}
              </button>
              <button
                @click="exportAsPdf"
                class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
              >
                <DocumentArrowDownIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.vap_proposal_templates.show.export_pdf') }}
              </button>
            </div>
          </details>
        </div>

        <!-- VARIABLE REFERENCE CARD -->
        <details class="rounded-[30px] border border-[#ded2bb] bg-white/90 p-6 shadow-[0_22px_70px_-46px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90">
          <summary class="flex cursor-pointer list-none items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
            <VariableIcon class="h-5 w-5 text-[#143d37] dark:text-emerald-200" />
            {{ $t('gestlab.general.labels.vap_proposal_templates.show.variable_reference') }}
          </summary>
          <div class="mt-4 space-y-3">
            <div 
              v-for="(description, variable) in availableVariables"
              :key="variable"
              class="rounded-2xl border border-[#ded2bb] p-3 transition-colors hover:border-[#c79a43] hover:bg-[#f7f1e6] dark:border-white/10 dark:hover:bg-emerald-400/10"
            >
              <div class="mb-1 font-mono text-sm font-medium text-[#143d37] dark:text-emerald-200">
                {{ variable }}
              </div>
              <div class="text-xs text-slate-600 dark:text-slate-300">
                {{ description }}
              </div>
            </div>
          </div>
          
          <div class="mt-4 border-t border-[#ded2bb] pt-4 dark:border-white/10">
            <p class="text-xs text-slate-500 dark:text-slate-400">
              {{ $t('gestlab.general.labels.vap_proposal_templates.show.variable_help') }}
            </p>
          </div>
        </details>

        <!-- DANGER ZONE CARD -->
        <div class="rounded-[26px] border border-red-200 bg-white/95 p-6 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-red-500/30 dark:bg-slate-950/85">
          <h3 class="mb-4 text-lg font-semibold text-red-900 dark:text-red-300">
            {{ $t('gestlab.general.labels.vap_proposal_templates.show.danger_zone') }}
          </h3>
          <p class="mb-4 text-sm text-slate-600 dark:text-slate-300">
            {{ $t('gestlab.general.labels.vap_proposal_templates.show.delete_warning') }}
          </p>
          <button
            @click="confirmDelete"
            :disabled="template.proposals_count > 0"
            :class="[
              'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
              template.proposals_count > 0
                ? 'cursor-not-allowed bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-500'
                : 'bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2'
            ]"
          >
            <TrashIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_proposal_templates.show.delete_template') }}
          </button>
          
          <div v-if="template.proposals_count > 0" class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-500/30 dark:bg-red-500/10">
            <div class="flex items-start gap-2">
              <ExclamationTriangleIcon class="mt-0.5 h-4 w-4 flex-shrink-0 text-red-600 dark:text-red-300" />
              <p class="text-xs text-red-700 dark:text-red-300">
                {{ $t('gestlab.general.labels.vap_proposal_templates.show.cannot_delete', { count: template.proposals_count }) }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- DELETE CONFIRMATION MODAL -->
  <ConfirmationModal
    :show="showDeleteModal"
    @close="showDeleteModal = false"
    @confirm="deleteTemplate"
    :danger="true"
  >
    <template #title>
      {{ $t('gestlab.general.labels.vap_proposal_templates.show.delete_confirm_title') }}
    </template>
    <template #content>
      <p class="text-sm text-slate-600 dark:text-slate-300">
        {{ $t('gestlab.general.labels.vap_proposal_templates.show.delete_confirm_message', { name: template.name }) }}
      </p>
      <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-500/30 dark:bg-red-500/10">
        <div class="flex items-start gap-2">
          <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-600 dark:text-red-300" />
          <div>
            <p class="text-sm font-medium text-red-900 dark:text-red-200">
              {{ $t('gestlab.general.labels.vap_proposal_templates.show.delete_warning_title') }}
            </p>
            <p class="mt-1 text-xs text-red-700 dark:text-red-300">
              {{ $t('gestlab.general.labels.vap_proposal_templates.show.delete_warning_detail') }}
            </p>
          </div>
        </div>
      </div>
    </template>
  </ConfirmationModal>

  <!-- STATUS CONFIRMATION MODAL -->
  <ConfirmationModal
    :show="showStatusModal"
    @close="showStatusModal = false"
    @confirm="toggleTemplateStatus"
    :danger="template.is_active"
  >
    <template #title>
      {{ template.is_active ? $t('gestlab.general.labels.vap_proposal_templates.show.status_modal_deactivate_title') : $t('gestlab.general.labels.vap_proposal_templates.show.status_modal_activate_title') }}
    </template>
    <template #content>
      <p class="text-sm text-slate-600 dark:text-slate-300">
        {{
          template.is_active
            ? $t('gestlab.general.labels.vap_proposal_templates.show.status_modal_deactivate_content')
            : $t('gestlab.general.labels.vap_proposal_templates.show.status_modal_activate_content')
        }}
      </p>
    </template>
  </ConfirmationModal>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { Link, router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import {
  ArrowLeftIcon, DocumentTextIcon, DocumentPlusIcon,
  CodeBracketIcon, DocumentDuplicateIcon, ChartBarIcon,
  ArrowRightIcon, UserIcon, PencilSquareIcon,
  ArrowPathIcon, ArrowDownTrayIcon, DocumentArrowDownIcon,
  VariableIcon, TrashIcon, ExclamationTriangleIcon,
  BeakerIcon, BugAntIcon, CpuChipIcon,
  GlobeAltIcon, CakeIcon, DocumentChartBarIcon
} from '@heroicons/vue/24/outline'
import ConfirmationModal from '@/Components/confirm-dialog.vue'
import { useToast } from 'vue-toastification'

const props = defineProps({
  template: Object,
  recentProposals: {
    type: Array,
    default: () => []
  },
  variables: {
    type: Object,
    default: () => ({
      '{proposal_number}': 'Número da proposta',
      '{customer_name}': 'Nome do cliente',
      '{customer_code}': 'Código do cliente',
      '{expiry_date}': 'Data de validade',
      '{service_location}': 'Local do serviço',
      '{lab_name}': 'Nome do laboratório',
      '{lab_details}': 'Dados do laboratório',
      '{customer_details}': 'Dados do cliente',
      '{department}': 'Departamento',
      '{items_table}': 'Tabela de Itens/Serviços',
      '{items_list}': 'Lista de itens/serviços',
      '{summary_table}': 'Resumo financeiro',
      '{sub_total}': 'Subtotal',
      '{total}': 'Total',
      '{banking_details}': 'Dados bancários',
      '{document_keywords}': 'Palavras-chave documentais',
      '{signature_block}': 'Bloco de assinatura',
    })
  }
})

// UI State
const showRawView = ref(false)
const showDeleteModal = ref(false)
const showStatusModal = ref(false)
const copied = ref(false)
const toast = useToast()

// Category icons mapping
const categoryIcons = {
  chemical: BeakerIcon,
  microbiology: BugAntIcon,
  physical: CpuChipIcon,
  environmental: GlobeAltIcon,
  food: CakeIcon,
  compliance: DocumentChartBarIcon,
  'field-services': GlobeAltIcon,
  general: DocumentChartBarIcon,
}

const categoryColors = {
  chemical: { bg: 'bg-sky-100 dark:bg-sky-500/15', text: 'text-sky-900 dark:text-sky-200' },
  microbiology: { bg: 'bg-emerald-100 dark:bg-emerald-500/15', text: 'text-emerald-900 dark:text-emerald-200' },
  physical: { bg: 'bg-amber-100 dark:bg-amber-500/15', text: 'text-amber-900 dark:text-amber-200' },
  environmental: { bg: 'bg-teal-100 dark:bg-teal-500/15', text: 'text-teal-900 dark:text-teal-200' },
  food: { bg: 'bg-rose-100 dark:bg-rose-500/15', text: 'text-rose-900 dark:text-rose-200' },
  compliance: { bg: 'bg-[#f7f1e6] dark:bg-emerald-400/10', text: 'text-[#143d37] dark:text-emerald-200' },
  'field-services': { bg: 'bg-[#f7f1e6] dark:bg-amber-400/10', text: 'text-[#946b23] dark:text-amber-200' },
  general: { bg: 'bg-slate-100 dark:bg-slate-800', text: 'text-slate-900 dark:text-slate-200' },
}

const statusBadges = {
  PENDING: { class: 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200', text: trans('gestlab.general.labels.vap_proposals.status.pending') },
  SENT: { class: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200', text: trans('gestlab.general.labels.vap_proposals.status.sent') },
  VIEWED: { class: 'bg-teal-100 text-teal-800 dark:bg-teal-500/15 dark:text-teal-200', text: trans('gestlab.general.labels.vap_proposals.status.viewed') },
  ACCEPTED: { class: 'bg-green-100 text-green-800 dark:bg-green-500/15 dark:text-green-200', text: trans('gestlab.general.labels.vap_proposals.status.accepted') },
  REJECTED: { class: 'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-200', text: trans('gestlab.general.labels.vap_proposals.status.rejected') },
  REVISED: { class: 'bg-orange-100 text-orange-800 dark:bg-orange-500/15 dark:text-orange-200', text: trans('gestlab.general.labels.vap_proposals.status.revised') },
  EXPIRED: { class: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200', text: trans('gestlab.general.labels.vap_proposals.status.expired') },
}

// Computed Properties
const templateVariables = computed(() => {
  const matches = props.template.content.match(/\{([^}]+)\}/g) || []
  return [...new Set(matches.map(v => v.slice(1, -1)))].sort()
})

const variablesCount = computed(() => {
  return templateVariables.value.length
})

const formattedTemplateContent = computed(() => {
  // Highlight variables in the template content
  return props.template.content
    .replace(/\{([^}]+)\}/g, '<span class="bg-yellow-100 text-yellow-800 px-1 rounded font-mono">{$1}</span>')
    .replace(/\n/g, '<br>')
})

const acceptedProposalsCount = computed(() => {
  return Number(props.template.accepted_proposals_count || 0)
})

const pendingProposalsCount = computed(() => {
  return Number(props.template.pending_proposals_count || 0)
})

const rejectedProposalsCount = computed(() => {
  return Number(props.template.rejected_proposals_count || 0)
})

const calculateAcceptanceRate = computed(() => {
  if (props.template.proposals_count === 0) return 0
  return Math.round((acceptedProposalsCount.value / props.template.proposals_count) * 100)
})

const availableVariables = computed(() => {
  // Merge template variables with system variables
  const merged = { ...props.variables }
  
  // Add template-specific variables that might not be in the default list
  templateVariables.value.forEach(variable => {
    const key = `{${variable}}`
    if (!merged[key]) {
      merged[key] = trans('gestlab.general.labels.vap_proposal_templates.show.custom_variable', { name: variable })
    }
  })
  
  return merged
})

// Methods
const getCategoryIcon = (category) => {
  return categoryIcons[category] || DocumentChartBarIcon
}

const getCategoryColor = (category) => {
  return categoryColors[category] || categoryColors.general
}

const getCategoryLabel = (category) => {
  const labels = {
    chemical: trans('gestlab.general.labels.vap_proposal_templates.categories.chemical'),
    microbiology: trans('gestlab.general.labels.vap_proposal_templates.categories.microbiology'),
    physical: trans('gestlab.general.labels.vap_proposal_templates.categories.physical'),
    environmental: trans('gestlab.general.labels.vap_proposal_templates.categories.environmental'),
    food: trans('gestlab.general.labels.vap_proposal_templates.categories.food'),
    compliance: trans('gestlab.general.labels.vap_proposal_templates.categories.compliance'),
    'field-services': trans('gestlab.general.labels.vap_proposal_templates.categories.field_services'),
    general: trans('gestlab.general.labels.vap_proposal_templates.categories.general'),
  }
  return labels[category] || labels.general
}

const getStatusBadge = (status) => {
  return statusBadges[status] || statusBadges.PENDING
}

const formatDateTime = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('pt-AO', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatCurrency = (amount) => {
  if (!amount) return '0,00'
  return parseFloat(amount).toLocaleString('pt-AO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })
}

const toggleRawView = () => {
  showRawView.value = !showRawView.value
}

const copyRawContent = async () => {
  try {
    await navigator.clipboard.writeText(props.template.content)
    copied.value = true
    toast.success(trans('gestlab.general.labels.vap_proposal_templates.show.notifications.copy_success'))
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch (err) {
    copied.value = false
    toast.error(trans('gestlab.general.labels.vap_proposal_templates.show.notifications.copy_error'))
  }
}

const requestStatusToggle = () => {
  showStatusModal.value = true
}

const toggleTemplateStatus = async () => {
  const newStatus = !props.template.is_active
  
  try {
    const response = await fetch(route('vap-proposals.templates.toggle-status', props.template.id), {
      method: 'PUT',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    })

    const payload = await response.json()
    
    if (!response.ok || !payload.success) {
      throw new Error(payload.message || trans('gestlab.general.labels.vap_proposal_templates.show.notifications.status_error'))
    }

    showStatusModal.value = false
    toast.success(newStatus ? trans('gestlab.general.labels.vap_proposal_templates.show.notifications.activated') : trans('gestlab.general.labels.vap_proposal_templates.show.notifications.deactivated'))
    router.reload({ only: ['template'] })
  } catch (error) {
    toast.error(error.message || trans('gestlab.general.labels.vap_proposal_templates.show.notifications.status_request_error'))
  }
}

const exportTemplate = async () => {
  try {
    const templateData = {
      name: props.template.name,
      content: props.template.content,
      category: props.template.category,
      description: props.template.description,
      theme_preset: props.template.theme_preset,
      is_active: props.template.is_active,
      layout_schema: props.template.layout_schema || {},
      export_settings: props.template.export_settings || {},
      variables: templateVariables.value,
      variable_reference: props.variables,
      metadata: {
        exported_at: new Date().toISOString(),
        exported_by: window.userName || trans('gestlab.general.labels.vap_proposal_templates.show.exported_by_fallback'),
        total_proposals: props.template.proposals_count,
        acceptance_rate: calculateAcceptanceRate.value
      }
    }
    
    const blob = new Blob([JSON.stringify(templateData, null, 2)], {
      type: 'application/json'
    })
    
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `modelo-proposta-${props.template.name.toLowerCase().replace(/\s+/g, '-')}-${new Date().toISOString().split('T')[0]}.json`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    toast.success(trans('gestlab.general.labels.vap_proposal_templates.show.notifications.export_json_success'))
  } catch (error) {
    toast.error(trans('gestlab.general.labels.vap_proposal_templates.show.notifications.export_error'))
  }
}

const exportAsPdf = () => {
  window.open(route('vap-proposals.templates.pdf', props.template.id), '_blank')
}

const confirmDelete = () => {
  if (props.template.proposals_count > 0) {
    toast.warning(trans('gestlab.general.labels.vap_proposal_templates.show.notifications.cannot_delete_in_use'))
    return
  }
  
  showDeleteModal.value = true
}

const deleteTemplate = () => {
  router.delete(route('vap-proposals.templates.destroy', props.template.id), {
    onSuccess: () => {
      showDeleteModal.value = false
      router.visit(route('vap-proposals.templates.index'))
    }
  })
}

onMounted(() => {
  // Scroll to top on mount
  window.scrollTo(0, 0)
})
</script>
