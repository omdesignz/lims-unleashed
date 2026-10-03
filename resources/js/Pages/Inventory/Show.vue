<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import NextStepBar from "@/Components/plano/NextStepBar.vue";
import StatusChip from "@/Components/plano/StatusChip.vue";
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

defineOptions({ layout: Layout });

/**
 * One stock position (an item in one warehouse) as a Plano dossier. The balance is
 * read-only here: it moves through adjustments, consumptions and transfers recorded
 * on the item dossier.
 */
const props = defineProps({
  record: { type: Object, default: () => ({ data: {} }) },
});

const position = computed(() => props.record?.data ?? {});
const quantity = computed(() => Number(position.value.qty_available ?? 0));
const reorderPoint = computed(() => Number(position.value.reorder_point ?? 0));
const stockStatus = computed(() => {
  if (quantity.value <= 0) {
    return { label: "Sem existências", tone: "bad" };
  }

  if (quantity.value <= reorderPoint.value) {
    return { label: "Reposição necessária", tone: "wait" };
  }

  return { label: "Disponível", tone: "ok" };
});
const balance = computed(() => [
  { label: "Disponível", value: quantity.value },
  { label: "Nível mínimo", value: Number(position.value.min_stock_level ?? 0) },
  { label: "Ponto de reposição", value: reorderPoint.value },
  { label: "Acima da reposição", value: Math.max(0, quantity.value - reorderPoint.value) },
]);
const identity = computed(() => [
  ["Item", position.value.item],
  ["Armazém", position.value.warehouse],
  ["Categoria", position.value.category],
]);
const nextStepText = computed(() => {
  const access = position.value.can_open_item ? "" : " Consultar o dossier do item exige permissão de consulta do catálogo.";

  if (quantity.value <= 0) {
    return `Sem existências neste armazém. A entrada regista-se no dossier do item, por pedido de compra ou ajuste.${access}`;
  }

  if (quantity.value <= reorderPoint.value) {
    return `Saldo de ${quantity.value} no ponto de reposição (${reorderPoint.value}) ou abaixo. Abra um pedido de compra a partir do dossier do item.${access}`;
  }

  return `Saldo acima do ponto de reposição. Ajustes, consumos e transferências registam-se no dossier do item.${access}`;
});
</script>

<template>
  <div class="pl-page" data-template="dossier">
    <PageHeader
      :crumbs="[{ title: 'Inventário' }, { title: 'Existências por armazém', url: route('inventory.index') }, { title: `Posição #${position.id}` }]"
      :title="position.item || 'Item de inventário'"
      :lede="`${position.warehouse || 'Armazém por identificar'} · ${quantity} disponíveis, reposição a ${reorderPoint}.`"
    >
      <template #badges><StatusChip :tone="stockStatus.tone">{{ stockStatus.label }}</StatusChip></template>
    </PageHeader>

    <div class="pl-dossier-grid">
      <div class="grid min-w-0 content-start gap-7">
        <section class="pl-panel" aria-labelledby="position-balance-title">
          <div class="pl-panel-head">
            <h2 id="position-balance-title" class="pl-k">Saldo e limites</h2>
            <span class="pl-k pl-faint">Só leitura</span>
          </div>
          <dl class="pl-facts pl-facts-2">
            <div v-for="metric in balance" :key="metric.label" class="pl-fact"><dt>{{ metric.label }}</dt><dd class="pl-num">{{ metric.value }}</dd></div>
          </dl>
          <p class="border-t border-[var(--pl-line)] p-4 text-sm text-[var(--pl-muted)]">
            Os limites alimentam os alertas de reposição. O saldo muda apenas por movimentos com motivo, registados no dossier do item.
          </p>
        </section>
      </div>

      <aside class="grid min-w-0 content-start gap-7">
        <section class="pl-panel" aria-labelledby="position-identity-title">
          <div class="pl-panel-head">
            <h2 id="position-identity-title" class="pl-k">Identificação da posição</h2>
            <span class="pl-k pl-faint pl-num">#{{ position.id }}</span>
          </div>
          <dl class="pl-facts">
            <div v-for="[label, value] in identity" :key="label" class="pl-fact"><dt>{{ label }}</dt><dd>{{ value || "—" }}</dd></div>
          </dl>
        </section>
      </aside>
    </div>

    <NextStepBar>
      {{ nextStepText }}
      <template #actions>
        <Link :href="route('inventory.index')" class="ds-button ds-button-quiet">Voltar ao registo</Link>
        <Link
          v-if="position.can_open_item"
          :href="route('vap-inventory.items.show', { item: position.item_id })"
          class="ds-button ds-button-primary"
        >
          Abrir dossier do item
        </Link>
      </template>
    </NextStepBar>
  </div>
</template>
