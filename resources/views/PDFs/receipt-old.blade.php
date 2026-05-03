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
            header: page-header;
            footer: page-footer;
            margin-top: 95mm;
            margin-bottom: 82mm;
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

<table class="tg" width="100%">
    <tr>
        <td colspan="5" class="tg-lboi" style="padding:5px 5px;border-top: 0.5mm solid black;">
            
        </td>
        <td colspan="2" class="tg-lboi" style="text-align:right;font-weight: bold;border-top: 0.5mm solid black;padding:5px 5px;font-size:14px">Total Pago (AKZ)</td>
        <td colspan="2" class="tg-lboi" style="font-weight: bold;border-top: 0.5mm solid black;padding:5px 5px;text-align:right;font-size:14px">{!! number_format($model->items()->sum('paid_amount'), 2, ',', '.') !!}</td>
    </tr>
</table><br>

<hr>
<small style="font-size:10px">Processado por programa validado N. <b>{!! $settings->app_agt_validation_number !!}</b> &copy; {!! $settings->app_name !!}</small>
<table class="tg" width="100%">
        <tr>
            <td style="text-align: left">
            <small style="text-align:left;font-size: 7pt">
            {!! mb_strtoupper($settings->app_client_address) !!}<br>
            {!! mb_strtoupper($settings->app_client_contact) !!}<br>
            {!! mb_strtoupper($settings->app_client_email) !!}<br>
            </small>
            </td>
            <td style="text-align: left; padding-right: 6mm">
                <!-- <img src="{!! public_path() . '/img/governo_novo.png'!!}" width="10%"> -->
            </td>
            <td style="text-align: right; padding-top: 6mm">
                <img src="{!! public_path() . '/images/SVG/vap_light.svg'!!}" width="10%">
            </td>
        </tr>
    </table>
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
            <th colspan="7" class="tg-lboi" style="font-weight: bold;border-bottom: 0.5mm solid black;padding:5px 5px;font-size:12px">
                {!! 'Recibo' !!} | <small style="font-weight: normal">Processado por computador - {!! ($model->user ? $model->user->name : 'SISTEMA') !!}</small>
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
                <!-- BL -->
            </th>
            <th colspan="2" class="tg-lboi" style="background:#F3F3F3;font-weight: bold;padding:2px 5px;font-size:10px">
                Data
            </th>
            <th colspan="1" class="tg-lboi" style="background:#F3F3F3;font-weight: bold;padding:2px 5px;font-size:10px">
                
            </th>
            <th colspan="1" class="tg-lboi" style="background:#F3F3F3;font-weight: bold;padding:2px 5px;font-size:10px">
                Pág.
            </th>
        </tr>
        <tr>
            <td colspan="2" class="tg-lboi" style="padding:5px 5px;font-size:10px">
                {!! $model->rec_no !!}
            </td>
            <td colspan="2" class="tg-lboi" style="padding:5px 5px;font-size:10px">
            </td>
            <td colspan="2" class="tg-lboi" style="padding:5px 5px;font-size:10px">
                {!! Carbon\Carbon::parse($model->date)->format('Y-m-d') !!}
            </td>
            <td colspan="1" class="tg-lboi" style="padding:5px 5px;font-size:10px">
                
            </td>
            <td colspan="1" class="tg-lboi" style="padding:5px 5px;font-size:10px">
                {PAGENO} / {nb}
            </td>
        </tr>
        <tr>
            <td colspan="5" style="padding:40px 5px;font-size:10px;text-align:right">
                <!-- <img src="{!! public_path() . '/images/SVG/logo.svg'!!}" width="20%"> -->
            </td>
        </tr>
    </table>
    </htmlpageheader>
    
    <table class="tg" width="100%">
        <thead>
        <tr>
            <th class="tg-lboi" style="background:#F3F3F3;font-weight: bold;border-top: 0.3mm solid black;padding:5px 5px;">Documento</th>
            <th class="tg-lboi" style="background:#F3F3F3;font-weight: bold;border-top: 0.3mm solid black;padding:5px 5px;text-align:left">Data</th>
            <th class="tg-lboi" style="background:#F3F3F3;font-weight: bold;border-top: 0.3mm solid black;padding:5px 5px;text-align:right;">Valor Por Regularizar</th>
            <th class="tg-lboi" style="background:#F3F3F3;font-weight: bold;border-top: 0.3mm solid black;padding:5px 5px;text-align:right">Regularizado</th>
            <th class="tg-lboi" style="background:#F3F3F3;font-weight: bold;border-top: 0.3mm solid black;padding:5px 5px;text-align:right;">Saldo Pendente</th>
        </tr>
        </thead>
        @foreach($model->items as $item)
            <tr>
                <td class="tg-lboi" style="padding:5px 5px;text-align:left">{!! $item->invoice->inv_no !!}</td>
                <td class="tg-lboi" style="padding:5px 5px;text-align:left">
                    {!! $model->date !!}
                </td>
                <td class="tg-lboi" style="text-align:right;padding:5px 5px;">
                    {!! number_format($item->invoice_pending_amount, 2, ',', '.') !!}
                </td>
                <td class="tg-lboi" style="padding:5px 5px;text-align:right">
                    {!! number_format($item->paid_amount, 2, ',', '.') !!}
                </td>
                <td class="tg-lboi" style="text-align:right;padding:5px 5px;">
                    {!! number_format($item->pending_amount, 2, ',', '.') !!}
                </td>
            </tr>
            <tr>
                <td colspan="5">
                </td>
            </tr>
        @endforeach
        
    </table>

</body>
</html>