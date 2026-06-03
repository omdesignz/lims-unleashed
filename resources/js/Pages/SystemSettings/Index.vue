<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
      <div class="bg-gradient-to-r from-primary-950 via-primary-900 to-primary-700 px-6 py-8 text-white sm:px-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
          <div class="max-w-3xl space-y-3">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-primary-100">Configuração operacional</p>
            <h1 class="text-3xl font-semibold tracking-tight">{{ form.app_name || settings.app_name || 'Configurações gerais' }}</h1>
            <p class="text-sm leading-7 text-slate-200 sm:text-base">
              Centralize identidade institucional, assinatura documental e dados do laboratório para manter os fluxos coerentes em certificados,
              propostas, portal do cliente e relatórios.
            </p>
          </div>

          <div class="flex flex-col gap-3 sm:flex-row">
            <button
              type="button"
              class="inline-flex items-center justify-center gap-2 rounded-full border border-white/20 px-5 py-3 text-sm font-semibold text-white transition hover:border-primary-300 hover:bg-white/10"
              @click="toggleEdit"
            >
              <PencilSquareIcon class="h-4 w-4" />
              {{ editSettings ? 'Cancelar edição' : 'Editar definições' }}
            </button>
            <button
              v-if="editSettings"
              type="button"
              class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-primary-100 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="form.processing"
              @click="submit"
            >
              <ArrowUpOnSquareIcon class="h-4 w-4" />
              {{ form.processing ? 'A guardar...' : 'Guardar alterações' }}
            </button>
          </div>
        </div>
      </div>

      <div class="grid gap-4 border-t border-slate-200 bg-slate-50 px-6 py-5 sm:grid-cols-2 xl:grid-cols-4 sm:px-8">
        <article class="rounded-2xl border border-slate-200 bg-white p-4">
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Marca</p>
          <p class="mt-2 text-lg font-semibold text-slate-900">{{ settings.app_name || 'Não configurado' }}</p>
          <p class="mt-1 text-sm text-slate-600">{{ settings.app_version || 'Sem versão definida' }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-4">
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Documentos assináveis</p>
          <p class="mt-2 text-lg font-semibold text-slate-900">{{ securitySummary.private_key_configured && securitySummary.public_key_configured ? 'Pronto' : 'Pendente' }}</p>
          <p class="mt-1 text-sm text-slate-600">Chave pública e privada para assinatura e validação.</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-4">
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Validação</p>
          <p class="mt-2 text-lg font-semibold text-slate-900">{{ settings.app_agt_validation_number || 'Sem número' }}</p>
          <p class="mt-1 text-sm text-slate-600">{{ settings.app_agt_valid_name || 'Entidade validante não configurada' }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-4">
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Segurança</p>
          <p class="mt-2 text-lg font-semibold text-slate-900">{{ securitySummary.two_factor_supported ? 'MFA disponível' : 'MFA indisponível' }}</p>
          <p class="mt-1 text-sm text-slate-600">O idioma da interface é trocado no cabeçalho e a autenticação forte está ativa no perfil.</p>
        </article>
      </div>
    </section>

    <div class="grid gap-8 xl:grid-cols-[18rem,minmax(0,1fr)]">
      <aside class="xl:sticky xl:top-24 xl:self-start">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-600">Secções</h2>
          </div>
          <nav class="space-y-2 p-4">
            <button
              v-for="tab in tabs"
              :key="tab.href"
              type="button"
              class="flex w-full items-start justify-between rounded-2xl px-4 py-3 text-left transition"
              :class="selectedTab === tab.href ? 'bg-slate-950 text-white' : 'text-slate-700 hover:bg-slate-100'"
              @click="selectedTab = tab.href"
            >
              <div>
                <p class="text-sm font-semibold">{{ tab.name }}</p>
                <p class="mt-1 text-xs" :class="selectedTab === tab.href ? 'text-slate-300' : 'text-slate-500'">{{ tab.description }}</p>
              </div>
              <ChevronRightIcon class="mt-0.5 h-4 w-4 shrink-0" />
            </button>
          </nav>
        </div>
      </aside>

      <div class="space-y-8">
        <section v-if="selectedTab === '#general'" class="space-y-8">
          <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex items-center gap-3">
              <Cog6ToothIcon class="h-5 w-5 text-primary-700" />
              <div>
                <h2 class="text-xl font-semibold text-slate-900">Identidade da plataforma</h2>
                <p class="mt-1 text-sm text-slate-600">Dados usados no backoffice, documentos emitidos e comunicação institucional.</p>
              </div>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-2">
              <div v-for="field in identityFields" :key="field.key" class="space-y-2">
                <label class="block text-sm font-medium text-slate-700">{{ field.label }}</label>
                <input
                  v-if="editSettings"
                  v-model="form[field.key]"
                  :type="field.type || 'text'"
                  class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20"
                />
                <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">
                  {{ settings[field.key] || '—' }}
                </div>
                <p v-if="form.errors[field.key]" class="text-xs text-red-600">{{ form.errors[field.key] }}</p>
              </div>

              <div class="space-y-2 md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Cor principal</label>
                <div v-if="editSettings" class="flex flex-wrap items-center gap-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                  <ColorPicker
                    v-model:pure-color="form.app_primary_color"
                    format="hex"
                    shape="circle"
                    lang="Pt"
                    picker-type="chrome"
                    disable-history="true"
                    disable-alpha="true"
                  />
                  <span class="text-sm text-slate-700">{{ form.app_primary_color || '#1f87e8' }}</span>
                </div>
                <div v-else class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">
                  <span class="h-8 w-8 rounded-full border border-slate-200" :style="{ backgroundColor: settings.app_primary_color || '#1f87e8' }"></span>
                  <span>{{ settings.app_primary_color || '#1f87e8' }}</span>
                </div>
                <p v-if="form.errors.app_primary_color" class="text-xs text-red-600">{{ form.errors.app_primary_color }}</p>
              </div>
            </div>
          </article>

          <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex items-center gap-3">
              <BuildingOfficeIcon class="h-5 w-5 text-primary-700" />
              <div>
                <h2 class="text-xl font-semibold text-slate-900">Informação institucional do laboratório</h2>
                <p class="mt-1 text-sm text-slate-600">Dados usados em portal, certificados, propostas e identificação externa.</p>
              </div>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-2">
              <div v-for="field in organizationFields" :key="field.key" class="space-y-2">
                <label class="block text-sm font-medium text-slate-700">{{ field.label }}</label>
                <input
                  v-if="editSettings"
                  v-model="form[field.key]"
                  :type="field.type || 'text'"
                  class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20"
                />
                <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">
                  {{ settings[field.key] || '—' }}
                </div>
                <p v-if="form.errors[field.key]" class="text-xs text-red-600">{{ form.errors[field.key] }}</p>
              </div>
            </div>
          </article>

          <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex items-center gap-3">
              <CreditCardIcon class="h-5 w-5 text-primary-700" />
              <div>
                <h2 class="text-xl font-semibold text-slate-900">Dados bancários e palavras-chave documentais</h2>
                <p class="mt-1 text-sm text-slate-600">Informação usada nos documentos comerciais, propostas e rodapés de controlo documental.</p>
              </div>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-2">
              <div v-for="field in bankingFields" :key="field.key" class="space-y-2" :class="field.multiline ? 'md:col-span-2' : ''">
                <label class="block text-sm font-medium text-slate-700">{{ field.label }}</label>
                <textarea
                  v-if="editSettings && field.multiline"
                  v-model="form[field.key]"
                  rows="3"
                  class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20"
                  :placeholder="field.placeholder"
                ></textarea>
                <input
                  v-else-if="editSettings"
                  v-model="form[field.key]"
                  type="text"
                  class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20"
                  :placeholder="field.placeholder"
                />
                <div v-else class="whitespace-pre-line rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">
                  {{ settings[field.key] || '—' }}
                </div>
                <p v-if="form.errors[field.key]" class="text-xs text-red-600">{{ form.errors[field.key] }}</p>
              </div>
            </div>
          </article>

          <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex items-center gap-3">
              <PhotoIcon class="h-5 w-5 text-primary-700" />
              <div>
                <h2 class="text-xl font-semibold text-slate-900">Ativos da marca e experiência de entrada</h2>
                <p class="mt-1 text-sm text-slate-600">Logo, headline e mensagem de entrada usados na landing e no ecrã de login.</p>
              </div>
            </div>

            <div class="mt-6 grid gap-5 xl:grid-cols-[1.1fr,0.9fr]">
              <div class="space-y-5">
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-slate-700">URL do logótipo</label>
                  <input
                    v-if="editSettings"
                    v-model="form.app_logo_url"
                    type="text"
                    class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20"
                    placeholder="https://.../logo.svg"
                  />
                  <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">
                    {{ settings.app_logo_url || '—' }}
                  </div>
                  <p v-if="form.errors.app_logo_url" class="text-xs text-red-600">{{ form.errors.app_logo_url }}</p>
                </div>

                <div class="space-y-2">
                  <label class="block text-sm font-medium text-slate-700">Headline do login</label>
                  <input
                    v-if="editSettings"
                    v-model="form.app_login_headline"
                    type="text"
                    class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20"
                  />
                  <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">
                    {{ settings.app_login_headline || '—' }}
                  </div>
                  <p v-if="form.errors.app_login_headline" class="text-xs text-red-600">{{ form.errors.app_login_headline }}</p>
                </div>

                <div class="space-y-2">
                  <label class="block text-sm font-medium text-slate-700">Subheadline do login</label>
                  <textarea
                    v-if="editSettings"
                    v-model="form.app_login_subheadline"
                    rows="3"
                    class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20"
                  ></textarea>
                  <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">
                    {{ settings.app_login_subheadline || '—' }}
                  </div>
                  <p v-if="form.errors.app_login_subheadline" class="text-xs text-red-600">{{ form.errors.app_login_subheadline }}</p>
                </div>
              </div>

              <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Pré-visualização da marca</p>
                <div class="mt-4 rounded-[1.75rem] border border-white bg-white p-5 shadow-sm">
                  <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 p-3">
                      <img
                        v-if="form.app_logo_url || props.settings.app_logo_url"
                        :src="form.app_logo_url || props.settings.app_logo_url"
                        alt="Logótipo"
                        class="max-h-full max-w-full object-contain"
                      />
                      <SwatchIcon v-else class="h-7 w-7 text-slate-400" />
                    </div>
                    <div>
                      <p class="text-lg font-semibold text-slate-900">{{ form.app_name || props.settings.app_name || 'LIMS Unleashed' }}</p>
                      <p class="mt-1 text-sm text-slate-600">{{ form.app_slogan || props.settings.app_slogan || 'Rastreabilidade, qualidade e conformidade para laboratórios modernos.' }}</p>
                    </div>
                  </div>

                  <div class="mt-5 rounded-[1.5rem] p-5 text-white shadow-sm" :style="themePreviewStyle">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-white/75">Login</p>
                    <h3 class="mt-3 text-2xl font-semibold">{{ form.app_login_headline || props.settings.app_login_headline || 'Bem-vindo de volta' }}</h3>
                    <p class="mt-2 text-sm leading-7 text-white/80">
                      {{ form.app_login_subheadline || props.settings.app_login_subheadline || 'Aceda à operação, acompanhe a rastreabilidade e mantenha o laboratório sob controlo.' }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </article>

          <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex items-center gap-3">
              <LanguageIcon class="h-5 w-5 text-primary-700" />
              <div>
                <h2 class="text-xl font-semibold text-slate-900">Idioma e apresentação</h2>
                <p class="mt-1 text-sm text-slate-600">O idioma ativo do utilizador continua disponível no cabeçalho do sistema.</p>
              </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Idioma de sessão</p>
                <p class="mt-2 text-base font-semibold text-slate-900">{{ currentLanguage }}</p>
              </div>
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Formato de data recomendado</p>
                <p class="mt-2 text-base font-semibold text-slate-900">DD-MM-YYYY</p>
              </div>
            </div>
          </article>

          <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex items-center gap-3">
              <BuildingOfficeIcon class="h-5 w-5 text-primary-700" />
              <div>
                <h2 class="text-xl font-semibold text-slate-900">White label e modo operacional</h2>
                <p class="mt-1 text-sm text-slate-600">Defina a paleta institucional, o preset visual e se a operação é apenas para clientes, interna ou híbrida.</p>
              </div>
            </div>

            <div class="mt-6 grid gap-5 lg:grid-cols-2">
              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700">Cor secundária</label>
                <div v-if="editSettings" class="flex flex-wrap items-center gap-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                  <ColorPicker v-model:pure-color="form.app_secondary_color" format="hex" shape="circle" lang="Pt" picker-type="chrome" disable-history="true" disable-alpha="true" />
                  <span class="text-sm text-slate-700">{{ form.app_secondary_color || '#0f172a' }}</span>
                </div>
                <div v-else class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">
                  <span class="h-8 w-8 rounded-full border border-slate-200" :style="{ backgroundColor: settings.app_secondary_color || '#0f172a' }"></span>
                  <span>{{ settings.app_secondary_color || '#0f172a' }}</span>
                </div>
                <p v-if="form.errors.app_secondary_color" class="text-xs text-red-600">{{ form.errors.app_secondary_color }}</p>
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700">Cor de destaque</label>
                <div v-if="editSettings" class="flex flex-wrap items-center gap-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                  <ColorPicker v-model:pure-color="form.app_accent_color" format="hex" shape="circle" lang="Pt" picker-type="chrome" disable-history="true" disable-alpha="true" />
                  <span class="text-sm text-slate-700">{{ form.app_accent_color || '#14b8a6' }}</span>
                </div>
                <div v-else class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">
                  <span class="h-8 w-8 rounded-full border border-slate-200" :style="{ backgroundColor: settings.app_accent_color || '#14b8a6' }"></span>
                  <span>{{ settings.app_accent_color || '#14b8a6' }}</span>
                </div>
                <p v-if="form.errors.app_accent_color" class="text-xs text-red-600">{{ form.errors.app_accent_color }}</p>
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700">Preset visual</label>
                <select
                  v-if="editSettings"
                  v-model="form.app_theme_preset"
                  class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20"
                >
                  <option v-for="preset in themePresets" :key="preset.value" :value="preset.value">{{ preset.label }}</option>
                </select>
                <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">
                  {{ selectedThemePreset }}
                </div>
                <p v-if="form.errors.app_theme_preset" class="text-xs text-red-600">{{ form.errors.app_theme_preset }}</p>
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700">Modo operacional</label>
                <select
                  v-if="editSettings"
                  v-model="form.app_operation_mode"
                  class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20"
                >
                  <option v-for="mode in operationModes" :key="mode.value" :value="mode.value">{{ mode.label }}</option>
                </select>
                <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">
                  {{ selectedOperationMode }}
                </div>
                <p v-if="form.errors.app_operation_mode" class="text-xs text-red-600">{{ form.errors.app_operation_mode }}</p>
              </div>
            </div>

            <div class="mt-6 rounded-3xl border border-slate-200 bg-slate-50 p-5">
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Pré-visualização do tema</p>
              <div class="mt-4 rounded-[1.75rem] p-6 text-white shadow-sm" :style="themePreviewStyle">
                <div class="flex items-start justify-between gap-4">
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-white/75">{{ form.app_theme_preset || 'corporate' }}</p>
                    <h3 class="mt-2 text-2xl font-semibold">{{ form.app_name || 'LIMS Unleashed' }}</h3>
                    <p class="mt-2 max-w-xl text-sm text-white/80">{{ form.app_slogan || 'Rastreabilidade, relatórios premium e operação laboratorial robusta.' }}</p>
                  </div>
                  <div class="rounded-2xl bg-white/10 px-4 py-3 text-right">
                    <p class="text-xs uppercase tracking-[0.18em] text-white/70">Modo</p>
                    <p class="mt-1 text-lg font-semibold">{{ selectedOperationMode }}</p>
                  </div>
                </div>
              </div>
            </div>
          </article>
        </section>

        <section v-else-if="selectedTab === '#messaging'" class="space-y-8">
          <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex items-center gap-3">
              <EnvelopeIcon class="h-5 w-5 text-primary-700" />
              <div>
                <h2 class="text-xl font-semibold text-slate-900">Emails e notificações geridos</h2>
                <p class="mt-1 text-sm text-slate-600">Defina a voz base da plataforma para o shell dos emails e para os defaults usados em notificações.</p>
              </div>
            </div>

            <div class="mt-6 grid gap-8 xl:grid-cols-[1.1fr,0.9fr]">
              <div class="space-y-8">
                <div>
                  <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-600">Shell do email</h3>
                  <div class="mt-4 grid gap-5">
                    <div class="space-y-2">
                      <label class="block text-sm font-medium text-slate-700">Saudação principal</label>
                      <input v-if="editSettings" v-model="form.app_mail_greeting" type="text" class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20" />
                      <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">{{ settings.app_mail_greeting || '—' }}</div>
                      <p v-if="form.errors.app_mail_greeting" class="text-xs text-red-600">{{ form.errors.app_mail_greeting }}</p>
                    </div>

                    <div class="space-y-2">
                      <label class="block text-sm font-medium text-slate-700">Rodapé contextual</label>
                      <textarea v-if="editSettings" v-model="form.app_mail_footer" rows="3" class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20"></textarea>
                      <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">{{ settings.app_mail_footer || '—' }}</div>
                      <p v-if="form.errors.app_mail_footer" class="text-xs text-red-600">{{ form.errors.app_mail_footer }}</p>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                      <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-700">Fecho do email</label>
                        <textarea v-if="editSettings" v-model="form.app_mail_salutation" rows="3" class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20"></textarea>
                        <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">{{ settings.app_mail_salutation || '—' }}</div>
                        <p v-if="form.errors.app_mail_salutation" class="text-xs text-red-600">{{ form.errors.app_mail_salutation }}</p>
                      </div>

                      <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-700">Nome da assinatura</label>
                        <input v-if="editSettings" v-model="form.app_mail_signature_name" type="text" class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20" />
                        <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">{{ settings.app_mail_signature_name || '—' }}</div>
                        <p v-if="form.errors.app_mail_signature_name" class="text-xs text-red-600">{{ form.errors.app_mail_signature_name }}</p>
                      </div>
                    </div>

                    <div class="space-y-2">
                      <label class="block text-sm font-medium text-slate-700">Texto auxiliar do botão/link</label>
                      <textarea v-if="editSettings" v-model="form.app_mail_subcopy" rows="3" class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20"></textarea>
                      <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">{{ settings.app_mail_subcopy || '—' }}</div>
                      <p v-if="form.errors.app_mail_subcopy" class="text-xs text-red-600">{{ form.errors.app_mail_subcopy }}</p>
                    </div>
                  </div>
                </div>

                <div>
                  <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-600">Defaults de notificação</h3>
                  <div class="mt-4 grid gap-5">
                    <div class="space-y-2">
                      <label class="block text-sm font-medium text-slate-700">Nome do remetente</label>
                      <input v-if="editSettings" v-model="form.app_notification_sender_alias" type="text" class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20" />
                      <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">{{ settings.app_notification_sender_alias || '—' }}</div>
                      <p v-if="form.errors.app_notification_sender_alias" class="text-xs text-red-600">{{ form.errors.app_notification_sender_alias }}</p>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                      <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-700">Título padrão</label>
                        <input v-if="editSettings" v-model="form.app_notification_default_title" type="text" class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20" />
                        <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">{{ settings.app_notification_default_title || '—' }}</div>
                        <p v-if="form.errors.app_notification_default_title" class="text-xs text-red-600">{{ form.errors.app_notification_default_title }}</p>
                      </div>

                      <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-700">Mensagem padrão</label>
                        <textarea v-if="editSettings" v-model="form.app_notification_default_message" rows="3" class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20"></textarea>
                        <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">{{ settings.app_notification_default_message || '—' }}</div>
                        <p v-if="form.errors.app_notification_default_message" class="text-xs text-red-600">{{ form.errors.app_notification_default_message }}</p>
                      </div>
                    </div>

                    <div class="space-y-2">
                      <label class="block text-sm font-medium text-slate-700">Introdução do email de notificação</label>
                      <textarea v-if="editSettings" v-model="form.app_notification_email_intro" rows="3" class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20"></textarea>
                      <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">{{ settings.app_notification_email_intro || '—' }}</div>
                      <p v-if="form.errors.app_notification_email_intro" class="text-xs text-red-600">{{ form.errors.app_notification_email_intro }}</p>
                    </div>

                    <div class="space-y-2">
                      <label class="block text-sm font-medium text-slate-700">Fecho do email de notificação</label>
                      <textarea v-if="editSettings" v-model="form.app_notification_email_outro" rows="3" class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20"></textarea>
                      <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">{{ settings.app_notification_email_outro || '—' }}</div>
                      <p v-if="form.errors.app_notification_email_outro" class="text-xs text-red-600">{{ form.errors.app_notification_email_outro }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <aside class="space-y-4">
                <section class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                  <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Pré-visualização do email</p>
                  <div class="mt-4 rounded-[1.5rem] border border-white bg-white p-5 shadow-sm">
                    <p class="text-xl font-semibold text-slate-900">{{ form.app_mail_greeting || props.settings.app_mail_greeting || 'Olá!' }}</p>
                    <p class="mt-4 text-sm leading-7 text-slate-600">{{ form.app_notification_email_intro || props.settings.app_notification_email_intro || 'Recebeu uma nova notificação no sistema.' }}</p>
                    <div class="mt-4 rounded-2xl bg-slate-100 px-4 py-3">
                      <p class="text-sm font-semibold text-slate-900">{{ form.app_notification_default_title || props.settings.app_notification_default_title || 'Notificação do sistema' }}</p>
                      <p class="mt-1 text-sm text-slate-600">{{ form.app_notification_default_message || props.settings.app_notification_default_message || 'Existe uma atualização importante disponível para si no sistema.' }}</p>
                    </div>
                    <p class="mt-4 text-sm leading-7 text-slate-600">{{ form.app_notification_email_outro || props.settings.app_notification_email_outro || 'Aceda ao sistema para acompanhar o detalhe completo e agir em tempo útil.' }}</p>
                    <p class="mt-4 whitespace-pre-line text-sm text-slate-900">
                      {{ form.app_mail_salutation || props.settings.app_mail_salutation || 'Com os melhores cumprimentos,' }}
                      <br>
                      {{ form.app_mail_signature_name || props.settings.app_mail_signature_name || form.app_name || props.settings.app_name || 'LIMS Unleashed' }}
                    </p>
                  </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                  <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Como isto é usado</p>
                  <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                    <li>O shell do email influencia a renderização padrão de notificações por email.</li>
                    <li>Os defaults de notificação alimentam o remetente e o conteúdo inicial das notificações globais.</li>
                    <li>As alterações ficam centralizadas no mesmo fluxo de white label da plataforma.</li>
                  </ul>
                </section>
              </aside>
            </div>
          </article>
        </section>

        <section v-else class="space-y-8">
          <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex items-center gap-3">
              <ShieldCheckIcon class="h-5 w-5 text-primary-700" />
              <div>
                <h2 class="text-xl font-semibold text-slate-900">Assinatura e validação documental</h2>
                <p class="mt-1 text-sm text-slate-600">Controle os elementos usados para assinar faturas, recibos, propostas e documentos oficiais.</p>
              </div>
            </div>

            <div class="mt-6 grid gap-4 lg:grid-cols-3">
              <div
                v-for="status in securityCards"
                :key="status.label"
                class="rounded-2xl border p-4"
                :class="status.ready ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'"
              >
                <p class="text-xs font-semibold uppercase tracking-[0.18em]" :class="status.ready ? 'text-emerald-700' : 'text-amber-700'">
                  {{ status.label }}
                </p>
                <p class="mt-3 text-lg font-semibold text-slate-900">{{ status.ready ? 'Configurado' : 'Pendente' }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ status.description }}</p>
              </div>
            </div>

            <div class="mt-6 grid gap-5">
              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700">Entidade validante</label>
                <input
                  v-if="editSettings"
                  v-model="form.app_agt_valid_name"
                  type="text"
                  class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20"
                />
                <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">
                  {{ settings.app_agt_valid_name || '—' }}
                </div>
                <p v-if="form.errors.app_agt_valid_name" class="text-xs text-red-600">{{ form.errors.app_agt_valid_name }}</p>
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700">Número de validação</label>
                <input
                  v-if="editSettings"
                  v-model="form.app_agt_validation_number"
                  type="text"
                  class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20"
                />
                <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">
                  {{ settings.app_agt_validation_number || '—' }}
                </div>
                <p v-if="form.errors.app_agt_validation_number" class="text-xs text-red-600">{{ form.errors.app_agt_validation_number }}</p>
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700">Chave pública</label>
                <textarea
                  v-if="editSettings"
                  v-model="form.app_public_key"
                  rows="5"
                  class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20"
                  placeholder="Cole aqui a chave pública usada para validação."
                ></textarea>
                <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">
                  {{ maskedKey(settings.app_public_key) }}
                </div>
                <p v-if="form.errors.app_public_key" class="text-xs text-red-600">{{ form.errors.app_public_key }}</p>
              </div>

              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700">Chave privada</label>
                <textarea
                  v-if="editSettings"
                  v-model="form.app_private_key"
                  rows="7"
                  class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-primary-600 focus:ring-2 focus:ring-primary-600/20"
                  placeholder="Cole aqui a chave privada usada para assinar documentos."
                ></textarea>
                <div v-else class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900">
                  {{ maskedKey(settings.app_private_key) }}
                </div>
                <p v-if="form.errors.app_private_key" class="text-xs text-red-600">{{ form.errors.app_private_key }}</p>
              </div>
            </div>
          </article>
        </section>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { useForm, usePage } from '@inertiajs/vue3'
import { ColorPicker } from 'vue3-colorpicker'
import {
  ArrowUpOnSquareIcon,
  BuildingOfficeIcon,
  BellIcon,
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
  },
  {
    name: 'Segurança',
    href: '#security',
    description: 'Chaves, validação e prontidão documental.',
  },
  {
    name: 'Mensagens',
    href: '#messaging',
    description: 'Texto gerido para email, notificações e experiência de entrada.',
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
  { key: 'app_bank_name', label: 'Banco', placeholder: 'Banco emissor / banco de recebimento' },
  { key: 'app_bank_account_name', label: 'Titular da conta', placeholder: 'Nome legal do titular' },
  { key: 'app_bank_account_number', label: 'Número de conta', placeholder: 'Conta bancária local' },
  { key: 'app_bank_iban', label: 'IBAN', placeholder: 'AO06...' },
  { key: 'app_bank_swift', label: 'SWIFT/BIC', placeholder: 'Código SWIFT/BIC, quando aplicável' },
  {
    key: 'app_bank_details',
    label: 'Observações bancárias',
    placeholder: 'Instruções de pagamento, referência obrigatória, moeda, comprovativo, etc.',
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
  const current = page.props.languages?.data?.find((language) => language.value === page.props.language)

  return current?.label || page.props.language || 'Português'
})

const selectedThemePreset = computed(() => {
  return themePresets.find((preset) => preset.value === (form.app_theme_preset || settingsFallback.value.app_theme_preset))?.label ?? 'Corporate'
})

const selectedOperationMode = computed(() => {
  return operationModes.find((mode) => mode.value === (form.app_operation_mode || settingsFallback.value.app_operation_mode))?.label ?? 'Apenas clientes'
})

const settingsFallback = computed(() => ({
  app_theme_preset: props.settings.app_theme_preset || 'corporate',
  app_operation_mode: props.settings.app_operation_mode || 'client_only',
}))

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
