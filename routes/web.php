<?php

use App\Events\TestEvent;
use App\Http\Controllers\AnalysisCategoryController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\Api\UserThemeController;
use App\Http\Controllers\APITokenController;
use App\Http\Controllers\ApplicationEventController;
use App\Http\Controllers\BackupStatusesController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\BrowserSessionController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\CardListController;
use App\Http\Controllers\CleanBackupsController;
use App\Http\Controllers\ClientPortal\PortalController;
use App\Http\Controllers\ClientPortal\PortalPasskeyController;
use App\Http\Controllers\CollectionCollaborationController;
use App\Http\Controllers\CollectionEndResultController;
use App\Http\Controllers\CollectionReasonController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\ContactCategoryController;
use App\Http\Controllers\ContractGuideController;
use App\Http\Controllers\ContractGuideItemController;
use App\Http\Controllers\CounterAnalysisController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\CustomerCategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerRequestCategoryController;
use App\Http\Controllers\CustomerRequestController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DirectCollectionController;
use App\Http\Controllers\DiscountCategoryController;
use App\Http\Controllers\DownloadBackupController;
use App\Http\Controllers\EnvironmentalConditionController;
use App\Http\Controllers\EquipmentCategoryController;
use App\Http\Controllers\EquipmentImportController;
use App\Http\Controllers\ExecutiveDashboardController;
use App\Http\Controllers\ExportCertificateController;
use App\Http\Controllers\FAQAnswerController;
use App\Http\Controllers\FAQCategoryController;
use App\Http\Controllers\FAQController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\FormulaController;
use App\Http\Controllers\ImportCertificateController;
use App\Http\Controllers\InventoryBatchController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryDeliveryController;
use App\Http\Controllers\InventoryEquipmentController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\InventoryItemLocationController;
use App\Http\Controllers\InventoryItemSupplierController;
use App\Http\Controllers\InventoryItemTransferController;
use App\Http\Controllers\InventoryItemTypeController;
use App\Http\Controllers\InventoryItemWarehouseController;
use App\Http\Controllers\InventoryOrderController;
use App\Http\Controllers\InventoryTransactionController;
use App\Http\Controllers\InventoryTransactionTypeController;
use App\Http\Controllers\InventoryUnitController;
use App\Http\Controllers\InvoiceCategoryController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceItemController;
use App\Http\Controllers\ISORevisionController;
use App\Http\Controllers\ItemCategoryController;
use App\Http\Controllers\ItemStatusController;
use App\Http\Controllers\LabCodeController;
use App\Http\Controllers\LanguageStoreController;
use App\Http\Controllers\MaintenanceCategoryController;
use App\Http\Controllers\MaintenanceTaskController;
use App\Http\Controllers\MaintenanceTaskImportController;
use App\Http\Controllers\ManagementReviewController;
use App\Http\Controllers\MatrixController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MetricController;
use App\Http\Controllers\ModernFolderController;
use App\Http\Controllers\NormativeWorkProcedureController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OccurrenceCategoryController;
use App\Http\Controllers\OccurrenceController;
use App\Http\Controllers\OccurrenceImportController;
use App\Http\Controllers\OccurrenceOriginController;
use App\Http\Controllers\OccurrenceStatusController;
use App\Http\Controllers\PackagingCategoryController;
use App\Http\Controllers\PaidServiceController;
use App\Http\Controllers\ParameterController;
use App\Http\Controllers\PasskeyController;
use App\Http\Controllers\PaymentCategoryController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PhytosanitaryProductController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProficiencyTestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgrammedCollectionController;
use App\Http\Controllers\ProposalComplianceAgreementController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\ProposalTemplateController;
use App\Http\Controllers\ProtocolController;
use App\Http\Controllers\QMSController;
use App\Http\Controllers\QualityCertificateController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\QuoteItemController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\ReagentConsumptionController;
use App\Http\Controllers\ReagentDashboardController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportStudioController;
use App\Http\Controllers\ResponsibilityMatrixController;
use App\Http\Controllers\ResultCategoryController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SampleController;
use App\Http\Controllers\Social\AuthCallbackController;
use App\Http\Controllers\Social\AuthRedirectController;
use App\Http\Controllers\StandardController;
use App\Http\Controllers\SupplierAssessmentController;
use App\Http\Controllers\SystemActivityController;
use App\Http\Controllers\SystemBackupController;
use App\Http\Controllers\SystemGeneralSettingsController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TaxExemptionController;
use App\Http\Controllers\TaxTypeController;
use App\Http\Controllers\TemperatureController;
use App\Http\Controllers\TransportCategoryController;
use App\Http\Controllers\UncertaintySourceController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\VAPFileController;
use App\Http\Controllers\VAPInventoryAnalyticsController;
use App\Http\Controllers\VAPInventoryItemController;
use App\Http\Controllers\VAPInventoryNeedController;
use App\Http\Controllers\VAPInventoryOrderController;
use App\Http\Controllers\VAPInventoryReportController;
use App\Http\Controllers\VAPInventoryTransferController;
use App\Http\Controllers\VAPLabController;
use App\Http\Controllers\VAPLabelController;
use App\Http\Controllers\VAPLabelTemplateController;
use App\Http\Controllers\VAPMaintenanceController;
use App\Http\Controllers\VAPNonConformityController;
use App\Http\Controllers\VAPProposalController;
use App\Http\Controllers\VAPProposalTemplateController;
use App\Http\Controllers\VAPPublicProposalController;
use App\Http\Controllers\VAPSampleDiscardController;
use App\Http\Controllers\VAPSampleEntryController;
use App\Http\Controllers\VariableController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\WorksheetController;
use App\Http\Middleware\UsePortalFortifyConfiguration;
use App\Models\CollectionProduct;
use App\Models\CustomerRequest;
use App\Models\InventoryItem;
use App\Models\ParameterProfile;
use App\Models\QualityCertificate;
use App\Models\VAPSampleEntry;
use App\Services\ModelsListingService;
use App\Services\NIFIdentificationService;
use App\Settings\GeneralSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\ConfirmablePasswordController;
use Laravel\Fortify\Http\Controllers\ConfirmedPasswordStatusController;
use Laravel\Fortify\Http\Controllers\ConfirmedTwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationPromptController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\RecoveryCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\TwoFactorQrCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorSecretKeyController;
use Laravel\Fortify\Http\Controllers\VerifyEmailController;
use Laravel\Fortify\RoutePath;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('worksheets', function () {
//     return inertia('Worksheets/Edit', [
//         'worksheetData' => []
//     ]);
// });

Route::get('broadcast', function () {

    // TestEvent::dispatch("Hey ma! I'm famous");

    broadcast(new TestEvent('hi there kids'));
    // broadcast(new TestEvent('hi there kids'))->toOthers();
});

Route::get('/sgs', function () {
    return view('PDFs.sgs_report_template');
});

Route::get('/cast', function () {
    return ParameterProfile::find(12);
});

Route::get('/labels', function () {

    return view('PDFs.sample_labels', [
        'qr' => CollectionProduct::first()->Qr,
    ]);
});

Route::get('/multiple-sample-analysis', function () {

    return view('PDFs.multiple_sample_analysis');
});

Route::get('/', function (Request $request, GeneralSettings $settings) {
    if ($request->user()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Public/Landing', [
        'branding' => [
            'app_name' => $settings->app_name ?? config('app.name'),
            'app_slogan' => $settings->app_slogan ?? 'Rastreabilidade, qualidade e conformidade para laboratórios modernos.',
            'primary_color' => $settings->app_primary_color ?? '#1f87e8',
            'secondary_color' => $settings->app_secondary_color ?? '#0f172a',
            'accent_color' => $settings->app_accent_color ?? '#14b8a6',
            'logo_url' => $settings->app_logo_url ?? null,
            'portal_enabled' => ($settings->app_operation_mode ?? 'client_only') !== 'internal_only',
            'validation_name' => $settings->app_agt_valid_name,
            'validation_number' => $settings->app_agt_validation_number,
            'lab_name' => $settings->app_client_lab_name,
            'lab_director' => $settings->app_client_lab_director,
        ],
        'metrics' => Cache::remember('public-landing.metrics', now()->addMinutes(15), function () {
            return [
                'samples' => VAPSampleEntry::query()->count(),
                'certificates' => QualityCertificate::query()->count(),
                'inventory_items' => InventoryItem::query()->count(),
                'customer_requests' => CustomerRequest::query()->count(),
            ];
        }),
    ]);
})->name('landing');

Route::redirect('/welcome', '/')->name('welcome');

Route::get('/models', function () {

    return collect((new ModelsListingService)->getModels())->map(function ($item) {

        $model = ! str()->contains($item, 'Settings') ? 'App?Models?'.$item : 'App?Settings?'.$item;
        $model = str_replace('?', '\\', $model);

        return [
            'model' => $model::MENU_NAME,
            'abilities' => method_exists($model, 'getAbilities') ? $model::ABILITIES : null,
        ];
    })->filter(function ($item) {
        return ! is_null($item['model']);
    })->values();

    // return (new NIFIdentificationService())->getCustomerData('000062398LN033');
})->middleware('auth', 'account.deactivated');

Route::get('/test', function () {

    return inertia('Dashboard', [
        'toast' => [
            'title' => 'title',
            'message' => 'test',
        ],
    ]);
})->middleware('password.confirm');

// Route::get('/users', function (Request $request) {

//     //dd($request->get('query'));
//     return App\Models\User::where('name', 'LIKE', "%{$request->get('query')}%")->get();
//     //return App\Models\User::all()->toJSON();
// });

Route::get('/components', function () {
    return Inertia::render('Welcome');
})->name('components.playground');

Route::post('/language', LanguageStoreController::class)->name('language.store');

// Route::get('/security', function () {
//     return \Inertia\Inertia::render('Profile/Show');
// })->name('security')->middleware(['web', 'auth']);

Route::middleware(['auth', 'account.deactivated', 'verified'])->group(function () {

    // Import Satus
    Route::get('/import-status/{batchId}', function ($batchId) {
        $batch = Bus::findBatch($batchId);

        return [
            'progress' => $batch->progress(),
            'totalJobs' => $batch->totalJobs,
            'pendingJobs' => $batch->pendingJobs,
            'failedJobs' => $batch->failedJobs,
            'processedJobs' => $batch->processedJobs(),
            'finished' => $batch->finished(),
            'failedJobIds' => $batch->failedJobIds,
        ];
    });

    Route::controller(OccurrenceImportController::class)->group(function () {
        Route::get('/occurrences/import', 'form')->name('occurrences.import.form');
        Route::post('/occurrences/import', 'upload')->name('occurrences.import.upload');
        Route::get('/occurrences/import-progress/{batchId}', 'progress')->name('occurrences.import.progress');
    });

    Route::controller(EquipmentImportController::class)->group(function () {
        Route::get('/equipments/import', 'form')->name('equipments.import.form');
        Route::post('/equipments/import', 'upload')->name('equipments.import.upload');
        Route::get('/equipments/import-progress/{batchId}', 'progress')->name('equipments.import.progress');
    });

    Route::controller(MaintenanceTaskImportController::class)->group(function () {
        Route::get('/maintenance-tasks/import', 'form')->name('maintenancetasks.import.form');
        Route::post('/maintenance-tasks/import', 'upload')->name('maintenancetasks.import.upload');
        Route::get('/maintenance-tasks/import-progress/{batchId}', 'progress')->name('maintenancetasks.import.progress');
    });

    Route::get('/dashboard', [ExecutiveDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/export', [ExecutiveDashboardController::class, 'export'])->name('dashboard.export');
    Route::get('/report-studios', [ReportStudioController::class, 'index'])->name('report-studios.index');
    Route::post('/report-studios/preview-pdf', [ReportStudioController::class, 'previewDraftPdf'])->name('report-studios.preview-draft-pdf');
    Route::post('/report-studios', [ReportStudioController::class, 'store'])->name('report-studios.store');
    Route::put('/report-studios/{reportStudio}', [ReportStudioController::class, 'update'])->name('report-studios.update');
    Route::delete('/report-studios/{reportStudio}', [ReportStudioController::class, 'destroy'])->name('report-studios.destroy');
    Route::get('/report-studios/{reportStudio}/preview-pdf', [ReportStudioController::class, 'previewPdf'])->name('report-studios.preview-pdf');
    Route::get('/qms', [QMSController::class, 'index'])->name('qms.index');
    Route::get('/supplier-assessments', [SupplierAssessmentController::class, 'index'])->name('supplier-assessments.index');
    Route::post('/supplier-assessments', [SupplierAssessmentController::class, 'store'])->name('supplier-assessments.store');
    Route::put('/supplier-assessments/{supplierAssessment}', [SupplierAssessmentController::class, 'update'])->name('supplier-assessments.update');
    Route::delete('/supplier-assessments/{supplierAssessment}', [SupplierAssessmentController::class, 'destroy'])->name('supplier-assessments.destroy');
    Route::get('/responsibility-matrix', [ResponsibilityMatrixController::class, 'index'])->name('responsibility-matrix.index');
    Route::post('/responsibility-matrix', [ResponsibilityMatrixController::class, 'store'])->name('responsibility-matrix.store');
    Route::put('/responsibility-matrix/{responsibilityMatrix}', [ResponsibilityMatrixController::class, 'update'])->name('responsibility-matrix.update');
    Route::delete('/responsibility-matrix/{responsibilityMatrix}', [ResponsibilityMatrixController::class, 'destroy'])->name('responsibility-matrix.destroy');
    Route::get('/uncertainty-sources', [UncertaintySourceController::class, 'index'])->name('uncertainty-sources.index');
    Route::post('/uncertainty-sources', [UncertaintySourceController::class, 'store'])->name('uncertainty-sources.store');
    Route::put('/uncertainty-sources/{uncertaintySource}', [UncertaintySourceController::class, 'update'])->name('uncertainty-sources.update');
    Route::delete('/uncertainty-sources/{uncertaintySource}', [UncertaintySourceController::class, 'destroy'])->name('uncertainty-sources.destroy');

    // Analysis Categories
    Route::controller(AnalysisCategoryController::class)->group(function () {
        Route::get('analysiscategories', 'index')->name('analysiscategories.index');
        Route::get('analysiscategories/create', 'create')->name('analysiscategories.create');
        Route::post('analysiscategories', 'store')->name('analysiscategories.store');
        Route::get('analysiscategories/{category}/edit', 'edit')->name('analysiscategories.edit');
        Route::put('analysiscategories/{category}', 'update')->name('analysiscategories.update');
        Route::get('analysiscategories/destroy', 'destroy')->name('analysiscategories.destroy');
        Route::get('analysiscategories/restore', 'restore')->name('analysiscategories.restore');
        Route::get('analysiscategories/getAnalysisCategory', 'getAnalysisCategory')->name('analysiscategories.getAnalysisCategory');
    });

    // User Profile
    Route::controller(UserProfileController::class)->group(function () {
        Route::get('/security', 'show')->name('security');
    });

    // Browser Sessions
    Route::controller(BrowserSessionController::class)->group(function () {
        Route::delete('/user/other-browser-sessions', 'destroy')->name('other-browser-sessions.destroy');
    });

    Route::patch('/user/theme', [UserThemeController::class, 'update'])->name('user.theme.update');

    // API Token Management
    Route::controller(APITokenController::class)->group(function () {
        Route::post('/user/api-tokens', 'store')->name('api-tokens.store');
        Route::put('/user/api-tokens/{token}', 'update')->name('api-tokens.update');
        Route::delete('/user/api-tokens/{token}', 'destroy')->name('api-tokens.destroy');
    });

    // Collection Collaborations
    Route::controller(CollectionCollaborationController::class)->group(function () {
        Route::get('collectioncollaborations', 'index')->name('collectioncollaborations.index');
        Route::get('collectioncollaborations/create', 'create')->name('collectioncollaborations.create');
        Route::post('collectioncollaborations', 'store')->name('collectioncollaborations.store');
        Route::get('collectioncollaborations/{collaboration}/edit', 'edit')->name('collectioncollaborations.edit');
        Route::put('collectioncollaborations/{collaboration}', 'update')->name('collectioncollaborations.update');
        Route::get('collectioncollaborations/destroy', 'destroy')->name('collectioncollaborations.destroy');
        Route::get('collectioncollaborations/restore', 'restore')->name('collectioncollaborations.restore');
        Route::get('collectioncollaborations/getCollectionCollaboration', 'getCollectionCollaboration')->name('collectioncollaborations.getCollectionCollaboration');
    });

    // Collection End Results
    Route::controller(CollectionEndResultController::class)->group(function () {
        Route::get('collectionendresults', 'index')->name('collectionendresults.index');
        Route::get('collectionendresults/create', 'create')->name('collectionendresults.create');
        Route::post('collectionendresults', 'store')->name('collectionendresults.store');
        Route::get('collectionendresults/{endresult}/edit', 'edit')->name('collectionendresults.edit');
        Route::put('collectionendresults/{endresult}', 'update')->name('collectionendresults.update');
        Route::get('collectionendresults/destroy', 'destroy')->name('collectionendresults.destroy');
        Route::get('collectionendresults/restore', 'restore')->name('collectionendresults.restore');
        Route::get('collectionendresults/getCollectionEndResult', 'getCollectionEndResult')->name('collectionendresults.getCollectionEndResult');
    });

    // Contact Categories
    Route::controller(ContactCategoryController::class)->group(function () {
        Route::get('contactcategories', 'index')->name('contactcategories.index');
        Route::get('contactcategories/create', 'create')->name('contactcategories.create');
        Route::post('contactcategories', 'store')->name('contactcategories.store');
        Route::get('contactcategories/{category}/edit', 'edit')->name('contactcategories.edit');
        Route::put('contactcategories/{category}', 'update')->name('contactcategories.update');
        Route::get('contactcategories/destroy', 'destroy')->name('contactcategories.destroy');
        Route::get('contactcategories/restore', 'restore')->name('contactcategories.restore');
    });

    // Metrics
    Route::controller(MetricController::class)->group(function () {
        Route::get('metrics', 'index')->name('metrics.index');
    });

    // Countries
    Route::controller(CountryController::class)->group(function () {
        Route::get('countries', 'index')->name('countries.index');
        Route::get('countries/create', 'create')->name('countries.create');
        Route::post('countries', 'store')->name('countries.store');
        Route::get('countries/{country}/edit', 'edit')->name('countries.edit');
        Route::put('countries/{country}', 'update')->name('countries.update');
        Route::get('countries/destroy', 'destroy')->name('countries.destroy');
        Route::get('countries/restore', 'restore')->name('countries.restore');
        Route::get('countries/getCountry', 'getCountry')->name('countries.getCountry');
    });

    // Currencies
    Route::controller(CurrencyController::class)->group(function () {
        Route::get('currencies', 'index')->name('currencies.index');
        Route::get('currencies/create', 'create')->name('currencies.create');
        Route::post('currencies', 'store')->name('currencies.store');
        Route::get('currencies/{currency}/edit', 'edit')->name('currencies.edit');
        Route::put('currencies/{currency}', 'update')->name('currencies.update');
        Route::get('currencies/destroy', 'destroy')->name('currencies.destroy');
        Route::get('currencies/restore', 'restore')->name('currencies.restore');
        Route::get('currencies/getCurrency', 'getCurrency')->name('currencies.getCurrency');
    });

    // Customer Categories
    Route::controller(CustomerCategoryController::class)->group(function () {
        Route::get('customercategories', 'index')->name('customercategories.index');
        Route::get('customercategories/create', 'create')->name('customercategories.create');
        Route::post('customercategories', 'store')->name('customercategories.store');
        Route::get('customercategories/{category}/edit', 'edit')->name('customercategories.edit');
        Route::put('customercategories/{category}', 'update')->name('customercategories.update');
        Route::get('customercategories/destroy', 'destroy')->name('customercategories.destroy');
        Route::get('customercategories/restore', 'restore')->name('customercategories.restore');
        Route::get('customercategories/getCustomerCategory', 'getCustomerCategory')->name('customercategories.getCustomerCategory');
    });

    // Folders
    // Route::controller(FolderController::class)->group(function () {
    //     Route::get('folders', 'index')->name('folders.index');
    //     Route::get('folders/folders-list', 'list')->name('folders.list');
    //     Route::get('folders/create', 'create')->name('folders.create');
    //     Route::get('folders/{folder}', 'show')->name('folders.show');
    //     Route::post('folders', 'store')->name('folders.store');
    //     Route::get('folders/{folder}/edit', 'edit')->name('folders.edit');
    //     Route::put('folders/{folder}', 'update')->name('folders.update');
    //     Route::delete('folders/{folder}', 'destroy')->name('folders.destroy');
    //     Route::get('folders/restore', 'restore')->name('folders.restore');
    //     Route::get('folders/getFolder', 'getFolder')->name('folders.getFolder');
    //     Route::post('folders/{folder}/share', 'share')->name('folders.share');
    //     Route::post('folders/{folder}/move', 'move')->name('folders.move');
    // });

    // Modern Folders
    // Route::get('/modern-folders', ModernFolderIndexController::class)->name('modern-folders.index');
    // Route::get('/modern-folders/{folder:slug}', ModernFolderShowController::class)->name('modern-folders.show');

    // Route::controller(ModernFolderController::class)->group(function () {
    //     Route::get('modern-folders', 'index')->name('modern-folders.index');
    //     Route::post('modern-folders', 'store')->name('modern-folders.store');
    //     Route::get('modern-folders/getFolder', 'getFolder')->name('modern-folders.getFolder');
    //     Route::get('modern-folders/{folder:slug}', 'show')->name('modern-folders.show');
    //     Route::put('modern-folders/{folder:slug}', 'update')->name('modern-folders.update');
    //     Route::delete('modern-folders/{folder:slug}', 'destroy')->name('modern-folders.destroy');
    //     Route::post('modern-folders/{folder:slug}/share', 'share')->name('modern-folders.share');
    //     Route::post('modern-folders/{folder:slug}/unshare', 'unshare')->name('modern-folders.unshare');
    //     Route::post('modern-folders/{folder:slug}/move', 'move')->name('modern-folders.move');
    //     Route::get('modern-folders/{folder:slug}/download', 'download')->name('modern-folders.download');
    //     Route::get('modern-folders/download-zipped/{file}', 'downloadZipped')->name('modern-folders.downloadZipped');
    // });

    Route::get('file-manager', function () {
        return inertia('VAPFileManager/Index');
    })->name('file-manager');

    Route::prefix('api')->group(function () {
        // File routes
        Route::post('/files/upload', [VAPFileController::class, 'upload'])->name('files.upload');
        Route::post('/files/upload-folder', [VAPFileController::class, 'uploadFolder'])->name('files.upload-folder');
        Route::get('/files', [VAPFileController::class, 'index'])->name('files.list');
        Route::get('/files/search', [VAPFileController::class, 'search'])->name('files.search');
        Route::get('/files/{file}', [VAPFileController::class, 'show'])->name('files.show');
        Route::put('/files/{file}/rename', [VAPFileController::class, 'rename'])->name('files.rename');
        Route::put('/files/{file}/move', [VAPFileController::class, 'move'])->name('files.move');
        Route::post('/files/{file}/archive', [VAPFileController::class, 'archive'])->name('files.archive');
        Route::post('/files/{file}/restore', [VAPFileController::class, 'restore'])->name('files.restore');
        Route::delete('/files/{file}', [VAPFileController::class, 'destroy'])->name('files.destroy');
        Route::post('/files/{file}/share', [VAPFileController::class, 'share'])->name('files.share');
        Route::get('/files/{file}/versions', [VAPFileController::class, 'versions'])->name('files.versions');
        Route::post('/files/{file}/versions/{version}/restore', [VAPFileController::class, 'restoreVersion'])->name('files.versions.restore');
        Route::get('/files/{file}/download', [VAPFileController::class, 'download'])->name('files.download');
        Route::put('/files/{file}/metadata', [VAPFileController::class, 'updateMetadata'])->name('files.metadata.update');
        Route::post('/files/{file}/submit-review', [VAPFileController::class, 'submitReview'])->name('files.submit-review');
        Route::post('/files/{file}/approve', [VAPFileController::class, 'approve'])->name('files.approve');
        Route::post('/files/{file}/obsolete', [VAPFileController::class, 'markObsolete'])->name('files.obsolete');
        Route::get('/files/breadcrumbs/{folder?}', [VAPFileController::class, 'getBreadcrumbs'])->name('files.breadcrumbs');
        Route::get('/files/folders/getFolder', [VAPFileController::class, 'getFolders'])->name('files.folders-list');

        // Tag routes
        Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
        Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
        Route::put('/files/{file}/tags', [TagController::class, 'updateFileTags'])->name('files.tags.update');

        // Workflow routes
        Route::get('/workflow/tasks', [WorkflowController::class, 'index'])->name('workflow.tasks.index');
        Route::post('/workflow/tasks', [WorkflowController::class, 'store'])->name('workflow.tasks.store');
        Route::put('/workflow/tasks/{task}/status', [WorkflowController::class, 'updateStatus'])->name('workflow.tasks.update-status');
        Route::post('/workflow/tasks/{task}/comments', [WorkflowController::class, 'addComment'])->name('workflow.tasks.add-comment');
    });

    // Files
    // Route::controller(FileController::class)->group(function () {
    //     Route::get('file-manager', 'index')->name('files.index');
    //     Route::get('files-folders', 'filesAndFolders')->name('files.filesAndFolders');
    //     Route::get('files/create', 'create')->name('files.create');
    //     Route::post('files', 'store')->name('files.store');
    //     Route::post('files/upload-folder', 'uploadFolder')->name('files.uploadFolder');
    //     Route::get('files/{file}/edit', 'edit')->name('files.edit');
    //     Route::put('files/{file}', 'update')->name('files.update');
    //     Route::get('files/move', 'move')->name('files.move');
    //     Route::get('files/{file}/versions', 'versions')->name('files.getFileVersions');
    //     Route::put('files/{file}/versions/{version}/revert', 'revertToVersion')->name('files.revertToVersion');
    //     Route::get('files/destroy', 'destroy')->name('files.destroy');
    //     Route::get('files/restore', 'restore')->name('files.restore');
    //     Route::get('files/getFile', 'getFile')->name('files.getFile');
    //     Route::get('files/download-file', 'downloadFile')->name('files.download');
    //     Route::get('files/download-zip', 'downloadZip')->name('files.downloadZip');
    // });

    // Maintenance Categories
    Route::controller(MaintenanceCategoryController::class)->group(function () {
        Route::get('maintenancecategories', 'index')->name('maintenancecategories.index');
        Route::get('maintenancecategories/create', 'create')->name('maintenancecategories.create');
        Route::post('maintenancecategories', 'store')->name('maintenancecategories.store');
        Route::get('maintenancecategories/{category}/edit', 'edit')->name('maintenancecategories.edit');
        Route::put('maintenancecategories/{category}', 'update')->name('maintenancecategories.update');
        Route::get('maintenancecategories/destroy', 'destroy')->name('maintenancecategories.destroy');
        Route::get('maintenancecategories/restore', 'restore')->name('maintenancecategories.restore');
        Route::get('maintenancecategories/getMaintenanceCategory', 'getMaintenanceCategory')->name('maintenancecategories.getMaintenanceCategory');
    });

    // Maintenance Tasks
    Route::controller(MaintenanceTaskController::class)->group(function () {
        Route::get('maintenancetasks', 'index')->name('maintenancetasks.index');
        Route::get('maintenancetasks/create', 'create')->name('maintenancetasks.create');
        Route::post('maintenancetasks', 'store')->name('maintenancetasks.store');
        Route::get('maintenancetasks/{maintenancetask}/edit', 'edit')->name('maintenancetasks.edit');
        Route::put('maintenancetasks/{maintenancetask}', 'update')->name('maintenancetasks.update');
        Route::get('maintenancetasks/{maintenancetask}/show', 'show')->name('maintenancetasks.show');
        Route::get('maintenancetasks/destroy', 'destroy')->name('maintenancetasks.destroy');
        Route::get('maintenancetasks/restore', 'restore')->name('maintenancetasks.restore');
        // Route::get('maintenancetasks/getMaintenanceTask', 'getMaintenanceTask')->name('maintenancetasks.getMaintenanceTask');
    });

    // Occurrence Categories
    Route::controller(OccurrenceCategoryController::class)->group(function () {
        Route::get('occurrencecategories', 'index')->name('occurrencecategories.index');
        Route::get('occurrencecategories/create', 'create')->name('occurrencecategories.create');
        Route::post('occurrencecategories', 'store')->name('occurrencecategories.store');
        Route::get('occurrencecategories/{category}/edit', 'edit')->name('occurrencecategories.edit');
        Route::put('occurrencecategories/{category}', 'update')->name('occurrencecategories.update');
        Route::get('occurrencecategories/destroy', 'destroy')->name('occurrencecategories.destroy');
        Route::get('occurrencecategories/restore', 'restore')->name('occurrencecategories.restore');
        Route::get('occurrencecategories/getOccurrenceCategory', 'getOccurrenceCategory')->name('occurrencecategories.getOccurrenceCategory');
    });

    // Occurrence Origins
    Route::controller(OccurrenceOriginController::class)->group(function () {
        Route::get('occurrenceorigins', 'index')->name('occurrenceorigins.index');
        Route::get('occurrenceorigins/create', 'create')->name('occurrenceorigins.create');
        Route::post('occurrenceorigins', 'store')->name('occurrenceorigins.store');
        Route::get('occurrenceorigins/{category}/edit', 'edit')->name('occurrenceorigins.edit');
        Route::put('occurrenceorigins/{category}', 'update')->name('occurrenceorigins.update');
        Route::get('occurrenceorigins/destroy', 'destroy')->name('occurrenceorigins.destroy');
        Route::get('occurrenceorigins/restore', 'restore')->name('occurrenceorigins.restore');
        Route::get('occurrenceorigins/getOccurrenceOrigin', 'getOccurrenceOrigin')->name('occurrenceorigins.getOccurrenceOrigin');
    });

    // Occurrence Statuses
    Route::controller(OccurrenceStatusController::class)->group(function () {
        Route::get('occurrencestatuses', 'index')->name('occurrencestatuses.index');
        Route::get('occurrencestatuses/create', 'create')->name('occurrencestatuses.create');
        Route::post('occurrencestatuses', 'store')->name('occurrencestatuses.store');
        Route::get('occurrencestatuses/{status}/edit', 'edit')->name('occurrencestatuses.edit');
        Route::put('occurrencestatuses/{status}', 'update')->name('occurrencestatuses.update');
        Route::get('occurrencestatuses/destroy', 'destroy')->name('occurrencestatuses.destroy');
        Route::get('occurrencestatuses/restore', 'restore')->name('occurrencestatuses.restore');
        Route::get('occurrencestatuses/getOccurrenceStatus', 'getOccurrenceStatus')->name('occurrencestatuses.getOccurrenceStatus');
    });

    // Occurrences
    Route::controller(OccurrenceController::class)->group(function () {
        Route::get('occurrences', 'index')->name('occurrences.index');
        Route::get('occurrences/create', 'create')->name('occurrences.create');
        Route::post('occurrences', 'store')->name('occurrences.store');
        Route::get('occurrences/{occurrence}/edit', 'edit')->name('occurrences.edit');
        Route::put('occurrences/{occurrence}', 'update')->name('occurrences.update');
        Route::get('occurrences/{occurrence}/show', 'show')->name('occurrences.show');
        Route::get('occurrences/destroy', 'destroy')->name('occurrences.destroy');
        Route::get('occurrences/restore', 'restore')->name('occurrences.restore');
        // Route::get('occurrences/getOccurrence', 'getOccurrence')->name('occurrences.getOccurrence');
    });

    Route::controller(ComplaintController::class)->group(function () {
        Route::get('complaints', 'index')->name('complaints.index');
        Route::post('complaints', 'store')->name('complaints.store');
        Route::put('complaints/{complaint}', 'update')->name('complaints.update');
    });

    Route::controller(ManagementReviewController::class)->group(function () {
        Route::get('management-reviews', 'index')->name('management-reviews.index');
        Route::post('management-reviews', 'store')->name('management-reviews.store');
        Route::put('management-reviews/{managementReview}', 'update')->name('management-reviews.update');
    });

    // Departments
    Route::controller(DepartmentController::class)->group(function () {
        Route::get('departments', 'index')->name('departments.index');
        Route::get('departments/create', 'create')->name('departments.create');
        Route::post('departments', 'store')->name('departments.store');
        Route::get('departments/{department}/edit', 'edit')->name('departments.edit');
        Route::put('departments/{department}', 'update')->name('departments.update');
        Route::get('departments/destroy', 'destroy')->name('departments.destroy');
        Route::get('departments/restore', 'restore')->name('departments.restore');
        Route::get('departments/getDepartment', 'getDepartment')->name('departments.getDepartment');
    });

    // Discount Categories
    Route::controller(DiscountCategoryController::class)->group(function () {
        Route::get('discountcategories', 'index')->name('discountcategories.index');
        Route::get('discountcategories/create', 'create')->name('discountcategories.create');
        Route::post('discountcategories', 'store')->name('discountcategories.store');
        Route::get('discountcategories/{category}/edit', 'edit')->name('discountcategories.edit');
        Route::put('discountcategories/{category}', 'update')->name('discountcategories.update');
        Route::get('discountcategories/destroy', 'destroy')->name('discountcategories.destroy');
        Route::get('discountcategories/restore', 'restore')->name('discountcategories.restore');
    });

    // Invoice Categories
    Route::controller(InvoiceCategoryController::class)->group(function () {
        Route::get('invoicecategories', 'index')->name('invoicecategories.index');
        Route::get('invoicecategories/create', 'create')->name('invoicecategories.create');
        Route::post('invoicecategories', 'store')->name('invoicecategories.store');
        Route::get('invoicecategories/{category}/edit', 'edit')->name('invoicecategories.edit');
        Route::put('invoicecategories/{category}', 'update')->name('invoicecategories.update');
        Route::get('invoicecategories/destroy', 'destroy')->name('invoicecategories.destroy');
        Route::get('invoicecategories/restore', 'restore')->name('invoicecategories.restore');
        Route::get('invoicecategories/getInvoiceCategory', 'getInvoiceCategory')->name('invoicecategories.getInvoiceCategory');
    });

    // Normative Work Procedures
    Route::controller(NormativeWorkProcedureController::class)->group(function () {
        Route::get('nwps', 'index')->name('nwps.index');
        Route::get('nwps/create', 'create')->name('nwps.create');
        Route::post('nwps', 'store')->name('nwps.store');
        Route::get('nwps/{nwp}/edit', 'edit')->name('nwps.edit');
        Route::put('nwps/{nwp}', 'update')->name('nwps.update');
        Route::get('nwps/destroy', 'destroy')->name('nwps.destroy');
        Route::get('nwps/restore', 'restore')->name('nwps.restore');
        Route::get('nwps/getNwp', 'getNwp')->name('nwps.getNwp');
    });

    // Packaging Categories
    Route::controller(PackagingCategoryController::class)->group(function () {
        Route::get('packagingcategories', 'index')->name('packagingcategories.index');
        Route::get('packagingcategories/create', 'create')->name('packagingcategories.create');
        Route::post('packagingcategories', 'store')->name('packagingcategories.store');
        Route::get('packagingcategories/{category}/edit', 'edit')->name('packagingcategories.edit');
        Route::put('packagingcategories/{category}', 'update')->name('packagingcategories.update');
        Route::get('packagingcategories/destroy', 'destroy')->name('packagingcategories.destroy');
        Route::get('packagingcategories/restore', 'restore')->name('packagingcategories.restore');
        Route::get('packagingcategories/getPackagingCategory', 'getPackagingCategory')->name('packagingcategories.getPackagingCategory');
    });

    // Parameters
    Route::controller(ParameterController::class)->group(function () {
        Route::get('parameters', 'index')->name('parameters.index');
        Route::get('parameters/create', 'create')->name('parameters.create');
        Route::post('parameters', 'store')->name('parameters.store');
        Route::get('parameters/{parameter}/edit', 'edit')->name('parameters.edit');
        Route::put('parameters/{parameter}', 'update')->name('parameters.update');
        Route::get('parameters/destroy', 'destroy')->name('parameters.destroy');
        Route::get('parameters/restore', 'restore')->name('parameters.restore');
        Route::get('parameters/getParameter', 'getParameter')->name('parameters.getParameter');
    });

    // Payment Categories
    Route::controller(PaymentCategoryController::class)->group(function () {
        Route::get('paymentcategories', 'index')->name('paymentcategories.index');
        Route::get('paymentcategories/create', 'create')->name('paymentcategories.create');
        Route::post('paymentcategories', 'store')->name('paymentcategories.store');
        Route::get('paymentcategories/{category}/edit', 'edit')->name('paymentcategories.edit');
        Route::put('paymentcategories/{category}', 'update')->name('paymentcategories.update');
        Route::get('paymentcategories/destroy', 'destroy')->name('paymentcategories.destroy');
        Route::get('paymentcategories/restore', 'restore')->name('paymentcategories.restore');
        Route::get('paymentcategories/getPaymentCategory', 'getPaymentCategory')->name('paymentcategories.getPaymentCategory');
    });

    // Protocols
    Route::controller(ProtocolController::class)->group(function () {
        Route::get('protocols', 'index')->name('protocols.index');
        Route::get('protocols/create', 'create')->name('protocols.create');
        Route::post('protocols', 'store')->name('protocols.store');
        Route::get('protocols/{protocol}/edit', 'edit')->name('protocols.edit');
        Route::put('protocols/{protocol}', 'update')->name('protocols.update');
        Route::get('protocols/destroy', 'destroy')->name('protocols.destroy');
        Route::get('protocols/restore', 'restore')->name('protocols.restore');
        Route::get('protocols/getProtocol', 'getProtocol')->name('protocols.getProtocol');
    });

    // Rating
    Route::controller(RatingController::class)->group(function () {
        Route::get('ratings', 'index')->name('ratings.index');
        Route::get('rate/{rateableType}/{rateableId?}', 'create')->whereNumber('rateableId')->name('rating.create');
        Route::post('rate/{rateableType}/{rateableId?}', 'store')->whereNumber('rateableId')->name('rating.store');
    });

    // Application Event Controller
    Route::controller(ApplicationEventController::class)->group(function () {
        Route::get('app-events', 'index')->name('app-events.index');
        Route::get('app-events/sync', 'sync')->name('app-events.sync');
        Route::post('app-events/{event}/associate', 'associate')->name('app-events.associate');
    });

    // Result Categories
    Route::controller(ResultCategoryController::class)->group(function () {
        Route::get('resultcategories', 'index')->name('resultcategories.index');
        Route::get('resultcategories/create', 'create')->name('resultcategories.create');
        Route::post('resultcategories', 'store')->name('resultcategories.store');
        Route::get('resultcategories/{category}/edit', 'edit')->name('resultcategories.edit');
        Route::put('resultcategories/{category}', 'update')->name('resultcategories.update');
        Route::get('resultcategories/destroy', 'destroy')->name('resultcategories.destroy');
        Route::get('resultcategories/restore', 'restore')->name('resultcategories.restore');
        Route::get('resultcategories/getResultCategory', 'getResultCategory')->name('resultcategories.getResultCategory');
    });

    // Standards
    Route::controller(StandardController::class)->group(function () {
        Route::get('standards', 'index')->name('standards.index');
        Route::get('standards/create', 'create')->name('standards.create');
        Route::post('standards', 'store')->name('standards.store');
        Route::get('standards/{standard}/edit', 'edit')->name('standards.edit');
        Route::put('standards/{standard}', 'update')->name('standards.update');
        Route::get('standards/destroy', 'destroy')->name('standards.destroy');
        Route::get('standards/restore', 'restore')->name('standards.restore');
        Route::get('standards/getStandard', 'getStandard')->name('standards.getStandard');
    });

    // Phytosanitary Products
    Route::controller(PhytosanitaryProductController::class)->group(function () {
        Route::get('phytosanitary-products', 'index')->name('phytosanitary_products.index');
        Route::get('phytosanitary-products/create', 'create')->name('phytosanitary_products.create');
        Route::post('phytosanitary-products', 'store')->name('phytosanitary_products.store');
        Route::get('phytosanitary-products/{product}/edit', 'edit')->name('phytosanitary_products.edit');
        Route::put('phytosanitary-products/{product}', 'update')->name('phytosanitary_products.update');
        Route::get('phytosanitary-products/destroy', 'destroy')->name('phytosanitary_products.destroy');
        Route::get('phytosanitary-products/restore', 'restore')->name('phytosanitary_products.restore');
        Route::get('phytosanitary-products/getPhytosanitaryProduct', 'getPhytosanitaryProduct')->name('phytosanitary_products.getPhytosanitaryProduct');
    });

    // Tax Exemptions
    Route::controller(TaxExemptionController::class)->group(function () {
        Route::get('taxexemptions', 'index')->name('taxexemptions.index');
        Route::get('taxexemptions/create', 'create')->name('taxexemptions.create');
        Route::post('taxexemptions', 'store')->name('taxexemptions.store');
        Route::get('taxexemptions/{taxexemption}/edit', 'edit')->name('taxexemptions.edit');
        Route::put('taxexemptions/{taxexemption}', 'update')->name('taxexemptions.update');
        Route::get('taxexemptions/destroy', 'destroy')->name('taxexemptions.destroy');
        Route::get('taxexemptions/restore', 'restore')->name('taxexemptions.restore');
        Route::get('taxexemptions/getExemption', 'getExemption')->name('taxexemptions.getExemption');
    });

    // Import Certificates
    Route::controller(ImportCertificateController::class)->group(function () {
        Route::get('import-certificates', 'index')->name('importcertificates.index');
        Route::get('import-certificates/create', 'create')->name('importcertificates.create');
        Route::post('import-certificates', 'store')->name('importcertificates.store');
        Route::get('import-certificates/{importcertificate}/edit', 'edit')->name('importcertificates.edit');
        Route::get('import-certificates/getIssueInvoiceModal', 'getIssueInvoiceModal')->name('importcertificates.getIssueInvoiceModal');
        Route::post('import-certificates', 'issueInvoice')->name('importcertificates.issueInvoice');
        Route::put('import-certificates/{importcertificate}', 'update')->name('importcertificates.update');
        Route::get('import-certificates/{importcertificate}/show', 'show')->name('importcertificates.show');
        Route::get('import-certificates/destroy', 'destroy')->name('importcertificates.destroy');
        Route::get('import-certificates/restore', 'restore')->name('importcertificates.restore');
        Route::get('import-certificates/getImportCertificate', 'getImportCertificate')->name('importcertificates.getImportCertificate');
        Route::get('import-certificates/getPDF', 'getPDF')->name('importcertificates.getPDF');

    });

    // Export Certificates
    Route::controller(ExportCertificateController::class)->group(function () {
        Route::get('export-certificates', 'index')->name('exportcertificates.index');
        Route::get('export-certificates/create', 'create')->name('exportcertificates.create');
        Route::post('export-certificates', 'store')->name('exportcertificates.store');
        Route::get('export-certificates/{exportcertificate}/edit', 'edit')->name('exportcertificates.edit');
        Route::get('export-certificates/getIssueInvoiceModal', 'getIssueInvoiceModal')->name('exportcertificates.getIssueInvoiceModal');
        Route::post('export-certificates', 'issueInvoice')->name('exportcertificates.issueInvoice');
        Route::put('export-certificates/{exportcertificate}', 'update')->name('exportcertificates.update');
        Route::get('export-certificates/{exportcertificate}/show', 'show')->name('exportcertificates.show');
        Route::get('export-certificates/destroy', 'destroy')->name('exportcertificates.destroy');
        Route::get('export-certificates/restore', 'restore')->name('exportcertificates.restore');
        Route::get('export-certificates/getExportCertificate', 'getExportCertificate')->name('exportcertificates.getExportCertificate');
        Route::get('export-certificates/getPDF', 'getPDF')->name('exportcertificates.getPDF');

    });

    // Tax Types
    Route::controller(TaxTypeController::class)->group(function () {
        Route::get('taxtypes', 'index')->name('taxtypes.index');
        Route::get('taxtypes/create', 'create')->name('taxtypes.create');
        Route::post('taxtypes', 'store')->name('taxtypes.store');
        Route::get('taxtypes/{taxtype}/edit', 'edit')->name('taxtypes.edit');
        Route::put('taxtypes/{taxtype}', 'update')->name('taxtypes.update');
        Route::get('taxtypes/destroy', 'destroy')->name('taxtypes.destroy');
        Route::get('taxtypes/restore', 'restore')->name('taxtypes.restore');
        Route::get('taxtypes/getTaxType', 'getTaxType')->name('taxtypes.getTaxType');
    });

    // Temperatures
    Route::controller(TemperatureController::class)->group(function () {
        Route::get('temperatures', 'index')->name('temperatures.index');
        Route::get('temperatures/create', 'create')->name('temperatures.create');
        Route::post('temperatures', 'store')->name('temperatures.store');
        Route::get('temperatures/{temperature}/edit', 'edit')->name('temperatures.edit');
        Route::put('temperatures/{temperature}', 'update')->name('temperatures.update');
        Route::get('temperatures/destroy', 'destroy')->name('temperatures.destroy');
        Route::get('temperatures/restore', 'restore')->name('temperatures.restore');
        Route::get('temperatures/getTemperature', 'getTemperature')->name('temperatures.getTemperature');
    });

    Route::controller(EnvironmentalConditionController::class)->group(function () {
        Route::get('environmental-conditions', 'index')->name('environmental-conditions.index');
        Route::post('environmental-conditions', 'store')->name('environmental-conditions.store');
        Route::put('environmental-conditions/{environmentalCondition}', 'update')->name('environmental-conditions.update');
        Route::delete('environmental-conditions/{environmentalCondition}', 'destroy')->name('environmental-conditions.destroy');
    });

    // Transport Categories
    Route::controller(TransportCategoryController::class)->group(function () {
        Route::get('transportcategories', 'index')->name('transportcategories.index');
        Route::get('transportcategories/create', 'create')->name('transportcategories.create');
        Route::post('transportcategories', 'store')->name('transportcategories.store');
        Route::get('transportcategories/{category}/edit', 'edit')->name('transportcategories.edit');
        Route::put('transportcategories/{category}', 'update')->name('transportcategories.update');
        Route::get('transportcategories/destroy', 'destroy')->name('transportcategories.destroy');
        Route::get('transportcategories/restore', 'restore')->name('transportcategories.restore');
        Route::get('transportcategories/getTransportCategory', 'getTransportCategory')->name('transportcategories.getTransportCategory');
    });

    // FAQ Categories
    Route::controller(FAQCategoryController::class)->group(function () {
        Route::get('faqcategories', 'index')->name('faqcategories.index');
        Route::get('faqcategories/create', 'create')->name('faqcategories.create');
        Route::post('faqcategories', 'store')->name('faqcategories.store');
        Route::get('faqcategories/{category}/edit', 'edit')->name('faqcategories.edit');
        Route::put('faqcategories/{category}', 'update')->name('faqcategories.update');
        Route::get('faqcategories/destroy', 'destroy')->name('faqcategories.destroy');
        Route::get('faqcategories/restore', 'restore')->name('faqcategories.restore');
        Route::get('faqcategories/getFAQCategory', 'getFAQCategory')->name('faqcategories.getFAQCategory');
    });

    // FAQs
    Route::controller(FAQController::class)->group(function () {
        Route::get('faqs', 'index')->name('faqs.index');
        Route::get('faqs/create', 'create')->name('faqs.create');
        Route::post('faqs', 'store')->name('faqs.store');
        Route::get('faqs/{faq}/edit', 'edit')->name('faqs.edit');
        Route::put('faqs/{faq}', 'update')->name('faqs.update');
        Route::get('faqs/destroy', 'destroy')->name('faqs.destroy');
        Route::get('faqs/restore', 'restore')->name('faqs.restore');
        Route::get('faqs/getFAQ', 'getFAQ')->name('faqs.getFAQ');
    });

    // FAQ Answers
    Route::controller(FAQAnswerController::class)->group(function () {
        Route::get('faqanswers', 'index')->name('faqanswers.index');
        Route::get('faqanswers/create', 'create')->name('faqanswers.create');
        Route::post('faqanswers', 'store')->name('faqanswers.store');
        Route::get('faqanswers/{answer}/edit', 'edit')->name('faqanswers.edit');
        Route::put('faqanswers/{answer}', 'update')->name('faqanswers.update');
        Route::get('faqanswers/destroy', 'destroy')->name('faqanswers.destroy');
        Route::get('faqanswers/restore', 'restore')->name('faqanswers.restore');
        Route::get('faqanswers/getFAQAnswer', 'getFAQAnswer')->name('faqanswers.getFAQAnswer');
    });

    // Formulas
    Route::controller(FormulaController::class)->group(function () {
        Route::get('formulas', 'index')->name('formulas.index');
        Route::get('formulas/mb', 'mb')->name('formulas.mb');
        Route::get('formulas/create', 'create')->name('formulas.create');
        Route::post('formulas', 'store')->name('formulas.store');
        Route::get('formulas/{formula}/edit', 'edit')->name('formulas.edit');
        Route::put('formulas/{formula}', 'update')->name('formulas.update');
        Route::get('formulas/destroy', 'destroy')->name('formulas.destroy');
        Route::get('formulas/restore', 'restore')->name('formulas.restore');
        Route::get('formulas/getFormula', 'getFormula')->name('formulas.getFormula');
    });

    Route::controller(ProficiencyTestController::class)->group(function () {
        Route::get('proficiency-tests', 'index')->name('proficiency_tests.index');
        Route::get('proficiency-tests/create', 'create')->name('proficiency_tests.create');
        Route::post('proficiency-tests', 'store')->name('proficiency_tests.store');
        Route::get('proficiency-tests/destroy', 'destroy')->name('proficiency_tests.destroy');
        Route::get('proficiency-tests/restore', 'restore')->name('proficiency_tests.restore');
        Route::get('proficiency-tests/{test}', 'show')->name('proficiency_tests.show');
        Route::get('proficiency-tests/{test}/edit', 'edit')->name('proficiency_tests.edit');
        Route::put('proficiency-tests/{test}', 'update')->name('proficiency_tests.update');
        Route::get('proficiency-tests/{test}/results-template', 'downloadResultsTemplate')->name('proficiency_tests.results.template');
        Route::post('proficiency-tests/{test}/results-import', 'importResults')->name('proficiency_tests.results.import');
        Route::put('proficiency-tests/{test}/results', 'updateResults')->name('proficiency_tests.results.update');
    });

    // Variables
    Route::controller(VariableController::class)->group(function () {
        Route::get('variables', 'index')->name('variables.index');
        Route::get('variables/create', 'create')->name('variables.create');
        Route::post('variables', 'store')->name('variables.store');
        Route::get('variables/{variableVariable}/edit', 'edit')->name('variables.edit');
        Route::put('variables/{variableVariable}', 'update')->name('variables.update');
        Route::get('variables/destroy', 'destroy')->name('variables.destroy');
        Route::get('variables/restore', 'restore')->name('variables.restore');
        Route::get('variables/getVariable', 'getVariable')->name('variables.getVariable');
    });

    // Messages
    Route::controller(MessageController::class)->group(function () {
        Route::get('messages', 'index')->name('messages.index');
        Route::get('messages/create', 'create')->name('messages.create');
        Route::post('messages', 'store')->name('messages.store');
        Route::post('messages/{id}/read', 'markAsRead')->name('messages.markAsRead');
        Route::get('messages/{message}/edit', 'edit')->name('messages.edit');
        Route::put('messages/{message}', 'update')->name('messages.update');
        Route::get('messages/destroy', 'destroy')->name('messages.destroy');
        Route::get('messages/restore', 'restore')->name('messages.restore');
        Route::get('messages/getMessage', 'getMessage')->name('messages.getMessage');
    });

    // Notifications

    Route::controller(NotificationController::class)->group(function () {

        Route::get('notifications', 'index')->name('notifications.index');

        // Mark as read/unread
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifications/{notification}/unread', [NotificationController::class, 'markAsUnread'])->name('notifications.unread');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

        // Delete notifications
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.delete');
        Route::delete('/notifications/clear-all', [NotificationController::class, 'clearAll'])->name('notifications.clear-all');
        Route::delete('/notifications/clear-read', [NotificationController::class, 'clearRead'])->name('notifications.clear-read');
    });

    // Admin Notification Center

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/notifications/dashboard', [NotificationController::class, 'adminDashboard'])->name('notifications.dashboard');
        Route::get('/notifications', [NotificationController::class, 'adminIndex'])->name('notifications.index');
        Route::get('/notifications/create', [NotificationController::class, 'adminCreate'])->name('notifications.create');
        Route::post('/notifications', [NotificationController::class, 'adminStore'])->name('notifications.store');
        Route::get('/notifications/{id}', [NotificationController::class, 'adminShow'])->name('notifications.show');
        Route::get('/notifications/analytics', [NotificationController::class, 'adminAnalytics'])->name('notifications.analytics');
        Route::get('/notifications/export', [NotificationController::class, 'adminExport'])->name('notifications.export');
    });

    // Customer Request Categories
    Route::controller(CustomerRequestCategoryController::class)->group(function () {
        Route::get('customerrequestcategories', 'index')->name('customerrequestcategories.index');
        Route::get('customerrequestcategories/create', 'create')->name('customerrequestcategories.create');
        Route::post('customerrequestcategories', 'store')->name('customerrequestcategories.store');
        Route::get('customerrequestcategories/{category}/edit', 'edit')->name('customerrequestcategories.edit');
        Route::put('customerrequestcategories/{category}', 'update')->name('customerrequestcategories.update');
        Route::get('customerrequestcategories/destroy', 'destroy')->name('customerrequestcategories.destroy');
        Route::get('customerrequestcategories/restore', 'restore')->name('customerrequestcategories.restore');
        Route::get('customerrequestcategories/getCustomerRequestCategory', 'getCustomerRequestCategory')->name('customerrequestcategories.getCustomerRequestCategory');
    });

    // Customer Requests
    Route::controller(CustomerRequestController::class)->group(function () {
        Route::get('customerrequests', 'index')->name('customerrequests.index');
        Route::get('customerrequests/create', 'create')->name('customerrequests.create');
        Route::post('customerrequests', 'store')->name('customerrequests.store');
        Route::get('customerrequests/{request}/edit', 'edit')->name('customerrequests.edit');
        Route::put('customerrequests/{request}', 'update')->name('customerrequests.update');
        Route::get('customerrequests/destroy', 'destroy')->name('customerrequests.destroy');
        Route::get('customerrequests/restore', 'restore')->name('customerrequests.restore');
        Route::get('customerrequests/getCustomerRequest', 'getCustomerRequest')->name('customerrequests.getCustomerRequest');
    });

    // Vehicles
    Route::controller(VehicleController::class)->group(function () {
        Route::get('vehicles', 'index')->name('vehicles.index');
        Route::get('vehicles/create', 'create')->name('vehicles.create');
        Route::post('vehicles', 'store')->name('vehicles.store');
        Route::get('vehicles/{vehicle}/edit', 'edit')->name('vehicles.edit');
        Route::put('vehicles/{vehicle}', 'update')->name('vehicles.update');
        Route::get('vehicles/destroy', 'destroy')->name('vehicles.destroy');
        Route::get('vehicles/restore', 'restore')->name('vehicles.restore');
        Route::get('vehicles/getVehicle', 'getVehicle')->name('vehicles.getVehicle');
    });

    // Analysis
    Route::controller(AnalysisController::class)->group(function () {
        Route::get('analysis', 'index')->name('analysis.index');
        Route::get('analysis/create', 'create')->name('analysis.create');
        Route::post('analysis', 'store')->name('analysis.store');
        Route::get('analysis/{analysis}/edit', 'edit')->name('analysis.edit');
        Route::put('analysis/{analysis}', 'update')->name('analysis.update');
        Route::get('analysis/destroy', 'destroy')->name('analysis.destroy');
        Route::get('analysis/restore', 'restore')->name('analysis.restore');
        Route::get('analysis/getAnalysis', 'getAnalysis')->name('analysis.getAnalysis');
    });

    // Counter Analysis
    Route::controller(CounterAnalysisController::class)->group(function () {
        Route::get('counteranalysis', 'index')->name('counteranalysis.index');
        Route::get('counteranalysis/create', 'create')->name('counteranalysis.create');
        Route::post('counteranalysis', 'store')->name('counteranalysis.store');
        Route::get('counteranalysis/{analysis}/edit', 'edit')->name('counteranalysis.edit');
        Route::put('counteranalysis/{analysis}', 'update')->name('counteranalysis.update');
        Route::get('counteranalysis/destroy', 'destroy')->name('counteranalysis.destroy');
        Route::get('counteranalysis/restore', 'restore')->name('counteranalysis.restore');
        Route::get('counteranalysis/getAnalysis', 'getAnalysis')->name('counteranalysis.getAnalysis');
    });

    // Samples
    Route::controller(SampleController::class)->group(function () {
        Route::get('samples', 'index')->name('samples.index');
        Route::get('samples/create', 'create')->name('samples.create');
        Route::post('samples', 'store')->name('samples.store');
        Route::get('samples/{sample}/edit', 'edit')->name('samples.edit');
        Route::put('samples/{sample}', 'update')->name('samples.update');
        Route::get('samples/destroy', 'destroy')->name('samples.destroy');
        Route::get('samples/restore', 'restore')->name('samples.restore');
        Route::get('samples/getCode', 'getCode')->name('samples.getCode');
    });

    // Units
    Route::controller(UnitController::class)->group(function () {
        Route::get('units', 'index')->name('units.index');
        Route::get('units/create', 'create')->name('units.create');
        Route::post('units', 'store')->name('units.store');
        Route::get('units/{unit}/edit', 'edit')->name('units.edit');
        Route::put('units/{unit}', 'update')->name('units.update');
        Route::get('units/destroy', 'destroy')->name('units.destroy');
        Route::get('units/restore', 'restore')->name('units.restore');
        Route::get('units/getUnit', 'getUnit')->name('units.getUnit');
    });

    // Profiles
    Route::controller(ProfileController::class)->group(function () {
        Route::get('profiles', 'index')->name('profiles.index');
        Route::get('profiles/create', 'create')->name('profiles.create');
        Route::post('profiles', 'store')->name('profiles.store');
        Route::get('profiles/{profile}/edit', 'edit')->name('profiles.edit');
        Route::put('profiles/{profile}', 'update')->name('profiles.update');
        Route::get('profiles/{profile}/show', 'show')->name('profiles.show');
        Route::get('profiles/destroy', 'destroy')->name('profiles.destroy');
        Route::get('profiles/restore', 'restore')->name('profiles.restore');
        Route::get('profiles/getProfile', 'getProfile')->name('profiles.getProfile');
    });

    // Matrixes
    Route::controller(MatrixController::class)->group(function () {
        Route::get('matrixes', 'index')->name('matrixes.index');
        Route::get('matrixes/create', 'create')->name('matrixes.create');
        Route::post('matrixes', 'store')->name('matrixes.store');
        Route::get('matrixes/{matrix}/edit', 'edit')->name('matrixes.edit');
        Route::put('matrixes/{matrix}', 'update')->name('matrixes.update');
        Route::get('matrixes/{matrix}/show', 'show')->name('matrixes.show');
        Route::get('matrixes/destroy', 'destroy')->name('matrixes.destroy');
        Route::get('matrixes/restore', 'restore')->name('matrixes.restore');
        Route::get('matrixes/getMatrix', 'getMatrix')->name('matrixes.getMatrix');
    });

    // Products
    Route::controller(ProductController::class)->group(function () {
        Route::get('products', 'index')->name('products.index');
        Route::get('products/create', 'create')->name('products.create');
        Route::post('products', 'store')->name('products.store');
        Route::get('products/{product}/edit', 'edit')->name('products.edit');
        Route::put('products/{product}', 'update')->name('products.update');
        Route::get('products/destroy', 'destroy')->name('products.destroy');
        Route::get('products/restore', 'restore')->name('products.restore');
        Route::get('products/getProduct', 'getProduct')->name('products.getProduct');
    });

    // Paid Services
    Route::controller(PaidServiceController::class)->group(function () {
        Route::get('paid-services', 'index')->name('paidservices.index');
        Route::get('paid-services/create', 'create')->name('paidservices.create');
        Route::post('paid-services', 'store')->name('paidservices.store');
        Route::get('paid-services/{service}/edit', 'edit')->name('paidservices.edit');
        Route::put('paid-services/{service}', 'update')->name('paidservices.update');
        Route::get('paid-services/destroy', 'destroy')->name('paidservices.destroy');
        Route::get('paid-services/restore', 'restore')->name('paidservices.restore');
        Route::get('paid-services/getPaidService', 'getPaidService')->name('paidservices.getPaidService');
    });

    // Customers
    Route::controller(CustomerController::class)->group(function () {
        Route::get('customers', 'index')->name('customers.index');
        Route::get('customers/create', 'create')->name('customers.create');
        Route::post('customers', 'store')->name('customers.store');
        Route::get('customers/{customer}/edit', 'edit')->name('customers.edit');
        Route::put('customers/{customer}', 'update')->name('customers.update');
        Route::get('customers/{customer}/show', 'show')->name('customers.show');
        Route::put('customers/{customer}/changePrimaryWarehouse', 'changePrimaryWarehouse')->name('customers.changePrimaryWarehouse');
        Route::get('customers/destroy', 'destroy')->name('customers.destroy');
        Route::get('customers/restore', 'restore')->name('customers.restore');
        Route::get('customers/tax-data', 'getTaxData')->name('customers.taxData');
        Route::get('customers/tax-identification', 'taxIdentification')->name('customers.taxIdentification');
        Route::get('customers/getCustomer', 'getCustomer')->name('customers.getCustomer');
    });

    // Collection Reasons
    Route::controller(CollectionReasonController::class)->group(function () {
        Route::get('collectionreasons', 'index')->name('collectionreasons.index');
        Route::get('collectionreasons/create', 'create')->name('collectionreasons.create');
        Route::post('collectionreasons', 'store')->name('collectionreasons.store');
        Route::get('collectionreasons/{reason}/edit', 'edit')->name('collectionreasons.edit');
        Route::put('collectionreasons/{reason}', 'update')->name('collectionreasons.update');
        Route::get('collectionreasons/destroy', 'destroy')->name('collectionreasons.destroy');
        Route::get('collectionreasons/restore', 'restore')->name('collectionreasons.restore');
        Route::get('collectionreasons/getCollectionReason', 'getCollectionReason')->name('collectionreasons.getCollectionReason');
    });

    // Direct Collections
    Route::controller(DirectCollectionController::class)->group(function () {
        Route::get('directcollections', 'index')->name('directcollections.index');
        Route::get('directcollections/create', 'create')->name('directcollections.create');
        Route::post('directcollections', 'store')->name('directcollections.store');
        Route::get('directcollections/{collection}/edit', 'edit')->name('directcollections.edit');
        Route::put('directcollections/{collection}', 'update')->name('directcollections.update');
        Route::get('directcollections/destroy', 'destroy')->name('directcollections.destroy');
        Route::get('directcollections/restore', 'restore')->name('directcollections.restore');
        Route::get('directcollections/getParametersToAnalyzePDF', 'getParametersToAnalyzePDF')->name('directcollections.getParametersToAnalyzePDF');
        Route::get('directcollections/getMultipleParametersToAnalyzePDF', 'getMultipleParametersToAnalyzePDF')->name('directcollections.getMultipleParametersToAnalyzePDF');
        Route::get('directcollections/exportParametersToAnalyzeSheet', 'exportParametersToAnalyzeSheet')->name('directcollections.exportParametersToAnalyzeSheet');
        Route::get('directcollections/getCollectionTermPDF', 'getCollectionTermPDF')->name('directcollections.getCollectionTermPDF');
        Route::get('directcollections/getCollectionLabels', 'getCollectionLabels')->name('directcollections.getCollectionLabels');
        Route::get('directcollections/{collection}', 'show')->name('directcollections.show');
    });

    // Programmed Collections
    Route::controller(ProgrammedCollectionController::class)->group(function () {
        Route::get('programmedcollections', 'index')->name('programmedcollections.index');
        Route::get('programmedcollections/create', 'create')->name('programmedcollections.create');
        Route::post('programmedcollections', 'store')->name('programmedcollections.store');
        Route::get('programmedcollections/{collection}/edit', 'edit')->name('programmedcollections.edit');
        Route::put('programmedcollections/{collection}', 'update')->name('programmedcollections.update');
        Route::get('programmedcollections/destroy', 'destroy')->name('programmedcollections.destroy');
        Route::get('programmedcollections/restore', 'restore')->name('programmedcollections.restore');
        Route::get('programmedcollections/getParametersToAnalyzePDF', 'getParametersToAnalyzePDF')->name('programmedcollections.getParametersToAnalyzePDF');
        Route::get('programmedcollections/getMultipleParametersToAnalyzePDF', 'getMultipleParametersToAnalyzePDF')->name('programmedcollections.getMultipleParametersToAnalyzePDF');
        Route::get('programmedcollections/exportParametersToAnalyzeSheet', 'exportParametersToAnalyzeSheet')->name('programmedcollections.exportParametersToAnalyzeSheet');
        Route::get('programmedcollections/getCollectionTermPDF', 'getCollectionTermPDF')->name('programmedcollections.getCollectionTermPDF');
        Route::get('programmedcollections/getCollectionLabels', 'getCollectionLabels')->name('programmedcollections.getCollectionLabels');
        Route::get('programmedcollections/PlaceProductsInAnalysis', 'PlaceProductsInAnalysis')->name('programmedcollections.PlaceProductsInAnalysis');
        Route::get('programmedcollections/{collection}', 'show')->name('programmedcollections.show');
    });

    // Users
    Route::controller(UserController::class)->group(function () {
        Route::get('users', 'index')->name('users.index');
        Route::get('users/create', 'create')->name('users.create');
        Route::post('users', 'store')->name('users.store');
        Route::post('users/signature', 'setSignature')->name('users.setsignature');
        Route::post('users/dashboard-header', 'setDashboardHeader')->name('users.setDashboardHeader');
        Route::get('users/unsetsignature', 'unsetSignature')->name('users.unsetsignature');
        Route::get('users/{user}/edit', 'edit')->name('users.edit');
        Route::put('users/{user}', 'update')->name('users.update');
        Route::put('users/{user}/password', 'setpass')->name('users.setpass');
        Route::get('users/destroy', 'destroy')->name('users.destroy');
        Route::get('users/restore', 'restore')->name('users.restore');
        Route::get('users/profile', 'profile')->name('users.profile');
        Route::get('users/help', 'help')->name('users.help');
        Route::get('users/manual.pdf', 'manualPdf')->name('users.manual.pdf');
        Route::get('users/activestatus/{id}', 'toggleActiveStatus')->name('users.toggleActiveStatus');
        Route::get('users/security', 'security')->name('users.security');
        Route::get('users/impersonate', 'impersonate')->name('users.impersonate');
        Route::get('users/stop-impersonating', 'leave')->name('users.stopimpersonating');
        Route::get('users/getUser', 'getUser')->name('users.getUser');
    });

    // System Backup
    // Route::controller(SystemBackupController::class)->group(function() {
    //     Route::get('system-backups', 'index')->name('systembackups.index');
    // });

    Route::controller(SystemBackupController::class)->group(function () {
        Route::get('system-backups/backups', 'backups')->name('systembackups.backups');
        Route::get('system-backups', 'index')->name('systembackups.index');
        Route::post('system-backups', 'create')->name('systembackups.create');
        Route::delete('system-backups', 'delete')->name('systembackups.destroy');
    });

    Route::get('download-backup', DownloadBackupController::class)->name('systembackups.download');
    Route::get('backup-statuses', [BackupStatusesController::class, 'index'])->name('systembackups.statuses');
    Route::post('clean-backups', CleanBackupsController::class)->name('systembackups.clean');

    // Warehouses
    Route::controller(WarehouseController::class)->group(function () {
        Route::get('warehouses', 'index')->name('warehouses.index');
        Route::get('warehouses/create', 'create')->name('warehouses.create');
        Route::post('warehouses', 'store')->name('warehouses.store');
        Route::get('warehouses/{warehouse}/edit', 'edit')->name('warehouses.edit');
        Route::put('warehouses/{warehouse}', 'update')->name('warehouses.update');
        Route::get('warehouses/{warehouse}/show', 'show')->name('warehouses.show');
        Route::put('warehouses/{warehouse}/password', 'setpass')->name('warehouses.setpass');
        Route::post('warehouses/{warehouse}/password-reset', 'sendPasswordReset')->name('warehouses.send-password-reset');
        Route::get('warehouses/destroy', 'destroy')->name('warehouses.destroy');
        Route::get('warehouses/restore', 'restore')->name('warehouses.restore');
        Route::get('warehouses/getWarehouse', 'getWarehouse')->name('warehouses.getWarehouse');
    });

    // Lab Codes
    Route::controller(LabCodeController::class)->group(function () {
        Route::get('labcodes/getCode', 'getCode')->name('labcodes.getCode');
        Route::get('labcodes/getCodeParameters', 'getCodeParameters')->name('labcodes.getCodeParameters');
        Route::get('labcodes/getCodeProducts', 'getCodeProducts')->name('labcodes.getCodeProducts');
        Route::get('labcodes/getWarehouseUninvoicedProducts', 'getWarehouseUninvoicedProducts')->name('labcodes.getWarehouseUninvoicedProducts');
        Route::get('labcodes/getSampleStatus', 'getSampleStatus')->name('labcodes.getSampleStatus');
    });

    // Proposals
    Route::controller(ProposalController::class)->group(function () {
        Route::get('proposals', 'index')->name('proposals.index');
        Route::get('proposals/create', 'create')->name('proposals.create');
        Route::post('proposals', 'store')->name('proposals.store');
        Route::get('proposals/{proposal}/edit', 'edit')->name('proposals.edit');
        Route::get('proposals/{proposal}/accept', 'accept')->name('proposals.accept');
        Route::get('proposals/{proposal}/reject', 'reject')->name('proposals.reject');
        Route::put('proposals/{proposal}', 'update')->name('proposals.update');
        Route::get('proposals/{proposal}/show', 'show')->name('proposals.show');
        Route::get('proposals/destroy', 'destroy')->name('proposals.destroy');
        Route::get('proposals/restore', 'restore')->name('proposals.restore');
        Route::get('proposals/getProposal', 'getProposal')->name('proposals.getProposal');
        Route::get('proposals/getPDF', 'getPDF')->name('proposals.getPDF');
    });

    // Proposal Templates
    Route::controller(ProposalTemplateController::class)->group(function () {
        Route::get('proposaltemplates', 'index')->name('proposaltemplates.index');
        Route::get('proposaltemplates/create', 'create')->name('proposaltemplates.create');
        Route::post('proposaltemplates', 'store')->name('proposaltemplates.store');
        Route::get('proposaltemplates/{template}/edit', 'edit')->name('proposaltemplates.edit');
        Route::put('proposaltemplates/{template}', 'update')->name('proposaltemplates.update');
        Route::get('proposaltemplates/destroy', 'destroy')->name('proposaltemplates.destroy');
        Route::get('proposaltemplates/restore', 'restore')->name('proposaltemplates.restore');
        Route::get('proposaltemplates/getProposalTemplate', 'getProposalTemplate')->name('proposaltemplates.getProposalTemplate');
    });

    // Proposal Compliance Agreements
    Route::controller(ProposalComplianceAgreementController::class)->group(function () {
        Route::get('proposalcomplianceagreements', 'index')->name('proposalcomplianceagreements.index');
        Route::get('proposalcomplianceagreements/create', 'create')->name('proposalcomplianceagreements.create');
        Route::post('proposalcomplianceagreements', 'store')->name('proposalcomplianceagreements.store');
        Route::get('proposalcomplianceagreements/{agreement}/edit', 'edit')->name('proposalcomplianceagreements.edit');
        Route::put('proposalcomplianceagreements/{agreement}', 'update')->name('proposalcomplianceagreements.update');
        Route::get('proposalcomplianceagreements/destroy', 'destroy')->name('proposalcomplianceagreements.destroy');
        Route::get('proposalcomplianceagreements/restore', 'restore')->name('proposalcomplianceagreements.restore');
        Route::get('proposalcomplianceagreements/getProposalComplianceAgreement', 'getProposalComplianceAgreement')->name('proposalcomplianceagreements.getProposalComplianceAgreement');
    });

    // Results
    Route::controller(ResultController::class)->group(function () {
        Route::get('results/getDefaultResultsData', 'getDefaultResultsData')->name('results.getDefaultResultsData');
        Route::get('results/getCounterAnalysisDefaultResultsData', 'getCounterAnalysisDefaultResultsData')->name('results.getCounterAnalysisDefaultResultsData');
        Route::post('results', 'store')->name('results.store');
        Route::post('results/individual', 'storeIndividual')->name('results.store.individual');
        Route::post('results/storeCounterAnalysisResults', 'storeCounterAnalysisResults')->name('results.storeCounterAnalysisResults');

    });

    // Quality Certificates
    Route::controller(QualityCertificateController::class)->group(function () {
        Route::get('qualitycertificates', 'index')->name('qualitycertificates.index');
        Route::get('qualitycertificates/create', 'create')->name('qualitycertificates.create');
        Route::post('qualitycertificates', 'store')->name('qualitycertificates.store');
        Route::get('qualitycertificates/{certificate}/edit', 'edit')->name('qualitycertificates.edit');
        Route::put('qualitycertificates/{certificate}', 'update')->name('qualitycertificates.update');
        Route::get('qualitycertificates/{certificate}/show', 'show')->name('qualitycertificates.show');
        Route::get('qualitycertificates/destroy', 'destroy')->name('qualitycertificates.destroy');
        Route::get('qualitycertificates/restore', 'restore')->name('qualitycertificates.restore');
        Route::get('qualitycertificates/getQualityCertificate', 'getQualityCertificate')->name('qualitycertificates.getQualityCertificate');
        Route::get('qualitycertificates/getPDF', 'getPDF')->name('qualitycertificates.getPDF');

        Route::get('qualitycertificates/{certificate}/approve', 'getApprove')->name('qualitycertificates.getApprove');
        Route::post('qualitycertificates/{certificate}/approve', 'approve')->name('qualitycertificates.approve');

    });

    // ISO 17025 Revision Management
    Route::prefix('qualitycertificates/{certificate}')->group(function () {
        // Main revision management routes
        Route::get('iso-revisions', [ISORevisionController::class, 'index'])->name('qualitycertificates.iso-revisions.index');
        Route::post('iso-revisions', [ISORevisionController::class, 'store'])->name('qualitycertificates.iso-revisions.store');
        Route::get('iso-revisions/create', [ISORevisionController::class, 'create'])->name('qualitycertificates.iso-revisions.create');

        // Register static ISO 17025 routes before dynamic revision segments.
        Route::get('iso-revisions/compare', [ISORevisionController::class, 'compare'])->name('qualitycertificates.iso-revisions.compare'); // For query parameters
        Route::get('iso-revisions/audit-trail', [ISORevisionController::class, 'auditTrail'])->name('qualitycertificates.iso-revisions.audit-trail');
        Route::get('iso-revisions/export', [ISORevisionController::class, 'export'])->name('qualitycertificates.iso-revisions.export');
        Route::get('iso-revisions/approvers', [ISORevisionController::class, 'approvers'])->name('qualitycertificates.iso-revisions.approvers');

        // Route::get('iso-revisions/{revision_a}/{revision_b}/compare', [ISORevisionController::class, 'compare'])->name('qualitycertificates.iso-revisions.compare');
        Route::get('iso-revisions/{revision_a}/compare-with/{revision_b}', [ISORevisionController::class, 'compareTwo'])->name('qualitycertificates.iso-revisions.compare-two');

        Route::get('iso-revisions/{revision}', [ISORevisionController::class, 'show'])->name('qualitycertificates.iso-revisions.show');
        Route::post('iso-revisions/{revision}/restore', [ISORevisionController::class, 'restore'])->name('qualitycertificates.iso-revisions.restore');
        // Export comparison
        Route::get('iso-revisions/{revision_a}/export-comparison/{revision_b}', [ISORevisionController::class, 'exportComparison'])->name('iso-revisions.export-comparison');

        Route::get('iso-revisions/{revision}/snapshot', [ISORevisionController::class, 'snapshot'])->name('qualitycertificates.iso-revisions.snapshot');
    });

    // Additional revision-specific routes
    Route::prefix('qualitycertificates/{certificate}/revisions')->group(function () {
        Route::get('{revision}/compare', [ISORevisionController::class, 'compare'])->name('qualitycertificates.revisions.compare');
        Route::post('{revision}/restore', [ISORevisionController::class, 'restore'])->name('qualitycertificates.revisions.restore');
        Route::get('audit-trail', [ISORevisionController::class, 'auditTrail'])->name('qualitycertificates.revisions.audit-trail');
        Route::get('export', [ISORevisionController::class, 'export'])->name('qualitycertificates.revisions.export');
    });

    // Invoices
    Route::controller(InvoiceController::class)->group(function () {
        Route::get('invoices', 'index')->name('invoices.index');
        Route::get('invoices/create', 'create')->name('invoices.create');
        Route::post('invoices', 'store')->name('invoices.store');
        Route::get('invoices/{invoice}/edit', 'edit')->name('invoices.edit');
        Route::put('invoices/{invoice}', 'update')->name('invoices.update');
        Route::get('invoices/{invoice}/show', 'show')->name('invoices.show');
        Route::get('invoices/destroy', 'destroy')->name('invoices.destroy');
        Route::get('invoices/restore', 'restore')->name('invoices.restore');
        Route::get('invoices/getInvoice', 'getInvoice')->name('invoices.getInvoice');
        Route::get('invoices/changeStatusToPaid', 'changeStatusToPaid')->name('invoices.changeStatusToPaid');
        Route::get('invoices/getPDF', 'getPDF')->name('invoices.getPDF');
    });

    // Credit Notes
    Route::controller(CreditNoteController::class)->group(function () {
        Route::get('creditnotes', 'index')->name('creditnotes.index');
        Route::get('creditnotes/create', 'create')->name('creditnotes.create');
        Route::post('creditnotes', 'store')->name('creditnotes.store');
        Route::get('creditnotes/{note}/edit', 'edit')->name('creditnotes.edit');
        Route::put('creditnotes/{note}', 'update')->name('creditnotes.update');
        Route::get('creditnotes/destroy', 'destroy')->name('creditnotes.destroy');
        Route::get('creditnotes/restore', 'restore')->name('creditnotes.restore');
        Route::get('creditnotes/getCreditNote', 'getCreditNote')->name('creditnotes.getCreditNote');
        Route::get('creditnotes/getInvoiceData', 'getInvoiceData')->name('creditnotes.getInvoiceData');
        Route::get('creditnotes/getPDF', 'getPDF')->name('creditnotes.getPDF');
    });

    // Invoice Items
    Route::controller(InvoiceItemController::class)->group(function () {
        Route::put('invoiceitems/{item}', 'update')->name('invoiceitems.update');
    });

    // Contract Guides
    Route::controller(ContractGuideController::class)->group(function () {
        Route::get('contractguides', 'index')->name('contractguides.index');
        Route::get('contractguides/create', 'create')->name('contractguides.create');
        Route::post('contractguides', 'store')->name('contractguides.store');
        Route::get('contractguides/{guide}/edit', 'edit')->name('contractguides.edit');
        Route::put('contractguides/{guide}', 'update')->name('contractguides.update');
        Route::get('contractguides/destroy', 'destroy')->name('contractguides.destroy');
        Route::get('contractguides/restore', 'restore')->name('contractguides.restore');
        Route::get('contractguides/getContractGuide', 'getContractGuide')->name('contractguides.getContractGuide');
        Route::get('contractguides/getPDF', 'getPDF')->name('contractguides.getPDF');
    });

    // Contract Guide Items
    Route::controller(ContractGuideItemController::class)->group(function () {
        Route::put('contractguideitems/{item}', 'update')->name('contractguideitems.update');
    });

    // Quotes
    Route::controller(QuoteController::class)->group(function () {
        Route::get('quotes', 'index')->name('quotes.index');
        Route::get('quotes/create', 'create')->name('quotes.create');
        Route::post('quotes', 'store')->name('quotes.store');
        Route::get('quotes/getConvertToInvoiceModal', 'getConvertToInvoiceModal')->name('quotes.getConvertToInvoiceModal');
        Route::post('quotes/convertToInvoice', 'convertToInvoice')->name('quotes.convertToInvoice');
        Route::get('quotes/{quote}/edit', 'edit')->name('quotes.edit');
        Route::put('quotes/{quote}', 'update')->name('quotes.update');
        Route::get('quotes/{quote}/show', 'show')->name('quotes.show');
        Route::get('quotes/destroy', 'destroy')->name('quotes.destroy');
        Route::get('quotes/restore', 'restore')->name('quotes.restore');
        Route::get('quotes/getQuote', 'getQuote')->name('quotes.getQuote');
        Route::get('quotes/getPDF', 'getPDF')->name('quotes.getPDF');
    });

    // Quote Items
    Route::controller(QuoteItemController::class)->group(function () {
        Route::put('quoteitems/{item}', 'update')->name('quoteitems.update');
    });

    // Receipts
    Route::controller(ReceiptController::class)->group(function () {
        Route::get('receipts', 'index')->name('receipts.index');
        Route::get('receipts/create', 'create')->name('receipts.create');
        Route::post('receipts', 'store')->name('receipts.store');
        Route::get('receipts/{receipt}/edit', 'edit')->name('receipts.edit');
        Route::put('receipts/{receipt}', 'update')->name('receipts.update');
        Route::get('receipts/destroy', 'destroy')->name('receipts.destroy');
        Route::get('receipts/restore', 'restore')->name('receipts.restore');
        Route::get('receipts/getReceipt', 'getReceipt')->name('receipts.getReceipt');
        Route::get('receipts/getPDF', 'getPDF')->name('receipts.getPDF');
    });

    // Permissions
    Route::controller(PermissionController::class)->group(function () {
        Route::get('permissions', 'index')->name('permissions.index');
        Route::get('permissions/create', 'create')->name('permissions.create');
        Route::post('permissions', 'store')->name('permissions.store');
        Route::get('permissions/{permission}/edit', 'edit')->name('permissions.edit');
        Route::put('permissions/{permission}', 'update')->name('permissions.update');
        Route::get('permissions/destroy', 'destroy')->name('permissions.destroy');
        Route::get('permissions/restore', 'restore')->name('permissions.restore');
        Route::get('permissions/getPermission', 'getPermission')->name('permissions.getPermission');
    });

    // Roles
    Route::controller(RoleController::class)->group(function () {
        Route::get('roles', 'index')->name('roles.index');
        Route::get('roles/create', 'create')->name('roles.create');
        Route::post('roles', 'store')->name('roles.store');
        Route::get('roles/{role}/edit', 'edit')->name('roles.edit');
        Route::put('roles/{role}', 'update')->name('roles.update');
        Route::get('roles/destroy', 'destroy')->name('roles.destroy');
        Route::get('roles/restore', 'restore')->name('roles.restore');
        Route::get('roles/getRole', 'getRole')->name('roles.getRole');
    });

    // System Activity
    // Route::controller(SystemActivityController::class)->group(function () {
    //     Route::get('system-activity', 'index')->name('systemactivity.index');

    //     Route::get('/stats', 'stats')->name('systemactivity.stats');
    //     Route::get('/cleanup-recommendations', 'cleanupRecommendations')->name('systemactivity.cleanup.recommendations');
    //     Route::get('/stream', 'stream')->name('systemactivity.stream');
    //     Route::get('/export', 'export')->name('systemactivity.export');
    //     Route::delete('/{activity}', 'destroy')->name('systemactivity.destroy');
    //     Route::delete('/', 'destroyAll')->name('systemactivity.destroyAll');
    //     Route::post('/archive', 'archive')->name('systemactivity.archive');
    //     Route::post('/restore-archive', 'restoreArchive')->name('systemactivity.restore.archive');
    // });

    // // API Routes for Vue components
    // Route::middleware(['auth', 'verified'])->prefix('api')->name('api.')->group(function () {
    //     Route::get('/activity-logs', [SystemActivityController::class, 'systemactivity.index']);
    //     Route::get('/activity-logs/{activity}', [SystemActivityController::class, 'systemactivity.show']);
    //     Route::get('/activity-logs/stats/summary', [SystemActivityController::class, 'systemactivity.stats']);
    // });

    // System Activity - FIXED VERSION
    Route::controller(SystemActivityController::class)->group(function () {
        Route::get('system-activity', 'index')->name('systemactivity.index');
        Route::get('system-activity/export', 'export')->name('systemactivity.export');

        Route::get('system-activity/{activity}', 'show')->name('systemactivity.show'); // ADDED

        Route::get('system-activity/stats', 'stats')->name('systemactivity.stats');
        Route::get('system-activity/cleanup-recommendations', 'cleanupRecommendations')->name('systemactivity.cleanup.recommendations');
        Route::get('system-activity/stream', 'stream')->name('systemactivity.stream');
        Route::delete('system-activity/{activity}', 'destroy')->name('systemactivity.destroy');
        Route::delete('system-activity/', 'destroyAll')->name('systemactivity.destroyAll');
        Route::post('system-activity/archive', 'archive')->name('systemactivity.archive');
        Route::post('system-activity/restore-archive', 'restoreArchive')->name('systemactivity.restore.archive');
    });

    // API Routes for Vue components - FIXED VERSION
    Route::middleware(['auth', 'verified'])->prefix('api')->name('api.')->group(function () {
        Route::get('/activity-logs', [SystemActivityController::class, 'index']); // FIXED
        Route::get('/activity-logs/{activity}', [SystemActivityController::class, 'show']); // FIXED
        Route::get('/activity-logs/stats/summary', [SystemActivityController::class, 'stats']); // FIXED
    });

    // System General Settings
    Route::controller(SystemGeneralSettingsController::class)->group(function () {
        Route::get('general-settings', 'index')->name('generalsettings.index');
        Route::post('general-settings', 'update')->name('generalsettings.update');
    });

    // Board / Tasks
    Route::controller(BoardController::class)->group(function () {
        Route::get('/boards', 'index')->name('boards');
        Route::get('boards/destroy', 'destroy')->name('boards.destroy');
        Route::get('/boards/{board}/{card?}', 'show')->name('boards.show');
        Route::put('/boards/{board}', 'update')->name('boards.update');
        Route::post('/boards', 'store')->name('boards.store');
    });

    Route::controller(CardListController::class)->group(function () {
        Route::post('/boards/{board}/lists', 'store')->name('cardLists.store');
        Route::delete('/boards/{board}/lists/{list}', 'destroy')->name('cardLists.destroy');
    });

    Route::controller(CardController::class)->group(function () {
        Route::post('/cards', [CardController::class, 'store'])->name('cards.store');
        Route::put('/cards/{card}', [CardController::class, 'update'])->name('cards.update');
        Route::put('/cards/{card}/move', [CardController::class, 'move'])->name('cards.move');
        Route::delete('/cards/{card}', [CardController::class, 'destroy'])->name('cards.destroy');
    });

    Route::controller(MediaController::class)->group(function () {
        Route::get('/media', 'index')->name('media.index');
        Route::get('/media/create', 'create')->name('media.create');
        Route::post('/media', 'store')->name('media.store');
        Route::get('/media/{media}/edit', 'edit')->name('media.edit');
        Route::put('/media/{media}', 'update')->name('media.update');
        Route::get('/media/destroy', 'destroy')->name('media.destroy');
        Route::get('/media/restore', 'restore')->name('media.restore');
    });

    // VAP Inventory Routes

    // Mobile API Routes
    Route::get('/api/inventory/batch-lookup/{id}', [InventoryBatchController::class, 'lookup'])->name('inventory.batches.lookup');
    Route::post('/api/inventory/mobile-action', [InventoryBatchController::class, 'handleMobileAction'])->name('inventory.batches.mobile-action');
    Route::post('/api/inventory/audit', [InventoryBatchController::class, 'performAudit'])->name('inventory.batches.audit');

    // Label Printing Route
    Route::get('/reports/labels/batches', [InventoryBatchController::class, 'printBatchLabels'])->name('printBatchLabels');

    // VAP Proposals
    Route::prefix('vap-proposals')->name('vap-proposals.')->group(function () {

        Route::get('/', [VAPProposalController::class, 'index'])->name('index');
        Route::get('/create', [VAPProposalController::class, 'create'])->name('create');
        Route::post('/', [VAPProposalController::class, 'store'])->name('store');
        Route::prefix('options')->name('options.')->group(function () {
            Route::get('/proposals', [VAPProposalController::class, 'getProposal'])->name('proposals');
            Route::get('/warehouses', [VAPProposalController::class, 'getWarehouse'])->name('warehouses');
            Route::get('/matrixes', [VAPProposalController::class, 'getMatrix'])->name('matrixes');
            Route::get('/parameters', [VAPProposalController::class, 'getParameter'])->name('parameters');
            Route::get('/lab-codes', [VAPProposalController::class, 'getLabCode'])->name('lab-codes');
            Route::get('/lab-code-parameters', [VAPProposalController::class, 'getLabCodeParameters'])->name('lab-code-parameters');
        });

        // Proposal Templates
        Route::get('/templates', [VAPProposalTemplateController::class, 'index'])->name('templates.index');
        Route::get('/templates/create', [VAPProposalTemplateController::class, 'create'])->name('templates.create');
        Route::post('/templates', [VAPProposalTemplateController::class, 'store'])->name('templates.store');
        Route::post('/templates/preview-pdf', [VAPProposalTemplateController::class, 'previewDraftPdf'])->name('templates.preview-draft-pdf');
        Route::post('/templates/import', [VAPProposalTemplateController::class, 'import'])->name('templates.import');
        Route::get('/templates/export', [VAPProposalTemplateController::class, 'export'])->name('templates.export');
        Route::get('/templates/{proposalTemplate}', [VAPProposalTemplateController::class, 'show'])->name('templates.show');
        Route::get('/templates/{proposalTemplate}/edit', [VAPProposalTemplateController::class, 'edit'])->name('templates.edit');
        Route::put('/templates/{proposalTemplate}', [VAPProposalTemplateController::class, 'update'])->name('templates.update');
        Route::delete('/templates/{proposalTemplate}', [VAPProposalTemplateController::class, 'destroy'])->name('templates.destroy');
        Route::get('/templates/{proposalTemplate}/pdf', [VAPProposalTemplateController::class, 'exportPdf'])->name('templates.pdf');
        // Ativar/Desativar template
        Route::put('/templates/{proposalTemplate}/toggle-status', [VAPProposalTemplateController::class, 'toggleStatus'])->name('templates.toggle-status');

        Route::get('/{proposal}', [VAPProposalController::class, 'show'])->name('show');
        Route::get('/{proposal}/edit', [VAPProposalController::class, 'edit'])->name('edit');
        Route::put('/{proposal}', [VAPProposalController::class, 'update'])->name('update');
        Route::delete('/{proposal}', [VAPProposalController::class, 'destroy'])->name('destroy');

        // Proposal Actions
        Route::post('/{proposal}/send', [VAPProposalController::class, 'send'])->name('send');
        Route::get('/{proposal}/download/pdf', [VAPProposalController::class, 'generatePdf'])->name('download.pdf');

    });

    Route::prefix('vap-inventory')->name('vap-inventory.')->group(function () {

        // Items Management
        Route::prefix('items')->name('items.')->group(function () {

            // Export
            Route::get('/export', [VAPInventoryItemController::class, 'exportInventory'])->name('export.inventory');

            Route::get('/', [VAPInventoryItemController::class, 'index'])->name('index');
            Route::get('/create', [VAPInventoryItemController::class, 'create'])->name('create');
            Route::post('/', [VAPInventoryItemController::class, 'store'])->name('store');
            Route::get('/{item}', [VAPInventoryItemController::class, 'show'])->name('show');
            Route::get('/{item}/edit', [VAPInventoryItemController::class, 'edit'])->name('edit');
            Route::put('/{item}', [VAPInventoryItemController::class, 'update'])->name('update');
            Route::delete('/{item}', [VAPInventoryItemController::class, 'destroy'])->name('destroy');

            // Stock Adjustment
            Route::post('/{item}/adjust-stock', [VAPInventoryItemController::class, 'adjustStock'])->name('adjust-stock');

            // Calibration Schedule
            Route::get('/calibration/schedule', [VAPInventoryItemController::class, 'calibrationSchedule'])->name('calibration.schedule');

            // Reagent Consumption
            Route::post('/reagents/{item}/consume', [VAPInventoryItemController::class, 'consume'])->name('reagents.consume');

            // Reagent Expiry
            Route::get('/reagents/expiry', [VAPInventoryItemController::class, 'reagentExpiryReport'])->name('reagents.expiry');

            // Attachments
            Route::get('/attachments/download-all', [VAPInventoryItemController::class, 'downloadallattachments'])->name('attachments.download-all');
            Route::get('/attachments/download-single', [VAPInventoryItemController::class, 'downloadsingleattachment'])->name('attachments.download-single');
            Route::delete('/attachments/delete/{id}', [VAPInventoryItemController::class, 'deleteattachment'])->name('attachments.delete');

        });

        // Orders Management
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [VAPInventoryOrderController::class, 'index'])->name('index');
            Route::get('/create', [VAPInventoryOrderController::class, 'create'])->name('create');
            Route::post('/', [VAPInventoryOrderController::class, 'store'])->name('store');
            Route::get('/{order}', [VAPInventoryOrderController::class, 'show'])->name('show');
            Route::get('/{order}/edit', [VAPInventoryOrderController::class, 'edit'])->name('edit');
            Route::put('/{order}', [VAPInventoryOrderController::class, 'update'])->name('update');
            Route::delete('/{order}', [VAPInventoryOrderController::class, 'destroy'])->name('destroy');

            // Order Status Updates
            Route::post('/{order}/receive', [VAPInventoryOrderController::class, 'receive'])->name('receive');
            Route::post('/{order}/cancel', [VAPInventoryOrderController::class, 'cancel'])->name('cancel');

            Route::get('/{order}/export-pdf', [VAPInventoryOrderController::class, 'exportPdf'])->name('export-pdf');
        });

        Route::prefix('needs')->name('needs.')->group(function () {
            Route::get('/', [VAPInventoryNeedController::class, 'index'])->name('index');
            Route::get('/create', [VAPInventoryNeedController::class, 'create'])->name('create');
            Route::post('/', [VAPInventoryNeedController::class, 'store'])->name('store');
            Route::get('/{need}', [VAPInventoryNeedController::class, 'show'])->name('show');
            Route::get('/{need}/pdf', [VAPInventoryNeedController::class, 'exportPdf'])->name('pdf');
            Route::post('/{need}/approve', [VAPInventoryNeedController::class, 'approve'])->name('approve');
            Route::post('/{need}/reject', [VAPInventoryNeedController::class, 'reject'])->name('reject');
            Route::post('/{need}/convert-to-order', [VAPInventoryNeedController::class, 'convertToOrder'])->name('convert-to-order');
        });

        // Reagent Consumption Routes
        Route::prefix('reagents')->name('reagents.')->group(function () {
            Route::get('/consumption', [VAPInventoryItemController::class, 'reagentConsumption'])->name('consumption.index');
            Route::get('/consumption/create', [VAPInventoryItemController::class, 'createConsumption'])->name('consumption.create');
            Route::post('/consumption', [VAPInventoryItemController::class, 'storeConsumption'])->name('consumption.store');
            Route::get('/consumption/{consumption}', [VAPInventoryItemController::class, 'showConsumption'])->name('consumption.show');
            Route::delete('/consumption/{consumption}', [VAPInventoryItemController::class, 'destroyConsumption'])->name('consumption.destroy');
            Route::post('/{item}/consume', [VAPInventoryItemController::class, 'consume'])->name('consume');
        });

        // Transfers Management
        Route::prefix('transfers')->name('transfers.')->group(function () {
            Route::get('/', [VAPInventoryTransferController::class, 'index'])->name('index');
            Route::get('/create', [VAPInventoryTransferController::class, 'create'])->name('create');
            Route::post('/', [VAPInventoryTransferController::class, 'store'])->name('store');
            Route::get('/item-stock', [VAPInventoryTransferController::class, 'getItemStock'])->name('item-stock');
            Route::get('/all-stock-info', [VAPInventoryTransferController::class, 'getAllStockInfo'])->name('all-stock-info');
            Route::get('/item-stock-all', [VAPInventoryTransferController::class, 'getItemStockAllWarehouses'])->name('item-stock-all');

            Route::get('/{transfer}', [VAPInventoryTransferController::class, 'show'])->name('show');

            // Transfer Status Updates
            Route::post('/{transfer}/receive', [VAPInventoryTransferController::class, 'receive'])->name('receive');
            Route::post('/{transfer}/cancel', [VAPInventoryTransferController::class, 'cancel'])->name('cancel');

            Route::post('/{transfer}/receive', [VAPInventoryTransferController::class, 'receive'])->name('receive');
            Route::post('/{transfer}/cancel', [VAPInventoryTransferController::class, 'cancel'])->name('cancel');
            Route::post('/bulk', [VAPInventoryTransferController::class, 'bulkTransfer'])->name('bulk');
        });

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/low-stock', [VAPInventoryItemController::class, 'lowStockReport'])->name('low-stock');
            Route::get('/stock-movement', [VAPInventoryReportController::class, 'stockMovement'])->name('stock-movement');

            Route::get('/consumption', [VAPInventoryReportController::class, 'consumptionReport'])->name('consumption');
            Route::get('/inventory-value', [VAPInventoryReportController::class, 'inventoryValue'])->name('inventory-value');
            Route::post('/export', [VAPInventoryReportController::class, 'exportReport'])->name('export');
            Route::get('/dashboard-stats', [VAPInventoryReportController::class, 'dashboardStats'])->name('dashboard-stats');
        });

        // Master Data Management
        Route::prefix('master')->name('master.')->group(function () {
            // Categories
            Route::get('/categories', [ItemCategoryController::class, 'index'])->name('categories.index');
            Route::post('/categories', [ItemCategoryController::class, 'store'])->name('categories.store');
            Route::put('/categories/{category}', [ItemCategoryController::class, 'update'])->name('categories.update');
            Route::delete('/categories/{category}', [ItemCategoryController::class, 'destroy'])->name('categories.destroy');

            // Suppliers
            Route::get('/suppliers', [InventoryItemSupplierController::class, 'index'])->name('suppliers.index');
            Route::post('/suppliers', [InventoryItemSupplierController::class, 'store'])->name('suppliers.store');
            Route::put('/suppliers/{supplier}', [InventoryItemSupplierController::class, 'update'])->name('suppliers.update');
            Route::delete('/suppliers/{supplier}', [InventoryItemSupplierController::class, 'destroy'])->name('suppliers.destroy');

            // Warehouses
            Route::get('/warehouses', [InventoryItemWarehouseController::class, 'index'])->name('warehouses.index');
            Route::post('/warehouses', [InventoryItemWarehouseController::class, 'store'])->name('warehouses.store');
            Route::put('/warehouses/{warehouse}', [InventoryItemWarehouseController::class, 'update'])->name('warehouses.update');
            Route::delete('/warehouses/{warehouse}', [InventoryItemWarehouseController::class, 'destroy'])->name('warehouses.destroy');

            // Units
            Route::get('/units', [InventoryUnitController::class, 'index'])->name('units.index');
            Route::post('/units', [InventoryUnitController::class, 'store'])->name('units.store');
            Route::put('/units/{unit}', [InventoryUnitController::class, 'update'])->name('units.update');
            Route::delete('/units/{unit}', [InventoryUnitController::class, 'destroy'])->name('units.destroy');
        });

        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/', [VAPInventoryAnalyticsController::class, 'index'])->name('index');
            Route::get('/data', [VAPInventoryAnalyticsController::class, 'getAnalyticsData'])->name('data');
            Route::get('/realtime', [VAPInventoryAnalyticsController::class, 'getRealtimeData'])->name('realtime');
            Route::post('/export', [VAPInventoryAnalyticsController::class, 'exportChart'])->name('export');
            Route::post('/report', [VAPInventoryAnalyticsController::class, 'generateReport'])->name('report');
            Route::post('/restock', [VAPInventoryAnalyticsController::class, 'createProcurementDraft'])->name('restock');
            Route::get('/summary', [VAPInventoryAnalyticsController::class, 'getDashboardSummary'])->name('summary');
        });

    });

    // Route::prefix('vap-labs')->name('vap-labs.')->group(function () {

    //     // Labs Resource Routes
    //     Route::resource('labs', VAPLabController::class);

    //     // Additional Lab Routes
    //     Route::post('/labs/{lab}/restore', [VAPLabController::class, 'restore'])
    //         ->name('labs.restore')
    //         ->withTrashed();

    //     Route::get('/lab-options', [VAPLabController::class, 'getLabOptions'])
    //         ->name('labs.options');

    //     // Alternative for specific permissions
    //     // Route::group(function () {
    //         Route::get('/labs', [VAPLabController::class, 'index'])->name('labs.index');
    //         Route::get('/labs/create', [VAPLabController::class, 'create'])->name('labs.create');
    //     // });

    //     // Route::group(function () {
    //         Route::post('/labs', [VAPLabController::class, 'store'])->name('labs.store');
    //     // });

    //     // Route::group(function () {
    //         Route::get('/labs/{lab}', [VAPLabController::class, 'show'])->name('labs.show');
    //     // });

    //     // Route::group(function () {
    //         Route::get('/labs/{lab}/edit', [VAPLabController::class, 'edit'])->name('labs.edit');
    //         Route::put('/labs/{lab}', [VAPLabController::class, 'update'])->name('labs.update');
    //     // });

    //     // Route::group(function () {
    //         Route::delete('/labs/{lab}', [VAPLabController::class, 'destroy'])->name('labs.destroy');
    //     // });

    // });

    Route::prefix('vap-labs')->name('vap-labs.')->group(function () {

        // Labs Resource Routes - this alone creates all necessary routes
        Route::resource('labs', VAPLabController::class);

        // Additional Lab Routes (only add custom ones not covered by resource)
        Route::post('/labs/{lab}/restore', [VAPLabController::class, 'restore'])
            ->name('labs.restore')
            ->withTrashed();

        Route::get('/lab-options', [VAPLabController::class, 'getLabOptions'])
            ->name('labs.options');

        // REMOVE ALL THESE DUPLICATE ROUTES - they're already created by Route::resource
        /*
        Route::get('/labs', [VAPLabController::class, 'index'])->name('labs.index');
        Route::get('/labs/create', [VAPLabController::class, 'create'])->name('labs.create');
        Route::post('/labs', [VAPLabController::class, 'store'])->name('labs.store');
        Route::get('/labs/{lab}', [VAPLabController::class, 'show'])->name('labs.show');
        Route::get('/labs/{lab}/edit', [VAPLabController::class, 'edit'])->name('labs.edit');
        Route::put('/labs/{lab}', [VAPLabController::class, 'update'])->name('labs.update');
        Route::delete('/labs/{lab}', [VAPLabController::class, 'destroy'])->name('labs.destroy');
        */

    });

    Route::prefix('vap-labels')->name('vap_labels.')->group(function () {
        Route::post('/label-generation/from-source', [VAPLabelController::class, 'generateFromSource'])->name('label-generation.from-source');
        Route::resource('labels', VAPLabelController::class);
        Route::resource('label-templates', VAPLabelTemplateController::class)->except(['show']);

        // Route::prefix('labels')->group(function () {
        Route::get('/{label}/preview-pdf', [VAPLabelController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/{label}/generate-pdf', [VAPLabelController::class, 'generatePdf'])->name('generate-pdf');
        Route::post('/{label}/generate-batch-pdf', [VAPLabelController::class, 'generateBatchPdf'])->name('generate-batch-pdf');
        Route::post('/{label}/toggle-status', [VAPLabelController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{label}/duplicate', [VAPLabelController::class, 'duplicate'])->name('duplicate');
        Route::post('/{label}/apply-template', [VAPLabelController::class, 'applyTemplate'])->name('apply-template');
        Route::get('/templates/list', [VAPLabelController::class, 'getTemplates'])->name('templates.list');

        Route::prefix('templates')->name('templates.')->group(function () {
            Route::post('/{labelTemplate}/toggle-status', [VAPLabelTemplateController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{labelTemplate}/toggle-featured', [VAPLabelTemplateController::class, 'toggleFeatured'])->name('toggle-featured');
        });
        // });
    });

    Route::prefix('vap-non-conformities')->name('vap_non_conformities.')->group(function () {
        // Non-Conformities
        Route::get('/', [VAPNonConformityController::class, 'index'])->name('index');

        Route::get('/create', [VAPNonConformityController::class, 'create'])->name('create');

        Route::post('/', [VAPNonConformityController::class, 'store'])
            ->name('store');

        Route::get('/{nonConformity}', [VAPNonConformityController::class, 'show'])
            ->name('show');

        Route::get('/{nonConformity}/edit', [VAPNonConformityController::class, 'edit'])
            ->name('edit');

        Route::put('/{nonConformity}', [VAPNonConformityController::class, 'update'])
            ->name('update');

        Route::delete('/{nonConformity}', [VAPNonConformityController::class, 'destroy'])
            ->name('destroy');

        Route::get('/export/excel', [VAPNonConformityController::class, 'exportExcel'])->name('export.excel');

        Route::get('/export/pdf', [VAPNonConformityController::class, 'exportPdf'])->name('export.pdf');

        Route::get('/{nonConformity}/export/excel', [VAPNonConformityController::class, 'exportDetailsExcel'])->name('export.details.excel');

        Route::get('/{nonConformity}/export/pdf', [VAPNonConformityController::class, 'exportDetailsPdf'])->name('export.details.pdf');
    });

    Route::prefix('vap-samples')->name('vap_samples.')->group(function () {

        // Main dashboard/interface route
        Route::get('/', [VAPSampleEntryController::class, 'index'])->name('index');

        // Sample Entry Routes
        Route::prefix('samples')->name('samples.')->group(function () {
            Route::post('/', [VAPSampleEntryController::class, 'store'])->name('store');
            Route::post('/bulk', [VAPSampleEntryController::class, 'bulkStore'])->name('bulk-store');
            Route::get('/import-template', [VAPSampleEntryController::class, 'downloadImportTemplate'])->name('import-template');
            Route::post('/import', [VAPSampleEntryController::class, 'import'])->name('import');
            Route::put('/{sampleEntry}', [VAPSampleEntryController::class, 'update'])->name('update');
            Route::patch('/{sampleEntry}/internal-quality-control-decision', [VAPSampleEntryController::class, 'updateInternalQualityControlDecision'])->name('internal-quality-control-decision');
            Route::delete('/{sampleEntry}', [VAPSampleEntryController::class, 'destroy'])->name('destroy');
            Route::get('/{sampleEntry}/pdf', [VAPSampleEntryController::class, 'generatePdf'])->name('pdf');
            Route::get('/stats', [VAPSampleEntryController::class, 'stats'])->name('stats');
            Route::get('/export', [VAPSampleEntryController::class, 'export'])->name('export');
        });

        // Sample Discard Routes
        Route::prefix('discards')->name('discards.')->group(function () {
            Route::post('/', [VAPSampleDiscardController::class, 'store'])->name('store');
            Route::get('/{sampleDiscard}/pdf', [VAPSampleDiscardController::class, 'generatePdf'])->name('pdf');
            Route::get('/recent', [VAPSampleDiscardController::class, 'recent'])->name('recent');
            Route::get('/stats', [VAPSampleDiscardController::class, 'stats'])->name('stats');
            Route::get('/export', [VAPSampleDiscardController::class, 'export'])->name('export');
        });

        // Combined routes
        Route::get('/dashboard', [VAPSampleEntryController::class, 'index'])->name('dashboard');
        Route::get('/reports', [VAPSampleEntryController::class, 'reports'])->name('reports');
        Route::get('/{sampleEntry}', [VAPSampleEntryController::class, 'show'])->name('show');
    });

    // Maintenance and Calibration Routes
    Route::prefix('maintenance')->name('vap-maintenance.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [VAPMaintenanceController::class, 'dashboard'])->name('dashboard');

        // Categories
        Route::get('/categories', [VAPMaintenanceController::class, 'categories'])->name('categories');
        Route::post('/categories', [VAPMaintenanceController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{category}', [VAPMaintenanceController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{category}', [VAPMaintenanceController::class, 'destroyCategory'])->name('categories.destroy');

        // Tasks
        Route::get('/tasks', [VAPMaintenanceController::class, 'tasks'])->name('tasks');
        Route::get('/tasks/create', [VAPMaintenanceController::class, 'createTask'])->name('tasks.create');
        Route::post('/tasks', [VAPMaintenanceController::class, 'storeTask'])->name('tasks.store');
        Route::get('/tasks/{task}', [VAPMaintenanceController::class, 'showTask'])->name('tasks.show');
        Route::put('/tasks/{task}', [VAPMaintenanceController::class, 'updateTask'])->name('tasks.update');
        Route::delete('/tasks/{task}', [VAPMaintenanceController::class, 'destroyTask'])->name('tasks.destroy');

        // Reports
        Route::get('/reports/generate', [VAPMaintenanceController::class, 'generateReport'])->name('report.generate');
        Route::post('/tasks/bulk-update', [VAPMaintenanceController::class, 'bulkUpdate'])->name('tasks.bulk-update');

        // Notifications
        Route::post('/send-notifications', [VAPMaintenanceController::class, 'sendNotifications'])
            ->name('notifications.send');
        Route::post('/tasks/{task}/notify-completion', [VAPMaintenanceController::class, 'notifyCompletion'])
            ->name('tasks.notify-completion');

        // Reports & Exports
        Route::get('/export', [VAPMaintenanceController::class, 'exportTasks'])
            ->name('export');
        Route::get('/stats', [VAPMaintenanceController::class, 'getDashboardStats'])
            ->name('stats');
        Route::get('/equipment/{equipment}/history', [VAPMaintenanceController::class, 'getEquipmentHistory'])
            ->name('equipment.history');
    });

    // Inventory Routes

    // Item Categories
    Route::controller(ItemCategoryController::class)->group(function () {
        Route::get('itemcategories', 'index')->name('itemcategories.index');
        Route::get('itemcategories/create', 'create')->name('itemcategories.create');
        Route::post('itemcategories', 'store')->name('itemcategories.store');
        Route::get('itemcategories/{parent}/edit', 'edit')->name('itemcategories.edit');
        Route::put('itemcategories/{parent}', 'update')->name('itemcategories.update');
        Route::get('itemcategories/destroy', 'destroy')->name('itemcategories.destroy');
        Route::get('itemcategories/restore', 'restore')->name('itemcategories.restore');
        Route::get('itemcategories/getItemCategory', 'getItemCategory')->name('itemcategories.getItemCategory');
    });

    // Equipment Categories
    Route::controller(EquipmentCategoryController::class)->group(function () {
        Route::get('equipmentcategories', 'index')->name('equipmentcategories.index');
        Route::get('equipmentcategories/create', 'create')->name('equipmentcategories.create');
        Route::post('equipmentcategories', 'store')->name('equipmentcategories.store');
        Route::get('equipmentcategories/{parent}/edit', 'edit')->name('equipmentcategories.edit');
        Route::put('equipmentcategories/{parent}', 'update')->name('equipmentcategories.update');
        Route::get('equipmentcategories/destroy', 'destroy')->name('equipmentcategories.destroy');
        Route::get('equipmentcategories/restore', 'restore')->name('equipmentcategories.restore');
        Route::get('equipmentcategories/getEquipmentCategory', 'getEquipmentCategory')->name('equipmentcategories.getEquipmentCategory');
    });

    // Inventory Item Statuses
    Route::controller(ItemStatusController::class)->group(function () {
        Route::get('itemstatuses', 'index')->name('itemstatuses.index');
        Route::get('itemstatuses/create', 'create')->name('itemstatuses.create');
        Route::post('itemstatuses', 'store')->name('itemstatuses.store');
        Route::get('itemstatuses/{status}/edit', 'edit')->name('itemstatuses.edit');
        Route::put('itemstatuses/{status}', 'update')->name('itemstatuses.update');
        Route::get('itemstatuses/destroy', 'destroy')->name('itemstatuses.destroy');
        Route::get('itemstatuses/restore', 'restore')->name('itemstatuses.restore');
        Route::get('itemstatuses/getItemStatus', 'getItemStatus')->name('itemstatuses.getItemStatus');
    });

    // Inventory Units
    Route::controller(InventoryUnitController::class)->group(function () {
        Route::get('iunits', 'index')->name('iunits.index');
        Route::get('iunits/create', 'create')->name('iunits.create');
        Route::post('iunits', 'store')->name('iunits.store');
        Route::get('iunits/{iunit}/edit', 'edit')->name('iunits.edit');
        Route::put('iunits/{iunit}', 'update')->name('iunits.update');
        Route::get('iunits/destroy', 'destroy')->name('iunits.destroy');
        Route::get('iunits/restore', 'restore')->name('iunits.restore');
        Route::get('iunits/getInventoryUnit', 'getInventoryUnit')->name('iunits.getInventoryUnit');
    });

    // Inventory Item Types
    Route::controller(InventoryItemTypeController::class)->group(function () {
        Route::get('itypes', 'index')->name('itypes.index');
        Route::get('itypes/create', 'create')->name('itypes.create');
        Route::post('itypes', 'store')->name('itypes.store');
        Route::get('itypes/{itype}/edit', 'edit')->name('itypes.edit');
        Route::put('itypes/{itype}', 'update')->name('itypes.update');
        Route::get('itypes/destroy', 'destroy')->name('itypes.destroy');
        Route::get('itypes/restore', 'restore')->name('itypes.restore');
        Route::get('itypes/getInventoryItemType', 'getInventoryItemType')->name('itypes.getInventoryItemType');
    });

    // Inventory Items
    Route::controller(InventoryItemController::class)->group(function () {
        Route::get('iitems', 'index')->name('iitems.index');
        Route::get('iitems/create', 'create')->name('iitems.create');
        Route::post('iitems', 'store')->name('iitems.store');
        Route::get('iitems/{iitem}/edit', 'edit')->name('iitems.edit');
        Route::put('iitems/{iitem}', 'update')->name('iitems.update');
        Route::get('iitems/{iitem}/show', 'show')->name('iitems.show');
        Route::get('iitems/destroy', 'destroy')->name('iitems.destroy');
        Route::get('iitems/restore', 'restore')->name('iitems.restore');
        Route::get('iitems/getInventoryItem', 'getInventoryItem')->name('iitems.getInventoryItem');
        Route::get('iitems/getReagentInventoryItem', 'getReagentInventoryItem')->name('iitems.getReagentInventoryItem');
        Route::get('iitems/{iitem}/maintenance-tasks', 'getMaintenanceTasks')->name('iitems.getMaintenanceTasks');

        Route::get('iitems/download-all-attachments', 'downloadAllAttachments')->name('iitems.download-all-attachments');
        Route::get('iitems/download-single-attachment', 'downloadSingleAttachment')->name('iitems.download-single-attachment');
        Route::delete('iitems/delete-attachment', 'deleteattachment')->name('iitems.delete-attachment');

        Route::get('iitems/export', 'exportInventory')->name('iitems.export');
    });

    // Inventory Equipment
    Route::controller(InventoryEquipmentController::class)->group(function () {
        Route::get('iequipments', 'index')->name('iequipments.index');
        Route::get('iequipments/create', 'create')->name('iequipments.create');
        Route::post('iequipments', 'store')->name('iequipments.store');
        Route::get('iequipments/{iitem}/edit', 'edit')->name('iequipments.edit');
        Route::put('iequipments/{iitem}', 'update')->name('iequipments.update');
        Route::get('iequipments/{iitem}/show', 'show')->name('iequipments.show');
        Route::get('iequipments/destroy', 'destroy')->name('iequipments.destroy');
        Route::get('iequipments/restore', 'restore')->name('iequipments.restore');
        Route::get('iequipments/getInventoryItem', 'getInventoryItem')->name('iequipments.getInventoryItem');
        Route::get('iequipments/getReagentInventoryItem', 'getReagentInventoryItem')->name('iequipments.getReagentInventoryItem');
        Route::get('iequipments/{iitem}/maintenance-tasks', 'getMaintenanceTasks')->name('iequipments.getMaintenanceTasks');

        Route::get('iequipments/download-all-attachments', 'downloadAllAttachments')->name('iequipments.download-all-attachments');
        Route::get('iequipments/download-single-attachment', 'downloadSingleAttachment')->name('iequipments.download-single-attachment');
        Route::delete('iequipments/delete-attachment', 'deleteattachment')->name('iequipments.delete-attachment');

        Route::get('iequipments/export', 'exportInventory')->name('iequipments.export');
    });

    // Inventory Item Locations
    Route::controller(InventoryItemLocationController::class)->group(function () {
        Route::get('ilocations', 'index')->name('ilocations.index');
        Route::get('ilocations/create', 'create')->name('ilocations.create');
        Route::post('ilocations', 'store')->name('ilocations.store');
        Route::get('ilocations/{ilocation}/edit', 'edit')->name('ilocations.edit');
        Route::put('ilocations/{ilocation}', 'update')->name('ilocations.update');
        Route::get('ilocations/destroy', 'destroy')->name('ilocations.destroy');
        Route::get('ilocations/restore', 'restore')->name('ilocations.restore');
        Route::get('ilocations/getInventoryItemLocation', 'getInventoryItemLocation')->name('ilocations.getInventoryItemLocation');
    });

    // Inventory Item Warehouses
    Route::controller(InventoryItemWarehouseController::class)->group(function () {
        Route::get('iwarehouses', 'index')->name('iwarehouses.index');
        Route::get('iwarehouses/create', 'create')->name('iwarehouses.create');
        Route::post('iwarehouses', 'store')->name('iwarehouses.store');
        Route::get('iwarehouses/{iwarehouse}/edit', 'edit')->name('iwarehouses.edit');
        Route::put('iwarehouses/{iwarehouse}', 'update')->name('iwarehouses.update');
        Route::get('iwarehouses/destroy', 'destroy')->name('iwarehouses.destroy');
        Route::get('iwarehouses/restore', 'restore')->name('iwarehouses.restore');
        Route::get('iwarehouses/getInventoryItemWarehouse', 'getInventoryItemWarehouse')->name('iwarehouses.getInventoryItemWarehouse');
    });

    // Inventory
    Route::controller(InventoryController::class)->group(function () {
        Route::get('inventory', 'index')->name('inventory.index');
        Route::get('inventory/create', 'create')->name('inventory.create');
        Route::post('inventory', 'store')->name('inventory.store');
        Route::get('inventory/{inventory}/edit', 'edit')->name('inventory.edit');
        Route::put('inventory/{inventory}', 'update')->name('inventory.update');
        Route::get('inventory/{inventory}/show', 'show')->name('inventory.show');
        Route::get('inventory/destroy', 'destroy')->name('inventory.destroy');
        Route::get('inventory/restore', 'restore')->name('inventory.restore');
        Route::get('inventory/getInventory', 'getInventory')->name('inventory.getInventory');
        Route::get('inventory/getInventoryReagentItem', 'getInventoryReagentItem')->name('inventory.getInventoryReagentItem');
        Route::post('inventory/{inventory}/increment', 'increment')->name('inventory.increment');
        Route::post('inventory/{inventory}/decrement', 'decrement')->name('inventory.decrement');
    });

    // Inventory Reagent Consumption
    Route::controller(ReagentConsumptionController::class)->group(function () {
        Route::get('reagent-consumption', 'index')->name('reagent-consumption.index');
        Route::get('consumption/{reagentId}/logs', 'consumptionLogs')->name('reagent-consumption.consumptionLogs');
        Route::post('consumption', 'store')->name('reagent-consumption.store');
        Route::post('consumption/batch', 'storeBatch')->name('reagent-consumption.storeBatch');
    });

    // Inventory Reagent Consumption
    // Route::post('/reagent-consumption', [ReagentConsumptionController::class, 'store'])->name('reagent-consumption.store');
    // Route::post('/reagent-consumption/batch', [ReagentConsumptionController::class, 'storeBatch'])->name('reagent-consumption.storeBatch');
    // Route::get('/reagent-consumption/{reagentId}/logs', [ReagentConsumptionController::class, 'consumptionLogs'])->name('reagent-consumption.consumptionLogs');

    // Reagent Dashboard
    Route::get('/reagent-dashboard', [ReagentDashboardController::class, 'index'])->name('reagent-dashboard.index');

    // Inventory Item Suppliers
    Route::controller(InventoryItemSupplierController::class)->group(function () {
        Route::get('isuppliers', 'index')->name('isuppliers.index');
        Route::get('isuppliers/create', 'create')->name('isuppliers.create');
        Route::post('isuppliers', 'store')->name('isuppliers.store');
        Route::get('isuppliers/{isupplier}/edit', 'edit')->name('isuppliers.edit');
        Route::put('isuppliers/{isupplier}', 'update')->name('isuppliers.update');
        Route::get('isuppliers/destroy', 'destroy')->name('isuppliers.destroy');
        Route::get('isuppliers/restore', 'restore')->name('isuppliers.restore');
        Route::get('isuppliers/getInventoryItemSupplier', 'getInventoryItemSupplier')->name('isuppliers.getInventoryItemSupplier');
    });

    // Inventory Orders
    Route::controller(InventoryOrderController::class)->group(function () {
        Route::get('iorders', 'index')->name('iorders.index');
        Route::get('iorders/create', 'create')->name('iorders.create');
        Route::post('iorders', 'store')->name('iorders.store');
        Route::get('iorders/{iorder}/edit', 'edit')->name('iorders.edit');
        Route::put('iorders/{iorder}', 'update')->name('iorders.update');
        Route::get('iorders/{iorder}/show', 'show')->name('iorders.show');
        Route::get('iorders/destroy', 'destroy')->name('iorders.destroy');
        Route::get('iorders/restore', 'restore')->name('iorders.restore');
        Route::get('iorders/getInventoryOrder', 'getInventoryOrder')->name('iorders.getInventoryOrder');

        // Change order status
        Route::get('iorders/{iorder}/change-status', 'changeOrderStatus')->name('iorders.changeOrderStatus');

    });

    // Inventory Transaction Types
    Route::controller(InventoryTransactionTypeController::class)->group(function () {
        Route::get('itransactiontypes', 'index')->name('itransactiontypes.index');
        Route::get('itransactiontypes/create', 'create')->name('itransactiontypes.create');
        Route::post('itransactiontypes', 'store')->name('itransactiontypes.store');
        Route::get('itransactiontypes/{type}/edit', 'edit')->name('itransactiontypes.edit');
        Route::put('itransactiontypes/{type}', 'update')->name('itransactiontypes.update');
        Route::get('itransactiontypes/destroy', 'destroy')->name('itransactiontypes.destroy');
        Route::get('itransactiontypes/restore', 'restore')->name('itransactiontypes.restore');
        Route::get('itransactiontypes/getInventoryTransactionType', 'getInventoryTransactionType')->name('itransactiontypes.getInventoryTransactionType');
    });

    // Inventory Transactions
    Route::controller(InventoryTransactionController::class)->group(function () {
        Route::get('itransactions', 'index')->name('itransactions.index');
        Route::get('itransactions/create', 'create')->name('itransactions.create');
        Route::post('itransactions', 'store')->name('itransactions.store');
        Route::get('itransactions/{transaction}/edit', 'edit')->name('itransactions.edit');
        Route::put('itransactions/{transaction}', 'update')->name('itransactions.update');
        Route::get('itransactions/{transaction}/show', 'show')->name('itransactions.show');
        Route::get('itransactions/destroy', 'destroy')->name('itransactions.destroy');
        Route::get('itransactions/restore', 'restore')->name('itransactions.restore');
    });

    // Inventory Deliveries
    Route::controller(InventoryDeliveryController::class)->group(function () {
        Route::get('ideliveries', 'index')->name('ideliveries.index');
        Route::get('ideliveries/create', 'create')->name('ideliveries.create');
        Route::post('ideliveries', 'store')->name('ideliveries.store');
        Route::get('ideliveries/{idelivery}/edit', 'edit')->name('ideliveries.edit');
        Route::put('ideliveries/{idelivery}', 'update')->name('ideliveries.update');
        Route::get('ideliveries/destroy', 'destroy')->name('ideliveries.destroy');
        Route::get('ideliveries/restore', 'restore')->name('ideliveries.restore');
        Route::get('ideliveries/getInventoryDelivery', 'getInventoryDelivery')->name('ideliveries.getInventoryDelivery');
    });

    // Inventory Item Transfers
    Route::controller(InventoryItemTransferController::class)->group(function () {
        Route::get('itransfers', 'index')->name('itransfers.index');
        Route::get('itransfers/create', 'create')->name('itransfers.create');
        Route::post('itransfers', 'store')->name('itransfers.store');
        Route::get('itransfers/{itransfer}/edit', 'edit')->name('itransfers.edit');
        Route::put('itransfers/{itransfer}', 'update')->name('itransfers.update');
        Route::get('itransfers/destroy', 'destroy')->name('itransfers.destroy');
        Route::get('itransfers/restore', 'restore')->name('itransfers.restore');
        Route::get('itransfers/getInventoryItemTransfer', 'getInventoryItemTransfer')->name('itransfers.getInventoryItemTransfer');
    });

    // Equipment Connection Testing
    Route::get('/equipment-connection-test', function () {
        return inertia('EquipmentConnectionTest', []);
    })->name('equipment-connection-test');

    // Worksheets
    Route::controller(WorksheetController::class)->group(function () {
        Route::get('worksheets', 'index')->name('worksheets.index');
        Route::post('worksheets', 'store')->name('worksheets.store');
        Route::post('analysis/{analysis}/worksheet-draft', 'storeAnalysisDraft')->name('analysis.worksheet-draft');
        Route::get('worksheets/destroy', 'destroy')->name('worksheets.destroy');
        Route::get('worksheets/restore', 'restore')->name('worksheets.restore');
        Route::get('worksheets/getWorksheet', 'getWorksheet')->name('worksheets.getWorksheet');
        Route::get('worksheets/{worksheet}', 'show')->name('worksheets.show');
        Route::put('worksheets/{worksheet}', 'update')->name('worksheets.update');
    });

});

Route::prefix('portal')->name('portal.')->middleware(UsePortalFortifyConfiguration::class)->group(function () {
    Route::get('/login', function () {
        return inertia('PortalAuth/Login', [
            'toast' => [
                'title' => 'Portal do cliente',
                'message' => 'Aceda aos seus pedidos, propostas, resultados e certificados com segurança.',
            ],
        ]);
    })->middleware('guest:portal')->name('login');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->middleware('guest:portal')
        ->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('guest:portal')
        ->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->middleware('guest:portal')
        ->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('guest:portal')
        ->name('password.update');
    Route::get('two-factor-challenge', [TwoFactorAuthenticatedSessionController::class, 'create'])
        ->middleware('guest:portal')
        ->name('two-factor.login');
    Route::post('two-factor-challenge', [TwoFactorAuthenticatedSessionController::class, 'store'])
        ->middleware('guest:portal')
        ->name('two-factor.login.store');
    Route::get('passkeys/authentication-options', [PortalPasskeyController::class, 'authenticationOptions'])
        ->middleware('guest:portal')
        ->name('passkeys.authentication_options');
    Route::post('passkeys/login', [PortalPasskeyController::class, 'login'])
        ->middleware('guest:portal')
        ->name('passkeys.login');
    Route::get('user/confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->middleware('auth:portal')
        ->name('password.confirm');
    Route::post('user/confirm-password', [ConfirmablePasswordController::class, 'store'])
        ->middleware('auth:portal')
        ->name('password.confirm.store');
    Route::get('user/confirmed-password-status', [ConfirmedPasswordStatusController::class, 'show'])
        ->middleware('auth:portal')
        ->name('password.confirmation');
    Route::put('user/password', [PasswordController::class, 'update'])
        ->middleware('auth:portal')
        ->name('user-password.update');
    Route::delete('user/other-browser-sessions', [BrowserSessionController::class, 'destroyPortal'])
        ->middleware('auth:portal')
        ->name('other-browser-sessions.destroy');
    Route::get('email/verify', [EmailVerificationPromptController::class, '__invoke'])
        ->middleware('auth:portal')
        ->name('verification.notice');
    Route::get('email/verify/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
        ->middleware(['auth:portal', 'signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware(['auth:portal', 'throttle:6,1'])
        ->name('verification.send');
    Route::post('user/two-factor-authentication', [TwoFactorAuthenticationController::class, 'store'])
        ->middleware(['auth:portal', 'password.confirm:portal.password.confirm'])
        ->name('two-factor.enable');
    Route::post('user/confirmed-two-factor-authentication', [ConfirmedTwoFactorAuthenticationController::class, 'store'])
        ->middleware(['auth:portal', 'password.confirm:portal.password.confirm'])
        ->name('two-factor.confirm');
    Route::delete('user/two-factor-authentication', [TwoFactorAuthenticationController::class, 'destroy'])
        ->middleware(['auth:portal', 'password.confirm:portal.password.confirm'])
        ->name('two-factor.disable');
    Route::get('user/two-factor-qr-code', [TwoFactorQrCodeController::class, 'show'])
        ->middleware(['auth:portal', 'password.confirm:portal.password.confirm'])
        ->name('two-factor.qr-code');
    Route::get('user/two-factor-secret-key', [TwoFactorSecretKeyController::class, 'show'])
        ->middleware(['auth:portal', 'password.confirm:portal.password.confirm'])
        ->name('two-factor.secret-key');
    Route::get('user/two-factor-recovery-codes', [RecoveryCodeController::class, 'index'])
        ->middleware(['auth:portal', 'password.confirm:portal.password.confirm'])
        ->name('two-factor.recovery-codes');
    Route::post('user/two-factor-recovery-codes', [RecoveryCodeController::class, 'store'])
        ->middleware(['auth:portal', 'password.confirm:portal.password.confirm'])
        ->name('two-factor.regenerate-recovery-codes');
    Route::middleware('auth:portal')->prefix('security/passkeys')->name('security.passkeys.')->group(function () {
        Route::post('registration-options', [PasskeyController::class, 'registrationOptions'])->name('registration-options');
        Route::post('/', [PasskeyController::class, 'store'])->name('store');
        Route::delete('/{passkey}', [PasskeyController::class, 'destroy'])->name('destroy');
    });

    // Portal Controller
    Route::controller(PortalController::class)->group(function () {
        Route::get('home', 'dashboard')->middleware('auth:portal')->name('home');
        Route::get('services', 'services')->middleware('auth:portal')->name('services');
        Route::get('profile', 'profile')->middleware('auth:portal')->name('profile');
        Route::get('security', 'security')->middleware('auth:portal')->name('security');
        Route::get('faqs', 'faqs')->middleware('auth:portal')->name('faqs');
        Route::get('requests', 'requests')->middleware('auth:portal')->name('requests.index');
        Route::post('requests', 'storerequest')->middleware('auth:portal')->name('request.store');
        Route::get('requests/export', 'exportRequests')->middleware('auth:portal')->name('request.export');
        Route::get('requests/markAsDone/{id}', 'markAsDone')->middleware('auth:portal')->name('request.markAsDone');
        Route::get('requests/destroy/{id}', 'destroyrequest')->middleware('auth:portal')->name('request.destroy');
        Route::get('collections', 'collections')->middleware('auth:portal')->name('collections');
        Route::get('collections/export', 'exportCollections')->middleware('auth:portal')->name('collections.export');
        Route::get('invoices', 'invoices')->middleware('auth:portal')->name('invoices');
        Route::get('invoices/getInvoicePDF', 'getInvoicePDF')->middleware('auth:portal')->name('invoices.getInvoicePDF');
        Route::get('receipts', 'receipts')->middleware('auth:portal')->name('receipts');
        Route::get('receipts/getReceiptPDF', 'getReceiptPDF')->middleware('auth:portal')->name('receipts.getReceiptPDF');
        Route::get('contractguides', 'contractguides')->middleware('auth:portal')->name('contractguides');
        Route::get('contractguides/getContractGuidePDF', 'getContractGuidePDF')->middleware('auth:portal')->name('contractguides.getContractGuidePDF');
        Route::get('creditnotes', 'creditnotes')->middleware('auth:portal')->name('creditnotes');
        Route::get('creditnotes/getCreditNotePDF', 'getCreditNotePDF')->middleware('auth:portal')->name('creditnotes.getCreditNotePDF');
        Route::get('quotes/getQuotePDF', 'getQuotePDF')->middleware('auth:portal')->name('quotes.getQuotePDF');
        Route::get('quotes', 'quotes')->middleware('auth:portal')->name('quotes');
        Route::get('qualitycertificates', 'qualitycertificates')->middleware('auth:portal')->name('qualitycertificates');
        Route::get('qualitycertificates/getQualityCertificatePDF', 'getQualityCertificatePDF')->middleware('auth:portal')->name('qualitycertificates.getQualityCertificatePDF');
    });

    Route::controller(RatingController::class)->middleware('auth:portal')->group(function () {
        Route::get('rate/{rateableType}/{rateableId?}', 'portalCreate')->whereNumber('rateableId')->name('rating.create');
        Route::post('rate/{rateableType}/{rateableId?}', 'portalStore')->whereNumber('rateableId')->name('rating.store');
    });

    $limiter = config('fortify.limiters.login');

    Route::post(RoutePath::for('login', '/login'), [AuthenticatedSessionController::class, 'store'])
        ->middleware(array_filter([
            'guest:portal',
            $limiter ? 'throttle:'.$limiter : null,
        ]))->name('login.store');

    Route::post(RoutePath::for('logout', '/logout'), [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth:portal')
        ->name('logout');

    // Route::get('/home', function(){
    //     return inertia('ClientPortal/Dashboard');
    // })->middleware('auth:portal')->name('home');
});

// VAP Proposal Public Routes (for client access)
Route::prefix('vap-proposals')->name('vap-proposals.')->group(function () {
    Route::get('/proposal/{hash}', [VAPPublicProposalController::class, 'show'])->name('public.show');
    Route::get('/proposal/{proposal:unique_hash}/thankyou', [VAPPublicProposalController::class, 'thankyou'])->name('public.thankyou');
    Route::get('/proposal/{hash}/download', [VAPPublicProposalController::class, 'downloadPdf'])->name('public.download');
});

Route::prefix('api')->group(function () {
    Route::post('/proposals/{proposal:unique_hash}/accept', [VAPProposalController::class, 'accept'])->name('proposals.api.accept');
    Route::post('/proposals/{proposal:unique_hash}/reject', [VAPProposalController::class, 'reject'])->name('proposals.api.reject');
});

// Route::prefix('api')->group(function () {
//     Route::post('/proposals/{proposal}/accept', [ProposalController::class, 'accept'])->name('proposals.api.accept');
//     Route::post('/proposals/{proposal}/reject', [ProposalController::class, 'reject'])->name('proposals.api.reject');
// });

Route::get('/auth/redirect/{service}', AuthRedirectController::class)->name('auth.redirect');
Route::get('/auth/callback/{service}', AuthCallbackController::class)->name('auth.callback');
Route::passkeys();

Route::middleware('auth')->prefix('security/passkeys')->name('security.passkeys.')->group(function () {
    Route::post('registration-options', [PasskeyController::class, 'registrationOptions'])->name('registration-options');
    Route::post('/', [PasskeyController::class, 'store'])->name('store');
    Route::delete('/{passkey}', [PasskeyController::class, 'destroy'])->name('destroy');
});
