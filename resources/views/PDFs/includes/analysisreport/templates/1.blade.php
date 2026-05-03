<br>
    <table class="tg" width="100%">
        <thead>
            <tr>
                <th class="tg-s268" style="padding:5px 5px;text-align:left;font-weight:bold;">
                    ENSAIO</th>
                <th class="tg-0lax" style="padding:5px 5px;text-align:left;font-weight:bold;">
                MÉTODO</th>
                <th class="tg-s268" style="padding:5px 5px;text-align:center;font-weight:bold;">
                    Resultados e U</th>
                <th class="tg-0lax" style="padding:5px 5px;text-align:center;font-weight:bold;">
                    Unidades</th>
                <th class="tg-s268" style="padding:5px 5px;text-align:center;font-weight:bold" colspan="2">
                Limites <small style="font-weight: normal; font-style: italic">(Limits)</small></th>
            </tr>
            <tr>
                <th style="border-bottom: 0.25mm solid black;text-align:left">
                    <small style="padding:5px 5px;font-weight: normal; font-style: italic;text-align:left">(Parameter)</small>
                </th>
                <th style="border-bottom: 0.25mm solid black;text-align:left">
                    <small style="padding:5px 5px;font-weight: normal; font-style: italic;text-align:left">(Method)</small>
                </th>
                <th style="border-bottom: 0.25mm solid black;text-align:center">
                    <small style="padding:5px 5px;font-weight: normal; font-style: italic;text-align:center">(Results and U)</small>
                </th>
                <th style="border-bottom: 0.25mm solid black;text-align:center">
                    <small style="padding:5px 5px;font-weight: normal; font-style: italic;text-align:center">(Units)</small>
                </th>
                <th style="border-bottom: 0.25mm solid black">
                    <small style="padding:5px 5px;font-weight: normal;font-weight:bold">Min</small>
                </th>
                <th style="border-bottom: 0.25mm solid black">
                    <small style="padding:5px 5px;font-weight: normal;font-weight:bold">Max</small>
                </th>
              </tr>
            <tr>
                <td class="tg-s268" style="padding:5px 5px;text-align:left;font-size:8px;" colspan="6">
                    <b>{{ $model->collection->code->results->pluck('ref_val_origin')->unique()->implode(', ') }}</b>
                </td>
            </tr>
        </thead>

        @if($model->collection->code->results->count() > 0)
        @foreach($model->collection->code->samples as $key => $sample)
        @if($sample->results)
        @foreach($sample->results as $result)
        <tr>
            <td class="tg-s268" style="padding:5px 5px;text-align:left;font-size:9pt">
                {!! str_replace('º', '°', $result->parameter_label) !!}
            </td>
            <td class="tg-0lax" style="padding:5px 5px;text-align:left;font-size:9pt">
                {!! $result->protocol_label !!}
            </td>
            <td class="tg-s268" style="padding:5px 5px;text-align:center;font-size:9pt">
                @include("PDFs.includes.analysisreport.results.{$result->type_id}", ['result' => $result])
            </td>
            <td class="tg-0lax" style="padding:5px 5px;text-align:center;font-size:9pt">
                {!! $result->unit_label !!}
            </td>
            <td class="tg-0lax" style="padding:5px 5px;text-align:center;font-size:9pt">
                {!! $result->min_ref_value !!}
            </td>
            <td class="tg-0lax" style="padding:5px 5px;text-align:center;font-size:9pt">
                {!! $result->ref_val_origin ? '(' . substr($result->ref_val_origin, 0, 1) . ')' : '' !!} {!! $result->max_ref_value !!}
            </td>
        </tr>
        @endforeach

        @else
        <tr>
            <td class="tg-s268" style="padding:5px 5px;text-align:left"></td>
            <td class="tg-s268" style="padding:5px 5px;text-align:center"></td>
            <td class="tg-s268" style="padding:5px 5px;text-align:center"></td>
            <td class="tg-0lax" style="padding:5px 5px;text-align:left"></td>
            <td class="tg-0lax" style="padding:5px 5px;text-align:left"></td>
            <td class="tg-0lax" style="padding:5px 5px;text-align:left"></td>
            <td class="tg-0lax" style="padding:5px 5px;text-align:left"></td>
        </tr>
        @endif
        @endforeach
        @endif
    </table>

    <p style="padding:5px 5px;font-size:10px;text-align:center">
        <b>Fim dos resultados analíticos. </b>
    </p>    
<br>