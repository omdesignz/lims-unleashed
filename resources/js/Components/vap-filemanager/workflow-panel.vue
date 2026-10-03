<template>
  <section class="grid gap-6" aria-label="Fluxo documental">
    <div v-if="selectedFile" class="grid gap-6">
      <dl class="pl-panel pl-facts">
        <div v-for="card in workflowCards" :key="card.label" class="pl-fact">
          <dt>{{ card.label }}</dt>
          <dd>
            <span class="pl-num font-medium">{{ card.value }}</span>
            <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ card.caption }}</span>
          </dd>
        </div>
      </dl>

      <section class="pl-panel" aria-labelledby="workflow-new-task">
        <div class="pl-panel-head">
          <h3 id="workflow-new-task" class="pl-k">{{ $t('gestlab.general.labels.vap_filemanager.create_workflow_task') }}</h3>
          <span class="pl-k pl-faint">{{ openTaskCount }} abertas</span>
        </div>
        <div class="grid gap-4 p-4 sm:grid-cols-2">
          <BaseSelect v-model="newTask.type" label="Etapa">
            <option value="review">{{ $t('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.review') }}</option>
            <option value="approve">{{ $t('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.approve') }}</option>
            <option value="publish">{{ $t('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.publish') }}</option>
          </BaseSelect>

          <DateTimePicker v-model="newTask.dueDate" type="date" label="Prazo" />

          <div class="ds-field-group sm:col-span-2">
            <span class="ds-field-label">Responsável</span>
            <comboboxEnhanced v-model="newTask.assignedTo" :load-options="loadUsers" />
          </div>

          <div class="sm:col-span-2">
            <button type="button" class="ds-button ds-button-primary" @click="createTask">
              {{ $t('gestlab.general.labels.vap_filemanager.buttons.create_task') }}
            </button>
          </div>
        </div>
      </section>

      <section v-if="workflowStore.pendingTasks.length > 0" class="grid gap-3" aria-labelledby="workflow-pending">
        <div class="flex items-center justify-between gap-3">
          <h3 id="workflow-pending" class="pl-k">{{ $t('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.pending_tasks') }}</h3>
          <span class="pl-k pl-num pl-faint">{{ workflowStore.pendingTasks.length }}</span>
        </div>
        <TaskCard
          v-for="task in workflowStore.pendingTasks"
          :key="task.id"
          :task="task"
          @status-change="updateTaskStatus"
          @add-comment="addTaskComment"
        />
      </section>

      <section v-if="workflowStore.inProgressTasks.length > 0" class="grid gap-3" aria-labelledby="workflow-in-progress">
        <div class="flex items-center justify-between gap-3">
          <h3 id="workflow-in-progress" class="pl-k">{{ $t('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.in_progress_tasks') }}</h3>
          <span class="pl-k pl-num pl-faint">{{ workflowStore.inProgressTasks.length }}</span>
        </div>
        <TaskCard
          v-for="task in workflowStore.inProgressTasks"
          :key="task.id"
          :task="task"
          @status-change="updateTaskStatus"
          @add-comment="addTaskComment"
        />
      </section>

      <div
        v-if="workflowStore.pendingTasks.length === 0 && workflowStore.inProgressTasks.length === 0 && !workflowStore.isLoading"
        class="ds-empty-state grid justify-items-start gap-2 p-6"
      >
        <span class="pl-k">Sem tarefas activas</span>
        <p class="text-sm text-[var(--pl-muted)]">{{ $t('gestlab.general.labels.vap_filemanager.no_active_workflow_tasks') }}</p>
      </div>
    </div>

    <div v-else class="ds-empty-state grid justify-items-start gap-2 p-6">
      <span class="pl-k">Nenhum documento seleccionado</span>
      <p class="text-sm text-[var(--pl-muted)]">{{ $t('gestlab.general.labels.vap_filemanager.select_document_workflow_hint') }}</p>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useWorkflowStore, type WorkflowTask } from '../../Stores/workflowStore'
import { useFileStore } from '../../Stores/fileStore'
import TaskCard from './task-card.vue'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import DateTimePicker from '@/Components/base/DateTimePicker.vue'
import { loadSelectOptions, optionMappers } from '@/Utils/selectOptions'

const workflowStore = useWorkflowStore()
const fileStore = useFileStore()

const selectedFile = computed(() => {
  const selectedIds = Array.from(fileStore.selectedItems)
  return selectedIds.length === 1 ? fileStore.files.find(f => f.id === selectedIds[0]) : null
})

const statusLabels: Record<string, string> = {
  draft: 'Rascunho',
  in_review: 'Em revisão',
  approved: 'Aprovado',
  effective: 'Efectivo',
  obsolete: 'Obsoleto',
  archived: 'Arquivado',
}

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
      label: 'Estado do documento',
      value: statusLabels[selectedFile.value?.status || 'draft'] ?? selectedFile.value?.status,
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
