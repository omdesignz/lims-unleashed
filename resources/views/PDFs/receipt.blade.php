<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
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
            margin-top: 135mm;
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
<body>

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
                            <div style="font-size: 14px; font-weight: 600; color: #1e3a8a;">VAP Soluções</div>
                        </td>
                    </tr>
                    <tr>
                        <td align="center">
                            <div style="font-size: 16px; font-weight: 700; color: #111827;">{!! mb_strtoupper($settings->app_client_lab_name) !!}</div>
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
                            <img src="{{ public_path() . '/images/SVG/sncqa_logo.png' }}" width="15%" alt="">
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
                            <div style="font-size: 15px; font-weight: 600; color: white; font-weight: bold;">Recibo</div>
                            <div style="font-size: 11px; font-weight: 400; color: rgba(255, 255, 255, 0.9); margin-top: 2px;">
                                Processado por computador - {!! ($model->user ? $model->user->name : 'SISTEMA') !!}
                            </div>
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
                        <td width="33%" valign="top">
                            <div style="font-size: 11px; font-weight: bold; color: #374151; margin-bottom: 3px;">Referência</div>
                            <div style="font-size: 12px; font-weight: 600; color: #111827;">{!! $model->rec_no !!}</div>
                        </td>
                        <td width="33%" valign="top">
                            <div style="font-size: 11px; font-weight: bold; color: #374151; margin-bottom: 3px;">Data</div>
                            <div style="font-size: 12px; font-weight: 600; color: #111827;">{!! Carbon\Carbon::parse($model->date)->format('Y-m-d') !!}</div>
                        </td>
                        <td width="33%" valign="top">
                            <div style="font-size: 11px; font-weight: bold; color: #374151; margin-bottom: 3px;">Página</div>
                            <div style="font-size: 12px; font-weight: 600; color: #111827;">{PAGENO} / {nb}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</htmlpageheader>

<htmlpagefooter name="page-footer">
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 15px;">
        <tr>
            <td>
                <!-- Total Paid Amount Card -->
                <table width="100%" cellpadding="0" cellspacing="0" style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="padding: 15px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <div style="font-size: 13px; font-weight: 600; color: #111827; margin-bottom: 8px;">Resumo do Pagamento</div>
                                        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9fafb; border-radius: 6px; padding: 12px;">
                                            <tr>
                                                <td style="padding-bottom: 10px;">
                                                    <table width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td><div style="font-size: 11px; font-weight: bold; color: #374151;">Total Pago</div></td>
                                                            <td align="right"><div style="font-size: 11px; font-weight: 600;">{!! number_format($model->items()->sum('paid_amount'), 2, ',', '.') !!} AKZ</div></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-top: 10px; border-top: 2px solid #1e3a8a;">
                                                    <table width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td><div style="font-size: 12px; font-weight: 600; color: #111827;">Total Pago (AKZ)</div></td>
                                                            <td align="right"><div style="font-size: 16px; font-weight: 700; color: #1e3a8a;">{!! number_format($model->items()->sum('paid_amount'), 2, ',', '.') !!}</div></td>
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
    
    <table width="100%" cellpadding="0" cellspacing="0" style="border-top: 1px solid #e5e7eb; padding-top: 12px; margin-top: 10px;">
        <tr>
            <td>
                <!-- Legal/Footer Information -->
                <div style="font-size: 8p7; color: #6b7280; line-height: 1.4; margin-bottom: 8px;">
                    Processado por programa validado N. <span style="font-weight: 600;">{!! $settings->app_agt_validation_number !!}</span> &copy; {!! $settings->app_name !!}
                </div>
                
                <!-- Contact Information -->
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="60%" valign="top">
                            <div style="font-size: 8px; color: #6b7280; line-height: 1.4;">
                                {!! mb_strtoupper($settings->app_client_address) !!}<br>
                                {!! mb_strtoupper($settings->app_client_contact) !!}<br>
                                {!! mb_strtoupper($settings->app_client_email) !!}
                            </div>
                        </td>
                        <td width="40%" align="right" valign="top">
                            <img src="{!! public_path() . '/images/SVG/sncqa_logo.png' !!}" style="width: 60px; height: auto;" alt="VAP Solutions">
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</htmlpagefooter>

<!-- Main Content -->
<!-- Payment Items Table -->
<table width="100%" cellpadding="0" cellspacing="0" class="keep-together" style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 15px;">
    <thead>
        <tr>
            <th style="background-color: #f9fafb; font-size: 11px; font-weight: 600; color: #374151; text-align: left; padding: 12px 10px; border-bottom: 1px solid #e5e7eb; width: 120px;">Documento</th>
            <th style="background-color: #f9fafb; font-size: 11px; font-weight: 600; color: #374151; text-align: left; padding: 12px 10px; border-bottom: 1px solid #e5e7eb; width: 80px;">Data</th>
            <th style="background-color: #f9fafb; font-size: 11px; font-weight: 600; color: #374151; text-align: right; padding: 12px 10px; border-bottom: 1px solid #e5e7eb; width: 100px;">Valor Por Regularizar</th>
            <th style="background-color: #f9fafb; font-size: 11px; font-weight: 600; color: #374151; text-align: right; padding: 12px 10px; border-bottom: 1px solid #e5e7eb; width: 100px;">Regularizado</th>
            <th style="background-color: #f9fafb; font-size: 11px; font-weight: 600; color: #374151; text-align: right; padding: 12px 10px; border-bottom: 1px solid #e5e7eb; width: 100px;">Saldo Pendente</th>
        </tr>
    </thead>
    <tbody>
        @foreach($model->items as $item)
            <tr>
                <td style="font-size: 11px; color: #374151; text-align: left; padding: 12px 10px; border-bottom: 1px solid #f3f4f6;">
                    <div style="font-weight: 600; color: #1e3a8a;">{!! $item->invoice->inv_no !!}</div>
                    @if($item->invoice->invoice_category)
                        <div style="font-size: 10px; color: #6b7280; margin-top: 2px;">{!! $item->invoice->invoice_category->name !!}</div>
                    @endif
                </td>
                <td style="font-size: 11px; color: #374151; text-align: left; padding: 12px 10px; border-bottom: 1px solid #f3f4f6;">
                    {!! $model->date !!}
                </td>
                <td style="font-size: 11px; color: #374151; text-align: right; padding: 12px 10px; border-bottom: 1px solid #f3f4f6;">
                    {!! number_format($item->invoice_pending_amount, 2, ',', '.') !!}
                </td>
                <td style="font-size: 11px; color: #1e3a8a; font-weight: 600; text-align: right; padding: 12px 10px; border-bottom: 1px solid #f3f4f6;">
                    {!! number_format($item->paid_amount, 2, ',', '.') !!}
                </td>
                <td style="font-size: 11px; color: #374151; text-align: right; padding: 12px 10px; border-bottom: 1px solid #f3f4f6;">
                    <div style="display: inline-block; padding: 2px 8px; border-radius: 4px; background-color: {!! $item->pending_amount > 0 ? '#fef3c7' : '#d1fae5' !!}; color: {!! $item->pending_amount > 0 ? '#92400e' : '#065f46' !!}; font-size: 10px; font-weight: bold;">
                        {!! number_format($item->pending_amount, 2, ',', '.') !!}
                    </div>
                </td>
            </tr>
            @if(!$loop->last)
            <tr>
                <td colspan="5" style="padding: 0 10px;">
                    <div style="height: 1px; background-color: #f3f4f6;"></div>
                </td>
            </tr>
            @endif
        @endforeach
    </tbody>
</table>

<!-- Payment Summary Section -->
@if($model->items->count() > 0)
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 20px;">
        <tr>
            <td>
                <table width="100%" cellpadding="0" cellspacing="0" style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="padding: 15px;">
                            <div style="font-size: 13px; font-weight: 600; color: #111827; margin-bottom: 12px;">Resumo de Pagamentos</div>
                            
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding-bottom: 10px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="50%" valign="top">
                                                    <div style="font-size: 11px; font-weight: bold; color: #374151; margin-bottom: 4px;">Total de Documentos</div>
                                                    <div style="font-size: 13px; font-weight: 600; color: #1e3a8a;">{!! $model->items->count() !!}</div>
                                                </td>
                                                <td width="50%" valign="top">
                                                    <div style="font-size: 11px; font-weight: bold; color: #374151; margin-bottom: 4px;">Valor Total Regularizado</div>
                                                    <div style="font-size: 13px; font-weight: 600; color: #1e3a8a;">{!! number_format($model->items->sum('paid_amount'), 2, ',', '.') !!} AKZ</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top: 10px; border-top: 1px solid #e5e7eb;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td>
                                                    <div style="font-size: 11px; font-weight: bold; color: #374151; margin-bottom: 4px;">Saldo Pendente Total</div>
                                                    <div style="font-size: 13px; font-weight: 600; color: #dc2626;">
                                                        {!! number_format($model->items->sum('pending_amount'), 2, ',', '.') !!} AKZ
                                                    </div>
                                                    @if($model->items->sum('pending_amount') == 0)
                                                        <div style="font-size: 10px; color: #10b981; margin-top: 2px; display: inline-block; padding: 2px 8px; background-color: #d1fae5; border-radius: 4px;">
                                                            ✓ Todos os pagamentos regularizados
                                                        </div>
                                                    @else
                                                        <div style="font-size: 10px; color: #f59e0b; margin-top: 2px; display: inline-block; padding: 2px 8px; border-radius: 4px;">
                                                            Pendente: {!! number_format($model->items->sum('pending_amount'), 2, ',', '.') !!} AKZ
                                                        </div>
                                                    @endif
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
@endif

<!-- Additional Notes Section -->
@if($model->obs)
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 15px;">
        <tr>
            <td>
                <table width="100%" cellpadding="0" cellspacing="0" style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="padding: 15px;">
                            <div style="font-size: 11px; font-weight: 600; color: #111827; margin-bottom: 8px;">Observações</div>
                            <div style="font-size: 11px; color: #6b7280; line-height: 1.5; padding: 10px; background-color: #f9fafb; border-radius: 6px;">
                                {!! $model->obs !!}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
@endif

</body>
</html>