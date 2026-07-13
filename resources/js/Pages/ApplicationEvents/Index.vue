<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { router } from "@inertiajs/vue3";
import {
  ArrowPathIcon,
  BoltIcon,
  CheckCircleIcon,
  EnvelopeIcon,
  LinkIcon,
} from "@heroicons/vue/24/outline";
import { computed, reactive, ref } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  events: { type: Array, default: () => [] },
  templates: { type: Array, default: () => [] },
});

const selectedTemplates = reactive(Object.fromEntries(
  props.events.map((event) => [event.id, event.email_template?.id ?? ""]),
));
const associatingEventId = ref(null);
const syncing = ref(false);
const linkedEvents = computed(() => props.events.filter((event) => event.email_template).length);
const metrics = computed(() => [
  { label: "Eventos", value: props.events.length, detail: "eventos descobertos", icon: BoltIcon },
  { label: "Associados", value: linkedEvents.value, detail: "com modelo definido", icon: LinkIcon },
  { label: "Sem modelo", value: props.events.length - linkedEvents.value, detail: "requerem configuração", icon: EnvelopeIcon },
  { label: "Modelos", value: props.templates.length, detail: "modelos disponíveis", icon: CheckCircleIcon },
]);

function eventLabel(name) {
  return name?.replace(/^App\\Events\\/, "") ?? "Evento sem nome";
}

function syncEvents() {
  syncing.value = true;
  router.get(route("app-events.sync"), {}, {
    preserveScroll: true,
    onFinish: () => {
      syncing.value = false;
    },
  });
}

function associateTemplate(eventId) {
  if (!selectedTemplates[eventId]) {
    return;
  }

  associatingEventId.value = eventId;
  router.post(route("app-events.associate", { event: eventId }), {
    email_template_id: selectedTemplates[eventId],
  }, {
    preserveScroll: true,
    onFinish: () => {
      associatingEventId.value = null;
    },
  });
}
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <BoltIcon class="h-5 w-5" />
          </span>
          <div>
            <p class="ds-kicker">Automação de notificações</p>
            <h1 class="ds-heading mt-1 text-2xl">Eventos da aplicação</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">Associe eventos do LIMS aos modelos de email usados nas notificações transacionais.</p>
          </div>
        </div>
        <button type="button" class="ds-button ds-button-secondary" :disabled="syncing" @click="syncEvents">
          <ArrowPathIcon class="h-4 w-4" :class="syncing ? 'animate-spin' : ''" />
          {{ syncing ? "A sincronizar..." : "Sincronizar eventos" }}
        </button>
      </div>

      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="metric in metrics" :key="metric.label" class="border-b border-[var(--ds-border)] px-4 py-3 sm:[&:nth-child(odd)]:border-r sm:[&:nth-child(n+3)]:border-b-0 xl:border-b-0 xl:border-r xl:last:border-r-0">
          <div class="flex items-start justify-between gap-3">
            <div>
              <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</dt>
              <dd class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ metric.value }}</dd>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ metric.detail }}</p>
            </div>
            <component :is="metric.icon" class="h-5 w-5 text-[var(--ds-text-soft)]" />
          </div>
        </div>
      </dl>
    </section>

    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] p-4 sm:p-5">
        <h2 class="ds-heading text-base">Mapa evento-modelo</h2>
        <p class="ds-copy mt-1 text-sm">Alterações afetam as mensagens enviadas por operações automatizadas.</p>
      </div>

      <div class="overflow-x-auto">
        <DataTable class="min-w-full divide-y divide-[var(--ds-border)] text-sm">
          <thead class="bg-[var(--ds-panel-subtle)]">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase text-[var(--ds-text-soft)] sm:px-5">Evento</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase text-[var(--ds-text-soft)] sm:px-5">Modelo atual</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase text-[var(--ds-text-soft)] sm:px-5">Associação</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--ds-border)]">
            <tr v-for="event in events" :key="event.id" class="transition-colors hover:bg-[var(--ds-panel-subtle)]">
              <td class="min-w-72 px-4 py-4 sm:px-5">
                <p class="font-bold text-[var(--ds-text)]">{{ eventLabel(event.name) }}</p>
                <p class="mt-1 font-mono text-xs text-[var(--ds-text-soft)]">{{ event.name }}</p>
              </td>
              <td class="min-w-48 px-4 py-4 sm:px-5">
                <span class="ds-badge" :class="event.email_template ? 'ds-badge-success' : 'ds-badge-warning'">
                  {{ event.email_template?.name || "Sem modelo" }}
                </span>
              </td>
              <td class="min-w-80 px-4 py-4 sm:px-5">
                <div class="flex items-center gap-2">
                  <BaseSelect v-model="selectedTemplates[event.id]" class="ds-field min-w-56">
                    <option value="">Selecionar modelo</option>
                    <option v-for="template in templates" :key="template.id" :value="template.id">{{ template.name }}</option>
                  </BaseSelect>
                  <button
                    type="button"
                    class="ds-button ds-button-primary"
                    :disabled="!selectedTemplates[event.id] || associatingEventId === event.id"
                    @click="associateTemplate(event.id)"
                  >
                    <LinkIcon class="h-4 w-4" />
                    {{ associatingEventId === event.id ? "A associar..." : "Associar" }}
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!events.length">
              <td colspan="3" class="px-5 py-12 text-center">
                <BoltIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
                <p class="ds-heading mt-3 text-sm">Nenhum evento sincronizado</p>
                <p class="ds-copy mt-1 text-sm">Sincronize o diretório de eventos para iniciar a configuração.</p>
              </td>
            </tr>
          </tbody>
        </DataTable>
      </div>
    </section>
  </div>
</template>
