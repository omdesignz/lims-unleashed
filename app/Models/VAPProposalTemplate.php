<?php

namespace App\Models;

use App\Settings\GeneralSettings;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
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

    /**
     * @return array<string, string>
     */
    public static function getPlaceholderLabels(): array
    {
        return [
            '{proposal_number}' => 'Número da proposta',
            '{customer_name}' => 'Nome do cliente',
            '{customer_code}' => 'Código do cliente',
            '{service_location}' => 'Local do serviço',
            '{department}' => 'Departamento',
            '{warehouse}' => 'Armazém',
            '{created_at}' => 'Data de criação',
            '{created_by}' => 'Responsável comercial',
            '{tolerance_days}' => 'Dias de tolerância',
            '{expiry_date}' => 'Data de validade',
            '{days_until_expiry}' => 'Dias até expirar',
            '{sub_total}' => 'Subtotal',
            '{total}' => 'Total',
            '{tax}' => 'Imposto',
            '{discount}' => 'Desconto',
            '{global_discount_amount}' => 'Desconto global',
            '{global_discount_percentage}' => 'Percentagem do desconto global',
            '{withholding_tax_amount}' => 'Retenção na fonte',
            '{withholding_tax_percentage}' => 'Percentagem da retenção',
            '{pricing_mode}' => 'Modo de preço',
            '{withhold_tax}' => 'Aplicação de retenção',
            '{observations}' => 'Observações',
            '{total_items}' => 'Total de itens',
            '{taxable_items}' => 'Itens tributáveis',
            '{items_table}' => 'Tabela de itens/serviços',
            '{items_list}' => 'Lista de itens/serviços',
            '{summary_table}' => 'Resumo financeiro',
            '{banking_details}' => 'Dados bancários',
            '{document_keywords}' => 'Palavras-chave documentais',
            '{lab_signature}' => 'Assinatura do laboratório',
            '{client_signature}' => 'Assinatura do cliente',
            '{signature_block}' => 'Bloco de assinatura',
            '{lab_name}' => 'Nome do laboratório',
            '{lab_details}' => 'Dados do laboratório',
            '{customer_details}' => 'Dados do cliente',
            '{bank_name}' => 'Nome do banco',
            '{bank_iban}' => 'IBAN',
            '{verification_url}' => 'Ligação pública de verificação',
            '{proposal_authenticity}' => 'QR e autenticidade da proposta',
            '{proposal_acceptance_evidence}' => 'Evidência de aceite do cliente',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getPlaceholders(): array
    {
        return array_keys(self::getPlaceholderLabels());
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
        $replacements['{verification_url}'] = self::verificationUrl($proposal);
        $replacements['{proposal_authenticity}'] = self::generateAuthenticityBlock($proposal);
        $replacements['{proposal_acceptance_evidence}'] = self::generateAcceptanceEvidenceBlock($proposal);

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

    private static function generateAuthenticityBlock(VAPProposal $proposal): string
    {
        $verificationUrl = self::verificationUrl($proposal);
        $status = $proposal->status_badge['text'] ?? $proposal->status ?? 'Pendente';
        $hash = $proposal->unique_hash ?: 'sem-hash';
        $shortHash = str($hash)->afterLast('-')->limit(18, '')->value();
        $qrDataUri = self::qrDataUri($verificationUrl);
        $escapedStatus = e($status);
        $escapedShortHash = e($shortHash);
        $escapedVerificationUrl = e($verificationUrl);

        return <<<HTML
<section style="padding:14px 16px; border:1px solid #ded3bf; border-radius:18px; background:#fffdf7;">
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:70%; vertical-align:top; padding-right:14px;">
                <div style="font-size:9px; letter-spacing:0.16em; text-transform:uppercase; color:#9a7a2f; font-weight:800;">Verificação da proposta</div>
                <div style="margin-top:8px; color:#20332f;">Documento verificável por código QR e ligação pública segura.</div>
                <div style="margin-top:8px; color:#58665f;">Estado: <strong style="color:#143d37;">{$escapedStatus}</strong></div>
                <div style="margin-top:4px; color:#58665f;">Código de controlo: <strong style="color:#143d37;">{$escapedShortHash}</strong></div>
                <div style="margin-top:8px; font-size:9px; color:#58665f; word-break:break-all;">{$escapedVerificationUrl}</div>
            </td>
            <td style="width:30%; vertical-align:top; text-align:right;">
                <img src="{$qrDataUri}" alt="QR de verificação da proposta" style="display:inline-block; width:92px; height:92px;" />
            </td>
        </tr>
    </table>
</section>
HTML;
    }

    private static function generateAcceptanceEvidenceBlock(VAPProposal $proposal): string
    {
        $proposal->loadMissing('complianceAgreement');

        $agreement = $proposal->complianceAgreement;
        $acceptedAt = $agreement?->acknowledged_at?->format('d/m/Y H:i');
        $rejectedAt = $agreement?->rejected_at?->format('d/m/Y H:i');

        if ($acceptedAt) {
            $title = 'Aceite pelo cliente';
            $summary = 'Conformidade, confidencialidade e imparcialidade reconhecidas no portal.';
            $tone = '#167a58';
            $evidenceRows = [
                'Aceite em' => $acceptedAt,
                'IP do cliente' => $agreement->client_ip,
                'Confidencialidade' => $agreement->confidentiality ? 'Confirmada' : 'Não confirmada',
                'Imparcialidade' => $agreement->impartiality ? 'Confirmada' : 'Não confirmada',
                'Não divulgação' => $agreement->nondisclosure ? 'Confirmada' : 'Não confirmada',
            ];
        } elseif ($rejectedAt) {
            $title = 'Rejeitada pelo cliente';
            $summary = $agreement?->rejection_reason ?: 'A proposta foi rejeitada pelo cliente.';
            $tone = '#b42318';
            $evidenceRows = [
                'Rejeitada em' => $rejectedAt,
                'IP do cliente' => $agreement?->client_ip,
            ];
        } else {
            $title = 'Aceite pendente';
            $summary = 'A proposta aguarda validação do cliente no portal.';
            $tone = '#9a7a2f';
            $evidenceRows = [
                'Estado' => $proposal->status_badge['text'] ?? $proposal->status ?? 'Pendente',
                'Validade' => $proposal->expiry_date?->format('d/m/Y'),
            ];
        }

        $rows = collect($evidenceRows)
            ->filter()
            ->map(fn (string $value, string $label): string => '<div style="display:flex; justify-content:space-between; gap:12px; padding:5px 0; border-bottom:1px solid #eee4d3;"><span style="color:#738076;">'.e($label).'</span><strong style="color:#143d37; text-align:right;">'.e($value).'</strong></div>')
            ->implode('');
        $escapedTitle = e($title);
        $escapedSummary = e($summary);

        return <<<HTML
<section style="padding:14px 16px; border:1px solid #ded3bf; border-radius:18px; background:#ffffff;">
    <div style="font-size:9px; letter-spacing:0.16em; text-transform:uppercase; color:#9a7a2f; font-weight:800;">Evidência de aceite</div>
    <div style="margin-top:8px; font-weight:800; color:{$tone};">{$escapedTitle}</div>
    <div style="margin-top:6px; color:#58665f;">{$escapedSummary}</div>
    <div style="margin-top:10px;">{$rows}</div>
</section>
HTML;
    }

    private static function verificationUrl(VAPProposal $proposal): string
    {
        if (! $proposal->unique_hash) {
            return '';
        }

        return route('vap-proposals.public.show', $proposal->unique_hash);
    }

    private static function qrDataUri(string $content): string
    {
        if ($content === '') {
            $content = 'Proposta sem código de verificação.';
        }

        $qrCode = new QrCode(
            data: $content,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 260,
            margin: 8,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(20, 61, 55),
            backgroundColor: new Color(255, 253, 247),
        );

        return (new SvgWriter)->write($qrCode)->getDataUri();
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
