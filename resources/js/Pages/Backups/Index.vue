<template>
  <div class="space-y-6">
    <section class="ds-command-surface overflow-hidden">
      <div class="flex flex-col gap-5 px-5 py-5 sm:px-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
          <p class="ds-kicker">Continuidade operacional</p>
          <div class="mt-2 flex items-start gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))]">
              <CircleStackIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="ds-heading text-xl sm:text-2xl">Cópias de segurança</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm">Monitorize a protecção dos dados do laboratório e execute cópias controladas por âmbito.</p>
            </div>
          </div>
        </div>

        <span
          class="inline-flex w-fit items-center gap-2 rounded-lg border px-3 py-2 text-xs font-bold"
          :class="loadError
            ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300'
            : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300'"
        >
          <span class="h-2 w-2 rounded-full" :class="loadError ? 'bg-red-500' : 'bg-emerald-500'" />
          {{ loadError ? 'Monitorização indisponível' : 'Monitorização activa' }}
        </span>
      </div>

      <dl class="grid border-t border-[var(--ds-border)] sm:grid-cols-3 sm:divide-x sm:divide-[var(--ds-border)]">
        <div class="px-5 py-4 sm:px-6">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Destinos protegidos</dt>
          <dd class="mt-1 text-2xl font-bold tabular-nums text-[var(--ds-text)]">{{ disks.length }}</dd>
        </div>
        <div class="border-t border-[var(--ds-border)] px-5 py-4 sm:border-t-0 sm:px-6">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Cópias no destino activo</dt>
          <dd class="mt-1 text-2xl font-bold tabular-nums text-[var(--ds-text)]">{{ activeDiskBackups.length }}</dd>
        </div>
        <div class="border-t border-[var(--ds-border)] px-5 py-4 sm:border-t-0 sm:px-6">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Última verificação</dt>
          <dd class="mt-2 text-sm font-bold tabular-nums text-[var(--ds-text)]">{{ lastUpdated }}</dd>
        </div>
      </dl>
    </section>

    <div v-if="loadError" role="alert" class="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-200">
      <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 shrink-0" />
      <div>
        <p class="font-bold">Não foi possível actualizar o estado das cópias.</p>
        <p class="mt-1 font-medium">Os últimos dados disponíveis permanecem visíveis. A próxima tentativa será automática.</p>
      </div>
    </div>

    <div class="grid min-w-0 gap-6 xl:grid-cols-[minmax(0,1fr)_18rem]">
      <div class="min-w-0 space-y-6">
        <backup-statuses-list :backup-statuses="backupStatuses" />

        <backups
          v-if="activeDisk"
          v-model:active-disk="activeDisk"
          :disks="disks"
          :backups="activeDiskBackups"
          @delete="deleteBackup"
        />

        <div v-else class="ds-empty-state grid min-h-48 place-items-center px-6 py-10 text-center">
          <div>
            <CircleStackIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
            <h2 class="ds-heading mt-3 text-base">Nenhum destino configurado</h2>
            <p class="ds-copy mt-1 text-sm">Configure pelo menos um disco de cópia de segurança para iniciar a protecção operacional.</p>
          </div>
        </div>
      </div>

      <aside class="space-y-4">
        <section class="ds-command-surface overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-4 py-3">
            <p class="ds-kicker">Execução manual</p>
            <h2 class="ds-heading mt-1 text-sm">Criar cópia</h2>
            <p class="ds-copy mt-1 text-xs">Seleccione o âmbito necessário para a verificação ou recuperação planeada.</p>
          </div>

          <div class="grid gap-2 p-4">
            <button type="button" class="ds-button ds-button-primary w-full justify-start" :disabled="form.processing" @click="createBackup">
              <ArrowPathIcon v-if="form.processing && form.option === ''" class="h-4 w-4 animate-spin" />
              <CircleStackIcon v-else class="h-4 w-4" />
              Cópia completa
            </button>
            <button type="button" class="ds-button ds-button-secondary w-full justify-start" :disabled="form.processing" @click="createPartialBackup('only-db')">
              <ArrowPathIcon v-if="form.processing && form.option === 'only-db'" class="h-4 w-4 animate-spin" />
              <TableCellsIcon v-else class="h-4 w-4" />
              Apenas base de dados
            </button>
            <button type="button" class="ds-button ds-button-secondary w-full justify-start" :disabled="form.processing" @click="createPartialBackup('only-files')">
              <ArrowPathIcon v-if="form.processing && form.option === 'only-files'" class="h-4 w-4 animate-spin" />
              <FolderIcon v-else class="h-4 w-4" />
              Apenas ficheiros
            </button>
          </div>
        </section>

        <section class="ds-command-surface p-4">
          <div class="flex items-start gap-3">
            <ShieldCheckIcon class="h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-300" />
            <div>
              <h2 class="ds-heading text-sm">Controlo de recuperação</h2>
              <p class="ds-copy mt-1 text-xs">Uma cópia concluída deve ser acompanhada por testes periódicos de restauro, retenção definida e evidência fora do servidor principal.</p>
            </div>
          </div>

          <dl class="mt-4 divide-y divide-[var(--ds-border)] border-y border-[var(--ds-border)]">
            <div class="flex items-center justify-between gap-3 py-3">
              <dt class="text-xs font-semibold text-[var(--ds-text-muted)]">Destino activo</dt>
              <dd class="truncate font-mono text-xs font-bold text-[var(--ds-text)]">{{ activeDisk || '—' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3 py-3">
              <dt class="text-xs font-semibold text-[var(--ds-text-muted)]">Actualização</dt>
              <dd class="text-xs font-bold text-[var(--ds-text)]">30 segundos</dd>
            </div>
          </dl>
        </section>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import {
  RefreshCw as ArrowPathIcon,
  Database as CircleStackIcon,
  TriangleAlert as ExclamationTriangleIcon,
  Folder as FolderIcon,
  ShieldCheck as ShieldCheckIcon,
  Table as TableCellsIcon,
} from '@lucide/vue'
import BackupStatusesList from '@/Components/backup-statuses-list.vue'
import Backups from '@/Components/backups.vue'
import Layout from '@/Shared/Layouts/Layout.vue'

defineOptions({ layout: Layout })

defineProps({
  record: { type: Object, default: () => ({}) },
})

const activeDisk = ref('')
const activeDiskBackups = ref([])
const backupStatuses = ref([])
const loadError = ref(false)
const lastUpdated = ref('A aguardar dados')
const poller = ref(null)

const disks = computed(() => backupStatuses.value.map((status) => status.disk))
const form = useForm({ option: '' })

async function getJson(url) {
  const response = await fetch(url, {
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
  })

  if (!response.ok) {
    throw new Error(`Cópia de segurança estado request failed with ${response.status}`)
  }

  return response.json()
}

async function updateBackupStatuses() {
  const data = await getJson(route('systembackups.statuses'))
  backupStatuses.value = Array.isArray(data) ? data : []

  if (!disks.value.includes(activeDisk.value)) {
    activeDisk.value = disks.value[0] || ''
  }
}

async function updateActiveDiskBackups() {
  if (!activeDisk.value) {
    activeDiskBackups.value = []
    return
  }

  const data = await getJson(route('systembackups.index', { disk: activeDisk.value }))
  activeDiskBackups.value = Array.isArray(data) ? data : []
}

async function refreshBackupData() {
  try {
    await updateBackupStatuses()
    await updateActiveDiskBackups()
    loadError.value = false
    lastUpdated.value = new Intl.DateTimeFormat('pt-PT', {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
    }).format(new Date())
  } catch {
    loadError.value = true
  }
}

function createBackup() {
  form.option = ''
  form.post(route('systembackups.create'), mutationOptions())
}

function createPartialBackup(option) {
  form.option = option
  form.post(route('systembackups.create', { option }), mutationOptions())
}

function deleteBackup(backup) {
  form.delete(route('systembackups.destroy', { disk: backup.disk, path: backup.path }), mutationOptions())
}

function mutationOptions() {
  return {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      refreshBackupData()
    },
  }
}

watch(activeDisk, async (disk, previousDisk) => {
  if (!disk || !previousDisk || disk === previousDisk) return

  try {
    await updateActiveDiskBackups()
    loadError.value = false
  } catch {
    loadError.value = true
  }
})

onMounted(() => {
  refreshBackupData()
  poller.value = window.setInterval(refreshBackupData, 30 * 1000)
})

onBeforeUnmount(() => {
  if (poller.value) {
    window.clearInterval(poller.value)
  }
})
</script>
