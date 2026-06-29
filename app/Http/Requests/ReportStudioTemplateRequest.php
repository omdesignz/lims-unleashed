<?php

namespace App\Http\Requests;

use HeadlessChromium\BrowserFactory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Spatie\Browsershot\Browsershot;

class ReportStudioTemplateRequest extends FormRequest
{
    /**
     * @var array<int, string>
     */
    private const CANVAS_SURFACES = [
        'content',
        'first_page_header_html',
        'default_header_html',
        'footer_html',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|\Closure|array<mixed>|string>
     */
    public function rules(): array
    {
        $cssColorRule = 'regex:/\A(?:#[0-9a-fA-F]{3,8}|(?:rgb|rgba|hsl|hsla)\([0-9\s,.%+\-]+\)|[a-zA-Z][a-zA-Z0-9-]{2,32})\z/';
        $cssPositionRule = 'regex:/\A(?:left|right|top|bottom|center|(?:100|[1-9]?\d)(?:\.\d{1,2})?%)(?:\s+(?:left|right|top|bottom|center|(?:100|[1-9]?\d)(?:\.\d{1,2})?%))?\z/i';
        $customPageSelected = $this->input('export_settings.paper_size') === 'custom';

        return [
            'name' => ['required', 'string', 'max:255'],
            'studio_type' => ['required', Rule::in(['analysis', 'executive', 'proposal', 'export_certificate', 'import_certificate', 'quote', 'invoice', 'receipt', 'credit_note'])],
            'renderer' => ['required', Rule::in(['internal', 'chrome', 'browsershot', 'canva'])],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'is_default' => ['sometimes', 'boolean'],
            'theme_preset' => ['nullable', 'string', 'max:100'],
            'canva_design_url' => ['nullable', 'url', 'max:2048'],
            'description' => ['nullable', 'string', 'max:5000'],
            'layout_schema' => ['nullable', 'array'],
            'layout_schema.first_page_header_html' => ['nullable', 'string'],
            'layout_schema.default_header_html' => ['nullable', 'string'],
            'layout_schema.footer_html' => ['nullable', 'string'],
            'layout_schema.body_html' => ['nullable', 'string'],
            'layout_schema.styles_css' => ['nullable', 'string'],
            'layout_schema.sections' => ['nullable', 'array'],
            'layout_schema.variable_catalog' => ['nullable', 'array'],
            'layout_schema.variable_catalog.*.value' => ['nullable', 'string', 'max:255'],
            'layout_schema.variable_catalog.*.label' => ['nullable', 'string', 'max:255'],
            'layout_schema.canvas_blocks' => ['nullable', 'array'],
            'layout_schema.document_font_family' => ['nullable', 'string', 'max:150'],
            'layout_schema.canvas_blocks.*.id' => ['nullable', 'string', 'max:100'],
            'layout_schema.canvas_blocks.*.title' => ['nullable', 'string', 'max:255'],
            'layout_schema.canvas_blocks.*.surface' => ['nullable', 'string', 'max:100', Rule::in(self::CANVAS_SURFACES)],
            'layout_schema.canvas_blocks.*.block_kind' => ['nullable', Rule::in(['rich_text', 'signature', 'image', 'stamp', 'qr_code', 'chart_snapshot'])],
            'layout_schema.canvas_blocks.*.asset_id' => ['nullable', 'string', 'max:100'],
            'layout_schema.canvas_blocks.*.asset_label' => ['nullable', 'string', 'max:255'],
            'layout_schema.canvas_blocks.*.asset_source' => ['nullable', 'string', 'max:255'],
            'layout_schema.canvas_blocks.*.asset_mime_type' => ['nullable', 'string', 'max:255'],
            'layout_schema.canvas_blocks.*.asset_size' => ['nullable', 'numeric', 'min:0'],
            'layout_schema.canvas_blocks.*.content_html' => ['nullable', 'string'],
            'layout_schema.canvas_blocks.*.image_url' => ['nullable', 'string', 'max:2048', $this->studioMediaReferenceRule()],
            'layout_schema.canvas_blocks.*.image_alt' => ['nullable', 'string', 'max:255'],
            'layout_schema.canvas_blocks.*.image_fit' => ['nullable', Rule::in(['cover', 'contain', 'auto'])],
            'layout_schema.canvas_blocks.*.image_position' => ['nullable', 'string', 'max:50', $cssPositionRule],
            'layout_schema.canvas_blocks.*.qr_content' => ['nullable', 'string', 'max:2048'],
            'layout_schema.canvas_blocks.*.qr_label' => ['nullable', 'string', 'max:255'],
            'layout_schema.canvas_blocks.*.qr_foreground_color' => ['nullable', 'string', 'max:255', $this->hexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.qr_background_color' => ['nullable', 'string', 'max:255', $this->hexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.qr_error_correction' => ['nullable', Rule::in(['low', 'medium', 'quartile', 'high'])],
            'layout_schema.canvas_blocks.*.qr_margin' => ['nullable', 'integer', 'min:0', 'max:32'],
            'layout_schema.canvas_blocks.*.chart_title' => ['nullable', 'string', 'max:255'],
            'layout_schema.canvas_blocks.*.chart_caption' => ['nullable', 'string', 'max:1000'],
            'layout_schema.canvas_blocks.*.chart_svg' => ['nullable', 'string'],
            'layout_schema.canvas_blocks.*.chart_image_url' => ['nullable', 'string', 'max:65535', $this->studioMediaReferenceRule()],
            'layout_schema.canvas_blocks.*.chart_type' => ['nullable', Rule::in(['bar', 'line', 'doughnut'])],
            'layout_schema.canvas_blocks.*.chart_labels' => ['nullable', $this->chartTextListRule()],
            'layout_schema.canvas_blocks.*.chart_labels.*' => ['nullable', 'string', 'max:120'],
            'layout_schema.canvas_blocks.*.chart_values' => ['nullable', $this->chartNumericListRule()],
            'layout_schema.canvas_blocks.*.chart_values.*' => ['nullable', $this->chartNumericOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.chart_colors' => ['nullable', $this->chartHexColorListRule()],
            'layout_schema.canvas_blocks.*.chart_colors.*' => ['nullable', $this->chartHexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.chart_primary_color' => ['nullable', 'string', 'max:255', $this->hexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.chart_background_color' => ['nullable', 'string', 'max:255', $this->hexColorOrPlaceholderRule()],
            'layout_schema.canvas_blocks.*.chart_show_values' => ['sometimes', 'boolean'],
            'layout_schema.canvas_blocks.*.x' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'layout_schema.canvas_blocks.*.y' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'layout_schema.canvas_blocks.*.width' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'layout_schema.canvas_blocks.*.min_height' => ['nullable', 'numeric', 'min:0', 'max:4000'],
            'layout_schema.canvas_blocks.*.z_index' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'layout_schema.canvas_blocks.*.padding' => ['nullable', 'numeric', 'min:0', 'max:400'],
            'layout_schema.canvas_blocks.*.background_color' => ['nullable', 'string', 'max:100', $cssColorRule],
            'layout_schema.canvas_blocks.*.background_image' => ['nullable', 'string', 'max:2048', $this->studioMediaReferenceRule()],
            'layout_schema.canvas_blocks.*.background_image_fit' => ['nullable', Rule::in(['cover', 'contain', 'auto'])],
            'layout_schema.canvas_blocks.*.background_image_position' => ['nullable', 'string', 'max:50', $cssPositionRule],
            'layout_schema.canvas_blocks.*.overlay_color' => ['nullable', 'string', 'max:100', $cssColorRule],
            'layout_schema.canvas_blocks.*.overlay_opacity' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'layout_schema.canvas_blocks.*.text_color' => ['nullable', 'string', 'max:100', $cssColorRule],
            'layout_schema.canvas_blocks.*.border_width' => ['nullable', 'numeric', 'min:0', 'max:40'],
            'layout_schema.canvas_blocks.*.border_color' => ['nullable', 'string', 'max:100', $cssColorRule],
            'layout_schema.canvas_blocks.*.border_radius' => ['nullable', 'numeric', 'min:0', 'max:2000'],
            'layout_schema.canvas_blocks.*.opacity' => ['nullable', 'numeric', 'min:0.05', 'max:1'],
            'layout_schema.canvas_blocks.*.rotation_deg' => ['nullable', 'numeric', 'min:-45', 'max:45'],
            'layout_schema.canvas_blocks.*.shadow_preset' => ['nullable', Rule::in(['none', 'soft', 'elevated', 'stamp', 'glow'])],
            'layout_schema.canvas_blocks.*.text_align' => ['nullable', Rule::in(['left', 'center', 'right', 'justify'])],
            'layout_schema.canvas_blocks.*.font_size' => ['nullable', 'numeric', 'min:8', 'max:72'],
            'layout_schema.canvas_blocks.*.line_height' => ['nullable', 'numeric', 'min:0.8', 'max:3'],
            'layout_schema.canvas_blocks.*.signature_label' => ['nullable', 'string', 'max:255'],
            'layout_schema.canvas_blocks.*.signature_name' => ['nullable', 'string', 'max:255'],
            'layout_schema.canvas_blocks.*.signature_title' => ['nullable', 'string', 'max:255'],
            'layout_schema.canvas_blocks.*.signature_image' => ['nullable', 'string', 'max:2048', $this->studioMediaReferenceRule()],
            'layout_schema.canvas_blocks.*.signature_image_fit' => ['nullable', Rule::in(['cover', 'contain', 'auto'])],
            'layout_schema.canvas_blocks.*.signature_image_position' => ['nullable', 'string', 'max:50', $cssPositionRule],
            'layout_schema.canvas_blocks.*.signature_image_width' => ['nullable', 'numeric', 'min:24', 'max:360'],
            'layout_schema.canvas_blocks.*.signature_image_height' => ['nullable', 'numeric', 'min:16', 'max:240'],
            'layout_schema.canvas_blocks.*.signature_line_style' => ['nullable', Rule::in(['solid', 'dashed'])],
            'layout_schema.canvas_blocks.*.signature_align' => ['nullable', Rule::in(['left', 'center', 'right'])],
            'layout_schema.canvas_blocks.*.signature_show_date' => ['sometimes', 'boolean'],
            'layout_schema.canvas_blocks.*.signature_date_label' => ['nullable', 'string', 'max:255'],
            'layout_schema.canvas_blocks.*.is_locked' => ['sometimes', 'boolean'],
            'layout_schema.canvas_blocks.*.is_hidden' => ['sometimes', 'boolean'],
            'layout_schema.canvas_blocks.*.page_scope' => ['nullable', Rule::in(['first', 'all', 'following', 'specific'])],
            'layout_schema.canvas_blocks.*.page_number' => ['nullable', 'integer', 'min:1', 'max:999'],
            'layout_schema.background_image_path' => ['nullable', 'string', 'max:2048', $this->studioMediaReferenceRule()],
            'layout_schema.page_background_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.background_size' => ['nullable', Rule::in(['cover', 'contain', 'auto'])],
            'layout_schema.background_position' => ['nullable', 'string', 'max:50', $cssPositionRule],
            'layout_schema.background_repeat' => ['nullable', Rule::in(['no-repeat', 'repeat', 'repeat-x', 'repeat-y'])],
            'layout_schema.table_header_background' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_header_text_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_border_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_font_size' => ['nullable', 'numeric', 'min:8', 'max:16'],
            'layout_schema.table_cell_padding' => ['nullable', 'numeric', 'min:2', 'max:24'],
            'layout_schema.table_summary_background' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_summary_text_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.table_summary_muted_color' => ['nullable', 'string', 'max:50', $cssColorRule],
            'layout_schema.show_canvas_grid' => ['sometimes', 'boolean'],
            'layout_schema.show_canvas_rulers' => ['sometimes', 'boolean'],
            'layout_schema.snap_to_grid' => ['sometimes', 'boolean'],
            'layout_schema.snap_grid_size' => ['nullable', 'numeric', 'min:1', 'max:24'],
            'layout_schema.page_safe_area' => ['sometimes', 'boolean'],
            'export_settings' => ['nullable', 'array'],
            'export_settings.paper_size' => ['nullable', Rule::in(['A4', 'Letter', 'Legal', 'custom'])],
            'export_settings.orientation' => ['nullable', Rule::in(['P', 'L'])],
            'export_settings.custom_page_width' => [Rule::requiredIf($customPageSelected), 'nullable', 'numeric', 'min:50', 'max:2000'],
            'export_settings.custom_page_height' => [Rule::requiredIf($customPageSelected), 'nullable', 'numeric', 'min:50', 'max:2000'],
            'export_settings.margin_top' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'export_settings.margin_right' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'export_settings.margin_bottom' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'export_settings.margin_left' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'export_settings.first_page_margin_top' => ['nullable', 'numeric', 'min:0', 'max:250'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_default' => $this->boolean('is_default'),
            'layout_schema' => $this->input('layout_schema', []),
            'export_settings' => $this->input('export_settings', []),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $renderer = $this->input('renderer');

            if ($renderer === 'chrome' && ! class_exists(BrowserFactory::class)) {
                $validator->errors()->add(
                    'renderer',
                    'O renderizador Chrome requer o pacote opcional chrome-php/chrome e um Chrome/Chromium disponível no servidor.'
                );
            }

            $chromeBinary = config('laravel-pdf.chrome.chrome_binary');

            if ($renderer === 'chrome' && is_string($chromeBinary) && $chromeBinary !== '' && ! is_executable($chromeBinary)) {
                $validator->errors()->add(
                    'renderer',
                    'O binário Chrome configurado para gerar PDFs não existe ou não é executável: '.$chromeBinary
                );
            }

            if ($renderer === 'browsershot' && ! class_exists(Browsershot::class)) {
                $validator->errors()->add(
                    'renderer',
                    'O renderizador Browsershot requer o pacote opcional spatie/browsershot, Node e Chrome/Chromium no servidor.'
                );
            }

            $this->validateCanvasBlockPlacement($validator);
        });
    }

    private function validateCanvasBlockPlacement(Validator $validator): void
    {
        $blocks = $this->input('layout_schema.canvas_blocks', []);

        if (! is_array($blocks)) {
            return;
        }

        foreach ($blocks as $index => $block) {
            if (! is_array($block)) {
                continue;
            }

            if (
                ($block['surface'] ?? 'content') === 'content'
                && ($block['page_scope'] ?? null) === 'specific'
                && blank($block['page_number'] ?? null)
            ) {
                $validator->errors()->add(
                    "layout_schema.canvas_blocks.{$index}.page_number",
                    'Indique a página específica onde este objecto deve aparecer no PDF.'
                );
            }
        }
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
            ->filter()
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
