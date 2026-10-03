<template>
  <div class="auth-canvas min-h-dvh">
    <Head :title="title" />
    <header class="auth-bar">
      <Link :href="route('dashboard')" aria-label="Voltar ao início">
        <img src="/brand/svg/VAP_Small_Black.svg" alt="VAP Sistemas" />
      </Link>
      <span class="pl-k auth-bar-label">LIMS · {{ title }}</span>
    </header>

    <main class="auth-main">
      <section class="auth-sheet">
        <div class="mb-9 flex justify-between gap-4"><span class="pl-k pl-muted">Erro {{ status }}</span><span class="pl-k pl-muted">{{ title }}</span></div>
        <h1 class="auth-title"><span class="auth-title-accent">{{ status }}.</span>{{ description }}</h1>
        <p class="mt-5 max-w-[52ch] text-[15px] leading-6 text-[var(--pl-muted)]">{{ paragraph }}</p>
        <div class="mt-9 flex flex-wrap gap-3">
          <Link :href="route('dashboard')" class="ds-button ds-button-primary min-h-12 justify-between">Ir para o início<ChevronRightIcon class="h-4 w-4" aria-hidden="true" /></Link>
          <button type="button" class="ds-button ds-button-secondary min-h-12" @click="goBack">Voltar à página anterior</button>
        </div>
      </section>
    </main>
  </div>
</template>

<script setup>
import EmptyLayout from '@/Shared/Layouts/empty-layout.vue'
import { ChevronRight as ChevronRightIcon } from '@lucide/vue'
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

defineOptions({
  layout: EmptyLayout,
})

const props = defineProps({
  status: Number,
})

const title = computed(() => ({
  400: 'Pedido inválido',
  401: 'Sessão necessária',
  403: 'Sem permissão',
  404: 'Página não encontrada',
  405: 'Operação não permitida',
  419: 'Sessão expirada',
  429: 'Demasiados pedidos',
  500: 'Erro interno',
  503: 'Serviço indisponível',
}[props.status] ?? 'Erro'))

const description = computed(() => ({
  400: 'O pedido não pôde ser interpretado.',
  401: 'Inicie sessão para continuar.',
  403: 'Não tem permissão para ver esta página.',
  404: 'Esta página não existe.',
  405: 'Esta operação não está disponível aqui.',
  419: 'A página expirou por inactividade.',
  429: 'Foram feitos demasiados pedidos em pouco tempo.',
  500: 'Algo falhou do nosso lado.',
  503: 'O serviço está temporariamente indisponível.',
}[props.status] ?? 'Ocorreu um erro inesperado.'))

const paragraph = computed(() => ({
  400: 'Verifique o endereço e tente novamente. Se chegou aqui através de uma ligação da aplicação, comunique-o ao administrador.',
  401: 'A sua sessão terminou ou ainda não foi iniciada. Depois de entrar, regressa ao ponto onde estava.',
  403: 'O seu perfil neste laboratório não inclui este módulo. Peça acesso ao administrador ou mude para um laboratório onde tenha essa permissão.',
  404: 'O registo pode ter sido movido, arquivado ou pertencer a outro laboratório. Use a pesquisa da aplicação para o encontrar.',
  405: 'O endereço existe, mas não aceita este tipo de pedido. Regresse à página anterior e repita a operação a partir da aplicação.',
  419: 'Por segurança, os formulários deixam de ser válidos ao fim de algum tempo. Actualize a página e repita a operação.',
  429: 'Aguarde alguns instantes antes de tentar novamente. Nenhum dado foi alterado.',
  500: 'O erro foi registado. Tente novamente dentro de instantes; os dados que já guardou não foram afectados.',
  503: 'Está em curso uma manutenção. Volte a tentar dentro de alguns minutos.',
}[props.status] ?? 'Tente novamente ou regresse à visão geral.'))

function goBack() {
  window.history.back()
}
</script>
