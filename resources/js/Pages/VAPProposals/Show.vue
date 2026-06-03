<template>
  <div :class="commercialDocumentThemeClasses" class="space-y-8 text-[#18241f] dark:text-slate-100">
    <section class="overflow-hidden rounded-[34px] border border-[#ded2bb] bg-[#fbfaf6] shadow-[0_26px_70px_-44px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950">
      <div class="relative border-b border-[#ded2bb] bg-[radial-gradient(circle_at_top_left,rgba(199,154,67,0.2),transparent_34%),linear-gradient(135deg,#fffaf0,#f6efe1_48%,#143d37_48%,#143d37)] px-6 py-7 dark:border-white/10 dark:bg-[radial-gradient(circle_at_top_left,rgba(199,154,67,0.18),transparent_34%),linear-gradient(135deg,#17231f,#101815_52%,#0b1210_52%,#0b1210)] sm:px-8">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
          <div class="max-w-3xl">
            <div class="flex flex-wrap items-center gap-3">
              <span class="inline-flex items-center gap-2 rounded-full border border-[#c79a43]/40 bg-white/80 px-3 py-1 text-xs font-bold uppercase tracking-[0.24em] text-[#143d37] shadow-sm dark:bg-white/10 dark:text-amber-100">
                <DocumentTextIcon class="h-4 w-4 text-[#c79a43]" />
                Proposta comercial
              </span>
              <span :class="['inline-flex items-center rounded-full px-3 py-1 text-xs font-bold uppercase tracking-[0.18em]', statusBadgeClasses]">
                {{ proposal.status_badge?.text || proposal.status }}
              </span>
              <span v-if="!proposal.is_original" class="inline-flex items-center gap-1 rounded-full border border-amber-300/60 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-800 dark:border-amber-300/20 dark:bg-amber-400/10 dark:text-amber-200">
                <ExclamationTriangleIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.vap_proposals.show.revision') }}
              </span>
            </div>
            <h1 class="mt-5 text-3xl font-black tracking-[-0.04em] text-[#10221d] dark:text-white sm:text-5xl">
              {{ proposal.proposal_number }}
            </h1>
            <p class="mt-4 max-w-2xl text-base font-medium leading-7 text-[#59665f] dark:text-slate-300">
              {{ $t('gestlab.general.labels.vap_proposals.show.description') }}
              <span class="font-black text-[#143d37] dark:text-emerald-100">{{ proposal.customer?.name || 'Cliente por identificar' }}</span>
            </p>
          </div>

          <div class="grid gap-3 sm:grid-cols-3 xl:min-w-[34rem]">
            <div class="rounded-[24px] border border-white/35 bg-white/80 p-4 shadow-[0_18px_50px_-34px_rgba(20,61,55,0.45)] backdrop-blur dark:border-white/10 dark:bg-white/10">
              <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#78847c] dark:text-slate-400">Valor</p>
              <p class="mt-2 text-xl font-black text-[#143d37] dark:text-emerald-100">{{ formatCurrency(proposal.total) }}</p>
            </div>
            <div class="rounded-[24px] border border-white/35 bg-white/80 p-4 shadow-[0_18px_50px_-34px_rgba(20,61,55,0.45)] backdrop-blur dark:border-white/10 dark:bg-white/10">
              <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#78847c] dark:text-slate-400">Validade</p>
              <p class="mt-2 text-xl font-black text-[#143d37] dark:text-emerald-100">{{ proposal.days_until_expiry ?? proposal.tolerance_days }} dias</p>
            </div>
            <div class="rounded-[24px] border border-white/35 bg-white/80 p-4 shadow-[0_18px_50px_-34px_rgba(20,61,55,0.45)] backdrop-blur dark:border-white/10 dark:bg-white/10">
              <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#78847c] dark:text-slate-400">Itens</p>
              <p class="mt-2 text-xl font-black text-[#143d37] dark:text-emerald-100">{{ proposalItems.length }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid gap-4 px-6 py-5 sm:grid-cols-2 xl:grid-cols-4 sm:px-8">
        <a
          v-if="proposal.file_path"
          :href="route('vap-proposals.download.pdf', proposal.id)"
          class="inline-flex items-center justify-center gap-2 rounded-[20px] bg-[#143d37] px-5 py-3 text-sm font-black text-white shadow-[0_18px_42px_-24px_rgba(20,61,55,0.7)] transition hover:bg-[#0f302b] focus:outline-none focus:ring-2 focus:ring-[#c79a43] focus:ring-offset-2 dark:ring-offset-slate-950"
        >
          <ArrowDownTrayIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.vap_proposals.show.download_pdf') }}
        </a>
        <button
          v-if="canSend"
          type="button"
          @click="sendProposal"
          class="inline-flex items-center justify-center gap-2 rounded-[20px] bg-[#c79a43] px-5 py-3 text-sm font-black text-[#10221d] shadow-[0_18px_42px_-24px_rgba(199,154,67,0.75)] transition hover:bg-[#b88930] focus:outline-none focus:ring-2 focus:ring-[#143d37] focus:ring-offset-2 dark:ring-offset-slate-950"
        >
          <PaperAirplaneIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.vap_proposals.show.send_to_client') }}
        </button>
        <Link
          v-if="canRevise"
          :href="route('vap-proposals.edit', proposal.id)"
          class="inline-flex items-center justify-center gap-2 rounded-[20px] border border-[#ded2bb] bg-white px-5 py-3 text-sm font-black text-[#143d37] transition hover:border-[#c79a43] hover:bg-[#fff7e5] dark:border-white/10 dark:bg-white/5 dark:text-emerald-100 dark:hover:bg-white/10"
        >
          <PencilSquareIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.vap_proposals.show.revise') }}
        </Link>
        <button
          type="button"
          @click="generatePdf"
          class="inline-flex items-center justify-center gap-2 rounded-[20px] border border-[#ded2bb] bg-white px-5 py-3 text-sm font-black text-[#143d37] transition hover:border-[#c79a43] hover:bg-[#fff7e5] dark:border-white/10 dark:bg-white/5 dark:text-emerald-100 dark:hover:bg-white/10"
        >
          <DocumentArrowDownIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.vap_proposals.show.actions.generate_pdf') }}
        </button>
      </div>
    </section>

    <div class="grid grid-cols-1 gap-8 xl:grid-cols-[minmax(0,1fr)_24rem]">
      <main class="space-y-8">
        <section class="rounded-[30px] border border-[#ded2bb] bg-white/90 p-6 shadow-[0_22px_70px_-46px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90 sm:p-7">
          <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.28em] text-[#c79a43]">Informação base</p>
              <h2 class="mt-2 text-2xl font-black tracking-[-0.03em] text-[#10221d] dark:text-white">
                {{ $t('gestlab.general.labels.vap_proposals.show.details.title') }}
              </h2>
            </div>
            <span v-if="proposal.use_matrix_price" class="inline-flex items-center gap-2 self-start rounded-full border border-[#ded2bb] bg-[#f7f1e6] px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-[#143d37] dark:border-white/10 dark:bg-white/10 dark:text-emerald-100">
              <Cog6ToothIcon class="h-4 w-4 text-[#c79a43]" />
              {{ $t('gestlab.general.labels.vap_proposals.show.matrix_pricing') }}
            </span>
          </div>

          <div class="mt-7 grid gap-5 lg:grid-cols-2">
            <InfoPanel :title="$t('gestlab.general.labels.vap_proposals.show.details.client_info')" icon="client">
              <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.details.customer')" :value="proposal.customer?.name" strong />
              <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.details.customer_code')" :value="proposal.customer?.code || '—'" />
              <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.details.service_location')" :value="proposal.service_location || '—'" />
              <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.details.withhold_tax')" :value="proposal.withhold_tax ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no')" />
            </InfoPanel>

            <InfoPanel :title="$t('gestlab.general.labels.vap_proposals.show.details.lab_info')" icon="lab">
              <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.details.department')" :value="proposal.department?.name || '—'" strong />
              <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.details.warehouse')" :value="proposal.warehouse?.address || proposal.warehouse?.name || '—'" />
              <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.details.created_by')" :value="proposal.user?.name || '—'" />
              <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.details.pricing_mode')" :value="proposal.use_matrix_price ? $t('gestlab.general.labels.vap_proposals.show.matrix') : $t('gestlab.general.labels.vap_proposals.show.parameter')" />
            </InfoPanel>
          </div>

          <div class="mt-7 grid gap-4 md:grid-cols-3">
            <TimelineItem
              :title="$t('gestlab.general.labels.vap_proposals.show.details.created_on')"
              :value="formatDate(proposal.created_at)"
              tone="green"
            />
            <TimelineItem
              v-if="proposal.expiry_date"
              :title="$t('gestlab.general.labels.vap_proposals.show.details.expires_on')"
              :value="`${formatDate(proposal.expiry_date)} (${proposal.days_until_expiry} ${$t('gestlab.general.labels.vap_proposals.show.details.days_left')})`"
              :tone="proposal.days_until_expiry <= 3 ? 'red' : 'gold'"
            />
            <TimelineItem
              v-if="proposal.tolerance_days"
              :title="$t('gestlab.general.labels.vap_proposals.show.details.tolerance_days')"
              :value="`${proposal.tolerance_days} ${$t('gestlab.general.labels.vap_proposals.show.details.days')}`"
              tone="neutral"
            />
          </div>

          <div v-if="proposal.obs" class="mt-7 rounded-[24px] border border-[#ded2bb] bg-[#fbfaf6] p-5 dark:border-white/10 dark:bg-white/5">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-[#78847c] dark:text-slate-400">
              {{ $t('gestlab.general.labels.vap_proposals.show.details.observations') }}
            </p>
            <p class="mt-3 whitespace-pre-wrap text-sm font-medium leading-7 text-[#33413a] dark:text-slate-300">{{ proposal.obs }}</p>
          </div>
        </section>

        <section class="overflow-hidden rounded-[30px] border border-[#ded2bb] bg-white/90 shadow-[0_22px_70px_-46px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90">
          <div class="flex flex-col gap-4 border-b border-[#ded2bb] px-6 py-6 dark:border-white/10 sm:flex-row sm:items-end sm:justify-between sm:px-7">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.28em] text-[#c79a43]">Escopo comercial</p>
              <h2 class="mt-2 flex items-center gap-2 text-2xl font-black tracking-[-0.03em] text-[#10221d] dark:text-white">
                <ListBulletIcon class="h-6 w-6 text-[#143d37] dark:text-emerald-100" />
                {{ $t('gestlab.general.labels.vap_proposals.show.items.title') }}
              </h2>
              <p class="mt-1 text-sm font-medium text-[#78847c] dark:text-slate-400">
                {{ proposalItems.length }} {{ $t('gestlab.general.labels.vap_proposals.items') }}
              </p>
            </div>
            <div class="rounded-[20px] bg-[#143d37] px-5 py-3 text-right text-white shadow-[0_18px_42px_-26px_rgba(20,61,55,0.75)]">
              <p class="text-xs font-bold uppercase tracking-[0.18em] text-white/70">{{ $t('gestlab.general.labels.vap_proposals.show.items.grand_total') }}</p>
              <p class="mt-1 text-2xl font-black">{{ formatCurrency(proposal.total) }}</p>
            </div>
          </div>

          <div class="divide-y divide-[#ebe1cf] dark:divide-white/10">
            <article
              v-for="(item, index) in proposalItems"
              :key="item.id || index"
              class="grid gap-5 px-6 py-6 transition hover:bg-[#fbfaf6] dark:hover:bg-white/5 sm:px-7 lg:grid-cols-[minmax(0,1fr)_14rem]"
            >
              <div class="flex gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-[18px] bg-[#f7f1e6] text-sm font-black text-[#143d37] ring-1 ring-[#ded2bb] dark:bg-white/10 dark:text-emerald-100 dark:ring-white/10">
                  {{ index + 1 }}
                </div>
                <div class="min-w-0 flex-1">
                  <h3 class="text-base font-black text-[#10221d] dark:text-white">{{ item.item_description }}</h3>
                  <div class="mt-3 flex flex-wrap gap-2">
                    <span class="rounded-full bg-[#f7f1e6] px-3 py-1 text-xs font-bold text-[#59665f] dark:bg-white/10 dark:text-slate-300">
                      {{ $t('gestlab.general.labels.vap_proposals.show.items.quantity') }}: {{ item.qty }} {{ item.unit?.code || item.unit?.symbol || '' }}
                    </span>
                    <span v-if="item.standard" class="rounded-full bg-[#f7f1e6] px-3 py-1 text-xs font-bold text-[#59665f] dark:bg-white/10 dark:text-slate-300">
                      {{ $t('gestlab.general.labels.vap_proposals.show.items.standard') }}: {{ item.standard.code || item.standard.name || item.standard.description }}
                    </span>
                    <span v-if="item.tax_percentage > 0" class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-800 dark:bg-amber-400/10 dark:text-amber-200">
                      {{ $t('gestlab.general.labels.vap_proposals.show.items.tax') }}: {{ item.tax_percentage }}%
                    </span>
                    <span v-if="item.itemable_type" class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-400/10 dark:text-emerald-200">
                      {{ getItemableTypeLabel(item.itemable_type) }} #{{ item.itemable_id }}
                    </span>
                  </div>

                  <div v-if="item.discount_amount > 0 || item.discount_percentage > 0" class="mt-3 text-sm font-semibold text-emerald-700 dark:text-emerald-300">
                    {{ $t('gestlab.general.labels.vap_proposals.show.items.discount') }}:
                    <span v-if="item.discount_id === 1">{{ item.discount_percentage }}% (-{{ formatCurrency(item.discount_amount) }})</span>
                    <span v-else>-{{ formatCurrency(item.discount_amount) }}</span>
                  </div>

                  <div v-if="item.exemption_code" class="mt-2 text-sm font-semibold text-[#59665f] dark:text-slate-300">
                    {{ $t('gestlab.general.labels.vap_proposals.show.items.exemption') }}: {{ item.exemption_code }}
                  </div>

                  <div v-if="item.obs" class="mt-4 rounded-[18px] border border-[#ded2bb] bg-[#fbfaf6] p-4 text-sm font-medium leading-6 text-[#59665f] dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                    {{ item.obs }}
                  </div>
                </div>
              </div>

              <div class="rounded-[22px] border border-[#ded2bb] bg-[#fbfaf6] p-4 dark:border-white/10 dark:bg-white/5">
                <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.items.unit_price')" :value="formatCurrency(item.unit_price)" />
                <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.items.total')" :value="formatCurrency(item.total)" strong />
                <DefinitionRow v-if="item.tax_amount > 0" :label="$t('gestlab.general.labels.vap_proposals.show.items.tax')" :value="`+${formatCurrency(item.tax_amount)}`" />
                <DefinitionRow v-if="item.charge_tax !== null" :label="$t('gestlab.general.labels.vap_proposals.show.items.charge_tax')" :value="item.charge_tax ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no')" />
              </div>
            </article>
          </div>

          <div class="border-t border-[#ded2bb] bg-[#fbfaf6] px-6 py-6 dark:border-white/10 dark:bg-white/5 sm:px-7">
            <div class="ml-auto max-w-md space-y-3">
              <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.items.subtotal')" :value="formatCurrency(proposal.sub_total)" />
              <DefinitionRow v-if="proposal.discount > 0" :label="$t('gestlab.general.labels.vap_proposals.show.items.total_discount')" :value="`-${formatCurrency(proposal.discount)}`" />
              <DefinitionRow v-if="proposal.tax > 0" :label="$t('gestlab.general.labels.vap_proposals.show.items.total_tax')" :value="formatCurrency(proposal.tax)" />
              <DefinitionRow v-if="proposal.global_discount_amount > 0" :label="$t('gestlab.general.labels.vap_proposals.show.items.global_discount')" :value="`-${formatCurrency(proposal.global_discount_amount)}`" />
              <DefinitionRow v-if="proposal.withholding_tax_amount > 0" :label="$t('gestlab.general.labels.vap_proposals.show.items.withholding_tax')" :value="formatCurrency(proposal.withholding_tax_amount)" />
              <div class="flex items-center justify-between border-t border-[#ded2bb] pt-4 dark:border-white/10">
                <span class="text-base font-black text-[#10221d] dark:text-white">{{ $t('gestlab.general.labels.vap_proposals.show.items.grand_total') }}</span>
                <span class="text-2xl font-black text-[#143d37] dark:text-emerald-100">{{ formatCurrency(proposal.total) }}</span>
              </div>
            </div>
          </div>
        </section>

        <section v-if="proposal.compliance_agreement" class="rounded-[30px] border border-[#ded2bb] bg-white/90 p-6 shadow-[0_22px_70px_-46px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90 sm:p-7">
          <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.28em] text-[#c79a43]">ISO 17025</p>
              <h2 class="mt-2 flex items-center gap-2 text-2xl font-black tracking-[-0.03em] text-[#10221d] dark:text-white">
                <ShieldCheckIcon class="h-6 w-6 text-[#143d37] dark:text-emerald-100" />
                {{ $t('gestlab.general.labels.vap_proposals.show.compliance.title') }}
              </h2>
            </div>
            <span v-if="proposal.compliance_agreement.acknowledged_at" class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-emerald-800 dark:bg-emerald-400/10 dark:text-emerald-200">
              <CheckCircleIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.vap_proposals.show.compliance.signed') }}
            </span>
          </div>

          <div v-if="proposal.compliance_agreement.acknowledged_at" class="mt-6 rounded-[24px] border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-400/20 dark:bg-emerald-400/10">
            <p class="text-sm font-black text-emerald-900 dark:text-emerald-100">
              {{ $t('gestlab.general.labels.vap_proposals.show.compliance.signed_on') }} {{ formatDateTime(proposal.compliance_agreement.acknowledged_at) }}
            </p>
            <p class="mt-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300">IP: {{ proposal.compliance_agreement.client_ip || '—' }}</p>
          </div>

          <div class="mt-6 grid gap-3 md:grid-cols-3">
            <ComplianceCheck :checked="proposal.compliance_agreement.confidentiality" :label="$t('gestlab.general.labels.vap_proposals.show.compliance.confidentiality')" />
            <ComplianceCheck :checked="proposal.compliance_agreement.impartiality" :label="$t('gestlab.general.labels.vap_proposals.show.compliance.impartiality')" />
            <ComplianceCheck :checked="proposal.compliance_agreement.nondisclosure" :label="$t('gestlab.general.labels.vap_proposals.show.compliance.nondisclosure')" />
          </div>
        </section>

        <section v-if="revisions.length > 0" class="rounded-[30px] border border-[#ded2bb] bg-white/90 p-6 shadow-[0_22px_70px_-46px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90 sm:p-7">
          <p class="text-xs font-black uppercase tracking-[0.28em] text-[#c79a43]">Histórico</p>
          <h2 class="mt-2 flex items-center gap-2 text-2xl font-black tracking-[-0.03em] text-[#10221d] dark:text-white">
            <ClockIcon class="h-6 w-6 text-[#143d37] dark:text-emerald-100" />
            {{ $t('gestlab.general.labels.vap_proposals.show.revisions.title') }}
            <span class="text-sm font-bold text-[#78847c] dark:text-slate-400">({{ revisions.length }} {{ $t('gestlab.general.labels.vap_proposals.changes') }})</span>
          </h2>

          <div class="mt-6 space-y-5">
            <article
              v-for="revision in revisions"
              :key="revision.id"
              class="rounded-[24px] border border-[#ded2bb] bg-[#fbfaf6] p-5 dark:border-white/10 dark:bg-white/5"
            >
              <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2">
                  <UserIcon class="h-4 w-4 text-[#c79a43]" />
                  <span class="text-sm font-black text-[#10221d] dark:text-white">{{ revision.causer?.name || 'Sistema' }}</span>
                </div>
                <span class="text-xs font-bold text-[#78847c] dark:text-slate-400">{{ formatDateTime(revision.created_at) }}</span>
              </div>
              <p class="mt-3 text-sm font-medium leading-6 text-[#59665f] dark:text-slate-300">{{ revision.description }}</p>
              <div v-if="revision.properties?.reason" class="mt-3 rounded-[18px] bg-white p-3 text-xs font-semibold text-[#59665f] dark:bg-slate-950 dark:text-slate-300">
                <strong>{{ $t('gestlab.general.labels.vap_proposals.show.revisions.reason') }}:</strong> {{ revision.properties.reason }}
              </div>
              <div v-if="revision.event === 'revised' && revision.properties?.old_values" class="mt-4 grid gap-3 text-xs md:grid-cols-2">
                <div v-if="revision.properties.old_values.total !== revision.properties.new_values.total" class="rounded-[18px] bg-white p-3 dark:bg-slate-950">
                  <span class="font-bold text-[#78847c] dark:text-slate-400">Total</span>
                  <div class="mt-2 flex items-center gap-2">
                    <span class="font-bold text-red-600 line-through dark:text-red-300">{{ formatCurrency(revision.properties.old_values.total) }}</span>
                    <ArrowRightIcon class="h-3 w-3 text-[#78847c]" />
                    <span class="font-bold text-emerald-700 dark:text-emerald-300">{{ formatCurrency(revision.properties.new_values.total) }}</span>
                  </div>
                </div>
                <div v-if="revision.properties.old_values.items_count !== revision.properties.new_values.items_count" class="rounded-[18px] bg-white p-3 dark:bg-slate-950">
                  <span class="font-bold text-[#78847c] dark:text-slate-400">{{ $t('gestlab.general.labels.vap_proposals.items') }}</span>
                  <div class="mt-2 flex items-center gap-2">
                    <span class="font-bold text-red-600 line-through dark:text-red-300">{{ revision.properties.old_values.items_count }}</span>
                    <ArrowRightIcon class="h-3 w-3 text-[#78847c]" />
                    <span class="font-bold text-emerald-700 dark:text-emerald-300">{{ revision.properties.new_values.items_count }}</span>
                  </div>
                </div>
              </div>
            </article>
          </div>
        </section>
      </main>

      <aside class="space-y-6 xl:sticky xl:top-6 xl:self-start">
        <section class="rounded-[30px] border border-[#ded2bb] bg-white/90 p-6 shadow-[0_22px_70px_-46px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90">
          <h3 class="text-lg font-black tracking-[-0.02em] text-[#10221d] dark:text-white">
            {{ $t('gestlab.general.labels.vap_proposals.show.actions.title') }}
          </h3>
          <div class="mt-5 space-y-3">
            <button
              type="button"
              @click="generatePdf"
              class="inline-flex w-full items-center justify-center gap-2 rounded-[20px] bg-[#143d37] px-4 py-3 text-sm font-black text-white transition hover:bg-[#0f302b] focus:outline-none focus:ring-2 focus:ring-[#c79a43] focus:ring-offset-2 dark:ring-offset-slate-950"
            >
              <DocumentArrowDownIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_proposals.show.actions.generate_pdf') }}
            </button>
            <a
              :href="route('vap-proposals.public.show', proposal.unique_hash)"
              target="_blank"
              class="inline-flex w-full items-center justify-center gap-2 rounded-[20px] border border-[#ded2bb] bg-[#fbfaf6] px-4 py-3 text-sm font-black text-[#143d37] transition hover:border-[#c79a43] hover:bg-[#fff7e5] dark:border-white/10 dark:bg-white/5 dark:text-emerald-100 dark:hover:bg-white/10"
            >
              <LinkIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_proposals.show.actions.view_public_link') }}
            </a>
          </div>

          <div class="mt-6 border-t border-[#ded2bb] pt-5 dark:border-white/10">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-[#78847c] dark:text-slate-400">{{ $t('gestlab.general.labels.vap_proposals.show.actions.share') }}</p>
            <div class="mt-3 flex gap-2">
              <input
                :value="publicLink"
                readonly
                class="min-w-0 flex-1 rounded-[18px] border border-[#ded2bb] bg-[#fbfaf6] px-3 py-2 text-sm font-semibold text-[#33413a] outline-none focus:border-[#c79a43] focus:ring-2 focus:ring-[#c79a43]/30 dark:border-white/10 dark:bg-white/5 dark:text-slate-100"
              />
              <button
                type="button"
                @click="copyToClipboard"
                class="rounded-[18px] bg-[#f7f1e6] px-3 py-2 text-sm font-black text-[#143d37] transition hover:bg-[#efe3cf] dark:bg-white/10 dark:text-emerald-100 dark:hover:bg-white/15"
              >
                {{ copied ? $t('gestlab.general.labels.vap_proposals.show.copied') : $t('gestlab.general.labels.vap_proposals.show.copy') }}
              </button>
            </div>
          </div>
        </section>

        <section class="rounded-[30px] border border-[#ded2bb] bg-white/90 p-6 shadow-[0_22px_70px_-46px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90">
          <h3 class="flex items-center gap-2 text-lg font-black tracking-[-0.02em] text-[#10221d] dark:text-white">
            <Cog6ToothIcon class="h-5 w-5 text-[#c79a43]" />
            {{ $t('gestlab.general.labels.vap_proposals.show.status.title') }}
          </h3>
          <div class="mt-5 space-y-3">
            <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.status.current')" :value="proposal.status_badge?.text || proposal.status" strong />
            <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.status.is_original')" :value="proposal.is_original ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no')" />
            <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.status.use_matrix_price')" :value="proposal.use_matrix_price ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no')" />
            <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.status.tolerance_days')" :value="`${proposal.tolerance_days} ${$t('gestlab.general.labels.vap_proposals.show.days')}`" />
            <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.status.converted')" :value="proposal.converted_to_invoice ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no')" />
          </div>
        </section>

        <section class="rounded-[30px] border border-[#ded2bb] bg-white/90 p-6 shadow-[0_22px_70px_-46px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90">
          <h3 class="flex items-center gap-2 text-lg font-black tracking-[-0.02em] text-[#10221d] dark:text-white">
            <CurrencyDollarIcon class="h-5 w-5 text-[#c79a43]" />
            {{ $t('gestlab.general.labels.vap_proposals.show.financial_summary.title') }}
          </h3>
          <div class="mt-5 space-y-3">
            <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.financial_summary.subtotal')" :value="formatCurrency(proposal.sub_total)" />
            <DefinitionRow v-if="proposal.discount > 0" :label="$t('gestlab.general.labels.vap_proposals.show.financial_summary.discount')" :value="`-${formatCurrency(proposal.discount)}`" />
            <DefinitionRow v-if="proposal.tax > 0" :label="$t('gestlab.general.labels.vap_proposals.show.financial_summary.tax')" :value="formatCurrency(proposal.tax)" />
            <div class="flex items-center justify-between border-t border-[#ded2bb] pt-4 dark:border-white/10">
              <span class="text-base font-black text-[#10221d] dark:text-white">{{ $t('gestlab.general.labels.vap_proposals.show.financial_summary.total') }}</span>
              <span class="text-xl font-black text-[#143d37] dark:text-emerald-100">{{ formatCurrency(proposal.total) }}</span>
            </div>
          </div>
        </section>

        <section v-if="proposal.template" class="rounded-[30px] border border-[#ded2bb] bg-white/90 p-6 shadow-[0_22px_70px_-46px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90">
          <h3 class="flex items-center gap-2 text-lg font-black tracking-[-0.02em] text-[#10221d] dark:text-white">
            <DocumentDuplicateIcon class="h-5 w-5 text-[#c79a43]" />
            {{ $t('gestlab.general.labels.vap_proposals.show.template.title') }}
          </h3>
          <div class="mt-5 rounded-[22px] border border-[#ded2bb] bg-[#fbfaf6] p-4 dark:border-white/10 dark:bg-white/5">
            <h4 class="text-sm font-black text-[#10221d] dark:text-white">{{ proposal.template.name }}</h4>
            <p class="mt-1 text-xs font-semibold text-[#78847c] dark:text-slate-400">
              {{ $t('gestlab.general.labels.vap_proposals.show.template.created_by') }} {{ proposal.template?.user?.name || '—' }}
            </p>
            <p class="mt-4 line-clamp-4 text-sm font-medium leading-6 text-[#59665f] dark:text-slate-300">{{ resolvedTemplateSummary }}</p>
            <button
              type="button"
              @click="showTemplatePreview = true"
              class="mt-4 text-sm font-black text-[#143d37] underline decoration-[#c79a43]/60 underline-offset-4 hover:text-[#0f302b] dark:text-emerald-100"
            >
              {{ $t('gestlab.general.labels.vap_proposals.show.view_full_template') }}
            </button>
          </div>
        </section>
      </aside>
    </div>

    <ConfirmationModal :show="showSendModal" @close="showSendModal = false" @confirm="confirmSend">
      <template #title>
        {{ $t('gestlab.general.labels.vap_proposals.show.send_modal.title') }}
      </template>
      <template #content>
        <div class="space-y-4 text-sm font-medium text-[#59665f] dark:text-slate-300">
          <p>{{ $t('gestlab.general.labels.vap_proposals.show.send_modal.message') }}</p>
          <label class="flex items-center gap-3 rounded-[18px] border border-[#ded2bb] bg-[#fbfaf6] p-3 dark:border-white/10 dark:bg-white/5">
            <input v-model="sendOptions.generatePdf" type="checkbox" class="h-4 w-4 rounded border-[#ded2bb] text-[#143d37] focus:ring-[#c79a43]" />
            <span>{{ $t('gestlab.general.labels.vap_proposals.show.send_modal.generate_pdf') }}</span>
          </label>
          <label class="flex items-center gap-3 rounded-[18px] border border-[#ded2bb] bg-[#fbfaf6] p-3 dark:border-white/10 dark:bg-white/5">
            <input v-model="sendOptions.sendEmail" type="checkbox" class="h-4 w-4 rounded border-[#ded2bb] text-[#143d37] focus:ring-[#c79a43]" />
            <span>{{ $t('gestlab.general.labels.vap_proposals.show.send_modal.send_email') }}</span>
          </label>
        </div>
      </template>
    </ConfirmationModal>

    <Modal :show="showTemplatePreview" @close="showTemplatePreview = false" max-width="4xl">
      <div class="bg-[#fbfaf6] p-6 text-[#18241f] dark:bg-slate-950 dark:text-slate-100">
        <div class="mb-6 flex items-start justify-between gap-4">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.24em] text-[#c79a43]">Pré-visualização resolvida</p>
            <h2 class="mt-2 text-xl font-black tracking-[-0.02em] text-[#10221d] dark:text-white">{{ proposal.template?.name }}</h2>
            <p class="mt-2 text-sm font-semibold text-[#78847c] dark:text-slate-400">
              Conteúdo com variáveis, dados bancários, escopo e assinaturas aplicados a esta proposta.
            </p>
          </div>
          <button type="button" @click="showTemplatePreview = false" class="rounded-full p-2 text-[#78847c] transition hover:bg-white hover:text-[#143d37] dark:hover:bg-white/10 dark:hover:text-white">
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>
        <div class="max-h-[65vh] overflow-y-auto rounded-[26px] border border-[#ded2bb] bg-white p-6 text-[#33413a] dark:border-white/10 dark:bg-white/5 dark:text-slate-200">
          <div class="prose max-w-none dark:prose-invert" v-html="resolvedTemplateContent"></div>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { computed, defineComponent, h, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import {
  ArrowDownTrayIcon,
  ArrowRightIcon,
  CalendarIcon,
  CheckCircleIcon,
  ClockIcon,
  Cog6ToothIcon,
  CurrencyDollarIcon,
  DocumentArrowDownIcon,
  DocumentDuplicateIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  LinkIcon,
  ListBulletIcon,
  PaperAirplaneIcon,
  PencilSquareIcon,
  ShieldCheckIcon,
  UserIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import ConfirmationModal from '@/Components/dialog-modal.vue'
import Modal from '@/Components/modal.vue'
import { commercialDocumentThemeClasses } from '@/Composables/useCommercialDocumentTheme'

const props = defineProps({
  proposal: {
    type: Object,
    required: true,
  },
  revisions: {
    type: Array,
    default: () => [],
  },
  canSend: {
    type: Boolean,
    default: false,
  },
  canRevise: {
    type: Boolean,
    default: false,
  },
  parsedTemplateContent: {
    type: String,
    default: '',
  },
})

const showSendModal = ref(false)
const showTemplatePreview = ref(false)
const sendOptions = ref({
  generatePdf: true,
  sendEmail: true,
})
const copied = ref(false)

const proposalItems = computed(() => props.proposal.items || [])
const resolvedTemplateContent = computed(() => props.parsedTemplateContent || props.proposal.template?.content || '')
const resolvedTemplateSummary = computed(() => stripHtml(resolvedTemplateContent.value) || 'Modelo sem conteúdo configurado.')

const publicLink = computed(() => route('vap-proposals.public.show', props.proposal.unique_hash))

const statusBadgeClasses = computed(() => {
  const classes = {
    PENDING: 'bg-amber-50 text-amber-800 ring-1 ring-amber-200 dark:bg-amber-400/10 dark:text-amber-200 dark:ring-amber-300/20',
    SENT: 'bg-[#f7f1e6] text-[#143d37] ring-1 ring-[#ded2bb] dark:bg-white/10 dark:text-emerald-100 dark:ring-white/10',
    VIEWED: 'bg-cyan-50 text-cyan-800 ring-1 ring-cyan-200 dark:bg-cyan-400/10 dark:text-cyan-200 dark:ring-cyan-300/20',
    ACCEPTED: 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-400/10 dark:text-emerald-200 dark:ring-emerald-300/20',
    REJECTED: 'bg-red-50 text-red-800 ring-1 ring-red-200 dark:bg-red-400/10 dark:text-red-200 dark:ring-red-300/20',
    REVISED: 'bg-orange-50 text-orange-800 ring-1 ring-orange-200 dark:bg-orange-400/10 dark:text-orange-200 dark:ring-orange-300/20',
    EXPIRED: 'bg-slate-100 text-slate-700 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-white/10',
  }

  return classes[props.proposal.status] || classes.PENDING
})

const DefinitionRow = defineComponent({
  name: 'ProposalDefinitionRow',
  props: {
    label: {
      type: String,
      required: true,
    },
    value: {
      type: [String, Number, Boolean],
      default: '—',
    },
    strong: {
      type: Boolean,
      default: false,
    },
  },
  setup(rowProps) {
    return () => h('div', { class: 'flex items-start justify-between gap-4 py-1.5' }, [
      h('span', { class: 'text-sm font-semibold text-[#78847c] dark:text-slate-400' }, rowProps.label),
      h('span', {
        class: [
          'text-right text-sm text-[#33413a] dark:text-slate-200',
          rowProps.strong ? 'font-black' : 'font-semibold',
        ],
      }, rowProps.value || '—'),
    ])
  },
})

const InfoPanel = defineComponent({
  name: 'ProposalInfoPanel',
  props: {
    title: {
      type: String,
      required: true,
    },
    icon: {
      type: String,
      default: 'client',
    },
  },
  setup(panelProps, { slots }) {
    const Icon = panelProps.icon === 'lab' ? ShieldCheckIcon : UserIcon

    return () => h('div', { class: 'rounded-[26px] border border-[#ded2bb] bg-[#fbfaf6] p-5 dark:border-white/10 dark:bg-white/5' }, [
      h('div', { class: 'mb-4 flex items-center gap-3' }, [
        h('span', { class: 'flex h-10 w-10 items-center justify-center rounded-[16px] bg-[#143d37] text-white shadow-[0_16px_34px_-22px_rgba(20,61,55,0.75)]' }, [
          h(Icon, { class: 'h-5 w-5 text-[#e7c16b]' }),
        ]),
        h('h3', { class: 'text-sm font-black uppercase tracking-[0.18em] text-[#10221d] dark:text-white' }, panelProps.title),
      ]),
      h('div', { class: 'space-y-1' }, slots.default?.()),
    ])
  },
})

const TimelineItem = defineComponent({
  name: 'ProposalTimelineItem',
  props: {
    title: {
      type: String,
      required: true,
    },
    value: {
      type: String,
      required: true,
    },
    tone: {
      type: String,
      default: 'neutral',
    },
  },
  setup(itemProps) {
    const toneClasses = {
      green: 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-400/10 dark:text-emerald-200 dark:ring-emerald-300/20',
      gold: 'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-400/10 dark:text-amber-200 dark:ring-amber-300/20',
      red: 'bg-red-50 text-red-800 ring-red-200 dark:bg-red-400/10 dark:text-red-200 dark:ring-red-300/20',
      neutral: 'bg-[#f7f1e6] text-[#143d37] ring-[#ded2bb] dark:bg-white/10 dark:text-emerald-100 dark:ring-white/10',
    }

    return () => h('div', { class: ['rounded-[24px] p-4 ring-1', toneClasses[itemProps.tone] || toneClasses.neutral] }, [
      h('div', { class: 'mb-3 flex h-9 w-9 items-center justify-center rounded-full bg-white/70 dark:bg-white/10' }, [
        h(itemProps.tone === 'green' ? CalendarIcon : ClockIcon, { class: 'h-5 w-5' }),
      ]),
      h('p', { class: 'text-xs font-black uppercase tracking-[0.18em] opacity-75' }, itemProps.title),
      h('p', { class: 'mt-2 text-sm font-black' }, itemProps.value),
    ])
  },
})

const ComplianceCheck = defineComponent({
  name: 'ProposalComplianceCheck',
  props: {
    checked: {
      type: Boolean,
      default: false,
    },
    label: {
      type: String,
      required: true,
    },
  },
  setup(checkProps) {
    return () => h('div', {
      class: [
        'flex items-center gap-3 rounded-[22px] border p-4',
        checkProps.checked
          ? 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-300/20 dark:bg-emerald-400/10 dark:text-emerald-100'
          : 'border-[#ded2bb] bg-[#fbfaf6] text-[#59665f] dark:border-white/10 dark:bg-white/5 dark:text-slate-300',
      ],
    }, [
      h('span', {
        class: [
          'flex h-8 w-8 shrink-0 items-center justify-center rounded-full',
          checkProps.checked ? 'bg-emerald-600 text-white' : 'bg-[#f0e7d6] text-[#78847c] dark:bg-white/10 dark:text-slate-400',
        ],
      }, [
        h(CheckCircleIcon, { class: 'h-5 w-5' }),
      ]),
      h('span', { class: 'text-sm font-black leading-5' }, checkProps.label),
    ])
  },
})

const formatDate = (date) => {
  if (!date) {
    return '—'
  }

  return new Intl.DateTimeFormat('pt-AO', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(new Date(date))
}

const formatDateTime = (date) => {
  if (!date) {
    return '—'
  }

  return new Intl.DateTimeFormat('pt-AO', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(date))
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('pt-AO', {
    style: 'currency',
    currency: 'AOA',
  }).format(Number(amount || 0))
}

const stripHtml = (html) => {
  if (!html) {
    return ''
  }

  const text = html.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim()

  return text.length > 220 ? `${text.substring(0, 220)}...` : text
}

const getItemableTypeLabel = (itemableType) => {
  if (!itemableType) {
    return '—'
  }

  if (itemableType.includes('Matrix')) {
    return trans('gestlab.general.labels.vap_proposals.show.matrix')
  }

  if (itemableType.includes('Parameter')) {
    return trans('gestlab.general.labels.vap_proposals.show.parameter')
  }

  return itemableType.split('\\').pop()
}

const sendProposal = () => {
  showSendModal.value = true
}

const confirmSend = () => {
  router.post(route('vap-proposals.send', props.proposal.id), {
    options: sendOptions.value,
  }, {
    onSuccess: () => {
      showSendModal.value = false
    },
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
  } catch {
    copied.value = false
  }
}
</script>
