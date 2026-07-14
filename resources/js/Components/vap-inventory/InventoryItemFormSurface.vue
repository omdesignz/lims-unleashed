<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 border-b border-[color:var(--ds-border)] px-5 py-5 lg:flex-row lg:items-start lg:justify-between lg:px-6">
        <div class="max-w-3xl">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-kicker">Inventário laboratorial</span>
            <span class="ds-chip">
              <span class="lims-status-dot" :class="mode === 'edit' ? 'lims-status-dot-instrument' : 'lims-status-dot-release'" />
              {{ mode === 'edit' ? 'Alteração controlada' : 'Novo registo' }}
            </span>
          </div>
          <h1 class="ds-heading mt-3 text-2xl">{{ title }}</h1>
          <p class="ds-copy mt-2 text-sm">{{ description }}</p>
        </div>

        <Link :href="backHref" class="ds-button ds-button-secondary">
          <ArrowLeftIcon class="h-4 w-4" />
          {{ backLabel }}
        </Link>
      </div>

      <dl class="grid grid-cols-2 divide-x divide-y divide-[color:var(--ds-border)] sm:grid-cols-4 sm:divide-y-0">
        <div class="px-5 py-4">
          <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Categoria</dt>
          <dd class="mt-2 truncate text-sm font-bold text-[color:var(--ds-text)]">{{ selectedCategory?.label || 'Por seleccionar' }}</dd>
        </div>
        <div class="px-5 py-4">
          <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Existências distribuídas</dt>
          <dd class="mt-2 text-2xl font-bold text-[color:var(--ds-text)]">{{ totalStock }}</dd>
        </div>
        <div class="px-5 py-4">
          <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Armazéns</dt>
          <dd class="mt-2 text-2xl font-bold text-[color:var(--ds-text)]">{{ form.warehouses.length }}</dd>
        </div>
        <div class="px-5 py-4">
          <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Documentos</dt>
          <dd class="mt-2 text-2xl font-bold text-[color:var(--ds-text)]">{{ documentItems.length }}</dd>
        </div>
      </dl>
    </section>

    <form class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_20rem]" @submit.prevent="emit('submit')">
      <div class="space-y-4">
        <section class="ds-panel overflow-hidden">
          <div class="flex items-center gap-3 border-b border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] px-5 py-4">
            <InformationCircleIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
            <div>
              <h2 class="ds-heading text-base">Dossier do item</h2>
              <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Identificação, classificação e requisitos operacionais.</p>
            </div>
          </div>

          <div class="divide-y divide-[color:var(--ds-border)]">
            <section class="grid gap-5 p-5 lg:grid-cols-[14rem_minmax(0,1fr)]">
              <div>
                <div class="flex items-center gap-2">
                  <TagIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                  <h3 class="ds-heading text-sm">Informação básica</h3>
                </div>
                <p class="ds-copy mt-2 text-xs">Nome, categoria, unidade e estado que governam o item.</p>
              </div>

              <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div class="ds-field-group md:col-span-2">
                  <label class="ds-field-label" for="inventory-item-name">Nome do item <span class="ds-field-required">*</span></label>
                  <BaseInput id="inventory-item-name" v-model="form.name" type="text" class="ds-field" :aria-invalid="Boolean(errorFor('name'))" placeholder="Nome descritivo do item" />
                  <p v-if="errorFor('name')" class="ds-field-error">{{ errorFor('name') }}</p>
                </div>

                <div class="ds-field-group">
                  <label class="ds-field-label">Categoria <span class="ds-field-required">*</span></label>
                  <comboboxEnhanced v-model="selectedCategory" :has-error="Boolean(errorFor('category_id'))" :options="categoryOptions" placeholder="Seleccione a categoria" />
                  <p v-if="errorFor('category_id')" class="ds-field-error">{{ errorFor('category_id') }}</p>
                </div>

                <div v-if="isEquipment" class="ds-field-group">
                  <label class="ds-field-label">Tipo de equipamento</label>
                  <comboboxEnhanced v-model="selectedType" :has-error="Boolean(errorFor('type_id'))" :options="typeOptions" placeholder="Seleccione o tipo" />
                  <p v-if="errorFor('type_id')" class="ds-field-error">{{ errorFor('type_id') }}</p>
                </div>

                <div class="ds-field-group">
                  <label class="ds-field-label">Unidade</label>
                  <comboboxEnhanced v-model="selectedUnit" :has-error="Boolean(errorFor('unit_id'))" :options="unitOptions" placeholder="Seleccione a unidade" />
                  <p v-if="errorFor('unit_id')" class="ds-field-error">{{ errorFor('unit_id') }}</p>
                </div>

                <div class="ds-field-group">
                  <label class="ds-field-label">Estado</label>
                  <comboboxEnhanced v-model="selectedStatus" :has-error="Boolean(errorFor('status_id'))" :options="statusOptions" placeholder="Seleccione o estado" />
                  <p v-if="errorFor('status_id')" class="ds-field-error">{{ errorFor('status_id') }}</p>
                </div>

                <div class="ds-field-group md:col-span-2 xl:col-span-3">
                  <label class="ds-field-label" for="inventory-item-description">Descrição</label>
                  <textarea id="inventory-item-description" v-model="form.description" rows="3" class="ds-field" :aria-invalid="Boolean(errorFor('description'))" placeholder="Finalidade, composição ou uso previsto"></textarea>
                  <p v-if="errorFor('description')" class="ds-field-error">{{ errorFor('description') }}</p>
                </div>

                <div class="ds-field-group md:col-span-2 xl:col-span-3">
                  <label class="ds-field-label" for="inventory-item-notes">Observações</label>
                  <textarea id="inventory-item-notes" v-model="form.obs" rows="3" class="ds-field" placeholder="Notas operacionais ou restrições adicionais"></textarea>
                </div>

                <div v-if="isReagent" class="border-l-4 border-amber-500 bg-amber-50/70 p-4 md:col-span-2 xl:col-span-3 dark:bg-amber-500/10">
                  <p class="text-sm font-bold text-amber-950 dark:text-amber-100">Item identificado como reagente</p>
                  <p class="mt-1 text-xs leading-5 text-amber-800 dark:text-amber-200">Validade, abertura, lote, conservação e dimensões de embalagem passam a ser controlados.</p>
                </div>
              </div>
            </section>

            <section class="grid gap-5 p-5 lg:grid-cols-[14rem_minmax(0,1fr)]">
              <div>
                <div class="flex items-center gap-2">
                  <FingerPrintIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                  <h3 class="ds-heading text-sm">Identificação física</h3>
                </div>
                <p class="ds-copy mt-2 text-xs">Códigos, fabricante, modelo, série e lote.</p>
              </div>

              <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div class="ds-field-group">
                  <label class="ds-field-label" for="inventory-internal-code">Código interno</label>
                  <BaseInput id="inventory-internal-code" v-model="form.internal_code" type="text" class="ds-field font-mono" :aria-invalid="Boolean(errorFor('internal_code'))" placeholder="INT-001" />
                  <p v-if="errorFor('internal_code')" class="ds-field-error">{{ errorFor('internal_code') }}</p>
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label" for="inventory-barcode">Código de barras</label>
                  <BaseInput id="inventory-barcode" v-model="form.barcode" type="text" class="ds-field font-mono" :aria-invalid="Boolean(errorFor('barcode'))" placeholder="123456789012" />
                  <p v-if="errorFor('barcode')" class="ds-field-error">{{ errorFor('barcode') }}</p>
                </div>
                <div v-if="isEquipment" class="ds-field-group">
                  <label class="ds-field-label" for="inventory-serial">Número de série</label>
                  <BaseInput id="inventory-serial" v-model="form.serial_number" type="text" class="ds-field font-mono" :aria-invalid="Boolean(errorFor('serial_number'))" placeholder="SN-001-2026" />
                  <p v-if="errorFor('serial_number')" class="ds-field-error">{{ errorFor('serial_number') }}</p>
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label" for="inventory-brand">Marca</label>
                  <BaseInput id="inventory-brand" v-model="form.brand" type="text" class="ds-field" :aria-invalid="Boolean(errorFor('brand'))" placeholder="Fabricante ou marca" />
                  <p v-if="errorFor('brand')" class="ds-field-error">{{ errorFor('brand') }}</p>
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label" for="inventory-model">Modelo</label>
                  <BaseInput id="inventory-model" v-model="form.model" type="text" class="ds-field" :aria-invalid="Boolean(errorFor('model'))" placeholder="Modelo ou referência" />
                  <p v-if="errorFor('model')" class="ds-field-error">{{ errorFor('model') }}</p>
                </div>
                <div v-if="isReagent" class="ds-field-group">
                  <label class="ds-field-label" for="inventory-lot">Lote</label>
                  <BaseInput id="inventory-lot" v-model="form.lot" type="text" class="ds-field font-mono" :aria-invalid="Boolean(errorFor('lot'))" placeholder="LOT-2026-001" />
                  <p v-if="errorFor('lot')" class="ds-field-error">{{ errorFor('lot') }}</p>
                </div>
              </div>
            </section>

            <section v-if="isEquipment" class="grid gap-5 p-5 lg:grid-cols-[14rem_minmax(0,1fr)]">
              <div>
                <div class="flex items-center gap-2">
                  <WrenchScrewdriverIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                  <h3 class="ds-heading text-sm">Especificações técnicas</h3>
                </div>
                <p class="ds-copy mt-2 text-xs">Desempenho, software e cadeia de rastreabilidade metrológica.</p>
              </div>

              <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div v-for="field in technicalFields" :key="field.key" class="ds-field-group">
                  <label class="ds-field-label" :for="'inventory-technical-' + field.key">{{ field.label }}</label>
                  <BaseInput :id="'inventory-technical-' + field.key" v-model="form[field.key]" type="text" class="ds-field" :aria-invalid="Boolean(errorFor(field.key))" :placeholder="field.placeholder" />
                  <p v-if="errorFor(field.key)" class="ds-field-error">{{ errorFor(field.key) }}</p>
                </div>

                <div class="ds-field-group">
                  <label class="ds-field-label" for="inventory-uncertainty-value">Incerteza metrológica</label>
                  <BaseInput id="inventory-uncertainty-value" v-model="form.metrological_uncertainty_value" type="number" step="any" class="ds-field" :aria-invalid="Boolean(errorFor('metrological_uncertainty_value'))" placeholder="0.00" />
                  <p v-if="errorFor('metrological_uncertainty_value')" class="ds-field-error">{{ errorFor('metrological_uncertainty_value') }}</p>
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label" for="inventory-uncertainty-unit">Unidade da incerteza</label>
                  <BaseInput id="inventory-uncertainty-unit" v-model="form.metrological_uncertainty_unit" type="text" class="ds-field" :aria-invalid="Boolean(errorFor('metrological_uncertainty_unit'))" placeholder="%, °C, mg" />
                  <p v-if="errorFor('metrological_uncertainty_unit')" class="ds-field-error">{{ errorFor('metrological_uncertainty_unit') }}</p>
                </div>
                <div class="ds-field-group md:col-span-2 xl:col-span-3">
                  <label class="ds-field-label" for="inventory-traceability">Referência de rastreabilidade metrológica</label>
                  <textarea id="inventory-traceability" v-model="form.metrological_traceability_reference" rows="2" class="ds-field" :aria-invalid="Boolean(errorFor('metrological_traceability_reference'))" placeholder="Certificado, padrão, laboratório ou cadeia de referência"></textarea>
                  <p v-if="errorFor('metrological_traceability_reference')" class="ds-field-error">{{ errorFor('metrological_traceability_reference') }}</p>
                </div>
              </div>
            </section>

            <section class="grid gap-5 p-5 lg:grid-cols-[14rem_minmax(0,1fr)]">
              <div>
                <div class="flex items-center gap-2">
                  <Cog6ToothIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                  <h3 class="ds-heading text-sm">Compras e conservação</h3>
                </div>
                <p class="ds-copy mt-2 text-xs">Fornecedor, custos, critérios de aceitação e reposição.</p>
              </div>

              <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div class="ds-field-group md:col-span-2 xl:col-span-3">
                  <label class="ds-field-label">Fornecedor principal</label>
                  <comboboxEnhanced v-model="selectedSupplier" :has-error="Boolean(errorFor('supplier_id'))" :options="supplierOptions" placeholder="Seleccione o fornecedor" />
                  <p v-if="errorFor('supplier_id')" class="ds-field-error">{{ errorFor('supplier_id') }}</p>
                </div>
                <div class="ds-field-group md:col-span-2 xl:col-span-3">
                  <label class="ds-field-label" for="inventory-acceptance">Critérios de aceitação</label>
                  <textarea id="inventory-acceptance" v-model="form.acceptance_criteria" rows="3" class="ds-field" :aria-invalid="Boolean(errorFor('acceptance_criteria'))" placeholder="Condições mínimas para recepção e libertação"></textarea>
                  <p v-if="errorFor('acceptance_criteria')" class="ds-field-error">{{ errorFor('acceptance_criteria') }}</p>
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label" for="inventory-standard-cost">Custo padrão</label>
                  <BaseInput id="inventory-standard-cost" v-model="form.standard_cost" type="number" min="0" step="0.01" class="ds-field" :aria-invalid="Boolean(errorFor('standard_cost'))" />
                  <p v-if="errorFor('standard_cost')" class="ds-field-error">{{ errorFor('standard_cost') }}</p>
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label" for="inventory-last-price">Último preço de compra</label>
                  <BaseInput id="inventory-last-price" v-model="form.last_purchase_price" type="number" min="0" step="0.01" class="ds-field" :aria-invalid="Boolean(errorFor('last_purchase_price'))" />
                  <p v-if="errorFor('last_purchase_price')" class="ds-field-error">{{ errorFor('last_purchase_price') }}</p>
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label" for="inventory-reorder-qty">Quantidade de reposição</label>
                  <BaseInput id="inventory-reorder-qty" v-model="form.reorder_qty" type="number" min="0" step="0.01" class="ds-field" :aria-invalid="Boolean(errorFor('reorder_qty'))" />
                  <p v-if="errorFor('reorder_qty')" class="ds-field-error">{{ errorFor('reorder_qty') }}</p>
                </div>
                <label class="flex items-start gap-3 border-l-4 border-primary-500 bg-[color:var(--ds-panel-subtle)] p-4">
                  <CheckboxInput v-model="form.has_safety_documentation" type="checkbox" class="ds-checkbox mt-0.5" />
                  <span>
                    <span class="block text-sm font-bold text-[color:var(--ds-text)]">Documentação de segurança</span>
                    <span class="mt-1 block text-xs leading-5 text-[color:var(--ds-text-soft)]">Ficha ou evidência técnica disponível.</span>
                  </span>
                </label>
                <label class="flex items-start gap-3 border-l-4 border-blue-500 bg-[color:var(--ds-panel-subtle)] p-4">
                  <CheckboxInput v-model="form.refrigerated" type="checkbox" class="ds-checkbox mt-0.5" />
                  <span>
                    <span class="block text-sm font-bold text-[color:var(--ds-text)]">Conservação refrigerada</span>
                    <span class="mt-1 block text-xs leading-5 text-[color:var(--ds-text-soft)]">Exigir localização com controlo térmico.</span>
                  </span>
                </label>
              </div>
            </section>

            <section v-if="isEquipment" class="grid gap-5 p-5 lg:grid-cols-[14rem_minmax(0,1fr)]">
              <div>
                <div class="flex items-center gap-2">
                  <CalendarIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                  <h3 class="ds-heading text-sm">Calibração e metrologia</h3>
                </div>
                <p class="ds-copy mt-2 text-xs">Datas críticas, revisão e notas de aptidão técnica.</p>
              </div>

              <div class="grid gap-4 md:grid-cols-2">
                <div class="ds-field-group">
                  <label class="ds-field-label">Última calibração</label>
                  <datePickerEnhanced v-model="form.last_calibration_date" />
                  <p v-if="errorFor('last_calibration_date')" class="ds-field-error">{{ errorFor('last_calibration_date') }}</p>
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label">Próxima calibração</label>
                  <datePickerEnhanced v-model="form.next_calibration_date" />
                  <p v-if="errorFor('next_calibration_date')" class="ds-field-error">{{ errorFor('next_calibration_date') }}</p>
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label">Próxima revisão metrológica</label>
                  <datePickerEnhanced v-model="form.metrology_review_due_at" />
                  <p v-if="errorFor('metrology_review_due_at')" class="ds-field-error">{{ errorFor('metrology_review_due_at') }}</p>
                </div>
                <div class="ds-field-group md:col-span-2">
                  <label class="ds-field-label" for="inventory-metrology-notes">Notas metrológicas</label>
                  <textarea id="inventory-metrology-notes" v-model="form.metrology_notes" rows="3" class="ds-field" :aria-invalid="Boolean(errorFor('metrology_notes'))" placeholder="Restrições, decisão de aptidão ou plano de revisão"></textarea>
                  <p v-if="errorFor('metrology_notes')" class="ds-field-error">{{ errorFor('metrology_notes') }}</p>
                </div>
              </div>
            </section>

            <section v-if="isReagent" class="grid gap-5 p-5 lg:grid-cols-[14rem_minmax(0,1fr)]">
              <div>
                <div class="flex items-center gap-2">
                  <BeakerIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                  <h3 class="ds-heading text-sm">Validade e embalagem</h3>
                </div>
                <p class="ds-copy mt-2 text-xs">Janela de utilização e dimensões logísticas do reagente.</p>
              </div>

              <div class="space-y-5">
                <div class="grid gap-4 md:grid-cols-2">
                  <div class="ds-field-group">
                    <label class="ds-field-label">Data de validade</label>
                    <datePickerEnhanced v-model="form.reagent_expiry_date" />
                    <p v-if="errorFor('reagent_expiry_date')" class="ds-field-error">{{ errorFor('reagent_expiry_date') }}</p>
                  </div>
                  <div class="ds-field-group">
                    <label class="ds-field-label">Data de abertura</label>
                    <datePickerEnhanced v-model="form.reagent_open_date" />
                    <p v-if="errorFor('reagent_open_date')" class="ds-field-error">{{ errorFor('reagent_open_date') }}</p>
                  </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                  <div v-for="dimension in packagingDimensions" :key="dimension.key" class="ds-field-group">
                    <label class="ds-field-label" :for="'inventory-' + dimension.key">{{ dimension.label }}</label>
                    <div class="grid grid-cols-[minmax(0,1fr)_5.5rem] gap-2">
                      <BaseInput :id="'inventory-' + dimension.key" v-model="form[dimension.key]" type="number" min="0" step="0.01" class="ds-field" />
                      <BaseSelect v-model="form[dimension.unitKey]" class="ds-field">
                        <option v-for="unit in dimension.units" :key="unit" :value="unit">{{ unit }}</option>
                      </BaseSelect>
                    </div>
                    <p v-if="errorFor(dimension.key)" class="ds-field-error">{{ errorFor(dimension.key) }}</p>
                  </div>
                </div>
              </div>
            </section>
          </div>
        </section>

        <section class="ds-panel overflow-hidden">
          <div class="flex flex-col gap-3 border-b border-[color:var(--ds-border)] px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-3">
              <BuildingLibraryIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
              <div>
                <h2 class="ds-heading text-base">Existências por armazém</h2>
                <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Defina o saldo, mínimo e ponto de reposição em cada localização.</p>
              </div>
            </div>
            <button type="button" class="ds-button ds-button-secondary" @click="emit('add-warehouse')">
              <PlusIcon class="h-4 w-4" />
              Adicionar armazém
            </button>
          </div>

          <div v-if="form.warehouses.length" class="divide-y divide-[color:var(--ds-border)]">
            <article v-for="(warehouse, index) in form.warehouses" :key="warehouse.row_key || index" class="p-5">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Localização {{ index + 1 }}</p>
                  <h3 class="mt-1 text-sm font-bold text-[color:var(--ds-text)]">{{ warehouseInfo[index]?.name || warehouse.id_obj?.label || 'Armazém por seleccionar' }}</h3>
                </div>
                <button type="button" class="ds-table-action ds-table-action-danger" title="Remover armazém" @click="emit('remove-warehouse', index)">
                  <TrashIcon class="h-4 w-4" />
                  Remover
                </button>
              </div>

              <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="ds-field-group md:col-span-2 xl:col-span-1">
                  <label class="ds-field-label">Armazém <span class="ds-field-required">*</span></label>
                  <comboboxEnhanced
                    :model-value="warehouse.id_obj"
                    :has-error="Boolean(warehouseErrors[index]?.id)"
                    :options="warehouseOptions"
                    placeholder="Seleccione o armazém"
                    @update:model-value="updateWarehouseSelection(index, $event)"
                  />
                  <p v-if="warehouseErrors[index]?.id" class="ds-field-error">{{ warehouseErrors[index].id }}</p>
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label" :for="'warehouse-qty-' + index">Quantidade disponível</label>
                  <BaseInput :id="'warehouse-qty-' + index" v-model="warehouse.qty_available" type="number" min="0" step="0.01" class="ds-field" :aria-invalid="Boolean(warehouseErrors[index]?.qty_available)" />
                  <p v-if="warehouseErrors[index]?.qty_available" class="ds-field-error">{{ warehouseErrors[index].qty_available }}</p>
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label" :for="'warehouse-min-' + index">Existências mínimo</label>
                  <BaseInput :id="'warehouse-min-' + index" v-model="warehouse.min_stock_level" type="number" min="0" step="0.01" class="ds-field" />
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label" :for="'warehouse-reorder-' + index">Ponto de reposição</label>
                  <BaseInput :id="'warehouse-reorder-' + index" v-model="warehouse.reorder_point" type="number" min="0" step="0.01" class="ds-field" />
                </div>
              </div>

              <div v-if="warehouseInfo[index]" class="ds-command-toolbar mt-4 grid gap-3 p-3 text-xs sm:grid-cols-3">
                <div>
                  <span class="font-bold uppercase text-[color:var(--ds-text-soft)]">Localização</span>
                  <p class="mt-1 font-semibold text-[color:var(--ds-text)]">{{ warehouseInfo[index].location?.name || 'Não definida' }}</p>
                </div>
                <div>
                  <span class="font-bold uppercase text-[color:var(--ds-text-soft)]">Temperatura</span>
                  <p class="mt-1 font-semibold text-[color:var(--ds-text)]">{{ warehouseInfo[index].is_refrigerated ? 'Refrigerado' : 'Ambiente' }}</p>
                </div>
                <div>
                  <span class="font-bold uppercase text-[color:var(--ds-text-soft)]">Código</span>
                  <p class="mt-1 font-mono font-semibold text-[color:var(--ds-text)]">{{ warehouseInfo[index].code || 'N/A' }}</p>
                </div>
              </div>
            </article>
          </div>

          <div v-else class="p-5">
            <div class="ds-empty-state px-5 py-10 text-center">
              <BuildingLibraryIcon class="mx-auto h-8 w-8 text-[color:var(--ds-text-soft)]" />
              <h3 class="ds-heading mt-3 text-sm">Nenhum armazém configurado</h3>
              <p class="ds-copy mt-1 text-xs">Adicione pelo menos uma localização para controlar o existências.</p>
            </div>
          </div>
        </section>

        <section class="ds-panel overflow-hidden">
          <div class="flex items-start gap-3 border-b border-[color:var(--ds-border)] px-5 py-4">
            <DocumentPlusIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
            <div>
              <h2 class="ds-heading text-base">Documentação técnica</h2>
              <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Fichas de segurança, certificados, manuais e evidências.</p>
            </div>
          </div>

          <div class="p-5">
            <label
              class="flex cursor-pointer flex-col items-center justify-center border border-dashed border-[color:var(--ds-border-strong)] bg-[color:var(--ds-panel-subtle)] px-5 py-8 text-center transition hover:border-primary-400 hover:bg-primary-50/50 dark:hover:bg-primary-500/10"
              :class="dragging ? 'border-primary-500 bg-primary-50/70 dark:bg-primary-500/10' : ''"
              @dragenter.prevent="dragging = true"
              @dragleave.prevent="dragging = false"
              @dragover.prevent
              @drop.prevent="onDroppedFiles"
            >
              <DocumentPlusIcon class="h-8 w-8 text-primary-700 dark:text-primary-300" />
              <span class="mt-3 text-sm font-bold text-[color:var(--ds-text)]">Seleccione ou arraste ficheiros</span>
              <span class="mt-1 text-xs text-[color:var(--ds-text-soft)]">PDF, imagens e documentos técnicos.</span>
              <FileInput type="file" multiple class="sr-only" @change="onSelectedFiles" />
            </label>

            <div v-if="documentItems.length" class="mt-4 divide-y divide-[color:var(--ds-border)] border-y border-[color:var(--ds-border)]">
              <article v-for="(document, index) in documentItems" :key="document.id || document.name || index" class="flex items-center justify-between gap-3 py-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-bold text-[color:var(--ds-text)]">{{ document.name || 'Documento' }}</p>
                  <p class="mt-0.5 text-xs text-[color:var(--ds-text-soft)]">{{ readableFileSize(document.size || 0) }}</p>
                </div>
                <button type="button" class="ds-table-action ds-table-action-danger" @click="removeDocument(document, index)">
                  <TrashIcon class="h-4 w-4" />
                  Remover
                </button>
              </article>
            </div>
            <p v-if="errorFor('documents')" class="ds-field-error mt-3">{{ errorFor('documents') }}</p>
          </div>
        </section>
      </div>

      <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
        <section class="ds-command-surface p-5">
          <p class="ds-kicker">Validação do registo</p>
          <h2 class="ds-heading mt-2 text-base">{{ mode === 'edit' ? 'Aplicar alterações' : 'Criar item' }}</h2>
          <p class="ds-copy mt-2 text-xs">Confirme a classificação, o estado e os saldos antes de submeter.</p>

          <button type="submit" class="ds-button ds-button-primary mt-5 w-full" :disabled="form.processing">
            <CheckCircleIcon class="h-4 w-4" />
            {{ form.processing ? 'A processar...' : submitLabel }}
          </button>
          <Link :href="backHref" class="ds-button ds-button-secondary mt-2 w-full">Cancelar</Link>

          <dl class="mt-5 divide-y divide-[color:var(--ds-border)] border-t border-[color:var(--ds-border)]">
            <div class="flex items-center justify-between gap-3 py-3">
              <dt class="text-xs font-semibold text-[color:var(--ds-text-muted)]">Campos principais</dt>
              <dd class="text-xs font-bold" :class="requiredReady ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300'">{{ requiredReady ? 'Prontos' : 'Incompletos' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3 py-3">
              <dt class="text-xs font-semibold text-[color:var(--ds-text-muted)]">Existências total</dt>
              <dd class="text-sm font-bold text-[color:var(--ds-text)]">{{ totalStock }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3 py-3">
              <dt class="text-xs font-semibold text-[color:var(--ds-text-muted)]">Documentos</dt>
              <dd class="text-sm font-bold text-[color:var(--ds-text)]">{{ documentItems.length }}</dd>
            </div>
          </dl>
        </section>

        <section class="ds-card p-5">
          <h2 class="ds-heading text-base">Controlos aplicáveis</h2>
          <div class="mt-4 space-y-3">
            <div class="flex items-start gap-3">
              <span class="lims-status-dot lims-status-dot-instrument mt-1" />
              <div>
                <p class="text-sm font-bold text-[color:var(--ds-text)]">Rastreabilidade</p>
                <p class="mt-1 text-xs leading-5 text-[color:var(--ds-text-soft)]">Códigos e localização acompanham todos os movimentos.</p>
              </div>
            </div>
            <div v-if="isEquipment" class="flex items-start gap-3">
              <span class="lims-status-dot lims-status-dot-hold mt-1" />
              <div>
                <p class="text-sm font-bold text-[color:var(--ds-text)]">Metrologia</p>
                <p class="mt-1 text-xs leading-5 text-[color:var(--ds-text-soft)]">Calibração e aptidão técnica são obrigatórias.</p>
              </div>
            </div>
            <div v-if="isReagent" class="flex items-start gap-3">
              <span class="lims-status-dot lims-status-dot-critical mt-1" />
              <div>
                <p class="text-sm font-bold text-[color:var(--ds-text)]">Validade</p>
                <p class="mt-1 text-xs leading-5 text-[color:var(--ds-text-soft)]">Lote, abertura e conservação ficam sob controlo.</p>
              </div>
            </div>
          </div>
        </section>
      </aside>
    </form>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
  ArrowLeftIcon,
  BeakerIcon,
  BuildingLibraryIcon,
  CalendarIcon,
  CheckCircleIcon,
  Cog6ToothIcon,
  DocumentPlusIcon,
  FingerPrintIcon,
  InformationCircleIcon,
  PlusIcon,
  TagIcon,
  TrashIcon,
  WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import datePickerEnhanced from '@/Components/date-picker-enhanced.vue'

const props = defineProps({
  mode: {
    type: String,
    default: 'create',
  },
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    required: true,
  },
  backHref: {
    type: String,
    required: true,
  },
  backLabel: {
    type: String,
    required: true,
  },
  submitLabel: {
    type: String,
    required: true,
  },
  form: {
    type: Object,
    required: true,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
  categories: {
    type: Array,
    default: () => [],
  },
  types: {
    type: Array,
    default: () => [],
  },
  statusOptions: {
    type: Array,
    default: () => [],
  },
  suppliers: {
    type: Array,
    default: () => [],
  },
  units: {
    type: Array,
    default: () => [],
  },
  warehouses: {
    type: Array,
    default: () => [],
  },
  warehouseInfo: {
    type: Object,
    default: () => ({}),
  },
  warehouseErrors: {
    type: Object,
    default: () => ({}),
  },
  isReagent: Boolean,
  isEquipment: Boolean,
  totalStock: {
    type: Number,
    default: 0,
  },
  item: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits([
  'submit',
  'add-warehouse',
  'remove-warehouse',
  'update-warehouse-info',
  'delete-attachment',
])

const selectedCategory = defineModel('selectedCategory')
const selectedType = defineModel('selectedType')
const selectedStatus = defineModel('selectedStatus')
const selectedSupplier = defineModel('selectedSupplier')
const selectedUnit = defineModel('selectedUnit')

const dragging = ref(false)

const categoryOptions = computed(() => props.categories.map(category => ({ value: category.id, label: category.name })))
const typeOptions = computed(() => props.types.map(type => ({ value: type.id, label: type.name })))
const supplierOptions = computed(() => props.suppliers.map(supplier => ({ value: supplier.id, label: supplier.name })))
const unitOptions = computed(() => props.units.map(unit => ({ value: unit.id, label: `${unit.code} - ${unit.description || unit.name || ''}`.trim() })))
const warehouseOptions = computed(() => props.warehouses.map(warehouse => ({ value: warehouse.id, label: warehouse.name })))
const documentItems = computed(() => Array.isArray(props.form.documents) ? props.form.documents : [])
const requiredReady = computed(() => Boolean(props.form.name && props.form.category_id && props.form.status_id && props.form.warehouses.length))

const technicalFields = [
  { key: 'resolution', label: 'Resolução', placeholder: '0.001' },
  { key: 'precision', label: 'Precisão', placeholder: '±0.5%' },
  { key: 'range', label: 'Alcance / gama', placeholder: '0-1000' },
  { key: 'firmware', label: 'Firmware', placeholder: 'Versão instalada' },
  { key: 'software', label: 'Software', placeholder: 'Aplicação ou versão' },
]

const packagingDimensions = [
  { key: 'packed_depth', unitKey: 'packed_depth_unit', label: 'Profundidade', units: ['mm', 'cm', 'm'] },
  { key: 'packed_width', unitKey: 'packed_width_unit', label: 'Largura', units: ['mm', 'cm', 'm'] },
  { key: 'packed_height', unitKey: 'packed_height_unit', label: 'Altura', units: ['mm', 'cm', 'm'] },
  { key: 'packed_weight', unitKey: 'packed_weight_unit', label: 'Peso', units: ['g', 'kg'] },
]

const errorFor = (field) => props.form.errors?.[field] || props.errors?.[field] || ''

const readableFileSize = (size) => {
  const units = ['Bytes', 'KB', 'MB', 'GB', 'TB']
  let value = Number(size || 0)
  let unitIndex = 0

  while (value >= 1024 && unitIndex < units.length - 1) {
    value /= 1024
    unitIndex += 1
  }

  return `${value.toFixed(2)} ${units[unitIndex]}`
}

const updateWarehouseSelection = (index, selection) => {
  props.form.warehouses[index].id_obj = selection
  emit('update-warehouse-info', index)
}

const onSelectedFiles = (event) => {
  props.form.documents = Array.from(event.target.files || [])
}

const onDroppedFiles = (event) => {
  dragging.value = false
  props.form.documents = Array.from(event.dataTransfer?.files || [])
}

const removeDocument = (document, index) => {
  if (props.mode === 'edit' && document?.id && props.item?.id) {
    emit('delete-attachment', props.item.id, document.id, index)
    return
  }

  props.form.documents.splice(index, 1)
}
</script>
