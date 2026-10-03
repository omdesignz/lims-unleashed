<template>
  <form class="pl-page" data-template="form" @submit.prevent="emit('submit')">
    <PageHeader :crumbs="crumbs" :title="title" :lede="description" />

    <slot name="notice" />

    <section class="pl-form-section">
      <header>
        <h2 class="pl-d3">Informação básica</h2>
        <p>Nome, categoria, unidade e estado que governam o item.</p>
      </header>

      <div class="pl-form-grid">
        <div class="ds-field-group pl-span-2">
          <label class="ds-field-label" for="inventory-item-name">Nome do item <span class="ds-field-required">*</span></label>
          <BaseInput id="inventory-item-name" v-model="form.name" type="text" class="ds-field" :aria-invalid="Boolean(errorFor('name'))" placeholder="Nome descritivo do item" />
          <p v-if="errorFor('name')" class="ds-field-error" role="alert">{{ errorFor('name') }}</p>
        </div>

        <div class="ds-field-group">
          <label class="ds-field-label">Categoria <span class="ds-field-required">*</span></label>
          <input v-if="identityLocks.category" id="inventory-item-category" :value="selectedCategory?.label || 'Sem categoria'" readonly class="ds-field" aria-label="Categoria" aria-describedby="inventory-category-lock" />
          <comboboxEnhanced v-else v-model="selectedCategory" :has-error="Boolean(errorFor('category_id'))" :options="categoryOptions" placeholder="Escolher categoria" />
          <p v-if="identityLocks.category" id="inventory-category-lock" class="text-[12.5px] text-[var(--pl-muted)]">Categoria fixa: integra a sequência emitida.</p>
          <p v-if="errorFor('category_id')" class="ds-field-error" role="alert">{{ errorFor('category_id') }}</p>
        </div>

        <div class="ds-field-group">
          <label class="ds-field-label">Unidade <span class="ds-field-required">*</span></label>
          <input v-if="identityLocks.unit" id="inventory-item-unit" :value="selectedUnit?.label || 'Sem unidade'" readonly class="ds-field" aria-label="Unidade" aria-describedby="inventory-unit-lock" />
          <comboboxEnhanced v-else v-model="selectedUnit" :has-error="Boolean(errorFor('unit_id'))" :options="unitOptions" placeholder="Escolher unidade" />
          <p v-if="identityLocks.unit" id="inventory-unit-lock" class="text-[12.5px] text-[var(--pl-muted)]">Unidade fixa: o item tem existências registadas, incluindo histórico arquivado.</p>
          <p v-if="errorFor('unit_id')" class="ds-field-error" role="alert">{{ errorFor('unit_id') }}</p>
        </div>

        <div v-if="isEquipment" class="ds-field-group">
          <comboboxEnhanced v-model="selectedType" title-label="Tipo de equipamento" :has-error="Boolean(errorFor('type_id'))" :options="typeOptions" placeholder="Escolher tipo" />
          <p v-if="errorFor('type_id')" class="ds-field-error" role="alert">{{ errorFor('type_id') }}</p>
        </div>

        <div class="ds-field-group">
          <comboboxEnhanced v-model="selectedStatus" title-label="Estado" :has-error="Boolean(errorFor('status_id'))" :options="statusOptions" placeholder="Escolher estado" />
          <p v-if="errorFor('status_id')" class="ds-field-error" role="alert">{{ errorFor('status_id') }}</p>
        </div>

        <div class="ds-field-group">
          <label class="ds-field-label" for="inventory-item-location">Localização física</label>
          <BaseInput id="inventory-item-location" v-model="form.location" type="text" class="ds-field" :aria-invalid="Boolean(errorFor('location'))" placeholder="Sala, bancada ou posição" />
          <p v-if="errorFor('location')" class="ds-field-error" role="alert">{{ errorFor('location') }}</p>
        </div>

        <div class="ds-field-group">
          <comboboxEnhanced v-model="selectedDepartment" title-label="Departamento" :has-error="Boolean(errorFor('department_id'))" :options="departmentOptions" placeholder="Escolher departamento" />
          <p v-if="errorFor('department_id')" class="ds-field-error" role="alert">{{ errorFor('department_id') }}</p>
        </div>

        <div class="ds-field-group">
          <comboboxEnhanced v-model="selectedEquipmentCategory" title-label="Classe de equipamento" :has-error="Boolean(errorFor('eq_cat_id'))" :options="equipmentCategoryOptions" placeholder="Se aplicável" />
          <p v-if="errorFor('eq_cat_id')" class="ds-field-error" role="alert">{{ errorFor('eq_cat_id') }}</p>
        </div>

        <div class="ds-field-group">
          <comboboxEnhanced v-model="selectedPackagingCategory" title-label="Tipo de embalagem" :has-error="Boolean(errorFor('packaging_type_id'))" :options="packagingCategoryOptions" placeholder="Se aplicável" />
          <p v-if="errorFor('packaging_type_id')" class="ds-field-error" role="alert">{{ errorFor('packaging_type_id') }}</p>
        </div>

        <div class="ds-field-group pl-span-2">
          <label class="ds-field-label" for="inventory-item-description">Descrição</label>
          <textarea id="inventory-item-description" v-model="form.description" rows="3" class="ds-field" :aria-invalid="Boolean(errorFor('description'))" placeholder="Finalidade, composição ou uso previsto"></textarea>
          <p v-if="errorFor('description')" class="ds-field-error" role="alert">{{ errorFor('description') }}</p>
        </div>

        <div class="ds-field-group pl-span-2">
          <label class="ds-field-label" for="inventory-item-notes">Observações</label>
          <textarea id="inventory-item-notes" v-model="form.obs" rows="3" class="ds-field" placeholder="Notas operacionais ou restrições adicionais"></textarea>
        </div>

        <p v-if="isReagent" class="pl-banner pl-banner-warn pl-span-2 text-sm">
          <span class="pl-k">Reagente</span>
          Validade, abertura, lote, conservação e dimensões de embalagem passam a ser controlados.
        </p>
      </div>
    </section>

    <section class="pl-form-section">
      <header>
        <h2 class="pl-d3">Identificação física</h2>
        <p>Códigos, fabricante, modelo, série e lote.</p>
      </header>

      <div class="pl-form-grid">
        <div class="ds-field-group">
          <label class="ds-field-label" for="inventory-internal-code">Código interno</label>
          <BaseInput id="inventory-internal-code" v-model="form.internal_code" type="text" class="ds-field font-mono" :aria-invalid="Boolean(errorFor('internal_code'))" placeholder="INT-001" />
          <p v-if="errorFor('internal_code')" class="ds-field-error" role="alert">{{ errorFor('internal_code') }}</p>
        </div>
        <div class="ds-field-group">
          <label class="ds-field-label" for="inventory-barcode">Código de barras</label>
          <BaseInput id="inventory-barcode" v-model="form.barcode" type="text" class="ds-field font-mono" :aria-invalid="Boolean(errorFor('barcode'))" placeholder="123456789012" />
          <p v-if="errorFor('barcode')" class="ds-field-error" role="alert">{{ errorFor('barcode') }}</p>
        </div>
        <div class="ds-field-group">
          <label class="ds-field-label" for="inventory-brand">Marca</label>
          <BaseInput id="inventory-brand" v-model="form.brand" type="text" class="ds-field" :aria-invalid="Boolean(errorFor('brand'))" placeholder="Fabricante ou marca" />
          <p v-if="errorFor('brand')" class="ds-field-error" role="alert">{{ errorFor('brand') }}</p>
        </div>
        <div class="ds-field-group">
          <label class="ds-field-label" for="inventory-model">Modelo</label>
          <BaseInput id="inventory-model" v-model="form.model" type="text" class="ds-field" :aria-invalid="Boolean(errorFor('model'))" placeholder="Modelo ou referência" />
          <p v-if="errorFor('model')" class="ds-field-error" role="alert">{{ errorFor('model') }}</p>
        </div>
        <div v-if="isEquipment" class="ds-field-group">
          <label class="ds-field-label" for="inventory-serial">Número de série</label>
          <BaseInput id="inventory-serial" v-model="form.serial_number" type="text" class="ds-field font-mono" :aria-invalid="Boolean(errorFor('serial_number'))" placeholder="SN-001-2026" />
          <p v-if="errorFor('serial_number')" class="ds-field-error" role="alert">{{ errorFor('serial_number') }}</p>
        </div>
        <div v-if="isReagent" class="ds-field-group">
          <label class="ds-field-label" for="inventory-lot">Lote</label>
          <BaseInput id="inventory-lot" v-model="form.lot" type="text" class="ds-field font-mono" :aria-invalid="Boolean(errorFor('lot'))" placeholder="LOT-2026-001" />
          <p v-if="errorFor('lot')" class="ds-field-error" role="alert">{{ errorFor('lot') }}</p>
        </div>
      </div>
    </section>

    <section v-if="isEquipment" class="pl-form-section">
      <header>
        <h2 class="pl-d3">Especificações técnicas</h2>
        <p>Desempenho, software e cadeia de rastreabilidade metrológica.</p>
      </header>

      <div class="pl-form-grid">
        <div v-for="field in technicalFields" :key="field.key" class="ds-field-group">
          <label class="ds-field-label" :for="'inventory-technical-' + field.key">{{ field.label }}</label>
          <BaseInput :id="'inventory-technical-' + field.key" v-model="form[field.key]" type="text" class="ds-field" :aria-invalid="Boolean(errorFor(field.key))" :placeholder="field.placeholder" />
          <p v-if="errorFor(field.key)" class="ds-field-error" role="alert">{{ errorFor(field.key) }}</p>
        </div>

        <div class="ds-field-group">
          <label class="ds-field-label" for="inventory-uncertainty-value">Incerteza metrológica</label>
          <BaseInput id="inventory-uncertainty-value" v-model="form.metrological_uncertainty_value" type="number" step="any" class="ds-field" :aria-invalid="Boolean(errorFor('metrological_uncertainty_value'))" placeholder="0.00" />
          <p v-if="errorFor('metrological_uncertainty_value')" class="ds-field-error" role="alert">{{ errorFor('metrological_uncertainty_value') }}</p>
        </div>
        <div class="ds-field-group">
          <label class="ds-field-label" for="inventory-uncertainty-unit">Unidade da incerteza</label>
          <BaseInput id="inventory-uncertainty-unit" v-model="form.metrological_uncertainty_unit" type="text" class="ds-field" :aria-invalid="Boolean(errorFor('metrological_uncertainty_unit'))" placeholder="%, °C, mg" />
          <p v-if="errorFor('metrological_uncertainty_unit')" class="ds-field-error" role="alert">{{ errorFor('metrological_uncertainty_unit') }}</p>
        </div>
        <div class="ds-field-group pl-span-2">
          <label class="ds-field-label" for="inventory-traceability">Referência de rastreabilidade metrológica</label>
          <textarea id="inventory-traceability" v-model="form.metrological_traceability_reference" rows="2" class="ds-field" :aria-invalid="Boolean(errorFor('metrological_traceability_reference'))" placeholder="Certificado, padrão, laboratório ou cadeia de referência"></textarea>
          <p v-if="errorFor('metrological_traceability_reference')" class="ds-field-error" role="alert">{{ errorFor('metrological_traceability_reference') }}</p>
        </div>
      </div>
    </section>

    <section class="pl-form-section">
      <header>
        <h2 class="pl-d3">Compras e conservação</h2>
        <p>Fornecedor, custos, critérios de aceitação e reposição.</p>
      </header>

      <div class="pl-form-grid">
        <div class="ds-field-group pl-span-2">
          <comboboxEnhanced v-model="selectedSupplier" title-label="Fornecedor principal" :has-error="Boolean(errorFor('supplier_id'))" :options="supplierOptions" placeholder="Escolher fornecedor" />
          <p v-if="errorFor('supplier_id')" class="ds-field-error" role="alert">{{ errorFor('supplier_id') }}</p>
        </div>
        <div class="ds-field-group pl-span-2">
          <label class="ds-field-label" for="inventory-acceptance">Critérios de aceitação</label>
          <textarea id="inventory-acceptance" v-model="form.acceptance_criteria" rows="3" class="ds-field" :aria-invalid="Boolean(errorFor('acceptance_criteria'))" placeholder="Condições mínimas para recepção e libertação"></textarea>
          <p v-if="errorFor('acceptance_criteria')" class="ds-field-error" role="alert">{{ errorFor('acceptance_criteria') }}</p>
        </div>
        <div class="ds-field-group">
          <label class="ds-field-label" for="inventory-standard-cost">Custo padrão</label>
          <BaseInput id="inventory-standard-cost" v-model="form.standard_cost" type="number" min="0" step="0.01" class="ds-field" :aria-invalid="Boolean(errorFor('standard_cost'))" />
          <p v-if="errorFor('standard_cost')" class="ds-field-error" role="alert">{{ errorFor('standard_cost') }}</p>
        </div>
        <div class="ds-field-group">
          <label class="ds-field-label" for="inventory-last-price">Último preço de compra</label>
          <BaseInput id="inventory-last-price" v-model="form.last_purchase_price" type="number" min="0" step="0.01" class="ds-field" :aria-invalid="Boolean(errorFor('last_purchase_price'))" />
          <p v-if="errorFor('last_purchase_price')" class="ds-field-error" role="alert">{{ errorFor('last_purchase_price') }}</p>
        </div>
        <div class="ds-field-group">
          <label class="ds-field-label" for="inventory-reorder-qty">Quantidade de reposição</label>
          <BaseInput id="inventory-reorder-qty" v-model="form.reorder_qty" type="number" min="0" step="0.01" class="ds-field" :aria-invalid="Boolean(errorFor('reorder_qty'))" />
          <p v-if="errorFor('reorder_qty')" class="ds-field-error" role="alert">{{ errorFor('reorder_qty') }}</p>
        </div>
        <div class="grid content-start gap-3 pt-1">
          <label class="flex items-start gap-3">
            <CheckboxInput v-model="form.has_safety_documentation" type="checkbox" class="ds-checkbox mt-0.5" />
            <span>
              <span class="block text-sm font-medium">Documentação de segurança</span>
              <span class="block text-[12.5px] text-[var(--pl-muted)]">Ficha ou evidência técnica disponível.</span>
            </span>
          </label>
          <label class="flex items-start gap-3">
            <CheckboxInput v-model="form.refrigerated" type="checkbox" class="ds-checkbox mt-0.5" />
            <span>
              <span class="block text-sm font-medium">Conservação refrigerada</span>
              <span class="block text-[12.5px] text-[var(--pl-muted)]">Exigir localização com controlo térmico.</span>
            </span>
          </label>
        </div>
      </div>
    </section>

    <section v-if="isEquipment" class="pl-form-section">
      <header>
        <h2 class="pl-d3">Calibração e metrologia</h2>
        <p>Datas críticas, revisão e notas de aptidão técnica.</p>
      </header>

      <div class="pl-form-grid">
        <div class="ds-field-group">
          <datePickerEnhanced v-model="form.last_calibration_date" label="Última calibração" :has-error="Boolean(errorFor('last_calibration_date'))" />
          <p v-if="errorFor('last_calibration_date')" class="ds-field-error" role="alert">{{ errorFor('last_calibration_date') }}</p>
        </div>
        <div class="ds-field-group">
          <datePickerEnhanced v-model="form.next_calibration_date" label="Próxima calibração" :has-error="Boolean(errorFor('next_calibration_date'))" />
          <p v-if="errorFor('next_calibration_date')" class="ds-field-error" role="alert">{{ errorFor('next_calibration_date') }}</p>
        </div>
        <div class="ds-field-group">
          <datePickerEnhanced v-model="form.metrology_review_due_at" label="Próxima revisão metrológica" :has-error="Boolean(errorFor('metrology_review_due_at'))" />
          <p v-if="errorFor('metrology_review_due_at')" class="ds-field-error" role="alert">{{ errorFor('metrology_review_due_at') }}</p>
        </div>
        <div class="ds-field-group pl-span-2">
          <label class="ds-field-label" for="inventory-metrology-notes">Notas metrológicas</label>
          <textarea id="inventory-metrology-notes" v-model="form.metrology_notes" rows="3" class="ds-field" :aria-invalid="Boolean(errorFor('metrology_notes'))" placeholder="Restrições, decisão de aptidão ou plano de revisão"></textarea>
          <p v-if="errorFor('metrology_notes')" class="ds-field-error" role="alert">{{ errorFor('metrology_notes') }}</p>
        </div>
      </div>
    </section>

    <section v-if="isReagent" class="pl-form-section">
      <header>
        <h2 class="pl-d3">Validade e embalagem</h2>
        <p>Janela de utilização e dimensões logísticas do reagente.</p>
      </header>

      <div class="pl-form-grid">
        <div class="ds-field-group">
          <datePickerEnhanced v-model="form.reagent_expiry_date" label="Data de validade" :has-error="Boolean(errorFor('reagent_expiry_date'))" />
          <p v-if="errorFor('reagent_expiry_date')" class="ds-field-error" role="alert">{{ errorFor('reagent_expiry_date') }}</p>
        </div>
        <div class="ds-field-group">
          <datePickerEnhanced v-model="form.reagent_open_date" label="Data de abertura" :has-error="Boolean(errorFor('reagent_open_date'))" />
          <p v-if="errorFor('reagent_open_date')" class="ds-field-error" role="alert">{{ errorFor('reagent_open_date') }}</p>
        </div>
        <div v-for="dimension in packagingDimensions" :key="dimension.key" class="ds-field-group">
          <label class="ds-field-label" :for="'inventory-' + dimension.key">{{ dimension.label }}</label>
          <div class="grid grid-cols-[minmax(0,1fr)_5.5rem] gap-2">
            <BaseInput :id="'inventory-' + dimension.key" v-model="form[dimension.key]" type="number" min="0" step="0.01" class="ds-field" />
            <BaseSelect v-model="form[dimension.unitKey]" class="ds-field" :aria-label="`Unidade de ${dimension.label.toLowerCase()}`">
              <option v-for="unit in dimension.units" :key="unit" :value="unit">{{ unit }}</option>
            </BaseSelect>
          </div>
          <p v-if="errorFor(dimension.key)" class="ds-field-error" role="alert">{{ errorFor(dimension.key) }}</p>
        </div>
      </div>
    </section>

    <section class="pl-form-section">
      <header>
        <h2 class="pl-d3">Existências por armazém</h2>
        <p>{{ mode === 'edit' ? 'Saldos do laboratório activo. Ajustes e transferências ficam no registo de movimentos.' : 'Defina o saldo inicial, o mínimo e o ponto de reposição em cada localização.' }}</p>
        <div>
          <button v-if="mode === 'create'" type="button" class="ds-button ds-button-secondary" @click="emit('add-warehouse')">
            <PlusIcon class="h-4 w-4" aria-hidden="true" />
            Adicionar armazém
          </button>
          <Link v-else :href="backHref" class="ds-button ds-button-secondary">Ver movimentos</Link>
        </div>
      </header>

      <div v-if="form.warehouses.length" class="grid content-start gap-4">
        <article v-for="(warehouse, index) in form.warehouses" :key="warehouse.row_key || index" class="pl-panel">
          <div class="pl-panel-head">
            <h3 class="pl-k">Localização {{ index + 1 }} · {{ warehouseInfo[index]?.name || warehouse.id_obj?.label || 'por escolher' }}</h3>
            <button v-if="mode === 'create'" type="button" class="ds-table-action ds-table-action-danger" title="Remover armazém" @click="emit('remove-warehouse', index)">
              Remover
            </button>
          </div>

          <div v-if="mode === 'create'" class="pl-form-grid p-4">
            <div class="ds-field-group pl-span-2">
              <label class="ds-field-label">Armazém <span class="ds-field-required">*</span></label>
              <comboboxEnhanced
                :model-value="warehouse.id_obj"
                :has-error="Boolean(warehouseErrors[index]?.id)"
                :options="warehouseOptions"
                placeholder="Escolher armazém"
                @update:model-value="updateWarehouseSelection(index, $event)"
              />
              <p v-if="warehouseErrors[index]?.id" class="ds-field-error" role="alert">{{ warehouseErrors[index].id }}</p>
            </div>
            <div class="ds-field-group">
              <label class="ds-field-label" :for="'warehouse-qty-' + index">Quantidade inicial</label>
              <BaseInput :id="'warehouse-qty-' + index" v-model="warehouse.qty_available" type="number" min="0" step="0.0001" class="ds-field" :aria-invalid="Boolean(warehouseErrors[index]?.qty_available)" />
              <p v-if="warehouseErrors[index]?.qty_available" class="ds-field-error" role="alert">{{ warehouseErrors[index].qty_available }}</p>
            </div>
            <div class="ds-field-group">
              <label class="ds-field-label" :for="'warehouse-min-' + index">Existência mínima</label>
              <BaseInput :id="'warehouse-min-' + index" v-model="warehouse.min_stock_level" type="number" min="0" step="0.0001" class="ds-field" />
            </div>
            <div class="ds-field-group">
              <label class="ds-field-label" :for="'warehouse-reorder-' + index">Ponto de reposição</label>
              <BaseInput :id="'warehouse-reorder-' + index" v-model="warehouse.reorder_point" type="number" min="0" step="0.0001" class="ds-field" />
            </div>
          </div>
          <dl v-else class="pl-facts">
            <div class="pl-fact"><dt>Disponível</dt><dd class="pl-num">{{ warehouse.qty_available }}</dd></div>
            <div class="pl-fact"><dt>Mínimo</dt><dd class="pl-num">{{ warehouse.min_stock_level }}</dd></div>
            <div class="pl-fact"><dt>Ponto de reposição</dt><dd class="pl-num">{{ warehouse.reorder_point }}</dd></div>
          </dl>

          <p v-if="warehouseInfo[index]" class="border-t border-[var(--pl-line)] px-4 py-3 text-[12.5px] text-[var(--pl-muted)]">
            {{ warehouseInfo[index].location?.name || 'Localização não definida' }}
            · {{ warehouseInfo[index].is_refrigerated ? 'Refrigerado' : 'Temperatura ambiente' }}
            · <span class="pl-num">{{ warehouseInfo[index].code || 'Sem código' }}</span>
          </p>
        </article>
      </div>

      <div v-else class="ds-empty-state grid justify-items-start gap-2 p-6">
        <span class="pl-k">Nenhum armazém configurado</span>
        <p class="text-sm text-[var(--pl-muted)]">{{ mode === 'edit' ? 'Este laboratório ainda não tem existências registadas para o item.' : 'Adicione uma localização para controlar as existências desde a entrada.' }}</p>
      </div>
    </section>

    <section class="pl-form-section">
      <header>
        <h2 class="pl-d3">Documentação técnica</h2>
        <p>Fichas de segurança, certificados, manuais e evidências.</p>
      </header>

      <div class="grid content-start gap-4">
        <label
          class="flex cursor-pointer flex-col items-center justify-center border border-dashed border-[var(--pl-line-strong)] px-5 py-8 text-center transition-colors focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-[var(--pl-accent-text)] hover:bg-[var(--pl-layer)]"
          :class="{ 'bg-[var(--pl-layer)]': dragging }"
          @dragenter.prevent="dragging = true"
          @dragleave.prevent="dragging = false"
          @dragover.prevent
          @drop.prevent="onDroppedFiles"
        >
          <DocumentPlusIcon class="h-6 w-6 text-[var(--pl-accent-text)]" aria-hidden="true" />
          <span class="mt-3 text-sm font-medium">Escolha ou arraste ficheiros</span>
          <span class="mt-1 text-[12.5px] text-[var(--pl-muted)]">PDF, imagens e documentos técnicos.</span>
          <FileInput type="file" multiple class="sr-only" @change="onSelectedFiles" />
        </label>

        <div v-if="documentItems.length" class="pl-panel">
          <div v-for="(document, index) in documentItems" :key="document.id || document.name || index" class="pl-row">
            <span class="min-w-0">
              <span class="block truncate font-medium">{{ document.name || 'Documento' }}</span>
              <span class="pl-num block text-[12.5px] text-[var(--pl-muted)]">{{ readableFileSize(document.size || 0) }}</span>
            </span>
            <button type="button" class="ds-table-action" :disabled="form.processing || attachmentProcessing" :aria-label="document.id ? 'Arquivar documento' : 'Remover ficheiro não guardado'" @click="removeDocument(document, index)">
              <ArchiveBoxIcon v-if="document.id" class="h-4 w-4" aria-hidden="true" />
              <TrashIcon v-else class="h-4 w-4" aria-hidden="true" />
              Remover
            </button>
          </div>
        </div>
        <p v-if="errorFor('documents')" class="ds-field-error" role="alert">{{ errorFor('documents') }}</p>
      </div>
    </section>

    <p v-if="errorFor('request')" class="ds-field-error" role="alert">{{ errorFor('request') }}</p>

    <NextStepBar>
      {{ nextStepText }}
      <template #actions>
        <Link :href="backHref" class="ds-button ds-button-quiet">{{ backLabel }}</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing">
          {{ form.processing ? 'A guardar…' : submitLabel }}
        </button>
      </template>
    </NextStepBar>
  </form>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
  FilePlus as DocumentPlusIcon,
  Plus as PlusIcon,
  Trash2 as TrashIcon,
  Archive as ArchiveBoxIcon,
} from '@lucide/vue'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import datePickerEnhanced from '@/Components/date-picker-enhanced.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import NextStepBar from '@/Components/plano/NextStepBar.vue'

/**
 * The inventory item form (Plano form template) shared by Create and Edit: one
 * section per concern in a two-column grid and a single next-step bar that holds
 * the submit. Stock is seeded on creation only; later it moves through adjustments.
 */
const props = defineProps({
  attachmentProcessing: { type: Boolean, default: false },
  mode: {
    type: String,
    default: 'create',
  },
  crumbs: {
    type: Array,
    default: () => [],
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
  identityLocks: {
    type: Object,
    default: () => ({ category: false, unit: false }),
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
  departments: {
    type: Array,
    default: () => [],
  },
  equipmentCategories: {
    type: Array,
    default: () => [],
  },
  packagingCategories: {
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
const selectedDepartment = defineModel('selectedDepartment')
const selectedEquipmentCategory = defineModel('selectedEquipmentCategory')
const selectedPackagingCategory = defineModel('selectedPackagingCategory')

const dragging = ref(false)

const categoryOptions = computed(() => props.categories.map(category => ({ value: category.id, label: category.name })))
const typeOptions = computed(() => props.types.map(type => ({ value: type.id, label: type.name })))
const supplierOptions = computed(() => props.suppliers.map(supplier => ({ value: supplier.id, label: supplier.name })))
const unitOptions = computed(() => props.units.map(unit => ({ value: unit.id, label: `${unit.code} - ${unit.description || unit.name || ''}`.trim() })))
const warehouseOptions = computed(() => props.warehouses.map(warehouse => ({ value: warehouse.id, label: warehouse.name })))
const departmentOptions = computed(() => props.departments.map(department => ({ value: department.id, label: department.name })))
const equipmentCategoryOptions = computed(() => props.equipmentCategories.map(category => ({ value: category.id, label: category.name })))
const packagingCategoryOptions = computed(() => props.packagingCategories.map(category => ({ value: category.id, label: category.name })))
const documentItems = computed(() => Array.isArray(props.form.documents) ? props.form.documents : [])

/** Fields the server requires (name, category, unit), in the order they appear. */
const missingFields = computed(() => [
  props.form.name ? null : 'nome',
  props.form.category_id ? null : 'categoria',
  props.form.unit_id ? null : 'unidade',
].filter(Boolean))

const countLabel = (count, singular, plural) => `${count} ${count === 1 ? singular : plural}`

const nextStepText = computed(() => {
  if (missingFields.value.length) {
    return `Falta preencher: ${missingFields.value.join(', ')}.`
  }

  const documents = documentItems.value.length
    ? ` · ${countLabel(documentItems.value.length, 'documento', 'documentos')}`
    : ''

  if (props.mode === 'edit') {
    return `Guardar os dados técnicos${documents}. As existências ajustam-se no dossier do item, com registo de movimento.`
  }

  return `${selectedCategory.value?.label || 'Item'}: ${countLabel(props.form.warehouses.length, 'armazém', 'armazéns')} com ${props.totalStock} em existências iniciais${documents}. Confirme os saldos antes de criar.`
})

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
  if (props.form.processing || props.attachmentProcessing) return;
  if (props.mode === 'edit' && document?.id && props.item?.id) {
    emit('delete-attachment', props.item.id, document.id, index)
    return
  }

  props.form.documents.splice(index, 1)
}
</script>
