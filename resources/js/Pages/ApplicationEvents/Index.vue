<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import { router } from "@inertiajs/vue3";
import {
  RefreshCw as ArrowPathIcon,
  Zap as BoltIcon,
  CircleCheck as CheckCircleIcon,
  Mail as EnvelopeIcon,
  Link as LinkIcon,
} from "@lucide/vue";
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
  <div class="pl-page space-y-6">
    <PageHeader title="Eventos da aplicação" lede="Associe eventos do LIMS aos modelos de correio electrónico usados nas notificações transacionais.">
      <template #actions>
        <button type="button" class="ds-button ds-button-secondary" :disabled="syncing" @click="syncEvents">
          <ArrowPathIcon class="h-4 w-4" :class="syncing ? 'animate-spin' : ''" />
          {{ syncing ? "A sincronizar..." : "Sincronizar eventos" }}
        </button>
      </template>
    </PageHeader>

    <dl class="pl-cells">
      <div v-for="metric in metrics" :key="metric.label" class="pl-cell">
        <dt class="pl-k pl-muted">{{ metric.label }}</dt>
        <dd class="pl-cell-value">{{ metric.value }}</dd>
      </div>
    </dl>

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
              <th class="px-4 py-3 text-left text-xs font-bold uppercase text-[var(--ds-text-soft)] sm:px-5">Modelo actual</th>
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
                    <option value="">Seleccionar modelo</option>
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
