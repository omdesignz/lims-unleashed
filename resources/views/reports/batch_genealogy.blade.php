<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Genealogia do Lote {{ $batch->batch_number }}</title>
    <style>
        @include('PDFs.partials.premium-document-style')

        @page {
            margin: 18mm 14mm 16mm 14mm;
        }

        body {
            font-size: 10.5px;
        }

        .report-shell {
            border: 0.25mm solid #d9e2ef;
            border-radius: 5mm;
            padding: 7mm;
        }

        .report-heading {
            margin-bottom: 6mm;
            padding-bottom: 4mm;
            border-bottom: 0.35mm solid #102a43;
        }
    </style>
</head>
<body class="pdf-document report-document">
    <div class="report-shell keep-together">
        <div class="report-heading">
            <div class="bilingual-label">Inventory batch genealogy</div>
            <h1 class="document-title">Genealogia do Lote {{ $batch->batch_number }}</h1>
        </div>

        <table class="report-table">
            <thead>
                <tr>
                    <th>Data<br><span class="bilingual-label">Date</span></th>
                    <th>Utilizador<br><span class="bilingual-label">User</span></th>
                    <th>Ação<br><span class="bilingual-label">Action</span></th>
                    <th>Variação<br><span class="bilingual-label">Qty change</span></th>
                    <th>Notas<br><span class="bilingual-label">Notes</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($batch->transactions as $tx)
                    <tr>
                        <td>{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $tx->user->name }}</td>
                        <td>{{ $tx->type->name }}</td>
                        <td>{{ $tx->qty }}</td>
                        <td>{{ $tx->reason }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
