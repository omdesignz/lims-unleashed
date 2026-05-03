<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue'
import { 
  FolderIcon, 
  DocumentIcon, 
  TrashIcon,
  PencilIcon,
  ArchiveBoxIcon,
  ShareIcon,
  ArrowDownTrayIcon,
  EyeIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
  ChevronUpIcon,
  ChevronDownIcon,
  ClockIcon,
  XMarkIcon,
  ExclamationTriangleIcon,
  TagIcon,
  CloudArrowUpIcon,
  FolderPlusIcon,
  ArrowsRightLeftIcon
} from '@heroicons/vue/24/outline'
import FilePreview from './file-preview.vue'
import FileVersionHistory from './file-version-history.vue'
import TagManager from './tag-manager.vue'
import { useFileStore } from '../../Stores/fileStore'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import { useDebounceFn } from '@vueuse/core'
import Breadcrumbs from './breadcrumbs.vue'
import { trans } from 'laravel-vue-i18n';
import combobox from '@/Components/combobox.vue';


const toast = useToast()
const fileStore = useFileStore()
const fileInput = ref<HTMLInputElement | null>(null)
const folderInput = ref<HTMLInputElement | null>(null)
const showRenameDialog = ref(false)
const showMoveDialog = ref(false)
const showShareDialog = ref(false)
const showPreviewDialog = ref(false)
const showFilterDialog = ref(false)
const showVersionHistoryDialog = ref(false)
const showDeleteDialog = ref(false)
const showTagDialog = ref(false)
const tagFileId = ref<string | null>(null)
const versionHistoryFileId = ref('')
const previewFile = ref<{
  name: string
  content: ArrayBuffer
  mimeType?: string
} | null>(null)
const renamingItemId = ref('')
const newItemName = ref('')
const sharingItemId = ref('')
const movingItemId = ref('')
const movingToFolderId = ref('')
const shareEmail = ref('')
const shareAccess = ref<'read' | 'write' | 'admin'>('read')

// Dragging State
const isUploading = ref(false)
const uploadProgress = ref<{ [key: string]: number }>({})
const draggedItem = ref<string | null>(null)
const dragOverItem = ref<string | null>(null)
const isDraggingFiles = ref(false)
const isDraggingExternal = ref(false)

// Debounced search function
const debouncedSearch = useDebounceFn((query: string) => {
  fileStore.searchFiles(query)
}, 300)

const isDragging = ref(false)
const clipboard = ref<{ action: 'copy' | 'cut'; items: string[] } | null>(null)
const itemToDelete = ref<{ id: string; name: string; type: 'file' | 'folder' } | null>(null)

const isSelecting = ref(false)
const lastSelectedId = ref<string | null>(null)

// Search and filter state
const searchQuery = ref('')
const sortField = ref<'name' | 'size' | 'modifiedAt'>('name')
const sortDirection = ref<'asc' | 'desc'>('asc')
const filterType = ref<string[]>([])
const filterDateRange = ref<'7days' | '30days' | 'custom' | null>(null)
const filterSize = ref<'small' | 'medium' | 'large' | null>(null)

const showCreateFolderDialog = ref(false)
const newFolderName = ref('')

const filteredFiles = computed(() => {
  let files = [...fileStore.currentFiles]

  // Apply search
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    files = files.filter(file => file.name.toLowerCase().includes(query))
  }

  // Apply type filter
  if (filterType.value.length > 0) {
    files = files.filter(file => filterType.value.includes(file.type))
  }

  // Apply date filter
  if (filterDateRange.value) {
    const now = new Date()
    const days = filterDateRange.value === '7days' ? 7 : 30
    const cutoff = new Date(now.setDate(now.getDate() - days))
    files = files.filter(file => file.modifiedAt >= cutoff)
  }

  // Apply size filter
  if (filterSize.value) {
    const sizes = {
      small: 1024 * 1024, // 1MB
      medium: 10 * 1024 * 1024, // 10MB
      large: 100 * 1024 * 1024 // 100MB
    }
    files = files.filter(file => {
      if (!file.size) return false
      switch (filterSize.value) {
        case 'small': return file.size < sizes.small
        case 'medium': return file.size >= sizes.small && file.size < sizes.medium
        case 'large': return file.size >= sizes.medium
        default: return true
      }
    })
  }

  // Apply sorting
  files.sort((a, b) => {
    if (a.type === 'folder' && b.type !== 'folder') return -1
    if (a.type !== 'folder' && b.type === 'folder') return 1

    let comparison = 0
    switch (sortField.value) {
      case 'name':
        comparison = a.name.localeCompare(b.name)
        break
      case 'size':
        comparison = (a.size || 0) - (b.size || 0)
        break
      case 'modifiedAt':
        comparison = a.modifiedAt.getTime() - b.modifiedAt.getTime()
        break
    }
    return sortDirection.value === 'asc' ? comparison : -comparison
  })

  return files
})

// Handle file/folder dragging Working
// function handleDragEnter(event: DragEvent) {
//   event.preventDefault()
//   isDraggingFiles.value = true
//   // Check if dragging from outside the app
//   isDraggingExternal.value = !draggedItem.value
// }

function handleDragEnter(event: DragEvent) {
  event.preventDefault()
  if (!draggedItem.value && event.dataTransfer?.types.includes('Files')) {
    isDraggingExternal.value = true
  }
}

function handleDragLeave(event: DragEvent) {
  event.preventDefault()
  if (!event.relatedTarget || !(event.relatedTarget as Element).closest('.file-list-container')) {
    isDraggingExternal.value = false
    dragOverItem.value = null
  }
}

// Working Drag Leave
// function handleDragLeave(event: DragEvent) {
//   event.preventDefault()
//   const target = event.relatedTarget as Node | null
//   if (!target || !event.currentTarget?.contains(target)) {
//     isDraggingFiles.value = false
//     isDraggingExternal.value = false
//   }
//   dragOverItem.value = null
// }

async function handleFileDrop(event: DragEvent, targetFolderId: string | null = null) {
  if (!event.dataTransfer?.items) return

  try {
    isUploading.value = true
    const items = Array.from(event.dataTransfer.items)
    
    for (const item of items) {
      const entry = item.webkitGetAsEntry && item.webkitGetAsEntry()
      
      if (!entry) {
        // Fallback for browsers that don't support webkitGetAsEntry
        const file = item.getAsFile()
        if (file) {
          const formData = new FormData()
          formData.append('file', file)
          formData.append('parent_id', targetFolderId || fileStore.currentFolder || '')
          
          try {
            await fileStore.uploadSingleFile(formData)
          } catch (error) {
            console.error('Error uploading file:', error)
            // toast.error(`Failed to upload: ${file.name}`)
            toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_uploading_file') + ' - ' + file.name)
          }
        }
        continue
      }

      if (entry.isDirectory) {
        await processDirectoryEntry(entry, targetFolderId)
      } else if (entry.isFile) {
        await processFileEntry(entry, targetFolderId)
      }
    }
  } catch (error) {
    console.error('Error handling drop:', error)
    // toast.error('Failed to process dropped items')
    toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_failed_to_process_dropped_items'))
  } finally {
    isUploading.value = false
  }
}

async function processDirectoryEntry(entry: any, parentId: string | null = null) {
  try {
    // Create the folder
    const folder = await fileStore.createFolder(entry.name, parentId)
    
    // Read directory contents
    const dirReader = entry.createReader()
    const entries: any[] = await new Promise((resolve, reject) => {
      dirReader.readEntries(resolve, reject)
    })

    // Process all entries
    for (const childEntry of entries) {
      if (childEntry.isDirectory) {
        await processDirectoryEntry(childEntry, folder.id)
      } else {
        await processFileEntry(childEntry, folder.id)
      }
    }
  } catch (error) {
    console.error(`Error processing directory ${entry.name}:`, error)
    // toast.error(`Failed to process directory: ${entry.name}`)
    toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_failed_to_process_folder') + ' - ' + entry.name)
  }
}

async function processFileEntry(entry: any, parentId: string | null = null) {
  try {
    const file: File = await new Promise((resolve, reject) => {
      entry.file(resolve, reject)
    })

    const formData = new FormData()
    formData.append('file', file)
    formData.append('parent_id', parentId || fileStore.currentFolder || '')

    await fileStore.uploadSingleFile(formData)
  } catch (error) {
    console.error(`Error processing file ${entry.name}:`, error)
    // toast.error(`Failed to upload: ${entry.name}`)
    toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_uploading_file') + ' - ' + entry.name)
  } finally {
    delete uploadProgress.value[entry.fullPath]
  }
}


// File/folder movement dragging
function handleDragStart(event: DragEvent, fileId: string) {
  if (!event.dataTransfer) return
  draggedItem.value = fileId
  event.dataTransfer.effectAllowed = 'move'
  
  // Add drag image
  const draggedFile = fileStore.files.find(f => f.id === fileId)
  if (draggedFile) {
    const dragImage = document.createElement('div')
    dragImage.className = 'fixed top-0 left-0 bg-white shadow-lg rounded-lg p-2 pointer-events-none'
    dragImage.innerHTML = `
      <div class="flex items-center">
        <span class="mr-2">${draggedFile.type === 'folder' ? '📁' : '📄'}</span>
        <span>${draggedFile.name}</span>
      </div>
    `
    document.body.appendChild(dragImage)
    event.dataTransfer.setDragImage(dragImage, 0, 0)
    setTimeout(() => document.body.removeChild(dragImage), 0)
  }
}


function handleDragOver(event: DragEvent, fileId: string | null = null) {
  event.preventDefault()
  
  // Check if this is an external drag (files from outside)
  if (!draggedItem.value && event.dataTransfer?.types.includes('Files')) {
    isDraggingExternal.value = true
  }
  
  dragOverItem.value = fileId
  event.dataTransfer!.dropEffect = isDraggingExternal.value ? 'copy' : 'move'
}

async function handleDrop(event: DragEvent, targetId: string | null = null) {
  event.preventDefault()
  isDraggingFiles.value = false
  isDraggingExternal.value = false
  
  // Handle file/folder uploads if items are dragged from outside
  if (event.dataTransfer?.items && !draggedItem.value) {
    await handleFileDrop(event)
    return
  }
  
  // Handle internal moves
  if (!draggedItem.value) return

  try {
    // Don't move if dropping onto itself
    if (draggedItem.value === targetId) return

    // Get the target file if dropping onto a specific item
    const targetFile = targetId ? fileStore.files.find(f => f.id === targetId) : null
    
    // Only allow dropping into folders or root
    if (targetId && (!targetFile || targetFile.type !== 'folder')) {
    //   toast.error('Files can only be moved to folders')
      toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_files_can_only_be_moved_to_folders'))
      return
    }

    // Check if the dragged item exists
    const draggedFile = fileStore.files.find(f => f.id === draggedItem.value)
    if (!draggedFile) {
    //   toast.error('Source file not found')
      toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_source_file_not_found'))
      return
    }

    // If moving a folder, check for circular references
    if (draggedFile.type === 'folder' && targetId) {
      let current = targetFile
      let hasCircular = false
      const visited = new Set<string>()

      while (current && !hasCircular) {
        if (visited.has(current.id)) {
          hasCircular = true
          break
        }
        visited.add(current.id)
        
        if (current.id === draggedFile.id) {
          hasCircular = true
          break
        }
        
        if (!current.parentId) break
        current = fileStore.files.find(f => f.id === current?.parentId)
      }

      if (hasCircular) {
        // toast.error('Cannot move a folder into its own subfolder')
        toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_cannot_move_folder_into_its_own_subfolder'))
        return
      }
    }

    // If dropping in the file list area (not on a folder), use currentFolder as target
    const finalTargetId = targetId || fileStore.currentFolder

    await fileStore.moveFile(draggedItem.value, finalTargetId)
    fileStore.fetchFiles();
  } catch (error) {
    console.error('Error moving file:', error)
    // toast.error('Failed to move item')
    toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_move_item'))
  } finally {
    draggedItem.value = null
    dragOverItem.value = null
  }
}

async function transferFile(fileId, targetId) {
  await fileStore.moveFile(fileId, targetId)
  fileStore.fetchFiles();
}

// Compute total upload progress
const totalProgress = computed(() => {
  const progressValues = Object.values(uploadProgress.value)
  if (progressValues.length === 0) return 0
  return Math.round(
    progressValues.reduce((sum, value) => sum + value, 0) / progressValues.length
  )
})


function showTagManager(id: string) {
  tagFileId.value = id
  showTagDialog.value = true
}

function canPreview(file: { type: string; mimeType?: string }) {
  if (file?.type !== 'file' || !file?.mimeType) return false

  const supportedTypes = [
    'image/',
    'application/pdf',
    'text/',
    'application/vnd.openxmlformats-officedocument.',
    'application/msword',
    'application/vnd.ms-',
    'audio/',
    'video/'
  ]

  return supportedTypes.some(type => file.mimeType?.startsWith(type))
}

const breadcrumb = computed(() => {
  const path: { id: string | null; name: string }[] = [{ id: null, name: 'Root' }]
  let current = fileStore.files.find(f => f.id === fileStore.currentFolder)
  
  while (current) {
    path.unshift({ id: current.id, name: current.name })
    current = fileStore.files.find(f => f.id === current?.parentId)
  }
  
  return path
})

async function previewItem(id: string) {
  const file = fileStore.files.find(f => f.id === id)
  if (!file || !canPreview(file)) return

  try {
    const response = await axios.get(`/api/files/${id}/download`, {
      responseType: 'arraybuffer'
    })

    previewFile.value = {
      name: file.name,
      content: response.data,
      mimeType: file.mimeType
    }
    showPreviewDialog.value = true
  } catch (error) {
    // toast.error('Failed to load file preview')
    toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_failed_to_load_preview'))
    console.error('Error loading preview:', error)
  }
}

function handleItemClick(file: any, event: MouseEvent) {
  if (file.type === 'folder') {
    fileStore.currentFolder = file.id
    fileStore.fetchFiles()
    fileStore.selectedItems.clear()
  } else {
    handleSelection(file.id, event)
  }
}

function handleSelection(id: string, event: MouseEvent) {
  if (event.ctrlKey || event.metaKey) {
    if (fileStore.selectedItems.has(id)) {
      fileStore.selectedItems.delete(id)
    } else {
      fileStore.selectedItems.add(id)
    }
    lastSelectedId.value = id
  } else if (event.shiftKey && lastSelectedId.value) {
    const files = filteredFiles.value
    const lastIndex = files.findIndex(f => f.id === lastSelectedId.value)
    const currentIndex = files.findIndex(f => f.id === id)
    const [start, end] = [Math.min(lastIndex, currentIndex), Math.max(lastIndex, currentIndex)]
    
    fileStore.selectedItems.clear()
    for (let i = start; i <= end; i++) {
      fileStore.selectedItems.add(files[i].id)
    }
  } else {
    fileStore.selectedItems.clear()
    fileStore.selectedItems.add(id)
    lastSelectedId.value = id
  }
}

function navigateToFolder(id: string | null) {
  fileStore.currentFolder = id
  fileStore.fetchFiles()
  fileStore.selectedItems.clear()
}

async function handleFileUpload(event: Event) {
  const input = event.target as HTMLInputElement
  if (input.files?.length) {
    await fileStore.uploadFiles(input.files)
    input.value = '' // Reset input
    fileStore.fetchFiles()
  }
}

async function handleFolderUpload(event: Event) {
  const input = event.target as HTMLInputElement;
  if (!input.files?.length) return;

  const files = Array.from(input.files);
  if (files.length === 0) return;

  try {
    const rootFolderName = files[0].webkitRelativePath.split('/')[0];
    let rootFolderId: string;

    try {
      const formData = new FormData();
      formData.append('name', rootFolderName);
      formData.append('parent_id', fileStore.currentFolder || '');

      const { data: rootFolder } = await axios.post('/api/files/upload-folder', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      console.log("root folder data: ", rootFolder?.data?.id);
      rootFolderId = rootFolder?.data?.id;
    } catch (error: any) {
      if (error.response?.status === 409) {
        rootFolderId = error.response.data.existing_folder?.id;
      } else {
        throw error;
      }
    }

    const folderIds = new Map<string, string>();
    folderIds.set(rootFolderName, rootFolderId);

    const filesByDir = new Map<string, File[]>();
    files.forEach((file) => {
      const pathParts = file.webkitRelativePath.split('/');
      const fileName = pathParts.pop();
      const dirPath = pathParts.join('/');

      if (!filesByDir.has(dirPath)) {
        filesByDir.set(dirPath, []);
      }
      filesByDir.get(dirPath)!.push(file);
    });

    for (const dirPath of filesByDir.keys()) {
      const pathParts = dirPath.split('/');
      if (pathParts.length === 1) continue;

      for (let i = 1; i < pathParts.length; i++) {
        const currentPath = pathParts.slice(0, i + 1).join('/');
        const parentPath = pathParts.slice(0, i).join('/');
        const folderName = pathParts[i];

        if (!folderIds.has(currentPath)) {
          const formData = new FormData();
          formData.append('name', folderName);
          formData.append('parent_id', folderIds.get(parentPath) || '');

          try {
            const { data: newFolder } = await axios.post('/api/files/upload-folder', formData, {
              headers: { 'Content-Type': 'multipart/form-data' },
            });
            folderIds.set(currentPath, newFolder?.data?.id);
          } catch (error: any) {
            if (error.response?.status === 409) {
              folderIds.set(currentPath, error.response.data.existing_folder?.id);
            } else {
              throw error;
            }
          }
        }
      }
    }

    // Correct file upload section.
    for (const [dirPath, dirFiles] of filesByDir) {
      const folderId = folderIds.get(dirPath);
      if (!folderId) {
        console.warn(`Folder ID not found for path: ${dirPath}`); // Add this line
        continue;
        }

        if(dirFiles.length === 0){
            console.warn(`dirFiles array is empty for path: ${dirPath}`);
            continue;
        }

      for (const file of dirFiles) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('parent_id', folderId);

        try {
          const { data: uploadedFile } = await axios.post('/api/files/upload', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
          });
          console.log(`File ${file.name} uploaded successfully:`, uploadedFile);
        } catch (error: any) {
          console.error(`Failed to upload file ${file.name}:`, error);
        //   toast.error(`Failed to upload ${file.name}`);
          toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_uploading_file') + ' - ' + file.name);
        }
      }
    }

    // toast.success('Folder uploaded successfully');
    toast.success(trans('gestlab.general.labels.vap_filemanager.notifications.folder_uploaded'));

    fileStore.fetchFiles();
  } catch (error) {
    console.error('Error uploading folder:', error);
    toast.error('Failed to upload folder');
  }

  input.value = '';
}

function triggerFileUpload() {
  fileInput.value?.click()
}

function triggerFolderUpload() {
  folderInput.value?.click()
}

// function handleDragOver(event: DragEvent) {
//   event.preventDefault()
//   isDragging.value = true
// }

// function handleDragLeave(event: DragEvent) {
//   event.preventDefault()
//   isDragging.value = false
// }

// function handleDrop(event: DragEvent) {
//   event.preventDefault()
//   isDragging.value = false
  
//   const items = event.dataTransfer?.items
//   if (!items) return

//   for (const item of items) {
//     if (item.kind === 'file') {
//       const entry = item.webkitGetAsEntry()
//       if (entry?.isFile) {
//         const file = item.getAsFile()
//         if (file) fileStore.uploadFiles([file])
//       } else if (entry?.isDirectory) {
//         entry.createReader().readEntries((entries) => {
//           // Process folder entries recursively
//           processEntries(entries)
//         })
//       }
//     }
//   }
// }

async function processEntries(entries: any[]) {
  for (const entry of entries) {
    if (entry.isFile) {
      entry.file((file: File) => {
        fileStore.uploadFiles([file])
      })
    } else if (entry.isDirectory) {
      fileStore.uploadFolder(entry.name)
      entry.createReader().readEntries((subEntries: any[]) => {
        processEntries(subEntries)
      })
    }
  }
}

function startRename(id: string) {
  const file = fileStore.files.find(f => f.id === id)
  if (file) {
    renamingItemId.value = id
    newItemName.value = file.name
    showRenameDialog.value = true
  }
}

function confirmRename() {
  if (renamingItemId.value && newItemName.value.trim()) {
    fileStore.renameItem(renamingItemId.value, newItemName.value.trim())
    showRenameDialog.value = false
    renamingItemId.value = ''
    newItemName.value = ''
  }
}

function startShare(id: string) {
  sharingItemId.value = id
  shareEmail.value = ''
  shareAccess.value = 'read'
  showShareDialog.value = true
}

function startMove(id: string) {
  movingItemId.value = id
  showMoveDialog.value = true
}

function confirmMove() {
  if (movingItemId.value && movingToFolderId.value.value) {

    console.log('Moving item ' + movingItemId.value + ' to folder ' + movingToFolderId.value?.value);
    fileStore.moveItem(movingItemId.value, movingToFolderId.value?.value)
    showMoveDialog.value = false
    movingItemId.value = ''
    movingToFolderId.value = ''
  }
}

function confirmShare() {
  if (sharingItemId.value && shareEmail.value.trim()) {
    fileStore.shareItem(sharingItemId.value, shareEmail.value.trim(), shareAccess.value)
    showShareDialog.value = false
    sharingItemId.value = ''
    shareEmail.value = ''
  }
}

function showVersionHistory(id: string) {
  versionHistoryFileId.value = id
  showVersionHistoryDialog.value = true
}

function startDelete(id: string) {
  const file = fileStore.files.find(f => f.id === id)
  if (file) {
    itemToDelete.value = {
      id: file.id,
      name: file.name,
      type: file.type
    }
    showDeleteDialog.value = true
  }
}

function confirmDelete() {
  if (itemToDelete.value) {
    fileStore.permanentlyDeleteItem(itemToDelete.value.id)
    showDeleteDialog.value = false
    itemToDelete.value = null
  }
}

function formatDate(date: Date | undefined | null) {
  if (!date || !(date instanceof Date) || isNaN(date.getTime())) {
    return ''
  }
  
  try {
    return new Intl.DateTimeFormat('en-US', {
      dateStyle: 'medium',
      timeStyle: 'short'
    }).format(date)
  } catch (error) {
    console.error('Error formatting date:', error)
    return ''
  }
}

function formatSize(size: number | undefined) {
  if (!size) return ''
  const units = ['B', 'KB', 'MB', 'GB']
  let value = size
  let unit = 0
  while (value >= 1024 && unit < units.length - 1) {
    value /= 1024
    unit++
  }
  return `${value.toFixed(1)} ${units[unit]}`
}

function startCreateFolder() {
  newFolderName.value = ''
  showCreateFolderDialog.value = true
}

async function confirmCreateFolder() {
  if (newFolderName.value.trim()) {
    await fileStore.createFolder(newFolderName.value.trim())
    showCreateFolderDialog.value = false
    newFolderName.value = ''
    fileStore.fetchFiles()
  }
}

function loadFolders(query, setOptions) {
    fetch('/api/files/folders/getFolder?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.name,
            };
        })
        );
    });
}

onMounted(() => {
  fileStore.fetchFiles()
})
</script>

<template>
  <!-- <div 
    class="bg-white rounded-lg shadow relative border-dashed border-2 border-gray-300 h-12"
    @dragenter="handleDragEnter"
    @dragover.prevent="handleDragOver($event)"
    @dragleave="handleDragLeave"
    @drop="handleDrop($event)"
  ></div> -->

  <div class="bg-white rounded-lg shadow relative file-list-container"
    @dragenter.prevent="handleDragEnter"
    @dragleave.prevent="handleDragLeave"
  >

  <!-- Breadcrumb navigation -->
  <!-- <div class="px-4 py-2 border-b flex items-center space-x-2 text-sm">
      <template v-for="(item, index) in breadcrumb" :key="index">
        <button
          class="hover:text-blue-600"
          @click="navigateToFolder(item.id)"
        >
          {{ item.name }}
        </button>
        <span v-if="index < breadcrumb.length - 1" class="text-gray-400">/</span>
      </template>
    </div> -->
    <!-- Breadcrumbs -->
    <!-- <Breadcrumbs /> -->
    <!-- Upload buttons --> 
    <div class="p-4 border-b flex items-center justify-between">
      <div class="flex space-x-2 text-sm">
        <input
          ref="fileInput"
          type="file"
          multiple
          class="hidden"
          @change="handleFileUpload"
        />
        <input
          ref="folderInput"
          type="file"
          webkitdirectory
          class="hidden"
          @change="handleFolderUpload"
        />
        <button
          class="btn btn-primary flex items-center space-x-2"
          :disabled="isUploading"
          @click="fileInput?.click()"
        >
          <CloudArrowUpIcon class="h-5 w-5" />
          <span v-if="isUploading">{{ $t('gestlab.general.labels.vap_filemanager.uploading') }}...</span>
          <span v-else>{{ $t('gestlab.general.labels.vap_filemanager.upload_files') }}</span>
        </button>
        <button
          class="btn btn-secondary flex items-center space-x-2"
          :disabled="isUploading"
          @click="folderInput?.click()"
        >
          <FolderPlusIcon class="h-5 w-5" />
          <span v-if="isUploading">{{ $t('gestlab.general.labels.vap_filemanager.uploading') }}...</span>
          <span v-else>{{ $t('gestlab.general.labels.vap_filemanager.upload_folder') }}</span>
        </button>

        <button
        class="btn btn-secondary"
        @click="startCreateFolder"
        >
        {{ $t('gestlab.general.labels.vap_filemanager.create_folder') }}
        </button>
      </div>

      <!-- Search and filter controls -->
      <div class="flex items-center space-x-2 text-sm">
        <div class="relative">
          <input
            type="text"
            v-model="fileStore.searchQuery"
            @input="debouncedSearch(fileStore.searchQuery)"
            :placeholder="$t('gestlab.general.labels.vap_filemanager.search_files') + '...'"
            class="pl-10 pr-4 py-2 rounded-md border-gray-300 text-sm"
          />
          <MagnifyingGlassIcon class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" />
        </div>
        <button
          class="btn btn-secondary"
          @click="showFilterDialog = true"
        >
          <FunnelIcon class="h-5 w-5" />
        </button>
      </div>
    </div>

    <Breadcrumbs />


    <!-- Root drop area indicator -->
    <div 
      v-if="isDraggingFiles && !dragOverItem" 
      class="absolute inset-0 border-2 border-dashed border-blue-400 bg-blue-50 bg-opacity-50 flex items-center justify-center z-10"
    >
      <div class="text-lg text-blue-600 flex items-center space-x-2">
        <span v-if="isDraggingExternal">{{ $t('gestlab.general.labels.vap_filemanager.drop_to_upload_to_current_folder') }}</span>
        <span v-else>{{ $t('gestlab.general.labels.vap_filemanager.drop_to_move_to_current_folder') }}</span>
      </div>
    </div>

    <!-- File list -->
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-blue-900">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
              <button 
                class="flex items-center space-x-1"
                @click="sortField = 'name'; sortDirection = sortDirection === 'asc' ? 'desc' : 'asc'"
              >
                <span>{{ $t('gestlab.general.labels.vap_filemanager.name') }}</span>
                <ChevronUpIcon v-if="sortField === 'name' && sortDirection === 'asc'" class="h-4 w-4" />
                <ChevronDownIcon v-if="sortField === 'name' && sortDirection === 'desc'" class="h-4 w-4" />
              </button>
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
              <button 
                class="flex items-center space-x-1"
                @click="sortField = 'size'; sortDirection = sortDirection === 'asc' ? 'desc' : 'asc'"
              >
                <span>{{ $t('gestlab.general.labels.vap_filemanager.size') }}</span>
                <ChevronUpIcon v-if="sortField === 'size' && sortDirection === 'asc'" class="h-4 w-4" />
                <ChevronDownIcon v-if="sortField === 'size' && sortDirection === 'desc'" class="h-4 w-4" />
              </button>
            </th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
              <button 
                class="flex items-center space-x-1"
                @click="sortField = 'modifiedAt'; sortDirection = sortDirection === 'asc' ? 'desc' : 'asc'"
              >
                <span>{{ $t('gestlab.general.labels.vap_filemanager.modified') }}</span>
                <ChevronUpIcon v-if="sortField === 'modifiedAt' && sortDirection === 'asc'" class="h-4 w-4" />
                <ChevronDownIcon v-if="sortField === 'modifiedAt' && sortDirection === 'desc'" class="h-4 w-4" />
              </button>
            </th>
            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-white uppercase tracking-wider">
              {{ $t('gestlab.general.labels.vap_filemanager.actions') }}
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="file in fileStore.currentFiles" :key="file.id" class="hover:bg-gray-50 relative" :class="{
              'bg-blue-50': dragOverItem === file.id && file.type === 'folder',
              'cursor-move': !isUploading
            }"
            draggable="true"
            @dragstart="handleDragStart($event, file.id)"
            @dragenter.prevent="handleDragEnter"
            @dragover.prevent="handleDragOver($event, file.id)"
            @dragleave.prevent="handleDragLeave"
            @drop.prevent="handleDrop($event, file.id)"
            >

            <!-- Folder drop indicator -->
            <td 
              v-if="file.type === 'folder' && dragOverItem === file.id"
              class="absolute inset-0 border-2 border-dashed border-blue-400 bg-blue-50 bg-opacity-75 z-10 pointer-events-none"
              colspan="4"
            >
              <div class="absolute inset-0 flex items-center justify-center">
                <div class="bg-white rounded-lg shadow-lg px-4 py-2 flex items-center space-x-2">
                  <FolderIcon class="h-5 w-5 text-blue-500" />
                  <span class="text-blue-700 font-medium">
                    {{ isDraggingExternal ? $t('gestlab.general.labels.vap_filemanager.upload_to') + ' "' + file.name + '"' : $t('gestlab.general.labels.vap_filemanager.move_to') + ' "' + file.name + '"' }}
                  </span>
                </div>
              </div>
            </td>

            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <FolderIcon
                  v-if="file.type === 'folder'"
                  class="h-5 w-5 text-gray-400 mr-2"
                  :class="{
                    'text-blue-400': dragOverItem === file.id,
                    'text-gray-400': dragOverItem !== file.id
                  }"
                />
                <DocumentIcon
                  v-else
                  class="h-5 w-5 text-gray-400 mr-2"
                />
                <span class="text-sm text-gray-900" @click="file.type === 'folder' ? navigateToFolder(file.id) : handleItemClick(file, $event)">{{ file.name }}</span>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="text-sm text-gray-500">{{ formatSize(file.size) }}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="text-sm text-gray-500">{{ formatDate(file.modifiedAt) }}</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right">
              <div class="flex justify-end space-x-2">
                <button
                  v-if="canPreview(file)"
                  class="text-gray-400 hover:text-purple-600"
                  @click="previewItem(file.id)"
                >
                  <EyeIcon class="h-5 w-5" />
                </button>

                <button
                  class="text-gray-400 hover:text-purple-600"
                  @click="startMove(file.id)"
                >
                  <ArrowsRightLeftIcon class="h-5 w-5" />
                </button>

                <button
                  v-if="file.type === 'file'"
                  class="text-gray-400 hover:text-purple-600"
                  @click="showVersionHistory(file.id)"
                >
                  <ClockIcon class="h-5 w-5" />
                </button>
                <button
                  class="text-gray-400 hover:text-blue-600"
                  @click="startRename(file.id)"
                >
                  <PencilIcon class="h-5 w-5" />
                </button>
                <button
                  class="text-gray-400 hover:text-green-600"
                  @click="startShare(file.id)"
                >
                  <ShareIcon class="h-5 w-5" />
                </button>
                <button
                  class="text-gray-400 hover:text-indigo-600"
                  @click="showTagManager(file.id)"
                >
                  <TagIcon class="h-5 w-5" />
                </button>
                <button
                  class="text-gray-400 hover:text-yellow-600"
                  @click="fileStore.archiveItem(file.id)"
                >
                  <ArchiveBoxIcon class="h-5 w-5" />
                </button>
                <button
                  class="text-gray-400 hover:text-red-600"
                  @click="startDelete(file.id)"
                >
                  <TrashIcon class="h-5 w-5" />
                </button>

                <button
                    v-if="file.type === 'file'"
                    class="text-gray-400 hover:text-blue-600"
                    @click.stop="fileStore.downloadFile(file.id)"
                    >
                    <ArrowDownTrayIcon class="h-5 w-5" />
                    </button>
              </div>
            </td>
          </tr>
          <tr v-if="filteredFiles.length === 0">
            <td colspan="4" class="px-6 py-4 text-center text-gray-500">
              {{ $t('gestlab.general.labels.vap_filemanager.no_files_found') }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Loading overlay -->
    <div 
      v-if="isUploading" 
      class="absolute inset-0 bg-white/50 flex flex-col items-center justify-center"
    >
      <div class="text-gray-500 mb-2">{{ $t('gestlab.general.labels.vap_filemanager.uploading') }}...</div>
      <div v-if="Object.keys(uploadProgress).length > 0" class="w-64">
        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
          <div 
            class="h-full bg-blue-600 transition-all duration-300"
            :style="{ width: `${totalProgress}%` }"
          ></div>
        </div>
        <div class="text-sm text-gray-500 mt-1 text-center">
          {{ totalProgress }}%
        </div>
      </div>
    </div>

    <!-- Rename Dialog -->
    <Dialog :open="showRenameDialog" @close="showRenameDialog = false" class="relative z-50">
      <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
      <div class="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel class="w-full max-w-sm rounded bg-white p-6">
          <DialogTitle class="text-lg font-medium mb-4">{{ $t('gestlab.general.labels.vap_filemanager.rename_item') }}</DialogTitle>
          <input
            type="text"
            v-model="newItemName"
            class="w-full rounded-md border-gray-300 mb-4"
            @keyup.enter="confirmRename"
          />
          <div class="flex justify-end space-x-2">
            <button
              class="btn btn-secondary"
              @click="showRenameDialog = false"
            >
              {{ $t('gestlab.general.labels.vap_filemanager.buttons.cancel') }}
            </button>
            <button
              class="btn btn-primary"
              @click="confirmRename"
            >
              {{ $t('gestlab.general.labels.vap_filemanager.buttons.rename') }}
            </button>
          </div>
        </DialogPanel>
      </div>
    </Dialog>

    <!-- Share Dialog -->
    <Dialog :open="showShareDialog" @close="showShareDialog = false" class="relative z-50">
      <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
      <div class="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel class="w-full max-w-sm rounded bg-white p-6">
          <DialogTitle class="text-lg font-medium mb-4">{{ $t('gestlab.general.labels.vap_filemanager.share_item') }}</DialogTitle>
          <input
            type="email"
            v-model="shareEmail"
            placeholder="Enter email address"
            class="w-full rounded-md border-gray-300 mb-4"
          />
          <select
            v-model="shareAccess"
            class="w-full rounded-md border-gray-300 mb-4"
          >
            <option value="read">{{ $t('gestlab.general.labels.vap_filemanager.read') }}</option>
            <option value="write">{{ $t('gestlab.general.labels.vap_filemanager.write') }}</option>
            <option value="admin">{{ $t('gestlab.general.labels.vap_filemanager.admin') }}</option>
          </select>
          <div class="flex justify-end space-x-2">
            <button
              class="btn btn-secondary"
              @click="showShareDialog = false"
            >
              {{ $t('gestlab.general.labels.vap_filemanager.buttons.cancel') }}
            </button>
            <button
              class="btn btn-primary"
              @click="confirmShare"
            >
              {{ $t('gestlab.general.labels.vap_filemanager.buttons.share') }}
            </button>
          </div>
        </DialogPanel>
      </div>
    </Dialog>

    <!-- Move Dialog -->
    <Dialog :open="showMoveDialog" @close="showMoveDialog = false" class="relative z-50">
      <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
      <div class="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel class="w-full max-w-sm rounded bg-white p-6">
          <DialogTitle class="text-lg font-medium mb-4">{{ $t('gestlab.general.labels.vap_filemanager.move_file') }}</DialogTitle>
          
          <div class="mt-2">
            <combobox v-model="movingToFolderId" :load-options="loadFolders" @update:model-value="" />
          </div>

          <div class="flex justify-end space-x-2">
            <button
              class="btn btn-secondary"
              @click="showMoveDialog = false"
            >
              {{ $t('gestlab.general.buttons.cancel') }}
            </button>
            <button
              class="btn btn-primary"
              @click="confirmMove"
            >
              {{ $t('gestlab.general.buttons.move') }}
            </button>
          </div>
        </DialogPanel>
      </div>
    </Dialog>

    <!-- Preview Dialog -->
    <FilePreview
      :is-open="showPreviewDialog"
      :file="previewFile"
      @close="showPreviewDialog = false"
    />

    <!-- Version History Dialog -->
    <FileVersionHistory
      :is-open="showVersionHistoryDialog"
      :file-id="versionHistoryFileId"
      @close="showVersionHistoryDialog = false"
    />

    <!-- Tag Manager Dialog -->
    <Dialog :open="showTagDialog" @close="showTagDialog = false" class="relative z-50">
      <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
      <div class="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel class="w-full max-w-md rounded bg-white p-6">
          <DialogTitle class="text-lg font-medium mb-4 flex items-center justify-between">
            <span>{{ $t('gestlab.general.labels.vap_filemanager.manage_tags') }}</span>
            <button @click="showTagDialog = false" class="text-gray-400 hover:text-gray-600">
              <XMarkIcon class="h-6 w-6" />
            </button>
          </DialogTitle>
          
          <TagManager v-if="tagFileId" :file-id="tagFileId" />
        </DialogPanel>
      </div>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <Dialog :open="showDeleteDialog" @close="showDeleteDialog = false" class="relative z-50">
      <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
      <div class="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel class="w-full max-w-sm rounded bg-white p-6">
          <DialogTitle class="text-lg font-medium mb-4 flex items-center">
            <ExclamationTriangleIcon class="h-6 w-6 text-red-600 mr-2" />
            <span>{{ $t('gestlab.general.labels.vap_filemanager.delete_item') }}</span>
          </DialogTitle>
          <p class="text-gray-600 mb-4">
            {{ $t('gestlab.general.labels.vap_filemanager.prompts.delete') }}
            <span class="font-medium">{{ itemToDelete?.name }}</span>?
            {{ $t('gestlab.general.labels.vap_filemanager.prompts.action_cannot_be_undone') }}.
          </p>
          <div class="flex justify-end space-x-2">
            <button
              class="btn btn-secondary"
              @click="showDeleteDialog = false"
            >
              {{ $t('gestlab.general.labels.vap_filemanager.buttons.cancel') }}
            </button>
            <button
              class="btn bg-red-600 text-white hover:bg-red-700"
              @click="confirmDelete"
            >
              {{ $t('gestlab.general.labels.vap_filemanager.buttons.delete') }}
            </button>
          </div>
        </DialogPanel>
      </div>
    </Dialog>

    <!-- Filter Dialog -->
    <Dialog :open="showFilterDialog" @close="showFilterDialog = false" class="relative z-50">
      <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
      <div class="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel class="w-full max-w-md rounded bg-white p-6">
          <DialogTitle class="text-lg font-medium mb-4">{{ $t('gestlab.general.labels.vap_filemanager.filter_files') }}</DialogTitle>
          
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('gestlab.general.labels.vap_filemanager.filter_type') }}</label>
              <div class="space-x-2">
                <label class="inline-flex items-center">
                  <input
                    type="checkbox"
                    v-model="filterType"
                    value="file"
                    class="rounded border-gray-300 text-blue-600"
                  />
                  <span class="ml-2">{{ $t('gestlab.general.labels.vap_filemanager.files') }}</span>
                </label>
                <label class="inline-flex items-center">
                  <input
                    type="checkbox"
                    v-model="filterType"
                    value="folder"
                    class="rounded border-gray-300 text-blue-600"
                  />
                  <span class="ml-2">{{ $t('gestlab.general.labels.vap_filemanager.folders') }}</span>
                </label>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('gestlab.general.labels.vap_filemanager.filter_date_range') }}</label>
              <select
                v-model="filterDateRange"
                class="w-full rounded-md border-gray-300"
              >
                <option value="">{{ $t('gestlab.general.labels.vap_filemanager.filter_date_ranges.all_time') }}</option>
                <option value="7days">{{ $t('gestlab.general.labels.vap_filemanager.filter_date_ranges.7days') }}</option>
                <option value="30days">{{ $t('gestlab.general.labels.vap_filemanager.filter_date_ranges.30days') }}</option>
                <option value="custom">{{ $t('gestlab.general.labels.vap_filemanager.filter_date_ranges.custom') }}</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('gestlab.general.labels.vap_filemanager.filter_size') }}</label>
              <select
                v-model="filterSize"
                class="w-full rounded-md border-gray-300"
              >
                <option value="">{{ $t('gestlab.general.labels.vap_filemanager.filter_sizes.any') }}</option>
                <option value="small">{{ $t('gestlab.general.labels.vap_filemanager.filter_sizes.small') }}</option>
                <option value="medium">{{ $t('gestlab.general.labels.vap_filemanager.filter_sizes.medium') }}</option>
                <option value="large">{{ $t('gestlab.general.labels.vap_filemanager.filter_sizes.large') }}</option>
              </select>
            </div>
          </div>

          <div class="mt-6 flex justify-end space-x-2">
            <button
              class="btn btn-secondary"
              @click="() => {
                filterType.value = []
                filterDateRange.value = null
                filterSize.value = null
                showFilterDialog = false
              }"
            >
              {{ $t('gestlab.general.labels.vap_filemanager.filter_reset') }}
            </button>
            <button
              class="btn btn-primary"
              @click="showFilterDialog = false"
            >
              {{ $t('gestlab.general.labels.vap_filemanager.filter_apply') }}
            </button>
          </div>
        </DialogPanel>
      </div>
    </Dialog>
  </div>

  <!-- Create Folder Dialog -->
  <Dialog :open="showCreateFolderDialog" @close="showCreateFolderDialog = false" class="relative z-50">
  <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
  <div class="fixed inset-0 flex items-center justify-center p-4">
    <DialogPanel class="w-full max-w-sm rounded bg-white p-6">
      <DialogTitle class="text-lg font-medium mb-4">{{ $t('gestlab.general.labels.vap_filemanager.create_folder') }}</DialogTitle>
      <input
        v-model="newFolderName"
        type="text"
        :placeholder="$t('gestlab.general.labels.vap_filemanager.create_folder_name')"
        class="w-full rounded-md border-gray-300 mb-4"
        @keyup.enter="confirmCreateFolder"
      />
      <div class="flex justify-end space-x-2">
        <button
          class="btn btn-secondary"
          @click="showCreateFolderDialog = false"
        >
          {{ $t('gestlab.general.labels.vap_filemanager.buttons.cancel') }}
        </button>
        <button
          class="btn btn-primary"
          @click="confirmCreateFolder"
        >
          {{ $t('gestlab.general.labels.vap_filemanager.buttons.create') }}
        </button>
      </div>
    </DialogPanel>
  </div>
</Dialog>

<!-- Override Dialog -->
<Dialog 
      :open="fileStore.showOverrideDialog" 
      @close="fileStore.cancelOverride" 
      class="relative z-50"
    >
      <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
      <div class="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel class="w-full max-w-md rounded bg-white p-6">
          <DialogTitle class="text-lg font-medium mb-4 flex items-center">
            <ExclamationTriangleIcon class="h-6 w-6 text-yellow-500 mr-2" />
            <span>{{ $t('gestlab.general.labels.vap_filemanager.prompts.notifications.file_already_exists') }}</span>
          </DialogTitle>
          <p class="text-gray-600 mb-4">
            <!-- A file with the name "{{ fileStore.pendingFile?.file.name }}" already exists in this location. -->
            {{ $t('gestlab.general.labels.vap_filemanager.prompts.notifications.file_override_confirmation') }}
            <!-- Would you like to override it? -->
          </p>
          <div class="flex justify-end space-x-2">
            <button
              class="btn btn-secondary"
              @click="fileStore.cancelOverride"
            >
              {{ $t('gestlab.general.labels.vap_filemanager.buttons.cancel') }}
            </button>
            <button
              class="btn bg-yellow-600 text-white hover:bg-yellow-700"
              @click="fileStore.confirmOverride"
            >
              {{ $t('gestlab.general.labels.vap_filemanager.file_override') }}
            </button>
          </div>
        </DialogPanel>
      </div>
    </Dialog>
</template>
<style scoped>
.cursor-move {
  cursor: move;
}
</style>