<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

defineOptions({ layout: false })

const props = defineProps({
  proposal: { type: Object, required: true },
  proposalUrl: { type: String, required: true },
})

const confirmation = computed(() => {
  if (props.proposal.status === 'ACCEPTED') {
    return { title: 'Aceitação registada', message: 'A sua aceitação desta proposta foi registada.' }
  }
  if (props.proposal.status === 'REJECTED') {
    return { title: 'Rejeição registada', message: 'A sua rejeição desta proposta foi registada.' }
  }
  return { title: 'Estado da proposta', message: 'Consulte a proposta para verificar o estado actual.' }
})
</script>

<template>
  <main class="flex min-h-screen items-center justify-center bg-[var(--ds-canvas)] px-4 py-8 text-[var(--ds-text)]">
    <Head :title="confirmation.title" />
    <section class="ds-card w-full max-w-lg p-6 sm:p-8" aria-labelledby="confirmation-title">
      <p class="ds-kicker break-words">{{ proposal.proposal_number }}</p>
      <h1 id="confirmation-title" class="ds-heading mt-3 text-2xl">{{ confirmation.title }}</h1>
      <p class="ds-copy mt-3 leading-7">{{ confirmation.message }}</p>
      <Link :href="proposalUrl" class="ds-button ds-button-primary mt-6 min-h-11">
        Voltar à proposta
      </Link>
    </section>
  </main>
</template>
