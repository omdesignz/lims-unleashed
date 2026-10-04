<template>
  <article class="pl-panel">
    <div class="flex items-start justify-between gap-3 p-4">
      <div class="min-w-0">
        <p class="pl-k pl-faint truncate">{{ task.file.name }}</p>
        <p class="mt-2 font-medium">
          {{ $t('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.' + task.type) }}
        </p>
        <p class="mt-1 text-[12.5px] text-[var(--pl-muted)]">
          {{ $t('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.assigned_to') }} {{ task.assignee.name }}
        </p>
      </div>

      <div class="w-44 shrink-0">
        <BaseSelect :model-value="task.status" aria-label="Estado da tarefa" @change="handleStatusChange">
          <option value="pending">{{ trans('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.task_statuses.pending') }}</option>
          <option value="in_progress">{{ trans('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.task_statuses.in_progress') }}</option>
          <option value="completed">{{ trans('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.task_statuses.completed') }}</option>
          <option value="rejected">{{ trans('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.task_statuses.rejected') }}</option>
        </BaseSelect>
      </div>
    </div>

    <dl class="pl-facts border-t border-[var(--pl-line)]">
      <div class="pl-fact">
        <dt>{{ $t('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.created_at') }}</dt>
        <dd class="pl-num">{{ formatDate(task.createdAt) }}</dd>
      </div>
      <div class="pl-fact">
        <dt>{{ $t('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.due_date') }}</dt>
        <dd class="pl-num">{{ formatDate(task.dueDate) || $t('gestlab.general.labels.vap_filemanager.no_due_date') }}</dd>
      </div>
      <div class="pl-fact">
        <dt>{{ $t('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.completed') }}</dt>
        <dd class="pl-num">{{ formatDate(task.completedAt) || 'Em curso' }}</dd>
      </div>
    </dl>

    <ul v-if="task.comments.length > 0" class="border-t border-[var(--pl-line)]" aria-label="Comentários">
      <li v-for="(comment, index) in task.comments" :key="index" class="border-b border-[var(--pl-line)] px-4 py-3 text-sm last:border-b-0">
        <p class="font-medium">{{ comment.creator.name }}</p>
        <p class="mt-1 leading-6 text-[var(--pl-muted)]">{{ comment.comment }}</p>
      </li>
    </ul>

    <form class="flex gap-2 border-t border-[var(--pl-line)] p-4" @submit.prevent="addComment">
      <BaseInput
        v-model="newComment"
        type="text"
        class="ds-field flex-1"
        :aria-label="$t('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.add_comment_placeholder')"
        :placeholder="$t('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.add_comment_placeholder')"
      />
      <button type="submit" class="ds-button ds-button-secondary">
        {{ $t('gestlab.general.labels.vap_filemanager.buttons.add_comment') }}
      </button>
    </form>
  </article>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { WorkflowTask } from '../../Stores/workflowStore'
import { trans } from 'laravel-vue-i18n'

const props = defineProps<{
  task: WorkflowTask
}>()

const emit = defineEmits<{
  (e: 'status-change', taskId: string, status: WorkflowTask['status']): void
  (e: 'add-comment', taskId: string, comment: string): void
}>()

const newComment = ref('')

function formatDate(date: Date | undefined) {
  if (!date) {
    return ''
  }

  return new Intl.DateTimeFormat('pt-PT', {
    dateStyle: 'medium',
    timeStyle: 'short'
  }).format(date)
}

function addComment() {
  if (newComment.value.trim()) {
    emit('add-comment', props.task.id, newComment.value.trim())
    newComment.value = ''
  }
}

function handleStatusChange(event: Event) {
  const select = event.target as HTMLSelectElement
  const newStatus = select.value as WorkflowTask['status']
  emit('status-change', props.task.id, newStatus)
}
</script>
