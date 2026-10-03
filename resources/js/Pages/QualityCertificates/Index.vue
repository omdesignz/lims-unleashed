<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import Pagination from "@/Components/pagination.vue";
import confirmDialog from "@/Components/confirm-dialog.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import StateCells from "@/Components/plano/StateCells.vue";
import StatusChip from "@/Components/plano/StatusChip.vue";
import { usePermission } from "@/Composables/usePermissions";
import { computed, ref } from "vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { FileDown as DocumentArrowDownIcon, X as XMarkIcon } from "@lucide/vue";

defineOptions({ layout: Layout });

/**
 * Certificates register (Plano queue). Certificates are created only from approved
 * results in the laboratory workflow; this register opens, downloads and archives
 * them. A validated certificate is an issued document and is never archived.
 */
const props = defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  counts: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
});

const { hasPermission } = usePermission();
const filter = useForm({ search: props.filters.search ?? "", state: props.filters.state ?? "" });
const pendingArchive = ref(null);
const archiving = ref(false);

const cells = computed(() => [
  { key: "", label: "Todos", value: props.counts.all ?? 0 },
  { key: "pending", label: "Por validar", value: props.counts.pending ?? 0, tone: props.counts.pending ? "bad" : undefined },
  { key: "validated", label: "Validados", value: props.counts.validated ?? 0 },
  { key: "archived", label: "Arquivados", value: props.counts.archived ?? 0 },
]);

const lede = computed(() => {
  const pending = props.counts.pending ?? 0;

  return pending
    ? `${pending} ${pending === 1 ? "boletim espera" : "boletins esperam"} validação. Os boletins nascem dos resultados aprovados no fluxo laboratorial.`
    : "Nenhum boletim espera validação. Os boletins nascem dos resultados aprovados no fluxo laboratorial.";
});

const formatDate = (value) => (value
  ? new Intl.DateTimeFormat("pt-AO", { day: "2-digit", month: "short", year: "numeric" }).format(new Date(value))
  : "—");

function search(state = filter.state) {
  filter.state = state;
  filter.get(route("qualitycertificates.index"), { preserveState: true, preserveScroll: true, replace: true });
}

function clearFilters() {
  filter.search = "";
  search("");
}

function archive(certificate) {
  const restoring = certificate.deleted;
  archiving.value = true;
  router.visit(route(restoring ? "qualitycertificates.restore" : "qualitycertificates.destroy"), {
    method: restoring ? "patch" : "delete",
    data: { recordIds: [certificate.id] },
    preserveScroll: true,
    onFinish: () => {
      archiving.value = false;
      pendingArchive.value = null;
    },
  });
}
</script>

<template>
  <div class="pl-page" data-template="queue">
    <PageHeader :crumbs="[{ title: 'Certificados' }, { title: 'Registo' }]" title="Certificados de qualidade" :lede="lede">
      <template #actions>
        <Link :href="route('laboratory-workflow.index', { stage: 'report_generation' })" class="ds-button ds-button-quiet">Gerar a partir do fluxo</Link>
      </template>
    </PageHeader>

    <StateCells class="mb-10" :items="cells" :model-value="filter.state" label="Filtrar estado dos boletins" @update:model-value="search($event)" />

    <form class="pl-filter" @submit.prevent="search()">
      <label for="certificate-search" class="pl-filter-prompt">Filtro://</label>
      <BaseInput id="certificate-search" v-model="filter.search" type="search" data-bare class="pl-filter-input" maxlength="100" placeholder="boletim, código laboratorial, cliente, produto" />
      <button class="ds-button ds-button-quiet" type="submit" :disabled="filter.processing">{{ filter.processing ? "A procurar…" : "Procurar" }}</button>
      <button v-if="filter.search || filter.state" class="ds-chip" type="button" @click="clearFilters">Limpar filtros <XMarkIcon class="h-3.5 w-3.5" aria-hidden="true" /></button>
    </form>
    <p v-if="filter.hasErrors" class="ds-field-error mb-3" role="alert">{{ Object.values(filter.errors)[0] }}</p>

    <section class="pl-panel" aria-label="Boletins" :aria-busy="filter.processing">
      <DataTable v-if="record.data.length">
        <thead>
          <tr>
            <th scope="col">Boletim</th>
            <th scope="col">Código laboratorial</th>
            <th scope="col">Cliente</th>
            <th scope="col">Produto</th>
            <th scope="col">Estado</th>
            <th scope="col" class="text-right">Emissão</th>
            <th scope="col"><span class="sr-only">Acções</span></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="certificate in record.data" :key="certificate.id">
            <td><Link :href="certificate.links.show_path" class="pl-num font-medium hover:text-[var(--pl-accent-text)]">{{ certificate.code || `#${certificate.id}` }}</Link></td>
            <td class="pl-num">{{ certificate.lab_code || "—" }}</td>
            <td>{{ certificate.customer || "Não associado" }}<span v-if="certificate.warehouse" class="block text-[12.5px] text-[var(--pl-muted)]">{{ certificate.warehouse }}</span></td>
            <td>{{ certificate.product || "—" }}</td>
            <td>
              <StatusChip v-if="certificate.deleted" tone="neutral">Arquivado</StatusChip>
              <StatusChip v-else-if="certificate.validated_at" tone="ok">Validado</StatusChip>
              <StatusChip v-else tone="wait">Por validar</StatusChip>
              <span v-if="certificate.validated_at" class="block pt-1 text-[12px] text-[var(--pl-muted)]">{{ certificate.validated_by_user }}</span>
            </td>
            <td class="pl-num text-right">{{ formatDate(certificate.validated_at || certificate.created_at) }}</td>
            <td class="text-right">
              <div class="flex justify-end gap-1">
                <a :href="certificate.links.pdf_path" target="_blank" rel="noopener" class="ds-table-action" :aria-label="`PDF do boletim ${certificate.code}`"><DocumentArrowDownIcon class="h-4 w-4" aria-hidden="true" /></a>
                <button
                  v-if="!certificate.validated_at && hasPermission(certificate.deleted ? 'restore_quality_certificates' : 'delete_quality_certificates')"
                  type="button"
                  class="ds-table-action"
                  :class="{ 'ds-table-action-danger': !certificate.deleted }"
                  :disabled="archiving"
                  @click="pendingArchive = certificate"
                >{{ certificate.deleted ? "Restaurar" : "Arquivar" }}</button>
              </div>
            </td>
          </tr>
        </tbody>
      </DataTable>
      <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
        <span class="pl-k">{{ filter.search || filter.state ? "Nenhum boletim neste filtro" : "Ainda não há boletins" }}</span>
        <p class="text-sm text-[var(--pl-muted)]">{{ filter.search || filter.state ? "Experimente outro termo ou estado." : "Quando todos os resultados de uma amostra forem aprovados, gere o boletim no fluxo laboratorial." }}</p>
      </div>
      <Pagination v-if="record.meta?.total" v-bind="record.meta" />
    </section>

    <confirm-dialog
      v-if="pendingArchive"
      :open="true"
      :variant="pendingArchive.deleted ? 'question' : 'danger'"
      :title="pendingArchive.deleted ? 'Restaurar boletim?' : 'Arquivar boletim?'"
      :description="pendingArchive.deleted ? 'O boletim volta ao registo e ao fluxo laboratorial.' : 'O boletim sai do registo activo até ser restaurado. Só boletins por validar podem ser arquivados.'"
      :confirm="pendingArchive.deleted ? 'Restaurar' : 'Arquivar'"
      cancel="Manter"
      :disabled="archiving"
      keep-open-on-confirm
      @canceled="pendingArchive = null"
      @confirmed="archive(pendingArchive)"
    />
  </div>
</template>
