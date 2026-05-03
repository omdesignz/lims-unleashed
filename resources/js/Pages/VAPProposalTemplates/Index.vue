<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <DocumentDuplicateIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_proposal_templates.title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.vap_proposal_templates.description') }}
            <span class="font-semibold text-blue-900">
              {{ templates.total }} {{ $t('gestlab.general.labels.vap_proposal_templates.total') }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <Link 
            :href="route('vap-proposals.templates.create')"
            class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
          >
            <PlusCircleIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_proposal_templates.create_new') }}
          </Link>
        </div>
      </div>
    </div>

    <!-- STATISTICS GRID -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div 
        class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">
              {{ $t('gestlab.general.labels.vap_proposal_templates.stats.total') }}
            </p>
            <p class="mt-2 text-3xl font-bold text-gray-900">
              {{ templates.total }}
            </p>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100">
            <DocumentTextIcon class="h-6 w-6 text-blue-900" />
          </div>
        </div>
      </div>
      
      <div 
        class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">
              {{ $t('gestlab.general.labels.vap_proposal_templates.stats.active') }}
            </p>
            <p class="mt-2 text-3xl font-bold text-gray-900">
              {{ activeTemplatesCount }}
            </p>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
            <CheckCircleIcon class="h-6 w-6 text-green-900" />
          </div>
        </div>
      </div>
      
      <div 
        class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">
              {{ $t('gestlab.general.labels.vap_proposal_templates.stats.inactive') }}
            </p>
            <p class="mt-2 text-3xl font-bold text-gray-900">
              {{ inactiveTemplatesCount }}
            </p>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
            <NoSymbolIcon class="h-6 w-6 text-gray-900" />
          </div>
        </div>
      </div>
      
      <div 
        class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">
              {{ $t('gestlab.general.labels.vap_proposal_templates.stats.used') }}
            </p>
            <p class="mt-2 text-3xl font-bold text-gray-900">
              {{ totalProposalsCount }}
            </p>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-purple-100">
            <ChartBarIcon class="h-6 w-6 text-purple-900" />
          </div>
        </div>
      </div>
    </div>

    <!-- FILTERS & SEARCH -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <div class="relative flex-1 max-w-md">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
            <input
              v-model="search"
              type="search"
              :placeholder="$t('gestlab.general.labels.vap_proposal_templates.search_placeholder')"
              class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
              @input="debouncedSearch"
            />
          </div>
          
          <select 
            v-model="statusFilter"
            class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
          >
            <option value="all">{{ $t('gestlab.general.labels.vap_proposal_templates.filters.all') }}</option>
            <option value="active">{{ $t('gestlab.general.labels.vap_proposal_templates.filters.active') }}</option>
            <option value="inactive">{{ $t('gestlab.general.labels.vap_proposal_templates.filters.inactive') }}</option>
          </select>
          
          <select 
            v-model="categoryFilter"
            class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
          >
            <option value="all">{{ $t('gestlab.general.labels.vap_proposal_templates.filters.all_categories') }}</option>
            <option value="chemical">{{ $t('gestlab.general.labels.vap_proposal_templates.categories.chemical') }}</option>
            <option value="microbiology">{{ $t('gestlab.general.labels.vap_proposal_templates.categories.microbiology') }}</option>
            <option value="physical">{{ $t('gestlab.general.labels.vap_proposal_templates.categories.physical') }}</option>
            <option value="environmental">{{ $t('gestlab.general.labels.vap_proposal_templates.categories.environmental') }}</option>
            <option value="food">{{ $t('gestlab.general.labels.vap_proposal_templates.categories.food') }}</option>
            <option value="general">{{ $t('gestlab.general.labels.vap_proposal_templates.categories.general') }}</option>
          </select>
        </div>
        
        <div class="flex items-center gap-2">
          <button
            @click="resetFilters"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
          >
            <ArrowPathIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.vap_proposal_templates.reset_filters') }}
          </button>
        </div>
      </div>
    </div>

    <!-- TEMPLATES GRID -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <ListBulletIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_proposal_templates.list.title') }}
            <span class="text-sm font-normal text-gray-500 ml-2">
              ({{ templates.total }} {{ $t('gestlab.general.buttons.items') }})
            </span>
          </h2>
          <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500">
              {{ $t('gestlab.general.labels.vap_proposal_templates.sort_by') }}
            </span>
            <select 
              v-model="sortBy"
              class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
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
        <DocumentDuplicateIcon class="mx-auto h-12 w-12 text-gray-300" />
        <h3 class="mt-4 text-sm font-semibold text-gray-900">
          {{ $t('gestlab.general.labels.vap_proposal_templates.empty_state.title') }}
        </h3>
        <p class="mt-2 text-sm text-gray-500">
          {{ $t('gestlab.general.labels.vap_proposal_templates.empty_state.description') }}
        </p>
        <Link 
          :href="route('vap-proposals.templates.create')"
          class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
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
          class="group relative bg-white rounded-xl border border-gray-200 hover:border-blue-900 hover:shadow-lg transition-all duration-200 overflow-hidden"
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
                  <h3 class="text-sm font-semibold text-gray-900 group-hover:text-blue-900">
                    {{ template.name }}
                  </h3>
                  <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs text-gray-500">
                      {{ getCategoryLabel(template.category) }}
                    </span>
                    <span class="text-xs text-gray-400">•</span>
                    <span class="text-xs text-gray-500">
                      {{ template.user.name }}
                    </span>
                  </div>
                </div>
              </div>
              
              <!-- STATUS BADGE -->
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                template.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
              ]">
                {{ template.is_active ? $t('gestlab.general.labels.vap_proposal_templates.active') : $t('gestlab.general.labels.vap_proposal_templates.inactive') }}
              </span>
            </div>

            <!-- TEMPLATE PREVIEW -->
            <div class="mt-4">
              <div class="text-sm text-gray-600 line-clamp-3 h-12">
                {{ stripHtml(template.content) }}
              </div>
            </div>

            <!-- STATISTICS -->
            <div class="mt-6 grid grid-cols-3 gap-4">
              <div class="text-center">
                <div class="text-lg font-bold text-blue-900">
                  {{ template.proposals_count || 0 }}
                </div>
                <div class="text-xs text-gray-500">
                  {{ $t('gestlab.general.labels.vap_proposal_templates.used') }}
                </div>
              </div>
              
              <div class="text-center">
                <div class="text-lg font-bold text-gray-900">
                  {{ calculateAcceptanceRate(template) }}%
                </div>
                <div class="text-xs text-gray-500">
                  {{ $t('gestlab.general.labels.vap_proposal_templates.acceptance_rate') }}
                </div>
              </div>
              
              <div class="text-center">
                <div class="text-lg font-bold text-green-900">
                  {{ formatDate(template.updated_at) }}
                </div>
                <div class="text-xs text-gray-500">
                  {{ $t('gestlab.general.labels.vap_proposal_templates.last_updated') }}
                </div>
              </div>
            </div>
          </div>

          <!-- CARD FOOTER -->
          <div class="border-t border-gray-200 bg-gray-50 px-6 py-4">
            <div class="flex items-center justify-between">
              <div class="text-xs text-gray-500">
                {{ $t('gestlab.general.labels.vap_proposal_templates.created_on') }} {{ formatDate(template.created_at) }}
              </div>
              
              <div class="flex items-center gap-2">
                <Link 
                  :href="route('vap-proposals.templates.show', template.id)"
                  class="text-blue-900 hover:text-blue-800 p-1.5 rounded-lg hover:bg-blue-50 transition-colors"
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
          <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center">
            <div class="flex items-center gap-2">
              <Link 
                :href="route('vap-proposals.templates.show', template.id)"
                class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-50"
              >
                <EyeIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.vap_proposal_templates.view') }}
              </Link>
              
              <Link 
                :href="route('vap-proposals.templates.edit', template.id)"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
              >
                <PencilSquareIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.vap_proposal_templates.use_template') }}
              </Link>
            </div>
          </div>
        </div>
      </div>

      <!-- PAGINATION -->
      <div v-if="templates.data.length > 0" class="px-6 py-4 border-t border-gray-200">
        <Pagination :links="templates.links" :from="templates.from" :to="templates.to" :total="templates.total" :current_page="templates.current_page" :last_page="templates.last_page" />
      </div>
    </div>

    <!-- QUICK ACTIONS PANEL -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">
        {{ $t('gestlab.general.labels.vap_proposal_templates.quick_actions.title') }}
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <button
          @click="importTemplate"
          class="text-left p-4 border border-gray-200 rounded-lg hover:border-blue-900 hover:bg-blue-50 transition-all duration-200 group"
        >
          <div class="flex items-center gap-3 mb-3">
            <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center">
              <ArrowDownTrayIcon class="h-5 w-5 text-blue-900" />
            </div>
            <div>
              <h3 class="font-semibold text-gray-900 group-hover:text-blue-900">
                {{ $t('gestlab.general.labels.vap_proposal_templates.quick_actions.import') }}
              </h3>
            </div>
          </div>
          <p class="text-sm text-gray-600">
            {{ $t('gestlab.general.labels.vap_proposal_templates.quick_actions.import_desc') }}
          </p>
        </button>
        
        <button
          @click="exportTemplates"
          class="text-left p-4 border border-gray-200 rounded-lg hover:border-green-900 hover:bg-green-50 transition-all duration-200 group"
        >
          <div class="flex items-center gap-3 mb-3">
            <div class="h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center">
              <ArrowUpTrayIcon class="h-5 w-5 text-green-900" />
            </div>
            <div>
              <h3 class="font-semibold text-gray-900 group-hover:text-green-900">
                {{ $t('gestlab.general.labels.vap_proposal_templates.quick_actions.export') }}
              </h3>
            </div>
          </div>
          <p class="text-sm text-gray-600">
            {{ $t('gestlab.general.labels.vap_proposal_templates.quick_actions.export_desc') }}
          </p>
        </button>
        
        <button
          @click="showUsageReport"
          class="text-left p-4 border border-gray-200 rounded-lg hover:border-purple-900 hover:bg-purple-50 transition-all duration-200 group"
        >
          <div class="flex items-center gap-3 mb-3">
            <div class="h-10 w-10 rounded-lg bg-purple-100 flex items-center justify-center">
              <ChartBarIcon class="h-5 w-5 text-purple-900" />
            </div>
            <div>
              <h3 class="font-semibold text-gray-900 group-hover:text-purple-900">
                {{ $t('gestlab.general.labels.vap_proposal_templates.quick_actions.report') }}
              </h3>
            </div>
          </div>
          <p class="text-sm text-gray-600">
            {{ $t('gestlab.general.labels.vap_proposal_templates.quick_actions.report_desc') }}
          </p>
        </button>
      </div>

      <div class="mt-5 flex flex-col gap-4 border-t border-gray-200 pt-5 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h3 class="text-sm font-semibold text-gray-900">Modelos pré-definidos</h3>
          <p class="mt-1 text-sm text-gray-600">Escolha uma base pronta para propostas comerciais, conformidade ISO ou serviços com recolha.</p>
        </div>

        <div class="flex items-center gap-3">
          <label class="text-sm text-gray-600">Formato de exportação</label>
          <select v-model="exportFormat" class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900">
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
          class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-left transition hover:border-blue-900 hover:bg-blue-50"
        >
          <div class="text-sm font-semibold text-gray-900">{{ preset.name }}</div>
          <div class="mt-1 text-xs uppercase tracking-wide text-gray-500">{{ preset.category }}</div>
          <p class="mt-3 text-sm text-gray-600">{{ preset.description }}</p>
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
      <p class="text-sm text-gray-600">
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
      <h2 class="text-lg font-semibold text-gray-900 mb-4">
        {{ $t('gestlab.general.labels.vap_proposal_templates.import.title') }}
      </h2>
      
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ $t('gestlab.general.labels.vap_proposal_templates.import.select_file') }}
          </label>
          <div 
            @dragover.prevent="dragOver = true"
            @dragleave="dragOver = false"
            @drop="handleFileDrop"
            :class="[
              'border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors',
              dragOver ? 'border-blue-900 bg-blue-50' : 'border-gray-300 hover:border-blue-900 hover:bg-blue-50'
            ]"
          >
            <input
              type="file"
              ref="fileInput"
              @change="handleFileSelect"
              accept=".json,.txt,.xlsx,.csv"
              class="hidden"
            />
            
            <CloudArrowUpIcon class="mx-auto h-12 w-12 text-gray-400" />
            <p class="mt-4 text-sm text-gray-600">
              {{ $t('gestlab.general.labels.vap_proposal_templates.import.drag_drop') }}
            </p>
            <button
              type="button"
              @click="$refs.fileInput.click()"
              class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
            >
              {{ $t('gestlab.general.labels.vap_proposal_templates.import.browse_files') }}
            </button>
            <p class="mt-2 text-xs text-gray-500">
              JSON, TXT, Excel (.xlsx) ou CSV
            </p>
          </div>
        </div>
        
        <div v-if="importFile" class="bg-gray-50 rounded-lg p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-900">
                {{ importFile.name }}
              </p>
              <p class="text-xs text-gray-500">
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
          class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
        >
          {{ $t('gestlab.general.buttons.cancel') }}
        </button>
        <button
          @click="processImport"
          :disabled="!importFile"
          :class="[
            'rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm',
            importFile ? 'bg-blue-900 hover:bg-blue-800' : 'bg-gray-400 cursor-not-allowed'
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

// Category icons mapping
const categoryIcons = {
  chemical: BeakerIcon,
  microbiology: BugAntIcon,
  physical: CpuChipIcon,
  environmental: GlobeAltIcon,
  food: CakeIcon,
  general: DocumentChartBarIcon,
}

const categoryColors = {
  chemical: { bg: 'bg-blue-100', text: 'text-blue-900' },
  microbiology: { bg: 'bg-green-100', text: 'text-green-900' },
  physical: { bg: 'bg-yellow-100', text: 'text-yellow-900' },
  environmental: { bg: 'bg-emerald-100', text: 'text-emerald-900' },
  food: { bg: 'bg-red-100', text: 'text-red-900' },
  general: { bg: 'bg-gray-100', text: 'text-gray-900' },
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

const getCategoryIcon = (category) => {
  return categoryIcons[category] || DocumentChartBarIcon
}

const getCategoryColor = (category) => {
  return categoryColors[category] || categoryColors.general
}

const getCategoryLabel = (category) => {
  const labels = {
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
  return new Date(date).toLocaleDateString('pt-BR', {
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
  
  // Esta lógica precisaria vir do backend
  // Por enquanto, retornamos um valor fictício
  const acceptedProposals = Math.floor(template.proposals_count * 0.75) // 75% de aceitação
  return Math.round((acceptedProposals / template.proposals_count) * 100)
}

const confirmDelete = (template) => {
  if (template.proposals_count > 0) {
    alert('Não é possível excluir templates que estão em uso por propostas.')
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
    const response = await axios.post(route('vap-proposals.templates.import'), formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    
    if (response.data.success) {
      showImportModal.value = false
      importFile.value = null
      router.reload()
    }
  } catch (error) {
    alert('Erro ao importar template. Verifique o formato do arquivo.')
  }
}

const exportTemplates = async () => {
  try {
    const response = await axios.get(route('vap-proposals.templates.export', {
      format: exportFormat.value,
    }), {
      responseType: 'blob'
    })
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `templates-${new Date().toISOString().split('T')[0]}.${exportFormat.value}`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    alert('Erro ao exportar templates.')
  }
}

const showUsageReport = () => {
  // Implementar lógica para mostrar relatório de uso
  alert('Relatório de uso em desenvolvimento...')
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
