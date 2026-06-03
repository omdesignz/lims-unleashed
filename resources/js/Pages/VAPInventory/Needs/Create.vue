<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <ModuleHero
      eyebrow="Procurement control"
      title="Nova necessidade operacional"
      description="Registe equipamentos, reagentes e materiais necessários para o laboratório. Depois da validação, a necessidade segue para aprovação e pode ser convertida em pedido de compra rastreável."
    >
      <template #actions>
        <Link :href="route('vap-inventory.needs.index')" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white/80 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-200 dark:hover:bg-slate-800">
          Voltar
        </Link>
      </template>
    </ModuleHero>

    <form class="space-y-6" @submit.prevent="submit">
      <ModuleCard
        title="Contexto da necessidade"
        description="Associe a requisição ao departamento/laboratório correto e deixe clara a urgência operacional."
      >
        <div class="grid gap-4 md:grid-cols-2">
          <BaseSelect v-model="form.department_id" label="Departamento" :error="form.errors.department_id">
            <option value="">Selecione</option>
            <option v-for="department in departments" :key="department.id" :value="department.id">{{ department.name }}</option>
          </BaseSelect>
          <BaseSelect v-model="form.lab_id" label="Laboratório" :error="form.errors.lab_id">
            <option value="">Opcional</option>
            <option v-for="lab in filteredLabs" :key="lab.id" :value="lab.id">{{ lab.name }}</option>
          </BaseSelect>
          <BaseInput v-model="form.needed_by_date" type="date" label="Necessário até" :error="form.errors.needed_by_date" />
          <div class="md:col-span-2">
            <BaseTextarea
              v-model="form.justification"
              label="Justificação operacional"
              :rows="4"
              placeholder="Explique o motivo da necessidade, impacto no serviço e urgência."
              :error="form.errors.justification"
            />
          </div>
        </div>
      </ModuleCard>

      <ModuleCard
        title="Itens solicitados"
        description="Combine reagentes, equipamentos e materiais numa única necessidade, com especificação e armazém alvo."
      >
        <template #actions>
          <button type="button" class="rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800" @click="addItem">
            Adicionar item
          </button>
        </template>

        <div class="mt-6 space-y-4">
          <article v-for="(item, index) in form.items" :key="index" class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-950/45">
            <div class="grid gap-4 md:grid-cols-4">
              <div class="md:col-span-2">
                <BaseSelect v-model="item.inventory_item_id" label="Item" :error="form.errors[`items.${index}.inventory_item_id`]">
                  <option value="">Selecione</option>
                  <option v-for="inventoryItem in items" :key="inventoryItem.id" :value="inventoryItem.id">
                    {{ inventoryItem.name }}{{ inventoryItem.code ? ` · ${inventoryItem.code}` : '' }}
                  </option>
                </BaseSelect>
              </div>
              <BaseInput v-model="item.quantity_requested" type="number" min="1" label="Quantidade" :error="form.errors[`items.${index}.quantity_requested`]" />
              <BaseSelect v-model="item.warehouse_id" label="Armazém" :error="form.errors[`items.${index}.warehouse_id`]">
                  <option value="">A definir</option>
                  <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
              </BaseSelect>
              <BaseInput v-model="item.estimated_unit_price" type="number" min="0" step="0.01" label="Preço estimado" :error="form.errors[`items.${index}.estimated_unit_price`]" />
              <div class="md:col-span-2">
                <BaseInput v-model="item.notes" label="Notas do item" placeholder="Marca preferencial, especificação, referência, etc." :error="form.errors[`items.${index}.notes`]" />
              </div>
              <div class="md:col-span-1 md:flex md:items-end">
                <button v-if="form.items.length > 1" type="button" class="w-full rounded-2xl border border-rose-200 px-4 py-2.5 text-sm font-medium text-rose-700 transition hover:bg-rose-50 dark:border-rose-500/30 dark:text-rose-300 dark:hover:bg-rose-500/10" @click="removeItem(index)">
                  Remover
                </button>
              </div>
            </div>
          </article>
        </div>
      </ModuleCard>

      <div class="flex justify-end">
        <button type="submit" class="rounded-2xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-primary-500 dark:hover:bg-primary-400" :disabled="form.processing">
          {{ form.processing ? 'A submeter...' : 'Submeter necessidade' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import BaseTextarea from '@/Components/base/BaseTextarea.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import ModuleCard from '@/Components/base/ModuleCard.vue'
import ModuleHero from '@/Components/base/ModuleHero.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { Link, useForm } from '@inertiajs/vue3'
import { computed, watch } from 'vue'

defineOptions({ layout: Layout })

const props = defineProps({
  departments: Array,
  labs: Array,
  items: Array,
  warehouses: Array,
})

const form = useForm({
  department_id: '',
  lab_id: '',
  needed_by_date: '',
  justification: '',
  items: [
    { inventory_item_id: '', warehouse_id: '', quantity_requested: 1, estimated_unit_price: '', notes: '' },
  ],
})

const filteredLabs = computed(() => {
  if (!form.department_id) {
    return props.labs
  }

  return props.labs.filter((lab) => String(lab.department_id) === String(form.department_id))
})

watch(() => form.department_id, () => {
  if (!form.lab_id) {
    return
  }

  const selectedLabStillMatchesDepartment = filteredLabs.value.some((lab) => String(lab.id) === String(form.lab_id))

  if (!selectedLabStillMatchesDepartment) {
    form.lab_id = ''
  }
})

const addItem = () => {
  form.items.push({ inventory_item_id: '', warehouse_id: '', quantity_requested: 1, estimated_unit_price: '', notes: '' })
}

const removeItem = (index) => {
  form.items.splice(index, 1)
}

const submit = () => {
  form.post(route('vap-inventory.needs.store'))
}
</script>
