<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
    .tg  {border-collapse:collapse;border-spacing:0;}
.tg td{font-family:Arial, sans-serif;font-size:14px;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
.tg th{font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
.tg .tg-0lax{text-align:left;vertical-align:top}
        body {font-family: sans-serif;
            font-size: 10pt;
        }
        p {	margin: 0pt; }
        table.items {
            border: 0.1mm solid #000000;
        }
        td { vertical-align: top; }

        table thead td { background-color: #EEEEEE;
            text-align: center;
            border: 0.1mm solid #000000;
            font-variant: small-caps;
        }
        .items td.blanktotal {
            background-color: #EEEEEE;
            background-color: #FFFFFF;
            border: 0mm none #000000;
            border-top: 0.1mm solid #000000;
            border-right: 0.1mm solid #000000;
        }
        .items td.totals {
            text-align: right;
            border: 0.1mm solid #000000;
        }
        .items td.cost {
            text-align: "." center;
        }
        .barcodecell {
            text-align: center;
            vertical-align: middle;
            padding: 0;
        }
        .barcode {
            padding: 1.5mm;
            margin: 0;
            vertical-align: top;
            color: #000000;
        }

        body {
            margin-top: 0px;
        }

        @page {
            background-image: url("{!! public_path() . '/images/oficio_template.svg'!!}") no-repeat 0 0;
            background-image-resize: 6;

            header: page-header;
            footer: page-footer;

            margin-top: 110mm;
            margin-bottom: 10mm;
        }
    </style>
</head>
<body>
<sethtmlpagefooter name="page-footer" value="on" />
<sethtmlpageheader name="page-header" value="on" />

<htmlpageheader name="page-header">

<div style="text-align: center;" width="100%">
<center><img src="{!! public_path() . '/images/aocrest.svg'!!}" style="width: 57px; height: 69px;" alt="" width="8%"></center><br>
       <p style="font-size: 9pt">
       VAP Soluções <br>
       {!! mb_strtoupper($settings->app_client_lab_name) !!}
       </p>
</div>

<hr style="color: black; height: 2px; margin-top: 0px; margin-bottom: 0px;">

<h3 style="text-align:center;font-style: italic;">GUIA DE CONTRATAÇÃO {!! $model->guide_no !!}</h3>
<h4 style="text-align:center;">Solicitante / Cliente</h4>
<table width="100%" style="font-size: 8pt">
    <tr>
        <td colspan="3"><b>Denominação da Empresa / Estabelecimento / Solicitante: </b>{!! mb_strtoupper($model->customer->company) !!}</td>
    </tr>
    <tr>
        <td colspan="3"><b>Local de Recolha: </b>{!! mb_strtoupper($model->collection_point) !!}</td>
    </tr>
    <tr>
        <td><b>NIF: </b>{!! $model->nif !!}</td>
        <td><b>Telefone: </b>{!! $model->contact !!}</td>
        <td><b>Email: </b>{!! $model->email !!}</td>
    </tr>
</table>
<hr style="color: black; height: 2px; margin-top: 0px; margin-bottom: 0px;">
<h4 style="text-align:center;">Documentação de Suporte</h4>
<table width="100%" style="font-size: 8pt">
    <tr>
        <td colspan="2"><b>Porto / Aeroporto / Posto de Desembarque: </b>{!! mb_strtoupper($model->entry_point) !!}</td>
    </tr>
    <tr>
        <td><b>B/L ou Carta de Porte: </b>{!! mb_strtoupper($model->ref_no) !!}</td>
        <td><b>Nº do DU: </b>{!! mb_strtoupper($model->du_no) !!}</td>
    </tr>
</table>

<hr style="color: black; height: 2px; margin-top: 0px; margin-bottom: 0px;">
</htmlpageheader>

<htmlpagefooter name="page-footer">
<small style="font-size:7pt;">
Página {PAGENO} de {nb}<br>
<b>{!! mb_strtoupper($settings->app_client_address) !!} | {!! mb_strtoupper($settings->app_client_email) !!} | {!! mb_strtoupper($settings->app_client_contact) !!}</b>
</small>

</htmlpagefooter>
<h4 style="text-align:center;">Informação do Produto Alimentar</h4>
<table class="" width="100%" style="border-collapse:collapse" cellspacing="0" cellpadding="0">
<thead>
  <tr>
    <th style="text-align: left;border-top: 0.2mm solid black;border-bottom: 0.2mm solid black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;font-weight:normal;padding:5px 5px;word-break:normal;"><b>Produto</b></th>
    <th style="text-align: left;border-top: 0.2mm solid black;border-bottom: 0.2mm solid black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;font-weight:normal;padding:5px 5px;word-break:normal;"><b>País de Origem</b></th>
    <th style="text-align: left;border-top: 0.2mm solid black;border-bottom: 0.2mm solid black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;font-weight:normal;padding:5px 5px;word-break:normal;"><b>Frabricante / Produtor</b></th>
    <th style="text-align: left;border-top: 0.2mm solid black;border-bottom: 0.2mm solid black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;font-weight:normal;padding:5px 5px;word-break:normal;"><b>Marca</b></th>
    <th style="text-align: left;border-top: 0.2mm solid black;border-bottom: 0.2mm solid black;font-family:Arial, sans-serif;font-size:12px;font-weight:normal;padding:5px 5px;word-break:normal;"><b>Lote</b></th>
    <!-- <th style="text-align: left;border-top: 0.2mm solid black;border-bottom: 0.2mm solid black;font-family:Arial, sans-serif;font-size:12px;font-weight:normal;padding:5px 5px;word-break:normal;"><b>BL</b></th> -->
  </tr>
</thead>
  @foreach($model->items as $item)
  @if($loop->last)
  <tr>
    <td class="tg-0lax" style="border-bottom: 0.2mm solid black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;">
    {!! mb_strtoupper($item->product->name) !!}
    </td>
    <td class="tg-0lax" style="border-bottom: 0.2mm solid black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;">
    {!! mb_strtoupper($item->country->name) !!}
    </td>
    <td class="tg-0lax" style="border-bottom: 0.2mm solid black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;">
    {!! mb_strtoupper($item->manufacturer) !!}
    </td>
    <td class="tg-0lax" style="border-bottom: 0.2mm solid black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;">
    {!! mb_strtoupper($item->brand) !!}
    </td>
    <td class="tg-0lax" style="border-bottom: 0.2mm solid black;font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;">
    {!! mb_strtoupper($item->lot) !!}
    </td>
    <!-- <td class="tg-0lax" style="border-bottom: 0.2mm solid black;font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;">
    {!! mb_strtoupper($item->bl) !!}
    </td> -->
  </tr>
  @else
  <tr>
    <td class="tg-0lax" style="border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;">
    {!! mb_strtoupper($item->product->name) !!}
    </td>
    <td class="tg-0lax" style="border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;">
    {!! mb_strtoupper($item->country->name) !!}
    </td>
    <td class="tg-0lax" style="border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;">
    {!! mb_strtoupper($item->manufacturer) !!}
    </td>
    <td class="tg-0lax" style="border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;">
    {!! mb_strtoupper($item->brand) !!}
    </td>
    <td class="tg-0lax" style="font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;">
    {!! mb_strtoupper($item->lot) !!}
    </td>
    <!-- <td class="tg-0lax" style="font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;">
    {!! mb_strtoupper($item->bl) !!}
    </td> -->
  </tr>
  @endif
  @endforeach

</table>
<br>
<hr style="color: black; height: 2px; margin-top: 0px; margin-bottom: 0px;">
<small style="font-size:8pt;font-weight: bold">
    1- Nos termos do Decreto Presidencial nº179/18 de 2 de agosto, o INIS compromete-se em realizar as análises dos produtos descritos nesta Guia de Contratação.<br>
    2- A VAP Soluções obriga-se a efectuar as referidas análises e apresentar o correspondente Boletim de Análises, no prazo máximo de 15 dias.<br>
    3- O presente documento não substitui o Boletim de Análises e não confere a certificação da qualidade do(s) produto(s).
</small>
<br>
<br>
<br>
<small style="font-size:8pt;">
    Luanda, {!! $model->date !!}
</small>
<br>
<br>
<br>
<div style="text-align: center;" width="100%">
    <b>O Director Geral</b><br>
    <!-- <img src="{!! public_path() . '/img/lancoq_alberto_sofia.svg'!!}" width="20%"> -->
</div>
<div style="position: fixed; left: 0mm; top: -83.5mm;">
<img src="{{ public_path() . '/images/SVG/vap_light.svg' }}" width="35%" alt="">

</div>
</body>
</html>
