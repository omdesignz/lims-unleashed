<template>
  <div class="inventory-item-form-shell space-y-8" :class="commercialDocumentThemeClasses">
    <ModuleHero
      eyebrow="Inventário laboratorial"
      title="Adicionar item"
      description="Registe reagentes, equipamentos e consumíveis com stock inicial, rastreabilidade, anexos e controlos metrológicos desde a entrada."
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-primary-900 ring-1 ring-primary-700/10 dark:bg-primary-500/10 dark:text-primary-200 dark:ring-primary-400/20">
            Novo registo
          </span>
          <Link
            :href="route('vap-inventory.items.index')"
            class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-200 dark:hover:bg-slate-800"
          >
            <ArrowLeftIcon class="h-4 w-4" />
            Voltar para Itens
          </Link>
        </div>
      </template>
    </ModuleHero>

    <form @submit.prevent="submit">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- LEFT COLUMN -->
        <div class="lg:col-span-2 space-y-6">
          <!-- BASIC INFORMATION -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
              <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <InformationCircleIcon class="h-5 w-5" />
                Informações Básicas
              </h2>
            </div>
            
            <div class="p-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- NAME -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                    <TagIcon class="h-4 w-4" />
                    Nome
                    <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.name"
                    type="text"
                    required
                    :class="[
                      'w-full rounded-lg border px-4 py-2.5 text-sm focus:ring-2 focus:ring-offset-1',
                      errors.name ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                    ]"
                    placeholder="Nome do Item"
                  />
                  <p v-if="errors.name" class="text-xs text-red-600">
                    {{ errors.name }}
                  </p>
                </div>

                <!-- CATEGORY -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                    <FolderIcon class="h-4 w-4" />
                    Categoria
                    <span class="text-red-500">*</span>
                  </label>
                  <comboboxEnhanced 
                    :hasError="form.errors.category_id"
                    v-model="selectedCategory"
                    :options="categories.map(category => ({
                      value: category.id,
                      label: category.name,
                    }))"
                    placeholder="Selecione uma Categoria"
                  />
                </div>

                <div v-if="isReagent" class="p-4 bg-amber-50 rounded-lg border border-amber-200">
                <p class="text-sm text-amber-800 font-medium">
                    Este item foi identificado como um Reagente. Por favor, preencha as informações de validade.
                </p>
                </div>

                <!-- TYPE -->
                <div class="space-y-2" v-if="isEquipment">
                  <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                    <CogIcon class="h-4 w-4" />
                    Tipo
                  </label>
                  <comboboxEnhanced 
                    :hasError="form.errors.type_id"
                    v-model="selectedType"
                    :options="types.map(type => ({
                      value: type.id,
                      label: type.name,
                    }))"
                    placeholder="Selecione um Tipo"
                  />
                </div>

                <!-- UNIT -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                    <ScaleIcon class="h-4 w-4" />
                    Unidade
                  </label>
                  <comboboxEnhanced 
                    :hasError="form.errors.unit_id"
                    v-model="selectedUnit"
                    :options="units.map(unit => ({
                      value: unit.id,
                      label: unit.code + ' - ' + unit.description,
                    }))"
                    placeholder="Selecione uma Unidade"
                  />
                </div>

                <!-- STATUS -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                    <CheckCircleIcon class="h-4 w-4" />
                    Estado
                  </label>
                  <comboboxEnhanced 
                    :hasError="form.errors.status_id"
                    v-model="selectedStatus"
                    :options="statusOptions"
                    placeholder="Selecione um Estado"
                  />

                  <!-- <p v-if="filteredStatuses.length === 0" class="text-xs text-amber-600">
                        Nenhum estado disponível para esta categoria. Selecione outra categoria ou adicione estados para esta categoria.
                    </p>
                    <p v-else-if="!selectedCategory" class="text-xs text-gray-500">
                        Estados globais disponíveis. Selecione uma categoria para ver estados específicos.
                    </p> -->
                </div>
              </div>

              <!-- DESCRIPTION -->
              <div class="mt-6 space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <DocumentTextIcon class="h-4 w-4" />
                  Descrição
                </label>
                <textarea
                  v-model="form.description"
                  rows="3"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm focus:ring-2 focus:ring-offset-1',
                    errors.description ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                  ]"
                  placeholder="Descrição do Item"
                />
              </div>
            </div>
          </div>

          <!-- IDENTIFICATION SECTION -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
              <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <FingerPrintIcon class="h-5 w-5" />
                Identificação
              </h2>
            </div>
            
            <div class="p-6">
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- INTERNAL CODE -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Código Interno
                  </label>
                  <input
                    v-model="form.internal_code"
                    type="text"
                    :class="[
                      'w-full rounded-lg border px-4 py-2.5 text-sm focus:ring-2 focus:ring-offset-1',
                      errors.internal_code ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                    ]"
                    placeholder="INT-001"
                  />
                </div>

                <!-- BARCODE -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Código de Barras
                  </label>
                  <input
                    v-model="form.barcode"
                    type="text"
                    :class="[
                      'w-full rounded-lg border px-4 py-2.5 text-sm focus:ring-2 focus:ring-offset-1',
                      errors.barcode ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                    ]"
                    placeholder="123456789012"
                  />
                </div>

                <!-- SERIAL NUMBER -->
                <div class="space-y-2" v-if="isEquipment">
                  <label class="block text-sm font-medium text-gray-700">
                    Número de Série
                  </label>
                  <input
                    v-model="form.serial_number"
                    type="text"
                    :class="[
                      'w-full rounded-lg border px-4 py-2.5 text-sm focus:ring-2 focus:ring-offset-1',
                      errors.serial_number ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                    ]"
                    placeholder="SN-001-2024"
                  />
                </div>

                <!-- BRAND -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Marca
                  </label>
                  <input
                    v-model="form.brand"
                    type="text"
                    :class="[
                      'w-full rounded-lg border px-4 py-2.5 text-sm focus:ring-2 focus:ring-offset-1',
                      errors.brand ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                    ]"
                    placeholder="Nome da Marca"
                  />
                </div>

                <!-- MODEL -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Modelo
                  </label>
                  <input
                    v-model="form.model"
                    type="text"
                    :class="[
                      'w-full rounded-lg border px-4 py-2.5 text-sm focus:ring-2 focus:ring-offset-1',
                      errors.model ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                    ]"
                    placeholder="Model X"
                  />
                </div>

                <!-- LOT -->
                <div class="space-y-2" v-if="isReagent">
                  <label class="block text-sm font-medium text-gray-700">
                    Lote
                  </label>
                  <input
                    v-model="form.lot"
                    type="text"
                    :class="[
                      'w-full rounded-lg border px-4 py-2.5 text-sm focus:ring-2 focus:ring-offset-1',
                      errors.lot ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                    ]"
                    placeholder="LOT-001"
                  />
                </div>

              </div>
            </div>
          </div>

          <!-- TECHNICAL SPECIFICATIONS -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" v-if="isEquipment">
            <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
              <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <WrenchScrewdriverIcon class="h-5 w-5" />
                Especificações Técnicas
              </h2>
            </div>
            
            <div class="p-6">
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- RESOLUTION -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Resolução
                  </label>
                  <input
                    v-model="form.resolution"
                    type="text"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                    placeholder="0.01 mm"
                  />
                </div>

                <!-- PRECISION -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Precisão
                  </label>
                  <input
                    v-model="form.precision"
                    type="text"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                    placeholder="±0.5%"
                  />
                </div>

                <!-- RANGE -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Alcance / Gama
                  </label>
                  <input
                    v-model="form.range"
                    type="text"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                    placeholder="0-1000°C"
                  />
                </div>

                <!-- FIRMWARE -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Firmware
                  </label>
                  <input
                    v-model="form.firmware"
                    type="text"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                    placeholder="v2.1.0"
                  />
                </div>

                <!-- SOFTWARE -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Software
                  </label>
                  <input
                    v-model="form.software"
                    type="text"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                    placeholder="v1.5.2"
                  />
                </div>

                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Incerteza Metrológica
                  </label>
                  <input
                    v-model="form.metrological_uncertainty_value"
                    type="number"
                    step="0.0001"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                    placeholder="0.2500"
                  />
                </div>

                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Unidade da Incerteza
                  </label>
                  <input
                    v-model="form.metrological_uncertainty_unit"
                    type="text"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                    placeholder="mg/L"
                  />
                </div>

                <div class="space-y-2 lg:col-span-3">
                  <label class="block text-sm font-medium text-gray-700">
                    Referência de Rastreabilidade
                  </label>
                  <input
                    v-model="form.metrological_traceability_reference"
                    type="text"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                    placeholder="Certificado / padrão / laboratório acreditado"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- INVENTORY LOCATIONS -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
              <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                  <BuildingLibraryIcon class="h-5 w-5" />
                  Locais de Inventário
                </h2>
                <button
                  @click="addWarehouse"
                  type="button"
                  class="inline-flex items-center gap-2 rounded-lg bg-white/20 px-3 py-1.5 text-sm font-medium text-white hover:bg-white/30"
                >
                  <PlusIcon class="h-4 w-4" />
                  Adicionar um Local
                </button>
              </div>
            </div>
            
            <div class="p-6">
              <!-- EMPTY STATE -->
              <div v-if="form.warehouses.length === 0" class="text-center py-8">
                <BuildingLibraryIcon class="mx-auto h-12 w-12 text-gray-300" />
                <h3 class="mt-4 text-sm font-semibold text-gray-900">
                  Nenhum local de armazenamento adicionado
                </h3>
                <p class="mt-2 text-sm text-gray-500">
                  Adicione pelo menos um local de armazenamento para armazenar este item
                </p>
                <button
                  @click="addWarehouse"
                  type="button"
                  class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
                >
                  <PlusIcon class="h-4 w-4" />
                  Adicionar um Local
                </button>
              </div>

              <!-- WAREHOUSE ITEMS -->
              <div v-else class="space-y-4">
                <div
                  v-for="(warehouse, index) in form.warehouses"
                  :key="index"
                  class="bg-gradient-to-r from-blue-50 to-white rounded-lg border border-blue-100 p-4"
                  v-motion
                  :initial="{ opacity: 0, y: 20 }"
                  :enter="{ opacity: 1, y: 0 }"
                  :delay="index * 50"
                >
                  <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                      <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-900 text-white font-semibold">
                        {{ index + 1 }}
                      </div>
                      <div>
                        <h3 class="text-sm font-semibold text-gray-900">
                          Local de Armazenamento #{{ index + 1 }}
                        </h3>
                        <p class="text-xs text-gray-500">
                          Definir níveis iniciais de estoque
                        </p>
                      </div>
                    </div>
                    <button
                      @click="removeWarehouse(index)"
                      type="button"
                      class="text-gray-400 hover:text-red-600 transition-colors p-1 rounded-full hover:bg-red-50"
                      :title="'Remove location'"
                    >
                      <TrashIcon class="h-5 w-5" />
                    </button>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- WAREHOUSE SELECTION -->
                    <div class="space-y-2">
                      <label class="block text-sm font-medium text-gray-700">
                        Armazém
                        <span class="text-red-500">*</span>
                      </label>

                      <comboboxEnhanced 
                            v-model="warehouse.id_obj" 
                            :options="warehouses.map(wh => ({
                            value: wh.id,
                            label: `${wh.name} (${wh.location?.name || 'Sem Local'})`,
                            }))"
                            placeholder="Selecione um Armazém"
                            @update:modelValue="updateWarehouseInfo(index)" 
                        />
                    </div>

                    <!-- INITIAL QUANTITY -->
                    <div class="space-y-2">
                      <label class="block text-sm font-medium text-gray-700">
                        Quantidade Inicial
                        <span class="text-red-500">*</span>
                      </label>
                      <input
                        v-model.number="warehouse.qty_available"
                        type="number"
                        min="0"
                        required
                        :class="[
                          'w-full rounded-lg border px-3 py-2 text-sm',
                          warehouseErrors[index]?.qty_available ? 'border-red-300' : 'border-gray-300'
                        ]"
                        placeholder="0"
                      />
                      <p v-if="warehouseErrors[index]?.qty_available" class="text-xs text-red-600">
                        {{ warehouseErrors[index]?.qty_available }}
                      </p>
                    </div>

                    <!-- MIN STOCK LEVEL -->
                    <div class="space-y-2">
                      <label class="block text-sm font-medium text-gray-700">
                        Nível Mínimo de Estoque
                      </label>
                      <input
                        v-model.number="warehouse.min_stock_level"
                        type="number"
                        min="0"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        placeholder="10"
                      />
                    </div>

                    <!-- REORDER POINT -->
                    <div class="space-y-2">
                      <label class="block text-sm font-medium text-gray-700">
                        Ponto de Reabastecimento
                      </label>
                      <input
                        v-model.number="warehouse.reorder_point"
                        type="number"
                        min="0"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        placeholder="20"
                      />
                    </div>
                  </div>

                  <div v-if="warehouseInfo[index]" class="mt-4 grid grid-cols-2 gap-2 text-xs">
                    <div class="flex items-center gap-1 text-gray-600">
                        <BuildingLibraryIcon class="h-3 w-3" />
                        <span>{{ warehouseInfo[index].name }}</span>
                    </div>
                    <div class="flex items-center gap-1 text-gray-600">
                        <MapPinIcon class="h-3 w-3" />
                        <span>{{ warehouseInfo[index].location?.name || 'Sem Localização' }}</span>
                    </div>
                    <div v-if="warehouseInfo[index].is_refrigerated" class="flex items-center gap-1 text-blue-600">
                        <SunIcon class="h-3 w-3" />
                        <span>Refrigerado</span>
                    </div>

                    <div v-if="warehouseInfo[index].is_ventilated" class="flex items-center gap-1 text-green-600">
                        <CubeIcon class="h-3 w-3" />
                        <span>Ventilado</span>
                    </div>
                 </div>
                  
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="space-y-6">
          <!-- ACTIONS CARD -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
              Acções
            </h3>
            <div class="space-y-4">
              <button
                type="submit"
                :disabled="form.processing"
                :class="[
                  'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                  form.processing
                    ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                    : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
                ]"
              >
                <CheckCircleIcon class="h-5 w-5" />
                {{ form.processing ? 'Criando...' : 'Criar Item' }}
              </button>
              
              <Link
                :href="route('vap-inventory.items.index')"
                class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50"
              >
                <ArrowLeftIcon class="h-5 w-5" />
                Cancelar
              </Link>

              <!-- QUICK STATS -->
              <div class="border-t border-gray-200 pt-4">
                <h4 class="text-sm font-medium text-gray-900 mb-2">
                  Estatísticas Rápidas
                </h4>
                <div class="space-y-2">
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Locais de Armazenamento</span>
                    <span class="font-semibold text-blue-900">{{ form.warehouses.length }}</span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Níveis Iniciais de Estoque</span>
                    <span class="font-semibold text-blue-900">
                      {{ totalInitialStock }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ADDITIONAL INFORMATION -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <Cog6ToothIcon class="h-5 w-5 text-blue-900" />
              Informações Adicionais
            </h3>
            <div class="space-y-4">
              <!-- SUPPLIER -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Fornecedor
                </label>
                <comboboxEnhanced 
                  :hasError="form.errors.supplier_id"
                  v-model="selectedSupplier"
                  :options="suppliers.map(supplier => ({
                    value: supplier.id,
                    label: supplier.name,
                  }))"
                  placeholder="Selecione um Fornecedor"
                />
              </div>

              <!-- ACCEPTANCE CRITERIA -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Critérios de Aceitação
                </label>
                <textarea
                  v-model="form.acceptance_criteria"
                  rows="3"
                  class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                  placeholder="Digite os critérios de aceitação para controle de qualidade"
                />
              </div>

              <!-- STANDARD COST -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Custo Padrão
                </label>
                <input
                  v-model="form.standard_cost"
                  type="number"
                  min="0"
                  step="0.01"
                  class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                  placeholder="Digite o custo padrão"
                />
                <p v-if="form.errors.standard_cost" class="text-xs text-red-600">
                  {{ form.errors.standard_cost }}
                </p>
              </div>

              <!-- LAST PURCHASE PRICE -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Último Preço de Compra
                </label>
                <input
                  v-model="form.last_purchase_price"
                  type="number"
                  min="0"
                  step="0.01"
                  class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                  placeholder="Digite o último preço de compra"
                />
                <p v-if="form.errors.last_purchase_price" class="text-xs text-red-600">
                  {{ form.errors.last_purchase_price }}
                </p>
            </div>

              <!-- Observations -->
              <div class="space-y-2 md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">
                  Observações / Observações
                </label>
                <textarea
                  v-model="form.obs"
                  rows="3"
                  class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                  placeholder="Qualquer observação adicional ou notas..."
                ></textarea>
                <p v-if="form.errors.obs" class="text-xs text-red-600">
                  {{ form.errors.obs }}
                </p>
              </div>

              <!-- REORDER QTY -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Quantidade de Reabastecimento
                </label>
                <input
                  v-model.number="form.reorder_qty"
                  type="number"
                  min="0"
                  step="0.01"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                  placeholder="0.00"
                />
              </div>

              <!-- CHECKBOXES -->
              <div class="space-y-3">
                <div class="flex items-center">
                  <input
                    v-model="form.has_safety_documentation"
                    type="checkbox"
                    class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                  />
                  <label class="ml-2 text-sm text-gray-700">
                    Tem Documentos de Segurança
                  </label>

                </div>

                <div v-if="isReagent" class="p-4 bg-amber-50 rounded-lg border border-amber-200">
                    <input
                        v-model="form.is_reagent"
                        type="checkbox"
                        class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                    />
                  <label class="ml-2 text-sm text-gray-700">
                    Assinalar Explicitamente como Reagente
                  </label>
                </div>

                <div class="flex items-center">
                  <input
                    v-model="form.refrigerated"
                    type="checkbox"
                    class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                  />
                  <label class="ml-2 text-sm text-gray-700">
                    Requer Refrigeração
                  </label>
                </div>
                
              </div>
            </div>
          </div>

          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <Cog6ToothIcon class="h-5 w-5 text-blue-900" />
              Documentos
            </h3>
            <div class="space-y-4">

              <!-- CHECKBOXES -->
              <div class="space-y-3">
                <div class="">

                    <!-- Spacer element to match the height of the toolbar -->
                    <div class="py-2" aria-hidden="true">

                    <!-- Upload Container -->
                    <div class="mt-0 pb-6">
                
                        <!-- Upload Form -->
                    <div class="col-span-full">
                        <div
                            @drop.prevent="onDroppedFiles"
                            @dragover.prevent="dragging = true"
                            @dragleave.prevent="dragging = false"
                            :class="[dragging ? 'border-indigo-500':'border-gray-400', 'mt-2 flex justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10']"
                        >
                        <div class="">
                            <DocumentPlusIcon class="mx-auto h-8 w-8 text-gray-400" aria-hidden="true" />
                            <div class="mt-4 flex text-xs leading-6 text-gray-600">
                            <label for="files" class="relative cursor-pointer rounded-full bg-orange font-semibold text-black focus-within:outline-none focus-within:ring-2 focus-within:ring-orange focus-within:ring-offset-2 hover:text-white hover:bg-orange-600 px-2">
                                <span @click="files.click()">{{ $t('gestlab.general.labels.files.upload_file') }}</span>
                                <input ref="files" @input="onSelectedFiles" type="file" name="files" multiple class="sr-only" />
                            </label>
                            <p class="pl-1">{{ $t('gestlab.general.labels.files.or') }} {{ $t('gestlab.general.labels.files.drag_file') }}</p>
                            </div>
                            <p class="text-xs leading-5 text-gray-600">PNG, JPG, GIF, DOCUMENTOS {{ $t('gestlab.general.labels.files.up_to') }} 10MB</p>
                        </div>
                        </div>
                    </div>
                    </div>

                    <!-- End Upload Form -->

                    <!-- List Files -->

                    <div v-if="form.documents.length" class="sm:col-span-full m-6">
                        <ul role="list" class="divide-y divide-gray-100">
                            <li v-for="(file, index) in form.documents" :key="file.name" class="flex items-center justify-between gap-x-6 py-5">
                            <div class="min-w-0">
                                <div class="flex items-start gap-x-3">
                                <p class="text-sm font-semibold leading-6 text-gray-900">{{ file.name }}</p>
                                <p class="mt-0.5 whitespace-nowrap rounded-md px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset text-white bg-blue-900 ring-blue-900">{{ file.extension || file.name.split('.').pop() }}</p>
                                </div>
                                <div class="mt-1 flex items-center gap-x-2 text-xs leading-5 text-gray-500">
                                <p class="truncate">{{ readableFileSize(file.size) }}</p>
                                </div>
                            </div>
                            <div class="flex flex-none items-center gap-x-4 pointer-events-auto cursor-pointer">
                                <a :href="file.original_url" :download="file.name" class="flex flex-none items-center gap-x-4 pointer-events-auto cursor-pointer" v-if="file.original_url">
                                <ArrowDownTrayIcon class="h-5 w-5" aria-hidden="true" />
                                </a>
                                <TrashIcon class="h-5 w-5" aria-hidden="true" @click="form.documents.splice(index, 1)" v-if="!file.original_url" />
                                <TrashIcon class="h-5 w-5" aria-hidden="true" @click="deleteAttachment(form.id, file.id, index)" v-if="file.original_url" />
                            </div>
                            </li>
                        </ul>
                    
                    </div>

                    <!-- End List Files -->
                </div>


                </div>
                
              </div>
            </div>
          </div>

          <!-- CALIBRATION DATES -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6" v-if="isEquipment">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <CalendarIcon class="h-5 w-5 text-blue-900" />
              Datas de Calibração
            </h3>
            <div class="space-y-4">
              <!-- LAST CALIBRATION -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Última Calibração
                </label>
                <date-picker-enhanced 
                  v-model="form.last_calibration_date"
                  :has-error="form.errors.last_calibration_date"
                  :error-message="form.errors.last_calibration_date"
                  placeholder="Selecione uma Data"
                />
                <p v-if="form.errors.last_calibration_date" class="text-xs text-red-600">
                  {{ form.errors.last_calibration_date }}
                </p>
              </div>

              <!-- NEXT CALIBRATION -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Próxima Calibração
                </label>
                <date-picker-enhanced 
                  v-model="form.next_calibration_date"
                  :has-error="form.errors.next_calibration_date"
                  :error-message="form.errors.next_calibration_date"
                  placeholder="Selecione uma Data"
                />
                <p v-if="form.errors.next_calibration_date" class="text-xs text-red-600">
                  {{ form.errors.next_calibration_date }}
                </p>
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Revisão Metrológica
                </label>
                <date-picker-enhanced
                  v-model="form.metrology_review_due_at"
                  :has-error="form.errors.metrology_review_due_at"
                  :error-message="form.errors.metrology_review_due_at"
                  placeholder="Selecione uma Data"
                />
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Notas Metrológicas
                </label>
                <textarea
                  v-model="form.metrology_notes"
                  rows="3"
                  class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                  placeholder="Condições de uso, restrições, incerteza expandida, referência do método..."
                />
              </div>
            </div>
          </div>

          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6" v-if="isReagent">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <CalendarIcon class="h-5 w-5 text-blue-900" />
              Informações do Reagente
            </h3>
            <div class="space-y-4">

              <!-- REAGENT DATES -->
              <template>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Data de Validade de Reagente
                  </label>
                  <date-picker-enhanced 
                    v-model="form.reagent_expiry_date"
                    :has-error="form.errors.reagent_expiry_date"
                    :error-message="form.errors.reagent_expiry_date"
                    placeholder="Selecione uma Data"
                    />
                    <p v-if="form.errors.reagent_expiry_date" class="text-xs text-red-600">
                      {{ form.errors.reagent_expiry_date }}
                    </p>
                </div>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Data de Abertura de Reagente
                  </label>
                  <date-picker-enhanced 
                    v-model="form.reagent_open_date"
                    :has-error="form.errors.reagent_open_date"
                    :error-message="form.errors.reagent_open_date"
                    placeholder="Selecione uma Data"
                    />
                    <p v-if="form.errors.reagent_open_date" class="text-xs text-red-600">
                      {{ form.errors.reagent_open_date }}
                    </p>
                </div>
              </template>
            </div>
          </div>

          <!-- PACKAGING DIMENSIONS -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6" v-if="isReagent">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
              Dimensões de Embalagem
            </h3>
            <div class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Profundidade
                  </label>
                  <input
                    v-model.number="form.packed_depth"
                    type="number"
                    step="0.01"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    placeholder="0.00"
                  />
                </div>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Unidade
                  </label>
                  <input
                    v-model="form.packed_depth_unit"
                    type="text"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    placeholder="cm"
                  />
                </div>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Largura
                  </label>
                  <input
                    v-model.number="form.packed_width"
                    type="number"
                    step="0.01"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    placeholder="0.00"
                  />
                </div>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Unidade
                  </label>
                  <input
                    v-model="form.packed_width_unit"
                    type="text"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    placeholder="cm"
                  />
                </div>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Altura
                  </label>
                  <input
                    v-model.number="form.packed_height"
                    type="number"
                    step="0.01"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    placeholder="0.00"
                  />
                </div>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Unidade
                  </label>
                  <input
                    v-model="form.packed_height_unit"
                    type="text"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    placeholder="cm"
                  />
                </div>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Peso
                  </label>
                  <input
                    v-model.number="form.packed_weight"
                    type="number"
                    step="0.01"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    placeholder="0.00"
                  />
                </div>
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    Unidade
                  </label>
                  <input
                    v-model="form.packed_weight_unit"
                    type="text"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    placeholder="kg"
                  />
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { Link, useForm } from '@inertiajs/vue3'
import { 
  PlusCircleIcon,
  ArrowLeftIcon,
  InformationCircleIcon,
  TagIcon,
  HashtagIcon,
  FolderIcon,
  CogIcon,
  ScaleIcon,
  CheckCircleIcon,
  DocumentTextIcon,
  FingerPrintIcon,
  WrenchScrewdriverIcon,
  BuildingLibraryIcon,
  PlusIcon,
  TrashIcon,
  MapPinIcon,
  SunIcon,
  CubeIcon,
  Cog6ToothIcon,
  CalendarIcon,
  DocumentPlusIcon
} from '@heroicons/vue/24/outline'
import datePickerEnhanced from '@/Components/date-picker-enhanced.vue'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import ModuleHero from '@/Components/base/ModuleHero.vue'

const props = defineProps({
  categories: Array,
  types: Array,
//   statuses: Array,
  allStatuses: Array,
  suppliers: Array,
  units: Array,
  warehouses: Array,
  errors: Object,
})

const warehouseInfo = ref({})
const filteredStatuses = ref([])

const form = useForm({
  name: '',
  code: '',
  category_id: '',
  type_id: '',
  is_reagent: false,
  unit_id: '',
  status_id: '',
  supplier_id: '',
  barcode: '',
  serial_number: '',
  internal_code: '',
  model: '',
  brand: '',
  lot: '',
  resolution: '',
  precision: '',
  range: '',
  metrological_uncertainty_value: '',
  metrological_uncertainty_unit: '',
  metrological_traceability_reference: '',
  firmware: '',
  software: '',
  description: '',
  acceptance_criteria: '',
  obs: '',
  reorder_qty: 0,
  packed_depth: 0,
  packed_width: 0,
  packed_height: 0,
  packed_weight: 0,
  standard_cost: 0,
  last_purchase_price: 0,
  packed_depth_unit: 'cm',
  packed_width_unit: 'cm',
  packed_height_unit: 'cm',
  packed_weight_unit: 'kg',
  has_safety_documentation: false,
  refrigerated: false,
  reagent_expiry_date: '',
  reagent_open_date: '',
  next_calibration_date: '',
  last_calibration_date: '',
  metrology_review_due_at: '',
  metrology_notes: '',
  warehouses: [],
  documents: [],
})

const warehouseErrors = ref({})
const dragging = ref(false);
const files = ref(null);

const selectedCategory = ref(null)
const selectedType = ref(null)
const selectedStatus = ref(null)
const selectedSupplier = ref(null)
const selectedUnit = ref(null)

// Function to update filtered statuses
const updateFilteredStatuses = (categoryId = null) => {
  
  if (!props.allStatuses || props.allStatuses.length === 0) {
    filteredStatuses.value = []
    return
  }
  
  // Make sure we're working with a clean array
  const allStatuses = [...props.allStatuses]
  
  if (categoryId) {
    const catId = Number(categoryId)
    
    // Show global statuses (null) AND statuses for the selected category
    const result = allStatuses.filter(status => {
      const statusCatId = status.category_id !== null && status.category_id !== undefined 
        ? Number(status.category_id) 
        : null
      
      const matches = statusCatId === null || statusCatId === catId
      return matches
    })
    
    filteredStatuses.value = result
  } else {
    
    // Only show statuses where category_id is null
    const result = allStatuses.filter(status => {
      const isGlobal = status.category_id === null
      return isGlobal
    })
    
    filteredStatuses.value = result
  }
}

// watch(selectedCategory, (newVal) => form.category_id = newVal?.value || '')
// Watch for category changes
watch(selectedCategory, (newVal) => {
  const categoryId = newVal?.value || null
  form.category_id = categoryId
  
  // Update filtered statuses
  updateFilteredStatuses(categoryId)
}, { immediate: true }) // Add immediate: true to run on initial setup

const statusOptions = computed(() => {
  
  if (!filteredStatuses.value || filteredStatuses.value.length === 0) {
    return []
  }
  
  const options = filteredStatuses.value.map(status => ({
    value: status.id,
    label: status.name,
    category_id: status.category_id
  }))
  
  return options
})

watch(selectedType, (newVal) => form.type_id = newVal?.value || '')
watch(selectedStatus, (newVal) => form.status_id = newVal?.value || '')
watch(selectedSupplier, (newVal) => form.supplier_id = newVal?.value || '')
watch(selectedUnit, (newVal) => form.unit_id = newVal?.value || '')

const isReagent = computed(() => {
  if (!selectedCategory.value) return false
  const category = props.categories.find(c => c.id === selectedCategory.value.value)
  return category?.is_reagent || category?.name?.toLowerCase().includes('reagente') || false
})

const isEquipment = computed(() => {
  if (!selectedCategory.value) return false
  const category = props.categories.find(c => c.id === selectedCategory.value.value)
  return category?.name?.toLowerCase().includes('equipamento') || false
})

const totalInitialStock = computed(() => {
  return form.warehouses.reduce((sum, wh) => sum + (Number(wh.qty_available) || 0), 0)
})

const addWarehouse = () => {
  form.warehouses.push({
    id_obj: null,
    id: '',
    qty_available: 0,
    min_stock_level: 0,
    reorder_point: 0,
  })
}

const removeWarehouse = (index) => {
  form.warehouses.splice(index, 1)
  delete warehouseErrors.value[index]
  // Re-index errors
  const newErrors = {}
  Object.keys(warehouseErrors.value).forEach(key => {
    if (key > index) {
      newErrors[key - 1] = warehouseErrors.value[key]
    } else if (key < index) {
      newErrors[key] = warehouseErrors.value[key]
    }
  })
  warehouseErrors.value = newErrors
}

const updateWarehouseInfo = (index) => {
  const selectedObj = form.warehouses[index].id_obj // Use a separate key for the UI object
  
  if (selectedObj) {
    // Set the actual ID for the form submission
    form.warehouses[index].id = selectedObj.value
    
    // Find metadata for the UI display
    const info = props.warehouses.find(w => w.id === selectedObj.value)
    warehouseInfo.value[index] = info
  } else {
    warehouseInfo.value[index] = null
    form.warehouses[index].id = ''
  }
}

const validateWarehouses = () => {
  warehouseErrors.value = {}
  let isValid = true

  form.warehouses.forEach((warehouse, index) => {
    const errors = {}
    
    if (!warehouse.id) {
      errors.id = 'Armazém é obrigatório'
      isValid = false
    }
    
    if (warehouse.qty_available === '' || warehouse.qty_available < 0) {
      errors.qty_available = 'Quantidade inicial válida é obrigatória'
      isValid = false
    }

    if (Object.keys(errors).length > 0) {
      warehouseErrors.value[index] = errors
    }
  })

  return isValid
}

const submit = () => {
  if (!validateWarehouses()) {
    return
  }

  form.post(route('vap-inventory.items.store'), {
    preserveScroll: true,
    onError: (errors) => {
      // Handle warehouse errors separately
      if (errors.warehouses) {
        try {
          const whErrors = JSON.parse(errors.warehouses)
          warehouseErrors.value = whErrors
        } catch (e) {
          console.error('Error parsing warehouse errors:', e)
        }
      }
    },
  })
}

const readableFileSize = (size) => {
    const units = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    let i = 0;
    while (size >= 1024 && i < units.length - 1) {
      size /= 1024;
      i++;
    }
    return `${size.toFixed(2)} ${units[i]}`;
  };

  const onSelectedFiles = ($event) => {
        form.documents = [...$event.target.files];
  }
  
  const onDroppedFiles = ($event) => {
      dragging.value = false;
 
      let droppedFiles = [...$event.dataTransfer.items]
          .filter(item => item.kind === 'file')
          .map(item => item.getAsFile());
 
}

// Initialize with one empty warehouse
addWarehouse()

onMounted(() => {
  // Initial filter for global statuses
  updateFilteredStatuses(null)
})
</script>

<style scoped>
.inventory-item-form-shell :deep(.bg-white.rounded-xl) {
  border-color: rgb(226 232 240);
  border-radius: 1.5rem;
  background: rgb(255 255 255);
  box-shadow: 0 1px 2px rgb(15 23 42 / 0.06);
}

.inventory-item-form-shell :deep(.bg-gradient-to-r.from-blue-900.to-blue-800) {
  --tw-gradient-from: rgb(var(--color-primary-700, 29 78 216)) var(--tw-gradient-from-position);
  --tw-gradient-to: rgb(var(--color-primary-900, 30 58 138)) var(--tw-gradient-to-position);
  --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to);
}

:global(.dark) .inventory-item-form-shell :deep(.bg-gradient-to-r.from-blue-900.to-blue-800) {
  --tw-gradient-from: rgb(15 23 42) var(--tw-gradient-from-position);
  --tw-gradient-to: rgb(23 37 84) var(--tw-gradient-to-position);
  --tw-gradient-stops: var(--tw-gradient-from), rgb(15 23 42) var(--tw-gradient-via-position), var(--tw-gradient-to);
}

.inventory-item-form-shell :deep(label) {
  color: rgb(51 65 85);
}

.inventory-item-form-shell :deep(input:not([type='checkbox']):not([type='radio']):not([type='file'])),
.inventory-item-form-shell :deep(textarea),
.inventory-item-form-shell :deep(select) {
  border-color: rgb(203 213 225);
  border-radius: 1rem;
  background: rgb(255 255 255);
  color: rgb(15 23 42);
  box-shadow: 0 1px 2px rgb(15 23 42 / 0.05);
  transition: border-color 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
}

.inventory-item-form-shell :deep(input[type='checkbox']) {
  border-color: rgb(203 213 225);
  border-radius: 0.25rem;
  color: rgb(var(--color-primary-600, 37 99 235));
}

.inventory-item-form-shell :deep(.bg-gradient-to-r.from-blue-50.to-white) {
  --tw-gradient-from: rgb(var(--color-primary-50, 239 246 255)) var(--tw-gradient-from-position);
  --tw-gradient-to: rgb(255 255 255) var(--tw-gradient-to-position);
  --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to);
}

.inventory-item-form-shell :deep(.border-blue-100) {
  border-color: rgb(var(--color-primary-100, 219 234 254));
}

.inventory-item-form-shell :deep(.bg-blue-900) {
  background-color: rgb(var(--color-primary-600, 37 99 235));
}

.inventory-item-form-shell :deep(.text-blue-900) {
  color: rgb(var(--color-primary-900, 30 58 138));
}

.inventory-item-form-shell :deep(.border-dashed) {
  border-color: rgb(203 213 225);
  border-radius: 1.5rem;
  background: rgb(248 250 252 / 0.72);
  transition: border-color 150ms ease, background-color 150ms ease;
}

.inventory-item-form-shell :deep(input:not([type='checkbox']):not([type='radio']):not([type='file']):focus),
.inventory-item-form-shell :deep(textarea:focus),
.inventory-item-form-shell :deep(select:focus) {
  border-color: rgb(var(--color-primary-500, 59 130 246));
  box-shadow: 0 0 0 3px rgb(var(--color-primary-500, 59 130 246) / 0.18);
  outline: none;
}

.inventory-item-form-shell :deep(input::placeholder),
.inventory-item-form-shell :deep(textarea::placeholder) {
  color: rgb(148 163 184);
}

:global(.dark) .inventory-item-form-shell :deep(.bg-white.rounded-xl) {
  border-color: rgb(30 41 59);
  background: rgb(2 6 23);
}

:global(.dark) .inventory-item-form-shell :deep(label),
:global(.dark) .inventory-item-form-shell :deep(.text-gray-900),
:global(.dark) .inventory-item-form-shell :deep(.text-gray-700),
:global(.dark) .inventory-item-form-shell :deep(.text-gray-600) {
  color: rgb(226 232 240);
}

:global(.dark) .inventory-item-form-shell :deep(.text-gray-500) {
  color: rgb(148 163 184);
}

:global(.dark) .inventory-item-form-shell :deep(.text-blue-900) {
  color: rgb(var(--color-primary-200, 191 219 254));
}

:global(.dark) .inventory-item-form-shell :deep(.bg-blue-900) {
  background-color: rgb(var(--color-primary-500, 59 130 246));
}

:global(.dark) .inventory-item-form-shell :deep(.border-gray-200),
:global(.dark) .inventory-item-form-shell :deep(.border-gray-300),
:global(.dark) .inventory-item-form-shell :deep(.border-gray-400),
:global(.dark) .inventory-item-form-shell :deep(.border-blue-100) {
  border-color: rgb(51 65 85);
}

:global(.dark) .inventory-item-form-shell :deep(.bg-gray-200) {
  background-color: rgb(30 41 59);
}

:global(.dark) .inventory-item-form-shell :deep(input:not([type='checkbox']):not([type='radio']):not([type='file'])),
:global(.dark) .inventory-item-form-shell :deep(textarea),
:global(.dark) .inventory-item-form-shell :deep(select) {
  border-color: rgb(51 65 85);
  background: rgb(2 6 23 / 0.72);
  color: rgb(241 245 249);
}

:global(.dark) .inventory-item-form-shell :deep(input::placeholder),
:global(.dark) .inventory-item-form-shell :deep(textarea::placeholder) {
  color: rgb(100 116 139);
}

:global(.dark) .inventory-item-form-shell :deep(input[type='checkbox']) {
  border-color: rgb(71 85 105);
  background: rgb(15 23 42);
  color: rgb(var(--color-primary-400, 96 165 250));
}

:global(.dark) .inventory-item-form-shell :deep(.bg-gradient-to-r.from-blue-50.to-white) {
  --tw-gradient-from: rgb(var(--color-primary-500, 59 130 246) / 0.1) var(--tw-gradient-from-position);
  --tw-gradient-to: rgb(2 6 23) var(--tw-gradient-to-position);
  --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to);
}

:global(.dark) .inventory-item-form-shell :deep(.border-dashed) {
  border-color: rgb(51 65 85);
  background: rgb(15 23 42 / 0.68);
}
</style>
