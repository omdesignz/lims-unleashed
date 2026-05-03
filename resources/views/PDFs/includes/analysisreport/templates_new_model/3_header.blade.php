<table class="tg" width="100%" style="border-collapse:collapse">
  <tr>
    <th class="tg-s268" rowspan="4" style="border: solid black; border-width:1.5px;text-align:center;padding:5px 5px;" width="15%">
        <center><img src="{!! public_path() . '/img/logo.svg'!!}" style="width: 57px; height: 69px;" alt=""></center>
    </th>
    <th class="tg-s268" style="border-right:0.4mm solid black;border-top:0.4mm solid black;border-left:0.4mm solid black;padding:5px 5px;" width="60%">
       {{ \Settings::get('qcertfood_h1') }}
    </th>
    <th class="tg-0lax" style="border: solid black; border-width:1.5px;padding:5px 5px;text-align:center;font-size:9px">
        <b>Boletim Nº {!! $model->cert_no !!} </b>
    </th>
  </tr>
  <tr>
    <td class="tg-0lax" style="border-right:0.4mm solid black;border-left:0.4mm solid black;padding:5px 5px;text-align:center">
        {{ \Settings::get('qcertfood_h2') }}
    </td>
    <td class="tg-0lax" style="border: solid black; border-width:1.5px;padding:5px 5px;vertical-align:bottom;font-size:9px;">
        DU - {{ strtoupper($model->collection->du_no) }}
    </td>
  </tr>
  <tr>
    <td class="tg-0lax" style="border-right:0.4mm solid black;border-left:0.4mm solid black;padding:5px 5px;text-align:center">
        {{ \Settings::get('qcertfood_h3') }}<br>
        <b>LABORATÓRIO REGIONAL DE VETERINÁRIA E AGROALIMENTAR DE CABINDA</b>
    </td>
    <td class="tg-0lax" style="border-right:0.4mm solid black;border-left:0.4mm solid black;padding:5px 5px;font-size:9px;">
        Nº Contentor:<br>
        {!! $model->collection->container_no !!}
    </td>
  </tr>
  <tr>
    <td class="tg-0lax" style="border-right:0.4mm solid black;border-left:0.4mm solid black;padding:5px 5px;text-align:center">
        <b>LABORATÓRIO CENTRAL</b>
    </td>
    <td class="tg-0lax" style="border-top:0.4mm solid black;border-right:0.4mm solid black;border-left:0.4mm solid black;padding:5px 5px;font-size:9px;">
        N. Contentor:
    </td>
  </tr>
  <tr>
    <td class="tg-0lax" style="border: solid black; border-width:1.5px;text-align:center;padding:5px 5px;">
        BOLETIM DE ANÁLISES
    </td>
    <td class="tg-0lax" style="border: solid black; border-width:1.5px;padding:5px 5px;font-size:9px">
        {{ \Settings::get('qcertfood_h6') }}
    </td>
  </tr>
</table><br>

 <table class="tg" width="100%" >
        <tr>
            <td class="tg-s268" style="padding:5px 5px;font-size:11px;" width="10px;">
               {{ \Settings::get('qcertfood_company_label') }}:
            </td>
            <td class="tg-s268" style="font-weight:bold;padding:5px 5px;font-size:11px;">
                {!! strtoupper($model->collection->collection->warehouse->customer->company) !!}
            </td>
            <td class="tg-s268" style="font-weight:bold;padding:5px 5px; padding-left:210px;font-size:11px;">
                {{ \Settings::get('qcertfood_id_label') }}:
            </td>
            <td class="tg-s268" style="text-align:right;padding:5px 5px;font-weight:bold;font-size:11px;">
                {!! $model->collection->code->description !!}
            </td>
        </tr>
        <tr>
            <td class="tg-0lax" style="padding:5px 5px;font-size:11px;">
               {{ \Settings::get('qcertfood_warehouse_label') }}:
            </td>
            <td class="tg-0lax" style="padding:5px 5px;font-size:11px;">
               
                {!! $model->collection->collection->warehouse->suburb !!}
                
            </td>
            <td class="tg-0lax" style="padding:5px 5px;padding-left:210px;font-size:11px;">
               {{ \Settings::get('qcertfood_collectiondate_label') }}:    
            </td>
            <td class="tg-0lax" style="text-align:right;padding:5px 5px;font-size:11px;">
                {!! ($model->collection->extra_data->col_date ? $model->collection->extra_data->col_date : 'N/A') !!}    
            </td>
        </tr>
    </table>

    <br>

    <table class="tg" width="100%">
        <tr>
            <td class="tg-s268" style="padding:5px 5px;background-color:#ddd9c3;text-align:center">
                {{ \Settings::get('qcertfood_product_label') }}
            </td>
            <td class="tg-s268" style="padding:5px 5px;background-color:#ddd9c3;text-align:center">
                {{ \Settings::get('qcertfood_brand_label12', 'Responsável pela Colheita') }}
            </td>
        </tr>
        <tr>
        <td class="tg-s268" style="padding:5px 5px;text-align:center;font-weight:bold;">
            {{ strtoupper($model->collection->product->description) }}
        </td>
        <td class="tg-s268" style="padding:5px 5px;text-align:center;font-weight:bold;">
            {!! strtoupper($model->collection->collection->collectionable->user->full_name) !!}
        </td>
        </tr>
        <tr>
            <td class="tg-0lax" style="padding:5px 5px;background-color:#ddd9c3;text-align:center">
                {{ \Settings::get('qcertfood_packaging_label') }}
            </td>
            <td class="tg-0lax" style="padding:5px 5px;background-color:#ddd9c3;text-align:center">
                {{ \Settings::get('qcertfood_quantity_label', 'Quantidade/Unidade') }}
            </td>
        </tr>
        <tr>
            <td class="tg-0lax" style="padding:5px 5px;text-align:center">
                {!! $model->collection->packaging->name !!}
            </td>
            <td class="tg-0lax" style="padding:5px 5px;text-align:center">
                {!! $model->collection->qty !!}
            </td>
        </tr>
        <tr>
            <td class="tg-0lax" style="padding:5px 5px;background-color:#ddd9c3;text-align:center">
                {{ \Settings::get('qcertfood_bl_label12', 'Local da Colheita') }}
            </td>
            <td class="tg-0lax" style="padding:5px 5px;background-color:#ddd9c3;text-align:center">
                {{ \Settings::get('qcertfood_expiry_label12', 'Referência / Ponto da Colheita') }}
            </td>
        </tr>
        <tr>
            <td class="tg-0lax" style="padding:5px 5px;text-align:center">
                {!! strtoupper($model->collection->extra_data->location) !!}
            </td>
            <td class="tg-0lax" style="padding:5px 5px;text-align:center">
                {!! strtoupper($model->collection->extra_data->col_ref_point) !!}
            </td>
        </tr>

        <tr>
            <td class="tg-0lax" style="padding:5px 5px;background-color:#ddd9c3;text-align:center">
                {{ \Settings::get('qcertfood_bl_label12', 'Hora da Colheita') }}
            </td>
            <td class="tg-0lax" style="padding:5px 5px;background-color:#ddd9c3;text-align:center">
                {{ \Settings::get('qcertfood_expiry_label12', 'Data de Entrada no Laboratório') }}
            </td>
        </tr>
        <tr>
            <td class="tg-0lax" style="padding:5px 5px;text-align:center">
                {!! $model->collection->extra_data->col_hour !!}
            </td>
            <td class="tg-0lax" style="padding:5px 5px;text-align:center">
                {!! $model->collection->collection->collectionable->entry_date->format('d/m/y') !!}
            </td>
        </tr>

        <tr>
            <td class="tg-0lax" style="padding:5px 5px;background-color:#ddd9c3;text-align:center">
                {{ \Settings::get('qcertfood_bl_label12', 'Profundidade') }}
            </td>
            <td class="tg-0lax" style="padding:5px 5px;background-color:#ddd9c3;text-align:center">
                {{ \Settings::get('qcertfood_expiry_label12', 'Data de Finalização das Análises') }}
            </td>
        </tr>
        <tr>
            <td class="tg-0lax" style="padding:5px 5px;text-align:center">
                {!! $model->collection->extra_data->col_depth !!}
            </td>
            <td class="tg-0lax" style="padding:5px 5px;text-align:center">
                {!! $model->collection->code->results->max('updated_at')->format('d/m/y') !!}
            </td>
        </tr>

        <tr>
            <td class="tg-0lax" style="padding:14px 5px;background-color:#ddd9c3;text-align:center"></td>
            <td class="tg-0lax" style="padding:5px 5px;background-color:#ddd9c3;text-align:center"></td>
        </tr>
    </table><br>