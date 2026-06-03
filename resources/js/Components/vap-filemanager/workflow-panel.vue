<template>
  <section class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
    <div class="border-b border-slate-200 bg-slate-50 px-5 py-5 dark:border-slate-700 dark:bg-slate-800/90">
      <div class="flex items-start justify-between gap-4">
        <div>
          <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Workflow</p>
          <h2 class="mt-2 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $t('gestlab.general.labels.vap_filemanager.workflow_tasks') }}</h2>
          <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
            Distribua revisão, aprovação e publicação com responsáveis, prazos e histórico de comentários.
          </p>
        </div>
        <div
          v-if="selectedFile"
          class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-right shadow-sm dark:border-slate-600 dark:bg-slate-900/80"
        >
          <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Tarefas abertas</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ openTaskCount }}</p>
        </div>
      </div>
    </div>

    <div class="p-5">
      <div v-if="selectedFile" class="space-y-5">
        <div class="grid gap-3 sm:grid-cols-3">
          <article
            v-for="card in workflowCards"
            :key="card.label"
            class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 dark:border-slate-700 dark:bg-slate-800/70"
          >
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ card.label }}</p>
            <p class="mt-3 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ card.value }}</p>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-300">{{ card.caption }}</p>
          </article>
        </div>

        <div class="rounded-[1.5rem] border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
          <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">
            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700 dark:text-slate-200">{{ $t('gestlab.general.labels.vap_filemanager.create_workflow_task') }}</h3>
          </div>
          <div class="grid gap-4 p-4">
            <label class="block text-sm">
              <span class="mb-1.5 block font-medium text-slate-700 dark:text-slate-200">Etapa</span>
              <select
                v-model="newTask.type"
                class="block w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
              >
                <option value="review">{{ $t('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.review') }}</option>
                <option value="approve">{{ $t('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.approve') }}</option>
                <option value="publish">{{ $t('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.publish') }}</option>
              </select>
            </label>

            <label class="block text-sm">
              <span class="mb-1.5 block font-medium text-slate-700 dark:text-slate-200">Responsável</span>
              <comboboxEnhanced v-model="newTask.assignedTo" :load-options="loadUsers"/>
            </label>

            <label class="block text-sm">
              <span class="mb-1.5 block font-medium text-slate-700 dark:text-slate-200">Prazo</span>
              <input
                type="date"
                v-model="newTask.dueDate"
                class="block w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
              />
            </label>

            <button
              class="rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
              @click="createTask"
            >
              {{ $t('gestlab.general.labels.vap_filemanager.buttons.create_task') }}
            </button>
          </div>
        </div>

        <div class="space-y-4">
          <div v-if="workflowStore.pendingTasks.length > 0">
            <div class="mb-3 flex items-center justify-between gap-3">
              <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700 dark:text-slate-200">{{ $t('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.pending_tasks') }}</h3>
              <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">{{ workflowStore.pendingTasks.length }}</span>
            </div>
            <div class="space-y-3">
              <TaskCard
                v-for="task in workflowStore.pendingTasks"
                :key="task.id"
                :task="task"
                @status-change="updateTaskStatus"
                @add-comment="addTaskComment"
              />
            </div>
          </div>

          <div v-if="workflowStore.inProgressTasks.length > 0">
            <div class="mb-3 flex items-center justify-between gap-3">
              <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700 dark:text-slate-200">{{ $t('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.in_progress_tasks') }}</h3>
              <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">{{ workflowStore.inProgressTasks.length }}</span>
            </div>
            <div class="space-y-3">
              <TaskCard
                v-for="task in workflowStore.inProgressTasks"
                :key="task.id"
                :task="task"
                @status-change="updateTaskStatus"
                @add-comment="addTaskComment"
              />
            </div>
          </div>

          <div
            v-if="workflowStore.pendingTasks.length === 0 && workflowStore.inProgressTasks.length === 0 && !workflowStore.isLoading"
            class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm leading-6 text-slate-500 dark:border-slate-600 dark:bg-slate-800/70 dark:text-slate-300"
          >
            {{ $t('gestlab.general.labels.vap_filemanager.no_active_workflow_tasks') }}
          </div>
        </div>
      </div>

      <div v-else class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm leading-6 text-slate-500 dark:border-slate-600 dark:bg-slate-800/70 dark:text-slate-300">
        {{ $t('gestlab.general.labels.vap_filemanager.select_document_workflow_hint') }}
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useWorkflowStore, type WorkflowTask } from '../../Stores/workflowStore'
import { useFileStore } from '../../Stores/fileStore'
import TaskCard from './task-card.vue'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import { loadSelectOptions, optionMappers } from '@/Utils/selectOptions'

const workflowStore = useWorkflowStore()
const fileStore = useFileStore()

const selectedFile = computed(() => {
  const selectedIds = Array.from(fileStore.selectedItems)
  return selectedIds.length === 1 ? fileStore.files.find(f => f.id === selectedIds[0]) : null
})

const openTaskCount = computed(() => workflowStore.pendingTasks.length + workflowStore.inProgressTasks.length)

const workflowCards = computed(() => {
  return [
    {
      label: 'Pendentes',
      value: workflowStore.pendingTasks.length,
      caption: 'Aguardam início ou decisão.',
    },
    {
      label: 'Em progresso',
      value: workflowStore.inProgressTasks.length,
      caption: 'Já atribuídas e em execução.',
    },
    {
      label: 'Estado seleccionado',
      value: selectedFile.value?.status || 'draft',
      caption: 'Situação documental actual.',
    },
  ]
})

const newTask = ref({
  type: 'review' as WorkflowTask['type'],
  assignedTo: null,
  dueDate: ''
})

watch(selectedFile, async (file) => {
  await workflowStore.fetchTasks(file?.id)
}, { immediate: true })

function selectedAssigneeId() {
  if (!newTask.value.assignedTo) {
    return null
  }

  if (typeof newTask.value.assignedTo === 'string') {
    return newTask.value.assignedTo
  }

  return newTask.value.assignedTo.value
}

async function createTask() {
  const assignedTo = selectedAssigneeId()

  if (selectedFile.value && assignedTo) {
    await workflowStore.createTask({
      fileId: selectedFile.value.id,
      type: newTask.value.type,
      assignedTo,
      dueDate: newTask.value.dueDate ? new Date(newTask.value.dueDate) : undefined
    })

    newTask.value = {
      type: 'review',
      assignedTo: null,
      dueDate: ''
    }
  }
}

async function updateTaskStatus(taskId: string, status: WorkflowTask['status']) {
  await workflowStore.updateTaskStatus(taskId, status)
}

async function addTaskComment(taskId: string, comment: string) {
  await workflowStore.addTaskComment(taskId, comment)
}

function loadUsers(query, setOptions) {
  return loadSelectOptions('/users/getUser', query, setOptions, optionMappers.name)
}
</script>
