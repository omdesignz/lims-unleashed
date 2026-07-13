<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 border-b border-[color:var(--ds-border)] px-5 py-5 lg:flex-row lg:items-start lg:justify-between lg:px-6">
        <div class="max-w-3xl">
          <p class="ds-kicker">Ciclo de amostras</p>
          <h1 class="ds-heading mt-2 text-2xl">
            {{ $page.props.title || 'Gestão de Amostras VAP' }}
          </h1>
          <p class="ds-copy mt-2 text-sm">
            {{ activeTab === 'entry'
              ? 'Receção, validação de condicionamento, escopo analítico e encaminhamento para o laboratório.'
              : 'Destruição controlada, método de eliminação e evidência rastreável para auditoria.' }}
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <span class="ds-chip">
            <span class="lims-status-dot lims-status-dot-instrument" />
            {{ stats.total_samples || 0 }} amostras
          </span>
          <button type="button" class="ds-button ds-button-secondary" @click="refreshData">
            <ArrowPathIcon class="h-4 w-4" />
            Atualizar
          </button>
        </div>
      </div>

      <div class="flex items-center gap-1 overflow-x-auto border-b border-[color:var(--ds-border)] px-4 sm:px-6" role="tablist" aria-label="Área de gestão de amostras">
        <button
          type="button"
          role="tab"
          :aria-selected="activeTab === 'entry'"
          :class="[
            '-mb-px inline-flex min-h-12 items-center gap-2 border-b-2 px-3 text-sm font-bold transition-colors',
            activeTab === 'entry'
              ? 'border-primary-600 text-primary-800 dark:border-primary-300 dark:text-primary-200'
              : 'border-transparent text-[color:var(--ds-text-muted)] hover:border-[color:var(--ds-border-strong)] hover:text-[color:var(--ds-text)]'
          ]"
          @click="activeTab = 'entry'"
        >
          <BeakerIcon class="h-5 w-5" />
          Entrada e triagem
        </button>
        <button
          type="button"
          role="tab"
          :aria-selected="activeTab === 'discard'"
          :class="[
            '-mb-px inline-flex min-h-12 items-center gap-2 border-b-2 px-3 text-sm font-bold transition-colors',
            activeTab === 'discard'
              ? 'border-rose-600 text-rose-700 dark:border-rose-400 dark:text-rose-200'
              : 'border-transparent text-[color:var(--ds-text-muted)] hover:border-[color:var(--ds-border-strong)] hover:text-[color:var(--ds-text)]'
          ]"
          @click="activeTab = 'discard'"
        >
          <ArchiveBoxXMarkIcon class="h-5 w-5" />
          Descarte
        </button>
      </div>

      <dl class="grid grid-cols-2 divide-x divide-y divide-[color:var(--ds-border)] sm:grid-cols-4 sm:divide-y-0">
        <div class="px-5 py-4">
          <dt class="flex items-center gap-2 text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">
            <span class="lims-status-dot lims-status-dot-hold" />
            Por iniciar
          </dt>
          <dd class="mt-2 text-2xl font-bold text-[color:var(--ds-text)]">{{ stats.pending_analysis || 0 }}</dd>
        </div>
        <div class="px-5 py-4">
          <dt class="flex items-center gap-2 text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">
            <span class="lims-status-dot lims-status-dot-instrument" />
            Em progresso
          </dt>
          <dd class="mt-2 text-2xl font-bold text-[color:var(--ds-text)]">{{ stats.in_progress || 0 }}</dd>
        </div>
        <div class="px-5 py-4">
          <dt class="flex items-center gap-2 text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">
            <span class="lims-status-dot lims-status-dot-release" />
            Completadas
          </dt>
          <dd class="mt-2 text-2xl font-bold text-[color:var(--ds-text)]">{{ stats.completed_analysis || 0 }}</dd>
        </div>
        <div class="px-5 py-4">
          <dt class="flex items-center gap-2 text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">
            <span class="lims-status-dot lims-status-dot-critical" />
            Descartadas
          </dt>
          <dd class="mt-2 text-2xl font-bold text-[color:var(--ds-text)]">{{ stats.total_discarded || 0 }}</dd>
        </div>
      </dl>
    </section>

    <template v-if="activeTab === 'entry'">
      <section class="ds-command-surface overflow-hidden">
        <div class="grid lg:grid-cols-[minmax(0,1fr)_20rem]">
          <div class="p-5 lg:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
              <div class="max-w-2xl">
                <p class="ds-kicker">Bancada de receção</p>
                <h2 class="ds-heading mt-2 text-lg">Iniciar um fluxo de entrada</h2>
                <p class="ds-copy mt-1 text-sm">
                  Escolha o modo de receção. Cada caminho mantém identificação, cadeia de custódia e escopo analítico no mesmo registo.
                </p>
              </div>
              <button type="button" class="ds-button ds-button-secondary" @click="downloadImportTemplate">
                <ArrowDownTrayIcon class="h-4 w-4" />
                Modelo Excel
              </button>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
              <button type="button" class="ds-card group p-4 text-left transition hover:border-primary-300" @click="newSample">
                <PlusCircleIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                <span class="mt-3 block text-sm font-bold text-[color:var(--ds-text)]">Entrada individual</span>
                <span class="mt-1 block text-xs leading-5 text-[color:var(--ds-text-muted)]">Registar uma amostra com escopo completo.</span>
              </button>
              <button type="button" class="ds-card group p-4 text-left transition hover:border-primary-300" @click="startManualBatch">
                <QueueListIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                <span class="mt-3 block text-sm font-bold text-[color:var(--ds-text)]">Fila manual</span>
                <span class="mt-1 block text-xs leading-5 text-[color:var(--ds-text-muted)]">Preparar várias entradas antes de submeter.</span>
              </button>
              <button type="button" class="ds-card group p-4 text-left transition hover:border-primary-300" @click="chooseImportFile">
                <CloudArrowUpIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                <span class="mt-3 block text-sm font-bold text-[color:var(--ds-text)]">Importação em lote</span>
                <span class="mt-1 block text-xs leading-5 text-[color:var(--ds-text-muted)]">Carregar um ficheiro validado de amostras.</span>
              </button>
              <button type="button" class="ds-card group p-4 text-left transition hover:border-emerald-300" @click="newInternalQcSample('microbiology_and_chemistry')">
                <ScaleIcon class="h-5 w-5 text-emerald-700 dark:text-emerald-300" />
                <span class="mt-3 block text-sm font-bold text-[color:var(--ds-text)]">CQ de matéria-prima</span>
                <span class="mt-1 block text-xs leading-5 text-[color:var(--ds-text-muted)]">Abrir microbiologia e química sem proposta.</span>
              </button>
            </div>

            <FileInput
              ref="importFileInput"
              type="file"
              accept=".xlsx,.xls,.csv,.txt"
              class="hidden"
              @change="onImportFileChange"
            />
            <p v-if="importForm.errors.file" class="ds-field-error mt-3">{{ importForm.errors.file }}</p>
          </div>

          <aside class="border-t border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] p-5 lg:border-l lg:border-t-0">
            <p class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Estado da receção</p>
            <dl class="mt-3 divide-y divide-[color:var(--ds-border)]">
              <div v-for="card in sampleEntryCommandCards" :key="card.label" class="py-3 first:pt-0 last:pb-0">
                <div class="flex items-baseline justify-between gap-3">
                  <dt class="text-sm font-bold text-[color:var(--ds-text-muted)]">{{ card.label }}</dt>
                  <dd class="text-xl font-bold text-[color:var(--ds-text)]">{{ card.value }}</dd>
                </div>
                <p class="mt-1 text-xs leading-5 text-[color:var(--ds-text-soft)]">{{ card.hint }}</p>
              </div>
            </dl>
          </aside>
        </div>
      </section>

      <section class="grid gap-4 xl:grid-cols-[minmax(0,1.25fr)_minmax(18rem,0.75fr)]">
        <article class="ds-card p-5">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <p class="ds-kicker">Carga de trabalho</p>
              <h2 class="ds-heading mt-2 text-base">Ritmo de receção</h2>
              <p class="ds-copy mt-1 text-xs">Volume dos últimos sete dias.</p>
            </div>
            <div class="text-right">
              <p class="text-2xl font-bold text-[color:var(--ds-text)]">{{ intakeTrendTotal }}</p>
              <p class="text-xs font-semibold text-[color:var(--ds-text-soft)]">na janela</p>
            </div>
          </div>
          <apexchart class="mt-3" type="area" height="250" :options="intakeTrendChartOptions" :series="intakeTrendChartSeries" />
        </article>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
          <article class="ds-card p-5">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="ds-kicker">Carteira</p>
                <h2 class="ds-heading mt-2 text-base">Estado do fluxo</h2>
              </div>
              <span class="ds-chip">{{ lifecycleStatusTotal }} amostras</span>
            </div>
            <apexchart class="mt-2" type="donut" height="210" :options="lifecycleStatusChartOptions" :series="lifecycleStatusChartSeries" />
          </article>

          <article class="ds-card p-5">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="ds-kicker">Retenção</p>
                <h2 class="ds-heading mt-2 text-base">Pressão operacional</h2>
              </div>
              <span class="ds-chip border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-400/30 dark:bg-amber-500/10 dark:text-amber-200">
                {{ retentionPressureAlertCount }} em atenção
              </span>
            </div>
            <apexchart class="mt-2" type="bar" height="190" :options="retentionPressureChartOptions" :series="retentionPressureChartSeries" />
          </article>
        </div>
      </section>

      <section class="ds-table-shell">
        <div class="ds-table-summary flex-col items-stretch px-5 py-4 lg:flex-row lg:items-center">
          <div>
            <h2 class="ds-heading text-base">Registo de amostras</h2>
            <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">
              {{ filteredSampleResults.length }} de {{ samples.length }} registos correspondem aos filtros.
            </p>
          </div>
          <div class="flex flex-col gap-2 sm:flex-row">
            <div class="min-w-0 sm:w-72">
              <label class="sr-only" for="sample-search">Pesquisar amostras</label>
              <BaseInput
                id="sample-search"
                v-model="searchQuery"
                type="search"
                class="ds-field"
                placeholder="Código, amostra, cliente, lote..."
              />
            </div>
            <div class="sm:w-48">
              <label class="sr-only" for="sample-status">Filtrar por estado</label>
              <BaseSelect id="sample-status" v-model="statusFilter" class="ds-field">
                <option value="">Todos os estados</option>
                <option value="POR_INICIAR">Por iniciar</option>
                <option value="EN_PROGRESO">Em progresso</option>
                <option value="COMPLETADO">Completado</option>
                <option value="CANCELADO">Cancelado</option>
                <option value="EN_PAUSA">Em pausa</option>
              </BaseSelect>
            </div>
            <button type="button" class="ds-button ds-button-secondary" @click="exportData">
              <ArrowDownTrayIcon class="h-4 w-4" />
              Exportar
            </button>
          </div>
        </div>

        <div v-if="filteredSamples.length === 0" class="p-5">
          <div class="ds-empty-state px-5 py-10 text-center">
            <BeakerIcon class="mx-auto h-8 w-8 text-[color:var(--ds-text-soft)]" />
            <h3 class="ds-heading mt-3 text-sm">Nenhuma amostra encontrada</h3>
            <p class="ds-copy mt-1 text-xs">
              {{ searchQuery || statusFilter ? 'Ajuste os filtros ou limpe a pesquisa.' : 'Inicie uma entrada para criar o primeiro registo.' }}
            </p>
          </div>
        </div>

        <div v-else class="overflow-x-auto">
          <DataTable class="min-w-full align-middle">
            <thead class="ds-table-head">
              <tr>
                <th class="ds-table-heading px-5 py-3 text-left">Amostra</th>
                <th class="ds-table-heading px-5 py-3 text-left">Cliente / produto</th>
                <th class="ds-table-heading px-5 py-3 text-left">Lote / origem</th>
                <th class="ds-table-heading px-5 py-3 text-left">Estado</th>
                <th class="ds-table-heading px-5 py-3 text-left">Receção</th>
                <th class="ds-table-heading px-5 py-3 text-right">Ações</th>
              </tr>
            </thead>
            <tbody class="ds-table-body divide-y divide-[color:var(--ds-border)]">
              <tr v-for="sample in filteredSamples" :key="sample.id" class="ds-table-row">
                <td class="px-5 py-3">
                  <button type="button" class="text-left" @click="viewSample(sample.id)">
                    <span class="block text-sm font-bold text-[color:var(--ds-text)]">{{ sample.name }}</span>
                    <span class="mt-0.5 block font-mono text-xs text-[color:var(--ds-text-soft)]">{{ sample.code }}</span>
                  </button>
                </td>
                <td class="ds-table-cell px-5 py-3">
                  <span class="block text-[color:var(--ds-text)]">{{ sample.customer?.name || 'Cliente por confirmar' }}</span>
                  <span class="mt-0.5 block text-xs text-[color:var(--ds-text-soft)]">{{ selectedSampleProductName(sample) }}</span>
                </td>
                <td class="ds-table-cell px-5 py-3">
                  <span class="block">{{ sample.client_submitted_info?.lot || 'Sem lote' }}</span>
                  <span class="mt-0.5 block text-xs text-[color:var(--ds-text-soft)]">
                    {{ sample.client_submitted_info?.origin || sample.client_submitted_info?.sampling_plan_ref || 'Origem por confirmar' }}
                  </span>
                </td>
                <td class="px-5 py-3">
                  <span :class="sampleStatusBadgeClass(sample.status)">{{ getStatusLabel(sample.status) }}</span>
                </td>
                <td class="ds-table-cell whitespace-nowrap px-5 py-3">{{ formatDate(sample.received_at) }}</td>
                <td class="px-5 py-3">
                  <div class="flex items-center justify-end gap-1">
                    <button type="button" class="ds-table-action" title="Visualizar" @click="viewSample(sample.id)">
                      <EyeIcon class="h-4 w-4" />
                      <span class="sr-only">Visualizar</span>
                    </button>
                    <button type="button" class="ds-table-action" title="Editar" @click="editSample(sample)">
                      <PencilSquareIcon class="h-4 w-4" />
                      <span class="sr-only">Editar</span>
                    </button>
                    <button type="button" class="ds-table-action" title="Gerar PDF de entrada" @click="generateEntryPdf(sample.id)">
                      <DocumentArrowDownIcon class="h-4 w-4" />
                      <span class="sr-only">Gerar PDF</span>
                    </button>
                    <button
                      v-if="sample.status === 'COMPLETADO' || sample.status === 'CANCELADO'"
                      type="button"
                      class="ds-table-action ds-table-action-danger"
                      title="Preparar descarte"
                      @click="prepareForDiscard(sample)"
                    >
                      <TrashIcon class="h-4 w-4" />
                      <span class="sr-only">Descartar</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </DataTable>
        </div>

        <div v-if="samples.length > 0" class="flex flex-col gap-3 border-t border-[color:var(--ds-border)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
          <p class="text-xs font-semibold text-[color:var(--ds-text-soft)]">
            Página {{ currentPage }} de {{ totalPages }} · {{ filteredSampleResults.length }} resultados
          </p>
          <div class="flex items-center gap-2">
            <button type="button" class="ds-button ds-button-secondary" :disabled="currentPage === 1" @click="currentPage--">Anterior</button>
            <button type="button" class="ds-button ds-button-secondary" :disabled="currentPage === totalPages" @click="currentPage++">Próxima</button>
          </div>
        </div>
      </section>

      <section v-if="portalAnalysisRequests.length" class="ds-command-surface p-5">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <p class="ds-kicker">Portal do cliente</p>
            <h2 class="ds-heading mt-2 text-base">Pedidos por validar</h2>
            <p class="ds-copy mt-1 text-xs">Use um pedido para pré-preencher a entrada e manter a referência comercial.</p>
          </div>
          <span class="ds-chip">{{ portalAnalysisRequests.length }} pendentes</span>
        </div>
        <div class="mt-4 grid gap-3 lg:grid-cols-2">
          <article v-for="request in portalAnalysisRequests.slice(0, 6)" :key="request.id" class="ds-card flex flex-col gap-4 p-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
              <h3 class="truncate text-sm font-bold text-[color:var(--ds-text)]">{{ request.title }}</h3>
              <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">{{ request.reference || 'Sem referência' }} · {{ request.customer || 'Sem cliente' }}</p>
              <p class="mt-2 text-xs leading-5 text-[color:var(--ds-text-muted)]">{{ (request.requested_profile_names || []).join(', ') || 'Sem perfis declarados' }}</p>
            </div>
            <button type="button" class="ds-button ds-button-secondary shrink-0" @click="prefillFromPortalRequest(request)">Pré-preencher</button>
          </article>
        </div>
      </section>

      <section v-if="editingSample" class="ds-panel overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="ds-kicker">{{ editingSample.id ? 'Alteração controlada' : 'Nova receção' }}</p>
            <h2 class="ds-heading mt-1 text-lg">
              {{ editingSample.id ? 'Editar amostra' : (manualBatchMode ? 'Adicionar amostra à fila manual' : 'Registar amostra') }}
            </h2>
          </div>
          <span v-if="manualBatchMode" class="ds-chip">
            <QueueListIcon class="h-4 w-4" />
            {{ manualSampleQueue.length }} na fila
          </span>
        </div>

        <form class="p-5 lg:p-6" @submit.prevent="editingSample.id ? updateSample() : submitSample()">
          <div class="space-y-8">
            <div class="grid gap-5 lg:grid-cols-[15rem_minmax(0,1fr)]">
              <div>
                <div class="flex items-center gap-2">
                  <TagIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                  <h3 class="ds-heading text-sm">Identificação e origem</h3>
                </div>
                <p class="ds-copy mt-2 text-xs">Identificação primária, natureza do pedido e ligação ao cliente.</p>
              </div>

              <div class="ds-card grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
                <div class="ds-field-group">
                  <label class="ds-field-label" for="sample-name">Nome da amostra <span class="ds-field-required">*</span></label>
                  <BaseInput id="sample-name" v-model="form.name" type="text" class="ds-field" :aria-invalid="Boolean(form.errors.name)" placeholder="Nome descritivo da amostra" />
                  <p v-if="form.errors.name" class="ds-field-error">{{ form.errors.name }}</p>
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label" for="sample-code">Código</label>
                  <BaseInput id="sample-code" v-model="form.code" type="text" class="ds-field font-mono" :aria-invalid="Boolean(form.errors.code)" placeholder="Gerado automaticamente" />
                  <p v-if="form.errors.code" class="ds-field-error">{{ form.errors.code }}</p>
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label" for="sample-type">Tipo <span class="ds-field-required">*</span></label>
                  <BaseSelect id="sample-type" v-model="form.sample_type" class="ds-field" :aria-invalid="Boolean(form.errors.sample_type)">
                    <option value="">Selecione o tipo</option>
                    <option value="ROTINA">Rotina</option>
                    <option value="MATERIA_PRIMA">Matéria-prima</option>
                    <option value="PRODUTO_ACABADO">Produto acabado</option>
                    <option value="ESTABILIDADE">Estabilidade</option>
                    <option value="CONTRAPROVA">Contraprova</option>
                    <option value="INTERLABORATORIAL">Interlaboratorial</option>
                    <option value="RETENCAO">Retenção</option>
                  </BaseSelect>
                  <p v-if="form.errors.sample_type" class="ds-field-error">{{ form.errors.sample_type }}</p>
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label" for="request-origin">Origem do trabalho</label>
                  <BaseSelect id="request-origin" v-model="form.client_submitted_info.request_origin" class="ds-field">
                    <option value="client">Cliente</option>
                    <option value="internal">Interno</option>
                  </BaseSelect>
                  <p class="ds-field-hint">Trabalhos internos podem seguir sem proposta aceite.</p>
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label" for="collection-type">Fluxo de colheita</label>
                  <BaseSelect id="collection-type" v-model="form.client_submitted_info.collection_type" class="ds-field">
                    <option value="direct">Direta / receção imediata</option>
                    <option value="programmed">Programada / recolha planeada</option>
                  </BaseSelect>
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label" for="sample-customer">Cliente <span class="ds-field-required">*</span></label>
                  <BaseSelect id="sample-customer" v-model="form.customer_id" class="ds-field" :aria-invalid="Boolean(form.errors.customer_id)">
                    <option value="">Selecione o cliente</option>
                    <option v-for="customer in customers" :key="customer.id" :value="customer.id">{{ customer.name }} ({{ customer.code }})</option>
                  </BaseSelect>
                  <p v-if="form.errors.customer_id" class="ds-field-error">{{ form.errors.customer_id }}</p>
                </div>

                <div v-if="form.client_submitted_info.collection_type === 'programmed'" class="md:col-span-2 xl:col-span-3">
                  <div class="border-l-4 border-primary-500 bg-primary-50/70 p-4 dark:bg-primary-500/10">
                    <h4 class="text-sm font-bold text-primary-950 dark:text-primary-100">Colheita programada</h4>
                    <p class="mt-1 text-xs text-primary-800 dark:text-primary-200">Indique onde e por quem a recolha será executada.</p>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                      <div class="ds-field-group">
                        <label class="ds-field-label" for="collection-location">Local de colheita</label>
                        <BaseInput id="collection-location" v-model="form.client_submitted_info.collection_location" type="text" class="ds-field" placeholder="Armazém, linha, sala fria..." />
                      </div>
                      <div class="ds-field-group">
                        <label class="ds-field-label" for="vehicle-reference">Viatura ou equipa</label>
                        <BaseInput id="vehicle-reference" v-model="form.client_submitted_info.vehicle_reference" type="text" class="ds-field" placeholder="Equipa externa, viatura 02..." />
                      </div>
                    </div>
                  </div>
                </div>

                <div v-if="isInternalRawMaterialQc" class="md:col-span-2 xl:col-span-3">
                  <div class="border-l-4 border-emerald-500 bg-emerald-50/70 p-4 dark:bg-emerald-500/10">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                      <div>
                        <h4 class="text-sm font-bold text-emerald-950 dark:text-emerald-100">Controlo interno de matéria-prima</h4>
                        <p class="mt-1 text-xs text-emerald-800 dark:text-emerald-200">O registo gera colheita, lab code e análises no fluxo normal.</p>
                      </div>
                      <span class="ds-chip border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-400/30 dark:bg-emerald-500/10 dark:text-emerald-200">Sem proposta</span>
                    </div>
                    <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                      <div class="ds-field-group">
                        <label class="ds-field-label" for="qc-discipline">Disciplina</label>
                        <BaseSelect id="qc-discipline" v-model="form.client_submitted_info.analysis_discipline" class="ds-field">
                          <option value="microbiology">Microbiologia</option>
                          <option value="chemistry">Química / físico-química</option>
                          <option value="microbiology_and_chemistry">Microbiologia + química</option>
                        </BaseSelect>
                      </div>
                      <div class="ds-field-group">
                        <label class="ds-field-label" for="qc-purpose">Objetivo</label>
                        <BaseSelect id="qc-purpose" v-model="form.client_submitted_info.quality_control_purpose" class="ds-field">
                          <option value="raw_material_release">Liberação de matéria-prima</option>
                          <option value="supplier_qualification">Qualificação de fornecedor</option>
                          <option value="process_validation">Validação de processo</option>
                          <option value="stability_follow_up">Acompanhamento de estabilidade</option>
                          <option value="investigation">Investigação interna</option>
                          <option value="other">Outro</option>
                        </BaseSelect>
                      </div>
                      <div class="ds-field-group">
                        <label class="ds-field-label" for="qc-decision">Decisão esperada</label>
                        <BaseSelect id="qc-decision" v-model="form.client_submitted_info.qc_decision" class="ds-field">
                          <option value="hold_until_release">Reter até liberação</option>
                          <option value="release_if_compliant">Liberar se conforme</option>
                          <option value="investigate_before_release">Investigar antes de liberar</option>
                          <option value="trend_only">Apenas tendência</option>
                        </BaseSelect>
                      </div>
                      <div class="ds-field-group">
                        <label class="ds-field-label" for="material-category">Categoria</label>
                        <BaseSelect id="material-category" v-model="form.client_submitted_info.material_category" class="ds-field">
                          <option value="raw_material">Matéria-prima</option>
                          <option value="ingredient">Ingrediente</option>
                          <option value="packaging_material">Material de embalagem</option>
                          <option value="intermediate">Produto intermédio</option>
                          <option value="finished_product">Produto acabado</option>
                          <option value="environmental_control">Controlo ambiental</option>
                          <option value="other">Outro</option>
                        </BaseSelect>
                      </div>
                    </div>
                    <div class="mt-4 grid gap-4 md:grid-cols-3">
                      <div class="ds-field-group">
                        <label class="ds-field-label" for="qc-lot">Lote</label>
                        <BaseInput id="qc-lot" v-model="form.client_submitted_info.lot" type="text" class="ds-field" placeholder="Lote da matéria-prima" />
                      </div>
                      <div class="ds-field-group">
                        <label class="ds-field-label" for="qc-batch">Batch / OP</label>
                        <BaseInput id="qc-batch" v-model="form.client_submitted_info.batch" type="text" class="ds-field" placeholder="Batch interno ou OP" />
                      </div>
                      <div class="ds-field-group">
                        <label class="ds-field-label" for="qc-supplier">Fornecedor</label>
                        <BaseInput id="qc-supplier" v-model="form.client_submitted_info.supplier_name" type="text" class="ds-field" placeholder="Fornecedor ou origem interna" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="grid gap-5 border-t border-[color:var(--ds-border)] pt-8 lg:grid-cols-[15rem_minmax(0,1fr)]">
              <div>
                <div class="flex items-center gap-2">
                  <BuildingOfficeIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                  <h3 class="ds-heading text-sm">Atribuição e escopo</h3>
                </div>
                <p class="ds-copy mt-2 text-xs">Produto, proposta, unidade responsável, armazenamento e perfis analíticos.</p>
              </div>

              <div class="space-y-4">
                <div class="ds-card grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
                  <div class="ds-field-group">
                    <label class="ds-field-label" for="sample-product">Produto</label>
                    <BaseSelect id="sample-product" v-model="form.client_submitted_info.product_id" class="ds-field" :aria-invalid="Boolean(form.errors['client_submitted_info.product_id'])">
                      <option :value="null">Selecionar depois</option>
                      <option v-for="product in products" :key="product.id" :value="product.id">{{ product.name }}{{ product.matrix ? ' · ' + product.matrix : '' }}</option>
                    </BaseSelect>
                    <p v-if="form.errors['client_submitted_info.product_id']" class="ds-field-error">{{ form.errors['client_submitted_info.product_id'] }}</p>
                  </div>
                  <div v-if="!isInternalRequest" class="ds-field-group">
                    <label class="ds-field-label" for="sample-proposal">Proposta aceite</label>
                    <BaseSelect id="sample-proposal" v-model="form.proposal_id" class="ds-field" :aria-invalid="Boolean(form.errors.proposal_id)">
                      <option value="">Selecionar depois</option>
                      <option v-for="proposal in acceptedProposals" :key="proposal.id" :value="proposal.id">{{ getProposalLabel(proposal) }}</option>
                    </BaseSelect>
                    <p class="ds-field-hint">Obrigatória antes da entrada em análise.</p>
                    <p v-if="form.errors.proposal_id" class="ds-field-error">{{ form.errors.proposal_id }}</p>
                  </div>
                  <div v-else class="flex items-center border-l-4 border-emerald-500 bg-emerald-50/70 p-4 text-xs font-semibold text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-200">
                    Trabalho interno: o fluxo não depende de proposta aceite.
                  </div>
                  <div class="ds-field-group">
                    <label class="ds-field-label" for="sample-lab">Laboratório <span class="ds-field-required">*</span></label>
                    <BaseSelect id="sample-lab" v-model="form.lab_id" class="ds-field" :aria-invalid="Boolean(form.errors.lab_id)">
                      <option value="">Selecione o laboratório</option>
                      <option v-for="lab in labs" :key="lab.id" :value="lab.id">{{ lab.name }} ({{ lab.code }})</option>
                    </BaseSelect>
                    <p v-if="form.errors.lab_id" class="ds-field-error">{{ form.errors.lab_id }}</p>
                  </div>
                  <div class="ds-field-group">
                    <label class="ds-field-label" for="sample-department">Departamento <span class="ds-field-required">*</span></label>
                    <BaseSelect id="sample-department" v-model="form.department_id" class="ds-field" :aria-invalid="Boolean(form.errors.department_id)">
                      <option value="">Selecione o departamento</option>
                      <option v-for="department in departments" :key="department.id" :value="department.id">{{ department.name }} ({{ department.code }})</option>
                    </BaseSelect>
                    <p v-if="form.errors.department_id" class="ds-field-error">{{ form.errors.department_id }}</p>
                  </div>
                  <div class="ds-field-group">
                    <label class="ds-field-label" for="received-at">Data de receção</label>
                    <DateTimePicker id="received-at" v-model="form.received_at" type="datetime-local" class="ds-field" />
                  </div>
                  <div class="ds-field-group">
                    <label class="ds-field-label" for="sample-packaging">Embalagem</label>
                    <BaseSelect id="sample-packaging" v-model="form.packaging_id" class="ds-field">
                      <option value="">Selecione a embalagem</option>
                      <option v-for="packaging in packagingCategories" :key="packaging.id" :value="packaging.id">{{ packaging.name }} ({{ packaging.code }})</option>
                    </BaseSelect>
                  </div>
                  <div class="ds-field-group">
                    <label class="ds-field-label" for="sample-warehouse">Armazém <span class="ds-field-required">*</span></label>
                    <BaseSelect id="sample-warehouse" v-model="form.warehouse_id" class="ds-field" :aria-invalid="Boolean(form.errors.warehouse_id)">
                      <option value="">Selecione o armazém</option>
                      <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }} ({{ warehouse.code }})</option>
                    </BaseSelect>
                    <p v-if="form.errors.warehouse_id" class="ds-field-error">{{ form.errors.warehouse_id }}</p>
                  </div>
                  <div class="ds-field-group md:col-span-2 xl:col-span-3">
                    <label class="ds-field-label" for="sample-profiles">Perfis analíticos</label>
                    <BaseSelect id="sample-profiles" v-model="form.client_submitted_info.requested_profile_ids" multiple class="ds-field sample-profile-select min-h-40 py-2">
                      <option v-for="profile in availableProfiles" :key="profile.id" :value="profile.id">
                        {{ profile.name }}{{ profile.analysis_type ? ' · ' + profile.analysis_type : '' }}{{ profile.parameter_count ? ' · ' + profile.parameter_count + ' parâmetros' : '' }}
                      </option>
                    </BaseSelect>
                    <p class="ds-field-hint">Use Ctrl/Cmd para selecionar mais de um perfil.</p>
                    <p v-if="form.errors['client_submitted_info.requested_profile_ids']" class="ds-field-error">{{ form.errors['client_submitted_info.requested_profile_ids'] }}</p>
                  </div>
                </div>

                <div v-if="selectedProduct || selectedProfileSummaries.length" class="ds-command-toolbar p-4">
                  <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                      <h4 class="ds-heading text-sm">Checklist analítico previsto</h4>
                      <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">
                        {{ selectedProduct?.matrix || 'Matriz por confirmar' }} · {{ selectedProfileSummaries.length }} perfis · {{ requiredParameterPreview.length }} parâmetros
                      </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                      <span v-for="profile in selectedProfileSummaries" :key="profile.id" class="ds-chip">{{ profile.name }}</span>
                    </div>
                  </div>
                  <div v-if="requiredParameterPreview.length" class="mt-4 grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                    <article v-for="parameter in requiredParameterPreview" :key="parameter.id" class="border-l-2 border-primary-400 bg-[color:var(--ds-panel-raised)] px-3 py-2">
                      <p class="text-sm font-bold text-[color:var(--ds-text)]">{{ parameter.name }}</p>
                      <p class="mt-0.5 text-xs text-[color:var(--ds-text-soft)]">{{ parameter.code || 'Sem código' }} · {{ parameter.profiles.join(', ') }}</p>
                    </article>
                  </div>
                </div>
              </div>
            </div>

            <div class="grid gap-5 border-t border-[color:var(--ds-border)] pt-8 lg:grid-cols-[15rem_minmax(0,1fr)]">
              <div>
                <div class="flex items-center gap-2">
                  <QrCodeIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                  <h3 class="ds-heading text-sm">Identificação técnica</h3>
                </div>
                <p class="ds-copy mt-2 text-xs">Lote, origem, quantidades e documentos que acompanham a amostra.</p>
              </div>

              <div class="ds-card grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div v-for="field in technicalIdentityFields" :key="field.key" class="ds-field-group">
                  <label class="ds-field-label" :for="'technical-' + field.key">{{ field.label }}</label>
                  <BaseInput
                    :id="'technical-' + field.key"
                    v-model="form.client_submitted_info[field.key]"
                    :type="field.type || 'text'"
                    class="ds-field"
                    :placeholder="field.placeholder"
                  />
                </div>
              </div>
            </div>

            <div class="grid gap-5 border-t border-[color:var(--ds-border)] pt-8 lg:grid-cols-[15rem_minmax(0,1fr)]">
              <div>
                <div class="flex items-center gap-2">
                  <ClipboardDocumentListIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                  <h3 class="ds-heading text-sm">Condicionamento e custódia</h3>
                </div>
                <p class="ds-copy mt-2 text-xs">Decisão na receção, integridade física, temperatura e observações de custódia.</p>
              </div>

              <div class="ds-card grid gap-4 p-5 md:grid-cols-2">
                <div class="ds-field-group">
                  <label class="ds-field-label" for="conditioning-status">Decisão de aceitação</label>
                  <BaseSelect id="conditioning-status" v-model="form.client_submitted_info.conditioning_status" class="ds-field">
                    <option :value="null">Não avaliado</option>
                    <option value="accepted">Aceite</option>
                    <option value="restricted">Aceite com restrições</option>
                    <option value="rejected">Rejeitado / quarentena</option>
                  </BaseSelect>
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label" for="packaging-condition">Estado da embalagem</label>
                  <BaseInput id="packaging-condition" v-model="form.client_submitted_info.packaging_condition" type="text" class="ds-field" placeholder="Íntegra, húmida, violada..." />
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label" for="temperature-condition">Condição térmica</label>
                  <BaseInput id="temperature-condition" v-model="form.client_submitted_info.temperature_condition" type="text" class="ds-field" placeholder="2-8 °C, ambiente, congelada..." />
                </div>
                <div class="ds-field-group md:col-span-2">
                  <label class="ds-field-label" for="integrity-observations">Observações de integridade</label>
                  <textarea id="integrity-observations" v-model="form.client_submitted_info.integrity_observations" rows="3" class="ds-field" placeholder="Volume, lacre, identificação e desvios visuais..."></textarea>
                </div>
                <div class="ds-field-group md:col-span-2">
                  <label class="ds-field-label" for="custody-notes">Cadeia de custódia e condicionamento</label>
                  <textarea id="custody-notes" v-model="form.client_submitted_info.chain_of_custody_notes" rows="3" class="ds-field" placeholder="Transporte, recipiente secundário e ações corretivas..."></textarea>
                </div>
                <div class="ds-field-group md:col-span-2">
                  <label class="ds-field-label" for="requested-services">Serviços solicitados</label>
                  <textarea id="requested-services" v-model="form.requested_services" rows="3" class="ds-field" placeholder="Análises e serviços solicitados..."></textarea>
                </div>
                <div class="ds-field-group md:col-span-2">
                  <label class="ds-field-label" for="sample-observations">Observações gerais</label>
                  <textarea id="sample-observations" v-model="form.obs" rows="3" class="ds-field" placeholder="Notas adicionais..."></textarea>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-8 flex flex-col gap-3 border-t border-[color:var(--ds-border)] pt-5 sm:flex-row sm:items-center sm:justify-between">
            <button
              v-if="!editingSample.id"
              type="button"
              class="ds-button ds-button-secondary"
              :disabled="!isFormValid"
              @click="addCurrentSampleToManualQueue"
            >
              <QueueListIcon class="h-4 w-4" />
              Adicionar à fila manual
            </button>
            <span v-else />

            <div class="flex items-center justify-end gap-2">
              <button type="button" class="ds-button ds-button-secondary" @click="cancelEdit">Cancelar</button>
              <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !isFormValid">
                <CheckCircleIcon class="h-4 w-4" />
                {{ form.processing ? 'A processar...' : (editingSample.id ? 'Atualizar amostra' : 'Registar amostra') }}
              </button>
            </div>
          </div>
        </form>
      </section>

      <section class="grid gap-4 xl:grid-cols-[minmax(0,1.3fr)_minmax(18rem,0.7fr)]">
        <article v-if="manualBatchMode || manualSampleQueue.length" class="ds-panel overflow-hidden">
          <div class="flex items-start justify-between gap-4 border-b border-[color:var(--ds-border)] px-5 py-4">
            <div>
              <p class="ds-kicker">Fila manual</p>
              <h2 class="ds-heading mt-1 text-base">{{ manualSampleQueue.length }} amostras preparadas</h2>
            </div>
            <button type="button" class="ds-button ds-button-secondary" :disabled="!manualSampleQueue.length || bulkForm.processing" @click="clearManualQueue">Limpar</button>
          </div>
          <div v-if="manualSampleQueue.length" class="divide-y divide-[color:var(--ds-border)]">
            <article v-for="(queueItem, index) in manualSampleQueue" :key="queueItem.temp_id" class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
              <div class="min-w-0">
                <p class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Amostra {{ index + 1 }}</p>
                <h3 class="mt-1 truncate text-sm font-bold text-[color:var(--ds-text)]">{{ queueItem.name }}</h3>
                <p class="mt-1 text-xs text-[color:var(--ds-text-muted)]">{{ queueItem.customer }} · {{ queueItem.product }} · {{ queueItem.lot }}</p>
              </div>
              <div class="flex items-center gap-1">
                <button type="button" class="ds-table-action" @click="useQueuedSampleAsBase(queueItem)">Usar como base</button>
                <button type="button" class="ds-table-action ds-table-action-danger" title="Remover" @click="removeManualQueueItem(queueItem.temp_id)">
                  <TrashIcon class="h-4 w-4" />
                  <span class="sr-only">Remover</span>
                </button>
              </div>
            </article>
          </div>
          <div v-else class="p-5">
            <div class="ds-empty-state px-5 py-8 text-center text-sm text-[color:var(--ds-text-muted)]">Preencha o formulário e adicione a primeira amostra à fila.</div>
          </div>
          <div class="flex flex-col gap-3 border-t border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <p v-if="bulkForm.errors.samples" class="ds-field-error">{{ bulkForm.errors.samples }}</p>
            <span v-else class="text-xs font-semibold text-[color:var(--ds-text-soft)]">A fila é submetida numa única operação controlada.</span>
            <button type="button" class="ds-button ds-button-primary" :disabled="!manualSampleQueue.length || bulkForm.processing" @click="submitManualBatch">
              <CheckCircleIcon class="h-4 w-4" />
              {{ bulkForm.processing ? 'A registar...' : 'Registar fila' }}
            </button>
          </div>
        </article>

        <aside class="ds-command-surface p-5">
          <p class="ds-kicker">Atalhos operacionais</p>
          <h2 class="ds-heading mt-2 text-base">Ações rápidas</h2>
          <div class="mt-4 grid gap-2">
            <button type="button" class="ds-button ds-button-primary w-full" @click="newSample">
              <PlusCircleIcon class="h-4 w-4" />
              Nova amostra
            </button>
            <button type="button" class="ds-button ds-button-secondary w-full" @click="startManualBatch">
              <QueueListIcon class="h-4 w-4" />
              Abrir fila manual
            </button>
            <button type="button" class="ds-button ds-button-secondary w-full" @click="newInternalQcSample('microbiology')">CQ microbiologia</button>
            <button type="button" class="ds-button ds-button-secondary w-full" @click="newInternalQcSample('chemistry')">CQ química</button>
          </div>
          <dl class="mt-5 divide-y divide-[color:var(--ds-border)] border-t border-[color:var(--ds-border)]">
            <div class="flex items-center justify-between gap-3 py-3">
              <dt class="text-xs font-semibold text-[color:var(--ds-text-muted)]">Hoje</dt>
              <dd class="text-sm font-bold text-[color:var(--ds-text)]">{{ stats.today_samples || 0 }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3 py-3">
              <dt class="text-xs font-semibold text-[color:var(--ds-text-muted)]">Esta semana</dt>
              <dd class="text-sm font-bold text-[color:var(--ds-text)]">{{ stats.week_samples || 0 }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3 py-3">
              <dt class="text-xs font-semibold text-[color:var(--ds-text-muted)]">CQ interno</dt>
              <dd class="text-sm font-bold text-[color:var(--ds-text)]">{{ stats.internal_qc_samples || 0 }}</dd>
            </div>
          </dl>
        </aside>
      </section>
    </template>

    <template v-else>
      <section class="grid gap-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(19rem,0.65fr)]">
        <div class="space-y-4">
          <section class="ds-table-shell">
            <div class="ds-table-summary flex-col items-stretch px-5 py-4 sm:flex-row sm:items-center">
              <div>
                <h2 class="ds-heading text-base">Descartes recentes</h2>
                <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">{{ filteredDiscards.length }} registos visíveis.</p>
              </div>
              <div class="sm:w-56">
                <label class="sr-only" for="discard-method-filter">Método de descarte</label>
                <BaseSelect id="discard-method-filter" v-model="discardMethodFilter" class="ds-field">
                  <option value="">Todos os métodos</option>
                  <option value="incineration">Incineração</option>
                  <option value="chemical_treatment">Tratamento químico</option>
                  <option value="autoclave">Autoclave</option>
                  <option value="landfill">Aterro</option>
                  <option value="recycling">Reciclagem</option>
                  <option value="return_to_client">Retorno ao cliente</option>
                </BaseSelect>
              </div>
            </div>

            <div v-if="filteredDiscards.length === 0" class="p-5">
              <div class="ds-empty-state px-5 py-10 text-center">
                <ArchiveBoxXMarkIcon class="mx-auto h-8 w-8 text-[color:var(--ds-text-soft)]" />
                <h3 class="ds-heading mt-3 text-sm">Nenhum descarte encontrado</h3>
                <p class="ds-copy mt-1 text-xs">{{ discardMethodFilter ? 'Altere o método selecionado.' : 'Ainda não existem registos de descarte.' }}</p>
              </div>
            </div>

            <div v-else class="overflow-x-auto">
              <DataTable class="min-w-full align-middle">
                <thead class="ds-table-head">
                  <tr>
                    <th class="ds-table-heading px-5 py-3 text-left">Amostra</th>
                    <th class="ds-table-heading px-5 py-3 text-left">Método</th>
                    <th class="ds-table-heading px-5 py-3 text-left">Quantidade</th>
                    <th class="ds-table-heading px-5 py-3 text-left">Data / responsável</th>
                    <th class="ds-table-heading px-5 py-3 text-right">Evidência</th>
                  </tr>
                </thead>
                <tbody class="ds-table-body divide-y divide-[color:var(--ds-border)]">
                  <tr v-for="discard in filteredDiscards" :key="discard.id" class="ds-table-row">
                    <td class="px-5 py-3">
                      <span class="block text-sm font-bold text-[color:var(--ds-text)]">{{ discard.sample?.name || 'Amostra desconhecida' }}</span>
                      <span class="mt-0.5 block font-mono text-xs text-[color:var(--ds-text-soft)]">{{ discard.sample?.code || 'Sem código' }}</span>
                    </td>
                    <td class="ds-table-cell px-5 py-3">{{ getDiscardMethodLabel(discard.discard_method) }}</td>
                    <td class="ds-table-cell px-5 py-3">{{ discard.qty }}</td>
                    <td class="ds-table-cell px-5 py-3">
                      <span class="block">{{ formatDate(discard.discarded_at) }}</span>
                      <span class="mt-0.5 block text-xs text-[color:var(--ds-text-soft)]">{{ discard.discarded_by?.name || 'Responsável desconhecido' }}</span>
                    </td>
                    <td class="px-5 py-3 text-right">
                      <button type="button" class="ds-table-action" title="Gerar certificado" @click="generateDiscardPdf(discard.id)">
                        <DocumentArrowDownIcon class="h-4 w-4" />
                        <span class="sr-only">Gerar certificado</span>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </DataTable>
            </div>
          </section>

          <section v-if="showDiscardForm" class="ds-panel overflow-hidden">
            <div class="border-b border-[color:var(--ds-border)] bg-rose-50/70 px-5 py-4 dark:bg-rose-500/10">
              <p class="text-xs font-bold uppercase text-rose-700 dark:text-rose-300">Operação irreversível</p>
              <h2 class="mt-1 text-lg font-bold text-rose-950 dark:text-rose-100">
                {{ selectedSample ? 'Descartar: ' + selectedSample.name : 'Registar descarte' }}
              </h2>
            </div>
            <form class="grid gap-4 p-5 md:grid-cols-2" @submit.prevent="submitDiscard">
              <div class="ds-field-group">
                <label class="ds-field-label" for="discard-sample">Amostra <span class="ds-field-required">*</span></label>
                <BaseSelect id="discard-sample" v-model="discardForm.sample_id" class="ds-field" :aria-invalid="Boolean(discardForm.errors.sample_id)" @change="onSampleSelect">
                  <option value="">Selecione uma amostra</option>
                  <option v-for="sample in discardableSamples" :key="sample.id" :value="sample.id">{{ sample.code }} - {{ sample.name }} ({{ getStatusLabel(sample.status) }})</option>
                </BaseSelect>
                <p v-if="discardForm.errors.sample_id" class="ds-field-error">{{ discardForm.errors.sample_id }}</p>
              </div>
              <div class="ds-field-group">
                <label class="ds-field-label" for="discard-method">Método <span class="ds-field-required">*</span></label>
                <BaseSelect id="discard-method" v-model="discardForm.discard_method" class="ds-field" :aria-invalid="Boolean(discardForm.errors.discard_method)">
                  <option value="">Selecione o método</option>
                  <option value="incineration">Incineração</option>
                  <option value="chemical_treatment">Tratamento químico</option>
                  <option value="autoclave">Autoclave</option>
                  <option value="landfill">Aterro</option>
                  <option value="recycling">Reciclagem</option>
                  <option value="return_to_client">Retorno ao cliente</option>
                </BaseSelect>
                <p v-if="discardForm.errors.discard_method" class="ds-field-error">{{ discardForm.errors.discard_method }}</p>
              </div>
              <div class="ds-field-group">
                <label class="ds-field-label" for="discard-quantity">Quantidade <span class="ds-field-required">*</span></label>
                <BaseInput id="discard-quantity" v-model="discardForm.qty" type="text" class="ds-field" :aria-invalid="Boolean(discardForm.errors.qty)" placeholder="250 g, 500 ml, 1 unidade" />
                <p v-if="discardForm.errors.qty" class="ds-field-error">{{ discardForm.errors.qty }}</p>
              </div>
              <div class="ds-field-group">
                <label class="ds-field-label" for="discarded-at">Data do descarte</label>
                <DateTimePicker id="discarded-at" v-model="discardForm.discarded_at" type="datetime-local" class="ds-field" />
              </div>
              <div class="flex items-center justify-end gap-2 border-t border-[color:var(--ds-border)] pt-4 md:col-span-2">
                <button type="button" class="ds-button ds-button-secondary" @click="cancelDiscard">Cancelar</button>
                <button type="submit" class="ds-button ds-button-danger" :disabled="discardForm.processing || !isDiscardFormValid">
                  <TrashIcon class="h-4 w-4" />
                  {{ discardForm.processing ? 'A processar...' : 'Confirmar descarte' }}
                </button>
              </div>
            </form>
          </section>
        </div>

        <aside class="space-y-4">
          <section class="ds-command-surface p-5">
            <p class="ds-kicker">Operações de descarte</p>
            <h2 class="ds-heading mt-2 text-base">Controlo e evidência</h2>
            <div class="mt-4 grid gap-2">
              <button type="button" :class="['ds-button w-full', showDiscardForm ? 'ds-button-secondary' : 'ds-button-danger']" @click="showDiscardForm = !showDiscardForm">
                <PlusCircleIcon class="h-4 w-4" />
                {{ showDiscardForm ? 'Fechar formulário' : 'Novo descarte' }}
              </button>
              <button type="button" class="ds-button ds-button-secondary w-full" @click="exportDiscards">
                <ArrowDownTrayIcon class="h-4 w-4" />
                Exportar registos
              </button>
            </div>
            <dl class="mt-5 divide-y divide-[color:var(--ds-border)] border-t border-[color:var(--ds-border)]">
              <div class="flex items-center justify-between py-3">
                <dt class="text-xs font-semibold text-[color:var(--ds-text-muted)]">Total descartado</dt>
                <dd class="text-sm font-bold text-rose-700 dark:text-rose-300">{{ stats.total_discarded || 0 }}</dd>
              </div>
              <div class="flex items-center justify-between py-3">
                <dt class="text-xs font-semibold text-[color:var(--ds-text-muted)]">Este mês</dt>
                <dd class="text-sm font-bold text-[color:var(--ds-text)]">{{ stats.discarded_this_month || 0 }}</dd>
              </div>
              <div class="flex items-center justify-between py-3">
                <dt class="text-xs font-semibold text-[color:var(--ds-text-muted)]">Incineração</dt>
                <dd class="text-sm font-bold text-[color:var(--ds-text)]">{{ getDiscardCountByMethod('incineration') }}</dd>
              </div>
              <div class="flex items-center justify-between py-3">
                <dt class="text-xs font-semibold text-[color:var(--ds-text-muted)]">Autoclave</dt>
                <dd class="text-sm font-bold text-[color:var(--ds-text)]">{{ getDiscardCountByMethod('autoclave') }}</dd>
              </div>
            </dl>
          </section>

          <section v-if="selectedSample && showDiscardForm" class="ds-card p-5">
            <div class="flex items-center gap-2">
              <InformationCircleIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
              <h2 class="ds-heading text-base">Amostra selecionada</h2>
            </div>
            <div class="mt-4 border-l-4 border-primary-500 pl-4">
              <h3 class="text-sm font-bold text-[color:var(--ds-text)]">{{ selectedSample.name }}</h3>
              <p class="mt-1 font-mono text-xs text-[color:var(--ds-text-soft)]">{{ selectedSample.code }}</p>
            </div>
            <dl class="mt-4 divide-y divide-[color:var(--ds-border)]">
              <div class="flex items-center justify-between gap-3 py-3">
                <dt class="text-xs font-semibold text-[color:var(--ds-text-muted)]">Estado</dt>
                <dd><span :class="sampleStatusBadgeClass(selectedSample.status)">{{ getStatusLabel(selectedSample.status) }}</span></dd>
              </div>
              <div class="flex items-center justify-between gap-3 py-3">
                <dt class="text-xs font-semibold text-[color:var(--ds-text-muted)]">Tipo</dt>
                <dd class="text-right text-xs font-bold text-[color:var(--ds-text)]">{{ getSampleTypeLabel(selectedSample.sample_type) }}</dd>
              </div>
              <div class="flex items-center justify-between gap-3 py-3">
                <dt class="text-xs font-semibold text-[color:var(--ds-text-muted)]">Receção</dt>
                <dd class="text-right text-xs font-bold text-[color:var(--ds-text)]">{{ formatDate(selectedSample.received_at) }}</dd>
              </div>
              <div v-if="selectedSample.analysis_start_date" class="flex items-center justify-between gap-3 py-3">
                <dt class="text-xs font-semibold text-[color:var(--ds-text-muted)]">Início da análise</dt>
                <dd class="text-right text-xs font-bold text-[color:var(--ds-text)]">{{ formatDate(selectedSample.analysis_start_date) }}</dd>
              </div>
              <div v-if="selectedSample.analysis_end_date" class="flex items-center justify-between gap-3 py-3">
                <dt class="text-xs font-semibold text-[color:var(--ds-text-muted)]">Fim da análise</dt>
                <dd class="text-right text-xs font-bold text-[color:var(--ds-text)]">{{ formatDate(selectedSample.analysis_end_date) }}</dd>
              </div>
            </dl>
          </section>

          <section class="border-l-4 border-rose-500 bg-rose-50 p-4 dark:bg-rose-500/10">
            <div class="flex items-start gap-3">
              <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 shrink-0 text-rose-700 dark:text-rose-300" />
              <div>
                <h2 class="text-sm font-bold text-rose-950 dark:text-rose-100">Verificação obrigatória</h2>
                <p class="mt-1 text-xs leading-5 text-rose-800 dark:text-rose-200">Confirme o código, método e quantidade. O descarte é irreversível.</p>
              </div>
            </div>
          </section>
        </aside>
      </section>
    </template>

    <footer class="flex flex-col gap-3 border-t border-[color:var(--ds-border)] pt-4 sm:flex-row sm:items-center sm:justify-between">
      <p class="text-xs font-semibold text-[color:var(--ds-text-soft)]">Última atualização: {{ formatDate(new Date()) }}</p>
      <p class="text-xs font-semibold text-[color:var(--ds-text-soft)]">Registos rastreáveis por código, lote e cadeia de custódia.</p>
    </footer>

    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-y-2 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-2 opacity-0"
    >
      <div
        v-if="flash.message"
        :class="[
          'fixed bottom-4 right-4 z-50 max-w-md border p-4 shadow-xl',
          flash.type === 'error'
            ? 'border-rose-300 bg-rose-50 text-rose-950 dark:border-rose-500/40 dark:bg-rose-950 dark:text-rose-100'
            : flash.type === 'warning'
              ? 'border-amber-300 bg-amber-50 text-amber-950 dark:border-amber-500/40 dark:bg-amber-950 dark:text-amber-100'
              : 'border-emerald-300 bg-emerald-50 text-emerald-950 dark:border-emerald-500/40 dark:bg-emerald-950 dark:text-emerald-100'
        ]"
      >
        <div class="flex items-start gap-3">
          <CheckCircleIcon v-if="flash.type === 'success'" class="h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-300" />
          <ExclamationTriangleIcon v-else class="h-5 w-5 shrink-0 text-rose-600 dark:text-rose-300" />
          <div>
            <p class="text-sm font-bold">{{ flash.message }}</p>
            <div v-if="flash.sample_id || flash.discard_id" class="mt-2 flex flex-wrap gap-2">
              <button v-if="flash.sample_id" type="button" class="text-xs font-bold underline" @click="generateEntryPdf(flash.sample_id)">PDF da entrada</button>
              <button v-if="flash.discard_id" type="button" class="text-xs font-bold underline" @click="generateDiscardPdf(flash.discard_id)">Certificado de descarte</button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>
<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import { 
  BeakerIcon,
  TagIcon,
  QrCodeIcon,
  BuildingOfficeIcon,
  QueueListIcon,
  ClipboardDocumentListIcon,
  CheckCircleIcon,
  ArrowPathIcon,
  TrashIcon,
  ArchiveBoxXMarkIcon,
  InformationCircleIcon,
  ExclamationTriangleIcon,
  ArrowDownTrayIcon,
  CloudArrowUpIcon,
  ScaleIcon,
  PlusCircleIcon,
  EyeIcon,
  PencilSquareIcon,
  DocumentArrowDownIcon
} from '@heroicons/vue/24/outline'

// Obter props da página
const page = usePage()
const flash = computed(() => page.props.flash || {})

// Estado reativo
const activeTab = ref('entry')
const selectedSample = ref(null)
const editingSample = ref(null)
const manualBatchMode = ref(false)
const manualSampleQueue = ref([])
const showDiscardForm = ref(false)
const searchQuery = ref('')
const statusFilter = ref('')
const discardMethodFilter = ref('')
const currentPage = ref(1)
const importFileInput = ref(null)
const itemsPerPage = 10

// Dados do controlador
const stats = computed(() => page.props.stats || {})
const charts = computed(() => page.props.charts || {})
const samples = computed(() => page.props.samples || [])
const discardableSamples = computed(() => page.props.discardableSamples || [])
const recentDiscards = computed(() => page.props.recentDiscards || [])
const customers = computed(() => page.props.customers || [])
const acceptedProposals = computed(() => page.props.acceptedProposals || [])
const portalAnalysisRequests = computed(() => page.props.portalAnalysisRequests || [])
const products = computed(() => page.props.products || [])
const profiles = computed(() => page.props.profiles || [])
const matrixes = computed(() => page.props.matrixes || [])
const labs = computed(() => page.props.labs || [])
const departments = computed(() => page.props.departments || [])
const warehouses = computed(() => page.props.warehouses || [])
const packagingCategories = computed(() => page.props.packagingCategories || [])
const internalQualityControlPath = computed(() => page.props.internalQualityControlPath || {})

const intakeTrendChartSeries = computed(() => charts.value.intake_trend?.series || [])
const intakeTrendTotal = computed(() => intakeTrendChartSeries.value.reduce(
  (total, series) => total + (series?.data || []).reduce((sum, value) => sum + value, 0),
  0,
))

const lifecycleStatusChartSeries = computed(() => charts.value.lifecycle_status?.series || [])
const lifecycleStatusTotal = computed(() => lifecycleStatusChartSeries.value.reduce((sum, value) => sum + value, 0))

const retentionPressureChartSeries = computed(() => [
  {
    name: 'Amostras',
    data: charts.value.retention_pressure?.series || [],
  },
])
const retentionPressureAlertCount = computed(() => {
  const series = charts.value.retention_pressure?.series || []

  return (series[1] || 0) + (series[2] || 0)
})

const sampleEntryCommandCards = computed(() => [
  {
    label: 'Em validação',
    value: stats.value.pending_analysis || 0,
    hint: 'Amostras recebidas que ainda precisam de validação técnica.',
  },
  {
    label: 'Fluxo activo',
    value: stats.value.in_progress || 0,
    hint: 'Amostras que já alimentam colheita, análise ou verificação.',
  },
  {
    label: 'CQ interno',
    value: stats.value.internal_qc_samples || 0,
    hint: 'Matérias-primas e controlos internos processados pelo fluxo normal.',
  },
])

const technicalIdentityFields = [
  { key: 'lot', label: 'Lote', placeholder: 'Lote / batch do cliente' },
  { key: 'origin', label: 'Origem', placeholder: 'Fornecedor, país, linha ou unidade' },
  { key: 'location', label: 'Local de colheita', placeholder: 'Local físico ou ponto de amostragem' },
  { key: 'sampling_plan_ref', label: 'Plano de amostragem', placeholder: 'Plano, norma ou referência' },
  { key: 'quantity', label: 'Quantidade recebida', placeholder: '2 kg, 500 ml' },
  { key: 'collected_qty', label: 'Quantidade colhida', placeholder: '3 frascos' },
  { key: 'temperature_value', label: 'Temperatura', placeholder: '4 °C, ambiente' },
  { key: 'container_no', label: 'Contentor', placeholder: 'Número do contentor' },
  { key: 'du_no', label: 'DU', placeholder: 'Documento único' },
  { key: 'term_no', label: 'Termo', placeholder: 'Número do termo' },
  { key: 'bl', label: 'BL', placeholder: 'Bill of lading' },
  { key: 'production_date', label: 'Produção', type: 'date' },
  { key: 'expiry_date', label: 'Validade', type: 'date' },
]

const isDarkMode = ref(false)
let themeObserver = null

const syncDarkMode = () => {
  if (typeof document === 'undefined') {
    return
  }

  isDarkMode.value = document.documentElement.classList.contains('dark')
}

onMounted(() => {
  syncDarkMode()

  if (typeof MutationObserver !== 'undefined') {
    themeObserver = new MutationObserver(syncDarkMode)
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
  }
})

onUnmounted(() => {
  themeObserver?.disconnect()
})

const chartThemeOptions = computed(() => ({
  theme: {
    mode: isDarkMode.value ? 'dark' : 'light',
  },
  chart: {
    background: 'transparent',
    foreColor: isDarkMode.value ? '#cbd5e1' : '#475569',
  },
  grid: {
    borderColor: isDarkMode.value ? '#1e293b' : '#e2e8f0',
    strokeDashArray: 4,
  },
  tooltip: {
    theme: isDarkMode.value ? 'dark' : 'light',
  },
}))

const intakeTrendChartOptions = computed(() => ({
  ...chartThemeOptions.value,
  chart: {
    ...chartThemeOptions.value.chart,
    toolbar: { show: false },
    zoom: { enabled: false },
    fontFamily: 'inherit',
  },
  colors: ['#0f766e'],
  dataLabels: { enabled: false },
  stroke: {
    curve: 'smooth',
    width: 3,
  },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.28,
      opacityTo: 0.04,
      stops: [0, 95, 100],
    },
  },
  grid: {
    ...chartThemeOptions.value.grid,
  },
  xaxis: {
    categories: charts.value.intake_trend?.categories || [],
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: {
      style: { colors: isDarkMode.value ? '#94a3b8' : '#64748b' },
    },
  },
  yaxis: {
    min: 0,
    forceNiceScale: true,
    labels: {
      style: { colors: isDarkMode.value ? '#94a3b8' : '#64748b' },
    },
  },
  tooltip: {
    ...chartThemeOptions.value.tooltip,
    y: {
      formatter: (value) => `${value} amostra${value === 1 ? '' : 's'}`,
    },
  },
  legend: { show: false },
}))

const lifecycleStatusChartOptions = computed(() => ({
  ...chartThemeOptions.value,
  chart: {
    ...chartThemeOptions.value.chart,
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  labels: charts.value.lifecycle_status?.labels || [],
  colors: ['#d97706', '#0f766e', '#64748b', '#059669', '#e11d48'],
  stroke: {
    colors: [isDarkMode.value ? '#0f172a' : '#ffffff'],
  },
  legend: {
    position: 'bottom',
    labels: { colors: isDarkMode.value ? '#cbd5e1' : '#334155' },
  },
  tooltip: chartThemeOptions.value.tooltip,
  dataLabels: {
    enabled: true,
    formatter: (value) => `${Math.round(value)}%`,
  },
  plotOptions: {
    pie: {
      donut: {
        size: '68%',
        labels: {
          show: true,
          total: {
            show: true,
            label: 'Amostras',
            formatter: () => `${lifecycleStatusTotal.value}`,
          },
        },
      },
    },
  },
}))

const retentionPressureChartOptions = computed(() => ({
  ...chartThemeOptions.value,
  chart: {
    ...chartThemeOptions.value.chart,
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  colors: ['#0f766e'],
  dataLabels: { enabled: false },
  grid: {
    ...chartThemeOptions.value.grid,
  },
  xaxis: {
    categories: charts.value.retention_pressure?.labels || [],
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: {
      style: { colors: isDarkMode.value ? '#94a3b8' : '#64748b' },
    },
  },
  yaxis: {
    min: 0,
    forceNiceScale: true,
    labels: {
      style: { colors: isDarkMode.value ? '#94a3b8' : '#64748b' },
    },
  },
  plotOptions: {
    bar: {
      borderRadius: 10,
      columnWidth: '48%',
      distributed: true,
    },
  },
  colors: ['#0f766e', '#f59e0b', '#ef4444', '#334155'],
  legend: { show: false },
}))

function defaultClientSubmittedInfo(overrides = {}) {
  return {
    request_origin: 'client',
    collection_type: page.props.entryWorkflowDefaults?.collection_type || 'direct',
    collection_location: '',
    vehicle_reference: '',
    product_id: null,
    matrix_id: null,
    packaging_id: null,
    requested_profile_ids: [],
    conditioning_status: null,
    quality_control_purpose: null,
    analysis_discipline: null,
    material_category: null,
    qc_decision: null,
    lot: '',
    batch: '',
    origin: '',
    location: '',
    quantity: '',
    collected_qty: '',
    production_date: '',
    expiry_date: '',
    temperature_value: '',
    container_no: '',
    du_no: '',
    term_no: '',
    bl: '',
    sampling_plan_ref: '',
    supplier_name: '',
    packaging_condition: '',
    temperature_condition: '',
    integrity_observations: '',
    chain_of_custody_notes: '',
    ...overrides,
  }
}

// Formulário de entrada de amostra
const form = useForm({
  name: '',
  code: '',
  sample_type: '',
  proposal_id: '',
  portal_request_id: '',
  customer_request_id: '',
  customer_id: '',
  lab_id: '',
  department_id: '',
  warehouse_id: '',
  packaging_id: '',
  received_at: '',
  requested_services: '',
  obs: '',
  status: 'POR_INICIAR',
  analysis_start_date: '',
  analysis_end_date: '',
  collected_by_lab: false,
  collected_at: '',
  client_submitted_info: defaultClientSubmittedInfo(),
})

// Formulário de descarte
const discardForm = useForm({
  sample_id: '',
  discard_method: '',
  qty: '',
  discarded_at: '',
  lab_id: '',
  department_id: ''
})

const importForm = useForm({
  file: null,
})

const bulkForm = useForm({
  samples: [],
})

// Propriedades computadas
const isFormValid = computed(() => {
  return form.name && 
         form.sample_type && 
         form.customer_id && 
         form.lab_id && 
         form.department_id && 
         form.warehouse_id
})

const isDiscardFormValid = computed(() => {
  return discardForm.sample_id && 
         discardForm.discard_method && 
         discardForm.qty
})

const filteredSampleResults = computed(() => {
  let filtered = samples.value
  
  // Filtrar por busca
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(sample => 
      sample.name.toLowerCase().includes(query) ||
      sample.code.toLowerCase().includes(query) ||
      (sample.customer?.name && sample.customer.name.toLowerCase().includes(query)) ||
      (sample.client_submitted_info?.product_name && sample.client_submitted_info.product_name.toLowerCase().includes(query)) ||
      (sample.client_submitted_info?.lot && sample.client_submitted_info.lot.toLowerCase().includes(query)) ||
      (sample.client_submitted_info?.origin && sample.client_submitted_info.origin.toLowerCase().includes(query))
    )
  }
  
  // Filtrar por status
  if (statusFilter.value) {
    filtered = filtered.filter(sample => sample.status === statusFilter.value)
  }
  
  return filtered
})

const filteredSamples = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return filteredSampleResults.value.slice(start, end)
})

const filteredDiscards = computed(() => {
  let filtered = recentDiscards.value
  
  // Filtrar por método
  if (discardMethodFilter.value) {
    filtered = filtered.filter(discard => discard.discard_method === discardMethodFilter.value)
  }
  
  return filtered
})

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(filteredSampleResults.value.length / itemsPerPage))
})

const selectedProduct = computed(() => products.value.find((product) => product.id === Number(form.client_submitted_info?.product_id)) || null)

const availableProfiles = computed(() => {
  const productProfiles = selectedProduct.value?.profiles?.length
    ? selectedProduct.value.profiles
    : profiles.value

  if (!form.department_id) {
    return productProfiles
  }

  return productProfiles.filter((profile) => Number(profile.department_id) === Number(form.department_id))
})

const selectedProfileIds = computed(() => {
  return (form.client_submitted_info?.requested_profile_ids || []).map((id) => Number(id))
})

const selectedProfileSummaries = computed(() => {
  const selectedProfiles = selectedProfileIds.value.length
    ? availableProfiles.value.filter((profile) => selectedProfileIds.value.includes(Number(profile.id)))
    : availableProfiles.value

  return selectedProfiles
})

const requiredParameterPreview = computed(() => {
  const parameterMap = new Map()

  selectedProfileSummaries.value.forEach((profile) => {
    ;(profile.parameters || []).forEach((parameter) => {
      const existing = parameterMap.get(parameter.id)

      if (existing) {
        existing.profiles = [...new Set([...existing.profiles, profile.name])]
        return
      }

      parameterMap.set(parameter.id, {
        id: parameter.id,
        name: parameter.name,
        code: parameter.code,
        profiles: [profile.name],
      })
    })
  })

  return Array.from(parameterMap.values()).sort((left, right) => left.name.localeCompare(right.name))
})

const isInternalRequest = computed(() => form.client_submitted_info?.request_origin === 'internal')
const isInternalRawMaterialQc = computed(() => isInternalRequest.value && ['MATERIA_PRIMA', 'RAW_MATERIAL'].includes(form.sample_type))

// Métodos auxiliares
const getStatusLabel = (status) => {
  const labels = {
    'POR_INICIAR': 'Por Iniciar',
    'EN_PROGRESO': 'Em Progresso',
    'COMPLETADO': 'Completado',
    'CANCELADO': 'Cancelado',
    'EN_PAUSA': 'Em Pausa'
  }
  return labels[status] || status
}

const sampleStatusBadgeClass = (status) => {
  const classes = {
    COMPLETADO: 'border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-400/30 dark:bg-emerald-500/10 dark:text-emerald-200',
    EN_PROGRESO: 'border-blue-300 bg-blue-50 text-blue-800 dark:border-blue-400/30 dark:bg-blue-500/10 dark:text-blue-200',
    POR_INICIAR: 'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-400/30 dark:bg-amber-500/10 dark:text-amber-200',
    CANCELADO: 'border-rose-300 bg-rose-50 text-rose-800 dark:border-rose-400/30 dark:bg-rose-500/10 dark:text-rose-200',
    EN_PAUSA: 'border-zinc-300 bg-zinc-50 text-zinc-800 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200',
  }

  return ['ds-chip', classes[status] || classes.EN_PAUSA]
}

const getSampleTypeLabel = (type) => {
  const labels = {
    'ROTINA': 'Rotina',
    'MATERIA_PRIMA': 'Matéria-prima',
    'PRODUTO_ACABADO': 'Produto acabado',
    'ESTABILIDADE': 'Estabilidade',
    'CONTRAPROVA': 'Contraprova',
    'COUNTER_ANALYSIS': 'Contra-análise',
    'INTERLABORATORIAL': 'Interlaboratorial',
    'RETENCAO': 'Retenção',
  }
  return labels[type] || type
}

const getProposalLabel = (proposal) => {
  if (!proposal) return 'Sem proposta'
  return `${proposal.proposal_no} - ${proposal.customer || 'Cliente'}`
}

const selectedSampleProductName = (sample) => {
  const productId = Number(sample?.client_submitted_info?.product_id)
  const product = products.value.find((candidate) => Number(candidate.id) === productId)

  return product?.name || 'Produto por confirmar'
}

const prefillFromPortalRequest = (request) => {
  if (!request) return

  const details = request.details || {}
  const batchSample = request.next_sample_row || request.remaining_sample_rows?.[0] || request.sample_rows?.[0] || null

  newSample()

  form.portal_request_id = request.id
  form.customer_request_id = request.id
  form.customer_id = request.customer_id || ''
  form.warehouse_id = request.warehouse_id || ''
  form.name = batchSample?.sample_name || details.sample_name || details.product_name || request.title || ''
  form.sample_type = form.sample_type || 'ROTINA'
  form.requested_services = (request.requested_profile_names || []).join(', ')
  form.obs = [
    request.description,
    batchSample?.product_name ? `Produto declarado: ${batchSample.product_name}` : null,
    batchSample?.matrix ? `Matriz declarada: ${batchSample.matrix}` : details.matrix ? `Matriz declarada: ${details.matrix}` : null,
    batchSample?.lot ? `Lote declarado: ${batchSample.lot}` : details.lot ? `Lote declarado: ${details.lot}` : null,
    batchSample?.notes ? `Notas do cliente: ${batchSample.notes}` : details.notes ? `Notas do cliente: ${details.notes}` : null,
  ]
    .filter(Boolean)
    .join('\n')
  form.client_submitted_info = defaultClientSubmittedInfo({
    request_origin: 'client',
    request_reference: request.reference,
    request_title: request.title,
    preferred_date: request.preferred_date,
    product_id: details.product_id || batchSample?.product_id || null,
    matrix_id: details.matrix_id || batchSample?.matrix_id || null,
    packaging_id: details.packaging_id || batchSample?.packaging_id || null,
    requested_profile_ids: details.requested_profiles || [],
    conditioning_status: null,
    packaging_condition: '',
    temperature_condition: '',
    integrity_observations: '',
    chain_of_custody_notes: '',
    batch_sample_index: batchSample?.batch_index ?? null,
    batch_sample: batchSample,
    details,
  })
}

const getDiscardMethodLabel = (method) => {
  const labels = {
    'incineration': 'Incineração',
    'chemical_treatment': 'Tratamento Químico',
    'autoclave': 'Autoclave',
    'landfill': 'Aterro',
    'recycling': 'Reciclagem',
    'return_to_client': 'Retorno ao Cliente'
  }
  return labels[method] || method
}

const getDiscardCountByMethod = (method) => {
  return recentDiscards.value.filter(d => d.discard_method === method).length
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('pt-Pt') + ' ' + date.toLocaleTimeString('pt-Pt', { hour: '2-digit', minute: '2-digit' })
  } catch (e) {
    return 'Data inválida'
  }
}

// Métodos principais
const newSample = () => {
  editingSample.value = {}
  form.reset()
  form.status = 'POR_INICIAR'
  form.collected_by_lab = false
  form.client_submitted_info = defaultClientSubmittedInfo()
  
  // Definir data de recebimento padrão
  const now = new Date()
  const timezoneOffset = now.getTimezoneOffset() * 60000
  const localISOTime = new Date(now - timezoneOffset).toISOString().slice(0, 16)
  form.received_at = localISOTime
}

const startManualBatch = () => {
  manualBatchMode.value = true
  newSample()
}

const samplePayloadFromForm = () => {
  const payload = JSON.parse(JSON.stringify(form.data()))

  payload.code = ''
  payload.client_submitted_info = defaultClientSubmittedInfo({
    ...(payload.client_submitted_info || {}),
    manual_entry: true,
  })

  return payload
}

const customerNameForPayload = (payload) => {
  return customers.value.find((customer) => Number(customer.id) === Number(payload.customer_id))?.name || 'Cliente por confirmar'
}

const productNameForPayload = (payload) => {
  return products.value.find((product) => Number(product.id) === Number(payload.client_submitted_info?.product_id))?.name || 'Produto por confirmar'
}

const addCurrentSampleToManualQueue = () => {
  if (!isFormValid.value) {
    return
  }

  const payload = samplePayloadFromForm()

  manualBatchMode.value = true
  manualSampleQueue.value.push({
    temp_id: `manual-${Date.now()}-${Math.random().toString(16).slice(2)}`,
    name: payload.name,
    customer: customerNameForPayload(payload),
    product: productNameForPayload(payload),
    lot: payload.client_submitted_info?.lot || 'Sem lote',
    payload,
  })

  newSample()
}

const applySamplePayloadToForm = (payload) => {
  form.reset()
  form.clearErrors()

  Object.keys(form.data()).forEach((key) => {
    if (payload[key] !== undefined) {
      form[key] = payload[key]
    }
  })

  form.client_submitted_info = defaultClientSubmittedInfo(payload.client_submitted_info || {})
  editingSample.value = {}
}

const useQueuedSampleAsBase = (queueItem) => {
  manualBatchMode.value = true
  applySamplePayloadToForm(queueItem.payload)
}

const removeManualQueueItem = (tempId) => {
  manualSampleQueue.value = manualSampleQueue.value.filter((queueItem) => queueItem.temp_id !== tempId)
}

const clearManualQueue = () => {
  manualSampleQueue.value = []
}

const newInternalQcSample = (discipline = 'chemistry') => {
  newSample()

  const preset = internalQualityControlPath.value?.presets?.[discipline] || {}
  const disciplineLabel = discipline === 'microbiology' ? 'Microbiologia' : 'Química / físico-química'

  form.sample_type = internalQualityControlPath.value.sample_type || 'MATERIA_PRIMA'
  form.department_id = preset.department_id || form.department_id
  form.requested_services = preset.requested_services || `Controlo interno de matéria-prima - ${disciplineLabel}`
  form.obs = [
    'Procedimento interno de controlo de qualidade de matéria-prima.',
    'Selecionar produto, matriz e perfis para gerar automaticamente o fluxo normal de análise.',
  ].join('\n')
  form.client_submitted_info = defaultClientSubmittedInfo({
    request_origin: 'internal',
    quality_control_purpose: 'raw_material_release',
    analysis_discipline: preset.discipline || discipline,
    material_category: 'raw_material',
    qc_decision: 'hold_until_release',
  })
}

const editSample = (sample) => {
  editingSample.value = sample

  form.reset()
  form.clearErrors()
  
  // Preencher formulário com dados da amostra
  Object.keys(form.data()).forEach(key => {
    if (sample[key] !== undefined && sample[key] !== null) {
      if (key.includes('_at') || key.includes('_date')) {
        const date = new Date(sample[key])
        if (!isNaN(date.getTime())) {
          const timezoneOffset = date.getTimezoneOffset() * 60000
          const localISOTime = new Date(date - timezoneOffset).toISOString().slice(0, 16)
          form[key] = localISOTime
        }
      } else {
        form[key] = sample[key]
      }
    }
  })

  if (!form.client_submitted_info || typeof form.client_submitted_info !== 'object') {
    form.client_submitted_info = defaultClientSubmittedInfo()
  } else {
    form.client_submitted_info = defaultClientSubmittedInfo(form.client_submitted_info)
  }
}

const viewSample = (sampleId) => {
  router.visit(route('vap_samples.show', sampleId))
}

const prepareForDiscard = (sample) => {
  activeTab.value = 'discard'
  showDiscardForm.value = true
  selectedSample.value = sample
  discardForm.sample_id = sample.id
  discardForm.lab_id = sample.lab_id
  discardForm.department_id = sample.department_id
  
  // Definir data de descarte padrão
  const now = new Date()
  const timezoneOffset = now.getTimezoneOffset() * 60000
  const localISOTime = new Date(now - timezoneOffset).toISOString().slice(0, 16)
  discardForm.discarded_at = localISOTime
}

const cancelEdit = () => {
  editingSample.value = null
  form.reset()
  form.clearErrors()
}

const submitManualBatch = () => {
  if (!manualSampleQueue.value.length) {
    return
  }

  bulkForm.samples = manualSampleQueue.value.map((queueItem) => queueItem.payload)
  bulkForm.post(route('vap_samples.samples.bulk-store'), {
    preserveScroll: true,
    onSuccess: () => {
      manualSampleQueue.value = []
      manualBatchMode.value = false
      editingSample.value = null
      bulkForm.reset()
      form.reset()
    },
  })
}

const cancelDiscard = () => {
  showDiscardForm.value = false
  selectedSample.value = null
  discardForm.reset()
  discardForm.clearErrors()
}

const submitSample = () => {
  if (editingSample.value?.id) {
    form.put(route('vap_samples.samples.update', editingSample.value.id), {
      preserveScroll: true,
      onSuccess: () => {
        editingSample.value = null
        form.reset()
      }
    })
  } else {
    form.post(route('vap_samples.samples.store'), {
      preserveScroll: true,
      onSuccess: () => {
        editingSample.value = null
        form.reset()
      }
    })
  }
}

const updateSample = () => {
  form.put(route('vap_samples.samples.update', editingSample.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      editingSample.value = null
      form.reset()
    }
  })
}

const submitDiscard = () => {
  discardForm.post(route('vap_samples.discards.store'), {
    preserveScroll: true,
    onSuccess: () => {
      showDiscardForm.value = false
      selectedSample.value = null
      discardForm.reset()
    }
  })
}

const onSampleSelect = () => {
  const sample = discardableSamples.value.find(s => s.id == discardForm.sample_id)
  if (sample) {
    selectedSample.value = sample
    discardForm.lab_id = sample.lab_id
    discardForm.department_id = sample.department_id
  }
}

const exportData = () => {
  window.open(route('vap_samples.samples.export'), '_blank')
}

const downloadImportTemplate = () => {
  window.open(route('vap_samples.samples.import-template'), '_blank')
}

const chooseImportFile = () => {
  importFileInput.value?.click()
}

const onImportFileChange = (event) => {
  const file = event.target.files?.[0] || null

  if (!file) {
    return
  }

  importForm.file = file
  importForm.post(route('vap_samples.samples.import'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      importForm.reset()
      event.target.value = ''
    },
    onError: () => {
      event.target.value = ''
    },
  })
}

const exportDiscards = () => {
  window.open(route('vap_samples.discards.export'), '_blank')
}

const refreshData = () => {
  router.reload({ preserveScroll: true })
}

const generateEntryPdf = (sampleId) => {
  window.open(route('vap_samples.samples.pdf', sampleId), '_blank')
}

const generateDiscardPdf = (discardId) => {
  window.open(route('vap_samples.discards.pdf', discardId), '_blank')
}

// Observadores
watch(() => activeTab.value, () => {
  currentPage.value = 1
  searchQuery.value = ''
  statusFilter.value = ''
  discardMethodFilter.value = ''
})

watch([searchQuery, statusFilter], () => {
  currentPage.value = 1
})

// Auto-gerar código quando tipo de amostra é selecionado
watch(() => form.sample_type, (newType) => {
  if (newType && !form.code && !editingSample.value?.id) {
    const prefix = newType.substring(0, 3).toUpperCase()
    const year = new Date().getFullYear()
    const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0')
    form.code = `SMP-${year}-${prefix}-${random}`
  }
})

watch(() => form.client_submitted_info?.request_origin, (origin) => {
  if (origin === 'internal') {
    form.proposal_id = ''
    form.portal_request_id = ''
    form.customer_request_id = ''
  }
})

watch(() => form.client_submitted_info?.product_id, (productId) => {
  const product = products.value.find((item) => item.id === Number(productId))

  if (product) {
    form.client_submitted_info.matrix_id = product.matrix_id || null
  }

  const allowedProfileIds = new Set((product?.profiles || []).map((profile) => Number(profile.id)))

  if (allowedProfileIds.size > 0) {
    form.client_submitted_info.requested_profile_ids = (form.client_submitted_info.requested_profile_ids || [])
      .map((id) => Number(id))
      .filter((id) => allowedProfileIds.has(id))
  }
})

watch(() => form.department_id, (departmentId) => {
  if (!departmentId) {
    return
  }

  const allowedProfileIds = new Set(
    availableProfiles.value.map((profile) => Number(profile.id))
  )

  form.client_submitted_info.requested_profile_ids = (form.client_submitted_info.requested_profile_ids || [])
    .map((id) => Number(id))
    .filter((id) => allowedProfileIds.has(id))
})

// Definir datas padrão
watch(() => form, () => {
  if (!form.received_at && !editingSample.value?.id) {
    const now = new Date()
    const timezoneOffset = now.getTimezoneOffset() * 60000
    const localISOTime = new Date(now - timezoneOffset).toISOString().slice(0, 16)
    form.received_at = localISOTime
  }
}, { immediate: true, deep: true })

watch(() => discardForm, () => {
  if (!discardForm.discarded_at) {
    const now = new Date()
    const timezoneOffset = now.getTimezoneOffset() * 60000
    const localISOTime = new Date(now - timezoneOffset).toISOString().slice(0, 16)
    discardForm.discarded_at = localISOTime
  }
}, { immediate: true, deep: true })
</script>

<style scoped>
.sample-profile-select {
  background-image: none;
  padding-right: 0.85rem;
}

:deep(.apexcharts-tooltip),
:deep(.apexcharts-menu) {
  border-radius: 0.5rem !important;
  border-color: rgb(203 213 225 / 0.9) !important;
  box-shadow: 0 12px 28px rgb(15 23 42 / 0.14) !important;
}

:global(.dark) :deep(.apexcharts-tooltip),
:global(.dark) :deep(.apexcharts-menu) {
  border-color: rgb(51 65 85 / 0.9) !important;
  background: #0f172a !important;
  color: #e2e8f0 !important;
}
</style>
