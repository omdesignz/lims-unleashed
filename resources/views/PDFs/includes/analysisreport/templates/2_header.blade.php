<div style="text-align: center;" width="90%">
<center><img src="{!! public_path() . '/images/aocrest.svg'!!}" style="width: 57px; height: 69px;" alt="" width="8%"></center>
</div><br>
    <table class="tg" width="100%" style="border-collapse:collapse;">
        <tr>
          <th class="tg-s268" rowspan="4" style="border: solid black; border-width:1.5px;text-align:center;padding:5px 5px;" width="15%">
              <center><img src="{{ public_path() . '/images/inis.svg' }}" width="14%" alt=""></center>
          </th>
          <th class="tg-s268" style="border-right:0.4mm solid black;border-top:0.4mm solid black;border-left:0.4mm solid black;padding:5px 5px;" width="60%">
             REPÚBLICA DE ANGOLA
          </th>
          <th class="tg-0lax" style="border: solid black; border-width:1.5px;padding:5px 5px;text-align:center;font-size:9px">
              <b>Boletim Nº {!! $model->code !!} </b>
          </th>
        </tr>
        <tr>
          <td class="tg-0lax" style="border-right:0.4mm solid black;border-left:0.4mm solid black;padding:5px 5px;text-align:center">
             MINISTÉRIO DA SAÚDE
          </td>
          <td class="tg-0lax" style="border: solid black; border-width:1.5px;padding:5px 5px;vertical-align:bottom;font-size:9px;">
              DU - {{ mb_strtoupper($model->collection->du_no) }}
          </td>
        </tr>
        <tr>
          <td class="tg-0lax" style="border-right:0.4mm solid black;border-left:0.4mm solid black;padding:5px 5px;text-align:center">
              <!-- Third Line<br> -->
              <b>INIS</b>
          </td>
          <td class="tg-0lax" style="border-right:0.4mm solid black;border-left:0.4mm solid black;padding:5px 5px;font-size:9px;">
              Nº Contentor:<br>
              {!! $model->collection->container_no !!}
          </td>
        </tr>
        <tr>
          <td class="tg-0lax" style="border: solid black; border-width:1.5px;text-align:center;padding:5px 5px;">
              BOLETIM DE ANÁLISES
          </td>
          <td class="tg-0lax" style="border: solid black; border-width:1.5px;padding:5px 5px;font-size:9px">
              <!-- Last Line -->
          </td>
        </tr>
      </table>
<br>


    <table class="tg" width="100%" >
        <tr>
            <td class="tg-s268" style="padding:5px 5px;font-size:11px;" width="10px;">
               Cliente:
            </td>
            <td class="tg-s268" style="font-weight:bold;padding:5px 5px;font-size:11px;">
                {!! mb_strtoupper($model?->collection?->customer->name) !!}
            </td>
            <td class="tg-s268" style="font-weight:bold;padding:5px 5px; padding-left:210px;font-size:11px;">
                Código de Laboratório:
            </td>
            <td class="tg-s268" style="text-align:right;padding:5px 5px;font-weight:bold;font-size:11px;">
                {!! $model?->lab_code?->code !!}
            </td>
        </tr>
        <tr>
            <td class="tg-0lax" style="padding:5px 5px;font-size:11px;">
               Armazém:
            </td>
            <td class="tg-0lax" style="padding:5px 5px;font-size:11px;">

                    {!! $model->collection->warehouse->address !!}

            </td>
            <td class="tg-0lax" style="padding:5px 5px;padding-left:210px;font-size:11px;">
               Data de Colheita:
            </td>
            <td class="tg-0lax" style="text-align:right;padding:5px 5px;font-size:11px;">
               {!! ($model->collection->collection_date ? $model->collection?->collection_date : 'N/A') !!}
            </td>
        </tr>
    </table>

    <br>

    <table class="tg" width="100%">
        <tr>
            <td class="tg-s268" style="padding:5px 5px;font-size:11px;" width="200px">
            Produto:
            </td>
            <td class="tg-s268" style="font-weight:bold;padding:5px 5px;font-size:11px;">
            {{ mb_strtoupper($model?->collection?->product?->name) }}
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
            {!! $model?->collection?->packaging?->name !!}
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
            {!! $model?->collection->lot !!}
            </td>
        </tr>
        <tr>
            <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
            Data de Expiração:
            </td>
            <td class="tg-s268" style="padding:5px 5px;font-weight:bold;font-size:11px;">
            {!! ( $model->collection->expiry_date ? Carbon\Carbon::parse($model?->collection->expiry_date)->format('d/m/Y') : 'N/A' ) !!}
            </td>
        </tr>
        <tr>
            <td class="tg-0lax" style="border-bottom:0.3mm solid black;"></td>
            <td class="tg-0lax" style="border-bottom:0.3mm solid black;"></td>
        </tr>
    </table>
