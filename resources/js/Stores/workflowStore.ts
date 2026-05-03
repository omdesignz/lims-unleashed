import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'
import { trans } from 'laravel-vue-i18n';


export interface WorkflowTask {
  id: string
  fileId: string,
  file: Object,
  type: 'review' | 'approve' | 'publish'
  assignedTo: string
  status: 'pending' | 'in_progress' | 'completed' | 'rejected'
  createdAt: Date
  dueDate?: Date
  completedAt?: Date
  comments: string[]
}

export const useWorkflowStore = defineStore('workflow', () => {
  const toast = useToast()
  const tasks = ref<WorkflowTask[]>([])
  const isLoading = ref(false)

  const pendingTasks = computed(() => 
    tasks.value.filter(task => task.status === 'pending')
  )

  const inProgressTasks = computed(() => 
    tasks.value.filter(task => task.status === 'in_progress')
  )

  async function fetchTasks(fileId?: string) {
    try {
      isLoading.value = true
      const response = await axios.get('/api/workflow/tasks', {
        params: { file_id: fileId }
      })
      tasks.value = response.data.data.map((task: any) => ({
        ...task,
        createdAt: new Date(task.created_at),
        dueDate: task.due_date ? new Date(task.due_date) : undefined,
        completedAt: task.completed_at ? new Date(task.completed_at) : undefined
      }))
    } catch (error) {
    //   toast.error('Failed to fetch workflow tasks')
      toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_fetching_workflow_tasks'))
      console.error('Error fetching tasks:', error)
    } finally {
      isLoading.value = false
    }
  }

  async function createTask(task: Omit<WorkflowTask, 'id' | 'createdAt' | 'status' | 'comments'>) {
    try {
      const response = await axios.post('/api/workflow/tasks', {
        file_id: task.fileId,
        type: task.type,
        assigned_to: task.assignedTo,
        due_date: task.dueDate?.toISOString()
      })

      const newTask = {
        ...response.data.data,
        createdAt: new Date(response.data.data.created_at),
        dueDate: response.data.data.due_date ? new Date(response.data.data.due_date) : undefined,
        completedAt: response.data.data.completed_at ? new Date(response.data.data.completed_at) : undefined
      }

      tasks.value.push(newTask)
    //   toast.success('Task created successfully')
      toast.success(trans('gestlab.general.labels.vap_filemanager.notifications.task_created'))
    } catch (error) {
    //   toast.error('Failed to create task')
      toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_creating_task'))
      console.error('Error creating task:', error)
    }
  }

  async function updateTaskStatus(taskId: string, status: WorkflowTask['status']) {
    try {
      const response = await axios.put(`/api/workflow/tasks/${taskId}/status`, { status })
      const task = tasks.value.find(t => t.id === taskId)
      if (task) {
        task.status = status
        task.completedAt = response.data.data.completed_at ? new Date(response.data.data.completed_at) : undefined
      }
    //   toast.success('Task status updated successfully')
      toast.success(trans('gestlab.general.labels.vap_filemanager.notifications.task_updated'))
    } catch (error) {
    //   toast.error('Failed to update task status')
      toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_updating_task'))
      console.error('Error updating task status:', error)
    }
  }

  async function addTaskComment(taskId: string, comment: string) {
    try {
      const response = await axios.post(`/api/workflow/tasks/${taskId}/comments`, { comment })
      const task = tasks.value.find(t => t.id === taskId)
      if (task) {
        task.comments = response.data.data.comments
      }
    //   toast.success('Comment added successfully')
      toast.success(trans('gestlab.general.labels.vap_filemanager.notifications.task_comment_added'))
    } catch (error) {
    //   toast.error('Failed to add comment')
      toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_adding_task_comment'))
      console.error('Error adding comment:', error)
    }
  }

  // Initialize by fetching tasks
  fetchTasks()

  return {
    tasks,
    pendingTasks,
    inProgressTasks,
    isLoading,
    fetchTasks,
    createTask,
    updateTaskStatus,
    addTaskComment
  }
})