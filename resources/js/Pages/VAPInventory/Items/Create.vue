<template>
  <InventoryItemFormSurface
    mode="create"
    title="Adicionar item"
    description="Registe reagentes, equipamentos e consumíveis com existências inicial, rastreabilidade, anexos e controlos metrológicos desde a entrada."
    :back-href="route('vap-inventory.items.index')"
    back-label="Voltar para itens"
    submit-label="Criar item"
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
    :total-stock="totalInitialStock"
    v-model:selected-category="selectedCategory"
    v-model:selected-type="selectedType"
    v-model:selected-status="selectedStatus"
    v-model:selected-supplier="selectedSupplier"
    v-model:selected-unit="selectedUnit"
    @submit="submit"
    @add-warehouse="addWarehouse"
    @remove-warehouse="removeWarehouse"
    @update-warehouse-info="updateWarehouseInfo"
  />
</template>
<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import InventoryItemFormSurface from '@/Components/vap-inventory/InventoryItemFormSurface.vue'

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
        } catch {
          warehouseErrors.value = {}
        }
      }
    },
  })
}


// Initialize with one empty warehouse
addWarehouse()

onMounted(() => {
  // Initial filter for global statuses
  updateFilteredStatuses(null)
})
</script>
