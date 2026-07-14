<script setup>
import {
  ArrowDownTrayIcon,
  BeakerIcon,
  DocumentTextIcon,
  MapPinIcon,
  PlusIcon,
  TrashIcon,
  UserCircleIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
  form: { type: Object, required: true },
  services: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  profiles: { type: Array, default: () => [] },
  products: { type: Array, default: () => [] },
  matrixes: { type: Array, default: () => [] },
  packagingCategories: { type: Array, default: () => [] },
  warehouse: { type: Object, default: () => ({}) },
});

const emit = defineEmits(["request-type-change"]);

function addBatchSample() {
  props.form.details.samples.push(emptyBatchSample());
}

function removeBatchSample(index) {
  props.form.details.samples.splice(index, 1);

  if (!props.form.details.samples.length) {
    props.form.details.samples.push(emptyBatchSample());
  }
}

function addCollectionItem() {
  props.form.details.items.push({ name: "", quantity: 1, lot: "" });
}

function removeCollectionItem(index) {
  props.form.details.items.splice(index, 1);

  if (!props.form.details.items.length) {
    props.form.details.items.push({ name: "", quantity: 1, lot: "" });
  }
}

function emptyBatchSample() {
  return {
    sample_name: "",
    product_name: "",
    product_id: null,
    matrix: "",
    matrix_id: null,
    lot: "",
    packaging: "",
    packaging_id: null,
    quantity: "",
    notes: "",
  };
}

function downloadBatchTemplate() {
  const content = [
    "sample_name,product_name,matrix,lot,packaging,quantity,notes",
    '"Amostra 01","Agua mineral","Agua","L-2026-001","Garrafa esteril","2 L","Controlo de rotina"',
  ].join("\n");
  const url = URL.createObjectURL(new Blob([content], { type: "text/csv;charset=utf-8" }));
  const link = document.createElement("a");
  link.href = url;
  link.download = "template-pedido-analises.csv";
  link.click();
  URL.revokeObjectURL(url);
}

function importBatchSamples(event) {
  const file = event.target.files?.[0];

  if (!file) {
    return;
  }

  const reader = new FileReader();
  reader.onload = () => {
    const rows = parseDelimitedText(String(reader.result || ""));
    props.form.details.samples = rows.length ? rows : [emptyBatchSample()];
  };
  reader.readAsText(file);
  event.target.value = "";
}

function parseDelimitedText(text) {
  const lines = text.split(/\r?\n/).map((line) => line.trim()).filter(Boolean);

  if (lines.length < 2) {
    return [];
  }

  const delimiter = [",", ";", "\t"].sort((left, right) => lines[0].split(right).length - lines[0].split(left).length)[0];
  const headers = parseDelimitedLine(lines[0], delimiter).map((header) => header.trim());

  return lines.slice(1).map((line) => {
    const values = parseDelimitedLine(line, delimiter);
    const row = headers.reduce((result, header, index) => ({ ...result, [header]: values[index]?.trim() || "" }), {});

    return { ...emptyBatchSample(), ...row };
  }).filter((row) => row.sample_name || row.product_name || row.matrix || row.lot);
}

function parseDelimitedLine(line, delimiter) {
  const values = [];
  let value = "";
  let quoted = false;

  for (let index = 0; index < line.length; index += 1) {
    const character = line[index];

    if (character === '"') {
      if (quoted && line[index + 1] === '"') {
        value += '"';
        index += 1;
      } else {
        quoted = !quoted;
      }
    } else if (character === delimiter && !quoted) {
      values.push(value);
      value = "";
    } else {
      value += character;
    }
  }

  values.push(value);

  return values;
}
</script>

<template>
  <div class="divide-y divide-[var(--ds-border)]">
    <section class="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-[14rem_minmax(0,1fr)] lg:gap-8">
      <div><div class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]"><DocumentTextIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />Pedido</div><p class="ds-copy mt-2 text-sm">Identifique o serviço, prioridade e objectivo do pedido.</p></div>
      <div class="grid gap-4 sm:grid-cols-2">
        <div class="ds-field-group"><label class="ds-field-label">Serviço <span class="ds-field-required">*</span></label><BaseSelect v-model="form.request_type" class="ds-field" @change="emit('request-type-change')"><option v-for="service in services" :key="service.type" :value="service.type">{{ service.title }}</option></BaseSelect><p v-if="form.errors.request_type" class="ds-field-error">{{ form.errors.request_type }}</p></div>
        <div class="ds-field-group"><label class="ds-field-label">Categoria</label><BaseSelect v-model="form.category_id" class="ds-field"><option :value="null">Selecção automática</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></BaseSelect><p v-if="form.errors.category_id" class="ds-field-error">{{ form.errors.category_id }}</p></div>
        <div class="ds-field-group sm:col-span-2"><label class="ds-field-label">Título <span class="ds-field-required">*</span></label><BaseInput v-model="form.title" type="text" class="ds-field" placeholder="Resumo claro do que precisa" :aria-invalid="Boolean(form.errors.title)" /><p v-if="form.errors.title" class="ds-field-error">{{ form.errors.title }}</p></div>
        <div class="ds-field-group"><label class="ds-field-label">Prioridade</label><BaseSelect v-model="form.priority" class="ds-field"><option value="low">Baixa</option><option value="normal">Normal</option><option value="high">Alta</option></BaseSelect></div>
        <div class="ds-field-group"><label class="ds-field-label">Data preferencial</label><DateTimePicker v-model="form.preferred_date" type="date" class="ds-field" :aria-invalid="Boolean(form.errors.preferred_date)" /><p v-if="form.errors.preferred_date" class="ds-field-error">{{ form.errors.preferred_date }}</p></div>
        <div class="ds-field-group sm:col-span-2"><label class="ds-field-label">Descrição detalhada <span class="ds-field-required">*</span></label><textarea v-model="form.description" rows="5" class="ds-field min-h-36 resize-y" placeholder="Contexto, objectivo, urgencia e condições especiais" :aria-invalid="Boolean(form.errors.description)" /><p v-if="form.errors.description" class="ds-field-error">{{ form.errors.description }}</p></div>
      </div>
    </section>

    <section class="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-[14rem_minmax(0,1fr)] lg:gap-8">
      <div><div class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]"><UserCircleIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />Contacto</div><p class="ds-copy mt-2 text-sm">Canal usado pela equipa para esclarecer e actualizar o pedido.</p></div>
      <div class="grid gap-4 sm:grid-cols-2"><div class="ds-field-group"><label class="ds-field-label">Correio electrónico <span class="ds-field-required">*</span></label><BaseInput v-model="form.email" type="email" class="ds-field" :aria-invalid="Boolean(form.errors.email)" /><p v-if="form.errors.email" class="ds-field-error">{{ form.errors.email }}</p></div><div class="ds-field-group"><label class="ds-field-label">Telefone <span class="ds-field-required">*</span></label><BaseInput v-model="form.contact" type="tel" class="ds-field" :aria-invalid="Boolean(form.errors.contact)" /><p v-if="form.errors.contact" class="ds-field-error">{{ form.errors.contact }}</p></div><div class="sm:col-span-2 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-4 py-3 text-xs font-semibold text-[var(--ds-text-muted)]"><p class="font-bold text-[var(--ds-text)]">{{ warehouse?.name || "Local autenticado" }}</p><p class="mt-1">{{ warehouse?.address || "Sem endereço registado" }}</p></div></div>
    </section>

    <section v-if="form.request_type === 'analysis_request'" class="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-[14rem_minmax(0,1fr)] lg:gap-8">
      <div><div class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]"><BeakerIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />Âmbito analítico</div><p class="ds-copy mt-2 text-sm">Descreva a amostra e seleccione os perfis requeridos.</p></div>
      <div class="space-y-5">
        <div class="grid gap-4 sm:grid-cols-2">
          <div class="ds-field-group"><label class="ds-field-label">Nome da amostra</label><BaseInput v-model="form.details.sample_name" type="text" class="ds-field" /><p v-if="form.errors['details.sample_name']" class="ds-field-error">{{ form.errors['details.sample_name'] }}</p></div>
          <div class="ds-field-group"><label class="ds-field-label">Produto</label><BaseSelect v-model="form.details.product_id" class="ds-field"><option :value="null">Não seleccionado</option><option v-for="product in products" :key="product.id" :value="product.id">{{ product.name }}</option></BaseSelect><BaseInput v-model="form.details.product_name" type="text" class="ds-field mt-2" placeholder="Ou descreva o produto" /></div>
          <div class="ds-field-group"><label class="ds-field-label">Matriz</label><BaseSelect v-model="form.details.matrix_id" class="ds-field"><option :value="null">Não seleccionada</option><option v-for="matrix in matrixes" :key="matrix.id" :value="matrix.id">{{ matrix.description }}</option></BaseSelect><BaseInput v-model="form.details.matrix" type="text" class="ds-field mt-2" placeholder="Ou descreva a matriz" /></div>
          <div class="ds-field-group"><label class="ds-field-label">Lote</label><BaseInput v-model="form.details.lot" type="text" class="ds-field" /></div>
          <div class="ds-field-group"><label class="ds-field-label">Embalagem</label><BaseSelect v-model="form.details.packaging_id" class="ds-field"><option :value="null">Não seleccionada</option><option v-for="item in packagingCategories" :key="item.id" :value="item.id">{{ item.name }}</option></BaseSelect><BaseInput v-model="form.details.packaging" type="text" class="ds-field mt-2" placeholder="Ou descreva a embalagem" /></div>
          <div class="ds-field-group"><label class="ds-field-label">Quantidade</label><BaseInput v-model="form.details.quantity" type="text" class="ds-field" placeholder="Ex.: 2 L ou 250 g" /></div>
          <div class="ds-field-group sm:col-span-2"><label class="ds-field-label">Observações da amostra</label><textarea v-model="form.details.notes" rows="3" class="ds-field resize-y" placeholder="Conservação, transporte, objectivo do controlo ou referência interna" /></div>
          <div class="ds-field-group sm:col-span-2"><label class="ds-field-label">Perfis solicitados <span class="ds-field-required">*</span></label><BaseSelect v-model="form.details.requested_profiles" multiple class="ds-field min-h-40"><option v-for="profile in profiles" :key="profile.id" :value="profile.id">{{ profile.name }}</option></BaseSelect><p class="ds-field-hint">Use Ctrl/Cmd para seleccionar varios perfis.</p><p v-if="form.errors['details.requested_profiles']" class="ds-field-error">{{ form.errors['details.requested_profiles'] }}</p></div>
          <label class="sm:col-span-2 flex items-start gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-4 py-3 text-sm font-semibold text-[var(--ds-text-muted)]"><CheckboxInput v-model="form.details.collection_required" type="checkbox" class="ds-checkbox mt-0.5" />Preciso que a equipa do laboratório recolha a amostra.</label>
        </div>

        <div class="border-t border-[var(--ds-border)] pt-5">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><h3 class="text-sm font-bold text-[var(--ds-text)]">Lote opcional de amostras</h3><p class="ds-copy mt-1 text-sm">Adicione linhas manualmente ou importe o modelo CSV.</p></div><div class="flex flex-wrap gap-2"><button type="button" class="ds-button ds-button-secondary" @click="downloadBatchTemplate"><ArrowDownTrayIcon class="h-4 w-4" />Modelo CSV</button><label class="ds-button ds-button-secondary cursor-pointer">Importar CSV<FileInput type="file" accept=".csv,text/csv" class="sr-only" @change="importBatchSamples" /></label><button type="button" class="ds-button ds-button-secondary" @click="addBatchSample"><PlusIcon class="h-4 w-4" />Linha</button></div></div>
          <p v-if="form.errors['details.samples']" class="ds-field-error mt-3">{{ form.errors['details.samples'] }}</p>
          <div v-if="form.details.samples.length" class="mt-4 space-y-3">
            <div v-for="(sample, index) in form.details.samples" :key="index" class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
              <div class="flex items-center justify-between gap-3"><p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Amostra {{ index + 1 }}</p><button type="button" class="ds-icon-button text-rose-600" title="Remover linha" @click="removeBatchSample(index)"><TrashIcon class="h-4 w-4" /></button></div>
              <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3"><BaseInput v-model="sample.sample_name" type="text" class="ds-field" placeholder="Nome da amostra" /><BaseInput v-model="sample.product_name" type="text" class="ds-field" placeholder="Produto" /><BaseInput v-model="sample.matrix" type="text" class="ds-field" placeholder="Matriz" /><BaseInput v-model="sample.lot" type="text" class="ds-field" placeholder="Lote" /><BaseInput v-model="sample.packaging" type="text" class="ds-field" placeholder="Embalagem" /><BaseInput v-model="sample.quantity" type="text" class="ds-field" placeholder="Quantidade" /><textarea v-model="sample.notes" rows="2" class="ds-field resize-y sm:col-span-2 lg:col-span-3" placeholder="Observações da amostra" /></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section v-if="form.request_type === 'collection_request'" class="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-[14rem_minmax(0,1fr)] lg:gap-8">
      <div><div class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]"><MapPinIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />Plano de colheita</div><p class="ds-copy mt-2 text-sm">Defina o local, a janela e os itens a recolher.</p></div>
      <div class="space-y-5"><div class="grid gap-4 sm:grid-cols-2"><div class="ds-field-group"><label class="ds-field-label">Local</label><BaseInput v-model="form.details.collection_location" type="text" class="ds-field" /><p v-if="form.errors['details.collection_location']" class="ds-field-error">{{ form.errors['details.collection_location'] }}</p></div><div class="ds-field-group"><label class="ds-field-label">Endereço</label><BaseInput v-model="form.details.collection_address" type="text" class="ds-field" /></div><div class="ds-field-group"><label class="ds-field-label">Pessoa no local</label><BaseInput v-model="form.details.collection_contact_name" type="text" class="ds-field" /></div><div class="ds-field-group"><label class="ds-field-label">Telefone no local</label><BaseInput v-model="form.details.collection_contact_phone" type="tel" class="ds-field" /></div><div class="ds-field-group sm:col-span-2"><label class="ds-field-label">Janela horária</label><BaseInput v-model="form.details.preferred_time_window" type="text" class="ds-field" placeholder="Ex.: 08:00-11:00" /></div></div><div class="border-t border-[var(--ds-border)] pt-5"><div class="flex items-center justify-between gap-3"><h3 class="text-sm font-bold text-[var(--ds-text)]">Itens a recolher</h3><button type="button" class="ds-button ds-button-secondary" @click="addCollectionItem"><PlusIcon class="h-4 w-4" />Adicionar item</button></div><p v-if="form.errors['details.items']" class="ds-field-error mt-2">{{ form.errors['details.items'] }}</p><div class="mt-4 space-y-3"><div v-for="(item, index) in form.details.items" :key="index" class="grid gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4 sm:grid-cols-[minmax(0,1fr)_8rem_minmax(0,1fr)_auto]"><BaseInput v-model="item.name" type="text" class="ds-field" placeholder="Item" /><BaseInput v-model="item.quantity" type="number" min="0" step="0.01" class="ds-field" placeholder="Qtd." /><BaseInput v-model="item.lot" type="text" class="ds-field" placeholder="Lote" /><button type="button" class="ds-icon-button text-rose-600" title="Remover item" @click="removeCollectionItem(index)"><TrashIcon class="h-4 w-4" /></button></div></div></div></div>
    </section>

    <section v-if="form.request_type === 'document_request'" class="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-[14rem_minmax(0,1fr)] lg:gap-8"><div><h3 class="text-sm font-bold text-[var(--ds-text)]">Documento</h3><p class="ds-copy mt-2 text-sm">Identifique o tipo e a referência.</p></div><div class="grid gap-4 sm:grid-cols-2"><div class="ds-field-group"><label class="ds-field-label">Tipo de documento</label><BaseInput v-model="form.details.document_type" type="text" class="ds-field" /></div><div class="ds-field-group"><label class="ds-field-label">Referência</label><BaseInput v-model="form.details.document_reference" type="text" class="ds-field" /></div></div></section>
    <section v-if="form.request_type === 'billing_support'" class="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-[14rem_minmax(0,1fr)] lg:gap-8"><div><h3 class="text-sm font-bold text-[var(--ds-text)]">Facturação</h3><p class="ds-copy mt-2 text-sm">Informe a referência do documento.</p></div><div class="ds-field-group"><label class="ds-field-label">Referência de facturação</label><BaseInput v-model="form.details.invoice_reference" type="text" class="ds-field" /></div></section>
    <section v-if="form.request_type === 'certificate_support'" class="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-[14rem_minmax(0,1fr)] lg:gap-8"><div><h3 class="text-sm font-bold text-[var(--ds-text)]">Certificado</h3><p class="ds-copy mt-2 text-sm">Informe o código ou referência.</p></div><div class="ds-field-group"><label class="ds-field-label">Referência do certificado</label><BaseInput v-model="form.details.certificate_reference" type="text" class="ds-field" /></div></section>
  </div>
</template>
