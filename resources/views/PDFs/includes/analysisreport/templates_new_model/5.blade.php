<table width="100%" style="border-collapse:collapse;border-spacing:0">
    <tr>
        <th style="font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;vertical-align:middle" rowspan="4">
            <center><img src="{!! public_path() . '/img/logo.svg'!!}" style="width: 57px; height: 69px;" alt=""></center>
        </th>
        
        <th style="font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;vertical-align:middle" rowspan="3">
            
            <small style="font-size: 10pt;"><b>{{ \Settings::get('qcertfood_h1') }}</b></small><br>
            <small style="font-size: 10pt;"><b>{{ \Settings::get('qcertfood_h2') }}</b></small><br>
            <small style="font-size: 10pt;"><b>{{ \Settings::get('qcertfood_h3') }}</b></small><br>
        </th>
        
        <th style="font-family:Arial, sans-serif;font-size:10px;font-weight:normal;padding:5px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;vertical-align:middle">
            
            <small>{{ \Settings::get('qcertfood_h4') }} {!! $model->cert_no !!}</small>
            
        </th>
    </tr>
        
    <tr>
        <td style="font-family:Arial, sans-serif;font-size:10px;padding:5px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;vertical-align:middle">
            <small>{{ \Settings::get('qcertfood_h5') }}</small>
        </td>
    </tr>
    
    <tr>
        <td style="font-family:Arial, sans-serif;font-size:10px;padding:0px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;vertical-align:middle">
            <small>{{ \Settings::get('qcertfood_h6') }}</small>
        </td>
    </tr>
    
    <tr>
        <td style="font-family:Arial, sans-serif;text-align:center;font-size:14px;padding:0px 2px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;vertical-align:middle">
            <small style="font-size: 10pt;"><b>{{$model->collection->end_result->name}}</b></small><br>
        </td>
        <td style="font-family:Arial, sans-serif;font-size:10px;padding:0px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;vertical-align:middle">
          
            <small>BOLETIM</small><br>
            
        </td>
    </tr>
</table>

    
    <br>

    <div>
        <div style="float:left;" width="70%">
            <small style="font-weight: bold;font-size: 10pt; color: #000000; font-family: sans;">{{ \Settings::get('qcertfood_company_label') }}: {!! $model->collection->collection->warehouse->customer->company !!}</small><br>
            <small style="font-size: 8pt; color: #000000; font-family: sans;">{{ \Settings::get('qcertfood_warehouse_label') }}: {!! $model->collection->collection->warehouse->street !!} - {!! $model->collection->collection->warehouse->suburb !!} - {!! $model->collection->collection->warehouse->province !!}</small><br>            
            
        </div>

        <div style="float:right; width:30%">
            <small style="font-weight: bold;font-size: 10pt; color: #000000; font-family: sans;">{{ \Settings::get('qcertfood_id_label') }}: {!! $model->collection->code->description !!}</small><br>
            <small style="font-size: 8pt; color: #000000; font-family: sans;">{{ \Settings::get('qcertfood_collectiondate_label') }}: {!! ($model->collection->collection->collectionable->col_date ? $model->collection->collection->collectionable->col_date->format('d/m/Y') : 'N/A') !!}</small><br>         
        </div>
    </div>
    <br>


    <table style="border-collapse:collapse;border-spacing:0" width="100%">
        <tr>
            <td style="font-family:Arial, sans-serif;background-color:#ddd9c3;font-size:11px;height:18px;font-weight:normal;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle" colspan="3">
                <small style="font-size: 9pt; color: #000000; font-family: sans;">{{ \Settings::get('qcertfood_product_label') }}</small>
            </td>
            
            <td style="font-family:Arial, sans-serif;background-color:#ddd9c3;font-size:11px;font-weight:normal;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                
            </td>
            
            <td style="font-family:Arial, sans-serif;background-color:#ddd9c3;font-size:11px;height:18px;font-weight:normal;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle" colspan="3">
                <small style="font-size: 9pt; color: #000000; font-family: sans;">{{ \Settings::get('qcertfood_brand_label') }}</small>
            </td>
        </tr>
        
        <tr>
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle; text-align:left;" colspan="3">
            <small style="font-size: 9pt; text-align:center; color: #000000; font-family: sans;">{!! $model->collection->product->description or '' !!}</small>
            </td>
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                
            </td>
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle;text-align:left;" colspan="3">
            <small style="font-size: 9pt; text-align:center; color: #000000; font-family: sans;">{!! $model->collection->comercial_brand or '' !!}</small>
            </td>
        </tr>
        
        <tr>
            <td style="font-family:Arial, sans-serif;background-color:#ddd9c3;font-size:11px;height:18px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle" colspan="3">
                <small style="font-size: 9pt; color: #000000; font-family: sans;">{{ \Settings::get('qcertfood_packaging_label') }}</small>
            </td>
            
            <td style="font-family:Arial, sans-serif;background-color:#ddd9c3;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                
            </td>
            
            <td style="font-family:Arial, sans-serif;background-color:#ddd9c3;font-size:11px;height:18px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle" colspan="3">
                <small style="font-size: 9pt; color: #000000; font-family: sans;">{{ \Settings::get('qcertfood_quantity_label', 'Quantidade/Unidade') }}</small>
            </td>
        </tr>
        
        <tr>
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle" colspan="2">
                <small style="font-size: 9pt; color: #000000; font-family: sans;">{!! $model->collection->packaging->name !!}</small>
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle" colspan="2">
            
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle" colspan="2">
                <small style="font-size: 9pt; color: #000000; font-family: sans;">{!! $model->collection->collection->collectionable->qty !!}</small>
            </td>
        </tr>
        
        <tr>
            <td style="font-family:Arial, sans-serif;height:18px;font-size:11px;background-color:#ddd9c3;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle" colspan="3">
                <small style="font-size: 9pt; color: #000000; font-family: sans;">{{ \Settings::get('qcertfood_bl_label12', 'Local da Colheita') }}</small>
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;background-color:#ddd9c3;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle" >
            
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;height:18px;background-color:#ddd9c3;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle" colspan="3">
                <small style="font-size: 9pt; color: #000000; font-family: sans;">{{ \Settings::get('qcertfood_expiry_label12', 'Referência / Ponto da Colheita') }}</small>
            </td>
        </tr>
        
        <tr>
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle" colspan="3">
                <small style="font-size: 9pt; color: #000000; font-family: sans;">{!! $model->collection->extra_data->location !!}</small>
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
            
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle" colspan="3">
                <small style="font-size: 9pt; color: #000000; font-family: sans;"> {!! $model->collection->extra_data->col_ref_point !!}</small>
            </td>
        </tr>

        <tr>
            <td style="font-family:Arial, sans-serif;height:18px;font-size:11px;background-color:#ddd9c3;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle" colspan="3">
                <small style="font-size: 9pt; color: #000000; font-family: sans;">{{ \Settings::get('qcertfood_bl_label12', 'Hora da Colheita') }}</small>
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;background-color:#ddd9c3;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle" >
            
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;height:18px;background-color:#ddd9c3;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle" colspan="3">
                <small style="font-size: 9pt; color: #000000; font-family: sans;">{{ \Settings::get('qcertfood_expiry_label12', 'Data de Entrada no Laboratório') }}</small>
            </td>
        </tr>

        <tr>
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle" colspan="3">
                <small style="font-size: 9pt; color: #000000; font-family: sans;">{!! $model->collection->extra_data->col_hour !!}</small>
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
            
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle" colspan="3">
                <small style="font-size: 9pt; color: #000000; font-family: sans;"> {!! $model->collection->collection->collectionable->entry_date->format('d/m/y') !!}</small>
            </td>
        </tr>


        <tr>
            <td style="font-family:Arial, sans-serif;height:18px;font-size:11px;background-color:#ddd9c3;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle" colspan="3">
                <small style="font-size: 9pt; color: #000000; font-family: sans;">{{ \Settings::get('qcertfood_bl_label12', 'Profundidade') }}</small>
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;background-color:#ddd9c3;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle" >
            
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;height:18px;background-color:#ddd9c3;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle" colspan="3">
                <small style="font-size: 9pt; color: #000000; font-family: sans;">{{ \Settings::get('qcertfood_expiry_label12', 'Data de Finalização das Análises') }}</small>
            </td>
        </tr>

        <tr>
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle" colspan="3">
                <small style="font-size: 9pt; color: #000000; font-family: sans;">{!! $model->collection->extra_data->col_depth !!}</small>
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
            
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle" colspan="3">
                <small style="font-size: 9pt; color: #000000; font-family: sans;"> {!! $model->collection->code->results->max('updated_at')->format('d/m/y') !!}</small>
            </td>
        </tr>
        
        <tr>
            <td style="font-family:Arial, sans-serif;font-size:11px;height:18px;background-color:#ddd9c3;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle" colspan="3">
            
            </td>
            
            <td style="text-align:center;font-family:Arial, sans-serif;font-size:11px;background-color:#ddd9c3;padding:0px 0px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                <small style="font-size: 9pt; color: #000000; font-family: sans;"></small>
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;height:18px;background-color:#ddd9c3;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle" colspan="3">
            <small style="font-size: 9pt; text-align:center; color: #000000; font-family: sans;"></small>
            </td>
        </tr>
        
        <tr>
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                <small style="font-size: 9pt; text-align:center; color: #000000; font-family: sans;">{{ \Settings::get('qcertfood_parameter_label') }}</small>
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                <small style="font-size: 9pt; text-align:center; color: #000000; font-family: sans;">{{ \Settings::get('qcertfood_result_label') }}</small>
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                <small style="font-size: 9pt; text-align:center; color: #000000; font-family: sans;">{{ \Settings::get('qcertfood_unit_label') }}</small>
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                <small style="font-size: 9pt; text-align:center; color: #000000; font-family: sans;">{{ \Settings::get('qcertfood_method_label') }}</small>
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                <small style="font-size: 9pt; text-align:center; color: #000000; font-family: sans;">{{ \Settings::get('qcertfood_standard_label') }}</small>
            </td>

            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                <small style="font-size: 9pt; text-align:center; color: #000000; font-family: sans;">{{ \Settings::get('qcertfood_standard_label13', 'Val. de Orientação') }}</small>
            </td>

        </tr>
       
        @if($model->collection->code->results->count() > 0)
        @foreach($model->collection->code->samples as $key => $sample)
        @if($sample->results)
        @foreach($sample->results as $result)
        <!-- @if ($result->counter_analysis)
        <tr>
            <td style=" font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                <small style="font-size: 9pt; text-align:center; color: #000000;">{!! str_replace('º', 'o', $result->parameter->description) !!}</small>
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                @include("quacertificate.results.{$result->type}", ['result' => $result])
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                <small style="font-size: 9pt; text-align:center; color: #000000; font-family: sans;">{!! getPivot($result->parameter_id, $result->sample->analysis->profile->id)[0]['unit'] !!}</small>
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                <small style="font-size: 9pt; text-align:center; color: #000000; font-family: sans;">{!! getPivot($result->parameter_id, $result->sample->analysis->profile->id)[0]['method'] !!}</small>
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                <small style="font-size: 9pt; text-align:center; color: #000000; font-family: sans;">{!! getPivot($result->parameter_id, $result->sample->analysis->profile->id)[0]['standard'] !!}</small> 
            </td>

            <td style="text-align:center;font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                <small style="font-size: 9pt; text-align:center; color: #000000; font-family: sans;">{!! getPivot($result->parameter_id, $result->sample->analysis->profile->id)[0]['min_ref_value'] .'-' . getPivot($result->parameter_id, $result->sample->analysis->profile->id)[0]['max_ref_value'] !!}</small> 
            </td>
            
        </tr>
        <tr>
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                <small style="font-size: 9pt; text-align:center; color: red; font-family: sans;">{!! str_replace('º', 'o', $result->parameter->description) !!}</small>
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                @include("quacertificate.results.{$result->type}", ['result' => $result])
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                <small style="font-size: 9pt; text-align:center; color: red; font-family: sans;">{!! getPivot($result->parameter_id, $result->sample->analysis->profile->id)[0]['unit'] !!}</small>
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                <small style="font-size: 9pt; text-align:center; color: red; font-family: sans;">{!! getPivot($result->parameter_id, $result->sample->analysis->profile->id)[0]['method'] !!}</small>
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                <small style="font-size: 9pt; text-align:center; color: red; font-family: sans;">{!! getPivot($result->parameter_id, $result->sample->analysis->profile->id)[0]['standard'] !!}</small>
            </td>

            <td style="text-align:center;font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                <small style="font-size: 9pt; text-align:center; color: #000000; font-family: sans;">{!! getPivot($result->parameter_id, $result->sample->analysis->profile->id)[0]['min_ref_value'] .'-' . getPivot($result->parameter_id, $result->sample->analysis->profile->id)[0]['max_ref_value'] !!}</small> 
            </td>
        </tr>
        @else -->
        <tr>
        <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                <small style="font-size: 9pt; text-align:center; color: #000000; font-family: sans;">{!! str_replace('º', '°', $result->parameter->description) !!}</small>
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                @include("quacertificate.results.{$result->type}", ['result' => $result])
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                <small style="font-size: 9pt; text-align:center; color: #000000; font-family: sans;">{!! getPivot($result->parameter_id, $result->sample->analysis->profile->id)[0]['unit'] !!}</small>
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                <small style="font-size: 9pt; text-align:center; color: #000000; font-family: sans;">{!! getPivot($result->parameter_id, $result->sample->analysis->profile->id)[0]['method'] !!}</small>
            </td>
            
            <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                <small style="font-size: 9pt; text-align:center; color: #000000; font-family: sans;">{!! getPivot($result->parameter_id, $result->sample->analysis->profile->id)[0]['standard'] !!}</small>
            </td>

            <td style="text-align:center;font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                <small style="font-size: 9pt; text-align:center; color: #000000; font-family: sans;">{!! getPivot($result->parameter_id, $result->sample->analysis->profile->id)[0]['min_ref_value'] .'-' . getPivot($result->parameter_id, $result->sample->analysis->profile->id)[0]['max_ref_value'] !!}</small> 
            </td>
        </tr>
        <!-- @endif -->
        @endforeach
        
        @else
            <tr>
                <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                    <small style="font-size: 9pt; text-align:center; color: red; font-family: sans;">N/C</small>
                </td>
                <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                    <small style="font-size: 9pt; text-align:center; color: red; font-family: sans;">N/C</small>
                </td>
                <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                    <small style="font-size: 9pt; text-align:center; color: red; font-family: sans;">N/C</small>
                </td>
                <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                    <small style="font-size: 9pt; text-align:center; color: red; font-family: sans;">N/C</small>
                </td>
                <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                    <small style="font-size: 9pt; text-align:center; color: red; font-family: sans;">N/C</small>
                </td>
                <td style="font-family:Arial, sans-serif;font-size:11px;padding:0px 5px;border-style:solid;overflow:hidden;word-break:normal;vertical-align:middle">
                    <small style="font-size: 9pt; color: red; font-family: sans;">N/C</small>
                </td>
            </tr>
        @endif
        @endforeach
        @endif
        
    </table>
    
<br>

<br>
<h4>{{ \Settings::get('qcertfood_obs_label') }}:</h4>        
<small>{!! $model->obs !!}</small>
<br><br>

@include('quacertificate.templates.obs')

@if ($model->status == '1')
    <small style="font-size:9pt;text-decoration:underline; font-weight: bold;">{!! $model->fine_print !!}</small>
    @else <small style="text-decoration:underline; font-weight: bold; color:red;">{!! $model->fine_print !!}</small>
@endif

<br><br>
<small>{{ \Settings::get('app_province') }}, {!! $model->created_at->format('d/m/Y') !!}</small><br><br>

<small style="font-size:11pt;font-weight:bold;">{{ \Settings::get('lab_name') }}</small><br>
