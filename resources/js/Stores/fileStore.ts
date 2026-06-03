import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'
import JSZip from 'jszip'
import { saveAs } from 'file-saver'
import { useToast } from 'vue-toastification'
import axios from 'axios'
import { trans } from 'laravel-vue-i18n';


export interface Breadcrumb {
  id: string
  name: string
}

export interface FilePermission {
  userId: string
  access: 'read' | 'write' | 'admin'
}

export interface FileVersion {
  id: string
  content: ArrayBuffer
  revision_code?: string
  createdAt: Date
  createdBy: string
  comment?: string
  change_reason?: string
}

export interface File {
  id: string
  name: string
  document_number?: string | null
  type: 'file' | 'folder'
  document_type?: string | null
  category?: string | null
  revision_code?: string | null
  size?: number
  modifiedAt: Date
  parentId: string | null
  content?: ArrayBuffer
  mimeType?: string
  status?: 'draft' | 'in_review' | 'approved' | 'effective' | 'obsolete' | 'archived'
  confidentiality_level?: 'public' | 'internal' | 'confidential' | 'restricted'
  is_controlled?: boolean
  requires_periodic_review?: boolean
  retention_period_days?: number | null
  effective_at?: string | null
  review_due_at?: string | null
  approved_at?: string | null
  obsolete_at?: string | null
  owner_id?: number | null
  approved_by?: number | null
  change_reason?: string | null
  meta?: Record<string, unknown> | null
  current_access_level?: 'read' | 'write' | 'admin' | null
  permissions: FilePermission[]
  createdBy: string
  archived: boolean
  archivedAt?: Date
  sharedWith: string[]
  versions: FileVersion[]
  currentVersionId: string | null
  tags: string[]
}

export const useFileStore = defineStore('files', () => {
  const toast = useToast()
  const files = ref<File[]>([])
  const currentFolder = ref<string | null>(localStorage.getItem('currentFolder'))
  const selectedItems = ref<Set<string>>(new Set())
  const showOverrideDialog = ref(false)
  const pendingFile = ref<{ file: File; action: 'upload' | 'move' } | null>(null)
  const isLoading = ref(false)
  const searchQuery = ref('')
  const filesLoaded = ref(false)
  const breadcrumbs = ref<Breadcrumb[]>([])

  function translatedMessage(key: string, fallback: string): string {
    const message = trans(key)

    if (!message || message === key) {
      return fallback
    }

    return message
  }

  function reportDevError(message: string, error: unknown): void {
    if (import.meta.env.DEV) {
      console.error(message, error)
    }
  }

  function unwrapPayload<T>(payload: { data?: T } | T): T {
    if (payload && typeof payload === 'object' && 'data' in payload) {
      return (payload as { data: T }).data
    }

    return payload as T
  }

  function unwrapCollection<T>(payload: { data?: T[] } | T[]): T[] {
    const unwrapped = unwrapPayload(payload)

    return Array.isArray(unwrapped) ? unwrapped : []
  }

  function normalizeTags(tags: unknown): string[] {
    if (!Array.isArray(tags)) {
      return []
    }

    return tags
      .map((tag) => {
        if (typeof tag === 'string') {
          return tag
        }

        if (tag && typeof tag === 'object' && 'name' in tag) {
          return String(tag.name)
        }

        return null
      })
      .filter((tag): tag is string => Boolean(tag))
  }

  function normalizeVersions(versions: any[] = []): FileVersion[] {
    return versions.map((version) => ({
      ...version,
      content: version.content,
      createdAt: new Date(version.created_at ?? version.createdAt ?? Date.now()),
      createdBy: version.creator?.name ?? version.createdBy ?? 'System',
    }))
  }

  function mapFileRecord(file: any): File {
    const payload = unwrapPayload(file)

    return {
      ...payload,
      modifiedAt: new Date(payload.modified_at ?? payload.modifiedAt ?? Date.now()),
      archivedAt: parseDate(payload.archived_at ?? payload.archivedAt ?? null),
      parentId: payload.parent_id ?? payload.parentId ?? null,
      mimeType: payload.mime_type ?? payload.mimeType,
      currentVersionId: payload.current_version_id ?? payload.currentVersionId ?? null,
      permissions: payload.permissions ?? [],
      sharedWith: payload.shares?.map((share: any) => share.shared_with_user?.name ?? share.shared_with) ?? payload.sharedWith ?? [],
      versions: normalizeVersions(payload.versions ?? []),
      tags: normalizeTags(payload.tags),
      createdBy: payload.creator?.name ?? payload.createdBy ?? '',
      archived: Boolean(payload.archived),
    }
  }

  function replaceFileRecord(file: File): void {
    files.value = [...files.value.filter((currentFile) => currentFile.id !== file.id), file]
  }

  function replaceFileCollection(collection: any): void {
    files.value = unwrapCollection(collection).map((file) => mapFileRecord(file))
  }

  const currentFiles = computed(() => {
    let filteredFiles = files.value.filter(file => !file.archived)

    // Apply search filter if there's a query
    if (searchQuery.value.trim()) {
      const query = searchQuery.value.toLowerCase()
      return filteredFiles.filter(file => 
        file.name.toLowerCase().includes(query) ||
        file.tags?.some(tag => tag.toLowerCase().includes(query))
      )
    }

    // Otherwise show files in current folder
    return filteredFiles
      .filter(file => file.parentId === currentFolder.value)
      .sort((a, b) => {
        if (a.type === 'folder' && b.type !== 'folder') return -1
        if (a.type !== 'folder' && b.type === 'folder') return 1
        return a.name.localeCompare(b.name)
      })
  })

  async function loadBreadcrumbs(folderId: string | null) {
    if (!folderId) {
      breadcrumbs.value = []
      return
    }

    try {
      const response = await axios.get(`/api/files/breadcrumbs/${folderId}`)
      breadcrumbs.value = response.data
    } catch (error) {
      reportDevError('Error loading breadcrumbs:', error)
      toast.error(translatedMessage(
        'gestlab.general.labels.vap_filemanager.notifications.error_loading_breadcrumbs',
        'Não foi possível carregar o percurso da pasta.'
      ))
    }
  }

  async function loadFiles() {
    try {
      isLoading.value = true
      const response = await axios.get('/api/files')
      const loadedFiles = unwrapCollection(response.data)

      if (loadedFiles.length) {
        files.value = loadedFiles.map((file) => mapFileRecord(file))
        filesLoaded.value = true

      // Validate current folder exists
      if (currentFolder.value) {
        const folderExists = files.value.some(f => f.id === currentFolder.value)
        if (!folderExists) {
          currentFolder.value = null
          localStorage.removeItem('currentFolder')
          breadcrumbs.value = []
        } else {
          // await loadBreadcrumbs(currentFolder.value)
          await loadBreadcrumbs(currentFolder.value)
        }
      }

      }
    } catch (error) {
      reportDevError('Error loading files:', error)
      toast.error(translatedMessage(
        'gestlab.general.labels.vap_filemanager.notifications.error_loading_files',
        'Não foi possível carregar os ficheiros.'
      ))
    } finally {
      isLoading.value = false
    }
  }

  // Function to handle initial load, separate from loadFiles
async function initializeStore() {
    isLoading.value = true;
    
    // We already initialized currentFolder.value from localStorage, 
    // so we simply use the value to navigate.
    const storedId = currentFolder.value;

    // Use the navigation action directly to set the view, breadcrumbs, and files.
    // This is the single source of truth for loading a folder view.
    await navigateToFolder(storedId); 

    isLoading.value = false;
}

  async function searchFiles(query: string) {
    searchQuery.value = query

    if (!query.trim()) {
      // If query is empty, reload all files
      await loadFiles()
      return
    }

    try {
      isLoading.value = true
      const response = await axios.get('/api/files/search', {
        params: { query }
      })

      // Update the files with search results
      const searchResults = unwrapCollection(response.data).map((file) => mapFileRecord(file))

      // Merge search results with existing files
      const updatedFiles = new Map(files.value.map(f => [f.id, f]))
      searchResults.forEach((result: File) => {
        updatedFiles.set(result.id, result)
      })

      files.value = Array.from(updatedFiles.values())
    } catch (error) {
      reportDevError('Error searching files:', error)
      toast.error(translatedMessage(
        'gestlab.general.labels.vap_filemanager.notifications.error_searching_files',
        'Não foi possível pesquisar os ficheiros.'
      ))
    } finally {
      isLoading.value = false
    }
  }

  function parseDate(dateString: string | null): Date | undefined {
    if (!dateString) return undefined
    const date = new Date(dateString)
    return isNaN(date.getTime()) ? undefined : date
  }

  async function fetchFiles() {
    try {
      isLoading.value = true
      const response = await axios.get('/api/files')
      replaceFileCollection(response.data)

      if (currentFolder.value) {
        loadBreadcrumbs(currentFolder.value)
      }

    } catch (error) {
      reportDevError('Error fetching files:', error)
      toast.error(translatedMessage(
        'gestlab.general.labels.vap_filemanager.notifications.error_fetching_files',
        'Não foi possível actualizar a lista de ficheiros.'
      ))
    } finally {
      isLoading.value = false
    }
  }

  async function initialFileLoad() {
    try {
        const response = await axios.get('/api/files') // Fetching all files or base data
        replaceFileCollection(response.data)
        filesLoaded.value = true;
    } catch (error) {
        reportDevError('Initial data fetch failed:', error)
    }
}

  async function uploadFiles(uploadedFiles: FileList) {
    for (const file of uploadedFiles) {
      const formData = new FormData()
      formData.append('file', file)
      formData.append('parent_id', currentFolder.value || '')
      await uploadSingleFile(formData)
    }
  }

  async function uploadSingleFile(formData: FormData) {
    try {
      const response = await axios.post('/api/files/upload', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })

      const newFile = mapFileRecord(response.data)
      replaceFileRecord(newFile)

      const uploadedFile = formData.get('file')
      const fileName = uploadedFile instanceof File ? uploadedFile.name : String(uploadedFile)
      // toast.success(`File "${fileName}" uploaded successfully`)
      toast.success(trans('gestlab.general.labels.vap_filemanager.notifications.file_uploaded') + ' - ' + fileName)
    } catch (error: any) {
      if (error.response?.status === 409) {
        const uploadedFile = formData.get('file') as File
        const parentId = formData.get('parent_id') as string
        pendingFile.value = { 
          file: {
            name: uploadedFile.name,
            type: 'file',
            content: await uploadedFile.arrayBuffer(),
            mimeType: uploadedFile.type,
            size: uploadedFile.size,
            parentId: parentId || null,
          } as File,
          action: 'upload'
        }
        showOverrideDialog.value = true
        return
      }
      const uploadedFile = formData.get('file')
      const fileName = uploadedFile instanceof File ? uploadedFile.name : String(uploadedFile)
      // toast.error(`Failed to upload "${fileName}"`)
      toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_uploading_file') + ' - ' + fileName)
      reportDevError('Error uploading file:', error)
      throw error
    }
  }

async function uploadFolder(folderHandle: FileSystemDirectoryHandle) {
    try {
      isLoading.value = true

      const queue: { handle: FileSystemHandle; parentId: string | null }[] = [
        { handle: folderHandle, parentId: currentFolder.value }
      ]

      while (queue.length > 0) {
        const { handle, parentId } = queue.shift()!

        if (handle.kind === 'directory') {
          const dirHandle = handle as FileSystemDirectoryHandle
          
          const formData = new FormData()
          formData.append('name', dirHandle.name)
          formData.append('parent_id', parentId || '')

        const response = await axios.post('/api/files/upload-folder', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })

          const newFolder = mapFileRecord(response.data)
          replaceFileRecord(newFolder)

          for await (const entry of dirHandle.values()) {
            queue.push({ handle: entry, parentId: newFolder.id })
          }
        } else if (handle.kind === 'file') {
          const fileHandle = handle as FileSystemFileHandle
          const file = await fileHandle.getFile()
          const formData = new FormData()
          formData.append('file', file)
          formData.append('parent_id', parentId || '')

          await uploadSingleFile(formData)
        }
      }

      // toast.success(`Folder "${folderHandle.name}" uploaded successfully`)
      toast.success(trans('gestlab.general.labels.vap_filemanager.notifications.folder_uploaded') + ' - ' + folderHandle.name)
    } catch (error) {
      toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_uploading_folder'))
      reportDevError('Error uploading folder:', error)
    } finally {
      isLoading.value = false
    }
  }

async function confirmOverride() {
    if (!pendingFile.value) return

    try {
      if (pendingFile.value.action === 'upload') {
        const formData = new FormData()
        formData.append('file', new Blob([pendingFile.value.file.content!], { 
          type: pendingFile.value.file.mimeType 
        }), pendingFile.value.file.name)
        formData.append('parent_id', pendingFile.value.file.parentId || '')
        formData.append('override', '1')

        const response = await axios.post('/api/files/upload', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })

        const newFile = mapFileRecord(response.data)
        replaceFileRecord(newFile)

        // toast.success('File overridden successfully')
        toast.success(trans('gestlab.general.labels.vap_filemanager.notifications.file_override'))
      }
    } catch (error) {
      toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_uploading_file'))
      reportDevError('Error overriding file:', error)
    } finally {
      showOverrideDialog.value = false
      pendingFile.value = null
    }
  }

  function cancelOverride() {
    showOverrideDialog.value = false
    pendingFile.value = null
  }

  async function renameItem(id: string, newName: string) {
    try {
      const response = await axios.put(`/api/files/${id}/rename`, { name: newName })
      const renamedFile = mapFileRecord(response.data)
      replaceFileRecord(renamedFile)
      // toast.success('Item renamed successfully')
      toast.success(trans('gestlab.general.labels.vap_filemanager.notifications.item_renamed') + ' - ' + newName)
    } catch (error: any) {
      if (error.response?.status === 409) {
        // toast.error('An item with this name already exists')
        toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_item_already_exists'))
      } else {
        toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_renaming_item'))
      }
      reportDevError('Error renaming item:', error)
    }
  }

  async function moveItem(id: string, newParentId: string | null) {
    try {
      const response = await axios.put(`/api/files/${id}/move`, { parent_id: newParentId })
      const movedFile = mapFileRecord(response.data)
      replaceFileRecord(movedFile)
      // toast.success('Item moved successfully')
      toast.success(trans('gestlab.general.labels.vap_filemanager.notifications.item_moved'))
    } catch (error: any) {
      if (error.response?.status === 409) {
        // toast.error('An item with this name already exists in the destination folder')
        toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_item_already_exists'))
      } else {
        // toast.error('Failed to move item')
        toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_moving_item'))
      }
      reportDevError('Error moving item:', error)
    }
  }

  async function archiveItem(id: string) {
    try {
      await axios.post(`/api/files/${id}/archive`)
      const file = files.value.find(f => f.id === id)
      if (file) {
        file.archived = true
        file.archivedAt = new Date()
      }
      // toast.success('Item archived successfully')
      toast.success(trans('gestlab.general.labels.vap_filemanager.notifications.item_archived'))
    } catch (error) {
      // toast.error('Failed to archive item')
      toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_archiving_item'))
      reportDevError('Error archiving item:', error)
    }
  }

  async function restoreArchivedItem(id: string) {
    try {
      await axios.post(`/api/files/${id}/restore`)
      const file = files.value.find(f => f.id === id)
      if (file) {
        file.archived = false
        file.archivedAt = undefined
      }
      // toast.success('Item restored successfully')
      toast.success(trans('gestlab.general.labels.vap_filemanager.notifications.item_unarchived'))
    } catch (error) {
      // toast.error('Failed to restore item')
      toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_restoring_item'))
      reportDevError('Error restoring item:', error)
    }
  }

  async function permanentlyDeleteItem(id: string) {
    try {
      await axios.delete(`/api/files/${id}`)
      files.value = files.value.filter(f => f.id !== id)
      // toast.success('Item deleted permanently')
      toast.success(trans('gestlab.general.labels.vap_filemanager.notifications.item_deleted'))
    } catch (error) {
      // toast.error('Failed to delete item')
      toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_deleting_item'))
      reportDevError('Error deleting item:', error)
    }
  }

  async function shareItem(id: string, userId: string, accessLevel: FilePermission['access']) {
    try {
      const response = await axios.post(`/api/files/${id}/share`, {
        user_id: userId,
        access_level: accessLevel
      })
      replaceFileRecord(mapFileRecord(response.data))
      // toast.success('Item shared successfully')
      toast.success(trans('gestlab.general.labels.vap_filemanager.notifications.item_shared'))
    } catch (error) {
      // toast.error('Failed to share item')
      toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_sharing_item'))
      reportDevError('Error sharing item:', error)
    }
  }

  async function restoreVersion(fileId: string, versionId: string) {
    try {
      const response = await axios.post(`/api/files/${fileId}/versions/${versionId}/restore`)
      replaceFileRecord(mapFileRecord(response.data))
      // toast.success('Version restored successfully')
      toast.success(trans('gestlab.general.labels.vap_filemanager.notifications.version_restored'))
    } catch (error) {
      // toast.error('Failed to restore version')
      toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_restoring_version'))
      reportDevError('Error restoring version:', error)
    }
  }

  async function createFolder(name: string, parentId: string | null = null) {
    try {
      const formData = new FormData()
      formData.append('name', name)
      formData.append('parent_id', parentId || currentFolder.value || '')

      const response = await axios.post('/api/files/upload-folder', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })

      const newFolder = mapFileRecord(response.data)
      replaceFileRecord(newFolder)

      return newFolder
    } catch (error: any) {
      if (error.response?.status !== 409) {
        // toast.error('Failed to create folder')
        toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_creating_folder'))
        reportDevError('Error creating folder:', error)
      }
      throw error
    }
  }
  
  async function downloadFile(id: string) {
    try {
      const file = files.value.find(f => f.id === id)
      if (!file || file.type !== 'file') {
        throw new Error('Invalid file')
      }

      const response = await axios.get(`/api/files/${id}/download`, {
        responseType: 'blob'
      })

      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', file.name)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)

      // toast.success(`File "${file.name}" downloaded successfully`)
      toast.success(trans('gestlab.general.labels.vap_filemanager.notifications.file_downloaded') + ' - ' + file.name)
    } catch (error) {
      // toast.error('Failed to download file')
      toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_downloading_file'))
      reportDevError('Error downloading file:', error)
    }
  }

  async function updateFileTags(fileId: string, tags: string[]) {
    try {
      const response = await axios.put(`/api/files/${fileId}/tags`, { tags })
      const file = files.value.find((currentFile) => currentFile.id === fileId)
      if (file) {
        replaceFileRecord({
          ...file,
          tags: normalizeTags(response.data.tags),
        })
      }
      // toast.success('Tags updated successfully')
      toast.success(trans('gestlab.general.labels.vap_filemanager.notifications.tags_updated'))
    } catch (error) {
      // toast.error('Failed to update tags')
      toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_updating_tags'))
      reportDevError('Error updating tags:', error)
    }
  }

  async function moveFile(fileId: string, newParentId: string | null) {
    try {
      // Find the file being moved
      const file = files.value.find(f => f.id === fileId)
      if (!file) {
        // throw new Error('File not found')
        throw new Error(trans('gestlab.general.labels.vap_filemanager.notifications.error_file_not_found'))
      }

      // If moving to a folder, verify the folder exists
      if (newParentId) {
        const targetFolder = files.value.find(f => f.id === newParentId)
        if (!targetFolder || targetFolder.type !== 'folder') {
          // throw new Error('Target folder not found')
          throw new Error(trans('gestlab.general.labels.vap_filemanager.notifications.error_folder_not_found'))
        }
      }

      // Send the request
      const response = await axios.put(`/api/files/${fileId}/move`, {
        parent_id: newParentId || '' // Convert null to empty string for root
      })

      const movedFile = mapFileRecord(response.data)
      replaceFileRecord(movedFile)

      // toast.success(`${file.name} moved successfully`)
      toast.success(trans('gestlab.general.labels.vap_filemanager.notifications.file_moved') + ' - ' + file.name)
    } catch (error: any) {
      if (error.response?.status === 409) {
        // toast.error('A file with the same name already exists in the destination folder')
        toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_file_already_exists'))
      } else {
        // toast.error('Failed to move file')
        toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_moving_file'))
        reportDevError('Error moving file:', error)
      }
      throw error
    }
  }

// async function navigateToFolder(folderId: string | null) {
//   currentFolder.value = folderId
//   searchQuery.value = ''
  
//   // Load breadcrumbs immediately
//   if (folderId) {
//     await loadBreadcrumbs(folderId)
//   } else {
//     breadcrumbs.value = []
//   }
  
//   // Then fetch files
//   await fetchFiles()
// }

async function navigateToFolder(folderId: string | null) {
    currentFolder.value = folderId
    searchQuery.value = ''

    if (folderId) {
        localStorage.setItem('currentFolder', folderId) // Save the ID
        await loadBreadcrumbs(folderId)
    } else {
        localStorage.removeItem('currentFolder') // Remove on root
        breadcrumbs.value = []
    }
    
    await fetchFiles() // Fetch files for the new folder (currentFolder.value)
}

async function initialize() {
    isLoading.value = true;

    // --- Phase 1: Restore State from LocalStorage ---
    // currentFolder.value is already set from localStorage at the top of the file.
    const storedId = currentFolder.value; 

    // --- Phase 2: Load Core Data & Validate (Optional but Safer) ---
    // If your front-end requires ALL files (for searching, moving, etc.) before navigation:
    // await initialFileLoad(); // Uncomment if you rely on 'files.value' holding everything.
    
    // --- Phase 3: Navigate to Stored Folder ---
    // This action handles everything:
    // a) Sets currentFolder.value (even if it was already set, it's safe)
    // b) Loads breadcrumbs
    // c) Calls fetchFiles() to load the correct directory contents
    await navigateToFolder(storedId || null); 

    isLoading.value = false;
}


   // Initialize by loading files
  //  loadFiles()
  // initializeStore()
  initialize()

  return {
    files,
    currentFiles,
    currentFolder,
    selectedItems,
    showOverrideDialog,
    isLoading,
    filesLoaded,
    uploadFiles,
    uploadSingleFile,
    uploadFolder,
    confirmOverride,
    cancelOverride,
    renameItem,
    moveItem,
    archiveItem,
    restoreArchivedItem,
    permanentlyDeleteItem,
    shareItem,
    restoreVersion,
    updateFileTags,
    fetchFiles,
    loadFiles,
    initializeStore,
    createFolder,
    downloadFile,
    moveFile,
    breadcrumbs,
    searchQuery,
    searchFiles,
    pendingFile,
    navigateToFolder,
    loadBreadcrumbs
  }
})
