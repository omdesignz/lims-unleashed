<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @include('PDFs.partials.premium-document-style')

        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            margin: 0;
            padding: 0;
            color: #374151;
        }
        
        @page {
            margin: 105mm 10mm 82mm 10mm; /* 10mm left/right margins */
            header: page-header;
            footer: page-footer;
            margin-header: 10mm;
            margin-footer: 15mm;
        }
        
        @page :first {
            margin-top: 125mm;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .keep-together {
            page-break-inside: avoid;
        }
        
        table {
            table-layout: auto;
            width: 100%;
        }
    </style>
</head>
<body class="pdf-document commercial-document">

<htmlpageheader name="page-header">
    {{-- <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 15px;">
        <tr>
            <td align="center">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="center" style="padding-bottom: 10px;">
                            <img src="{!! public_path() . '/images/aocrest.svg' !!}" style="width: 57px; height: 69px;" alt="">
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-bottom: 5px;">
                            <div style="font-size: 14px; font-weight: 600; color: #1e3a8a;">SERVIÇO NACIONAL DE CONTROLO DE QUALIDADE DE ALIMENTOS</div>
                        </td>
                    </tr>
                    <tr>
                        <td align="center">
                            <div style="font-size: 16px; font-weight: 700; color: #111827;">{!! mb_strtoupper($settings->app_client_lab_name) ?? 'LABORATÓRIO CENTRAL AGRO-ALIMENTAR DE LUANDA' !!}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table> --}}

    <div style="text-align: center; border-bottom:0.3mm solid black;padding-bottom: 0px" width="100%" >
        <center><img src="{!! public_path() . '/images/ao_crest.svg'!!}" width="8%"></center>
        <h6>REPÚBLICA DE ANGOLA</h6>
        <h6>MINISTÉRIO DA AGRICULTURA E FLORESTAS</h6>
        <h6>SERVIÇO NACIONAL DE CONTROLO DA QUALIDADE DOS ALIMENTOS</h6>
        <h6>LABORATÓRIO CENTRAL AGRO-ALIMENTAR DE LUANDA</h6>
    </div><br>

    <!-- Main Header Card -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <tr>
            <td style="padding: 15px; border-bottom: 1px solid #e5e7eb;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="45%" valign="top">
                            @include('PDFs.partials.brand-logo', ['settings' => $settings, 'width' => '15%'])
                            <div style="font-size: 15px; font-weight: 600; color: #1e3a8a; margin-bottom: 5px;">{!! mb_strtoupper($settings->app_client_name) !!}</div>
                            <div style="font-size: 10px; color: #6b7280; line-height: 1.4;">
                                {!! mb_strtoupper($settings->app_client_address) !!}<br>
                                Nº CONTRIBUINTE: {!! mb_strtoupper($settings->app_client_nif) !!}
                            </div>
                        </td>
                        <td width="10%"></td>
                        <td width="45%" valign="top">
                            <div style="font-size: 15px; font-weight: 600; color: #1e3a8a; margin-bottom: 5px;">{!! $model?->customer?->name !!}</div>
                            <div style="font-size: 10px; color: #6b7280; line-height: 1.4;">
                                {!! $model?->warehouse?->address !!}<br>
                                Nº CONTRIBUINTE: {!! (empty($model->warehouse->nif) || is_null($model->warehouse->nif) ? 'Consumidor Final' : $model->warehouse->nif) !!}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="background: linear-gradient(to right, #1e3a8a, #1e40af); color: white; padding: 12px 15px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            <div style="font-size: 15px; font-weight: 600; color: white; font-weight:bold;">Factura Proforma</div>
                        </td>
                        <td align="right">
                            <span style="display: inline-block; color: white; padding: 4px 12px; font-size: 11px; font-weight: bold;">
                                Original
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 12px 15px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="25%" valign="top">
                            <div style="font-size: 11px; font-weight: 500; color: #374151; margin-bottom: 3px;">Referência</div>
                            <div style="font-size: 12px; font-weight: 600; color: #111827;">{!! $model->quote_no !!}</div>
                        </td>
                        <td width="25%" valign="top">
                            <div style="font-size: 11px; font-weight: 500; color: #374151; margin-bottom: 3px;">LOTE</div>
                            <div style="font-size: 12px; font-weight: 600; color: #111827;">{!! $model->internal_ref ?? 'N/A' !!}</div>
                        </td>
                        <td width="25%" valign="top">
                            <div style="font-size: 11px; font-weight: 500; color: #374151; margin-bottom: 3px;">Data</div>
                            <div style="font-size: 12px; font-weight: 600; color: #111827;">{!! Carbon\Carbon::parse($model->date)->format('Y-m-d') !!}</div>
                        </td>
                        <td width="25%" valign="top">
                            <div style="font-size: 11px; font-weight: 500; color: #374151; margin-bottom: 3px;">Vencimento</div>
                            <div style="font-size: 12px; font-weight: 600; color: #111827;">{!! Carbon\Carbon::parse($model->date)->addDays(15)->format('Y-m-d') !!}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</htmlpageheader>

<htmlpagefooter name="page-footer">
    @if(count($model->items->chunk(12)) < 2)
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 15px;">
            <tr>
                <td>
                    <table width="100%" cellpadding="0" cellspacing="0" style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <tr>
                            <td style="padding: 15px;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td width="55%" valign="top" style="padding-right: 15px;">
                                            <table width="100%" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="background: linear-gradient(to right, #1e3a8a, #1e40af); color: white; padding: 8px 12px; border-radius: 6px 6px 0 0;">
                                                        <div style="font-size: 12px; font-weight: 600; color: white; font-weight:bold;">RESUMO DE IMPOSTOS</div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 6px 6px;">
                                                        <table width="100%" cellpadding="0" cellspacing="0">
                                                            <tr>
                                                                <td width="33%" valign="top" style="padding-right: 10px;">
                                                                    <div style="font-size: 10px; font-weight: 500; color: #374151; margin-bottom: 4px;">TAXA DO IMPOSTO</div>
                                                                    <div style="font-size: 13px; font-weight: 600; color: #1e3a8a;">
                                                                        {!! ($model->items()->whereChargeTax(1)->count() > 0 ? number_format($model->items()->whereChargeTax(1)->first()->tax_percentage, 2, ',', '.') : number_format(0, 2, ',', '.')) !!}%
                                                                    </div>
                                                                </td>
                                                                <td width="33%" valign="top" style="padding: 0 10px;">
                                                                    <div style="font-size: 10px; font-weight: 500; color: #374151; margin-bottom: 4px;">BASE DE INCIDÊNCIA</div>
                                                                    <div style="font-size: 13px; font-weight: 600; color: #1e3a8a;">
                                                                        {!! number_format($model->items->sum(function($it){if($it->charge_tax){return $it->total;}}), 2, ',', '.') !!}
                                                                    </div>
                                                                </td>
                                                                <td width="33%" valign="top" style="padding-left: 10px;">
                                                                    <div style="font-size: 10px; font-weight: 500; color: #374151; margin-bottom: 4px;">VALOR DO IMPOSTO</div>
                                                                    <div style="font-size: 13px; font-weight: 600; color: #1e3a8a;">
                                                                        {!! number_format($model->items->sum('tax_amount'), 2, ',', '.') !!}
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        
                                                        @if($model->items()->whereChargeTax(0)->count() > 0)
                                                        <div style="font-size: 10px; color: #6b7280; margin-top: 8px; padding: 6px 8px; background-color: #ffffff; border-radius: 4px; border: 1px solid #e5e7eb;">
                                                            {!! $model->items()->where('charge_tax', 0)->first()->exemption_code . ' - ' . $model->items()->where('charge_tax', false)->first()->exemption->reason !!}
                                                        </div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td width="45%" valign="top">
                                            <table width="100%" cellpadding="0" cellspacing="0" style="background: white; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px;">
                                                <tr>
                                                    <td style="padding-bottom: 8px;">
                                                        <table width="100%" cellpadding="0" cellspacing="0">
                                                            <tr>
                                                                <td><div style="font-size: 11px; font-weight: 500; color: #374151;">Total Ilíquido</div></td>
                                                                <td align="right"><div style="font-size: 11px; font-weight: 600;">{!! number_format($model->items->sum('total') + $model->items->sum('tax_amount') + $model->items->sum('discount_amount'), 2, ',', '.') !!}</div></td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px 0; border-top: 1px solid #f3f4f6;">
                                                        <table width="100%" cellpadding="0" cellspacing="0">
                                                            <tr>
                                                                <td><div style="font-size: 11px; font-weight: 500; color: #374151;">Descontos</div></td>
                                                                <td align="right"><div style="font-size: 11px; font-weight: 600; color: #dc2626;">-{!! number_format($model->items->sum('discount_amount'), 2, ',', '.') !!}</div></td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px 0; border-top: 1px solid #f3f4f6;">
                                                        <table width="100%" cellpadding="0" cellspacing="0">
                                                            <tr>
                                                                <td><div style="font-size: 11px; font-weight: 500; color: #374151;">Valor do Imposto (IVA)</div></td>
                                                                <td align="right"><div style="font-size: 11px; font-weight: 600;">{!! number_format($model->items->sum('tax_amount'), 2, ',', '.') !!}</div></td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding-top: 12px; border-top: 2px solid #1e3a8a;">
                                                        <table width="100%" cellpadding="0" cellspacing="0">
                                                            <tr>
                                                                <td><div style="font-size: 12px; font-weight: 600; color: #111827;">Total a Pagar (AKZ)</div></td>
                                                                <td align="right"><div style="font-size: 15px; font-weight: 700; color: #1e3a8a;">{!! number_format($model->total, 2, ',', '.') !!}</div></td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        
        <!-- Bank Coordinates Section for Quotes -->
        {{-- <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 15px;">
            <tr>
                <td>
                    <table width="100%" cellpadding="0" cellspacing="0" style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <tr>
                            <td style="padding: 15px;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="background: linear-gradient(to right, #1e3a8a, #1e40af); color: white; padding: 8px 12px; border-radius: 6px 6px 0 0;">
                                            <div style="font-size: 12px; font-weight: 600; color: white; font-weight: bold;">COORDENADAS BANCÁRIAS</div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 6px 6px;">
                                            <table width="100%" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td width="25%" valign="top">
                                                        <div style="font-size: 10px; font-weight: 500; color: #374151; margin-bottom: 4px;">BANCO</div>
                                                        <div style="font-size: 11px; font-weight: 600; color: #1e3a8a;">BPC</div>
                                                    </td>
                                                    <td width="25%" valign="top">
                                                        <div style="font-size: 10px; font-weight: 500; color: #374151; margin-bottom: 4px;">MOEDA</div>
                                                        <div style="font-size: 11px; font-weight: 600; color: #1e3a8a;">AKZ</div>
                                                    </td>
                                                    <td width="25%" valign="top">
                                                        <div style="font-size: 10px; font-weight: 500; color: #374151; margin-bottom: 4px;">N. CONTA</div>
                                                        <div style="font-size: 11px; font-weight: 600; color: #1e3a8a;">0005120735011</div>
                                                    </td>
                                                    <td width="25%" valign="top">
                                                        <div style="font-size: 10px; font-weight: 500; color: #374151; margin-bottom: 4px;">IBAN</div>
                                                        <div style="font-size: 11px; font-weight: 600; color: #1e3a8a;">AO06 0010 0005 0012 0735 011 79</div>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table> --}}
    @endif
    
    <table width="100%" cellpadding="0" cellspacing="0" style="border-top: 1px solid #e5e7eb; padding-top: 12px; margin-top: 10px;">
        <tr>
            <td>
                <div style="font-size: 10px; color: #6b7280; line-height: 1.4; margin-bottom: 8px; font-weight: bold;">
                    Este documento não serve de factura. O pagamento desta factura deve ser efectuado por meio do RUPE (Referência Única de Pagamento ao Estado).
                </div>
                <div style="font-size: 9px; color: #6b7280; line-height: 1.4;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="font-size: 8pt;">
                                {!! $model->unique_hash[0] . $model->unique_hash[10] . $model->unique_hash[20] . $model->unique_hash[30] !!} - Processado por programa validado N. <span style="font-weight: 600;">{!! $settings->app_agt_validation_number !!}</span>
                            </td>
                            <td align="right" style="font-size: 8pt;">
                                <span style="font-weight: bold;">&copy; {!! $settings->app_name !!}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>
</htmlpagefooter>

<!-- Main Content -->
@php
    $carry = null; 
    $chunk_total = null;
    $chunks = $model->items->chunk(13);
@endphp

@foreach ($chunks as $key => $chunk)
    @if(!$loop->first && $loop->count > 1)
        @php
            $carry = $carry + $chunk_total;
            $chunk_total = $carry + $chunk->sum('total');
        @endphp
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 8px; background-color: #f9fafb; border-radius: 6px; padding: 8px;">
            <tr>
                <td>
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td><div style="font-size: 11px; font-weight: 600; color: #374151;">Valor Transportado:</div></td>
                            <td align="right"><div style="font-size: 11px; font-weight: 700; color: #1e3a8a;">{!! number_format($carry, 2, ',', '.') !!}</div></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    @else
        @php
            $chunk_total = $carry + $chunk->sum('total');
        @endphp
    @endif

    <!-- Items Table Card -->
    <table width="100%" cellpadding="0" cellspacing="0" class="keep-together report-table" style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <thead>
            <tr>
                <th style="background-color: #f9fafb; font-size: 10px; font-weight: 600; color: #374151; text-align: left; padding: 10px 8px; border-bottom: 1px solid #e5e7eb; width: 35px; font-weight:bold;">Artigo</th>
                <th style="background-color: #f9fafb; font-size: 10px; font-weight: 600; color: #374151; text-align: left; padding: 10px 8px; border-bottom: 1px solid #e5e7eb; font-weight:bold;">Descrição</th>
                <th style="background-color: #f9fafb; font-size: 10px; font-weight: 600; color: #374151; text-align: right; padding: 10px 8px; border-bottom: 1px solid #e5e7eb; width: 40px; font-weight:bold;">Qtd.</th>
                <th style="background-color: #f9fafb; font-size: 10px; font-weight: 600; color: #374151; text-align: right; padding: 10px 8px; border-bottom: 1px solid #e5e7eb; width: 35px; font-weight:bold;">Un.</th>
                <th style="background-color: #f9fafb; font-size: 10px; font-weight: 600; color: #374151; text-align: right; padding: 10px 8px; border-bottom: 1px solid #e5e7eb; width: 70px; font-weight:bold;">Pr. UN.</th>
                <th style="background-color: #f9fafb; font-size: 10px; font-weight: 600; color: #374151; text-align: right; padding: 10px 8px; border-bottom: 1px solid #e5e7eb; width: 45px; font-weight:bold;">Desc.</th>
                <th style="background-color: #f9fafb; font-size: 10px; font-weight: 600; color: #374151; text-align: right; padding: 10px 8px; border-bottom: 1px solid #e5e7eb; width: 60px; font-weight:bold;">Taxa %</th>
                <th style="background-color: #f9fafb; font-size: 10px; font-weight: 600; color: #374151; text-align: right; padding: 10px 8px; border-bottom: 1px solid #e5e7eb; width: 75px; font-weight:bold;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($chunk as $item)
                <tr>
                    <td style="font-size: 10px; color: #6b7280; text-align: left; padding: 10px 8px; border-bottom: 1px solid #f3f4f6;">{!! str_pad($item->item_id, 4, '0', STR_PAD_LEFT) !!}</td>
                    <td style="text-align: left; padding: 10px 8px; border-bottom: 1px solid #f3f4f6;">
                        <div style="font-size: 10px; color: #374151; margin-bottom: 3px;">{!! $item->item_description !!}</div>
                        @if($item->itemable && $item->itemable->code?->code)
                            <div style="font-size: 9px; color: #6b7280;">({!! $item->itemable->code->code !!})</div>
                        @endif
                    </td>
                    <td style="font-size: 10px; color: #374151; text-align: right; padding: 10px 8px; border-bottom: 1px solid #f3f4f6;">{!! $item->qty !!}</td>
                    <td style="font-size: 10px; color: #6b7280; text-align: right; padding: 10px 8px; border-bottom: 1px solid #f3f4f6;">{!! $item?->unit?->name ?? 'UN' !!}</td>
                    <td style="font-size: 10px; font-weight: 500; color: #374151; text-align: right; padding: 10px 8px; border-bottom: 1px solid #f3f4f6;">{!! number_format($item->unit_price + $item->discount_amount, 2, ',', '.') !!}</td>
                    <td style="font-size: 10px; color: #6b7280; text-align: right; padding: 10px 8px; border-bottom: 1px solid #f3f4f6;">{!! number_format($item->discount_percentage, 2, ',', '.') !!}%</td>
                    <td style="text-align: right; padding: 10px 8px; border-bottom: 1px solid #f3f4f6;">
                        <div style="font-size: 10px; font-weight: 500; color: #374151;">{!! number_format($item->tax_percentage, 2, ',', '.') !!}%</div>
                        @if(!$item->charge_tax)
                            <div style="font-size: 9px; color: #6b7280;">({!! $item->exemption_code !!})</div>
                        @endif
                    </td>
                    <td style="font-size: 10px; font-weight: 600; color: #1e3a8a; text-align: right; padding: 10px 8px; border-bottom: 1px solid #f3f4f6;">{!! number_format($item->total, 2, ',', '.') !!}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($loop->count > 1 && !$loop->last)
        @php
            $carry = $carry ?? 0;
            $chunk_total = $carry + $chunk->sum('total');
        @endphp
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 8px; background-color: #f9fafb; border-radius: 6px; padding: 8px; border-bottom: 2px solid #1e3a8a;">
            <tr>
                <td>
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td><div style="font-size: 11px; font-weight: 600; color: #374151;">Valor a Transportar:</div></td>
                            <td align="right"><div style="font-size: 11px; font-weight: 700; color: #1e3a8a;">{!! number_format($chunk_total, 2, ',', '.') !!}</div></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    @else
        @php
            $chunk_total = ($carry ?? 0) + $chunk->sum('total');
        @endphp
    @endif

    @if($loop->count > 1 && !$loop->last)
        <pagebreak />
    @endif
@endforeach

@if(count($chunks) > 1)
    <!-- Final Summary Section for Multi-page Quotes -->
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 15px;">
        <tr>
            <td>
                <!-- Summary Tables -->
                <table width="100%" cellpadding="0" cellspacing="0" style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 15px;">
                    <tr>
                        <td style="padding: 15px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="55%" valign="top" style="padding-right: 15px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="background: linear-gradient(to right, #1e3a8a, #1e40af); color: white; padding: 10px 12px; border-radius: 8px 8px 0 0;">
                                                    <div style="font-size: 12px; font-weight: 600; color: white;">RESUMO DE IMPOSTOS</div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 15px; background: #f9fafb; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
                                                    <table width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td width="33%" valign="top" style="padding-right: 10px;">
                                                                <div style="font-size: 10px; font-weight: 500; color: #374151; margin-bottom: 6px;">TAXA DO IMPOSTO</div>
                                                                <div style="font-size: 14px; font-weight: 600; color: #1e3a8a;">
                                                                    {!! ($model->items()->whereChargeTax(1)->count() > 0 ? number_format($model->items()->whereChargeTax(1)->first()->tax_percentage, 2, ',', '.') : number_format(0, 2, ',', '.')) !!}%
                                                                </div>
                                                            </td>
                                                            <td width="33%" valign="top" style="padding: 0 10px;">
                                                                <div style="font-size: 10px; font-weight: 500; color: #374151; margin-bottom: 6px;">BASE DE INCIDÊNCIA</div>
                                                                <div style="font-size: 14px; font-weight: 600; color: #1e3a8a;">
                                                                    {!! number_format($model->items->sum(function($it){if($it->charge_tax){return $it->total;}}), 2, ',', '.') !!}
                                                                </div>
                                                            </td>
                                                            <td width="33%" valign="top" style="padding-left: 10px;">
                                                                <div style="font-size: 10px; font-weight: 500; color: #374151; margin-bottom: 6px;">VALOR DO IMPOSTO</div>
                                                                <div style="font-size: 14px; font-weight: 600; color: #1e3a8a;">
                                                                    {!! number_format($model->items->sum('tax_amount'), 2, ',', '.') !!}
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    
                                                    @if($model->items()->whereChargeTax(0)->count() > 0)
                                                    <div style="font-size: 10px; color: #6b7280; margin-top: 10px; padding: 8px; background-color: #ffffff; border-radius: 6px; border: 1px solid #e5e7eb;">
                                                        {!! $model->items()->where('charge_tax', 0)->first()->exemption_code . ' - ' . $model->items()->where('charge_tax', false)->first()->exemption->reason !!}
                                                    </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td width="45%" valign="top">
                                        <table width="100%" cellpadding="0" cellspacing="0" style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px;">
                                            <tr>
                                                <td style="padding-bottom: 10px;">
                                                    <table width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td><div style="font-size: 11px; font-weight: 500; color: #374151;">Total Ilíquido</div></td>
                                                            <td align="right"><div style="font-size: 11px; font-weight: 600;">{!! number_format($model->items->sum('total') + $model->items->sum('tax_amount') + $model->items->sum('discount_amount'), 2, ',', '.') !!}</div></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 10px 0; border-top: 1px solid #f3f4f6;">
                                                    <table width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td><div style="font-size: 11px; font-weight: 500; color: #374151;">Descontos</div></td>
                                                            <td align="right"><div style="font-size: 11px; font-weight: 600; color: #dc2626;">-{!! number_format($model->items->sum('discount_amount'), 2, ',', '.') !!}</div></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 10px 0; border-top: 1px solid #f3f4f6;">
                                                    <table width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td><div style="font-size: 11px; font-weight: 500; color: #374151;">Valor do Imposto (IVA)</div></td>
                                                            <td align="right"><div style="font-size: 11px; font-weight: 600;">{!! number_format($model->items->sum('tax_amount'), 2, ',', '.') !!}</div></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-top: 15px; border-top: 2px solid #1e3a8a;">
                                                    <table width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td><div style="font-size: 12px; font-weight: 600; color: #111827;">Total a Pagar (AKZ)</div></td>
                                                            <td align="right"><div style="font-size: 15px; font-weight: 700; color: #1e3a8a;">{!! number_format($model->total, 2, ',', '.') !!}</div></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                
                <!-- Bank Coordinates for Multi-page Quotes -->
                {{-- <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 15px;">
                    <tr>
                        <td>
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                <tr>
                                    <td style="padding: 15px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="background: linear-gradient(to right, #1e3a8a, #1e40af); color: white; padding: 8px 12px; border-radius: 6px 6px 0 0;">
                                                    <div style="font-size: 12px; font-weight: 600; color: white; font-weight:bold;">COORDENADAS BANCÁRIAS</div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 6px 6px;">
                                                    <table width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td width="25%" valign="top">
                                                                <div style="font-size: 10px; font-weight: 500; color: #374151; margin-bottom: 4px;">BANCO</div>
                                                                <div style="font-size: 11px; font-weight: 600; color: #1e3a8a;">BPC</div>
                                                            </td>
                                                            <td width="25%" valign="top">
                                                                <div style="font-size: 10px; font-weight: 500; color: #374151; margin-bottom: 4px;">MOEDA</div>
                                                                <div style="font-size: 11px; font-weight: 600; color: #1e3a8a;">AKZ</div>
                                                            </td>
                                                            <td width="25%" valign="top">
                                                                <div style="font-size: 10px; font-weight: 500; color: #374151; margin-bottom: 4px;">N. CONTA</div>
                                                                <div style="font-size: 11px; font-weight: 600; color: #1e3a8a;">0005120735011</div>
                                                            </td>
                                                            <td width="25%" valign="top">
                                                                <div style="font-size: 10px; font-weight: 500; color: #374151; margin-bottom: 4px;">IBAN</div>
                                                                <div style="font-size: 11px; font-weight: 600; color: #1e3a8a;">AO06 0010 0005 0012 0735 011 79</div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table> --}}
            </td>
        </tr>
    </table>
@endif

</body>
</html>
