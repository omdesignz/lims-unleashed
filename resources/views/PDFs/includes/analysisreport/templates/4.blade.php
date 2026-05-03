    <br>
    <table class="tg" width="100%">
        <thead>
            <tr>
                <th class="tg-s268" style="padding:5px 5px;text-align:center;font-weight:bold;">
                    {{ \Settings::get('qcertfood_parameter_label') }}</th>
                <th class="tg-s268" style="padding:5px 5px;text-align:center;font-weight:bold;">
                    {{ \Settings::get('qcertfood_result_label') }}</th>
                <!-- <th class="tg-s268" style="padding:5px 5px;text-align:center;font-weight:bold;">
                    {{ \Settings::get('qcertfood_limit_label', 'Limite') }}</th>     -->
                <th class="tg-0lax" style="padding:5px 5px;text-align:center;font-weight:bold;">
                    {{ \Settings::get('qcertfood_unit_label') }}</th>
                <th class="tg-0lax" style="padding:5px 5px;text-align:center;font-weight:bold;">
                    {{ \Settings::get('qcertfood_method_label') }}</th>
                <th class="tg-0lax" style="padding:5px 5px;text-align:center;font-weight:bold;">
                    {{ \Settings::get('qcertfood_standard_label') }}</th>
            </tr>
        </thead>

        @if($model->collection->code->results->count() > 0)
        @foreach($model->collection->code->samples as $key => $sample)
        @if($sample->results)
        @foreach($sample->results as $result)
        <tr>
            <td class="tg-s268" style="padding:5px 5px;text-align:center">
                {!! str_replace('º', '°', $result->parameter->description) !!}
            </td>
            <td class="tg-s268" style="padding:5px 5px;text-align:center">
                @include("quacertificate.results.{$result->type}", ['result' => $result])
            </td>
            <!-- <td class="tg-s268" style="padding:5px 5px;text-align:center">
                
                {!! getPivot($result->parameter_id, $result->sample->analysis->profile->id)[0]['max_ref_value'] !!}
            </td> -->
            <td class="tg-0lax" style="padding:5px 5px;text-align:center">
                {!! getPivot($result->parameter_id, $result->sample->analysis->profile->id)[0]['unit'] !!}
            </td>
            <td class="tg-0lax" style="padding:5px 5px;text-align:center">
                {!! getPivot($result->parameter_id, $result->sample->analysis->profile->id)[0]['method'] !!}
            </td>
            <td class="tg-0lax" style="padding:5px 5px;text-align:center">
                {!! getPivot($result->parameter_id, $result->sample->analysis->profile->id)[0]['standard'] !!}
            </td>
        </tr>
        @endforeach

        @else
        <tr>
            <td class="tg-s268" style="padding:5px 5px;text-align:center"></td>
            <td class="tg-s268" style="padding:5px 5px;text-align:center"></td>
            <td class="tg-0lax" style="padding:5px 5px;text-align:center"></td>
            <td class="tg-0lax" style="padding:5px 5px;text-align:center"></td>
            <td class="tg-0lax" style="padding:5px 5px;text-align:center"></td>
        </tr>
        @endif
        @endforeach
        @endif
    </table>
<br>