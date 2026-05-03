<template>
  <div class="space-y-8">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="space-y-2">
          <div class="inline-flex rounded-full bg-cyan-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">
            Gestão de solicitações
          </div>
          <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Pedidos do cliente</h1>
          <p class="max-w-3xl text-sm leading-6 text-slate-600">
            Registe pedidos estruturados para análises, colheitas, documentos, certificados e apoio operacional. O histórico abaixo mostra o estado de cada solicitação.
          </p>
        </div>
        <div class="flex flex-wrap gap-3">
          <button
            type="button"
            class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
            @click="openNewRequest()"
          >
            Nova solicitação
          </button>
          <a
            :href="exportUrl"
            class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
          >
            Exportar CSV
          </a>
        </div>
      </div>

      <div class="mt-6 grid gap-4 md:grid-cols-4">
        <article v-for="card in overviewCards" :key="card.label" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <p class="text-sm font-medium text-slate-500">{{ card.label }}</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
          <p class="mt-1 text-xs text-slate-500">{{ card.caption }}</p>
        </article>
      </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
      <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">Serviços disponíveis</h2>
              <p class="mt-1 text-sm text-slate-500">Escolha um serviço para abrir o pedido com o formulário certo.</p>
            </div>
          </div>

          <div class="mt-5 grid gap-3">
            <button
              v-for="service in service_catalog"
              :key="service.type"
              type="button"
              class="rounded-2xl border p-4 text-left transition"
              :class="selectedType === service.type ? 'border-cyan-300 bg-cyan-50' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'"
              @click="selectService(service.type, true)"
            >
              <div class="flex items-center justify-between gap-3">
                <div>
                  <div class="font-semibold text-slate-900">{{ service.title }}</div>
                  <div class="mt-1 text-sm text-slate-600">{{ service.description }}</div>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide" :class="selectedType === service.type ? 'bg-cyan-100 text-cyan-800' : 'bg-slate-100 text-slate-600'">
                  {{ serviceLabel(service.type) }}
                </span>
              </div>
            </button>
          </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">Filtros</h2>
              <p class="mt-1 text-sm text-slate-500">Refine a lista de pedidos sem perder o contexto.</p>
            </div>
            <button type="button" class="text-sm font-medium text-cyan-700 hover:text-cyan-800" @click="resetFilters">
              Limpar
            </button>
          </div>

          <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-1">
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-700">Pesquisar</label>
              <input
                v-model="query.search"
                type="search"
                placeholder="Referência, título ou descrição"
                class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
              >
            </div>
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-700">Estado</label>
              <select
                v-model="query.status_filter"
                class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
              >
                <option value="">Todos</option>
                <option value="pending">Pendente</option>
                <option value="in_progress">Em tratamento</option>
                <option value="completed">Concluída</option>
                <option value="cancelled">Cancelada</option>
              </select>
            </div>
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-700">Tipo de pedido</label>
              <select
                v-model="query.request_type"
                class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
              >
                <option value="">Todos</option>
                <option v-for="service in service_catalog" :key="service.type" :value="service.type">
                  {{ service.title }}
                </option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="space-y-6">
        <section v-if="showRequestForm" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">Nova solicitação</h2>
              <p class="mt-1 text-sm text-slate-500">{{ selectedService?.description || 'Preencha os detalhes para submeter um novo pedido.' }}</p>
            </div>
            <button type="button" class="text-sm font-medium text-slate-500 hover:text-slate-700" @click="showRequestForm = false">
              Fechar
            </button>
          </div>

          <form class="mt-6 space-y-6" @submit.prevent="submitRequest">
            <div v-if="form.errors.duplicate_submission" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
              {{ form.errors.duplicate_submission }}
            </div>

            <div class="grid gap-4 md:grid-cols-2">
              <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Serviço</label>
                <select
                  v-model="form.request_type"
                  class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                  @change="handleRequestTypeChange"
                >
                  <option v-for="service in service_catalog" :key="service.type" :value="service.type">
                    {{ service.title }}
                  </option>
                </select>
              </div>
              <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Categoria</label>
                <select
                  v-model="form.category_id"
                  class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                >
                  <option :value="null">Seleção automática</option>
                  <option v-for="category in request_categories" :key="category.id" :value="category.id">
                    {{ category.name }}
                  </option>
                </select>
                <p v-if="form.errors.category_id" class="mt-1 text-xs text-rose-600">{{ form.errors.category_id }}</p>
              </div>
              <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Título</label>
                <input
                  v-model="form.title"
                  type="text"
                  class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                  placeholder="Resumo claro do que precisa"
                >
                <p v-if="form.errors.title" class="mt-1 text-xs text-rose-600">{{ form.errors.title }}</p>
              </div>
              <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Prioridade</label>
                <select
                  v-model="form.priority"
                  class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                >
                  <option value="low">Baixa</option>
                  <option value="normal">Normal</option>
                  <option value="high">Alta</option>
                </select>
              </div>
              <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Email de contacto</label>
                <input
                  v-model="form.email"
                  type="email"
                  class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                >
                <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">{{ form.errors.email }}</p>
              </div>
              <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Telefone de contacto</label>
                <input
                  v-model="form.contact"
                  type="text"
                  class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                >
                <p v-if="form.errors.contact" class="mt-1 text-xs text-rose-600">{{ form.errors.contact }}</p>
              </div>
              <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Data preferencial</label>
                <input
                  v-model="form.preferred_date"
                  type="date"
                  class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                >
                <p v-if="form.errors.preferred_date" class="mt-1 text-xs text-rose-600">{{ form.errors.preferred_date }}</p>
              </div>
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                <div class="font-medium text-slate-900">{{ warehouse?.name || 'Armazém autenticado' }}</div>
                <div class="mt-1">{{ warehouse?.address || 'Sem endereço registado' }}</div>
                <div class="mt-1">{{ warehouse?.customer || 'Sem cliente associado' }}</div>
              </div>
            </div>

            <div>
              <label class="mb-2 block text-sm font-medium text-slate-700">Descrição detalhada</label>
              <textarea
                v-model="form.description"
                rows="5"
                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                placeholder="Explique o contexto, objetivo, urgência e qualquer condição especial."
              />
              <p v-if="form.errors.description" class="mt-1 text-xs text-rose-600">{{ form.errors.description }}</p>
            </div>

            <div v-if="form.request_type === 'analysis_request'" class="rounded-3xl border border-cyan-100 bg-cyan-50/60 p-5">
              <h3 class="text-base font-semibold text-slate-900">Detalhes da análise</h3>
              <p class="mt-1 text-sm text-slate-500">
                Pode submeter uma amostra individual ou preparar um lote com vários itens. A equipa técnica valida cada linha e encaminha para o fluxo laboratorial normal.
              </p>
              <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                  <label class="mb-2 block text-sm font-medium text-slate-700">Nome da amostra</label>
                  <input v-model="form.details.sample_name" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                  <label class="mb-2 block text-sm font-medium text-slate-700">Produto</label>
                  <select v-model="form.details.product_id" class="mb-2 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option :value="null">Selecionar da lista</option>
                    <option v-for="product in products" :key="product.id" :value="product.id">
                      {{ product.name }}
                    </option>
                  </select>
                  <input v-model="form.details.product_name" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                  <label class="mb-2 block text-sm font-medium text-slate-700">Matriz</label>
                  <select v-model="form.details.matrix_id" class="mb-2 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option :value="null">Selecionar da lista</option>
                    <option v-for="matrix in matrixes" :key="matrix.id" :value="matrix.id">
                      {{ matrix.description }}
                    </option>
                  </select>
                  <input v-model="form.details.matrix" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                  <label class="mb-2 block text-sm font-medium text-slate-700">Lote</label>
                  <input v-model="form.details.lot" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                  <label class="mb-2 block text-sm font-medium text-slate-700">Tipo de embalagem</label>
                  <select v-model="form.details.packaging_id" class="mb-2 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    <option :value="null">Selecionar da lista</option>
                    <option v-for="packaging in packaging_categories" :key="packaging.id" :value="packaging.id">
                      {{ packaging.name }}
                    </option>
                  </select>
                  <input v-model="form.details.packaging" type="text" placeholder="Ex.: frasco estéril, saco, garrafa PET" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                  <label class="mb-2 block text-sm font-medium text-slate-700">Quantidade</label>
                  <input v-model="form.details.quantity" type="text" placeholder="Ex.: 2 L, 5 unidades, 250 g" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                </div>
                <div class="md:col-span-2">
                  <label class="mb-2 block text-sm font-medium text-slate-700">Observações da amostra</label>
                  <textarea v-model="form.details.notes" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" placeholder="Condições de conservação, observações de transporte, objetivo do controlo, referência interna, etc."></textarea>
                </div>
                <div class="md:col-span-2">
                  <label class="mb-2 block text-sm font-medium text-slate-700">Perfis solicitados</label>
                  <select
                    v-model="form.details.requested_profiles"
                    multiple
                    class="min-h-36 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900"
                  >
                    <option v-for="profile in analysis_profiles" :key="profile.id" :value="profile.id">
                      {{ profile.name }}
                    </option>
                  </select>
                  <p class="mt-1 text-xs text-slate-500">Use Ctrl/Cmd para selecionar vários perfis.</p>
                  <p v-if="form.errors['details.requested_profiles']" class="mt-1 text-xs text-rose-600">{{ form.errors['details.requested_profiles'] }}</p>
                </div>
                <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 md:col-span-2">
                  <input v-model="form.details.collection_required" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                  Preciso que a equipa do laboratório recolha a amostra no meu local.
                </label>
              </div>

              <div class="mt-5 rounded-3xl border border-slate-200 bg-white p-5">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                  <div>
                    <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Lote opcional de amostras</h4>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                      Para pedidos com várias amostras do mesmo cliente, pode preencher linhas manualmente ou importar um ficheiro CSV/Excel guardado em CSV.
                    </p>
                  </div>
                  <div class="flex flex-wrap gap-2">
                    <button type="button" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="downloadBatchTemplate">
                      Transferir modelo CSV
                    </button>
                    <label class="inline-flex cursor-pointer items-center rounded-xl border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                      Importar CSV
                      <input type="file" accept=".csv,text/csv" class="hidden" @change="importBatchSamples">
                    </label>
                    <button type="button" class="rounded-xl border border-cyan-200 bg-cyan-50 px-3 py-2 text-sm font-medium text-cyan-800 hover:bg-cyan-100" @click="addBatchSample">
                      Adicionar linha
                    </button>
                  </div>
                </div>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                  <span class="font-medium text-slate-900">{{ meaningfulBatchSamples.length }}</span>
                  amostra(s) em lote preparadas para validação.
                </div>

                <p v-if="form.errors['details.samples']" class="mt-3 text-xs text-rose-600">{{ form.errors['details.samples'] }}</p>

                <div class="mt-4 space-y-3">
                  <div
                    v-for="(sample, index) in form.details.samples"
                    :key="index"
                    class="rounded-3xl border border-slate-200 bg-slate-50 p-4"
                  >
                    <div class="flex items-center justify-between gap-3">
                      <div class="text-sm font-semibold text-slate-900">Amostra {{ index + 1 }}</div>
                      <button type="button" class="rounded-xl border border-rose-200 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50" @click="removeBatchSample(index)">
                        Remover
                      </button>
                    </div>

                    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                      <input v-model="sample.sample_name" type="text" placeholder="Nome da amostra" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                      <select v-model="sample.product_id" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                        <option :value="null">Produto</option>
                        <option v-for="product in products" :key="product.id" :value="product.id">
                          {{ product.name }}
                        </option>
                      </select>
                      <input v-model="sample.product_name" type="text" placeholder="Produto" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                      <select v-model="sample.matrix_id" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                        <option :value="null">Matriz</option>
                        <option v-for="matrix in matrixes" :key="matrix.id" :value="matrix.id">
                          {{ matrix.description }}
                        </option>
                      </select>
                      <input v-model="sample.matrix" type="text" placeholder="Matriz" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                      <input v-model="sample.lot" type="text" placeholder="Lote" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                      <select v-model="sample.packaging_id" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                        <option :value="null">Embalagem</option>
                        <option v-for="packaging in packaging_categories" :key="packaging.id" :value="packaging.id">
                          {{ packaging.name }}
                        </option>
                      </select>
                      <input v-model="sample.packaging" type="text" placeholder="Embalagem" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                      <input v-model="sample.quantity" type="text" placeholder="Quantidade" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                      <textarea v-model="sample.notes" rows="2" placeholder="Observações" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm md:col-span-2 xl:col-span-3"></textarea>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="form.request_type === 'collection_request'" class="rounded-3xl border border-cyan-100 bg-cyan-50/60 p-5">
              <h3 class="text-base font-semibold text-slate-900">Detalhes da colheita</h3>
              <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                  <label class="mb-2 block text-sm font-medium text-slate-700">Local</label>
                  <input v-model="form.details.collection_location" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                  <p v-if="form.errors['details.collection_location']" class="mt-1 text-xs text-rose-600">{{ form.errors['details.collection_location'] }}</p>
                </div>
                <div>
                  <label class="mb-2 block text-sm font-medium text-slate-700">Janela horária</label>
                  <input v-model="form.details.preferred_time_window" type="text" placeholder="Ex.: 08h00 - 11h00" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                </div>
                <div class="md:col-span-2">
                  <label class="mb-2 block text-sm font-medium text-slate-700">Endereço completo</label>
                  <textarea v-model="form.details.collection_address" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"></textarea>
                </div>
                <div>
                  <label class="mb-2 block text-sm font-medium text-slate-700">Contacto no local</label>
                  <input v-model="form.details.collection_contact_name" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                  <label class="mb-2 block text-sm font-medium text-slate-700">Telefone no local</label>
                  <input v-model="form.details.collection_contact_phone" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                </div>
              </div>

              <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between">
                  <div>
                    <h4 class="font-semibold text-slate-900">Itens a recolher</h4>
                    <p class="mt-1 text-sm text-slate-500">Identifique o material, quantidade e lote quando existir.</p>
                  </div>
                  <button type="button" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="addCollectionItem">
                    Adicionar item
                  </button>
                </div>

                <div class="mt-4 space-y-3">
                  <div v-for="(item, index) in form.details.items" :key="index" class="grid gap-3 rounded-2xl border border-slate-200 p-4 md:grid-cols-[1.2fr_0.5fr_0.8fr_auto]">
                    <input v-model="item.name" type="text" placeholder="Nome do item / amostra" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    <input v-model="item.quantity" type="number" min="0" step="0.01" placeholder="Qtd." class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    <input v-model="item.lot" type="text" placeholder="Lote" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    <button type="button" class="rounded-xl border border-rose-200 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50" @click="removeCollectionItem(index)">
                      Remover
                    </button>
                  </div>
                  <p v-if="form.errors['details.items']" class="text-xs text-rose-600">{{ form.errors['details.items'] }}</p>
                </div>
              </div>
            </div>

            <div v-if="form.request_type === 'document_request'" class="rounded-3xl border border-cyan-100 bg-cyan-50/60 p-5">
              <h3 class="text-base font-semibold text-slate-900">Detalhes do documento</h3>
              <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                  <label class="mb-2 block text-sm font-medium text-slate-700">Tipo de documento</label>
                  <input v-model="form.details.document_type" type="text" placeholder="Ex.: Fatura, guia, comprovativo" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                  <label class="mb-2 block text-sm font-medium text-slate-700">Referência do documento</label>
                  <input v-model="form.details.document_reference" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                </div>
              </div>
            </div>

            <div v-if="form.request_type === 'billing_support'" class="rounded-3xl border border-cyan-100 bg-cyan-50/60 p-5">
              <h3 class="text-base font-semibold text-slate-900">Detalhes de faturação</h3>
              <div class="mt-4">
                <label class="mb-2 block text-sm font-medium text-slate-700">Referência da fatura ou pagamento</label>
                <input v-model="form.details.invoice_reference" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
              </div>
            </div>

            <div v-if="form.request_type === 'certificate_support'" class="rounded-3xl border border-cyan-100 bg-cyan-50/60 p-5">
              <h3 class="text-base font-semibold text-slate-900">Detalhes do certificado</h3>
              <div class="mt-4">
                <label class="mb-2 block text-sm font-medium text-slate-700">Código do certificado</label>
                <input v-model="form.details.certificate_reference" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
              </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4">
              <p class="text-sm text-slate-500">Os pedidos são registados com referência própria e entram em triagem pela equipa.</p>
              <div class="flex gap-3">
                <button type="button" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="resetForm">
                  Limpar
                </button>
                <button
                  type="submit"
                  class="rounded-xl bg-cyan-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-cyan-800 disabled:cursor-not-allowed disabled:bg-slate-300"
                  :disabled="form.processing"
                >
                  {{ form.processing ? 'A submeter...' : 'Submeter solicitação' }}
                </button>
              </div>
            </div>
          </form>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">Histórico de pedidos</h2>
              <p class="mt-1 text-sm text-slate-500">{{ record.meta.total }} solicitações registadas.</p>
            </div>
          </div>

          <div v-if="record.data.length" class="mt-6 space-y-4">
            <article v-for="request in record.data" :key="request.id" class="rounded-2xl border border-slate-200 p-5">
              <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div class="space-y-2">
                  <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">
                      {{ request.reference || `REQ-${request.id}` }}
                    </span>
                    <span :class="statusBadgeClass(request.status)" class="rounded-full px-2.5 py-1 text-xs font-semibold">
                      {{ statusLabel(request.status) }}
                    </span>
                    <span class="rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-semibold text-cyan-700">
                      {{ typeLabel(request.request_type) }}
                    </span>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                      Prioridade {{ priorityLabel(request.priority) }}
                    </span>
                  </div>
                  <h3 class="text-lg font-semibold text-slate-900">{{ request.title || 'Solicitação sem título' }}</h3>
                  <p class="text-sm leading-6 text-slate-600">{{ request.description }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                  <Link
                    v-if="request.status !== 'completed'"
                    :href="route('portal.request.markAsDone', { id: request.id })"
                    class="rounded-xl border border-emerald-200 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50"
                  >
                    Marcar como concluída
                  </Link>
                  <Link
                    v-if="request.status !== 'cancelled'"
                    :href="route('portal.request.destroy', { id: request.id })"
                    class="rounded-xl border border-rose-200 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50"
                  >
                    Cancelar
                  </Link>
                </div>
              </div>

              <div class="mt-4 grid gap-3 text-sm text-slate-600 md:grid-cols-2 xl:grid-cols-4">
                <div>
                  <div class="text-xs font-medium uppercase tracking-wide text-slate-400">Submetido em</div>
                  <div class="mt-1">{{ formatDate(request.submitted_at || request.created_at) }}</div>
                </div>
                <div>
                  <div class="text-xs font-medium uppercase tracking-wide text-slate-400">Data preferencial</div>
                  <div class="mt-1">{{ request.preferred_date || 'Sem preferência' }}</div>
                </div>
                <div>
                  <div class="text-xs font-medium uppercase tracking-wide text-slate-400">Contacto</div>
                  <div class="mt-1">{{ request.contact || request.email }}</div>
                </div>
                <div>
                  <div class="text-xs font-medium uppercase tracking-wide text-slate-400">Tempo de resposta</div>
                  <div class="mt-1">{{ formatResponseTime(request.response_time) }}</div>
                </div>
              </div>

              <div v-if="request.extra_data && Object.keys(request.extra_data).length" class="mt-4 rounded-2xl bg-slate-50 p-4">
                <div class="text-xs font-medium uppercase tracking-wide text-slate-400">Detalhes adicionais</div>
                <ul class="mt-2 space-y-1 text-sm text-slate-600">
                  <li v-for="detail in requestDetailLines(request)" :key="detail">{{ detail }}</li>
                </ul>
              </div>
            </article>
          </div>
          <div v-else class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500">
            Nenhum pedido encontrado para os filtros atuais.
          </div>

          <div v-if="record.meta?.links?.length" class="mt-6">
            <Pagination
              :links="record.meta.links"
              :from="record.meta.from"
              :to="record.meta.to"
              :total="record.meta.total"
              :current_page="record.meta.current_page"
              :last_page="record.meta.last_page"
            />
          </div>
        </section>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, reactive, watch } from 'vue'
import debounce from 'lodash/debounce'
import { Link, router, useForm } from '@inertiajs/vue3'
import Pagination from '@/Components/pagination.vue'
import Layout from '@/Shared/Layouts/PortalLayout.vue'

defineOptions({
  layout: Layout,
})

const props = defineProps({
  record: Object,
  request_categories: {
    type: Array,
    default: () => [],
  },
  service_catalog: {
    type: Array,
    default: () => [],
  },
  analysis_profiles: {
    type: Array,
    default: () => [],
  },
  products: {
    type: Array,
    default: () => [],
  },
  matrixes: {
    type: Array,
    default: () => [],
  },
  packaging_categories: {
    type: Array,
    default: () => [],
  },
  warehouse: Object,
  query: Object,
  prefill: Object,
})

const buildDefaultDetails = () => ({
  sample_name: '',
  matrix: '',
  matrix_id: null,
  product_name: '',
  product_id: null,
  lot: '',
  packaging: '',
  packaging_id: null,
  quantity: '',
  notes: '',
  collection_required: false,
  requested_profiles: [],
  samples: [],
  collection_location: '',
  collection_address: '',
  collection_contact_name: '',
  collection_contact_phone: '',
  preferred_time_window: '',
  items: [{ name: '', quantity: 1, lot: '' }],
  document_reference: '',
  document_type: '',
  invoice_reference: '',
  certificate_reference: '',
})

const form = useForm({
  request_type: props.prefill?.request_type || props.service_catalog[0]?.type || 'general_support',
  title: props.prefill?.title || '',
  description: '',
  email: props.warehouse?.email || '',
  contact: props.warehouse?.primary_phone || props.warehouse?.alternative_phone || '',
  category_id: null,
  priority: 'normal',
  preferred_date: '',
  details: buildDefaultDetails(),
  show_form: Boolean(props.prefill?.open_form),
})

const buildBatchSample = () => ({
  sample_name: '',
  product_name: '',
  product_id: null,
  matrix: '',
  matrix_id: null,
  lot: '',
  packaging: '',
  packaging_id: null,
  quantity: '',
  notes: '',
})

const selectedType = computed(() => form.request_type)
const selectedService = computed(() => props.service_catalog.find((service) => service.type === form.request_type) || null)
const meaningfulBatchSamples = computed(() => (form.details.samples || []).filter(isMeaningfulBatchSample))
const showRequestForm = computed({
  get: () => form.show_form,
  set: (value) => {
    form.show_form = value
  },
})

const query = reactive({
  search: props.query?.search || '',
  status_filter: props.query?.status_filter || '',
  request_type: props.query?.request_type || props.prefill?.request_type || '',
  page: props.query?.page || 1,
})

const overviewCards = computed(() => {
  const requests = props.record.data || []

  return [
    {
      label: 'Pendentes',
      value: requests.filter((request) => request.status === 'pending').length,
      caption: 'Aguardam triagem ou resposta.',
    },
    {
      label: 'Em tratamento',
      value: requests.filter((request) => request.status === 'in_progress').length,
      caption: 'Pedidos já acompanhados pela equipa.',
    },
    {
      label: 'Concluídas',
      value: requests.filter((request) => request.status === 'completed').length,
      caption: 'Solicitações encerradas.',
    },
    {
      label: 'Tempo médio',
      value: averageResponseTime(requests),
      caption: 'Média calculada sobre pedidos concluídos.',
    },
  ]
})

const cleanQuery = () => {
  const filters = {
    search: query.search || undefined,
    status_filter: query.status_filter || undefined,
    request_type: query.request_type || undefined,
    page: query.page && query.page !== 1 ? query.page : undefined,
  }

  return Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== undefined && value !== ''))
}

const exportUrl = computed(() => route('portal.request.export', cleanQuery()))

const openNewRequest = (type = form.request_type) => {
  selectService(type, false)
  showRequestForm.value = true
}

const selectService = (type, openForm = true) => {
  form.request_type = type

  if (!form.title) {
    const service = props.service_catalog.find((item) => item.type === type)
    form.title = service?.title || ''
  }

  if (openForm) {
    showRequestForm.value = true
  }
}

const handleRequestTypeChange = () => {
  const currentItems = form.details.items?.length ? form.details.items : [{ name: '', quantity: 1, lot: '' }]
  const currentSamples = form.details.samples?.length ? form.details.samples : []
  const defaults = buildDefaultDetails()

  form.details = {
    ...defaults,
    items: currentItems,
    samples: currentSamples,
  }
}

const addCollectionItem = () => {
  form.details.items.push({ name: '', quantity: 1, lot: '' })
}

const removeCollectionItem = (index) => {
  if (form.details.items.length === 1) {
    form.details.items[0] = { name: '', quantity: 1, lot: '' }
    return
  }

  form.details.items.splice(index, 1)
}

const addBatchSample = () => {
  form.details.samples.push(buildBatchSample())
}

const removeBatchSample = (index) => {
  if (form.details.samples.length === 1) {
    form.details.samples[0] = buildBatchSample()
    return
  }

  form.details.samples.splice(index, 1)
}

const resetForm = () => {
  form.reset()
  form.clearErrors()
  form.request_type = props.prefill?.request_type || props.service_catalog[0]?.type || 'general_support'
  form.email = props.warehouse?.email || ''
  form.contact = props.warehouse?.primary_phone || props.warehouse?.alternative_phone || ''
  form.priority = 'normal'
  form.details = buildDefaultDetails()
}

const resetFilters = () => {
  query.search = ''
  query.status_filter = ''
  query.request_type = ''
  query.page = 1
}

const submitRequest = () => {
  form.transform((data) => ({
    ...data,
    details: {
      ...data.details,
      items: (data.details.items || []).filter((item) => item.name || item.quantity || item.lot),
      samples: (data.details.samples || []).filter(isMeaningfulBatchSample),
    },
  })).post(route('portal.request.store'), {
    preserveScroll: true,
    onSuccess: () => {
      resetForm()
      showRequestForm.value = false
    },
  })
}

const averageResponseTime = (requests) => {
  const completed = requests.filter((request) => request.status === 'completed' && Number.isFinite(request.response_time))

  if (!completed.length) {
    return '-'
  }

  const average = Math.round(completed.reduce((sum, request) => sum + request.response_time, 0) / completed.length)

  if (average >= 24) {
    return `${Math.round(average / 24)} d`
  }

  return `${average} h`
}

const statusLabel = (status) => ({
  pending: 'Pendente',
  in_progress: 'Em tratamento',
  completed: 'Concluída',
  cancelled: 'Cancelada',
}[status] || 'Pendente')

const statusBadgeClass = (status) => ({
  pending: 'bg-amber-100 text-amber-800',
  in_progress: 'bg-sky-100 text-sky-800',
  completed: 'bg-emerald-100 text-emerald-800',
  cancelled: 'bg-rose-100 text-rose-800',
}[status] || 'bg-slate-100 text-slate-700')

const priorityLabel = (priority) => ({
  low: 'baixa',
  normal: 'normal',
  high: 'alta',
}[priority] || 'normal')

const typeLabel = (type) => ({
  analysis_request: 'Análise',
  collection_request: 'Colheita',
  certificate_support: 'Certificados',
  document_request: 'Documentos',
  billing_support: 'Faturação',
  general_support: 'Suporte geral',
}[type] || 'Serviço')

const serviceLabel = (type) => typeLabel(type)

const formatDate = (value) => {
  if (!value) {
    return 'Sem data'
  }

  return new Intl.DateTimeFormat('pt-PT', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value))
}

const formatResponseTime = (value) => {
  if (!Number.isFinite(value)) {
    return 'Ainda sem resposta'
  }

  return value >= 24 ? `${Math.round(value / 24)} dia(s)` : `${value} hora(s)`
}

const requestDetailLines = (request) => {
  const details = request.extra_data || {}
  const lines = []

  if (details.sample_name) lines.push(`Amostra: ${details.sample_name}`)
  if (details.product_name) lines.push(`Produto: ${details.product_name}`)
  if (details.matrix) lines.push(`Matriz: ${details.matrix}`)
  if (details.lot) lines.push(`Lote: ${details.lot}`)
  if (details.packaging) lines.push(`Embalagem: ${details.packaging}`)
  if (details.quantity) lines.push(`Quantidade: ${details.quantity}`)
  if (details.document_type) lines.push(`Documento: ${details.document_type}`)
  if (details.document_reference) lines.push(`Referência documental: ${details.document_reference}`)
  if (details.invoice_reference) lines.push(`Referência de faturação: ${details.invoice_reference}`)
  if (details.certificate_reference) lines.push(`Certificado: ${details.certificate_reference}`)
  if (details.collection_location) lines.push(`Local de colheita: ${details.collection_location}`)
  if (details.collection_address) lines.push(`Endereço: ${details.collection_address}`)
  if (Array.isArray(details.requested_profiles) && details.requested_profiles.length) lines.push(`Perfis solicitados: ${details.requested_profiles.length}`)
  if (Number.isFinite(Number(details.sample_count)) && Number(details.sample_count) > 0) lines.push(`Amostras em lote: ${details.sample_count}`)
  if (Array.isArray(details.items) && details.items.length) lines.push(`Itens planeados para colheita: ${details.items.length}`)

  return lines.slice(0, 6)
}

function isMeaningfulBatchSample(sample) {
  return Boolean(
    sample?.sample_name ||
    sample?.product_name ||
    sample?.matrix ||
    sample?.lot ||
    sample?.packaging ||
    sample?.quantity ||
    sample?.notes
  )
}

function downloadBatchTemplate() {
  const headers = ['sample_name', 'product_name', 'matrix', 'lot', 'packaging', 'quantity', 'notes']
  const exampleRows = [
    headers.join(','),
    '"Água de processo 01","Água mineral","Água","L-2026-001","Garrafa estéril","2 L","Controlo de rotina semanal"',
    '"Leite cru tanque A","Leite cru","Lácteo","LC-445","Frasco","500 mL","Recolhido às 08:30"',
  ]

  const blob = new Blob([exampleRows.join('\n')], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = 'modelo-pedido-analises.csv'
  link.click()
  URL.revokeObjectURL(url)
}

function importBatchSamples(event) {
  const [file] = event.target.files || []

  if (!file) {
    return
  }

  const reader = new FileReader()
  reader.onload = () => {
    const text = String(reader.result || '')
    const rows = parseDelimitedText(text)

    if (!rows.length) {
      event.target.value = ''
      return
    }

    form.details.samples = rows.map((row) => ({
      sample_name: row.sample_name || '',
      product_name: row.product_name || '',
      matrix: row.matrix || '',
      lot: row.lot || '',
      packaging: row.packaging || '',
      quantity: row.quantity || '',
      notes: row.notes || '',
    }))

    if (!form.details.samples.length) {
      form.details.samples = [buildBatchSample()]
    }
  }

  reader.readAsText(file)
  event.target.value = ''
}

function parseDelimitedText(text) {
  const lines = text
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter(Boolean)

  if (!lines.length) {
    return []
  }

  const delimiter = detectDelimiter(lines[0])
  const headers = parseDelimitedLine(lines[0], delimiter).map((header) => header.trim())

  return lines
    .slice(1)
    .map((line) => parseDelimitedLine(line, delimiter))
    .map((values) => headers.reduce((row, header, index) => {
      row[header] = (values[index] || '').trim()
      return row
    }, {}))
    .filter(isMeaningfulBatchSample)
}

function detectDelimiter(line) {
  const candidates = [',', ';', '\t']

  return candidates
    .map((delimiter) => ({
      delimiter,
      count: line.split(delimiter).length,
    }))
    .sort((left, right) => right.count - left.count)[0]?.delimiter || ','
}

function parseDelimitedLine(line, delimiter) {
  const values = []
  let currentValue = ''
  let insideQuotes = false

  for (let index = 0; index < line.length; index += 1) {
    const character = line[index]
    const nextCharacter = line[index + 1]

    if (character === '"') {
      if (insideQuotes && nextCharacter === '"') {
        currentValue += '"'
        index += 1
      } else {
        insideQuotes = !insideQuotes
      }

      continue
    }

    if (character === delimiter && !insideQuotes) {
      values.push(currentValue)
      currentValue = ''
      continue
    }

    currentValue += character
  }

  values.push(currentValue)

  return values
}

watch(
  () => ({ ...query }),
  debounce(() => {
    router.get(route('portal.requests.index'), cleanQuery(), {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    })
  }, 300),
  { deep: true }
)

if (props.prefill?.open_form) {
  openNewRequest(props.prefill?.request_type || form.request_type)
}
</script>
