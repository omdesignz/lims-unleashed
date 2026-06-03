<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import {
  BeakerIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  PlusCircleIcon,
  PencilSquareIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline'
import { computed, ref, watch } from 'vue'

defineOptions({
  layout: Layout,
})

const props = defineProps({
  conditions: Object,
  filters: Object,
  stats: Object,
})

const editingId = ref(null)

const filters = useForm({
  search: props.filters?.search || '',
  status: props.filters?.status || '',
})

const form = useForm({
  area: '',
  location: '',
  recorded_at: new Date().toISOString().slice(0, 16),
  temperature_c: '',
  humidity_percent: '',
  pressure_kpa: '',
  co2_ppm: '',
  temperature_min_c: '',
  temperature_max_c: '',
  humidity_min_percent: '',
  humidity_max_percent: '',
  notes: '',
})

const isEditing = computed(() => editingId.value !== null)

const submit = () => {
  if (isEditing.value) {
    form.put(route('environmental-conditions.update', editingId.value), {
      preserveScroll: true,
      onSuccess: resetForm,
    })

    return
  }

  form.post(route('environmental-conditions.store'), {
    preserveScroll: true,
    onSuccess: resetForm,
  })
}

const editCondition = (condition) => {
  editingId.value = condition.id
  form.area = condition.area || ''
  form.location = condition.location || ''
  form.recorded_at = (condition.recorded_at || '').slice(0, 16)
  form.temperature_c = condition.temperature_c ?? ''
  form.humidity_percent = condition.humidity_percent ?? ''
  form.pressure_kpa = condition.pressure_kpa ?? ''
  form.co2_ppm = condition.co2_ppm ?? ''
  form.temperature_min_c = condition.temperature_min_c ?? ''
  form.temperature_max_c = condition.temperature_max_c ?? ''
  form.humidity_min_percent = condition.humidity_min_percent ?? ''
  form.humidity_max_percent = condition.humidity_max_percent ?? ''
  form.notes = condition.notes || ''
}

const removeCondition = (conditionId) => {
  if (!window.confirm('Remover este registo ambiental?')) {
    return
  }

  router.delete(route('environmental-conditions.destroy', conditionId), {
    preserveScroll: true,
  })
}

const resetForm = () => {
  editingId.value = null
  form.reset()
  form.recorded_at = new Date().toISOString().slice(0, 16)
  form.clearErrors()
}

const applyFilters = () => {
  router.get(route('environmental-conditions.index'), {
    search: filters.search || undefined,
    status: filters.status || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

watch(() => filters.status, applyFilters)

const statusClasses = (status) => {
  if (status === 'critical') {
    return 'bg-red-100 text-red-800'
  }

  if (status === 'pending') {
    return 'bg-yellow-100 text-yellow-800'
  }

  return 'bg-green-100 text-green-800'
}

const statusLabel = (status) => {
  if (status === 'critical') {
    return 'Fora dos limites'
  }

  if (status === 'pending') {
    return 'Pendente'
  }

  return 'Dentro dos limites'
}
</script>

<template>
  <Head title="Condições Ambientais" />

  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h1 class="flex items-center gap-2 text-2xl font-bold text-gray-900">
            <BeakerIcon class="h-7 w-7 text-blue-900" />
            Condições Ambientais
          </h1>
          <p class="mt-2 max-w-3xl text-sm text-gray-600">
            Registe temperatura, humidade, pressão e CO2 por área para garantir controlo ambiental e rastreabilidade operacional.
          </p>
        </div>
        <div class="flex flex-wrap gap-3">
          <Link
            :href="route('temperatures.index')"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            Catálogo de Temperaturas
          </Link>
          <button
            type="button"
            @click="resetForm"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
          >
            <PlusCircleIcon class="h-4 w-4" />
            Novo Registo
          </button>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
      <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="text-sm text-gray-500">Total</div>
        <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.total }}</div>
      </div>
      <div class="rounded-xl border border-red-200 bg-red-50 p-5 shadow-sm">
        <div class="text-sm text-red-700">Críticos</div>
        <div class="mt-2 text-3xl font-bold text-red-900">{{ stats.critical }}</div>
      </div>
      <div class="rounded-xl border border-green-200 bg-green-50 p-5 shadow-sm">
        <div class="text-sm text-green-700">Dentro dos limites</div>
        <div class="mt-2 text-3xl font-bold text-green-900">{{ stats.within_limits }}</div>
      </div>
      <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
        <div class="text-sm text-blue-700">Registos hoje</div>
        <div class="mt-2 text-3xl font-bold text-blue-900">{{ stats.today }}</div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-8 xl:grid-cols-[1.1fr,1.9fr]">
      <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">
              {{ isEditing ? 'Atualizar condição' : 'Novo registo ambiental' }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">
              Defina também os limites aplicáveis para identificar desvios automaticamente.
            </p>
          </div>
          <button
            v-if="isEditing"
            type="button"
            @click="resetForm"
            class="text-sm font-medium text-blue-900 hover:text-blue-700"
          >
            Cancelar edição
          </button>
        </div>

        <form class="space-y-4" @submit.prevent="submit">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Área</label>
              <input v-model="form.area" type="text" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900" />
              <p v-if="form.errors.area" class="mt-1 text-xs text-red-600">{{ form.errors.area }}</p>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Localização</label>
              <input v-model="form.location" type="text" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900" />
              <p v-if="form.errors.location" class="mt-1 text-xs text-red-600">{{ form.errors.location }}</p>
            </div>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Data e hora do registo</label>
            <input v-model="form.recorded_at" type="datetime-local" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900" />
            <p v-if="form.errors.recorded_at" class="mt-1 text-xs text-red-600">{{ form.errors.recorded_at }}</p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Temperatura (°C)</label>
              <input v-model="form.temperature_c" type="number" step="0.01" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Humidade (%)</label>
              <input v-model="form.humidity_percent" type="number" step="0.01" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Pressão (kPa)</label>
              <input v-model="form.pressure_kpa" type="number" step="0.01" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">CO2 (ppm)</label>
              <input v-model="form.co2_ppm" type="number" step="0.01" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Temperatura mínima</label>
              <input v-model="form.temperature_min_c" type="number" step="0.01" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Temperatura máxima</label>
              <input v-model="form.temperature_max_c" type="number" step="0.01" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Humidade mínima</label>
              <input v-model="form.humidity_min_percent" type="number" step="0.01" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Humidade máxima</label>
              <input v-model="form.humidity_max_percent" type="number" step="0.01" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900" />
            </div>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Notas</label>
            <textarea v-model="form.notes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900" />
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 disabled:cursor-not-allowed disabled:bg-blue-400"
          >
            <CheckCircleIcon class="h-4 w-4" />
            {{ isEditing ? 'Atualizar registo' : 'Guardar registo' }}
          </button>
        </form>
      </section>

      <section class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-[1.6fr,1fr]">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Pesquisar</label>
              <input
                v-model="filters.search"
                type="text"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                placeholder="Área, localização, notas..."
                @keyup.enter="applyFilters"
              />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Estado</label>
              <select v-model="filters.status" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900">
                <option value="">Todos</option>
                <option value="within_limits">Dentro dos limites</option>
                <option value="critical">Fora dos limites</option>
                <option value="pending">Pendente</option>
              </select>
            </div>
          </div>
          <div class="mt-4 flex justify-end">
            <button type="button" @click="applyFilters" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
              Aplicar filtros
            </button>
          </div>
        </div>

        <div class="space-y-4">
          <article
            v-for="condition in conditions.data"
            :key="condition.id"
            class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"
          >
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
              <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                  <h3 class="text-lg font-semibold text-gray-900">{{ condition.area }}</h3>
                  <span :class="['inline-flex rounded-full px-3 py-1 text-xs font-medium', statusClasses(condition.status)]">
                    {{ statusLabel(condition.status) }}
                  </span>
                </div>
                <p class="text-sm text-gray-500">
                  {{ condition.location || 'Sem localização definida' }} · {{ new Date(condition.recorded_at).toLocaleString() }}
                </p>
                <div class="grid grid-cols-2 gap-3 text-sm text-gray-700 md:grid-cols-4">
                  <div>Temp: <span class="font-semibold">{{ condition.temperature_c ?? '--' }}</span></div>
                  <div>Hum.: <span class="font-semibold">{{ condition.humidity_percent ?? '--' }}</span></div>
                  <div>Pressão: <span class="font-semibold">{{ condition.pressure_kpa ?? '--' }}</span></div>
                  <div>CO2: <span class="font-semibold">{{ condition.co2_ppm ?? '--' }}</span></div>
                </div>
                <p v-if="condition.notes" class="text-sm text-gray-600">{{ condition.notes }}</p>
                <p class="text-xs text-gray-400">
                  Registado por {{ condition.recorded_by?.name || 'Sistema' }}
                </p>
              </div>
              <div class="flex gap-2">
                <button type="button" @click="editCondition(condition)" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                  <PencilSquareIcon class="h-4 w-4" />
                  Editar
                </button>
                <button type="button" @click="removeCondition(condition.id)" class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-100">
                  <TrashIcon class="h-4 w-4" />
                  Remover
                </button>
              </div>
            </div>
          </article>

          <div v-if="conditions.data.length === 0" class="rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center shadow-sm">
            <ExclamationTriangleIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">Nenhum registo encontrado</h3>
            <p class="mt-2 text-sm text-gray-500">
              Ajuste os filtros ou registe a primeira condição ambiental desta área.
            </p>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>
