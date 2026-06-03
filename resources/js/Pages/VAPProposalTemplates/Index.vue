<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <section class="overflow-hidden rounded-[34px] border border-[#ded2bb] bg-[#fbfaf6] shadow-[0_26px_70px_-44px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950">
      <div class="bg-[radial-gradient(circle_at_top_left,rgba(199,154,67,0.22),transparent_34%),linear-gradient(135deg,#fffaf0,#f7f1e6_58%,#143d37_58%,#143d37)] px-6 py-7 dark:bg-[radial-gradient(circle_at_top_left,rgba(199,154,67,0.18),transparent_34%),linear-gradient(135deg,#17231f,#101815_58%,#0b1210_58%,#0b1210)] sm:px-8">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
          <div class="max-w-4xl">
            <span class="inline-flex items-center gap-2 rounded-full border border-[#c79a43]/40 bg-white/85 px-3 py-1 text-xs font-black uppercase tracking-[0.24em] text-[#143d37] shadow-sm dark:bg-white/10 dark:text-amber-100">
              <DocumentDuplicateIcon class="h-4 w-4 text-[#c79a43]" />
              {{ $t('gestlab.general.labels.vap_proposal_templates.surface.library') }}
            </span>
            <h1 class="mt-5 text-3xl font-black tracking-[-0.04em] text-[#10221d] dark:text-white sm:text-5xl">
              {{ $t('gestlab.general.labels.vap_proposal_templates.title') }}
            </h1>
            <p class="mt-4 max-w-3xl text-base font-medium leading-7 text-[#59665f] dark:text-slate-300">
              {{ $t('gestlab.general.labels.vap_proposal_templates.description') }}
              <span class="font-black text-[#143d37] dark:text-emerald-100">
                {{ templates.total }} {{ $t('gestlab.general.labels.vap_proposal_templates.total') }}
              </span>
            </p>
          </div>

          <Link
            :href="route('vap-proposals.templates.create')"
            class="inline-flex items-center justify-center gap-2 rounded-[20px] bg-[#143d37] px-5 py-3 text-sm font-black text-white shadow-[0_18px_42px_-24px_rgba(20,61,55,0.75)] transition hover:bg-[#0f302b] focus:outline-none focus:ring-2 focus:ring-[#c79a43] focus:ring-offset-2 dark:ring-offset-slate-950"
          >
            <PlusCircleIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_proposal_templates.create_new') }}
          </Link>
        </div>
      </div>

      <div class="grid gap-4 border-t border-[#ded2bb] bg-white/55 px-6 py-5 dark:border-white/10 dark:bg-white/5 sm:grid-cols-2 xl:grid-cols-4 sm:px-8">
        <article
          v-for="stat in templateStatCards"
          :key="stat.key"
          class="rounded-[24px] border border-[#ded2bb] bg-white/85 p-4 shadow-[0_18px_48px_-36px_rgba(20,61,55,0.48)] dark:border-white/10 dark:bg-white/5"
        >
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.18em] text-[#78847c] dark:text-slate-400">{{ $t(stat.labelKey) }}</p>
              <p class="mt-3 text-2xl font-black tracking-[-0.04em] text-[#10221d] dark:text-white">{{ stat.value }}</p>
            </div>
            <span :class="['flex h-11 w-11 shrink-0 items-center justify-center rounded-[18px]', stat.tone]">
              <component :is="stat.icon" class="h-5 w-5" />
            </span>
          </div>
        </article>
      </div>
    </section>

    <!-- FILTERS & SEARCH -->
    <div class="rounded-[30px] border border-[#ded2bb] bg-white/90 p-6 shadow-[0_22px_70px_-46px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90">
      <div class="mb-5 flex items-center justify-between gap-4">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.28em] text-[#c79a43]">{{ $t('gestlab.general.labels.vap_proposal_templates.surface.search') }}</p>
          <h2 class="mt-2 text-2xl font-black tracking-[-0.03em] text-[#10221d] dark:text-white">{{ $t('gestlab.general.labels.vap_proposal_templates.surface.refine_library') }}</h2>
        </div>
      </div>
      <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex flex-1 flex-col gap-3 lg:flex-row lg:items-center">
          <div class="relative flex-1 max-w-md">
            <MagnifyingGlassIcon class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
              v-model="search"
              type="search"
              :placeholder="$t('gestlab.general.labels.vap_proposal_templates.search_placeholder')"
              class="w-full rounded-[20px] border border-[#ded2bb] bg-[#fbfaf6] py-3 pl-11 pr-4 text-sm font-semibold text-[#33413a] shadow-sm outline-none transition placeholder:text-[#9aa59d] focus:border-[#c79a43] focus:ring-2 focus:ring-[#c79a43]/30 dark:border-white/10 dark:bg-white/5 dark:text-slate-100 dark:placeholder:text-slate-500"
              @input="debouncedSearch"
            />
          </div>
          
          <select 
            v-model="statusFilter"
            class="rounded-[20px] border border-[#ded2bb] bg-[#fbfaf6] px-4 py-3 text-sm font-black text-[#143d37] shadow-sm outline-none transition focus:border-[#c79a43] focus:ring-2 focus:ring-[#c79a43]/30 dark:border-white/10 dark:bg-white/5 dark:text-emerald-100"
          >
            <option value="all">{{ $t('gestlab.general.labels.vap_proposal_templates.filters.all') }}</option>
            <option value="active">{{ $t('gestlab.general.labels.vap_proposal_templates.filters.active') }}</option>
            <option value="inactive">{{ $t('gestlab.general.labels.vap_proposal_templates.filters.inactive') }}</option>
          </select>
          
          <select 
            v-model="categoryFilter"
            class="rounded-[20px] border border-[#ded2bb] bg-[#fbfaf6] px-4 py-3 text-sm font-black text-[#143d37] shadow-sm outline-none transition focus:border-[#c79a43] focus:ring-2 focus:ring-[#c79a43]/30 dark:border-white/10 dark:bg-white/5 dark:text-emerald-100"
          >
            <option value="all">{{ $t('gestlab.general.labels.vap_proposal_templates.filters.all_categories') }}</option>
            <option value="general">{{ $t('gestlab.general.labels.vap_proposal_templates.categories.general') }}</option>
            <option value="compliance">{{ $t('gestlab.general.labels.vap_proposal_templates.categories.compliance') }}</option>
            <option value="field-services">{{ $t('gestlab.general.labels.vap_proposal_templates.categories.field_services') }}</option>
            <option value="chemical">{{ $t('gestlab.general.labels.vap_proposal_templates.categories.chemical') }}</option>
            <option value="microbiology">{{ $t('gestlab.general.labels.vap_proposal_templates.categories.microbiology') }}</option>
            <option value="physical">{{ $t('gestlab.general.labels.vap_proposal_templates.categories.physical') }}</option>
            <option value="environmental">{{ $t('gestlab.general.labels.vap_proposal_templates.categories.environmental') }}</option>
            <option value="food">{{ $t('gestlab.general.labels.vap_proposal_templates.categories.food') }}</option>
          </select>
        </div>
        
        <div class="flex items-center gap-2">
          <button
            @click="resetFilters"
            class="inline-flex items-center gap-2 rounded-[20px] border border-[#ded2bb] bg-[#fbfaf6] px-4 py-3 text-sm font-black text-[#143d37] shadow-sm transition hover:border-[#c79a43] hover:bg-[#fff7e5] dark:border-white/10 dark:bg-white/5 dark:text-emerald-100 dark:hover:bg-white/10"
          >
            <ArrowPathIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.vap_proposal_templates.reset_filters') }}
          </button>
        </div>
      </div>
    </div>

    <!-- TEMPLATES GRID -->
    <div class="overflow-hidden rounded-[28px] border border-[#d8cbb4] bg-white/95 shadow-[0_18px_50px_-30px_rgba(20,61,55,0.28)] dark:border-white/10 dark:bg-slate-950/85">
      <div class="border-b border-[#ded2bb] px-6 py-4 dark:border-white/10">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
            <ListBulletIcon class="h-5 w-5 text-[#143d37] dark:text-emerald-200" />
            {{ $t('gestlab.general.labels.vap_proposal_templates.list.title') }}
            <span class="ml-2 text-sm font-normal text-slate-500 dark:text-slate-400">
              ({{ templates.total }} {{ $t('gestlab.general.buttons.items') }})
            </span>
          </h2>
          <div class="flex items-center gap-2">
            <span class="text-sm text-slate-500 dark:text-slate-400">
              {{ $t('gestlab.general.labels.vap_proposal_templates.sort_by') }}
            </span>
            <select 
              v-model="sortBy"
              class="rounded-2xl border border-[#d8cbb4] bg-white px-4 py-2.5 text-sm text-slate-900 focus:border-[#143d37] focus:outline-none focus:ring-2 focus:ring-[#143d37]/20 dark:border-white/10 dark:bg-slate-900 dark:text-slate-100"
            >
              <option value="name">{{ $t('gestlab.general.labels.vap_proposal_templates.sort.name') }}</option>
              <option value="created_at">{{ $t('gestlab.general.labels.vap_proposal_templates.sort.newest') }}</option>
              <option value="proposals_count">{{ $t('gestlab.general.labels.vap_proposal_templates.sort.most_used') }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- EMPTY STATE -->
      <div v-if="templates.data.length === 0" class="p-12 text-center">
        <DocumentDuplicateIcon class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-700" />
        <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">
          {{ $t('gestlab.general.labels.vap_proposal_templates.empty_state.title') }}
        </h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
          {{ $t('gestlab.general.labels.vap_proposal_templates.empty_state.description') }}
        </p>
        <Link 
          :href="route('vap-proposals.templates.create')"
          class="mt-6 inline-flex items-center gap-2 rounded-2xl bg-[#143d37] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#0f302b] focus:outline-none focus:ring-2 focus:ring-[#c79a43] focus:ring-offset-2"
        >
          <PlusCircleIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.vap_proposal_templates.create_first') }}
        </Link>
      </div>

      <!-- TEMPLATES GRID -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
        <!-- TEMPLATE CARD -->
        <div 
          v-for="template in templates.data"
          :key="template.id"
          class="group relative overflow-hidden rounded-[24px] border border-[#d8cbb4] bg-white/95 transition-all duration-200 hover:border-[#143d37] hover:shadow-[0_20px_55px_-32px_rgba(20,61,55,0.55)] dark:border-white/10 dark:bg-slate-950/85"
          v-motion
          :initial="{ opacity: 0, y: 20 }"
          :enter="{ opacity: 1, y: 0 }"
        >
          <!-- CARD HEADER -->
          <div class="p-6">
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-3">
                <div :class="[
                  'flex h-10 w-10 items-center justify-center rounded-lg',
                  getCategoryColor(template.category).bg
                ]">
                  <component 
                    :is="getCategoryIcon(template.category)" 
                    class="h-5 w-5"
                    :class="getCategoryColor(template.category).text"
                  />
                </div>
                <div>
                  <h3 class="text-sm font-semibold text-slate-900 group-hover:text-[#143d37] dark:text-white dark:group-hover:text-emerald-200">
                    {{ template.name }}
                  </h3>
                  <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                      {{ getCategoryLabel(template.category) }}
                    </span>
                    <span class="text-xs text-slate-400">•</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                      {{ template.user.name }}
                    </span>
                  </div>
                </div>
              </div>
              
              <!-- STATUS BADGE -->
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                template.is_active
                  ? 'bg-green-100 text-green-800 dark:bg-green-500/15 dark:text-green-200'
                  : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200'
              ]">
                {{ template.is_active ? $t('gestlab.general.labels.vap_proposal_templates.active') : $t('gestlab.general.labels.vap_proposal_templates.inactive') }}
              </span>
            </div>

            <!-- TEMPLATE PREVIEW -->
            <div class="mt-4">
              <div class="text-sm text-slate-600 line-clamp-3 h-12 dark:text-slate-300">
                {{ stripHtml(template.content) }}
              </div>
            </div>

            <!-- STATISTICS -->
            <div class="mt-6 grid grid-cols-3 gap-4">
              <div class="text-center">
                <div class="text-lg font-bold text-[#143d37] dark:text-emerald-200">
                  {{ template.proposals_count || 0 }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400">
                  {{ $t('gestlab.general.labels.vap_proposal_templates.used') }}
                </div>
              </div>
              
              <div class="text-center">
                <div class="text-lg font-bold text-slate-900 dark:text-white">
                  {{ calculateAcceptanceRate(template) }}%
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400">
                  {{ $t('gestlab.general.labels.vap_proposal_templates.acceptance_rate') }}
                </div>
              </div>
              
              <div class="text-center">
                <div class="text-lg font-bold text-green-900">
                  {{ formatDate(template.updated_at) }}
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400">
                  {{ $t('gestlab.general.labels.vap_proposal_templates.last_updated') }}
                </div>
              </div>
            </div>
          </div>

          <!-- CARD FOOTER -->
          <div class="border-t border-[#ded2bb] bg-[#f7f1e6]/70 px-6 py-4 dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center justify-between">
              <div class="text-xs text-slate-500 dark:text-slate-400">
                {{ $t('gestlab.general.labels.vap_proposal_templates.created_on') }} {{ formatDate(template.created_at) }}
              </div>
              
              <div class="flex items-center gap-2">
                <Link 
                  :href="route('vap-proposals.templates.show', template.id)"
                  class="rounded-lg p-1.5 text-[#143d37] transition-colors hover:bg-white hover:text-[#0f302b] dark:text-emerald-200 dark:hover:bg-white/10"
                  :title="$t('gestlab.general.labels.vap_proposal_templates.view_details')"
                >
                  <EyeIcon class="h-4 w-4" />
                </Link>
                
                <Link 
                  :href="route('vap-proposals.templates.edit', template.id)"
                  class="text-yellow-600 hover:text-yellow-800 p-1.5 rounded-lg hover:bg-yellow-50 transition-colors"
                  :title="$t('gestlab.general.labels.vap_proposal_templates.edit')"
                >
                  <PencilSquareIcon class="h-4 w-4" />
                </Link>
                
                <button 
                  @click="confirmDelete(template)"
                  class="text-red-600 hover:text-red-800 p-1.5 rounded-lg hover:bg-red-50 transition-colors"
                  :title="$t('gestlab.general.labels.vap_proposal_templates.delete')"
                  :disabled="template.proposals_count > 0"
                >
                  <TrashIcon class="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>
          
          <!-- QUICK ACTIONS OVERLAY -->
          <div class="absolute inset-0 flex items-center justify-center bg-[#0b1513]/70 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
            <div class="flex items-center gap-2">
              <Link 
                :href="route('vap-proposals.templates.show', template.id)"
                class="inline-flex items-center gap-2 rounded-2xl bg-white px-4 py-2 text-sm font-semibold text-[#263c36] shadow-sm hover:bg-[#f7f1e6]"
              >
                <EyeIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.vap_proposal_templates.view') }}
              </Link>
              
              <Link 
                :href="route('vap-proposals.templates.edit', template.id)"
                class="inline-flex items-center gap-2 rounded-2xl bg-[#143d37] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#0f302b]"
              >
                <PencilSquareIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.vap_proposal_templates.use_template') }}
              </Link>
            </div>
          </div>
        </div>
      </div>

      <!-- PAGINATION -->
      <div v-if="templates.data.length > 0" class="border-t border-[#ded2bb] px-6 py-4 dark:border-white/10">
        <Pagination :links="templates.links" :from="templates.from" :to="templates.to" :total="templates.total" :current_page="templates.current_page" :last_page="templates.last_page" />
      </div>
    </div>

    <!-- QUICK ACTIONS PANEL -->
    <div class="rounded-[28px] border border-[#d8cbb4] bg-white/95 p-6 shadow-[0_18px_50px_-30px_rgba(20,61,55,0.28)] dark:border-white/10 dark:bg-slate-950/85">
      <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">
        {{ $t('gestlab.general.labels.vap_proposal_templates.quick_actions.title') }}
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <button
          @click="importTemplate"
          class="group rounded-2xl border border-slate-200 p-4 text-left transition-all duration-200 hover:border-[#d8cbb4] hover:bg-[#f7f1e6] dark:border-slate-800 dark:hover:bg-emerald-400/10"
        >
          <div class="flex items-center gap-3 mb-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#f7f1e6] dark:bg-emerald-400/10">
              <ArrowDownTrayIcon class="h-5 w-5 text-[#143d37] dark:text-emerald-200" />
            </div>
            <div>
              <h3 class="font-semibold text-slate-900 group-hover:text-[#143d37] dark:text-white dark:group-hover:text-emerald-200">
                {{ $t('gestlab.general.labels.vap_proposal_templates.quick_actions.import') }}
              </h3>
            </div>
          </div>
          <p class="text-sm text-slate-600 dark:text-slate-300">
            {{ $t('gestlab.general.labels.vap_proposal_templates.quick_actions.import_desc') }}
          </p>
        </button>
        
        <button
          @click="exportTemplates"
          class="group rounded-2xl border border-slate-200 p-4 text-left transition-all duration-200 hover:border-green-700 hover:bg-green-50 dark:border-slate-800 dark:hover:bg-green-400/10"
        >
          <div class="flex items-center gap-3 mb-3">
            <div class="h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center">
              <ArrowUpTrayIcon class="h-5 w-5 text-green-900" />
            </div>
            <div>
              <h3 class="font-semibold text-slate-900 group-hover:text-green-900 dark:text-white dark:group-hover:text-green-200">
                {{ $t('gestlab.general.labels.vap_proposal_templates.quick_actions.export') }}
              </h3>
            </div>
          </div>
          <p class="text-sm text-slate-600 dark:text-slate-300">
            {{ $t('gestlab.general.labels.vap_proposal_templates.quick_actions.export_desc') }}
          </p>
        </button>
        
        <button
          @click="showUsageReport"
          class="group rounded-2xl border border-slate-200 p-4 text-left transition-all duration-200 hover:border-amber-700 hover:bg-amber-50 dark:border-slate-800 dark:hover:bg-amber-400/10"
        >
          <div class="flex items-center gap-3 mb-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-100 dark:bg-amber-400/10">
              <ChartBarIcon class="h-5 w-5 text-amber-900 dark:text-amber-200" />
            </div>
            <div>
              <h3 class="font-semibold text-slate-900 group-hover:text-amber-900 dark:text-white dark:group-hover:text-amber-200">
                {{ $t('gestlab.general.labels.vap_proposal_templates.quick_actions.report') }}
              </h3>
            </div>
          </div>
          <p class="text-sm text-slate-600 dark:text-slate-300">
            {{ $t('gestlab.general.labels.vap_proposal_templates.quick_actions.report_desc') }}
          </p>
        </button>
      </div>

      <div class="mt-5 flex flex-col gap-4 border-t border-[#ded2bb] pt-5 dark:border-white/10 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
            {{ $t('gestlab.general.labels.vap_proposal_templates.surface.presets') }}
          </h3>
          <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
            {{ $t('gestlab.general.labels.vap_proposal_templates.surface.presets_help') }}
          </p>
        </div>

        <div class="flex items-center gap-3">
          <label class="text-sm text-slate-600 dark:text-slate-300">
            {{ $t('gestlab.general.labels.vap_proposal_templates.surface.export_format') }}
          </label>
          <select v-model="exportFormat" class="rounded-2xl border border-[#d8cbb4] bg-white px-4 py-2.5 text-sm text-slate-900 focus:border-[#143d37] focus:outline-none focus:ring-2 focus:ring-[#143d37]/20 dark:border-white/10 dark:bg-slate-900 dark:text-slate-100">
            <option value="xlsx">Excel (.xlsx)</option>
            <option value="csv">CSV (.csv)</option>
            <option value="json">JSON (.json)</option>
          </select>
        </div>
      </div>

      <div v-if="props.presets.length" class="mt-5 grid gap-4 md:grid-cols-3">
        <Link
          v-for="preset in props.presets"
          :key="preset.slug"
          :href="route('vap-proposals.templates.create', { preset: preset.slug })"
          class="rounded-2xl border border-slate-200 bg-[#f7f1e6]/70 p-4 text-left transition hover:border-[#143d37] hover:bg-[#f7f1e6] dark:border-slate-800 dark:bg-white/5 dark:hover:bg-emerald-400/10"
        >
          <div class="text-sm font-semibold text-slate-900 dark:text-white">{{ preset.name }}</div>
          <div class="mt-1 text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ getCategoryLabel(preset.category) }}</div>
          <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">{{ preset.description }}</p>
        </Link>
      </div>
    </div>
  </div>

  <!-- DELETE CONFIRMATION MODAL -->
  <ConfirmationModal
    :show="showDeleteModal"
    @close="showDeleteModal = false"
    @confirm="deleteTemplate"
  >
    <template #title>
      {{ $t('gestlab.general.labels.vap_proposal_templates.delete_modal.title') }}
    </template>
    <template #content>
      <p class="text-sm text-slate-600 dark:text-slate-300">
        {{ $t('gestlab.general.labels.vap_proposal_templates.delete_modal.message', { name: selectedTemplate?.name }) }}
      </p>
      
      <div v-if="selectedTemplate?.proposals_count > 0" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
        <div class="flex items-start gap-2">
          <ExclamationTriangleIcon class="h-5 w-5 text-red-600 mt-0.5 flex-shrink-0" />
          <div>
            <p class="text-sm font-medium text-red-900">
              {{ $t('gestlab.general.labels.vap_proposal_templates.delete_modal.warning_title') }}
            </p>
            <p class="text-sm text-red-700 mt-1">
              {{ $t('gestlab.general.labels.vap_proposal_templates.delete_modal.warning_message', { count: selectedTemplate?.proposals_count }) }}
            </p>
          </div>
        </div>
      </div>
      
      <div v-else class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <div class="flex items-start gap-2">
          <ExclamationTriangleIcon class="h-5 w-5 text-yellow-600 mt-0.5 flex-shrink-0" />
          <div>
            <p class="text-sm font-medium text-yellow-900">
              {{ $t('gestlab.general.labels.vap_proposal_templates.delete_modal.confirm_warning') }}
            </p>
          </div>
        </div>
      </div>
    </template>
  </ConfirmationModal>

  <!-- IMPORT MODAL -->
  <Modal :show="showImportModal" @close="showImportModal = false" max-width="lg">
    <div class="p-6">
      <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
        {{ $t('gestlab.general.labels.vap_proposal_templates.import.title') }}
      </h2>
      
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
            {{ $t('gestlab.general.labels.vap_proposal_templates.import.select_file') }}
          </label>
          <div 
            @dragover.prevent="dragOver = true"
            @dragleave="dragOver = false"
            @drop="handleFileDrop"
            :class="[
              'border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors',
              dragOver ? 'border-[#143d37] bg-[#f7f1e6]' : 'border-slate-300 dark:border-slate-700 hover:border-[#143d37] hover:bg-[#f7f1e6]'
            ]"
          >
            <input
              type="file"
              ref="fileInput"
              @change="handleFileSelect"
              accept=".json,.txt,.xlsx,.csv"
              class="hidden"
            />
            
            <CloudArrowUpIcon class="mx-auto h-12 w-12 text-slate-400" />
            <p class="mt-4 text-sm text-slate-600 dark:text-slate-300">
              {{ $t('gestlab.general.labels.vap_proposal_templates.import.drag_drop') }}
            </p>
            <button
              type="button"
              @click="$refs.fileInput.click()"
              class="mt-4 inline-flex items-center gap-2 rounded-lg bg-[#143d37] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#0f302b]"
            >
              {{ $t('gestlab.general.labels.vap_proposal_templates.import.browse_files') }}
            </button>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
              JSON, TXT, Excel (.xlsx) ou CSV
            </p>
          </div>
        </div>
        
        <div v-if="importFile" class="bg-slate-50 dark:bg-slate-900/70 rounded-lg p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-900 dark:text-white">
                {{ importFile.name }}
              </p>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ formatFileSize(importFile.size) }}
              </p>
            </div>
            <button
              @click="importFile = null"
              class="text-red-600 hover:text-red-800"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>
        </div>
      </div>

      <div class="mt-6 flex items-center justify-end gap-3">
        <button
          @click="showImportModal = false"
          class="rounded-lg border border-slate-300 dark:border-slate-700 bg-white px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:bg-slate-900/70"
        >
          {{ $t('gestlab.general.buttons.cancel') }}
        </button>
        <button
          @click="processImport"
          :disabled="!importFile"
          :class="[
            'rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm',
            importFile ? 'bg-[#143d37] hover:bg-[#0f302b]' : 'bg-slate-400 cursor-not-allowed'
          ]"
        >
          {{ $t('gestlab.general.labels.vap_proposal_templates.import.process') }}
        </button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { Link, router } from '@inertiajs/vue3'
import { 
  DocumentDuplicateIcon, PlusCircleIcon, DocumentTextIcon,
  CheckCircleIcon, NoSymbolIcon, ChartBarIcon,
  MagnifyingGlassIcon, ArrowPathIcon, ListBulletIcon,
  EyeIcon, PencilSquareIcon, TrashIcon,
  ArrowDownTrayIcon, ArrowUpTrayIcon,
  ExclamationTriangleIcon, CloudArrowUpIcon, XMarkIcon,
  BeakerIcon, BugAntIcon, CpuChipIcon,
  GlobeAltIcon, CakeIcon, DocumentChartBarIcon
} from '@heroicons/vue/24/outline'
import Pagination from '@/Components/Pagination.vue'
import ConfirmationModal from '@/Components/confirm-dialog.vue'
import Modal from '@/Components/modal.vue'
import debounce from 'lodash/debounce'
import datePickerEnhanced from '@/Components/date-picker-enhanced.vue'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import { useToast } from 'vue-toastification'
import { trans } from 'laravel-vue-i18n'

const props = defineProps({
  templates: Object,
  filters: Object,
  presets: {
    type: Array,
    default: () => [],
  },
})

const search = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || 'all')
const categoryFilter = ref(props.filters.category || 'all')
const sortBy = ref(props.filters.sort || 'created_at')
const showDeleteModal = ref(false)
const showImportModal = ref(false)
const selectedTemplate = ref(null)
const importFile = ref(null)
const dragOver = ref(false)
const exportFormat = ref('xlsx')
const toast = useToast()

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

const downloadFilename = (response, fallback) => {
  const disposition = response.headers.get('Content-Disposition') || response.headers.get('content-disposition') || ''
  const match = disposition.match(/filename="?([^"]+)"?/i)

  return match?.[1] || fallback
}

// Category icons mapping
const categoryIcons = {
  compliance: CheckCircleIcon,
  'field-services': GlobeAltIcon,
  chemical: BeakerIcon,
  microbiology: BugAntIcon,
  physical: CpuChipIcon,
  environmental: GlobeAltIcon,
  food: CakeIcon,
  general: DocumentChartBarIcon,
}

const categoryColors = {
  compliance: { bg: 'bg-emerald-100 dark:bg-emerald-500/15', text: 'text-emerald-900 dark:text-emerald-200' },
  'field-services': { bg: 'bg-amber-100 dark:bg-amber-500/15', text: 'text-amber-900 dark:text-amber-200' },
  chemical: { bg: 'bg-sky-100 dark:bg-sky-500/15', text: 'text-sky-900 dark:text-sky-200' },
  microbiology: { bg: 'bg-green-100 dark:bg-green-500/15', text: 'text-green-900 dark:text-green-200' },
  physical: { bg: 'bg-yellow-100 dark:bg-yellow-500/15', text: 'text-yellow-900 dark:text-yellow-200' },
  environmental: { bg: 'bg-emerald-100 dark:bg-emerald-500/15', text: 'text-emerald-900 dark:text-emerald-200' },
  food: { bg: 'bg-red-100 dark:bg-red-500/15', text: 'text-red-900 dark:text-red-200' },
  general: { bg: 'bg-slate-100 dark:bg-slate-800', text: 'text-slate-900 dark:text-slate-200' },
}

// Computed Properties
const activeTemplatesCount = computed(() => {
  return props.templates.data.filter(t => t.is_active).length
})

const inactiveTemplatesCount = computed(() => {
  return props.templates.data.filter(t => !t.is_active).length
})

const totalProposalsCount = computed(() => {
  return props.templates.data.reduce((sum, template) => {
    return sum + (template.proposals_count || 0)
  }, 0)
})

const templateStatCards = computed(() => [
  {
    key: 'total',
    labelKey: 'gestlab.general.labels.vap_proposal_templates.stats.total',
    value: props.templates.total,
    icon: DocumentTextIcon,
    tone: 'bg-[#f7f1e6] text-[#143d37] dark:bg-emerald-400/10 dark:text-emerald-100',
  },
  {
    key: 'active',
    labelKey: 'gestlab.general.labels.vap_proposal_templates.stats.active',
    value: activeTemplatesCount.value,
    icon: CheckCircleIcon,
    tone: 'bg-emerald-50 text-emerald-800 dark:bg-emerald-400/10 dark:text-emerald-100',
  },
  {
    key: 'inactive',
    labelKey: 'gestlab.general.labels.vap_proposal_templates.stats.inactive',
    value: inactiveTemplatesCount.value,
    icon: NoSymbolIcon,
    tone: 'bg-[#fbfaf6] text-[#59665f] dark:bg-white/10 dark:text-slate-300',
  },
  {
    key: 'used',
    labelKey: 'gestlab.general.labels.vap_proposal_templates.stats.used',
    value: totalProposalsCount.value,
    icon: ChartBarIcon,
    tone: 'bg-amber-50 text-amber-800 dark:bg-amber-400/10 dark:text-amber-100',
  },
])

const getCategoryIcon = (category) => {
  return categoryIcons[category] || DocumentChartBarIcon
}

const getCategoryColor = (category) => {
  return categoryColors[category] || categoryColors.general
}

const getCategoryLabel = (category) => {
  const labels = {
    compliance: 'Conformidade ISO 17025',
    'field-services': 'Recolha e logística',
    chemical: 'Química',
    microbiology: 'Microbiologia',
    physical: 'Física',
    environmental: 'Ambiental',
    food: 'Alimentos',
    general: 'Geral',
  }
  return labels[category] || 'Geral'
}

// Methods
const debouncedSearch = debounce(() => {
  router.get(route('vap-proposals.templates.index'), 
    { 
      search: search.value, 
      status: statusFilter.value,
      category: categoryFilter.value,
      sort: sortBy.value
    },
    { preserveState: true }
  )
}, 500)

const resetFilters = () => {
  search.value = ''
  statusFilter.value = 'all'
  categoryFilter.value = 'all'
  sortBy.value = 'created_at'
  debouncedSearch()
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('pt-AO', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const stripHtml = (html) => {
  if (!html) return ''
  const stripped = html.replace(/<[^>]*>/g, '')
  return stripped.length > 150 ? stripped.substring(0, 150) + '...' : stripped
}

const calculateAcceptanceRate = (template) => {
  if (!template.proposals_count || template.proposals_count === 0) return 0

  const acceptedProposals = Number(template.accepted_proposals_count || 0)
  return Math.round((acceptedProposals / template.proposals_count) * 100)
}

const confirmDelete = (template) => {
  if (template.proposals_count > 0) {
    toast.warning(trans('gestlab.general.labels.vap_proposal_templates.notifications.cannot_delete_in_use'))
    return
  }
  
  selectedTemplate.value = template
  showDeleteModal.value = true
}

const deleteTemplate = () => {
  router.delete(route('vap-proposals.templates.destroy', selectedTemplate.value.id), {
    onSuccess: () => {
      showDeleteModal.value = false
      selectedTemplate.value = null
    }
  })
}

const importTemplate = () => {
  showImportModal.value = true
}

const handleFileDrop = (event) => {
  event.preventDefault()
  dragOver.value = false
  
  const files = event.dataTransfer.files
  if (files.length > 0) {
    importFile.value = files[0]
  }
}

const handleFileSelect = (event) => {
  const files = event.target.files
  if (files.length > 0) {
    importFile.value = files[0]
  }
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const processImport = async () => {
  if (!importFile.value) return
  
  const formData = new FormData()
  formData.append('template_file', importFile.value)
  
  try {
    const response = await fetch(route('vap-proposals.templates.import'), {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken(),
      },
      body: formData,
    })
    const payload = await response.json()
    
    if (!response.ok || !payload.success) {
      throw new Error(payload.message || trans('gestlab.general.labels.vap_proposal_templates.notifications.import_request_error'))
    }

    showImportModal.value = false
    importFile.value = null
    toast.success(trans('gestlab.general.labels.vap_proposal_templates.notifications.import_success'))
    router.reload()
  } catch (error) {
    toast.error(error.message || trans('gestlab.general.labels.vap_proposal_templates.notifications.import_error'))
  }
}

const exportTemplates = async () => {
  try {
    const response = await fetch(route('vap-proposals.templates.export', {
      format: exportFormat.value,
    }))

    if (!response.ok) {
      throw new Error(trans('gestlab.general.labels.vap_proposal_templates.notifications.export_request_error'))
    }
    
    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', downloadFilename(response, `modelos-proposta-${new Date().toISOString().split('T')[0]}.${exportFormat.value}`))
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    toast.success(trans('gestlab.general.labels.vap_proposal_templates.notifications.export_success'))
  } catch (error) {
    toast.error(error.message || trans('gestlab.general.labels.vap_proposal_templates.notifications.export_error'))
  }
}

const showUsageReport = () => {
  toast.info(trans('gestlab.general.labels.vap_proposal_templates.notifications.usage_report'))
}

// Watchers
watch([statusFilter, categoryFilter, sortBy], () => {
  debouncedSearch()
})

// Initialize
onMounted(() => {
  // Configurar inicializações se necessário
})
</script>

<style scoped>

</style>
