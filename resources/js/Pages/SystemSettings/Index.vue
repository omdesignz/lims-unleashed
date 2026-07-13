<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 border-b border-[color:var(--ds-border)] px-5 py-5 lg:flex-row lg:items-start lg:justify-between lg:px-6">
        <div class="max-w-3xl">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-kicker">Configuração operacional</span>
            <span class="ds-chip">
              <span class="lims-status-dot" :class="editSettings ? 'lims-status-dot-hold' : 'lims-status-dot-release'" />
              {{ editSettings ? 'Edição em curso' : 'Configuração publicada' }}
            </span>
          </div>
          <h1 class="ds-heading mt-3 text-2xl">{{ form.app_name || settings.app_name || 'Configurações gerais' }}</h1>
          <p class="ds-copy mt-2 text-sm">
            Centralize identidade institucional, comunicação e assinatura documental para manter certificados, propostas, portal e relatórios coerentes.
          </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row">
          <button type="button" class="ds-button ds-button-secondary" @click="toggleEdit">
            <PencilSquareIcon class="h-4 w-4" />
            {{ editSettings ? 'Cancelar edição' : 'Editar definições' }}
          </button>
          <button v-if="editSettings" type="button" class="ds-button ds-button-primary" :disabled="form.processing" @click="submit">
            <ArrowUpOnSquareIcon class="h-4 w-4" />
            {{ form.processing ? 'A guardar...' : 'Guardar alterações' }}
          </button>
        </div>
      </div>

      <dl class="grid grid-cols-2 divide-x divide-y divide-[color:var(--ds-border)] sm:grid-cols-4 sm:divide-y-0">
        <div class="px-5 py-4">
          <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Marca</dt>
          <dd class="mt-2 truncate text-sm font-bold text-[color:var(--ds-text)]">{{ settings.app_name || 'Não configurada' }}</dd>
          <p class="mt-1 text-xs text-[color:var(--ds-text-muted)]">{{ settings.app_version || 'Sem versão' }}</p>
        </div>
        <div class="px-5 py-4">
          <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Assinatura</dt>
          <dd class="mt-2 text-sm font-bold text-[color:var(--ds-text)]">{{ securitySummary.private_key_configured && securitySummary.public_key_configured ? 'Pronta' : 'Pendente' }}</dd>
          <p class="mt-1 text-xs text-[color:var(--ds-text-muted)]">Chaves documental e pública</p>
        </div>
        <div class="px-5 py-4">
          <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Validação</dt>
          <dd class="mt-2 truncate text-sm font-bold text-[color:var(--ds-text)]">{{ settings.app_agt_validation_number || 'Sem número' }}</dd>
          <p class="mt-1 truncate text-xs text-[color:var(--ds-text-muted)]">{{ settings.app_agt_valid_name || 'Entidade pendente' }}</p>
        </div>
        <div class="px-5 py-4">
          <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Acesso forte</dt>
          <dd class="mt-2 text-sm font-bold text-[color:var(--ds-text)]">{{ securitySummary.two_factor_supported ? 'MFA disponível' : 'MFA indisponível' }}</dd>
          <p class="mt-1 text-xs text-[color:var(--ds-text-muted)]">Gerido no perfil do utilizador</p>
        </div>
      </dl>
    </section>

    <div class="grid gap-4 xl:grid-cols-[17rem_minmax(0,1fr)]">
      <aside class="xl:sticky xl:top-24 xl:self-start">
        <section class="ds-panel overflow-hidden">
          <div class="border-b border-[color:var(--ds-border)] px-4 py-4">
            <p class="ds-kicker">Áreas de configuração</p>
            <p class="ds-copy mt-2 text-xs">Escolha uma área para rever ou editar.</p>
          </div>

          <nav class="flex gap-2 overflow-x-auto p-2 xl:flex-col" aria-label="Secções das configurações">
            <button
              v-for="tab in tabs"
              :key="tab.href"
              type="button"
              class="ds-settings-tab group text-left"
              :class="selectedTab === tab.href ? 'ds-settings-tab-active' : ''"
              :aria-current="selectedTab === tab.href ? 'page' : undefined"
              @click="selectedTab = tab.href"
            >
              <component :is="tab.icon" class="mt-0.5 h-4 w-4 shrink-0" />
              <span class="min-w-0 flex-1">
                <span class="block text-sm font-bold">{{ tab.name }}</span>
                <span class="mt-1 hidden text-xs leading-5 text-[color:var(--ds-text-soft)] xl:block">{{ tab.description }}</span>
              </span>
              <ChevronRightIcon class="mt-0.5 hidden h-4 w-4 shrink-0 xl:block" />
            </button>
          </nav>

          <div class="border-t border-[color:var(--ds-border)] px-4 py-4">
            <div class="flex items-center justify-between gap-3">
              <span class="text-xs font-semibold text-[color:var(--ds-text-muted)]">Estado do formulário</span>
              <span class="text-xs font-bold" :class="editSettings ? 'text-amber-700 dark:text-amber-300' : 'text-emerald-700 dark:text-emerald-300'">
                {{ editSettings ? 'Não publicado' : 'Sincronizado' }}
              </span>
            </div>
          </div>
        </section>
      </aside>

      <div class="min-w-0 space-y-4">
        <template v-if="selectedTab === '#general'">
          <section class="ds-panel overflow-hidden">
            <div class="flex items-start gap-3 border-b border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] px-5 py-4">
              <Cog6ToothIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
              <div>
                <h2 class="ds-heading text-base">Identidade da plataforma</h2>
                <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Dados usados no backoffice, documentos e comunicação institucional.</p>
              </div>
            </div>

            <div class="grid gap-4 p-5 md:grid-cols-2">
              <SettingsField
                v-for="field in identityFields"
                :key="field.key"
                v-model="form[field.key]"
                :label="field.label"
                :type="field.type || 'text'"
                :editing="editSettings"
                :display-value="settings[field.key]"
                :error="form.errors[field.key]"
              />

              <div class="ds-field-group md:col-span-2">
                <label class="ds-field-label">Cor principal</label>
                <div v-if="editSettings" class="ds-command-toolbar flex flex-wrap items-center gap-4 p-3">
                  <ColorPicker v-model:pure-color="form.app_primary_color" format="hex" shape="circle" lang="Pt" picker-type="chrome" disable-history="true" disable-alpha="true" />
                  <span class="h-9 w-9 border border-[color:var(--ds-border-strong)]" :style="{ backgroundColor: form.app_primary_color || '#1f87e8' }"></span>
                  <span class="font-mono text-sm font-bold text-[color:var(--ds-text)]">{{ form.app_primary_color || '#1f87e8' }}</span>
                </div>
                <div v-else class="flex min-h-11 items-center gap-3 border border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] px-3 py-2.5">
                  <span class="h-7 w-7 border border-[color:var(--ds-border-strong)]" :style="{ backgroundColor: settings.app_primary_color || '#1f87e8' }"></span>
                  <span class="font-mono text-sm font-bold text-[color:var(--ds-text)]">{{ settings.app_primary_color || '#1f87e8' }}</span>
                </div>
                <p v-if="form.errors.app_primary_color" class="ds-field-error">{{ form.errors.app_primary_color }}</p>
              </div>
            </div>
          </section>

          <section class="ds-panel overflow-hidden">
            <div class="flex items-start gap-3 border-b border-[color:var(--ds-border)] px-5 py-4">
              <BuildingOfficeIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
              <div>
                <h2 class="ds-heading text-base">Informação institucional do laboratório</h2>
                <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Identificação externa usada em portal, certificados e propostas.</p>
              </div>
            </div>

            <div class="grid gap-4 p-5 md:grid-cols-2">
              <SettingsField
                v-for="field in organizationFields"
                :key="field.key"
                v-model="form[field.key]"
                :label="field.label"
                :type="field.type || 'text'"
                :editing="editSettings"
                :display-value="settings[field.key]"
                :error="form.errors[field.key]"
              />
            </div>
          </section>

          <section class="ds-panel overflow-hidden">
            <div class="flex items-start gap-3 border-b border-[color:var(--ds-border)] px-5 py-4">
              <CreditCardIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
              <div>
                <h2 class="ds-heading text-base">Dados bancários e controlo documental</h2>
                <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Informação financeira e palavras-chave usadas nos documentos emitidos.</p>
              </div>
            </div>

            <div class="grid gap-4 p-5 md:grid-cols-2">
              <SettingsField
                v-for="field in bankingFields"
                :key="field.key"
                v-model="form[field.key]"
                :label="field.label"
                :editing="editSettings"
                :display-value="settings[field.key]"
                :multiline="field.multiline"
                :wide="field.multiline"
                :placeholder="field.placeholder"
                :error="form.errors[field.key]"
              />
            </div>
          </section>

          <section class="ds-panel overflow-hidden">
            <div class="flex items-start gap-3 border-b border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] px-5 py-4">
              <PhotoIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
              <div>
                <h2 class="ds-heading text-base">Marca e experiência de entrada</h2>
                <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Logótipo, headline e mensagem apresentados na landing e no login.</p>
              </div>
            </div>

            <div class="grid gap-5 p-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
              <div class="grid content-start gap-4">
                <SettingsField v-model="form.app_logo_url" label="URL do logótipo" :editing="editSettings" :display-value="settings.app_logo_url" :error="form.errors.app_logo_url" placeholder="https://.../logo.svg" />
                <SettingsField v-model="form.app_login_headline" label="Headline do login" :editing="editSettings" :display-value="settings.app_login_headline" :error="form.errors.app_login_headline" />
                <SettingsField v-model="form.app_login_subheadline" label="Subheadline do login" :editing="editSettings" :display-value="settings.app_login_subheadline" :error="form.errors.app_login_subheadline" multiline :rows="3" />
              </div>

              <div class="border border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] p-4">
                <p class="ds-kicker">Pré-visualização</p>
                <div class="mt-4 flex items-center gap-3 border-b border-[color:var(--ds-border)] pb-4">
                  <div class="flex h-14 w-14 shrink-0 items-center justify-center border border-[color:var(--ds-border)] bg-[color:var(--ds-panel)] p-2">
                    <img v-if="form.app_logo_url || settings.app_logo_url" :src="form.app_logo_url || settings.app_logo_url" alt="Logótipo" class="max-h-full max-w-full object-contain" />
                    <SwatchIcon v-else class="h-6 w-6 text-[color:var(--ds-text-soft)]" />
                  </div>
                  <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-[color:var(--ds-text)]">{{ form.app_name || settings.app_name || 'LIMS Unleashed' }}</p>
                    <p class="mt-1 line-clamp-2 text-xs leading-5 text-[color:var(--ds-text-soft)]">{{ form.app_slogan || settings.app_slogan || 'Rastreabilidade e conformidade laboratorial.' }}</p>
                  </div>
                </div>
                <div class="mt-4 border-l-4 border-white/50 p-4 text-white" :style="themePreviewStyle">
                  <p class="text-xs font-bold uppercase text-white/75">Área interna</p>
                  <h3 class="mt-2 text-lg font-bold">{{ form.app_login_headline || settings.app_login_headline || 'Bem-vindo de volta' }}</h3>
                  <p class="mt-2 text-xs leading-5 text-white/85">{{ form.app_login_subheadline || settings.app_login_subheadline || 'Aceda à operação e mantenha o laboratório sob controlo.' }}</p>
                </div>
              </div>
            </div>
          </section>

          <section class="ds-panel overflow-hidden">
            <div class="flex items-start gap-3 border-b border-[color:var(--ds-border)] px-5 py-4">
              <LanguageIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
              <div>
                <h2 class="ds-heading text-base">Apresentação e modo operacional</h2>
                <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Paleta institucional, preset visual, idioma e alcance do portal.</p>
              </div>
            </div>

            <div class="grid gap-5 p-5 md:grid-cols-2">
              <div class="ds-command-toolbar p-4">
                <p class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Idioma de sessão</p>
                <p class="mt-2 text-sm font-bold text-[color:var(--ds-text)]">{{ currentLanguage }}</p>
              </div>
              <div class="ds-command-toolbar p-4">
                <p class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Formato de data</p>
                <p class="mt-2 font-mono text-sm font-bold text-[color:var(--ds-text)]">DD-MM-YYYY</p>
              </div>

              <div class="ds-field-group">
                <label class="ds-field-label">Cor secundária</label>
                <div v-if="editSettings" class="ds-command-toolbar flex items-center gap-3 p-3">
                  <ColorPicker v-model:pure-color="form.app_secondary_color" format="hex" shape="circle" lang="Pt" picker-type="chrome" disable-history="true" disable-alpha="true" />
                  <span class="h-8 w-8 border border-[color:var(--ds-border-strong)]" :style="{ backgroundColor: form.app_secondary_color || '#0f172a' }"></span>
                  <span class="font-mono text-xs font-bold text-[color:var(--ds-text)]">{{ form.app_secondary_color || '#0f172a' }}</span>
                </div>
                <div v-else class="flex min-h-11 items-center gap-3 border border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] px-3 py-2.5">
                  <span class="h-7 w-7 border border-[color:var(--ds-border-strong)]" :style="{ backgroundColor: settings.app_secondary_color || '#0f172a' }"></span>
                  <span class="font-mono text-xs font-bold text-[color:var(--ds-text)]">{{ settings.app_secondary_color || '#0f172a' }}</span>
                </div>
              </div>

              <div class="ds-field-group">
                <label class="ds-field-label">Cor de destaque</label>
                <div v-if="editSettings" class="ds-command-toolbar flex items-center gap-3 p-3">
                  <ColorPicker v-model:pure-color="form.app_accent_color" format="hex" shape="circle" lang="Pt" picker-type="chrome" disable-history="true" disable-alpha="true" />
                  <span class="h-8 w-8 border border-[color:var(--ds-border-strong)]" :style="{ backgroundColor: form.app_accent_color || '#14b8a6' }"></span>
                  <span class="font-mono text-xs font-bold text-[color:var(--ds-text)]">{{ form.app_accent_color || '#14b8a6' }}</span>
                </div>
                <div v-else class="flex min-h-11 items-center gap-3 border border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] px-3 py-2.5">
                  <span class="h-7 w-7 border border-[color:var(--ds-border-strong)]" :style="{ backgroundColor: settings.app_accent_color || '#14b8a6' }"></span>
                  <span class="font-mono text-xs font-bold text-[color:var(--ds-text)]">{{ settings.app_accent_color || '#14b8a6' }}</span>
                </div>
              </div>

              <div class="ds-field-group">
                <label class="ds-field-label">Preset visual</label>
                <select v-if="editSettings" v-model="form.app_theme_preset" class="ds-field">
                  <option v-for="preset in themePresets" :key="preset.value" :value="preset.value">{{ preset.label }}</option>
                </select>
                <div v-else class="min-h-11 border border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] px-3 py-2.5 text-sm font-semibold text-[color:var(--ds-text)]">{{ selectedThemePreset }}</div>
                <p v-if="form.errors.app_theme_preset" class="ds-field-error">{{ form.errors.app_theme_preset }}</p>
              </div>

              <div class="ds-field-group">
                <label class="ds-field-label">Modo operacional</label>
                <select v-if="editSettings" v-model="form.app_operation_mode" class="ds-field">
                  <option v-for="mode in operationModes" :key="mode.value" :value="mode.value">{{ mode.label }}</option>
                </select>
                <div v-else class="min-h-11 border border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] px-3 py-2.5 text-sm font-semibold text-[color:var(--ds-text)]">{{ selectedOperationMode }}</div>
                <p v-if="form.errors.app_operation_mode" class="ds-field-error">{{ form.errors.app_operation_mode }}</p>
              </div>
            </div>

            <div class="border-t border-[color:var(--ds-border)] p-5">
              <div class="flex flex-col gap-4 p-5 text-white sm:flex-row sm:items-center sm:justify-between" :style="themePreviewStyle">
                <div>
                  <p class="text-xs font-bold uppercase text-white/75">{{ form.app_theme_preset || 'corporate' }}</p>
                  <h3 class="mt-2 text-lg font-bold">{{ form.app_name || 'LIMS Unleashed' }}</h3>
                  <p class="mt-1 text-xs text-white/85">{{ form.app_slogan || 'Rastreabilidade e operação laboratorial robusta.' }}</p>
                </div>
                <div class="border-l-4 border-white/50 px-4 py-2">
                  <p class="text-xs font-bold uppercase text-white/70">Modo</p>
                  <p class="mt-1 text-sm font-bold">{{ selectedOperationMode }}</p>
                </div>
              </div>
            </div>
          </section>
        </template>

        <template v-else-if="selectedTab === '#messaging'">
          <section class="ds-panel overflow-hidden">
            <div class="flex items-start gap-3 border-b border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] px-5 py-4">
              <EnvelopeIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
              <div>
                <h2 class="ds-heading text-base">Emails e notificações geridos</h2>
                <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Defina a voz base da plataforma e os conteúdos iniciais das notificações.</p>
              </div>
            </div>

            <div class="grid gap-6 p-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
              <div class="space-y-6">
                <section>
                  <div class="flex items-center gap-2 border-b border-[color:var(--ds-border)] pb-3">
                    <EnvelopeIcon class="h-4 w-4 text-primary-700 dark:text-primary-300" />
                    <h3 class="ds-heading text-sm">Shell do email</h3>
                  </div>
                  <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <SettingsField v-model="form.app_mail_greeting" label="Saudação principal" :editing="editSettings" :display-value="settings.app_mail_greeting" :error="form.errors.app_mail_greeting" wide />
                    <SettingsField v-model="form.app_mail_footer" label="Rodapé contextual" :editing="editSettings" :display-value="settings.app_mail_footer" :error="form.errors.app_mail_footer" multiline wide />
                    <SettingsField v-model="form.app_mail_salutation" label="Fecho do email" :editing="editSettings" :display-value="settings.app_mail_salutation" :error="form.errors.app_mail_salutation" multiline />
                    <SettingsField v-model="form.app_mail_signature_name" label="Nome da assinatura" :editing="editSettings" :display-value="settings.app_mail_signature_name" :error="form.errors.app_mail_signature_name" />
                    <SettingsField v-model="form.app_mail_subcopy" label="Texto auxiliar do botão ou link" :editing="editSettings" :display-value="settings.app_mail_subcopy" :error="form.errors.app_mail_subcopy" multiline wide />
                  </div>
                </section>

                <section>
                  <div class="flex items-center gap-2 border-b border-[color:var(--ds-border)] pb-3">
                    <BellIcon class="h-4 w-4 text-primary-700 dark:text-primary-300" />
                    <h3 class="ds-heading text-sm">Defaults de notificação</h3>
                  </div>
                  <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <SettingsField v-model="form.app_notification_sender_alias" label="Nome do remetente" :editing="editSettings" :display-value="settings.app_notification_sender_alias" :error="form.errors.app_notification_sender_alias" wide />
                    <SettingsField v-model="form.app_notification_default_title" label="Título padrão" :editing="editSettings" :display-value="settings.app_notification_default_title" :error="form.errors.app_notification_default_title" />
                    <SettingsField v-model="form.app_notification_default_message" label="Mensagem padrão" :editing="editSettings" :display-value="settings.app_notification_default_message" :error="form.errors.app_notification_default_message" multiline />
                    <SettingsField v-model="form.app_notification_email_intro" label="Introdução do email de notificação" :editing="editSettings" :display-value="settings.app_notification_email_intro" :error="form.errors.app_notification_email_intro" multiline wide />
                    <SettingsField v-model="form.app_notification_email_outro" label="Fecho do email de notificação" :editing="editSettings" :display-value="settings.app_notification_email_outro" :error="form.errors.app_notification_email_outro" multiline wide />
                  </div>
                </section>
              </div>

              <aside class="space-y-4">
                <section class="border border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] p-4">
                  <p class="ds-kicker">Pré-visualização do email</p>
                  <div class="mt-4 border border-[color:var(--ds-border)] bg-[color:var(--ds-panel)] p-4">
                    <p class="text-lg font-bold text-[color:var(--ds-text)]">{{ form.app_mail_greeting || settings.app_mail_greeting || 'Olá!' }}</p>
                    <p class="ds-copy mt-4 text-xs">{{ form.app_notification_email_intro || settings.app_notification_email_intro || 'Recebeu uma nova notificação no sistema.' }}</p>
                    <div class="ds-command-toolbar mt-4 p-3">
                      <p class="text-sm font-bold text-[color:var(--ds-text)]">{{ form.app_notification_default_title || settings.app_notification_default_title || 'Notificação do sistema' }}</p>
                      <p class="mt-1 text-xs leading-5 text-[color:var(--ds-text-soft)]">{{ form.app_notification_default_message || settings.app_notification_default_message || 'Existe uma atualização importante disponível para si.' }}</p>
                    </div>
                    <p class="ds-copy mt-4 text-xs">{{ form.app_notification_email_outro || settings.app_notification_email_outro || 'Aceda ao sistema para acompanhar o detalhe completo.' }}</p>
                    <p class="mt-4 whitespace-pre-line text-xs font-semibold text-[color:var(--ds-text)]">
                      {{ form.app_mail_salutation || settings.app_mail_salutation || 'Com os melhores cumprimentos,' }}
                      <br>
                      {{ form.app_mail_signature_name || settings.app_mail_signature_name || form.app_name || settings.app_name || 'LIMS Unleashed' }}
                    </p>
                  </div>
                </section>

                <section class="ds-command-surface p-4">
                  <p class="ds-kicker">Aplicação</p>
                  <ul class="mt-3 space-y-3 text-xs leading-5 text-[color:var(--ds-text-soft)]">
                    <li class="border-l-4 border-primary-500 pl-3">O shell orienta a renderização padrão dos emails.</li>
                    <li class="border-l-4 border-blue-500 pl-3">Os defaults alimentam notificações globais.</li>
                    <li class="border-l-4 border-emerald-500 pl-3">A gestão permanece centralizada no white label.</li>
                  </ul>
                </section>
              </aside>
            </div>
          </section>
        </template>

        <template v-else>
          <section class="ds-panel overflow-hidden">
            <div class="flex items-start gap-3 border-b border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] px-5 py-4">
              <ShieldCheckIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
              <div>
                <h2 class="ds-heading text-base">Assinatura e validação documental</h2>
                <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Elementos criptográficos usados em faturas, recibos, propostas e documentos oficiais.</p>
              </div>
            </div>

            <div class="grid gap-3 border-b border-[color:var(--ds-border)] p-5 lg:grid-cols-3">
              <article
                v-for="status in securityCards"
                :key="status.label"
                class="border-l-4 bg-[color:var(--ds-panel-subtle)] p-4"
                :class="status.ready ? 'border-emerald-500' : 'border-amber-500'"
              >
                <div class="flex items-center justify-between gap-3">
                  <p class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">{{ status.label }}</p>
                  <span class="lims-status-dot" :class="status.ready ? 'lims-status-dot-release' : 'lims-status-dot-hold'" />
                </div>
                <p class="mt-3 text-sm font-bold text-[color:var(--ds-text)]">{{ status.ready ? 'Configurado' : 'Pendente' }}</p>
                <p class="mt-1 text-xs leading-5 text-[color:var(--ds-text-soft)]">{{ status.description }}</p>
              </article>
            </div>

            <div class="grid gap-4 p-5 md:grid-cols-2">
              <SettingsField v-model="form.app_agt_valid_name" label="Entidade validante" :editing="editSettings" :display-value="settings.app_agt_valid_name" :error="form.errors.app_agt_valid_name" />
              <SettingsField v-model="form.app_agt_validation_number" label="Número de validação" :editing="editSettings" :display-value="settings.app_agt_validation_number" :error="form.errors.app_agt_validation_number" />
              <SettingsField v-model="form.app_public_key" label="Chave pública" :editing="editSettings" :display-value="maskedKey(settings.app_public_key)" :error="form.errors.app_public_key" multiline monospace wide :rows="5" placeholder="Cole aqui a chave pública usada para validação." />
              <SettingsField v-model="form.app_private_key" label="Chave privada" :editing="editSettings" :display-value="maskedKey(settings.app_private_key)" :error="form.errors.app_private_key" multiline monospace wide :rows="7" placeholder="Cole aqui a chave privada usada para assinar documentos." />
            </div>
          </section>
        </template>

        <div v-if="editSettings" class="ds-command-surface sticky bottom-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="text-sm font-bold text-[color:var(--ds-text)]">Alterações por publicar</p>
            <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">Reveja a área ativa e guarde quando terminar.</p>
          </div>
          <div class="flex gap-2">
            <button type="button" class="ds-button ds-button-secondary" @click="toggleEdit">Cancelar</button>
            <button type="button" class="ds-button ds-button-primary" :disabled="form.processing" @click="submit">
              <ArrowUpOnSquareIcon class="h-4 w-4" />
              {{ form.processing ? 'A guardar...' : 'Guardar alterações' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { ColorPicker } from 'vue3-colorpicker'
import {
  ArrowUpOnSquareIcon,
  BellIcon,
  BuildingOfficeIcon,
  ChevronRightIcon,
  Cog6ToothIcon,
  CreditCardIcon,
  EnvelopeIcon,
  LanguageIcon,
  PencilSquareIcon,
  PhotoIcon,
  ShieldCheckIcon,
  SwatchIcon,
} from '@heroicons/vue/24/outline'
import SettingsField from '@/Components/settings/SettingsField.vue'

const props = defineProps({
  settings: {
    type: Object,
    required: true,
  },
  model: {
    type: String,
    required: true,
  },
  abilities: {
    type: Array,
    default: () => [],
  },
  securitySummary: {
    type: Object,
    default: () => ({}),
  },
})

const page = usePage()
const editSettings = ref(false)
const selectedTab = ref('#general')

const tabs = [
  {
    name: 'Geral',
    href: '#general',
    description: 'Marca, identidade, white label e modo operacional.',
    icon: Cog6ToothIcon,
  },
  {
    name: 'Segurança',
    href: '#security',
    description: 'Chaves, validação e prontidão documental.',
    icon: ShieldCheckIcon,
  },
  {
    name: 'Mensagens',
    href: '#messaging',
    description: 'Email, notificações e experiência de entrada.',
    icon: EnvelopeIcon,
  },
]

const identityFields = [
  { key: 'app_name', label: 'Nome da aplicação' },
  { key: 'app_version', label: 'Versão da aplicação' },
  { key: 'app_slogan', label: 'Slogan' },
  { key: 'app_nif', label: 'NIF' },
  { key: 'app_contact', label: 'Contacto' },
  { key: 'app_email', label: 'Email', type: 'email' },
]

const organizationFields = [
  { key: 'app_client_name', label: 'Nome da organização' },
  { key: 'app_client_nif', label: 'NIF da organização' },
  { key: 'app_client_address', label: 'Morada' },
  { key: 'app_client_contact', label: 'Contacto institucional' },
  { key: 'app_client_email', label: 'Email institucional', type: 'email' },
  { key: 'app_client_lab_name', label: 'Nome do laboratório' },
  { key: 'app_client_lab_province', label: 'Província' },
  { key: 'app_client_lab_director', label: 'Direção técnica' },
  { key: 'app_client_lab_slogan', label: 'Slogan do laboratório' },
]

const bankingFields = [
  { key: 'app_bank_name', label: 'Banco', placeholder: 'Banco emissor ou banco de recebimento' },
  { key: 'app_bank_account_name', label: 'Titular da conta', placeholder: 'Nome legal do titular' },
  { key: 'app_bank_account_number', label: 'Número de conta', placeholder: 'Conta bancária local' },
  { key: 'app_bank_iban', label: 'IBAN', placeholder: 'AO06...' },
  { key: 'app_bank_swift', label: 'SWIFT/BIC', placeholder: 'Código SWIFT/BIC, quando aplicável' },
  {
    key: 'app_bank_details',
    label: 'Observações bancárias',
    placeholder: 'Instruções de pagamento, referência obrigatória, moeda e comprovativo.',
    multiline: true,
  },
  {
    key: 'app_document_keywords',
    label: 'Palavras-chave documentais',
    placeholder: 'ISO 17025; rastreabilidade; controlo documental; ensaios laboratoriais',
    multiline: true,
  },
]

const form = useForm({ ...props.settings })

const themePresets = [
  { value: 'corporate', label: 'Corporate' },
  { value: 'clinical', label: 'Clinical' },
  { value: 'executive', label: 'Executive' },
  { value: 'vibrant', label: 'Vibrant' },
]

const operationModes = [
  { value: 'client_only', label: 'Apenas clientes' },
  { value: 'internal_only', label: 'Apenas interno' },
  { value: 'hybrid', label: 'Híbrido' },
]

const currentLanguage = computed(() => {
  const current = page.props.languages?.data?.find(language => language.value === page.props.language)

  return current?.label || page.props.language || 'Português'
})

const settingsFallback = computed(() => ({
  app_theme_preset: props.settings.app_theme_preset || 'corporate',
  app_operation_mode: props.settings.app_operation_mode || 'client_only',
}))

const selectedThemePreset = computed(() => {
  return themePresets.find(preset => preset.value === (form.app_theme_preset || settingsFallback.value.app_theme_preset))?.label ?? 'Corporate'
})

const selectedOperationMode = computed(() => {
  return operationModes.find(mode => mode.value === (form.app_operation_mode || settingsFallback.value.app_operation_mode))?.label ?? 'Apenas clientes'
})

const themePreviewStyle = computed(() => ({
  background: `linear-gradient(135deg, ${form.app_secondary_color || props.settings.app_secondary_color || '#0f172a'} 0%, ${form.app_primary_color || props.settings.app_primary_color || '#1f87e8'} 60%, ${form.app_accent_color || props.settings.app_accent_color || '#14b8a6'} 100%)`,
}))

const securityCards = computed(() => [
  {
    label: 'Chave privada',
    ready: Boolean(props.securitySummary.private_key_configured),
    description: 'Necessária para assinatura criptográfica dos documentos emitidos.',
  },
  {
    label: 'Chave pública',
    ready: Boolean(props.securitySummary.public_key_configured),
    description: 'Usada para validação e consulta posterior da assinatura.',
  },
  {
    label: 'Referência de validação',
    ready: Boolean(props.securitySummary.validation_number_configured),
    description: 'Aparece nos documentos emitidos e reforça a rastreabilidade externa.',
  },
])

const maskedKey = (value) => {
  if (!value) {
    return 'Não configurada'
  }

  if (value.length <= 18) {
    return value
  }

  return `${value.slice(0, 14)}...${value.slice(-14)}`
}

const toggleEdit = () => {
  editSettings.value = !editSettings.value

  if (!editSettings.value) {
    form.defaults({ ...props.settings })
    form.reset()
    form.clearErrors()
  }
}

const submit = () => {
  form.post(route('generalsettings.update'), {
    preserveScroll: true,
    onSuccess: () => {
      form.defaults(form.data())
      editSettings.value = false
    },
  })
}
</script>
