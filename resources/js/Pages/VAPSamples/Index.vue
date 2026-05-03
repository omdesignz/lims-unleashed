<template>
  <div class="space-y-8">
    <!-- CARTÃO DE CABEÇALHO -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <BeakerIcon class="h-7 w-7 text-blue-900" />
            {{ $page.props.title || 'Gestão de Amostras VAP' }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ activeTab === 'entry' ? 'Registre novas amostras e gerencie informações' : 'Registre descartes de amostras e gere certificados' }}
            <span v-if="activeTab === 'discard'" class="font-semibold text-blue-900">
              (Modo de Descarte)
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ stats.total_samples }} Amostras
          </span>
          <div class="flex border border-gray-300 rounded-lg overflow-hidden">
            <button
              @click="activeTab = 'entry'"
              :class="[
                'px-4 py-2 text-sm font-medium transition-colors duration-200',
                activeTab === 'entry'
                  ? 'bg-blue-900 text-white'
                  : 'bg-white text-gray-700 hover:bg-gray-50'
              ]"
            >
              Entrada de Amostras
            </button>
            <button
              @click="activeTab = 'discard'"
              :class="[
                'px-4 py-2 text-sm font-medium transition-colors duration-200',
                activeTab === 'discard'
                  ? 'bg-blue-900 text-white'
                  : 'bg-white text-gray-700 hover:bg-gray-50'
              ]"
            >
              Descarte de Amostras
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- SEÇÃO DE ENTRADA DE AMOSTRAS -->
    <div v-if="activeTab === 'entry'" class="space-y-8">
      <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">Ritmo de receção</h2>
              <p class="mt-1 text-sm text-gray-500">
                Volume de amostras recebidas nos últimos 7 dias para antecipar carga operacional.
              </p>
            </div>
            <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right">
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Janela monitorizada</p>
              <p class="mt-2 text-2xl font-semibold text-slate-900">{{ intakeTrendTotal }}</p>
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
        </article>

        <div class="grid gap-6">
          <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h2 class="text-lg font-semibold text-gray-900">Estado do fluxo</h2>
                <p class="mt-1 text-sm text-gray-500">Distribuição atual da carteira de amostras.</p>
              </div>
              <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Amostras</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ lifecycleStatusTotal }}</p>
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
          </article>

          <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h2 class="text-lg font-semibold text-gray-900">Pressão de retenção</h2>
                <p class="mt-1 text-sm text-gray-500">Acompanhe descarte próximo, retenção vencida e histórico descartado.</p>
              </div>
              <span class="inline-flex items-center rounded-full bg-rose-50 px-3 py-1 text-sm font-medium text-rose-700">
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
          </article>
        </div>
      </section>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- COLUNA ESQUERDA (2/3 largura) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- LISTAGEM DE AMOSTRAS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <ArchiveBoxIcon class="h-5 w-5 text-blue-900" />
                Amostras Registradas
                <span class="text-sm font-normal text-gray-500 ml-2">
                  ({{ samples.length }} itens)
                </span>
              </h2>
              <div class="flex items-center gap-3">
                <input
                  type="text"
                  v-model="searchQuery"
                  placeholder="Buscar amostra..."
                  class="rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900/20 w-64"
                />
                <select
                  v-model="statusFilter"
                  class="rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900/20"
                >
                  <option value="">Todos os status</option>
                  <option value="POR_INICIAR">Por Iniciar</option>
                  <option value="EN_PROGRESO">Em Progresso</option>
                  <option value="COMPLETADO">Completado</option>
                  <option value="CANCELADO">Cancelado</option>
                  <option value="EN_PAUSA">Em Pausa</option>
                </select>
              </div>
            </div>
          </div>

          <!-- ESTADO VAZIO -->
          <div v-if="filteredSamples.length === 0" class="p-12 text-center">
            <BeakerIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              Nenhuma amostra encontrada
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              {{ searchQuery || statusFilter ? 'Tente ajustar os filtros de busca' : 'Comece registrando sua primeira amostra' }}
            </p>
          </div>

          <!-- TABELA DE AMOSTRAS -->
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Código
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Nome
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Tipo
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Cliente
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Recebido em
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Ações
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr 
                  v-for="sample in filteredSamples"
                  :key="sample.id"
                  class="hover:bg-gray-50 transition-colors duration-150"
                >
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-blue-900">
                      {{ sample.code }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">
                      {{ sample.name }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-700">
                      {{ getSampleTypeLabel(sample.sample_type) }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-700">
                      {{ sample.customer?.name || 'N/A' }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="[
                      'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                      sample.status === 'COMPLETADO' ? 'bg-green-100 text-green-800' :
                      sample.status === 'EN_PROGRESO' ? 'bg-blue-100 text-blue-800' :
                      sample.status === 'POR_INICIAR' ? 'bg-yellow-100 text-yellow-800' :
                      sample.status === 'CANCELADO' ? 'bg-red-100 text-red-800' :
                      'bg-gray-100 text-gray-800'
                    ]">
                      {{ getStatusLabel(sample.status) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(sample.received_at) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex items-center gap-2">
                      <button
                        @click="viewSample(sample.id)"
                        class="text-blue-600 hover:text-blue-900 p-1 rounded hover:bg-blue-50"
                        title="Visualizar"
                      >
                        <EyeIcon class="h-4 w-4" />
                      </button>
                      <button
                        @click="editSample(sample)"
                        class="text-yellow-600 hover:text-yellow-900 p-1 rounded hover:bg-yellow-50"
                        title="Editar"
                      >
                        <PencilSquareIcon class="h-4 w-4" />
                      </button>
                      <button
                        @click="generateEntryPdf(sample.id)"
                        class="text-green-600 hover:text-green-900 p-1 rounded hover:bg-green-50"
                        title="Gerar PDF"
                      >
                        <DocumentArrowDownIcon class="h-4 w-4" />
                      </button>
                      <button
                        v-if="sample.status === 'COMPLETADO' || sample.status === 'CANCELADO'"
                        @click="prepareForDiscard(sample)"
                        class="text-red-600 hover:text-red-900 p-1 rounded hover:bg-red-50"
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
          <div v-if="samples.length > 0" class="px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
              <div class="text-sm text-gray-500">
                Mostrando {{ Math.min(filteredSamples.length, 10) }} de {{ filteredSamples.length }} resultados
              </div>
              <div class="flex items-center gap-2">
                <button
                  @click="currentPage--"
                  :disabled="currentPage === 1"
                  :class="[
                    'rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium',
                    currentPage === 1 ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-50'
                  ]"
                >
                  Anterior
                </button>
                <span class="text-sm text-gray-700">
                  Página {{ currentPage }} de {{ totalPages }}
                </span>
                <button
                  @click="currentPage++"
                  :disabled="currentPage === totalPages"
                  :class="[
                    'rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium',
                    currentPage === totalPages ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-50'
                  ]"
                >
                  Próxima
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="portalAnalysisRequests.length" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <ClipboardDocumentListIcon class="h-5 w-5 text-cyan-700" />
                Pedidos do Portal para Validar
              </h2>
              <span class="inline-flex items-center rounded-full bg-cyan-50 px-3 py-1 text-sm font-medium text-cyan-800">
                {{ portalAnalysisRequests.length }} pendentes
              </span>
            </div>
          </div>

          <div class="divide-y divide-gray-100">
            <div
              v-for="request in portalAnalysisRequests.slice(0, 5)"
              :key="request.id"
              class="px-6 py-4 flex items-start justify-between gap-4"
            >
              <div>
                <p class="text-sm font-semibold text-gray-900">{{ request.title }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ request.reference || 'Sem referência' }} · {{ request.customer || 'Sem cliente' }}</p>
                <p class="mt-2 text-sm text-gray-600">{{ (request.requested_profile_names || []).join(', ') || 'Sem perfis declarados' }}</p>
              </div>
              <button
                type="button"
                @click="prefillFromPortalRequest(request)"
                class="inline-flex items-center rounded-lg bg-cyan-700 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-600"
              >
                Pré-preencher
              </button>
            </div>
          </div>
        </div>

        <!-- CARTÃO DE DETALHES DA AMOSTRA -->
        <div v-if="editingSample" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <PencilSquareIcon class="h-5 w-5" />
              {{ editingSample.id ? 'Editar Amostra' : 'Nova Amostra' }}
            </h2>
          </div>
          
          <div class="p-6">
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

              <div v-if="selectedProduct" class="rounded-xl border border-blue-100 bg-blue-50/70 px-4 py-3 text-sm text-blue-950">
                <p class="font-semibold">Escopo analítico base</p>
                <p class="mt-1 text-xs text-blue-800">
                  Matriz: {{ selectedProduct.matrix || 'Sem matriz definida' }} ·
                  {{ selectedProduct.profiles?.length || 0 }} perfis disponíveis para o produto
                </p>
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

              <div v-else class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-700">
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

              <div class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 md:col-span-2 lg:col-span-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                  <div>
                    <p class="text-sm font-semibold text-slate-900">Checklist analítico previsto</p>
                    <p class="text-xs text-slate-600">
                      A receção já define o escopo esperado para os técnicos.
                    </p>
                  </div>
                  <div class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                    {{ selectedProfileIds.length || availableProfiles.length || 0 }} perfis · {{ requiredParameterPreview.length }} parâmetros
                  </div>
                </div>

                <div v-if="selectedProfileSummaries.length" class="flex flex-wrap gap-2">
                  <span
                    v-for="profile in selectedProfileSummaries"
                    :key="profile.id"
                    class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-900"
                  >
                    {{ profile.name }}
                  </span>
                </div>

                <div v-if="requiredParameterPreview.length" class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                  <div
                    v-for="parameter in requiredParameterPreview"
                    :key="parameter.id"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2"
                  >
                    <p class="text-sm font-medium text-slate-900">
                      {{ parameter.name }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                      {{ parameter.code || 'Sem código' }}
                    </p>
                    <p class="mt-1 text-xs text-slate-600">
                      {{ parameter.profiles.join(', ') }}
                    </p>
                  </div>
                </div>

                <p v-else class="text-xs text-slate-500">
                  Selecione um produto com matriz analítica ou escolha perfis para ver os parâmetros obrigatórios.
                </p>
              </div>

              <div class="space-y-4 rounded-2xl border border-amber-200 bg-amber-50/70 px-5 py-4 md:col-span-2 lg:col-span-3">
                <div>
                  <p class="text-sm font-semibold text-amber-950">Avaliação de condicionamento na receção</p>
                  <p class="text-xs text-amber-800">
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
            <div class="mt-6 flex items-center justify-end gap-3">
              <button 
                @click="cancelEdit"
                type="button"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
              >
                Cancelar
              </button>
              <button 
                @click="editingSample.id ? updateSample() : submitSample()"
                :disabled="form.processing || !isFormValid"
                :class="[
                  'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200',
                  form.processing || !isFormValid
                    ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                    : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
                ]"
              >
                <CheckCircleIcon class="h-5 w-5" />
                {{ form.processing ? 'Processando...' : (editingSample.id ? 'Atualizar Amostra' : 'Salvar Amostra') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- COLUNA DIREITA (1/3 largura) -->
      <div class="space-y-6">
        <!-- CARTÃO DE AÇÕES -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Ações Rápidas
          </h3>
          <div class="space-y-4">
            <button 
              @click="newSample"
              type="button"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
            >
              <PlusCircleIcon class="h-5 w-5" />
              Nova Amostra
            </button>
            
            <button 
              @click="exportData"
              type="button"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <ArrowDownTrayIcon class="h-5 w-5" />
              Exportar Dados
            </button>

            <!-- ESTATÍSTICAS -->
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                Estatísticas
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Total de Amostras</span>
                  <span class="font-semibold text-blue-900">{{ stats.total_samples }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Por Iniciar</span>
                  <span class="font-semibold text-yellow-500">{{ stats.pending_analysis }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Em Progresso</span>
                  <span class="font-semibold text-blue-500">{{ stats.in_progress || 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Completadas</span>
                  <span class="font-semibold text-green-500">{{ stats.completed_analysis }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Descartadas</span>
                  <span class="font-semibold text-red-600">{{ stats.total_discarded }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- CARTÃO DE STATUS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <Cog6ToothIcon class="h-5 w-5 text-blue-900" />
            Status do Sistema
          </h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Última atualização</span>
              <span class="text-sm font-medium text-gray-900">{{ formatDate(new Date()) }}</span>
            </div>
            
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Amostras hoje</span>
              <span class="text-sm font-medium text-green-600">{{ stats.today_samples || 0 }}</span>
            </div>
            
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Amostras esta semana</span>
              <span class="text-sm font-medium text-blue-600">{{ stats.week_samples || 0 }}</span>
            </div>
          </div>
        </div>
      </div>
      </div>
    </div>

    <!-- SEÇÃO DE DESCARTE DE AMOSTRAS -->
    <div v-if="activeTab === 'discard'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- COLUNA ESQUERDA (2/3 largura) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- LISTAGEM DE DESCARTES -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <ArchiveBoxXMarkIcon class="h-5 w-5 text-red-600" />
                Descartados Recentemente
                <span class="text-sm font-normal text-gray-500 ml-2">
                  ({{ recentDiscards.length }} itens)
                </span>
              </h2>
              <div class="flex items-center gap-3">
                <select
                  v-model="discardMethodFilter"
                  class="rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900/20"
                >
                  <option value="">Todos os métodos</option>
                  <option value="incineration">Incineração</option>
                  <option value="chemical_treatment">Tratamento Químico</option>
                  <option value="autoclave">Autoclave</option>
                  <option value="landfill">Aterro</option>
                  <option value="recycling">Reciclagem</option>
                  <option value="return_to_client">Retorno ao Cliente</option>
                </select>
              </div>
            </div>
          </div>

          <!-- ESTADO VAZIO -->
          <div v-if="filteredDiscards.length === 0" class="p-12 text-center">
            <ArchiveBoxXMarkIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              Nenhum descarte encontrado
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              {{ discardMethodFilter ? 'Tente ajustar os filtros de busca' : 'Nenhum descarte registrado ainda' }}
            </p>
          </div>

          <!-- LISTA DE DESCARTES -->
          <div v-else class="divide-y divide-gray-200">
            <div 
              v-for="discard in filteredDiscards"
              :key="discard.id"
              class="px-6 py-4 hover:bg-gray-50 transition-colors duration-200"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 text-red-800 font-semibold">
                    <TrashIcon class="h-4 w-4" />
                  </div>
                  <div>
                    <h3 class="text-sm font-semibold text-gray-900">
                      {{ discard.sample?.name || 'Amostra Desconhecida' }}
                    </h3>
                    <div class="flex items-center gap-3 mt-1">
                      <span class="text-xs text-gray-500">
                        {{ discard.sample?.code || 'Sem Código' }}
                      </span>
                      <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-800">
                        {{ getDiscardMethodLabel(discard.discard_method) }}
                      </span>
                      <span class="text-xs text-gray-500">
                        {{ formatDate(discard.discarded_at) }}
                      </span>
                    </div>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <div class="text-right mr-4">
                    <span class="text-sm font-semibold text-red-600">
                      {{ discard.qty }}
                    </span>
                    <p class="text-xs text-gray-500 mt-1">
                      por {{ discard.discarded_by?.name || 'Desconhecido' }}
                    </p>
                  </div>
                  <button
                    @click="generateDiscardPdf(discard.id)"
                    class="text-green-600 hover:text-green-900 p-1 rounded hover:bg-green-50"
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
        <div v-if="showDiscardForm" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-red-600 to-red-500 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <TrashIcon class="h-5 w-5" />
              {{ selectedSample ? `Descartar: ${selectedSample.name}` : 'Registrar Descarte' }}
            </h2>
          </div>
          
          <div class="p-6">
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
                  class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
                >
                  Cancelar
                </button>
                <button 
                  @click="submitDiscard"
                  :disabled="discardForm.processing || !isDiscardFormValid"
                  :class="[
                    'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200',
                    discardForm.processing || !isDiscardFormValid
                      ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                      : 'bg-gradient-to-r from-red-600 to-red-500 text-white hover:from-red-500 hover:to-red-400 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2'
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
        <div v-if="selectedSample && showDiscardForm" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <InformationCircleIcon class="h-5 w-5 text-blue-900" />
            Informações da Amostra
          </h3>
          <div class="space-y-4">
            <div>
              <h4 class="text-sm font-medium text-gray-900">{{ selectedSample.name }}</h4>
              <p class="text-xs text-gray-500 mt-1">{{ selectedSample.code }}</p>
            </div>
            
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Status</span>
                <span :class="[
                  'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                  selectedSample.status === 'COMPLETADO' ? 'bg-green-100 text-green-800' :
                  selectedSample.status === 'EN_PROGRESO' ? 'bg-blue-100 text-blue-800' :
                  selectedSample.status === 'POR_INICIAR' ? 'bg-yellow-100 text-yellow-800' :
                  'bg-gray-100 text-gray-800'
                ]">
                  {{ getStatusLabel(selectedSample.status) }}
                </span>
              </div>
              
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Tipo de Amostra</span>
                <span class="text-sm font-medium text-gray-900">{{ getSampleTypeLabel(selectedSample.sample_type) }}</span>
              </div>
              
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Recebido em</span>
                <span class="text-sm text-gray-900">{{ formatDate(selectedSample.received_at) }}</span>
              </div>
              
              <div v-if="selectedSample.analysis_start_date" class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Início da Análise</span>
                <span class="text-sm text-gray-900">{{ formatDate(selectedSample.analysis_start_date) }}</span>
              </div>
              
              <div v-if="selectedSample.analysis_end_date" class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Fim da Análise</span>
                <span class="text-sm text-gray-900">{{ formatDate(selectedSample.analysis_end_date) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- CARTÃO DE AÇÕES DE DESCARTE -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Ações de Descarte
          </h3>
          <div class="space-y-4">
            <button 
              @click="showDiscardForm = !showDiscardForm"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                showDiscardForm
                  ? 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                  : 'bg-gradient-to-r from-red-600 to-red-500 text-white hover:from-red-500 hover:to-red-400 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2'
              ]"
            >
              <PlusCircleIcon class="h-5 w-5" />
              {{ showDiscardForm ? 'Cancelar Novo Descarte' : 'Novo Descarte' }}
            </button>
            
            <button 
              @click="exportDiscards"
              type="button"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <ArrowDownTrayIcon class="h-5 w-5" />
              Exportar Descartados
            </button>

            <!-- ESTATÍSTICAS DE DESCARTE -->
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                Estatísticas de Descarte
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Total Descartado</span>
                  <span class="font-semibold text-red-600">{{ stats.total_discarded }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Este Mês</span>
                  <span class="font-semibold text-red-600">{{ stats.discarded_this_month }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Por Incineração</span>
                  <span class="text-sm text-gray-700">{{ getDiscardCountByMethod('incineration') }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Por Autoclave</span>
                  <span class="text-sm text-gray-700">{{ getDiscardCountByMethod('autoclave') }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- CARTÃO DE AVISO -->
        <div class="bg-red-50 border border-red-200 rounded-xl p-6">
          <div class="flex items-start gap-3">
            <ExclamationTriangleIcon class="h-5 w-5 text-red-600 mt-0.5" />
            <div>
              <h4 class="text-sm font-semibold text-red-900 mb-2">
                ⚠️ Aviso Importante
              </h4>
              <p class="text-xs text-red-700">
                O descarte de amostras é uma ação IRREVERSÍVEL. Uma vez descartada, a amostra NÃO pode ser recuperada. Certifique-se de que esta é a amostra correta antes de prosseguir.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- RODAPÉ -->
    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
      <div class="text-sm text-gray-500">
        Última atualização: {{ formatDate(new Date()) }}
      </div>
      <div class="flex items-center gap-4">
        <button 
          @click="refreshData"
          type="button"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
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
          'fixed bottom-4 right-4 rounded-lg p-4 shadow-lg z-50 max-w-md',
          $page.props.flash.type === 'error' ? 'bg-red-50 border border-red-200' :
          $page.props.flash.type === 'warning' ? 'bg-yellow-50 border border-yellow-200' :
          'bg-green-50 border border-green-200'
        ]"
      >
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <CheckCircleIcon 
              v-if="$page.props.flash.type === 'success'" 
              class="h-5 w-5 text-green-400" 
            />
            <ExclamationTriangleIcon 
              v-else 
              class="h-5 w-5 text-red-400" 
            />
          </div>
          <div class="ml-3">
            <p :class="[
              'text-sm font-medium',
              $page.props.flash.type === 'error' ? 'text-red-800' :
              $page.props.flash.type === 'warning' ? 'text-yellow-800' :
              'text-green-800'
            ]">
              {{ $page.props.flash.message }}
            </p>
            <div v-if="$page.props.flash.sample_id" class="mt-2">
              <button
                @click="generateEntryPdf($page.props.flash.sample_id)"
                class="text-sm font-medium text-blue-600 hover:text-blue-500"
              >
                Gerar PDF da Entrada
              </button>
            </div>
            <div v-if="$page.props.flash.discard_id" class="mt-2">
              <button
                @click="generateDiscardPdf($page.props.flash.discard_id)"
                class="text-sm font-medium text-blue-600 hover:text-blue-500"
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
import { ref, computed, reactive, watch } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
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
const showDiscardForm = ref(false)
const searchQuery = ref('')
const statusFilter = ref('')
const discardMethodFilter = ref('')
const currentPage = ref(1)
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

const intakeTrendChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    zoom: { enabled: false },
    fontFamily: 'inherit',
  },
  colors: ['#1d4ed8'],
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
    borderColor: '#e5e7eb',
    strokeDashArray: 4,
  },
  xaxis: {
    categories: charts.value.intake_trend?.categories || [],
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: {
      style: { colors: '#6b7280' },
    },
  },
  yaxis: {
    min: 0,
    forceNiceScale: true,
    labels: {
      style: { colors: '#6b7280' },
    },
  },
  tooltip: {
    y: {
      formatter: (value) => `${value} amostra${value === 1 ? '' : 's'}`,
    },
  },
  legend: { show: false },
}))

const lifecycleStatusChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  labels: charts.value.lifecycle_status?.labels || [],
  colors: ['#f59e0b', '#3b82f6', '#64748b', '#10b981', '#ef4444'],
  stroke: {
    colors: ['#ffffff'],
  },
  legend: {
    position: 'bottom',
    labels: { colors: '#334155' },
  },
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
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  colors: ['#0f766e'],
  dataLabels: { enabled: false },
  grid: {
    borderColor: '#e5e7eb',
    strokeDashArray: 4,
  },
  xaxis: {
    categories: charts.value.retention_pressure?.labels || [],
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: {
      style: { colors: '#6b7280' },
    },
  },
  yaxis: {
    min: 0,
    forceNiceScale: true,
    labels: {
      style: { colors: '#6b7280' },
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
  client_submitted_info: {
    request_origin: 'client',
    product_id: null,
    matrix_id: null,
    packaging_id: null,
    requested_profile_ids: [],
    conditioning_status: null,
    packaging_condition: '',
    temperature_condition: '',
    integrity_observations: '',
    chain_of_custody_notes: '',
  }
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

const filteredSamples = computed(() => {
  let filtered = samples.value
  
  // Filtrar por busca
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(sample => 
      sample.name.toLowerCase().includes(query) ||
      sample.code.toLowerCase().includes(query) ||
      (sample.customer?.name && sample.customer.name.toLowerCase().includes(query))
    )
  }
  
  // Filtrar por status
  if (statusFilter.value) {
    filtered = filtered.filter(sample => sample.status === statusFilter.value)
  }
  
  // Paginação
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return filtered.slice(start, end)
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
  return Math.ceil(samples.value.length / itemsPerPage)
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
  form.client_submitted_info = {
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
  }
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
    return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
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
  form.client_submitted_info = {
    request_origin: 'client',
    product_id: null,
    matrix_id: null,
    packaging_id: null,
    requested_profile_ids: [],
    conditioning_status: null,
    packaging_condition: '',
    temperature_condition: '',
    integrity_observations: '',
    chain_of_custody_notes: '',
  }
  
  // Definir data de recebimento padrão
  const now = new Date()
  const timezoneOffset = now.getTimezoneOffset() * 60000
  const localISOTime = new Date(now - timezoneOffset).toISOString().slice(0, 16)
  form.received_at = localISOTime
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
    form.client_submitted_info = {
      request_origin: 'client',
      product_id: null,
      matrix_id: null,
      packaging_id: null,
      requested_profile_ids: [],
      conditioning_status: null,
      packaging_condition: '',
      temperature_condition: '',
      integrity_observations: '',
      chain_of_custody_notes: '',
    }
  }
}

const viewSample = (sampleId) => {
  // Aqui você pode implementar a visualização detalhada
  console.log('Visualizar amostra:', sampleId)
  // Pode abrir um modal ou navegar para uma página de detalhes
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

const exportDiscards = () => {
  window.open(route('vap_samples.discards.export'), '_blank')
}

const refreshData = () => {
  window.location.reload()
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
/* Estilos personalizados se necessário */
</style>
