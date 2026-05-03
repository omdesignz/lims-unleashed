<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link href="http://app.mediagagroalimentar.com/public/css/bootstrap.min.css"> 
    <style>
        body {font-family: sans-serif;
        
            /* font-size: 10pt; */
            margin-top: 0px;
        }
        .tg  {border-collapse:collapse;border-spacing:0;border-color:#ccc;}
        .tg td{font-family:Arial, sans-serif;font-size:10;padding:10px 5px;border-style:solid;border-width:0px;overflow:hidden;word-break:normal;border-color:#ccc;color:#000;}
        .tg th{font-family:Arial, sans-serif;font-size:10;font-weight:normal;padding:10px 5px;border-style:solid;border-width:0px;overflow:hidden;word-break:normal;border-color:#ccc;color:#000;}
        .tg .tg-lboi{border-color:inherit;text-align:left;vertical-align:top}
        @page {
            @if(count($model->items->chunk(12)) < 2)
                margin-top: 95mm;
                margin-bottom: 82mm;
            @else
            
            margin-top: 95mm;
            margin-bottom: 27mm;
            
            @endif
            header: page-header;
            footer: page-footer;
            margin-header: 15mm;
	        margin-footer: 15mm;
            background-image: url("{!! public_path() . '/images/oficio_template.svg'!!}") no-repeat 0 0;
            background-image-resize: 6;
        }

        .barcode {
            padding: 1.5mm;
            margin: 0;
            vertical-align: top;
            color: #000000;
        }
    </style>
</head>
<body>
<sethtmlpagefooter name="page-footer" value="on" />
<sethtmlpageheader name="page-header" value="on" />


<htmlpagefooter name="page-footer">

@if(count($model->items->chunk(13)) < 2)

<table class="tg" width="100%">

<tr>
    <td rowspan="3" colspan="5" class="tg-lboi" style="border-top: 0.3mm solid black;border-left: 0.3mm solid black;padding:5px 5px;border-right: 0.3mm solid black;">
    <table class="tg">
    <tr>
        <th colspan="8" class="tg-lboi" style="font-size:8px;padding:5px 5px;font-weight: bold;border-bottom: 0.2mm solid black;">
            RESUMO DE IMPOSTOS
        </th>
    </tr>
    <tr>
        <th colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;font-weight: bold;">
            TAXA DO IMPOSTO
        </th>
        <th colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;font-weight: bold;">
            BASE DE INCIDÊNCIA
        </th>
        <th colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;font-weight: bold;">
            VALOR DO IMPOSTO
        </th>
    </tr>
    <tr>
        <td colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;">
            {!! ($model->items()->whereChargeTax(1)->count() > 0 ? number_format($model->items()->whereChargeTax(1)->first()->tax_percentage, 2, ',', '.') : number_format(0, 2, ',', '.')) !!}
        </td>
        <td colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;">
            {!! number_format($model->items->sum(function($it){if($it->charge_tax){return $it->total;}}), 2, ',', '.') !!}
        </td>
        <td colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;">
             {!! number_format($model->items->sum('tax_amount'), 2, ',', '.') !!}
           
        </td>
    </tr>
    <tr>
        <td colspan="6" style="font-size:8px;">
            @if($model->items()->whereChargeTax(0)->count() > 0)
            {!! $model->items()->where('charge_tax', 0)->first()->exemption_code . ' - ' .$model->items()->where('charge_tax', false)->first()->exemption->reason !!}
            @endif
        </td>
    </tr>
    </table>
    
    </td>
    <td colspan="2" class="tg-lboi" style="border-top: 0.3mm solid black;padding:5px 5px;">
        Total Ilíquido
    </td>
    <td colspan="2" class="tg-lboi" style="border-top: 0.3mm solid black;padding:5px 5px;text-align:right;border-right: 0.3mm solid black;">
        {!! number_format($model->items->sum('total') + $model->items->sum('tax_amount') + $model->items->sum('discount_amount'), 2, ',', '.') !!}
    </td>
</tr>
<tr>

    <td colspan="2" class="tg-lboi" style="padding:5px 5px;">
        Descontos
    </td>
    <td colspan="2" class="tg-lboi" style="padding:5px 5px;text-align:right;border-right: 0.3mm solid black;">
        {!! number_format($model->items->sum('discount_amount'), 2, ',', '.') !!}
    </td>
</tr>
<tr>
    
    <td colspan="2" class="tg-lboi" style="padding:5px 5px;">
        Valor do Imposto (IVA)
    </td>
    <td colspan="2" class="tg-lboi" style="padding:5px 5px;text-align:right;border-right: 0.3mm solid black;">
        {!! number_format($model->items->sum('tax_amount'), 2, ',', '.') !!}
    </td>
</tr>

<tr>
    <td colspan="5" class="tg-lboi" style="padding:5px 5px;border-top: 0.5mm solid black;">
        
    </td>
    <td colspan="2" class="tg-lboi" style="text-align:right;font-weight: bold;border-top: 0.5mm solid black;padding:5px 5px;font-size:12px">Total a Pagar (AKZ)</td>
    <td colspan="2" class="tg-lboi" style="font-weight: bold;border-top: 0.5mm solid black;padding:5px 5px;text-align:right;font-size:12px">{!! number_format($model->total, 2, ',', '.') !!}</td>
</tr>
</table><br>
<table class="tg">
    <tr>
        <!-- <th width="80%">
        <table style="text-align:left" width="80%">
        <tr>
            <th colspan="10" class="tg-lboi" style="background:#F3F3F3;font-size:8px;padding:5px 5px;font-weight: bold;border-bottom: 0.2mm solid black;">
                COORDENADAS BANCÁRIAS
                
            </th>
        </tr>
        <tr>
            <th colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;font-weight: bold;">
                BANCO
            </th>
            <th colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;font-weight: bold;">
                MOEDA
            </th>
            <th colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;font-weight: bold;">
                N. CONTA
            </th>
            <th colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;font-weight: bold;">
                IBAN
            </th>
            <th rowspan="5" colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;font-weight: bold;">
                <barcode code="{!! url('/verify?id=') !!}" type="QR" size="0.5" error="M" />
            </th>
        </tr>
        <tr>
            <tr>
                <td colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;">
                    BPC
                </td>
                <td colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;">
                    AKZ
                </td>
                <td colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;">
                    0005120735011
                </td>
                <td colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;">
                    AO06 0010 0005 0012 0735 011 79
                </td>
            </tr>
        </tr>
        </table>
        </th> -->
        <th width="20%" style="text-align: right">
            <!-- <img src="{!! public_path() . '/img/inacoq_stamp_color_changed_signature_felix_manuel_doc.svg'!!}" width="20%"> -->
        </th>
    </tr>
</table><br>

@endif
<small style="font-size:10px;padding:5px 5px">
    O cliente tomou conhecimento da emissão deste documento:___________________________________
</small><br><br>
<small style="font-size:10px;padding:5px 5px">
    Os bens/serviços foram colocados à disposição do adquirente na data do documento.
</small>
<hr>
<small style="font-size:10px">{!! $model->unique_hash[0] . $model->unique_hash[10] . $model->unique_hash[20] . $model->unique_hash[30] !!}-Processado por programa validado N. <b>{!! $settings->app_agt_validation_number !!}</b> &copy; {!! $settings->app_name !!}</small>
</htmlpagefooter>

<htmlpageheader name="page-header">
    <table class="" width="100%">
        <tr>
            <th class="tg-0lax" style="vertical-align: middle;text-align:center">
                <img src="{!! public_path() . '/images/aocrest.svg'!!}" style="width: 57px; height: 69px;" alt="">
            </th>
        </tr>
    </table>    

    <div style="text-align: center;">
        
    <!-- <small><b>REPÚBLICA DE ANGOLA</b></small><br> -->
    <small><b>VAP Soluções</b></small><br>
    <small><b>{!! mb_strtoupper($settings->app_client_lab_name) !!}</b></small>
    </div><br><br>

    <table class="tg" width="100%">
        <tr>
        <td colspan="3" class="tg-lboi" style="padding:10px 5px;font-size:11px">
        <img src="{{ public_path() . '/images/SVG/vap_light.svg' }}" width="15%" alt="">
                <small style="font-weight:bold;font-size:12px">
                {!! mb_strtoupper($settings->app_client_name) !!}
                </small><br>
                <small>
                {!! mb_strtoupper($settings->app_client_address) !!}
                </small><br>
                <small>
                    Nº CONTRIBUINTE: {!! mb_strtoupper($settings->app_client_nif) !!}
                </small>
            </td>
            <td colspan="2" class="tg-lboi" style="padding:10px 5px;font-size:11px">
                
            </td>
            <td colspan="3" class="tg-lboi" style="padding:10px 5px;font-size:11px">
                <small style="font-weight:bold;font-size:12px">
                    {!! $model?->customer?->name !!}
                </small><br>
                <small>
                    {!! $model?->warehouse?->address !!}
                </small><br>
                <small>
                    Nº CONTRIBUINTE: {!! (empty($model->warehouse->nif) || is_null($model->warehouse->nif) ? 'Consumidor Final' : $model->warehouse->nif) !!}
                </small>
            </td>
        </tr>
        <tr>
            <th colspan="5" class="tg-lboi" style="font-weight: bold;border-bottom: 0.5mm solid black;padding:5px 5px;font-size:12px">
                Nota de Crédito
            </th>
            <th colspan="2" class="tg-lboi" style="text-align:right;font-weight: bold;border-bottom: 0.5mm solid black;padding:5px 5px;font-size:12px">
                Motivo: {!! ($model->reason == 'R' ? 'Rectificação' : 'Anulação') !!}
            </th>
            <th colspan="1" class="tg-lboi" style="text-align:right;font-weight: bold;border-bottom: 0.5mm solid black;padding:5px 5px;font-size:12px">
                Original
            </th>
        </tr>
        
        <tr>
            <th colspan="2" class="tg-lboi" style="background:#F3F3F3;font-weight: bold;padding:2px 5px;font-size:10px">
                Referência
            </th>
            <th colspan="2" class="tg-lboi" style="background:#F3F3F3;font-weight: bold;padding:2px 5px;font-size:10px">
                Documento
            </th>
            <th colspan="2" class="tg-lboi" style="background:#F3F3F3;font-weight: bold;padding:2px 5px;font-size:10px">
                Data
            </th>
            <th colspan="1" class="tg-lboi" style="background:#F3F3F3;font-weight: bold;padding:2px 5px;font-size:10px">
                Vencimento
            </th>
            <th colspan="1" class="tg-lboi" style="background:#F3F3F3;font-weight: bold;padding:2px 5px;font-size:10px">
                Pág.
            </th>
        </tr>
        <tr>
            <td colspan="2" class="tg-lboi" style="padding:5px 5px;font-size:10px">
                {!! $model->note_no !!}
            </td>
            <td colspan="2" class="tg-lboi" style="padding:5px 5px;font-size:10px">
                Referente à factura {!! $model->invoice->inv_no ?? $model->inv_no !!}
            </td>
            <td colspan="2" class="tg-lboi" style="padding:5px 5px;font-size:10px">
                {!! Carbon\Carbon::parse($model->date)->format('Y-m-d') !!}
            </td>
            <td colspan="1" class="tg-lboi" style="padding:5px 5px;font-size:10px">
                {!! Carbon\Carbon::parse($model->date)->addDays(15)->format('Y-m-d') !!}
            </td>
            <td colspan="1" class="tg-lboi" style="padding:5px 5px;font-size:10px">
                {PAGENO} / {nb}
            </td>
        </tr>
    </table>
    </htmlpageheader>

    @php
        $carry = null; 
        $chunk_total = null;
    @endphp

    @foreach ($model->items->chunk(13) as $key => $chunk)
    @if(!$loop->first && $loop->count > 1)
        @php
            $carry = $carry + $chunk_total;
            $chunk_total = $carry + $chunk->sum('total');
        @endphp
        <table width="100%" style="font-size:8pt">
            <tbody>
            <tr>
                <td width="80%" style="text-align:right;font-weight:bold">Valor Transportado:</td>
                <td width="20%" style="text-align:right;font-weight:bold">
                    {!! number_format($carry, 2, ',', '.') !!}
                </td>
            </tr>
            </tbody>
        </table>

        @else
        @php
            $chunk_total = $carry + $chunk->sum('total');
        @endphp
    @endif
    <table class="tg" width="100%">
        <thead>
        <tr>
            <th class="tg-lboi" style="background:#F3F3F3;font-weight: bold;border-top: 0.3mm solid black;padding:5px 5px;" width="25px">Artigo</th>
            <th class="tg-lboi" style="background:#F3F3F3;font-weight: bold;border-top: 0.3mm solid black;padding:5px 5px;text-align:left" width="300px">Descrição</th>
            <th class="tg-lboi" style="background:#F3F3F3;font-weight: bold;border-top: 0.3mm solid black;padding:5px 5px;text-align:right;" width="25px">Qtd.</th>
            <th class="tg-lboi" style="background:#F3F3F3;font-weight: bold;border-top: 0.3mm solid black;padding:5px 5px;text-align:right" width="35px">Un.</th>
            <th class="tg-lboi" style="background:#F3F3F3;font-weight: bold;border-top: 0.3mm solid black;padding:5px 5px;text-align:right;"width="70px">Pr. Unitário</th>
            <th class="tg-lboi" style="background:#F3F3F3;font-weight: bold;border-top: 0.3mm solid black;padding:5px 5px;text-align:right;" width="25px">Desc.</th>
            <th class="tg-lboi" style="background:#F3F3F3;font-weight: bold;border-top: 0.3mm solid black;padding:5px 5px;text-align:right;" width="90px">Taxa %</th>
            <th class="tg-lboi" style="background:#F3F3F3;font-weight: bold;border-top: 0.3mm solid black;padding:5px 5px;text-align:right;" width="80px">Total Líquido</th>
        </tr>
        </thead>
        <tbody>

            @foreach($chunk as $item)
                <tr>
                    <td class="tg-lboi" style="padding:5px 5px;text-align:left" width="25px">{!! str_pad ($item->item_id, 4, '0', STR_PAD_LEFT) !!}</td>
                    <td class="tg-lboi" style="padding:5px 5px;text-align:left" width="300px">
                    {!! $item->item_description !!} {!! ($item->itemable ? '(CL: ' . $item->itemable->code->description . ' DU: ' . $item->itemable->du_no . ')' : '') !!}
                       
                    </td>
                    <td class="tg-lboi" style="text-align:right;padding:5px 5px;" width="25px">{!! $item->qty !!}</td>
                    <td class="tg-lboi" style="padding:5px 5px;text-align:right" width="35px">{!! $item?->unit?->name ?? 'UN' !!}</td>
                    <td class="tg-lboi" style="text-align:right;padding:5px 5px;" width="70px">{!! number_format($item->unit_price + $item->discount_amount, 2, ',', '.') !!}</td>
                    <td class="tg-lboi" style="text-align:right;padding:5px 5px;" width="25px">{!! number_format($item->discount_percentage, 2, ',', '.') !!}</td>
                    <td class="tg-lboi" style="text-align:right;padding:5px 5px;" width="90px">{!! number_format($item->tax_percentage, 2, ',', '.') !!} {!! (!$item->charge_tax ? '(' . $item->exemption_code . ')' : '') !!}</td>
                    <td class="tg-lboi" style="text-align:right;padding:5px 5px;" width="80px">{!! number_format($item->total, 2, ',', '.') !!}</td>
                </tr>
            @endforeach
        </tbody>
        </table>
        @if($loop->count > 1 && !$loop->last)
        @php
            $carry = $carry;
            $chunk_total = $carry + $chunk->sum('total');
        @endphp
        <table width="100%" style="font-size:8pt">
            <tbody>
            <tr>
                <td width="80%" style="text-align:right;font-weight:bold;">Valor a Transportar:</td>
                <td width="20%" style="text-align:right;font-weight:bold;border-bottom: 0.3mm solid black;">
                    {!! number_format($chunk_total, 2, ',', '.') !!}
                </td>
            </tr>
            </tbody>
        </table>

        @else
        @php
            $chunk_total = $carry + $chunk->sum('total');
        @endphp
    @endif
        @if(count($model->items->chunk(13)) > 1 && !$loop->last )
            <pagebreak />
        @endif

    @endforeach
    
    @if(count($model->items->chunk(13)) > 1)
    <br><br>
    <table class="tg" width="100%">

<tr>
    <td rowspan="3" colspan="5" class="tg-lboi" style="border-top: 0.3mm solid black;border-left: 0.3mm solid black;padding:5px 5px;border-right: 0.3mm solid black;">
    <table class="tg">
    <tr>
        <th colspan="8" class="tg-lboi" style="font-size:8px;padding:5px 5px;font-weight: bold;border-bottom: 0.2mm solid black;">
            RESUMO DE IMPOSTOS
        </th>
    </tr>
    <tr>
        <th colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;font-weight: bold;">
            TAXA DO IMPOSTO
        </th>
        <th colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;font-weight: bold;">
            BASE DE INCIDÊNCIA
        </th>
        <th colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;font-weight: bold;">
            VALOR DO IMPOSTO
        </th>
    </tr>
    <tr>
        <td colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;">
            {!! ($model->items()->whereChargeTax(1)->count() > 0 ? number_format($model->items()->whereChargeTax(1)->first()->tax_percentage, 2, ',', '.') : number_format(0, 2, ',', '.')) !!}
        </td>
        <td colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;">
            {!! number_format($model->items->sum(function($it){if($it->charge_tax){return $it->total;}}), 2, ',', '.') !!}
        </td>
        <td colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;">
             {!! number_format($model->items->sum('tax_amount'), 2, ',', '.') !!}
           
        </td>
    </tr>
    <tr>
        <td colspan="6" style="font-size:8px;">
            @if($model->items()->whereChargeTax(0)->count() > 0)
            {!! $model->items()->where('charge_tax', 0)->first()->exemption_code . ' - ' .$model->items()->first()->where('charge_tax', false)->first()->exemption->reason !!}
            @endif
        </td>
    </tr>
    </table>
    
    </td>
    <td colspan="2" class="tg-lboi" style="border-top: 0.3mm solid black;padding:5px 5px;">
        Total Ilíquido
    </td>
    <td colspan="2" class="tg-lboi" style="border-top: 0.3mm solid black;padding:5px 5px;text-align:right;border-right: 0.3mm solid black;">
        {!! number_format($model->items->sum('total') + $model->items->sum('tax_amount') + $model->items->sum('discount_amount'), 2, ',', '.') !!}
    </td>
</tr>
<tr>

    <td colspan="2" class="tg-lboi" style="padding:5px 5px;">
        Descontos
    </td>
    <td colspan="2" class="tg-lboi" style="padding:5px 5px;text-align:right;border-right: 0.3mm solid black;">
        {!! number_format($model->items->sum('discount_amount'), 2, ',', '.') !!}
    </td>
</tr>
<tr>
    
    <td colspan="2" class="tg-lboi" style="padding:5px 5px;">
        Valor do Imposto (IVA)
    </td>
    <td colspan="2" class="tg-lboi" style="padding:5px 5px;text-align:right;border-right: 0.3mm solid black;">
        {!! number_format($model->items->sum('tax_amount'), 2, ',', '.') !!}
    </td>
</tr>

<tr>
    <td colspan="5" class="tg-lboi" style="padding:5px 5px;border-top: 0.5mm solid black;">
        
    </td>
    <td colspan="2" class="tg-lboi" style="text-align:right;font-weight: bold;border-top: 0.5mm solid black;padding:5px 5px;font-size:12px">Total a Pagar (AKZ)</td>
    <td colspan="2" class="tg-lboi" style="font-weight: bold;border-top: 0.5mm solid black;padding:5px 5px;text-align:right;font-size:12px">{!! number_format($model->total, 2, ',', '.') !!}</td>
</tr>
</table><br>
<table class="tg">
    <tr>
        <!-- <th width="80%">
        <table style="text-align:left" width="80%">
        <tr>
            <th colspan="10" class="tg-lboi" style="background:#F3F3F3;font-size:8px;padding:5px 5px;font-weight: bold;border-bottom: 0.2mm solid black;">
                COORDENADAS BANCÁRIAS
                
            </th>
        </tr>
        <tr>
            <th colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;font-weight: bold;">
                BANCO
            </th>
            <th colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;font-weight: bold;">
                MOEDA
            </th>
            <th colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;font-weight: bold;">
                N. CONTA
            </th>
            <th colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;font-weight: bold;">
                IBAN
            </th>
            <th rowspan="5" colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;font-weight: bold;">
                <barcode code="{!! url('/verify?id=') !!}" type="QR" size="0.5" error="M" />
            </th>
        </tr>
        <tr>
            <tr>
                <td colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;">
                    BPC
                </td>
                <td colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;">
                    AKZ
                </td>
                <td colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;">
                    0005120735011
                </td>
                <td colspan="2" class="tg-lboi" style="font-size:8px;padding:5px 5px;">
                    AO06 0010 0005 0012 0735 011 79
                </td>
            </tr>
        </tr>
        </table>
        </th> -->
        <th width="20%" style="text-align: right">
            <img src="{!! public_path() . '/img/inacoq_stamp_color_changed_signature_felix_manuel_doc.svg'!!}" width="20%">
        </th>
    </tr>
</table>

    @endif


</body>
</html>