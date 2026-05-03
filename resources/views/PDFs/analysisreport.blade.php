<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
    <!-- <link href="http://app.mediagagroalimentar.com/public/css/bootstrap.min.css"/> -->
    <style>
        .center {
            position: relative;
            /* left: 50%;
            top: 50%; */
            transform: translate(-50%, -50%);
            background-color: #122536;
            color: white;
            padding: 10px;
            text-align: center;
        }
    .tg  {border-collapse:collapse;border-spacing:0;}
    .tg td{font-family:Arial, sans-serif;font-size:10pt;padding:10px 5px;border-style:solid;border-width:0px;overflow:hidden;word-break:normal;}
    .tg th{font-family:Arial, sans-serif;font-size:10pt;font-weight:normal;padding:10px 5px;border-style:solid;border-width:0px;overflow:hidden;word-break:normal;}
    .tg .tg-0pky{border-color:inherit;text-align:left;vertical-align:top}
    .tg .tg-r0kq{font-family:Verdana, Geneva, sans-serif !important;border-color:inherit;text-align:left;vertical-align:top}
    .tg .tg-0lax{text-align:left;vertical-align:top}
        body {font-family: sans-serif;
            font-size: 11px;
        }
        p {	margin: 0pt; }

        body {
            margin-top: 0px;
        }

        @page {
            background-image: url("{!! public_path() . '/images/oficio_template.svg'!!}") no-repeat 0 0;
            background-image-resize: 6;

            /* background-repeat: no-repeat 0 0; */
            header: page-header;
            footer: page-footer;
            @if($model->collection->result_id == 4 || 3)
              margin-top: 135mm;
              margin-bottom: 130mm;
            @endif

            @if($model->collection->result_id == 1 || $model->collection->result_id == 2)
              margin-top: 135mm;
              margin-bottom: 130mm;
            @endif
        }

    </style>
</head>
<body>
<sethtmlpagefooter name="page-footer" value="on" />
<sethtmlpageheader name="page-header" value="on" />

<htmlpageheader name="page-header">
    @include("PDFs.includes.analysisreport.templates.{$model->collection['result_id']}_header")
</htmlpageheader>

<htmlpagefooter name="page-footer">
<small style="font-size:11px;">{!! mb_strtoupper($settings->app_client_lab_province ?? '') !!}, {!! $model->created_at->format('d/m/Y') !!}</small><br>
<small style="font-size:11px;"><b>Observações:</b></small>
<small style="font-size:11px;">{{ $model->collection->obs }}</small><br>
<small style="font-size:11px;color:{!! ($model->status ? '' : 'red') !!}">

        {!! $model?->fine_print !!}
</small><br>

<table class="tg" width="100%">
  <tr>

    <td class="tg-s268" style="">
        <small><b>Autorização Técnica</b></small><br>
        @for ($i = 0; $i < $model->collection->code->samples->count(); $i++)
            @if( $model->collection->code->samples[$i]->analysis->type_id == 1 )
            <small>Química: </small><br>
            @endif
            @if( $model->collection->code->samples[$i]->analysis->type_id == 2 )
            <small>Microbiologia:  </small><br>
            @endif
            @if( $model->collection->code->samples[$i]->analysis->type_id == 3 )
                <small>Entomologia:  </small><br>
            @endif
        @endfor
    </td>

    <td class="tg-s268" style="text-align:center">
        <img src="{!! public_path() . '/images/ej.png'!!}" width="25%"><br>
        <small><b>{{ $settings?->app_client_lab_director }}</b></small>

    </td>

    <td class="tg-s268" style="text-align:center">
        <barcode code="{!! url('/verify?id=' . $model->id . '&type=quacertificate' . '&doc_no=' . $model->cert_no) !!}" type="QR" size="1.0" error="M" /><br><br>
        <small style="align:center;font-size:6pt">
        PARA EFEITO DE VERIFICAÇÃO DE AUTENTICIDADE,<br>
        POR FAVOR ESCANEE O CÓDIGO QR PRESENTE NESTE <br>
        DOCUMENTO
        </small>

    </td>
  </tr>
</table><br>
{{-- @include('quacertificate.templates.obs') --}}
{{-- <small style="font-size:10px;font-weight:bold;">{!! mb_strtoupper($settings->app_client_lab_name) !!}</small><br> --}}
    {{-- <div style="font-size: 9pt; text-align: center;">
    <img src="{!! public_path() . '/img/governo_novo.png'!!}" width="10%"><br>
    <small style="align:center;">{!! mb_strtoupper($settings->app_client_address) !!}</small>
    </div> --}}
    <div class="center">
    <img src="{!! public_path() . '/images/SVG/minsa_white.svg'!!}" width="20%">
    </div>
</htmlpagefooter>

@include("PDFs.includes.analysisreport.templates.{$model->collection['result_id']}")

</body>
</html>
