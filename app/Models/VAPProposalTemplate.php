<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        ];
    }

    /**
     * Parse template content with proposal data
     * Updated to handle all new calculation fields
     */
    public static function parseContent(string $content, VAPProposal $proposal): string
    {
        // Basic proposal data
        $replacements = [
            '{proposal_number}' => $proposal->proposal_number ?? '',
            '{customer_name}' => $proposal->customer->name ?? '',
            '{customer_code}' => $proposal->customer->code ?? '',
            '{service_location}' => $proposal->service_location ?? '',
            '{department}' => $proposal->department->name ?? '',
            '{warehouse}' => $proposal->warehouse->address ?? '',
            '{created_at}' => $proposal->created_at ? $proposal->created_at->format('d/m/Y') : '',
            '{created_by}' => $proposal->user->name ?? '',
            '{tolerance_days}' => $proposal->tolerance_days ?? '',
            '{expiry_date}' => $proposal->expiry_date ? $proposal->expiry_date->format('d/m/Y') : '',
            '{days_until_expiry}' => $proposal->days_until_expiry ?? '',
            
            // Financial data - FIXED to use actual calculated values
            '{sub_total}' => number_format($proposal->sub_total, 2, ',', '.'),
            '{total}' => number_format($proposal->total, 2, ',', '.'),
            '{tax}' => number_format($proposal->tax ?? 0, 2, ',', '.'),
            '{discount}' => number_format($proposal->discount ?? 0, 2, ',', '.'),
            '{global_discount_amount}' => number_format($proposal->global_discount_amount ?? 0, 2, ',', '.'),
            '{global_discount_percentage}' => number_format($proposal->global_discount_percentage ?? 0, 2, ',', '.'),
            '{withholding_tax_amount}' => number_format($proposal->withholding_tax_amount ?? 0, 2, ',', '.'),
            '{withholding_tax_percentage}' => number_format($proposal->withholding_tax_percentage ?? 0, 2, ',', '.'),
            
            // Pricing mode
            '{pricing_mode}' => $proposal->use_matrix_price ? 'Preço de Matriz' : 'Preço de Parâmetro',
            '{withhold_tax}' => $proposal->withhold_tax ? 'Sim' : 'Não',
            
            // Observations
            '{observations}' => $proposal->obs ?? '',
        ];

        // Calculate total items count
        $totalItems = $proposal->items->count();
        $taxableItems = $proposal->items->where('charge_tax', true)->count();
        
        $replacements['{total_items}'] = $totalItems;
        $replacements['{taxable_items}'] = $taxableItems;

        // Items table generation
        if (strpos($content, '{items_table}') !== false) {
            $itemsTable = self::generateItemsTable($proposal);
            $replacements['{items_table}'] = $itemsTable;
        }

        // Items list generation
        if (strpos($content, '{items_list}') !== false) {
            $itemsList = self::generateItemsList($proposal);
            $replacements['{items_list}'] = $itemsList;
        }

        // Summary table generation
        if (strpos($content, '{summary_table}') !== false) {
            $summaryTable = self::generateSummaryTable($proposal);
            $replacements['{summary_table}'] = $summaryTable;
        }

        // Replace all placeholders
        $parsedContent = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $content
        );

        return $parsedContent;
    }

    /**
     * Generate HTML table for items
     */
    private static function generateItemsTable(VAPProposal $proposal): string
    {
        $html = '<table style="width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 9pt;">';
        $html .= '<thead>';
        $html .= '<tr style="background: #1e3a8a; color: white;">';
        $html .= '<th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Item</th>';
        $html .= '<th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Descrição</th>';
        $html .= '<th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Padrão</th>';
        $html .= '<th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Qtd</th>';
        $html .= '<th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Unid.</th>';
        $html .= '<th style="padding: 8px; border: 1px solid #ddd; text-align: right;">Preço Unit.</th>';
        $html .= '<th style="padding: 8px; border: 1px solid #ddd; text-align: right;">Desconto</th>';
        $html .= '<th style="padding: 8px; border: 1px solid #ddd; text-align: right;">Taxa</th>';
        $html .= '<th style="padding: 8px; border: 1px solid #ddd; text-align: right;">Total</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($proposal->items as $index => $item) {
            $rowStyle = $index % 2 === 0 ? 'background: #f9fafb;' : '';
            
            $html .= '<tr style="' . $rowStyle . '">';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; text-align: center; font-weight: bold;">' . ($index + 1) . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd;">';
            $html .= '<div style="font-weight: bold;">' . htmlspecialchars($item->item_description) . '</div>';
            
            if ($item->obs) {
                $html .= '<div style="font-size: 8pt; color: #666; margin-top: 2px;">' . htmlspecialchars($item->obs) . '</div>';
            }
            
            if ($item->itemable_type) {
                $typeLabel = str_contains($item->itemable_type, 'Matrix') ? 'Matriz' : 'Parâmetro';
                $html .= '<div style="font-size: 8pt; color: #666; margin-top: 2px;">' . $typeLabel . ' #' . $item->itemable_id . '</div>';
            }
            
            if (!$item->charge_tax) {
                $html .= '<div style="font-size: 8pt; color: #10b981; margin-top: 2px;">✓ Isento de taxa</div>';
            }
            
            if ($item->exemption_code) {
                $html .= '<div style="font-size: 8pt; color: #10b981; margin-top: 2px;">Código isenção: ' . $item->exemption_code . '</div>';
            }
            
            $html .= '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; text-align: center;">' . ($item->standard->code ?? '-') . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; text-align: center;">' . number_format($item->qty, 2, ',', '.') . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; text-align: center;">' . ($item->unit->code ?? '-') . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; text-align: right;">AOA ' . number_format($item->unit_price, 2, ',', '.') . '</td>';
            
            // Discount cell
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; text-align: right;">';
            if ($item->discount_amount > 0) {
                if ($item->discount_id == 1) {
                    $html .= '<div style="font-size: 8pt; color: #10b981;">' . number_format($item->discount_percentage, 2, ',', '.') . '%</div>';
                }
                $html .= '-AOA ' . number_format($item->discount_amount, 2, ',', '.');
            } else {
                $html .= '-';
            }
            $html .= '</td>';
            
            // Tax cell
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; text-align: right;">';
            if ($item->tax_amount > 0) {
                $html .= '<div style="font-size: 8pt; color: #3b82f6;">' . number_format($item->tax_percentage, 2, ',', '.') . '%</div>';
                $html .= '+AOA ' . number_format($item->tax_amount, 2, ',', '.');
            } else {
                $html .= '-';
            }
            $html .= '</td>';
            
            // Total cell
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; text-align: right; font-weight: bold;">';
            $html .= 'AOA ' . number_format($item->total, 2, ',', '.');
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
            $html .= '<li style="margin-bottom: 15px; padding: 10px; border-left: 3px solid #1e3a8a; background: #f8fafc;">';
            $html .= '<div style="font-weight: bold;">' . ($index + 1) . '. ' . htmlspecialchars($item->item_description) . '</div>';
            
            $details = [];
            if ($item->standard->code ?? false) {
                $details[] = 'Padrão: ' . $item->standard->code;
            }
            
            $details[] = 'Quantidade: ' . number_format($item->qty, 2, ',', '.') . ' ' . ($item->unit->code ?? '');
            $details[] = 'Preço unitário: AOA ' . number_format($item->unit_price, 2, ',', '.');
            
            if ($item->discount_amount > 0) {
                if ($item->discount_id == 1) {
                    $details[] = 'Desconto: ' . number_format($item->discount_percentage, 2, ',', '.') . '% (-AOA ' . number_format($item->discount_amount, 2, ',', '.') . ')';
                } else {
                    $details[] = 'Desconto: -AOA ' . number_format($item->discount_amount, 2, ',', '.');
                }
            }
            
            if ($item->tax_amount > 0) {
                $details[] = 'Taxa: ' . number_format($item->tax_percentage, 2, ',', '.') . '% (+AOA ' . number_format($item->tax_amount, 2, ',', '.') . ')';
            }
            
            $details[] = '<strong>Total: AOA ' . number_format($item->total, 2, ',', '.') . '</strong>';
            
            $html .= '<div style="font-size: 9pt; color: #4b5563; margin-top: 5px;">' . implode(' • ', $details) . '</div>';
            
            if ($item->obs) {
                $html .= '<div style="font-size: 8pt; color: #666; margin-top: 5px; font-style: italic;">' . htmlspecialchars($item->obs) . '</div>';
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
        $html .= '<td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: bold;">Subtotal:</td>';
        $html .= '<td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right; width: 150px;">';
        $html .= 'AOA ' . number_format($proposal->sub_total, 2, ',', '.');
        $html .= '</td>';
        $html .= '</tr>';
        
        // Discount
        if ($proposal->discount > 0) {
            $html .= '<tr>';
            $html .= '<td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: bold; color: #10b981;">Desconto Total:</td>';
            $html .= '<td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right; color: #10b981;">';
            $html .= '-AOA ' . number_format($proposal->discount, 2, ',', '.');
            $html .= '</td>';
            $html .= '</tr>';
        }
        
        // Tax
        if ($proposal->tax > 0) {
            $html .= '<tr>';
            $html .= '<td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: bold;">Taxa Total:</td>';
            $html .= '<td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right;">';
            $html .= '+AOA ' . number_format($proposal->tax, 2, ',', '.');
            $html .= '</td>';
            $html .= '</tr>';
        }
        
        // Global discount
        if ($proposal->global_discount_amount > 0) {
            $html .= '<tr>';
            $html .= '<td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: bold; color: #10b981;">Desconto Global:</td>';
            $html .= '<td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right; color: #10b981;">';
            $html .= '-AOA ' . number_format($proposal->global_discount_amount, 2, ',', '.');
            
            if ($proposal->global_discount_percentage > 0) {
                $html .= '<div style="font-size: 8pt;">(' . number_format($proposal->global_discount_percentage, 2, ',', '.') . '%)</div>';
            }
            
            $html .= '</td>';
            $html .= '</tr>';
        }
        
        // Withholding tax
        if ($proposal->withholding_tax_amount > 0) {
            $html .= '<tr>';
            $html .= '<td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: bold;">Imposto Retido:</td>';
            $html .= '<td style="padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: right;">';
            $html .= 'AOA ' . number_format($proposal->withholding_tax_amount, 2, ',', '.');
            
            if ($proposal->withholding_tax_percentage > 0) {
                $html .= '<div style="font-size: 8pt;">(' . number_format($proposal->withholding_tax_percentage, 2, ',', '.') . '%)</div>';
            }
            
            $html .= '</td>';
            $html .= '</tr>';
        }
        
        // Grand Total
        $html .= '<tr style="background: #f1f5f9;">';
        $html .= '<td style="padding: 12px; border-top: 2px solid #1e3a8a; text-align: right; font-weight: bold; font-size: 11pt; color: #1e3a8a;">TOTAL GERAL:</td>';
        $html .= '<td style="padding: 12px; border-top: 2px solid #1e3a8a; text-align: right; font-weight: bold; font-size: 11pt; color: #1e3a8a;">';
        $html .= 'AOA ' . number_format($proposal->total, 2, ',', '.');
        $html .= '</td>';
        $html .= '</tr>';
        
        // Summary footer
        $html .= '<tr>';
        $html .= '<td colspan="2" style="padding: 8px; text-align: right; font-size: 8pt; color: #666;">';
        $html .= $proposal->items->count() . ' itens • ';
        $html .= $proposal->items->where('tax_amount', '>', 0)->count() . ' tributáveis';
        $html .= '</td>';
        $html .= '</tr>';
        
        $html .= '</table>';
        
        return $html;
    }
}
