<template>
  <div class="space-y-8">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-700">New need</p>
          <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">Nova necessidade operacional</h1>
          <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
            Registe equipamentos, reagentes e materiais necessários para o seu laboratório. Depois da validação, a necessidade segue para aprovação e pode ser convertida em pedido de compra.
          </p>
        </div>
        <Link :href="route('vap-inventory.needs.index')" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
          Voltar
        </Link>
      </div>
    </section>

    <form class="space-y-6" @submit.prevent="submit">
      <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="grid gap-4 md:grid-cols-2">
          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Departamento</label>
            <select v-model="form.department_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
              <option value="">Selecione</option>
              <option v-for="department in departments" :key="department.id" :value="department.id">{{ department.name }}</option>
            </select>
            <p v-if="form.errors.department_id" class="mt-1 text-xs text-rose-600">{{ form.errors.department_id }}</p>
          </div>
          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Laboratório</label>
            <select v-model="form.lab_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
              <option value="">Opcional</option>
              <option v-for="lab in filteredLabs" :key="lab.id" :value="lab.id">{{ lab.name }}</option>
            </select>
            <p v-if="form.errors.lab_id" class="mt-1 text-xs text-rose-600">{{ form.errors.lab_id }}</p>
          </div>
          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Necessário até</label>
            <input v-model="form.needed_by_date" type="date" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
          </div>
          <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-medium text-slate-700">Justificação operacional</label>
            <textarea v-model="form.justification" rows="4" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" placeholder="Explique o motivo da necessidade, impacto no serviço e urgência."></textarea>
          </div>
        </div>
      </section>

      <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">Itens solicitados</h2>
            <p class="mt-1 text-sm text-slate-500">Pode combinar itens para reagentes, equipamentos e materiais.</p>
          </div>
          <button type="button" class="rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800" @click="addItem">
            Adicionar item
          </button>
        </div>

        <div class="mt-6 space-y-4">
          <article v-for="(item, index) in form.items" :key="index" class="rounded-2xl border border-slate-200 p-4">
            <div class="grid gap-4 md:grid-cols-4">
              <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-medium text-slate-700">Item</label>
                <select v-model="item.inventory_item_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                  <option value="">Selecione</option>
                  <option v-for="inventoryItem in items" :key="inventoryItem.id" :value="inventoryItem.id">
                    {{ inventoryItem.name }}{{ inventoryItem.code ? ` · ${inventoryItem.code}` : '' }}
                  </option>
                </select>
                <p v-if="form.errors[`items.${index}.inventory_item_id`]" class="mt-1 text-xs text-rose-600">
                  {{ form.errors[`items.${index}.inventory_item_id`] }}
                </p>
              </div>
              <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Quantidade</label>
                <input v-model="item.quantity_requested" type="number" min="1" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                <p v-if="form.errors[`items.${index}.quantity_requested`]" class="mt-1 text-xs text-rose-600">
                  {{ form.errors[`items.${index}.quantity_requested`] }}
                </p>
              </div>
              <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Armazém</label>
                <select v-model="item.warehouse_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                  <option value="">A definir</option>
                  <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
                </select>
                <p v-if="form.errors[`items.${index}.warehouse_id`]" class="mt-1 text-xs text-rose-600">
                  {{ form.errors[`items.${index}.warehouse_id`] }}
                </p>
              </div>
              <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Preço estimado</label>
                <input v-model="item.estimated_unit_price" type="number" min="0" step="0.01" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                <p v-if="form.errors[`items.${index}.estimated_unit_price`]" class="mt-1 text-xs text-rose-600">
                  {{ form.errors[`items.${index}.estimated_unit_price`] }}
                </p>
              </div>
              <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-medium text-slate-700">Notas do item</label>
                <input v-model="item.notes" type="text" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm" placeholder="Marca preferencial, especificação, referência, etc.">
                <p v-if="form.errors[`items.${index}.notes`]" class="mt-1 text-xs text-rose-600">
                  {{ form.errors[`items.${index}.notes`] }}
                </p>
              </div>
              <div class="md:col-span-1 md:flex md:items-end">
                <button v-if="form.items.length > 1" type="button" class="w-full rounded-2xl border border-rose-200 px-4 py-2.5 text-sm font-medium text-rose-700 hover:bg-rose-50" @click="removeItem(index)">
                  Remover
                </button>
              </div>
            </div>
          </article>
        </div>
      </section>

      <div class="flex justify-end">
        <button type="submit" class="rounded-2xl bg-cyan-700 px-5 py-3 text-sm font-semibold text-white hover:bg-cyan-800" :disabled="form.processing">
          {{ form.processing ? 'A submeter...' : 'Submeter necessidade' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
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
