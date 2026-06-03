<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <ModuleHero
      eyebrow="Sample lifecycle"
      :title="$page.props.title || 'Gestão de Amostras VAP'"
      :description="activeTab === 'entry' ? 'Receba amostras, valide condicionamento, associe proposta/produto e prepare o fluxo normal de análise.' : 'Registe descarte rastreável, método de eliminação e certificados com evidência para auditoria.'"
    >
      <template #actions>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
          <span class="inline-flex items-center rounded-full bg-primary-50 px-3 py-1 text-sm font-medium text-primary-800 ring-1 ring-inset ring-primary-700/10 dark:bg-primary-500/10 dark:text-primary-200 dark:ring-primary-400/20">
            {{ stats.total_samples }} Amostras
          </span>
          <div class="flex overflow-hidden rounded-2xl border border-slate-300 bg-white/80 shadow-sm dark:border-slate-700 dark:bg-slate-950/50">
            <button
              @click="activeTab = 'entry'"
              :class="[
                'px-4 py-2 text-sm font-semibold transition-colors duration-200',
                activeTab === 'entry'
                  ? 'bg-primary-600 text-white dark:bg-primary-500'
                  : 'text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800'
              ]"
            >
              Entrada
            </button>
            <button
              @click="activeTab = 'discard'"
              :class="[
                'px-4 py-2 text-sm font-semibold transition-colors duration-200',
                activeTab === 'discard'
                  ? 'bg-primary-600 text-white dark:bg-primary-500'
                  : 'text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800'
              ]"
            >
              Descarte
            </button>
          </div>
        </div>
      </template>

      <div class="mt-6 grid gap-4 md:grid-cols-4">
        <article class="rounded-2xl border border-slate-200 bg-white/70 p-4 dark:border-slate-700 dark:bg-slate-950/45">
          <p class="text-sm text-slate-500 dark:text-slate-400">Por iniciar</p>
          <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ stats.pending_analysis }}</p>
        </article>
        <article class="rounded-2xl border border-primary-200 bg-primary-50/80 p-4 dark:border-primary-500/30 dark:bg-primary-500/10">
          <p class="text-sm text-primary-800 dark:text-primary-200">Em progresso</p>
          <p class="mt-2 text-2xl font-semibold text-primary-900 dark:text-primary-100">{{ stats.in_progress || 0 }}</p>
        </article>
        <article class="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 dark:border-emerald-500/30 dark:bg-emerald-500/10">
          <p class="text-sm text-emerald-800 dark:text-emerald-200">Completadas</p>
          <p class="mt-2 text-2xl font-semibold text-emerald-900 dark:text-emerald-100">{{ stats.completed_analysis }}</p>
        </article>
        <article class="rounded-2xl border border-rose-200 bg-rose-50/80 p-4 dark:border-rose-500/30 dark:bg-rose-500/10">
          <p class="text-sm text-rose-800 dark:text-rose-200">Descartadas</p>
          <p class="mt-2 text-2xl font-semibold text-rose-900 dark:text-rose-100">{{ stats.total_discarded }}</p>
        </article>
      </div>
    </ModuleHero>

    <!-- SEÇÃO DE ENTRADA DE AMOSTRAS -->
    <div v-if="activeTab === 'entry'" class="space-y-8">
      <section class="overflow-hidden rounded-[2rem] border border-[#ded3bf] bg-[#fffdf7] shadow-[0_24px_70px_rgba(20,61,55,0.10)] ring-1 ring-white/70 dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10">
        <div class="grid gap-0 lg:grid-cols-[minmax(0,1fr)_24rem]">
          <div class="relative isolate p-6 sm:p-8">
            <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_10%_0%,rgb(var(--primary-200-rgb)/0.45),transparent_36%),linear-gradient(135deg,#fffdf7,#f4efe4)] dark:bg-[radial-gradient(circle_at_10%_0%,rgb(var(--primary-500-rgb)/0.18),transparent_38%),linear-gradient(135deg,#07110f,#10231f)]" />
            <div class="inline-flex rounded-full border border-[#ded3bf] bg-white/80 px-3 py-1 text-xs font-black uppercase tracking-[0.26em] text-[#143d37] dark:border-[#25443c] dark:bg-[#10231f] dark:text-[#f1d78b]">
              Ponto de entrada oficial
            </div>
            <h2 class="mt-5 max-w-3xl text-2xl font-black tracking-tight text-[#15231f] dark:text-[#f7f1e7] sm:text-3xl">
              Uma receção ampla para transformar pedidos, colheitas e CQ interno em análise rastreável.
            </h2>
            <p class="mt-3 max-w-4xl text-sm font-medium leading-6 text-[#475a53] dark:text-[#cbd8cf]">
              A Sample Entry concentra produto, matriz, armazém, lote, perfis, condicionamento e cadeia de custódia. Depois de validada, alimenta o fluxo normal de colheita, lab code, análises, resultados, contra-análise e certificado.
            </p>
            <div class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
              <button
                type="button"
                @click="newSample"
                class="group rounded-3xl border border-[#ded3bf] bg-white/86 p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-[#d9b05f] hover:shadow-xl dark:border-[#25443c] dark:bg-[#081512] dark:hover:border-[#d9b05f]/60"
              >
                <PlusCircleIcon class="h-6 w-6 text-[#143d37] dark:text-[#f1d78b]" />
                <span class="mt-4 block text-sm font-black text-[#15231f] dark:text-[#f7f1e7]">Entrada individual</span>
                <span class="mt-1 block text-xs leading-5 text-[#475a53] dark:text-[#cbd8cf]">Registe uma amostra completa com escopo e condicionamento.</span>
              </button>
              <button
                type="button"
                @click="startManualBatch"
                class="group rounded-3xl border border-[#ded3bf] bg-white/86 p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-[#d9b05f] hover:shadow-xl dark:border-[#25443c] dark:bg-[#081512] dark:hover:border-[#d9b05f]/60"
              >
                <QueueListIcon class="h-6 w-6 text-[#143d37] dark:text-[#f1d78b]" />
                <span class="mt-4 block text-sm font-black text-[#15231f] dark:text-[#f7f1e7]">Fila manual</span>
                <span class="mt-1 block text-xs leading-5 text-[#475a53] dark:text-[#cbd8cf]">Capture várias amostras manualmente e registe tudo de uma vez.</span>
              </button>
              <button
                type="button"
                @click="chooseImportFile"
                class="group rounded-3xl border border-[#ded3bf] bg-white/86 p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-[#d9b05f] hover:shadow-xl dark:border-[#25443c] dark:bg-[#081512] dark:hover:border-[#d9b05f]/60"
              >
                <CloudArrowUpIcon class="h-6 w-6 text-[#143d37] dark:text-[#f1d78b]" />
                <span class="mt-4 block text-sm font-black text-[#15231f] dark:text-[#f7f1e7]">Importação em lote</span>
                <span class="mt-1 block text-xs leading-5 text-[#475a53] dark:text-[#cbd8cf]">Use nomes e códigos no Excel, sem memorizar IDs internos.</span>
              </button>
              <button
                type="button"
                @click="newInternalQcSample('microbiology_and_chemistry')"
                class="group rounded-3xl border border-[#ded3bf] bg-white/86 p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-[#d9b05f] hover:shadow-xl dark:border-[#25443c] dark:bg-[#081512] dark:hover:border-[#d9b05f]/60"
              >
                <ScaleIcon class="h-6 w-6 text-[#143d37] dark:text-[#f1d78b]" />
                <span class="mt-4 block text-sm font-black text-[#15231f] dark:text-[#f7f1e7]">CQ de matéria-prima</span>
                <span class="mt-1 block text-xs leading-5 text-[#475a53] dark:text-[#cbd8cf]">Microbiologia e química seguem o fluxo normal sem proposta.</span>
              </button>
            </div>
          </div>
          <aside class="border-t border-[#ded3bf] bg-[#f7f1e7] p-6 dark:border-[#25443c] dark:bg-[#081512] lg:border-l lg:border-t-0">
            <div class="space-y-4">
              <div
                v-for="card in sampleEntryCommandCards"
                :key="card.label"
                class="rounded-3xl border border-white/70 bg-white/78 p-4 shadow-sm dark:border-[#25443c] dark:bg-[#07110f]"
              >
                <div class="text-xs font-black uppercase tracking-[0.18em] text-[#6b7b74] dark:text-[#83978d]">{{ card.label }}</div>
                <div class="mt-2 text-2xl font-black text-[#143d37] dark:text-[#f1d78b]">{{ card.value }}</div>
                <div class="mt-1 text-xs leading-5 text-[#475a53] dark:text-[#cbd8cf]">{{ card.hint }}</div>
              </div>
            </div>
            <button
              type="button"
              @click="downloadImportTemplate"
              class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-[#d8cfbe] bg-[#fffdf7] px-4 py-3 text-sm font-black text-[#143d37] shadow-sm transition hover:border-[#d9b05f] hover:bg-white dark:border-[#25443c] dark:bg-[#10231f] dark:text-[#f1d78b]"
            >
              <ArrowDownTrayIcon class="h-4 w-4" />
              Descarregar modelo Excel
            </button>
          </aside>
        </div>
      </section>

      <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <ModuleCard>
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Ritmo de receção</h2>
              <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Volume de amostras recebidas nos últimos 7 dias para antecipar carga operacional.
              </p>
            </div>
            <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right dark:bg-slate-950/50">
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Janela monitorizada</p>
              <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ intakeTrendTotal }}</p>
            </div>
          </div>

          <div class="mt-6">
            <apexchart
              type="area"
              height="300"
              :options="intakeTrendChartOptions"
              :series="intakeTrendChartSeries"
            />
          </div>
        </ModuleCard>

        <div class="grid gap-6">
          <ModuleCard>
            <div class="flex items-start justify-between gap-4">
              <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Estado do fluxo</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Distribuição atual da carteira de amostras.</p>
              </div>
              <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right dark:bg-slate-950/50">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Amostras</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ lifecycleStatusTotal }}</p>
              </div>
            </div>

            <div class="mt-6">
              <apexchart
                type="donut"
                height="300"
                :options="lifecycleStatusChartOptions"
                :series="lifecycleStatusChartSeries"
              />
            </div>
          </ModuleCard>

          <ModuleCard>
            <div class="flex items-start justify-between gap-4">
              <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Pressão de retenção</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Acompanhe descarte próximo, retenção vencida e histórico descartado.</p>
              </div>
              <span class="inline-flex items-center rounded-full bg-rose-50 px-3 py-1 text-sm font-medium text-rose-700 dark:bg-rose-500/10 dark:text-rose-200">
                {{ retentionPressureAlertCount }} sob atenção
              </span>
            </div>

            <div class="mt-6">
              <apexchart
                type="bar"
                height="250"
                :options="retentionPressureChartOptions"
                :series="retentionPressureChartSeries"
              />
            </div>
          </ModuleCard>
        </div>
      </section>

      <div class="grid grid-cols-1 gap-8">
      <!-- PALCO PRINCIPAL -->
      <div class="min-w-0 space-y-6">
        <!-- LISTAGEM DE AMOSTRAS -->
        <div class="overflow-hidden rounded-[2rem] border border-[#ded3bf] bg-[#fffdf7] shadow-[0_24px_80px_rgba(20,61,55,0.10)] ring-1 ring-white/70 dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10">
          <div class="border-b border-[#ded3bf] bg-[#f7f1e7] px-6 py-5 dark:border-[#25443c] dark:bg-[#10231f]">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
              <div>
                <p class="text-xs font-black uppercase tracking-[0.22em] text-[#6b7b74] dark:text-[#83978d]">Carteira de entrada</p>
                <h2 class="mt-2 flex items-center gap-2 text-xl font-black text-[#15231f] dark:text-[#f7f1e7]">
                  <ArchiveBoxIcon class="h-5 w-5 text-[#143d37] dark:text-[#f1d78b]" />
                  Amostras registadas
                </h2>
                <p class="mt-1 text-sm font-medium text-[#5f6f68] dark:text-[#a9bbb4]">
                  Pesquise por código, cliente, produto, lote ou origem sem sair do fluxo de receção.
                </p>
              </div>
              <div class="flex flex-col gap-3 xl:items-end">
                <span class="inline-flex w-fit rounded-full border border-[#ded3bf] bg-white px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-[#143d37] dark:border-[#25443c] dark:bg-[#07110f] dark:text-[#f1d78b]">
                  {{ samples.length }} itens
                </span>
                <div class="grid gap-3 sm:grid-cols-[minmax(0,18rem)_12rem] xl:grid-cols-[minmax(0,18rem)_12rem_auto_auto]">
                  <BaseInput v-model="searchQuery" type="search" placeholder="Buscar amostra..." />
                  <BaseSelect v-model="statusFilter">
                    <option value="">Todos os status</option>
                    <option value="POR_INICIAR">Por Iniciar</option>
                    <option value="EN_PROGRESO">Em Progresso</option>
                    <option value="COMPLETADO">Completado</option>
                    <option value="CANCELADO">Cancelado</option>
                    <option value="EN_PAUSA">Em Pausa</option>
                  </BaseSelect>
                  <button
                    type="button"
                    @click="downloadImportTemplate"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-[#ded3bf] bg-white px-4 py-2.5 text-sm font-black text-[#143d37] shadow-sm transition hover:border-[#d9b05f] hover:bg-[#fffdf7] dark:border-[#25443c] dark:bg-[#07110f] dark:text-[#f1d78b] dark:hover:bg-[#16342e]"
                  >
                    <ArrowDownTrayIcon class="h-4 w-4" />
                    Modelo Excel
                  </button>
                  <button
                    type="button"
                    @click="chooseImportFile"
                    :disabled="importForm.processing"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[#143d37] px-4 py-2.5 text-sm font-black text-white shadow-[0_16px_40px_rgba(20,61,55,0.18)] transition hover:bg-[#0f2f2a] disabled:cursor-not-allowed disabled:opacity-60 dark:bg-[#f1d78b] dark:text-[#07110f] dark:hover:bg-[#f6e7bf]"
                  >
                    <CloudArrowUpIcon class="h-4 w-4" />
                    {{ importForm.processing ? 'A importar...' : 'Importar lote' }}
                  </button>
                  <input
                    ref="importFileInput"
                    type="file"
                    accept=".xlsx,.xls,.csv,.txt"
                    class="hidden"
                    @change="onImportFileChange"
                  />
                  <p v-if="importForm.errors.file" class="text-xs font-semibold text-rose-600 dark:text-rose-300 xl:col-span-4">
                    {{ importForm.errors.file }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- ESTADO VAZIO -->
          <div v-if="filteredSamples.length === 0" class="p-12 text-center">
            <BeakerIcon class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-700" />
            <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">
              Nenhuma amostra encontrada
            </h3>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
              {{ searchQuery || statusFilter ? 'Tente ajustar os filtros de busca' : 'Comece registrando sua primeira amostra' }}
            </p>
          </div>

          <!-- TABELA DE AMOSTRAS -->
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[#ded3bf] dark:divide-[#25443c]">
              <thead class="bg-[#f7f1e7]/90 dark:bg-[#10231f]/90">
                <tr>
                  <th scope="col" class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-[0.18em] text-[#6b7b74] dark:text-[#a9bbb4]">
                    Código
                  </th>
                  <th scope="col" class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-[0.18em] text-[#6b7b74] dark:text-[#a9bbb4]">
                    Nome
                  </th>
                  <th scope="col" class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-[0.18em] text-[#6b7b74] dark:text-[#a9bbb4]">
                    Tipo
                  </th>
                  <th scope="col" class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-[0.18em] text-[#6b7b74] dark:text-[#a9bbb4]">
                    Cliente
                  </th>
                  <th scope="col" class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-[0.18em] text-[#6b7b74] dark:text-[#a9bbb4]">
                    Produto / matriz
                  </th>
                  <th scope="col" class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-[0.18em] text-[#6b7b74] dark:text-[#a9bbb4]">
                    Lote / origem
                  </th>
                  <th scope="col" class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-[0.18em] text-[#6b7b74] dark:text-[#a9bbb4]">
                    Status
                  </th>
                  <th scope="col" class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-[0.18em] text-[#6b7b74] dark:text-[#a9bbb4]">
                    Recebido em
                  </th>
                  <th scope="col" class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-[0.18em] text-[#6b7b74] dark:text-[#a9bbb4]">
                    Ações
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[#ded3bf] bg-[#fffdf7] dark:divide-[#25443c] dark:bg-[#07110f]">
                <tr 
                  v-for="sample in filteredSamples"
                  :key="sample.id"
                  class="transition-colors duration-150 hover:bg-[#f7f1e7]/70 dark:hover:bg-[#10231f]/70"
                >
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-black text-[#143d37] dark:text-[#f1d78b]">
                      {{ sample.code }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-bold text-[#15231f] dark:text-[#f7f1e7]">
                      {{ sample.name }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-[#31413b] dark:text-[#d7e2dd]">
                      {{ getSampleTypeLabel(sample.sample_type) }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-[#31413b] dark:text-[#d7e2dd]">
                      {{ sample.customer?.name || 'N/A' }}
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="max-w-64 text-sm font-bold text-[#15231f] dark:text-[#f7f1e7]">
                      {{ sample.client_submitted_info?.product_name || selectedSampleProductName(sample) }}
                    </div>
                    <p class="mt-1 text-xs font-medium text-[#5f6f68] dark:text-[#a9bbb4]">
                      {{ sample.client_submitted_info?.matrix_description || sample.client_submitted_info?.matrix || 'Matriz por confirmar' }}
                    </p>
                  </td>
                  <td class="px-6 py-4">
                    <div class="text-sm font-medium text-[#31413b] dark:text-[#d7e2dd]">
                      {{ sample.client_submitted_info?.lot || 'Sem lote' }}
                    </div>
                    <p class="mt-1 text-xs font-medium text-[#5f6f68] dark:text-[#a9bbb4]">
                      {{ sample.client_submitted_info?.origin || sample.client_submitted_info?.sampling_plan_ref || 'Origem/plano por confirmar' }}
                    </p>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="sampleStatusBadgeClass(sample.status)">
                      {{ getStatusLabel(sample.status) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-[#5f6f68] dark:text-[#a9bbb4]">
                    {{ formatDate(sample.received_at) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex items-center gap-2">
                      <button
                        @click="viewSample(sample.id)"
                        class="rounded-full p-2 text-primary-700 transition hover:bg-primary-50 hover:text-primary-900 dark:text-primary-300 dark:hover:bg-primary-500/10"
                        title="Visualizar"
                      >
                        <EyeIcon class="h-4 w-4" />
                      </button>
                      <button
                        @click="editSample(sample)"
                        class="rounded-full p-2 text-amber-700 transition hover:bg-amber-50 hover:text-amber-900 dark:text-amber-300 dark:hover:bg-amber-500/10"
                        title="Editar"
                      >
                        <PencilSquareIcon class="h-4 w-4" />
                      </button>
                      <button
                        @click="generateEntryPdf(sample.id)"
                        class="rounded-full p-2 text-emerald-700 transition hover:bg-emerald-50 hover:text-emerald-900 dark:text-emerald-300 dark:hover:bg-emerald-500/10"
                        title="Gerar PDF"
                      >
                        <DocumentArrowDownIcon class="h-4 w-4" />
                      </button>
                      <button
                        v-if="sample.status === 'COMPLETADO' || sample.status === 'CANCELADO'"
                        @click="prepareForDiscard(sample)"
                        class="rounded-full p-2 text-rose-700 transition hover:bg-rose-50 hover:text-rose-900 dark:text-rose-300 dark:hover:bg-rose-500/10"
                        title="Descartar"
                      >
                        <TrashIcon class="h-4 w-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- PAGINAÇÃO -->
          <div v-if="samples.length > 0" class="border-t border-[#ded3bf] bg-[#f7f1e7]/60 px-6 py-5 dark:border-[#25443c] dark:bg-[#10231f]/60">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <div class="text-sm font-medium text-[#5f6f68] dark:text-[#a9bbb4]">
                Mostrando {{ filteredSamples.length }} de {{ filteredSampleResults.length }} resultados
              </div>
              <div class="flex items-center gap-2">
                <button
                  @click="currentPage--"
                  :disabled="currentPage === 1"
                  :class="[
                    'rounded-full border px-3 py-1.5 text-sm font-semibold transition',
                    currentPage === 1 ? 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-600' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-200 dark:hover:bg-slate-800'
                  ]"
                >
                  Anterior
                </button>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                  Página {{ currentPage }} de {{ totalPages }}
                </span>
                <button
                  @click="currentPage++"
                  :disabled="currentPage === totalPages"
                  :class="[
                    'rounded-full border px-3 py-1.5 text-sm font-semibold transition',
                    currentPage === totalPages ? 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-600' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-200 dark:hover:bg-slate-800'
                  ]"
                >
                  Próxima
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="portalAnalysisRequests.length" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
            <div class="flex items-center justify-between">
              <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
                <ClipboardDocumentListIcon class="h-5 w-5 text-primary-800 dark:text-primary-300" />
                Pedidos do Portal para Validar
              </h2>
              <span class="inline-flex items-center rounded-full bg-primary-50 px-3 py-1 text-sm font-medium text-primary-800 dark:bg-primary-500/10 dark:text-primary-200">
                {{ portalAnalysisRequests.length }} pendentes
              </span>
            </div>
          </div>

          <div class="divide-y divide-slate-100 dark:divide-slate-800">
            <div
              v-for="request in portalAnalysisRequests.slice(0, 5)"
              :key="request.id"
              class="flex items-start justify-between gap-4 px-6 py-4 transition hover:bg-slate-50 dark:hover:bg-slate-950/50"
            >
              <div>
                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ request.title }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ request.reference || 'Sem referência' }} · {{ request.customer || 'Sem cliente' }}</p>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ (request.requested_profile_names || []).join(', ') || 'Sem perfis declarados' }}</p>
              </div>
              <button
                type="button"
                @click="prefillFromPortalRequest(request)"
                class="inline-flex items-center rounded-2xl bg-primary-700 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-600 dark:bg-primary-500 dark:hover:bg-primary-400"
              >
                Pré-preencher
              </button>
            </div>
          </div>
        </div>

        <!-- CARTÃO DE DETALHES DA AMOSTRA -->
        <div v-if="editingSample" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <div class="bg-gradient-to-r from-primary-900 to-primary-700 px-6 py-4 dark:from-slate-950 dark:to-primary-950">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <h2 class="flex items-center gap-2 text-lg font-semibold text-white">
                <PencilSquareIcon class="h-5 w-5" />
                {{ editingSample.id ? 'Editar Amostra' : (manualBatchMode ? 'Nova Amostra da Fila Manual' : 'Nova Amostra') }}
              </h2>
              <span
                v-if="manualBatchMode"
                class="inline-flex items-center justify-center rounded-full bg-white/12 px-3 py-1 text-xs font-black uppercase tracking-[0.18em] text-white ring-1 ring-white/25"
              >
                {{ manualSampleQueue.length }} na fila
              </span>
            </div>
          </div>
          
          <div class="vap-sample-entry-form p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- FORMULÁRIO (mesmo do anterior) -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <TagIcon class="h-4 w-4" />
                  Nome da Amostra
                  <span class="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  v-model="form.name"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    form.errors.name 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' 
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                  placeholder="Digite o nome da amostra"
                />
                <p v-if="form.errors.name" class="text-xs text-red-600">
                  {{ form.errors.name }}
                </p>
              </div>

              <!-- CÓDIGO -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <QrCodeIcon class="h-4 w-4" />
                  Código
                </label>
                <input
                  type="text"
                  v-model="form.code"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    form.errors.code 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' 
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                  placeholder="Será gerado automaticamente"
                />
              </div>

              <!-- TIPO DE AMOSTRA -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <CubeIcon class="h-4 w-4" />
                  Tipo de Amostra
                  <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.sample_type"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    form.errors.sample_type 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' 
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                >
                  <option value="">Selecione o tipo</option>
                  <option value="ROTINA">Rotina</option>
                  <option value="MATERIA_PRIMA">Matéria-prima</option>
                  <option value="PRODUTO_ACABADO">Produto acabado</option>
                  <option value="ESTABILIDADE">Estabilidade</option>
                  <option value="CONTRAPROVA">Contraprova</option>
                  <option value="INTERLABORATORIAL">Interlaboratorial</option>
                  <option value="RETENCAO">Retenção</option>
                </select>
                <p v-if="form.errors.sample_type" class="text-xs text-red-600">
                  {{ form.errors.sample_type }}
                </p>
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <DocumentTextIcon class="h-4 w-4" />
                  Origem do trabalho
                </label>
                <select
                  v-model="form.client_submitted_info.request_origin"
                  class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:ring-offset-2"
                >
                  <option value="client">Cliente</option>
                  <option value="internal">Interno</option>
                </select>
                <p class="text-xs text-gray-500">
                  Trabalhos internos podem seguir para análise sem proposta aceite, desde que o modo operacional permita.
                </p>
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1 dark:text-slate-200">
                  <ClipboardDocumentListIcon class="h-4 w-4" />
                  Fluxo de colheita
                </label>
                <select
                  v-model="form.client_submitted_info.collection_type"
                  class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                >
                  <option value="direct">Direta / receção imediata</option>
                  <option value="programmed">Programada / recolha planeada</option>
                </select>
                <p class="text-xs text-gray-500 dark:text-slate-400">
                  A Sample Entry é o ponto de entrada. A colheita criada fica ligada a este código de amostra.
                </p>
              </div>

              <div
                v-if="form.client_submitted_info.collection_type === 'programmed'"
                class="space-y-4 rounded-2xl border border-primary-200 bg-primary-50/70 px-5 py-4 dark:border-primary-500/30 dark:bg-primary-500/10 md:col-span-2 lg:col-span-3"
              >
                <div>
                  <p class="text-sm font-semibold text-primary-950 dark:text-primary-100">Planeamento da colheita programada</p>
                  <p class="mt-1 text-xs leading-5 text-primary-800 dark:text-primary-200">
                    Use estes campos quando a amostra ainda depende de recolha planeada. Depois de validada, a colheita programada mantém link para esta Sample Entry.
                  </p>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                  <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Local de colheita</span>
                    <input
                      v-model="form.client_submitted_info.collection_location"
                      type="text"
                      class="mt-2 w-full rounded-xl border border-primary-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-700/20 dark:border-primary-500/30 dark:bg-slate-950 dark:text-slate-100"
                      placeholder="Ex.: armazém, linha de produção, sala fria..."
                    />
                  </label>
                  <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Referência de viatura/equipa</span>
                    <input
                      v-model="form.client_submitted_info.vehicle_reference"
                      type="text"
                      class="mt-2 w-full rounded-xl border border-primary-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-700/20 dark:border-primary-500/30 dark:bg-slate-950 dark:text-slate-100"
                      placeholder="Ex.: equipa externa, viatura 02, rota norte..."
                    />
                  </label>
                </div>
              </div>

              <div v-if="isInternalRawMaterialQc" class="space-y-4 rounded-2xl border border-emerald-200 bg-emerald-50/80 px-5 py-4 md:col-span-2 lg:col-span-3 dark:border-emerald-500/30 dark:bg-emerald-500/10">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                  <div>
                    <p class="text-sm font-semibold text-emerald-950 dark:text-emerald-100">Procedimento interno de CQ de matéria-prima</p>
                    <p class="mt-1 text-xs leading-5 text-emerald-800 dark:text-emerald-200">
                      Este caminho gera a receção interna e, quando produto/perfis forem definidos, cria automaticamente colheita, lab code, amostras internas e análises.
                    </p>
                  </div>
                  <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-bold text-emerald-800 ring-1 ring-emerald-200 dark:bg-slate-950/50 dark:text-emerald-200 dark:ring-emerald-500/30">
                    Sem proposta
                  </span>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                  <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Disciplina</span>
                    <select
                      v-model="form.client_submitted_info.analysis_discipline"
                      class="mt-2 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 dark:border-emerald-500/30 dark:bg-slate-950 dark:text-slate-100"
                    >
                      <option value="microbiology">Microbiologia</option>
                      <option value="chemistry">Química / físico-química</option>
                      <option value="microbiology_and_chemistry">Microbiologia + química</option>
                    </select>
                  </label>

                  <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Objetivo</span>
                    <select
                      v-model="form.client_submitted_info.quality_control_purpose"
                      class="mt-2 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 dark:border-emerald-500/30 dark:bg-slate-950 dark:text-slate-100"
                    >
                      <option value="raw_material_release">Liberação de matéria-prima</option>
                      <option value="supplier_qualification">Qualificação de fornecedor</option>
                      <option value="process_validation">Validação de processo</option>
                      <option value="stability_follow_up">Acompanhamento de estabilidade</option>
                      <option value="investigation">Investigação interna</option>
                      <option value="other">Outro</option>
                    </select>
                  </label>

                  <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Decisão esperada</span>
                    <select
                      v-model="form.client_submitted_info.qc_decision"
                      class="mt-2 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 dark:border-emerald-500/30 dark:bg-slate-950 dark:text-slate-100"
                    >
                      <option value="hold_until_release">Reter até liberação</option>
                      <option value="release_if_compliant">Liberar se conforme</option>
                      <option value="investigate_before_release">Investigar antes de liberar</option>
                      <option value="trend_only">Apenas tendência</option>
                    </select>
                  </label>

                  <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Categoria</span>
                    <select
                      v-model="form.client_submitted_info.material_category"
                      class="mt-2 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 dark:border-emerald-500/30 dark:bg-slate-950 dark:text-slate-100"
                    >
                      <option value="raw_material">Matéria-prima</option>
                      <option value="ingredient">Ingrediente</option>
                      <option value="packaging_material">Material de embalagem</option>
                      <option value="intermediate">Produto intermédio</option>
                      <option value="finished_product">Produto acabado</option>
                      <option value="environmental_control">Controlo ambiental</option>
                      <option value="other">Outro</option>
                    </select>
                  </label>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                  <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Lote</span>
                    <input
                      v-model="form.client_submitted_info.lot"
                      type="text"
                      class="mt-2 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 dark:border-emerald-500/30 dark:bg-slate-950 dark:text-slate-100"
                      placeholder="Lote da matéria-prima"
                    />
                  </label>
                  <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Batch / OP</span>
                    <input
                      v-model="form.client_submitted_info.batch"
                      type="text"
                      class="mt-2 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 dark:border-emerald-500/30 dark:bg-slate-950 dark:text-slate-100"
                      placeholder="Batch interno, OP ou receção"
                    />
                  </label>
                  <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Fornecedor</span>
                    <input
                      v-model="form.client_submitted_info.supplier_name"
                      type="text"
                      class="mt-2 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 dark:border-emerald-500/30 dark:bg-slate-950 dark:text-slate-100"
                      placeholder="Fornecedor / origem interna"
                    />
                  </label>
                </div>
              </div>

              <!-- CLIENTE -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <UserGroupIcon class="h-4 w-4" />
                  Cliente
                  <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.customer_id"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    form.errors.customer_id 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' 
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                >
                  <option value="">Selecione o cliente</option>
                  <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                    {{ customer.name }} ({{ customer.code }})
                  </option>
                </select>
                <p v-if="form.errors.customer_id" class="text-xs text-red-600">
                  {{ form.errors.customer_id }}
                </p>
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <CubeIcon class="h-4 w-4" />
                  Produto
                </label>
                <select
                  v-model="form.client_submitted_info.product_id"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    form.errors['client_submitted_info.product_id']
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20'
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                >
                  <option :value="null">Selecionar depois</option>
                  <option v-for="product in products" :key="product.id" :value="product.id">
                    {{ product.name }}{{ product.matrix ? ` · ${product.matrix}` : '' }}
                  </option>
                </select>
                <p class="text-xs text-gray-500">Quando definido, a amostra pode ser integrada no fluxo normal de colheita/análise.</p>
                <p v-if="form.errors['client_submitted_info.product_id']" class="text-xs text-red-600">
                  {{ form.errors['client_submitted_info.product_id'] }}
                </p>
              </div>

              <div v-if="selectedProduct" class="rounded-2xl border border-primary-200 bg-primary-50/70 px-4 py-3 text-sm text-primary-950 dark:border-primary-500/30 dark:bg-primary-500/10 dark:text-primary-100">
                <p class="font-semibold">Escopo analítico base</p>
                <p class="mt-1 text-xs text-primary-800 dark:text-primary-200">
                  Matriz: {{ selectedProduct.matrix || 'Sem matriz definida' }} ·
                  {{ selectedProduct.profiles?.length || 0 }} perfis disponíveis para o produto
                </p>
              </div>

              <div class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50/80 px-5 py-4 dark:border-slate-700 dark:bg-slate-950/50 md:col-span-2 lg:col-span-3">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                  <div>
                    <p class="text-sm font-semibold text-slate-900 dark:text-white">Identificação técnica da amostra</p>
                    <p class="text-xs leading-5 text-slate-600 dark:text-slate-400">
                      Estes campos acompanham a entrada, alimentam o `collection_product` e ficam disponíveis no relatório analítico.
                    </p>
                  </div>
                  <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-700 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700">
                    Rastreável no PDF
                  </span>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                  <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Lote</span>
                    <input v-model="form.client_submitted_info.lot" type="text" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-700/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Lote / batch do cliente" />
                  </label>
                  <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Origem</span>
                    <input v-model="form.client_submitted_info.origin" type="text" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-700/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Fornecedor, país, linha, unidade..." />
                  </label>
                  <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Local de colheita</span>
                    <input v-model="form.client_submitted_info.location" type="text" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-700/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Local físico / ponto de amostragem" />
                  </label>
                  <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Plano de amostragem</span>
                    <input v-model="form.client_submitted_info.sampling_plan_ref" type="text" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-700/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Plano, norma ou referência" />
                  </label>
                  <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Quantidade recebida</span>
                    <input v-model="form.client_submitted_info.quantity" type="text" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-700/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Ex.: 2 kg, 500 ml" />
                  </label>
                  <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Quantidade colhida</span>
                    <input v-model="form.client_submitted_info.collected_qty" type="text" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-700/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Ex.: 3 frascos" />
                  </label>
                  <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Temperatura</span>
                    <input v-model="form.client_submitted_info.temperature_value" type="text" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-700/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Ex.: 4 °C, ambiente" />
                  </label>
                  <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Contentor</span>
                    <input v-model="form.client_submitted_info.container_no" type="text" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-700/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="N.º do contentor" />
                  </label>
                  <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">DU</span>
                    <input v-model="form.client_submitted_info.du_no" type="text" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-700/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Documento único" />
                  </label>
                  <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Termo</span>
                    <input v-model="form.client_submitted_info.term_no" type="text" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-700/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="N.º do termo" />
                  </label>
                  <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">BL</span>
                    <input v-model="form.client_submitted_info.bl" type="text" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-700/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Bill of lading" />
                  </label>
                  <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Produção</span>
                    <input v-model="form.client_submitted_info.production_date" type="date" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-700/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" />
                  </label>
                  <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Validade</span>
                    <input v-model="form.client_submitted_info.expiry_date" type="date" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm focus:border-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-700/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" />
                  </label>
                </div>
              </div>

              <div v-if="!isInternalRequest" class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <DocumentTextIcon class="h-4 w-4" />
                  Proposta aceite
                </label>
                <select
                  v-model="form.proposal_id"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    form.errors.proposal_id
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20'
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                >
                  <option value="">Selecionar depois</option>
                  <option v-for="proposal in acceptedProposals" :key="proposal.id" :value="proposal.id">
                    {{ getProposalLabel(proposal) }}
                  </option>
                </select>
                <p class="text-xs text-gray-500">
                  A amostra só pode entrar em análise quando estiver associada a uma proposta aceite.
                </p>
                <p v-if="form.errors.proposal_id" class="text-xs text-red-600">
                  {{ form.errors.proposal_id }}
                </p>
              </div>

              <div v-else class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
                Este registo está marcado como trabalho interno. A validação laboratorial seguirá o fluxo normal sem depender de proposta aceite.
              </div>

              <!-- LABORATÓRIO -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <BuildingOfficeIcon class="h-4 w-4" />
                  Laboratório
                  <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.lab_id"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    form.errors.lab_id 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' 
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                >
                  <option value="">Selecione o laboratório</option>
                  <option v-for="lab in labs" :key="lab.id" :value="lab.id">
                    {{ lab.name }} ({{ lab.code }})
                  </option>
                </select>
                <p v-if="form.errors.lab_id" class="text-xs text-red-600">
                  {{ form.errors.lab_id }}
                </p>
              </div>

              <!-- DEPARTAMENTO -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <BuildingLibraryIcon class="h-4 w-4" />
                  Departamento
                  <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.department_id"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    form.errors.department_id 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' 
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                >
                  <option value="">Selecione o departamento</option>
                  <option v-for="department in departments" :key="department.id" :value="department.id">
                    {{ department.name }} ({{ department.code }})
                  </option>
                </select>
                <p v-if="form.errors.department_id" class="text-xs text-red-600">
                  {{ form.errors.department_id }}
                </p>
              </div>

              <!-- DATA DE RECEBIMENTO -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <CalendarIcon class="h-4 w-4" />
                  Data de Recebimento
                </label>
                <input
                  type="datetime-local"
                  v-model="form.received_at"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                />
              </div>

              <!-- EMBALAGEM -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <QueueListIcon class="h-4 w-4" />
                  Embalagem
                </label>
                <select
                  v-model="form.packaging_id"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                >
                  <option value="">Selecione a embalagem</option>
                  <option v-for="packaging in packagingCategories" :key="packaging.id" :value="packaging.id">
                    {{ packaging.name }} ({{ packaging.code }})
                  </option>
                </select>
              </div>

              <!-- ARMAZÉM -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <ArchiveBoxIcon class="h-4 w-4" />
                  Armazém
                  <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.warehouse_id"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    form.errors.warehouse_id 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' 
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                >
                  <option value="">Selecione o armazém</option>
                  <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                    {{ warehouse.name }} ({{ warehouse.code }})
                  </option>
                </select>
                <p v-if="form.errors.warehouse_id" class="text-xs text-red-600">
                  {{ form.errors.warehouse_id }}
                </p>
              </div>

              <div class="space-y-2 md:col-span-2 lg:col-span-3">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <ClipboardDocumentListIcon class="h-4 w-4" />
                  Perfis analíticos para o fluxo normal
                </label>
                <select
                  v-model="form.client_submitted_info.requested_profile_ids"
                  multiple
                  class="min-h-32 w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900/20"
                >
                  <option v-for="profile in availableProfiles" :key="profile.id" :value="profile.id">
                    {{ profile.name }}{{ profile.analysis_type ? ` · ${profile.analysis_type}` : '' }}{{ profile.parameter_count ? ` · ${profile.parameter_count} parâmetros` : '' }}
                  </option>
                </select>
                <p class="text-xs text-gray-500">Estes perfis serão usados para gerar a colheita/lab code/amostras/análises do fluxo principal.</p>
                <p v-if="form.errors['client_submitted_info.requested_profile_ids']" class="text-xs text-red-600">
                  {{ form.errors['client_submitted_info.requested_profile_ids'] }}
                </p>
              </div>

              <div class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-700 dark:bg-slate-950/50 md:col-span-2 lg:col-span-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                  <div>
                    <p class="text-sm font-semibold text-slate-900 dark:text-white">Checklist analítico previsto</p>
                    <p class="text-xs text-slate-600 dark:text-slate-400">
                      A receção já define o escopo esperado para os técnicos.
                    </p>
                  </div>
                  <div class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700">
                    {{ selectedProfileIds.length || availableProfiles.length || 0 }} perfis · {{ requiredParameterPreview.length }} parâmetros
                  </div>
                </div>

                <div v-if="selectedProfileSummaries.length" class="flex flex-wrap gap-2">
                  <span
                    v-for="profile in selectedProfileSummaries"
                    :key="profile.id"
                    class="inline-flex items-center rounded-full bg-primary-100 px-3 py-1 text-xs font-medium text-primary-900 dark:bg-primary-500/15 dark:text-primary-200"
                  >
                    {{ profile.name }}
                  </span>
                </div>

                <div v-if="requiredParameterPreview.length" class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                  <div
                    v-for="parameter in requiredParameterPreview"
                    :key="parameter.id"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 dark:border-slate-700 dark:bg-slate-900"
                  >
                    <p class="text-sm font-medium text-slate-900 dark:text-white">
                      {{ parameter.name }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                      {{ parameter.code || 'Sem código' }}
                    </p>
                    <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">
                      {{ parameter.profiles.join(', ') }}
                    </p>
                  </div>
                </div>

                <p v-else class="text-xs text-slate-500 dark:text-slate-400">
                  Selecione um produto com matriz analítica ou escolha perfis para ver os parâmetros obrigatórios.
                </p>
              </div>

              <div class="space-y-4 rounded-2xl border border-amber-200 bg-amber-50/70 px-5 py-4 dark:border-amber-500/30 dark:bg-amber-500/10 md:col-span-2 lg:col-span-3">
                <div>
                  <p class="text-sm font-semibold text-amber-950 dark:text-amber-100">Avaliação de condicionamento na receção</p>
                  <p class="text-xs text-amber-800 dark:text-amber-200">
                    Registe o estado em que a amostra chegou para suportar rastreabilidade e decisões ISO 17025.
                  </p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Decisão de aceitação</label>
                    <select
                      v-model="form.client_submitted_info.conditioning_status"
                      class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900/20"
                    >
                      <option :value="null">Não avaliado</option>
                      <option value="accepted">Aceite</option>
                      <option value="restricted">Aceite com restrições</option>
                      <option value="rejected">Rejeitado / quarentena</option>
                    </select>
                  </div>

                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Estado da embalagem</label>
                    <input
                      v-model="form.client_submitted_info.packaging_condition"
                      type="text"
                      class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900/20"
                      placeholder="Íntegra, húmida, violada, refrigerada..."
                    />
                  </div>

                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Condição térmica na receção</label>
                    <input
                      v-model="form.client_submitted_info.temperature_condition"
                      type="text"
                      class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900/20"
                      placeholder="2-8 °C, ambiente, congelada..."
                    />
                  </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Observações de integridade</label>
                    <textarea
                      v-model="form.client_submitted_info.integrity_observations"
                      rows="3"
                      class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900/20"
                      placeholder="Volume, lacre, identificação, desvios visuais..."
                    ></textarea>
                  </div>

                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Notas de cadeia de custódia / condicionamento</label>
                    <textarea
                      v-model="form.client_submitted_info.chain_of_custody_notes"
                      rows="3"
                      class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900/20"
                      placeholder="Tempo de transporte, recipientes secundários, ações corretivas..."
                    ></textarea>
                  </div>
                </div>
              </div>

              <!-- SERVIÇOS SOLICITADOS -->
              <div class="space-y-2 md:col-span-2 lg:col-span-3">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <ClipboardDocumentListIcon class="h-4 w-4" />
                  Serviços Solicitados
                </label>
                <textarea
                  v-model="form.requested_services"
                  rows="3"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                  placeholder="Liste todas as análises e serviços solicitados..."
                ></textarea>
              </div>

              <!-- OBSERVAÇÕES -->
              <div class="space-y-2 md:col-span-2 lg:col-span-3">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <ChatBubbleLeftRightIcon class="h-4 w-4" />
                  Observações
                </label>
                <textarea
                  v-model="form.obs"
                  rows="2"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                  placeholder="Quaisquer observações ou notas adicionais..."
                ></textarea>
              </div>
            </div>

            <!-- BOTÕES DO FORMULÁRIO -->
            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <button
                v-if="!editingSample.id"
                type="button"
                @click="addCurrentSampleToManualQueue"
                :disabled="!isFormValid"
                :class="[
                  'inline-flex items-center justify-center gap-2 rounded-full border px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200',
                  !isFormValid
                    ? 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-600'
                    : 'border-[#d9b05f] bg-[#fff8e6] text-[#143d37] hover:bg-[#f6e7bf] dark:border-[#d9b05f]/60 dark:bg-[#d9b05f]/10 dark:text-[#f1d78b] dark:hover:bg-[#d9b05f]/20'
                ]"
              >
                <QueueListIcon class="h-5 w-5" />
                Adicionar à fila manual
              </button>
              <div class="flex items-center justify-end gap-3">
              <button 
                @click="cancelEdit"
                type="button"
                class="rounded-full border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-colors duration-200 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-200 dark:hover:bg-slate-800"
              >
                Cancelar
              </button>
              <button 
                @click="editingSample.id ? updateSample() : submitSample()"
                :disabled="form.processing || !isFormValid"
                :class="[
                  'inline-flex items-center gap-2 rounded-full px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200',
                  form.processing || !isFormValid
                    ? 'cursor-not-allowed bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-500'
                    : 'bg-primary-700 text-white hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:bg-primary-500 dark:hover:bg-primary-400'
                ]"
              >
                <CheckCircleIcon class="h-5 w-5" />
                {{ form.processing ? 'Processando...' : (editingSample.id ? 'Atualizar Amostra' : 'Salvar Amostra') }}
              </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- COLUNA DIREITA (1/3 largura) -->
      <div class="grid gap-6 xl:grid-cols-2">
        <ModuleCard v-if="manualBatchMode || manualSampleQueue.length" title="Fila manual de amostras">
          <div class="space-y-4">
            <div class="rounded-3xl border border-[#ded3bf] bg-[#fffdf7] p-4 dark:border-[#25443c] dark:bg-[#07110f]">
              <p class="text-xs font-black uppercase tracking-[0.18em] text-[#6b7b74] dark:text-[#83978d]">Registo em lote manual</p>
              <p class="mt-2 text-3xl font-black text-[#143d37] dark:text-[#f1d78b]">{{ manualSampleQueue.length }}</p>
              <p class="mt-1 text-xs leading-5 text-[#475a53] dark:text-[#cbd8cf]">
                Adicione amostras com o formulário principal, reveja a fila e submeta-as juntas para o fluxo normal.
              </p>
            </div>

            <div v-if="manualSampleQueue.length" class="max-h-[28rem] space-y-3 overflow-y-auto pr-1">
              <article
                v-for="(queueItem, index) in manualSampleQueue"
                :key="queueItem.temp_id"
                class="rounded-3xl border border-[#ded3bf] bg-white/80 p-4 shadow-sm dark:border-[#25443c] dark:bg-[#081512]"
              >
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-[#6b7b74] dark:text-[#83978d]">Amostra {{ index + 1 }}</p>
                    <h3 class="mt-1 text-sm font-black text-[#15231f] dark:text-[#f7f1e7]">{{ queueItem.name }}</h3>
                    <p class="mt-1 text-xs text-[#475a53] dark:text-[#cbd8cf]">
                      {{ queueItem.customer }} · {{ queueItem.product }} · {{ queueItem.lot }}
                    </p>
                  </div>
                  <button
                    type="button"
                    @click="removeManualQueueItem(queueItem.temp_id)"
                    class="rounded-full p-2 text-rose-600 transition hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-500/10"
                    title="Remover da fila"
                  >
                    <TrashIcon class="h-4 w-4" />
                  </button>
                </div>
                <button
                  type="button"
                  @click="useQueuedSampleAsBase(queueItem)"
                  class="mt-3 inline-flex items-center rounded-full border border-[#ded3bf] px-3 py-1.5 text-xs font-bold text-[#143d37] transition hover:bg-[#f7f1e7] dark:border-[#25443c] dark:text-[#f1d78b] dark:hover:bg-[#10231f]"
                >
                  Usar como base
                </button>
              </article>
            </div>
            <div v-else class="rounded-3xl border border-dashed border-[#ded3bf] bg-[#fffdf7] px-4 py-8 text-center text-sm font-medium text-[#6b7b74] dark:border-[#25443c] dark:bg-[#07110f] dark:text-[#a9bbb4]">
              A fila ainda está vazia. Preencha a amostra no formulário e clique em “Adicionar à fila manual”.
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
              <button
                type="button"
                @click="clearManualQueue"
                :disabled="!manualSampleQueue.length || bulkForm.processing"
                class="inline-flex items-center justify-center rounded-2xl border border-[#ded3bf] px-4 py-3 text-sm font-bold text-[#475a53] transition hover:bg-[#f7f1e7] disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#25443c] dark:text-[#cbd8cf] dark:hover:bg-[#10231f]"
              >
                Limpar fila
              </button>
              <button
                type="button"
                @click="submitManualBatch"
                :disabled="!manualSampleQueue.length || bulkForm.processing"
                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[#143d37] px-4 py-3 text-sm font-black text-white shadow-[0_16px_40px_rgba(20,61,55,0.18)] transition hover:bg-[#0f2f2a] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-[#f1d78b] dark:text-[#07110f] dark:hover:bg-[#f6e7bf]"
              >
                <CheckCircleIcon class="h-5 w-5" />
                {{ bulkForm.processing ? 'A registar...' : 'Registar fila' }}
              </button>
            </div>
            <p v-if="bulkForm.errors.samples" class="text-xs font-semibold text-rose-600 dark:text-rose-300">{{ bulkForm.errors.samples }}</p>
          </div>
        </ModuleCard>

        <!-- CARTÃO DE AÇÕES -->
        <ModuleCard title="Ações rápidas">
          <div class="space-y-4">
            <button
              @click="newSample"
              type="button"
              class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-primary-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:bg-primary-500 dark:hover:bg-primary-400"
            >
              <PlusCircleIcon class="h-5 w-5" />
              Nova Amostra
            </button>
            <button
              @click="startManualBatch"
              type="button"
              class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-[#ded3bf] bg-[#fffdf7] px-4 py-3 text-sm font-semibold text-[#143d37] shadow-sm transition hover:border-[#d9b05f] hover:bg-[#f7f1e7] dark:border-[#25443c] dark:bg-[#07110f] dark:text-[#f1d78b] dark:hover:bg-[#10231f]"
            >
              <QueueListIcon class="h-5 w-5" />
              Fila manual
            </button>

            <div class="rounded-3xl border border-emerald-200 bg-emerald-50/80 p-4 dark:border-emerald-500/30 dark:bg-emerald-500/10">
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-300">
                CQ interno
              </p>
              <h3 class="mt-2 text-sm font-semibold text-emerald-950 dark:text-emerald-100">
                Matéria-prima
              </h3>
              <p class="mt-1 text-xs leading-5 text-emerald-800 dark:text-emerald-200">
                Abre o caminho para microbiologia/química sem proposta, mantendo lab code, análise, resultados e certificado no fluxo normal.
              </p>
              <div class="mt-4 grid gap-2">
                <button
                  type="button"
                  class="inline-flex items-center justify-center rounded-2xl bg-emerald-700 px-3 py-2 text-xs font-bold text-white transition hover:bg-emerald-600 dark:bg-emerald-400 dark:text-emerald-950"
                  @click="newInternalQcSample('microbiology')"
                >
                  Microbiologia
                </button>
                <button
                  type="button"
                  class="inline-flex items-center justify-center rounded-2xl border border-emerald-300 bg-white px-3 py-2 text-xs font-bold text-emerald-800 transition hover:bg-emerald-50 dark:border-emerald-500/40 dark:bg-slate-950/40 dark:text-emerald-200 dark:hover:bg-emerald-500/10"
                  @click="newInternalQcSample('chemistry')"
                >
                  Química / físico-química
                </button>
              </div>
            </div>

            <button
              @click="exportData"
              type="button"
              class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-slate-700 dark:bg-slate-950/50 dark:text-slate-200 dark:hover:bg-slate-800"
            >
              <ArrowDownTrayIcon class="h-5 w-5" />
              Exportar Dados
            </button>

            <div class="border-t border-slate-200 pt-4 dark:border-slate-800">
              <h4 class="mb-2 text-sm font-medium text-slate-900 dark:text-white">
                Estatísticas
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-slate-600 dark:text-slate-400">Total de Amostras</span>
                  <span class="font-semibold text-primary-900 dark:text-primary-300">{{ stats.total_samples }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-600 dark:text-slate-400">Por Iniciar</span>
                  <span class="font-semibold text-amber-600 dark:text-amber-300">{{ stats.pending_analysis }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-600 dark:text-slate-400">Em Progresso</span>
                  <span class="font-semibold text-primary-700 dark:text-primary-300">{{ stats.in_progress || 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-600 dark:text-slate-400">Completadas</span>
                  <span class="font-semibold text-emerald-600 dark:text-emerald-300">{{ stats.completed_analysis }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-600 dark:text-slate-400">Descartadas</span>
                  <span class="font-semibold text-rose-600 dark:text-rose-300">{{ stats.total_discarded }}</span>
                </div>
              </div>
            </div>
          </div>
        </ModuleCard>

        <!-- CARTÃO DE STATUS -->
        <ModuleCard>
          <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
            <Cog6ToothIcon class="h-5 w-5 text-primary-800 dark:text-primary-300" />
            Status do Sistema
          </h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-slate-600 dark:text-slate-400">Última atualização</span>
              <span class="text-sm font-medium text-slate-900 dark:text-white">{{ formatDate(new Date()) }}</span>
            </div>

            <div class="flex items-center justify-between">
              <span class="text-sm text-slate-600 dark:text-slate-400">Amostras hoje</span>
              <span class="text-sm font-medium text-emerald-600 dark:text-emerald-300">{{ stats.today_samples || 0 }}</span>
            </div>

            <div class="flex items-center justify-between">
              <span class="text-sm text-slate-600 dark:text-slate-400">Amostras esta semana</span>
              <span class="text-sm font-medium text-primary-700 dark:text-primary-300">{{ stats.week_samples || 0 }}</span>
            </div>
          </div>
        </ModuleCard>

      </div>
      </div>
    </div>

    <!-- SEÇÃO DE DESCARTE DE AMOSTRAS -->
    <div v-if="activeTab === 'discard'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- COLUNA ESQUERDA (2/3 largura) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- LISTAGEM DE DESCARTES -->
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
              <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
                <ArchiveBoxXMarkIcon class="h-5 w-5 text-rose-600 dark:text-rose-300" />
                Descartados Recentemente
                <span class="ml-2 text-sm font-normal text-slate-500 dark:text-slate-400">
                  ({{ recentDiscards.length }} itens)
                </span>
              </h2>
              <div class="w-full sm:w-60">
                <BaseSelect v-model="discardMethodFilter">
                  <option value="">Todos os métodos</option>
                  <option value="incineration">Incineração</option>
                  <option value="chemical_treatment">Tratamento Químico</option>
                  <option value="autoclave">Autoclave</option>
                  <option value="landfill">Aterro</option>
                  <option value="recycling">Reciclagem</option>
                  <option value="return_to_client">Retorno ao Cliente</option>
                </BaseSelect>
              </div>
            </div>
          </div>

          <!-- ESTADO VAZIO -->
          <div v-if="filteredDiscards.length === 0" class="p-12 text-center">
            <ArchiveBoxXMarkIcon class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-700" />
            <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">
              Nenhum descarte encontrado
            </h3>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
              {{ discardMethodFilter ? 'Tente ajustar os filtros de busca' : 'Nenhum descarte registrado ainda' }}
            </p>
          </div>

          <!-- LISTA DE DESCARTES -->
          <div v-else class="divide-y divide-slate-200 dark:divide-slate-800">
            <div 
              v-for="discard in filteredDiscards"
              :key="discard.id"
              class="px-6 py-4 transition-colors duration-200 hover:bg-slate-50 dark:hover:bg-slate-950/50"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="flex h-8 w-8 items-center justify-center rounded-full bg-rose-100 font-semibold text-rose-800 dark:bg-rose-500/15 dark:text-rose-200">
                    <TrashIcon class="h-4 w-4" />
                  </div>
                  <div>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                      {{ discard.sample?.name || 'Amostra Desconhecida' }}
                    </h3>
                    <div class="flex items-center gap-3 mt-1">
                      <span class="text-xs text-slate-500 dark:text-slate-400">
                        {{ discard.sample?.code || 'Sem Código' }}
                      </span>
                      <span class="rounded-full bg-slate-100 px-2 py-1 text-xs text-slate-800 dark:bg-slate-800 dark:text-slate-200">
                        {{ getDiscardMethodLabel(discard.discard_method) }}
                      </span>
                      <span class="text-xs text-slate-500 dark:text-slate-400">
                        {{ formatDate(discard.discarded_at) }}
                      </span>
                    </div>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <div class="text-right mr-4">
                    <span class="text-sm font-semibold text-rose-600 dark:text-rose-300">
                      {{ discard.qty }}
                    </span>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                      por {{ discard.discarded_by?.name || 'Desconhecido' }}
                    </p>
                  </div>
                  <button
                    @click="generateDiscardPdf(discard.id)"
                    class="rounded-full p-2 text-emerald-700 transition hover:bg-emerald-50 hover:text-emerald-900 dark:text-emerald-300 dark:hover:bg-emerald-500/10"
                    title="Gerar Certificado"
                  >
                    <DocumentArrowDownIcon class="h-4 w-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- FORMULÁRIO DE DESCARTE -->
        <div v-if="showDiscardForm" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <div class="bg-gradient-to-r from-rose-700 to-rose-500 px-6 py-4 dark:from-rose-950 dark:to-rose-700">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <TrashIcon class="h-5 w-5" />
              {{ selectedSample ? `Descartar: ${selectedSample.name}` : 'Registrar Descarte' }}
            </h2>
          </div>
          
          <div class="vap-sample-discard-form p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- SELECIONAR AMOSTRA -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <BeakerIcon class="h-4 w-4" />
                  Selecionar Amostra
                  <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="discardForm.sample_id"
                  @change="onSampleSelect"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    discardForm.errors.sample_id 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' 
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                >
                  <option value="">Selecione uma amostra para descartar</option>
                  <option v-for="sample in discardableSamples" :key="sample.id" :value="sample.id">
                    {{ sample.code }} - {{ sample.name }} ({{ getStatusLabel(sample.status) }})
                  </option>
                </select>
                <p v-if="discardForm.errors.sample_id" class="text-xs text-red-600">
                  {{ discardForm.errors.sample_id }}
                </p>
              </div>

              <!-- MÉTODO DE DESCARTE -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <CogIcon class="h-4 w-4" />
                  Método de Descarte
                  <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="discardForm.discard_method"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    discardForm.errors.discard_method 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' 
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                >
                  <option value="">Selecione o método</option>
                  <option value="incineration">Incineração</option>
                  <option value="chemical_treatment">Tratamento Químico</option>
                  <option value="autoclave">Autoclave</option>
                  <option value="landfill">Aterro</option>
                  <option value="recycling">Reciclagem</option>
                  <option value="return_to_client">Retorno ao Cliente</option>
                </select>
                <p v-if="discardForm.errors.discard_method" class="text-xs text-red-600">
                  {{ discardForm.errors.discard_method }}
                </p>
              </div>

              <!-- QUANTIDADE -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <ScaleIcon class="h-4 w-4" />
                  Quantidade
                  <span class="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  v-model="discardForm.qty"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    discardForm.errors.qty 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' 
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                  placeholder="ex: 250g, 500ml, 1 unidade"
                />
                <p v-if="discardForm.errors.qty" class="text-xs text-red-600">
                  {{ discardForm.errors.qty }}
                </p>
              </div>

              <!-- DATA DO DESCARTE -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <CalendarIcon class="h-4 w-4" />
                  Data do Descarte
                </label>
                <input
                  type="datetime-local"
                  v-model="discardForm.discarded_at"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                    'border-gray-300 focus:border-blue-900 focus:ring-blue-900/20'
                  ]"
                />
              </div>

              <!-- BOTÕES DO FORMULÁRIO DE DESCARTE -->
              <div class="md:col-span-2 flex items-center justify-end gap-3 pt-4">
                <button 
                  @click="cancelDiscard"
                  type="button"
                  class="rounded-full border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-colors duration-200 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-200 dark:hover:bg-slate-800"
                >
                  Cancelar
                </button>
                <button 
                  @click="submitDiscard"
                  :disabled="discardForm.processing || !isDiscardFormValid"
                  :class="[
                    'inline-flex items-center gap-2 rounded-full px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200',
                    discardForm.processing || !isDiscardFormValid
                      ? 'cursor-not-allowed bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-500'
                      : 'bg-rose-600 text-white hover:bg-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/30 dark:bg-rose-500 dark:hover:bg-rose-400'
                  ]"
                >
                  <TrashIcon class="h-5 w-5" />
                  {{ discardForm.processing ? 'Processando...' : 'Confirmar Descarte' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- COLUNA DIREITA (1/3 largura) -->
      <div class="space-y-6">
        <!-- INFORMAÇÕES DA AMOSTRA SELECIONADA -->
        <div v-if="selectedSample && showDiscardForm" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
            <InformationCircleIcon class="h-5 w-5 text-primary-800 dark:text-primary-300" />
            Informações da Amostra
          </h3>
          <div class="space-y-4">
            <div>
              <h4 class="text-sm font-medium text-slate-900 dark:text-white">{{ selectedSample.name }}</h4>
              <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ selectedSample.code }}</p>
            </div>
            
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm text-slate-600 dark:text-slate-400">Status</span>
                <span :class="sampleStatusBadgeClass(selectedSample.status)">
                  {{ getStatusLabel(selectedSample.status) }}
                </span>
              </div>
              
              <div class="flex items-center justify-between">
                <span class="text-sm text-slate-600 dark:text-slate-400">Tipo de Amostra</span>
                <span class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ getSampleTypeLabel(selectedSample.sample_type) }}</span>
              </div>
              
              <div class="flex items-center justify-between">
                <span class="text-sm text-slate-600 dark:text-slate-400">Recebido em</span>
                <span class="text-sm text-slate-900 dark:text-slate-100">{{ formatDate(selectedSample.received_at) }}</span>
              </div>
              
              <div v-if="selectedSample.analysis_start_date" class="flex items-center justify-between">
                <span class="text-sm text-slate-600 dark:text-slate-400">Início da Análise</span>
                <span class="text-sm text-slate-900 dark:text-slate-100">{{ formatDate(selectedSample.analysis_start_date) }}</span>
              </div>
              
              <div v-if="selectedSample.analysis_end_date" class="flex items-center justify-between">
                <span class="text-sm text-slate-600 dark:text-slate-400">Fim da Análise</span>
                <span class="text-sm text-slate-900 dark:text-slate-100">{{ formatDate(selectedSample.analysis_end_date) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- CARTÃO DE AÇÕES DE DESCARTE -->
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">
            Ações de Descarte
          </h3>
          <div class="space-y-4">
            <button 
              @click="showDiscardForm = !showDiscardForm"
              :class="[
                'inline-flex w-full items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                showDiscardForm
                  ? 'bg-slate-200 text-slate-700 hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700'
                  : 'bg-rose-600 text-white hover:bg-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/30 dark:bg-rose-500 dark:hover:bg-rose-400'
              ]"
            >
              <PlusCircleIcon class="h-5 w-5" />
              {{ showDiscardForm ? 'Cancelar Novo Descarte' : 'Novo Descarte' }}
            </button>
            
            <button 
              @click="exportDiscards"
              type="button"
              class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition-colors duration-200 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-200 dark:hover:bg-slate-800"
            >
              <ArrowDownTrayIcon class="h-5 w-5" />
              Exportar Descartados
            </button>

            <!-- ESTATÍSTICAS DE DESCARTE -->
            <div class="border-t border-slate-200 pt-4 dark:border-slate-800">
              <h4 class="mb-2 text-sm font-medium text-slate-900 dark:text-white">
                Estatísticas de Descarte
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-slate-600 dark:text-slate-400">Total Descartado</span>
                  <span class="font-semibold text-rose-600 dark:text-rose-300">{{ stats.total_discarded }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-600 dark:text-slate-400">Este Mês</span>
                  <span class="font-semibold text-rose-600 dark:text-rose-300">{{ stats.discarded_this_month }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-600 dark:text-slate-400">Por Incineração</span>
                  <span class="text-sm text-slate-700 dark:text-slate-300">{{ getDiscardCountByMethod('incineration') }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-600 dark:text-slate-400">Por Autoclave</span>
                  <span class="text-sm text-slate-700 dark:text-slate-300">{{ getDiscardCountByMethod('autoclave') }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- CARTÃO DE AVISO -->
        <div class="rounded-3xl border border-rose-200 bg-rose-50 p-6 dark:border-rose-500/30 dark:bg-rose-500/10">
          <div class="flex items-start gap-3">
            <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 text-rose-600 dark:text-rose-300" />
            <div>
              <h4 class="mb-2 text-sm font-semibold text-rose-900 dark:text-rose-100">
                Aviso importante
              </h4>
              <p class="text-xs leading-5 text-rose-700 dark:text-rose-200">
                O descarte de amostras é irreversível. Confirme o código, o método e a quantidade antes de registar a evidência.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- RODAPÉ -->
    <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
      <div class="text-sm text-slate-500 dark:text-slate-400">
        Última atualização: {{ formatDate(new Date()) }}
      </div>
      <div class="flex items-center gap-4">
        <button 
          @click="refreshData"
          type="button"
          class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-colors duration-200 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-200 dark:hover:bg-slate-800"
        >
          <ArrowPathIcon class="h-5 w-5" />
          Atualizar
        </button>
      </div>
    </div>

    <!-- MENSAGENS DE SUCESSO/ERRO -->
    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="transform opacity-0 translate-y-2"
      enter-to-class="transform opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="transform opacity-100 translate-y-0"
      leave-to-class="transform opacity-0 translate-y-2"
    >
      <div 
        v-if="$page.props.flash.message"
        :class="[
          'fixed bottom-4 right-4 z-50 max-w-md rounded-3xl border p-4 shadow-2xl backdrop-blur',
          $page.props.flash.type === 'error' ? 'border-rose-200 bg-rose-50/95 dark:border-rose-500/30 dark:bg-rose-950/95' :
          $page.props.flash.type === 'warning' ? 'border-amber-200 bg-amber-50/95 dark:border-amber-500/30 dark:bg-amber-950/95' :
          'border-emerald-200 bg-emerald-50/95 dark:border-emerald-500/30 dark:bg-emerald-950/95'
        ]"
      >
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <CheckCircleIcon 
              v-if="$page.props.flash.type === 'success'" 
              class="h-5 w-5 text-emerald-500 dark:text-emerald-300"
            />
            <ExclamationTriangleIcon 
              v-else 
              class="h-5 w-5 text-rose-500 dark:text-rose-300"
            />
          </div>
          <div class="ml-3">
            <p :class="[
              'text-sm font-medium',
              $page.props.flash.type === 'error' ? 'text-rose-800 dark:text-rose-100' :
              $page.props.flash.type === 'warning' ? 'text-amber-800 dark:text-amber-100' :
              'text-emerald-800 dark:text-emerald-100'
            ]">
              {{ $page.props.flash.message }}
            </p>
            <div v-if="$page.props.flash.sample_id" class="mt-2">
              <button
                @click="generateEntryPdf($page.props.flash.sample_id)"
                class="text-sm font-semibold text-primary-700 hover:text-primary-600 dark:text-primary-300 dark:hover:text-primary-200"
              >
                Gerar PDF da Entrada
              </button>
            </div>
            <div v-if="$page.props.flash.discard_id" class="mt-2">
              <button
                @click="generateDiscardPdf($page.props.flash.discard_id)"
                class="text-sm font-semibold text-primary-700 hover:text-primary-600 dark:text-primary-300 dark:hover:text-primary-200"
              >
                Gerar Certificado de Descarte
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import ModuleCard from '@/Components/base/ModuleCard.vue'
import ModuleHero from '@/Components/base/ModuleHero.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import { 
  BeakerIcon,
  DocumentTextIcon,
  TagIcon,
  QrCodeIcon,
  CubeIcon,
  UserGroupIcon,
  BuildingOfficeIcon,
  BuildingLibraryIcon,
  CalendarIcon,
  QueueListIcon,
  ArchiveBoxIcon,
  ClipboardDocumentListIcon,
  ChatBubbleLeftRightIcon,
  ClockIcon,
  PlayIcon,
  StopIcon,
  CheckCircleIcon,
  ArrowPathIcon,
  Cog6ToothIcon,
  TrashIcon,
  ArchiveBoxXMarkIcon,
  InformationCircleIcon,
  ExclamationTriangleIcon,
  ArrowDownTrayIcon,
  CloudArrowUpIcon,
  CogIcon,
  ScaleIcon,
  PlusCircleIcon,
  EyeIcon,
  PencilSquareIcon,
  DocumentArrowDownIcon
} from '@heroicons/vue/24/outline'

// Obter props da página
const page = usePage()

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
    COMPLETADO: 'bg-emerald-100 text-emerald-800 ring-emerald-600/20 dark:bg-emerald-500/15 dark:text-emerald-200 dark:ring-emerald-400/20',
    EN_PROGRESO: 'bg-primary-100 text-primary-800 ring-primary-600/20 dark:bg-primary-500/15 dark:text-primary-200 dark:ring-primary-400/20',
    POR_INICIAR: 'bg-amber-100 text-amber-800 ring-amber-600/20 dark:bg-amber-500/15 dark:text-amber-200 dark:ring-amber-400/20',
    CANCELADO: 'bg-rose-100 text-rose-800 ring-rose-600/20 dark:bg-rose-500/15 dark:text-rose-200 dark:ring-rose-400/20',
    EN_PAUSA: 'bg-slate-100 text-slate-800 ring-slate-600/20 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-600',
  }

  return [
    'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset',
    classes[status] || classes.EN_PAUSA,
  ]
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
.vap-sample-entry-form :is(label, .form-label),
.vap-sample-discard-form :is(label, .form-label) {
  color: #334155;
  font-weight: 700;
}

.vap-sample-entry-form :is(input, select, textarea),
.vap-sample-discard-form :is(input, select, textarea) {
  width: 100%;
  border: 1px solid #d8cfbe;
  border-radius: 1rem;
  background: #fffdf7;
  color: #17231f;
  box-shadow: 0 1px 2px rgb(15 23 42 / 0.05);
  transition: border-color 160ms ease, box-shadow 160ms ease, background-color 160ms ease;
}

.vap-sample-entry-form :is(input, select, textarea):focus,
.vap-sample-discard-form :is(input, select, textarea):focus {
  border-color: #0f766e;
  box-shadow: 0 0 0 4px rgb(15 118 110 / 0.14);
  outline: none;
}

.vap-sample-entry-form :is(input, textarea)::placeholder,
.vap-sample-discard-form :is(input, textarea)::placeholder {
  color: #94a3b8;
}

.vap-sample-entry-form select[multiple] {
  min-height: 10rem;
  background-image: linear-gradient(180deg, rgb(255 253 247 / 0.96), rgb(248 244 235 / 0.9));
}

.vap-sample-entry-form select[multiple] option,
.vap-sample-discard-form select option {
  padding: 0.55rem 0.75rem;
}

.vap-sample-entry-form p.text-xs,
.vap-sample-discard-form p.text-xs {
  line-height: 1.55;
}

:global(.dark) .vap-sample-entry-form :is(label, .form-label),
:global(.dark) .vap-sample-discard-form :is(label, .form-label) {
  color: #e2e8f0;
}

:global(.dark) .vap-sample-entry-form :is(input, select, textarea),
:global(.dark) .vap-sample-discard-form :is(input, select, textarea) {
  border-color: #25443c;
  background: #081512;
  color: #f7f1e7;
  color-scheme: dark;
}

:global(.dark) .vap-sample-entry-form :is(input, select, textarea):focus,
:global(.dark) .vap-sample-discard-form :is(input, select, textarea):focus {
  border-color: #5eead4;
  box-shadow: 0 0 0 4px rgb(20 184 166 / 0.18);
}

:global(.dark) .vap-sample-entry-form :is(input, textarea)::placeholder,
:global(.dark) .vap-sample-discard-form :is(input, textarea)::placeholder {
  color: #64748b;
}

:global(.dark) .vap-sample-entry-form select[multiple] {
  background-image: linear-gradient(180deg, rgb(8 21 18 / 0.98), rgb(15 23 42 / 0.92));
}

:global(.dark) .vap-sample-entry-form :is(.text-gray-500, .text-gray-600),
:global(.dark) .vap-sample-discard-form :is(.text-gray-500, .text-gray-600) {
  color: #94a3b8;
}

:deep(.apexcharts-tooltip),
:deep(.apexcharts-menu) {
  border-radius: 1rem !important;
  border-color: rgb(203 213 225 / 0.9) !important;
  box-shadow: 0 18px 45px rgb(15 23 42 / 0.16) !important;
}

:global(.dark) :deep(.apexcharts-tooltip),
:global(.dark) :deep(.apexcharts-menu) {
  border-color: rgb(51 65 85 / 0.9) !important;
  background: #0f172a !important;
  color: #e2e8f0 !important;
}
</style>
