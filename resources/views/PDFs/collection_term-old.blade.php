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

        input.larger {
        width: 50px;
        height: 50px;
      }

        @page {
            background-image: url("{!! public_path() . '/images/oficio_template.svg'!!}") no-repeat 0 0;
            background-image-resize: 6;

            /* background-repeat: no-repeat 0 0; */
            header: page-header;
            footer: page-footer;
              margin-top: 95mm;
              margin-bottom: 50mm;
        }

    </style>
</head>
<body>
<sethtmlpagefooter name="page-footer" value="on" />
<sethtmlpageheader name="page-header" value="on" />

<htmlpageheader name="page-header">

    <table class="tg" width="100%" style="border-collapse:collapse;">
        <tr>
          <th class="tg-s268" rowspan="4" style="border: solid black; border-width:1.5px;text-align:center;padding:5px 5px;" width="33%">
              <!-- <center><img src="{{ public_path() . '/images/SVG/logo.svg' }}" width="14%" alt=""></center> -->
              <center><img src="{{ public_path() . '/images/SVG/vap_light.svg' }}" width="15%" alt=""></center>
          </th>
          <th class="tg-s268" style="border-right:0.4mm solid black;border-top:0.4mm solid black;border-left:0.4mm solid black;padding:5px 5px;" width="33%">
            <!-- REPÚBLICA DE ANGOLA <br>
            MINISTÉRIO DA SAÚDE <br>
            INSTITUTO NACIONAL DE INVESTIGAÇÃO EM SAÚDE -->

            VAP Soluções


          </th>
          <th class="tg-0lax" rowspan="3" style="border-right: 0.4mm solid black;border-top: 0.4mm solid black;padding:5px 5px;text-align:left" width="33%">
            <b>Código:</b> DSA-SRA:03/00 <br>
            <b>Edição:</b>1/19 <br>
            <b>Rev.</b> 00/19 <br>

          Página <b>{PAGENO}</b> de <b>{nb}</b>
          </th>
        </tr>
        <tr>
          <td class="tg-0lax" style="border-right:0.4mm solid black;border-left:0.4mm solid black;padding:5px 5px;text-align:center">
             
          </td>
        </tr>
        <tr>
          <td class="tg-0lax" style="border-right:0.4mm solid black;border-left:0.4mm solid black;padding:5px 5px;text-align:center">
             
          </td>
        </tr>
        <tr>
          <td class="tg-0lax" style="border-bottom: 0.4mm solid black;text-align:center;padding:5px 5px;">
              <b>DEPARTAMENTO DE SAÚDE AMBIENTAL</b>
          </td>
          <td class="tg-0lax" style="border: solid black; border-width:1.5px;padding:5px 5px;font-size:10px;text-align:center" valign="center">
          <b>SECÇÃO DE RECEPÇÃO DE AMOSTRAS E REGISTRO</b>
          </td>
        </tr>
      </table>
      <div>
        <h2 style="text-decoration: underline;text-align:center;padding-top:10px">TERMO DE COLHEITA DE AMOSTRAS</h2>
      </div>
      <div>
        <ol>
            <li style="font-weight: bold;"><b>RAZÕES DA AMOSTRAGEM:</b>
        @foreach ($reasons as $reason)
        <label for="{{ $reason->name }}"> {{ $reason->name }} <input class="larger" type="checkbox" name="{{ $reason->name }}" id="{{ $reason->id }}" value="{{ $reason->id }}" @checked(in_array($reason->id, $model->collection->reasons->pluck('id')->toArray())) /></label>
        @endforeach
        
        </li>
        </ol>
      DADOS DA ENTIDADE REQUERENTE
      <table class="tg" width="100%">
        <tr>
            <td class="tg-s268" style="padding:5px 5px;font-size:11px;border-left: 0.2mm solid black;border-top: 0.2mm solid black;border-bottom: 0.2mm solid black;">
               <b>Requisitante:</b> {!! mb_strtoupper($model->collection->customer->name) !!}
            </td>
            <td class="tg-s268" style="padding:5px 5px;font-size:11px;border-left: 0.2mm solid black;border-top: 0.2mm solid black;border-bottom: 0.2mm solid black;border-right: 0.2mm solid black">
                <b>NIF:</b> {!! $model->collection->warehouse->nif !!}
            </td>
        </tr>
        <tr>
            <td class="tg-0lax" style="padding:5px 5px;font-size:11px;border-left: 0.2mm solid black;border-top: 0.2mm solid black;border-bottom: 0.2mm solid black;border-right: 0.2mm solid black">
               <b>Email:</b> {!! $model->collection->warehouse->email !!}
            </td>
            <td class="tg-0lax" style="padding:5px 5px;font-size:11px;border-left: 0.2mm solid black;border-top: 0.2mm solid black;border-bottom: 0.2mm solid black;border-right: 0.2mm solid black">
               <b>Telefone:</b> {!! $model->collection->warehouse->primary_phone !!} / {!! $model->collection->warehouse->alternative_phone !!}
            </td>
        </tr>
        <tr>
            <td class="tg-0lax" style="padding:5px 5px;font-size:11px;border-left: 0.2mm solid black;border-top: 0.2mm solid black;border-bottom: 0.2mm solid black;border-right: 0.2mm solid black">
               <b>Email para Envio de Cobrança:</b> {!! $model->collection->warehouse->invoicing_email !!}
            </td>
            <td class="tg-0lax" style="padding:5px 5px;font-size:11px;border-left: 0.2mm solid black;border-top: 0.2mm solid black;border-bottom: 0.2mm solid black;border-right: 0.2mm solid black">
               <b>Endereço:</b> {!! $model->collection->warehouse->address !!}
            </td>
        </tr>
    </table>

      </div>
</htmlpageheader>

DADOS DA AMOSTRA
<table class="" width="100%" style="border-collapse:collapse" cellspacing="0" cellpadding="0">
<thead>
  <tr>
    <th style="text-align: left;border-top: 0.2mm solid black;border-bottom: 0.2mm solid black;border-left: 0.2mm dotted black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;font-weight:normal;padding:5px 5px;word-break:normal;"><b>Designação</b></th>
    <th style="text-align: left;border-top: 0.2mm solid black;border-bottom: 0.2mm solid black;border-left: 0.2mm dotted black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;font-weight:normal;padding:5px 5px;word-break:normal;"><b>Marca Comercial</b></th>
    <th style="text-align: left;border-top: 0.2mm solid black;border-bottom: 0.2mm solid black;border-left: 0.2mm dotted black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;font-weight:normal;padding:5px 5px;word-break:normal;"><b>Data de Colheita</b></th>
    <th style="text-align: left;border-top: 0.2mm solid black;border-bottom: 0.2mm solid black;border-left: 0.2mm dotted black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;font-weight:normal;padding:5px 5px;word-break:normal;"><b>Data de Produção</b></th>
    <th style="text-align: left;border-top: 0.2mm solid black;border-bottom: 0.2mm solid black;border-left: 0.2mm dotted black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;font-weight:normal;padding:5px 5px;word-break:normal;"><b>Data de Expiração</b></th>
    <th style="text-align: left;border-top: 0.2mm solid black;border-bottom: 0.2mm solid black;border-left: 0.2mm dotted black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;font-weight:normal;padding:5px 5px;word-break:normal;"><b>Temperatura</b></th>
    <th style="text-align: left;border-top: 0.2mm solid black;border-bottom: 0.2mm solid black;border-left: 0.2mm dotted black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;font-weight:normal;padding:5px 5px;word-break:normal;"><b>Lote</b></th>
    <th style="text-align: left;border-top: 0.2mm solid black;border-bottom: 0.2mm solid black;border-left: 0.2mm dotted black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;font-weight:normal;padding:5px 5px;word-break:normal;"><b>Tipo de Embalagem</b></th>
    <th style="text-align: left;border-top: 0.2mm solid black;border-bottom: 0.2mm solid black;border-left: 0.2mm dotted black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;font-weight:normal;padding:5px 5px;word-break:normal;"><b>Quantidade</b></th>
  </tr>
</thead>
  <tr>
    <td class="tg-0lax" style="border-bottom: 0.2mm solid black;border-left: 0.2mm dotted black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;">
    {!! mb_strtoupper($model->product->name) !!}
    </td>
    <td class="tg-0lax" style="border-bottom: 0.2mm solid black;border-left: 0.2mm dotted black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;">
    {!! mb_strtoupper($model->comercial_brand) !!}
    </td>
    <td class="tg-0lax" style="border-bottom: 0.2mm solid black;border-left: 0.2mm dotted black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;">
    {!! mb_strtoupper($model->collection_date) !!}
    </td>
    <td class="tg-0lax" style="border-bottom: 0.2mm solid black;border-left: 0.2mm dotted black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;">
    {!! mb_strtoupper($model->production_date) !!}
    </td>
    <td class="tg-0lax" style="border-bottom: 0.2mm solid black;border-left: 0.2mm dotted black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;">
    {!! mb_strtoupper($model->expiry_date) !!}
    </td>
    <td class="tg-0lax" style="border-bottom: 0.2mm solid black;border-left: 0.2mm dotted black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;">
    {!! mb_strtoupper($model->temperature_value) !!}
    </td>
    <td class="tg-0lax" style="border-bottom: 0.2mm solid black;border-left: 0.2mm dotted black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;">
    {!! mb_strtoupper($model->lot) !!}
    </td>
    <td class="tg-0lax" style="border-bottom: 0.2mm solid black;border-left: 0.2mm dotted black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;">
    {!! mb_strtoupper($model->packaging->name) !!}
    </td>
    <td class="tg-0lax" style="border-bottom: 0.2mm solid black;border-left: 0.2mm dotted black;border-right: 0.2mm dotted black;font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;font-family:Arial, sans-serif;font-size:12px;padding:2px 5px;vertical-align:top;word-break:normal;">
    {!! mb_strtoupper($model->qty) !!}
    </td>
  </tr>

</table> <br>

<div style="border-right: 0.2mm solid black;border-left: 0.2mm solid black;border-top: 0.2mm solid black;border-bottom: 0.2mm solid black;">
<p style="margin: 5px;"><b>OBSERVAÇÕES:</b> {{ $model->obs }}</p>
</div> <br> <br>

<p>A amostragem foi efectuada em triplicado, o qual (<b>2</b>) amostras vão para o laboratório e (<b>1</b>) fica com a entidade requerente como fiel depositário para efeito de contra-análise.</p> <br>

<p><b>Nome do requerente ou representante legal:</b> ______________________________________ Telf:__________________________________         Data: ______/______/_______ </p> <br>

<p><b>Nome do Responsável pela colheita ou recepção de amostras:</b>  ___________________________________   Telf:______________________     Data: ______/______/_______ </p>

<htmlpagefooter name="page-footer">

        
<!-- </small><br>

    <div class="center">
    <img src="{!! public_path() . '/images/SVG/minsa_white.svg'!!}" width="20%">
    </div> -->
</htmlpagefooter>


</body>
</html>
