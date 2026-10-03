<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommercialQuotePickerRequest;
use App\Models\LabCode;
use App\Models\Matrix;
use App\Models\Parameter;
use App\Models\Product;
use App\Services\LaboratoryWorkflowOwnership;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabCodeController extends Controller
{
    public function getCode(Request $request, LaboratoryWorkflowOwnership $ownership, SampleLaboratoryAccess $access): JsonResponse
    {
        $labId = $access->activeLabId();
        $operator = $ownership->eligibleUsers($labId)->find($request->user()?->id);
        abort_unless($operator?->canAny([
            'view_quotes', 'add_quotes', 'edit_quotes', 'view_invoices', 'add_invoices', 'edit_invoices',
            'view_credit_notes', 'add_credit_notes', 'edit_credit_notes', 'view_receipts', 'add_receipts', 'edit_receipts',
            'view_quality_certificates', 'add_quality_certificates', 'edit_quality_certificates',
        ]), 403);
        $request->validate(['q' => ['nullable', 'string', 'max:200']]);

        return response()->json($request->has('q') ? $ownership->labCodesForLaboratory($labId)
            ->where('code', 'LIKE', '%'.$request->string('q').'%')
            ->orderBy('code')->limit(50)->get(['id', 'code', 'collection_id']) : []);
    }

    public function getCodeParameters(CommercialQuotePickerRequest $request): JsonResponse
    {
        $ownership = app(LaboratoryWorkflowOwnership::class);
        $labId = app(SampleLaboratoryAccess::class)->activeLabId();
        $code = $ownership->labCodesForLaboratory($labId)->with('collection.product.matrix')->findOrFail(request()->integer('code_id'));
        $data = [];

        if (request()->has('code_id') && ! request()->boolean('use_matrix_price')) {

            $parameterIDs = Matrix::parameters($code->collection?->product?->matrix_id);

            $data = collect(Parameter::whereIn('id', $parameterIDs)->get())->map(function ($item) use ($code) {
                return [
                    'invoice_id' => null,
                    'unit_id' => '',
                    'exemption_id' => $item->exemption_id ?? null,
                    'exemption_code' => $item->exemption_code ?? null,
                    'discount_id' => 1,
                    'item_id' => [
                        'value' => $item->id,
                        'catalog_type' => 'parameter',
                        'label' => $item->name,
                        'price' => $item->price,
                        'tax_id' => $item->tax_id,
                        'charge_tax' => $item->charge_tax,
                        'tax_percentage' => $item->tax_percentage,
                        'exemption_id' => $item->exemption_id,
                        'exemption_code' => $item->exemption_code,
                        'withhold_tax' => $item->withhold_tax,
                    ],
                    'item_description' => $item->code,
                    'invoice_id' => null,
                    'itemable_id' => $code->collection_id,
                    'itemable_type' => 'collectionproduct',
                    'qty' => 1,
                    'unit_price' => $item->price,
                    'tax_id' => $item->tax_id,
                    'total' => 0,
                    'discount_percentage' => 0,
                    'discount_amount' => 0,
                    'global_discount_amount' => 0,
                    'global_discount_percentage' => 0,
                    'global_discount_portion_percentage' => 0,
                    'tax_percentage' => $item->tax_percentage,
                    'tax_amount' => 0,
                    'obs' => null,
                    'charge_tax' => $item->charge_tax,
                    'withhold_tax' => $item->withhold_tax,

                ];
            });
        }

        if (request()->has('code_id') && request()->boolean('use_matrix_price')) {

            $matrix = Matrix::findOrFail($code->collection?->product?->matrix_id);

            $data = collect([

                [
                    'invoice_id' => null,
                    'unit_id' => '',
                    'exemption_id' => $matrix->exemption_id ?? null,
                    'exemption_code' => $matrix->exemption_code ?? null,
                    'discount_id' => 1,
                    'item_id' => [
                        'value' => $matrix->id,
                        'catalog_type' => 'matrix',
                        'label' => $matrix->description,
                        'price' => $matrix->fixed_price,
                        'tax_id' => $matrix->tax_id,
                        'charge_tax' => $matrix->charge_tax,
                        'tax_percentage' => $matrix->tax_percentage,
                        'exemption_id' => $matrix->exemption_id,
                        'exemption_code' => $matrix->exemption_code,
                        'withhold_tax' => $matrix->withhold_tax,
                    ],
                    'item_description' => $matrix->code,
                    'invoice_id' => null,
                    'itemable_id' => $code->collection_id,
                    'itemable_type' => 'collectionproduct',
                    'qty' => 1,
                    'unit_price' => $matrix->price,
                    'tax_id' => $matrix->tax_id,
                    'total' => 0,
                    'discount_percentage' => 0,
                    'discount_amount' => 0,
                    'global_discount_amount' => 0,
                    'global_discount_percentage' => 0,
                    'global_discount_portion_percentage' => 0,
                    'tax_percentage' => $matrix->tax_percentage,
                    'tax_amount' => 0,
                    'obs' => null,
                    'charge_tax' => $matrix->charge_tax,
                    'withhold_tax' => $matrix->withhold_tax,

                ],

            ]);
        }

        return response()->json($data);
    }

    public function getCodeProducts(CommercialQuotePickerRequest $request): JsonResponse
    {
        $ownership = app(LaboratoryWorkflowOwnership::class);
        $labId = app(SampleLaboratoryAccess::class)->activeLabId();
        $code = $ownership->labCodesForLaboratory($labId)->with('collection.product.matrix')->findOrFail(request()->integer('code_id'));
        $data = [];

        if (request()->has('code_id') && ! request()->boolean('use_matrix_price')) {

            $productID = $code->collection?->product_id;

            $product = Product::findOrfail($productID);

            $data = collect([
                [
                    'invoice_id' => null,
                    'unit_id' => '',
                    'exemption_id' => $product->exemption_id ?? null,
                    'exemption_code' => $product->exemption_code ?? null,
                    'discount_id' => 1,
                    'item_id' => [
                        'value' => $product->id,
                        'catalog_type' => 'product',
                        'label' => $product->name,
                        'price' => $product?->matrix?->fixed_price,
                        'tax_id' => $product->tax_id,
                        'charge_tax' => $product->charge_tax,
                        'tax_percentage' => $product->tax_percentage,
                        'exemption_id' => $product->exemption_id,
                        'exemption_code' => $product->exemption_code,
                        'withhold_tax' => $product->withhold_tax,
                    ],
                    'item_description' => $product->name,
                    'invoice_id' => null,
                    'itemable_id' => $code->collection_id,
                    'itemable_type' => 'collectionproduct',
                    'qty' => 1,
                    'unit_price' => $product?->matrix?->fixed_price,
                    'tax_id' => $product->tax_id,
                    'total' => 0,
                    'discount_percentage' => 0,
                    'discount_amount' => 0,
                    'global_discount_amount' => 0,
                    'global_discount_percentage' => 0,
                    'global_discount_portion_percentage' => 0,
                    'tax_percentage' => $product->tax_percentage,
                    'tax_amount' => 0,
                    'obs' => null,
                    'charge_tax' => $product->charge_tax,
                    'withhold_tax' => $product->withhold_tax,

                ],
            ]);
        }

        if (request()->has('code_id') && request()->boolean('use_matrix_price')) {

            $productID = $code->collection?->product_id;

            $product = Product::findOrfail($productID);

            $data = collect([

                [
                    'invoice_id' => null,
                    'unit_id' => '',
                    'exemption_id' => $product->exemption_id ?? null,
                    'exemption_code' => $product->exemption_code ?? null,
                    'discount_id' => 1,
                    'item_id' => [
                        'value' => $product->id,
                        'catalog_type' => 'product',
                        'label' => $product->name,
                        'price' => $product?->matrix?->price,
                        'tax_id' => $product->tax_id,
                        'charge_tax' => $product->charge_tax,
                        'tax_percentage' => $product->tax_percentage,
                        'exemption_id' => $product->exemption_id,
                        'exemption_code' => $product->exemption_code,
                        'withhold_tax' => $product->withhold_tax,
                    ],
                    'item_description' => $product->name,
                    'invoice_id' => null,
                    'itemable_id' => $code->collection_id,
                    'itemable_type' => 'collectionproduct',
                    'qty' => 1,
                    'unit_price' => $product?->matrix?->price,
                    'tax_id' => $product->tax_id,
                    'total' => 0,
                    'discount_percentage' => 0,
                    'discount_amount' => 0,
                    'global_discount_amount' => 0,
                    'global_discount_percentage' => 0,
                    'global_discount_portion_percentage' => 0,
                    'tax_percentage' => $product->tax_percentage,
                    'tax_amount' => 0,
                    'obs' => null,
                    'charge_tax' => $product->charge_tax,
                    'withhold_tax' => $product->withhold_tax,

                ],

            ]);
        }

        return response()->json($data);
    }

    public function getWarehouseUninvoicedProducts(CommercialQuotePickerRequest $request): JsonResponse
    {
        $ownership = app(LaboratoryWorkflowOwnership::class);
        $labId = app(SampleLaboratoryAccess::class)->activeLabId();
        $data = [];

        if (request()->has('warehouse_id') && ! request()->boolean('use_matrix_price')) {

            $data = collect($ownership->collectionProductsForLaboratory($labId)->with('product.matrix')->where('warehouse_id', request()->warehouse_id)->where('invoiced', false)->get())->map(function ($item) {
                return [
                    'invoice_id' => null,
                    'unit_id' => '',
                    'exemption_id' => $item->product->exemption_id ?? null,
                    'exemption_code' => $item->product->exemption_code ?? null,
                    'discount_id' => 1,
                    'item_id' => [
                        'value' => $item->product->id,
                        'catalog_type' => 'product',
                        'label' => $item->product->name,
                        'price' => $item->product?->matrix?->fixed_price,
                        'tax_id' => $item->product->tax_id,
                        'charge_tax' => $item->product->charge_tax,
                        'tax_percentage' => $item->product->tax_percentage,
                        'exemption_id' => $item->product->exemption_id,
                        'exemption_code' => $item->product->exemption_code,
                        'withhold_tax' => $item->product->withhold_tax,
                    ],
                    'item_description' => $item->product->name,
                    'invoice_id' => null,
                    'itemable_id' => $item->id,
                    'itemable_type' => 'collectionproduct',
                    'qty' => 1,
                    'unit_price' => $item->product?->matrix?->fixed_price,
                    'tax_id' => $item->product->tax_id,
                    'total' => 0,
                    'discount_percentage' => 0,
                    'discount_amount' => 0,
                    'global_discount_amount' => 0,
                    'global_discount_percentage' => 0,
                    'global_discount_portion_percentage' => 0,
                    'tax_percentage' => $item->product->tax_percentage,
                    'tax_amount' => 0,
                    'obs' => null,
                    'charge_tax' => $item->product->charge_tax,
                    'withhold_tax' => $item->product->withhold_tax,
                ];
            })->toArray();

        }

        if (request()->has('warehouse_id') && request()->boolean('use_matrix_price')) {

            $data = collect($ownership->collectionProductsForLaboratory($labId)->with('product.matrix')->where('warehouse_id', request()->warehouse_id)->where('invoiced', false)->get())->map(function ($item) {
                return [
                    'invoice_id' => null,
                    'unit_id' => '',
                    'exemption_id' => $item->product->exemption_id ?? null,
                    'exemption_code' => $item->product->exemption_code ?? null,
                    'discount_id' => 1,
                    'item_id' => [
                        'value' => $item->product->id,
                        'catalog_type' => 'product',
                        'label' => $item->product->name,
                        'price' => $item->product?->matrix?->price,
                        'tax_id' => $item->product->tax_id,
                        'charge_tax' => $item->product->charge_tax,
                        'tax_percentage' => $item->product->tax_percentage,
                        'exemption_id' => $item->product->exemption_id,
                        'exemption_code' => $item->product->exemption_code,
                        'withhold_tax' => $item->product->withhold_tax,
                    ],
                    'item_description' => $item->product->name,
                    'invoice_id' => null,
                    'itemable_id' => $item->id,
                    'itemable_type' => 'collectionproduct',
                    'qty' => 1,
                    'unit_price' => $item->product?->matrix?->price,
                    'tax_id' => $item->product->tax_id,
                    'total' => 0,
                    'discount_percentage' => 0,
                    'discount_amount' => 0,
                    'global_discount_amount' => 0,
                    'global_discount_percentage' => 0,
                    'global_discount_portion_percentage' => 0,
                    'tax_percentage' => $item->product->tax_percentage,
                    'tax_amount' => 0,
                    'obs' => null,
                    'charge_tax' => $item->product->charge_tax,
                    'withhold_tax' => $item->product->withhold_tax,
                ];
            })->toArray();

        }

        return response()->json($data);
    }

    public function getSampleStatus()
    {
        $code = LabCode::with('results', 'latest_approved_result', 'collection', 'analysis.profile.parameters', 'analysis.department', 'analysis.results')->where('collection_id', request()->id)->first();

        $analysis = [];

        $analysis = collect($code->analysis)->map(function ($item) {
            return [
                'id' => $item->id ?? null,
                'profile' => $item->profile->name ?? null,
                'parameter_count' => $item->profile->parameters()->count() ?? 0,
                'total_params' => $item->profile->parameters()->count() ?? 0,
                'completed_params' => $item?->results->whereNotNull('approved_date')->count() ?? 0,
                'progress' => $item?->results->whereNotNull('approved_date')->count() > 0 ? ($item?->results->count() / $item?->results->whereNotNull('approved_date')->count() * 100) : 0,
                'status' => is_null($item->init_date) ? 'pending' : (! is_null($item->init_date) && is_null($item->end_date) ? 'in_progress' : 'completed'),
                'updated_at' => $item->updated_at,
                'department' => $item->department->name,
            ];
        })->toArray();

        return response()->json($analysis);
    }
}
