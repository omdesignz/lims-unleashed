<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="grid gap-5 border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end lg:p-6">
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-chip">
              <DocumentTextIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.vap_proposals.show.commercial_proposal') }}
            </span>
            <span :class="statusBadgeClasses">{{ proposal.status_badge?.text || proposal.status }}</span>
            <span v-if="!proposal.is_original" class="ds-badge ds-badge-warning gap-1">
              <ExclamationTriangleIcon class="h-3.5 w-3.5" />
              {{ $t('gestlab.general.labels.vap_proposals.show.revision') }}
            </span>
          </div>
          <h1 class="ds-heading mt-3 break-words font-mono text-2xl sm:text-3xl">{{ proposal.proposal_number }}</h1>
          <p class="ds-copy mt-1 text-sm">
            {{ $t('gestlab.general.labels.vap_proposals.show.description') }}
            <span class="font-bold text-[var(--ds-text)]">{{ proposal.customer?.name || $t('gestlab.general.labels.vap_proposals.show.unidentified_customer') }}</span>
          </p>
        </div>

        <dl class="grid grid-cols-3 divide-x divide-[var(--ds-border)] overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)]">
          <div class="min-w-0 px-3 py-2.5 sm:px-4">
            <dt class="text-[0.65rem] font-bold text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_proposals.surface.total') }}</dt>
            <dd class="mt-1 truncate text-sm font-bold text-[var(--ds-text)] sm:text-base" :title="formatCurrency(proposal.total)">{{ formatCurrency(proposal.total) }}</dd>
          </div>
          <div class="min-w-0 px-3 py-2.5 sm:px-4">
            <dt class="text-[0.65rem] font-bold text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_proposals.surface.expiry') }}</dt>
            <dd class="mt-1 text-sm font-bold text-[var(--ds-text)] sm:text-base">{{ proposal.days_until_expiry ?? proposal.tolerance_days }}d</dd>
          </div>
          <div class="min-w-0 px-3 py-2.5 sm:px-4">
            <dt class="text-[0.65rem] font-bold text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_proposals.surface.items') }}</dt>
            <dd class="mt-1 text-sm font-bold text-[var(--ds-text)] sm:text-base">{{ proposalItems.length }}</dd>
          </div>
        </dl>
      </div>

      <div class="flex flex-wrap gap-2 px-5 py-4 lg:px-6">
        <a v-if="proposal.file_path" :href="route('vap-proposals.download.pdf', proposal.id)" class="ds-button ds-button-primary">
          <ArrowDownTrayIcon class="h-4 w-4" />
          {{ $t('gestlab.general.labels.vap_proposals.show.download_pdf') }}
        </a>
        <button v-if="canSend" type="button" class="ds-button ds-button-primary" @click="sendProposal">
          <PaperAirplaneIcon class="h-4 w-4" />
          {{ $t('gestlab.general.labels.vap_proposals.show.send_to_client') }}
        </button>
        <Link v-if="canRevise" :href="route('vap-proposals.edit', proposal.id)" class="ds-button ds-button-secondary">
          <PencilSquareIcon class="h-4 w-4" />
          {{ $t('gestlab.general.labels.vap_proposals.show.revise') }}
        </Link>
        <button type="button" class="ds-button ds-button-secondary" @click="generatePdf">
          <DocumentArrowDownIcon class="h-4 w-4" />
          {{ $t('gestlab.general.labels.vap_proposals.show.actions.generate_pdf') }}
        </button>
      </div>
    </section>

    <section v-if="laboratoryDossier" class="ds-panel overflow-hidden">
      <div class="grid gap-6 border-b border-[var(--ds-border)] px-5 py-5 lg:grid-cols-[minmax(0,1fr)_19rem] lg:items-start lg:px-6">
        <div>
          <div class="flex flex-wrap items-center gap-2">
            <p class="ds-kicker">Execução laboratorial</p>
            <span class="inline-flex border border-primary-300 bg-primary-50 px-2.5 py-1 text-xs font-bold text-primary-900 dark:border-primary-400/30 dark:bg-primary-500/10 dark:text-primary-100">
              {{ laboratoryDossier.stage.label }}
            </span>
          </div>
          <h2 class="ds-heading mt-2 text-lg">Do aceite ao boletim</h2>
          <p class="ds-copy mt-2 max-w-3xl text-sm">{{ laboratoryDossier.stage.description }}</p>
        </div>

        <div class="border-l-4 border-primary-500 bg-[var(--ds-panel-subtle)] p-4">
          <p class="text-xs font-bold text-[var(--ds-text)]">Próxima acção</p>
          <p class="mt-1 text-xs leading-5 text-[var(--ds-text-muted)]">{{ laboratoryDossier.primary_action.description }}</p>
          <button v-if="laboratoryDossier.primary_action.disabled" type="button" class="ds-button ds-button-secondary mt-4 w-full" disabled>
            {{ laboratoryDossier.primary_action.label }}
          </button>
          <Link
            v-else-if="laboratoryDossier.primary_action.method === 'post'"
            :href="laboratoryDossier.primary_action.url"
            method="post"
            as="button"
            class="ds-button ds-button-primary mt-4 w-full"
          >
            {{ laboratoryDossier.primary_action.label }}
            <ArrowRightIcon class="h-4 w-4" />
          </Link>
          <Link v-else :href="laboratoryDossier.primary_action.url" class="ds-button ds-button-primary mt-4 w-full">
            {{ laboratoryDossier.primary_action.label }}
            <ArrowRightIcon class="h-4 w-4" />
          </Link>
        </div>
      </div>

      <div class="grid gap-6 p-5 lg:grid-cols-[minmax(0,1fr)_17rem] lg:p-6">
        <div>
          <ol class="grid grid-cols-6 gap-1" aria-label="Etapas do dossier laboratorial">
            <li v-for="(step, index) in laboratoryDossier.steps" :key="step.key" class="min-w-0 text-center">
              <div class="flex items-center">
                <span v-if="index > 0" :class="['h-px flex-1', step.status === 'pending' ? 'bg-[var(--ds-border)]' : 'bg-emerald-500']" />
                <span
                  :class="[
                    'flex h-8 w-8 shrink-0 items-center justify-center border text-xs font-bold',
                    step.status === 'complete'
                      ? 'border-emerald-600 bg-emerald-600 text-white'
                      : step.status === 'current'
                        ? 'border-primary-600 bg-primary-50 text-primary-800 ring-2 ring-primary-200 dark:bg-primary-500/10 dark:text-primary-100 dark:ring-primary-500/20'
                        : 'border-[var(--ds-border-strong)] bg-[var(--ds-panel)] text-[var(--ds-text-soft)]'
                  ]"
                >
                  <CheckCircleIcon v-if="step.status === 'complete'" class="h-5 w-5" />
                  <span v-else>{{ index + 1 }}</span>
                </span>
                <span v-if="index < laboratoryDossier.steps.length - 1" :class="['h-px flex-1', step.status === 'complete' ? 'bg-emerald-500' : 'bg-[var(--ds-border)]']" />
              </div>
              <span class="mt-2 block truncate text-[10px] font-bold text-[var(--ds-text-soft)]" :title="step.label">{{ step.label }}</span>
            </li>
          </ol>

          <div v-if="laboratoryDossier.samples.length" class="mt-6 border border-[var(--ds-border)] text-xs">
            <div class="hidden grid-cols-[minmax(12rem,1.2fr)_9rem_minmax(11rem,1fr)_4rem] bg-[var(--ds-panel-subtle)] px-4 py-3 font-bold text-[var(--ds-text-soft)] md:grid">
              <span>Amostra</span>
              <span>Código laboratorial</span>
              <span>Produto</span>
              <span class="text-right">Acção</span>
            </div>
            <div class="divide-y divide-[var(--ds-border)]">
              <div v-for="sample in laboratoryDossier.samples" :key="sample.id" class="grid gap-3 px-4 py-3 md:grid-cols-[minmax(12rem,1.2fr)_9rem_minmax(11rem,1fr)_4rem] md:items-center">
                <div class="min-w-0">
                  <p class="font-mono font-bold text-[var(--ds-text)]">{{ sample.code || 'Por gerar' }}</p>
                  <p class="mt-1 truncate text-[var(--ds-text-muted)]" :title="sample.name">{{ sample.name }}</p>
                </div>
                <p class="font-mono font-bold text-[var(--ds-text)]"><span class="mr-2 font-sans text-[var(--ds-text-soft)] md:hidden">Código laboratorial</span>{{ sample.lab_code || 'Pendente' }}</p>
                <p class="truncate font-semibold text-[var(--ds-text-muted)]" :title="sample.product || 'Âmbito por definir'"><span class="mr-2 text-[var(--ds-text-soft)] md:hidden">Produto</span>{{ sample.product || 'Âmbito por definir' }}</p>
                <Link :href="sample.show_url" class="justify-self-start font-bold text-primary-800 hover:underline dark:text-primary-200 md:justify-self-end">Abrir</Link>
              </div>
            </div>
          </div>
        </div>

        <dl class="divide-y divide-[var(--ds-border)] border-y border-[var(--ds-border)]">
          <div class="flex items-center justify-between gap-3 py-3">
            <dt class="text-xs font-semibold text-[var(--ds-text-muted)]">Amostras</dt>
            <dd class="text-sm font-bold text-[var(--ds-text)]">{{ laboratoryDossier.counts.accessioned_samples }}/{{ laboratoryDossier.counts.samples }}</dd>
          </div>
          <div class="flex items-center justify-between gap-3 py-3">
            <dt class="text-xs font-semibold text-[var(--ds-text-muted)]">Análises</dt>
            <dd class="text-sm font-bold text-[var(--ds-text)]">{{ laboratoryDossier.counts.analyses }}</dd>
          </div>
          <div class="flex items-center justify-between gap-3 py-3">
            <dt class="text-xs font-semibold text-[var(--ds-text-muted)]">Resultados aprovados</dt>
            <dd class="text-sm font-bold text-[var(--ds-text)]">{{ laboratoryDossier.counts.approved_results }}/{{ laboratoryDossier.counts.results }}</dd>
          </div>
          <div class="flex items-center justify-between gap-3 py-3">
            <dt class="text-xs font-semibold text-[var(--ds-text-muted)]">Boletins validados</dt>
            <dd class="text-sm font-bold text-[var(--ds-text)]">{{ laboratoryDossier.counts.validated_reports }}/{{ laboratoryDossier.counts.reports }}</dd>
          </div>
        </dl>
      </div>
    </section>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
      <main class="min-w-0 space-y-6">
        <section class="ds-panel p-5 sm:p-6">
          <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
              <p class="ds-kicker">{{ $t('gestlab.general.labels.vap_proposals.show.base_information') }}</p>
              <h2 class="ds-heading mt-1 text-lg">
                {{ $t('gestlab.general.labels.vap_proposals.show.details.title') }}
              </h2>
            </div>
            <span v-if="proposal.use_matrix_price" class="ds-badge ds-badge-info gap-1 self-start">
              <Cog6ToothIcon class="h-3.5 w-3.5" />
              {{ $t('gestlab.general.labels.vap_proposals.show.matrix_pricing') }}
            </span>
          </div>

          <div class="mt-5 grid gap-4 lg:grid-cols-2">
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

          <div class="mt-5 grid gap-3 md:grid-cols-3">
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

          <div v-if="proposal.obs" class="mt-5 border-l-4 border-primary-500 bg-[var(--ds-panel-subtle)] p-4">
            <p class="text-xs font-bold text-[var(--ds-text-soft)]">
              {{ $t('gestlab.general.labels.vap_proposals.show.details.observations') }}
            </p>
            <p class="mt-2 whitespace-pre-wrap text-sm font-medium leading-6 text-[var(--ds-text-muted)]">{{ proposal.obs }}</p>
          </div>
        </section>

        <section class="ds-table-shell">
          <div class="ds-table-summary flex-col px-5 py-4 sm:flex-row sm:items-end sm:px-6">
            <div>
              <p class="ds-kicker">{{ $t('gestlab.general.labels.vap_proposals.show.commercial_scope') }}</p>
              <h2 class="ds-heading mt-1 flex items-center gap-2 text-lg">
                <ListBulletIcon class="h-5 w-5 text-primary-700 dark:text-primary-200" />
                {{ $t('gestlab.general.labels.vap_proposals.show.items.title') }}
              </h2>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">
                {{ proposalItems.length }} {{ $t('gestlab.general.labels.vap_proposals.items') }}
              </p>
            </div>
            <div class="text-right">
              <p class="text-xs font-bold text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_proposals.show.items.grand_total') }}</p>
              <p class="mt-1 text-lg font-bold text-[var(--ds-text)]">{{ formatCurrency(proposal.total) }}</p>
            </div>
          </div>

          <div class="divide-y divide-[var(--ds-border)]">
            <article
              v-for="(item, index) in proposalItems"
              :key="item.id || index"
              class="grid gap-4 px-5 py-5 transition hover:bg-primary-50/60 dark:hover:bg-primary-400/5 sm:px-6 lg:grid-cols-[minmax(0,1fr)_14rem]"
            >
              <div class="flex gap-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-sm font-bold text-[var(--ds-text)]">
                  {{ index + 1 }}
                </div>
                <div class="min-w-0 flex-1">
                  <h3 class="text-sm font-bold text-[var(--ds-text)]">{{ item.item_description }}</h3>
                  <div class="mt-3 flex flex-wrap gap-2">
                    <span class="ds-badge ds-badge-neutral">
                      {{ $t('gestlab.general.labels.vap_proposals.show.items.quantity') }}: {{ item.qty }} {{ item.unit?.code || item.unit?.symbol || '' }}
                    </span>
                    <span v-if="item.standard" class="ds-badge ds-badge-neutral">
                      {{ $t('gestlab.general.labels.vap_proposals.show.items.standard') }}: {{ item.standard.code || item.standard.name || item.standard.description }}
                    </span>
                    <span v-if="item.tax_percentage > 0" class="ds-badge ds-badge-warning">
                      {{ $t('gestlab.general.labels.vap_proposals.show.items.tax') }}: {{ item.tax_percentage }}%
                    </span>
                    <span v-if="item.itemable_type" class="ds-badge ds-badge-success">
                      {{ getItemableTypeLabel(item.itemable_type) }} #{{ item.itemable_id }}
                    </span>
                  </div>

                  <div v-if="item.discount_amount > 0 || item.discount_percentage > 0" class="mt-3 text-sm font-semibold text-emerald-700 dark:text-emerald-300">
                    {{ $t('gestlab.general.labels.vap_proposals.show.items.discount') }}:
                    <span v-if="item.discount_id === 1">{{ item.discount_percentage }}% (-{{ formatCurrency(item.discount_amount) }})</span>
                    <span v-else>-{{ formatCurrency(item.discount_amount) }}</span>
                  </div>

                  <div v-if="item.exemption_code" class="mt-2 text-sm font-semibold text-[var(--ds-text-muted)]">
                    {{ $t('gestlab.general.labels.vap_proposals.show.items.exemption') }}: {{ item.exemption_code }}
                  </div>

                  <div v-if="item.obs" class="mt-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3 text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
                    {{ item.obs }}
                  </div>
                </div>
              </div>

              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
                <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.items.unit_price')" :value="formatCurrency(item.unit_price)" />
                <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.items.total')" :value="formatCurrency(item.total)" strong />
                <DefinitionRow v-if="item.tax_amount > 0" :label="$t('gestlab.general.labels.vap_proposals.show.items.tax')" :value="`+${formatCurrency(item.tax_amount)}`" />
                <DefinitionRow v-if="item.charge_tax !== null" :label="$t('gestlab.general.labels.vap_proposals.show.items.charge_tax')" :value="item.charge_tax ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no')" />
              </div>
            </article>
          </div>

          <div class="border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-5 sm:px-6">
            <div class="ml-auto max-w-md space-y-3">
              <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.items.subtotal')" :value="formatCurrency(proposal.sub_total)" />
              <DefinitionRow v-if="proposal.discount > 0" :label="$t('gestlab.general.labels.vap_proposals.show.items.total_discount')" :value="`-${formatCurrency(proposal.discount)}`" />
              <DefinitionRow v-if="proposal.tax > 0" :label="$t('gestlab.general.labels.vap_proposals.show.items.total_tax')" :value="formatCurrency(proposal.tax)" />
              <DefinitionRow v-if="proposal.global_discount_amount > 0" :label="$t('gestlab.general.labels.vap_proposals.show.items.global_discount')" :value="`-${formatCurrency(proposal.global_discount_amount)}`" />
              <DefinitionRow v-if="proposal.withholding_tax_amount > 0" :label="$t('gestlab.general.labels.vap_proposals.show.items.withholding_tax')" :value="formatCurrency(proposal.withholding_tax_amount)" />
              <div class="flex items-center justify-between border-t border-[var(--ds-border)] pt-4">
                <span class="text-sm font-bold text-[var(--ds-text)]">{{ $t('gestlab.general.labels.vap_proposals.show.items.grand_total') }}</span>
                <span class="text-xl font-bold text-[var(--ds-text)]">{{ formatCurrency(proposal.total) }}</span>
              </div>
            </div>
          </div>
        </section>

        <section v-if="proposal.compliance_agreement" class="ds-panel p-5 sm:p-6">
          <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
              <p class="ds-kicker">ISO 17025</p>
              <h2 class="ds-heading mt-1 flex items-center gap-2 text-lg">
                <ShieldCheckIcon class="h-5 w-5 text-primary-700 dark:text-primary-200" />
                {{ $t('gestlab.general.labels.vap_proposals.show.compliance.title') }}
              </h2>
            </div>
            <span v-if="proposal.compliance_agreement.acknowledged_at" class="ds-badge ds-badge-success gap-1">
              <CheckCircleIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.vap_proposals.show.compliance.signed') }}
            </span>
          </div>

          <div v-if="proposal.compliance_agreement.acknowledged_at" class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-400/20 dark:bg-emerald-400/10">
            <p class="text-sm font-bold text-emerald-900 dark:text-emerald-100">
              {{ $t('gestlab.general.labels.vap_proposals.show.compliance.signed_on') }} {{ formatDateTime(proposal.compliance_agreement.acknowledged_at) }}
            </p>
            <p class="mt-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300">IP: {{ proposal.compliance_agreement.client_ip || '—' }}</p>
          </div>

          <div class="mt-5 grid gap-3 md:grid-cols-3">
            <ComplianceCheck :checked="proposal.compliance_agreement.confidentiality" :label="$t('gestlab.general.labels.vap_proposals.show.compliance.confidentiality')" />
            <ComplianceCheck :checked="proposal.compliance_agreement.impartiality" :label="$t('gestlab.general.labels.vap_proposals.show.compliance.impartiality')" />
            <ComplianceCheck :checked="proposal.compliance_agreement.nondisclosure" :label="$t('gestlab.general.labels.vap_proposals.show.compliance.nondisclosure')" />
          </div>
        </section>

        <section v-if="revisions.length > 0" class="ds-panel p-5 sm:p-6">
          <p class="ds-kicker">{{ $t('gestlab.general.labels.vap_proposals.show.history') }}</p>
          <h2 class="ds-heading mt-1 flex items-center gap-2 text-lg">
            <ClockIcon class="h-5 w-5 text-primary-700 dark:text-primary-200" />
            {{ $t('gestlab.general.labels.vap_proposals.show.revisions.title') }}
            <span class="text-xs font-semibold text-[var(--ds-text-soft)]">({{ revisions.length }} {{ $t('gestlab.general.labels.vap_proposals.changes') }})</span>
          </h2>

          <div class="mt-5 divide-y divide-[var(--ds-border)] border-y border-[var(--ds-border)]">
            <article
              v-for="revision in revisions"
              :key="revision.id"
              class="py-4"
            >
              <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2">
                  <UserIcon class="h-4 w-4 text-primary-700 dark:text-primary-200" />
                  <span class="text-sm font-bold text-[var(--ds-text)]">{{ revision.causer?.name || $t('gestlab.general.labels.vap_proposals.show.system_user') }}</span>
                </div>
                <span class="text-xs font-semibold text-[var(--ds-text-soft)]">{{ formatDateTime(revision.created_at) }}</span>
              </div>
              <p class="mt-2 text-sm font-medium leading-6 text-[var(--ds-text-muted)]">{{ revision.description }}</p>
              <div v-if="revision.properties?.reason" class="mt-3 rounded-lg bg-[var(--ds-panel-subtle)] p-3 text-xs font-semibold text-[var(--ds-text-muted)]">
                <strong>{{ $t('gestlab.general.labels.vap_proposals.show.revisions.reason') }}:</strong> {{ revision.properties.reason }}
              </div>
              <div v-if="revision.event === 'revised' && revision.properties?.old_values" class="mt-4 grid gap-3 text-xs md:grid-cols-2">
                <div v-if="revision.properties.old_values.total !== revision.properties.new_values.total" class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                  <span class="font-bold text-[var(--ds-text-soft)]">Total</span>
                  <div class="mt-2 flex items-center gap-2">
                    <span class="font-bold text-red-600 line-through dark:text-red-300">{{ formatCurrency(revision.properties.old_values.total) }}</span>
                    <ArrowRightIcon class="h-3 w-3 text-[var(--ds-text-soft)]" />
                    <span class="font-bold text-emerald-700 dark:text-emerald-300">{{ formatCurrency(revision.properties.new_values.total) }}</span>
                  </div>
                </div>
                <div v-if="revision.properties.old_values.items_count !== revision.properties.new_values.items_count" class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                  <span class="font-bold text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_proposals.items') }}</span>
                  <div class="mt-2 flex items-center gap-2">
                    <span class="font-bold text-red-600 line-through dark:text-red-300">{{ revision.properties.old_values.items_count }}</span>
                    <ArrowRightIcon class="h-3 w-3 text-[var(--ds-text-soft)]" />
                    <span class="font-bold text-emerald-700 dark:text-emerald-300">{{ revision.properties.new_values.items_count }}</span>
                  </div>
                </div>
              </div>
            </article>
          </div>
        </section>
      </main>

      <aside class="space-y-4 xl:sticky xl:top-6 xl:self-start">
        <section class="ds-panel p-5">
          <h3 class="ds-heading text-sm">
            {{ $t('gestlab.general.labels.vap_proposals.show.actions.title') }}
          </h3>
          <div class="mt-4 space-y-2">
            <button
              type="button"
              @click="generatePdf"
              class="ds-button ds-button-primary w-full"
            >
              <DocumentArrowDownIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_proposals.show.actions.generate_pdf') }}
            </button>
            <a
              :href="route('vap-proposals.public.show', proposal.unique_hash)"
              target="_blank"
              class="ds-button ds-button-secondary w-full"
            >
              <LinkIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_proposals.show.actions.view_public_link') }}
            </a>
          </div>

          <div class="mt-5 border-t border-[var(--ds-border)] pt-4">
            <p class="text-xs font-bold text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_proposals.show.actions.share') }}</p>
            <div class="mt-3 flex gap-2">
              <BaseInput
                :value="publicLink"
                readonly
                class="ds-field min-w-0 flex-1"
              />
              <button
                type="button"
                @click="copyToClipboard"
                class="ds-button ds-button-secondary shrink-0"
              >
                {{ copied ? $t('gestlab.general.labels.vap_proposals.show.copied') : $t('gestlab.general.labels.vap_proposals.show.copy') }}
              </button>
            </div>
          </div>
        </section>

        <section class="ds-panel p-5">
          <h3 class="ds-heading flex items-center gap-2 text-sm">
            <Cog6ToothIcon class="h-4 w-4 text-primary-700 dark:text-primary-200" />
            {{ $t('gestlab.general.labels.vap_proposals.show.status.title') }}
          </h3>
          <div class="mt-4 space-y-2">
            <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.status.current')" :value="proposal.status_badge?.text || proposal.status" strong />
            <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.status.is_original')" :value="proposal.is_original ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no')" />
            <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.status.use_matrix_price')" :value="proposal.use_matrix_price ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no')" />
            <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.status.tolerance_days')" :value="`${proposal.tolerance_days} ${$t('gestlab.general.labels.vap_proposals.show.days')}`" />
            <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.status.converted')" :value="proposal.converted_to_invoice ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no')" />
          </div>
        </section>

        <section class="ds-panel p-5">
          <h3 class="ds-heading flex items-center gap-2 text-sm">
            <CurrencyDollarIcon class="h-4 w-4 text-primary-700 dark:text-primary-200" />
            {{ $t('gestlab.general.labels.vap_proposals.show.financial_summary.title') }}
          </h3>
          <div class="mt-4 space-y-2">
            <DefinitionRow :label="$t('gestlab.general.labels.vap_proposals.show.financial_summary.subtotal')" :value="formatCurrency(proposal.sub_total)" />
            <DefinitionRow v-if="proposal.discount > 0" :label="$t('gestlab.general.labels.vap_proposals.show.financial_summary.discount')" :value="`-${formatCurrency(proposal.discount)}`" />
            <DefinitionRow v-if="proposal.tax > 0" :label="$t('gestlab.general.labels.vap_proposals.show.financial_summary.tax')" :value="formatCurrency(proposal.tax)" />
            <div class="flex items-center justify-between border-t border-[var(--ds-border)] pt-4">
              <span class="text-sm font-bold text-[var(--ds-text)]">{{ $t('gestlab.general.labels.vap_proposals.show.financial_summary.total') }}</span>
              <span class="text-lg font-bold text-[var(--ds-text)]">{{ formatCurrency(proposal.total) }}</span>
            </div>
          </div>
        </section>

        <section v-if="proposal.template" class="ds-panel p-5">
          <h3 class="ds-heading flex items-center gap-2 text-sm">
            <DocumentDuplicateIcon class="h-4 w-4 text-primary-700 dark:text-primary-200" />
            {{ $t('gestlab.general.labels.vap_proposals.show.template.title') }}
          </h3>
          <div class="mt-4 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
            <h4 class="text-sm font-bold text-[var(--ds-text)]">{{ proposal.template.name }}</h4>
            <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">
              {{ $t('gestlab.general.labels.vap_proposals.show.template.created_by') }} {{ proposal.template?.user?.name || '—' }}
            </p>
            <p class="mt-3 line-clamp-4 text-sm font-medium leading-6 text-[var(--ds-text-muted)]">{{ resolvedTemplateSummary }}</p>
            <button
              type="button"
              @click="showTemplatePreview = true"
              class="mt-3 text-sm font-bold text-primary-700 hover:text-primary-900 dark:text-primary-200"
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
        <div class="space-y-4 text-sm font-medium text-[var(--ds-text-muted)]">
          <p>{{ $t('gestlab.general.labels.vap_proposals.show.send_modal.message') }}</p>
          <label class="flex items-center gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
            <CheckboxInput v-model="sendOptions.generatePdf" type="checkbox" class="ds-checkbox" />
            <span>{{ $t('gestlab.general.labels.vap_proposals.show.send_modal.generate_pdf') }}</span>
          </label>
          <label class="flex items-center gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
            <CheckboxInput v-model="sendOptions.sendEmail" type="checkbox" class="ds-checkbox" />
            <span>{{ $t('gestlab.general.labels.vap_proposals.show.send_modal.send_email') }}</span>
          </label>
        </div>
      </template>
    </ConfirmationModal>

    <Modal :show="showTemplatePreview" @close="showTemplatePreview = false" max-width="4xl">
      <div class="bg-[var(--ds-panel)] p-6 text-[var(--ds-text)]">
        <div class="mb-6 flex items-start justify-between gap-4">
          <div>
            <p class="ds-kicker">{{ $t('gestlab.general.labels.vap_proposals.show.resolved_preview') }}</p>
            <h2 class="ds-heading mt-1 text-lg">{{ proposal.template?.name }}</h2>
            <p class="mt-1 text-sm font-semibold text-[var(--ds-text-soft)]">
              {{ $t('gestlab.general.labels.vap_proposals.show.resolved_preview_description') }}
            </p>
          </div>
          <button type="button" class="ds-icon-button" @click="showTemplatePreview = false">
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>
        <div class="max-h-[65vh] overflow-y-auto rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-6 text-[var(--ds-text-muted)]">
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
  laboratoryDossier: {
    type: Object,
    default: null,
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
const resolvedTemplateSummary = computed(() => stripHtml(resolvedTemplateContent.value) || trans('gestlab.general.labels.vap_proposals.show.empty_template'))

const publicLink = computed(() => route('vap-proposals.public.show', props.proposal.unique_hash))

const statusBadgeClasses = computed(() => {
  const classes = {
    PENDING: 'ds-badge ds-badge-warning',
    SENT: 'ds-badge ds-badge-info',
    VIEWED: 'ds-badge ds-badge-info',
    ACCEPTED: 'ds-badge ds-badge-success',
    REJECTED: 'ds-badge ds-badge-danger',
    REVISED: 'ds-badge ds-badge-warning',
    EXPIRED: 'ds-badge ds-badge-neutral',
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
      h('span', { class: 'text-sm font-semibold text-[var(--ds-text-soft)]' }, rowProps.label),
      h('span', {
        class: [
          'text-right text-sm text-[var(--ds-text)]',
          rowProps.strong ? 'font-bold' : 'font-semibold',
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

    return () => h('div', { class: 'rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4' }, [
      h('div', { class: 'mb-4 flex items-center gap-3' }, [
        h('span', { class: 'flex h-9 w-9 items-center justify-center rounded-lg bg-primary-700 text-white dark:bg-primary-300 dark:text-primary-950' }, [
          h(Icon, { class: 'h-4 w-4' }),
        ]),
        h('h3', { class: 'text-sm font-bold text-[var(--ds-text)]' }, panelProps.title),
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
      neutral: 'bg-[var(--ds-panel-subtle)] text-[var(--ds-text)] ring-[var(--ds-border)]',
    }

    return () => h('div', { class: ['rounded-lg p-4 ring-1', toneClasses[itemProps.tone] || toneClasses.neutral] }, [
      h('div', { class: 'mb-3 flex h-8 w-8 items-center justify-center rounded-lg bg-white/70 dark:bg-white/10' }, [
        h(itemProps.tone === 'green' ? CalendarIcon : ClockIcon, { class: 'h-5 w-5' }),
      ]),
      h('p', { class: 'text-xs font-bold opacity-75' }, itemProps.title),
      h('p', { class: 'mt-2 text-sm font-bold' }, itemProps.value),
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
        'flex items-center gap-3 rounded-lg border p-4',
        checkProps.checked
          ? 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-300/20 dark:bg-emerald-400/10 dark:text-emerald-100'
          : 'border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)]',
      ],
    }, [
      h('span', {
        class: [
          'flex h-8 w-8 shrink-0 items-center justify-center rounded-full',
          checkProps.checked ? 'bg-emerald-600 text-white' : 'bg-[var(--ds-panel-muted)] text-[var(--ds-text-soft)]',
        ],
      }, [
        h(CheckCircleIcon, { class: 'h-5 w-5' }),
      ]),
      h('span', { class: 'text-sm font-bold leading-5' }, checkProps.label),
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
