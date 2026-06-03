<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pedido #{{ $order->seq ?? $order->id }} - {{ $companyName }}</title>
    <style>
        @include('PDFs.partials.premium-document-style')

        /* ===== BASE STYLES ===== */
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #1f2937;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        /* ===== TYPOGRAPHY HIERARCHY ===== */
        h1 {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 4px 0;
        }

        h2 {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            margin: 0 0 8px 0;
        }

        h3 {
            font-size: 12px;
            font-weight: 600;
            color: #111827;
            margin: 0 0 6px 0;
        }

        .text-sm { font-size: 11px; }
        .text-xs { font-size: 10px; }
        .text-xxs { font-size: 9px; }

        .font-bold { font-weight: 700; }
        .font-semibold { font-weight: 600; }
        .font-medium { font-weight: 500; }

        /* ===== COLOR SYSTEM ===== */
        .text-gray-900 { color: #111827; }
        .text-gray-700 { color: #374151; }
        .text-gray-600 { color: #4b5563; }
        .text-gray-500 { color: #6b7280; }
        .text-blue-900 { color: #1e3a8a; }
        .text-blue-800 { color: #1e40af; }
        .text-green-500 { color: #10b981; }
        .text-yellow-500 { color: #f59e0b; }
        .text-red-600 { color: #dc2626; }
        .text-white { color: #ffffff; }

        .bg-white { background-color: #ffffff; }
        .bg-gray-50 { background-color: #f9fafb; }
        .bg-blue-50 { background-color: #eff6ff; }
        .bg-blue-900 { background-color: #1e3a8a; }
        .bg-blue-800 { background-color: #1e40af; }

        /* ===== LAYOUT UTILITIES ===== */
        .w-full { width: 100%; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .align-top { vertical-align: top; }

        .m-0 { margin: 0; }
        .mb-4 { margin-bottom: 16px; }
        .mb-8 { margin-bottom: 32px; }
        .mt-8 { margin-top: 32px; }
        .mt-12 { margin-top: 48px; }
        .mb-12 { margin-bottom: 48px; }

        .p-4 { padding: 16px; }
        .p-6 { padding: 24px; }
        .px-4 { padding-left: 16px; padding-right: 16px; }
        .px-6 { padding-left: 24px; padding-right: 24px; }
        .py-2 { padding-top: 8px; padding-bottom: 8px; }
        .py-3 { padding-top: 12px; padding-bottom: 12px; }
        .py-4 { padding-top: 16px; padding-bottom: 16px; }

        /* ===== BORDER & RADIUS ===== */
        .border { border: 1px solid #e5e7eb; }
        .border-b { border-bottom: 1px solid #e5e7eb; }
        .border-t { border-top: 1px solid #e5e7eb; }

        .rounded-lg { border-radius: 8px; }
        .rounded-xl { border-radius: 12px; }

        /* ===== SPECIAL COMPONENTS ===== */
        .document-header {
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }

        .company-info {
            text-align: center;
            padding-bottom: 16px;
        }

        .company-name {
            font-size: 16px;
            font-weight: 700;
            color: #1e3a8a;
            margin-bottom: 4px;
        }

        .company-details {
            font-size: 10px;
            color: #6b7280;
            line-height: 1.3;
        }

        .document-title {
            text-align: center;
            margin: 20px 0;
        }

        .document-subtitle {
            font-size: 11px;
            color: #6b7280;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 600;
            text-align: center;
        }

        /* ===== TABLE STYLES ===== */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .info-table th {
            text-align: left;
            padding: 8px 12px;
            background-color: #f8fafc;
            border: 1px solid #e5e7eb;
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
        }

        .info-table td {
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .info-table .label-cell {
            background-color: #f9fafb;
            font-weight: 600;
            color: #4b5563;
            width: 25%;
        }

        .info-table .value-cell {
            background-color: #ffffff;
            color: #111827;
        }

        /* Main Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 24px 0;
            font-size: 10px;
        }

        .items-table thead th {
            background-color: #1e3a8a;
            color: #ffffff;
            padding: 10px 8px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            text-align: center;
            border: 1px solid #1e40af;
        }

        .items-table tbody td {
            padding: 8px;
            border: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        .items-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .item-name {
            font-weight: 600;
            color: #111827;
        }

        .item-code {
            color: #6b7280;
            font-size: 9px;
        }

        /* Summary Table */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 24px 0;
        }

        .summary-table th {
            background-color: #f8fafc;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
            text-align: center;
        }

        .summary-table td {
            padding: 12px;
            border: 1px solid #e5e7eb;
            text-align: center;
            vertical-align: middle;
        }

        .summary-table .total-row td {
            background: linear-gradient(to right, #1e3a8a, #1e40af);
            color: #ffffff;
            font-weight: 700;
            font-size: 13px;
            padding: 15px 12px;
        }

        /* ===== FOOTER ===== */
        .footer {
            margin-top: 40px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            color: #6b7280;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .footer-table td {
            padding: 4px 0;
            vertical-align: top;
            width: 50%;
        }

        .footer-label {
            font-weight: 600;
            color: #4b5563;
        }

        .page-info {
            text-align: right;
            font-size: 9px;
            color: #9ca3af;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            margin-top: 8px;
        }

        /* ===== STATUS COLORS ===== */
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-ordered { background-color: #e0e7ff; color: #3730a3; }
        .status-partial { background-color: #ffedd5; color: #9a3412; }
        .status-received { background-color: #d1fae5; color: #065f46; }
        .status-approved { background-color: #dbeafe; color: #1e40af; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }

        /* ===== UTILITY CLASSES ===== */
        .currency {
            font-family: 'DejaVu Sans Mono', monospace;
            letter-spacing: 0.5px;
        }

        .section-header {
            background: linear-gradient(to right, #1e3a8a, #1e40af);
            color: #ffffff;
            padding: 10px 16px;
            font-size: 12px;
            font-weight: 600;
            margin: 0;
            border-radius: 6px 6px 0 0;
        }

        .section-content {
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 6px 6px;
        }
    </style>
</head>
<body class="pdf-document commercial-document">

    <!-- HEADER SECTION -->
    <table class="w-full document-header">
        <tr>
            <td class="text-center">
                <div class="company-info">
                    <div class="company-name">{{ $companyName }}</div>
                    @if($companyAddress)
                        <div class="company-details">{{ $companyAddress }}</div>
                    @endif
                    @if($companyPhone || $companyEmail)
                        <div class="company-details">
                            {{ $companyPhone }} 
                            @if($companyPhone && $companyEmail) | @endif
                            {{ $companyEmail }}
                        </div>
                    @endif
                </div>

                <div class="document-title">
                    <h1>PEDIDO DE COMPRA</h1>
                    <div class="document-subtitle">Documento #{{ $order->reference }}</div>
                </div>

                <!-- ORDER STATUS -->
                <div class="mb-8">
                    <span class="status-badge" style="
                        @if($order->status->value === 'RECEIVED') background-color: #d1fae5; color: #065f46;
                        @elseif($order->status->value === 'PARTIALLY_RECEIVED') background-color: #ffedd5; color: #9a3412;
                        @elseif($order->status->value === 'ORDERED') background-color: #e0e7ff; color: #3730a3;
                        @elseif($order->status->value === 'APPROVED') background-color: #dbeafe; color: #1e40af;
                        @elseif($order->status->value === 'PENDING') background-color: #fef3c7; color: #92400e;
                        @elseif($order->status->value === 'CANCELLED') background-color: #fee2e2; color: #991b1b;
                        @else background-color: #f3f4f6; color: #374151; @endif
                    ">
                        {{ $orderStatus }}
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <!-- ORDER INFORMATION TABLE -->
    <h2 class="section-header">INFORMAÇÕES DO PEDIDO</h2>
    <table class="info-table">
        <tr>
            <td class="label-cell">Número do Pedido</td>
            <td class="value-cell">#{{ $order->reference }}</td>
            <td class="label-cell">Data do Pedido</td>
            <td class="value-cell">{{ $orderDate }}</td>
        </tr>
        <tr>
            <td class="label-cell">Referência</td>
            <td class="value-cell">{{ $order->reference ?: 'N/A' }}</td>
            <td class="label-cell">Criado por</td>
            <td class="value-cell">{{ $order->user->name ?? 'N/A' }}</td>
        </tr>
    </table>

    <!-- SUPPLIER INFORMATION TABLE -->
    <h2 class="section-header">FORNECEDOR</h2>
    <table class="info-table">
        <tr>
            <td class="label-cell">Nome</td>
            <td class="value-cell font-semibold text-blue-900">{{ $order->supplier->name ?? 'N/A' }}</td>
        </tr>
        @if($order->supplier->address ?? false)
        <tr>
            <td class="label-cell">Endereço</td>
            <td class="value-cell">{{ $order->supplier->address }}</td>
        </tr>
        @endif
    </table>

    <!-- ITEMS TABLE SECTION -->
    <h2 class="section-header">ITENS DO PEDIDO</h2>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 32%;">Item</th>
                <th style="width: 8%;">Qtd.</th>
                <th style="width: 8%;">Recebido</th>
                <th style="width: 8%;">Pendente</th>
                <th style="width: 10%;">Armazém</th>
                <th style="width: 10%;">Preço Unit.</th>
                <th style="width: 10%;">Total</th>
                <th style="width: 10%;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $index => $item)
                @php
                    $itemStatus = $item->status instanceof \BackedEnum ? $item->status->value : (string) $item->status;
                    $statusClass = '';
                    switch($itemStatus) {
                        case 'PENDING': $statusClass = 'status-pending'; break;
                        case 'ORDERED': $statusClass = 'status-ordered'; break;
                        case 'PARTIALLY_RECEIVED': $statusClass = 'status-partial'; break;
                        case 'RECEIVED': $statusClass = 'status-received'; break;
                        case 'CANCELLED': $statusClass = 'status-cancelled'; break;
                        default: $statusClass = '';
                    }
                    
                    $statusText = '';
                    switch($itemStatus) {
                        case 'PENDING': $statusText = 'Pendente'; break;
                        case 'ORDERED': $statusText = 'Pedido'; break;
                        case 'PARTIALLY_RECEIVED': $statusText = 'Parcial'; break;
                        case 'RECEIVED': $statusText = 'Recebido'; break;
                        case 'CANCELLED': $statusText = 'Cancelado'; break;
                        default: $statusText = $itemStatus ?: 'N/A';
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <div class="item-name">{{ $item->item->name ?? 'N/A' }}</div>
                        <div class="item-code">{{ $item->item->code ?? '' }}</div>
                    </td>
                    <td class="text-center">{{ $item->qty }}</td>
                    <td class="text-center text-green-500 font-semibold">{{ $item->received_qty ?? 0 }}</td>
                    <td class="text-center text-yellow-500 font-semibold">{{ $item->pending_qty ?? $item->qty }}</td>
                    <td class="text-center">{{ $item->warehouse->name ?? 'N/A' }}</td>
                    <td class="text-right currency">{{ number_format($item->unit_price, 2, ',', ' ') }} {{ $item->currency }}</td>
                    <td class="text-right font-semibold currency">{{ number_format($item->total_price, 2, ',', ' ') }} {{ $item->currency }}</td>
                    <td class="text-center">
                        <span class="status-badge {{ $statusClass }}">{{ $statusText }}</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- SUMMARY SECTION -->
    <h2 class="section-header">RESUMO DO PEDIDO</h2>
    <table class="summary-table">
        <thead>
            <tr>
                <th style="width: 25%;">Total Itens</th>
                <th style="width: 25%;">Quantidade Total</th>
                <th style="width: 25%;">Recebido</th>
                <th style="width: 25%;">Pendente</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-sm font-semibold">{{ $totalItems }}</td>
                <td class="text-sm font-semibold">{{ $totalQty }}</td>
                <td class="text-sm font-semibold text-green-500">{{ $totalReceived }}</td>
                <td class="text-sm font-semibold text-yellow-500">{{ $totalPending }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="4" class="text-white">
                    VALOR TOTAL DO PEDIDO: 
                    <span class="currency">{{ number_format($totalAmount, 2, ',', ' ') }} {{ $order->currency }}</span>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- OBSERVATIONS -->
    @if($order->obs)
    <h2 class="section-header">OBSERVAÇÕES</h2>
    <div class="section-content">
        <table class="w-full">
            <tr>
                <td class="p-4 bg-gray-50 rounded-lg border">
                    <div class="text-sm text-gray-700" style="white-space: pre-line;">{{ $order->obs }}</div>
                </td>
            </tr>
        </table>
    </div>
    @endif

    <!-- FOOTER -->
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td>
                    <div class="footer-label">Gerado em:</div>
                    <div>{{ $printedDate }}</div>
                </td>
                <td class="text-right">
                    <div class="footer-label">Documento:</div>
                    <div>Pedido #{{ $order->seq ?? $order->id }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="footer-label">Por:</div>
                    <div>{{ $printedBy }}</div>
                </td>
                <td class="text-right">
                    <div class="footer-label">Criado em:</div>
                    <div>{{ $createdDate }}</div>
                </td>
            </tr>
        </table>
        <div class="page-info">
            Página {PAGENO} de {nbpg}
        </div>
    </div>

</body>
</html>
