<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <div class="flex items-center gap-3">
            <div :class="[
              'flex h-12 w-12 items-center justify-center rounded-xl',
              getCategoryColor(template.category).bg
            ]">
              <component 
                :is="getCategoryIcon(template.category)" 
                class="h-6 w-6"
                :class="getCategoryColor(template.category).text"
              />
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900">
                {{ template.name }}
              </h1>
              <div class="flex items-center gap-3 mt-2">
                <span :class="[
                  'inline-flex items-center rounded-full px-3 py-1 text-sm font-medium',
                  template.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                ]">
                  {{ template.is_active ? $t('gestlab.general.labels.vap_proposal_templates.active') : $t('gestlab.general.labels.vap_proposal_templates.inactive') }}
                </span>
                <span class="text-sm text-gray-500">
                  {{ getCategoryLabel(template.category) }}
                </span>
                <span class="text-sm text-gray-500">
                  {{ $t('gestlab.general.labels.vap_proposal_templates.show.created_by') }} {{ template.user.name }}
                </span>
              </div>
            </div>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <Link 
            :href="route('vap-proposals.templates.index')"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
          >
            <ArrowLeftIcon class="h-5 w-5" />
            {{ $t('gestlab.general.buttons.back') }}
          </Link>
          <Link 
            :href="route('vap-proposals.create') + '?template_id=' + template.id"
            class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
          >
            <DocumentPlusIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_proposal_templates.show.use_this_template') }}
          </Link>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT GRID -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- TEMPLATE CONTENT CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <DocumentTextIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_proposal_templates.show.content') }}
            </h2>
          </div>
          
          <div class="p-6">
            <!-- VARIABLES PREVIEW -->
            <div class="mb-6">
              <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-medium text-gray-900">
                  {{ $t('gestlab.general.labels.vap_proposal_templates.show.variables') }}
                </h3>
                <span class="text-xs text-gray-500">
                  {{ variablesCount }} {{ $t('gestlab.general.labels.vap_proposal_templates.show.variables') }}
                </span>
              </div>
              <div class="flex flex-wrap gap-2">
                <span 
                  v-for="variable in templateVariables"
                  :key="variable"
                  class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-900"
                >
                  <CodeBracketIcon class="h-3 w-3" />
                  {{ variable }}
                </span>
              </div>
            </div>

            <!-- TEMPLATE CONTENT -->
            <div class="border-t border-gray-200 pt-6">
              <div class="prose max-w-none min-h-[400px] p-6 bg-gray-50 rounded-lg border border-gray-200">
                <div v-html="formattedTemplateContent"></div>
              </div>
            </div>

            <!-- RAW CONTENT VIEW -->
            <div class="mt-6 border-t border-gray-200 pt-6">
              <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-medium text-gray-900">
                  {{ $t('gestlab.general.labels.vap_proposal_templates.show.raw_content') }}
                </h3>
                <button
                  @click="toggleRawView"
                  class="text-xs text-blue-900 hover:text-blue-800 font-medium"
                >
                  {{ showRawView ? $t('gestlab.general.labels.vap_proposal_templates.show.hide_raw') : $t('gestlab.general.labels.vap_proposal_templates.show.show_raw') }}
                </button>
              </div>
              <div v-if="showRawView" class="relative">
                <textarea
                  v-model="template.content"
                  rows="10"
                  readonly
                  class="w-full font-mono text-sm bg-gray-900 text-gray-100 rounded-lg p-4 resize-none"
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
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" v-if="template.proposals_count > 0">
          <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <ChartBarIcon class="h-5 w-5 text-blue-900" />
                {{ $t('gestlab.general.labels.vap_proposal_templates.show.usage') }}
                <span class="text-sm font-normal text-gray-500 ml-2">
                  ({{ template.proposals_count }} {{ $t('gestlab.general.labels.vap_proposal_templates.show.proposals_using') }})
                </span>
              </h2>
              <div class="text-sm font-semibold text-blue-900">
                {{ calculateAcceptanceRate }}% {{ $t('gestlab.general.labels.vap_proposal_templates.acceptance_rate') }}
              </div>
            </div>
          </div>
          
          <div class="p-6">
            <!-- USAGE STATS -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
              <div class="bg-blue-50 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-blue-900">{{ template.proposals_count }}</div>
                <div class="text-sm text-blue-700 mt-1">{{ $t('gestlab.general.labels.vap_proposal_templates.used') }}</div>
              </div>
              
              <div class="bg-green-50 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-green-900">{{ acceptedProposalsCount }}</div>
                <div class="text-sm text-green-700 mt-1">{{ $t('gestlab.general.labels.vap_proposal_templates.show.accepted') }}</div>
              </div>
              
              <div class="bg-yellow-50 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-yellow-900">{{ pendingProposalsCount }}</div>
                <div class="text-sm text-yellow-700 mt-1">{{ $t('gestlab.general.labels.vap_proposal_templates.show.pending') }}</div>
              </div>
              
              <div class="bg-red-50 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-red-900">{{ rejectedProposalsCount }}</div>
                <div class="text-sm text-red-700 mt-1">{{ $t('gestlab.general.labels.vap_proposal_templates.show.rejected') }}</div>
              </div>
            </div>

            <!-- RECENT PROPOSALS -->
            <div v-if="recentProposals.length > 0">
              <h3 class="text-sm font-medium text-gray-900 mb-4">
                {{ $t('gestlab.general.labels.vap_proposal_templates.show.recent_proposals') }}
              </h3>
              <div class="space-y-3">
                <div 
                  v-for="proposal in recentProposals"
                  :key="proposal.id"
                  class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50"
                >
                  <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                      <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                        <DocumentTextIcon class="h-4 w-4 text-blue-900" />
                      </div>
                    </div>
                    <div>
                      <div class="text-sm font-medium text-gray-900">
                        {{ proposal.proposal_number }}
                      </div>
                      <div class="text-xs text-gray-500">
                        {{ proposal.customer?.name || 'Cliente não informado' }}
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
                    <span class="text-sm text-gray-900 font-medium">
                      AOA {{ formatCurrency(proposal.total) }}
                    </span>
                    <Link 
                      :href="route('proposals.show', proposal.id)"
                      class="text-blue-900 hover:text-blue-800"
                      :title="$t('gestlab.general.labels.vap_proposal_templates.show.view_proposal')"
                    >
                      <ArrowRightIcon class="h-4 w-4" />
                    </Link>
                  </div>
                </div>
              </div>
              
              <div class="mt-4 text-center">
                <Link 
                  :href="route('proposals.index', { template_id: template.id })"
                  class="text-sm text-blue-900 hover:text-blue-800 font-medium"
                >
                  {{ $t('gestlab.general.labels.vap_proposal_templates.show.view_all_proposals') }}
                </Link>
              </div>
            </div>
            
            <div v-else class="text-center py-8">
              <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-300" />
              <p class="mt-4 text-sm text-gray-500">
                {{ $t('gestlab.general.labels.vap_proposal_templates.show.no_proposals_yet') }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6">
        <!-- TEMPLATE INFO CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.vap_proposal_templates.show.title') }}
          </h3>
          <div class="space-y-4">
            <div>
              <label class="block text-xs font-medium text-gray-500">
                {{ $t('gestlab.general.labels.vap_proposal_templates.show.created_by') }}
              </label>
              <div class="flex items-center gap-2 mt-1">
                <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                  <UserIcon class="h-4 w-4 text-gray-600" />
                </div>
                <span class="text-sm text-gray-900">{{ template.user.name }}</span>
              </div>
            </div>
            
            <div>
              <label class="block text-xs font-medium text-gray-500">
                {{ $t('gestlab.general.labels.vap_proposal_templates.show.created_at') }}
              </label>
              <p class="text-sm text-gray-900 mt-1">{{ formatDateTime(template.created_at) }}</p>
            </div>
            
            <div>
              <label class="block text-xs font-medium text-gray-500">
                {{ $t('gestlab.general.labels.vap_proposal_templates.show.updated_at') }}
              </label>
              <p class="text-sm text-gray-900 mt-1">{{ formatDateTime(template.updated_at) }}</p>
            </div>
            
            <div>
              <label class="block text-xs font-medium text-gray-500">
                {{ $t('gestlab.general.labels.vap_proposal_templates.categories.chemical') }}
              </label>
              <div class="flex items-center gap-2 mt-1">
                <div :class="[
                  'h-8 w-8 rounded-lg flex items-center justify-center',
                  getCategoryColor(template.category).bg
                ]">
                  <component 
                    :is="getCategoryIcon(template.category)" 
                    class="h-4 w-4"
                    :class="getCategoryColor(template.category).text"
                  />
                </div>
                <span class="text-sm text-gray-900">{{ getCategoryLabel(template.category) }}</span>
              </div>
            </div>
            
            <div>
              <label class="block text-xs font-medium text-gray-500">
                Status
              </label>
              <div class="mt-1">
                <span :class="[
                  'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                  template.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                ]">
                  {{ template.is_active ? $t('gestlab.general.labels.vap_proposal_templates.active') : $t('gestlab.general.labels.vap_proposal_templates.inactive') }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- ACTIONS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.vap_proposal_templates.actions.title') }}
          </h3>
          <div class="space-y-3">
            <Link 
              :href="route('vap-proposals.templates.edit', template.id)"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
            >
              <PencilSquareIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_proposal_templates.show.edit_template') }}
            </Link>
            
            <Link 
              :href="route('proposals.create') + '?template_id=' + template.id"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-green-300 bg-white px-4 py-3 text-sm font-semibold text-green-700 shadow-sm hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200"
            >
              <DocumentPlusIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_proposal_templates.show.create_proposal') }}
            </Link>
            
            <button
              @click="toggleTemplateStatus"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                template.is_active 
                  ? 'border border-yellow-300 bg-white text-yellow-700 hover:bg-yellow-50'
                  : 'border border-green-300 bg-white text-green-700 hover:bg-green-50'
              ]"
            >
              <ArrowPathIcon class="h-5 w-5" />
              {{ template.is_active ? $t('gestlab.general.labels.vap_proposal_templates.show.deactivate') : $t('gestlab.general.labels.vap_proposal_templates.show.activate') }}
            </button>
          </div>
          
          <div class="border-t border-gray-200 pt-4 mt-4">
            <h4 class="text-sm font-medium text-gray-900 mb-2">
              {{ $t('gestlab.general.labels.vap_proposal_templates.show.export') }}
            </h4>
            <div class="space-y-2">
              <button
                @click="exportTemplate"
                class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
              >
                <ArrowDownTrayIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.vap_proposal_templates.show.export_json') }}
              </button>
              <button
                @click="exportAsPdf"
                class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
              >
                <DocumentArrowDownIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.vap_proposal_templates.show.export_pdf') }}
              </button>
            </div>
          </div>
        </div>

        <!-- VARIABLE REFERENCE CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <VariableIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_proposal_templates.show.variable_reference') }}
          </h3>
          <div class="space-y-3">
            <div 
              v-for="(description, variable) in availableVariables"
              :key="variable"
              class="p-3 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-colors"
            >
              <div class="font-mono text-sm font-medium text-blue-900 mb-1">
                {{ variable }}
              </div>
              <div class="text-xs text-gray-600">
                {{ description }}
              </div>
            </div>
          </div>
          
          <div class="mt-4 pt-4 border-t border-gray-200">
            <p class="text-xs text-gray-500">
              {{ $t('gestlab.general.labels.vap_proposal_templates.show.variable_help') }}
            </p>
          </div>
        </div>

        <!-- DANGER ZONE CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-red-200 p-6">
          <h3 class="text-lg font-semibold text-red-900 mb-4">
            {{ $t('gestlab.general.labels.vap_proposal_templates.show.danger_zone') }}
          </h3>
          <p class="text-sm text-gray-600 mb-4">
            {{ $t('gestlab.general.labels.vap_proposal_templates.show.delete_warning') }}
          </p>
          <button
            @click="confirmDelete"
            :disabled="template.proposals_count > 0"
            :class="[
              'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
              template.proposals_count > 0
                ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                : 'bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2'
            ]"
          >
            <TrashIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_proposal_templates.show.delete_template') }}
          </button>
          
          <div v-if="template.proposals_count > 0" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-start gap-2">
              <ExclamationTriangleIcon class="h-4 w-4 text-red-600 mt-0.5 flex-shrink-0" />
              <p class="text-xs text-red-700">
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
      <p class="text-sm text-gray-600">
        {{ $t('gestlab.general.labels.vap_proposal_templates.show.delete_confirm_message', { name: template.name }) }}
      </p>
      <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
        <div class="flex items-start gap-2">
          <ExclamationTriangleIcon class="h-5 w-5 text-red-600 mt-0.5 flex-shrink-0" />
          <div>
            <p class="text-sm font-medium text-red-900">
              {{ $t('gestlab.general.labels.vap_proposal_templates.show.delete_warning_title') }}
            </p>
            <p class="text-xs text-red-700 mt-1">
              {{ $t('gestlab.general.labels.vap_proposal_templates.show.delete_warning_detail') }}
            </p>
          </div>
        </div>
      </div>
    </template>
  </ConfirmationModal>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
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

const props = defineProps({
  template: Object,
  recentProposals: {
    type: Array,
    default: () => []
  },
  variables: {
    type: Object,
    default: () => ({
      '{client_name}': 'Nome do Cliente',
      '{client_code}': 'Código do Cliente',
      '{proposal_number}': 'Número da Proposta',
      '{proposal_date}': 'Data da Proposta',
      '{expiry_date}': 'Data de Validade',
      '{service_location}': 'Local do Serviço',
      '{lab_name}': 'Nome do Laboratório',
      '{department}': 'Departamento',
      '{items_table}': 'Tabela de Itens/Serviços',
      '{subtotal}': 'Subtotal',
      '{total}': 'Total',
      '{terms_conditions}': 'Termos e Condições',
    })
  }
})

// UI State
const showRawView = ref(false)
const showDeleteModal = ref(false)
const copied = ref(false)

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

const statusBadges = {
  PENDING: { class: 'bg-yellow-100 text-yellow-800', text: 'Pendente' },
  SENT: { class: 'bg-blue-100 text-blue-800', text: 'Enviada' },
  VIEWED: { class: 'bg-purple-100 text-purple-800', text: 'Visualizada' },
  ACCEPTED: { class: 'bg-green-100 text-green-800', text: 'Aceita' },
  REJECTED: { class: 'bg-red-100 text-red-800', text: 'Rejeitada' },
  REVISED: { class: 'bg-orange-100 text-orange-800', text: 'Revisada' },
  EXPIRED: { class: 'bg-gray-100 text-gray-800', text: 'Expirada' },
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
  return props.recentProposals.filter(p => p.status === 'ACCEPTED').length
})

const pendingProposalsCount = computed(() => {
  return props.recentProposals.filter(p => ['PENDING', 'SENT', 'VIEWED', 'REVISED'].includes(p.status)).length
})

const rejectedProposalsCount = computed(() => {
  return props.recentProposals.filter(p => p.status === 'REJECTED').length
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
      merged[key] = `Variável personalizada: ${variable}`
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
    chemical: 'Química',
    microbiology: 'Microbiologia',
    physical: 'Física',
    environmental: 'Ambiental',
    food: 'Alimentos',
    general: 'Geral',
  }
  return labels[category] || 'Geral'
}

const getStatusBadge = (status) => {
  return statusBadges[status] || statusBadges.PENDING
}

const formatDateTime = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatCurrency = (amount) => {
  if (!amount) return '0,00'
  return parseFloat(amount).toLocaleString('pt-BR', {
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
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch (err) {
    console.error('Falha ao copiar:', err)
  }
}

const toggleTemplateStatus = async () => {
  const newStatus = !props.template.is_active
  const message = newStatus 
    ? 'Tem certeza que deseja ativar este template?'
    : 'Tem certeza que deseja desativar este template?'
  
  if (!confirm(message)) return
  
  try {
    const response = await axios.put(route('vap-proposals.templates.update', props.template.id), {
      is_active: newStatus
    })
    
    if (response.data.success) {
      router.reload()
    }
  } catch (error) {
    console.error('Erro ao alterar status:', error)
    alert('Erro ao alterar status do template.')
  }
}

const exportTemplate = async () => {
  try {
    const templateData = {
      name: props.template.name,
      content: props.template.content,
      category: props.template.category,
      is_active: props.template.is_active,
      variables: templateVariables.value,
      metadata: {
        exported_at: new Date().toISOString(),
        exported_by: window.userName || 'Usuário',
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
    link.setAttribute('download', `template-${props.template.name.toLowerCase().replace(/\s+/g, '-')}-${new Date().toISOString().split('T')[0]}.json`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    console.error('Erro ao exportar template:', error)
    alert('Erro ao exportar template.')
  }
}

const exportAsPdf = () => {
  // Implementar exportação para PDF
  alert('Exportação para PDF em desenvolvimento...')
}

const confirmDelete = () => {
  if (props.template.proposals_count > 0) {
    alert('Não é possível excluir templates que estão em uso por propostas.')
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