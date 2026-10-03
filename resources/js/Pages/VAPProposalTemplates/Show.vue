<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-5 sm:px-6">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
          <div class="max-w-4xl">
            <div class="flex flex-wrap items-center gap-3">
              <span class="ds-chip">
                <component
                  :is="getCategoryIcon(template.category)"
                  class="h-4 w-4"
                />
                {{ getCategoryLabel(template.category) }}
              </span>
              <span :class="[
                'ds-badge',
                templateActive
                  ? 'ds-badge-success'
                  : 'ds-badge-neutral'
              ]">
                {{ templateActive ? $t('gestlab.general.labels.vap_proposal_templates.active') : $t('gestlab.general.labels.vap_proposal_templates.inactive') }}
              </span>
            </div>
            <h1 class="ds-heading mt-3 break-words text-2xl sm:text-3xl">
              {{ template.name }}
            </h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">
              {{ template.description || $t('gestlab.general.labels.vap_proposal_templates.show.description') }}
            </p>
          </div>

          <div class="flex flex-col gap-3 sm:flex-row">
            <Link
              :href="route('vap-proposals.templates.index')"
              class="ds-button ds-button-secondary"
            >
              <ArrowLeftIcon class="h-4 w-4" />
              {{ $t('gestlab.general.buttons.back') }}
            </Link>
            <Link
              :href="route('vap-proposals.create') + '?template_id=' + template.id"
              class="ds-button ds-button-primary"
            >
              <DocumentPlusIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.vap_proposal_templates.show.use_this_template') }}
            </Link>
          </div>
        </div>
      </div>

      <div class="grid gap-px bg-[var(--ds-border)] sm:grid-cols-2 xl:grid-cols-4">
        <article class="bg-[var(--ds-panel)] px-5 py-4 sm:px-6">
          <p class="text-xs font-bold text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_proposal_templates.used') }}</p>
          <p class="mt-1 text-xl font-bold text-[var(--ds-text)]">{{ template.proposals_count || 0 }}</p>
        </article>
        <article class="bg-[var(--ds-panel)] px-5 py-4 sm:px-6">
          <p class="text-xs font-bold text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_proposal_templates.acceptance_rate') }}</p>
          <p class="mt-1 text-xl font-bold text-[var(--ds-text)]">{{ calculateAcceptanceRate }}%</p>
        </article>
        <article class="bg-[var(--ds-panel)] px-5 py-4 sm:px-6">
          <p class="text-xs font-bold text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_proposal_templates.show.variables') }}</p>
          <p class="mt-1 text-xl font-bold text-[var(--ds-text)]">{{ variablesCount }}</p>
        </article>
        <article class="bg-[var(--ds-panel)] px-5 py-4 sm:px-6">
          <p class="text-xs font-bold text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_proposal_templates.show.created_by') }}</p>
          <p class="mt-1 truncate text-base font-bold text-[var(--ds-text)]">{{ template.user?.name || '—' }}</p>
        </article>
      </div>
    </section>

    <!-- MAIN CONTENT GRID -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="min-w-0 space-y-6">
        <!-- TEMPLATE CONTENT CARD -->
        <div class="ds-panel overflow-hidden">
          <div class="flex items-center gap-2 border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:px-6">
            <DocumentTextIcon class="h-4 w-4 text-primary-700 dark:text-primary-200" />
            <h2 class="ds-heading text-sm">
              {{ $t('gestlab.general.labels.vap_proposal_templates.show.content') }}
            </h2>
          </div>
          
          <div class="p-6">
            <!-- VARIABLES PREVIEW -->
            <div class="mb-6">
              <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold text-[var(--ds-text)]">
                  {{ $t('gestlab.general.labels.vap_proposal_templates.show.variables') }}
                </h3>
                <span class="text-xs font-semibold text-[var(--ds-text-soft)]">
                  {{ variablesCount }} {{ $t('gestlab.general.labels.vap_proposal_templates.show.variables') }}
                </span>
              </div>
              <div class="flex flex-wrap gap-2">
                <span 
                  v-for="variable in templateVariables"
                  :key="variable"
                  class="ds-badge ds-badge-info gap-1 font-mono"
                >
                  <CodeBracketIcon class="h-3 w-3" />
                  {{ variable }}
                </span>
              </div>
            </div>

            <!-- TEMPLATE CONTENT -->
            <div class="border-t border-[var(--ds-border)] pt-6">
              <div class="prose min-h-[400px] max-w-none rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-6 dark:prose-invert">
                <div v-html="formattedTemplateContent"></div>
              </div>
            </div>

            <!-- RAW CONTENT VIEW -->
            <div class="mt-6 border-t border-[var(--ds-border)] pt-6">
              <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold text-[var(--ds-text)]">
                  {{ $t('gestlab.general.labels.vap_proposal_templates.show.raw_content') }}
                </h3>
                <button
                  @click="toggleRawView"
                  class="text-xs font-bold text-primary-700 hover:text-primary-900 dark:text-primary-200"
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
                  class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-bold text-white hover:bg-slate-700"
                >
                  <DocumentDuplicateIcon class="h-3 w-3" />
                  {{ copied ? $t('gestlab.general.buttons.copied') : $t('gestlab.general.buttons.copy') }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- TEMPLATE USAGE HISTORY -->
        <div v-if="template.proposals_count > 0" class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:px-6">
            <div class="flex items-center justify-between">
              <h2 class="ds-heading flex items-center gap-2 text-sm">
                <ChartBarIcon class="h-4 w-4 text-primary-700 dark:text-primary-200" />
                {{ $t('gestlab.general.labels.vap_proposal_templates.show.usage') }}
                <span class="ml-2 text-sm font-normal text-slate-500 dark:text-slate-400">
                  ({{ template.proposals_count }} {{ $t('gestlab.general.labels.vap_proposal_templates.show.proposals_using') }})
                </span>
              </h2>
              <div class="text-sm font-bold text-primary-700 dark:text-primary-200">
                {{ calculateAcceptanceRate }}% {{ $t('gestlab.general.labels.vap_proposal_templates.acceptance_rate') }}
              </div>
            </div>
          </div>
          
          <div class="p-6">
            <!-- USAGE STATS -->
            <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3 text-center">
                <div class="text-xl font-bold text-[var(--ds-text)]">{{ template.proposals_count }}</div>
                <div class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ $t('gestlab.general.labels.vap_proposal_templates.used') }}</div>
              </div>
              
              <div class="rounded-lg border border-green-100 bg-green-50 p-3 text-center dark:border-green-500/20 dark:bg-green-500/10">
                <div class="text-xl font-bold text-green-900 dark:text-green-200">{{ acceptedProposalsCount }}</div>
                <div class="mt-1 text-xs font-semibold text-green-700 dark:text-green-300">{{ $t('gestlab.general.labels.vap_proposal_templates.show.accepted') }}</div>
              </div>
              
              <div class="rounded-lg border border-yellow-100 bg-yellow-50 p-3 text-center dark:border-yellow-500/20 dark:bg-yellow-500/10">
                <div class="text-xl font-bold text-yellow-900 dark:text-yellow-200">{{ pendingProposalsCount }}</div>
                <div class="mt-1 text-xs font-semibold text-yellow-700 dark:text-yellow-300">{{ $t('gestlab.general.labels.vap_proposal_templates.show.pending') }}</div>
              </div>
              
              <div class="rounded-lg border border-red-100 bg-red-50 p-3 text-center dark:border-red-500/20 dark:bg-red-500/10">
                <div class="text-xl font-bold text-red-900 dark:text-red-200">{{ rejectedProposalsCount }}</div>
                <div class="mt-1 text-xs font-semibold text-red-700 dark:text-red-300">{{ $t('gestlab.general.labels.vap_proposal_templates.show.rejected') }}</div>
              </div>
            </div>

            <!-- RECENT PROPOSALS -->
            <div v-if="recentProposals.length > 0">
              <h3 class="mb-3 text-sm font-bold text-[var(--ds-text)]">
                {{ $t('gestlab.general.labels.vap_proposal_templates.show.recent_proposals') }}
              </h3>
              <div class="space-y-3">
                <div 
                  v-for="proposal in recentProposals"
                  :key="proposal.id"
                  class="flex flex-col gap-3 rounded-lg border border-[var(--ds-border)] p-3 transition-colors hover:bg-[var(--ds-panel-subtle)] sm:flex-row sm:items-center sm:justify-between"
                >
                  <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                      <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-500/10">
                        <DocumentTextIcon class="h-4 w-4 text-primary-700 dark:text-primary-200" />
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
                    <span :class="['ds-badge', getStatusBadge(proposal.status).class]">
                      {{ getStatusBadge(proposal.status).text }}
                    </span>
                    <span class="text-sm font-medium text-slate-900 dark:text-slate-100">
                      AOA {{ formatCurrency(proposal.total) }}
                    </span>
                    <Link 
                      v-if="proposal.can_view"
                      :href="route('vap-proposals.show', proposal.id)"
                      class="ds-table-action px-2"
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
                  class="text-sm font-bold text-primary-700 hover:text-primary-900 dark:text-primary-200"
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
      <aside class="space-y-4 lg:sticky lg:top-6 lg:self-start">
        <!-- TEMPLATE INFO CARD -->
        <div class="ds-panel p-5">
          <h3 class="ds-heading mb-4 text-sm">
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
                <span class="text-sm text-slate-900 dark:text-slate-100">{{ template.user?.name || '—' }}</span>
              </div>
            </div>

            <details class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
              <summary class="cursor-pointer list-none text-sm font-bold text-[var(--ds-text)]">
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
                    <span :class="['ds-badge', templateActive ? 'ds-badge-success' : 'ds-badge-neutral']">
                      {{ templateActive ? $t('gestlab.general.labels.vap_proposal_templates.active') : $t('gestlab.general.labels.vap_proposal_templates.inactive') }}
                    </span>
                  </div>
                </div>
              </div>
            </details>
          </div>
        </div>

        <!-- ACTIONS CARD -->
        <div class="ds-panel p-5">
          <h3 class="ds-heading mb-4 text-sm">
            {{ $t('gestlab.general.labels.vap_proposal_templates.actions.title') }}
          </h3>
          <div class="space-y-3">
            <Link 
              :href="route('vap-proposals.templates.edit', template.id)"
              class="ds-button ds-button-primary w-full"
            >
              <PencilSquareIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_proposal_templates.show.edit_template') }}
            </Link>
            
            <Link 
              :href="route('vap-proposals.create') + '?template_id=' + template.id"
              class="ds-button ds-button-secondary w-full"
            >
              <DocumentPlusIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_proposal_templates.show.create_proposal') }}
            </Link>
            
            <button
              type="button"
              @click="requestStatusToggle"
              :disabled="statusProcessing || archive.processing.value"
              :class="[
                'ds-button w-full',
                templateActive
                  ? 'ds-button-secondary text-yellow-700 dark:text-yellow-300'
                  : 'ds-button-secondary text-green-700 dark:text-green-300'
              ]"
            >
              <ArrowPathIcon class="h-5 w-5" />
              {{ templateActive ? $t('gestlab.general.labels.vap_proposal_templates.show.deactivate') : $t('gestlab.general.labels.vap_proposal_templates.show.activate') }}
            </button>
            <p v-if="statusRefreshError" class="text-sm text-red-700 dark:text-red-300" role="alert">{{ statusRefreshError }}</p>
          </div>
          
          <details class="mt-4 border-t border-[var(--ds-border)] pt-4">
            <summary class="cursor-pointer list-none text-sm font-bold text-[var(--ds-text)]">
              {{ $t('gestlab.general.labels.vap_proposal_templates.show.export') }}
            </summary>
            <div class="mt-3 space-y-2">
              <button
                @click="exportTemplate"
                class="ds-button ds-button-secondary w-full"
              >
                <ArrowDownTrayIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.vap_proposal_templates.show.export_json') }}
              </button>
              <button
                @click="exportAsPdf"
                class="ds-button ds-button-secondary w-full"
              >
                <DocumentArrowDownIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.vap_proposal_templates.show.export_pdf') }}
              </button>
            </div>
          </details>
        </div>

        <!-- VARIABLE REFERENCE CARD -->
        <details class="ds-panel p-5">
          <summary class="ds-heading flex cursor-pointer list-none items-center gap-2 text-sm">
            <VariableIcon class="h-4 w-4 text-primary-700 dark:text-primary-200" />
            {{ $t('gestlab.general.labels.vap_proposal_templates.show.variable_reference') }}
          </summary>
          <div class="mt-4 space-y-3">
            <div 
              v-for="(description, variable) in availableVariables"
              :key="variable"
              class="rounded-lg border border-[var(--ds-border)] p-3 transition-colors hover:bg-[var(--ds-panel-subtle)]"
            >
              <div class="mb-1 font-mono text-sm font-bold text-primary-700 dark:text-primary-200">
                {{ variable }}
              </div>
              <div class="text-xs text-slate-600 dark:text-slate-300">
                {{ description }}
              </div>
            </div>
          </div>
          
          <div class="mt-4 border-t border-[var(--ds-border)] pt-4">
            <p class="text-xs text-slate-500 dark:text-slate-400">
              {{ $t('gestlab.general.labels.vap_proposal_templates.show.variable_help') }}
            </p>
          </div>
        </details>

        <!-- DANGER ZONE CARD -->
        <div class="rounded-lg border border-red-200 bg-[var(--ds-panel)] p-5 dark:border-red-500/30">
          <h3 class="mb-3 text-sm font-bold text-red-900 dark:text-red-300">
            {{ $t('gestlab.general.labels.vap_proposal_templates.show.danger_zone') }}
          </h3>
          <p class="mb-4 text-sm text-slate-600 dark:text-slate-300">
            {{ $t('gestlab.general.labels.vap_proposal_templates.show.delete_warning') }}
          </p>
          <button
            type="button"
            @click="confirmDelete"
            :disabled="template.proposals_count > 0 || statusProcessing || archive.processing.value"
            :class="[
              'ds-button w-full',
              template.proposals_count > 0
                ? 'cursor-not-allowed bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-500'
                : 'ds-button-danger'
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
      </aside>
    </div>

  <!-- DELETE CONFIRMATION MODAL -->
  <ConfirmationModal
    v-if="showDeleteModal"
    :title="$t('gestlab.general.labels.vap_proposal_templates.show.delete_confirm_title')"
    :disabled="archive.processing.value"
    :confirm="archive.processing.value ? 'A arquivar…' : $t('gestlab.general.buttons.confirm')"
    keep-open-on-confirm
    @canceled="cancelDelete"
    @confirmed="deleteTemplate"
  >
    <template #default>
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
      <p v-if="archive.processing.value" class="ds-copy mt-3 text-sm" role="status">A arquivar o modelo…</p>
      <p v-if="archive.failed.value" class="mt-3 text-sm text-red-700 dark:text-red-300" role="alert">{{ archive.message.value }}</p>
    </template>
  </ConfirmationModal>

  <!-- STATUS CONFIRMATION MODAL -->
  <ConfirmationModal
    v-if="showStatusModal"
    :title="pendingStatus?.is_active ? $t('gestlab.general.labels.vap_proposal_templates.show.status_modal_activate_title') : $t('gestlab.general.labels.vap_proposal_templates.show.status_modal_deactivate_title')"
    :variant="pendingStatus?.is_active ? 'success' : 'warning'"
    :disabled="statusProcessing"
    :confirm="statusProcessing ? 'A actualizar…' : $t('gestlab.general.buttons.confirm')"
    keep-open-on-confirm
    @canceled="cancelStatusToggle"
    @confirmed="toggleTemplateStatus"
  >
    <template #default>
      <p class="text-sm text-slate-600 dark:text-slate-300">
        {{
          pendingStatus?.is_active
            ? $t('gestlab.general.labels.vap_proposal_templates.show.status_modal_activate_content')
            : $t('gestlab.general.labels.vap_proposal_templates.show.status_modal_deactivate_content')
        }}
      </p>
      <p v-if="statusProcessing" class="ds-copy mt-3 text-sm" role="status">A actualizar o estado…</p>
      <p v-if="statusError" class="mt-3 text-sm text-red-700 dark:text-red-300" role="alert">{{ statusError }}</p>
    </template>
  </ConfirmationModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Link, router, useHttp } from '@inertiajs/vue3'
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
import { useRecordArchive } from '@/Composables/useRecordArchive'

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
const pendingStatus = ref(null)
const statusError = ref('')
const statusRefreshError = ref('')
const confirmedStatus = ref(null)
const templateActive = computed(() => confirmedStatus.value?.id === props.template.id ? confirmedStatus.value.is_active : props.template.is_active)
watch(() => props.template, syncConfirmedTemplateStatus)
const statusSubmitting = ref(false)
const statusRequest = useHttp({ is_active: false })
const statusProcessing = computed(() => statusSubmitting.value || statusRequest.processing)
const pendingDeleteId = ref(null)
const archive = useRecordArchive({
  destroyUrl: ids => route('vap-proposals.templates.destroy', ids[0]),
  onSuccess: () => {
    showDeleteModal.value = false
    pendingDeleteId.value = null
    router.visit(route('vap-proposals.templates.index'))
  },
})
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
  compliance: { bg: 'bg-indigo-100 dark:bg-indigo-500/15', text: 'text-indigo-900 dark:text-indigo-200' },
  'field-services': { bg: 'bg-cyan-100 dark:bg-cyan-500/15', text: 'text-cyan-900 dark:text-cyan-200' },
  general: { bg: 'bg-slate-100 dark:bg-slate-800', text: 'text-slate-900 dark:text-slate-200' },
}

const statusBadges = {
  PENDING: { class: 'ds-badge-warning', text: trans('gestlab.general.labels.vap_proposals.status.pending') },
  SENT: { class: 'ds-badge-info', text: trans('gestlab.general.labels.vap_proposals.status.sent') },
  VIEWED: { class: 'ds-badge-info', text: trans('gestlab.general.labels.vap_proposals.status.viewed') },
  ACCEPTED: { class: 'ds-badge-success', text: trans('gestlab.general.labels.vap_proposals.status.accepted') },
  REJECTED: { class: 'ds-badge-danger', text: trans('gestlab.general.labels.vap_proposals.status.rejected') },
  REVISED: { class: 'ds-badge-warning', text: trans('gestlab.general.labels.vap_proposals.status.revised') },
  EXPIRED: { class: 'ds-badge-neutral', text: trans('gestlab.general.labels.vap_proposals.status.expired') },
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

function requestStatusToggle() {
  if (statusProcessing.value || archive.processing.value || showStatusModal.value || showDeleteModal.value) return
  pendingStatus.value = { id: props.template.id, is_active: !templateActive.value }
  statusError.value = ''
  statusRequest.clearErrors()
  showStatusModal.value = true
}

function cancelStatusToggle() {
  if (statusProcessing.value) return
  showStatusModal.value = false
  pendingStatus.value = null
}

function reportStatusRefreshFailure() {
  statusRefreshError.value = 'Estado guardado. Não foi possível actualizar os restantes dados. Actualize a página.'
  return false
}

function syncConfirmedTemplateStatus(template) {
  if (!confirmedStatus.value) return
  if (template.id !== confirmedStatus.value.id || template.is_active === confirmedStatus.value.is_active) {
    confirmedStatus.value = null
    return
  }
  reportStatusRefreshFailure()
}

async function toggleTemplateStatus() {
  if (statusProcessing.value || !pendingStatus.value) return
  const intent = { ...pendingStatus.value }
  statusError.value = ''
  statusRequest.clearErrors()
  statusSubmitting.value = true
  try {
    await statusRequest.transform(() => ({ is_active: intent.is_active })).put(route('vap-proposals.templates.toggle-status', intent.id), {
      onSuccess: payload => {
        if (payload?.success !== true || payload.is_active !== intent.is_active) {
          statusError.value = 'Não foi possível confirmar o estado solicitado. Actualize a página antes de tentar novamente.'
          return
        }
        showStatusModal.value = false
        pendingStatus.value = null
        confirmedStatus.value = { ...intent }
        toast.success(payload.is_active ? trans('gestlab.general.labels.vap_proposal_templates.show.notifications.activated') : trans('gestlab.general.labels.vap_proposal_templates.show.notifications.deactivated'))
        try {
          router.reload({ only: ['template'],
            onSuccess: () => {
              if (confirmedStatus.value?.id === props.template.id && props.template.is_active !== confirmedStatus.value.is_active) reportStatusRefreshFailure()
              else statusRefreshError.value = ''
            },
            onError: reportStatusRefreshFailure,
            onHttpException: reportStatusRefreshFailure,
            onNetworkError: reportStatusRefreshFailure,
            onCancel: reportStatusRefreshFailure,
          })
        } catch {
          reportStatusRefreshFailure()
        }
      },
      onError: errors => { statusError.value = Object.values(errors).flat().join(' ') || trans('gestlab.general.labels.vap_proposal_templates.show.notifications.status_error') },
      onHttpException: response => {
        statusError.value = [403, 404].includes(response.status)
          ? 'O modelo ou a autorização já não está disponível. Actualize a página.'
          : trans('gestlab.general.labels.vap_proposal_templates.show.notifications.status_error')
        return false
      },
      onNetworkError: () => {
        statusError.value = 'Falha de ligação. Não foi possível confirmar o estado. Tente novamente para aplicar a mesma decisão.'
        return false
      },
      onCancel: () => { statusError.value = 'Operação interrompida. Tente novamente para aplicar a mesma decisão.' },
    })
  } catch {
    if (!statusError.value) statusError.value = trans('gestlab.general.labels.vap_proposal_templates.show.notifications.status_request_error')
  } finally {
    statusSubmitting.value = false
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
      is_active: templateActive.value,
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
    link.setAttribute('download', `template-proposta-${props.template.name.toLowerCase().replace(/\s+/g, '-')}-${new Date().toISOString().split('T')[0]}.json`)
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

function confirmDelete() {
  if (statusProcessing.value || archive.processing.value || showStatusModal.value || showDeleteModal.value) return
  if (props.template.proposals_count > 0) {
    toast.warning(trans('gestlab.general.labels.vap_proposal_templates.show.notifications.cannot_delete_in_use'))
    return
  }
  pendingDeleteId.value = props.template.id
  archive.failed.value = false
  archive.message.value = ''
  showDeleteModal.value = true
}

function cancelDelete() {
  if (archive.processing.value) return
  showDeleteModal.value = false
  pendingDeleteId.value = null
}

function deleteTemplate() {
  if (archive.processing.value || !pendingDeleteId.value) return
  archive.submit('delete', [pendingDeleteId.value])
}

onMounted(() => {
  // Scroll to top on mount
  window.scrollTo(0, 0)
})
</script>
