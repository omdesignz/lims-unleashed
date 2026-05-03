<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <TagIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_labels.title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.vap_labels.description') }}
            <span class="font-semibold text-blue-900">
              {{ filters.type ? $t('gestlab.general.labels.vap_labels.types.' + filters.type) : $t('gestlab.general.labels.vap_labels.all_types') }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ stats.total }} {{ $t('gestlab.general.labels.vap_labels.total_labels') }}
          </span>
          <Link
            :href="route('vap_labels.labels.create')"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <PlusCircleIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_labels.buttons.create_label') }}
          </Link>
        </div>
      </div>
    </div>

    <!-- FILTERS CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            {{ $t('gestlab.general.labels.vap_labels.search') }}
          </label>
          <input
            v-model="filters.search"
            type="search"
            :placeholder="$t('gestlab.general.labels.vap_labels.search_placeholder')"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
          />
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            {{ $t('gestlab.general.labels.vap_labels.type') }}
          </label>
          <select
            v-model="filters.type"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
          >
            <option value="">{{ $t('gestlab.general.labels.vap_labels.all_types') }}</option>
            <option value="equipment">{{ $t('gestlab.general.labels.vap_labels.types.equipment') }}</option>
            <option value="material">{{ $t('gestlab.general.labels.vap_labels.types.material') }}</option>
            <option value="sample">{{ $t('gestlab.general.labels.vap_labels.types.sample') }}</option>
            <option value="custom">{{ $t('gestlab.general.labels.vap_labels.types.custom') }}</option>
          </select>
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            {{ $t('gestlab.general.labels.vap_labels.lab') }}
          </label>
          <select
            v-model="filters.lab_id"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
          >
            <option value="">{{ $t('gestlab.general.labels.vap_labels.all_labs') }}</option>
            <option v-for="lab in labs" :key="lab.id" :value="lab.id">
              {{ lab.name }}
            </option>
          </select>
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            {{ $t('gestlab.general.labels.vap_labels.status') }}
          </label>
          <select
            v-model="filters.status"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
          >
            <option value="">{{ $t('gestlab.general.labels.vap_labels.all_status') }}</option>
            <option value="active">{{ $t('gestlab.general.labels.vap_labels.active') }}</option>
            <option value="inactive">{{ $t('gestlab.general.labels.vap_labels.inactive') }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- STATS CARD -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.vap_labels.total_labels') }}</p>
            <p class="mt-2 text-3xl font-bold text-blue-900">{{ stats.total }}</p>
          </div>
          <TagIcon class="h-10 w-10 text-blue-100" />
        </div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.vap_labels.active_labels') }}</p>
            <p class="mt-2 text-3xl font-bold text-green-500">{{ stats.active }}</p>
          </div>
          <CheckCircleIcon class="h-10 w-10 text-green-100" />
        </div>
      </div>
      <div v-for="typeStat in stats.by_type" :key="typeStat.type" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.vap_labels.types.' + typeStat.type) }}</p>
            <p class="mt-2 text-3xl font-bold text-blue-700">{{ typeStat.count }}</p>
          </div>
          <div class="h-10 w-10 rounded-full flex items-center justify-center" :class="typeColor(typeStat.type)">
            <TagIcon class="h-6 w-6 text-white" />
          </div>
        </div>
      </div>
    </div>

    <!-- LABELS LIST -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <ListBulletIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_labels.list') }}
            <span class="text-sm font-normal text-gray-500 ml-2">
              ({{ labels.total }} {{ $t('gestlab.general.labels.vap_labels.general.items') }})
            </span>
          </h2>
        </div>
      </div>

      <!-- EMPTY STATE -->
      <div v-if="labels.data.length === 0" class="p-12 text-center">
        <TagIcon class="mx-auto h-12 w-12 text-gray-300" />
        <h3 class="mt-4 text-sm font-semibold text-gray-900">
          {{ $t('gestlab.general.labels.vap_labels.empty_state.title') }}
        </h3>
        <p class="mt-2 text-sm text-gray-500">
          {{ $t('gestlab.general.labels.vap_labels.empty_state.description') }}
        </p>
        <Link
          :href="route('vap_labels.labels.create')"
          class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
        >
          <PlusCircleIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.vap_labels.buttons.create_first_label') }}
        </Link>
      </div>

      <!-- LABELS TABLE -->
      <div v-else>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('gestlab.general.labels.vap_labels.name') }}
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('gestlab.general.labels.vap_labels.type') }}
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('gestlab.general.labels.vap_labels.dimensions') }}
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('gestlab.general.labels.vap_labels.lab') }}
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('gestlab.general.labels.vap_labels.status') }}
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('gestlab.general.labels.vap_labels.actions') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr 
                v-for="label in labels.data" 
                :key="label.id"
                class="hover:bg-gray-50 transition-colors duration-150"
                v-motion
                :initial="{ opacity: 0, y: 10 }"
                :enter="{ opacity: 1, y: 0 }"
                :delay="100"
              >
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="h-10 w-10 flex-shrink-0">
                      <div class="h-10 w-10 rounded-lg flex items-center justify-center" :style="{ backgroundColor: label.background_color, color: label.text_color, border: `${label.border_width}px solid ${label.border_color}` }">
                        <TagIcon class="h-6 w-6" />
                      </div>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900">
                        {{ label.name }}
                      </div>
                      <div class="text-sm text-gray-500">
                        {{ label.content.substring(0, 30) }}...
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="typeBadgeClass(label.type)">
                    {{ $t('gestlab.general.labels.vap_labels.types.' + label.type) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ label.width }} × {{ label.height }} mm
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ label.lab?.name || '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="statusBadgeClass(label.is_active)">
                    {{ label.is_active ? $t('gestlab.general.labels.vap_labels.active') : $t('gestlab.general.labels.vap_labels.inactive') }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <div class="flex items-center gap-2">
                    <Link
                      :href="route('vap_labels.labels.show', label.id)"
                      class="text-blue-900 hover:text-blue-800 p-1 rounded hover:bg-blue-50"
                      :title="$t('gestlab.general.labels.vap_labels.buttons.view')"
                    >
                      <EyeIcon class="h-5 w-5" />
                    </Link>
                    <Link
                      :href="route('vap_labels.labels.edit', label.id)"
                      class="text-blue-900 hover:text-blue-800 p-1 rounded hover:bg-blue-50"
                      :title="$t('gestlab.general.labels.vap_labels.buttons.edit')"
                    >
                      <PencilIcon class="h-5 w-5" />
                    </Link>
                    <button
                      @click="toggleStatus(label)"
                      :class="[
                        'p-1 rounded',
                        label.is_active 
                          ? 'text-yellow-500 hover:text-yellow-600 hover:bg-yellow-50' 
                          : 'text-green-500 hover:text-green-600 hover:bg-green-50'
                      ]"
                      :title="label.is_active ? $t('gestlab.general.labels.vap_labels.buttons.deactivate') : $t('gestlab.general.labels.vap_labels.buttons.activate')"
                    >
                      <PowerIcon class="h-5 w-5" />
                    </button>
                    <button
                      @click="confirmDelete(label)"
                      class="text-red-600 hover:text-red-800 p-1 rounded hover:bg-red-50"
                      :title="$t('gestlab.general.labels.vap_labels.buttons.delete')"
                    >
                      <TrashIcon class="h-5 w-5" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- PAGINATION -->
        <div class="border-t border-gray-200 px-6 py-4">
          <Pagination :links="labels.links" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { 
  TagIcon, 
  PlusCircleIcon, 
  ListBulletIcon, 
  EyeIcon, 
  PencilIcon, 
  TrashIcon, 
  PowerIcon,
  CheckCircleIcon 
} from '@heroicons/vue/24/outline'
import { debounce } from 'lodash'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  labels: Object,
  filters: Object,
  stats: Object,
  labs: Array,
  departments: Array,
})

const filters = ref(props.filters)

const typeBadgeClass = (type) => {
  const classes = {
    equipment: 'bg-blue-100 text-blue-800',
    material: 'bg-green-100 text-green-800',
    sample: 'bg-purple-100 text-purple-800',
    custom: 'bg-gray-100 text-gray-800',
  }
  return `inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ${classes[type] || 'bg-gray-100 text-gray-800'}`
}

const statusBadgeClass = (isActive) => {
  return isActive 
    ? 'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium bg-green-100 text-green-800'
    : 'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium bg-gray-100 text-gray-800'
}

const typeColor = (type) => {
  const colors = {
    equipment: 'bg-blue-500',
    material: 'bg-green-500',
    sample: 'bg-purple-500',
    custom: 'bg-gray-500',
  }
  return colors[type] || 'bg-gray-500'
}

const confirmDelete = (label) => {
  if (confirm('Are you sure you want to delete this label?')) {
    router.delete(route('vap_labels.labels.destroy', label.id))
  }
}

const toggleStatus = (label) => {
  router.post(route('vap_labels.toggle-status', label.id), {}, {
    preserveScroll: true,
  })
}

// Debounce filter changes
const applyFilters = debounce(() => {
  router.get(route('vap_labels.labels.index'), filters.value, {
    preserveState: true,
    preserveScroll: true,
  })
}, 300)

watch(filters, applyFilters, { deep: true })
</script>
