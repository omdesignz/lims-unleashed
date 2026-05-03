<?php
// app/Http/Controllers/SampleEntryController.php

namespace App\Http\Controllers;

use App\Http\Requests\VAP\StoreSampleEntryRequest;
use App\Models\VAPSampleEntry;
use App\Models\Customer;
use App\Models\CustomerRequest;
use App\Models\Department;
use App\Models\Matrix;
use App\Models\Profile;
use App\Models\Product;
use App\Models\Proposal;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\PackagingCategory;
use App\Models\VAPLab;
use App\Models\VAPSampleDiscard;
use App\Notifications\SampleTrackingNotification;
use App\Support\LaboratoryWorkflowNotifier;
use App\Support\PersonnelQualificationGate;
use App\Support\SampleEntryCollectionFlowService;
use App\Settings\GeneralSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use PDF;

class VAPSampleEntryController extends Controller
{
    private function buildSampleIntakeTrendChart(): array
    {
        $window = collect(range(6, 0))
            ->map(fn (int $daysAgo) => now()->copy()->startOfDay()->subDays($daysAgo));

        $dailyCounts = VAPSampleEntry::query()
            ->selectRaw('DATE(created_at) as day_key, COUNT(*) as aggregate')
            ->whereDate('created_at', '>=', $window->first()->toDateString())
            ->groupBy('day_key')
            ->pluck('aggregate', 'day_key');

        return [
            'categories' => $window->map(fn (Carbon $day) => $day->translatedFormat('d M'))->all(),
            'series' => [
                [
                    'name' => 'Amostras recebidas',
                    'data' => $window->map(
                        fn (Carbon $day) => (int) ($dailyCounts[$day->toDateString()] ?? 0)
                    )->all(),
                ],
            ],
        ];
    }

    private function buildSampleLifecycleChart(): array
    {
        $statusCounts = VAPSampleEntry::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'labels' => [
                'Por iniciar',
                'Em progresso',
                'Em pausa',
                'Completado',
                'Cancelado',
            ],
            'series' => [
                (int) ($statusCounts['POR_INICIAR'] ?? 0),
                (int) ($statusCounts['EN_PROGRESO'] ?? 0),
                (int) ($statusCounts['EN_PAUSA'] ?? 0),
                (int) ($statusCounts['COMPLETADO'] ?? 0),
                (int) ($statusCounts['CANCELADO'] ?? 0),
            ],
        ];
    }

    private function buildSampleRetentionChart(): array
    {
        $retentionCounts = VAPSampleEntry::query()
            ->selectRaw('retention_status, COUNT(*) as aggregate')
            ->groupBy('retention_status')
            ->pluck('aggregate', 'retention_status');

        return [
            'labels' => [
                'Retenção ativa',
                'Próximo descarte',
                'Retenção vencida',
                'Descartadas',
            ],
            'series' => [
                (int) ($retentionCounts['active'] ?? 0),
                (int) ($retentionCounts['due_soon'] ?? 0),
                (int) ($retentionCounts['overdue'] ?? 0),
                (int) VAPSampleDiscard::count(),
            ],
        ];
    }

    /**
     * Display the sample entry interface
     */
    public function index(Request $request)
    {
        // $stats = [
        //     'total_samples' => VAPSampleEntry::count(),
        //     'pending_analysis' => VAPSampleEntry::pending()->count(),
        //     'completed_analysis' => VAPSampleEntry::completed()->count(),
        //     'total_discarded' => VAPSampleEntry::onlyTrashed()->count(),
        //     'discarded_this_month' => VAPSampleEntry::onlyTrashed()
        //         ->whereMonth('deleted_at', now()->month)
        //         ->count(),
        // ];

        $discardableSamples = VAPSampleEntry::discardable()
            ->with(['customer', 'lab', 'department'])
            ->get()
            ->map(function ($sample) {
                return [
                    'id' => $sample->id,
                    'name' => $sample->name,
                    'code' => $sample->code,
                    'status' => $sample->status,
                    'sample_type' => $sample->sample_type,
                    'received_at' => $sample->received_at,
                    'customer' => $sample->customer ? $sample->customer->only(['id', 'name']) : null,
                    'lab_id' => $sample->lab_id,
                    'department_id' => $sample->department_id,
                ];
            });

        $recentDiscards = VAPSampleDiscard::with(['sample', 'discardedBy'])
            ->recent(7)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($discard) {
                return [
                    'id' => $discard->id,
                    'sample_id' => $discard->sample_id,
                    'sample' => $discard->sample ? $discard->sample->only(['id', 'name', 'code']) : null,
                    'discard_method' => $discard->discard_method,
                    'qty' => $discard->qty,
                    'discarded_at' => $discard->discarded_at,
                    'discarded_by' => $discard->discardedBy ? $discard->discardedBy->only(['id', 'name']) : null,
                ];
            });

        $stats = [
            'total_samples' => VAPSampleEntry::count(),
            'pending_analysis' => VAPSampleEntry::pending()->count(),
            'in_progress' => VAPSampleEntry::inProgress()->count(),
            'completed_analysis' => VAPSampleEntry::completed()->count(),
            'total_discarded' => VAPSampleDiscard::count(),
            'discarded_this_month' => VAPSampleDiscard::whereMonth('created_at', now()->month)->count(),
            'today_samples' => VAPSampleEntry::whereDate('created_at', today())->count(),
            'week_samples' => VAPSampleEntry::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];
        
        
        $samples = VAPSampleEntry::with(['customer', 'lab', 'department', 'warehouse'])
            ->when($request->has('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('code', 'like', '%' . $request->search . '%');
            })
            ->when($request->has('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($sample) {
                return [
                    'id' => $sample->id,
                    'name' => $sample->name,
                    'code' => $sample->code,
                    'sample_type' => $sample->sample_type,
                    'status' => $sample->status,
                    'received_at' => $sample->received_at,
                    'analysis_start_date' => $sample->analysis_start_date,
                    'analysis_end_date' => $sample->analysis_end_date,
                    'customer' => $sample->customer ? $sample->customer->only(['id', 'name']) : null,
                    'customer_id' => $sample->customer_id,
                    'lab_id' => $sample->lab_id,
                    'department_id' => $sample->department_id,
                    'packaging_id' => $sample->packaging_id,
                    'warehouse_id' => $sample->warehouse_id,
                    'proposal_id' => $sample->proposal_id,
                    'customer_request_id' => $sample->customer_request_id,
                    'requested_services' => $sample->requested_services,
                    'client_submitted_info' => $sample->client_submitted_info,
                    'obs' => $sample->obs,
                    'retention_period_days' => $sample->retention_period_days,
                    'retention_due_at' => optional($sample->retention_due_at)?->toDateString(),
                    'discard_scheduled_at' => optional($sample->discard_scheduled_at)?->toDateString(),
                    'retention_status' => $sample->retention_status,
                    'created_at' => $sample->created_at,
                ];
        });

        // return Inertia::render('Samples/Index', [
        //     'stats' => $stats,
        //     'discardableSamples' => $discardableSamples,
        //     'recentDiscards' => $recentDiscards,
        //     'customers' => Customer::select('id', 'name', 'code')->orderBy('name')->get(),
        //     'labs' => VAPLab::select('id', 'name', 'code')->orderBy('name')->get(),
        //     'departments' => Department::select('id', 'name', 'code')->orderBy('name')->get(),
        //     'warehouses' => Warehouse::select('id', 'name', 'code')->orderBy('name')->get(),
        //     'packagingCategories' => PackagingCategory::select('id', 'name', 'code')->orderBy('name')->get(),
        // ]);

        return Inertia::render('VAPSamples/Index', [
            'title' => 'Gestão de Amostras',
            'stats' => $stats,
            'charts' => [
                'intake_trend' => $this->buildSampleIntakeTrendChart(),
                'lifecycle_status' => $this->buildSampleLifecycleChart(),
                'retention_pressure' => $this->buildSampleRetentionChart(),
            ],
            'samples' => $samples,
            'discardableSamples' => $discardableSamples,
            'recentDiscards' => $recentDiscards,
            'customers' => Customer::select('id', 'name', 'code')->orderBy('name')->get(),
            'acceptedProposals' => Proposal::query()
                ->accepted()
                ->with(['customer:id,name', 'warehouse:id,address'])
                ->latest('updated_at')
                ->limit(100)
                ->get()
                ->map(fn (Proposal $proposal) => [
                    'id' => $proposal->id,
                    'proposal_no' => $proposal->proposal_no,
                    'customer_id' => $proposal->customer_id,
                    'customer' => $proposal->customer?->name,
                    'warehouse_id' => $proposal->warehouse_id,
                    'warehouse' => $proposal->warehouse?->address,
                    'status' => (string) $proposal->status,
                    'total' => $proposal->total,
                    'updated_at' => optional($proposal->updated_at)?->toIso8601String(),
                ]),
            'portalAnalysisRequests' => $this->pendingPortalAnalysisRequests(),
            'products' => Product::query()
                ->with([
                    'matrix:id,description',
                    'matrix.profiles:id,name,category_id',
                    'matrix.profiles.type:id,name,department_id',
                    'matrix.profiles.parameters:id,name,code',
                ])
                ->orderBy('name')
                ->get(['id', 'name', 'matrix_id'])
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'matrix_id' => $product->matrix_id,
                    'matrix' => $product->matrix?->description,
                    'profiles' => $product->matrix?->profiles
                        ? $product->matrix->profiles->map(fn (Profile $profile) => [
                            'id' => $profile->id,
                            'name' => $profile->name,
                            'category_id' => $profile->category_id,
                            'analysis_type' => $profile->type?->name,
                            'department_id' => $profile->type?->department_id,
                            'parameter_count' => $profile->parameters->unique('id')->count(),
                            'parameters' => $profile->parameters
                                ->unique('id')
                                ->sortBy('name')
                                ->values()
                                ->map(fn ($parameter) => [
                                    'id' => $parameter->id,
                                    'name' => $parameter->name,
                                    'code' => $parameter->code,
                                ]),
                        ])->values()
                        : [],
                ]),
            'profiles' => Profile::query()
                ->with('type:id,name,department_id')
                ->orderBy('name')
                ->get(['id', 'name', 'category_id'])
                ->map(fn (Profile $profile) => [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'category_id' => $profile->category_id,
                    'analysis_type' => $profile->type?->name,
                    'department_id' => $profile->type?->department_id,
                ]),
            'matrixes' => Matrix::query()->orderBy('description')->get(['id', 'description']),
            'labs' => VAPLab::select('id', 'name', 'code')->orderBy('name')->get(),
            'departments' => Department::select('id', 'name', 'code')->orderBy('name')->get(),
            'warehouses' => Warehouse::select('id', 'name', 'address', 'code')->orderBy('name')->get(),
            'packagingCategories' => PackagingCategory::select('id', 'name', 'description')->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created sample entry
     */
    public function store(
        StoreSampleEntryRequest $request,
        SampleEntryCollectionFlowService $sampleEntryCollectionFlowService,
        LaboratoryWorkflowNotifier $workflowNotifier
    )
    {
        $validated = $request->validated();
        app(PersonnelQualificationGate::class)->ensure(auth()->user(), 'sample_intake_validation', $validated['department_id'] ?? null);

        DB::beginTransaction();
        try {
            $portalRequest = $this->resolvePortalRequest($validated);
            $proposal = $this->resolveProposal($validated['proposal_id'] ?? null);
            $this->ensureExecutionIsAuthorized($validated, $proposal);
            $validated = $this->enrichSamplePayload($validated, $portalRequest);

            $sample = VAPSampleEntry::create(array_merge($validated, [
                'received_by_id' => auth()->id(),
                'received_by_label' => auth()->user()->name,
                'sample_year' => date('Y'),
            ]));

            // Generate code if not provided
            if (!$sample->code) {
                $sample->generateCode();
                $sample->save();
            }

            $sampleEntryCollectionFlowService->sync($sample->fresh());
            $this->markPortalRequestAsValidated($portalRequest, $sample);
            $sample = $sample->fresh(['collectionProduct.code', 'warehouse', 'receivedBy']);
            $this->sendSampleTrackingNotifications($sample, 'created');
            $workflowNotifier->notifySampleCollectionLinked($sample, auth()->user());

            DB::commit();

            return redirect()->back()->with([
                'message' => 'A amostra foi registada com sucesso.',
                'type' => 'success',
                'sample_id' => $sample->id,
            ]);
        } catch (ValidationException $exception) {
            DB::rollBack();

            throw $exception;
        } catch (\Throwable $exception) {
            DB::rollBack();
            Log::error('Failed to create sample entry.', [
                'exception' => $exception,
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()->with([
                'message' => 'Não foi possível registar a amostra. Tente novamente.',
                'type' => 'error',
            ]);
        }
    }

    /**
     * Update the specified sample entry
     */
    public function update(
        StoreSampleEntryRequest $request,
        VAPSampleEntry $sampleEntry,
        SampleEntryCollectionFlowService $sampleEntryCollectionFlowService,
        LaboratoryWorkflowNotifier $workflowNotifier
    )
    {
        $validated = $request->validated();
        app(PersonnelQualificationGate::class)->ensure(auth()->user(), 'sample_intake_validation', $validated['department_id'] ?? $sampleEntry->department_id);
        $portalRequest = $this->resolvePortalRequest($validated);
        $proposal = $this->resolveProposal($validated['proposal_id'] ?? $sampleEntry->proposal_id);
        $this->ensureExecutionIsAuthorized($validated, $proposal);
        $validated = $this->enrichSamplePayload($validated, $portalRequest, $sampleEntry);
        $previousStatus = $sampleEntry->status;
        $sampleEntry->update($validated);
        $sampleEntryCollectionFlowService->sync($sampleEntry->fresh());
        $this->markPortalRequestAsValidated($portalRequest, $sampleEntry);
        $sampleEntry = $sampleEntry->fresh(['collectionProduct.code', 'warehouse', 'receivedBy']);
        $this->sendSampleTrackingNotifications(
            $sampleEntry,
            $previousStatus !== $sampleEntry->status ? 'status_updated' : 'updated',
            ['previous_status' => $previousStatus]
        );
        $workflowNotifier->notifySampleCollectionLinked($sampleEntry, auth()->user());

        return redirect()->back()->with([
            'message' => 'A amostra foi actualizada com sucesso.',
            'type' => 'success',
        ]);
    }

    /**
     * Generate sample entry PDF
     */
    public function generatePdf(VAPSampleEntry $sampleEntry)
    {
        $sampleEntry->load(['customer', 'lab', 'department', 'warehouse', 'packaging', 'receivedBy']);
        
        $pdf = PDF::loadView('PDFs.sample-entry', [
            'sample' => $sampleEntry,
            'date' => now()->format('d/m/Y'),
            'time' => now()->format('H:i:s'),
        ]);

        $filename = "sample-entry-{$sampleEntry->code}-" . now()->format('Ymd-His') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Get sample statistics
     */
    public function stats()
    {
        $stats = [
            'total_samples' => VAPSampleEntry::count(),
            'pending_analysis' => VAPSampleEntry::pending()->count(),
            'completed_analysis' => VAPSampleEntry::completed()->count(),
            'total_discarded' => VAPSampleEntry::onlyTrashed()->count(),
            'discarded_this_month' => VAPSampleEntry::onlyTrashed()
                ->whereMonth('deleted_at', now()->month)
                ->count(),
            'by_sample_type' => VAPSampleEntry::select('sample_type', DB::raw('count(*) as total'))
                ->groupBy('sample_type')
                ->get(),
            'by_status' => VAPSampleEntry::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get(),
        ];

        return response()->json($stats);
    }

    public function export(Request $request)
    {
        $samples = VAPSampleEntry::with(['customer', 'lab', 'department', 'warehouse', 'packaging'])
            ->when($request->has('start_date'), function ($query) use ($request) {
                $query->where('created_at', '>=', $request->start_date);
            })
            ->when($request->has('end_date'), function ($query) use ($request) {
                $query->where('created_at', '<=', $request->end_date);
            })
            ->when($request->has('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="samples-' . now()->format('Ymd-His') . '.csv"',
        ];

        $callback = function () use ($samples) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID',
                'Code',
                'Name',
                'Sample Type',
                'Customer',
                'Laboratory',
                'Department',
                'Warehouse',
                'Status',
                'Received Date',
                'Analysis Start',
                'Analysis End',
                'Created At',
            ]);

            // CSV data
            foreach ($samples as $sample) {
                fputcsv($file, [
                    $sample->id,
                    $sample->code,
                    $sample->name,
                    $sample->sample_type,
                    $sample->customer->name ?? 'N/A',
                    $sample->lab->name ?? 'N/A',
                    $sample->department->name ?? 'N/A',
                    $sample->warehouse->name ?? 'N/A',
                    $sample->status,
                    $sample->received_at ? $sample->received_at->format('Y-m-d H:i:s') : 'N/A',
                    $sample->analysis_start_date ? $sample->analysis_start_date->format('Y-m-d H:i:s') : 'N/A',
                    $sample->analysis_end_date ? $sample->analysis_end_date->format('Y-m-d H:i:s') : 'N/A',
                    $sample->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Display consolidated VAP sample reports.
     */
    public function reports(Request $request)
    {
        $filters = [
            'date_from' => $request->string('date_from')->value(),
            'date_to' => $request->string('date_to')->value(),
            'status' => $request->string('status')->value(),
            'sample_type' => $request->string('sample_type')->value(),
            'customer_id' => $request->string('customer_id')->value(),
            'lab_id' => $request->string('lab_id')->value(),
            'department_id' => $request->string('department_id')->value(),
            'discard_method' => $request->string('discard_method')->value(),
        ];

        $sampleQuery = VAPSampleEntry::query()
            ->with(['customer:id,name', 'lab:id,name', 'department:id,name', 'warehouse:id,name'])
            ->when($filters['date_from'], function ($query, $dateFrom) {
                $query->whereDate('received_at', '>=', $dateFrom);
            })
            ->when($filters['date_to'], function ($query, $dateTo) {
                $query->whereDate('received_at', '<=', $dateTo);
            })
            ->when($filters['status'], function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['sample_type'], function ($query, $sampleType) {
                $query->where('sample_type', $sampleType);
            })
            ->when($filters['customer_id'], function ($query, $customerId) {
                $query->where('customer_id', $customerId);
            })
            ->when($filters['lab_id'], function ($query, $labId) {
                $query->where('lab_id', $labId);
            })
            ->when($filters['department_id'], function ($query, $departmentId) {
                $query->where('department_id', $departmentId);
            });

        $discardQuery = VAPSampleDiscard::query()
            ->with([
                'sample:id,name,code,status,sample_type,customer_id,lab_id,department_id',
                'sample.customer:id,name',
                'sample.lab:id,name',
                'sample.department:id,name',
                'discardedBy:id,name',
                'lab:id,name',
                'department:id,name',
            ])
            ->when($filters['date_from'], function ($query, $dateFrom) {
                $query->whereDate('discarded_at', '>=', $dateFrom);
            })
            ->when($filters['date_to'], function ($query, $dateTo) {
                $query->whereDate('discarded_at', '<=', $dateTo);
            })
            ->when($filters['discard_method'], function ($query, $discardMethod) {
                $query->where('discard_method', $discardMethod);
            })
            ->when($filters['lab_id'], function ($query, $labId) {
                $query->where(function ($nested) use ($labId) {
                    $nested->where('lab_id', $labId)
                        ->orWhereHas('sample', function ($sampleQuery) use ($labId) {
                            $sampleQuery->where('lab_id', $labId);
                        });
                });
            })
            ->when($filters['department_id'], function ($query, $departmentId) {
                $query->where(function ($nested) use ($departmentId) {
                    $nested->where('department_id', $departmentId)
                        ->orWhereHas('sample', function ($sampleQuery) use ($departmentId) {
                            $sampleQuery->where('department_id', $departmentId);
                        });
                });
            })
            ->when($filters['customer_id'], function ($query, $customerId) {
                $query->whereHas('sample', function ($sampleQuery) use ($customerId) {
                    $sampleQuery->where('customer_id', $customerId);
                });
            })
            ->when($filters['sample_type'], function ($query, $sampleType) {
                $query->whereHas('sample', function ($sampleQuery) use ($sampleType) {
                    $sampleQuery->where('sample_type', $sampleType);
                });
            })
            ->when($filters['status'], function ($query, $status) {
                $query->whereHas('sample', function ($sampleQuery) use ($status) {
                    $sampleQuery->where('status', $status);
                });
            });

        $totalSamples = (clone $sampleQuery)->count();
        $completedSamples = (clone $sampleQuery)->where('status', 'COMPLETADO')->count();
        $pendingSamples = (clone $sampleQuery)->where('status', 'POR_INICIAR')->count();
        $inProgressSamples = (clone $sampleQuery)->where('status', 'EN_PROGRESO')->count();
        $totalDiscards = (clone $discardQuery)->count();

        $averageTurnaroundHours = round(
            (float) ((clone $sampleQuery)
                ->whereNotNull('received_at')
                ->whereNotNull('analysis_end_date')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, received_at, analysis_end_date)) as avg_hours')
                ->value('avg_hours') ?? 0),
            1
        );

        $statusBreakdown = (clone $sampleQuery)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->status,
                'total' => (int) $row->total,
            ]);

        $sampleTypeBreakdown = (clone $sampleQuery)
            ->select('sample_type', DB::raw('count(*) as total'))
            ->groupBy('sample_type')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->sample_type ?: 'N/A',
                'total' => (int) $row->total,
            ]);

        $discardMethodBreakdown = (clone $discardQuery)
            ->select('discard_method', DB::raw('count(*) as total'))
            ->groupBy('discard_method')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->discard_method ?: 'N/A',
                'total' => (int) $row->total,
            ]);

        $sampleTimeline = (clone $sampleQuery)
            ->selectRaw('DATE(received_at) as report_date, COUNT(*) as total')
            ->whereNotNull('received_at')
            ->groupBy('report_date')
            ->orderBy('report_date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->report_date,
                'total' => (int) $row->total,
            ]);

        $samples = (clone $sampleQuery)
            ->latest('received_at')
            ->paginate(10, ['*'], 'samples_page')
            ->withQueryString()
            ->through(function (VAPSampleEntry $sample) {
                return [
                    'id' => $sample->id,
                    'name' => $sample->name,
                    'code' => $sample->code,
                    'sample_type' => $sample->sample_type,
                    'status' => $sample->status,
                    'received_at' => optional($sample->received_at)?->toIso8601String(),
                    'analysis_start_date' => optional($sample->analysis_start_date)?->toIso8601String(),
                    'analysis_end_date' => optional($sample->analysis_end_date)?->toIso8601String(),
                    'customer' => $sample->customer?->only(['id', 'name']),
                    'lab' => $sample->lab?->only(['id', 'name']),
                    'department' => $sample->department?->only(['id', 'name']),
                    'warehouse' => $sample->warehouse?->only(['id', 'name']),
                ];
            });

        $discards = (clone $discardQuery)
            ->latest('discarded_at')
            ->paginate(10, ['*'], 'discards_page')
            ->withQueryString()
            ->through(function (VAPSampleDiscard $discard) {
                return [
                    'id' => $discard->id,
                    'discard_method' => $discard->discard_method,
                    'qty' => $discard->qty,
                    'discarded_at' => optional($discard->discarded_at)?->toIso8601String(),
                    'discarded_by' => $discard->discardedBy?->only(['id', 'name']),
                    'lab' => $discard->lab?->only(['id', 'name']) ?? $discard->sample?->lab?->only(['id', 'name']),
                    'department' => $discard->department?->only(['id', 'name']) ?? $discard->sample?->department?->only(['id', 'name']),
                    'sample' => $discard->sample ? [
                        'id' => $discard->sample->id,
                        'name' => $discard->sample->name,
                        'code' => $discard->sample->code,
                        'status' => $discard->sample->status,
                        'sample_type' => $discard->sample->sample_type,
                        'customer' => $discard->sample->customer?->only(['id', 'name']),
                    ] : null,
                ];
            });

        return Inertia::render('VAPSamples/Reports', [
            'title' => 'Relatórios de Amostras',
            'filters' => $filters,
            'summary' => [
                'total_samples' => $totalSamples,
                'completed_samples' => $completedSamples,
                'pending_samples' => $pendingSamples,
                'in_progress_samples' => $inProgressSamples,
                'total_discards' => $totalDiscards,
                'discard_rate' => $totalSamples > 0 ? round(($totalDiscards / $totalSamples) * 100, 1) : 0,
                'avg_turnaround_hours' => $averageTurnaroundHours,
            ],
            'samples' => $samples,
            'discards' => $discards,
            'statusBreakdown' => $statusBreakdown,
            'sampleTypeBreakdown' => $sampleTypeBreakdown,
            'discardMethodBreakdown' => $discardMethodBreakdown,
            'sampleTimeline' => $sampleTimeline,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'labs' => VAPLab::query()->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'discardMethods' => VAPSampleDiscard::query()
                ->select('discard_method')
                ->distinct()
                ->orderBy('discard_method')
                ->pluck('discard_method')
                ->filter()
                ->values(),
            'sampleTypes' => VAPSampleEntry::query()
                ->select('sample_type')
                ->distinct()
                ->orderBy('sample_type')
                ->pluck('sample_type')
                ->filter()
                ->values(),
            'generatedAt' => Carbon::now()->toIso8601String(),
        ]);
    }

    private function pendingPortalAnalysisRequests()
    {
        $profiles = Profile::query()->pluck('name', 'id');

        return CustomerRequest::query()
            ->with(['customer:id,name', 'warehouse:id,address'])
            ->where('request_type', 'analysis_request')
            ->where(function ($query) {
                $query->whereIn('status', ['pending', 'in_progress'])
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('status')->where('answered', false);
                    });
            })
            ->latest('submitted_at')
            ->limit(100)
            ->get()
            ->map(function (CustomerRequest $request) use ($profiles) {
                $details = collect($request->extra_data ?? []);
                $sampleRows = collect($details->get('samples', []))
                    ->map(function ($sample, int $index) {
                        return [
                            'batch_index' => $sample['batch_index'] ?? $index,
                            'sample_name' => $sample['sample_name'] ?? null,
                            'product_name' => $sample['product_name'] ?? null,
                            'matrix' => $sample['matrix'] ?? null,
                            'lot' => $sample['lot'] ?? null,
                            'packaging' => $sample['packaging'] ?? null,
                            'quantity' => $sample['quantity'] ?? null,
                            'notes' => $sample['notes'] ?? null,
                        ];
                    })
                    ->values();
                $validatedBatchIndexes = collect($details->get('validated_batch_indexes', []))
                    ->filter(fn ($index) => is_numeric($index))
                    ->map(fn ($index) => (int) $index)
                    ->values();
                $remainingSampleRows = $sampleRows
                    ->reject(fn (array $sample) => $validatedBatchIndexes->contains((int) ($sample['batch_index'] ?? -1)))
                    ->values();
                $requestedProfileNames = collect($details->get('requested_profiles', []))
                    ->map(fn ($profileId) => $profiles->get($profileId, 'Perfil #' . $profileId))
                    ->values()
                    ->all();

                return [
                    'id' => $request->id,
                    'reference' => $request->reference,
                    'title' => $request->title,
                    'status' => $request->portal_status,
                    'description' => $request->description,
                    'customer_id' => $request->customer_id,
                    'customer' => $request->customer?->name,
                    'warehouse_id' => $request->warehouse_id,
                    'warehouse' => $request->warehouse?->address,
                    'preferred_date' => optional($request->preferred_date)?->format('Y-m-d'),
                    'details' => $details,
                    'product_id' => data_get($details, 'product_id'),
                    'matrix_id' => data_get($details, 'matrix_id'),
                    'packaging_id' => data_get($details, 'packaging_id'),
                    'sample_rows' => $sampleRows,
                    'remaining_sample_rows' => $remainingSampleRows,
                    'remaining_sample_count' => $remainingSampleRows->count(),
                    'next_sample_row' => $remainingSampleRows->first(),
                    'requested_profile_names' => $requestedProfileNames,
                    'submitted_at' => optional($request->submitted_at)?->toIso8601String(),
                ];
            })
            ->values();
    }

    private function resolvePortalRequest(array $validated): ?CustomerRequest
    {
        $portalRequestId = $validated['portal_request_id'] ?? $validated['customer_request_id'] ?? null;

        if (! $portalRequestId) {
            return null;
        }

        return CustomerRequest::query()->findOrFail($portalRequestId);
    }

    private function resolveProposal(?int $proposalId): ?Proposal
    {
        if (! $proposalId) {
            return null;
        }

        return Proposal::query()->findOrFail($proposalId);
    }

    private function ensureExecutionIsAuthorized(array $validated, ?Proposal $proposal): void
    {
        $workIsStarting = ($validated['status'] ?? 'POR_INICIAR') !== 'POR_INICIAR'
            || ! empty($validated['analysis_start_date'])
            || ! empty($validated['analysis_end_date']);

        if (! $workIsStarting) {
            return;
        }

        $operationMode = app(GeneralSettings::class)->app_operation_mode ?? 'client_only';
        $requestOrigin = data_get($validated, 'client_submitted_info.request_origin', 'client');

        if ($requestOrigin === 'internal' && in_array($operationMode, ['internal_only', 'hybrid'], true)) {
            return;
        }

        if (! $proposal || ! $proposal->isAccepted()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'proposal_id' => 'É necessário associar uma proposta aceite antes de colocar a amostra em análise.',
            ]);
        }
    }

    private function enrichSamplePayload(
        array $validated,
        ?CustomerRequest $portalRequest,
        ?VAPSampleEntry $sampleEntry = null
    ): array {
        unset($validated['portal_request_id']);

        if ($portalRequest) {
            $validated['customer_request_id'] = $portalRequest->id;
            $validated['client_submitted_info'] = collect($validated['client_submitted_info'] ?? [])
                ->merge([
                    'request_origin' => data_get($portalRequest->extra_data, 'request_origin', 'client'),
                    'request_reference' => $portalRequest->reference,
                    'request_title' => $portalRequest->title,
                    'request_description' => $portalRequest->description,
                    'preferred_date' => optional($portalRequest->preferred_date)?->format('Y-m-d'),
                    'details' => $portalRequest->extra_data,
                ])
                ->all();
            if (blank($validated['requested_services'] ?? null)) {
                $validated['requested_services'] = collect($portalRequest->extra_data['requested_profiles'] ?? [])
                    ->filter()
                    ->values()
                    ->all();
            }

            $portalDetails = collect($portalRequest->extra_data ?? []);
            $validated['client_submitted_info'] = collect($validated['client_submitted_info'] ?? [])
                ->merge([
                    'product_id' => $portalDetails->get('product_id'),
                    'matrix_id' => $portalDetails->get('matrix_id'),
                    'packaging_id' => $portalDetails->get('packaging_id'),
                    'requested_profile_ids' => collect($portalDetails->get('requested_profiles', []))->filter()->values()->all(),
                    'quantity' => $portalDetails->get('quantity'),
                    'lot' => $portalDetails->get('lot'),
                    'product_name' => $portalDetails->get('product_name'),
                    'matrix' => $portalDetails->get('matrix'),
                    'packaging' => $portalDetails->get('packaging'),
                ])
                ->all();
        } elseif ($sampleEntry && ! array_key_exists('customer_request_id', $validated)) {
            $validated['customer_request_id'] = $sampleEntry->customer_request_id;
        }

        $validated['client_submitted_info'] = collect($validated['client_submitted_info'] ?? [])
            ->merge([
                'request_origin' => data_get($validated, 'client_submitted_info.request_origin', 'client'),
            ])
            ->all();

        $validated['client_submitted_info'] = $this->attachAnalyticalScopeSnapshot(
            $validated['client_submitted_info'],
            $validated['department_id'] ?? $sampleEntry?->department_id
        );

        $receivedAt = isset($validated['received_at'])
            ? Carbon::parse($validated['received_at'])
            : ($sampleEntry?->received_at ?? now());
        $retentionDays = (int) ($validated['retention_period_days']
            ?? $sampleEntry?->retention_period_days
            ?? VAPSampleEntry::defaultRetentionPeriodFor($validated['sample_type'] ?? $sampleEntry?->sample_type));

        $validated['retention_period_days'] = $retentionDays;
        $validated['retention_due_at'] = $validated['retention_due_at'] ?? $receivedAt->copy()->addDays($retentionDays)->toDateString();
        $validated['discard_scheduled_at'] = $validated['discard_scheduled_at'] ?? $validated['retention_due_at'];
        $validated['retention_status'] = $this->resolveRetentionStatus($validated['retention_due_at']);

        return $validated;
    }

    private function attachAnalyticalScopeSnapshot(array $clientSubmittedInfo, ?int $departmentId): array
    {
        $productId = data_get($clientSubmittedInfo, 'product_id');

        if (! $productId) {
            return $clientSubmittedInfo;
        }

        $product = Product::query()
            ->with([
                'matrix:id,description',
                'matrix.profiles' => function ($query) use ($departmentId) {
                    $query->with([
                        'type:id,name,department_id',
                        'parameters:id,name,code',
                    ]);

                    if ($departmentId) {
                        $query->whereHas('type', function ($typeQuery) use ($departmentId) {
                            $typeQuery->where('department_id', $departmentId);
                        });
                    }
                },
            ])
            ->find($productId);

        if (! $product) {
            return $clientSubmittedInfo;
        }

        $availableProfiles = $product->matrix?->profiles ?? collect();
        $requestedProfileIds = collect(data_get($clientSubmittedInfo, 'requested_profile_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        $resolvedProfiles = $requestedProfileIds->isNotEmpty()
            ? $availableProfiles->whereIn('id', $requestedProfileIds)->values()
            : $availableProfiles->values();

        $resolvedParameters = $resolvedProfiles
            ->flatMap(fn (Profile $profile) => $profile->parameters->map(function ($parameter) use ($profile) {
                return [
                    'id' => $parameter->id,
                    'name' => $parameter->name,
                    'code' => $parameter->code,
                    'profile_id' => $profile->id,
                    'profile' => $profile->name,
                ];
            }))
            ->groupBy('id')
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'id' => $first['id'],
                    'name' => $first['name'],
                    'code' => $first['code'],
                    'profiles' => $items->pluck('profile')->unique()->values()->all(),
                    'profile_ids' => $items->pluck('profile_id')->unique()->values()->all(),
                ];
            })
            ->sortBy('name')
            ->values();

        return collect($clientSubmittedInfo)
            ->merge([
                'matrix_id' => $product->matrix_id,
                'matrix_description' => $product->matrix?->description,
                'resolved_profile_ids' => $resolvedProfiles->pluck('id')->values()->all(),
                'resolved_profiles' => $resolvedProfiles->map(fn (Profile $profile) => [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'analysis_type' => $profile->type?->name,
                    'department_id' => $profile->type?->department_id,
                    'parameter_count' => $profile->parameters->unique('id')->count(),
                ])->values()->all(),
                'required_parameter_count' => $resolvedParameters->count(),
                'required_parameters' => $resolvedParameters->all(),
            ])
            ->all();
    }

    private function resolveRetentionStatus(?string $retentionDueAt): string
    {
        if (! $retentionDueAt) {
            return 'active';
        }

        $dueDate = Carbon::parse($retentionDueAt);

        if ($dueDate->isPast()) {
            return 'overdue';
        }

        if ($dueDate->lte(now()->addDays(7))) {
            return 'due_soon';
        }

        return 'active';
    }

    private function markPortalRequestAsValidated(?CustomerRequest $portalRequest, VAPSampleEntry $sample): void
    {
        if (! $portalRequest) {
            return;
        }

        $portalRequest->forceFill([
            'status' => 'in_progress',
            'answered' => false,
            'extra_data' => collect($portalRequest->extra_data ?? [])
                ->merge([
                    'validated_sample_entry_id' => $sample->id,
                    'validated_sample_code' => $sample->code,
                    'validated_by_id' => auth()->id(),
                    'validated_by_name' => auth()->user()->name,
                    'validated_at' => now()->toIso8601String(),
                ])
                ->merge([
                    'validated_sample_entry_ids' => collect($portalRequest->extra_data['validated_sample_entry_ids'] ?? [])
                        ->push($sample->id)
                        ->unique()
                        ->values()
                        ->all(),
                ])
                ->when(
                    data_get($sample->client_submitted_info, 'batch_sample_index') !== null,
                    function ($details) use ($sample) {
                        return $details->merge([
                            'validated_batch_indexes' => collect($details->get('validated_batch_indexes', []))
                                ->push((int) data_get($sample->client_submitted_info, 'batch_sample_index'))
                                ->unique()
                                ->values()
                                ->all(),
                        ]);
                    }
                )
                ->all(),
        ])->save();
    }

    private function sendSampleTrackingNotifications(VAPSampleEntry $sample, string $action, array $context = []): void
    {
        $sender = auth()->user();

        if (! $sender instanceof User) {
            return;
        }

        $title = match ($action) {
            'created' => 'Nova amostra registada',
            'status_updated' => 'Estado da amostra atualizado',
            default => 'Amostra atualizada',
        };

        $message = match ($action) {
            'created' => sprintf('A amostra %s foi registada e aguarda validação operacional.', $sample->code ?: $sample->name),
            'status_updated' => sprintf(
                'A amostra %s mudou de %s para %s.',
                $sample->code ?: $sample->name,
                $context['previous_status'] ?? 'estado anterior',
                $sample->status
            ),
            default => sprintf('A amostra %s recebeu uma atualização no fluxo de rastreio.', $sample->code ?: $sample->name),
        };

        $recipients = collect([
            $sample->warehouse,
            $sample->receivedBy,
            $sender,
        ])->filter()->unique(fn ($recipient) => get_class($recipient) . ':' . $recipient->getKey());

        Notification::send($recipients, new SampleTrackingNotification($sample, $title, $message, $sender));
    }

}
