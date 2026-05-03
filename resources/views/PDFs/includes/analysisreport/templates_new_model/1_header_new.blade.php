{{-- <div style="text-align: center;" width="90%">
    <center><img src="{!! public_path() . '/img/logo.svg'!!}" width="8%"></center>
</div><br> --}}

    <table class="tg" width="100%" CELLSPACING=0>
        <tr>
            <td style="border-bottom:0.3mm solid black;" rowspan="7">
                <img src="{!! public_path() . '/img/logo.svg'!!}" width="8%">
            </td>
            <td style="border-bottom:0.3mm solid black;" rowspan="7">
                <img src="{!! public_path() . '/img/inacoq_logo.png'!!}" width="14%" alt="">
            </td>
        </tr>
        <tr>
            <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
                First Line
            </td>
        </tr>
        <tr>
            <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
                Second Line
            </td>
        </tr>
        <tr>
            <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
                Third Line
                
            </td>
        </tr>
        <tr>
            <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
                BOLETIM DE ANÁLISES Nº {!! $model->code !!}
            </td>
        </tr>
        <tr>
            <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
                CONTENTOR Nº {!! $model->collection->container_no !!}
            </td>
        </tr>
        <tr>
            <td class="tg-s268" style="padding:5px 5px;font-size:11px;border-bottom:0.3mm solid black;">
                DU Nº {{ mb_strtoupper($model->collection->du_no) }}
            </td>
        </tr>
        
    </table>
    
    
    <table class="tg" width="100%">
        <tr>
            <td class="tg-s268" style="padding:5px 5px;font-size:11px;" width="10px;">
                <img src="{!! public_path() . '/img/hicons/heroicons-mini/user.svg'!!}">
            </td>
            <td class="tg-s268" style="font-weight:bold;padding:5px 5px;font-size:11px;">
                {!! mb_strtoupper($model->collection->collection->customer->name) !!}
            </td>
            <td class="tg-s268" style="font-weight:bold;padding:5px 5px; padding-left:410px;font-size:11px;">
                <img src="{!! public_path() . '/img/hicons/heroicons-mini/archive-box.svg'!!}">
            </td>
            <td class="tg-s268" style="text-align:right;padding:5px 5px;font-weight:bold;font-size:11px;">
                {!! $model->collection->code->description !!}
            </td>
        </tr>
        <tr>
            <td class="tg-0lax" style="padding:5px 5px;font-size:11px;">
                <img src="{!! public_path() . '/img/hicons/heroicons-mini/building-storefront.svg'!!}">
            </td>
            <td class="tg-0lax" style="padding:5px 5px;font-size:11px;">

                    {!! $model->collection->collection->warehouse->address !!}

            </td>
            <td class="tg-0lax" style="padding:5px 5px;padding-left:410px;font-size:11px;">
                <img src="{!! public_path() . '/img/hicons/heroicons-mini/calendar.svg'!!}">
            </td>
            <td class="tg-0lax" style="text-align:right;padding:5px 5px;font-size:11px;">
                {!! ($model->collection->collection->collectionable->col_date ? $model->collection->collection->collectionable->col_date->format('d/m/Y') : 'N/A') !!}
            </td>
        </tr>
    </table>
    
        <br>
    
        <table class="tg" width="100%">
            <tr>
                <td style="border-bottom:0.3mm solid black;" rowspan="8">
                    <img src="{!! public_path() . '/img/hicons/heroicons-mini/archive-box-arrow-down.svg'!!}" width="25%">    
                </td>
            </tr>
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
                Quantidade/Unidade:
                </td>
                <td class="tg-s268" style="padding:5px 5px;font-weight:bold;font-size:11px;">
                {!! $model->collection->qty !!}
                </td>
            </tr>
            <tr>
                <td class="tg-s268" style="padding:5px 5px;font-size:11px;">
                BL / Lote:
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
        </table>
        
    