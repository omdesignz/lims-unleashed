<script setup>
import { Link } from '@inertiajs/vue3'
import PortalLayout from '@/Shared/Layouts/PortalLayout.vue'
import Pagination from '@/Components/Pagination.vue'

defineOptions({ layout: PortalLayout })
defineProps({ invitations: { type: Object, required: true } })

function subjectLabel(invitation) {
  return { service: 'Serviço geral', sample_entry: 'Entrada de amostra', proposal: 'Proposta' }[invitation.rateable_type] || 'Processo'
}
</script>

<template>
  <section class="ds-card overflow-hidden">
    <header class="border-b border-[var(--ds-border)] p-5">
      <h1 class="ds-heading text-xl">Avaliações pendentes</h1>
      <p class="ds-copy mt-2 text-sm">Convites dos seus laboratórios. Cada convite aceita uma resposta.</p>
    </header>
    <div v-if="invitations.data.length" class="divide-y divide-[var(--ds-border)]">
      <article v-for="invitation in invitations.data" :key="invitation.invitation" class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-sm font-bold text-[var(--ds-text)]">{{ invitation.laboratory }}</h2>
          <p class="ds-copy mt-1 text-sm">{{ subjectLabel(invitation) }}<span v-if="invitation.rateable_id"> #{{ invitation.rateable_id }}</span></p>
          <p class="mt-1 text-xs text-[var(--ds-text-muted)]">Validade: {{ new Date(invitation.expires_at).toLocaleDateString('pt-PT') }}</p>
        </div>
        <Link :href="route('portal.rating.create', { invitation: invitation.invitation })" class="ds-button ds-button-primary">Responder</Link>
      </article>
    </div>
    <p v-else class="ds-empty-state m-5 p-5">Sem convites pendentes.</p>
    <Pagination v-if="invitations.links" :links="invitations.links" class="p-5" />
  </section>
</template>
