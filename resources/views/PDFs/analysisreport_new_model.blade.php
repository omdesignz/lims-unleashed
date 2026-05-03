<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
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
            
            header: other-pages-header;
            footer: page-footer;
            @if($model->collection->result_id == 4 || 3)
              margin-top: 30mm;
              margin-bottom: 130mm;
            @endif

            @if($model->collection->result_id == 1 || $model->collection->result_id == 2) 
              margin-top: 20mm;
              margin-bottom: 130mm;
            @endif
        }

        @page :first {    
            header: page-header;
            footer: page-footer;

            @if($model->collection->result_id == 4 || 3)
              margin-top: 200mm;
              margin-bottom: 90mm;
            @endif

            @if($model->collection->result_id == 1 || $model->collection->result_id == 2)
              margin-top: 200mm;
              margin-bottom: 90mm;
            @endif
        }

    </style>
</head>
<body>
<sethtmlpagefooter name="page-footer" value="on" show-this-page="1" />
<sethtmlpageheader name="page-header" value="on" show-this-page="1" />

<htmlpageheader name="page-header">
    {{-- @include("PDFs.includes.analysisreport.templates_new_model.{$model->collection['result_id']}_header") --}}
    {{-- <hr style="border-bottom:0.3mm solid black;padding-bottom: 0px"> --}}
    <div style="text-align: center; border-bottom:0.3mm solid black;padding-bottom: 0px" width="100%" >
        <center><img src="{!! public_path() . '/images/ao_crest.svg'!!}" width="8%"></center>
        <h4>REPÚBLICA DE ANGOLA</h4>
        <h4>MINISTÉRIO DA AGRICULTURA E FLORESTAS</h4>
        <h4>SERVIÇO NACIONAL DE CONTROLO DA QUALIDADE DOS ALIMENTOS</h4>
        <h4>LABORATÓRIO CENTRAL AGRO-ALIMENTAR DE LUANDA</h4>
    </div><br>
    
    {!! $header !!}
    <div class="obs">
    <p style="padding:5px 5px;font-weight:bold;font-size:7pt;">Observações:</p>
    <p style="padding:5px 5px;font-weight:normal;font-size:7pt;">
    </p>
    <hr style="height: 1px; color:black">
    @include('PDFs.includes.analysisreport.templates_new_model.obs')
</div>
</htmlpageheader>

<htmlpageheader name="other-pages-header">
    {{ $model->code }}, Emitido aos {{ $model->created_at->format('d/m/Y') }}
    <hr style="height: 1px; color:black; margin-top: 0">
</htmlpageheader>

<htmlpagefooter name="page-footer">

<small style="font-size:11px;color:{!! ($model->status ? '' : 'red') !!}">

        {!! $model?->fine_print !!}
</small><br>

@if (!is_null($model->validated_at))
    <table class="tg" width="100%">
        <tr>
            <td class="tg-s268" style="text-align:center" width="100%">

            <div style="text-align:center">
            <div style="padding-bottom: 0px; margin-bottom: 0px;">
                <img src="{{ asset('/images/stamp_wgaspar.svg') }}" alt="" width="15%">
            </div>
                <small><b>Dra. Wladimira Gaspar</b></small><br>
                <small><b>Autorizado Por</b></small><br>
            </div>
            </td>
        </tr>
    </table><br>

@else
<div style="text-align:center">
    <span style="color:red; text-align:center; font-weight:bold; font-size:7pt;">
        A informação que consta neste certificado ainda não foi validada.
    </span>
</div>
@endif

    <p style="text-align: right;padding-bottom: 0px; font-size: 7pt">{{ $model?->code }}  Pág. {PAGENO} / {nb}</p>    
    <hr style="height: 1px; color:black">


    <table class="tg" width="100%">
        <tr>
            <td style="text-align: left">
            <small style="text-align:left;font-size: 7pt;">
                <b>LABORATÓRIO CENTRAL AGRO-ALIMENTAR DE LUANDA </b> <br>
                AV. Deolinda Rodrigues – Estrada de Catete, KM 6 <br>
                Rua dos Comandos, junto a entrada da Filda <br>
                Município do Cazenga - Luanda - Angola <br>
                Telefone: (+244) 949 574584 / 949574497 / 949 574587 <br>
                E-mail:  apoiocliente@sncqa.co.ao / info@sncqa.co.ao / apoiocliente1@sncqa.co.ao

            </small>
            </td>
            <td style="text-align: right; padding-top: 2mm; vertical-align:middle">
                <img src="{!! public_path() . '/images/governo_novo.png'!!}" width="10%">
            </td>
        </tr>
    </table>
    <small style="text-align:left;font-size: 7pt;">
        <b>Nota 1</b> - “Ensaios realizados nas instalações permanentes do Laboratório”
        <b>Nota 2</b> – “Os resultados do presente relatório referem-se aos itens ensaiados”
        <b>Nota 3</b> – “Este relatório não pode ser reproduzido, a não ser na integra, sem a aprovação do Laboratório”
        <b>Nota 4</b> - “Quando identificado como responsável da colheita o Cliente, todas as informações referentes à amostra são da sua responsabilidade e os resultados aplicam-se à amostra conforme rececionada”
        <b>Nota 5</b> – “A avaliação da conformidade face aos valores de referencia indicados e de acordo com a regra de decisão previamente acordada com o cliente: A incerteza da medição não é considerada na avaliaçãoda conformidade”
        <b>Nota 6</b> – “Opiniões e interpretações expressas neste relatório não estão incluidas no âmbito”
        <b>Nota 7</b> – “Quando identificado como responsável da colheita, o laboratório, o método utilizado é o PO005.
    </small>

</htmlpagefooter>

@include("PDFs.includes.analysisreport.templates.{$model->collection['result_id']}")

</body>
</html>