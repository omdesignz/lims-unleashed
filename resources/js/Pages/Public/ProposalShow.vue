<template>
  <div class="public-proposal-shell min-h-screen" :class="commercialDocumentThemeClasses">
    <!-- HEADER -->
    <div class="sticky top-0 z-20 border-b border-[#ded2bb]/80 bg-[#fffaf0]/90 shadow-[0_18px_48px_-34px_rgba(20,61,55,0.45)] backdrop-blur-xl dark:border-white/10 dark:bg-[#0b1210]/88">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <img v-if="companyLogo" class="h-10 max-w-[140px] rounded-2xl object-contain" :src="companyLogo" alt="Logótipo">
              <div v-else class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#143d37] text-sm font-black text-[#fffdf7] shadow-[0_16px_32px_-22px_rgba(20,61,55,0.85)]">
                {{ companyInitials }}
              </div>
            </div>
            <div class="ml-4">
              <h1 class="text-lg font-black tracking-[-0.02em] text-[#10221d] dark:text-white">{{ companyName }}</h1>
              <p class="text-sm font-semibold text-[#78847c] dark:text-slate-400">{{ companyTagline }}</p>
            </div>
          </div>
          <div class="flex items-center gap-4">
            <div class="text-right">
              <p class="text-xs font-black uppercase tracking-[0.22em] text-[#78847c] dark:text-slate-400">{{ $t('gestlab.general.labels.vap_proposals.public.proposal') }}</p>
              <p class="text-lg font-black text-[#143d37] dark:text-emerald-100">{{ proposal.proposal_number }}</p>
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

    <div v-if="pageErrorMessage" class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
      <div class="rounded-[22px] border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-800 shadow-[0_18px_40px_-30px_rgba(185,28,28,0.45)] dark:border-red-300/20 dark:bg-red-400/10 dark:text-red-200">
        {{ pageErrorMessage }}
      </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="overflow-hidden rounded-[34px] border border-[#ded2bb] bg-white/92 shadow-[0_30px_90px_-54px_rgba(20,61,55,0.62)] dark:border-white/10 dark:bg-slate-950/92">
        <!-- PROPOSAL HEADER -->
        <div class="relative overflow-hidden border-b border-[#ded2bb] bg-[radial-gradient(circle_at_top_left,rgba(199,154,67,0.24),transparent_34%),linear-gradient(135deg,#fffaf0,#f6efe1_46%,#143d37_46%,#143d37)] px-6 py-8 dark:border-white/10 dark:bg-[radial-gradient(circle_at_top_left,rgba(199,154,67,0.18),transparent_34%),linear-gradient(135deg,#17231f,#101815_48%,#0b1210_48%,#0b1210)]">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
              <span class="inline-flex items-center rounded-full border border-[#c79a43]/40 bg-white/85 px-3 py-1 text-xs font-black uppercase tracking-[0.24em] text-[#143d37] shadow-sm dark:bg-white/10 dark:text-amber-100">
                {{ $t('gestlab.general.labels.vap_proposals.public.client_validation') }}
              </span>
              <h1 class="mt-5 text-3xl font-black tracking-[-0.04em] text-[#10221d] dark:text-white md:max-w-2xl lg:text-5xl">
                {{ $t('gestlab.general.labels.vap_proposals.public.proposal_for') }} {{ proposal.customer.name }}
              </h1>
              <p class="mt-4 max-w-2xl text-sm font-semibold leading-6 text-[#59665f] dark:text-slate-300">
                {{ $t('gestlab.general.labels.vap_proposals.public.created_on') }} {{ formatDate(proposal.created_at) }}
                {{ $t('gestlab.general.labels.vap_proposals.public.by') }} {{ proposal.user?.name || $t('gestlab.general.labels.vap_proposals.public.unknown') }}
              </p>
              <p v-if="proposal.expiry_date" class="mt-1 text-sm font-semibold text-[#59665f] dark:text-slate-300">
                {{ $t('gestlab.general.labels.vap_proposals.public.valid_until') }} {{ formatDate(proposal.expiry_date) }}
                ({{ proposal.days_until_expiry }} {{ $t('gestlab.general.labels.vap_proposals.public.days_left') }})
              </p>
            </div>
            <div class="mt-4 md:mt-0">
              <div class="flex items-center gap-4">
                <button
                  @click="downloadPdf"
                  class="inline-flex items-center gap-2 rounded-[20px] bg-[#143d37] px-5 py-3 text-sm font-black text-white shadow-[0_18px_42px_-24px_rgba(20,61,55,0.75)] transition hover:bg-[#0f302b] focus:outline-none focus:ring-2 focus:ring-[#c79a43] focus:ring-offset-2 dark:ring-offset-slate-950"
                >
                  <ArrowDownTrayIcon class="h-5 w-5" />
                  {{ $t('gestlab.general.labels.vap_proposals.public.download_pdf') }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="grid gap-4 border-b border-[#ded2bb] bg-[#fbfaf6] px-6 py-5 dark:border-white/10 dark:bg-white/5 md:grid-cols-3">
          <article class="rounded-[24px] border border-[#ded2bb] bg-white/85 p-4 shadow-[0_18px_48px_-36px_rgba(20,61,55,0.48)] dark:border-white/10 dark:bg-white/5">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[#78847c] dark:text-slate-400">{{ $t('gestlab.general.labels.vap_proposals.public.total_amount') }}</p>
            <p class="mt-2 text-2xl font-black text-[#143d37] dark:text-emerald-100">{{ formatCurrency(proposal.total) }}</p>
          </article>
          <article class="rounded-[24px] border border-[#ded2bb] bg-white/85 p-4 shadow-[0_18px_48px_-36px_rgba(20,61,55,0.48)] dark:border-white/10 dark:bg-white/5">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[#78847c] dark:text-slate-400">{{ $t('gestlab.general.labels.vap_proposals.public.service_items') }}</p>
            <p class="mt-2 text-2xl font-black text-[#143d37] dark:text-emerald-100">{{ proposal.items?.length || 0 }}</p>
          </article>
          <article class="rounded-[24px] border border-[#ded2bb] bg-white/85 p-4 shadow-[0_18px_48px_-36px_rgba(20,61,55,0.48)] dark:border-white/10 dark:bg-white/5">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-[#78847c] dark:text-slate-400">{{ $t('gestlab.general.labels.vap_proposals.public.pricing_mode') }}</p>
            <p class="mt-2 text-2xl font-black text-[#143d37] dark:text-emerald-100">
              {{ proposal.use_matrix_price ? $t('gestlab.general.labels.vap_proposals.public.matrix') : $t('gestlab.general.labels.vap_proposals.public.parameter') }}
            </p>
          </article>
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
                  <span class="text-sm font-medium" :class="proposal.use_matrix_price ? 'text-[#143d37] dark:text-emerald-100' : 'text-gray-900'">
                    {{ proposal.use_matrix_price ? $t('gestlab.general.labels.vap_proposals.public.matrix') : $t('gestlab.general.labels.vap_proposals.public.parameter') }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div v-if="hasBankingDetails" class="mb-8 rounded-[28px] border border-[#ded2bb] bg-[#fbfaf6] p-6 shadow-[0_20px_58px_-42px_rgba(20,61,55,0.45)] dark:border-white/10 dark:bg-white/5">
            <p class="text-xs font-black uppercase tracking-[0.26em] text-[#c79a43]">{{ $t('gestlab.general.labels.vap_proposals.public.banking_details') }}</p>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
              <div
                v-for="row in bankingRows"
                :key="row.label"
                class="rounded-[18px] border border-[#ded2bb] bg-white/85 px-4 py-3 dark:border-white/10 dark:bg-slate-950/60"
              >
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#78847c] dark:text-slate-400">{{ row.label }}</p>
                <p class="mt-1 break-words text-sm font-black text-[#143d37] dark:text-emerald-100">{{ row.value }}</p>
              </div>
            </div>
            <p v-if="companyBankDetails" class="mt-4 whitespace-pre-wrap text-sm font-semibold leading-6 text-[#59665f] dark:text-slate-300">
              {{ companyBankDetails }}
            </p>
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
                        <span class="block text-[#9a7a2f] dark:text-amber-200">+{{ formatCurrency(item.tax_amount) }}</span>
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
                  <tr class="bg-[#f7f1e6] dark:bg-white/5">
                    <td colspan="5" class="px-6 py-4 text-right text-lg font-bold text-gray-900">
                      {{ $t('gestlab.general.labels.vap_proposals.public.total_amount') }}
                    </td>
                    <td colspan="2" class="px-6 py-4 text-lg font-black text-[#143d37] dark:text-emerald-100">
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
                    :class="['mt-1 h-4 w-4 rounded border-gray-300 text-[#143d37] focus:ring-[#c79a43]', form.errors.confidentiality ? 'border-red-500' : '']"
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
                    :class="['mt-1 h-4 w-4 rounded border-gray-300 text-[#143d37] focus:ring-[#c79a43]', form.errors.impartiality ? 'border-red-500' : '']"
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
                    :class="['mt-1 h-4 w-4 rounded border-gray-300 text-[#143d37] focus:ring-[#c79a43]', form.errors.nondisclosure ? 'border-red-500' : '']"
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
                      : 'bg-[#143d37] text-white hover:bg-[#0f302b] focus:outline-none focus:ring-2 focus:ring-[#c79a43] focus:ring-offset-2'
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
                class="w-full rounded-[20px] border border-[#ded2bb] bg-[#fbfaf6] px-4 py-3 text-sm font-semibold text-[#33413a] focus:border-[#c79a43] focus:outline-none focus:ring-2 focus:ring-[#c79a43]/30 dark:border-white/10 dark:bg-white/5 dark:text-slate-100"
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
import { ref, computed, reactive } from 'vue'
import {
  ArrowDownTrayIcon,
  ExclamationCircleIcon,
  CheckCircleIcon,
  XCircleIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'
import Modal from '@/Components/modal.vue'
import Layout from '@/Shared/Layouts/empty-layout.vue'
import { trans } from 'laravel-vue-i18n'
import { commercialDocumentThemeClasses } from '@/Composables/useCommercialDocumentTheme'

defineOptions({
    layout: Layout
})

const props = defineProps({
  proposal: Object,
  isExpired: Boolean,
  company: {
    type: Object,
    default: () => ({}),
  },
})

const companyName = computed(() => props.company?.name || 'Laboratório')
const companyLogo = computed(() => props.company?.logo_url || '')
const companyTagline = computed(() => props.company?.tagline || 'Propostas técnicas e comerciais com rastreabilidade.')
const companyAddress = computed(() => props.company?.address || 'Endereço institucional por configurar')
const companyPhone = computed(() => props.company?.phone || 'Contacto por configurar')
const companyEmail = computed(() => props.company?.email || 'email por configurar')
const companyCopyright = computed(() => `© ${new Date().getFullYear()} ${companyName.value}. Todos os direitos reservados.`)
const companyInitials = computed(() => companyName.value
  .split(/\s+/)
  .filter(Boolean)
  .slice(0, 2)
  .map((word) => word[0]?.toUpperCase())
  .join('') || 'LU')

const form = reactive({
  processing: false,
  errors: {},
})

const rejectForm = reactive({
  reason: '',
  processing: false,
  errors: {},
})

// UI State
const showRejectModal = ref(false)
const pageErrorMessage = ref('')

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

const bankingRows = computed(() => [
  [trans('gestlab.general.labels.vap_proposals.public.bank_name'), props.company?.bank_name],
  [trans('gestlab.general.labels.vap_proposals.public.bank_account_name'), props.company?.bank_account_name],
  [trans('gestlab.general.labels.vap_proposals.public.bank_account_number'), props.company?.bank_account_number],
  ['IBAN', props.company?.bank_iban],
  ['SWIFT', props.company?.bank_swift],
].filter(([, value]) => Boolean(value))
  .map(([label, value]) => ({ label, value })))

const hasBankingDetails = computed(() => bankingRows.value.length > 0 || Boolean(props.company?.bank_details))

const companyBankDetails = computed(() => props.company?.bank_details || '')

const parsedTemplateContent = computed(() => {
  if (!props.proposal.template?.content) return ''
  
  // Parse template content with proposal data
  return parseTemplateContent(props.proposal.template.content, props.proposal)
})

// Methods
const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('pt-AO', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatDateTime = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleString('pt-AO', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('pt-AO', {
    style: 'currency',
    currency: 'AOA'
  }).format(normaliseNumber(amount))
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
    '{global_discount_percentage}': formatNumber(proposal.global_discount_percentage),
    '{withholding_tax_amount}': formatCurrency(proposal.withholding_tax_amount || 0),
    '{withholding_tax_percentage}': formatNumber(proposal.withholding_tax_percentage),
    '{pricing_mode}': proposal.use_matrix_price ? 'Preço de Matriz' : 'Preço de Parâmetro',
    '{withhold_tax}': proposal.withhold_tax ? 'Sim' : 'Não',
    '{observations}': proposal.obs || '',
    '{total_items}': proposal.items?.length || 0,
    '{taxable_items}': taxableItemsCount.value,
    '{banking_details}': generateBankingDetailsHtml(),
    '{document_keywords}': generateDocumentKeywordsHtml(),
    '{lab_signature}': props.company?.lab_director || 'Direcção técnica',
    '{client_signature}': proposal.customer?.name || 'Representante do cliente',
    '{signature_block}': generateSignatureBlockHtml(proposal),
    '{lab_name}': companyName.value,
    '{lab_details}': generateLabDetailsHtml(),
    '{customer_details}': generateCustomerDetailsHtml(proposal),
    '{bank_name}': props.company?.bank_name || '',
    '{bank_iban}': props.company?.bank_iban || '',
  }
  
  // Special handling for template tags
  // Items table
  if (content.includes('{items_table}') || content.includes('{{items_table}}')) {
    const itemsTableHtml = generateItemsTableHtml(proposal)
    content = replacePlaceholder(content, '{items_table}', itemsTableHtml)
  }
  
  // Items list
  if (content.includes('{items_list}') || content.includes('{{items_list}}')) {
    const itemsListHtml = generateItemsListHtml(proposal)
    content = replacePlaceholder(content, '{items_list}', itemsListHtml)
  }
  
  // Summary table
  if (content.includes('{summary_table}') || content.includes('{{summary_table}}')) {
    const summaryTableHtml = generateSummaryTableHtml(proposal)
    content = replacePlaceholder(content, '{summary_table}', summaryTableHtml)
  }
  
  // Replace all other placeholders
  Object.keys(replacements).forEach(key => {
    content = replacePlaceholder(content, key, replacements[key])
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

const replacePlaceholder = (content, placeholder, value) => {
  const barePlaceholder = placeholder.replace(/^\{|\}$/g, '')

  return content
    .replace(new RegExp(escapeRegExp(placeholder), 'g'), value ?? '')
    .replace(new RegExp(escapeRegExp(`{{${barePlaceholder}}}`), 'g'), value ?? '')
}

const generateBankingDetailsHtml = () => {
  const bankRows = [
    ['Banco', props.company?.bank_name],
    ['Titular', props.company?.bank_account_name],
    ['Conta', props.company?.bank_account_number],
    ['IBAN', props.company?.bank_iban],
    ['SWIFT', props.company?.bank_swift],
  ].filter(([, value]) => Boolean(value))

  const details = props.company?.bank_details
    ? `<p style="margin:10px 0 0; color:#58665f; white-space:pre-line;">${escapeHtml(props.company.bank_details)}</p>`
    : ''

  if (bankRows.length === 0 && !details) {
    return '<section style="padding:14px 16px; border:1px solid #ded3bf; border-radius:18px; background:#fffdf7; color:#58665f;">Dados bancários por configurar nas definições da aplicação.</section>'
  }

  const rows = bankRows
    .map(([label, value]) => `<div style="display:flex; justify-content:space-between; gap:18px; padding:6px 0; border-bottom:1px solid #eee4d3;"><span style="color:#738076;">${label}</span><strong style="color:#143d37; text-align:right;">${escapeHtml(value)}</strong></div>`)
    .join('')

  return `<section style="padding:16px 18px; border:1px solid #ded3bf; border-radius:18px; background:#fffdf7;"><div style="font-size:9px; letter-spacing:0.16em; text-transform:uppercase; color:#9a7a2f; font-weight:800;">Dados bancários</div><div style="margin-top:10px;">${rows}</div>${details}</section>`
}

const generateDocumentKeywordsHtml = () => {
  const keywords = String(props.company?.document_keywords || 'proposta, análise, laboratório, ISO 17025')
    .split(/[,;]+/)
    .map((keyword) => keyword.trim())
    .filter(Boolean)

  if (keywords.length === 0) return ''

  const chips = keywords
    .map((keyword) => `<span style="display:inline-block; margin:0 6px 6px 0; padding:5px 9px; border:1px solid #ded3bf; border-radius:999px; background:#fbf7ee; color:#143d37; font-size:9px; font-weight:700;">${escapeHtml(keyword)}</span>`)
    .join('')

  return `<section style="margin-top:12px;"><div style="font-size:9px; letter-spacing:0.16em; text-transform:uppercase; color:#9a7a2f; font-weight:800; margin-bottom:8px;">Palavras-chave</div>${chips}</section>`
}

const generateSignatureBlockHtml = (proposal) => {
  const labSigner = props.company?.lab_director || proposal.user?.name || 'Direcção técnica'
  const clientSigner = proposal.customer?.name || 'Representante do cliente'

  return `<section style="margin-top:24px;"><table style="width:100%; border-collapse:collapse;"><tr><td style="width:48%; padding-top:26px; border-top:1px solid #143d37; color:#20332f;"><strong>${escapeHtml(labSigner)}</strong><br><span style="color:#58665f;">Validação técnica / comercial</span></td><td style="width:4%;"></td><td style="width:48%; padding-top:26px; border-top:1px solid #143d37; color:#20332f;"><strong>${escapeHtml(clientSigner)}</strong><br><span style="color:#58665f;">Aceitação da proposta</span></td></tr></table></section>`
}

const generateLabDetailsHtml = () => {
  return detailsLinesHtml([
    companyName.value,
    props.company?.address,
    props.company?.nif ? `NIF: ${props.company.nif}` : null,
    props.company?.phone,
    props.company?.email,
    props.company?.lab_director ? `Direção técnica: ${props.company.lab_director}` : null,
  ], 'Laboratório')
}

const generateCustomerDetailsHtml = (proposal) => {
  const customer = proposal.customer || {}

  return detailsLinesHtml([
    customer.name,
    customer.address,
    customer.nif ? `NIF: ${customer.nif}` : null,
    customer.primary_phone || customer.contact || customer.phone,
    customer.email || customer.invoicing_email,
  ], customer.name || 'Cliente')
}

const detailsLinesHtml = (lines, fallback) => {
  const visibleLines = lines
    .filter((line) => line !== null && line !== undefined && String(line).trim() !== '')
    .map((line) => escapeHtml(line).replace(/\n/g, '<br>'))

  return (visibleLines.length ? visibleLines : [escapeHtml(fallback)]).join('<br>')
}

const generateItemsTableHtml = (proposal) => {
  if (!proposal.items || proposal.items.length === 0) {
    return '<p>Nenhum item disponível</p>'
  }
  
  let html = '<table style="width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 9pt; border: 1px solid #ded3bf;">'
  html += '<thead>'
  html += '<tr style="background: #143d37; color: #fffdf7;">'
  html += '<th style="padding: 8px; border: 1px solid #143d37; text-align: left;">Item</th>'
  html += '<th style="padding: 8px; border: 1px solid #143d37; text-align: left;">Descrição</th>'
  html += '<th style="padding: 8px; border: 1px solid #143d37; text-align: center;">Norma</th>'
  html += '<th style="padding: 8px; border: 1px solid #143d37; text-align: center;">Qtd.</th>'
  html += '<th style="padding: 8px; border: 1px solid #143d37; text-align: center;">Unid.</th>'
  html += '<th style="padding: 8px; border: 1px solid #143d37; text-align: right;">Preço unit.</th>'
  html += '<th style="padding: 8px; border: 1px solid #143d37; text-align: right;">Desconto</th>'
  html += '<th style="padding: 8px; border: 1px solid #143d37; text-align: right;">Imposto</th>'
  html += '<th style="padding: 8px; border: 1px solid #143d37; text-align: right;">Total</th>'
  html += '</tr>'
  html += '</thead>'
  html += '<tbody>'

  proposal.items.forEach((item, index) => {
    const rowStyle = index % 2 === 0 ? 'background: #fffdf7;' : 'background: #ffffff;'
    
    html += `<tr style="${rowStyle}">`
    html += `<td style="padding: 8px; border: 1px solid #ded3bf; text-align: center; font-weight: bold; color:#143d37;">${index + 1}</td>`
    html += `<td style="padding: 8px; border: 1px solid #ded3bf;">`
    html += `<div style="font-weight: bold;">${escapeHtml(item.item_description)}</div>`
    
    if (item.obs) {
      html += `<div style="font-size: 8pt; color: #58665f; margin-top: 2px;">${escapeHtml(item.obs)}</div>`
    }
    
    if (item.itemable_type) {
      const typeLabel = item.itemable_type.includes('Matrix') ? 'Matriz' : 'Parâmetro'
      html += `<div style="font-size: 8pt; color: #58665f; margin-top: 2px;">${typeLabel} #${item.itemable_id}</div>`
    }
    
    if (!item.charge_tax) {
      html += '<div style="font-size: 8pt; color: #147a61; margin-top: 2px;">✓ Isento de imposto</div>'
    }
    
    if (item.exemption_code) {
      html += `<div style="font-size: 8pt; color: #147a61; margin-top: 2px;">Código isenção: ${escapeHtml(item.exemption_code)}</div>`
    }
    
    html += '</td>'
    html += `<td style="padding: 8px; border: 1px solid #ded3bf; text-align: center;">${escapeHtml(item.standard?.code || '-')}</td>`
    html += `<td style="padding: 8px; border: 1px solid #ded3bf; text-align: center;">${formatNumber(item.qty)}</td>`
    html += `<td style="padding: 8px; border: 1px solid #ded3bf; text-align: center;">${escapeHtml(item.unit?.code || '-')}</td>`
    html += `<td style="padding: 8px; border: 1px solid #ded3bf; text-align: right;">AOA ${formatNumber(item.unit_price)}</td>`
    
    // Discount cell
    html += '<td style="padding: 8px; border: 1px solid #ded3bf; text-align: right;">'
    if (item.discount_amount > 0) {
      if (item.discount_id == 1) {
        html += `<div style="font-size: 8pt; color: #147a61;">${formatNumber(item.discount_percentage)}%</div>`
      }
      html += `-AOA ${formatNumber(item.discount_amount)}`
    } else {
      html += '-'
    }
    html += '</td>'
    
    // Tax cell
    html += '<td style="padding: 8px; border: 1px solid #ded3bf; text-align: right;">'
    if (item.tax_amount > 0) {
      html += `<div style="font-size: 8pt; color: #9a7a2f;">${formatNumber(item.tax_percentage)}%</div>`
      html += `+AOA ${formatNumber(item.tax_amount)}`
    } else {
      html += '-'
    }
    html += '</td>'
    
    // Total cell
    html += '<td style="padding: 8px; border: 1px solid #ded3bf; text-align: right; font-weight: bold; color:#143d37;">'
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
    html += '<li style="margin-bottom: 15px; padding: 12px 14px; border-left: 3px solid #d8b85f; border-radius: 14px; background: #fffdf7;">'
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
    
    html += `<div style="font-size: 9pt; color: #58665f; margin-top: 5px;">${details.join(' • ')}</div>`
    
    if (item.obs) {
      html += `<div style="font-size: 8pt; color: #58665f; margin-top: 5px; font-style: italic;">${escapeHtml(item.obs)}</div>`
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
  html += '<tr style="background: #fbf7ee;">'
  html += '<td style="padding: 12px; border-top: 2px solid #143d37; text-align: right; font-weight: bold; font-size: 11pt; color: #143d37;">TOTAL GERAL:</td>'
  html += '<td style="padding: 12px; border-top: 2px solid #143d37; text-align: right; font-weight: bold; font-size: 11pt; color: #143d37;">'
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
  return new Intl.NumberFormat('pt-AO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(normaliseNumber(number))
}

const normaliseNumber = (value) => {
  if (value === null || value === undefined || value === '') return 0
  if (typeof value === 'number') return Number.isFinite(value) ? value : 0

  const rawValue = String(value).trim().replace(/\s/g, '')
  const normalised = rawValue.includes(',')
    ? rawValue.replace(/\./g, '').replace(',', '.')
    : rawValue

  const parsed = Number.parseFloat(normalised)

  return Number.isFinite(parsed) ? parsed : 0
}

const escapeHtml = (text = '') => {
  return String(text ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  }[char]))
}

const downloadPdf = () => {
  window.open(route('vap-proposals.public.download', props.proposal.unique_hash), '_blank')
}

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

const postProposalDecision = async (url, payload) => {
  const response = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': csrfToken(),
    },
    body: JSON.stringify(payload),
  })

  const data = await response.json().catch(() => ({}))

  if (!response.ok) {
    const error = new Error(data.message || 'Não foi possível concluir a resposta à proposta.')
    error.errors = data.errors || {}
    error.status = response.status
    throw error
  }

  return data
}

const submitAgreement = async () => {
  pageErrorMessage.value = ''
  form.errors = {}

  if (!canSubmitAgreement.value) {
    pageErrorMessage.value = 'Confirme os compromissos de confidencialidade, imparcialidade e não divulgação antes de aceitar.'
    return
  }

  form.processing = true

  try {
    const data = await postProposalDecision(route('proposals.api.accept', props.proposal.id), {
      confidentiality: agreement.value.confidentiality,
      impartiality: agreement.value.impartiality,
      nondisclosure: agreement.value.nondisclosure,
    })

    window.location.href = data.redirect || route('vap-proposals.public.thankyou', props.proposal.id)
  } catch (error) {
    form.errors = error.errors || {}
    pageErrorMessage.value = error.message || 'Não foi possível aceitar a proposta. Revise os campos assinalados e tente novamente.'
  } finally {
    form.processing = false
  }
}

const submitRejection = async () => {
  pageErrorMessage.value = ''
  rejectForm.errors = {}

  if (!rejectForm.reason.trim()) {
    rejectForm.errors = {
      reason: 'Indique o motivo da rejeição para concluir a resposta.',
    }
    pageErrorMessage.value = 'Indique o motivo da rejeição para concluir a resposta.'
    return
  }

  rejectForm.processing = true

  try {
    const data = await postProposalDecision(route('proposals.api.reject', props.proposal.id), {
      reason: rejectForm.reason.trim(),
    })

    showRejectModal.value = false
    window.location.href = data.redirect || route('vap-proposals.public.thankyou', props.proposal.id)
  } catch (error) {
    rejectForm.errors = error.errors || {}
    pageErrorMessage.value = error.message || 'Não foi possível rejeitar a proposta. Revise o motivo informado e tente novamente.'
  } finally {
    rejectForm.processing = false
  }
}
</script>

<style scoped>
@reference "../../../css/app.css";

.public-proposal-shell {
  background:
    radial-gradient(circle at top left, rgba(199, 154, 67, 0.2), transparent 34%),
    linear-gradient(135deg, #fffaf0 0%, #f7f1e6 52%, #eef3ed 100%);
  color: #18241f;
}

:global(.dark) .public-proposal-shell {
  background:
    radial-gradient(circle at top left, rgba(199, 154, 67, 0.12), transparent 34%),
    linear-gradient(135deg, #0b1210 0%, #111c18 54%, #020617 100%);
  color: #f8fafc;
}

.public-proposal-shell :deep(.bg-white) {
  background-color: rgba(255, 255, 255, 0.92) !important;
}

.public-proposal-shell :deep(.bg-gray-50) {
  background-color: #fbfaf6 !important;
}

.public-proposal-shell :deep(.border-gray-200),
.public-proposal-shell :deep(.divide-gray-200 > :not([hidden]) ~ :not([hidden])) {
  border-color: #ded2bb !important;
}

.public-proposal-shell :deep(.text-gray-900) {
  color: #10221d !important;
}

.public-proposal-shell :deep(.text-gray-700),
.public-proposal-shell :deep(.text-gray-600),
.public-proposal-shell :deep(.text-gray-500) {
  color: #59665f !important;
}

.public-proposal-shell :deep(.rounded-xl),
.public-proposal-shell :deep(.rounded-lg) {
  border-radius: 1.5rem !important;
}

.public-proposal-shell :deep(table) {
  overflow: hidden;
  border-radius: 1.5rem;
}

.public-proposal-shell :deep(input:not([type='checkbox'])),
.public-proposal-shell :deep(textarea) {
  background: rgba(255, 255, 255, 0.98);
  color: #18231f;
  border-color: #d8cbb4;
  box-shadow: 0 14px 30px -28px rgba(20, 61, 55, 0.55);
}

:global(.dark) .public-proposal-shell :deep(.bg-white),
:global(.dark) .public-proposal-shell :deep(.bg-gray-50),
:global(.dark) .public-proposal-shell :deep(tbody),
:global(.dark) .public-proposal-shell :deep(tfoot),
:global(.dark) .public-proposal-shell :deep(thead) {
  background-color: rgba(15, 23, 42, 0.9) !important;
}

:global(.dark) .public-proposal-shell :deep(.text-gray-900),
:global(.dark) .public-proposal-shell :deep(.text-gray-700),
:global(.dark) .public-proposal-shell :deep(.text-gray-600) {
  color: #f8fafc !important;
}

:global(.dark) .public-proposal-shell :deep(.text-gray-500) {
  color: #cbd5e1 !important;
}

:global(.dark) .public-proposal-shell :deep(.border-gray-200),
:global(.dark) .public-proposal-shell :deep(.divide-gray-200 > :not([hidden]) ~ :not([hidden])) {
  border-color: rgba(255, 255, 255, 0.12) !important;
}

:global(.dark) .public-proposal-shell :deep(input:not([type='checkbox'])),
:global(.dark) .public-proposal-shell :deep(textarea) {
  background: rgba(15, 23, 42, 0.92);
  color: #f8fafc;
  border-color: rgba(255, 255, 255, 0.12);
}

.prose :deep(h1) {
  @apply text-2xl font-bold text-[#10221d] mb-4 dark:text-white;
}

.prose :deep(h2) {
  @apply text-xl font-semibold text-[#10221d] mb-3 dark:text-white;
}

.prose :deep(h3) {
  @apply text-lg font-medium text-[#10221d] mb-2 dark:text-white;
}

.prose :deep(p) {
  @apply text-sm text-[#59665f] mb-3 dark:text-slate-300;
}

.prose :deep(ul) {
  @apply list-disc pl-5 mb-4;
}

.prose :deep(li) {
  @apply text-sm text-[#59665f] mb-1 dark:text-slate-300;
}

.prose :deep(table) {
  @apply min-w-full divide-y divide-[#ded2bb] dark:divide-white/10;
}

.prose :deep(th) {
  @apply px-3 py-2 text-left text-xs font-black uppercase tracking-wider text-[#78847c] dark:text-slate-400;
}

.prose :deep(td) {
  @apply px-3 py-2 text-sm text-[#33413a] dark:text-slate-200;
}
</style>
