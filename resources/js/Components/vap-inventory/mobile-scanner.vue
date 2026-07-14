<template>
  <div class="mx-auto min-h-screen max-w-md bg-[var(--ds-canvas)] p-4">
    <header class="mb-5 flex items-center justify-between">
      <div>
        <p class="ds-kicker">Controlo móvel de existências</p>
        <h1 class="ds-heading mt-1 text-xl">VAP LabScan</h1>
      </div>
      <span class="ds-chip">
        <span class="lims-status-dot lims-status-dot-release"></span>
        Online
      </span>
    </header>

    <section v-if="!scannedBatch" class="ds-empty-state border-dashed p-8 text-center">
      <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)]">
        <QrCodeIcon class="h-10 w-10 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
      </div>
      <h2 class="mt-4 text-base font-bold text-[var(--ds-text)]">Ler lote de reagente</h2>
      <p class="mt-2 text-sm font-medium text-[var(--ds-text-muted)]">
        Posicione o código QR na moldura ou use um scanner de bancada.
      </p>

      <BaseInput
        ref="barcodeInput"
        v-model="manualInput"
        class="absolute opacity-0 pointer-events-none"
        autofocus
        @keyup.enter="handleManualScan"
      />

      <button type="button" class="ds-button ds-button-primary mt-6 w-full" @click="initCamera">
        <QrCodeIcon class="h-4 w-4" />
        Abrir câmera
      </button>
    </section>

    <section v-else class="space-y-4">
      <div class="ds-panel overflow-hidden p-5">
        <div class="flex items-start justify-between gap-4">
          <div class="min-w-0">
            <p class="ds-kicker">Lote identificado</p>
            <h2 class="mt-2 truncate text-lg font-bold text-[var(--ds-text)]">{{ scannedBatch.item_name }}</h2>
            <p class="mt-1 font-mono text-xs font-semibold text-[var(--ds-text-soft)]">Lote: {{ scannedBatch.batch_number }}</p>
          </div>
          <button type="button" class="ds-icon-button" @click="resetScan">
            <XMarkIcon class="h-4 w-4" />
          </button>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-3">
          <div class="ds-card p-3">
            <p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Disponível</p>
            <p class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ scannedBatch.qty_remaining }}</p>
          </div>
          <div class="ds-card p-3">
            <p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Validade</p>
            <p class="mt-2 text-sm font-bold" :class="scannedBatch.is_expired ? 'text-rose-700 dark:text-rose-300' : 'text-emerald-700 dark:text-emerald-300'">
              {{ scannedBatch.expiry_date }}
            </p>
          </div>
        </div>

        <label class="ds-field-group mt-5">
          <span class="ds-field-label">Quantidade a remover</span>
          <BaseInput v-model="form.qty" type="number" step="0.01" class="ds-field text-lg font-bold" />
        </label>

        <div class="mt-5 grid gap-2">
          <button type="button" class="ds-button ds-button-danger w-full" :disabled="loading" @click="submitAction('consumption')">
            <ArrowPathIcon v-if="loading" class="h-4 w-4 animate-spin" />
            Registrar uso
          </button>
          <button type="button" class="ds-button ds-button-primary w-full" :disabled="loading" @click="submitAction('transfer')">
            Transferir armazém
          </button>
          <button type="button" class="ds-button ds-button-secondary w-full" :disabled="loading" @click="submitAudit">
            <CheckBadgeIcon class="h-4 w-4" />
            Auditar existências
          </button>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ArrowPathIcon, CheckBadgeIcon, QrCodeIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { onMounted, reactive, ref } from 'vue'
import { useToast } from 'vue-toastification'

const toast = useToast()
const manualInput = ref('')
const barcodeInput = ref(null)
const scannedBatch = ref(null)
const loading = ref(false)
const form = reactive({
  qty: 1,
  batch_id: null,
})

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? ''
}

async function requestJson(url, options = {}) {
  const response = await fetch(url, {
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken(),
      ...(options.headers ?? {}),
    },
    ...options,
  })

  if (!response.ok) {
    throw new Error('Request failed')
  }

  return response.json()
}

async function handleManualScan() {
  try {
    const response = await requestJson(route('inventory.batches.lookup', { id: manualInput.value }))
    scannedBatch.value = response
    form.batch_id = response.id
    manualInput.value = ''
  } catch {
    toast.error('Lote não encontrado ou código inválido.')
    manualInput.value = ''
  }
}

async function submitAction(type) {
  loading.value = true

  try {
    await requestJson(route('inventory.batches.mobile-action'), {
      method: 'POST',
      body: JSON.stringify({ ...form, type }),
    })
    toast.success('Transacção registada.')
    resetScan()
  } catch {
    toast.error('Não foi possível processar a transacção.')
  } finally {
    loading.value = false
  }
}

function resetScan() {
  scannedBatch.value = null
  form.qty = 1
  form.batch_id = null
  barcodeInput.value?.focus()
}

async function submitAudit() {
  if (!scannedBatch.value) {
    return
  }

  loading.value = true

  try {
    await requestJson(route('inventory.batches.audit'), {
      method: 'POST',
      body: JSON.stringify({
        batch_id: scannedBatch.value.id,
        physical_qty: form.qty,
        system_qty: scannedBatch.value.qty_remaining,
      }),
    })
    toast.success('Auditoria concluída.')
    resetScan()
  } catch {
    toast.error('Não foi possível gravar a auditoria.')
  } finally {
    loading.value = false
  }
}

function initCamera() {
  barcodeInput.value?.focus()
}

onMounted(() => {
  barcodeInput.value?.focus()
})
</script>
