<template>
  <div class="mb-8 mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
    <button
      v-for="card in cards"
      :key="card.type"
      type="button"
      class="ds-card border-l-4 p-4 text-left transition hover:border-[rgb(var(--primary-500-rgb))]"
      :class="card.borderClass"
      @click="goTo(card.type)"
    >
      <div class="flex items-center">
        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-[var(--ds-panel-subtle)]">
          <component :is="card.icon" class="h-6 w-6" :class="card.iconClass" />
        </span>
        <span class="ml-4 min-w-0">
          <span class="block text-sm font-bold uppercase text-[var(--ds-text-soft)]">{{ card.label }}</span>
          <span class="mt-1 block text-2xl font-bold text-[var(--ds-text)]">
            {{ card.value }}
            <span class="text-sm font-semibold text-[var(--ds-text-soft)]">{{ card.unit }}</span>
          </span>
        </span>
      </div>
      <span class="mt-2 block text-xs font-bold" :class="card.iconClass">{{ card.caption }} -></span>
    </button>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import { ExclamationTriangleIcon, ShoppingCartIcon, TruckIcon } from '@heroicons/vue/24/outline'
import { computed, onMounted, ref } from 'vue'

const props = defineProps({
  summary: {
    type: Object,
    default: () => ({}),
  },
})

const summary = ref(props.summary)

const cards = computed(() => [
  {
    type: 'critical',
    label: 'Problemas críticos',
    value: summary.value.critical ?? 0,
    unit: 'Itens',
    caption: 'Lotes ou reagentes vencidos',
    icon: ExclamationTriangleIcon,
    borderClass: 'border-rose-500',
    iconClass: 'text-rose-700 dark:text-rose-300',
  },
  {
    type: 'reorder',
    label: 'Pedidos pendentes',
    value: summary.value.toOrder ?? 0,
    unit: 'Existências reduzidas',
    caption: 'Gerar rascunhos agora',
    icon: ShoppingCartIcon,
    borderClass: 'border-amber-500',
    iconClass: 'text-amber-700 dark:text-amber-300',
  },
  {
    type: 'orders',
    label: 'Envios atrasados',
    value: summary.value.delayed ?? 0,
    unit: 'Pedidos',
    caption: 'Seguir com fornecedores',
    icon: TruckIcon,
    borderClass: 'border-[rgb(var(--primary-500-rgb))]',
    iconClass: 'text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200',
  },
])

function goTo(type) {
  const routes = {
    critical: route('vap-inventory.items.index', { filter: 'critical' }),
    reorder: route('vap-inventory.items.index', { filter: 'low_stock' }),
    orders: route('vap-inventory.orders.index', { status: 'delayed' }),
  }

  router.visit(routes[type])
}

async function fetchSummary() {
  try {
    const response = await fetch(route('vap-inventory.analytics.summary'), {
      headers: { Accept: 'application/json' },
    })

    if (response.ok) {
      summary.value = await response.json()
    }
  } catch {
    // Keep server-provided summary when the live refresh is unavailable.
  }
}

onMounted(() => {
  fetchSummary()
})
</script>
