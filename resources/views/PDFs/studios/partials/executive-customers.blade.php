@php
    $customers = collect($payload['top_customers'] ?? [])->values();
@endphp

<table class="report-table" width="100%" cellspacing="0" cellpadding="7" style="border-collapse: collapse; margin-top: 6px;">
    <thead>
        <tr>
            <th style="text-align:left;">Cliente</th>
            <th style="text-align:left;">Código</th>
            <th style="text-align:right;">Locais registados</th>
        </tr>
    </thead>
    <tbody>
        @forelse($customers as $customer)
            <tr>
                <td>{{ $customer['name'] ?? 'Cliente' }}</td>
                <td>{{ $customer['code'] ?: '—' }}</td>
                <td style="text-align:right;">{{ $customer['warehouses_count'] ?? 0 }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3" style="text-align:center; color:#6b7b74;">Sem clientes para apresentar neste período.</td>
            </tr>
        @endforelse
    </tbody>
</table>
