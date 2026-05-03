<?php

namespace App\Support;

use App\Models\VAPProposalTemplate;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProposalTemplatesSpreadsheetImport implements ToCollection, WithHeadingRow
{
    public function __construct(
        private readonly int $userId
    ) {
    }

    public function collection(Collection $rows): void
    {
        $rows
            ->filter(fn ($row) => filled($row['name'] ?? null) && filled($row['content'] ?? null))
            ->each(function ($row): void {
                VAPProposalTemplate::query()->updateOrCreate(
                    ['name' => (string) $row['name']],
                    [
                        'category' => (string) ($row['category'] ?? 'general'),
                        'description' => $row['description'] ?: null,
                        'theme_preset' => $row['theme_preset'] ?: null,
                        'is_active' => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
                        'content' => (string) $row['content'],
                        'layout_schema' => $this->decodeJsonColumn($row['layout_schema_json'] ?? null),
                        'export_settings' => $this->decodeJsonColumn($row['export_settings_json'] ?? null),
                        'user_id' => $this->userId,
                    ]
                );
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonColumn(mixed $value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
