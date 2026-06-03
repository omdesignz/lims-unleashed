
@include('PDFs.partials.brand-logo', ['settings' => $settings ?? null, 'width' => '25%']) <br> <br>


<div style="float: right; width: 54%;">
<div class="rounded">
<h3 style="padding-left: 15px;">RELATÓRIO DE ENSAIO <small style="font-weight: normal; font-style: italic;">(Test Report)</small><br>
    {!! mb_strtoupper($model?->code) !!}
</h3>

<div style="float: right; width: 28%;">
<barcode code="{!! url('/verify?id=' . $model->id . '&type=quacertificate' . '&doc_no=' . $model->cert_no) !!}" type="QR" size="1.0" error="M" /><br><br>

</div>

<div style="float: left; width: 54%;padding-left: 15px;">
    
    <h4><p style="font-size:10px;padding-bottom: 0px">Cliente</p>{!! mb_strtoupper($model?->collection?->customer?->name) !!} <br>{{ $model?->collection?->warehouse?->primary_phone }}</h4>

    <table class="tg" width="100%">
    <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;" width="150px">
        NIF:
        </td>
        <td class="tg-s268" style="font-weight:bold;padding:5px 5px;font-size:11px;">
        {{ mb_strtoupper($model?->collection?->warehouse?->nif) }}
        </td>
    </tr>
    <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;" width="150px">
        Endereço:
        </td>
        <td class="tg-s268" style="font-weight:bold;padding:5px 5px;font-size:11px;">
        {{ mb_strtoupper($model?->collection?->warehouse?->address) }}
        </td>
    </tr>
    <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;" width="150px">
        Produto:
        </td>
        <td class="tg-s268" style="font-weight:bold;padding:5px 5px;font-size:11px;" width="150px">
        {{ mb_strtoupper($model?->collection?->product?->name) }}
        </td>
    </tr>
    <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
        Marca:
        </td>
        <td class="tg-s268" style="padding:5px 5px;font-weight:bold;font-size:11px;">
        {!! $model->collection->comercial_brand ?? '' !!}
        </td>
    </tr>
    <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
        ID Amostra:
        </td>
        <td class="tg-s268" style="padding:5px 5px;font-weight:bold;font-size:11px;">
        {!! mb_strtoupper($model?->lab_code?->code) !!}
        </td>
    </tr>
    <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
            Data de Produção:
        </td>
        <td class="tg-s268" style="padding:5px 5px;font-weight:bold;font-size:11px;">
            {!! mb_strtoupper($model?->collection?->production_date ?? '--') !!}
        </td>
    </tr>
    <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
            Data de Expiração:
        </td>
        <td class="tg-s268" style="padding:5px 5px;font-weight:bold;font-size:11px;">
            {!! mb_strtoupper($model?->collection?->expiry_date ?? '--') !!}
        </td>
    </tr>
    {{-- <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
        Boletim Nº <small style="font-weight: normal; font-style: italic;">(Test Report No.)</small>:
        </td>
        <td class="tg-s268" style="padding:5px 5px;font-weight:bold;font-size:11px;">
        {!! mb_strtoupper($model?->code) !!}
        </td>
    </tr> --}}
</table>

</div>

<div style="clear: both; margin: 0pt; padding-bottom: 10pt;"></div>

</div> <br>
</div>

<div style="float: left; width: 38%;">
    <!-- This is text that is set to float:left. -->
    <table class="tg" width="100%">
    <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;" width="150px">
        Data de Recepção da Amostra:
        </td>
        <td class="tg-s268" style="font-weight:bold;padding:5px 5px;font-size:11px;">
        {{ mb_strtoupper($model?->collection?->created_at) ?? '--' }}
        </td>
    </tr>
    <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;" width="150px">
        Data de Emissão <small style="font-weight: normal; font-style: italic;">(Issue Date)</small>:
        </td>
        <td class="tg-s268" style="font-weight:bold;padding:5px 5px;font-size:11px;" width="150px">
        {{ mb_strtoupper($model?->created_at) ?? '--' }}
        </td>
    </tr>
    <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
        Data de Colheita:
        </td>
        <td class="tg-s268" style="padding:5px 5px;font-weight:bold;font-size:11px;">
        {!! $model->collection->collection_date ?? '' !!}
        </td>
    </tr>
    <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
        Local de Colheita:
        </td>
        <td class="tg-s268" style="padding:5px 5px;font-weight:bold;font-size:11px;">
        {!! mb_strtoupper($model?->collection?->location) !!}
        </td>
    </tr>
    <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
        Responsável pela Colheita:
        </td>
        <td class="tg-s268" style="padding:5px 5px;font-weight:bold;font-size:11px;">
        {!! $model?->collection?->collected_by_lab ? 'SNCQA' : 'Cliente' !!}
        </td>
    </tr>
    <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
        RFª Plano de Amostragem:
        </td>
        <td class="tg-s268" style="padding:5px 5px;font-weight:bold;font-size:11px;">
        {!! mb_strtoupper($model?->collection?->sampling_plan_ref ?? '--') !!}
        </td>
    </tr>
    <tr>
        <td class="tg-0lax" style="border-bottom:0.3mm solid black;padding-bottom: 0px"></td>
        <td class="tg-0lax" style="border-bottom:0.3mm solid black;padding-bottom: 0px"></td>
    </tr>

    <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
        Data de Início:
        </td>
        <td class="tg-s268" style="padding:5px 5px;font-weight:bold;font-size:11px;">
        {!! mb_strtoupper($model?->collection?->analysis_start_date ?? '--') !!}
        </td>
    </tr>
    <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
        Data de Conclusão:
        </td>
        <td class="tg-s268" style="padding:5px 5px;font-weight:bold;font-size:11px;">
        {!! mb_strtoupper($model?->collection?->analysis_end_date ?? '--') !!}
        </td>
    </tr>
    <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
        Embalagem:
        </td>
        <td class="tg-s268" style="padding:5px 5px;font-weight:bold;font-size:11px;">
        {!! mb_strtoupper($model?->collection?->packaging?->name) ?? '--' !!}
        </td>
    </tr>
    <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
        Estado da Amostra:
        </td>
        <td class="tg-s268" style="padding:5px 5px;font-weight:bold;font-size:11px;">
        {!! mb_strtoupper($model?->collection?->sample_status) ?? '--' !!}
        </td>
    </tr>
    <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
        Origem da Amostra:
        </td>
        <td class="tg-s268" style="padding:5px 5px;font-weight:bold;font-size:11px;">
        {!! mb_strtoupper($model?->collection?->origin) ?? '--' !!}
        </td>
    </tr>
</table>
<!-- <br> -->

</div>

<div style="clear: both; margin: 0pt; padding: 0pt; "></div>

<p style="padding:5px 5px;font-size:10px;">QTD / UN: <b>{!! mb_strtoupper($model?->collection?->qty) !!}</b>, BL / Lote Nº: <b>{!! $model->collection?->bl . ' / ' . $model?->collection?->lot !!}</b>, Nº DU: <b>{!! mb_strtoupper($model?->collection?->du_no) !!}</b>, Nº Contentor: <b>{!! mb_strtoupper($model?->collection?->container_no) !!}</b>.</p>

<p style="padding:5px 5px;font-size:10px;">
    <b>Dados Fornecidos pelo Cliente:</b><br>
    ------
</p>

</div>



<!-- <br>

<table class="tg" width="100%">
    <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;" width="200px">
        Produto:
        </td>
        <td class="tg-s268" style="font-weight:bold;padding:5px 5px;font-size:11px;">
        {{ mb_strtoupper($model->collection->product->name) }}
        </td>
    </tr>
    <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
        Marca Comercial:
        </td>
        <td class="tg-s268" style="padding:5px 5px;font-weight:bold;font-size:11px;">
        {!! $model->collection->comercial_brand ?? '' !!}
        </td>
    </tr>
    <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
        Tipo de Embalagem:
        </td>
        <td class="tg-s268" style="padding:5px 5px;font-weight:bold;font-size:11px;">
        {!! $model->collection->packaging->name !!}
        </td>
    </tr>
    <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
        Quantidade / Unidade:
        </td>
        <td class="tg-s268" style="padding:5px 5px;font-weight:bold;font-size:11px;">
        {!! $model->collection->qty !!}
        </td>
    </tr>
    <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
        Lote / BL:
        </td>
        <td class="tg-s268" style="padding:5px 5px;font-weight:bold;font-size:11px;">
        {!! $model->collection->lot !!}
        </td>
    </tr>
    <tr>
        <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
        Data de Expiração:
        </td>
        <td class="tg-s268" style="padding:5px 5px;font-weight:bold;font-size:11px;">
        {!! ( $model->collection->expiry_date ? Carbon\Carbon::parse($model->collection->expiry_date)->format('d/m/Y') : 'N/A' ) !!}
        </td>
    </tr>
    <tr>
        <td class="tg-0lax" style="border-bottom:0.3mm solid black;"></td>
        <td class="tg-0lax" style="border-bottom:0.3mm solid black;"></td>
    </tr>
</table> -->
<hr style="height: 1px; color:black">
