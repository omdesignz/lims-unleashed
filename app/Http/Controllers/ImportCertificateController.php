<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ImportCertificateRequest;
use App\Http\Requests\InvoiceRequest;
use App\Http\Resources\ImportCertificateItemResource;
use App\Http\Resources\ImportCertificateResource;
use App\Models\DiscountCategory;
use Illuminate\Support\Facades\DB;
use App\Models\ImportCertificate;
use App\Models\ImportCertificateItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Settings\GeneralSettings;
use Inertia\Inertia;
use PDF;

class ImportCertificateController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        abort_if( !auth()->user()->can('view_import_certificates'), 403, '');

        return Inertia::render('ImportCertificates/Index', [
            'record' => ImportCertificateResource::collection(
                ImportCertificate::query()
                            ->with('destination_country', 'trans', 'currency', 'importer', 'importer_warehouse', 'exporter', 'exporter_warehouse', 'user', 'currency')
                            ->when(request()->input('search'), function($query, $search){
                                $query->where('cert_no', 'like', "%{$search}%");
                            })
                            ->when(request()->input('filter'), function($query, $filter){
                                if($filter = 'trashed'){
                                    $query->withTrashed();
                                }
                            })
                            ->latest()
                            ->paginate(10)
                            ->withQueryString()
                        ),
            'slideOverEdit' => false,            
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.import_certificates.date'),
                    'value' => 'date'
                ],
                [
                    'name' => trans('gestlab.general.labels.import_certificates.cert_no'),
                    'value' => 'cert_no'
                ],
                [
                    'name' => trans('gestlab.general.labels.import_certificates.importer_id'),
                    'value' => 'importer'
                ],
                [
                    'name' => trans('gestlab.general.labels.import_certificates.importer_warehouse_id'),
                    'value' => 'importer_warehouse'
                ],
                [
                    'name' => trans('gestlab.general.labels.import_certificates.authorized_personnel'),
                    'value' => 'authorized_personnel'
                ],
            ],
            'model' => ImportCertificate::MENU_NAME,
            'abilities' => method_exists(ImportCertificate::class, 'getAbilities') ? collect(ImportCertificate::ABILITIES)->map(function($item){
                return $item . '_' . ImportCertificate::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function($item){
                return $item . '_' . ImportCertificate::MENU_NAME;
            }),                           
            'query' => request()->only(['search', 'trashed', 'importer_warehouse_id'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     */
    public function create()
    {
        abort_if( !auth()->user()->can('add_import_certificates'), 403, '');

        return Inertia::render('ImportCertificates/Create', [
            
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(ImportCertificateRequest $request)
    {
        abort_if( !auth()->user()->can('add_import_certificates'), 403, '');

        // dd(collect($request->safe()->only(['items']))->first()); 


        DB::transaction(function () use ($request): void {
            $certificate = ImportCertificate::create($request->safe()->except(['items']));

            foreach(collect($request->safe()->only(['items']))->first() as $item) {

                $obj = new ImportCertificateItem;

                $obj->certificate_id = $certificate->id;
                $obj->product_id = $item['product_id'];
                $obj->qty = $item['qty'];
                $obj->origin = $item['origin'];
                $obj->validity = $item['validity'];
                $obj->lot = $item['lot'];
                $obj->bl_no = $item['bl_no'];


                $obj->save();

            }
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
        abort_if( !auth()->user()->can('view_import_certificates'), 403, '');

        // Find the record
        $record = ImportCertificate::with('items.product', 'destination_country', 'trans', 'currency', 'importer', 'importer_warehouse', 'exporter', 'exporter_warehouse', 'user', 'currency')->findOrFail($id);
        
        // Return Inertia View with record data 
        return Inertia::render('ImportCertificates/Show', [
            'record' => ImportCertificateResource::make($record)
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     */
    public function edit($id)
    {
        abort_if( !auth()->user()->can('edit_import_certificates'), 403, '');

        // Find the record
        $record = ImportCertificate::with('items.product', 'destination_country', 'trans', 'currency', 'importer', 'importer_warehouse', 'exporter', 'exporter_warehouse', 'user', 'currency')->findOrFail($id);


        // Return Inertia View with record data 
        return Inertia::render('ImportCertificates/Edit', [

            'record' => ImportCertificateResource::make($record)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(ImportCertificateRequest $request, $id)
    {
        abort_if( !auth()->user()->can('edit_import_certificates'), 403, '');

        // dd(request()->all());

        DB::transaction(function () use ($request, $id): void {

            $record = tap(ImportCertificate::findOrFail($id), function($record) use($request) {

                $record->update($request->safe()->except(['items']));

                ImportCertificateItem::where('certificate_id', $record->id)->forcedelete();

                foreach(collect($request->safe()->only(['items']))->first() as $item) {

                    $obj = new ImportCertificateItem;

                    $obj->certificate_id = $record->id;
                    $obj->product_id = $item['product_id'];
                    $obj->qty = $item['qty'];
                    $obj->origin = $item['origin'];
                    $obj->validity = $item['validity'];
                    $obj->lot = $item['lot'];
                    $obj->bl_no = $item['bl_no'];


                    $obj->save();

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

    /**
     * Remove the specified resource from storage.
     *
     */
    public function destroy()
    {
        abort_if( !auth()->user()->can('delete_import_certificates'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and delete the record
        foreach (ImportCertificate::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->delete();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ]
        ]);
    }

    /**
     * restore the specified resource from storage.
     *
     */
    public function restore()
    {
        abort_if( !auth()->user()->can('restore_import_certificates'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array']
        ]);
        // Find and restore the record
        foreach (ImportCertificate::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

       return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ]
       ]);
    }


    public function getInvoice() {
        $data = [];

        if(request()->has('q')){
            $search = request()->q;
            
            $data = DB::table("invoices")
                ->select('invoices.*')
                ->where('inv_no','LIKE',"%$search%")
                ->get();
        }

        return response()->json($data);
    }

    public function getPDF() {
        abort_if( !auth()->user()->can('view_import_certificates'), 403, '');

        $app_name = app(GeneralSettings::class)->app_name;
        $app_validation_number = app(GeneralSettings::class)->app_agt_validation_number;
        $model = ImportCertificate::with('items.product', 'destination_country', 'trans', 'currency', 'importer', 'importer_warehouse', 'exporter', 'exporter_warehouse', 'user', 'currency')->find(request()->id);
        //dd($model);
        
        $pdf = PDF::loadView('PDFs.import_certificate', [
            'model' => $model,
            'settings' => app(GeneralSettings::class),
        ], [], [
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
            'margin_header' => 25,
            'margin_footer' => 25,
            'title'=> 'Fitosanitário Para Importação Nº ' . $model->cert_no,
            'author'=> $model->user->name,
            'watermark'            => 'PAGO',
            'show_watermark'       => false,
            'display_mode' => 'fullpage',
            'watermark_text_alpha' => 0.1,
            'showBarcodeNumbers' => FALSE
        ]);

        if (request()->q) {
                 activity()->log('baixou o Fitosanitário Para Importação Nº ' . $model->cert_no);

                return $pdf->download($model->cert_no . '.pdf');
        }  if (!request()->q ) {
                activity()
                ->causedBy(auth()->user()->id)
                ->log('visualizou o Fitosanitário Para Importação Nº ' . $model->cert_no );
                return  $pdf->stream($model->cert_no . '.pdf');
        }
        
    }

    public function getIssueInvoiceModal()
    {
        abort_if( !auth()->user()->can('edit_quotes'), 403, '');

        return Inertia::modal('ImportCertificates/issue-invoice-modal', [
            'record' => ImportCertificateResource::make(
                ImportCertificate::with('importer', 'importer_warehouse')
                            ->findOrFail(request()->id)
            ),
            'discount_categories' => collect(DiscountCategory::all())->map(function($item) {
                return [
                    'value' => $item->id,
                    'label' => $item->symbol,
                ];
            }),
            'type' => 'import_certificate',
            'title' => 'Emissão de Factura para Fitosanitário Para Importação',
            'action' => 'issue',
            'url' => route('importcertificates.issueInvoice', request()->id)
        ], route('importcertificates.show', request()->id));
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function issueInvoice(InvoiceRequest $request)
    {
        abort_if( !auth()->user()->can('add_invoices'), 403, '');

        DB::transaction(function () use ($request): void {

            tap(Invoice::create($request->safe()->except(['items'])), function($record) use ($request) {

                foreach(collect($request->safe()->only(['items']))->first() as $item) {

                $obj = new InvoiceItem;

                $obj->invoice_id = $record->id;
                $obj->item_id = $item['item_id'];
                $obj->item_description = $item['item_description'];
                $obj->exemption_id = $item['exemption_id'];
                $obj->exemption_code = $item['exemption_code'];
                $obj->discount_id = $item['discount_id'];
                $obj->unit_id = $item['unit_id'];
                $obj->tax_id = $item['tax_id'];
                $obj->qty = $item['qty'];
                $obj->unit_price = $item['unit_price'];
                $obj->total = $item['total'];
                $obj->charge_tax = $item['charge_tax'];
                $obj->tax_amount = $item['tax_amount'];
                $obj->tax_percentage = $item['tax_percentage'];
                $obj->discount_amount = $item['discount_amount'];
                $obj->discount_percentage = $item['discount_percentage'];
                $obj->obs = $item['obs'];

                $obj->save();

            }

            // Update Certificate Invoice Details
            ImportCertificate::find(request()->certificate_id)->update([
                'invoice_id' => $record->id,
                'invoiced' => true
            ]);
    
            });
        });

        

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ]
        ]);


    }
}
