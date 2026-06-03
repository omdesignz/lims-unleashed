<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class VAPSampleEntriesImport implements ToCollection, WithHeadingRow
{
    /**
     * @var Collection<int, array<string, mixed>>
     */
    private Collection $rows;

    public function __construct()
    {
        $this->rows = collect();
    }

    /**
     * @param  Collection<int, mixed>  $collection
     */
    public function collection(Collection $collection): void
    {
        $this->rows = $collection
            ->map(fn ($row) => $row instanceof Collection ? $row->toArray() : (array) $row)
            ->filter(fn (array $row) => collect($row)->filter(fn ($value) => filled($value))->isNotEmpty())
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(): Collection
    {
        return $this->rows;
    }
}
