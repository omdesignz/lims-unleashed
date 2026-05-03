import { defineStore } from 'pinia'
import { ref } from 'vue'
import { io, Socket } from 'socket.io-client'
import { useToast } from 'vue-toastification'

export interface CollaborationEvent {
  type: 'edit' | 'rename' | 'move' | 'delete' | 'comment'
  userId: string
  fileId: string
  timestamp: Date
  details: any
}

export const useCollaborationStore = defineStore('collaboration', () => {
  const socket = ref<Socket | null>(null)
  const connectedUsers = ref<Set<string>>(new Set())
  const activeUsers = ref<Map<string, { fileId: string; lastActive: Date }>>(new Map())
  const events = ref<CollaborationEvent[]>([])
  const toast = useToast()

  function connect(userId: string) {
    socket.value = io('YOUR_COLLABORATION_SERVER_URL', {
      auth: { userId }
    })

    socket.value.on('user:connected', (userId: string) => {
      connectedUsers.value.add(userId)
      toast.info(`${userId} joined`)
    })

    socket.value.on('user:disconnected', (userId: string) => {
      connectedUsers.value.delete(userId)
      activeUsers.value.delete(userId)
      toast.info(`${userId} left`)
    })

    socket.value.on('file:activity', (data: { userId: string; fileId: string }) => {
      activeUsers.value.set(data.userId, {
        fileId: data.fileId,
        lastActive: new Date()
      })
    })

    socket.value.on('collaboration:event', (event: CollaborationEvent) => {
      events.value.push(event)
      notifyEvent(event)
    })
  }

  function notifyEvent(event: CollaborationEvent) {
    switch (event.type) {
      case 'edit':
        toast.info(`${event.userId} edited ${event.details.fileName}`)
        break
      case 'comment':
        toast.info(`${event.userId} commented on ${event.details.fileName}`)
        break
      case 'rename':
        toast.info(`${event.userId} renamed ${event.details.oldName} to ${event.details.newName}`)
        break
    }
  }

  function emitActivity(fileId: string) {
    socket.value?.emit('file:activity', { fileId })
  }

  function emitEvent(event: Omit<CollaborationEvent, 'timestamp'>) {
    const fullEvent = { ...event, timestamp: new Date() }
    socket.value?.emit('collaboration:event', fullEvent)
    events.value.push(fullEvent)
  }

  function disconnect() {
    socket.value?.disconnect()
    socket.value = null
    connectedUsers.value.clear()
    activeUsers.value.clear()
  }

  return {
    connectedUsers,
    activeUsers,
    events,
    connect,
    disconnect,
    emitActivity,
    emitEvent
  }
})