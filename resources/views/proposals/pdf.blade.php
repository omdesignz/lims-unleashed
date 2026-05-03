<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Proposal {{ $proposal->proposal_number }}</title>
    <style>
        @page {
            margin: 20px;
            margin-top: 50px;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }
        
        .header {
            position: fixed;
            top: -20px;
            left: 0;
            right: 0;
            height: 50px;
            text-align: center;
            border-bottom: 2px solid #1e3a8a;
            margin-bottom: 20px;
        }
        
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .footer {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 30px;
            text-align: center;
            border-top: 1px solid #ccc;
            font-size: 8pt;
            color: #666;
        }
        
        .page-number:after {
            content: counter(page);
        }
        
        .company-info {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        
        .company-info td {
            vertical-align: top;
            padding: 5px;
        }
        
        .logo-cell {
            width: 150px;
        }
        
        .company-logo {
            max-width: 120px;
            max-height: 60px;
        }
        
        .proposal-header {
            background: #1e3a8a;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .proposal-header h1 {
            margin: 0;
            font-size: 18pt;
            font-weight: bold;
        }
        
        .proposal-header p {
            margin: 5px 0 0 0;
            font-size: 10pt;
            opacity: 0.9;
        }
        
        .info-grid {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }
        
        .info-grid td {
            padding: 8px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
        }
        
        .info-grid .section-header {
            background: #f8fafc;
            font-weight: bold;
            color: #1e3a8a;
        }
        
        .info-label {
            font-weight: bold;
            color: #4b5563;
            width: 40%;
        }
        
        .info-value {
            color: #111827;
        }
        
        .items-table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        
        .items-table th {
            background: #1e3a8a;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 9pt;
        }
        
        .items-table td {
            padding: 8px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
            font-size: 9pt;
        }
        
        .items-table .item-number {
            width: 30px;
            text-align: center;
            font-weight: bold;
        }
        
        .items-table .item-description {
            width: 35%;
        }
        
        .items-table .item-standard {
            width: 10%;
        }
        
        .items-table .item-qty {
            width: 8%;
            text-align: center;
        }
        
        .items-table .item-unit {
            width: 8%;
            text-align: center;
        }
        
        .items-table .item-price {
            width: 10%;
            text-align: right;
        }
        
        .items-table .item-discount {
            width: 10%;
            text-align: right;
        }
        
        .items-table .item-tax {
            width: 10%;
            text-align: right;
        }
        
        .items-table .item-total {
            width: 10%;
            text-align: right;
            font-weight: bold;
        }
        
        .item-notes {
            font-size: 8pt;
            color: #6b7280;
            margin-top: 2px;
        }
        
        .item-tax-info {
            font-size: 8pt;
            color: #3b82f6;
        }
        
        .item-discount-info {
            font-size: 8pt;
            color: #10b981;
        }
        
        .totals-table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }
        
        .totals-table td {
            padding: 8px;
            border: none;
        }
        
        .totals-table .label {
            text-align: right;
            font-weight: bold;
            color: #4b5563;
        }
        
        .totals-table .amount {
            text-align: right;
            width: 150px;
        }
        
        .totals-table .subtotal {
            border-top: 1px solid #e5e7eb;
            padding-top: 12px;
        }
        
        .totals-table .total-row {
            border-top: 2px solid #1e3a8a;
            font-weight: bold;
            font-size: 11pt;
            padding-top: 12px;
        }
        
        .totals-table .discount {
            color: #10b981;
        }
        
        .totals-table .tax {
            color: #3b82f6;
        }
        
        .totals-table .grand-total {
            color: #1e3a8a;
            font-size: 12pt;
        }
        
        .template-content {
            margin: 20px 0;
            padding: 15px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            font-size: 10pt;
            line-height: 1.6;
        }
        
        .observations {
            margin: 20px 0;
            padding: 15px;
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 5px;
            font-size: 10pt;
        }
        
        .compliance-section {
            margin: 30px 0;
            padding: 15px;
            border: 2px solid #1e3a8a;
            border-radius: 5px;
            page-break-inside: avoid;
        }
        
        .compliance-header {
            background: #1e3a8a;
            color: white;
            padding: 10px;
            margin: -15px -15px 15px -15px;
            border-radius: 3px 3px 0 0;
            font-weight: bold;
        }
        
        .compliance-checkbox {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #1e3a8a;
            border-radius: 3px;
            margin-right: 10px;
            vertical-align: middle;
        }
        
        .compliance-checkbox.checked {
            background: #1e3a8a;
            position: relative;
        }
        
        .compliance-checkbox.checked:after {
            content: "✓";
            color: white;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 12pt;
        }
        
        .compliance-item {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .signature-section {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #1e3a8a;
        }
        
        .signature-line {
            width: 300px;
            border-bottom: 1px solid #333;
            margin: 40px 0 5px 0;
        }
        
        .signature-label {
            font-size: 9pt;
            color: #666;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 8pt;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-sent { background: #dbeafe; color: #1e40af; }
        .status-viewed { background: #e0e7ff; color: #3730a3; }
        .status-accepted { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .status-revised { background: #ffedd5; color: #9a3412; }
        .status-expired { background: #f3f4f6; color: #374151; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .text-blue { color: #1e3a8a; }
        .text-green { color: #10b981; }
        .text-red { color: #ef4444; }
        .bg-gray-50 { background: #f9fafb; }
        .mt-10 { margin-top: 10px; }
        .mt-20 { margin-top: 20px; }
        .mb-10 { margin-bottom: 10px; }
        .mb-20 { margin-bottom: 20px; }
        .p-10 { padding: 10px; }
        
        .page-break {
            page-break-before: always;
        }
        
        .no-break {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 150px; text-align: left;">
                    @if(config('app.company_logo'))
                        <img src="{{ config('app.company_logo') }}" class="company-logo" alt="Company Logo">
                    @else
                        <div style="font-weight: bold; font-size: 14pt; color: #1e3a8a;">
                            {{ config('app.company_name', 'Laboratory Name') }}
                        </div>
                    @endif
                </td>
                <td style="text-align: center; vertical-align: middle;">
                    <div style="font-size: 14pt; font-weight: bold; color: #1e3a8a;">
                        PROPOSTA DE SERVIÇO
                    </div>
                </td>
                <td style="width: 150px; text-align: right; vertical-align: middle;">
                    <div style="font-size: 9pt; color: #666;">
                        {{ date('d/m/Y H:i') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- FOOTER -->
    <div class="footer">
        <table width="100%">
            <tr>
                <td style="text-align: left; width: 33%;">
                    {{ config('app.company_name') }} • Página <span class="page-number"></span>
                </td>
                <td style="text-align: center; width: 34%;">
                    {{ config('app.company_phone') }} • {{ config('app.company_email') }}
                </td>
                <td style="text-align: right; width: 33%;">
                    {{ config('app.company_address') }}
                </td>
            </tr>
        </table>
    </div>
    
    <!-- CONTENT START -->
    <div style="margin-top: 60px;">
        
        <!-- PROPOSAL HEADER -->
        <div class="proposal-header">
            <table width="100%">
                <tr>
                    <td>
                        <h1>PROPOSTA {{ $proposal->proposal_number }}</h1>
                        <p>
                            Cliente: {{ $proposal->customer?->name ?? null }} • 
                            Data: {{ $proposal->created_at?->format('d/m/Y') ?? null }} • 
                            Status: 
                            <span class="status-badge status-{{ strtolower($proposal->status) }}">
                                {{ $proposal->status_badge['text'] }}
                            </span>
                        </p>
                        @if(!$proposal->is_original)
                            <p style="font-size: 9pt; background: #fef3c7; color: #92400e; padding: 3px 8px; border-radius: 3px; display: inline-block;">
                                ⚠️ Esta é uma revisão da proposta original
                            </p>
                        @endif
                    </td>
                    <td style="text-align: right; width: 200px;">
                        <div style="font-size: 16pt; font-weight: bold; color: white;">
                            {{ number_format($proposal->total, 2, ',', '.') }} R$
                        </div>
                        <div style="font-size: 9pt; opacity: 0.8;">
                            VALOR TOTAL
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- COMPANY & PROPOSAL INFO -->
        <table class="info-grid">
            <tr>
                <td colspan="4" class="section-header">INFORMAÇÕES DA EMPRESA</td>
            </tr>
            <tr>
                <td class="info-label">Empresa</td>
                <td class="info-value">{{ config('app.company_name') }}</td>
                <td class="info-label">CNPJ</td>
                <td class="info-value">{{ config('app.company_cnpj', '00.000.000/0000-00') }}</td>
            </tr>
            <tr>
                <td class="info-label">Endereço</td>
                <td class="info-value">{{ config('app.company_address') }}</td>
                <td class="info-label">Telefone</td>
                <td class="info-value">{{ config('app.company_phone') }}</td>
            </tr>
            <tr>
                <td class="info-label">E-mail</td>
                <td class="info-value">{{ config('app.company_email') }}</td>
                <td class="info-label">Site</td>
                <td class="info-value">{{ config('app.company_website', 'www.example.com') }}</td>
            </tr>
        </table>
        
        <table class="info-grid mt-20">
            <tr>
                <td colspan="4" class="section-header">INFORMAÇÕES DA PROPOSTA</td>
            </tr>
            <tr>
                <td class="info-label">Cliente</td>
                <td class="info-value">{{ $proposal->customer?->name }} ({{ $proposal->customer?->code }})</td>
                <td class="info-label">Local de Serviço</td>
                <td class="info-value">{{ $proposal->service_location }}</td>
            </tr>
            <tr>
                <td class="info-label">Departamento</td>
                <td class="info-value">{{ $proposal->department?->name ?? 'Não especificado' }}</td>
                <td class="info-label">Armazém</td>
                <td class="info-value">{{ $proposal->warehouse?->address ?? 'Não especificado' }}</td>
            </tr>
            <tr>
                <td class="info-label">Dias de Tolerância</td>
                <td class="info-value">{{ $proposal->tolerance_days }} dias</td>
                <td class="info-label">Validade</td>
                <td class="info-value">
                    @if($proposal->expiry_date)
                        {{ $proposal->expiry_date?->format('d/m/Y') ?? null }}
                        ({{ $proposal->days_until_expiry }} dias restantes)
                    @else
                        Não definida
                    @endif
                </td>
            </tr>
            <tr>
                <td class="info-label">Modo de Precificação</td>
                <td class="info-value">
                    {{ $proposal->use_matrix_price ? 'Preço de Matriz' : 'Preço de Parâmetro' }}
                </td>
                <td class="info-label">Reter Imposto</td>
                <td class="info-value">{{ $proposal->withhold_tax ? 'Sim' : 'Não' }}</td>
            </tr>
            <tr>
                <td class="info-label">Criado por</td>
                <td class="info-value">{{ $proposal->user->name ?? 'Sistema' }}</td>
                <td class="info-label">Data de Criação</td>
                <td class="info-value">{{ $proposal->created_at?->format('d/m/Y H:i') ?? null }}</td>
            </tr>
        </table>
        
        <!-- TEMPLATE CONTENT -->
        @if($proposal->template && $proposal->template->content)
            <div class="page-break"></div>
            <div class="compliance-section no-break">
                <div class="compliance-header">
                    {{ $proposal->template->name }}
                </div>
                <div class="template-content">
                    {{-- {!! $proposal->template->content !!} --}}
                    {!! $parsedContent !!}
                </div>
            </div>
        @endif
        
        <!-- ITEMS TABLE -->
        <div class="page-break"></div>
        <div class="no-break">
            <h2 style="color: #1e3a8a; border-bottom: 2px solid #1e3a8a; padding-bottom: 5px; margin-top: 30px;">
                ITENS DE SERVIÇO
            </h2>
            
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="item-number">#</th>
                        <th class="item-description">DESCRIÇÃO DO ITEM</th>
                        <th class="item-standard">PADRÃO</th>
                        <th class="item-qty">QTDE</th>
                        <th class="item-unit">UNID.</th>
                        <th class="item-price">PREÇO UNIT.</th>
                        <th class="item-discount">DESCONTO</th>
                        <th class="item-tax">TAXA</th>
                        <th class="item-total">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($proposal->items as $index => $item)
                        <tr>
                            <td class="item-number">{{ $index + 1 }}</td>
                            <td class="item-description">
                                <div class="text-bold">{{ $item->item_description }}</div>
                                @if($item->obs)
                                    <div class="item-notes">{{ $item->obs }}</div>
                                @endif
                                @if($item->itemable_type)
                                    <div class="item-notes">
                                        {{ $item->itemable_type == 'App\\Models\\Matrix' ? 'Matriz' : 'Parâmetro' }} #{{ $item->itemable_id }}
                                    </div>
                                @endif
                                @if(!$item->charge_tax)
                                    <div class="item-notes text-green">✓ Isento de taxa</div>
                                @endif
                                @if($item->exemption_code)
                                    <div class="item-notes text-green">Código isenção: {{ $item->exemption_code }}</div>
                                @endif
                            </td>
                            <td class="item-standard">
                                {{ $item->standard->code ?? '-' }}
                            </td>
                            <td class="item-qty">
                                {{ number_format($item->qty, 2, ',', '.') }}
                            </td>
                            <td class="item-unit">
                                {{ $item->unit->code ?? '-' }}
                            </td>
                            <td class="item-price text-right">
                                AOA {{ number_format($item->unit_price, 2, ',', '.') }}
                            </td>
                            <td class="item-discount text-right">
                                @if($item->discount_amount > 0)
                                    <div class="item-discount-info">
                                        @if($item->discount_id == 1)
                                            {{ number_format($item->discount_percentage, 2, ',', '.') }}%
                                        @endif
                                    </div>
                                    -AOA {{ number_format($item->discount_amount, 2, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="item-tax text-right">
                                @if($item->tax_amount > 0)
                                    <div class="item-tax-info">
                                        {{ number_format($item->tax_percentage, 2, ',', '.') }}%
                                    </div>
                                    +AOA {{ number_format($item->tax_amount, 2, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="item-total text-right text-bold">
                                AOA {{ number_format($item->total, 2, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
            <!-- TOTALS -->
            <table class="totals-table" style="float: right; width: 300px;">
                <tr>
                    <td class="label">Subtotal:</td>
                    <td class="amount">AOA {{ number_format($proposal->sub_total, 2, ',', '.') }}</td>
                </tr>
                
                @if($proposal->discount > 0)
                    <tr>
                        <td class="label text-green">Desconto Total:</td>
                        <td class="amount text-green">-AOA {{ number_format($proposal->discount, 2, ',', '.') }}</td>
                    </tr>
                @endif
                
                @if($proposal->tax > 0)
                    <tr>
                        <td class="label">Taxa Total:</td>
                        <td class="amount">+AOA {{ number_format($proposal->tax, 2, ',', '.') }}</td>
                    </tr>
                @endif
                
                @if($proposal->global_discount_amount > 0)
                    <tr>
                        <td class="label text-green">Desconto Global:</td>
                        <td class="amount text-green">-AOA {{ number_format($proposal->global_discount_amount, 2, ',', '.') }}</td>
                    </tr>
                @endif
                
                @if($proposal->withholding_tax_amount > 0)
                    <tr>
                        <td class="label">Imposto Retido:</td>
                        <td class="amount">AOA {{ number_format($proposal->withholding_tax_amount, 2, ',', '.') }}</td>
                    </tr>
                @endif
                
                <tr class="total-row">
                    <td class="label grand-total">TOTAL GERAL:</td>
                    <td class="amount grand-total">AOA {{ number_format($proposal->total, 2, ',', '.') }}</td>
                </tr>
                
                <tr>
                    <td colspan="2" style="text-align: right; font-size: 8pt; color: #666; padding-top: 5px;">
                        {{ count($proposal->items) }} itens • 
                        {{ $proposal->items->where('tax_amount', '>', 0)->count() }} tributáveis
                    </td>
                </tr>
            </table>
            
            <div style="clear: both;"></div>
        </div>
        
        <!-- OBSERVATIONS -->
        @if($proposal->obs)
            <div class="page-break"></div>
            <div class="observations no-break">
                <h3 style="color: #92400e; margin-top: 0;">OBSERVAÇÕES</h3>
                <p style="white-space: pre-wrap;">{{ $proposal->obs }}</p>
            </div>
        @endif
        
        <!-- COMPLIANCE AGREEMENT -->
        <div class="page-break"></div>
        <div class="compliance-section no-break">
            <div class="compliance-header">
                ACORDO DE CONFORMIDADE
            </div>
            
            <div style="margin-bottom: 20px;">
                <p>Para aceitar esta proposta, o cliente deve concordar com os seguintes termos:</p>
            </div>
            
            <div class="compliance-item">
                <div style="margin-bottom: 5px;">
                    @if($proposal->compliance_agreement && $proposal->compliance_agreement->confidentiality)
                        <span class="compliance-checkbox checked"></span>
                    @else
                        <span class="compliance-checkbox"></span>
                    @endif
                    <span class="text-bold">Confidencialidade</span>
                </div>
                <p style="margin-left: 30px; font-size: 9pt;">
                    Concordo em manter confidencialidade sobre todas as informações técnicas, processos e resultados 
                    fornecidos pelo laboratório, não divulgando tais informações a terceiros sem autorização prévia por escrito.
                </p>
            </div>
            
            <div class="compliance-item">
                <div style="margin-bottom: 5px;">
                    @if($proposal->compliance_agreement && $proposal->compliance_agreement->impartiality)
                        <span class="compliance-checkbox checked"></span>
                    @else
                        <span class="compliance-checkbox"></span>
                    @endif
                    <span class="text-bold">Imparcialidade</span>
                </div>
                <p style="margin-left: 30px; font-size: 9pt;">
                    Declaro que esta proposta foi elaborada de forma imparcial, sem conflitos de interesse que possam 
                    comprometer a qualidade do serviço prestado.
                </p>
            </div>
            
            <div class="compliance-item">
                <div style="margin-bottom: 5px;">
                    @if($proposal->compliance_agreement && $proposal->compliance_agreement->nondisclosure)
                        <span class="compliance-checkbox checked"></span>
                    @else
                        <span class="compliance-checkbox"></span>
                    @endif
                    <span class="text-bold">Não Divulgação</span>
                </div>
                <p style="margin-left: 30px; font-size: 9pt;">
                    Concordo em não divulgar informações confidenciais a terceiros sem autorização prévia por escrito 
                    da empresa prestadora do serviço.
                </p>
            </div>
            
            @if($proposal->compliance_agreement && $proposal->compliance_agreement->acknowledged_at)
                <div style="margin-top: 30px; padding: 15px; background: #d1fae5; border-radius: 5px;">
                    <p class="text-bold" style="color: #065f46; margin: 0;">
                        ✓ PROPOSTA ACEITA
                    </p>
                    <p style="color: #065f46; margin: 5px 0 0 0; font-size: 9pt;">
                        Aceita em: {{ $proposal->compliance_agreement->acknowledged_at->format('d/m/Y H:i') }}
                        @if($proposal->compliance_agreement->client_ip)
                            • IP: {{ $proposal->compliance_agreement->client_ip }}
                        @endif
                    </p>
                </div>
            @endif
        </div>
        
        <!-- SIGNATURES -->
        <div class="signature-section no-break">
            <table width="100%">
                <tr>
                    <td width="50%" style="vertical-align: top;">
                        <div style="text-align: center;">
                            <div class="signature-line"></div>
                            <div class="signature-label">Assinatura do Representante do Laboratório</div>
                            <div style="margin-top: 10px;">
                                <p style="margin: 5px 0;">{{ $proposal->user->name ?? 'Representante' }}</p>
                                <p style="margin: 0; font-size: 9pt; color: #666;">{{ config('app.company_name') }}</p>
                            </div>
                        </div>
                    </td>
                    <td width="50%" style="vertical-align: top;">
                        <div style="text-align: center;">
                            <div class="signature-line"></div>
                            <div class="signature-label">Assinatura do Cliente</div>
                            <div style="margin-top: 10px;">
                                <p style="margin: 5px 0;">{{ $proposal->customer?->name ?? null }}</p>
                                <p style="margin: 0; font-size: 9pt; color: #666;">Cliente</p>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- SUMMARY -->
        <div style="margin-top: 50px; padding: 15px; background: #f8fafc; border-radius: 5px; font-size: 9pt;">
            <table width="100%">
                <tr>
                    <td width="33%" style="vertical-align: top;">
                        <p class="text-bold" style="margin: 0 0 5px 0;">Informações da Proposta:</p>
                        <p style="margin: 0;">Número: {{ $proposal->proposal_number }}</p>
                        <p style="margin: 0;">Status: {{ $proposal->status_badge['text'] }}</p>
                        <p style="margin: 0;">Validade: {{ $proposal->expiry_date ? $proposal->expiry_date->format('d/m/Y') : 'Não definida' }}</p>
                    </td>
                    <td width="33%" style="vertical-align: top;">
                        <p class="text-bold" style="margin: 0 0 5px 0;">Informações do Cliente:</p>
                        <p style="margin: 0;">{{ $proposal->customer?->name }}</p>
                        <p style="margin: 0;">Código: {{ $proposal->customer?->code }}</p>
                        <p style="margin: 0;">Local: {{ $proposal->service_location }}</p>
                    </td>
                    <td width="33%" style="vertical-align: top;">
                        <p class="text-bold" style="margin: 0 0 5px 0;">Resumo Financeiro:</p>
                        <p style="margin: 0;">Subtotal: AOA {{ number_format($proposal->sub_total, 2, ',', '.') }}</p>
                        <p style="margin: 0;">Desconto: AOA {{ number_format($proposal->discount, 2, ',', '.') }}</p>
                        <p style="margin: 0;">Taxa: AOA {{ number_format($proposal->tax, 2, ',', '.') }}</p>
                        <p style="margin: 0;" class="text-bold">Total: AOA {{ number_format($proposal->total, 2, ',', '.') }}</p>
                    </td>
                </tr>
            </table>
        </div>
        
    </div>
    <!-- CONTENT END -->
    
</body>
</html>