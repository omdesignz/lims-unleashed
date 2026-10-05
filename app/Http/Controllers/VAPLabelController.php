<?php

namespace App\Http\Controllers;

use App\Models\CollectionProduct;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\VAPLab;
use App\Models\VAPLabel;
use App\Models\VAPLabelTemplate;
use App\Models\VAPSampleEntry;
use App\Services\SampleLaboratoryAccess;
use App\Support\LabelStudioSourceResolver;
use App\Support\VAPLabelPdfRenderer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class VAPLabelController extends Controller
{
    public function __construct(private readonly SampleLaboratoryAccess $laboratoryAccess) {}

    public function index(Request $request)
    {
        $labId = $this->laboratoryAccess->activeLabId();
        $baseQuery = VAPLabel::query()->where('lab_id', $labId);
        $query = (clone $baseQuery)->with(['lab', 'department', 'user'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%");
                });
            })
            ->when($request->type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($request->lab_id, function ($query, $labId) {
                $query->where('lab_id', $labId);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('is_active', $status === 'active');
            });

        $labels = $query->latest()->paginate(20);
        $labs = VAPLab::query()->whereKey($labId)->get(['id', 'name']);
        $departments = Department::active()->get(['id', 'name']);

        return Inertia::render('VAPLabels/Index', [
            'labels' => $labels,
            'filters' => $request->only(['search', 'type', 'lab_id', 'status']),
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'active' => (clone $baseQuery)->where('is_active', true)->count(),
                'by_type' => (clone $baseQuery)->groupBy('type')->selectRaw('type, count(*) as count')->get(),
            ],
            'labs' => $labs,
            'departments' => $departments,
        ]);
    }

    public function create(Request $request, LabelStudioSourceResolver $resolver)
    {
        $labId = $this->laboratoryAccess->activeLabId();
        $templates = $this->availableTemplates()->where('is_active', true)
            ->orderBy('is_featured', 'desc')
            ->orderBy('name')
            ->get();
        $selectedTemplate = $templates->firstWhere('id', $request->integer('template_id'));

        $labs = VAPLab::query()->whereKey($labId)->get(['id', 'name']);
        $departments = Department::active()->get(['id', 'name']);

        return Inertia::render('VAPLabels/Create', [
            'templates' => $templates,
            'selectedTemplateId' => $selectedTemplate?->id,
            'labs' => $labs,
            'departments' => $departments,
            'sourcePreview' => $resolver->resolve($request->string('source_type')->value(), $request->input('source_id'), $labId),
            'supportedPlaceholders' => $resolver->supportedPlaceholders(),
            'sourceOptions' => $this->labelStudioSourceOptions(),
            'defaultSettings' => $this->defaultLabelSettings(),
        ]);
    }

    public function store(Request $request, LabelStudioSourceResolver $resolver)
    {
        $labId = $this->laboratoryAccess->activeLabId();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:equipment,material,sample,custom',
            'content' => 'required|string',
            'width' => 'required|numeric|min:1|max:1000',
            'height' => 'required|numeric|min:1|max:1000',
            'background_color' => 'required|string|size:7',
            'text_color' => 'required|string|size:7',
            'font_size' => 'required|integer|min:6|max:72',
            'border_width' => 'required|integer|min:0|max:10',
            'border_color' => 'required|string|size:7',
            'text_alignment' => 'required|in:left,center,right,justify',
            'lab_id' => ['nullable', Rule::in([$labId])],
            'department_id' => 'nullable|exists:departments,id',
            'logo_path' => 'nullable|string',
            'logo_size' => 'nullable|numeric|min:1|max:50',
            'has_qr_code' => 'boolean',
            'qr_code_content' => 'nullable|string|max:255',
            'qr_code_size' => 'nullable|numeric|min:1|max:50',
            'has_barcode' => 'boolean',
            'barcode_content' => 'nullable|string|max:255',
            'barcode_type' => 'nullable|string',
            'barcode_width' => 'nullable|numeric|min:1|max:50',
            'barcode_height' => 'nullable|numeric|min:1|max:50',
            'text_position' => 'nullable|array',
            'logo_position' => 'nullable|array',
            'qr_code_position' => 'nullable|array',
            'barcode_position' => 'nullable|array',
            'is_active' => 'boolean',
            'source_type' => 'nullable|in:sample_entry,sample,equipment,reagent,collection_product',
            'source_id' => 'nullable|integer',
            'template_id' => 'nullable|exists:label_templates,id',
        ], [], $this->labelAttributes());

        if ($validated['template_id'] ?? null) {
            $this->availableTemplates()->where('is_active', true)->findOrFail($validated['template_id']);
        }
        $validated['user_id'] = auth()->id();
        $validated['tenant_id'] = auth()->user()->tenant_id;
        $validated['lab_id'] = $labId;
        $validated = $this->enrichLabelPayload($validated, $resolver);

        $label = VAPLabel::create($validated);

        return redirect()->route('vap_labels.labels.show', $label->id)
            ->with('success', __('gestlab.general.labels.vap_labels.created'));
    }

    public function show(VAPLabel $label, LabelStudioSourceResolver $resolver, VAPLabelPdfRenderer $renderer)
    {
        $this->ensureLaboratoryOwns($label);
        $label->load(['lab', 'department', 'user']);
        $templates = $this->availableTemplates()->where('is_active', true)
            ->orderBy('is_featured', 'desc')
            ->orderBy('name')
            ->get();
        $sourcePreview = $resolver->resolve(
            data_get($label->template_data, 'source_type'),
            data_get($label->template_data, 'source_id'),
            (int) $label->lab_id
        );

        if (request()->wantsJson()) {
            return response()->json([
                'label' => $label,
                'source_preview' => $sourcePreview,
            ]);
        }

        return Inertia::render('VAPLabels/Show', [
            'label' => $label,
            'previewData' => [
                'sample_text' => $sourcePreview
                    ? $resolver->renderContent($label->content, $sourcePreview)
                    : (string) $label->content,
                'sample_qr' => $renderer->codeContent($label, 'qr', $sourcePreview, $resolver) ?? 'LAB-'.strtoupper(Str::random(8)),
                'sample_barcode' => $renderer->codeContent($label, 'barcode', $sourcePreview, $resolver) ?? 'BAR-'.strtoupper(Str::random(6)),
            ],
            'templates' => $templates,
            'sourcePreview' => $sourcePreview,
            'pdfRenderer' => $renderer->capabilities(),
            'printSettings' => $this->defaultPrintSettings($label),
        ]);
    }

    public function edit(Request $request, VAPLabel $label, LabelStudioSourceResolver $resolver)
    {
        $this->ensureLaboratoryOwns($label);
        $templates = $this->availableTemplates()->where('is_active', true)
            ->orderBy('is_featured', 'desc')
            ->orderBy('name')
            ->get();
        $labs = VAPLab::query()->whereKey($label->lab_id)->get(['id', 'name']);
        $departments = Department::active()->get(['id', 'name']);

        return Inertia::render('VAPLabels/Create', [
            'label' => $label,
            'templates' => $templates,
            'selectedTemplateId' => data_get($label->template_data, 'template_id'),
            'labs' => $labs,
            'departments' => $departments,
            // A record chosen in the editor shows in the preview before it is saved.
            'sourcePreview' => $resolver->resolve(
                $request->filled('source_type') ? $request->string('source_type')->value() : data_get($label->template_data, 'source_type'),
                $request->filled('source_id') ? $request->input('source_id') : data_get($label->template_data, 'source_id'),
                (int) $label->lab_id
            ),
            'supportedPlaceholders' => $resolver->supportedPlaceholders(),
            'sourceOptions' => $this->labelStudioSourceOptions(),
            'defaultSettings' => $this->defaultLabelSettings(),
        ]);
    }

    public function update(Request $request, VAPLabel $label, LabelStudioSourceResolver $resolver)
    {
        $this->ensureLaboratoryOwns($label);
        $labId = $this->laboratoryAccess->activeLabId();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:equipment,material,sample,custom',
            'content' => 'required|string',
            'width' => 'required|numeric|min:1|max:1000',
            'height' => 'required|numeric|min:1|max:1000',
            'background_color' => 'required|string|size:7',
            'text_color' => 'required|string|size:7',
            'font_size' => 'required|integer|min:6|max:72',
            'border_width' => 'required|integer|min:0|max:10',
            'border_color' => 'required|string|size:7',
            'text_alignment' => 'required|in:left,center,right,justify',
            'lab_id' => ['nullable', Rule::in([$labId])],
            'department_id' => 'nullable|exists:departments,id',
            'logo_path' => 'nullable|string',
            'logo_size' => 'nullable|numeric|min:1|max:50',
            'has_qr_code' => 'boolean',
            'qr_code_content' => 'nullable|string|max:255',
            'qr_code_size' => 'nullable|numeric|min:1|max:50',
            'has_barcode' => 'boolean',
            'barcode_content' => 'nullable|string|max:255',
            'barcode_type' => 'nullable|string',
            'barcode_width' => 'nullable|numeric|min:1|max:50',
            'barcode_height' => 'nullable|numeric|min:1|max:50',
            'is_active' => 'boolean',
            'source_type' => 'nullable|in:sample_entry,sample,equipment,reagent,collection_product',
            'source_id' => 'nullable|integer',
            'template_id' => 'nullable|exists:label_templates,id',
        ], [], $this->labelAttributes());

        if ($validated['template_id'] ?? null) {
            $this->availableTemplates()->where('is_active', true)->findOrFail($validated['template_id']);
        }
        unset($validated['lab_id']);
        $validated = $this->enrichLabelPayload($validated, $resolver, $label);
        $label->update($validated);

        return redirect()->route('vap_labels.labels.show', $label->id)
            ->with('success', __('gestlab.general.labels.vap_labels.updated'));
    }

    public function destroy(VAPLabel $label)
    {
        $this->ensureLaboratoryOwns($label);
        $label->delete();

        return redirect()->route('vap_labels.labels.index')
            ->with('success', __('gestlab.general.labels.vap_labels.deleted'));
    }

    public function duplicate(VAPLabel $label)
    {
        $this->ensureLaboratoryOwns($label);
        $duplicate = $label->replicate();
        $duplicate->name = $label->name.' (cópia)';
        $duplicate->tenant_id = auth()->user()->tenant_id;
        $duplicate->user_id = auth()->id();
        $duplicate->save();

        return redirect()->route('vap_labels.labels.edit', $duplicate)
            ->with('success', __('gestlab.general.labels.vap_labels.duplicated'));
    }

    public function previewPdf(VAPLabel $label, LabelStudioSourceResolver $resolver, VAPLabelPdfRenderer $renderer)
    {
        $this->ensureLaboratoryOwns($label);
        $renderedPdf = $renderer->renderPreview($label, $resolver);

        return response($renderedPdf['content'])
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="preview-etiqueta.pdf"')
            ->header('X-Label-Pdf-Renderer', $renderedPdf['renderer']);
    }

    public function generatePdf(Request $request, VAPLabel $label, LabelStudioSourceResolver $resolver, VAPLabelPdfRenderer $renderer)
    {
        $this->ensureLaboratoryOwns($label);
        $validated = $request->validate([
            'data' => 'required|array',
            'data.*.content' => 'required|string',
            'data.*.qr_content' => 'nullable|string',
            'data.*.barcode_content' => 'nullable|string',
            'include_cutouts' => 'boolean',
            'labels_per_page' => 'integer|min:1|max:100',
            'margin' => 'numeric|min:0|max:20',
            'page_size' => 'nullable|in:A3,A4,Letter,Legal',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);
        $printSettings = $this->persistPrintSettings($label, $request->only([
            'include_cutouts',
            'labels_per_page',
            'margin',
            'page_size',
            'orientation',
        ]));

        $renderedPdf = $renderer->renderLabels($label, $validated['data'], $printSettings, $resolver);
        $filename = 'etiquetas-'.Str::slug($label->name).'-'.now()->format('Y-m-d-H-i-s').'.pdf';

        return response($renderedPdf['content'])
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$filename.'"')
            ->header('X-Label-Pdf-Renderer', $renderedPdf['renderer']);
    }

    public function generateBatchPdf(Request $request, VAPLabel $label, VAPLabelPdfRenderer $renderer)
    {
        $this->ensureLaboratoryOwns($label);
        $validated = $request->validate([
            'data' => 'required|array',
            'data.*.content' => 'required|string',
            'data.*.qr_content' => 'nullable|string',
            'data.*.barcode_content' => 'nullable|string',
            'columns' => 'integer|min:1|max:10',
            'rows' => 'integer|min:1|max:50',
            'spacing' => 'numeric|min:0|max:20',
            'include_cutouts' => 'boolean',
            'page_size' => 'nullable|in:A3,A4,Letter,Legal',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);
        $printSettings = $this->persistPrintSettings($label, $request->only([
            'columns',
            'rows',
            'spacing',
            'include_cutouts',
            'page_size',
            'orientation',
        ]));

        $renderedPdf = $renderer->renderBatch($label, $validated['data'], $printSettings);
        $filename = 'etiquetas-lote-'.Str::slug($label->name).'-'.now()->format('Y-m-d-H-i-s').'.pdf';

        return response($renderedPdf['content'])
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$filename.'"')
            ->header('X-Label-Pdf-Renderer', $renderedPdf['renderer']);
    }

    public function toggleStatus(VAPLabel $label)
    {
        $this->ensureLaboratoryOwns($label);
        $label->update(['is_active' => ! $label->is_active]);

        return back()->with('success', __('gestlab.general.labels.vap_labels.status_updated'));
    }

    public function getTemplates()
    {
        $labId = $this->laboratoryAccess->activeLabId();
        if (request()->filled('lab_id')) {
            abort_unless(request()->integer('lab_id') === $labId, 404);

            return response()->json(
                VAPLabel::query()
                    ->where('lab_id', $labId)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name'])
            );
        }

        $templates = $this->availableTemplates()->where('is_active', true)
            ->orderBy('is_featured', 'desc')
            ->orderBy('name')
            ->get();

        return response()->json($templates);
    }

    public function applyTemplate(Request $request, VAPLabel $label)
    {
        $this->ensureLaboratoryOwns($label);
        $validated = $request->validate([
            'template_id' => 'required|exists:label_templates,id',
        ]);

        $template = $this->availableTemplates()->where('is_active', true)->findOrFail($validated['template_id']);
        $presentation = Arr::only($template->template_data ?? [], VAPLabelTemplate::PRESENTATION_FIELDS);
        $templateData = array_merge(
            $presentation,
            array_filter([
                'template_id' => $template->id,
                'source_type' => data_get($label->template_data, 'source_type'),
                'source_id' => data_get($label->template_data, 'source_id'),
            ], fn ($value) => ! is_null($value))
        );

        $label->update(array_merge($presentation, [
            'template_data' => $templateData,
        ]));

        return back()->with('success', __('gestlab.general.labels.vap_labels.template_applied'));
    }

    public function generateFromSource(Request $request, LabelStudioSourceResolver $resolver)
    {
        $labId = $this->laboratoryAccess->activeLabId();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'template_id' => 'required|exists:label_templates,id',
            'source_type' => 'required|in:sample_entry,sample,equipment,reagent,collection_product',
            'source_id' => 'required|integer',
            'lab_id' => ['nullable', Rule::in([$labId])],
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $template = $this->availableTemplates()->where('is_active', true)->findOrFail($validated['template_id']);
        $sourcePayload = $resolver->resolve($validated['source_type'], $validated['source_id'], $labId);

        abort_if(! $sourcePayload, 404, 'Registo de origem não encontrado.');

        $templateData = $template->template_data ?? [];

        $label = VAPLabel::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'type' => $this->normalizeSourceLabelType($validated['source_type']),
            'content' => $resolver->renderContent((string) data_get($templateData, 'content', '{name}'), $sourcePayload),
            'width' => data_get($templateData, 'width', 50),
            'height' => data_get($templateData, 'height', 25),
            'background_color' => data_get($templateData, 'background_color', '#ffffff'),
            'text_color' => data_get($templateData, 'text_color', '#000000'),
            'font_size' => data_get($templateData, 'font_size', 12),
            'border_width' => data_get($templateData, 'border_width', 1),
            'border_color' => data_get($templateData, 'border_color', '#000000'),
            'text_alignment' => data_get($templateData, 'text_alignment', 'center'),
            'logo_path' => data_get($templateData, 'logo_path'),
            'logo_size' => data_get($templateData, 'logo_size'),
            'has_qr_code' => (bool) data_get($templateData, 'has_qr_code', true),
            'qr_code_content' => data_get($sourcePayload, 'qr_content'),
            'qr_code_size' => data_get($templateData, 'qr_code_size'),
            'has_barcode' => (bool) data_get($templateData, 'has_barcode', true),
            'barcode_content' => data_get($sourcePayload, 'barcode_content'),
            'barcode_type' => data_get($templateData, 'barcode_type', 'CODE128'),
            'barcode_width' => data_get($templateData, 'barcode_width'),
            'barcode_height' => data_get($templateData, 'barcode_height'),
            'lab_id' => $labId,
            'department_id' => $validated['department_id'] ?? null,
            'template_data' => array_merge($templateData, [
                'content_template' => (string) data_get($templateData, 'content', '{name}'),
                'template_id' => $template->id,
                'source_type' => $validated['source_type'],
                'source_id' => $validated['source_id'],
            ]),
            'is_active' => true,
        ]);

        return redirect()->route('vap_labels.labels.show', $label)
            ->with('success', __('gestlab.general.labels.vap_labels.generated_from_source'));
    }

    private function enrichLabelPayload(array $validated, LabelStudioSourceResolver $resolver, ?VAPLabel $existingLabel = null): array
    {
        $templateData = $existingLabel?->template_data ?? [];

        if (array_key_exists('source_type', $validated) || array_key_exists('source_id', $validated) || array_key_exists('template_id', $validated)) {
            $templateData = array_merge($templateData, array_filter([
                'source_type' => $validated['source_type'] ?? null,
                'source_id' => $validated['source_id'] ?? null,
                'template_id' => $validated['template_id'] ?? null,
            ], fn ($value) => ! is_null($value)));
        }

        $sourcePayload = $resolver->resolve(
            $validated['source_type'] ?? data_get($templateData, 'source_type'),
            $validated['source_id'] ?? data_get($templateData, 'source_id'),
            $this->laboratoryAccess->activeLabId()
        );

        if (filled(data_get($templateData, 'source_type')) && filled(data_get($templateData, 'source_id'))) {
            abort_unless($sourcePayload, 404, 'Registo de origem não encontrado.');
        }

        if ($sourcePayload) {
            // The label prints its source's values; the text as written, with its
            // placeholders, is kept so a later edit can fill it from another record.
            $templateData['content_template'] = $validated['content'];
            $validated['content'] = $resolver->renderContent($validated['content'], $sourcePayload);
            $validated['qr_code_content'] = $validated['qr_code_content'] ?? data_get($sourcePayload, 'qr_content');
            $validated['barcode_content'] = $validated['barcode_content'] ?? data_get($sourcePayload, 'barcode_content');

            if (($validated['type'] ?? 'custom') === 'custom') {
                $validated['type'] = $this->normalizeSourceLabelType($sourcePayload['source_type'] ?? 'custom');
            }
        }

        $validated['template_data'] = $templateData;

        unset($validated['source_type'], $validated['source_id'], $validated['template_id']);

        return $validated;
    }

    /**
     * The fields of a label as the editor names them, for validation messages.
     *
     * @return array<string, string>
     */
    private function labelAttributes(): array
    {
        return [
            'name' => 'nome',
            'type' => 'tipo',
            'content' => 'conteúdo',
            'width' => 'largura',
            'height' => 'altura',
            'background_color' => 'cor de fundo',
            'text_color' => 'cor do texto',
            'font_size' => 'tamanho da letra',
            'border_width' => 'largura do contorno',
            'border_color' => 'cor do contorno',
            'text_alignment' => 'alinhamento do texto',
            'department_id' => 'departamento',
            'logo_path' => 'logótipo',
            'logo_size' => 'tamanho do logótipo',
            'qr_code_content' => 'conteúdo do QR',
            'qr_code_size' => 'tamanho do QR',
            'barcode_content' => 'conteúdo do código de barras',
            'barcode_width' => 'largura do código de barras',
            'barcode_height' => 'altura do código de barras',
            'source_type' => 'tipo de origem',
            'source_id' => 'registo de origem',
            'template_id' => 'modelo',
        ];
    }

    private function normalizeSourceLabelType(string $sourceType): string
    {
        return match ($sourceType) {
            'sample', 'sample_entry', 'collection_product' => 'sample',
            'equipment' => 'equipment',
            'reagent' => 'material',
            default => 'custom',
        };
    }

    private function labelStudioSourceOptions(): array
    {
        $labId = $this->laboratoryAccess->activeLabId();

        return [
            'samples' => VAPSampleEntry::query()
                ->where('lab_id', $labId)
                ->latest()
                ->limit(50)
                ->get(['id', 'name', 'code'])
                ->map(fn ($sample) => [
                    'id' => $sample->id,
                    'label' => trim(($sample->code ? $sample->code.' · ' : '').($sample->name ?: 'Amostra')),
                ])
                ->values(),
            'inventory' => InventoryItem::forLaboratory($labId)
                ->whereHas('inventory', fn (Builder $query): Builder => $query
                    ->whereHas('warehouse', fn (Builder $warehouse): Builder => $warehouse->where('lab_id', $labId)))
                ->latest()
                ->limit(50)
                ->get(['id', 'name', 'code', 'internal_code'])
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'label' => trim(($item->code ?: $item->internal_code ?: 'ITEM-'.$item->id).' · '.$item->name),
                ])
                ->values(),
            'collection_products' => CollectionProduct::query()
                ->whereHas('sampleEntry', fn (Builder $query): Builder => $query->where('lab_id', $labId))
                ->latest()
                ->limit(50)
                ->get(['id', 'lot'])
                ->map(fn ($product) => [
                    'id' => $product->id,
                    'label' => 'Recolha #'.$product->id.($product->lot ? ' · Lote '.$product->lot : ''),
                ])
                ->values(),
        ];
    }

    private function defaultLabelSettings(): array
    {
        return [
            'width' => 50,
            'height' => 25,
            'font_size' => 12,
            'border_width' => 1,
        ];
    }

    /**
     * @return array{include_cutouts: bool, labels_per_page: int, margin: int, columns: int, rows: int, spacing: int, page_size: string, orientation: string}
     */
    private function defaultPrintSettings(?VAPLabel $label = null): array
    {
        return array_merge([
            'include_cutouts' => true,
            'labels_per_page' => 1,
            'margin' => 5,
            'columns' => 2,
            'rows' => 4,
            'spacing' => 5,
            'page_size' => 'A4',
            'orientation' => 'portrait',
        ], data_get($label?->template_data, 'print_settings', []));
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array{include_cutouts: bool, labels_per_page: int, margin: int, columns: int, rows: int, spacing: int, page_size: string, orientation: string}
     */
    private function persistPrintSettings(VAPLabel $label, array $settings): array
    {
        $normalized = $this->normalizePrintSettings(array_merge($this->defaultPrintSettings($label), $settings));
        $templateData = $label->template_data ?? [];
        $templateData['print_settings'] = $normalized;

        $label->forceFill(['template_data' => $templateData])->save();

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array{include_cutouts: bool, labels_per_page: int, margin: int, columns: int, rows: int, spacing: int, page_size: string, orientation: string}
     */
    private function normalizePrintSettings(array $settings): array
    {
        $pageSize = strtoupper((string) ($settings['page_size'] ?? 'A4'));

        return [
            'include_cutouts' => filter_var($settings['include_cutouts'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'labels_per_page' => max(1, min(100, (int) ($settings['labels_per_page'] ?? 1))),
            'margin' => max(0, min(20, (int) ($settings['margin'] ?? 5))),
            'columns' => max(1, min(10, (int) ($settings['columns'] ?? 2))),
            'rows' => max(1, min(50, (int) ($settings['rows'] ?? 4))),
            'spacing' => max(0, min(20, (int) ($settings['spacing'] ?? 5))),
            'page_size' => in_array($pageSize, ['A3', 'A4', 'LETTER', 'LEGAL'], true) ? Str::title(strtolower($pageSize)) : 'A4',
            'orientation' => in_array($settings['orientation'] ?? 'portrait', ['portrait', 'landscape'], true) ? (string) $settings['orientation'] : 'portrait',
        ];
    }

    private function ensureLaboratoryOwns(VAPLabel $label): void
    {
        abort_unless((int) $label->lab_id === $this->laboratoryAccess->activeLabId(), 404);
    }

    /** @return Builder<VAPLabelTemplate> */
    private function availableTemplates(): Builder
    {
        $labId = $this->laboratoryAccess->activeLabId();

        return VAPLabelTemplate::query()
            ->where(fn (Builder $query): Builder => $query->where('lab_id', $labId)->orWhere('is_system', true));
    }
}
