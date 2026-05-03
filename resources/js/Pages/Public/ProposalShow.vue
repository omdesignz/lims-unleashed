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
                {{ $t('gestlab.general.labels.vap_proposals.public.by') }} {{ proposal.user?.name || $t('gestlab.general.labels.vap_proposals.public.unknown') }}
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
                    {{ proposal.withhold_tax ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no') }}
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
          <div v-if="parsedTemplateContent" class="mb-8">
            <div class="prose max-w-none border border-gray-200 rounded-lg p-6 bg-gray-50">
              <div v-html="parsedTemplateContent"></div>
            </div>
          </div>

          <!-- ITEMS TABLE -->
          <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-lg font-semibold text-gray-900">
                {{ $t('gestlab.general.labels.vap_proposals.public.service_items') }}
              </h2>
              <div class="text-sm text-gray-500">
                {{ proposal.items?.length || 0 }} {{ $t('gestlab.general.labels.vap_proposals.public.items') }} • 
                {{ taxableItemsCount }} {{ $t('gestlab.general.labels.vap_proposals.public.taxable_items') }}
              </div>
            </div>
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
                    <td class="px-6 py-4">
                      <div class="text-sm font-medium text-gray-900">{{ item.item_description }}</div>
                      <div v-if="item.obs" class="text-xs text-gray-500 mt-1">{{ item.obs }}</div>
                      <div v-if="item.itemable_type" class="text-xs text-gray-500 mt-1">
                        {{ getItemableTypeLabel(item.itemable_type) }} #{{ item.itemable_id }}
                      </div>
                      <div v-if="!item.charge_tax" class="text-xs text-green-600 mt-1">
                        ✓ {{ $t('gestlab.general.labels.vap_proposals.public.tax_exempt') }}
                      </div>
                      <div v-if="item.exemption_code" class="text-xs text-green-600 mt-1">
                        {{ $t('gestlab.general.labels.vap_proposals.public.exemption_code') }}: {{ item.exemption_code }}
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
                  <tr v-if="proposal.global_discount_amount > 0">
                    <td colspan="5" class="px-6 py-4 text-right text-sm font-medium text-gray-900 text-green-600">
                      {{ $t('gestlab.general.labels.vap_proposals.public.global_discount') }}
                    </td>
                    <td colspan="2" class="px-6 py-4 text-sm font-semibold text-green-600">
                      -{{ formatCurrency(proposal.global_discount_amount) }}
                      <div v-if="proposal.global_discount_percentage > 0" class="text-xs text-gray-500">
                        ({{ proposal.global_discount_percentage }}%)
                      </div>
                    </td>
                  </tr>
                  <tr v-if="proposal.withholding_tax_amount > 0">
                    <td colspan="5" class="px-6 py-4 text-right text-sm font-medium text-gray-900">
                      {{ $t('gestlab.general.labels.vap_proposals.public.withholding_tax') }}
                    </td>
                    <td colspan="2" class="px-6 py-4 text-sm font-semibold text-gray-900">
                      -{{ formatCurrency(proposal.withholding_tax_amount) }}
                      <div v-if="proposal.withholding_tax_percentage > 0" class="text-xs text-gray-500">
                        ({{ proposal.withholding_tax_percentage }}%)
                      </div>
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
              <p v-if="proposal.compliance_agreement?.rejection_reason" class="text-sm text-red-700 mt-2">
                {{ $t('gestlab.general.labels.vap_proposals.public.rejection_reason') }}: {{ proposal.compliance_agreement.rejection_reason }}
              </p>
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
import { ref, computed, watch } from 'vue'
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

const taxableItemsCount = computed(() => {
  return props.proposal.items?.filter(item => item.charge_tax).length || 0
})

const parsedTemplateContent = computed(() => {
  if (!props.proposal.template?.content) return ''
  
  // Parse template content with proposal data
  return parseTemplateContent(props.proposal.template.content, props.proposal)
})

// Methods
const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('pt-BR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatDateTime = (date) => {
  if (!date) return ''
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
    currency: 'AOA'
  }).format(amount || 0)
}

const getItemableTypeLabel = (itemableType) => {
  if (itemableType.includes('Matrix')) {
    return trans('gestlab.general.labels.vap_proposals.public.matrix')
  } else if (itemableType.includes('Parameter')) {
    return trans('gestlab.general.labels.vap_proposals.public.parameter')
  }
  return itemableType.split('\\').pop()
}

const parseTemplateContent = (template, proposal) => {
  if (!template) return ''
  
  let content = template
  
  // Define all replacements matching the PHP model
  const replacements = {
    '{proposal_number}': proposal.proposal_number || '',
    '{customer_name}': proposal.customer?.name || '',
    '{customer_code}': proposal.customer?.code || '',
    '{service_location}': proposal.service_location || '',
    '{department}': proposal.department?.name || '',
    '{warehouse}': proposal.warehouse?.address || '',
    '{created_at}': formatDate(proposal.created_at),
    '{created_by}': proposal.user?.name || '',
    '{tolerance_days}': proposal.tolerance_days || '',
    '{expiry_date}': formatDate(proposal.expiry_date),
    '{days_until_expiry}': proposal.days_until_expiry || '',
    '{sub_total}': formatCurrency(proposal.sub_total),
    '{total}': formatCurrency(proposal.total),
    '{tax}': formatCurrency(proposal.tax || 0),
    '{discount}': formatCurrency(proposal.discount || 0),
    '{global_discount_amount}': formatCurrency(proposal.global_discount_amount || 0),
    '{global_discount_percentage}': proposal.global_discount_percentage ? proposal.global_discount_percentage.replace('.', ',') : '0,00',
    '{withholding_tax_amount}': formatCurrency(proposal.withholding_tax_amount || 0),
    '{withholding_tax_percentage}': proposal.withholding_tax_percentage ? proposal.withholding_tax_percentage.replace('.', ',') : '0,00',
    '{pricing_mode}': proposal.use_matrix_price ? 'Preço de Matriz' : 'Preço de Parâmetro',
    '{withhold_tax}': proposal.withhold_tax ? 'Sim' : 'Não',
    '{observations}': proposal.obs || '',
    '{total_items}': proposal.items?.length || 0,
    '{taxable_items}': taxableItemsCount.value,
  }
  
  // Special handling for template tags
  // Items table
  if (content.includes('{items_table}')) {
    const itemsTableHtml = generateItemsTableHtml(proposal)
    content = content.replace('{items_table}', itemsTableHtml)
  }
  
  // Items list
  if (content.includes('{items_list}')) {
    const itemsListHtml = generateItemsListHtml(proposal)
    content = content.replace('{items_list}', itemsListHtml)
  }
  
  // Summary table
  if (content.includes('{summary_table}')) {
    const summaryTableHtml = generateSummaryTableHtml(proposal)
    content = content.replace('{summary_table}', summaryTableHtml)
  }
  
  // Replace all other placeholders
  Object.keys(replacements).forEach(key => {
    const regex = new RegExp(escapeRegExp(key), 'g')
    content = content.replace(regex, replacements[key])
  })
  
  // Handle conditional observations
  if (content.includes('{if:observations}') && content.includes('{endif:observations}')) {
    const start = content.indexOf('{if:observations}')
    const end = content.indexOf('{endif:observations}') + '{endif:observations}'.length
    const conditionalBlock = content.substring(start, end)
    
    if (proposal.obs) {
      // Keep content inside the conditional block
      const innerContent = conditionalBlock
        .replace('{if:observations}', '')
        .replace('{endif:observations}', '')
      content = content.replace(conditionalBlock, innerContent)
    } else {
      // Remove the entire conditional block
      content = content.replace(conditionalBlock, '')
    }
  }
  
  return content
}

const escapeRegExp = (string) => {
  return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}

const generateItemsTableHtml = (proposal) => {
  if (!proposal.items || proposal.items.length === 0) {
    return '<p>Nenhum item disponível</p>'
  }
  
  let html = '<table style="width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 9pt;">'
  html += '<thead>'
  html += '<tr style="background: #1e3a8a; color: white;">'
  html += '<th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Item</th>'
  html += '<th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Descrição</th>'
  html += '<th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Padrão</th>'
  html += '<th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Qtd</th>'
  html += '<th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Unid.</th>'
  html += '<th style="padding: 8px; border: 1px solid #ddd; text-align: right;">Preço Unit.</th>'
  html += '<th style="padding: 8px; border: 1px solid #ddd; text-align: right;">Desconto</th>'
  html += '<th style="padding: 8px; border: 1px solid #ddd; text-align: right;">Taxa</th>'
  html += '<th style="padding: 8px; border: 1px solid #ddd; text-align: right;">Total</th>'
  html += '</tr>'
  html += '</thead>'
  html += '<tbody>'

  proposal.items.forEach((item, index) => {
    const rowStyle = index % 2 === 0 ? 'background: #f9fafb;' : ''
    
    html += `<tr style="${rowStyle}">`
    html += `<td style="padding: 8px; border: 1px solid #ddd; text-align: center; font-weight: bold;">${index + 1}</td>`
    html += `<td style="padding: 8px; border: 1px solid #ddd;">`
    html += `<div style="font-weight: bold;">${escapeHtml(item.item_description)}</div>`
    
    if (item.obs) {
      html += `<div style="font-size: 8pt; color: #666; margin-top: 2px;">${escapeHtml(item.obs)}</div>`
    }
    
    if (item.itemable_type) {
      const typeLabel = item.itemable_type.includes('Matrix') ? 'Matriz' : 'Parâmetro'
      html += `<div style="font-size: 8pt; color: #666; margin-top: 2px;">${typeLabel} #${item.itemable_id}</div>`
    }
    
    if (!item.charge_tax) {
      html += '<div style="font-size: 8pt; color: #10b981; margin-top: 2px;">✓ Isento de taxa</div>'
    }
    
    if (item.exemption_code) {
      html += `<div style="font-size: 8pt; color: #10b981; margin-top: 2px;">Código isenção: ${item.exemption_code}</div>`
    }
    
    html += '</td>'
    html += `<td style="padding: 8px; border: 1px solid #ddd; text-align: center;">${item.standard?.code || '-'}</td>`
    html += `<td style="padding: 8px; border: 1px solid #ddd; text-align: center;">${formatNumber(item.qty)}</td>`
    html += `<td style="padding: 8px; border: 1px solid #ddd; text-align: center;">${item.unit?.code || '-'}</td>`
    html += `<td style="padding: 8px; border: 1px solid #ddd; text-align: right;">AOA ${formatNumber(item.unit_price)}</td>`
    
    // Discount cell
    html += '<td style="padding: 8px; border: 1px solid #ddd; text-align: right;">'
    if (item.discount_amount > 0) {
      if (item.discount_id == 1) {
        html += `<div style="font-size: 8pt; color: #10b981;">${formatNumber(item.discount_percentage)}%</div>`
      }
      html += `-AOA ${formatNumber(item.discount_amount)}`
    } else {
      html += '-'
    }
    html += '</td>'
    
    // Tax cell
    html += '<td style="padding: 8px; border: 1px solid #ddd; text-align: right;">'
    if (item.tax_amount > 0) {
      html += `<div style="font-size: 8pt; color: #3b82f6;">${formatNumber(item.tax_percentage)}%</div>`
      html += `+AOA ${formatNumber(item.tax_amount)}`
    } else {
      html += '-'
    }
    html += '</td>'
    
    // Total cell
    html += '<td style="padding: 8px; border: 1px solid #ddd; text-align: right; font-weight: bold;">'
    html += `AOA ${formatNumber(item.total)}`
    html += '</td>'
    
    html += '</tr>'
  })

  html += '</tbody>'
  html += '</table>'

  return html
}

const generateItemsListHtml = (proposal) => {
  if (!proposal.items || proposal.items.length === 0) {
    return '<p>Nenhum item disponível</p>'
  }
  
  let html = '<ul style="list-style-type: none; padding-left: 0; margin: 20px 0;">'
  
  proposal.items.forEach((item, index) => {
    html += '<li style="margin-bottom: 15px; padding: 10px; border-left: 3px solid #1e3a8a; background: #f8fafc;">'
    html += `<div style="font-weight: bold;">${index + 1}. ${escapeHtml(item.item_description)}</div>`
    
    const details = []
    if (item.standard?.code) {
      details.push(`Padrão: ${item.standard.code}`)
    }
    
    details.push(`Quantidade: ${formatNumber(item.qty)} ${item.unit?.code || ''}`)
    details.push(`Preço unitário: AOA ${formatNumber(item.unit_price)}`)
    
    if (item.discount_amount > 0) {
      if (item.discount_id == 1) {
        details.push(`Desconto: ${formatNumber(item.discount_percentage)}% (-AOA ${formatNumber(item.discount_amount)})`)
      } else {
        details.push(`Desconto: -AOA ${formatNumber(item.discount_amount)}`)
      }
    }
    
    if (item.tax_amount > 0) {
      details.push(`Taxa: ${formatNumber(item.tax_percentage)}% (+AOA ${formatNumber(item.tax_amount)})`)
    }
    
    details.push(`<strong>Total: AOA ${formatNumber(item.total)}</strong>`)
    
    html += `<div style="font-size: 9pt; color: #4b5563; margin-top: 5px;">${details.join(' • ')}</div>`
    
    if (item.obs) {
      html += `<div style="font-size: 8pt; color: #666; margin-top: 5px; font-style: italic;">${escapeHtml(item.obs)}</div>`
    }
    
    html += '</li>'
  })
  
  html += '</ul>'
  
  return html
}

const generateSummaryTableHtml = (proposal) => {
  let html = '<table style="width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 10pt;">'
  
  // Subtotal
  html += '<tr>'
  html += '<td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: bold;">Subtotal:</td>'
  html += '<td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right; width: 150px;">'
  html += `AOA ${formatNumber(proposal.sub_total)}`
  html += '</td>'
  html += '</tr>'
  
  // Discount
  if (proposal.discount > 0) {
    html += '<tr>'
    html += '<td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: bold; color: #10b981;">Desconto Total:</td>'
    html += '<td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right; color: #10b981;">'
    html += `-AOA ${formatNumber(proposal.discount)}`
    html += '</td>'
    html += '</tr>'
  }
  
  // Tax
  if (proposal.tax > 0) {
    html += '<tr>'
    html += '<td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: bold;">Taxa Total:</td>'
    html += '<td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right;">'
    html += `+AOA ${formatNumber(proposal.tax)}`
    html += '</td>'
    html += '</tr>'
  }
  
  // Global discount
  if (proposal.global_discount_amount > 0) {
    html += '<tr>'
    html += '<td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: bold; color: #10b981;">Desconto Global:</td>'
    html += '<td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right; color: #10b981;">'
    html += `-AOA ${formatNumber(proposal.global_discount_amount)}`
    
    if (proposal.global_discount_percentage > 0) {
      html += `<div style="font-size: 8pt;">(${formatNumber(proposal.global_discount_percentage)}%)</div>`
    }
    
    html += '</td>'
    html += '</tr>'
  }
  
  // Withholding tax
  if (proposal.withholding_tax_amount > 0) {
    html += '<tr>'
    html += '<td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: bold;">Imposto Retido:</td>'
    html += '<td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right;">'
    html += `AOA ${formatNumber(proposal.withholding_tax_amount)}`
    
    if (proposal.withholding_tax_percentage > 0) {
      html += `<div style="font-size: 8pt;">(${formatNumber(proposal.withholding_tax_percentage)}%)</div>`
    }
    
    html += '</td>'
    html += '</tr>'
  }
  
  // Grand Total
  html += '<tr style="background: #f1f5f9;">'
  html += '<td style="padding: 12px; border-top: 2px solid #1e3a8a; text-align: right; font-weight: bold; font-size: 11pt; color: #1e3a8a;">TOTAL GERAL:</td>'
  html += '<td style="padding: 12px; border-top: 2px solid #1e3a8a; text-align: right; font-weight: bold; font-size: 11pt; color: #1e3a8a;">'
  html += `AOA ${formatNumber(proposal.total)}`
  html += '</td>'
  html += '</tr>'
  
  // Summary footer
  html += '<tr>'
  html += '<td colspan="2" style="padding: 8px; text-align: right; font-size: 8pt; color: #666;">'
  html += `${proposal.items?.length || 0} itens • `
  html += `${taxableItemsCount.value} tributáveis`
  html += '</td>'
  html += '</tr>'
  
  html += '</table>'
  
  return html
}

const formatNumber = (number) => {
  return number ? number.replace('.', ',') : '0,00'
}

const escapeHtml = (text) => {
  const div = document.createElement('div')
  div.textContent = text
  return div.innerHTML
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

// Watch for form changes
watch(() => form.confidentiality, () => {
  agreement.value.confidentiality = form.confidentiality
})
watch(() => form.impartiality, () => {
  agreement.value.impartiality = form.impartiality
})
watch(() => form.nondisclosure, () => {
  agreement.value.nondisclosure = form.nondisclosure
})
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
