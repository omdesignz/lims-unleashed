<?php

namespace App\Http\Controllers;

use App\Actions\CreateProposal;
use App\Actions\DownloadStaffProposalPdf;
use App\Actions\RecordPublicProposalDecision;
use App\Actions\ReviseProposal;
use App\Actions\SendProposal;
use App\Actions\SetProposalArchived;
use App\Http\Requests\RecordPublicProposalDecisionRequest;
use App\Http\Requests\StoreVAPProposalRequest;
use App\Http\Requests\UpdateVAPProposalRequest;
use App\Http\Resources\ProposalRevisionResource;
use App\Http\Resources\VAPProposalResource;
use App\Http\Resources\VAPProposalTemplateResource;
use App\Models\Customer;
use App\Models\Department;
use App\Models\LabCode;
use App\Models\Matrix;
use App\Models\Parameter;
use App\Models\Standard;
use App\Models\Unit;
use App\Models\VAPProposal;
use App\Models\VAPProposalTemplate;
use App\Models\Warehouse;
use App\Settings\GeneralSettings;
use App\Support\LaboratoryDossierService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class VAPProposalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->string('status')->value() ?: 'all';
        $search = $request->string('search')->value();
        $templateId = $request->integer('template_id') ?: null;
        $period = in_array($request->integer('period'), [7, 30, 90], true)
            ? $request->integer('period')
            : 30;

        $query = VAPProposal::with([
            'customer:id,name,code',
            'department:id,name',
            'user:id,name',
            'template:id,name,category',
        ])
            ->withCount('items')
            ->withSum('items', 'tax_amount')
            ->withSum('items', 'discount_amount')
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($templateId) {
            $query->where('template_id', $templateId);
        }

        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('proposal_no', 'ilike', "%{$search}%")
                    ->orWhere('proposal_year', 'ilike', "%{$search}%")
                    ->orWhereHas('customer', function ($customer) use ($search) {
                        $customer->where('name', 'ilike', "%{$search}%")
                            ->orWhere('code', 'ilike', "%{$search}%");
                    });
            });
        }

        $proposals = $query->paginate(20)->withQueryString()
            ->through(fn (VAPProposal $proposal): array => VAPProposalResource::make($proposal)->resolve($request));

        $statsQuery = VAPProposal::query()
            ->when($templateId, fn ($builder) => $builder->where('template_id', $templateId));

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'pending' => (clone $statsQuery)->where('status', 'PENDING')->count(),
            'accepted' => (clone $statsQuery)->where('status', 'ACCEPTED')->count(),
            'rejected' => (clone $statsQuery)->where('status', 'REJECTED')->count(),
            'expired' => (clone $statsQuery)->where('status', 'EXPIRED')->count(),
            'total_value' => (clone $statsQuery)->where('status', 'ACCEPTED')->sum('total'),
        ];

        return Inertia::render('VAPProposals/Index', [
            'proposals' => $proposals,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'template_id' => $templateId,
                'period' => $period,
            ],
            'stats' => $stats,
            'selectedTemplate' => $templateId
                ? VAPProposalTemplate::query()->select(['id', 'name', 'category'])->find($templateId)
                : null,
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('VAPProposals/Create', [
            'customers' => Customer::active()->get(['id', 'name', 'code']),
            'warehouses' => Warehouse::active()->get(['id', 'name']),
            'departments' => Department::active()->get(['id', 'name']),
            'templates' => VAPProposalTemplate::query()
                ->with('user')
                ->where('is_active', true)
                ->orderBy('name')
                ->get()->map(fn (VAPProposalTemplate $template): array => VAPProposalTemplateResource::make($template)->forAuthoring()->resolve($request)),
            'units' => Unit::all(['id', 'code', 'description']),
            'standards' => Standard::all(['id', 'description', 'code']),
            'nextProposalNo' => $this->generateProposalNumber(),
        ]);
    }

    public function store(StoreVAPProposalRequest $request, CreateProposal $create): RedirectResponse
    {
        $proposal = $create->execute((int) $request->attributes->get('proposal_laboratory_id'), $request->user()->id, $request->validated());

        return redirect()->route('vap-proposals.show', $proposal)
            ->with('success', 'Proposta criada com sucesso. Já pode enviá-la ao cliente.');
    }

    public function show(
        Request $request,
        VAPProposal $proposal,
        GeneralSettings $settings,
        LaboratoryDossierService $laboratoryDossierService
    ) {
        $proposal->load([
            'customer',
            'warehouse',
            'department',
            'user',
            'template.user',
            'items.standard',
            'items.unit',
            'complianceAgreement',
        ]);

        // Get revision history
        $revisions = $proposal->activities()
            ->where(fn ($query) => $query->whereIn('event', ['updated', 'revised'])->orWhere('description', 'revised'))
            ->with('causer')
            ->latest()
            ->get()->map(fn ($revision): array => ProposalRevisionResource::make($revision)->resolve($request));

        return Inertia::render('VAPProposals/Show', [
            'proposal' => VAPProposalResource::make($proposal)->resolve($request),
            'revisions' => $revisions,
            'parsedTemplateContent' => $proposal->template?->content
                ? VAPProposalTemplate::parseContent($proposal->template->content, $proposal, $settings)
                : null,
            'canSend' => $request->user()->can('edit_proposals') && in_array($proposal->status, ['PENDING', 'REVISED'], true),
            'canRevise' => $request->user()->can('edit_proposals') && in_array($proposal->status, ['PENDING', 'SENT', 'VIEWED', 'REJECTED'], true),
            'laboratoryDossier' => $proposal->status === 'ACCEPTED'
                ? $laboratoryDossierService->summarize($proposal, auth()->user())
                : null,
        ]);
    }

    public function edit(Request $request, VAPProposal $proposal)
    {
        if (! in_array($proposal->status, ['PENDING', 'SENT', 'VIEWED', 'REJECTED'])) {
            return redirect()->route('vap-proposals.show', $proposal)
                ->with('error', 'A proposta não pode ser editada no estado actual.');
        }

        $proposal->load(['customer', 'warehouse', 'department', 'user', 'items.standard', 'items.unit']);

        return Inertia::render('VAPProposals/Edit', [
            'proposal' => VAPProposalResource::make($proposal)->resolve($request),
            'customers' => Customer::active()->get(['id', 'name', 'code']),
            'warehouses' => Warehouse::active()->get(['id', 'name']),
            'departments' => Department::active()->get(['id', 'name']),
            'templates' => VAPProposalTemplate::query()
                ->with('user')
                ->where(function ($query) use ($proposal): void {
                    $query->where('is_active', true);

                    if ($proposal->template_id !== null) {
                        $query->orWhere('id', $proposal->template_id);
                    }
                })
                ->orderBy('name')
                ->get()->map(fn (VAPProposalTemplate $template): array => VAPProposalTemplateResource::make($template)->forAuthoring()->resolve($request)),
            'units' => Unit::all(['id', 'code', 'description']),
            'standards' => Standard::all(['id', 'description', 'code']),
        ]);
    }

    public function update(UpdateVAPProposalRequest $request, VAPProposal $proposal, ReviseProposal $revise): RedirectResponse
    {
        $revised = $revise->execute((int) $request->attributes->get('proposal_laboratory_id'), $request->user()->id, $proposal, $request->validated());

        return redirect()->route('vap-proposals.show', $revised)->with('success', 'Proposta revista com sucesso.');
    }

    public function send(Request $request, VAPProposal $proposal, SendProposal $send): RedirectResponse
    {
        $send->execute((int) $request->attributes->get('proposal_laboratory_id'), $request->user()->id, $proposal);

        return back()->with('success', 'Proposta marcada como enviada e pronta para acompanhamento.');
    }

    public function generatePdf(Request $request, VAPProposal $proposal, DownloadStaffProposalPdf $download): Response
    {
        $rendered = $download->execute((int) $request->attributes->get('proposal_laboratory_id'), $request->user()->id, $proposal);

        return response($rendered['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$rendered['filename'].'"',
            'X-Report-Studio-Renderer' => $rendered['renderer'],
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function destroy(Request $request, VAPProposal $proposal, SetProposalArchived $archive): RedirectResponse
    {
        $archive->execute((int) $request->attributes->get('proposal_laboratory_id'), $request->user()->id, [$proposal->id], true);

        return redirect()->route('vap-proposals.index')
            ->with('success', 'Proposta eliminada com sucesso.');
    }

    public function accept(RecordPublicProposalDecisionRequest $request, VAPProposal $proposal, RecordPublicProposalDecision $decision): JsonResponse
    {
        return $this->recordPublicDecision($request, $proposal, $decision, true);
    }

    public function reject(RecordPublicProposalDecisionRequest $request, VAPProposal $proposal, RecordPublicProposalDecision $decision): JsonResponse
    {
        return $this->recordPublicDecision($request, $proposal, $decision, false);
    }

    private function recordPublicDecision(RecordPublicProposalDecisionRequest $request, VAPProposal $proposal, RecordPublicProposalDecision $decision, bool $accepted): JsonResponse
    {
        try {
            $recorded = $decision->execute($proposal, $accepted, $request->validated(), $request->ip());
        } catch (HttpException $exception) {
            if (! in_array($exception->getStatusCode(), [400, 409], true)) {
                throw $exception;
            }

            return response()->json(['success' => false, 'message' => $exception->getMessage()], $exception->getStatusCode());
        } catch (ModelNotFoundException|ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            if (app()->environment('testing')) {
                throw $exception;
            }

            return response()->json([
                'success' => false,
                'message' => $accepted ? 'Não foi possível aceitar a proposta.' : 'Não foi possível rejeitar a proposta.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $accepted ? 'Proposta aceite com sucesso.' : 'Proposta rejeitada com sucesso.',
            'redirect' => route('vap-proposals.public.thankyou', $recorded->unique_hash),
        ]);
    }

    public function getWarehouse(Request $request)
    {
        $query = $request->string('q')->value();
        $customerId = $request->integer('customer_id') ?: null;

        $warehouses = Warehouse::query()
            ->when($customerId, function ($builder) use ($customerId): void {
                $builder->where('customer_id', $customerId);
            })
            ->when($query, function ($builder) use ($query): void {
                $builder->where(function ($nested) use ($query): void {
                    $nested->where('address', 'ilike', "%{$query}%")
                        ->orWhere('name', 'ilike', "%{$query}%");
                });
            })
            ->orderBy('address')
            ->limit(20)
            ->get(['id', 'name', 'address'])
            ->map(function (Warehouse $warehouse): array {
                $label = $warehouse->address ?: ($warehouse->name ?: "Armazém #{$warehouse->id}");

                return [
                    'id' => $warehouse->id,
                    'value' => $warehouse->id,
                    'label' => $label,
                    'name' => $warehouse->name,
                    'address' => $warehouse->address,
                ];
            })
            ->values();

        return response()->json($warehouses);
    }

    public function getProposal(Request $request)
    {
        $query = $request->string('q')->value();

        return response()->json(
            VAPProposal::query()
                ->when($query, function ($builder) use ($query): void {
                    $builder->where(function ($nested) use ($query): void {
                        $nested->where('proposal_no', 'ilike', "%{$query}%")
                            ->orWhere('service_location', 'ilike', "%{$query}%")
                            ->orWhere('status', 'ilike', "%{$query}%");
                    });
                })
                ->latest('id')
                ->limit(20)
                ->get(['id', 'proposal_no', 'proposal_year', 'service_location', 'status', 'created_at'])
                ->map(function (VAPProposal $proposal): array {
                    $label = trim(implode(' · ', array_filter([
                        $proposal->proposal_number,
                        $proposal->service_location,
                    ])));

                    return [
                        'id' => $proposal->id,
                        'value' => $proposal->id,
                        'label' => $label !== '' ? $label : "Proposta #{$proposal->id}",
                        'proposal_no' => $proposal->proposal_no,
                        'proposal_number' => $proposal->proposal_number,
                        'proposal_year' => $proposal->proposal_year,
                        'service_location' => $proposal->service_location,
                        'status' => $proposal->status,
                        'created_at' => optional($proposal->created_at)->format('d/m/Y'),
                    ];
                })
                ->values()
        );
    }

    public function getLabCode(Request $request)
    {
        $query = $request->string('q')->value();

        return response()->json(
            LabCode::query()
                ->forLaboratory((int) $request->attributes->get('proposal_laboratory_id', 0))
                ->when($query, function ($builder) use ($query): void {
                    $builder->where('code', 'ilike', "%{$query}%");
                })
                ->orderByDesc('id')
                ->limit(20)
                ->get(['id', 'code'])
                ->map(fn (LabCode $labCode): array => [
                    'id' => $labCode->id,
                    'value' => $labCode->id,
                    'label' => $labCode->code ?: "Código #{$labCode->id}",
                    'code' => $labCode->code,
                ])
                ->values()
        );
    }

    public function getMatrix(Request $request)
    {
        $query = $request->string('q')->value();

        $matrixes = Matrix::query()
            ->when($query, function ($builder) use ($query): void {
                $builder->where(function ($nested) use ($query): void {
                    $nested->where('description', 'ilike', "%{$query}%")
                        ->orWhere('code', 'ilike', "%{$query}%");
                });
            })
            ->orderBy('description')
            ->limit(20)
            ->get(['id', 'code', 'description', 'fixed_price', 'tax_id', 'charge_tax', 'tax_percentage', 'exemption_id', 'exemption_code', 'withhold_tax'])
            ->map(fn (Matrix $matrix): array => [
                'id' => $matrix->id,
                'value' => $matrix->id,
                'label' => $matrix->description ?: ($matrix->code ?: "Matriz #{$matrix->id}"),
                'code' => $matrix->code,
                'description' => $matrix->description,
                'price' => (float) $matrix->fixed_price,
                'tax_id' => $matrix->tax_id,
                'charge_tax' => (bool) $matrix->charge_tax,
                'tax_percentage' => (float) $matrix->tax_percentage,
                'exemption_id' => $matrix->exemption_id,
                'exemption_code' => $matrix->exemption_code,
                'withhold_tax' => (bool) $matrix->withhold_tax,
            ])
            ->values();

        return response()->json($matrixes);
    }

    public function getParameter(Request $request)
    {
        $query = $request->string('q')->value();

        $parameters = Parameter::query()
            ->where('active', true)
            ->when($query, function ($builder) use ($query): void {
                $builder->where(function ($nested) use ($query): void {
                    $nested->where('name', 'ilike', "%{$query}%")
                        ->orWhere('code', 'ilike', "%{$query}%");
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'code', 'price', 'tax_id', 'charge_tax', 'tax_percentage', 'exemption_id', 'exemption_code', 'withhold_tax'])
            ->map(fn (Parameter $parameter): array => [
                'id' => $parameter->id,
                'value' => $parameter->id,
                'label' => $parameter->name ?: ($parameter->code ?: "Parâmetro #{$parameter->id}"),
                'name' => $parameter->name,
                'code' => $parameter->code,
                'price' => (float) $parameter->price,
                'tax_id' => $parameter->tax_id,
                'charge_tax' => (bool) $parameter->charge_tax,
                'tax_percentage' => (float) $parameter->tax_percentage,
                'exemption_id' => $parameter->exemption_id,
                'exemption_code' => $parameter->exemption_code,
                'withhold_tax' => (bool) $parameter->withhold_tax,
            ])
            ->values();

        return response()->json($parameters);
    }

    public function getLabCodeParameters(Request $request)
    {
        $codeId = $request->integer('code_id');
        $useMatrixPrice = $request->boolean('use_matrix_price', true);

        if (! $codeId) {
            return response()->json([]);
        }

        $labCode = LabCode::query()
            ->forLaboratory((int) $request->attributes->get('proposal_laboratory_id', 0))
            ->with('collection.product.matrix')
            ->find($codeId);

        if (! $labCode) {
            return response()->json([]);
        }

        $matrix = $labCode->collection?->product?->matrix;

        if (! $matrix) {
            return response()->json([]);
        }

        if ($useMatrixPrice) {
            return response()->json([
                $this->matrixOptionPayload($matrix),
            ]);
        }

        $parameterIds = DB::table('parameter_profile')
            ->join('profiles', 'profiles.id', '=', 'parameter_profile.profile_id')
            ->join('matrix_profile', 'matrix_profile.profile_id', '=', 'profiles.id')
            ->where('matrix_profile.matrix_id', $matrix->id)
            ->whereNull('parameter_profile.deleted_at')
            ->whereNull('matrix_profile.deleted_at')
            ->whereNull('profiles.deleted_at')
            ->select('parameter_profile.parameter_id');

        return response()->json(
            Parameter::query()
                ->whereIn('id', $parameterIds)
                ->where('active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (Parameter $parameter) => $this->parameterOptionPayload($parameter))
                ->values()
        );
    }

    private function generateProposalNumber(): string
    {
        $year = date('Y');
        $lastProposal = VAPProposal::where('proposal_year', $year)
            ->orderBy('seq', 'desc')
            ->first();

        $nextNumber = $lastProposal ? $lastProposal->seq + 1 : 1;

        return str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, mixed>
     */
    private function matrixOptionPayload(Matrix $matrix): array
    {
        $label = $matrix->description ?: ($matrix->code ?: "Matriz #{$matrix->id}");

        return [
            'id' => $matrix->id,
            'value' => $matrix->id,
            'label' => $label,
            'item_id' => $matrix->id,
            'itemable_type' => Matrix::class,
            'itemable_id' => $matrix->id,
            'item_description' => $label,
            'name' => $label,
            'description' => $label,
            'price' => (float) $matrix->fixed_price,
            'unit_id' => Unit::query()->value('id'),
            'qty' => 1,
            'tax_percentage' => (float) $matrix->tax_percentage,
            'charge_tax' => (bool) $matrix->charge_tax,
            'tax_id' => $matrix->tax_id,
            'exemption_id' => $matrix->exemption_id,
            'exemption_code' => $matrix->exemption_code,
            'withhold_tax' => (bool) $matrix->withhold_tax,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parameterOptionPayload(Parameter $parameter): array
    {
        $label = $parameter->name ?: ($parameter->code ?: "Parâmetro #{$parameter->id}");

        return [
            'id' => $parameter->id,
            'value' => $parameter->id,
            'label' => $label,
            'item_id' => $parameter->id,
            'itemable_type' => Parameter::class,
            'itemable_id' => $parameter->id,
            'item_description' => $label,
            'name' => $label,
            'description' => $parameter->description ?: $label,
            'price' => (float) $parameter->price,
            'unit_id' => Unit::query()->value('id'),
            'qty' => 1,
            'tax_percentage' => (float) $parameter->tax_percentage,
            'charge_tax' => (bool) $parameter->charge_tax,
            'tax_id' => $parameter->tax_id,
            'exemption_id' => $parameter->exemption_id,
            'exemption_code' => $parameter->exemption_code,
            'withhold_tax' => (bool) $parameter->withhold_tax,
        ];
    }
}
