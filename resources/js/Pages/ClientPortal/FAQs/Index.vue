<script setup>
import Pagination from "@/Components/pagination.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import PortalLayout from "@/Shared/Layouts/PortalLayout.vue";
import { Disclosure, DisclosureButton, DisclosurePanel } from "@headlessui/vue";
import { Link, router } from "@inertiajs/vue3";
import { MessagesSquare as ChatBubbleLeftRightIcon, ChevronDown as ChevronDownIcon, Search as MagnifyingGlassIcon, Plus as PlusIcon, CircleHelp as QuestionMarkCircleIcon, Tag as TagIcon } from "@lucide/vue";
import { computed, ref } from "vue";

defineOptions({ layout: PortalLayout });

const props = defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  query: { type: Object, default: () => ({}) },
});

const search = ref(props.query?.search || "");
const category = ref(props.query?.category || "");
const questions = computed(() => props.record?.data || []);
const categories = computed(() => {
  const values = new Map();
  questions.value.forEach((faq) => {
    if (faq.category_id && faq.category) {
      values.set(String(faq.category_id), faq.category);
    }
  });

  return [...values].map(([value, label]) => ({ value, label }));
});
const answeredCount = computed(() => questions.value.filter((faq) => faq.answers?.length).length);

function applyFilters() {
  router.get(route("portal.faqs"), {
    search: search.value.trim() || undefined,
    category: category.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}
</script>

<template>
  <div class="space-y-6">
    <PageHeader title="Perguntas frequentes" lede="Respostas sobre colheitas, análises, certificados, documentos e facturação.">
      <template #actions>
        <Link :href="route('portal.requests.index', { new: 1, request_type: 'general_support', title: 'Pedido de ajuda' })" class="ds-button ds-button-primary"><PlusIcon class="h-4 w-4" />Pedir ajuda</Link>
      </template>
    </PageHeader>

    <dl class="pl-cells">
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Perguntas nesta página</dt>
        <dd class="pl-cell-value">{{ questions.length }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Com resposta</dt>
        <dd class="pl-cell-value">{{ answeredCount }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Categorias visíveis</dt>
        <dd class="pl-cell-value">{{ categories.length }}</dd>
      </div>
    </dl>

    <section class="ds-card overflow-hidden">
      <form class="grid gap-4 px-5 py-5 sm:grid-cols-[minmax(0,1fr)_14rem_auto] sm:items-end sm:px-6" role="search" @submit.prevent="applyFilters">
        <div class="ds-field-group"><label class="ds-field-label">Pesquisar</label><div class="relative"><MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-[var(--ds-text-soft)]" /><BaseInput v-model="search" type="search" class="ds-field pl-10" placeholder="Escreva uma pergunta ou tema" /></div></div>
        <div class="ds-field-group"><label class="ds-field-label">Categoria</label><BaseSelect v-model="category" class="ds-field"><option value="">Todas</option><option v-for="item in categories" :key="item.value" :value="item.value">{{ item.label }}</option></BaseSelect></div>
        <button type="submit" class="ds-button ds-button-secondary">Aplicar</button>
      </form>
    </section>

    <section class="ds-card overflow-hidden">
      <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6"><h2 class="text-base font-bold text-[var(--ds-text)]">Respostas</h2><p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">Abra uma pergunta para consultar a orientação disponível.</p></header>
      <div v-if="questions.length" class="divide-y divide-[var(--ds-border)]">
        <Disclosure v-for="faq in questions" :key="faq.id" v-slot="{ open }" as="div">
          <DisclosureButton class="flex w-full items-start justify-between gap-4 px-5 py-4 text-left hover:bg-[var(--ds-panel-subtle)] focus:outline-none focus-visible:ring-4 focus-visible:ring-[var(--ds-focus)] sm:px-6">
            <div class="min-w-0"><h3 class="break-words text-sm font-bold text-[var(--ds-text)]">{{ faq.description }}</h3><span v-if="faq.category" class="ds-chip mt-2"><TagIcon class="h-3.5 w-3.5" />{{ faq.category }}</span></div>
            <ChevronDownIcon :class="['h-5 w-5 shrink-0 text-[var(--ds-text-soft)] transition', open ? 'rotate-180' : '']" />
          </DisclosureButton>
          <DisclosurePanel class="border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-5 sm:px-6">
            <div v-if="faq.answers?.length" class="space-y-4"><div v-for="answer in faq.answers" :key="answer.id" class="flex items-start gap-3"><ChatBubbleLeftRightIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))]" /><p class="whitespace-pre-line text-sm font-medium leading-6 text-[var(--ds-text-muted)]">{{ answer.description }}</p></div></div>
            <p v-else class="text-sm font-semibold text-[var(--ds-text-muted)]">A resposta ainda não esta publicada. Abra um pedido de apoio para acompanhamento.</p>
          </DisclosurePanel>
        </Disclosure>
      </div>
      <div v-else class="ds-empty-state m-5 py-12 text-center sm:m-6"><QuestionMarkCircleIcon class="mx-auto h-9 w-9 text-[var(--ds-text-soft)]" /><h3 class="mt-3 text-sm font-bold text-[var(--ds-text)]">Nenhuma pergunta encontrada</h3><p class="ds-copy mx-auto mt-1 max-w-md text-sm">Ajuste os filtros ou abra um pedido de ajuda.</p></div>
      <Pagination v-if="record.meta" v-bind="record.meta" />
    </section>
  </div>
</template>
