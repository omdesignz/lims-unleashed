<?php

namespace App\Support;

use App\Models\VAPProposalTemplate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator as ValidationValidator;

class ProposalTemplateValidation
{
    private const FIELDS = ['name', 'category', 'description', 'theme_preset', 'is_active', 'content', 'layout_schema', 'export_settings'];

    /**
     * @var array<int, string>
     */
    private const CANVAS_SURFACES = [
        'content',
        'first_page_header_html',
        'default_header_html',
        'footer_html',
    ];

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function rules(array $data, ?VAPProposalTemplate $template = null, bool $preview = false): array
    {
        $this->checkBudget($data);
        $cssColorRule = 'regex:/\A(?:#[0-9a-fA-F]{3,8}|(?:rgb|rgba|hsl|hsla)\([0-9\s,.%+\-]+\)|[a-zA-Z][a-zA-Z0-9-]{2,32})\z/';
        $cssPositionRule = 'regex:/\A(?:left|right|top|bottom|center|(?:100|[1-9]?\d)(?:\.\d{1,2})?%)(?:\s+(?:left|right|top|bottom|center|(?:100|[1-9]?\d)(?:\.\d{1,2})?%))?\z/i';
        $mediaReferenceRule = $this->studioMediaReferenceRule();

        $rules = [
            'name' => $preview ? ['nullable', 'string', 'max:255'] : ['required', 'string', 'max:255', Rule::unique(VAPProposalTemplate::class, 'name')->ignore($template?->id)],
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:5000',
            'theme_preset' => 'nullable|string|max:100',
            'is_active' => 'sometimes|boolean',
            'content' => $preview ? ['nullable', 'string'] : ['required', 'string'],
            'layout_schema' => 'nullable|array',
            'layout_schema.first_page_header_html' => 'nullable|string',
            'layout_schema.default_header_html' => 'nullable|string',
            'layout_schema.footer_html' => 'nullable|string',
            'layout_schema.styles_css' => 'nullable|string',
            'layout_schema.document_font_family' => 'nullable|string|max:160',
            'layout_schema.variable_catalog' => 'nullable|array',
            'layout_schema.variable_catalog.*.value' => 'nullable|string|max:255',
            'layout_schema.variable_catalog.*.label' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks' => 'nullable|array',
            'layout_schema.canvas_blocks.*.id' => 'nullable|string|max:100',
            'layout_schema.canvas_blocks.*.title' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.surface' => $this->canvasSurfaceRule(),
            'layout_schema.canvas_blocks.*.block_kind' => 'nullable|string|in:rich_text,signature,image,stamp,qr_code,chart_snapshot',
            'layout_schema.canvas_blocks.*.content_html' => 'nullable|string',
            'layout_schema.canvas_blocks.*.image_url' => ['nullable', 'string', 'max:2048', $mediaReferenceRule],
            'layout_schema.canvas_blocks.*.image_alt' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.image_fit' => 'nullable|string|in:cover,contain,auto',
            'layout_schema.canvas_blocks.*.image_position' => ['nullable', 'string', 'max:50', $cssPositionRule],
            'layout_schema.canvas_blocks.*.qr_content' => 'nullable|string|max:2048',
            'layout_schema.canvas_blocks.*.qr_label' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.qr_foreground_color' => ['nullable', 'string', 'max:255', $this->hexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.qr_background_color' => ['nullable', 'string', 'max:255', $this->hexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.qr_error_correction' => 'nullable|in:low,medium,quartile,high',
            'layout_schema.canvas_blocks.*.qr_margin' => 'nullable|integer|min:0|max:32',
            'layout_schema.canvas_blocks.*.chart_title' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.chart_caption' => 'nullable|string|max:1000',
            'layout_schema.canvas_blocks.*.chart_svg' => 'nullable|string',
            'layout_schema.canvas_blocks.*.chart_image_url' => ['nullable', 'string', 'max:65535', $mediaReferenceRule],
            'layout_schema.canvas_blocks.*.chart_type' => 'nullable|in:bar,line,doughnut',
            'layout_schema.canvas_blocks.*.chart_labels' => ['nullable', $this->chartTextListRule()],
            'layout_schema.canvas_blocks.*.chart_labels.*' => 'nullable|string|max:120',
            'layout_schema.canvas_blocks.*.chart_values' => ['nullable', $this->chartNumericListRule()],
            'layout_schema.canvas_blocks.*.chart_values.*' => ['nullable', $this->chartNumericOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.chart_colors' => ['nullable', $this->chartHexColorListRule()],
            'layout_schema.canvas_blocks.*.chart_colors.*' => ['nullable', $this->chartHexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.chart_primary_color' => ['nullable', 'string', 'max:255', $this->hexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.chart_background_color' => ['nullable', 'string', 'max:255', $this->hexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.chart_show_values' => 'sometimes|boolean',
            'layout_schema.canvas_blocks.*.x' => 'nullable|numeric|min:0|max:100',
            'layout_schema.canvas_blocks.*.y' => 'nullable|numeric|min:0|max:100',
            'layout_schema.canvas_blocks.*.width' => 'nullable|numeric|min:1|max:100',
            'layout_schema.canvas_blocks.*.min_height' => 'nullable|numeric|min:0|max:4000',
            'layout_schema.canvas_blocks.*.z_index' => 'nullable|numeric|min:0|max:999',
            'layout_schema.canvas_blocks.*.padding' => 'nullable|numeric|min:0|max:400',
            'layout_schema.canvas_blocks.*.background_color' => ['nullable', 'string', 'max:100', $cssColorRule],
            'layout_schema.canvas_blocks.*.background_image' => ['nullable', 'string', 'max:2048', $mediaReferenceRule],
            'layout_schema.canvas_blocks.*.background_image_fit' => 'nullable|string|in:cover,contain,auto',
            'layout_schema.canvas_blocks.*.background_image_position' => ['nullable', 'string', 'max:50', $cssPositionRule],
            'layout_schema.canvas_blocks.*.overlay_color' => ['nullable', 'string', 'max:100', $cssColorRule],
            'layout_schema.canvas_blocks.*.overlay_opacity' => 'nullable|numeric|min:0|max:1',
            'layout_schema.canvas_blocks.*.text_color' => ['nullable', 'string', 'max:100', $cssColorRule],
            'layout_schema.canvas_blocks.*.border_width' => 'nullable|numeric|min:0|max:40',
            'layout_schema.canvas_blocks.*.border_color' => ['nullable', 'string', 'max:100', $cssColorRule],
            'layout_schema.canvas_blocks.*.border_radius' => 'nullable|numeric|min:0|max:2000',
            'layout_schema.canvas_blocks.*.opacity' => 'nullable|numeric|min:0.05|max:1',
            'layout_schema.canvas_blocks.*.text_align' => 'nullable|string|in:left,center,right,justify',
            'layout_schema.canvas_blocks.*.font_size' => 'nullable|numeric|min:8|max:72',
            'layout_schema.canvas_blocks.*.line_height' => 'nullable|numeric|min:0.8|max:3',
            'layout_schema.canvas_blocks.*.signature_label' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.signature_name' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.signature_title' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.signature_image' => ['nullable', 'string', 'max:2048', $mediaReferenceRule],
            'layout_schema.canvas_blocks.*.signature_image_fit' => 'nullable|string|in:cover,contain,auto',
            'layout_schema.canvas_blocks.*.signature_image_position' => ['nullable', 'string', 'max:50', $cssPositionRule],
            'layout_schema.canvas_blocks.*.signature_image_width' => 'nullable|numeric|min:24|max:360',
            'layout_schema.canvas_blocks.*.signature_image_height' => 'nullable|numeric|min:16|max:240',
            'layout_schema.canvas_blocks.*.signature_line_style' => 'nullable|string|in:solid,dashed',
            'layout_schema.canvas_blocks.*.signature_align' => 'nullable|string|in:left,center,right',
            'layout_schema.canvas_blocks.*.signature_show_date' => 'sometimes|boolean',
            'layout_schema.canvas_blocks.*.signature_date_label' => 'nullable|string|max:255',
            'layout_schema.canvas_blocks.*.is_locked' => 'sometimes|boolean',
            'layout_schema.canvas_blocks.*.page_scope' => 'nullable|string|in:first,all,following,specific',
            'layout_schema.canvas_blocks.*.page_number' => 'nullable|integer|min:1|max:999',
            'layout_schema.background_image_path' => ['nullable', 'string', 'max:2048', $mediaReferenceRule],
            'layout_schema.page_background_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.background_size' => 'nullable|string|in:cover,contain,auto',
            'layout_schema.background_position' => ['nullable', 'string', 'max:50', $cssPositionRule],
            'layout_schema.background_repeat' => 'nullable|string|in:no-repeat,repeat,repeat-x,repeat-y',
            'layout_schema.table_header_background' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_header_text_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_border_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_font_size' => 'nullable|numeric|min:8|max:16',
            'layout_schema.table_cell_padding' => 'nullable|numeric|min:2|max:24',
            'layout_schema.table_summary_background' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_summary_text_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_summary_muted_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'export_settings' => 'nullable|array',
            'export_settings.paper_size' => 'nullable|string|in:A4,Letter,Legal,custom',
            'export_settings.custom_page_width' => 'nullable|required_if:export_settings.paper_size,custom|numeric|min:50|max:2000',
            'export_settings.custom_page_height' => 'nullable|required_if:export_settings.paper_size,custom|numeric|min:50|max:2000',
            'export_settings.orientation' => 'nullable|string|in:P,L',
            'export_settings.margin_top' => 'nullable|numeric|min:0|max:200',
            'export_settings.margin_right' => 'nullable|numeric|min:0|max:200',
            'export_settings.margin_bottom' => 'nullable|numeric|min:0|max:200',
            'export_settings.margin_left' => 'nullable|numeric|min:0|max:200',
            'export_settings.first_page_margin_top' => 'nullable|numeric|min:0|max:250',
        ];

        $nestedKeys = function (string $prefix) use ($rules): array {
            $keys = [];
            foreach (array_keys($rules) as $field) {
                if (str_starts_with($field, $prefix)) {
                    $key = substr($field, strlen($prefix));
                    if (! str_contains($key, '.')) {
                        $keys[] = $key;
                    }
                }
            }

            return $keys;
        };
        $rules['layout_schema'] = ['nullable', 'array:'.implode(',', $nestedKeys('layout_schema.'))];
        $rules['export_settings'] = ['nullable', 'array:'.implode(',', $nestedKeys('export_settings.'))];
        $rules['layout_schema.variable_catalog'] = ['nullable', 'array', 'list', 'max:500'];
        $rules['layout_schema.variable_catalog.*'] = ['required', 'array:value,label'];
        $rules['layout_schema.canvas_blocks'] = ['nullable', 'array', 'list', 'max:500'];
        $rules['layout_schema.canvas_blocks.*'] = ['required', 'array:'.implode(',', $nestedKeys('layout_schema.canvas_blocks.*.'))];

        return $rules;
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function validate(array $data, ?VAPProposalTemplate $template = null, bool $preview = false): array
    {
        $validator = Validator::make($data, $this->rules($data, $template, $preview));
        $validator->after($this->after());

        return $this->normalize($validator->validate());
    }

    /** @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public function validateImport(array $data): array
    {
        $rules = $this->rules($data);
        $rules['name'] = ['required', 'string', 'max:255'];
        $validator = Validator::make($data, $rules);
        $validator->after($this->after());

        return $this->normalize($validator->validate());
    }

    /** @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    public function normalize(array $validated): array
    {
        if (array_key_exists('is_active', $validated)) {
            $validated['is_active'] = (bool) $validated['is_active'];
        }
        if (array_key_exists('category', $validated) && $validated['category'] === null) {
            $validated['category'] = 'general';
        }
        if (is_array($validated['layout_schema'] ?? null)) {
            foreach (['canvas_blocks', 'variable_catalog'] as $field) {
                if (array_key_exists($field, $validated['layout_schema']) && $validated['layout_schema'][$field] === null) {
                    $validated['layout_schema'][$field] = [];
                }
            }
        }

        return $validated;
    }

    /** @param list<string> $transportFields */
    public function after(array $transportFields = []): \Closure
    {
        return function (ValidationValidator $validator) use ($transportFields): void {
            $data = $validator->getData();
            foreach (array_diff(array_keys($data), [...self::FIELDS, ...$transportFields]) as $field) {
                $validator->errors()->add((string) $field, 'Este campo não pode ser alterado neste formulário.');
            }
            $blocks = data_get($data, 'layout_schema.canvas_blocks', []);
            if (! is_array($blocks)) {
                return;
            }
            foreach ($blocks as $index => $block) {
                if (is_array($block) && ($block['surface'] ?? 'content') === 'content'
                    && ($block['page_scope'] ?? null) === 'specific' && blank($block['page_number'] ?? null)) {
                    $validator->errors()->add("layout_schema.canvas_blocks.{$index}.page_number", 'Indique a página específica onde este objecto deve aparecer no PDF.');
                }
            }
        };
    }

    /** @param array<string, mixed> $data */
    private function checkBudget(array $data): void
    {
        foreach (['canvas_blocks', 'variable_catalog'] as $field) {
            $value = data_get($data, 'layout_schema.'.$field);
            if (is_array($value) && count($value) > 500) {
                throw ValidationException::withMessages(['layout_schema.'.$field => 'A lista não pode exceder 500 entradas.']);
            }
        }
        $blocks = data_get($data, 'layout_schema.canvas_blocks', []);
        if (! is_array($blocks)) {
            return;
        }
        $total = 0;
        foreach ($blocks as $index => $block) {
            if (! is_array($block)) {
                continue;
            }
            foreach (['chart_labels', 'chart_values', 'chart_colors'] as $field) {
                $value = $block[$field] ?? null;
                $entries = is_array($value) ? count($value) : (is_string($value) ? count($this->splitChartStudioList($value)) : 0);
                $total += $entries;
                if ($entries > 1000 || $total > 10000) {
                    throw ValidationException::withMessages(["layout_schema.canvas_blocks.{$index}.{$field}" => 'O gráfico excede o limite de entradas do modelo.']);
                }
                if (is_array($value) && ! array_is_list($value)) {
                    throw ValidationException::withMessages(["layout_schema.canvas_blocks.{$index}.{$field}" => 'Os dados do gráfico devem ser uma lista ordenada.']);
                }
            }
        }
    }

    private function canvasSurfaceRule(): string
    {
        return 'nullable|string|max:100|in:'.implode(',', self::CANVAS_SURFACES);
    }

    private function chartTextListRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '' || is_array($value)) {
                return;
            }

            if (! is_string($value)) {
                $fail('A lista de etiquetas do gráfico deve ser texto ou uma lista.');

                return;
            }

            foreach ($this->splitChartStudioList($value) as $item) {
                if (mb_strlen($item) > 120) {
                    $fail('Cada etiqueta do gráfico deve ter no máximo 120 caracteres.');

                    return;
                }
            }
        };
    }

    private function chartNumericListRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '' || is_array($value)) {
                return;
            }

            if (! is_string($value)) {
                $fail('Os valores do gráfico devem ser numéricos ou variáveis do estúdio.');

                return;
            }

            foreach ($this->splitChartStudioList($value) as $item) {
                if (! $this->isNumericChartValue($item) && ! $this->isStudioPlaceholder($item)) {
                    $fail('Os valores do gráfico devem conter apenas números ou variáveis separados por vírgula, ponto e vírgula ou quebra de linha.');

                    return;
                }
            }
        };
    }

    private function chartHexColorListRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '' || is_array($value)) {
                return;
            }

            if (! is_string($value)) {
                $fail('A paleta do gráfico deve ser texto ou uma lista de cores.');

                return;
            }

            foreach ($this->splitChartStudioList($value) as $item) {
                if (! $this->isHexChartColor($item) && ! $this->isStudioPlaceholder($item)) {
                    $fail('A paleta do gráfico deve conter apenas cores HEX no formato #RRGGBB ou variáveis do estúdio.');

                    return;
                }
            }
        };
    }

    private function chartNumericOrPlaceholderRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
                $fail('Este valor deve ser texto ou um número simples.');

                return;
            }

            $item = (string) $value;

            if (! $this->isNumericChartValue($item) && ! $this->isStudioPlaceholder($item)) {
                $fail('Os valores do gráfico devem ser numéricos ou variáveis do estúdio.');
            }
        };
    }

    private function chartHexColorOrPlaceholderRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
                $fail('Este valor deve ser texto ou um número simples.');

                return;
            }

            $item = (string) $value;

            if (! $this->isHexChartColor($item) && ! $this->isStudioPlaceholder($item)) {
                $fail('A paleta do gráfico deve conter apenas cores HEX no formato #RRGGBB ou variáveis do estúdio.');
            }
        };
    }

    private function hexColorOrPlaceholderRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
                $fail('Este valor deve ser texto ou um número simples.');

                return;
            }

            $item = (string) $value;

            if (! $this->isHexChartColor($item) && ! $this->isStudioPlaceholder($item)) {
                $fail('A cor deve ser HEX no formato #RRGGBB ou uma variável do estúdio.');
            }
        };
    }

    private function studioMediaReferenceRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            if (! is_string($value)) {
                $fail('A referência de media do estúdio deve ser um URL, caminho público, data URI de imagem ou variável do estúdio.');

                return;
            }

            $value = trim($value);

            if ($value === '' || $this->isStudioPlaceholder($value)) {
                return;
            }

            if (preg_match('/[\x00-\x1F\x7F<>"\']/', $value) === 1) {
                $fail('A referência de media do estúdio contém caracteres inseguros.');

                return;
            }

            if (preg_match('/\Adata:image\/(?:png|jpe?g|gif|webp|avif|svg\+xml);base64,[A-Za-z0-9+\/=\s]+\z/i', $value) === 1) {
                return;
            }

            if (preg_match('/\Adata:/i', $value) === 1) {
                $fail('Apenas data URIs de imagem em base64 são permitidos nos elementos de media do estúdio.');

                return;
            }

            if (filter_var($value, FILTER_VALIDATE_URL)) {
                $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

                if (in_array($scheme, ['http', 'https'], true)) {
                    return;
                }
            }

            if (preg_match('/\A\/?(?:storage|images)\/[A-Za-z0-9._~!$&()*+,;=:@%\/-]+\z/', $value) === 1) {
                return;
            }

            $fail('A referência de media do estúdio deve apontar para uma imagem pública segura, URL HTTP(S), data URI de imagem ou variável do estúdio.');
        };
    }

    /**
     * @return array<int, string>
     */
    private function splitChartStudioList(string $value): array
    {
        return collect(preg_split('/[\r\n;]+|(?<!\d),|,(?!\d)/', $value) ?: [])
            ->map(fn (string $item): string => trim($item))
            ->filter(fn (string $item): bool => $item !== '')
            ->values()
            ->all();
    }

    private function isNumericChartValue(string $value): bool
    {
        return is_numeric(str_replace(',', '.', trim($value)));
    }

    private function isHexChartColor(string $value): bool
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', trim($value)) === 1;
    }

    private function isStudioPlaceholder(string $value): bool
    {
        $value = trim($value);

        return preg_match('/^\{\{\s*[A-Za-z_][A-Za-z0-9_.-]*\s*\}\}$/', $value) === 1
            || preg_match('/^\{\s*[A-Za-z_][A-Za-z0-9_.-]*\s*\}$/', $value) === 1;
    }
}
