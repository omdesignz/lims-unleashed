{{-- resources/views/pdf/sample-entry.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sample Entry Certificate - {{ $sample->code }}</title>
    <style>
        @page {
            margin: 20px;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            border-bottom: 3px solid #1e3a8a;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo h1 {
            color: #1e3a8a;
            font-size: 24px;
            margin: 0;
        }
        .certificate-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 30px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            background-color: #1e3a8a;
            color: white;
            padding: 8px 15px;
            font-weight: bold;
            font-size: 14px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 10px;
        }
        .info-item {
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            color: #4b5563;
        }
        .info-value {
            color: #111827;
            border-bottom: 1px dotted #d1d5db;
            padding-bottom: 3px;
        }
        .footer {
            margin-top: 50px;
            padding-top: 15px;
            border-top: 1px solid #d1d5db;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
        }
        .signature-area {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #d1d5db;
        }
        .signature-line {
            width: 200px;
            border-bottom: 1px solid #333;
            margin: 0 auto;
            padding-top: 40px;
        }
        .signature-label {
            text-align: center;
            font-size: 11px;
            color: #6b7280;
            margin-top: 5px;
        }
        .qr-code {
            position: absolute;
            right: 20px;
            top: 20px;
            width: 80px;
            height: 80px;
            background-color: #f3f4f6;
            padding: 5px;
            border: 1px solid #d1d5db;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            margin-left: 10px;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-in-progress {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
        }
        .barcode {
            text-align: center;
            margin: 20px 0;
            font-family: 'Libre Barcode 128', cursive;
            font-size: 36px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <h1>LABORATORY INFORMATION MANAGEMENT SYSTEM</h1>
            <p>Sample Entry Certificate</p>
        </div>
        
        <div class="certificate-title">
            SAMPLE ENTRY #{{ $sample->code }}
            <span class="status-badge status-{{ strtolower(str_replace(' ', '-', $sample->status)) }}">
                {{ $sample->status }}
            </span>
        </div>
    </div>

    <div class="barcode">
        *{{ $sample->code }}*
    </div>

    <div class="section">
        <div class="section-title">Sample Information</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Sample Name:</div>
                <div class="info-value">{{ $sample->name }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Sample Type:</div>
                <div class="info-value">{{ $sample->sample_type }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Sample Code:</div>
                <div class="info-value">{{ $sample->code }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Received Date:</div>
                <div class="info-value">{{ $sample->received_at ? $sample->received_at->format('d/m/Y H:i') : 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Status:</div>
                <div class="info-value">{{ $sample->status }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Customer Information</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Customer:</div>
                <div class="info-value">{{ $sample->customer->name ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Customer Code:</div>
                <div class="info-value">{{ $sample->customer->code ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Laboratory Information</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Laboratory:</div>
                <div class="info-value">{{ $sample->lab->name ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Department:</div>
                <div class="info-value">{{ $sample->department->name ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Warehouse:</div>
                <div class="info-value">{{ $sample->warehouse->name ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Packaging:</div>
                <div class="info-value">{{ $sample->packaging->name ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Analysis Timeline</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Analysis Start:</div>
                <div class="info-value">{{ $sample->analysis_start_date ? $sample->analysis_start_date->format('d/m/Y H:i') : 'Not Started' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Analysis End:</div>
                <div class="info-value">{{ $sample->analysis_end_date ? $sample->analysis_end_date->format('d/m/Y H:i') : 'Not Completed' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Collected by Lab:</div>
                <div class="info-value">{{ $sample->collected_by_lab ? 'Yes' : 'No' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Collection Date:</div>
                <div class="info-value">{{ $sample->collected_at ? $sample->collected_at->format('d/m/Y H:i') : 'N/A' }}</div>
            </div>
        </div>
    </div>

    @if($sample->requested_services)
    <div class="section">
        <div class="section-title">Requested Services</div>
        <div class="info-item">
            <div class="info-value">{{ $sample->requested_services }}</div>
        </div>
    </div>
    @endif

    @php
        $intakeData = collect($sample->client_submitted_info ?? []);
        $resolvedProfiles = collect($intakeData->get('resolved_profiles', []));
        $requiredParameters = collect($intakeData->get('required_parameters', []));
        $conditioningLabels = [
            'accepted' => 'Accepted',
            'restricted' => 'Accepted with restrictions',
            'rejected' => 'Rejected / quarantine',
        ];
    @endphp

    @if($resolvedProfiles->isNotEmpty() || $requiredParameters->isNotEmpty())
    <div class="section">
        <div class="section-title">Planned Analytical Scope</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Resolved Profiles:</div>
                <div class="info-value">
                    {{ $resolvedProfiles->pluck('name')->implode(', ') ?: 'N/A' }}
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Required Parameters:</div>
                <div class="info-value">{{ $requiredParameters->count() }}</div>
            </div>
        </div>
        @if($requiredParameters->isNotEmpty())
        <div class="info-item" style="margin-top: 10px;">
            <div class="info-label">Parameter Checklist:</div>
            <div class="info-value">
                {{ $requiredParameters->map(fn ($parameter) => ($parameter['code'] ?? 'N/A') . ' - ' . ($parameter['name'] ?? '')) ->implode('; ') }}
            </div>
        </div>
        @endif
    </div>
    @endif

    @if(
        $intakeData->get('conditioning_status')
        || $intakeData->get('packaging_condition')
        || $intakeData->get('temperature_condition')
        || $intakeData->get('integrity_observations')
        || $intakeData->get('chain_of_custody_notes')
    )
    <div class="section">
        <div class="section-title">Reception & Conditioning Assessment</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Acceptance Decision:</div>
                <div class="info-value">{{ $conditioningLabels[$intakeData->get('conditioning_status')] ?? 'Not evaluated' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Packaging Condition:</div>
                <div class="info-value">{{ $intakeData->get('packaging_condition') ?: 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Thermal Condition:</div>
                <div class="info-value">{{ $intakeData->get('temperature_condition') ?: 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Integrity Notes:</div>
                <div class="info-value">{{ $intakeData->get('integrity_observations') ?: 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Chain of Custody:</div>
                <div class="info-value">{{ $intakeData->get('chain_of_custody_notes') ?: 'N/A' }}</div>
            </div>
        </div>
    </div>
    @endif

    @if($sample->obs)
    <div class="section">
        <div class="section-title">Observations</div>
        <div class="info-item">
            <div class="info-value">{{ $sample->obs }}</div>
        </div>
    </div>
    @endif

    <div class="signature-area">
        <div class="signature-line"></div>
        <div class="signature-label">Received by: {{ $sample->received_by_label ?? 'N/A' }}</div>
    </div>

    <div class="footer">
        <p>Document generated on {{ $date }} at {{ $time }}</p>
        <p>Certificate ID: {{ strtoupper(uniqid('CERT-')) }}</p>
        <p>This is a computer-generated document. No signature is required.</p>
    </div>
</body>
</html>
