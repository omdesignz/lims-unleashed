<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link href="http://app.mediagagroalimentar.com/public/css/bootstrap.min.css"> 
    <style>
        @page {
        header: page-header;
        footer: page-footer;
    }
        body {font-family: sans-serif;
            font-size: 10pt;
        }

        table.center {
        width:100%; 
        
    }

        body {
            margin-top: 0px;
        }
    </style>
</head>
<body>
<sethtmlpagefooter name="page-footer" value="on" />

<htmlpagefooter name="page-footer">
    <div style="border-top: 1px solid black; font-size: 9pt; text-align: center; padding-top: 3mm; ">
    <small style="align:left;"> Página {PAGENO} de {nb} </small><br>
    </div>
</htmlpagefooter>
    
    <div style="text-align: center; font-weight: bold">
        <h3 style="text-decoration:underline;">FOLHA DE TRABALHO</h3><br>
    </div>

    <div>
        <h4>Data da Colheita: {!! Carbon\Carbon::parse($model->collection_date)->format('d/m/Y') !!}</h4>
    </div>
        @if ($model->code->samples->count() > 0)

        <table align="center" class="center" style="border: 0.3mm solid black">
            <thead>        
                <tr>
                    <th style="text-align:left;border-bottom: 0.3mm solid black; border-right: 0.3mm solid black; border-left: 0.3mm solid black"></th>
                   @foreach($parameters->unique('id') as $parameter) 
                    <th style="text-align:center;border-bottom: 0.3mm solid black; border-right: 0.3mm solid black; border-left: 0.3mm solid black">
                        {!! ($parameter['code'] == '' ? $parameter['description'] : $parameter['code']) !!}
                    </th>
                    @if (count($parameter['new_dilutions']) > 0)
                        @foreach ($parameter['new_dilutions'] as $dilution)
                            @foreach ($dilution as $item)
                                <th style="text-align:center;border-bottom: 0.3mm solid black; border-right: 0.3mm solid black; border-left: 0.3mm solid black">Dil: {{ $item['ratio'] }}</th>
                            @endforeach
                        @endforeach
                    @endif
                    <!-- <th style="text-align:center;border-bottom: 0.3mm solid black; border-right: 0.3mm solid black; border-left: 0.3mm solid black">Dil</th> -->
                   @endforeach 
                </tr>                
            </thead>
            <tbody> 
            @foreach($model->code->samples as $sample)            
                <tr>
                    <td style="border-bottom: 0.3mm solid black; border-right: 0.3mm solid black; border-left: 0.3mm solid black">
                        <small style="font-size: 8pt; font-size: 8pt; color: #000000; font-family: sans;">
                            <p>
                                {!! $model->code->code . ' ' . $sample->analysis->department->name !!}
                            </p>
                            <p>
                                <barcode code="{!!str_replace('/', '', $model->code->description) !!}" text="-1" height="0.26" />
                            </p>
                            </small>
                    </td>
                    @foreach($parameters->unique('id') as $parameter)
                    
                        @if($sample->analysis->profile->parameters->contains('id', $parameter['id']))
                        <td style="text-align:center;border-bottom: 0.3mm solid black; border-right: 0.3mm solid black; border-left: 0.3mm solid black">
                            
                        </td>
                        @if (count($parameter['new_dilutions']) > 0)

                        @foreach ($parameter['new_dilutions'] as $dilution)
                            @foreach ($dilution as $item)
                                <td style="text-align:center;border-bottom: 0.3mm solid black; border-right: 0.3mm solid black; border-left: 0.3mm solid black"></td> 
                            @endforeach 

                        @endforeach
                            
                        @endif  
                        <!-- <td style="text-align:center;border-bottom: 0.3mm solid black; border-right: 0.3mm solid black; border-left: 0.3mm solid black"><small>{!! $parameter['dilutions'] !!}</small></td>   -->
                        @else
                        <td style="text-align:center;background-color:#98FB98;border-bottom: 0.3mm solid black; border-right: 0.3mm solid black; border-left: 0.3mm solid black"></td>
                        <td style="text-align:center;border-bottom: 0.3mm solid black; border-right: 0.3mm solid black; border-left: 0.3mm solid black"><small></small></td>
                        @endif
                    
                    @endforeach

                   
                </tr>  
                @endforeach 
            </tbody>
        </table>

        @else

        <div>
            <h2>A amostra não foi colocada em análise</h2>
        </div>
            
        @endif        
    
         <br>
        <div>
            <small>Técnico de Microbiologia / Fisico-Química: _________________________________</small> <br><br>
            <small>Técnico de Codificação: _________________________________</small><br><br>
            <small>Data: {{ now()->format('d/m/Y') }}</small>
        </div>  

        <!-- <hr style="border: 0.3mm solid black"> -->

</body>
</html>