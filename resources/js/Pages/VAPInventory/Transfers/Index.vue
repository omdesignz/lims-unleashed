<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-start">
        <div class="min-w-0">
          <p class="text-xs font-black uppercase tracking-[0.18em] text-[var(--ds-text-soft)]">
            Logística interna
          </p>
          <div class="mt-3 flex flex-wrap items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <ArrowsRightLeftIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="text-2xl font-black tracking-tight text-[var(--ds-text)]">
                Transferências de stock
              </h1>
              <p class="mt-1 max-w-3xl text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
                Coordene movimentos entre armazéns, confirme receções e preserve a rastreabilidade de cada unidade movimentada.
              </p>
            </div>
          </div>
        </div>

        <Link :href="route('vap-inventory.transfers.create')" class="ds-button ds-button-primary">
          <PlusCircleIcon class="h-4 w-4" />
          Nova transferência
        </Link>
      </div>

      <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article
          v-for="stat in statCards"
          :key="stat.label"
          class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-4"
        >
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">
                {{ stat.label }}
              </p>
              <p class="mt-3 text-2xl font-black text-[var(--ds-text)]">
                {{ stat.value }}
              </p>
            </div>
            <component :is="stat.icon" :class="['h-5 w-5', stat.tone]" />
          </div>
          <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
            {{ stat.detail }}
          </p>
        </article>
      </div>
    </section>

    <section class="ds-command-surface p-5 sm:p-6">
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-[minmax(16rem,1.4fr)_repeat(3,minmax(11rem,1fr))]">
        <label class="ds-field-group">
          <span class="ds-field-label">Pesquisar</span>
          <span class="relative block">
            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" />
            <input
              v-model="localFilters.search"
              type="search"
              class="ds-field pl-10"
              placeholder="Item ou código interno"
            />
          </span>
        </label>

        <label class="ds-field-group">
          <span class="ds-field-label">Estado</span>
          <select v-model="localFilters.status" class="ds-field">
            <option value="">Todos os estados</option>
            <option value="pending">Pendente</option>
            <option value="sent">Em trânsito</option>
            <option value="received">Recebida</option>
          </select>
        </label>

        <label class="ds-field-group">
          <span class="ds-field-label">Origem</span>
          <select v-model="localFilters.source_id" class="ds-field">
            <option value="">Todos os armazéns</option>
            <option v-for="warehouse in warehouses" :key="`source-${warehouse.id}`" :value="warehouse.id">
              {{ warehouse.name }}
            </option>
          </select>
        </label>

        <label class="ds-field-group">
          <span class="ds-field-label">Destino</span>
          <select v-model="localFilters.destination_id" class="ds-field">
            <option value="">Todos os armazéns</option>
            <option v-for="warehouse in warehouses" :key="`destination-${warehouse.id}`" :value="warehouse.id">
              {{ warehouse.name }}
            </option>
          </select>
        </label>
      </div>

      <div class="ds-command-toolbar mt-5 grid gap-3 p-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
        <div class="min-w-0">
          <p class="text-sm font-bold text-[var(--ds-text)]">
            Mostrando {{ transfers.from || 0 }} a {{ transfers.to || 0 }} de {{ transfers.total || 0 }} movimentos
          </p>
          <div v-if="activeFilterPills.length" class="mt-2 flex flex-wrap gap-2">
            <span v-for="pill in activeFilterPills" :key="pill" class="ds-chip">
              {{ pill }}
            </span>
          </div>
          <p v-else class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
            Fila completa, sem filtros adicionais.
          </p>
        </div>

        <button type="button" class="ds-button ds-button-secondary" :disabled="!hasActiveFilters" @click="clearFilters">
          <FunnelIcon class="h-4 w-4" />
          Limpar filtros
        </button>
      </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
      <section class="ds-table-shell">
        <div class="ds-table-summary px-5 py-4">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">
              Fila de movimentação
            </p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">
              Transferências registadas
            </h2>
          </div>
          <span class="ds-chip">{{ transferRows.length }} nesta página</span>
        </div>

        <div v-if="transferRows.length" class="divide-y divide-[var(--ds-border)] lg:hidden">
          <article v-for="transfer in transferRows" :key="`mobile-${transfer.id}`" class="space-y-4 p-5">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <p class="font-mono text-xs font-bold text-[var(--ds-text-soft)]">TRF-{{ transfer.id }}</p>
                <h3 class="mt-1 text-base font-black text-[var(--ds-text)]">{{ transfer.item?.name || 'Item sem identificação' }}</h3>
                <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">
                  {{ transfer.source?.name || 'N/D' }} → {{ transfer.destination?.name || 'N/D' }}
                </p>
              </div>
              <span class="inline-flex items-center gap-2 text-xs font-black text-[var(--ds-text)]">
                <span :class="['h-2 w-2 rounded-full', statusDotClass(transfer)]"></span>
                {{ statusText(transfer) }}
              </span>
            </div>

            <dl class="grid gap-3 sm:grid-cols-3">
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Quantidade</dt>
                <dd class="mt-2 text-lg font-black text-[var(--ds-text)]">{{ transfer.qty }} {{ transfer.item?.unit?.code || 'UN' }}</dd>
              </div>
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Expedição</dt>
                <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ formatDate(transfer.sent_date) || 'Por expedir' }}</dd>
              </div>
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Receção</dt>
                <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ formatDate(transfer.received_date) || 'Pendente' }}</dd>
              </div>
            </dl>

            <div class="flex flex-wrap gap-2">
              <Link :href="route('vap-inventory.transfers.show', transfer.id)" class="ds-table-action">
                <EyeIcon class="h-4 w-4" />
                Abrir
              </Link>
              <button v-if="canReceiveTransfer(transfer)" type="button" class="ds-table-action" @click="openReceiveModal(transfer)">
                <CheckCircleIcon class="h-4 w-4" />
                Receber
              </button>
              <button v-if="canCancelTransfer(transfer)" type="button" class="ds-table-action ds-table-action-danger" @click="openCancelModal(transfer)">
                <XCircleIcon class="h-4 w-4" />
                Cancelar
              </button>
            </div>
          </article>
        </div>

        <div v-if="transferRows.length" class="hidden overflow-x-auto lg:block">
          <table class="min-w-full divide-y divide-[var(--ds-border)] text-left text-sm">
            <thead class="bg-[var(--ds-panel-subtle)]">
              <tr>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Transferência</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Rota</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Quantidade</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Prazos</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Estado</th>
                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Ações</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--ds-border)] bg-[var(--ds-panel-raised)]">
              <tr v-for="transfer in transferRows" :key="transfer.id" class="transition-colors hover:bg-[var(--ds-panel-subtle)]">
                <td class="px-5 py-4 align-top">
                  <p class="font-mono text-xs font-bold text-[var(--ds-text-soft)]">TRF-{{ transfer.id }}</p>
                  <p class="mt-1 font-black text-[var(--ds-text)]">{{ transfer.item?.name || 'Item sem identificação' }}</p>
                  <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ transfer.item?.internal_code || transfer.item?.code || 'Sem código' }}</p>
                </td>
                <td class="px-5 py-4 align-top">
                  <div class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-3 gap-y-1">
                    <ArrowUpTrayIcon class="mt-0.5 h-4 w-4 text-rose-600 dark:text-rose-300" />
                    <span class="font-bold text-[var(--ds-text)]">{{ transfer.source?.name || 'N/D' }}</span>
                    <ArrowDownTrayIcon class="mt-0.5 h-4 w-4 text-emerald-600 dark:text-emerald-300" />
                    <span class="font-bold text-[var(--ds-text)]">{{ transfer.destination?.name || 'N/D' }}</span>
                  </div>
                </td>
                <td class="px-5 py-4 align-top">
                  <p class="text-xl font-black text-[var(--ds-text)]">{{ transfer.qty }}</p>
                  <p class="text-xs font-semibold text-[var(--ds-text-muted)]">{{ transfer.item?.unit?.code || 'UN' }}</p>
                </td>
                <td class="px-5 py-4 align-top">
                  <p class="font-bold text-[var(--ds-text)]">{{ formatDate(transfer.sent_date) || 'Por expedir' }}</p>
                  <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
                    Prevista: {{ formatDate(transfer.expected_date) || 'Sem prazo' }}
                  </p>
                </td>
                <td class="px-5 py-4 align-top">
                  <span class="inline-flex items-center gap-2 font-bold text-[var(--ds-text)]">
                    <span :class="['h-2 w-2 rounded-full', statusDotClass(transfer)]"></span>
                    {{ statusText(transfer) }}
                  </span>
                  <p v-if="isOverdue(transfer)" class="mt-2 text-xs font-black text-rose-700 dark:text-rose-300">Prazo excedido</p>
                </td>
                <td class="px-5 py-4 align-top">
                  <div class="flex justify-end gap-2">
                    <Link :href="route('vap-inventory.transfers.show', transfer.id)" class="ds-table-action" title="Abrir transferência">
                      <EyeIcon class="h-4 w-4" />
                      <span class="sr-only">Abrir transferência {{ transfer.id }}</span>
                    </Link>
                    <button v-if="canReceiveTransfer(transfer)" type="button" class="ds-table-action" title="Receber transferência" @click="openReceiveModal(transfer)">
                      <CheckCircleIcon class="h-4 w-4" />
                      <span class="sr-only">Receber transferência {{ transfer.id }}</span>
                    </button>
                    <button v-if="canCancelTransfer(transfer)" type="button" class="ds-table-action ds-table-action-danger" title="Cancelar transferência" @click="openCancelModal(transfer)">
                      <XCircleIcon class="h-4 w-4" />
                      <span class="sr-only">Cancelar transferência {{ transfer.id }}</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="!transferRows.length" class="ds-empty-state p-10 text-center">
          <ArrowsRightLeftIcon class="mx-auto h-9 w-9 text-[var(--ds-text-soft)]" />
          <h3 class="mt-4 text-base font-black text-[var(--ds-text)]">Nenhuma transferência encontrada</h3>
          <p class="mx-auto mt-2 max-w-md text-sm font-medium text-[var(--ds-text-muted)]">
            Ajuste os filtros ou registe um novo movimento entre armazéns.
          </p>
          <Link :href="route('vap-inventory.transfers.create')" class="ds-button ds-button-primary mt-5">
            <PlusCircleIcon class="h-4 w-4" />
            Registar transferência
          </Link>
        </div>

        <div v-if="transferRows.length" class="border-t border-[var(--ds-border)] px-5 py-4">
          <Pagination
            :links="transfers.links"
            :from="transfers.from"
            :to="transfers.to"
            :total="transfers.total"
            :current_page="transfers.current_page"
            :last_page="transfers.last_page"
          />
        </div>
      </section>

      <aside class="space-y-5">
        <section class="ds-card p-5">
          <div class="flex items-center justify-between gap-3">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Fila operacional</p>
              <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Atenção necessária</h2>
            </div>
            <ClockIcon class="h-5 w-5 text-amber-600 dark:text-amber-300" />
          </div>

          <dl class="mt-5 divide-y divide-[var(--ds-border)]">
            <div class="flex items-center justify-between gap-4 py-3">
              <dt class="text-sm font-semibold text-[var(--ds-text-muted)]">Em trânsito</dt>
              <dd class="text-lg font-black text-[var(--ds-text)]">{{ inTransitCount }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 py-3">
              <dt class="text-sm font-semibold text-[var(--ds-text-muted)]">Prazo excedido</dt>
              <dd class="text-lg font-black text-rose-700 dark:text-rose-300">{{ overdueCount }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 py-3">
              <dt class="text-sm font-semibold text-[var(--ds-text-muted)]">Recebidas hoje</dt>
              <dd class="text-lg font-black text-emerald-700 dark:text-emerald-300">{{ stats?.received_today || 0 }}</dd>
            </div>
          </dl>
        </section>

        <section class="ds-card p-5">
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Fluxo controlado</p>
          <ol class="mt-5 space-y-0">
            <li v-for="(step, index) in workflowSteps" :key="step.label" class="relative flex gap-3 pb-6 last:pb-0">
              <span v-if="index < workflowSteps.length - 1" class="absolute left-[0.4375rem] top-4 h-[calc(100%-0.5rem)] w-px bg-[var(--ds-border)]"></span>
              <span :class="['relative mt-1 h-4 w-4 shrink-0 rounded-full border-4 border-[var(--ds-panel-raised)]', step.tone]"></span>
              <div>
                <p class="text-sm font-black text-[var(--ds-text)]">{{ step.label }}</p>
                <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">{{ step.detail }}</p>
              </div>
            </li>
          </ol>
        </section>

        <section class="ds-card p-5">
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Acesso rápido</p>
          <div class="mt-4 grid gap-2">
            <Link :href="route('vap-inventory.transfers.create')" class="ds-button ds-button-primary w-full">
              <PlusCircleIcon class="h-4 w-4" />
              Nova transferência
            </Link>
            <Link :href="route('vap-inventory.items.index')" class="ds-button ds-button-secondary w-full">
              <CubeIcon class="h-4 w-4" />
              Consultar inventário
            </Link>
          </div>
        </section>
      </aside>
    </div>

    <TransitionRoot as="template" :show="isReceiveModalOpen">
      <Dialog class="relative z-50" @close="closeReceiveModal">
        <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-150" leave-from="opacity-100" leave-to="opacity-0">
          <div class="ds-modal-backdrop fixed inset-0" />
        </TransitionChild>
        <div class="fixed inset-0 z-50 overflow-y-auto">
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center">
            <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-150" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
              <DialogPanel class="ds-modal-panel w-full max-w-xl p-0 text-left">
                <form @submit.prevent="submitReceive">
                  <div class="flex items-start justify-between gap-4 border-b border-[var(--ds-border)] p-5">
                    <div>
                      <DialogTitle class="text-lg font-black text-[var(--ds-text)]">Confirmar receção</DialogTitle>
                      <p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">Registe a quantidade física verificada no destino.</p>
                    </div>
                    <button type="button" class="ds-table-action" title="Fechar" @click="closeReceiveModal">
                      <XMarkIcon class="h-4 w-4" />
                    </button>
                  </div>

                  <div class="space-y-5 p-5">
                    <div class="ds-command-toolbar grid gap-3 p-3 sm:grid-cols-2">
                      <div>
                        <p class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Item</p>
                        <p class="mt-1 text-sm font-black text-[var(--ds-text)]">{{ selectedTransfer?.item?.name || 'N/D' }}</p>
                      </div>
                      <div>
                        <p class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Rota</p>
                        <p class="mt-1 text-sm font-black text-[var(--ds-text)]">{{ selectedTransfer?.source?.name }} → {{ selectedTransfer?.destination?.name }}</p>
                      </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                      <label class="ds-field-group">
                        <span class="ds-field-label">Quantidade recebida <span class="ds-field-required">*</span></span>
                        <input v-model="receiveForm.actual_qty" type="number" min="1" :max="selectedTransfer?.qty" class="ds-field" required />
                        <span class="ds-field-hint">Máximo previsto: {{ selectedTransfer?.qty || 0 }}</span>
                        <span v-if="receiveForm.errors.actual_qty" class="ds-field-error">{{ receiveForm.errors.actual_qty }}</span>
                      </label>
                      <label class="ds-field-group">
                        <span class="ds-field-label">Data de receção <span class="ds-field-required">*</span></span>
                        <input v-model="receiveForm.received_date" type="date" class="ds-field" required />
                        <span v-if="receiveForm.errors.received_date" class="ds-field-error">{{ receiveForm.errors.received_date }}</span>
                      </label>
                    </div>

                    <label class="ds-field-group">
                      <span class="ds-field-label">Observações</span>
                      <textarea v-model="receiveForm.notes" rows="3" class="ds-field" placeholder="Condição da carga, divergências ou evidências de receção"></textarea>
                      <span v-if="receiveForm.errors.notes" class="ds-field-error">{{ receiveForm.errors.notes }}</span>
                    </label>
                  </div>

                  <div class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] p-5 sm:flex-row sm:justify-end">
                    <button type="button" class="ds-button ds-button-secondary" @click="closeReceiveModal">Voltar</button>
                    <button type="submit" class="ds-button ds-button-primary" :disabled="receiveForm.processing || !canSubmitReceive">
                      <CheckCircleIcon class="h-4 w-4" />
                      {{ receiveForm.processing ? 'A registar...' : 'Confirmar receção' }}
                    </button>
                  </div>
                </form>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>

    <TransitionRoot as="template" :show="isCancelModalOpen">
      <Dialog class="relative z-50" @close="closeCancelModal">
        <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-150" leave-from="opacity-100" leave-to="opacity-0">
          <div class="ds-modal-backdrop fixed inset-0" />
        </TransitionChild>
        <div class="fixed inset-0 z-50 overflow-y-auto">
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center">
            <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-150" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
              <DialogPanel class="ds-modal-panel w-full max-w-lg p-0 text-left">
                <form @submit.prevent="submitCancel">
                  <div class="border-b border-[var(--ds-border)] p-5">
                    <DialogTitle class="text-lg font-black text-[var(--ds-text)]">Cancelar transferência</DialogTitle>
                    <p class="mt-1 text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
                      O stock reservado será devolvido ao armazém de origem. Registe o motivo para a trilha de auditoria.
                    </p>
                  </div>
                  <div class="p-5">
                    <label class="ds-field-group">
                      <span class="ds-field-label">Motivo do cancelamento <span class="ds-field-required">*</span></span>
                      <textarea v-model="cancelForm.notes" rows="4" class="ds-field" placeholder="Descreva a razão operacional" required></textarea>
                      <span v-if="cancelForm.errors.notes" class="ds-field-error">{{ cancelForm.errors.notes }}</span>
                    </label>
                  </div>
                  <div class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] p-5 sm:flex-row sm:justify-end">
                    <button type="button" class="ds-button ds-button-secondary" @click="closeCancelModal">Manter transferência</button>
                    <button type="submit" class="ds-button ds-button-danger" :disabled="cancelForm.processing || !cancelForm.notes.trim()">
                      <XCircleIcon class="h-4 w-4" />
                      {{ cancelForm.processing ? 'A cancelar...' : 'Cancelar transferência' }}
                    </button>
                  </div>
                </form>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { debounce } from 'lodash'
import {
  ArrowDownTrayIcon,
  ArrowUpTrayIcon,
  ArrowsRightLeftIcon,
  CheckCircleIcon,
  ClockIcon,
  CubeIcon,
  EyeIcon,
  FunnelIcon,
  MagnifyingGlassIcon,
  PlusCircleIcon,
  XCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  transfers: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  warehouses: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({}),
  },
})

const localFilters = reactive({
  search: props.filters?.search || '',
  status: props.filters?.status || '',
  source_id: props.filters?.source_id || '',
  destination_id: props.filters?.destination_id || '',
})

const selectedTransfer = ref(null)
const isReceiveModalOpen = ref(false)
const isCancelModalOpen = ref(false)

const receiveForm = useForm({
  actual_qty: '',
  received_date: '',
  notes: '',
})

const cancelForm = useForm({
  notes: '',
})

const transferRows = computed(() => props.transfers?.data || [])
const overdueCount = computed(() => transferRows.value.filter(isOverdue).length)
const inTransitCount = computed(() => transferRows.value.filter(canReceiveTransfer).length)

const statCards = computed(() => [
  {
    label: 'Pendentes',
    value: props.stats?.pending_transfers || 0,
    detail: 'Movimentos ainda não concluídos',
    icon: ClockIcon,
    tone: 'text-amber-600 dark:text-amber-300',
  },
  {
    label: 'Expedidas hoje',
    value: props.stats?.sent_today || 0,
    detail: 'Saídas registadas no dia',
    icon: ArrowUpTrayIcon,
    tone: 'text-cyan-700 dark:text-cyan-300',
  },
  {
    label: 'Recebidas hoje',
    value: props.stats?.received_today || 0,
    detail: 'Entradas confirmadas no destino',
    icon: CheckCircleIcon,
    tone: 'text-emerald-700 dark:text-emerald-300',
  },
  {
    label: 'Histórico total',
    value: props.stats?.total_transfers || 0,
    detail: 'Transferências sob rastreabilidade',
    icon: ArrowsRightLeftIcon,
    tone: 'text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200',
  },
])

const workflowSteps = [
  {
    label: 'Reservar na origem',
    detail: 'A quantidade fica indisponível para outro consumo assim que a transferência é criada.',
    tone: 'bg-cyan-600',
  },
  {
    label: 'Expedir com prazo',
    detail: 'Origem, destino e data prevista permanecem visíveis na fila operacional.',
    tone: 'bg-amber-500',
  },
  {
    label: 'Confirmar no destino',
    detail: 'A receção física atualiza o stock e encerra o movimento auditável.',
    tone: 'bg-emerald-600',
  },
]

const activeFilterPills = computed(() => {
  const pills = []
  if (localFilters.search) pills.push(`Pesquisa: ${localFilters.search}`)
  if (localFilters.status) pills.push(`Estado: ${statusFilterLabel(localFilters.status)}`)
  if (localFilters.source_id) pills.push(`Origem: ${warehouseName(localFilters.source_id)}`)
  if (localFilters.destination_id) pills.push(`Destino: ${warehouseName(localFilters.destination_id)}`)
  return pills
})

const hasActiveFilters = computed(() => activeFilterPills.value.length > 0)
const canSubmitReceive = computed(() => (
  Number(receiveForm.actual_qty) > 0
  && Number(receiveForm.actual_qty) <= Number(selectedTransfer.value?.qty || 0)
  && Boolean(receiveForm.received_date)
))

function formatDate(dateString) {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

function statusText(transfer) {
  if (transfer.deleted_at) return 'Cancelada'
  if (transfer.received_date) return 'Recebida'
  if (transfer.sent_date) return 'Em trânsito'
  return 'Pendente'
}

function statusDotClass(transfer) {
  if (transfer.deleted_at) return 'bg-rose-600'
  if (transfer.received_date) return 'bg-emerald-600'
  if (transfer.sent_date) return isOverdue(transfer) ? 'bg-rose-600' : 'bg-cyan-600'
  return 'bg-amber-500'
}

function statusFilterLabel(status) {
  return {
    pending: 'Pendente',
    sent: 'Em trânsito',
    received: 'Recebida',
  }[status] || status
}

function warehouseName(id) {
  return props.warehouses.find((warehouse) => String(warehouse.id) === String(id))?.name || 'N/D'
}

function isOverdue(transfer) {
  if (transfer.received_date || !transfer.expected_date) return false
  const expectedDate = new Date(transfer.expected_date)
  expectedDate.setHours(23, 59, 59, 999)
  return expectedDate < new Date()
}

function canReceiveTransfer(transfer) {
  return !transfer.deleted_at && !transfer.received_date && Boolean(transfer.sent_date)
}

function canCancelTransfer(transfer) {
  return !transfer.deleted_at && !transfer.received_date
}

function clearFilters() {
  Object.assign(localFilters, {
    search: '',
    status: '',
    source_id: '',
    destination_id: '',
  })
}

function openReceiveModal(transfer) {
  selectedTransfer.value = transfer
  receiveForm.clearErrors()
  receiveForm.actual_qty = transfer.qty
  receiveForm.received_date = new Date().toISOString().split('T')[0]
  receiveForm.notes = ''
  isReceiveModalOpen.value = true
}

function closeReceiveModal() {
  if (receiveForm.processing) return
  isReceiveModalOpen.value = false
  selectedTransfer.value = null
  receiveForm.reset()
  receiveForm.clearErrors()
}

function submitReceive() {
  if (!selectedTransfer.value || !canSubmitReceive.value) return

  receiveForm.post(route('vap-inventory.transfers.receive', selectedTransfer.value.id), {
    preserveScroll: true,
    onSuccess: closeReceiveModal,
  })
}

function openCancelModal(transfer) {
  selectedTransfer.value = transfer
  cancelForm.reset()
  cancelForm.clearErrors()
  isCancelModalOpen.value = true
}

function closeCancelModal() {
  if (cancelForm.processing) return
  isCancelModalOpen.value = false
  selectedTransfer.value = null
  cancelForm.reset()
  cancelForm.clearErrors()
}

function submitCancel() {
  if (!selectedTransfer.value || !cancelForm.notes.trim()) return

  cancelForm.post(route('vap-inventory.transfers.cancel', selectedTransfer.value.id), {
    preserveScroll: true,
    onSuccess: closeCancelModal,
  })
}

watch(
  localFilters,
  debounce((filters) => {
    router.get(route('vap-inventory.transfers.index'), {
      search: filters.search || undefined,
      status: filters.status || undefined,
      source_id: filters.source_id || undefined,
      destination_id: filters.destination_id || undefined,
    }, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    })
  }, 350),
  { deep: true },
)
</script>
