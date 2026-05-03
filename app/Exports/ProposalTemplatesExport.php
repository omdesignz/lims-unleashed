<?php

namespace App\Exports;

use App\Models\VAPProposalTemplate;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProposalTemplatesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function collection()
    {
        return VAPProposalTemplate::query()
            ->with('user')
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        return [
            'name',
            'category',
            'description',
            'theme_preset',
            'is_active',
            'content',
            'layout_schema_json',
            'export_settings_json',
            'created_by',
            'created_at',
            'updated_at',
        ];
    }

    public function map($template): array
    {
        return [
            $template->name,
            $template->category,
            $template->description,
            $template->theme_preset,
            $template->is_active ? '1' : '0',
            $template->content,
            json_encode($template->layout_schema ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode($template->export_settings ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $template->user?->name,
            optional($template->created_at)->toIso8601String(),
            optional($template->updated_at)->toIso8601String(),
        ];
    }
}
