<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <TagIcon class="h-7 w-7 text-blue-900" />
            Categorias de Manutenção
          </h1>
          <p class="mt-2 text-gray-600">
            Gerencie os tipos de manutenção e calibração disponíveis no sistema
          </p>
        </div>
        <div class="flex items-center gap-3">
          <button
            @click="showCreateModal = true"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
          >
            <PlusIcon class="h-5 w-5" />
            Nova Categoria
          </button>
        </div>
      </div>
    </div>

    <!-- SEARCH & FILTERS -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div class="flex-1 max-w-lg">
          <div class="relative">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
            <input
              v-model="search"
              type="search"
              placeholder="Pesquisar categorias..."
              class="w-full rounded-lg border border-gray-300 pl-10 pr-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
              @input="applySearch"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- CATEGORIES GRID -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="category in categories.data"
        :key="category.id"
        class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-200"
      >
        <!-- CATEGORY HEADER -->
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-white">
              {{ category.name }}
            </h3>
            <div class="flex items-center gap-2">
              <button
                @click="editCategory(category)"
                class="text-white hover:text-blue-200 transition-colors p-1"
                title="Editar"
              >
                <PencilIcon class="h-4 w-4" />
              </button>
              <button
                @click="deleteCategory(category)"
                class="text-white hover:text-red-200 transition-colors p-1"
                title="Eliminar"
              >
                <TrashIcon class="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
        
        <!-- CATEGORY CONTENT -->
        <div class="p-6">
          <div class="space-y-4">
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">
                Código
              </label>
              <div class="text-sm font-medium text-gray-900">
                {{ category.code || 'Não definido' }}
              </div>
            </div>
            
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">
                Descrição
              </label>
              <div class="text-sm text-gray-700">
                {{ category.description || 'Sem descrição' }}
              </div>
            </div>
            
            <div class="pt-4 border-t border-gray-200">
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">Criada em:</span>
                <span class="font-medium text-gray-900">
                  {{ formatDate(category.created_at) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- EMPTY STATE -->
    <div v-if="categories.data.length === 0" class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
      <TagIcon class="mx-auto h-12 w-12 text-gray-300" />
      <h3 class="mt-4 text-sm font-semibold text-gray-900">
        Nenhuma categoria encontrada
      </h3>
      <p class="mt-2 text-sm text-gray-500">
        Comece por criar a sua primeira categoria de manutenção
      </p>
      <button
        @click="showCreateModal = true"
        class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
      >
        <PlusIcon class="h-5 w-5" />
        Criar Primeira Categoria
      </button>
    </div>

    <!-- PAGINATION -->
    <div v-if="categories.data.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <Pagination :links="categories.links" />
    </div>

    <!-- CREATE/EDIT MODAL -->
    <Modal :show="showCreateModal || editingCategory" @close="closeModal">
      <div class="p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">
          {{ editingCategory ? 'Editar Categoria' : 'Nova Categoria de Manutenção' }}
        </h2>
        
        <form @submit.prevent="submitForm">
          <div class="space-y-6">
            <!-- NAME -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Nome da Categoria *
              </label>
              <input
                v-model="form.name"
                type="text"
                required
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                placeholder="Ex: Calibração Interna"
              />
              <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">
                {{ form.errors.name }}
              </p>
            </div>
            
            <!-- CODE -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Código
              </label>
              <input
                v-model="form.code"
                type="text"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                placeholder="Ex: CAL_INT"
              />
              <p class="mt-1 text-xs text-gray-500">
                Código único para identificar o tipo de manutenção
              </p>
              <p v-if="form.errors.code" class="mt-1 text-xs text-red-600">
                {{ form.errors.code }}
              </p>
            </div>
            
            <!-- DESCRIPTION -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Descrição
              </label>
              <textarea
                v-model="form.description"
                rows="3"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                placeholder="Descreva o propósito desta categoria..."
              ></textarea>
            </div>
          </div>
          
          <!-- FORM ACTIONS -->
          <div class="mt-8 flex items-center justify-end gap-3">
            <button
              type="button"
              @click="closeModal"
              class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <CheckIcon class="h-5 w-5" />
              {{ form.processing ? 'A processar...' : (editingCategory ? 'Atualizar' : 'Criar') }}
            </button>
          </div>
        </form>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import {
  TagIcon,
  PlusIcon,
  MagnifyingGlassIcon,
  PencilIcon,
  TrashIcon,
  CheckIcon,
} from '@heroicons/vue/24/outline'
import Modal from '@/Components/Modal.vue'
import Pagination from '@/Components/Pagination.vue'
import { debounce } from 'lodash'

const props = defineProps({
  categories: Object,
  filters: Object,
})

const search = ref(props.filters.search || '')
const showCreateModal = ref(false)
const editingCategory = ref(null)

const form = useForm({
  name: '',
  code: '',
  description: '',
})

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const applySearch = debounce(() => {
  router.get(route('vap-maintenance.categories'), { search: search.value }, {
    preserveState: true,
    replace: true,
  })
}, 300)

const editCategory = (category) => {
  editingCategory.value = category
  form.name = category.name
  form.code = category.code
  form.description = category.description
}

const deleteCategory = (category) => {
  if (confirm('Tem a certeza que deseja eliminar esta categoria? Esta ação não pode ser revertida.')) {
    router.delete(route('vap-maintenance.categories.destroy', category.id))
  }
}

const submitForm = () => {
  if (editingCategory.value) {
    form.put(route('vap-maintenance.categories.update', editingCategory.value.id), {
      onSuccess: () => {
        closeModal()
      }
    })
  } else {
    form.post(route('vap-maintenance.categories.store'), {
      onSuccess: () => {
        closeModal()
      }
    })
  }
}

const closeModal = () => {
  showCreateModal.value = false
  editingCategory.value = null
  form.reset()
  form.clearErrors()
}

// Watch search
watch(search, applySearch)
</script>