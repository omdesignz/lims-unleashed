<html>
<head>
    <style>
        @page { margin: 2mm; }
        body { font-family: 'Helvetica', sans-serif; }
        .label-box { width: 100%; height: 100%; position: relative; }
        .item-name { font-size: 8pt; font-weight: bold; line-height: 1; height: 18pt; overflow: hidden; }
        .details { font-size: 6pt; margin-top: 2mm; }
        .qr-code { position: absolute; right: 0; bottom: 0; }
    </style>
</head>
<body>
    @foreach($batches as $batch)
    <div class="label-box">
        <div class="item-name">{{ $batch->inventory->item->name }}</div>
        
        <div class="details">
            <strong>LOT:</strong> {{ $batch->batch_number }}<br>
            <strong>EXP:</strong> {{ $batch->expiry_date->format('d/m/Y') }}<br>
            <strong>BATCH ID:</strong> #{{ $batch->id }}
        </div>

        <div class="qr-code">
            <img src="{{ $batch->qr_code }}" width="45px">
        </div>
    </div>
    @if(!$loop->last) <pagebreak /> @endif
    @endforeach
</body>
</html>