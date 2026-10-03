<template>
  <section
    class="file-list-container relative min-w-0"
    aria-label="Biblioteca documental"
    @dragenter.prevent="handleDragEnter"
    @dragleave.prevent="handleDragLeave"
  >
    <StateCells
      class="mb-10"
      :items="quickFilters"
      :model-value="statusFilter"
      label="Estado documental"
      @update:model-value="statusFilter = $event"
    />

    <form class="pl-filter" role="search" data-testid="document-library-toolbar" @submit.prevent>
      <label for="document-search" class="pl-filter-prompt">Filtro://</label>
      <BaseInput
        id="document-search"
        v-model="searchQuery"
        type="search"
        data-bare
        class="pl-filter-input"
        maxlength="100"
        placeholder="nome do documento ou da pasta"
        data-testid="document-search"
        @input="debouncedSearch(searchQuery)"
      />
      <button type="button" class="ds-button ds-button-quiet" @click="showFilterDialog = true">
        <FunnelIcon class="h-4 w-4" aria-hidden="true" />
        Filtros
      </button>
      <button v-if="hasActiveFilters" type="button" class="ds-chip" @click="clearFilters">
        Limpar filtros
        <XMarkIcon class="h-3.5 w-3.5" aria-hidden="true" />
      </button>
    </form>

    <div class="pl-panel relative" :aria-busy="isUploading">
      <div class="pl-panel-head">
        <Breadcrumbs />
        <span class="pl-k pl-faint shrink-0">{{ filteredFiles.length }} de {{ fileStore.currentFiles.length }} itens</span>
      </div>

      <!-- Root drop area indicator -->
      <div
        v-if="isDraggingFiles && !dragOverItem"
        class="pointer-events-none absolute inset-0 z-10 grid place-items-center border-2 border-dashed border-[var(--pl-accent-text)] bg-[var(--pl-layer)]"
      >
        <div class="grid justify-items-center gap-2 p-6 text-center">
          <FolderIcon class="h-8 w-8 text-[var(--pl-accent-text)]" aria-hidden="true" />
          <p class="pl-k pl-acc">
            {{ isDraggingExternal
              ? $t('gestlab.general.labels.vap_filemanager.drop_to_upload_to_current_folder')
              : $t('gestlab.general.labels.vap_filemanager.drop_to_move_to_current_folder')
            }}
          </p>
          <p class="text-sm text-[var(--pl-muted)]">{{ $t('gestlab.general.labels.vap_filemanager.drop_files_anywhere') }}</p>
        </div>
      </div>

      <div v-if="filteredFiles.length" class="relative overflow-x-auto" data-testid="document-register">
        <DataTable class="min-w-full">
          <thead>
            <tr>
              <th scope="col" class="w-12">
                <CheckboxInput
                  type="checkbox"
                  class="h-4 w-4"
                  aria-label="Seleccionar todos os itens visíveis"
                  :checked="allVisibleSelected"
                  :indeterminate="someVisibleSelected && !allVisibleSelected"
                  @change="toggleSelectVisible"
                />
              </th>
              <th scope="col" :aria-sort="sortField === 'name' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : undefined">
                <button type="button" class="inline-flex items-center gap-1 hover:text-[var(--pl-fg)]" @click="sortField = 'name'; sortDirection = sortDirection === 'asc' ? 'desc' : 'asc'">
                  {{ $t('gestlab.general.labels.vap_filemanager.name') }}
                  <ChevronUpIcon v-if="sortField === 'name' && sortDirection === 'asc'" class="h-3.5 w-3.5" aria-hidden="true" />
                  <ChevronDownIcon v-if="sortField === 'name' && sortDirection === 'desc'" class="h-3.5 w-3.5" aria-hidden="true" />
                </button>
              </th>
              <th scope="col" :aria-sort="sortField === 'size' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : undefined">
                <button type="button" class="inline-flex items-center gap-1 hover:text-[var(--pl-fg)]" @click="sortField = 'size'; sortDirection = sortDirection === 'asc' ? 'desc' : 'asc'">
                  {{ $t('gestlab.general.labels.vap_filemanager.size') }}
                  <ChevronUpIcon v-if="sortField === 'size' && sortDirection === 'asc'" class="h-3.5 w-3.5" aria-hidden="true" />
                  <ChevronDownIcon v-if="sortField === 'size' && sortDirection === 'desc'" class="h-3.5 w-3.5" aria-hidden="true" />
                </button>
              </th>
              <th scope="col" :aria-sort="sortField === 'modifiedAt' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : undefined">
                <button type="button" class="inline-flex items-center gap-1 hover:text-[var(--pl-fg)]" @click="sortField = 'modifiedAt'; sortDirection = sortDirection === 'asc' ? 'desc' : 'asc'">
                  {{ $t('gestlab.general.labels.vap_filemanager.modified') }}
                  <ChevronUpIcon v-if="sortField === 'modifiedAt' && sortDirection === 'asc'" class="h-3.5 w-3.5" aria-hidden="true" />
                  <ChevronDownIcon v-if="sortField === 'modifiedAt' && sortDirection === 'desc'" class="h-3.5 w-3.5" aria-hidden="true" />
                </button>
              </th>
              <th scope="col"><span class="sr-only">{{ $t('gestlab.general.labels.vap_filemanager.actions') }}</span></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="file in filteredFiles"
              :key="file.id"
              class="group relative"
              :class="{
                'bg-[var(--pl-layer)]': fileStore.selectedItems.has(file.id) || (dragOverItem === file.id && file.type === 'folder'),
                'cursor-move': !isUploading,
              }"
              :data-selected="fileStore.selectedItems.has(file.id) || undefined"
              draggable="true"
              @dragstart="handleDragStart($event, file.id)"
              @dragenter.prevent="handleDragEnter"
              @dragover.prevent="handleDragOver($event, file.id)"
              @dragleave.prevent="handleDragLeave"
              @drop.prevent="handleDrop($event, file.id)"
            >
              <td>
                <CheckboxInput
                  type="checkbox"
                  class="h-4 w-4"
                  :aria-label="`Seleccionar ${file.name}`"
                  :checked="fileStore.selectedItems.has(file.id)"
                  @change="toggleSelection(file.id)"
                />
              </td>
              <!-- Folder drop indicator -->
              <td
                v-if="file.type === 'folder' && dragOverItem === file.id"
                class="pointer-events-none absolute inset-0 z-10 border-2 border-dashed border-[var(--pl-accent-text)] bg-[var(--pl-layer)]"
                colspan="5"
              >
                <span class="absolute inset-0 flex items-center justify-center gap-2 pl-k pl-acc">
                  <FolderIcon class="h-4 w-4" aria-hidden="true" />
                  {{ isDraggingExternal
                    ? $t('gestlab.general.labels.vap_filemanager.upload_to') + ' "' + file.name + '"'
                    : $t('gestlab.general.labels.vap_filemanager.move_to') + ' "' + file.name + '"'
                  }}
                </span>
              </td>

              <td class="min-w-80">
                <div class="flex items-start gap-3">
                  <FolderIcon v-if="file.type === 'folder'" class="mt-0.5 h-4 w-4 shrink-0 text-[var(--pl-accent-text)]" aria-hidden="true" />
                  <DocumentIcon v-else class="mt-0.5 h-4 w-4 shrink-0 text-[var(--pl-muted)]" aria-hidden="true" />
                  <div class="min-w-0">
                    <button
                      type="button"
                      class="block max-w-md truncate text-left font-medium hover:text-[var(--pl-accent-text)]"
                      @click="file.type === 'folder' ? navigateToFolder(file.id) : handleItemClick(file, $event)"
                    >
                      {{ file.name }}
                    </button>
                    <div class="mt-1 flex flex-wrap items-center gap-1.5">
                      <span class="text-[12.5px] text-[var(--pl-muted)]">{{ file.type === 'folder' ? 'Pasta' : (file.document_type || 'Ficheiro') }}</span>
                      <StatusChip v-if="file.status" :tone="statusTone(file)">{{ formatStatusLabel(file.status) }}</StatusChip>
                      <span v-if="file.document_number" class="pl-num text-[12px] text-[var(--pl-muted)]">{{ file.document_number }}</span>
                      <span v-if="file.revision_code" class="pl-tag">{{ file.revision_code }}</span>
                      <StatusChip v-if="isReviewOverdue(file)" tone="bad">Revisão em atraso</StatusChip>
                    </div>
                  </div>
                </div>
              </td>

              <td class="pl-num whitespace-nowrap">{{ formatSize(file.size) || '—' }}</td>
              <td class="pl-num whitespace-nowrap">{{ formatDate(file.modifiedAt) || '—' }}</td>

              <td class="whitespace-nowrap text-right">
                <div class="flex items-center justify-end gap-1">
                  <button
                    v-if="canPreview(file)"
                    type="button"
                    class="ds-table-action"
                    :aria-label="`Pré-visualizar ${file.name}`"
                    @click="previewItem(file.id)"
                  >
                    <EyeIcon class="h-4 w-4" aria-hidden="true" />
                  </button>
                  <button
                    v-if="file.type === 'file'"
                    type="button"
                    class="ds-table-action"
                    :aria-label="`Transferir ${file.name}`"
                    @click.stop="fileStore.downloadFile(file.id)"
                  >
                    <ArrowDownTrayIcon class="h-4 w-4" aria-hidden="true" />
                  </button>

                  <Menu as="div" class="relative">
                    <MenuButton class="ds-table-action" :aria-label="`Mais opções para ${file.name}`">
                      <EllipsisHorizontalIcon class="h-4 w-4" aria-hidden="true" />
                    </MenuButton>
                    <MenuItems class="ds-floating-panel absolute right-0 z-30 mt-1 w-56 origin-top-right text-left focus:outline-none">
                      <MenuItem v-slot="{ active }">
                        <button type="button" class="pl-menu-item" :data-active="active" @click="startMove(file.id)">
                          <ArrowsRightLeftIcon aria-hidden="true" />
                          Mover
                        </button>
                      </MenuItem>
                      <MenuItem v-if="file.type === 'file'" v-slot="{ active }">
                        <button type="button" class="pl-menu-item" :data-active="active" @click="showVersionHistory(file.id)">
                          <ClockIcon aria-hidden="true" />
                          Histórico de versões
                        </button>
                      </MenuItem>
                      <MenuItem v-slot="{ active }">
                        <button type="button" class="pl-menu-item" :data-active="active" @click="startRename(file.id)">
                          <PencilIcon aria-hidden="true" />
                          Renomear
                        </button>
                      </MenuItem>
                      <MenuItem v-slot="{ active }">
                        <button type="button" class="pl-menu-item" :data-active="active" @click="startShare(file.id)">
                          <ShareIcon aria-hidden="true" />
                          Partilhar
                        </button>
                      </MenuItem>
                      <MenuItem v-slot="{ active }">
                        <button type="button" class="pl-menu-item" :data-active="active" @click="showTagManager(file.id)">
                          <TagIcon aria-hidden="true" />
                          Etiquetas
                        </button>
                      </MenuItem>
                      <MenuItem v-slot="{ active }">
                        <button type="button" class="pl-menu-item" :data-active="active" @click="fileStore.archiveItem(file.id)">
                          <ArchiveBoxIcon aria-hidden="true" />
                          Arquivar
                        </button>
                      </MenuItem>
                      <div class="pl-menu-sep" />
                      <MenuItem v-slot="{ active }">
                        <button type="button" class="pl-menu-item pl-menu-item-danger" :data-active="active" @click="startDelete(file.id)">
                          <TrashIcon aria-hidden="true" />
                          Eliminar
                        </button>
                      </MenuItem>
                    </MenuItems>
                  </Menu>
                </div>
              </td>
            </tr>
          </tbody>
        </DataTable>
      </div>

      <div v-if="filteredFiles.length === 0" class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
        <span class="pl-k">{{ $t('gestlab.general.labels.vap_filemanager.no_files_found') }}</span>
        <p class="text-sm text-[var(--pl-muted)]">
          {{ hasActiveFilters
            ? $t('gestlab.general.labels.vap_filemanager.try_different_search')
            : $t('gestlab.general.labels.vap_filemanager.upload_files_to_get_started')
          }}
        </p>
        <button v-if="!hasActiveFilters" type="button" class="ds-button ds-button-secondary mt-2" :disabled="isUploading" @click="triggerFileUpload">
          <CloudArrowUpIcon class="h-4 w-4" aria-hidden="true" />
          {{ $t('gestlab.general.labels.vap_filemanager.upload_files') }}
        </button>
      </div>

      <!-- Upload progress -->
      <div
        v-if="isUploading"
        class="absolute inset-0 z-20 grid place-items-center bg-[color-mix(in_srgb,var(--pl-bg)_85%,transparent)]"
        role="status"
      >
        <div class="pl-panel grid w-72 gap-3 p-5">
          <span class="pl-k">{{ $t('gestlab.general.labels.vap_filemanager.uploading') }}…</span>
          <p class="text-sm text-[var(--pl-muted)]">{{ $t('gestlab.general.labels.vap_filemanager.please_wait') }}</p>
          <template v-if="Object.keys(uploadProgress).length > 0">
            <div class="pl-bar"><i :style="{ width: `${totalProgress}%` }"></i></div>
            <span class="pl-num text-[12px] text-[var(--pl-muted)]">{{ totalProgress }}% {{ $t('gestlab.general.labels.vap_filemanager.complete') }}</span>
          </template>
        </div>
      </div>
    </div>

    <div v-if="selectedCount" class="pl-selection" role="region" aria-label="Acções sobre a selecção" data-testid="document-selection-toolbar">
      <span>{{ selectedCount }} {{ selectedCount > 1 ? 'itens seleccionados' : 'item seleccionado' }}</span>
      <button type="button" @click="archiveSelected">Arquivar</button>
      <button v-if="singleSelectedFile?.type === 'file'" type="button" @click="fileStore.downloadFile(singleSelectedFile.id)">Transferir</button>
      <button type="button" @click="deleteSelected">Eliminar</button>
      <button type="button" aria-label="Limpar selecção" @click="clearSelection"><XMarkIcon class="h-4 w-4" aria-hidden="true" /></button>
    </div>

    <!-- Hidden inputs -->
    <FileInput
      ref="fileInput"
      type="file"
      multiple
      class="hidden"
      @change="handleFileUpload"
    />
    <FileInput
      ref="folderInput"
      type="file"
      webkitdirectory
      class="hidden"
      @change="handleFolderUpload"
    />

    <!-- Move dialog -->
    <Dialog :open="showMoveDialog" class="relative z-50" @close="showMoveDialog = false">
      <div class="ds-modal-backdrop fixed inset-0" aria-hidden="true" />
      <div class="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel class="ds-modal-panel w-full max-w-md overflow-hidden">
          <div class="grid gap-1 border-b border-[var(--pl-line)] px-6 py-4">
            <DialogTitle class="pl-d3">{{ $t('gestlab.general.labels.vap_filemanager.move_item') }}</DialogTitle>
            <p class="text-sm text-[var(--pl-muted)]">Escolha a pasta de destino para manter a estrutura documental organizada.</p>
          </div>
          <div class="grid gap-6 p-6">
            <div class="ds-field-group">
              <span class="ds-field-label">{{ $t('gestlab.general.labels.vap_filemanager.destination_folder') }}</span>
              <comboboxEnhanced v-model="movingToFolderId" :load-options="loadFolders" />
            </div>
            <div class="flex justify-end gap-3">
              <button type="button" class="ds-button ds-button-quiet" @click="showMoveDialog = false">{{ $t('gestlab.general.buttons.cancel') }}</button>
              <button type="button" class="ds-button ds-button-primary" @click="confirmMove">{{ $t('gestlab.general.buttons.move') }}</button>
            </div>
          </div>
        </DialogPanel>
      </div>
    </Dialog>

    <!-- Preview dialog -->
    <FilePreview
      :is-open="showPreviewDialog"
      :file="previewFile"
      @close="showPreviewDialog = false"
    />

    <!-- Version history dialog -->
    <FileVersionHistory
      :is-open="showVersionHistoryDialog"
      :file-id="versionHistoryFileId"
      @close="showVersionHistoryDialog = false"
    />

    <!-- Tag manager dialog -->
    <Dialog :open="showTagDialog" class="relative z-50" @close="showTagDialog = false">
      <div class="ds-modal-backdrop fixed inset-0" aria-hidden="true" />
      <div class="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel class="ds-modal-panel w-full max-w-2xl overflow-hidden">
          <div class="flex items-start justify-between gap-4 border-b border-[var(--pl-line)] px-6 py-4">
            <div class="grid gap-1">
              <DialogTitle class="pl-d3">{{ $t('gestlab.general.labels.vap_filemanager.manage_tags') }}</DialogTitle>
              <p class="text-sm text-[var(--pl-muted)]">Organize o ficheiro com etiquetas consistentes para facilitar pesquisa e rastreabilidade.</p>
            </div>
            <button type="button" class="ds-icon-button" aria-label="Fechar" @click="showTagDialog = false">
              <XMarkIcon class="h-5 w-5" aria-hidden="true" />
            </button>
          </div>
          <div class="p-6">
            <TagManager v-if="tagFileId" :file-id="tagFileId" />
          </div>
        </DialogPanel>
      </div>
    </Dialog>

    <!-- Filter dialog -->
    <Dialog :open="showFilterDialog" class="relative z-50" @close="showFilterDialog = false">
      <div class="ds-modal-backdrop fixed inset-0" aria-hidden="true" />
      <div class="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel class="ds-modal-panel w-full max-w-lg overflow-hidden">
          <div class="grid gap-1 border-b border-[var(--pl-line)] px-6 py-4">
            <DialogTitle class="pl-d3">{{ $t('gestlab.general.labels.vap_filemanager.filter_files') }}</DialogTitle>
            <p class="text-sm text-[var(--pl-muted)]">Refine a vista sem perder o contexto da pasta actual.</p>
          </div>
          <div class="grid gap-5 p-6">
            <fieldset class="ds-field-group">
              <legend class="ds-field-label">{{ $t('gestlab.general.labels.vap_filemanager.filter_type') }}</legend>
              <div class="mt-2 flex flex-wrap gap-4">
                <label class="inline-flex items-center gap-2 text-sm">
                  <CheckboxInput v-model="filterType" type="checkbox" value="file" class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_filemanager.files') }}
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                  <CheckboxInput v-model="filterType" type="checkbox" value="folder" class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_filemanager.folders') }}
                </label>
              </div>
            </fieldset>

            <BaseSelect v-model="filterDateRange" :label="$t('gestlab.general.labels.vap_filemanager.filter_date_range')">
              <option value="">{{ $t('gestlab.general.labels.vap_filemanager.filter_date_ranges.all_time') }}</option>
              <option value="7days">{{ $t('gestlab.general.labels.vap_filemanager.filter_date_ranges.7days') }}</option>
              <option value="30days">{{ $t('gestlab.general.labels.vap_filemanager.filter_date_ranges.30days') }}</option>
              <option value="custom">{{ $t('gestlab.general.labels.vap_filemanager.filter_date_ranges.custom') }}</option>
            </BaseSelect>

            <BaseSelect v-model="filterSize" :label="$t('gestlab.general.labels.vap_filemanager.filter_size')">
              <option value="">{{ $t('gestlab.general.labels.vap_filemanager.filter_sizes.any') }}</option>
              <option value="small">{{ $t('gestlab.general.labels.vap_filemanager.filter_sizes.small') }}</option>
              <option value="medium">{{ $t('gestlab.general.labels.vap_filemanager.filter_sizes.medium') }}</option>
              <option value="large">{{ $t('gestlab.general.labels.vap_filemanager.filter_sizes.large') }}</option>
            </BaseSelect>

            <div class="flex justify-end gap-3">
              <button
                type="button"
                class="ds-button ds-button-quiet"
                @click="() => {
                  filterType = []
                  filterDateRange = null
                  filterSize = null
                  showFilterDialog = false
                }"
              >
                {{ $t('gestlab.general.labels.vap_filemanager.filter_reset') }}
              </button>
              <button type="button" class="ds-button ds-button-primary" @click="showFilterDialog = false">
                {{ $t('gestlab.general.labels.vap_filemanager.filter_apply') }}
              </button>
            </div>
          </div>
        </DialogPanel>
      </div>
    </Dialog>

    <!-- Rename dialog -->
    <Dialog :open="showRenameDialog" class="relative z-50" @close="showRenameDialog = false">
      <div class="ds-modal-backdrop fixed inset-0" aria-hidden="true" />
      <div class="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel class="ds-modal-panel w-full max-w-md overflow-hidden">
          <div class="grid gap-1 border-b border-[var(--pl-line)] px-6 py-4">
            <DialogTitle class="pl-d3">{{ $t('gestlab.general.labels.vap_filemanager.rename_item') }}</DialogTitle>
            <p class="text-sm text-[var(--pl-muted)]">Use nomes claros para manter a recuperação e a trilha de auditoria limpas.</p>
          </div>
          <div class="grid gap-6 p-6">
            <BaseInput
              v-model="newItemName"
              type="text"
              class="ds-field"
              :label="$t('gestlab.general.labels.vap_filemanager.name')"
              :placeholder="$t('gestlab.general.labels.vap_filemanager.enter_new_name')"
              @keyup.enter="confirmRename"
            />
            <div class="flex justify-end gap-3">
              <button type="button" class="ds-button ds-button-quiet" @click="showRenameDialog = false">
                {{ $t('gestlab.general.labels.vap_filemanager.buttons.cancel') }}
              </button>
              <button type="button" class="ds-button ds-button-primary" :disabled="!newItemName.trim()" @click="confirmRename">
                {{ $t('gestlab.general.labels.vap_filemanager.buttons.rename') }}
              </button>
            </div>
          </div>
        </DialogPanel>
      </div>
    </Dialog>

    <!-- Share dialog -->
    <Dialog :open="showShareDialog" class="relative z-50" @close="showShareDialog = false">
      <div class="ds-modal-backdrop fixed inset-0" aria-hidden="true" />
      <div class="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel class="ds-modal-panel w-full max-w-md overflow-hidden">
          <div class="grid gap-1 border-b border-[var(--pl-line)] px-6 py-4">
            <DialogTitle class="pl-d3">{{ $t('gestlab.general.labels.vap_filemanager.share_item') }}</DialogTitle>
            <p class="text-sm text-[var(--pl-muted)]">Partilhe com intenção e preserve a confidencialidade documental.</p>
          </div>
          <div class="grid gap-5 p-6">
            <div class="ds-field-group">
              <span class="ds-field-label">Destinatário</span>
              <comboboxEnhanced v-model="shareRecipient" :load-options="loadUsers" />
            </div>
            <BaseSelect v-model="shareAccess" :label="$t('gestlab.general.labels.vap_filemanager.permissions')">
              <option value="read">{{ $t('gestlab.general.labels.vap_filemanager.read') }}</option>
              <option value="write">{{ $t('gestlab.general.labels.vap_filemanager.write') }}</option>
              <option value="admin">{{ $t('gestlab.general.labels.vap_filemanager.admin') }}</option>
            </BaseSelect>
            <div class="flex justify-end gap-3">
              <button type="button" class="ds-button ds-button-quiet" @click="showShareDialog = false">
                {{ $t('gestlab.general.labels.vap_filemanager.buttons.cancel') }}
              </button>
              <button type="button" class="ds-button ds-button-primary" :disabled="!selectedShareRecipientId" @click="confirmShare">
                {{ $t('gestlab.general.labels.vap_filemanager.buttons.share') }}
              </button>
            </div>
          </div>
        </DialogPanel>
      </div>
    </Dialog>

    <!-- Delete confirmation dialog -->
    <Dialog :open="showDeleteDialog" class="relative z-50" @close="showDeleteDialog = false">
      <div class="ds-modal-backdrop fixed inset-0" aria-hidden="true" />
      <div class="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel class="ds-modal-panel w-full max-w-md overflow-hidden">
          <div class="grid gap-1 border-b border-[var(--pl-line)] px-6 py-4">
            <DialogTitle class="pl-d3">{{ $t('gestlab.general.labels.vap_filemanager.delete_item') }}</DialogTitle>
            <p class="text-sm text-[var(--pl-muted)]">Esta acção remove permanentemente o registo e os seus dados de rastreabilidade.</p>
          </div>
          <div class="grid gap-6 p-6">
            <p class="pl-banner pl-banner-bad text-sm" role="alert">
              <ExclamationTriangleIcon aria-hidden="true" />
              <span>
                {{ $t('gestlab.general.labels.vap_filemanager.prompts.delete') }}
                <strong>{{ itemToDelete?.name }}</strong>?
                {{ $t('gestlab.general.labels.vap_filemanager.prompts.action_cannot_be_undone') }}.
              </span>
            </p>
            <div class="flex justify-end gap-3">
              <button type="button" class="ds-button ds-button-quiet" @click="showDeleteDialog = false">
                {{ $t('gestlab.general.labels.vap_filemanager.buttons.cancel') }}
              </button>
              <button type="button" class="ds-button ds-button-danger" @click="confirmDelete">
                {{ $t('gestlab.general.labels.vap_filemanager.buttons.delete') }}
              </button>
            </div>
          </div>
        </DialogPanel>
      </div>
    </Dialog>

    <!-- Create folder dialog -->
    <Dialog :open="showCreateFolderDialog" class="relative z-50" @close="showCreateFolderDialog = false">
      <div class="ds-modal-backdrop fixed inset-0" aria-hidden="true" />
      <div class="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel class="ds-modal-panel w-full max-w-md overflow-hidden">
          <div class="grid gap-1 border-b border-[var(--pl-line)] px-6 py-4">
            <DialogTitle class="pl-d3">{{ $t('gestlab.general.labels.vap_filemanager.create_folder') }}</DialogTitle>
            <p class="text-sm text-[var(--pl-muted)]">Crie uma pasta bem identificada para agrupar procedimentos e registos de forma lógica.</p>
          </div>
          <div class="grid gap-6 p-6">
            <BaseInput
              v-model="newFolderName"
              type="text"
              class="ds-field"
              :label="$t('gestlab.general.labels.vap_filemanager.create_folder_name')"
              @keyup.enter="confirmCreateFolder"
            />
            <div class="flex justify-end gap-3">
              <button type="button" class="ds-button ds-button-quiet" @click="showCreateFolderDialog = false">
                {{ $t('gestlab.general.labels.vap_filemanager.buttons.cancel') }}
              </button>
              <button type="button" class="ds-button ds-button-primary" :disabled="!newFolderName.trim()" @click="confirmCreateFolder">
                {{ $t('gestlab.general.labels.vap_filemanager.buttons.create') }}
              </button>
            </div>
          </div>
        </DialogPanel>
      </div>
    </Dialog>
  </section>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Dialog, DialogPanel, DialogTitle, Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import {
  Folder as FolderIcon,
  File as DocumentIcon,
  Trash2 as TrashIcon,
  Pencil as PencilIcon,
  Archive as ArchiveBoxIcon,
  Share2 as ShareIcon,
  Download as ArrowDownTrayIcon,
  Eye as EyeIcon,
  Funnel as FunnelIcon,
  ChevronUp as ChevronUpIcon,
  ChevronDown as ChevronDownIcon,
  Ellipsis as EllipsisHorizontalIcon,
  Clock as ClockIcon,
  X as XMarkIcon,
  TriangleAlert as ExclamationTriangleIcon,
  Tag as TagIcon,
  CloudUpload as CloudArrowUpIcon,
  ArrowLeftRight as ArrowsRightLeftIcon,
} from '@lucide/vue'
import StateCells from '@/Components/plano/StateCells.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'
import FilePreview from './file-preview.vue'
import FileVersionHistory from './file-version-history.vue'
import TagManager from './tag-manager.vue'
import { useFileStore } from '../../Stores/fileStore'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import { useDebounceFn } from '@vueuse/core'
import Breadcrumbs from './breadcrumbs.vue'
import { trans } from 'laravel-vue-i18n';
import comboboxEnhanced from '@/Components/combobox-enhanced.vue';
import { loadSelectOptions, optionMappers } from '@/Utils/selectOptions';

type ComboboxOption = {
  value: string
  label: string
}

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
const movingToFolderId = ref<ComboboxOption | string | null>(null)
const shareRecipient = ref<ComboboxOption | string | null>(null)
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
const searchQuery = ref(fileStore.searchQuery)
const sortField = ref<'name' | 'size' | 'modifiedAt'>('name')
const sortDirection = ref<'asc' | 'desc'>('asc')
const filterType = ref<string[]>([])
const filterDateRange = ref<'7days' | '30days' | 'custom' | null>(null)
const filterSize = ref<'small' | 'medium' | 'large' | null>(null)

const showCreateFolderDialog = ref(false)
const newFolderName = ref('')
const statusFilter = ref<'all' | 'draft' | 'in_review' | 'effective' | 'review_due' | 'controlled'>('all')
const selectedFiles = computed(() => filteredFiles.value.filter((file) => fileStore.selectedItems.has(file.id)))
const selectedCount = computed(() => selectedFiles.value.length)
const singleSelectedFile = computed(() => selectedCount.value === 1 ? selectedFiles.value[0] : null)
const allVisibleSelected = computed(() => filteredFiles.value.length > 0 && filteredFiles.value.every((file) => fileStore.selectedItems.has(file.id)))
const someVisibleSelected = computed(() => filteredFiles.value.some((file) => fileStore.selectedItems.has(file.id)))
const selectedShareRecipientId = computed(() => extractOptionValue(shareRecipient.value))

function reportDevError(message: string, error: unknown): void {
  if (import.meta.env.DEV) {
    console.error(message, error)
  }
}

function reportDevWarning(message: string): void {
  if (import.meta.env.DEV) {
    console.warn(message)
  }
}

const quickFilters = computed(() => {
  const visibleFiles = fileStore.currentFiles
  const overdue = visibleFiles.filter((file) => isReviewOverdue(file)).length

  return [
    { key: 'all', label: 'Todos', value: visibleFiles.length },
    { key: 'draft', label: 'Rascunhos', value: visibleFiles.filter((file) => file.status === 'draft').length },
    { key: 'in_review', label: 'Em revisão', value: visibleFiles.filter((file) => file.status === 'in_review').length },
    { key: 'effective', label: 'Efectivos', value: visibleFiles.filter((file) => file.status === 'effective').length },
    { key: 'controlled', label: 'Controlados', value: visibleFiles.filter((file) => file.is_controlled).length },
    { key: 'review_due', label: 'Revisão vencida', value: overdue, tone: overdue ? 'bad' : undefined },
  ]
})

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

  if (statusFilter.value !== 'all') {
    files = files.filter((file) => {
      if (statusFilter.value === 'controlled') {
        return file.is_controlled
      }

      if (statusFilter.value === 'review_due') {
        return isReviewOverdue(file)
      }

      return file.status === statusFilter.value
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

const hasActiveFilters = computed(() => {
  return Boolean(
    searchQuery.value ||
      filterType.value.length ||
      filterDateRange.value ||
      filterSize.value ||
      statusFilter.value !== 'all',
  )
})

function clearFilters() {
  searchQuery.value = ''
  fileStore.searchQuery = ''
  filterType.value = []
  filterDateRange.value = null
  filterSize.value = null
  statusFilter.value = 'all'
  fileStore.loadFiles()
}

function extractOptionValue(option: ComboboxOption | string | null): string | null {
  if (!option) {
    return null
  }

  if (typeof option === 'string') {
    return option
  }

  return option.value
}

const statusLabels: Record<string, string> = {
  draft: 'Rascunho',
  in_review: 'Em revisão',
  approved: 'Aprovado',
  effective: 'Efectivo',
  obsolete: 'Obsoleto',
  archived: 'Arquivado',
}

function formatStatusLabel(status?: string | null): string {
  const key = status || 'draft'

  return statusLabels[key] ?? key.replaceAll('_', ' ')
}

/** Status chip tone: in review waits, approved is in progress, effective conforms, obsolete/archived are closed. */
function statusTone(file: { status?: string | null }): string {
  return ({
    in_review: 'wait',
    approved: 'run',
    effective: 'ok',
    obsolete: 'done',
    archived: 'done',
  } as Record<string, string>)[file.status || 'draft'] ?? 'neutral'
}

function isReviewOverdue(file: { review_due_at?: string | null; status?: string | null }) {
  if (!file.review_due_at || file.status === 'archived') {
    return false
  }

  return new Date(file.review_due_at).getTime() < Date.now()
}

function toggleSelection(id: string) {
  if (fileStore.selectedItems.has(id)) {
    fileStore.selectedItems.delete(id)
    return
  }

  fileStore.selectedItems.add(id)
}

function toggleSelectVisible() {
  if (allVisibleSelected.value) {
    filteredFiles.value.forEach((file) => fileStore.selectedItems.delete(file.id))
    return
  }

  filteredFiles.value.forEach((file) => fileStore.selectedItems.add(file.id))
}

function clearSelection() {
  fileStore.selectedItems.clear()
}

async function archiveSelected() {
  await Promise.all(selectedFiles.value.map((file) => fileStore.archiveItem(file.id)))
  clearSelection()
}

async function deleteSelected() {
  await Promise.all(selectedFiles.value.map((file) => fileStore.permanentlyDeleteItem(file.id)))
  clearSelection()
}

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
            reportDevError('Error uploading file:', error)
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
    reportDevError('Error handling drop:', error)
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
    reportDevError(`Error processing directory ${entry.name}:`, error)
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
    reportDevError(`Error processing file ${entry.name}:`, error)
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
    dragImage.className = 'pointer-events-none fixed left-0 top-0 border border-[var(--pl-line-strong)] bg-[var(--pl-layer)] px-3 py-2 text-sm font-medium text-[var(--pl-fg)]'
    dragImage.textContent = `${draggedFile.type === 'folder' ? 'Pasta' : 'Ficheiro'} · ${draggedFile.name}`
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
    await handleFileDrop(event, targetId || fileStore.currentFolder)
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
    reportDevError('Error moving file:', error)
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
    reportDevError('Error loading preview:', error)
  }
}

function handleItemClick(file: any, event: MouseEvent) {
  if (file.type === 'folder') {
    fileStore.navigateToFolder(file.id)
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
  fileStore.navigateToFolder(id)
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
        reportDevWarning(`Folder ID not found for path: ${dirPath}`)
        continue;
        }

        if(dirFiles.length === 0){
            reportDevWarning(`dirFiles array is empty for path: ${dirPath}`)
            continue;
        }

      for (const file of dirFiles) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('parent_id', folderId);

        try {
          await axios.post('/api/files/upload', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
          });
        } catch (error: any) {
          reportDevError(`Failed to carregamento file ${file.name}:`, error)
        //   toast.error(`Failed to upload ${file.name}`);
          toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_uploading_file') + ' - ' + file.name);
        }
      }
    }

    // toast.success('Folder uploaded successfully');
    toast.success(trans('gestlab.general.labels.vap_filemanager.notifications.folder_uploaded'));

    fileStore.fetchFiles();
  } catch (error) {
    reportDevError('Error uploading folder:', error)
    toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_uploading_folder'));
  }

  input.value = '';
}

function triggerFileUpload() {
  fileInput.value?.click()
}

function triggerFolderUpload() {
  folderInput.value?.click()
}

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
  shareRecipient.value = null
  shareAccess.value = 'read'
  showShareDialog.value = true
}

function startMove(id: string) {
  movingItemId.value = id
  movingToFolderId.value = null
  showMoveDialog.value = true
}

function confirmMove() {
  const destinationFolderId = extractOptionValue(movingToFolderId.value)

  if (movingItemId.value && destinationFolderId) {
    fileStore.moveItem(movingItemId.value, destinationFolderId)
    showMoveDialog.value = false
    movingItemId.value = ''
    movingToFolderId.value = null
  }
}

function confirmShare() {
  if (sharingItemId.value && selectedShareRecipientId.value) {
    fileStore.shareItem(sharingItemId.value, selectedShareRecipientId.value, shareAccess.value)
    showShareDialog.value = false
    sharingItemId.value = ''
    shareRecipient.value = null
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
    return new Intl.DateTimeFormat('pt-PT', {
      dateStyle: 'medium',
      timeStyle: 'short'
    }).format(date)
  } catch (error) {
    reportDevError('Error formatting date:', error)
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
    return loadSelectOptions('/api/files/folders/getFolder', query, setOptions, optionMappers.name);
}

function loadUsers(query, setOptions) {
  return loadSelectOptions('/users/getUser', query, setOptions, result => ({
    value: result.id,
    label: `${result.name} (${result.email})`,
  }));
}

defineExpose({ triggerFileUpload, triggerFolderUpload, startCreateFolder, isUploading })
</script>
