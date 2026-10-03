<?php

namespace App\Http\Controllers;

use App\Exports\ActivityLogExport;
use App\Exports\ConfiguredQueryExport;
use App\Exports\CustomersExport;
use App\Exports\ProductsExport;
use App\Http\Requests\ExportHubRequest;
use App\Models\MaintenanceTask;
use App\Models\User;
use App\Models\VAPSampleEntry;
use App\Services\InventoryCatalogueRead;
use App\Services\SampleLaboratoryAccess;
use App\Support\ExportHubCatalog;
use App\Support\ExportHubQuery;
use App\Support\LaboratoryDataExportQuery;
use App\Support\SpreadsheetDownloadResponder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportHubController extends Controller
{
    public function __construct(
        private readonly ExportHubQuery $exportHubQuery,
        private readonly ExportHubCatalog $exportHubCatalog,
        private readonly LaboratoryDataExportQuery $laboratoryDataExportQuery,
        private readonly SampleLaboratoryAccess $laboratoryAccess,
        private readonly InventoryCatalogueRead $catalogueRead
    ) {}

    public function index(ExportHubRequest $request): Response
    {
        $filters = $request->validated();
        $dataset = $filters['dataset'];

        return Inertia::render('Exports/Index', [
            'datasets' => $this->datasets($request->user()),
            'selectedDataset' => $dataset,
            'selectedCount' => $this->exportHubCatalog->isDirect($dataset)
                ? (clone $this->exportHubQuery->forDataset($dataset, $filters))->count()
                : null,
            'filterOptions' => $this->exportHubQuery->filterOptions($dataset),
            'filters' => $filters,
        ]);
    }

    public function download(ExportHubRequest $request): BinaryFileResponse
    {
        $filters = $request->validated();
        $dataset = $filters['dataset'];
        abort_unless($this->exportHubCatalog->isDirect($dataset), 404);

        $query = $this->exportHubQuery->forDataset($dataset, $filters);
        $timestamp = now()->format('Ymd-His');

        $definition = $this->exportHubCatalog->get($dataset);

        return match ($dataset) {
            'activity_log' => SpreadsheetDownloadResponder::download(new ActivityLogExport($query), "registo-actividade-{$timestamp}.xlsx"),
            'customers' => SpreadsheetDownloadResponder::download(new CustomersExport($query), "clientes-{$timestamp}.xlsx"),
            'products' => SpreadsheetDownloadResponder::download(new ProductsExport($query), "produtos-{$timestamp}.xlsx"),
            default => SpreadsheetDownloadResponder::download(
                new ConfiguredQueryExport($query, $definition, $this->exportHubCatalog->columns($dataset)),
                "{$definition['filename']}-{$timestamp}.xlsx"
            ),
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function datasets(User $user): array
    {
        $datasets = [];

        foreach ($this->exportHubCatalog->directDatasets() as $key => $definition) {
            if (! $user->can($definition['permission'])) {
                continue;
            }

            $summaryQuery = $key === 'activity_log'
                ? Activity::query()->toBase()
                : DB::table($definition['table']);

            if (in_array($key, ['occurrences', 'invoices', 'credit_notes', 'receipts', 'quotes', 'import_certificates', 'export_certificates'], true)) {
                $summaryQuery->where('lab_id', $this->laboratoryAccess->activeLabId());
            }

            $datasets[] = $this->dataset(
                key: $key,
                title: $definition['title'],
                description: $definition['description'],
                category: $definition['category'],
                count: (clone $summaryQuery)->count(),
                updatedAt: (clone $summaryQuery)->max('updated_at'),
                filterGroup: $definition['filterGroup'],
                dateLabel: $definition['dateLabel']
            );
        }

        if ($user->can('view_analysis')) {
            $datasets[] = [
                'key' => 'pending_analysis',
                'title' => 'Análises pendentes',
                'description' => 'Amostras e parâmetros ainda por processar na rotina diária.',
                'category' => 'Laboratório',
                'count' => $this->laboratoryDataExportQuery->pendingSummary([])['tasks'],
                'updated_at' => null,
                'mode' => 'workspace',
                'href' => route('analysis.data-exports.index', ['view' => 'pending']),
            ];
        }

        if ($user->can('view_results')) {
            $datasets[] = [
                'key' => 'results_audit',
                'title' => 'Auditoria de resultados',
                'description' => 'Resultados inseridos, verificados e aprovados com trilho de decisão.',
                'category' => 'Laboratório',
                'count' => $this->laboratoryDataExportQuery->auditSummary([])['total'],
                'updated_at' => null,
                'mode' => 'workspace',
                'href' => route('analysis.data-exports.index', ['view' => 'audit']),
            ];
        }

        if ($user->can('view_samples')) {
            $datasets[] = $this->workspaceDataset('sample_register', 'Registo de amostras', 'Ciclo de recepção, retenção e estado das amostras com exportação própria.', 'Laboratório', VAPSampleEntry::query()->where('lab_id', $this->laboratoryAccess->activeLabId())->count(), route('vap_samples.index'));
        }

        if ($this->catalogueRead->allowedTypes($user) !== []) {
            $datasets[] = $this->workspaceDataset('inventory_register', 'Inventário e metrologia', 'Equipamentos, reagentes, stock, calibração e dados metrológicos.', 'Inventário', $this->catalogueRead->items($this->laboratoryAccess->activeLabId(), $user)->count(), route('vap-inventory.items.index'));
        }

        if ($user->can('view_maintenance_tasks') && in_array('equipment', $this->catalogueRead->allowedTypes($user), true)) {
            $tasks = MaintenanceTask::forLaboratory($this->laboratoryAccess->activeLabId())
                ->whereHas('equipment', fn (Builder $items): Builder => $this->catalogueRead->constrainItems($items, $this->laboratoryAccess->activeLabId(), $user));
            $datasets[] = $this->workspaceDataset('maintenance_register', 'Manutenção', 'Plano e histórico de tarefas, equipamentos, prazos e execução.', 'Inventário', $tasks->count(), route('vap-maintenance.tasks'));
        }

        if ($user->can('view_occurrences')) {
            $datasets[] = $this->workspaceDataset('nonconformity_register', 'Não conformidades laboratoriais', 'Registo CAPA detalhado com acções, evidências e exportação própria.', 'Qualidade', DB::table('v_non_conformities')->where('lab_id', $this->laboratoryAccess->activeLabId())->count(), route('vap_non_conformities.index'));
        }

        return $datasets;
    }

    /**
     * @return array<string, mixed>
     */
    private function dataset(string $key, string $title, string $description, string $category, int $count, mixed $updatedAt, string $filterGroup, string $dateLabel): array
    {
        return [
            'key' => $key,
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'count' => $count,
            'updated_at' => $updatedAt,
            'mode' => 'download',
            'href' => route('exports.index', ['dataset' => $key]),
            'filter_group' => $filterGroup,
            'date_label' => $dateLabel,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function workspaceDataset(string $key, string $title, string $description, string $category, int $count, string $href): array
    {
        return compact('key', 'title', 'description', 'category', 'count', 'href') + [
            'updated_at' => null,
            'mode' => 'workspace',
            'filter_group' => null,
            'date_label' => null,
        ];
    }
}
