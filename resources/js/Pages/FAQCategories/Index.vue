<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { QuestionMarkCircleIcon } from '@heroicons/vue/24/outline'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="faqcategories" route-parameter="category" permission-key="faq_categories" title="Categorias de perguntas frequentes" kicker="Base de conhecimento" description="Organizacao controlada dos temas de suporte apresentados aos utilizadores e clientes." entity-label="Categoria de FAQ" new-entity-label="Nova categoria" name-label="Nome" description-label="Escopo do tema" :supports-name="true" :supports-code="false" :icon="QuestionMarkCircleIcon" create-description="Adicione um tema reutilizavel na organizacao das perguntas frequentes." form-description="Defina um nome reconhecivel e descreva os assuntos que pertencem a categoria." />
</template>
