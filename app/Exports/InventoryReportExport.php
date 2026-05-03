<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;
    protected $reportType;

    public function __construct($data, $reportType)
    {
        $this->data = $data;
        $this->reportType = $reportType;
    }

    public function collection()
    {
        // For 'consumption', $this->data contains the trend/topItems arrays
        // We return the specific part we want to list in rows
        return collect($this->data['items'] ?? $this->data);
    }

    public function headings(): array
    {
        return match($this->reportType) {
            'consumption' => ['Date', 'Reagent', 'Quantity Used', 'User'],
            'stock'       => ['Item Name', 'Warehouse', 'Available Qty', 'Min Level'],
            'expiry'      => ['Item Name', 'Batch', 'Expiry Date', 'Status'],
            default       => []
        };
    }

    public function map($row): array
    {
        return match($this->reportType) {
            'consumption' => [
                $row['date'],
                $row['reagent_name'],
                $row['quantity_used'],
                $row['used_by']
            ],
            'stock' => [
                $row->item->name,
                $row->warehouse->name,
                $row->qty_available,
                $row->min_stock_level
            ],
            // ... add mapping for other types
            default => []
        };
    }
}