<template>
  <article class="rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <span class="inline-flex max-w-full items-center rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-600 dark:bg-slate-800 dark:text-slate-300">
          {{ task.file.name }}
        </span>
        <p class="mt-3 text-sm font-semibold text-slate-900 dark:text-slate-100">
          {{ $t('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.' + task.type) }}
        </p>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
          {{ $t('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.assigned_to') }} {{ task.assignee.name }}
        </p>
      </div>

      <select
        :value="task.status"
        class="rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
        @change="handleStatusChange"
      >
        <option value="pending">{{ trans('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.task_statuses.pending') }}</option>
        <option value="in_progress">{{ trans('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.task_statuses.in_progress') }}</option>
        <option value="completed">{{ trans('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.task_statuses.completed') }}</option>
        <option value="rejected">{{ trans('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.task_statuses.rejected') }}</option>
      </select>
    </div>

    <dl class="mt-4 grid gap-3 sm:grid-cols-3">
      <div class="rounded-2xl bg-slate-50 px-3 py-3 dark:bg-slate-800/70">
        <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.created_at') }}</dt>
        <dd class="mt-2 text-sm font-medium text-slate-900 dark:text-slate-100">{{ formatDate(task.createdAt) }}</dd>
      </div>
      <div class="rounded-2xl bg-slate-50 px-3 py-3 dark:bg-slate-800/70">
        <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.due_date') }}</dt>
        <dd class="mt-2 text-sm font-medium text-slate-900 dark:text-slate-100">{{ formatDate(task.dueDate) || $t('gestlab.general.labels.vap_filemanager.no_due_date') }}</dd>
      </div>
      <div class="rounded-2xl bg-slate-50 px-3 py-3 dark:bg-slate-800/70">
        <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.completed') }}</dt>
        <dd class="mt-2 text-sm font-medium text-slate-900 dark:text-slate-100">{{ formatDate(task.completedAt) || 'Em curso' }}</dd>
      </div>
    </dl>

    <div v-if="task.comments.length > 0" class="mt-4 space-y-2">
      <div
        v-for="(comment, index) in task.comments"
        :key="index"
        class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-700 dark:bg-slate-800/70"
      >
        <p class="font-semibold text-slate-800 dark:text-slate-100">{{ comment.creator.name }}</p>
        <p class="mt-1 leading-6 text-slate-600 dark:text-slate-300">{{ comment.comment }}</p>
      </div>
    </div>

    <div class="mt-4 flex gap-2">
      <input
        type="text"
        v-model="newComment"
        :placeholder="$t('gestlab.general.labels.vap_filemanager.labels.workflow_tasks.add_comment_placeholder')"
        class="flex-1 rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-400"
        @keyup.enter="addComment"
      />
      <button
        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
        @click="addComment"
      >
        {{ $t('gestlab.general.labels.vap_filemanager.buttons.add_comment') }}
      </button>
    </div>
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
