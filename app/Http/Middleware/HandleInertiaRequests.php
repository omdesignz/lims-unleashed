<?php

namespace App\Http\Middleware;

use App\Http\Resources\LanguageResource;
use App\Lang\Lang;
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
                            'unread_notifications' => $user->unreadNotifications ?? collect(),
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
                'primary_color' => $settings->app_primary_color ?? '#143d37',
                'secondary_color' => $settings->app_secondary_color ?? '#07110f',
                'accent_color' => $settings->app_accent_color ?? '#d9b05f',
                'theme_preset' => $settings->app_theme_preset ?? 'corporate',
                'app_primary_color' => $settings->app_primary_color ?? '#143d37',
                'app_secondary_color' => $settings->app_secondary_color ?? '#07110f',
                'app_accent_color' => $settings->app_accent_color ?? '#d9b05f',
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
                'notification_default_message' => $settings->app_notification_default_message ?? 'Existe uma atualização importante disponível para si no sistema.',
            ];
        } catch (Throwable) {
            return [
                'app_name' => 'LIMS Unleashed',
                'app_slogan' => 'Rastreabilidade, qualidade e conformidade para laboratórios modernos.',
                'primary_color' => '#143d37',
                'secondary_color' => '#07110f',
                'accent_color' => '#d9b05f',
                'theme_preset' => 'corporate',
                'app_primary_color' => '#143d37',
                'app_secondary_color' => '#07110f',
                'app_accent_color' => '#d9b05f',
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
                'notification_default_message' => 'Existe uma atualização importante disponível para si no sistema.',
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
            'dashboard' => 'Dashboard',
            'samples' => 'Samples',
            'analysis' => 'Analysis',
            'parameters' => 'Parameters',
            'customers' => 'Customers',
            'products' => 'Products',
            'proposals' => 'Proposals',
            'invoices' => 'Invoices',
            'quotes' => 'Quotes',
            'settings' => 'Settings',
            'users' => 'Users',
            'roles' => 'Roles',
            'permissions' => 'Permissions',
            'notifications' => 'Notifications',
            'departments' => 'Departments',
            'announcements' => 'Announcements',
            'security' => 'Security',
            'systemactivity' => 'Activity Log',
            'systembackups' => 'Backups',
            'generalsettings' => 'Settings',
            'vap-inventory' => 'Inventory',
            'matrixes' => 'Matrixes',
            'protocols' => 'Protocols',
            'standards' => 'Standards',
            'units' => 'Units',
            'warehouses' => 'Warehouses',
            'directcollections' => 'Direct Collections',
            'programmedcollections' => 'Programmed Collections',
            'collectionreasons' => 'Collection Reasons',
            'resultcategories' => 'Result Categories',
            'packagingcategories' => 'Packaging Categories',
            'customerrequestcategories' => 'Request Categories',
            'customerrequests' => 'Customer Requests',
            'collectionendresults' => 'Collection End Results',
            'countries' => 'Countries',
            'customercategories' => 'Customer Categories',
            'contactcategories' => 'Contact Categories',
            'invoicecategories' => 'Invoice Categories',
            'proposaltemplates' => 'Proposal Templates',
            'creditnotes' => 'Credit Notes',
            'receipts' => 'Receipts',
            'currencies' => 'Currencies',
            'paymentcategories' => 'Payment Categories',
            'discountcategories' => 'Discount Categories',
            'taxtypes' => 'Tax Types',
            'taxexemptions' => 'Tax Exemptions',
            'transportcategories' => 'Transport Categories',
            'vehicles' => 'Vehicles',
            'phytosanitaryproducts' => 'Phytosanitary Products',
            'paidservices' => 'Paid Services',
            'faqcategories' => 'FAQ Categories',
            'faqs' => 'FAQs',
            'faqanswers' => 'FAQ Answers',
            'contractguides' => 'Contract Guides',
            'importcertificates' => 'Import Certificates',
            'exportcertificates' => 'Export Certificates',
            'qualitycertificates' => 'Quality Certificates',
            'occurrencecategories' => 'Occurrence Categories',
            'occurrenceorigins' => 'Occurrence Origins',
            'occurrencestatuses' => 'Occurrence Statuses',
            'occurrences' => 'Occurrences',
            'maintenance' => 'Maintenance',
            'iunits' => 'Inventory Units',
            'itypes' => 'Item Types',
            'ilocations' => 'Locations',
            'ideliveries' => 'Deliveries',
            'isuppliers' => 'Suppliers',
            'itemcategories' => 'Item Categories',
            'equipmentcategories' => 'Equipment Categories',
            'itemstatuses' => 'Item Statuses',
            'itemcategories' => 'Categories',
            'analysiscategories' => 'Analysis Categories',
            'profiles' => 'Profiles',
            'counteranalysis' => 'Counter Analysis',
            'nwps' => 'NWPs',
            'temperatures' => 'Temperatures',
            'environmental-conditions' => 'Environmental Conditions',
            'modern-folders' => 'Files',
            'media' => 'Media',
            'files' => 'Files',
            'boards' => 'Boards',
            'formulas' => 'Formulas',
            'report-studios' => 'Report Studios',
            'qms' => 'QMS',
            'supplier-assessments' => 'Supplier Assessments',
            'responsibility-matrix' => 'Responsibility Matrix',
            'uncertainty-sources' => 'Sources of Uncertainty',
            'vap_samples' => 'Sample Entry',
            'vap-labs' => __('gestlab.general.labels.vap_labs.title'),
            'qualitycertificates' => 'Quality Certificates',
            'importcertificates' => 'Import Certificates',
            'exportcertificates' => 'Export Certificates',
        ];

        $actionLabels = [
            'index' => 'List',
            'create' => 'Create',
            'show' => 'Show',
            'edit' => 'Edit',
            'analytics' => 'Analytics',
            'dashboard' => 'Dashboard',
            'reports' => 'Reports',
            'samples' => 'Samples',
            'discards' => 'Discards',
            'labs' => __('gestlab.general.labels.vap_labs.title'),
            'preview-pdf' => 'Preview PDF',
            'pdf' => 'PDF',
            'export' => 'Export',
            'update' => null,
            'store' => null,
            'destroy' => null,
        ];

        if ($routeName === 'dashboard') {
            return [
                ['name' => 'dashboard', 'title' => 'Dashboard', 'url' => route('dashboard'), 'current' => true],
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
