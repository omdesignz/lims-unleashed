<?php

namespace App\Exports;

use App\Models\InventoryItem;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryItemsExport implements FromCollection, WithHeadings, WithMapping
{
    /** @param list<string> $inventoryTypes */
    public function __construct(
        private readonly int $labId,
        private readonly array $inventoryTypes,
        private readonly ?string $startDate = null,
        private readonly ?string $endDate = null,
        private readonly ?int $categoryId = null,
    ) {}

    /**
     * Define the collection of data to be exported.
     */
    public function collection(): Collection
    {
        $query = InventoryItem::forLaboratory($this->labId)
            ->whereHas('category', fn ($category) => $category->withTrashed()->whereIn('inventory_type', $this->inventoryTypes));

        // Apply a date filter if a start and end date are provided.
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [$this->startDate, $this->endDate]);
        }

        if ($this->categoryId !== null) {
            $query->where('category_id', $this->categoryId);
        }

        // Eager load relationships to avoid N+1 query issues.
        $query->with([
            'status',
            'packagingType',
            'category' => fn ($category) => $category->withTrashed(),
            'unit',
            'type',
            'supplier',
            'user',
        ]);

        return $query->get();
    }

    /**
     * Define the headings for the Excel columns.
     */
    public function headings(): array
    {
        return [
            'ID',
            trans('gestlab.general.labels.iitems.name'),
            trans('gestlab.general.labels.iitems.brand'),
            trans('gestlab.general.labels.iitems.location'),
            trans('gestlab.general.labels.iitems.model'),
            trans('gestlab.general.labels.iitems.software'),
            trans('gestlab.general.labels.iitems.firmware'),
            trans('gestlab.general.labels.iitems.internal_code'),
            trans('gestlab.general.labels.iitems.range'),
            trans('gestlab.general.labels.iitems.precision'),
            trans('gestlab.general.labels.iitems.resolution'),
            trans('gestlab.general.labels.iitems.description'),
            trans('gestlab.general.labels.iitems.obs'),
            trans('gestlab.general.labels.iitems.code'),
            trans('gestlab.general.labels.iitems.barcode'),
            trans('gestlab.general.labels.iitems.reorder_qty'),
            trans('gestlab.general.labels.iitems.packed_weight'),
            trans('gestlab.general.labels.iitems.packed_weight_unit'),
            trans('gestlab.general.labels.iitems.packed_height'),
            trans('gestlab.general.labels.iitems.packed_height_unit'),
            trans('gestlab.general.labels.iitems.packed_width'),
            trans('gestlab.general.labels.iitems.packed_width_unit'),
            trans('gestlab.general.labels.iitems.packed_depth'),
            trans('gestlab.general.labels.iitems.packed_depth_unit'),
            trans('gestlab.general.labels.iitems.refrigerated'),
            trans('gestlab.general.labels.iitems.status_id'),
            trans('gestlab.general.labels.iitems.has_safety_documentation'),
            trans('gestlab.general.labels.iitems.packaging_type_id'),
            trans('gestlab.general.labels.iitems.category_id'),
            trans('gestlab.general.labels.iitems.unit_id'),
            trans('gestlab.general.labels.iitems.type_id'),
            trans('gestlab.general.labels.iitems.lot'),
            trans('gestlab.general.labels.iitems.supplier_id'),
            trans('gestlab.general.labels.iitems.serial_number'),
            trans('gestlab.general.labels.iitems.last_calibration_date'),
            trans('gestlab.general.labels.iitems.next_calibration_date'),
            trans('gestlab.general.labels.iitems.reagent_open_date'),
            trans('gestlab.general.labels.iitems.reagent_expiry_date'),
            trans('gestlab.general.labels.iitems.user_id'),
            trans('gestlab.general.labels.iitems.created_at'),
            trans('gestlab.general.labels.iitems.updated_at'),
            // 'Brand',
            // 'Location',
            // 'Model',
            // 'Software',
            // 'Firmware',
            // 'Internal Code',
            // 'Range',
            // 'Precision',
            // 'Resolution',
            // 'Description',
            // 'Observations',
            // 'Code',
            // 'Barcode',
            // 'Reorder Qty',
            // 'Packed Weight',
            // 'Packed Weight Unit',
            // 'Packed Height',
            // 'Packed Height Unit',
            // 'Packed Width',
            // 'Packed Width Unit',
            // 'Packed Depth',
            // 'Packed Depth Unit',
            // 'Refrigerated',
            // 'Status',
            // 'Has Safety Documentation',
            // 'Packaging Type',
            // 'Category',
            // 'Unit',
            // 'Type',
            // 'Lot',
            // 'Supplier',
            // 'Serial Number',
            // 'Last Calibration Date',
            // 'Next Calibration Date',
            // 'Reagent Open Date',
            // 'Reagent Expiry Date',
            // 'User',
            // 'Created At',
            // 'Updated At',
        ];
    }

    /**
     * Map the data from the collection to the Excel row format.
     *
     * @param  mixed  $row
     */
    public function map($row): array
    {
        return [
            $row->id,
            $row->name,
            $row->brand,
            $row->location,
            $row->model,
            $row->software,
            $row->firmware,
            $row->internal_code,
            $row->range,
            $row->precision,
            $row->resolution,
            $row->description,
            $row->obs,
            $row->code,
            $row->barcode,
            $row->reorder_qty,
            $row->packed_weight,
            $row->packed_weight_unit,
            $row->packed_height,
            $row->packed_height_unit,
            $row->packed_width,
            $row->packed_width_unit,
            $row->packed_depth,
            $row->packed_depth_unit,
            $row->refrigerated ? 'SIM' : 'Não',
            $row->status->name ?? null,
            $row->has_safety_documentation ? 'SIM' : 'Não',
            $row->packagingType->name ?? null,
            $row->category->name ?? null,
            $row->unit->name ?? null,
            $row->type->name ?? null,
            $row->lot,
            $row->supplier->name ?? null,
            $row->serial_number,
            $row->last_calibration_date,
            $row->next_calibration_date,
            $row->reagent_open_date,
            $row->reagent_expiry_date,
            $row->user->name ?? null,
            $row->created_at,
            $row->updated_at,
        ];
    }
}
