    <br>
    <table class="tg" width="100%">
        <thead>
            <tr>
                <th class="tg-s268" style="padding:5px 5px;text-align:left;font-weight:bold;">
                    Parâmetro</th>
                <th class="tg-s268" style="padding:5px 5px;text-align:center;font-weight:bold;">
                    Resultado</th>
                <th class="tg-0lax" style="padding:5px 5px;text-align:left;font-weight:bold;">
                    Unidade</th>
                <th class="tg-0lax" style="padding:5px 5px;text-align:left;font-weight:bold;">
                    Metodologia</th>
                <th class="tg-0lax" style="padding:5px 5px;text-align:left;font-weight:bold;">
                    Normativa</th>
            </tr>
        </thead>

        @if($model->collection->code->results->count() > 0)
        @foreach($model->collection->code->samples as $key => $sample)
        @if($sample->results)
        @foreach($sample->results as $result)
        <tr>
            <td class="tg-s268" style="padding:5px 5px;text-align:left">
                {!! str_replace('º', '°', $result->parameter_label) !!}
            </td>
            <td class="tg-s268" style="padding:5px 5px;text-align:center">
                @include("PDFs.includes.analysisreport.results.{$result->type_id}", ['result' => $result])
            </td>
            <td class="tg-0lax" style="padding:5px 5px;text-align:left">
                {!! $result->unit_label !!}
            </td>
            <td class="tg-0lax" style="padding:5px 5px;text-align:left">
                {!! $result->protocol_label !!}
            </td>
            <td class="tg-0lax" style="padding:5px 5px;text-align:left">
                {!! $result->standard_label !!}
            </td>
        </tr>
        @endforeach

        @else
        <tr>
            <td class="tg-s268" style="padding:5px 5px;text-align:left"></td>
            <td class="tg-s268" style="padding:5px 5px;text-align:center"></td>
            <td class="tg-0lax" style="padding:5px 5px;text-align:left"></td>
            <td class="tg-0lax" style="padding:5px 5px;text-align:left"></td>
            <td class="tg-0lax" style="padding:5px 5px;text-align:left"></td>
        </tr>
        @endif
        @endforeach
        @endif
    </table>

    <p>Fim dos resultados analíticos.</p>
<br>