<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900">
              {{ proposal.proposal_number }}
            </h1>
            <span 
              :class="[
                'inline-flex items-center rounded-full px-3 py-1 text-sm font-medium',
                proposal.status_badge.class
              ]"
            >
              {{ proposal.status_badge.text }}
            </span>
          </div>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.vap_proposals.show.description') }}
            <span class="font-semibold text-blue-900">
              {{ proposal.customer.name }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <a 
            v-if="proposal.file_path"
            :href="route('vap-proposals.download.pdf', proposal.id)"
            class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
          >
            <ArrowDownTrayIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_proposals.show.download_pdf') }}
          </a>
          <button 
            v-if="canSend"
            @click="sendProposal"
            class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 transition-all duration-200"
          >
            <PaperAirplaneIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_proposals.show.send_to_client') }}
          </button>
          <Link 
            v-if="canRevise"
            :href="route('vap-proposals.edit', proposal.id)"
            class="inline-flex items-center gap-2 rounded-lg bg-yellow-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:ring-offset-2 transition-all duration-200"
          >
            <PencilSquareIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_proposals.show.revise') }}
          </Link>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT GRID -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- PROPOSAL DETAILS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <DocumentTextIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_proposals.show.details.title') }}
            </h2>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h3 class="text-sm font-medium text-gray-700 mb-2">
                  {{ $t('gestlab.general.labels.vap_proposals.show.details.client_info') }}
                </h3>
                <div class="space-y-2">
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.show.details.customer') }}</span>
                    <span class="text-sm font-medium text-gray-900">{{ proposal.customer.name }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.show.details.customer_code') }}</span>
                    <span class="text-sm text-gray-900">{{ proposal.customer.code }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.show.details.service_location') }}</span>
                    <span class="text-sm text-gray-900">{{ proposal.service_location }}</span>
                  </div>
                </div>
              </div>
              <div>
                <h3 class="text-sm font-medium text-gray-700 mb-2">
                  {{ $t('gestlab.general.labels.vap_proposals.show.details.lab_info') }}
                </h3>
                <div class="space-y-2">
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.show.details.department') }}</span>
                    <span class="text-sm font-medium text-gray-900">{{ proposal?.department?.name }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.show.details.warehouse') }}</span>
                    <span class="text-sm text-gray-900">{{ proposal?.warehouse?.address }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.show.details.created_by') }}</span>
                    <span class="text-sm text-gray-900">{{ proposal?.user?.name }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- TIMELINE -->
            <div class="mt-8">
              <h3 class="text-sm font-medium text-gray-700 mb-4">
                {{ $t('gestlab.general.labels.vap_proposals.show.details.timeline') }}
              </h3>
              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <div class="flex-shrink-0">
                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                      <CalendarIcon class="h-4 w-4 text-blue-900" />
                    </div>
                  </div>
                  <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900">
                      {{ $t('gestlab.general.labels.vap_proposals.show.details.created_on') }}
                    </p>
                    <p class="text-sm text-gray-500">
                      {{ formatDate(proposal.created_at) }}
                    </p>
                  </div>
                </div>
                <div class="flex items-start gap-3" v-if="proposal.expiry_date">
                  <div class="flex-shrink-0">
                    <div :class="[
                      'h-8 w-8 rounded-full flex items-center justify-center',
                      proposal.days_until_expiry <= 3 ? 'bg-red-100' : 'bg-yellow-100'
                    ]">
                      <ClockIcon :class="[
                        'h-4 w-4',
                        proposal.days_until_expiry <= 3 ? 'text-red-900' : 'text-yellow-900'
                      ]" />
                    </div>
                  </div>
                  <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900">
                      {{ $t('gestlab.general.labels.vap_proposals.show.details.expires_on') }}
                    </p>
                    <p class="text-sm" :class="proposal.days_until_expiry <= 3 ? 'text-red-600' : 'text-gray-500'">
                      {{ formatDate(proposal.expiry_date) }}
                      ({{ proposal.days_until_expiry }} {{ $t('gestlab.general.labels.vap_proposals.show.details.days_left') }})
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- OBSERVATIONS -->
            <div class="mt-8" v-if="proposal.obs">
              <h3 class="text-sm font-medium text-gray-700 mb-2">
                {{ $t('gestlab.general.labels.vap_proposals.show.details.observations') }}
              </h3>
              <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ proposal.obs }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- ITEMS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <ListBulletIcon class="h-5 w-5 text-blue-900" />
                {{ $t('gestlab.general.labels.vap_proposals.show.items.title') }}
                <span class="text-sm font-normal text-gray-500 ml-2">
                  ({{ proposal.items.length }} {{ $t('general.items') }})
                </span>
              </h2>
              <div class="text-lg font-bold text-blue-900">
                {{ formatCurrency(proposal.total) }}
              </div>
            </div>
          </div>
          <div class="p-6">
            <div class="space-y-4">
              <div 
                v-for="(item, index) in proposal.items"
                :key="item.id"
                class="border border-gray-200 rounded-lg p-4 hover:border-blue-900 transition-colors duration-200"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-blue-900 font-semibold">
                      {{ index + 1 }}
                    </div>
                    <div>
                      <h3 class="text-sm font-semibold text-gray-900">
                        {{ item.item_description }}
                      </h3>
                      <div class="flex items-center gap-2 mt-1">
                        <span v-if="item.standard" class="text-xs text-gray-500">
                          {{ item.standard.name }}
                        </span>
                        <span class="text-xs text-gray-500">•</span>
                        <span class="text-xs text-gray-500">
                          {{ item.qty }} {{ item.unit?.symbol || item.unit?.name }}
                        </span>
                      </div>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="text-sm font-semibold text-gray-900">
                      {{ formatCurrency(item.unit_price) }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ formatCurrency(item.total) }}
                    </div>
                  </div>
                </div>
                <div v-if="item.discount_percentage > 0" class="mt-2 text-xs text-green-600">
                  {{ $t('gestlab.general.labels.vap_proposals.show.items.discount') }}: {{ item.discount_percentage }}%
                  (-{{ formatCurrency(item.discount_amount) }})
                </div>
              </div>
            </div>

            <!-- TOTALS -->
            <div class="mt-8 border-t border-gray-200 pt-6">
              <div class="max-w-md ml-auto space-y-3">
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.show.items.subtotal') }}</span>
                  <span class="text-sm font-medium text-gray-900">{{ formatCurrency(proposal.sub_total) }}</span>
                </div>
                <div v-if="proposal.global_discount_amount > 0" class="flex justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.show.items.global_discount') }}</span>
                  <span class="text-sm font-medium text-green-600">-{{ formatCurrency(proposal.global_discount_amount) }}</span>
                </div>
                <div v-if="proposal.withholding_tax_amount > 0" class="flex justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.show.items.tax') }}</span>
                  <span class="text-sm font-medium text-gray-900">{{ formatCurrency(proposal.withholding_tax_amount) }}</span>
                </div>
                <div class="flex justify-between border-t border-gray-200 pt-3">
                  <span class="text-base font-semibold text-gray-900">{{ $t('gestlab.general.labels.vap_proposals.show.items.total') }}</span>
                  <span class="text-lg font-bold text-blue-900">{{ formatCurrency(proposal.total) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- COMPLIANCE AGREEMENT CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" v-if="proposal.compliance_agreement">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <ShieldCheckIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_proposals.show.compliance.title') }}
            </h2>
          </div>
          <div class="p-6">
            <div class="space-y-4">
              <div v-if="proposal.compliance_agreement.acknowledged_at" class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center gap-3">
                  <CheckCircleIcon class="h-6 w-6 text-green-600" />
                  <div>
                    <h3 class="text-sm font-semibold text-green-900">
                      {{ $t('gestlab.general.labels.vap_proposals.show.compliance.signed') }}
                    </h3>
                    <p class="text-sm text-green-700 mt-1">
                      {{ $t('gestlab.general.labels.vap_proposals.show.compliance.signed_on') }} {{ formatDateTime(proposal.compliance_agreement.acknowledged_at) }}
                    </p>
                    <p class="text-xs text-green-600 mt-1">
                      IP: {{ proposal.compliance_agreement.client_ip }}
                    </p>
                  </div>
                </div>
              </div>
              <div class="space-y-3">
                <div class="flex items-center">
                  <input
                    type="checkbox"
                    :checked="proposal.compliance_agreement.confidentiality"
                    disabled
                    class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                  />
                  <label class="ml-2 text-sm text-gray-700">
                    {{ $t('gestlab.general.labels.vap_proposals.show.compliance.confidentiality') }}
                  </label>
                </div>
                <div class="flex items-center">
                  <input
                    type="checkbox"
                    :checked="proposal.compliance_agreement.impartiality"
                    disabled
                    class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                  />
                  <label class="ml-2 text-sm text-gray-700">
                    {{ $t('gestlab.general.labels.vap_proposals.show.compliance.impartiality') }}
                  </label>
                </div>
                <div class="flex items-center">
                  <input
                    type="checkbox"
                    :checked="proposal.compliance_agreement.nondisclosure"
                    disabled
                    class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                  />
                  <label class="ml-2 text-sm text-gray-700">
                    {{ $t('gestlab.general.labels.vap_proposals.show.compliance.nondisclosure') }}
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- REVISION HISTORY CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" v-if="revisions.length > 0">
          <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <ClockIcon class="h-5 w-5 text-blue-900" />
              {{ $t('gestlab.general.labels.vap_proposals.show.revisions.title') }}
              <span class="text-sm font-normal text-gray-500 ml-2">
                ({{ revisions.length }} {{ $t('general.changes') }})
              </span>
            </h2>
          </div>
          <div class="p-6">
            <div class="space-y-6">
              <div 
                v-for="revision in revisions"
                :key="revision.id"
                class="border-l-2 border-blue-900 pl-4 py-2"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <UserIcon class="h-4 w-4 text-gray-400" />
                    <span class="text-sm font-medium text-gray-900">
                      {{ revision.causer?.name || 'System' }}
                    </span>
                  </div>
                  <span class="text-xs text-gray-500">
                    {{ formatDateTime(revision.created_at) }}
                  </span>
                </div>
                <p class="mt-1 text-sm text-gray-700">
                  {{ revision.description }}
                </p>
                <div v-if="revision.properties?.reason" class="mt-2 text-xs text-gray-600 bg-gray-50 p-2 rounded">
                  <strong>{{ $t('gestlab.general.labels.vap_proposals.show.revisions.reason') }}:</strong> {{ revision.properties.reason }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3) -->
      <div class="space-y-6">
        <!-- ACTIONS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.vap_proposals.show.actions.title') }}
          </h3>
          <div class="space-y-4">
            <button 
              @click="generatePdf"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
            >
              <DocumentArrowDownIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_proposals.show.actions.generate_pdf') }}
            </button>
            
            <a 
              :href="route('vap-proposals.public.show', proposal.unique_hash)"
              target="_blank"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-blue-900 bg-white px-4 py-3 text-sm font-semibold text-blue-900 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
            >
              <LinkIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_proposals.show.actions.view_public_link') }}
            </a>

            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                {{ $t('gestlab.general.labels.vap_proposals.show.actions.share') }}
              </h4>
              <div class="flex gap-2">
                <input
                  :value="publicLink"
                  readonly
                  class="flex-1 rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
                />
                <button
                  @click="copyToClipboard"
                  class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500"
                >
                  <DocumentDuplicateIcon class="h-4 w-4" />
                  {{ copied ? $t('general.copied') : $t('general.copy') }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- STATUS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <Cog6ToothIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_proposals.show.status.title') }}
          </h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.show.status.current') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                proposal.status_badge.class
              ]">
                {{ proposal.status_badge.text }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.show.status.is_original') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                proposal.is_original ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
              ]">
                {{ proposal.is_original ? $t('general.yes') : $t('general.no') }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.show.status.tolerance_days') }}</span>
              <span class="text-sm font-medium text-gray-900">{{ proposal.tolerance_days }} days</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.show.status.converted') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                proposal.converted_to_invoice ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
              ]">
                {{ proposal.converted_to_invoice ? $t('general.yes') : $t('general.no') }}
              </span>
            </div>
          </div>
        </div>

        <!-- TEMPLATE INFO CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6" v-if="proposal.template">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <DocumentDuplicateIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_proposals.show.template.title') }}
          </h3>
          <div class="space-y-2">
            <div>
              <h4 class="text-sm font-medium text-gray-700">{{ proposal.template.name }}</h4>
              <p class="text-xs text-gray-500 mt-1">
                {{ $t('gestlab.general.labels.vap_proposals.show.template.created_by') }} {{ proposal.template?.user?.name }}
              </p>
            </div>
            <div class="mt-4 text-sm text-gray-600 line-clamp-3">
              {{ stripHtml(proposal.template.content) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- SEND CONFIRMATION MODAL -->
  <ConfirmationModal
    :show="showSendModal"
    @close="showSendModal = false"
    @confirm="confirmSend"
  >
    <template #title>
      {{ $t('gestlab.general.labels.vap_proposals.show.send_modal.title') }}
    </template>
    <template #content>
      <p class="text-sm text-gray-600">
        {{ $t('gestlab.general.labels.vap_proposals.show.send_modal.message') }}
      </p>
      <div class="mt-4 space-y-3">
        <label class="flex items-center">
          <input
            type="checkbox"
            v-model="sendOptions.generatePdf"
            class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
          />
          <span class="ml-2 text-sm text-gray-700">
            {{ $t('gestlab.general.labels.vap_proposals.show.send_modal.generate_pdf') }}
          </span>
        </label>
        <label class="flex items-center">
          <input
            type="checkbox"
            v-model="sendOptions.sendEmail"
            class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
          />
          <span class="ml-2 text-sm text-gray-700">
            {{ $t('gestlab.general.labels.vap_proposals.show.send_modal.send_email') }}
          </span>
        </label>
      </div>
    </template>
  </ConfirmationModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
  DocumentTextIcon, ArrowDownTrayIcon, PaperAirplaneIcon,
  PencilSquareIcon, ListBulletIcon, ShieldCheckIcon,
  ClockIcon, UserIcon, DocumentArrowDownIcon,
  LinkIcon, DocumentDuplicateIcon, Cog6ToothIcon,
  CalendarIcon, CheckCircleIcon
} from '@heroicons/vue/24/outline'
import ConfirmationModal from '@/Components/dialog-modal.vue'

const props = defineProps({
  proposal: Object,
  revisions: Array,
  canSend: Boolean,
  canRevise: Boolean,
})

const showSendModal = ref(false)
const sendOptions = ref({
  generatePdf: true,
  sendEmail: true,
})
const copied = ref(false)

const publicLink = computed(() => {
  return route('vap-proposals.public.show', props.proposal.unique_hash)
})

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDateTime = (date) => {
  return new Date(date).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'AOA'
  }).format(amount)
}

const stripHtml = (html) => {
  return html.replace(/<[^>]*>/g, '')
}

const sendProposal = () => {
  showSendModal.value = true
}

const confirmSend = () => {
  router.post(route('vap-proposals.send', props.proposal.id), {
    options: sendOptions.value
  }, {
    onSuccess: () => {
      showSendModal.value = false
    }
  })
}

const generatePdf = () => {
  window.open(route('vap-proposals.download.pdf', props.proposal.id), '_blank')
}

const copyToClipboard = async () => {
  try {
    await navigator.clipboard.writeText(publicLink.value)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch (err) {
    console.error('Failed to copy:', err)
  }
}
</script>
