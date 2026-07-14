<template>
  <InventoryItemFormSurface
    mode="edit"
    :title="'Editar item: ' + item.name"
    description="Actualize dados técnicos, existências por armazém, anexos e controlos metrológicos mantendo a rastreabilidade do item."
    :back-href="route('vap-inventory.items.show', item.id)"
    back-label="Voltar ao item"
    submit-label="Guardar alterações"
    :form="form"
    :errors="errors"
    :categories="categories"
    :types="types"
    :status-options="statusOptions"
    :suppliers="suppliers"
    :units="units"
    :warehouses="warehouses"
    :warehouse-info="warehouseInfo"
    :warehouse-errors="warehouseErrors"
    :is-reagent="isReagent"
    :is-equipment="isEquipment"
    :total-stock="totalStock"
    :item="item"
    v-model:selected-category="selectedCategory"
    v-model:selected-type="selectedType"
    v-model:selected-status="selectedStatus"
    v-model:selected-supplier="selectedSupplier"
    v-model:selected-unit="selectedUnit"
    @submit="submit"
    @add-warehouse="addWarehouse"
    @remove-warehouse="removeWarehouse"
    @update-warehouse-info="updateWarehouseInfo"
    @delete-attachment="deleteAttachment"
  />
</template>
<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import InventoryItemFormSurface from '@/Components/vap-inventory/InventoryItemFormSurface.vue'

const props = defineProps({
  item: Object,
  categories: Array,
  types: Array,
//   statuses: Array,
  allStatuses: Array,
  suppliers: Array,
  units: Array,
  warehouses: Array,
  errors: Object,
  documents: Array,
})

const warehouseInfo = ref({})
const filteredStatuses = ref([])
const warehouseErrors = ref({})

const form = useForm({
  name: props.item.name,
  code: props.item.code,
  category_id: props.item.category_id,
  type_id: props.item.type_id,
  unit_id: props.item.unit_id,
  status_id: props.item.status_id,
  supplier_id: props.item.supplier_id,
  barcode: props.item.barcode,
  serial_number: props.item.serial_number,
  internal_code: props.item.internal_code,
  model: props.item.model,
  brand: props.item.brand,
  lot: props.item.lot,
  resolution: props.item.resolution,
  precision: props.item.precision,
  range: props.item.range,
  metrological_uncertainty_value: props.item.metrological_uncertainty_value,
  metrological_uncertainty_unit: props.item.metrological_uncertainty_unit,
  metrological_traceability_reference: props.item.metrological_traceability_reference,
  firmware: props.item.firmware,
  software: props.item.software,
  description: props.item.description,
  acceptance_criteria: props.item.acceptance_criteria,
  obs: props.item.obs,
  reorder_qty: props.item.reorder_qty,
  packed_depth: props.item.packed_depth,
  packed_width: props.item.packed_width,
  packed_height: props.item.packed_height,
  packed_weight: props.item.packed_weight,
  standard_cost: props.item.standard_cost || 0,
  last_purchase_price: props.item.last_purchase_price || 0,
  packed_depth_unit: props.item.packed_depth_unit,
  packed_width_unit: props.item.packed_width_unit,
  packed_height_unit: props.item.packed_height_unit,
  packed_weight_unit: props.item.packed_weight_unit,
  has_safety_documentation: props.item.has_safety_documentation,
  refrigerated: props.item.refrigerated,
  reagent_expiry_date: props.item.reagent_expiry_date,
  reagent_open_date: props.item.reagent_open_date,
  next_calibration_date: props.item.next_calibration_date,
  last_calibration_date: props.item.last_calibration_date,
  metrology_review_due_at: props.item.metrology_review_due_at,
  metrology_notes: props.item.metrology_notes,
  warehouses: [],
  documents: props.documents || [],
})

const selectedCategory = ref(null)
const selectedType = ref(null)
const selectedStatus = ref(null)
const selectedSupplier = ref(null)
const selectedUnit = ref(null)

const updateFilteredStatuses = (categoryId = null, forceIncludeCurrent = false) => {
  if (!props.allStatuses || props.allStatuses.length === 0) {
    filteredStatuses.value = []
    return
  }
  
  const allStatuses = [...props.allStatuses]
  let result = []
  
  if (categoryId) {
    const catId = Number(categoryId)
    
    // Filter statuses for this category
    result = allStatuses.filter(status => {
      const statusCatId = status.category_id !== null && status.category_id !== undefined 
        ? Number(status.category_id) 
        : null
      
      return statusCatId === null || statusCatId === catId
    })
  } else {
    // Only global statuses
    result = allStatuses.filter(status => status.category_id === null)
  }
  
  // Always include the current status if it exists
  if (props.item.status_id) {
    const currentStatus = allStatuses.find(s => s.id === props.item.status_id)
    if (currentStatus && !result.some(s => s.id === currentStatus.id)) {
      result.push(currentStatus)
    }
  }
  
  filteredStatuses.value = result
}

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

// watch(selectedCategory, (newVal) => form.category_id = newVal?.value || '')
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

const totalStock = computed(() => {
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

// const updateWarehouseInfo = (index) => {
//   const warehouseId = form.warehouses[index].id
//   if (warehouseId) {
//     const warehouse = props.warehouses.find(w => w.id == warehouseId)
//     warehouseInfo.value[index] = warehouse
//   } else {
//     delete warehouseInfo.value[index]
//   }
// }

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
      errors.qty_available = 'Quantidade válida é obrigatória'
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

  form.put(route('vap-inventory.items.update', props.item.id), {
    preserveScroll: true,
    onError: (errors) => {
      // Handle warehouse errors separately
      if (errors.warehouses) {
        try {
          const whErrors = JSON.parse(errors.warehouses)
          warehouseErrors.value = whErrors
        } catch {
          warehouseErrors.value = {}
        }
      }
    },
    onSuccess: () => {
      // Success - optionally show message
    },
  })
}

onMounted(() => {
  if (props.item.inventory?.length) {
    props.item.inventory.forEach(inv => {
      form.warehouses.push({
        id_obj: {
          value: inv.warehouse_id,
          label: inv.warehouse?.name,
        },
        id: inv.warehouse_id,
        qty_available: inv.qty_available,
        min_stock_level: inv.min_stock_level,
        reorder_point: inv.reorder_point,
      })
      
      // Load warehouse info
      const warehouse = props.warehouses.find(w => w.id == inv.warehouse_id)
      if (warehouse) {
        warehouseInfo.value[form.warehouses.length - 1] = warehouse
      }
    })
  } else {
    addWarehouse()
  }

  const category = props.categories.find(category => Number(category.id) === Number(props.item.category_id))
  if (category) {
    selectedCategory.value = {
      value: category.id,
      label: category.name,
    }
  }

  const type = props.types.find(type => Number(type.id) === Number(props.item.type_id))
  if (type) {
    selectedType.value = {
      value: type.id,
      label: type.name,
    }
  }

  const unit = props.units.find(unit => Number(unit.id) === Number(props.item.unit_id))
  if (unit) {
    selectedUnit.value = {
      value: unit.id,
      label: `${unit.code} - ${unit.description || unit.name || ''}`.trim(),
    }
  }

  const status = props.allStatuses.find(status => Number(status.id) === Number(props.item.status_id))
  if (status) {
    selectedStatus.value = {
      value: status.id,
      label: status.name,
    }
  }

  const supplier = props.suppliers.find(supplier => Number(supplier.id) === Number(props.item.supplier_id))
  if (supplier) {
    selectedSupplier.value = {
      value: supplier.id,
      label: supplier.name,
    }
  }
})

const deleteForm = useForm({
    model_id: null,
    id: null,
});


function deleteAttachment(model_id, id, index) {
    deleteForm.model_id = model_id;
    deleteForm.id = id;

    deleteForm.delete(route('vap-inventory.items.delete-attachment', {model_id: model_id, id: id}), {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            form.documents.splice(index, 1);
        },
    });
}
</script>
