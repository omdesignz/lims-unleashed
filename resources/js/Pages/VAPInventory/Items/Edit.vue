<template>
  <InventoryItemFormSurface
    mode="edit"
    :crumbs="[{ title: 'Inventário' }, { title: 'Itens', url: route('vap-inventory.items.index') }, { title: item.name, url: route('vap-inventory.items.show', item.id) }, { title: 'Modificar' }]"
    :title="'Modificar ' + item.name"
    description="Actualize os dados técnicos, os documentos e os controlos metrológicos. As existências não se alteram aqui: ajustam-se no dossier do item, com registo de movimento."
    :back-href="route('vap-inventory.items.show', item.id)"
    back-label="Cancelar"
    submit-label="Guardar alterações"
    :form="form"
    :attachment-processing="documentProcessing"
    :errors="errors"
    :categories="categories"
    :types="types"
    :status-options="statusOptions"
    :suppliers="suppliers"
    :units="units"
    :departments="departments"
    :equipment-categories="equipmentCategories"
    :packaging-categories="packagingCategories"
    :warehouses="warehouses"
    :warehouse-info="warehouseInfo"
    :is-reagent="isReagent"
    :is-equipment="isEquipment"
    :total-stock="totalStock"
    :item="item"
    :identity-locks="identityLocks"
    v-model:selected-category="selectedCategory"
    v-model:selected-type="selectedType"
    v-model:selected-status="selectedStatus"
    v-model:selected-supplier="selectedSupplier"
    v-model:selected-unit="selectedUnit"
    v-model:selected-department="selectedDepartment"
    v-model:selected-equipment-category="selectedEquipmentCategory"
    v-model:selected-packaging-category="selectedPackagingCategory"
    @submit="submit"
    @delete-attachment="deleteAttachment"
  >
    <template #notice>
      <p v-if="documentMessage" :role="documentFailed ? 'alert' : 'status'" class="mb-4 text-sm" :class="{ 'ds-field-error': documentFailed }">{{ documentMessage }}</p>
    </template>
  </InventoryItemFormSurface>
</template>
<script setup>
import { useRecordArchive } from '@/Composables/useRecordArchive'
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
  departments: Array,
  equipmentCategories: Array,
  packagingCategories: Array,
  warehouses: Array,
  errors: Object,
  documents: Array,
  identityLocks: Object,
})

const warehouseInfo = ref({})
const filteredStatuses = ref([])

const form = useForm({
  name: props.item.name,
  location: props.item.location,
  department_id: props.item.department_id,
  eq_cat_id: props.item.eq_cat_id,
  packaging_type_id: props.item.packaging_type_id,
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

const selection = (rows, id, label = row => row.name) => {
  const row = rows?.find(row => Number(row.id) === Number(id))
  return row ? { value: row.id, label: label(row) } : null
}
const selectedCategory = ref(selection(props.categories, props.item.category_id))
const selectedType = ref(selection(props.types, props.item.type_id))
const selectedStatus = ref(selection(props.allStatuses, props.item.status_id))
const selectedSupplier = ref(selection(props.suppliers, props.item.supplier_id))
const selectedUnit = ref(selection(props.units, props.item.unit_id, unit => `${unit.code} - ${unit.description || unit.name || ''}`.trim()))
const selectedDepartment = ref(selection(props.departments, props.item.department_id))
const selectedEquipmentCategory = ref(selection(props.equipmentCategories, props.item.eq_cat_id))
const selectedPackagingCategory = ref(selection(props.packagingCategories, props.item.packaging_type_id))

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
watch(selectedDepartment, (selection) => form.department_id = selection?.value ?? null)
watch(selectedEquipmentCategory, (selection) => form.eq_cat_id = selection?.value ?? null)
watch(selectedPackagingCategory, (selection) => form.packaging_type_id = selection?.value ?? null)


const isReagent = computed(() => {
  if (!selectedCategory.value) return false
  const category = props.categories.find(c => c.id === selectedCategory.value.value)
  return category?.is_reagent || category?.name?.toLowerCase().includes('reagente') || false
})

const isEquipment = computed(() => {
  if (!selectedCategory.value) return false
  const category = props.categories.find(c => c.id === selectedCategory.value.value)
  return category?.inventory_type === 'equipment'
})

const totalStock = computed(() => {
  return form.warehouses.reduce((sum, wh) => sum + (Number(wh.qty_available) || 0), 0)
})

const submit = () => {
  if (form.processing || documentProcessing.value) return
  form.clearErrors('request')
  const hasNewDocuments = form.documents.some(document => document instanceof File)
  const options = {
    preserveScroll: true,
    onHttpException: () => {
      form.setError('request', 'Não foi possível guardar as alterações. Confirme os dados e as permissões antes de tentar novamente.')
      return false
    },
    onNetworkError: () => {
      form.setError('request', 'Ligação interrompida. A operação não foi confirmada; verifique o registo antes de repetir.')
      return false
    },
  }
  form.transform(({ warehouses, ...data }) => ({
    ...data,
    documents: data.documents.filter(document => document instanceof File),
    ...(hasNewDocuments ? { _method: 'put' } : {}),
  }))

  if (hasNewDocuments) {
    form.post(route('vap-inventory.items.update', props.item.id), options)
    return
  }

  form.put(route('vap-inventory.items.update', props.item.id), options)
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
  }

})

const { processing: documentProcessing, message: documentMessage, failed: documentFailed, submit: submitDocumentArchive } = useRecordArchive({
  destroyUrl: ids => route('vap-inventory.items.attachments.delete', { model_id: props.item.id, id: ids[0] }),
  onSuccess: () => { form.documents = form.documents.filter(document => document instanceof File || props.documents.some(retained => retained.id === document.id)); },
});

function deleteAttachment(model_id, id) {
  if (form.processing || documentProcessing.value || model_id !== props.item.id) return;
  submitDocumentArchive('delete', [id]);
}
</script>
