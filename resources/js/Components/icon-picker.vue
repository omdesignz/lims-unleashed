<script setup>
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { CheckIcon, MagnifyingGlassIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import * as OutlinedIcons from '@heroicons/vue/24/outline'
import { computed, ref } from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['icon-selected', 'picker-closed', 'update:modelValue'])
const query = ref('')

const iconLabels = {
  ArchiveBoxIcon: 'Arquivo',
  BeakerIcon: 'Laboratório',
  BellAlertIcon: 'Alertas',
  BookOpenIcon: 'Procedimentos',
  BuildingLibraryIcon: 'Normas',
  BuildingOffice2Icon: 'Organização',
  CalculatorIcon: 'Cálculos',
  CalendarDaysIcon: 'Calendário',
  ChartBarIcon: 'Indicadores',
  CheckBadgeIcon: 'Aprovação',
  CircleStackIcon: 'Inventário',
  ClipboardDocumentListIcon: 'Lista de controlo',
  ClockIcon: 'Prazos',
  CpuChipIcon: 'Equipamento',
  CubeIcon: 'Amostras',
  DocumentCheckIcon: 'Certificados',
  DocumentTextIcon: 'Documentos',
  EnvelopeIcon: 'Mensagens',
  ExclamationTriangleIcon: 'Ocorrências',
  FolderIcon: 'Pastas',
  GlobeAltIcon: 'Portal',
  HomeModernIcon: 'Instalações',
  ListBulletIcon: 'Listas',
  MagnifyingGlassIcon: 'Pesquisa',
  MapPinIcon: 'Locais',
  PhotoIcon: 'Multimédia',
  PresentationChartLineIcon: 'Relatórios',
  QrCodeIcon: 'Código QR',
  QueueListIcon: 'Filas',
  ScaleIcon: 'Metrologia',
  ServerStackIcon: 'Sistemas',
  ShieldCheckIcon: 'Qualidade',
  ShoppingCartIcon: 'Compras',
  SwatchIcon: 'Identidade visual',
  TableCellsIcon: 'Tabelas',
  TagIcon: 'Etiquetas',
  TruckIcon: 'Logística',
  UserGroupIcon: 'Equipa',
  UsersIcon: 'Utilizadores',
  WrenchScrewdriverIcon: 'Manutenção',
}

const iconOptions = Object.entries(iconLabels)
  .filter(([name]) => OutlinedIcons[name])
  .map(([name, label]) => ({
    name,
    label,
    component: OutlinedIcons[name],
  }))
  .sort((firstIcon, secondIcon) => firstIcon.label.localeCompare(secondIcon.label, 'pt'))

const filteredIconOptions = computed(() => {
  const normalizedQuery = query.value.trim().toLowerCase()

  if (!normalizedQuery) {
    return iconOptions
  }

  return iconOptions.filter((icon) => {
    const searchIndex = `${icon.label} ${icon.name}`.toLocaleLowerCase('pt')

    return searchIndex.includes(normalizedQuery)
  })
})

function closePicker() {
  emit('picker-closed')
}

function selectIcon(iconName) {
  emit('update:modelValue', iconName)
  emit('icon-selected', iconName)
  closePicker()
}
</script>

<template>
  <Teleport to="body">
    <TransitionRoot appear as="template" :show="true">
      <Dialog as="div" class="relative z-[90]" @close="closePicker">
      <TransitionChild
        as="template"
        enter="ease-out duration-200"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-150"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="ds-modal-backdrop fixed inset-0 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto p-4 sm:p-6">
        <div class="flex min-h-full items-end justify-center sm:items-center">
          <TransitionChild
            as="template"
            enter="ease-out duration-200"
            enter-from="translate-y-3 opacity-0 sm:translate-y-0 sm:scale-95"
            enter-to="translate-y-0 opacity-100 sm:scale-100"
            leave="ease-in duration-150"
            leave-from="translate-y-0 opacity-100 sm:scale-100"
            leave-to="translate-y-3 opacity-0 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="ds-modal-panel relative flex max-h-[min(44rem,calc(100vh-2rem))] w-full max-w-3xl transform flex-col overflow-hidden">
              <header class="flex items-start justify-between gap-4 border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
                <div class="min-w-0">
                  <p class="ds-kicker">Configuração visual</p>
                  <DialogTitle class="ds-heading mt-1 text-lg">Seleccionar ícone do quadro</DialogTitle>
                  <p class="ds-copy mt-1 text-sm">Escolha um símbolo reconhecível para identificar este fluxo de trabalho.</p>
                </div>
                <button
                  type="button"
                  class="ds-icon-button shrink-0"
                  aria-label="Fechar seletor de ícones"
                  title="Fechar"
                  @click="closePicker"
                >
                  <XMarkIcon class="h-5 w-5" />
                </button>
              </header>

              <div class="border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:px-6">
                <label for="icon-search" class="ds-field-label">Pesquisar ícones</label>
                <div class="relative mt-2">
                  <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" />
                  <BaseInput
                    id="icon-search"
                    v-model="query"
                    type="search"
                    class="ds-field min-h-10 py-2 pl-9"
                    placeholder="Ex.: laboratório, documentos, qualidade"
                  />
                </div>
              </div>

              <div class="flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                <div class="mb-3 flex items-center justify-between gap-3">
                  <p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">
                    {{ filteredIconOptions.length }} ícone(s)
                  </p>
                  <p v-if="props.modelValue" class="truncate text-xs font-semibold text-[var(--ds-text-muted)]">
                    Actual: {{ props.modelValue }}
                  </p>
                </div>

                <ul
                  v-if="filteredIconOptions.length"
                  role="listbox"
                  aria-label="Ícones disponíveis"
                  class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4"
                >
                  <li v-for="icon in filteredIconOptions" :key="icon.name" role="none">
                    <button
                      type="button"
                      role="option"
                      :aria-selected="icon.name === props.modelValue"
                      :title="icon.label"
                      class="group relative flex h-24 w-full flex-col items-center justify-center gap-2 rounded-lg border px-2 py-3 text-center transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[rgb(var(--primary-500-rgb))] focus-visible:ring-offset-2"
                      :class="icon.name === props.modelValue
                        ? 'border-[rgb(var(--primary-500-rgb))] bg-[rgb(var(--primary-50-rgb))] text-[rgb(var(--primary-800-rgb))] dark:bg-[rgb(var(--primary-950-rgb))] dark:text-[rgb(var(--primary-200-rgb))]'
                        : 'border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[var(--ds-text-muted)] hover:border-[var(--ds-border-strong)] hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)]'"
                      @click="selectIcon(icon.name)"
                    >
                      <component :is="icon.component" class="h-6 w-6 shrink-0" aria-hidden="true" />
                      <span class="w-full truncate text-[11px] font-bold">{{ icon.label }}</span>
                      <span
                        v-if="icon.name === props.modelValue"
                        class="absolute right-2 top-2 grid h-5 w-5 place-items-center rounded-full bg-[rgb(var(--primary-600-rgb))] text-white"
                      >
                        <CheckIcon class="h-3.5 w-3.5" />
                      </span>
                    </button>
                  </li>
                </ul>

                <div v-else class="ds-empty-state py-10 text-center">
                  <MagnifyingGlassIcon class="mx-auto h-6 w-6 text-[var(--ds-text-soft)]" />
                  <p class="mt-3 text-sm font-bold text-[var(--ds-text)]">Nenhum ícone encontrado</p>
                  <p class="ds-copy mt-1 text-xs">Experimente outro termo de pesquisa.</p>
                </div>
              </div>

              <footer class="flex justify-end border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:px-6">
                <button type="button" class="ds-button ds-button-secondary" @click="closePicker">Cancelar</button>
              </footer>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
      </Dialog>
    </TransitionRoot>
  </Teleport>
</template>
