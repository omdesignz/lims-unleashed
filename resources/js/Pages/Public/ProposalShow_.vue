<template>
  <div class="min-h-screen bg-gray-50">
    <!-- HEADER -->
    <div class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <img class="h-8 w-auto" :src="companyLogo" alt="Company Logo">
            </div>
            <div class="ml-4">
              <h1 class="text-lg font-semibold text-gray-900">{{ companyName }}</h1>
              <p class="text-sm text-gray-500">{{ companyTagline }}</p>
            </div>
          </div>
          <div class="flex items-center gap-4">
            <div class="text-right">
              <p class="text-sm text-gray-500">{{ $t('gestlab.general.labels.vap_proposals.public.proposal') }}</p>
              <p class="text-lg font-bold text-blue-900">{{ proposal.proposal_number }}</p>
            </div>
            <div>
              <span :class="[
                'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium',
                proposal.status_badge.class
              ]">
                {{ proposal.status_badge.text }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- EXPIRY WARNING -->
    <div v-if="isExpired" class="bg-red-50 border-b border-red-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center">
          <ExclamationCircleIcon class="h-5 w-5 text-red-400 mr-3" />
          <p class="text-sm text-red-800">
            {{ $t('gestlab.general.labels.vap_proposals.public.expired_warning') }}
          </p>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- PROPOSAL HEADER -->
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-8">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
              <h1 class="text-2xl font-bold text-white">
                {{ $t('gestlab.general.labels.vap_proposals.public.proposal_for') }} {{ proposal.customer.name }}
              </h1>
              <p class="mt-2 text-blue-100">
                {{ $t('gestlab.general.labels.vap_proposals.public.created_on') }} {{ formatDate(proposal.created_at) }}
              </p>
              <p v-if="proposal.expiry_date" class="mt-1 text-blue-100">
                {{ $t('gestlab.general.labels.vap_proposals.public.valid_until') }} {{ formatDate(proposal.expiry_date) }}
                ({{ proposal.days_until_expiry }} {{ $t('gestlab.general.labels.vap_proposals.public.days_left') }})
              </p>
            </div>
            <div class="mt-4 md:mt-0">
              <div class="flex items-center gap-4">
                <button
                  @click="downloadPdf"
                  class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-blue-900 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-blue-800"
                >
                  <ArrowDownTrayIcon class="h-5 w-5" />
                  {{ $t('gestlab.general.labels.vap_proposals.public.download_pdf') }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="p-6">
          <!-- CUSTOMER & LAB INFO -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <div>
              <h2 class="text-lg font-semibold text-gray-900 mb-4">
                {{ $t('gestlab.general.labels.vap_proposals.public.customer_info') }}
              </h2>
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.public.customer') }}</span>
                  <span class="text-sm font-medium text-gray-900">{{ proposal.customer.name }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.public.customer_code') }}</span>
                  <span class="text-sm text-gray-900">{{ proposal.customer.code }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.public.service_location') }}</span>
                  <span class="text-sm text-gray-900">{{ proposal.service_location }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.public.withhold_tax') }}</span>
                  <span class="text-sm font-medium" :class="proposal.withhold_tax ? 'text-green-600' : 'text-gray-900'">
                    {{ proposal.withhold_tax ? $t('general.yes') : $t('general.no') }}
                  </span>
                </div>
              </div>
            </div>
            <div>
              <h2 class="text-lg font-semibold text-gray-900 mb-4">
                {{ $t('gestlab.general.labels.vap_proposals.public.lab_info') }}
              </h2>
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.public.department') }}</span>
                  <span class="text-sm font-medium text-gray-900">{{ proposal?.department?.name }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.public.warehouse') }}</span>
                  <span class="text-sm text-gray-900">{{ proposal?.warehouse?.address }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.public.tolerance_days') }}</span>
                  <span class="text-sm text-gray-900">{{ proposal.tolerance_days }} {{ $t('gestlab.general.labels.vap_proposals.public.days') }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_proposals.public.pricing_mode') }}</span>
                  <span class="text-sm font-medium" :class="proposal.use_matrix_price ? 'text-blue-600' : 'text-gray-900'">
                    {{ proposal.use_matrix_price ? $t('gestlab.general.labels.vap_proposals.public.matrix') : $t('gestlab.general.labels.vap_proposals.public.parameter') }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- TEMPLATE CONTENT -->
          <div v-if="proposal.template" class="mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              {{ proposal.template.name }}
            </h2>
            <div class="prose max-w-none border border-gray-200 rounded-lg p-6 bg-gray-50">
              <div v-html="proposal.template.content"></div>
            </div>
          </div>

          <!-- ITEMS TABLE -->
          <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              {{ $t('gestlab.general.labels.vap_proposals.public.service_items') }}
            </h2>
            <div class="overflow-hidden border border-gray-200 rounded-lg">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      {{ $t('gestlab.general.labels.vap_proposals.public.item_description') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      {{ $t('gestlab.general.labels.vap_proposals.public.standard') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      {{ $t('gestlab.general.labels.vap_proposals.public.quantity') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      {{ $t('gestlab.general.labels.vap_proposals.public.unit_price') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      {{ $t('gestlab.general.labels.vap_proposals.public.discount') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      {{ $t('gestlab.general.labels.vap_proposals.public.tax') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      {{ $t('gestlab.general.labels.vap_proposals.public.total') }}
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="(item, index) in proposal.items" :key="item.id">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-gray-900">{{ item.item_description }}</div>
                      <div v-if="item.obs" class="text-xs text-gray-500 mt-1">{{ item.obs }}</div>
                      <div v-if="item.itemable_type" class="text-xs text-gray-500 mt-1">
                        {{ getItemableTypeLabel(item.itemable_type) }} #{{ item.itemable_id }}
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="text-sm text-gray-900">{{ item.standard?.code || '-' }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900">{{ item.qty }} {{ item.unit?.code || '' }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900">{{ formatCurrency(item.unit_price) }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div v-if="item.discount_amount > 0" class="text-sm text-green-600">
                        <span v-if="item.discount_id === 1">{{ item.discount_percentage }}%</span>
                        <span class="block">-{{ formatCurrency(item.discount_amount) }}</span>
                      </div>
                      <div v-else class="text-sm text-gray-500">-</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div v-if="item.tax_amount > 0" class="text-sm">
                        <span class="text-gray-900">{{ item.tax_percentage }}%</span>
                        <span class="block text-blue-600">+{{ formatCurrency(item.tax_amount) }}</span>
                      </div>
                      <div v-else class="text-sm text-gray-500">-</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-semibold text-gray-900">{{ formatCurrency(item.total) }}</div>
                    </td>
                  </tr>
                </tbody>
                <tfoot class="bg-gray-50">
                  <tr>
                    <td colspan="5" class="px-6 py-4 text-right text-sm font-medium text-gray-900">
                      {{ $t('gestlab.general.labels.vap_proposals.public.subtotal') }}
                    </td>
                    <td colspan="2" class="px-6 py-4 text-sm font-semibold text-gray-900">
                      {{ formatCurrency(proposal.sub_total) }}
                    </td>
                  </tr>
                  <tr v-if="proposal.discount > 0">
                    <td colspan="5" class="px-6 py-4 text-right text-sm font-medium text-gray-900">
                      {{ $t('gestlab.general.labels.vap_proposals.public.total_discount') }}
                    </td>
                    <td colspan="2" class="px-6 py-4 text-sm font-semibold text-green-600">
                      -{{ formatCurrency(proposal.discount) }}
                    </td>
                  </tr>
                  <tr v-if="proposal.tax > 0">
                    <td colspan="5" class="px-6 py-4 text-right text-sm font-medium text-gray-900">
                      {{ $t('gestlab.general.labels.vap_proposals.public.total_tax') }}
                    </td>
                    <td colspan="2" class="px-6 py-4 text-sm font-semibold text-gray-900">
                      +{{ formatCurrency(proposal.tax) }}
                    </td>
                  </tr>
                  <tr class="bg-blue-50">
                    <td colspan="5" class="px-6 py-4 text-right text-lg font-bold text-gray-900">
                      {{ $t('gestlab.general.labels.vap_proposals.public.total_amount') }}
                    </td>
                    <td colspan="2" class="px-6 py-4 text-lg font-bold text-blue-900">
                      {{ formatCurrency(proposal.total) }}
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          <!-- OBSERVATIONS -->
          <div v-if="proposal.obs" class="mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
              {{ $t('gestlab.general.labels.vap_proposals.public.observations') }}
            </h2>
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
              <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ proposal.obs }}</p>
            </div>
          </div>

          <!-- COMPLIANCE AGREEMENT FORM -->
          <div v-if="showComplianceForm && !isExpired" class="bg-white border border-gray-200 rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">
              {{ $t('gestlab.general.labels.vap_proposals.public.compliance_agreement') }}
            </h2>
            
            <div v-if="proposal.compliance_agreement?.acknowledged_at" class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
              <div class="flex items-center">
                <CheckCircleIcon class="h-5 w-5 text-green-600 mr-3" />
                <div>
                  <h3 class="text-sm font-semibold text-green-900">
                    {{ $t('gestlab.general.labels.vap_proposals.public.agreement_signed') }}
                  </h3>
                  <p class="text-sm text-green-700 mt-1">
                    {{ $t('gestlab.general.labels.vap_proposals.public.signed_on') }} {{ formatDateTime(proposal.compliance_agreement.acknowledged_at) }}
                  </p>
                </div>
              </div>
            </div>

            <form v-else @submit.prevent="submitAgreement" class="space-y-6">
              <div class="space-y-4">
                <div class="flex items-start">
                  <input
                    type="checkbox"
                    v-model="agreement.confidentiality"
                    id="confidentiality"
                    :class="['mt-1 h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900', form.errors.confidentiality ? 'border-red-500' : '']"
                  />
                  <label for="confidentiality" class="ml-3 text-sm text-gray-700">
                    {{ $t('gestlab.general.labels.vap_proposals.public.confidentiality_text') }}
                  </label>
                </div>
                <p v-if="form.errors.confidentiality" class="text-xs text-red-600">
                  {{ form.errors.confidentiality }}
                </p>

                <div class="flex items-start">
                  <input
                    type="checkbox"
                    v-model="agreement.impartiality"
                    id="impartiality"
                    :class="['mt-1 h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900', form.errors.impartiality ? 'border-red-500' : '']"
                  />
                  <label for="impartiality" class="ml-3 text-sm text-gray-700">
                    {{ $t('gestlab.general.labels.vap_proposals.public.impartiality_text') }}
                  </label>
                </div>
                <p v-if="form.errors.impartiality" class="text-xs text-red-600">
                  {{ form.errors.impartiality }}
                </p>

                <div class="flex items-start">
                  <input
                    type="checkbox"
                    v-model="agreement.nondisclosure"
                    id="nondisclosure"
                    :class="['mt-1 h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900', form.errors.nondisclosure ? 'border-red-500' : '']"
                  />
                  <label for="nondisclosure" class="ml-3 text-sm text-gray-700">
                    {{ $t('gestlab.general.labels.vap_proposals.public.nondisclosure_text') }}
                  </label>
                </div>
                <p v-if="form.errors.nondisclosure" class="text-xs text-red-600">
                  {{ form.errors.nondisclosure }}
                </p>
              </div>

              <div class="flex items-center gap-4">
                <button
                  type="submit"
                  :disabled="form.processing || !canSubmitAgreement"
                  :class="[
                    'inline-flex items-center gap-2 rounded-lg px-6 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                    form.processing || !canSubmitAgreement
                      ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                      : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
                  ]"
                >
                  <CheckCircleIcon class="h-5 w-5" />
                  {{ form.processing ? $t('actions.processing') : $t('gestlab.general.labels.vap_proposals.public.accept_proposal') }}
                </button>

                <button
                  type="button"
                  @click="showRejectModal = true"
                  :disabled="form.processing"
                  class="inline-flex items-center gap-2 rounded-lg border border-red-300 bg-white px-6 py-3 text-sm font-semibold text-red-700 shadow-sm hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                >
                  <XCircleIcon class="h-5 w-5" />
                  {{ $t('gestlab.general.labels.vap_proposals.public.reject_proposal') }}
                </button>
              </div>
            </form>
          </div>

          <!-- STATUS MESSAGES -->
          <div v-if="!showComplianceForm" class="text-center py-8">
            <div v-if="proposal.status === 'ACCEPTED'" class="bg-green-50 border border-green-200 rounded-lg p-6 inline-block">
              <CheckCircleIcon class="h-12 w-12 text-green-600 mx-auto mb-4" />
              <h3 class="text-lg font-semibold text-green-900 mb-2">
                {{ $t('gestlab.general.labels.vap_proposals.public.proposal_accepted') }}
              </h3>
              <p class="text-sm text-green-700">
                {{ $t('gestlab.general.labels.vap_proposals.public.accepted_on') }} {{ formatDateTime(proposal.compliance_agreement?.acknowledged_at) }}
              </p>
            </div>
            
            <div v-else-if="proposal.status === 'REJECTED'" class="bg-red-50 border border-red-200 rounded-lg p-6 inline-block">
              <XCircleIcon class="h-12 w-12 text-red-600 mx-auto mb-4" />
              <h3 class="text-lg font-semibold text-red-900 mb-2">
                {{ $t('gestlab.general.labels.vap_proposals.public.proposal_rejected') }}
              </h3>
            </div>
            
            <div v-else-if="isExpired" class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 inline-block">
              <ExclamationCircleIcon class="h-12 w-12 text-yellow-600 mx-auto mb-4" />
              <h3 class="text-lg font-semibold text-yellow-900 mb-2">
                {{ $t('gestlab.general.labels.vap_proposals.public.proposal_expired') }}
              </h3>
              <p class="text-sm text-yellow-700">
                {{ $t('gestlab.general.labels.vap_proposals.public.expired_on') }} {{ formatDate(proposal.expiry_date) }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- FOOTER -->
    <div class="bg-white border-t border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="text-center text-sm text-gray-500">
          <p>{{ companyName }} • {{ companyAddress }} • {{ companyPhone }} • {{ companyEmail }}</p>
          <p class="mt-2">{{ companyCopyright }}</p>
        </div>
      </div>
    </div>

    <!-- REJECT MODAL -->
    <Modal :show="showRejectModal" @close="showRejectModal = false">
      <div class="p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-lg font-semibold text-gray-900">
            {{ $t('gestlab.general.labels.vap_proposals.public.reject_proposal') }}
          </h2>
          <button @click="showRejectModal = false" class="text-gray-400 hover:text-gray-600">
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>
        
        <form @submit.prevent="submitRejection">
          <div class="space-y-4">
            <div>
              <label for="reject_reason" class="block text-sm font-medium text-gray-700 mb-2">
                {{ $t('gestlab.general.labels.vap_proposals.public.reject_reason') }}
                <span class="text-red-500">*</span>
              </label>
              <textarea
                v-model="rejectForm.reason"
                id="reject_reason"
                rows="4"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
                :class="{ 'border-red-500': rejectForm.errors.reason }"
                :placeholder="$t('gestlab.general.labels.vap_proposals.public.reject_reason_placeholder')"
              ></textarea>
              <p v-if="rejectForm.errors.reason" class="mt-2 text-xs text-red-600">
                {{ rejectForm.errors.reason }}
              </p>
            </div>
            
            <div class="flex justify-end gap-4 pt-4">
              <button
                type="button"
                @click="showRejectModal = false"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500"
              >
                {{ $t('general.cancel') }}
              </button>
              <button
                type="submit"
                :disabled="rejectForm.processing || !rejectForm.reason"
                :class="[
                  'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200',
                  rejectForm.processing || !rejectForm.reason
                    ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                    : 'bg-gradient-to-r from-red-600 to-red-700 text-white hover:from-red-700 hover:to-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2'
                ]"
              >
                <XCircleIcon class="h-5 w-5" />
                {{ rejectForm.processing ? $t('actions.processing') : $t('gestlab.general.labels.vap_proposals.public.confirm_reject') }}
              </button>
            </div>
          </div>
        </form>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import {
  ArrowDownTrayIcon,
  ExclamationCircleIcon,
  CheckCircleIcon,
  XCircleIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'
import Modal from '@/Components/modal.vue'
import Layout from "@/Shared/Layouts/empty-layout.vue";
import { trans } from "laravel-vue-i18n";


defineOptions({
    layout: Layout
});

const props = defineProps({
  proposal: Object,
  isExpired: Boolean,
})

// Company info from config
const companyName = ref('')
const companyLogo = ref('')
const companyTagline = ref('')
const companyAddress = ref('')
const companyPhone = ref('')
const companyEmail = ref('')
const companyCopyright = ref(`© ${new Date().getFullYear()} All rights reserved.`)

// Forms
const form = useForm({
  confidentiality: false,
  impartiality: false,
  nondisclosure: false,
})

const rejectForm = useForm({
  reason: '',
})

// UI State
const showRejectModal = ref(false)

// Agreement object
const agreement = ref({
  confidentiality: false,
  impartiality: false,
  nondisclosure: false,
})

// Computed properties
const showComplianceForm = computed(() => {
  return !props.isExpired && 
         !props.proposal.compliance_agreement?.acknowledged_at &&
         props.proposal.status !== 'REJECTED' &&
         props.proposal.status !== 'ACCEPTED'
})

const canSubmitAgreement = computed(() => {
  return agreement.value.confidentiality && 
         agreement.value.impartiality && 
         agreement.value.nondisclosure
})

// Methods
const formatDate = (date) => {
  return new Date(date).toLocaleDateString('pt-BR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatDateTime = (date) => {
  return new Date(date).toLocaleString('pt-BR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL'
  }).format(amount)
}

const getItemableTypeLabel = (itemableType) => {
  if (itemableType.includes('Matrix')) {
    return trans('gestlab.general.labels.vap_proposals.public.matrix')
  } else if (itemableType.includes('Parameter')) {
    return trans('gestlab.general.labels.vap_proposals.public.parameter')
  }
  return itemableType.split('\\').pop()
}

const downloadPdf = () => {
  window.open(route('vap-proposals.public.download', props.proposal.unique_hash), '_blank')
}

const submitAgreement = () => {
  if (!canSubmitAgreement.value) {
    return
  }

  form.confidentiality = agreement.value.confidentiality
  form.impartiality = agreement.value.impartiality
  form.nondisclosure = agreement.value.nondisclosure

  form.post(route('proposals.api.accept', props.proposal.id), {
    preserveScroll: true,
    onSuccess: () => {
      // Show success message or redirect
      window.location.reload()
    },
    onError: (errors) => {
      console.error('Error submitting agreement:', errors)
    }
  })
}

const submitRejection = () => {
  if (!rejectForm.reason.trim()) {
    return
  }

  rejectForm.post(route('proposals.api.reject', props.proposal.id), {
    preserveScroll: true,
    onSuccess: () => {
      showRejectModal.value = false
      window.location.reload()
    },
    onError: (errors) => {
      console.error('Error rejecting proposal:', errors)
    }
  })
}

// Update agreement object when form changes
const updateAgreement = () => {
  agreement.value = {
    confidentiality: form.confidentiality,
    impartiality: form.impartiality,
    nondisclosure: form.nondisclosure,
  }
}
</script>

<style scoped>
@reference "../../../css/app.css";

.prose :deep(h1) {
  @apply text-2xl font-bold text-gray-900 mb-4;
}

.prose :deep(h2) {
  @apply text-xl font-semibold text-gray-900 mb-3;
}

.prose :deep(h3) {
  @apply text-lg font-medium text-gray-900 mb-2;
}

.prose :deep(p) {
  @apply text-sm text-gray-700 mb-3;
}

.prose :deep(ul) {
  @apply list-disc pl-5 mb-4;
}

.prose :deep(li) {
  @apply text-sm text-gray-700 mb-1;
}

.prose :deep(table) {
  @apply min-w-full divide-y divide-gray-200;
}

.prose :deep(th) {
  @apply px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider;
}

.prose :deep(td) {
  @apply px-3 py-2 text-sm text-gray-900;
}
</style>
