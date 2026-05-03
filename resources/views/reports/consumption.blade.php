<!DOCTYPE html>
<html>
<head>
    <style>
        @page {
            header: page-header;
            footer: page-footer;
            margin-top: 120px; /* Space for the header */
        }
        body { font-family: 'DejaVu Sans', sans-serif; color: #333; }
        
        #page-header { position: absolute; top: 0; width: 100%; border-bottom: 2px solid #1e3a8a; }
        .company-name { font-size: 20px; font-weight: bold; color: #1e3a8a; }
        
        .stats-table { width: 100%; margin-bottom: 20px; border-spacing: 10px; }
        .stat-card { background: #f1f5f9; padding: 15px; border-radius: 8px; text-align: center; }
        .stat-val { font-size: 20px; font-weight: bold; color: #1e3a8a; display: block; }
        .stat-label { font-size: 10px; color: #64748b; text-transform: uppercase; }

        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { background: #1e3a8a; color: white; padding: 10px; font-size: 11px; text-align: left; }
        .data-table td { padding: 8px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
        
        .text-right { text-align: right; }
        .footer-text { font-size: 9px; text-align: center; color: #94a3b8; }
    </style>
</head>
<body>

    <htmlpageheader name="page-header">
        <div id="page-header">
            <table width="100%">
                <tr>
                    <td class="company-name">VAP LABS INVENTORY</td>
                    <td align="right">
                        <span style="font-weight: bold;">CONSUMPTION REPORT</span><br>
                        {{ $dateRange['start']->format('d M Y') }} - {{ $dateRange['end']->format('d M Y') }}
                    </td>
                </tr>
            </table>
        </div>
    </htmlpageheader>

    <htmlpagefooter name="page-footer">
        <div class="footer-text">
            Generated on {{ date('Y-m-d H:i') }} | Page {PAGENO} of {nbpg}
        </div>
    </htmlpagefooter>

    <table class="stats-table">
        <tr>
            <td class="stat-card">
                <span class="stat-label">Total Consumption</span>
                <span class="stat-val">{{ number_format($data['metrics']['totalConsumption'], 2) }}</span>
            </td>
            <td class="stat-card">
                <span class="stat-label">Daily Average</span>
                <span class="stat-val">{{ number_format($data['metrics']['dailyAverage'], 2) }}</span>
            </td>
            <td class="stat-card">
                <span class="stat-label">Inventory Value</span>
                <span class="stat-val">${{ number_format($data['metrics']['inventoryValue'], 2) }}</span>
            </td>
        </tr>
    </table>

    <h3>Top Reagents Usage</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Reagent Name</th>
                <th class="text-right">Quantity Consumed</th>
                <th class="text-right">% of Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['topItems'] as $item)
            <tr>
                <td>{{ $item['name'] }}</td>
                <td class="text-right">{{ number_format($item['consumption'], 2) }}</td>
                <td class="text-right">
                    {{ $data['metrics']['totalConsumption'] > 0 
                        ? number_format(($item['consumption'] / $data['metrics']['totalConsumption']) * 100, 1) 
                        : 0 }}%
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>