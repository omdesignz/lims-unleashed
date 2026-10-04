<?php

namespace App\Http\Middleware;

use App\Http\Resources\LanguageResource;
use App\Lang\Lang;
use App\Services\LabNetworkAccess;
use App\Services\LaboratoryWorkflowOwnership;
use App\Settings\GeneralSettings;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Laravel\Fortify\Features;
use Throwable;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     */
    public function share(Request $request): array
    {
        if ($request->wantsModal()) {
            return [];
        }

        if (! $this->requestIsPortal($request)) {

            return array_merge(parent::share($request), [
                'auth' => function () use ($request) {
                    $user = $request->user();

                    return [
                        'user' => $user ? [
                            'id' => $user->id,
                            'name' => $user->name,
                            'profile_photo_url' => $user->profile_photo_url ?? null,
                            'dashboard_header_image' => $user->dashboard_header_image ?? null,
                            'email' => $user->email,
                            'two_factor_secret' => $user->two_factor_secret ? true : false,
                            'signature_url' => $user?->signature_url ?? null,
                            'roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames() : collect(),
                            'permissions' => method_exists($user, 'getAllPermissions') ? $user->getAllPermissions()->pluck('name') : collect(),
                            'unread_notifications' => app(LaboratoryWorkflowOwnership::class)
                                ->scopeStoredNotifications($user->unreadNotifications()->getQuery(), $user)->get(),
                            'last_login_at' => $user->last_login_at ?? null,
                            'last_activity_at' => $user->last_activity_at ?? null,
                            'email_verified_at' => $user->email_verified_at ? true : false,
                        ] : null,
                    ];
                },
                'language' => app()->getLocale(),
                'languages' => LanguageResource::collection(Lang::cases()),
                'errorBags' => function () {
                    return collect(optional(session()->get('errors'))->getBags() ?: [])->mapWithKeys(function ($bag, $key) {
                        return [$key => $bag->messages()];
                    })->all();
                },
                'ziggy' => function () use ($request) {
                    return array_merge((new Ziggy)->toArray(), [
                        'location' => $request->url(),
                    ]);
                },
                'popstate' => false,
                'settings' => fn (GeneralSettings $settings) => $this->sharedBrandSettings($settings),
                'laboratory' => fn () => $request->user()
                    ? app(LabNetworkAccess::class)->context($request->user(), $request->session()->get('active_lab_id'))
                    : ['labs' => [], 'active_lab' => null],
                'breadcrumbs' => $this->generateBreadcrumbs($request),
                'impersonation' => session()->has('impersonate'),
                'toast' => session('toast'),
                'fortify' => function () use ($request) {
                    $user = $request->user();

                    return [
                        // 'canCreateTeams' => $user && Jetstream::userHasTeamFeatures($user) && Gate::forUser($user)->check('create', Jetstream::newTeamModel()),
                        'canManageTwoFactorAuthentication' => Features::canManageTwoFactorAuthentication(),
                        'canUpdatePassword' => Features::enabled(Features::updatePasswords()),
                        'canUpdateProfileInformation' => Features::enabled(Features::updateProfileInformation()),
                        'hasEmailVerification' => Features::enabled(Features::emailVerification()),
                        // 'hasAccountDeletionFeatures' => Jetstream::hasAccountDeletionFeatures(),
                        // 'hasApiFeatures' => Jetstream::hasApiFeatures(),
                        // 'hasTeamFeatures' => Jetstream::hasTeamFeatures(),
                        // 'hasTermsAndPrivacyPolicyFeature' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
                        // 'managesProfilePhotos' => Jetstream::managesProfilePhotos(),
                    ];
                },
                'socialAuth' => fn () => $this->socialAuthConfig(),
            ]);
        }

        return array_merge(parent::share($request), [
            'auth' => function () use ($request) {
                $portalUser = $request->user('portal');

                return [
                    'user' => $portalUser ? [
                        'id' => $portalUser->id,
                        'name' => $portalUser?->customer?->name,
                        'address' => $portalUser->address,
                        'profile_photo_url' => $portalUser->profile_photo_url,
                        'signature_url' => $portalUser->signature_url,
                        'email' => $portalUser->email,
                        'two_factor_secret' => $portalUser->two_factor_secret ? true : false,
                        'last_login_at' => $portalUser->last_login_at ?? null,
                        'last_activity_at' => $portalUser->last_activity_at ?? null,
                        'email_verified_at' => $portalUser->email_verified_at ? true : false,
                    ] : null,
                ];
            },
            'language' => app()->getLocale(),
            'languages' => LanguageResource::collection(Lang::cases()),
            'errorBags' => function () {
                return collect(optional(session()->get('errors'))->getBags() ?: [])->mapWithKeys(function ($bag, $key) {
                    return [$key => $bag->messages()];
                })->all();
            },
            'ziggy' => function () use ($request) {
                return array_merge((new Ziggy)->toArray(), [
                    'location' => $request->url(),
                ]);
            },
            'popstate' => false,
            'settings' => fn (GeneralSettings $settings) => $this->sharedBrandSettings($settings),
            'breadcrumbs' => $this->generateBreadcrumbs($request),
            'impersonation' => session()->has('impersonate'),
            'toast' => session('toast'),
            'fortify' => function () use ($request) {
                $user = $request->user();

                return [
                    // 'canCreateTeams' => $user && Jetstream::userHasTeamFeatures($user) && Gate::forUser($user)->check('create', Jetstream::newTeamModel()),
                    'canManageTwoFactorAuthentication' => Features::canManageTwoFactorAuthentication(),
                    'canUpdatePassword' => Features::enabled(Features::updatePasswords()),
                    'canUpdateProfileInformation' => Features::enabled(Features::updateProfileInformation()),
                    'hasEmailVerification' => Features::enabled(Features::emailVerification()),
                    // 'hasAccountDeletionFeatures' => Jetstream::hasAccountDeletionFeatures(),
                    // 'hasApiFeatures' => Jetstream::hasApiFeatures(),
                    // 'hasTeamFeatures' => Jetstream::hasTeamFeatures(),
                    // 'hasTermsAndPrivacyPolicyFeature' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
                    // 'managesProfilePhotos' => Jetstream::managesProfilePhotos(),
                ];
            },
            'socialAuth' => fn () => $this->socialAuthConfig(),
        ]);
    }

    private function sharedBrandSettings(GeneralSettings $settings): array
    {
        try {
            return [
                'app_name' => $settings->app_name ?? 'LIMS Unleashed',
                'app_slogan' => $settings->app_slogan ?? 'Rastreabilidade, qualidade e conformidade para laboratórios modernos.',
                'primary_color' => $settings->app_primary_color ?? '#0757b5',
                'secondary_color' => $settings->app_secondary_color ?? '#061f46',
                'accent_color' => $settings->app_accent_color ?? '#087cf0',
                'theme_preset' => $settings->app_theme_preset ?? 'corporate',
                'app_primary_color' => $settings->app_primary_color ?? '#0757b5',
                'app_secondary_color' => $settings->app_secondary_color ?? '#061f46',
                'app_accent_color' => $settings->app_accent_color ?? '#087cf0',
                'app_theme_preset' => $settings->app_theme_preset ?? 'corporate',
                'operation_mode' => $settings->app_operation_mode ?? 'client_only',
                'logo_url' => $settings->app_logo_url ?? null,
                'app_logo_url' => $settings->app_logo_url ?? null,
                'login_headline' => $settings->app_login_headline ?? 'Bem-vindo de volta',
                'login_subheadline' => $settings->app_login_subheadline ?? 'Aceda à operação, acompanhe a rastreabilidade e mantenha o laboratório sob controlo.',
                'validation_name' => $settings->app_agt_valid_name ?? null,
                'validation_number' => $settings->app_agt_validation_number ?? null,
                'lab_name' => $settings->app_client_lab_name ?? $settings->app_name ?? 'LIMS Unleashed',
                'lab_director' => $settings->app_client_lab_director ?? null,
                'portal_enabled' => ($settings->app_operation_mode ?? 'client_only') !== 'internal_only',
                'notification_sender_alias' => $settings->app_notification_sender_alias ?? ($settings->app_name ?? 'LIMS Unleashed'),
                'notification_default_title' => $settings->app_notification_default_title ?? 'Notificação do sistema',
                'notification_default_message' => $settings->app_notification_default_message ?? 'Existe uma actualização importante disponível para si no sistema.',
            ];
        } catch (Throwable) {
            return [
                'app_name' => 'LIMS Unleashed',
                'app_slogan' => 'Rastreabilidade, qualidade e conformidade para laboratórios modernos.',
                'primary_color' => '#0757b5',
                'secondary_color' => '#061f46',
                'accent_color' => '#087cf0',
                'theme_preset' => 'corporate',
                'app_primary_color' => '#0757b5',
                'app_secondary_color' => '#061f46',
                'app_accent_color' => '#087cf0',
                'app_theme_preset' => 'corporate',
                'operation_mode' => 'client_only',
                'logo_url' => null,
                'app_logo_url' => null,
                'login_headline' => 'Bem-vindo de volta',
                'login_subheadline' => 'Aceda à operação, acompanhe a rastreabilidade e mantenha o laboratório sob controlo.',
                'validation_name' => null,
                'validation_number' => null,
                'lab_name' => 'LIMS Unleashed',
                'lab_director' => null,
                'portal_enabled' => true,
                'notification_sender_alias' => 'LIMS Unleashed',
                'notification_default_title' => 'Notificação do sistema',
                'notification_default_message' => 'Existe uma actualização importante disponível para si no sistema.',
            ];
        }
    }

    private function requestIsPortal(Request $request): bool
    {
        return $request->is('portal') || $request->is('portal/*');
    }

    private function socialAuthConfig(): array
    {
        $providers = collect([
            ['service' => 'google', 'label' => 'Google', 'enabled' => filled(config('services.google.client_id'))],
            ['service' => 'github', 'label' => 'GitHub', 'enabled' => filled(config('services.github.client_id'))],
            ['service' => 'microsoft', 'label' => 'Microsoft', 'enabled' => filled(config('services.microsoft.client_id'))],
        ])->where('enabled')->values();

        return [
            'providers' => $providers,
        ];
    }

    private function generateBreadcrumbs(Request $request): array
    {
        $routeName = $request->route()?->getName();
        if (! $routeName) {
            return [];
        }

        $crumbs = [];

        $parts = explode('.', $routeName);
        $section = $parts[0] ?? '';
        $action = $parts[1] ?? 'index';

        $sectionLabels = [
            'dashboard' => 'Painel',
            'samples' => 'Amostras',
            'analysis' => 'Análises',
            'parameters' => 'Parâmetros',
            'customers' => 'Clientes',
            'products' => 'Produtos',
            'proposals' => 'Propostas',
            'invoices' => 'Facturas',
            'quotes' => 'Proformas',
            'settings' => 'Definições',
            'users' => 'Utilizadores',
            'roles' => 'Papéis',
            'permissions' => 'Permissões',
            'notifications' => 'Notificações',
            'departments' => 'Departamentos',
            'announcements' => 'Anúncios',
            'security' => 'Segurança',
            'systemactivity' => 'Registo de actividade',
            'systembackups' => 'Cópias de segurança',
            'generalsettings' => 'Definições',
            'vap-inventory' => 'Inventário',
            'matrixes' => 'Matrizes',
            'protocols' => 'Protocolos',
            'standards' => 'Normas',
            'units' => 'Unidades',
            'warehouses' => 'Armazéns',
            'directcollections' => 'Recolhas directas',
            'programmedcollections' => 'Recolhas programadas',
            'collectionreasons' => 'Motivos de recolha',
            'resultcategories' => 'Categorias de resultados',
            'packagingcategories' => 'Categorias de embalagens',
            'customerrequestcategories' => 'Categorias de pedidos',
            'customerrequests' => 'Pedidos de clientes',
            'collectionendresults' => 'Resultados finais da recolha',
            'countries' => 'Países',
            'customercategories' => 'Categorias de clientes',
            'contactcategories' => 'Categorias de contactos',
            'invoicecategories' => 'Categorias de facturas',
            'proposaltemplates' => 'Modelos de propostas',
            'creditnotes' => 'Notas de crédito',
            'receipts' => 'Recibos',
            'currencies' => 'Moedas',
            'paymentcategories' => 'Categorias de pagamentos',
            'discountcategories' => 'Categorias de descontos',
            'taxtypes' => 'Tipos de impostos',
            'taxexemptions' => 'Isenções fiscais',
            'transportcategories' => 'Categorias de transporte',
            'vehicles' => 'Viaturas',
            'phytosanitaryproducts' => 'Produtos fitossanitários',
            'paidservices' => 'Serviços pagos',
            'faqcategories' => 'Categorias de perguntas frequentes',
            'faqs' => 'Perguntas frequentes',
            'faqanswers' => 'Respostas às perguntas frequentes',
            'contractguides' => 'Guias de contrato',
            'importcertificates' => 'Certificados de importação',
            'exportcertificates' => 'Certificados de exportação',
            'qualitycertificates' => 'Certificados de qualidade',
            'occurrencecategories' => 'Categorias de ocorrências',
            'occurrenceorigins' => 'Origens das ocorrências',
            'occurrencestatuses' => 'Estados das ocorrências',
            'occurrences' => 'Ocorrências',
            'maintenance' => 'Manutenção',
            'iunits' => 'Unidades de inventário',
            'itypes' => 'Tipos de artigos',
            'ilocations' => 'Localizações',
            'ideliveries' => 'Entregas',
            'isuppliers' => 'Fornecedores',
            'itemcategories' => 'Categorias',
            'equipmentcategories' => 'Categorias de equipamentos',
            'itemstatuses' => 'Estados dos artigos',
            'analysiscategories' => 'Categorias de análises',
            'profiles' => 'Perfis',
            'counteranalysis' => 'Contra-análise',
            'nwps' => 'NWPs',
            'temperatures' => 'Temperaturas',
            'environmental-conditions' => 'Condições ambientais',
            'modern-folders' => 'Ficheiros',
            'media' => 'Multimédia',
            'files' => 'Ficheiros',
            'boards' => 'Quadros',
            'formulas' => 'Fórmulas',
            'report-studios' => 'Estúdios de relatórios',
            'qms' => 'SGQ',
            'supplier-assessments' => 'Avaliações de fornecedores',
            'responsibility-matrix' => 'Matriz de responsabilidades',
            'uncertainty-sources' => 'Fontes de incerteza',
            'vap_samples' => 'Amostras',
            'vap-proposals' => 'Propostas',
            'laboratory-workflow' => 'Fluxo laboratorial',
            'lab-network' => 'Rede de laboratórios',
            'exports' => 'Exportações',
            'integration-hub' => 'Integrações',
            'vap-labs' => __('gestlab.general.labels.vap_labs.title'),
            'qualitycertificates' => 'Certificados de qualidade',
            'importcertificates' => 'Certificados de importação',
            'exportcertificates' => 'Certificados de exportação',
            'admin' => 'Administração',
            'app-events' => 'Eventos da aplicação',
            'archived_documents' => 'Documentos arquivados',
            'collectioncollaborations' => 'Colaborações de recolha',
            'complaints' => 'Reclamações',
            'reagent-consumption' => 'Consumo de reagentes',
            'reagent-dashboard' => 'Painel de reagentes',
            'file-manager' => 'Ficheiros',
            'iequipments' => 'Equipamentos',
            'iitems' => 'Artigos',
            'inventory' => 'Inventário',
            'iorders' => 'Encomendas',
            'itransactions' => 'Movimentos',
            'itransactiontypes' => 'Tipos de movimento',
            'itransfers' => 'Transferências',
            'iwarehouses' => 'Armazéns de inventário',
            'maintenancetasks' => 'Tarefas de manutenção',
            'vap-maintenance' => 'Manutenção',
            'maintenancecategories' => 'Categorias de manutenção',
            'management-reviews' => 'Revisões pela gestão',
            'messages' => 'Mensagens',
            'metrics' => 'Indicadores',
            'notification-preferences' => 'Preferências de notificação',
            'phytosanitary_products' => 'Produtos fitossanitários',
            'proficiency_tests' => 'Ensaios de proficiência',
            'proposalcomplianceagreements' => 'Acordos de conformidade',
            'ratings' => 'Avaliações',
            'printBatchLabels' => 'Etiquetas de lote',
            'variables' => 'Variáveis',
            'worksheets' => 'Folhas de cálculo',
            'analytics' => 'Análises',
        ];

        $actionLabels = [
            'index' => 'Lista',
            'create' => 'Criar',
            'show' => 'Detalhes',
            'edit' => 'Editar',
            'analytics' => 'Análise de dados',
            'dashboard' => 'Painel',
            'reports' => 'Relatórios',
            'samples' => 'Amostras',
            'discards' => 'Descartes',
            'labs' => __('gestlab.general.labels.vap_labs.title'),
            'preview-pdf' => 'Pré-visualizar PDF',
            'templates' => 'Modelos',
            'queue' => 'Fila',
            'pdf' => 'PDF',
            'export' => 'Exportar',
            'notifications' => 'Notificações',
            'notification-templates' => 'Modelos de notificação',
            'data-exports' => 'Folha diária',
            'taxData' => 'Dados fiscais',
            'taxIdentification' => 'Identificação fiscal',
            'mb' => 'Editor de fórmulas',
            'import' => 'Importar',
            'categories' => 'Categorias',
            'tasks' => 'Tarefas',
            'backups' => 'Cópias',
            'help' => 'Manual',
            'profile' => 'Perfil',
            'security' => 'Segurança',
            'items' => 'Artigos',
            'master' => 'Catálogos',
            'needs' => 'Necessidades',
            'orders' => 'Pedidos de compra',
            'reagents' => 'Reagentes',
            'transfers' => 'Transferências',
            'label-templates' => 'Modelos de etiqueta',
            'board' => 'Painel',
            'update' => null,
            'store' => null,
            'destroy' => null,
        ];

        if ($routeName === 'dashboard') {
            return [
                ['name' => 'dashboard', 'title' => 'Painel', 'url' => route('dashboard'), 'current' => true],
            ];
        }

        if ($section === 'vap_labels') {
            return $this->generateVAPLabelBreadcrumbs($request, $parts);
        }

        if ($section === 'vap_non_conformities') {
            return $this->generateVAPNonConformityBreadcrumbs($request, $parts);
        }

        if ($section === 'vap-labs') {
            return $this->generateVAPLabBreadcrumbs($request, $parts);
        }

        $sectionLabel = $sectionLabels[$section] ?? $this->humanizeBreadcrumbSegment($section);
        $sectionUrl = $this->sectionUrl($request, $section);

        if ($action === 'index') {
            $crumbs[] = ['name' => $section, 'title' => $sectionLabel, 'url' => $sectionUrl, 'current' => true];
        } else {
            $crumbs[] = ['name' => $section, 'title' => $sectionLabel, 'url' => $sectionUrl, 'current' => false];

            $actionLabel = $actionLabels[$action] ?? $this->humanizeBreadcrumbSegment($action);
            if ($actionLabel) {
                $actionUrl = $action === 'create'
                    ? $sectionUrl.'/create'
                    : $request->url();
                $crumbs[] = [
                    'name' => $action,
                    'title' => $actionLabel,
                    'url' => $actionUrl,
                    'current' => ! in_array($action, ['store', 'update', 'destroy'], true),
                ];
            }
        }

        return $crumbs;
    }

    private function generateVAPLabelBreadcrumbs(Request $request, array $parts): array
    {
        $resource = $parts[1] ?? 'labels';
        $action = $parts[2] ?? 'index';
        $isTemplateRoute = $resource === 'label-templates' || $resource === 'templates';

        $sectionUrl = route($isTemplateRoute ? 'vap_labels.label-templates.index' : 'vap_labels.labels.index');
        $sectionTitle = $isTemplateRoute
            ? __('gestlab.general.labels.vap_labels.templates.title')
            : __('gestlab.general.labels.vap_labels.title');

        if ($action === 'index') {
            return [
                ['name' => $resource, 'title' => $sectionTitle, 'url' => $sectionUrl, 'current' => true],
            ];
        }

        $actionLabels = [
            'create' => $isTemplateRoute
                ? __('gestlab.general.labels.vap_labels.templates.create_title')
                : __('gestlab.general.labels.vap_labels.create_title'),
            'show' => __('gestlab.general.labels.vap_labels.details'),
            'edit' => $isTemplateRoute
                ? __('gestlab.general.labels.vap_labels.templates.edit_title')
                : __('gestlab.general.labels.vap_labels.edit_title'),
        ];

        return array_values(array_filter([
            ['name' => $resource, 'title' => $sectionTitle, 'url' => $sectionUrl, 'current' => false],
            isset($actionLabels[$action]) ? [
                'name' => $action,
                'title' => $actionLabels[$action],
                'url' => $request->url(),
                'current' => in_array($action, ['create', 'show', 'edit'], true),
            ] : null,
        ]));
    }

    private function generateVAPLabBreadcrumbs(Request $request, array $parts): array
    {
        $action = $parts[2] ?? 'index';
        $sectionTitle = __('gestlab.general.labels.vap_labs.title');
        $sectionUrl = route('vap-labs.labs.index');

        if ($action === 'index') {
            return [
                ['name' => 'vap-labs', 'title' => $sectionTitle, 'url' => $sectionUrl, 'current' => true],
            ];
        }

        $actionLabels = [
            'create' => __('gestlab.general.labels.vap_labs.buttons.add_lab'),
            'show' => __('gestlab.general.labels.vap_labs.buttons.view_details'),
            'edit' => __('gestlab.general.labels.vap_labs.buttons.edit_lab'),
        ];

        return array_values(array_filter([
            ['name' => 'vap-labs', 'title' => $sectionTitle, 'url' => $sectionUrl, 'current' => false],
            isset($actionLabels[$action]) ? [
                'name' => $action,
                'title' => $actionLabels[$action],
                'url' => $request->url(),
                'current' => in_array($action, ['create', 'show', 'edit'], true),
            ] : null,
        ]));
    }

    private function generateVAPNonConformityBreadcrumbs(Request $request, array $parts): array
    {
        $action = $parts[1] ?? 'index';
        $sectionTitle = __('gestlab.general.labels.vap_non_conformities.title');
        $sectionUrl = route('vap_non_conformities.index');

        if ($action === 'index') {
            return [
                ['name' => 'vap_non_conformities', 'title' => $sectionTitle, 'url' => $sectionUrl, 'current' => true],
            ];
        }

        $actionLabels = [
            'create' => __('gestlab.general.labels.vap_non_conformities.create_title'),
            'show' => __('gestlab.general.labels.vap_non_conformities.details_title'),
            'edit' => __('gestlab.general.labels.vap_non_conformities.edit_title'),
            'export' => __('gestlab.general.labels.vap_non_conformities.buttons.export'),
        ];

        return array_values(array_filter([
            ['name' => 'vap_non_conformities', 'title' => $sectionTitle, 'url' => $sectionUrl, 'current' => false],
            isset($actionLabels[$action]) ? [
                'name' => $action,
                'title' => $actionLabels[$action],
                'url' => $request->url(),
                'current' => in_array($action, ['create', 'show', 'edit', 'export'], true),
            ] : null,
        ]));
    }

    private function sectionUrl(Request $request, string $section): string
    {
        $sectionRouteMap = [
            'dashboard' => 'dashboard',
            'samples' => 'samples.index',
            'analysis' => 'analysis.index',
            'parameters' => 'parameters.index',
            'customers' => 'customers.index',
            'products' => 'products.index',
            'proposals' => 'vap-proposals.index',
            'proposaltemplates' => 'vap-proposals.templates.index',
            'invoices' => 'invoices.index',
            'quotes' => 'quotes.index',
            'users' => 'users.index',
            'roles' => 'roles.index',
            'permissions' => 'permissions.index',
            'notifications' => 'notifications.index',
            'departments' => 'departments.index',
            'settings' => 'generalsettings.index',
            'generalsettings' => 'generalsettings.index',
            'systembackups' => 'systembackups.backups',
            'security' => 'security',
            'systemactivity' => 'systemactivity.index',
            'announcements' => 'announcements',
            'matrixes' => 'matrixes.index',
            'protocols' => 'protocols.index',
            'standards' => 'standards.index',
            'units' => 'units.index',
            'warehouses' => 'warehouses.index',
            'directcollections' => 'directcollections.index',
            'programmedcollections' => 'programmedcollections.index',
            'collectionreasons' => 'collectionreasons.index',
            'resultcategories' => 'resultcategories.index',
            'packagingcategories' => 'packagingcategories.index',
            'customerrequestcategories' => 'customerrequestcategories.index',
            'customerrequests' => 'customerrequests.index',
            'collectionendresults' => 'collectionendresults.index',
            'countries' => 'countries.index',
            'customercategories' => 'customercategories.index',
            'contactcategories' => 'contactcategories.index',
            'invoicecategories' => 'invoicecategories.index',
            'creditnotes' => 'creditnotes.index',
            'receipts' => 'receipts.index',
            'currencies' => 'currencies.index',
            'paymentcategories' => 'paymentcategories.index',
            'discountcategories' => 'discountcategories.index',
            'taxtypes' => 'taxtypes.index',
            'taxexemptions' => 'taxexemptions.index',
            'transportcategories' => 'transportcategories.index',
            'vehicles' => 'vehicles.index',
            'phytosanitaryproducts' => 'phytosanitary_products.index',
            'paidservices' => 'paidservices.index',
            'faqcategories' => 'faqcategories.index',
            'faqs' => 'faqs.index',
            'faqanswers' => 'faqanswers.index',
            'contractguides' => 'contractguides.index',
            'importcertificates' => 'importcertificates.index',
            'exportcertificates' => 'exportcertificates.index',
            'qualitycertificates' => 'qualitycertificates.index',
            'occurrencecategories' => 'occurrencecategories.index',
            'occurrenceorigins' => 'occurrenceorigins.index',
            'occurrencestatuses' => 'occurrencestatuses.index',
            'occurrences' => 'occurrences.index',
            'maintenance' => 'maintenance.tasks',
            'iunits' => 'iunits.index',
            'itypes' => 'itypes.index',
            'ilocations' => 'ilocations.index',
            'ideliveries' => 'ideliveries.index',
            'isuppliers' => 'isuppliers.index',
            'itemcategories' => 'itemcategories.index',
            'equipmentcategories' => 'equipmentcategories.index',
            'itemstatuses' => 'itemstatuses.index',
            'analysiscategories' => 'analysiscategories.index',
            'profiles' => 'profiles.index',
            'counteranalysis' => 'counteranalysis.index',
            'nwps' => 'nwps.index',
            'temperatures' => 'temperatures.index',
            'environmental-conditions' => 'environmental-conditions.index',
            'modern-folders' => 'modern-folders.index',
            'media' => 'media.index',
            'files' => 'files.index',
            'boards' => 'boards.index',
            'formulas' => 'formulas.index',
            'report-studios' => 'report-studios.index',
            'qms' => 'qms.index',
            'supplier-assessments' => 'supplier-assessments.index',
            'responsibility-matrix' => 'responsibility-matrix.index',
            'uncertainty-sources' => 'uncertainty-sources.index',
            'vap_samples' => 'vap_samples.index',
            'vap-labs' => 'vap-labs.labs.index',
            'vap-inventory' => 'vap-inventory.items.index',
        ];

        $routeName = $sectionRouteMap[$section] ?? $section.'.index';
        try {
            return route($routeName);
        } catch (Throwable) {
            return $request->url();
        }
    }

    private function humanizeBreadcrumbSegment(string $segment): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $segment));
    }
}
