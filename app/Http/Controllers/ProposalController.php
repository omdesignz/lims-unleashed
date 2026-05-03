<?php

namespace App\Http\Controllers;

use App\Enums\Proposals\ProposalTrackingStatus;
use App\Http\Requests\ProposalRequest;
use App\Http\Resources\ProposalResource;
use App\Models\CollectionProduct;
use App\Models\DiscountCategory;
use App\Models\LabCode;
use App\Models\Proposal;
use App\Models\ProposalComplianceAgreement;
use App\Models\ProposalComplianceAgreementLog;
use App\Models\ProposalItem;
use App\Models\ProposalTemplate;
use App\Notifications\ProposalComplianceAcknowledged;
use App\Notifications\ProposalSentNotification;
use App\Settings\GeneralSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use NumberToWords\NumberToWords;
use Spatie\QueryBuilder\QueryBuilder;
use PDF;

class ProposalController extends Controller
{
    public function index()
    {
        // abort_if( !auth()->user()->can('view_proposals'), 403, '');

        $records = QueryBuilder::for(Proposal::class)
                                ->with('complianceAgreement', 'customer', 'warehouse')
                                ->withCount('activities as revision_count')
                                ->withMax('activities as last_revision_at', 'created_at')
                                ->allowedFilters(Proposal::getAllowedFilters())
                                ->allowedSorts(Proposal::getAllowedSorts())
                                ->paginate(request()->query('per_page', 10));


        return Inertia::render('Proposals/Index', [
            'record' => ProposalResource::collection($records),
            'initialFilters' => request()->query('filter', ['proposal_no' => '', 'service_location' => '', 'created_at' => '', 'globalFilter' => '']),
            'initialSortField' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? ltrim(request()->query('sort'), '-') : request()->query('sort')) : '',
            'initialSortDirection' => request()->query('sort') ? (request()->query('sort')[0] === '-' ? 'desc' : 'asc') : 'asc',
            'initialIncludes' => request()->query('includes', []),
            'initialGlobalFilter' => request()->query('globalFilter', ''),
            'per_page' => request()->query('per_page', 2),
            'slideOverEdit' => false,  
            'trashedFilter' => true,
            'trashedOptions' => Proposal::getTrashedOptions(),
            'fields' => Proposal::getColumns(),
            'model' => Proposal::MENU_NAME,
            'abilities' => method_exists(Proposal::class, 'getAbilities') ? collect(Proposal::ABILITIES)->map(function($item){
                return $item . '_' . Proposal::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . Proposal::MENU_NAME;
            }),                           
            'query' => request()->only(['search', 'trashed', 'date', 'orderBy'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     */
    public function create()
    {
        // abort_if( !auth()->user()->can('add_proposals'), 403, '');

        return Inertia::render('Proposals/Create', [
            'templates' => ProposalTemplate::select('id', 'name', 'content')->get(),
            'discount_categories' => collect(DiscountCategory::all())->map(function($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->symbol,
                ];
            })
        ]);
    }


    //
    public function store(ProposalRequest $request)
    {
        // Send proposal via email
        // $proposal->warehouse->notify(new ProposalSentNotification($proposal));

        DB::transaction(function () use ($request): void {
            $proposal = Proposal::create($request->safe()->except(['items']));

            foreach(collect($request->safe()->only(['items']))->first() as $item) {

                $obj = new ProposalItem;

                $obj->proposal_id = $proposal->id;
                $obj->item_id = $item['item_id'];
                $obj->item_description = $item['item_description'];
                $obj->exemption_id = $item['exemption_id'];
                $obj->exemption_code = $item['exemption_code'];
                $obj->discount_id = $item['discount_id'];
                $obj->unit_id = $item['unit_id'];
                $obj->standard_id = $item['standard_id'];
                $obj->tax_id = $item['tax_id'];
                $obj->qty = $item['qty'];
                $obj->unit_price = $item['unit_price'];
                $obj->total = $item['total'];
                $obj->charge_tax = $item['charge_tax'];
                $obj->withhold_tax = $item['withhold_tax'];
                $obj->global_discount_portion_percentage = $item['global_discount_portion_percentage'];
                $obj->global_discount_amount = $item['global_discount_amount'];
                $obj->tax_amount = $item['tax_amount'];
                $obj->tax_percentage = $item['tax_percentage'];
                $obj->discount_amount = $item['discount_amount'];
                $obj->discount_percentage = $item['discount_percentage'];
                $obj->obs = $item['obs'];

                $obj->save();

            }

            $proposal->complianceAgreement()->create([
                'confidentiality' => $request->confidentiality ?? false,
                'impartiality' => $request->impartiality ?? false,
                'nondisclosure' => $request->nondisclosure ?? false,
                'acknowledged_at' => null,
                'client_ip' => null,
            ]);

            $proposal->warehouse->notify(new ProposalSentNotification($proposal));

        });
    
        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ]
        ]);
    }

            /**
     * Display the specified resource.
     *
     */
    public function show($id)
    {
        $proposal = Proposal::query()
            ->with('complianceAgreement', 'customer', 'warehouse', 'template', 'items', 'user', 'discount_category', 'department')
            ->withCount('activities as revision_count')
            ->withMax('activities as last_revision_at', 'created_at')
            ->find($id);

        $items = collect($proposal?->items ?? []);
        $taxableItems = $items->filter(fn ($item) => (float) ($item->tax_amount ?? 0) > 0)->count();
        $discountedItems = $items->filter(fn ($item) => (float) ($item->discount_amount ?? 0) > 0)->count();
        $withholdingItems = $items->filter(fn ($item) => (bool) ($item->withhold_tax ?? false))->count();
        $plainItems = $items->filter(fn ($item) => (float) ($item->tax_amount ?? 0) <= 0
            && (float) ($item->discount_amount ?? 0) <= 0
            && ! (bool) ($item->withhold_tax ?? false))->count();
        $daysUntilExpiry = $proposal?->expiry_date
            ? max((int) now()->diffInDays($proposal->expiry_date, false), 0)
            : 0;

        return Inertia::render('Proposals/Show', [
            'record' => ProposalResource::make($proposal),
            'charts' => [
                'financial_breakdown' => [
                    'labels' => ['Subtotal', 'Desconto global', 'Imposto', 'Retenção', 'Total'],
                    'series' => [
                        (float) ($proposal?->sub_total ?? 0),
                        (float) ($proposal?->global_discount_amount ?? 0),
                        (float) $items->sum(fn ($item) => (float) ($item->tax_amount ?? 0)),
                        (float) ($proposal?->withholding_tax_amount ?? 0),
                        (float) ($proposal?->total ?? 0),
                    ],
                ],
                'item_composition' => [
                    'labels' => ['Itens tributáveis', 'Itens com desconto', 'Itens com retenção', 'Itens sem ajuste'],
                    'series' => [
                        $taxableItems,
                        $discountedItems,
                        $withholdingItems,
                        $plainItems,
                    ],
                ],
                'workflow_summary' => [
                    'labels' => ['Revisões', 'Dias tolerância', 'Itens', 'Dias até expirar'],
                    'series' => [
                        (int) ($proposal?->revision_count ?? 0),
                        (int) ($proposal?->tolerance_days ?? 0),
                        (int) $items->count(),
                        $daysUntilExpiry,
                    ],
                ],
            ],
        ]);
    }

    public function edit($id)
    {
        // abort_if( !auth()->user()->can('edit_proposals'), 403, '');

        // Find the record
        $record = Proposal::with('complianceAgreement', 'customer', 'warehouse', 'template', 'items', 'user', 'discount_category', 'department')->findOrFail($id);

        // Return Inertia View with record data
        return Inertia::render('Proposals/Edit', [
            'record' => [
                'id' => $record->id,
                'proposal_no' => $record->proposal_no,
                'service_location' => $record->service_location,
                'customer_id' => [
                    'value' => $record->customer_id,
                    'label' => $record->customer->name,
                ],
                'warehouse_id' => [
                    'value' => $record->warehouse_id,
                    'label' => $record->warehouse->address,
                ],
                'department_id' => [
                    'value' => $record->department_id,
                    'label' => $record->department->name,
                ],
                'template_id' => [
                    'value' => $record->template_id,
                    'label' => $record->template->name,
                ],
                'discount_type' => [
                    'value' => $record->discount_type,
                    'label' => $record->discount_category?->name,
                ],
                'status' => $record->status,
                'details' => json_decode($record->details),
                'expiry_date' => $record->expiry_date,
                'is_original' => $record->is_original,
                'discount_type' => $record->discount_type,
                'file_path' => $record->file_path,
                'sub_total' => $record->sub_total,
                'total' => $record->total,
                'unique_hash' => $record->unique_hash,
                'use_matrix_price' => $record->use_matrix_price,
                'withholding_tax_amount' => $record->withholding_tax_amount,
                'withholding_tax_percentage' => $record->withholding_tax_percentage,
                'global_discount_amount' => $record->global_discount_amount,
                'global_discount_percentage' => $record->global_discount_percentage,
                'withhold_tax' => $record->withhold_tax,
                'converted_to_invoice' => $record->converted_to_invoice,
                'obs' => $record->obs,
                'tolerance_days' => $record->tolerance_days,
                'items' => collect($record->items)->map(function($item) {
                    return [
                        'id' => $item->id ?? null,
                        'proposal_id' => $item->proposal_id ?? null,
                        'unit_id' => [
                            'value' => $item->unit_id,
                            'label' => $item->unit->code,
                        ],
                        'standard_id' => [
                            'value' => $item->standard_id,
                            'label' => $item->standard->code,
                        ],
                        'exemption_id' => $item->exemption_id ?? null,
                        'exemption_code' => $item->exemption_code ?? null,
                        'discount_id' => $item->discount_id,
                        'item_id' => [
                            'value' => $item->item_id,
                            'label' => $item->item_description,
                            'price'=> $item->unit_price + $item->discount_amount,
                            'tax_id'=> $item->tax_id,
                            'charge_tax'=> $item->charge_tax,
                            'tax_percentage'=> $item->tax_percentage,
                            'exemption_id'=> $item->exemption_id,
                            'exemption_code'=> $item->exemption_code,
                        ],
                        'item_description' => $item->item_description,
                        'itemable_id' => [
                            'value' => $item->itemable_id ?? '',
                            'label' => $item->itemable_type,
                        ],
                        'itemable_type' => $item->itemable_type,
                        'qty' => $item->qty ?? 1,
                        'unit_price' => $item->unit_price,
                        'tax_id' => $item->tax_id,
                        'total' => $item->total,
                        'discount_percentage' => $item->discount_percentage,
                        'discount_amount' => $item->discount_amount,
                        'tax_percentage' => $item->tax_percentage,
                        'tax_amount' => $item->tax_amount,
                        'charge_tax' => $item->charge_tax,
                        'withhold_tax' => $item->withhold_tax,
                        'global_discount_portion_percentage' => $item->global_discount_portion_percentage,
                        'global_discount_amount' => $item->global_discount_amount,
                        'obs' => $item->obs,
                    ];
                })
            ],
            'templates' => ProposalTemplate::select('id', 'name', 'content')->get(),
            'discount_categories' => collect(DiscountCategory::all())->map(function($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->symbol,
                ];
            })
        ]);
    }

    public function update(ProposalRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_proposals'), 403, '');

        DB::transaction(function () use ($request, $id): void {

            tap(Proposal::findOrFail($id), function($record) use($request) {

                $record->update($request->validated());

                ProposalItem::where('proposal_id', $record->id)->forcedelete();


                foreach(collect($request->safe()->only(['items']))->first() as $item) {

                    $obj = new ProposalItem;
    
                    $obj->proposal_id = $record->id;
                    $obj->item_id = $item['item_id'];
                    $obj->item_description = $item['item_description'];
                    $obj->exemption_id = $item['exemption_id'];
                    $obj->exemption_code = $item['exemption_code'];
                    $obj->discount_id = $item['discount_id'];
                    $obj->unit_id = $item['unit_id'];
                    $obj->tax_id = $item['tax_id'];
                    $obj->qty = $item['qty'];
                    $obj->unit_price = $item['unit_price'];
                    $obj->standard_id = $item['standard_id'];
                    $obj->total = $item['total'];
                    $obj->charge_tax = $item['charge_tax'];
                    $obj->tax_amount = $item['tax_amount'];
                    $obj->tax_percentage = $item['tax_percentage'];
                    $obj->discount_amount = $item['discount_amount'];
                    $obj->discount_percentage = $item['discount_percentage'];
                    $obj->withhold_tax = $item['withhold_tax'];
                    $obj->global_discount_portion_percentage = $item['global_discount_portion_percentage'];
                    $obj->global_discount_amount = $item['global_discount_amount'];
                    $obj->obs = $item['obs'];
    
                    $obj->save();


                    if($record->itemable_id) {

                        CollectionProduct::findOrFail(LabCode::findOrFail($record->itemable_id)->collection_id)->proposal_item()->save($record);
        
                    }
    
                }
    
            });

        });

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ]
        ]);
    }

    public function destroy()
    {
        // abort_if( !auth()->user()->can('delete_proposals'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (Proposal::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->delete();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ]
        ]);
    }

    public function restore()
    {
        // abort_if( !auth()->user()->can('restore_proposals'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (Proposal::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }

    public function accept(Request $request, Proposal $proposal)
    {
        // Validate acknowledgment
        $request->validate([
            'confidentiality' => 'required|boolean',
            'impartiality' => 'required|boolean',
            'nondisclosure' => 'required|boolean',
        ]);

        $complianceData = [
            'confidentiality' => $request->confidentiality,
            'impartiality' => $request->impartiality,
            'nondisclosure' => $request->nondisclosure,
            'acknowledged_at' => now(),
            'client_ip' => $request->ip(),
        ];
    
        // Log the current agreement to the logs table
        ProposalComplianceAgreementLog::create(array_merge($complianceData, [
            'proposal_id' => $proposal->id,
        ]));
    
        // Update or create the current agreement
        ProposalComplianceAgreement::updateOrCreate(
            ['proposal_id' => $proposal->id],
            $complianceData
        );

        // Update proposal status
        $proposal->update([
            'status' => ProposalTrackingStatus::ACCEPTED,
        ]);

        $proposal->warehouse?->notify(new ProposalComplianceAcknowledged($proposal));

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ]
        ]);
    }

    public function reject(Request $request, Proposal $proposal)
    {
        // Validate acknowledgment
        $request->validate([
            'confidentiality' => 'required|boolean',
            'impartiality' => 'required|boolean',
            'nondisclosure' => 'required|boolean',
        ]);

        $complianceData = [
            'confidentiality' => $request->confidentiality,
            'impartiality' => $request->impartiality,
            'nondisclosure' => $request->nondisclosure,
            'acknowledged_at' => now(),
            'client_ip' => $request->ip(),
        ];
    
        // Log the current agreement to the logs table
        ProposalComplianceAgreementLog::create(array_merge($complianceData, [
            'proposal_id' => $proposal->id,
        ]));
    
        // Update or create the current agreement
        ProposalComplianceAgreement::updateOrCreate(
            ['proposal_id' => $proposal->id],
            $complianceData
        );

        // Update proposal status
        $proposal->update([
            'status' => ProposalTrackingStatus::REJECTED,
        ]);

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ]
        ]);
    }

    public function getProposal() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("proposals")
                ->select('proposals.*')
                ->where('proposal_no','LIKE',"%$search%")
                ->orWhere('service_location','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

    public function getPDF() {
        abort_if( !auth()->user()->can('view_proposals'), 403, '');

        $ntw = new NumberToWords();
        $nTrans = $ntw->getNumberTransformer('pt_BR');
        $cTrans = $ntw->getCurrencyTransformer('pt_BR');
        $model = Proposal::with('items', 'user', 'customer', 'warehouse')->find(request()->id);
        //dd($model);
        
        $pdf = PDF::loadView('PDFs.proposal', [
            'model' => $model,
            'settings' => app(GeneralSettings::class),
        ], [], [
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
            'margin_header' => 25,
            'margin_footer' => 25,
            'title'=> 'Proposta Nº ' . $model->proposal_no,
            'author'=> $model->user->name,
            'watermark'            => 'PAGO',
            'show_watermark'       => false,
            'display_mode' => 'fullpage',
            'watermark_text_alpha' => 0.1,
            'showBarcodeNumbers' => FALSE
        ]);

        if (request()->q) {
                activity()->log('baixou a Proposta Nº ' . $model->proposal_no);

                return $pdf->download($model->proposal_no . '.pdf');
        }  if (!request()->q ) {
                activity()
                ->causedBy(auth()->user()->id)
                ->log('visualizou a Proposta Nº ' . $model->proposal_no );
                return  $pdf->stream($model->proposal_no . '.pdf');
        }
    }
}
