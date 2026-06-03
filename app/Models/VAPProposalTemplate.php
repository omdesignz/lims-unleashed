<?php

namespace App\Models;

use App\Settings\GeneralSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VAPProposalTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'proposal_templates';

    protected $fillable = [
        'name',
        'category',
        'content',
        'description',
        'user_id',
        'is_active',
        'theme_preset',
        'layout_schema',
        'export_settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'layout_schema' => 'array',
            'export_settings' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function proposals()
    {
        return $this->hasMany(VAPProposal::class, 'template_id');
    }

    public static function getPlaceholders(): array
    {
        return [
            '{proposal_number}',
            '{customer_name}',
            '{customer_code}',
            '{service_location}',
            '{department}',
            '{warehouse}',
            '{created_at}',
            '{created_by}',
            '{tolerance_days}',
            '{expiry_date}',
            '{days_until_expiry}',
            '{sub_total}',
            '{total}',
            '{tax}',
            '{discount}',
            '{global_discount_amount}',
            '{global_discount_percentage}',
            '{withholding_tax_amount}',
            '{withholding_tax_percentage}',
            '{pricing_mode}',
            '{withhold_tax}',
            '{observations}',
            '{total_items}',
            '{taxable_items}',
            '{items_table}',
            '{items_list}',
            '{summary_table}',
            '{banking_details}',
            '{document_keywords}',
            '{lab_signature}',
            '{client_signature}',
            '{signature_block}',
            '{lab_name}',
            '{lab_details}',
            '{customer_details}',
            '{bank_name}',
            '{bank_iban}',
        ];
    }

    /**
     * @return array<string, string|int>
     */
    public static function placeholderValues(VAPProposal $proposal, ?GeneralSettings $settings = null): array
    {
        $replacements = [
            '{proposal_number}' => $proposal->proposal_number ?? '',
            '{customer_name}' => $proposal->customer?->name ?? '',
            '{customer_code}' => $proposal->customer?->code ?? '',
            '{service_location}' => $proposal->service_location ?? '',
            '{department}' => $proposal->department?->name ?? '',
            '{warehouse}' => $proposal->warehouse?->address ?: ($proposal->warehouse?->name ?? ''),
            '{created_at}' => $proposal->created_at ? $proposal->created_at->format('d/m/Y') : '',
            '{created_by}' => $proposal->user?->name ?? '',
            '{tolerance_days}' => $proposal->tolerance_days ?? '',
            '{expiry_date}' => $proposal->expiry_date ? $proposal->expiry_date->format('d/m/Y') : '',
            '{days_until_expiry}' => $proposal->days_until_expiry ?? '',

            '{sub_total}' => number_format($proposal->sub_total, 2, ',', '.'),
            '{total}' => number_format($proposal->total, 2, ',', '.'),
            '{tax}' => number_format($proposal->tax ?? 0, 2, ',', '.'),
            '{discount}' => number_format($proposal->discount ?? 0, 2, ',', '.'),
            '{global_discount_amount}' => number_format($proposal->global_discount_amount ?? 0, 2, ',', '.'),
            '{global_discount_percentage}' => number_format($proposal->global_discount_percentage ?? 0, 2, ',', '.'),
            '{withholding_tax_amount}' => number_format($proposal->withholding_tax_amount ?? 0, 2, ',', '.'),
            '{withholding_tax_percentage}' => number_format($proposal->withholding_tax_percentage ?? 0, 2, ',', '.'),

            '{pricing_mode}' => $proposal->use_matrix_price ? 'Preço de Matriz' : 'Preço de Parâmetro',
            '{withhold_tax}' => $proposal->withhold_tax ? 'Sim' : 'Não',
            '{observations}' => $proposal->obs ?? '',
        ];

        $totalItems = $proposal->items->count();
        $taxableItems = $proposal->items->where('charge_tax', true)->count();

        $replacements['{total_items}'] = $totalItems;
        $replacements['{taxable_items}'] = $taxableItems;
        $replacements['{items_table}'] = self::generateItemsTable($proposal);
        $replacements['{items_list}'] = self::generateItemsList($proposal);
        $replacements['{summary_table}'] = self::generateSummaryTable($proposal);
        $replacements['{banking_details}'] = self::generateBankingDetails($settings);
        $replacements['{document_keywords}'] = self::generateDocumentKeywords($settings);
        $replacements['{lab_signature}'] = $settings?->app_client_lab_director ?: 'Direcção técnica';
        $replacements['{client_signature}'] = $proposal->customer?->name ?? 'Representante do cliente';
        $replacements['{signature_block}'] = self::generateSignatureBlock($proposal, $settings);
        $replacements['{lab_name}'] = $settings?->app_client_lab_name ?: $settings?->app_name ?: 'Laboratório';
        $replacements['{lab_details}'] = self::generateLabDetails($settings);
        $replacements['{customer_details}'] = self::generateCustomerDetails($proposal);
        $replacements['{bank_name}'] = $settings?->app_bank_name ?? '';
        $replacements['{bank_iban}'] = $settings?->app_bank_iban ?? '';

        return $replacements;
    }

    /**
     * Parse template content with proposal data.
     */
    public static function parseContent(string $content, VAPProposal $proposal, ?GeneralSettings $settings = null): string
    {
        $replacements = self::templateReplacements(self::placeholderValues($proposal, $settings));

        return strtr($content, $replacements);
    }

    /**
     * @param  array<string, string|int>  $values
     * @return array<string, string>
     */
    private static function templateReplacements(array $values): array
    {
        $replacements = [];

        foreach ($values as $key => $value) {
            $placeholder = trim($key, '{}');

            $replacements[$key] = (string) $value;
            $replacements['{{'.$placeholder.'}}'] = (string) $value;
        }

        return $replacements;
    }

    private static function generateBankingDetails(?GeneralSettings $settings): string
    {
        if (! $settings) {
            return '<section style="padding:14px 16px; border:1px solid #ded3bf; border-radius:18px; background:#fffdf7; color:#58665f;">Dados bancários por configurar nas definições da aplicação.</section>';
        }

        $rows = collect([
            'Banco' => $settings->app_bank_name,
            'Titular' => $settings->app_bank_account_name,
            'Conta' => $settings->app_bank_account_number,
            'IBAN' => $settings->app_bank_iban,
            'SWIFT' => $settings->app_bank_swift,
        ])->filter();

        $details = $settings->app_bank_details
            ? '<p style="margin:10px 0 0; color:#58665f; white-space:pre-line;">'.nl2br(e($settings->app_bank_details), false).'</p>'
            : '';

        if ($rows->isEmpty() && $details === '') {
            return '<section style="padding:14px 16px; border:1px solid #ded3bf; border-radius:18px; background:#fffdf7; color:#58665f;">Dados bancários por configurar nas definições da aplicação.</section>';
        }

        $htmlRows = $rows
            ->map(fn (string $value, string $label): string => '<div style="display:flex; justify-content:space-between; gap:18px; padding:6px 0; border-bottom:1px solid #eee4d3;"><span style="color:#738076;">'.e($label).'</span><strong style="color:#143d37; text-align:right;">'.e($value).'</strong></div>')
            ->implode('');

        return '<section style="padding:16px 18px; border:1px solid #ded3bf; border-radius:18px; background:#fffdf7;"><div style="font-size:9px; letter-spacing:0.16em; text-transform:uppercase; color:#9a7a2f; font-weight:800;">Dados bancários</div><div style="margin-top:10px;">'.$htmlRows.'</div>'.$details.'</section>';
    }

    private static function generateDocumentKeywords(?GeneralSettings $settings): string
    {
        $keywords = str($settings?->app_document_keywords ?: 'proposta, análise, laboratório, ISO 17025')
            ->explode(',')
            ->map(fn (string $keyword): string => trim($keyword))
            ->filter()
            ->values();

        if ($keywords->isEmpty()) {
            return '';
        }

        $chips = $keywords
            ->map(fn (string $keyword): string => '<span style="display:inline-block; margin:0 6px 6px 0; padding:5px 9px; border:1px solid #ded3bf; border-radius:999px; background:#fbf7ee; color:#143d37; font-size:9px; font-weight:700;">'.e($keyword).'</span>')
            ->implode('');

        return '<section style="margin-top:12px;"><div style="font-size:9px; letter-spacing:0.16em; text-transform:uppercase; color:#9a7a2f; font-weight:800; margin-bottom:8px;">Palavras-chave</div>'.$chips.'</section>';
    }

    private static function generateSignatureBlock(VAPProposal $proposal, ?GeneralSettings $settings): string
    {
        $labSigner = $settings?->app_client_lab_director ?: $proposal->user?->name ?: 'Direcção técnica';
        $clientSigner = $proposal->customer?->name ?: 'Representante do cliente';

        return '<section style="margin-top:24px;"><table style="width:100%; border-collapse:collapse;"><tr><td style="width:48%; padding-top:26px; border-top:1px solid #143d37; color:#20332f;"><strong>'.e($labSigner).'</strong><br><span style="color:#58665f;">Validação técnica / comercial</span></td><td style="width:4%;"></td><td style="width:48%; padding-top:26px; border-top:1px solid #143d37; color:#20332f;"><strong>'.e($clientSigner).'</strong><br><span style="color:#58665f;">Aceitação da proposta</span></td></tr></table></section>';
    }

    private static function generateLabDetails(?GeneralSettings $settings): string
    {
        if (! $settings) {
            return self::detailsLinesHtml(['Laboratório'], 'Laboratório');
        }

        return self::detailsLinesHtml([
            $settings->app_client_lab_name ?: $settings->app_client_name ?: $settings->app_name,
            $settings->app_client_address,
            $settings->app_client_nif ? 'NIF: '.$settings->app_client_nif : ($settings->app_nif ? 'NIF: '.$settings->app_nif : null),
            $settings->app_client_contact ?: $settings->app_contact,
            $settings->app_client_email ?: $settings->app_email,
            $settings->app_client_lab_director ? 'Direção técnica: '.$settings->app_client_lab_director : null,
        ], 'Laboratório');
    }

    private static function generateCustomerDetails(VAPProposal $proposal): string
    {
        $customer = $proposal->customer;

        return self::detailsLinesHtml([
            $customer?->name,
            $customer?->address,
            $customer?->nif ? 'NIF: '.$customer->nif : null,
            $customer?->primary_phone ?: $customer?->contact ?: $customer?->phone,
            $customer?->email ?: $customer?->invoicing_email,
        ], $customer?->name ?: 'Cliente');
    }

    /**
     * @param  array<int, string|null>  $lines
     */
    private static function detailsLinesHtml(array $lines, string $fallback): string
    {
        $lines = array_values(array_filter($lines, fn ($line) => filled($line)));

        if ($lines === []) {
            $lines = [$fallback];
        }

        return collect($lines)
            ->map(fn ($line) => nl2br(e((string) $line), false))
            ->implode('<br>');
    }

    /**
     * Generate HTML table for items
     */
    private static function generateItemsTable(VAPProposal $proposal): string
    {
        $html = '<table style="width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 9pt;">';
        $html .= '<thead>';
        $html .= '<tr style="background: #143d37; color: #fffdf7;">';
        $html .= '<th style="padding: 8px; border: 1px solid #143d37; text-align: left;">Item</th>';
        $html .= '<th style="padding: 8px; border: 1px solid #143d37; text-align: left;">Descrição</th>';
        $html .= '<th style="padding: 8px; border: 1px solid #143d37; text-align: center;">Norma</th>';
        $html .= '<th style="padding: 8px; border: 1px solid #143d37; text-align: center;">Qtd.</th>';
        $html .= '<th style="padding: 8px; border: 1px solid #143d37; text-align: center;">Unid.</th>';
        $html .= '<th style="padding: 8px; border: 1px solid #143d37; text-align: right;">Preço unit.</th>';
        $html .= '<th style="padding: 8px; border: 1px solid #143d37; text-align: right;">Desconto</th>';
        $html .= '<th style="padding: 8px; border: 1px solid #143d37; text-align: right;">Imposto</th>';
        $html .= '<th style="padding: 8px; border: 1px solid #143d37; text-align: right;">Total</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($proposal->items as $index => $item) {
            $rowStyle = $index % 2 === 0 ? 'background: #fffdf7;' : 'background: #ffffff;';

            $html .= '<tr style="'.$rowStyle.'">';
            $html .= '<td style="padding: 8px; border: 1px solid #ded3bf; text-align: center; font-weight: bold; color:#143d37;">'.($index + 1).'</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ded3bf;">';
            $html .= '<div style="font-weight: bold;">'.htmlspecialchars($item->item_description).'</div>';

            if ($item->obs) {
                $html .= '<div style="font-size: 8pt; color: #58665f; margin-top: 2px;">'.htmlspecialchars($item->obs).'</div>';
            }

            if ($item->itemable_type) {
                $typeLabel = str_contains($item->itemable_type, 'Matrix') ? 'Matriz' : 'Parâmetro';
                $html .= '<div style="font-size: 8pt; color: #58665f; margin-top: 2px;">'.$typeLabel.' #'.$item->itemable_id.'</div>';
            }

            if (! $item->charge_tax) {
                $html .= '<div style="font-size: 8pt; color: #167a58; margin-top: 2px;">Isento de imposto</div>';
            }

            if ($item->exemption_code) {
                $html .= '<div style="font-size: 8pt; color: #167a58; margin-top: 2px;">Código de isenção: '.$item->exemption_code.'</div>';
            }

            $html .= '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ded3bf; text-align: center;">'.($item->standard->code ?? '-').'</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ded3bf; text-align: center;">'.number_format($item->qty, 2, ',', '.').'</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ded3bf; text-align: center;">'.($item->unit->code ?? '-').'</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ded3bf; text-align: right;">AOA '.number_format($item->unit_price, 2, ',', '.').'</td>';

            // Discount cell
            $html .= '<td style="padding: 8px; border: 1px solid #ded3bf; text-align: right;">';
            if ($item->discount_amount > 0) {
                if ($item->discount_id == 1) {
                    $html .= '<div style="font-size: 8pt; color: #167a58;">'.number_format($item->discount_percentage, 2, ',', '.').'%</div>';
                }
                $html .= '-AOA '.number_format($item->discount_amount, 2, ',', '.');
            } else {
                $html .= '-';
            }
            $html .= '</td>';

            // Tax cell
            $html .= '<td style="padding: 8px; border: 1px solid #ded3bf; text-align: right;">';
            if ($item->tax_amount > 0) {
                $html .= '<div style="font-size: 8pt; color: #9a7a2f;">'.number_format($item->tax_percentage, 2, ',', '.').'%</div>';
                $html .= '+AOA '.number_format($item->tax_amount, 2, ',', '.');
            } else {
                $html .= '-';
            }
            $html .= '</td>';

            // Total cell
            $html .= '<td style="padding: 8px; border: 1px solid #ded3bf; text-align: right; font-weight: bold; color:#143d37;">';
            $html .= 'AOA '.number_format($item->total, 2, ',', '.');
            $html .= '</td>';

            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    /**
     * Generate simple list for items
     */
    private static function generateItemsList(VAPProposal $proposal): string
    {
        $html = '<ul style="list-style-type: none; padding-left: 0; margin: 20px 0;">';

        foreach ($proposal->items as $index => $item) {
            $html .= '<li style="margin-bottom: 15px; padding: 12px; border-left: 3px solid #d8b85f; background: #fffdf7;">';
            $html .= '<div style="font-weight: bold;">'.($index + 1).'. '.htmlspecialchars($item->item_description).'</div>';

            $details = [];
            if ($item->standard->code ?? false) {
                $details[] = 'Padrão: '.$item->standard->code;
            }

            $details[] = 'Quantidade: '.number_format($item->qty, 2, ',', '.').' '.($item->unit->code ?? '');
            $details[] = 'Preço unitário: AOA '.number_format($item->unit_price, 2, ',', '.');

            if ($item->discount_amount > 0) {
                if ($item->discount_id == 1) {
                    $details[] = 'Desconto: '.number_format($item->discount_percentage, 2, ',', '.').'% (-AOA '.number_format($item->discount_amount, 2, ',', '.').')';
                } else {
                    $details[] = 'Desconto: -AOA '.number_format($item->discount_amount, 2, ',', '.');
                }
            }

            if ($item->tax_amount > 0) {
                $details[] = 'Taxa: '.number_format($item->tax_percentage, 2, ',', '.').'% (+AOA '.number_format($item->tax_amount, 2, ',', '.').')';
            }

            $details[] = '<strong>Total: AOA '.number_format($item->total, 2, ',', '.').'</strong>';

            $html .= '<div style="font-size: 9pt; color: #58665f; margin-top: 5px;">'.implode(' • ', $details).'</div>';

            if ($item->obs) {
                $html .= '<div style="font-size: 8pt; color: #58665f; margin-top: 5px; font-style: italic;">'.htmlspecialchars($item->obs).'</div>';
            }

            $html .= '</li>';
        }

        $html .= '</ul>';

        return $html;
    }

    /**
     * Generate summary table with totals
     */
    private static function generateSummaryTable(VAPProposal $proposal): string
    {
        $html = '<table style="width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 10pt;">';

        // Subtotal
        $html .= '<tr>';
        $html .= '<td style="padding: 8px; border-bottom: 1px solid #ded3bf; text-align: right; font-weight: bold;">Subtotal:</td>';
        $html .= '<td style="padding: 8px; border-bottom: 1px solid #ded3bf; text-align: right; width: 150px;">';
        $html .= 'AOA '.number_format($proposal->sub_total, 2, ',', '.');
        $html .= '</td>';
        $html .= '</tr>';

        // Discount
        if ($proposal->discount > 0) {
            $html .= '<tr>';
            $html .= '<td style="padding: 8px; border-bottom: 1px solid #ded3bf; text-align: right; font-weight: bold; color: #167a58;">Desconto total:</td>';
            $html .= '<td style="padding: 8px; border-bottom: 1px solid #ded3bf; text-align: right; color: #167a58;">';
            $html .= '-AOA '.number_format($proposal->discount, 2, ',', '.');
            $html .= '</td>';
            $html .= '</tr>';
        }

        // Tax
        if ($proposal->tax > 0) {
            $html .= '<tr>';
            $html .= '<td style="padding: 8px; border-bottom: 1px solid #ded3bf; text-align: right; font-weight: bold;">Impostos:</td>';
            $html .= '<td style="padding: 8px; border-bottom: 1px solid #ded3bf; text-align: right;">';
            $html .= '+AOA '.number_format($proposal->tax, 2, ',', '.');
            $html .= '</td>';
            $html .= '</tr>';
        }

        // Global discount
        if ($proposal->global_discount_amount > 0) {
            $html .= '<tr>';
            $html .= '<td style="padding: 8px; border-bottom: 1px solid #ded3bf; text-align: right; font-weight: bold; color: #167a58;">Desconto global:</td>';
            $html .= '<td style="padding: 8px; border-bottom: 1px solid #ded3bf; text-align: right; color: #167a58;">';
            $html .= '-AOA '.number_format($proposal->global_discount_amount, 2, ',', '.');

            if ($proposal->global_discount_percentage > 0) {
                $html .= '<div style="font-size: 8pt;">('.number_format($proposal->global_discount_percentage, 2, ',', '.').'%)</div>';
            }

            $html .= '</td>';
            $html .= '</tr>';
        }

        // Withholding tax
        if ($proposal->withholding_tax_amount > 0) {
            $html .= '<tr>';
            $html .= '<td style="padding: 8px; border-bottom: 1px solid #ded3bf; text-align: right; font-weight: bold;">Imposto retido:</td>';
            $html .= '<td style="padding: 8px; border-bottom: 1px solid #ded3bf; text-align: right;">';
            $html .= 'AOA '.number_format($proposal->withholding_tax_amount, 2, ',', '.');

            if ($proposal->withholding_tax_percentage > 0) {
                $html .= '<div style="font-size: 8pt;">('.number_format($proposal->withholding_tax_percentage, 2, ',', '.').'%)</div>';
            }

            $html .= '</td>';
            $html .= '</tr>';
        }

        // Grand Total
        $html .= '<tr style="background: #fbf7ee;">';
        $html .= '<td style="padding: 12px; border-top: 2px solid #143d37; text-align: right; font-weight: bold; font-size: 11pt; color: #143d37;">TOTAL GERAL:</td>';
        $html .= '<td style="padding: 12px; border-top: 2px solid #143d37; text-align: right; font-weight: bold; font-size: 11pt; color: #143d37;">';
        $html .= 'AOA '.number_format($proposal->total, 2, ',', '.');
        $html .= '</td>';
        $html .= '</tr>';

        // Summary footer
        $html .= '<tr>';
        $html .= '<td colspan="2" style="padding: 8px; text-align: right; font-size: 8pt; color: #58665f;">';
        $html .= $proposal->items->count().' itens • ';
        $html .= $proposal->items->where('tax_amount', '>', 0)->count().' tributáveis';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '</table>';

        return $html;
    }
}
